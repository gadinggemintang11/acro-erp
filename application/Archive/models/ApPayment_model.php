<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ApPayment_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('JurnalUmum_model');
        $this->load->model('SettingAkun_model');
    }

    public function get_all_payment_headers()
    {
        $this->db->select('ap.*, s.nama_supplier, coa.account_name as payment_account_name');
        $this->db->from('ap_payment_header ap');
        $this->db->join('suppliers s', 'ap.supplier_id = s.id');
        $this->db->join('chart_of_accounts coa', 'ap.payment_account_id = coa.id');
        $this->db->order_by('ap.payment_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id)
    {
        $this->db->select('ap.*, s.nama_supplier, coa.account_code, coa.account_name as payment_account_name');
        $this->db->from('ap_payment_header ap');
        $this->db->join('suppliers s', 'ap.supplier_id = s.id');
        $this->db->join('chart_of_accounts coa', 'ap.payment_account_id = coa.id');
        $this->db->where('ap.id', $id);
        return $this->db->get()->row();
    }
    
    public function get_details_by_header_id($header_id)
    {
        $this->db->select('apd.*, api.invoice_no, api.invoice_date, api.total_invoice');
        $this->db->from('ap_payment_details apd');
        $this->db->join('ap_invoice_header api', 'apd.ap_invoice_header_id = api.id');
        $this->db->where('apd.payment_header_id', $header_id);
        return $this->db->get()->result();
    }
    
    public function get_new_payment_number()
    {
        $this->db->select('RIGHT(payment_no, 5) as kode', FALSE);
        $this->db->order_by('payment_no', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get_where('ap_payment_header', ['YEAR(payment_date)' => date('Y')]);
        
        $kode = ($query->num_rows() > 0) ? intval($query->row()->kode) + 1 : 1;
        $kode_max = str_pad($kode, 5, "0", STR_PAD_LEFT);
        return "APP/" . date('Ym') . "/" . $kode_max;
    }

    // Ambil akun Kas & Bank (Asumsi IDR)
    public function get_payment_accounts()
    {
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->where('currency', 'IDR');
        $this->db->where('account_type', 'Aset');
        // Asumsi akun Kas/Bank punya kode 1-11xx
        $this->db->like('account_code', '1-11', 'after'); 
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    // Ambil faktur yg belum lunas
    public function get_unpaid_invoices($supplier_id)
    {
        $this->db->select('id, invoice_no, invoice_date, total_invoice, total_paid, (total_invoice - total_paid) as sisa_utang');
        $this->db->from('ap_invoice_header');
        $this->db->where('supplier_id', $supplier_id);
        $this->db->where('status', 'Posted'); // Hanya bayar yg sudah di-posting
        $this->db->where('payment_status !=', 'Paid');
        return $this->db->get()->result();
    }

    // Simpan Pembayaran (Draft)
    public function insert_payment($post)
    {
        $this->db->trans_start();

        $details = $post['details'];
        $total_amount = 0;
        $detail_batch = [];

        foreach ($details as $inv_id => $data) {
            $amount_paid = (float) $data['amount_paid'];
            if ($amount_paid > 0) {
                $total_amount += $amount_paid;
                $detail_batch[] = [
                    // 'payment_header_id' diisi nanti
                    'ap_invoice_header_id' => $inv_id,
                    'amount_paid' => $amount_paid
                ];
            }
        }
        
        if (empty($detail_batch)) {
            throw new Exception("Tidak ada jumlah pembayaran yang diinput.");
        }

        $header_data = [
            'payment_no' => $post['payment_no'],
            'payment_date' => $post['payment_date'],
            'supplier_id' => $post['supplier_id'],
            'payment_account_id' => $post['payment_account_id'],
            'total_amount' => $total_amount,
            'notes' => $post['notes'],
            'status' => 'Draft',
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('ap_payment_header', $header_data);
        $header_id = $this->db->insert_id();

        foreach ($detail_batch as &$d) {
            $d['payment_header_id'] = $header_id;
        }
        $this->db->insert_batch('ap_payment_details', $detail_batch);

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }
    
    public function delete_draft_payment($id)
    {
        $this->db->where('id', $id);
        $this->db->where('status', 'Draft');
        $this->db->delete('ap_payment_header'); // Detail akan terhapus (ON DELETE CASCADE)
        return $this->db->affected_rows() > 0;
    }

    /**
     * ======================================
     * FUNGSI POSTING PEMBAYARAN & JURNAL
     * ======================================
     */
    public function post_payment($payment_header_id, $user_id)
    {
        $this->db->trans_start();

        $header = $this->get_header_by_id($payment_header_id);
        $details = $this->get_details_by_header_id($payment_header_id);

        if ($header->status != 'Draft') {
            throw new Exception("Hanya status Draft yang bisa di-posting.");
        }

        // 1. Update status faktur yang dibayar
        foreach ($details as $d) {
            $this->db->where('id', $d->ap_invoice_header_id);
            $this->db->set('total_paid', 'total_paid + ' . $d->amount_paid, FALSE);
            $this->db->update('ap_invoice_header');
            
            // Cek apakah lunas
            $this->db->query("
                UPDATE ap_invoice_header
                SET payment_status = IF(total_paid >= total_invoice, 'Paid', 'Partial')
                WHERE id = ?
            ", [$d->ap_invoice_header_id]);
        }
        
        // 2. Buat Jurnal
        $acc_utang_id = $this->SettingAkun_model->get_account_id('akun_utang_usaha');
        $acc_kas_bank_id = $header->payment_account_id; // Diambil dari header

        if(!$acc_utang_id || !$acc_kas_bank_id) {
             throw new Exception("Akun Utang Usaha atau Akun Pembayaran belum di-setting.");
        }
        
        $journal_no = $this->JurnalUmum_model->get_new_journal_number();
        
        // Jurnalnya simpel: Db Utang, Cr Kas/Bank
        $journal_details = [
            [
                'account_id' => $acc_utang_id,
                'debit' => $header->total_amount,
                'kredit' => 0,
                'description' => 'Pembayaran utang ke ' . $header->nama_supplier
            ],
            [
                'account_id' => $acc_kas_bank_id,
                'debit' => 0,
                'kredit' => $header->total_amount,
                'description' => 'Pembayaran via ' . $header->payment_account_name
            ]
        ];

        $journal_header_data = [
            'journal_no' => $journal_no,
            'journal_date' => $header->payment_date,
            'description' => 'Pembayaran Faktur Supplier No: ' . $header->payment_no,
            'source_module' => 'AP_PAYMENT',
            'source_doc_id' => $header->id,
            'total_debit' => $header->total_amount,
            'total_kredit' => $header->total_amount,
            'total_debit_local' => $header->total_amount,
            'total_kredit_local' => $header->total_amount,
            'currency' => 'IDR',
            'exchange_rate' => 1,
            'created_by' => $user_id,
            'status' => 'Draft'
        ];

        $journal_id = $this->JurnalUmum_model->insert_journal($journal_header_data, $journal_details);
        $this->JurnalUmum_model->post_journal($journal_id, $user_id);
        
        // 3. Update status Payment Header
        $this->db->where('id', $header->id);
        $this->db->update('ap_payment_header', [
            'status' => 'Posted',
            'journal_header_id' => $journal_id,
            'posted_by' => $user_id,
            'posted_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE)
            ? ['status' => FALSE, 'message' => 'Transaksi DB gagal']
            : ['status' => TRUE, 'journal_no' => $journal_no];
    }
}
