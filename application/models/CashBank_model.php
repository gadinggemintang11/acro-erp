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
        $this->db->select('RIGHT(doc_no, 5) as kode', FALSE);
        $this->db->order_by('doc_no', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get_where('cash_bank_header', ['YEAR(doc_date)' => date('Y')]);
        
        $kode = ($query->num_rows() > 0) ? intval($query->row()->kode) + 1 : 1;
        $kode_max = str_pad($kode, 5, "0", STR_PAD_LEFT);
        return "CB/" . date('Ym') . "/" . $kode_max;
    }

    public function get_cash_bank_accounts_by_currency($currency)
    {
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->where('currency', $currency);
        $this->db->where('account_type', 'Aset');
        $this->db->like('account_code', '1-11', 'after'); 
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    public function get_postable_accounts_by_currency($currency)
    {
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->where('currency', $currency);
        $this->db->not_like('account_code', '1-11', 'after'); // Kecuali Kas/Bank
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
        $this->db->where('id', $id);
        $this->db->where('status', 'Draft');
        $this->db->delete('cash_bank_header'); 
        return $this->db->affected_rows() > 0;
    }

    public function post_transaction($header_id, $user_id)
    {
        $this->db->trans_start();

        $header = $this->get_header_by_id($header_id);
        $details = $this->get_details_by_header_id($header_id);

        if ($header->status != 'Draft') {
            throw new Exception("Hanya status Draft yang bisa di-posting.");
        }
        
        $journal_no = $this->JurnalUmum_model->get_new_journal_number();
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

        $journal_id = $this->JurnalUmum_model->insert_journal($journal_header_data, $journal_details);
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
