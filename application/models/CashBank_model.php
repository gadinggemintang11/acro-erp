<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CashBank_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('JurnalUmum_model');
        $this->load->model('SettingAkun_model');
    }

    public function get_all_headers()
    {
        $this->db->select('cb.*, coa.account_name as payment_account_name');
        $this->db->from('cash_bank_header cb');
        $this->db->join('chart_of_accounts coa', 'cb.cash_bank_account_id = coa.id');
        $this->db->order_by('cb.doc_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id)
    {
        $this->db->select('cb.*, coa.account_code, coa.account_name as payment_account_name, u.fullname as creator_name, p.fullname as poster_name');
        $this->db->from('cash_bank_header cb');
        $this->db->join('chart_of_accounts coa', 'cb.cash_bank_account_id = coa.id');
        $this->db->join('users u', 'cb.created_by = u.id', 'left');
        $this->db->join('users p', 'cb.posted_by = p.id', 'left');
        $this->db->where('cb.id', $id);
        return $this->db->get()->row();
    }
    
    public function get_details_by_header_id($header_id)
    {
        $this->db->select('cbd.*, coa.account_code, coa.account_name');
        $this->db->from('cash_bank_details cbd');
        $this->db->join('chart_of_accounts coa', 'cbd.account_id = coa.id');
        $this->db->where('cbd.header_id', $header_id);
        return $this->db->get()->result();
    }
    
    public function get_new_doc_number()
    {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('cash_bank', 'cash_bank_header', 'doc_no');
    }

    public function get_cash_bank_accounts_by_currency($currency)
    {
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->where('currency', $currency);
        $this->db->where('account_type', 'Aset');
        // COA Acropoly (2026-11-07): 1.101.* Kas, 1.102.* Bank -- sama dengan
        // filter Kas/Bank di AP Payment, AR Receipt dan Report_model.
        $this->db->group_start();
        $this->db->like('account_code', '1.101', 'after');
        $this->db->or_like('account_code', '1.102', 'after');
        $this->db->group_end();
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    public function get_postable_accounts_by_currency($currency)
    {
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->where('currency', $currency);
        // Kecuali Kas/Bank (1.101.* / 1.102.*)
        $this->db->not_like('account_code', '1.101', 'after');
        $this->db->not_like('account_code', '1.102', 'after');
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    public function insert_transaction($post)
    {
        $this->db->trans_start();

        $details = $post['details'];
        $rate = (float) $post['exchange_rate'];
        $total_amount = 0;
        $total_amount_local = 0;
        $detail_batch = [];

        foreach ($details as $d) {
            $amount = (float) $d['amount'];
            if ($amount > 0) {
                $amount_local = $amount * $rate;
                $total_amount += $amount;
                $total_amount_local += $amount_local;
                
                $detail_batch[] = [
                    'account_id' => $d['account_id'],
                    'amount' => $amount,
                    'amount_local' => $amount_local,
                    'description' => $d['description']
                ];
            }
        }
        
        if (empty($detail_batch)) {
            throw new Exception("Detail transaksi tidak boleh kosong.");
        }

        $header_data = [
            'doc_no' => $post['doc_no'],
            'doc_date' => $post['doc_date'],
            'transaction_type' => $post['transaction_type'],
            'cash_bank_account_id' => $post['cash_bank_account_id'],
            'currency' => $post['currency'],
            'exchange_rate' => $rate,
            'total_amount' => $total_amount,
            'total_amount_local' => $total_amount_local,
            'description' => $post['description'],
            'status' => 'Draft',
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('cash_bank_header', $header_data);
        $header_id = $this->db->insert_id();

        foreach ($detail_batch as &$d) {
            $d['header_id'] = $header_id;
        }
        unset($d);

        $this->db->insert_batch('cash_bank_details', $detail_batch);

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }
    
    public function delete_draft_transaction($id)
    {
        $h = $this->db->select('id, status, journal_header_id')->where('id', (int) $id)->get('cash_bank_header')->row();
        if (!$h || $h->status !== 'Draft') return false;

        $this->db->trans_start();
        // Transaksi hasil redraft masih membawa jurnal Draft-nya -> ikut dihapus.
        if (!empty($h->journal_header_id)) {
            $this->db->where('journal_header_id', (int) $h->journal_header_id)->delete('journal_details');
            $this->db->where('id', (int) $h->journal_header_id)->where('status', 'Draft')
                     ->where('source_module', 'CASH_BANK')->delete('journal_header');
        }
        $this->db->where('id', (int) $id)->where('status', 'Draft')->delete('cash_bank_header');
        $deleted = $this->db->affected_rows() > 0;
        $this->db->trans_complete();
        return $deleted && $this->db->trans_status() !== FALSE;
    }

    /**
     * ======================================
     * REDRAFT & EDIT (koreksi transaksi yang sudah diposting)
     * ======================================
     * Redraft: Posted -> Draft, jurnalnya ikut Draft (keluar dari laporan, nomor
     * jurnal tetap). Posting ulang memperbarui jurnal yang sama lalu
     * mempostingnya lagi.
     */

    /** Alasan transaksi TIDAK boleh di-redraft; null = boleh. */
    public function redraft_block_reason($h)
    {
        if (!$h) return 'Transaksi tidak ditemukan.';
        if ($h->status !== 'Posted') return 'Hanya transaksi berstatus Posted yang bisa di-redraft.';
        $j = !empty($h->journal_header_id)
            ? $this->db->select('id, status')->where('id', (int) $h->journal_header_id)->get('journal_header')->row()
            : null;
        if (!$j) return 'Jurnal transaksi ini sudah tidak ada (kemungkinan di-clear saat tutup bulan) — tidak bisa redraft.';
        if ($j->status !== 'Posted') return 'Status jurnal transaksi ini bukan Posted.';
        return null;
    }

    /** Posted -> Draft; jurnal ikut Draft. Throw Exception bila ditolak. */
    public function redraft_transaction($id)
    {
        $h = $this->get_header_by_id($id);
        if ($why = $this->redraft_block_reason($h)) throw new Exception($why);

        $this->db->trans_start();
        $this->db->where('id', (int) $h->journal_header_id)->where('status', 'Posted')
                 ->update('journal_header', ['status' => 'Draft', 'posted_by' => null, 'posted_at' => null]);
        $this->db->where('id', (int) $h->id)->where('status', 'Posted')
                 ->update('cash_bank_header', ['status' => 'Draft', 'posted_by' => null, 'posted_at' => null]);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal redraft (transaksi database).');
        return $h;
    }

    /** Simpan hasil edit transaksi Draft (No. Dokumen tetap). */
    public function update_transaction($id, $post)
    {
        $h = $this->get_header_by_id($id);
        if (!$h || $h->status !== 'Draft') throw new Exception('Hanya transaksi Draft yang bisa diedit.');

        $rate = (float) ($post['exchange_rate'] ?? 0);
        if ($rate <= 0) throw new Exception('Kurs harus lebih dari 0.');
        if (!in_array($post['transaction_type'] ?? '', ['IN', 'OUT'], true)) throw new Exception('Tipe transaksi tidak valid.');

        $rows = [];
        $total = 0; $total_local = 0;
        foreach ((array) ($post['details'] ?? []) as $d) {
            $amount = round((float) ($d['amount'] ?? 0), 2);
            if ($amount <= 0 || empty($d['account_id'])) continue;
            $local = $amount * $rate;
            $total += $amount; $total_local += $local;
            $rows[] = [
                'header_id'    => (int) $id,
                'account_id'   => (int) $d['account_id'],
                'amount'       => $amount,
                'amount_local' => $local,
                'description'  => (string) ($d['description'] ?? ''),
            ];
        }
        if (empty($rows)) throw new Exception('Detail transaksi tidak boleh kosong.');

        $this->db->trans_start();
        $this->db->where('id', (int) $id)->where('status', 'Draft')->update('cash_bank_header', [
            'doc_date'             => $post['doc_date'],
            'transaction_type'     => $post['transaction_type'],
            'cash_bank_account_id' => (int) $post['cash_bank_account_id'],
            'currency'             => $post['currency'],
            'exchange_rate'        => $rate,
            'total_amount'         => $total,
            'total_amount_local'   => $total_local,
            'description'          => $post['description'],
        ]);
        $this->db->where('header_id', (int) $id)->delete('cash_bank_details');
        $this->db->insert_batch('cash_bank_details', $rows);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menyimpan perubahan (transaksi database).');
        return true;
    }

    public function post_transaction($header_id, $user_id)
    {
        $this->db->trans_start();

        $header = $this->get_header_by_id($header_id);
        $details = $this->get_details_by_header_id($header_id);

        if ($header->status != 'Draft') {
            throw new Exception("Hanya status Draft yang bisa di-posting.");
        }
        
        // Transaksi hasil redraft masih membawa jurnal Draft-nya: diperbarui &
        // diposting ulang (nomor jurnal tetap), bukan membuat jurnal baru.
        $redraft_journal = !empty($header->journal_header_id)
            ? $this->db->select('id, journal_no, status')->where('id', (int) $header->journal_header_id)->get('journal_header')->row()
            : null;
        if ($redraft_journal && $redraft_journal->status !== 'Draft') $redraft_journal = null;
        $journal_no = $redraft_journal ? $redraft_journal->journal_no : $this->JurnalUmum_model->get_new_journal_number();
        $journal_details = [];
        $akun_kas_bank = $header->cash_bank_account_id;
        $tipe = $header->transaction_type; // 'IN' or 'OUT'

        if ($tipe == 'OUT') {
            // Kas/Bank di sisi KREDIT, detail di sisi DEBIT
            foreach ($details as $d) {
                $journal_details[] = [
                    'account_id' => $d->account_id,
                    'debit' => $d->amount,
                    'kredit' => 0,
                    'description' => $d->description
                ];
            }
            // Tambah baris Kas/Bank (KREDIT)
            $journal_details[] = [
                'account_id' => $akun_kas_bank,
                'debit' => 0,
                'kredit' => $header->total_amount,
                'description' => $header->description
            ];
        } else { // 'IN'
            // Kas/Bank di sisi DEBIT
            $journal_details[] = [
                'account_id' => $akun_kas_bank,
                'debit' => $header->total_amount,
                'kredit' => 0,
                'description' => $header->description
            ];
            // Detail (pendapatan) di sisi KREDIT
            foreach ($details as $d) {
                $journal_details[] = [
                    'account_id' => $d->account_id,
                    'debit' => 0,
                    'kredit' => $d->amount,
                    'description' => $d->description
                ];
            }
        }

        $journal_header_data = [
            'journal_no' => $journal_no,
            'journal_date' => $header->doc_date,
            'description' => $header->description,
            'source_module' => 'CASH_BANK',
            'source_doc_id' => $header->id,
            'currency' => $header->currency,
            'exchange_rate' => $header->exchange_rate,
            'total_debit' => $header->total_amount,
            'total_kredit' => $header->total_amount,
            'total_debit_local' => $header->total_amount_local,
            'total_kredit_local' => $header->total_amount_local,
            'created_by' => $user_id,
            'status' => 'Draft'
        ];

        if ($redraft_journal) {
            $journal_id = (int) $redraft_journal->id;
            if (!$this->JurnalUmum_model->replace_draft_journal($journal_id, $journal_header_data, $journal_details)) {
                throw new Exception('Gagal memperbarui jurnal ' . $journal_no . '.');
            }
        } else {
            $journal_id = $this->JurnalUmum_model->insert_journal($journal_header_data, $journal_details);
        }
        $this->JurnalUmum_model->post_journal($journal_id, $user_id);
        
        $this->db->where('id', $header->id);
        $this->db->update('cash_bank_header', [
            'status' => 'Posted',
            'journal_header_id' => $journal_id,
            'posted_by' => $user_id,
            'posted_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? 
            ['status' => FALSE, 'message' => 'Transaksi DB gagal'] : 
            ['status' => TRUE, 'journal_no' => $journal_no];
    }
}
