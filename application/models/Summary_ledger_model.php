<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Summary Ledger Import Model
 *
 * Memproses Excel "Journal Import" dari software akuntansi China,
 * memetakan setiap voucher ke journal_header + journal_details (status Draft).
 *
 * Format kolom Excel (header di row 1):
 *   A Date | B FY | C FP | D Voucher Category | E Voucher No.
 *   F Description | G Account Code | H Full Account Name | I Currency
 *   J Amount (Original Currency) | K Debit Amount | L Credit Amount
 *   M Prepared by | N Approved by | O Post | P Cashier | Q Attachment Qty
 *   R Source System | S Business Type | T Approving Status | U Voiding Status
 *
 * Aturan parsing:
 *   - Baris di mana kolom A (Date) terisi tanggal = HEADER voucher baru.
 *     Baris ini juga menjadi DETAIL pertama voucher.
 *   - Baris berikutnya tanpa Date = detail lanjutan voucher saat ini.
 *   - Voucher unik di-key dengan Voucher Category + FY + FP + Voucher No.
 *   - Baris dengan nilai 'Total' di kolom A di-skip.
 *   - Jika ada detail invalid (akun tidak ada / debit-kredit invalid /
 *     total tidak balance), seluruh voucher di-skip dan dicatat di error.
 */
class Summary_ledger_model extends CI_Model {

    public function get_recent_imports($limit = 50)
    {
        $this->db->select('sli.*, u.fullname AS uploader_name');
        $this->db->from('summary_ledger_imports sli');
        $this->db->join('users u', 'sli.uploaded_by = u.id', 'left');
        $this->db->order_by('sli.uploaded_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get()->result();
    }

    /**
     * Map account_code -> account row (id, currency, is_header, status).
     * Hanya akun postable (is_header=0, status=active).
     */
    public function build_account_map()
    {
        $this->db->select('id, account_code, account_name, currency');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $rows = $this->db->get('chart_of_accounts')->result();

        $map = [];
        foreach ($rows as $r) {
            $map[trim((string) $r->account_code)] = $r;
        }
        return $map;
    }

    /**
     * Generate nomor jurnal yang aman terhadap duplikat.
     * Format dasar: {VC}/{FY}{FP:02}/{No:05d}
     * Bila sudah ada, tambah suffix -A, -B, dst.
     */
    private function generate_journal_no($voucher_category, $fy, $fp, $voucher_no)
    {
        $vc      = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $voucher_category));
        if ($vc === '') $vc = 'IMP';
        $fy      = (int) $fy;
        $fp      = (int) $fp;
        $voucher = trim((string) $voucher_no);
        $voucher_padded = is_numeric($voucher)
            ? str_pad((string) (int) $voucher, 5, '0', STR_PAD_LEFT)
            : substr(preg_replace('/[^A-Z0-9\-]/i', '', $voucher), 0, 20);

        $base = sprintf('%s/%04d%02d/%s', $vc, $fy, $fp, $voucher_padded);

        // Pastikan unik
        $candidate = $base;
        $suffix_idx = 0;
        while ($this->journal_no_exists($candidate)) {
            $suffix_idx++;
            $candidate = $base . '-' . chr(64 + $suffix_idx); // -A, -B, -C
            if ($suffix_idx > 25) {
                $candidate = $base . '-' . substr(uniqid(), -4);
                break;
            }
        }
        return $candidate;
    }

    private function journal_no_exists($journal_no)
    {
        $this->db->where('journal_no', $journal_no);
        return $this->db->count_all_results('journal_header') > 0;
    }

    /**
     * Parse sheet data ke daftar voucher dalam memory.
     * $sheet_data: array indexed by row number (1-based).
     *   Setiap row: ['A'=>..., 'B'=>..., ..., 'U'=>...]
     *
     * Return:
     *   [
     *     'vouchers'        => [...],  // siap diproses (lihat struktur di bawah)
     *     'skipped'         => [...],  // voucher yang di-skip + alasan
     *     'parse_warnings'  => [...],  // catatan parsing umum
     *   ]
     */
    public function parse_sheet($sheet_data, $account_map)
    {
        $vouchers       = [];
        $skipped        = [];
        $parse_warnings = [];

        $current = null; // voucher yang sedang dibangun

        foreach ($sheet_data as $row_num => $row) {
            // Skip baris header Excel (row 1) — wajib
            if ($row_num <= 1) continue;

            $date_raw     = $row['A'] ?? null;
            $fy           = $row['B'] ?? null;
            $fp           = $row['C'] ?? null;
            $voucher_cat  = trim((string) ($row['D'] ?? ''));
            $voucher_no   = trim((string) ($row['E'] ?? ''));
            $description  = trim((string) ($row['F'] ?? ''));
            $account_code = trim((string) ($row['G'] ?? ''));
            $currency     = trim((string) ($row['I'] ?? '')) ?: 'IDR';
            $amount_orig  = $this->to_float($row['J'] ?? 0);
            $debit_local  = $this->to_float($row['K'] ?? 0);
            $credit_local = $this->to_float($row['L'] ?? 0);

            // Skip baris 'Total' di akhir sheet
            if (is_string($date_raw) && strcasecmp(trim($date_raw), 'Total') === 0) {
                continue;
            }

            // Skip baris benar-benar kosong
            $is_blank_row = ($date_raw === null || $date_raw === '')
                && $voucher_no === ''
                && $description === ''
                && $account_code === ''
                && $amount_orig == 0
                && $debit_local == 0
                && $credit_local == 0;
            if ($is_blank_row) continue;

            // Deteksi voucher baru: Date terisi DAN voucher_no terisi
            $is_new_voucher_header = ($date_raw !== null && $date_raw !== '')
                && $voucher_no !== '';

            if ($is_new_voucher_header) {
                // Simpan voucher sebelumnya
                if ($current !== null) {
                    $this->finalize_voucher($current, $vouchers, $skipped, $account_map);
                }

                $journal_date = $this->normalize_date($date_raw);
                if ($journal_date === null) {
                    // Tanggal invalid -> tetap buka voucher tapi akan ditandai error
                    $parse_warnings[] = "Baris {$row_num}: tanggal voucher tidak valid ('{$date_raw}'), voucher akan di-skip.";
                }

                $current = [
                    'start_row'    => $row_num,
                    'voucher_cat'  => $voucher_cat,
                    'voucher_no'   => $voucher_no,
                    'fy'           => (int) $fy,
                    'fp'           => (int) $fp,
                    'journal_date' => $journal_date,
                    'description'  => $description,
                    'details'      => [],
                    'errors'       => $journal_date === null
                        ? ["Tanggal voucher tidak valid: '{$date_raw}'"]
                        : [],
                ];
            }

            // Detail line — wajib ada voucher aktif
            if ($current === null) {
                $skipped[] = [
                    'voucher_no' => '(unknown)',
                    'rows'       => $row_num . '',
                    'reasons'    => ["Baris {$row_num} tidak punya voucher header di atasnya."],
                ];
                continue;
            }

            // Jika baris hanya berisi pembuka header tanpa account, lewati detail-nya
            if ($account_code === '' && $amount_orig == 0
                && $debit_local == 0 && $credit_local == 0) {
                continue;
            }

            $line_errors = [];

            // Validasi akun
            if ($account_code === '') {
                $line_errors[] = "Baris {$row_num}: Account Code kosong.";
            } elseif (!isset($account_map[$account_code])) {
                $line_errors[] = "Baris {$row_num}: Account Code '{$account_code}' tidak ditemukan di COA atau tidak aktif.";
            }

            // Validasi sisi debit/kredit (pakai kolom local IDR sebagai sumber kebenaran)
            if ($debit_local == 0 && $credit_local == 0) {
                $line_errors[] = "Baris {$row_num}: Debit Amount dan Credit Amount keduanya 0.";
            }
            if ($debit_local != 0 && $credit_local != 0) {
                $line_errors[] = "Baris {$row_num}: Debit dan Credit terisi bersamaan.";
            }

            $is_debit = $debit_local != 0;

            $current['details'][] = [
                'row_num'      => $row_num,
                'description'  => $description,
                'account_code' => $account_code,
                'account_id'   => isset($account_map[$account_code]) ? (int) $account_map[$account_code]->id : null,
                'currency'     => strtoupper($currency),
                'amount_orig'  => $amount_orig,
                'debit_local'  => $debit_local,
                'credit_local' => $credit_local,
                'is_debit'     => $is_debit,
            ];

            if (!empty($line_errors)) {
                $current['errors'] = array_merge($current['errors'], $line_errors);
            }
        }

        // Finalisasi voucher terakhir
        if ($current !== null) {
            $this->finalize_voucher($current, $vouchers, $skipped, $account_map);
        }

        return [
            'vouchers'       => $vouchers,
            'skipped'        => $skipped,
            'parse_warnings' => $parse_warnings,
        ];
    }

    /**
     * Validasi level voucher (balance, ada detail valid), masukkan ke
     * $vouchers (siap insert) atau $skipped (gagal).
     */
    private function finalize_voucher(array $voucher, array &$vouchers, array &$skipped, array $account_map)
    {
        $rows_str = $voucher['start_row'] . '';
        if (!empty($voucher['details'])) {
            $first = (int) $voucher['details'][0]['row_num'];
            $last  = (int) end($voucher['details'])['row_num'];
            $rows_str = $first . '-' . $last;
        }

        $voucher_label = trim($voucher['voucher_cat'] . '-' . $voucher['voucher_no']);

        // Cek voucher tanpa detail valid
        if (empty($voucher['details'])) {
            $skipped[] = [
                'voucher_no' => $voucher_label,
                'rows'       => $rows_str,
                'reasons'    => array_merge(
                    ["Voucher tidak punya baris detail apapun."],
                    $voucher['errors']
                ),
            ];
            return;
        }

        // Cek balance pakai kolom local (IDR)
        $total_debit_local  = 0;
        $total_credit_local = 0;
        foreach ($voucher['details'] as $d) {
            $total_debit_local  += $d['debit_local'];
            $total_credit_local += $d['credit_local'];
        }

        if (abs($total_debit_local - $total_credit_local) > 0.01) {
            $voucher['errors'][] = sprintf(
                'Voucher tidak balance (Debit Local %s vs Credit Local %s, selisih %s).',
                number_format($total_debit_local, 2),
                number_format($total_credit_local, 2),
                number_format($total_debit_local - $total_credit_local, 2)
            );
        }

        if (!empty($voucher['errors'])) {
            $skipped[] = [
                'voucher_no' => $voucher_label,
                'rows'       => $rows_str,
                'reasons'    => $voucher['errors'],
            ];
            return;
        }

        // Tentukan currency header: jika semua sama -> currency itu, jika beda -> 'MIXED'
        $currs = array_unique(array_map(fn($d) => $d['currency'], $voucher['details']));
        $header_currency = (count($currs) === 1) ? reset($currs) : 'MIXED';

        $voucher['totals'] = [
            'total_debit_local'  => $total_debit_local,
            'total_credit_local' => $total_credit_local,
            'header_currency'    => $header_currency,
            'rows_str'           => $rows_str,
            'voucher_label'      => $voucher_label,
        ];

        $vouchers[] = $voucher;
    }

    /**
     * Insert semua voucher valid ke journal_header / journal_details
     * dalam satu transaksi DB. Return ringkasan.
     */
    public function insert_vouchers(array $vouchers, $user_id)
    {
        $created_journals = []; // [['journal_no'=>..., 'voucher_label'=>..., 'rows'=>...]]

        $this->db->trans_start();

        foreach ($vouchers as $v) {
            $journal_no = $this->generate_journal_no(
                $v['voucher_cat'],
                $v['fy'],
                $v['fp'],
                $v['voucher_no']
            );

            $header_data = [
                'journal_no'         => $journal_no,
                'journal_date'       => $v['journal_date'],
                'description'        => $v['description'] !== '' ? $v['description'] : $v['totals']['voucher_label'],
                'currency'           => substr($v['totals']['header_currency'], 0, 10),
                'exchange_rate'      => 1, // per-line sudah pakai local amount
                'source_module'      => 'Summary Ledger',
                'source_doc_id'      => null,
                'total_debit'        => $v['totals']['total_debit_local'],
                'total_kredit'       => $v['totals']['total_credit_local'],
                'total_debit_local'  => $v['totals']['total_debit_local'],
                'total_kredit_local' => $v['totals']['total_credit_local'],
                'status'             => 'Draft',
                'created_by'         => $user_id,
                'created_at'         => date('Y-m-d H:i:s'),
            ];

            $this->db->insert('journal_header', $header_data);
            $header_id = $this->db->insert_id();

            foreach ($v['details'] as $d) {
                $debit_orig  = $d['is_debit']  ? $d['amount_orig'] : 0;
                $kredit_orig = !$d['is_debit'] ? $d['amount_orig'] : 0;

                $this->db->insert('journal_details', [
                    'journal_header_id' => $header_id,
                    'account_id'        => $d['account_id'],
                    'debit'             => $debit_orig,
                    'kredit'            => $kredit_orig,
                    'debit_local'       => $d['debit_local'],
                    'kredit_local'      => $d['credit_local'],
                    'description'       => mb_substr((string) $d['description'], 0, 255),
                ]);
            }

            $created_journals[] = [
                'id'            => (int) $header_id,
                'journal_no'    => $journal_no,
                'voucher_label' => $v['totals']['voucher_label'],
                'rows'          => $v['totals']['rows_str'],
            ];
        }

        $this->db->trans_complete();

        return [
            'success'          => $this->db->trans_status() !== FALSE,
            'created_journals' => $created_journals,
        ];
    }

    /**
     * Batch-post semua journal Draft hasil import Summary Ledger.
     * Hanya update baris dengan source_module='Summary Ledger' dan status='Draft'
     * agar tidak mengubah journal lain yang tidak terkait.
     *
     * Return jumlah baris ter-update.
     */
    public function post_imported_journals(array $journal_ids, $user_id)
    {
        $ids = array_values(array_filter(array_map('intval', $journal_ids), fn($v) => $v > 0));
        if (empty($ids)) return 0;

        $this->db->where_in('id', $ids);
        $this->db->where('status', 'Draft');
        $this->db->where('source_module', 'Summary Ledger');
        $this->db->update('journal_header', [
            'status'    => 'Posted',
            'posted_by' => $user_id,
            'posted_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->affected_rows();
    }

    public function log_import(array $payload)
    {
        $this->db->insert('summary_ledger_imports', $payload);
        return $this->db->insert_id();
    }

    /**
     * Link journal_header.source_doc_id ke summary_ledger_imports.id
     * supaya halaman detail import bisa list journal yang ter-create di batch itu.
     */
    public function link_journals_to_import(array $journal_ids, $import_id)
    {
        $ids = array_values(array_filter(array_map('intval', $journal_ids), fn($v) => $v > 0));
        if (empty($ids) || (int) $import_id <= 0) return 0;
        $this->db->where_in('id', $ids);
        $this->db->where('source_module', 'Summary Ledger');
        $this->db->update('journal_header', ['source_doc_id' => (int) $import_id]);
        return (int) $this->db->affected_rows();
    }

    public function get_import_by_id($id)
    {
        $this->db->select('sli.*, u.fullname AS uploader_name');
        $this->db->from('summary_ledger_imports sli');
        $this->db->join('users u', 'sli.uploaded_by = u.id', 'left');
        $this->db->where('sli.id', (int) $id);
        return $this->db->get()->row();
    }

    public function get_journals_by_import_id($import_id)
    {
        $this->db->select('id, journal_no, journal_date, description, currency, total_debit_local, total_kredit_local, status, posted_at');
        $this->db->where('source_module', 'Summary Ledger');
        $this->db->where('source_doc_id', (int) $import_id);
        $this->db->order_by('id', 'ASC');
        return $this->db->get('journal_header')->result();
    }

    /**
     * List voucher disbursement (journal hasil Summary Ledger) dengan filter.
     * $filters: ['status'=>'Draft|Posted|all', 'q'=>'search', 'date_from'=>YYYY-MM-DD, 'date_to'=>YYYY-MM-DD]
     */
    public function get_voucher_list(array $filters = [], $limit = null, $offset = 0)
    {
        $this->db->select('jh.id, jh.journal_no, jh.journal_date, jh.description, jh.currency,
                           jh.total_debit_local, jh.total_kredit_local, jh.status, jh.source_module,
                           jh.created_at, jh.posted_at, jh.source_doc_id,
                           u_c.fullname AS created_by_name,
                           u_p.fullname AS posted_by_name,
                           sli.original_filename AS import_filename');
        $this->db->from('journal_header jh');
        $this->db->join('users u_c', 'jh.created_by = u_c.id', 'left');
        $this->db->join('users u_p', 'jh.posted_by  = u_p.id', 'left');
        $this->db->join('summary_ledger_imports sli', 'jh.source_doc_id = sli.id', 'left');

        // Voucher bisa dibuat dari jurnal MANA SAJA (bukan hanya hasil upload
        // Summary Ledger), jadi tidak ada lagi filter source_module di sini.
        $this->_apply_voucher_filters($filters);

        $this->db->order_by('jh.journal_date', 'DESC');
        $this->db->order_by('jh.id', 'DESC');

        if ($limit !== null) {
            $this->db->limit((int) $limit, (int) $offset);
        }
        return $this->db->get()->result();
    }

    /**
     * Hitung total voucher (jurnal) sesuai filter — untuk pagination.
     */
    public function count_vouchers(array $filters = [])
    {
        $this->db->from('journal_header jh');
        $this->_apply_voucher_filters($filters);
        return (int) $this->db->count_all_results();
    }

    /**
     * Hitung jumlah jurnal per status (all/Draft/Posted/Cancelled) dengan filter
     * q + tanggal diterapkan, tapi TANPA filter status — untuk badge tab.
     */
    public function get_voucher_status_counts(array $filters = [])
    {
        $base = $filters;
        unset($base['status']); // abaikan status agar tiap tab menghitung domainnya sendiri

        $counts = ['all' => 0, 'Draft' => 0, 'Posted' => 0, 'Cancelled' => 0];

        $this->db->select('jh.status, COUNT(*) AS cnt');
        $this->db->from('journal_header jh');
        $this->_apply_voucher_filters($base);
        $this->db->group_by('jh.status');
        $rows = $this->db->get()->result();

        foreach ($rows as $r) {
            $counts['all'] += (int) $r->cnt;
            if (isset($counts[$r->status])) {
                $counts[$r->status] += (int) $r->cnt;
            }
        }
        return $counts;
    }

    /**
     * Terapkan filter status/q/tanggal ke query builder aktif (dipakai bersama
     * oleh list & count supaya konsisten).
     */
    private function _apply_voucher_filters(array $filters)
    {
        // Voucher disbursement HANYA muncul setelah jurnal di-Post.
        // Draft tetap dikelola di Jurnal Umum (boleh print voucher dari sana).
        $this->db->where('jh.status !=', 'Draft');

        $status = $filters['status'] ?? 'all';
        if ($status === 'Posted' || $status === 'Cancelled') {
            $this->db->where('jh.status', $status);
        }

        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $this->db->group_start();
            $this->db->like('jh.journal_no', $q);
            $this->db->or_like('jh.description', $q);
            $this->db->group_end();
        }

        if (!empty($filters['date_from'])) {
            $this->db->where('jh.journal_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('jh.journal_date <=', $filters['date_to']);
        }
    }

    /**
     * Hapus banyak voucher Draft sekaligus.
     * Filter ketat: status='Draft' AND source_module='Summary Ledger'.
     * Return jumlah voucher yang berhasil dihapus.
     */
    public function bulk_delete_draft_vouchers(array $journal_ids)
    {
        $ids = array_values(array_filter(array_map('intval', $journal_ids), fn($v) => $v > 0));
        if (empty($ids)) return 0;

        // Ambil dulu hanya yang valid (Draft + Summary Ledger), untuk hindari hapus yg ga boleh.
        $this->db->select('id');
        $this->db->where_in('id', $ids);
        $this->db->where('status', 'Draft');
        $this->db->where('source_module', 'Summary Ledger');
        $rows = $this->db->get('journal_header')->result();
        $valid_ids = array_map(fn($r) => (int) $r->id, $rows);
        if (empty($valid_ids)) return 0;

        $this->db->trans_start();
        $this->db->where_in('journal_header_id', $valid_ids);
        $this->db->delete('journal_details');

        $this->db->where_in('id', $valid_ids);
        $this->db->where('status', 'Draft');
        $this->db->where('source_module', 'Summary Ledger');
        $this->db->delete('journal_header');
        $deleted = (int) $this->db->affected_rows();
        $this->db->trans_complete();

        return $this->db->trans_status() === FALSE ? 0 : $deleted;
    }

    /**
     * Post 1 journal Draft hasil Summary Ledger.
     * Return true jika berhasil di-update, false jika tidak (sudah posted / bukan dari summary ledger).
     */
    public function post_single_voucher($journal_id, $user_id)
    {
        $this->db->where('id', (int) $journal_id);
        $this->db->where('status', 'Draft');
        $this->db->where('source_module', 'Summary Ledger');
        $this->db->update('journal_header', [
            'status'    => 'Posted',
            'posted_by' => $user_id,
            'posted_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->affected_rows() > 0;
    }

    /**
     * Ambil header + details lengkap untuk PDF Disbursement.
     * Hanya boleh untuk journal milik Summary Ledger.
     */
    public function get_journal_for_pdf($journal_id)
    {
        $this->db->select('jh.*, sli.original_filename AS import_filename, u.fullname AS creator_name');
        $this->db->from('journal_header jh');
        $this->db->join('summary_ledger_imports sli', 'jh.source_doc_id = sli.id', 'left');
        $this->db->join('users u', 'jh.created_by = u.id', 'left');
        $this->db->where('jh.id', (int) $journal_id);
        // Voucher PDF/Excel boleh untuk jurnal apa saja (tidak dibatasi Summary Ledger).
        $header = $this->db->get()->row();
        if (!$header) return null;

        $this->db->select('jd.*, coa.account_code, coa.account_name');
        $this->db->from('journal_details jd');
        $this->db->join('chart_of_accounts coa', 'jd.account_id = coa.id', 'left');
        $this->db->where('jd.journal_header_id', $header->id);
        $this->db->order_by('jd.id', 'ASC');
        $details = $this->db->get()->result();

        return ['header' => $header, 'details' => $details];
    }

    /**
     * Hapus 1 journal hasil import — hanya boleh jika status Draft
     * dan source_module='Summary Ledger'.
     * Return true jika terhapus, false jika tidak (sudah Posted / bukan import).
     */
    public function delete_draft_journal($journal_id)
    {
        $this->db->where('id', (int) $journal_id);
        $this->db->where('status', 'Draft');
        $this->db->where('source_module', 'Summary Ledger');
        $row = $this->db->get('journal_header')->row();
        if (!$row) return false;

        $this->db->trans_start();
        $this->db->delete('journal_details', ['journal_header_id' => $row->id]);
        $this->db->delete('journal_header', ['id' => $row->id, 'status' => 'Draft']);
        $this->db->trans_complete();
        return $this->db->trans_status() !== FALSE;
    }

    private function to_float($v)
    {
        if ($v === null || $v === '') return 0.0;
        if (is_numeric($v)) return (float) $v;
        // Strip thousand separators
        $clean = preg_replace('/[^0-9\.\-]/', '', (string) $v);
        return is_numeric($clean) ? (float) $clean : 0.0;
    }

    /**
     * Normalisasi tanggal: terima DateTime, serial Excel, atau string.
     * Return 'Y-m-d' atau null bila gagal.
     */
    private function normalize_date($value)
    {
        if ($value instanceof DateTime || $value instanceof DateTimeImmutable) {
            return $value->format('Y-m-d');
        }
        if (is_numeric($value)) {
            // Excel serial number
            try {
                $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }
        if (is_string($value) && $value !== '') {
            $ts = strtotime($value);
            return $ts !== false ? date('Y-m-d', $ts) : null;
        }
        return null;
    }
}
