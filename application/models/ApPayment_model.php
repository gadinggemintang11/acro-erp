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
        $this->db->join('suppliers s', 'ap.supplier_id = s.id', 'left');
        $this->db->join('chart_of_accounts coa', 'ap.payment_account_id = coa.id', 'left');
        $this->db->order_by('ap.payment_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id)
    {
        $this->db->select('ap.*, s.nama_supplier, coa.account_code, coa.account_name as payment_account_name');
        $this->db->from('ap_payment_header ap');
        $this->db->join('suppliers s', 'ap.supplier_id = s.id', 'left');
        $this->db->join('chart_of_accounts coa', 'ap.payment_account_id = coa.id', 'left');
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

    /**
     * Ambil daftar akun kas/bank untuk pembayaran (untuk IDR saat ini).
     * User akan memilih dari dropdown ini.
     * Bisa dikembangkan untuk multi-currency.
     * 
     * @return array List of accounts
     */
    public function get_payment_accounts()
    {
        $this->db->select('id, account_code, account_name, currency, normal_balance');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->where('currency', 'IDR'); // Bisa ditambah parameter jika perlu multi-currency
        $this->db->where('account_type', 'Aset');
        
        // Filter akun kas/bank yang dimulai dengan 1.101 (Kas) atau 1.102 (Bank)
        $this->db->group_start();
        $this->db->like('account_code', '1.101', 'after');
        $this->db->or_like('account_code', '1.102', 'after');
        $this->db->group_end();
        
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    /**
     * ✨ NEW: Ambil list akun utang untuk pilihan debet saat pembayaran
     * Filter hanya akun dengan kode yang dimulai dengan 2.1 (Utang)
     */
    public function get_debet_accounts()
    {
        $this->db->select('id, account_code, account_name');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->like('account_code', '2.1', 'after'); // Filter akun utang
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    /**
     * ✨ NEW: Ambil list akun kas/bank untuk pilihan kredit saat pembayaran
     * Filter hanya akun dengan kode yang dimulai dengan 1.101 atau 1.102 (Kas/Bank)
     */
    public function get_kredit_accounts()
    {
        $this->db->select('id, account_code, account_name');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->group_start();
        $this->db->like('account_code', '1.101', 'after');
        $this->db->or_like('account_code', '1.102', 'after');
        $this->db->group_end();
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    /**
     * ✨ NEW: Ambil list akun Hutang PPh untuk pilihan saat pembayaran dengan PPh dipotong
     * Filter hanya akun dengan kode yang dimulai dengan 2.302 (Hutang PPh Ditanggung)
     */
    public function get_pph_payable_accounts()
    {
        $this->db->select('id, account_code, account_name');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->like('account_code', '2.302', 'after'); // Filter akun Hutang PPh
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    // Ambil faktur yg belum lunas (dengan detail dari ap_invoice_details)
    public function get_unpaid_invoices($supplier_id)
    {
        $this->db->select('api.id, api.invoice_no, api.invoice_date, api.total_invoice, api.total_paid, 
                          (api.total_invoice - api.total_paid) as sisa_utang');
        $this->db->from('ap_invoice_header api');
        $this->db->where('api.supplier_id', $supplier_id);
        $this->db->where('api.status', 'Posted'); // Hanya bayar yg sudah di-posting
        $this->db->where('api.payment_status !=', 'Paid');
        return $this->db->get()->result();
    }

    /**
     * ✨ NEW: Ambil detail invoice untuk ditampilkan di form pembayaran
     * Termasuk info item_type dari GRN jika ada
     */
    public function get_invoice_details_for_payment($invoice_header_id)
    {
        $this->db->select('aid.*, gd.item_type, gd.item_id');
        $this->db->from('ap_invoice_details aid');
        $this->db->join('grn_details gd', 'aid.grn_detail_id = gd.id', 'left');
        $this->db->where('aid.ap_invoice_header_id', $invoice_header_id);
        return $this->db->get()->result();
    }

    // Simpan Pembayaran (Draft)
    public function insert_payment($post)
    {
        $this->db->trans_start();
        
        // ✨ NEW: Validasi akun debet (Hutang) yang dipilih user
        $debet_account_id = isset($post['debet_account_id']) ? $post['debet_account_id'] : null;
        
        if (!$debet_account_id) {
            $this->db->trans_complete();
            throw new Exception("Silakan pilih akun DEBET (Hutang) untuk pembayaran.");
        }
        
        // ✨ NEW: Validasi akun kredit (Kas/Bank) yang dipilih user
        $kredit_account_id = isset($post['kredit_account_id']) ? $post['kredit_account_id'] : null;
        
        if (!$kredit_account_id) {
            $this->db->trans_complete();
            throw new Exception("Silakan pilih akun KREDIT (Kas/Bank) untuk pembayaran.");
        }
        
        // Validasi akun debet valid & active
        $acc_db = $this->db->where('id', $debet_account_id)->where('status', 'active')->get('chart_of_accounts')->row();
        if (!$acc_db) {
            $this->db->trans_complete();
            throw new Exception("Akun DEBET yang dipilih tidak valid atau tidak aktif.");
        }
        
        // Validasi akun kredit valid & active
        $acc_kr = $this->db->where('id', $kredit_account_id)->where('status', 'active')->get('chart_of_accounts')->row();
        if (!$acc_kr) {
            $this->db->trans_complete();
            throw new Exception("Akun KREDIT yang dipilih tidak valid atau tidak aktif.");
        }

        $details = $post['details'];
        $total_amount = 0;
        $detail_batch = [];

        foreach ($details as $inv_id => $data) {
            $amount_paid = (float) $data['amount_paid'];
            if ($amount_paid > 0) {
                // Validasi tidak overpayment
                $invoice = $this->db->where('id', $inv_id)->get('ap_invoice_header')->row();
                if ($invoice) {
                    $sisa_utang = $invoice->total_invoice - $invoice->total_paid;
                    if ($amount_paid > $sisa_utang + 0.01) {
                        $this->db->trans_complete();
                        throw new Exception("Pembayaran untuk invoice {$invoice->invoice_no} melebihi sisa utang (Max: {$sisa_utang}).");
                    }
                }
                
                $total_amount += $amount_paid;
                $detail_batch[] = [
                    'ap_invoice_header_id' => $inv_id,
                    'amount_paid' => $amount_paid
                ];
            }
        }
        
        if (empty($detail_batch)) {
            $this->db->trans_complete();
            throw new Exception("Tidak ada jumlah pembayaran yang diinput.");
        }
        
        if ($total_amount <= 0) {
            $this->db->trans_complete();
            throw new Exception("Total pembayaran harus lebih dari 0.");
        }

        // ✨ NEW: Validasi dan hitung PPh jika ada potong pajak
        $pph_rate = isset($post['pph_rate']) ? (float)$post['pph_rate'] : 0;
        $pph_amount = 0;
        $pph_account_id = null;
        
        if ($pph_rate > 0) {
            $pph_amount = $total_amount * ($pph_rate / 100);
            $pph_account_id = isset($post['pph_account_id']) ? $post['pph_account_id'] : null;
            
            if (!$pph_account_id) {
                $this->db->trans_complete();
                throw new Exception("Akun Hutang PPh harus dipilih jika ada potong pajak.");
            }
            
            // Validasi akun PPh valid & active
            $acc_pph = $this->db->where('id', $pph_account_id)->where('status', 'active')->get('chart_of_accounts')->row();
            if (!$acc_pph) {
                $this->db->trans_complete();
                throw new Exception("Akun Hutang PPh yang dipilih tidak valid atau tidak aktif.");
            }
        }

        // ✨ NEW: Simpan debet_account_id dan kredit_account_id di header (BUKAN detail)
        // Semua invoice dalam satu payment batch menggunakan account mapping yang sama
        $header_data = [
            'payment_no' => $post['payment_no'],
            'payment_date' => $post['payment_date'],
            'supplier_id' => $post['supplier_id'],
            'debet_account_id' => $debet_account_id,      // User pilih akun hutang mana yang di-debet
            'kredit_account_id' => $kredit_account_id,    // User pilih akun kas/bank mana yang di-kredit
            'total_amount' => $total_amount,
            // ✨ NEW: Simpan PPh rate, amount, dan account untuk potong pajak
            'pph_rate' => $pph_rate,
            'pph_amount' => $pph_amount,
            'pph_account_id' => $pph_account_id,
            'notes' => isset($post['notes']) ? $post['notes'] : null,
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
        
        // 2. ✨ NEW: Buat Jurnal dengan mapping account dari header (BUKAN per detail)
        // Debet: Akun Hutang (user pilih)
        // Kredit: Akun Kas/Bank (user pilih)
        $debet_account_id = $header->debet_account_id;
        $kredit_account_id = $header->kredit_account_id;

        if (!$debet_account_id || !$kredit_account_id) {
             throw new Exception("Akun DEBET (Hutang) atau KREDIT (Kas/Bank) belum dipilih.");
        }
        
        $journal_no = $this->JurnalUmum_model->get_new_journal_number();
        
        // ✨ NEW: Jurnal dengan header-level account mapping
        // Debit: Akun Hutang (semua invoice dalam batch membayar dari akun hutang yang sama)
        // Kredit: Akun Kas/Bank (pembayaran keluar dari kas/bank yang dipilih)
        // Jika ada PPh: Kredit tambahan ke Hutang PPh
        $journal_details = [];
        
        // Entry DEBIT: Hutang berkurang (full amount)
        $journal_details[] = [
            'account_id' => $debet_account_id,
            'debit' => $header->total_amount,
            'kredit' => 0,
            'description' => 'Pembayaran Supplier (Hutang berkurang)'
        ];
        
        // Entry KREDIT: Kas/Bank berkurang (netto = after PPh if any)
        $kas_amount = $header->total_amount - $header->pph_amount;
        $journal_details[] = [
            'account_id' => $kredit_account_id,
            'debit' => 0,
            'kredit' => $kas_amount,
            'description' => 'Pembayaran Supplier (Kas/Bank berkurang)'
        ];
        
        // ✨ NEW: Entry KREDIT PPh jika ada potong pajak
        if ($header->pph_rate > 0 && $header->pph_amount > 0 && $header->pph_account_id) {
            $journal_details[] = [
                'account_id' => $header->pph_account_id,
                'debit' => 0,
                'kredit' => $header->pph_amount,
                'description' => 'PPh 23 Ditanggung Supplier (' . $header->pph_rate . '%)'
            ];
        }

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
