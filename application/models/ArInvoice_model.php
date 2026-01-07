<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ArInvoice_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('JurnalUmum_model');
        $this->load->model('SettingAkun_model');
        $this->load->model('Coa_model');
    }

    public function get_all_invoice_headers()
    {
        $this->db->select('ar.*, c.nama_customer, c.jenis as customer_type');
        $this->db->from('ar_invoice_header ar');
        $this->db->join('customers c', 'ar.customer_id = c.id');
        $this->db->order_by('ar.invoice_date', 'DESC');
        $this->db->order_by('ar.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_customers()
    {
        return $this->db->where('status', 'active')->get('customers')->result();
    }
    
    public function get_pending_fg_delivery($customer_id)
    {
        $this->db->select("
            fdh.id, 
            fdh.delivery_number, 
            fdh.delivery_date, 
            sc.sc_number, 
            sc.currency_code
        ");
        $this->db->from('fg_delivery_header fdh');
        $this->db->join('fg_delivery_details fdd', 'fdd.fg_delivery_header_id = fdh.id', 'left');
        $this->db->join('sales_contracts sc', 'fdd.sales_contract_id = sc.id', 'left');
        $this->db->where('fdh.customer_id', $customer_id);
        $this->db->where('fdh.status', 'Confirmed'); 
        
        $this->db->where("NOT EXISTS (
            SELECT 1 FROM ar_invoice_header ar 
            WHERE ar.source_type = 'FG_DELIVERY'
            AND JSON_SEARCH(ar.source_header_ids, 'one', CAST(fdh.id AS CHAR)) IS NOT NULL
            AND ar.status != 'Cancelled'
        )", NULL, FALSE);
        
        $this->db->group_by('fdh.id');
        return $this->db->get()->result();
    }

    public function get_pending_scrap_out($customer_id)
    {
        $this->db->select('soh.id, soh.doc_no, soh.doc_date, soh.bc_number, \'IDR\' as currency_code');
        $this->db->from('scrap_out_header soh');
        $this->db->where('soh.destination', $customer_id);
        $this->db->where('soh.status', 'Confirmed');
        
        $this->db->where("NOT EXISTS (
            SELECT 1 FROM ar_invoice_header ar 
            WHERE ar.source_type = 'SCRAP_OUT'
            AND JSON_SEARCH(ar.source_header_ids, 'one', CAST(soh.id AS CHAR)) IS NOT NULL
            AND ar.status != 'Cancelled'
        )", NULL, FALSE);

        return $this->db->get()->result();
    }
    
    public function get_details_from_fg_delivery($source_ids = [])
    {
        $this->db->select('
            fdd.id as source_detail_id, 
            fdd.fg_id as item_id, 
            \'finished_goods\' as item_type, 
            fdd.qty_shipped as qty,
            fg.nama_barang as description,
            sc.unit_price, 
            (fdd.qty_shipped * sc.unit_price) as total_price,
            sc.currency_code
        ');
        $this->db->from('fg_delivery_details fdd');
        $this->db->join('finished_goods fg', 'fdd.fg_id = fg.id');
        $this->db->join('sales_contracts sc', 'fdd.sales_contract_id = sc.id', 'left');
        $this->db->where_in('fdd.fg_delivery_header_id', $source_ids);
        return $this->db->get()->result();
    }
    
    public function get_details_from_scrap_out($source_ids = [])
    {
        $this->db->select('
            sod.id as source_detail_id, 
            sod.scrap_id as item_id, 
            \'scrap\' as item_type, 
            sod.qty,
            ms.scrap_name as description,
            0 as unit_price, 
            0 as total_price,
            \'IDR\' as currency_code
        ');
        $this->db->from('scrap_out_detail sod');
        $this->db->join('master_scrap ms', 'sod.scrap_id = ms.id');
        $this->db->where_in('sod.header_id', $source_ids);
        return $this->db->get()->result();
    }

    public function insert_invoice($post_data)
    {
        $this->db->trans_start();

        $details = $post_data['details'];
        $source_type = $post_data['source_type'];
        $currency = $post_data['currency'];
        $rate = (float) $post_data['exchange_rate'];
        $ppn_rate = (float) ($post_data['ppn_rate'] ?? 0);
        
        $subtotal = 0;
        $subtotal_local = 0;
        $detail_batch = [];

        foreach ($details as $d) {
            $unit_price = (float) $d['unit_price'];
            $qty = (float) $d['qty'];
            $total_price = $qty * $unit_price;
            $total_price_local = $total_price * $rate;
            
            $subtotal += $total_price;
            $subtotal_local += $total_price_local;
            
            $detail_data = [
                'source_detail_id' => $d['source_detail_id'],
                'item_id' => $d['item_id'],
                'item_type' => $d['item_type'],
                'description' => $d['description'],
                'qty' => $qty,
                'unit_price' => $unit_price,
                'total_price' => $total_price,
                'unit_price_local' => $unit_price * $rate,
                'total_price_local' => $total_price_local
            ];
            $detail_batch[] = $detail_data;
        }

        $ppn_amount = $subtotal * ($ppn_rate / 100);
        $total_invoice = $subtotal + $ppn_amount;
        
        $ppn_local = $subtotal_local * ($ppn_rate / 100);
        $total_invoice_local = $subtotal_local + $ppn_local;

        $header_data = [
            'invoice_no' => $this->get_new_invoice_number(),
            'invoice_date' => $post_data['invoice_date'],
            'due_date' => $post_data['due_date'],
            'customer_id' => $post_data['customer_id'],
            'source_type' => $source_type,
            'source_header_ids' => json_encode($post_data['source_ids']),
            'currency' => $currency,
            'exchange_rate' => $rate,
            'subtotal' => $subtotal,
            'ppn_amount' => $ppn_amount,
            'pph_amount' => 0, 
            'total_invoice' => $total_invoice,
            'subtotal_local' => $subtotal_local,
            'ppn_local' => $ppn_local,
            'pph_local' => 0,
            'total_invoice_local' => $total_invoice_local,
            'total_paid' => 0,
            'payment_status' => 'Unpaid',
            'status' => 'Draft',
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('ar_invoice_header', $header_data);
        $header_id = $this->db->insert_id();

        foreach ($detail_batch as &$d) {
            $d['ar_invoice_header_id'] = $header_id;
        }
        $this->db->insert_batch('ar_invoice_details', $detail_batch);

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }
    
    public function get_new_invoice_number()
    {
        $this->db->select('RIGHT(invoice_no, 5) as kode', FALSE);
        $this->db->order_by('invoice_no', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get_where('ar_invoice_header', ['YEAR(invoice_date)' => date('Y')]);
        
        $kode = ($query->num_rows() > 0) ? intval($query->row()->kode) + 1 : 1;
        $kode_max = str_pad($kode, 5, "0", STR_PAD_LEFT);
        return "INV/" . date('Ym') . "/" . $kode_max;
    }

    public function get_invoice_header_by_id($id)
    {
        $this->db->select('ar.*, c.nama_customer, c.alamat as customer_address, c.npwp');
        $this->db->from('ar_invoice_header ar');
        $this->db->join('customers c', 'ar.customer_id = c.id');
        $this->db->where('ar.id', $id);
        return $this->db->get()->row();
    }
    
    public function get_invoice_details_by_header_id($header_id)
    {
        $this->db->select('ad.*');
        $this->db->from('ar_invoice_details ad');
        $this->db->where('ad.ar_invoice_header_id', $header_id);
        return $this->db->get()->result();
    }
    
    public function delete_draft_invoice($id)
    {
        $this->db->where('id', $id);
        $this->db->where('status', 'Draft');
        $this->db->delete('ar_invoice_header');
        return $this->db->affected_rows() > 0;
    }

    public function post_invoice_journal($header_id, $user_id)
    {
        $this->db->trans_start();
        
        $header = $this->get_invoice_header_by_id($header_id);
        $details = $this->get_invoice_details_by_header_id($header_id);
        $currency = $header->currency;

        if ($header->status != 'Draft') {
            throw new Exception("Hanya status Draft yang bisa di-posting.");
        }
        
        // Akun dari setting (1 akun untuk semua currency)
        $acc_piutang_id = $this->SettingAkun_model->get_account_id('akun_piutang_usaha', 'IDR');
        $acc_penjualan_fg_id = $this->SettingAkun_model->get_account_id('akun_penjualan_fg', 'IDR');
        $acc_penjualan_scrap_id = $this->SettingAkun_model->get_account_id('akun_penjualan_scrap', 'IDR');
        $acc_ppn_id = $this->SettingAkun_model->get_account_id('akun_ppn_keluaran', 'IDR');
        $acc_hpp_id = $this->SettingAkun_model->get_account_id('akun_hpp_fg', 'IDR');
        $acc_persediaan_id = $this->SettingAkun_model->get_account_id('akun_persediaan_fg', 'IDR');

        if(!$acc_piutang_id || !$acc_penjualan_fg_id || !$acc_penjualan_scrap_id) {
            throw new Exception("Akun Piutang/Penjualan (FG/Scrap) belum di-setting.");
        }

        // Tidak perlu validasi currency - 1 akun piutang untuk semua currency
        // Currency disimpan di field transaksi, bukan di akun COA

        $journal_no = $this->JurnalUmum_model->get_new_journal_number();
        $journal_details = [];

        // Debit Piutang
        $journal_details[] = [
            'account_id' => $acc_piutang_id,
            'debit' => $header->total_invoice,
            'kredit' => 0,
            'description' => 'Piutang atas faktur ' . $header->invoice_no
        ];
        
        // Kredit PPN
        if ($header->ppn_amount > 0) {
            $journal_details[] = [
                'account_id' => $acc_ppn_id,
                'debit' => 0,
                'kredit' => $header->ppn_amount,
                'description' => 'PPN Keluaran ' . $header->invoice_no
            ];
        }

        // Kredit Penjualan
        $total_penjualan_fg = 0;
        $total_penjualan_scrap = 0;
        foreach($details as $d) {
            if ($d->item_type == 'finished_goods') {
                $total_penjualan_fg += $d->total_price;
            } else if ($d->item_type == 'scrap') {
                $total_penjualan_scrap += $d->total_price;
            }
        }

        if ($total_penjualan_fg > 0) {
            $journal_details[] = [
                'account_id' => $acc_penjualan_fg_id,
                'debit' => 0,
                'kredit' => $total_penjualan_fg,
                'description' => 'Penjualan Barang Jadi ' . $header->invoice_no
            ];
        }
        if ($total_penjualan_scrap > 0) {
            $journal_details[] = [
                'account_id' => $acc_penjualan_scrap_id,
                'debit' => 0,
                'kredit' => $total_penjualan_scrap,
                'description' => 'Penjualan Scrap ' . $header->invoice_no
            ];
        }

        // Simpan jurnal utama
        $journal_header_data = [
            'journal_no' => $journal_no,
            'journal_date' => $header->invoice_date,
            'description' => 'Piutang Faktur Customer ' . $header->invoice_no,
            'source_module' => 'AR_INVOICE',
            'source_doc_id' => $header_id,
            'currency' => $header->currency,
            'exchange_rate' => $header->exchange_rate,
            'total_debit' => $header->total_invoice,
            'total_kredit' => $header->total_invoice,
            'total_debit_local' => $header->total_invoice_local,
            'total_kredit_local' => $header->total_invoice_local,
            'created_by' => $user_id,
            'status' => 'Draft'
        ];

        $journal_id = $this->JurnalUmum_model->insert_journal($journal_header_data, $journal_details);
        $this->JurnalUmum_model->post_journal($journal_id, $user_id);

        // Update status header
        $this->db->where('id', $header_id);
        $this->db->update('ar_invoice_header', [
            'status' => 'Posted',
            'journal_header_id' => $journal_id,
            'confirmed_by' => $user_id,
            'confirmed_at' => date('Y-m-d H:i:s')
        ]);
        
        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE)
            ? ['status' => FALSE, 'message' => 'Transaksi DB gagal']
            : ['status' => TRUE, 'journal_no' => $journal_no];
    }
}
