<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ArReceipt_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('JurnalUmum_model');
    }

    public function get_all_receipt_headers()
    {
        $this->db->select('ar.*, c.nama_customer, coa.account_name as payment_account_name');
        $this->db->from('ar_receipt_header ar');
        $this->db->join('customers c', 'ar.customer_id = c.id');
        $this->db->join('chart_of_accounts coa', 'ar.payment_account_id = coa.id');
        $this->db->order_by('ar.receipt_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id)
    {
        $this->db->select('ar.*, c.nama_customer, coa.account_code, coa.account_name as payment_account_name');
        $this->db->from('ar_receipt_header ar');
        $this->db->join('customers c', 'ar.customer_id = c.id');
        $this->db->join('chart_of_accounts coa', 'ar.payment_account_id = coa.id');
        $this->db->where('ar.id', $id);
        return $this->db->get()->row();
    }
    
    public function get_details_by_header_id($header_id)
    {
        $this->db->select('ard.*, ari.invoice_no, ari.invoice_date, ari.total_invoice');
        $this->db->from('ar_receipt_details ard');
        $this->db->join('ar_invoice_header ari', 'ard.ar_invoice_header_id = ari.id');
        $this->db->where('ard.receipt_header_id', $header_id);
        return $this->db->get()->result();
    }
    
    public function get_new_receipt_number()
    {
        // Ambil kode terakhir untuk tahun berjalan
        $this->db->select("RIGHT(receipt_no, 5) as kode", FALSE);
        $this->db->where('YEAR(receipt_date)', date('Y'));
        $this->db->order_by('receipt_no', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get('ar_receipt_header');

        $kode = ($query->num_rows() > 0) ? intval($query->row()->kode) + 1 : 1;
        $kode_max = str_pad($kode, 5, "0", STR_PAD_LEFT);
        return "ARP/" . date('Ym') . "/" . $kode_max;
    }

    public function get_payment_accounts_by_currency($currency)
    {
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->where('currency', $currency);
        $this->db->where('account_type', 'Aset');
        // bila akun kas/bank diawali dengan kode 1-11 (sesuaikan pola jika beda)
        $this->db->like('account_code', '1-11', 'after'); 
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    public function get_unpaid_invoices_by_currency($customer_id, $currency)
    {
        $this->db->select('id, invoice_no, invoice_date, total_invoice, total_paid, (total_invoice - total_paid) as sisa_piutang', FALSE);
        $this->db->from('ar_invoice_header');
        $this->db->where('customer_id', $customer_id);
        $this->db->where('currency', $currency);
        $this->db->where('status', 'Posted');
        $this->db->where('payment_status !=', 'Paid');
        return $this->db->get()->result();
    }

    public function insert_receipt($post)
    {
        $this->db->trans_start();

        $details = isset($post['details']) ? $post['details'] : [];
        $rate = (float) (isset($post['exchange_rate']) ? $post['exchange_rate'] : 1);
        $total_amount = 0;
        $total_amount_local = 0;
        $detail_batch = [];

        foreach ($details as $inv_id => $data) {
            $amount_paid = (float) (isset($data['amount_paid']) ? $data['amount_paid'] : 0);
            if ($amount_paid > 0) {
                $amount_paid_local = $amount_paid * $rate;
                $total_amount += $amount_paid;
                $total_amount_local += $amount_paid_local;
                
                $detail_batch[] = [
                    'receipt_header_id' => 0, // akan diisi setelah header disimpan
                    'ar_invoice_header_id' => $inv_id,
                    'amount_paid' => $amount_paid,
                    'amount_paid_local' => $amount_paid_local
                ];
            }
        }
        
        if (empty($detail_batch)) {
            // batalkan transaksi dan throw; caller harus menangkapnya
            $this->db->trans_complete();
            throw new Exception("Tidak ada jumlah pembayaran yang diinput.");
        }

        $header_data = [
            'receipt_no' => $post['receipt_no'],
            'receipt_date' => $post['receipt_date'],
            'customer_id' => $post['customer_id'],
            'payment_account_id' => $post['payment_account_id'],
            'currency' => $post['currency'],
            'exchange_rate' => $rate,
            'total_amount' => $total_amount,
            'total_amount_local' => $total_amount_local,
            'notes' => isset($post['notes']) ? $post['notes'] : null,
            'status' => 'Draft',
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('ar_receipt_header', $header_data);
        $header_id = $this->db->insert_id();

        // update receipt_header_id di setiap detail sebelum insert_batch
        foreach ($detail_batch as &$d) {
            $d['receipt_header_id'] = $header_id;
        }
        unset($d);

        $this->db->insert_batch('ar_receipt_details', $detail_batch);

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }
    
    public function delete_draft_receipt($id)
    {
        $this->db->where('id', $id);
        $this->db->where('status', 'Draft');
        $this->db->delete('ar_receipt_header'); 
        return $this->db->affected_rows() > 0;
    }

    /**
     * Posting receipt:
     * - update total_paid di ar_invoice_header
     * - set payment_status (Paid/Partial)
     * - membuat jurnal (menggunakan JurnalUmum_model)
     * - ubah header receipt => status Posted dan simpan journal_header_id
     *
     * @param int $receipt_header_id
     * @param int $user_id
     * @param object $SettingAkun_model instance model setting akun (harus disuntikkan dari caller)
     * @return array ['status' => bool, 'journal_no' => string] atau throws Exception
     */
    public function post_receipt($receipt_header_id, $user_id, $SettingAkun_model)
    {
        $this->db->trans_start();

        $header = $this->get_header_by_id($receipt_header_id);
        if (!$header) {
            $this->db->trans_complete();
            throw new Exception("Header penerimaan tidak ditemukan.");
        }

        $details = $this->get_details_by_header_id($receipt_header_id);

        if ($header->status != 'Draft') {
            $this->db->trans_complete();
            throw new Exception("Hanya status Draft yang bisa di-posting.");
        }

        // Update total_paid per invoice dan set payment_status
        foreach ($details as $d) {
            $this->db->where('id', $d->ar_invoice_header_id);
            $this->db->set('total_paid', 'total_paid + ' . (float)$d->amount_paid, FALSE);
            $this->db->update('ar_invoice_header');

            // set payment_status berdasarkan pembulatan 2 desimal
            $this->db->query("
                UPDATE ar_invoice_header
                SET payment_status = IF(ROUND(total_paid, 2) >= ROUND(total_invoice, 2), 'Paid', 'Partial')
                WHERE id = ?
            ", [$d->ar_invoice_header_id]);
        }
        
        // Ambil akun piutang dan akun kas/bank
        $acc_piutang_id = $SettingAkun_model->get_account_id('akun_piutang_usaha', $header->currency);
        $acc_kas_bank_id = $header->payment_account_id; 

        if(!$acc_piutang_id || !$acc_kas_bank_id) {
            $this->db->trans_complete();
            throw new Exception("Akun Piutang atau Akun Penerimaan (Kas/Bank) belum di-setting.");
        }
        
        $journal_no = $this->JurnalUmum_model->get_new_journal_number();
        
        $journal_details = [
            [
                'account_id' => $acc_kas_bank_id,
                'debit' => $header->total_amount,
                'kredit' => 0,
                'description' => 'Penerimaan piutang dari ' . $header->nama_customer
            ],
            [
                'account_id' => $acc_piutang_id,
                'debit' => 0,
                'kredit' => $header->total_amount,
                'description' => 'Pelunasan piutang via ' . $header->receipt_no
            ]
        ];

        $journal_header_data = [
            'journal_no' => $journal_no,
            'journal_date' => $header->receipt_date,
            'description' => 'Penerimaan Piutang No: ' . $header->receipt_no,
            'source_module' => 'AR_RECEIPT',
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
        // post journal (ubah status jurnal menjadi Posted, dll.)
        $this->JurnalUmum_model->post_journal($journal_id, $user_id);
        
        // update header receipt => Posted
        $this->db->where('id', $header->id);
        $this->db->update('ar_receipt_header', [
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
