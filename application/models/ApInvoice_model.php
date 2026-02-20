<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ApInvoice_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('JurnalUmum_model', 'jurnal');
        $this->load->model('Coa_model', 'coa');
    }

    /**
     * ======================================
     * FUNGSI UNTUK HALAMAN LIST (INDEX)
     * ======================================
     */
    public function get_all_invoice_headers()
    {
        $this->db->select('
            ap.*, 
            s.nama_supplier,
            s.jenis as supplier_type,
            (SELECT GROUP_CONCAT(DISTINCT gh.base_on) 
             FROM grn_header gh 
             WHERE JSON_SEARCH(ap.grn_ids, "one", gh.id) IS NOT NULL
            ) as grn_base_on
        ', FALSE);
        $this->db->from('ap_invoice_header ap');
        $this->db->join('suppliers s', 'ap.supplier_id = s.id');
        $this->db->order_by('ap.invoice_date', 'DESC');
        $this->db->order_by('ap.id', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * ======================================
     * FUNGSI UNTUK HALAMAN CREATE
     * ======================================
     */
    public function get_suppliers()
    {
        return $this->db->where('status', 'active')->get('suppliers')->result();
    }
    
    public function get_postable_accounts_for_expense()
    {
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->where_in('account_type', ['Biaya', 'Aset', 'HPP']);
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    /**
     * ✨ NEW: Ambil list akun persediaan untuk pilihan debet saat pembuatan invoice
     * Filter akun dengan kode: 1.105, 1.106, 1.107, 1.108, 1.109 (Persediaan)
     */
    public function get_debet_accounts()
    {
        $this->db->select('id, account_code, account_name');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->group_start();
        $this->db->like('account_code', '1.105', 'after');
        $this->db->or_like('account_code', '1.106', 'after');
        $this->db->or_like('account_code', '1.107', 'after');
        $this->db->or_like('account_code', '1.108', 'after');
        $this->db->or_like('account_code', '1.109', 'after');
        $this->db->group_end();
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    /**
     * ✨ NEW: Ambil list akun utang untuk pilihan kredit saat pembuatan invoice
     * Filter hanya akun dengan kode yang dimulai dengan 2.1 (Utang)
     */
    public function get_kredit_accounts()
    {
        $this->db->select('id, account_code, account_name');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->like('account_code', '2.1', 'after'); // Filter akun hutang
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    /**
     * ✨ NEW: Ambil list akun PPN Masukan (pajak input) untuk pilihan pajak
     * Filter hanya akun dengan kode yang dimulai dengan 1.118 (PPN Masukan)
     */
    public function get_ppn_accounts()
    {
        $this->db->select('id, account_code, account_name');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->like('account_code', '1.118', 'after'); // Filter akun PPN Masukan
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    /**
     * ✨ NEW: Ambil list akun PPh Ditanggung (pajak withholding) untuk pilihan pajak
     * Filter hanya akun dengan kode yang dimulai dengan 1.119 (PPh Ditanggung)
     */
    public function get_pph_accounts()
    {
        $this->db->select('id, account_code, account_name');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->like('account_code', '1.119', 'after'); // Filter akun PPh Ditanggung
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }
    
    public function get_pending_grn($supplier_id)
    {
        $this->db->select('gh.id, gh.grn_number, gh.grn_date, gh.base_on,
                          CASE 
                            WHEN gh.base_on = "PO" THEN ph.po_number
                            WHEN gh.base_on = "MIR" THEN mh.mir_number
                            ELSE NULL
                          END as doc_number', FALSE);
        $this->db->from('grn_header gh');
        
        // Subquery untuk mendapatkan source_doc_header_id dari grn_details
        $this->db->join('(SELECT grn_header_id, MIN(source_doc_header_id) as source_doc_header_id, MAX(source_doc_type) as source_doc_type 
                         FROM grn_details 
                         GROUP BY grn_header_id) gd', 'gh.id = gd.grn_header_id', 'left');
        
        $this->db->join('po_header ph', "gh.base_on = 'PO' AND gd.source_doc_header_id = ph.id", 'left');
        $this->db->join('mir_header mh', "gh.base_on = 'MIR' AND gd.source_doc_header_id = mh.id", 'left');
        
        $this->db->where('gh.supplier_id', $supplier_id);
        $this->db->where('gh.status', 'Completed');
        $this->db->where("NOT EXISTS (
            SELECT 1 FROM ap_invoice_header ap 
            WHERE JSON_SEARCH(ap.grn_ids, 'one', CAST(gh.id AS CHAR)) IS NOT NULL
            AND ap.status != 'Cancelled'
        )", NULL, FALSE);
        
        $this->db->where("(mh.payment_type_code != 'FOC' OR mh.payment_type_code IS NULL)", NULL, FALSE);

        return $this->db->get()->result();
    }

    public function get_details_from_grn($grn_ids = [])
    {
        $select = "gd.id as grn_detail_id, 
            gd.item_id, 
            gd.item_type, 
            gd.receive_qty as qty, 
            gd.unit_value as unit_price, 
            gd.total_value as total_price,
            CASE 
                WHEN gd.item_type = 'material' THEN m.nama_material
                WHEN gd.item_type = 'machine' THEN mc.nama_mesin
                WHEN gd.item_type = 'inventory' THEN inv.nama_barang
                ELSE 'Unknown Item' 
            END as description";
        // Pass FALSE to avoid Query Builder escaping the CASE expression as an identifier
        $this->db->select($select, FALSE);
        $this->db->from('grn_details gd');
        $this->db->join('materials m', "gd.item_id = m.id AND gd.item_type = 'material'", 'left');
        $this->db->join('machines mc', "gd.item_id = mc.id AND gd.item_type = 'machine'", 'left');
        $this->db->join('inventories inv', "gd.item_id = inv.id AND gd.item_type = 'inventory'", 'left');
        $this->db->where_in('gd.grn_header_id', $grn_ids);
        return $this->db->get()->result();
    }

    public function insert_invoice($post_data)
    {
        $this->db->trans_start();

        $details = $post_data['details'];
        $invoice_type = $post_data['invoice_type'];
        $ppn_rate = (float) ($post_data['ppn_rate'] ?? 0);
        $pph_rate = (float) ($post_data['pph_rate'] ?? 0);
        
        $subtotal = 0;
        $detail_batch = [];

        foreach ($details as $d) {
            $total_price = (float) $d['qty'] * (float) $d['unit_price'];
            $subtotal += $total_price;
            
            $detail_data = [
                'description' => $d['description'],
                'qty' => $d['qty'],
                'unit_price' => $d['unit_price'],
                'total_price' => $total_price
            ];

            if ($invoice_type == 'GRN_BASED') {
                $detail_data['grn_detail_id'] = $d['grn_detail_id'];
                $detail_data['item_id'] = $d['item_id'];
                $detail_data['item_type'] = $d['item_type'];
                // ✨ NEW: Simpan akun yang dipilih user untuk GRN_BASED
                $detail_data['debet_account_id'] = isset($d['debet_account_id']) ? $d['debet_account_id'] : null;
                $detail_data['kredit_account_id'] = isset($d['kredit_account_id']) ? $d['kredit_account_id'] : null;
            } else {
                $detail_data['account_id'] = $d['account_id'];
                // ✨ NEW: Simpan akun yang dipilih user untuk NON_GRN_BASED
                $detail_data['debet_account_id'] = isset($d['debet_account_id']) ? $d['debet_account_id'] : null;
                $detail_data['kredit_account_id'] = isset($d['kredit_account_id']) ? $d['kredit_account_id'] : null;
            }

            $detail_batch[] = $detail_data;
        }

        $ppn_amount = $subtotal * ($ppn_rate / 100);
        $pph_amount = $subtotal * ($pph_rate / 100);
        $total_invoice = ($subtotal + $ppn_amount) - $pph_amount;

        $header_data = [
            'invoice_no' => $post_data['invoice_no'],
            'invoice_date' => $post_data['invoice_date'],
            'due_date' => $post_data['due_date'],
            'supplier_id' => $post_data['supplier_id'],
            'invoice_type' => $invoice_type,
            'grn_ids' => ($invoice_type == 'GRN_BASED') ? json_encode($post_data['grn_ids']) : NULL,
            'subtotal' => $subtotal,
            'ppn_amount' => $ppn_amount,
            'pph_amount' => $pph_amount,
            'total_invoice' => $total_invoice,
            'total_paid' => 0,
            'payment_status' => 'Unpaid',
            'status' => 'Draft',
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('ap_invoice_header', $header_data);
        $header_id = $this->db->insert_id();

        foreach ($detail_batch as &$d) {
            $d['ap_invoice_header_id'] = $header_id;
        }
        $this->db->insert_batch('ap_invoice_details', $detail_batch);

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }

    /**
     * ======================================
     * FUNGSI UNTUK HALAMAN VIEW & AKSI
     * ======================================
     */
    public function get_invoice_header_by_id($id)
    {
        $this->db->select('ap.*, s.nama_supplier, s.jenis as supplier_type,
                          (SELECT GROUP_CONCAT(DISTINCT gh.base_on) 
                           FROM grn_header gh 
                           WHERE JSON_SEARCH(ap.grn_ids, "one", gh.id) IS NOT NULL
                          ) as grn_base_on', FALSE);
        $this->db->from('ap_invoice_header ap');
        $this->db->join('suppliers s', 'ap.supplier_id = s.id');
        $this->db->where('ap.id', $id);
        return $this->db->get()->row();
    }
    
    public function get_invoice_details_by_header_id($header_id)
    {
        $this->db->select('ad.*, coa.account_code, coa.account_name');
        $this->db->from('ap_invoice_details ad');
        $this->db->join('chart_of_accounts coa', 'ad.account_id = coa.id', 'left');
        $this->db->where('ad.ap_invoice_header_id', $header_id);
        return $this->db->get()->result();
    }
    
    public function delete_draft_invoice($id)
    {
        $this->db->where('id', $id);
        $this->db->where('status', 'Draft');
        $this->db->delete('ap_invoice_header');
        return $this->db->affected_rows() > 0;
    }

    /**
     * ======================================
     * FUNGSI POSTING JURNAL OTOMATIS
     * ======================================
     */
    public function post_invoice_journal($header_id, $user_id)
    {
        $this->db->trans_start();
        
        $this->load->model('SettingAkun_model');

        $header = $this->get_invoice_header_by_id($header_id);
        $details = $this->get_invoice_details_by_header_id($header_id);

        if ($header->status != 'Draft') {
            throw new Exception("Hanya status Draft yang bisa di-posting.");
        }
        
        $acc_utang_id = $this->SettingAkun_model->get_account_id('akun_utang_usaha');
        $acc_ppn_id = $this->SettingAkun_model->get_account_id('akun_ppn_masukan');
        $acc_pph_id = $this->SettingAkun_model->get_account_id('akun_utang_pph_23');
        $acc_clearing_id = $this->SettingAkun_model->get_account_id('akun_grn_clearing');

        if(!$acc_utang_id) throw new Exception("Akun 'Utang Usaha' belum di-setting di modul Setting Akun.");
        if($header->ppn_amount > 0 && !$acc_ppn_id) throw new Exception("Akun 'PPN Masukan' belum di-setting.");
        if($header->pph_amount > 0 && !$acc_pph_id) throw new Exception("Akun 'Utang PPh 23' belum di-setting.");
        if($header->invoice_type == 'GRN_BASED' && !$acc_clearing_id) throw new Exception("Akun 'GRN Clearing' belum di-setting.");

        $journal_no = $this->jurnal->get_new_journal_number();
        $journal_details = [];
        $total_debit = 0;
        $total_kredit = 0;

        if ($header->invoice_type == 'GRN_BASED') {
            // ✨ NEW: Gunakan akun yang dipilih user (dari debet_account_id), bukan clearing account
            foreach ($details as $d) {
                // Gunakan akun yang dipilih user, atau fallback ke clearing account
                $debet_acc = $d->debet_account_id ? $d->debet_account_id : $acc_clearing_id;
                $journal_details[] = [
                    'account_id' => $debet_acc,
                    'debit' => $d->total_price,
                    'kredit' => 0,
                    'description' => 'Pencatatan faktur ' . $header->invoice_no . ' - ' . $d->description
                ];
                $total_debit += $d->total_price;
            }
        } else {
            foreach ($details as $d) {
                // ✨ NEW: Gunakan akun yang dipilih user (dari debet_account_id)
                $debet_acc = $d->debet_account_id ? $d->debet_account_id : $d->account_id;
                $journal_details[] = [
                    'account_id' => $debet_acc,
                    'debit' => $d->total_price,
                    'kredit' => 0,
                    'description' => $d->description
                ];
                $total_debit += $d->total_price;
            }
        }

        if ($header->ppn_amount > 0) {
            $journal_details[] = [
                'account_id' => $acc_ppn_id,
                'debit' => $header->ppn_amount,
                'kredit' => 0,
                'description' => 'PPN Masukan ' . $header->invoice_no
            ];
            $total_debit += $header->ppn_amount;
        }
        
        if ($header->pph_amount > 0) {
            $journal_details[] = [
                'account_id' => $acc_pph_id,
                'debit' => 0,
                'kredit' => $header->pph_amount,
                'description' => 'Utang PPh 23 ' . $header->invoice_no
            ];
            $total_kredit += $header->pph_amount;
        }

        $journal_details[] = [
            'account_id' => $acc_utang_id,
            'debit' => 0,
            'kredit' => $header->total_invoice, 
            'description' => 'Utang ke Supplier ' . $header->nama_supplier
        ];
        $total_kredit += $header->total_invoice;
        
        if (round($total_debit, 2) != round($total_kredit, 2)) {
            throw new Exception("Jurnal tidak seimbang. Debit: {$total_debit}, Kredit: {$total_kredit}");
        }

        $journal_header_data = [
            'journal_no' => $journal_no,
            'journal_date' => $header->invoice_date,
            'description' => 'Pencatatan Faktur Supplier ' . $header->invoice_no,
            'source_module' => 'AP_INVOICE',
            'source_doc_id' => $header_id,
            'total_debit' => $total_debit,
            'total_kredit' => $total_kredit,
            'total_debit_local' => $total_debit,
            'total_kredit_local' => $total_kredit,
            'currency' => 'IDR',
            'exchange_rate' => 1,
            'created_by' => $user_id,
            'status' => 'Draft'
        ];

        $journal_id = $this->jurnal->insert_journal($journal_header_data, $journal_details);
        $this->jurnal->post_journal($journal_id, $user_id);
        
        $this->db->where('id', $header_id);
        $this->db->update('ap_invoice_header', [
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
