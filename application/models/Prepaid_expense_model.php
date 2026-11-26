<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Prepaid_expense_model — Biaya Dibayar Dimuka (mis. Sewa Dibayar Dimuka).
 *
 * Pembayarannya tetap lewat Kas/Bank atau Faktur Supplier (Dr Sewa Dibayar
 * Dimuka / Cr Bank); di sini hanya jadwal amortisasinya. Amortisasi garis
 * lurus = nilai / jumlah bulan, mulai bulan berikutnya setelah tanggal bayar
 * (bisa diubah), diproses di batch bulanan Penyusutan & Amortisasi:
 *   Dr Beban (mis. Biaya Sewa) / Cr Biaya Dibayar Dimuka.
 */
class Prepaid_expense_model extends CI_Model {

    private $table = 'prepaid_expenses';

    public function get_list($filters = [])
    {
        $this->db->select('p.*, cp.account_code AS coa_prepaid_code, cp.account_name AS coa_prepaid_name,
                           ce.account_code AS coa_expense_code, ce.account_name AS coa_expense_name')
            ->from($this->table . ' p')
            ->join('chart_of_accounts cp', 'cp.id = p.coa_prepaid_id', 'left')
            ->join('chart_of_accounts ce', 'ce.id = p.coa_expense_id', 'left');
        if (!empty($filters['status'])) $this->db->where('p.status', $filters['status']);
        if (!empty($filters['q'])) $this->db->group_start()->like('p.doc_no', $filters['q'])->or_like('p.nama', $filters['q'])->group_end();
        return $this->db->order_by('p.mulai_amortisasi', 'DESC')->order_by('p.id', 'DESC')->get()->result();
    }

    public function get($id)
    {
        return $this->db->select('p.*, cp.account_code AS coa_prepaid_code, cp.account_name AS coa_prepaid_name,
                                  ce.account_code AS coa_expense_code, ce.account_name AS coa_expense_name')
            ->from($this->table . ' p')
            ->join('chart_of_accounts cp', 'cp.id = p.coa_prepaid_id', 'left')
            ->join('chart_of_accounts ce', 'ce.id = p.coa_expense_id', 'left')
            ->where('p.id', (int) $id)->get()->row();
    }

    public function get_history($id)
    {
        return $this->db->select('d.*, h.batch_number, h.periode_year, h.periode_month, h.status AS batch_status, h.id AS batch_id, j.journal_no')
            ->from('prepaid_amortization_details d')
            ->join('asset_depreciation_header h', 'h.id = d.depreciation_header_id')
            ->join('journal_header j', 'j.id = h.journal_header_id', 'left')
            ->where('d.prepaid_id', (int) $id)
            ->order_by('h.periode_year', 'ASC')->order_by('h.periode_month', 'ASC')->get()->result();
    }

    public function has_posted_amortization($id)
    {
        return $this->db->from('prepaid_amortization_details d')
            ->join('asset_depreciation_header h', 'h.id = d.depreciation_header_id')
            ->where('d.prepaid_id', (int) $id)->where('h.status', 'Posted')->count_all_results() > 0;
    }

    public function monthly($p)
    {
        return (int) $p->jumlah_bulan > 0 ? round((float) $p->nilai / (int) $p->jumlah_bulan, 2) : 0;
    }

    public function default_accounts()
    {
        $this->load->model('SettingAkun_model');
        return [
            'prepaid' => $this->SettingAkun_model->get_account_id('akun_sewa_dibayar_dimuka', 'IDR'),
            'expense' => $this->SettingAkun_model->get_account_id('akun_biaya_sewa', 'IDR'),
        ];
    }

    private function _next_doc_no($date)
    {
        $prefix = 'PPD-' . date('Y', strtotime($date)) . '-';
        $row = $this->db->query("SELECT MAX(CAST(SUBSTRING(doc_no, ?) AS UNSIGNED)) AS m FROM {$this->table} WHERE doc_no LIKE ?",
            [strlen($prefix) + 1, $prefix . '%'])->row();
        return $prefix . str_pad((string) ((int) ($row->m ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function _account_ok($id)
    {
        return (int) $id > 0 && $this->db->where('id', (int) $id)->where('is_header', 0)->where('status', 'active')->count_all_results('chart_of_accounts') > 0;
    }

    private function _fields($post)
    {
        $nama = trim((string) ($post['nama'] ?? ''));
        if ($nama === '') throw new Exception('Nama / keterangan biaya dibayar dimuka wajib diisi.');
        $tgl = (string) ($post['tanggal_bayar'] ?? '');
        if (!strtotime($tgl)) throw new Exception('Tanggal bayar wajib diisi.');
        $nilai = round((float) ($post['nilai'] ?? 0), 2);
        if ($nilai <= 0) throw new Exception('Nilai harus lebih dari 0.');
        $bulan = (int) ($post['jumlah_bulan'] ?? 0);
        if ($bulan <= 0 || $bulan > 600) throw new Exception('Jumlah bulan amortisasi harus 1 - 600.');
        $awal = round((float) ($post['amortisasi_awal'] ?? 0), 2);
        if ($awal < 0 || $awal > $nilai) throw new Exception('Amortisasi awal harus 0 sampai nilai.');
        if (!$this->_account_ok($post['coa_prepaid_id'] ?? 0)) throw new Exception('Pilih akun Biaya Dibayar Dimuka.');
        if (!$this->_account_ok($post['coa_expense_id'] ?? 0)) throw new Exception('Pilih akun Beban.');
        // Default: bulan berikutnya setelah tanggal bayar.
        $mulai = !empty($post['mulai_amortisasi']) && preg_match('/^\d{4}-\d{2}/', $post['mulai_amortisasi'])
            ? substr($post['mulai_amortisasi'], 0, 7) . '-01'
            : date('Y-m-01', strtotime(date('Y-m-01', strtotime($tgl)) . ' +1 month'));
        return [
            'nama' => mb_substr($nama, 0, 150),
            'coa_prepaid_id' => (int) $post['coa_prepaid_id'],
            'coa_expense_id' => (int) $post['coa_expense_id'],
            'department' => ($post['department'] ?? '') !== '' ? $post['department'] : null,
            'tanggal_bayar' => date('Y-m-d', strtotime($tgl)),
            'nilai' => $nilai, 'jumlah_bulan' => $bulan, 'mulai_amortisasi' => $mulai,
            'amortisasi_awal' => $awal,
            'akumulasi_amortisasi' => $awal,
            'sisa' => round($nilai - $awal, 2),
            'status' => round($nilai - $awal, 2) <= 0 ? 'Selesai' : 'Aktif',
        ];
    }

    public function create($post, $user_id)
    {
        $f = $this->_fields($post);
        $f['doc_no'] = $this->_next_doc_no($f['tanggal_bayar']);
        $f['ref_journal_no'] = ($post['ref_journal_no'] ?? '') !== '' ? mb_substr(trim($post['ref_journal_no']), 0, 50) : null;
        $f['keterangan'] = ($post['keterangan'] ?? '') !== '' ? $post['keterangan'] : null;
        $f['created_by'] = $user_id;
        $this->db->insert($this->table, $f);
        return (int) $this->db->insert_id();
    }

    /** Data keuangan hanya bisa diubah sebelum ada amortisasi Posted. */
    public function update($id, $post)
    {
        $p = $this->get($id);
        if (!$p) throw new Exception('Data tidak ditemukan.');
        $data = [
            'department' => ($post['department'] ?? '') !== '' ? $post['department'] : null,
            'ref_journal_no' => ($post['ref_journal_no'] ?? '') !== '' ? mb_substr(trim($post['ref_journal_no']), 0, 50) : null,
            'keterangan' => ($post['keterangan'] ?? '') !== '' ? $post['keterangan'] : null,
        ];
        if (!$this->has_posted_amortization($id)) $data = $this->_fields($post) + $data;
        $this->db->where('id', (int) $id)->update($this->table, $data);
        return true;
    }

    public function delete($id)
    {
        $p = $this->get($id);
        if (!$p) throw new Exception('Data tidak ditemukan.');
        if ($this->has_posted_amortization($id)) throw new Exception('Sudah ada amortisasi yang diposting. Tidak bisa dihapus.');
        $this->db->trans_start();
        $this->db->where('prepaid_id', (int) $id)->delete('prepaid_amortization_details'); // baris di batch Draft
        $this->db->where('id', (int) $id)->delete($this->table);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menghapus (transaksi database).');
        return $p->doc_no;
    }
}
