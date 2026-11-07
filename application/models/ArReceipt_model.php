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
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('ar_receipt', 'ar_receipt_header', 'receipt_no');
    }

    /**
     * Ambil daftar akun kas/bank untuk penerimaan berdasarkan currency.
     * User akan memilih dari dropdown ini.
     * 
     * @param string $currency IDR, USD, RMB
     * @return array List of accounts
     */
    public function get_payment_accounts_by_currency($currency)
    {
        $this->db->select('id, account_code, account_name, currency, normal_balance');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->where('currency', $currency);
        $this->db->where('account_type', 'Aset');
        
        // Filter akun kas/bank yang dimulai dengan 1.101 (Kas) atau 1.102 (Bank)
        $this->db->group_start();
        $this->db->like('account_code', '1.101', 'after');
        $this->db->or_like('account_code', '1.102', 'after');
        $this->db->group_end();
        
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

        // Validasi input
        $details = isset($post['details']) ? $post['details'] : [];
        $currency = $post['currency'];
        $rate = (float) (isset($post['exchange_rate']) ? $post['exchange_rate'] : 1);
        $payment_account_id = isset($post['payment_account_id']) ? $post['payment_account_id'] : null;
        
        // Validasi kurs
        if ($currency != 'IDR' && $rate <= 0) {
            $this->db->trans_complete();
            throw new Exception("Kurs harus lebih besar dari 0 untuk mata uang {$currency}.");
        }
        
        // Validasi akun kas/bank yang dipilih user
        if (!$payment_account_id) {
            $this->db->trans_complete();
            throw new Exception("Silakan pilih akun kas/bank untuk menerima pembayaran.");
        }
        
        // Validasi akun valid, aktif, dan sesuai currency
        $this->db->where('id', $payment_account_id);
        $this->db->where('status', 'active');
        $this->db->where('is_header', 0);
        $account = $this->db->get('chart_of_accounts')->row();
        
        if (!$account) {
            $this->db->trans_complete();
            throw new Exception("Akun kas/bank yang dipilih tidak valid atau tidak aktif.");
        }
        
        if ($account->currency != $currency) {
            $this->db->trans_complete();
            throw new Exception("Currency akun ({$account->currency}) tidak sesuai dengan currency penerimaan ({$currency}). Pilih akun {$currency}.");
        }
        
        // Validasi akun adalah kas/bank (kode 1.101 atau 1.102)
        if (!preg_match('/^1\.10[12]/', $account->account_code)) {
            $this->db->trans_complete();
            throw new Exception("Akun yang dipilih bukan akun Kas/Bank. Pilih akun dengan kode 1.101.x atau 1.102.x");
        }

        $total_amount = 0;
        $total_amount_local = 0;
        $detail_batch = [];

        foreach ($details as $inv_id => $data) {
            $amount_paid = (float) (isset($data['amount_paid']) ? $data['amount_paid'] : 0);
            if ($amount_paid > 0) {
                // Validasi tidak overpayment
                $invoice = $this->db->where('id', $inv_id)->get('ar_invoice_header')->row();
                if ($invoice) {
                    $sisa_piutang = $invoice->total_invoice - $invoice->total_paid;
                    if ($amount_paid > $sisa_piutang + 0.01) { // tolerance 0.01
                        $this->db->trans_complete();
                        throw new Exception("Pembayaran untuk invoice {$invoice->invoice_no} melebihi sisa piutang (Max: {$sisa_piutang}).");
                    }
                }
                
                $amount_paid_local = $amount_paid * $rate;
                $total_amount += $amount_paid;
                $total_amount_local += $amount_paid_local;
                
                $detail_batch[] = [
                    'receipt_header_id' => 0,
                    'ar_invoice_header_id' => $inv_id,
                    'amount_paid' => $amount_paid,
                    'amount_paid_local' => $amount_paid_local
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

        $header_data = [
            'receipt_no' => $post['receipt_no'],
            'receipt_date' => $post['receipt_date'],
            'customer_id' => $post['customer_id'],
            'payment_account_id' => $payment_account_id, // Dari setting
            'currency' => $currency,
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
        $acc_exchange_gain_id = $SettingAkun_model->get_account_id('akun_exchange_gain', 'IDR');
        $acc_exchange_loss_id = $SettingAkun_model->get_account_id('akun_exchange_loss', 'IDR');

        if(!$acc_piutang_id) {
            $this->db->trans_complete();
            throw new Exception("Akun Piutang Usaha ({$header->currency}) belum di-setting di menu Setting Akun.");
        }
        
        if(!$acc_kas_bank_id) {
            $this->db->trans_complete();
            throw new Exception("Akun Penerimaan ({$header->currency}) belum di-setting di menu Setting Akun.");
        }
        
        // Untuk currency non-IDR, validasi akun exchange gain/loss
        if ($header->currency != 'IDR' && (!$acc_exchange_gain_id || !$acc_exchange_loss_id)) {
            $this->db->trans_complete();
            throw new Exception("Akun Exchange Gain/Loss belum di-setting di menu Setting Akun. Diperlukan untuk transaksi mata uang {$header->currency}.");
        }
        
        // Validasi akun masih aktif
        $this->db->where_in('id', [$acc_piutang_id, $acc_kas_bank_id]);
        $this->db->where('status', 'active');
        if ($this->db->count_all_results('chart_of_accounts') < 2) {
            $this->db->trans_complete();
            throw new Exception("Salah satu akun tidak aktif. Periksa Setting Akun.");
        }
        
        // Hitung total selisih kurs untuk semua invoices
        // PENTING: Selisih dihitung langsung dalam IDR (local currency)
        $total_exchange_difference = 0;
        
        foreach ($details as $d) {
            // Ambil data invoice original
            $invoice = $this->db->where('id', $d->ar_invoice_header_id)->get('ar_invoice_header')->row();
            if ($invoice) {
                // PERHITUNGAN EXCHANGE DIFFERENCE LANGSUNG DALAM IDR:
                // amount_paid = dalam currency asli (misal USD 1,200)
                // invoice->exchange_rate = kurs saat invoice dibuat (misal 16,000)
                // header->exchange_rate = kurs saat pembayaran (misal 15,800)
                // 
                // Nilai dalam IDR:
                //   Piutang (saat Invoice): amount_paid × invoice->exchange_rate = 1,200 × 16,000 = 19,200,000 IDR
                //   Kas (saat Bayar):       amount_paid × header->exchange_rate  = 1,200 × 15,800 = 18,960,000 IDR
                //   Selisih (IDR):          19,200,000 - 18,960,000 = 240,000 IDR (LOSS jika positif)
                
                $amount_at_invoice_rate = $d->amount_paid * $invoice->exchange_rate;
                $amount_at_receipt_rate = $d->amount_paid * $header->exchange_rate;
                
                $difference = $amount_at_invoice_rate - $amount_at_receipt_rate;
                $total_exchange_difference += $difference; // Sudah dalam IDR!
            }
        }
        
        $journal_no = $this->JurnalUmum_model->get_new_journal_number();
        
        // PENTING: Untuk transaksi dengan selisih kurs, kita HARUS menggunakan IDR
        // karena setiap baris jurnal bisa memiliki equivalent IDR yang berbeda
        // (Bank pakai kurs payment, Piutang pakai kurs invoice)
        
        // Hitung nilai dalam IDR
        $bank_amount_idr = $header->total_amount * $header->exchange_rate;
        
        // Hitung total piutang dalam IDR (pakai kurs invoice original)
        $piutang_amount_idr = 0;
        foreach ($details as $d) {
            $invoice = $this->db->where('id', $d->ar_invoice_header_id)->get('ar_invoice_header')->row();
            if ($invoice) {
                $piutang_amount_idr += $d->amount_paid * $invoice->exchange_rate;
            }
        }
        
        $journal_details = [
            [
                'account_id' => $acc_kas_bank_id,
                'debit' => $bank_amount_idr, // IDR value dengan kurs payment
                'kredit' => 0,
                'description' => 'Penerimaan piutang dari ' . $header->nama_customer
            ],
            [
                'account_id' => $acc_piutang_id,
                'debit' => 0,
                'kredit' => $piutang_amount_idr, // IDR value dengan kurs invoice
                'description' => 'Pelunasan piutang via ' . $header->receipt_no
            ]
        ];
        
        // Tambahkan jurnal exchange gain/loss jika ada selisih kurs
        // Selisih = Bank (IDR) - Piutang (IDR)
        $exchange_difference_idr = $bank_amount_idr - $piutang_amount_idr;
        
        if (abs($exchange_difference_idr) > 0.01) { // tolerance untuk rounding
            
            if ($exchange_difference_idr < 0) {
                // Rugi kurs (exchange loss) - Bank lebih kecil dari Piutang
                // Kurs turun saat bayar
                $journal_details[] = [
                    'account_id' => $acc_exchange_loss_id,
                    'debit' => abs($exchange_difference_idr), // IDR value
                    'kredit' => 0,
                    'description' => 'Rugi selisih kurs ' . $header->receipt_no
                ];
            } else {
                // Untung kurs (exchange gain) - Bank lebih besar dari Piutang
                // Kurs naik saat bayar
                // Untung Kurs = Income/Pendapatan, posisi normal KREDIT
                $journal_details[] = [
                    'account_id' => $acc_exchange_gain_id,
                    'debit' => 0,
                    'kredit' => $exchange_difference_idr, // IDR value
                    'description' => 'Untung selisih kurs ' . $header->receipt_no
                ];
            }
        }

        
        // Hitung total debit dan kredit dari journal_details
        $total_journal_debit = 0;
        $total_journal_kredit = 0;
        foreach ($journal_details as $jd) {
            $total_journal_debit += (float)$jd['debit'];
            $total_journal_kredit += (float)$jd['kredit'];
        }
        
        // Karena sudah dalam IDR, gunakan currency IDR dengan rate 1
        $journal_header_data = [
            'journal_no' => $journal_no,
            'journal_date' => $header->receipt_date,
            'description' => 'Penerimaan Piutang No: ' . $header->receipt_no,
            'source_module' => 'AR_RECEIPT',
            'source_doc_id' => $header->id,
            'currency' => 'IDR', // Gunakan IDR karena setiap baris punya kurs berbeda
            'exchange_rate' => 1, // Rate 1 karena sudah IDR
            'total_debit' => $total_journal_debit,
            'total_kredit' => $total_journal_kredit,
            'total_debit_local' => $total_journal_debit,
            'total_kredit_local' => $total_journal_kredit,
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
