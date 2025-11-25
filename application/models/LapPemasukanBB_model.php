<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LapPemasukanBB_model extends CI_Model {

    private $table_ledger = 'stock_ledger'; // Nama tabel stock ledger

    public function __construct() {
        parent::__construct();
        // $this->load->database(); // Load jika belum
    }

    /**
     * Mengambil data Laporan Pemasukan Bahan Baku (Revisi: JOIN grn_details diperbaiki)
     */
    // Di dalam LapPemasukanBB_model.php

    public function get_report($filters = [], $limit = 25, $offset = 0)
    {
        $this->db->select("
            sl.id AS ledger_id,
            sl.transaction_date,
            grn.grn_number,
            grn.grn_date,
            grn.bc_type_code,
            grn.bc_number,
            grn.bc_date,
            s.nama_supplier,
            
            
            CASE 
                WHEN sl.item_type = 'material' THEN m.kode_material
                WHEN sl.item_type = 'semifinished' THEN sf.kode_barang
                WHEN sl.item_type = 'machine' THEN mc.kode_mesin   -- <-- TAMBAHAN
                WHEN sl.item_type = 'inventory' THEN inv.kode_barang
                ELSE NULL
            END AS item_code,

            CASE 
                WHEN sl.item_type = 'material' THEN m.nama_material
                WHEN sl.item_type = 'semifinished' THEN sf.nama_barang
                WHEN sl.item_type = 'machine' THEN mc.nama_mesin   -- <-- TAMBAHAN
                WHEN sl.item_type = 'inventory' THEN inv.nama_barang
                ELSE NULL
            END AS item_name,

            CASE 
                WHEN sl.item_type = 'material' THEN m.satuan
                WHEN sl.item_type = 'semifinished' THEN sf.satuan
                WHEN sl.item_type = 'machine' THEN mc.satuan      -- <-- TAMBAHAN (dari master machine)
                WHEN sl.item_type = 'inventory' THEN inv.satuan   -- <-- TAMBAHAN (dari master inventory)
                ELSE NULL
            END AS item_unit,

            sl.qty_in,
            COALESCE(po.currency_code, mir.currency_code, 'IDR') AS currency_code,

            CASE
                WHEN gd.source_doc_type = 'PO' THEN gd.unit_value * sl.qty_in
                WHEN gd.source_doc_type = 'MIR' THEN gd.unit_value * sl.qty_in
                ELSE 0
            END AS unit_value_idr
        ");
        $this->db->from('stock_ledger sl');
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id AND sl.transaction_type = "in"', 'left');
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');
        $this->db->join('grn_details gd', 'grn.id = gd.grn_header_id AND sl.item_id = gd.item_id AND sl.item_type = gd.item_type', 'left'); // FIX KRITIS: Tambahkan item_type ke kondisi join

        // JOIN ke PO/MIR header
        $this->db->join('po_header po', 'gd.source_doc_type = "PO" AND gd.source_doc_header_id = po.id', 'left');
        $this->db->join('mir_header mir', 'gd.source_doc_type = "MIR" AND gd.source_doc_header_id = mir.id', 'left');

        // JOIN master item
        $this->db->join('materials m', 'sl.item_id = m.id AND sl.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sl.item_id = sf.id AND sl.item_type = "semifinished"', 'left');
        $this->db->join('machines mc', 'sl.item_id = mc.id AND sl.item_type = "machine"', 'left');       // <-- JOIN HILANG
        $this->db->join('inventories inv', 'sl.item_id = inv.id AND sl.item_type = "inventory"', 'left'); // FIX 2: Perbaiki item_type inventories

        // ... (Filter tanggal, BC, dan Search sama) ...
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $this->db->where('DATE(sl.transaction_date) >=', $filters['start_date']);
            $this->db->where('DATE(sl.transaction_date) <=', $filters['end_date']);
        }

        $this->db->where('grn.bc_type_code IS NOT NULL');
        $this->db->where('grn.bc_number IS NOT NULL');
        $this->db->where('grn.bc_type_code !=', '');
        $this->db->where('grn.bc_number !=', '');

        if (!empty($filters['bc_type'])) {
            $this->db->where('grn.bc_type_code', $filters['bc_type']);
        }

        if (!empty($filters['search'])) {
            $this->db->group_start()
                    ->like('m.kode_material', $filters['search'])
                    ->or_like('m.nama_material', $filters['search'])
                    ->or_like('sf.nama_barang', $filters['search'])
                    ->or_like('mc.nama_mesin', $filters['search']) // Tambahkan Mesin
                    ->or_like('inv.nama_barang', $filters['search'])
                    ->or_like('grn.grn_number', $filters['search'])
                    ->group_end();
        }

        $this->db->order_by('sl.transaction_date', 'DESC');
        
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get()->result_array();
    }


    /**
     * Menghitung total baris Laporan Pemasukan Bahan Baku (Revisi JOIN)
     */
    public function count_report($filters = []) {
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;
        $bc_type = $filters['bc_type'] ?? null;
        $search = $filters['search'] ?? null;

        $this->db->reset_query();
        $this->db->from($this->table_ledger . ' sl');
        $this->db->where('sl.item_type', 'material');
        $this->db->where('sl.transaction_type', 'in');

        // Join minimal untuk filter
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id', 'inner');
        $this->db->join('materials m', 'sl.item_id = m.id', 'inner');
        // Join tambahan HANYA JIKA diperlukan oleh filter $search
        if ($search) {
             $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');
             // --- PERBAIKAN JOIN GRN DETAILS ---
             $this->db->join('grn_details gd', 'grn.id = gd.grn_header_id AND sl.item_id = gd.item_id', 'left');
             // --- AKHIR PERBAIKAN ---
             $this->db->join('po_header po', 'gd.source_doc_type = "PO" AND gd.source_doc_header_id = po.id', 'left');
        }

        // Terapkan Filter (SAMA seperti get_report)
        if ($start_date) { $this->db->where('sl.transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('sl.transaction_date <=', $end_date . ' 23:59:59'); }
        if ($bc_type) { $this->db->where('grn.bc_type_code', $bc_type); }
        if ($search) {
            $this->db->group_start();
            $this->db->like('m.kode_material', $search);
            $this->db->or_like('m.nama_material', $search);
            $this->db->or_like('grn.grn_number', $search);
            // Cek join sebelum like
            if (strpos($this->db->get_compiled_select(), 'suppliers s') !== false) { $this->db->or_like('s.nama_supplier', $search); } // Perlu cek join sebelum like
             if (strpos($this->db->get_compiled_select(), 'po_header po') !== false) { $this->db->or_like('po.po_number', $search); } // Perlu cek join sebelum like
            $this->db->or_like('grn.bc_number', $search);
            $this->db->group_end();
            $this->db->reset_query(); // Reset setelah get_compiled_select
        }

        // Hitung total baris
        $count = $this->db->count_all_results(); // count_all_results sudah otomatis reset query
        log_message('debug', 'Count LapPemasukanBB SQL (Success): ' . $this->db->last_query());
        return $count;
    }


    // === FUNGSI DETAIL HISTORY ===
    // (Fungsi ini tidak berubah, join ke grn_details tidak pakai stock_ledger_id)
    public function get_trace_detail($ledger_id)
    {
        $this->db->select("
            sl.*, 
            grn.grn_number, grn.grn_date, grn.bc_type_code, grn.bc_number, grn.bc_date,
            s.nama_supplier, 
            prh.prod_return_no, prh.return_date, 
            mr_h.mr_number, mr_h.request_date, 
            
           
            po.po_number, 
            
            woh.wo_number, 
            po.currency_code, po.exchange_rate, 
            
            
            COALESCE(gd.unit_value, pod.local_price) AS unit_value_idr,
            pod.base_price AS unit_value_foreign,

            CASE 
                WHEN sl.item_type = 'material' THEN m.nama_material
                WHEN sl.item_type = 'semifinished' THEN sf.nama_barang
                WHEN sl.item_type = 'finished' THEN fg.nama_barang
                ELSE 'Unknown Item'
            END AS item_name
        ");
        $this->db->from('stock_ledger sl');
        
        // JOIN GRN Header
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id AND sl.transaction_type = "in"', 'left');
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');
        
        // FIX KRITIS 2: JOIN ke GRN Details (menggunakan item_id untuk akurasi)
        $this->db->join('grn_details gd', 'grn.id = gd.grn_header_id AND sl.item_id = gd.item_id AND sl.item_type = gd.item_type', 'left');
        
        // JOIN PO Header dan Detail
        $this->db->join('po_details pod', 'gd.source_doc_type = "PO" AND gd.source_doc_detail_id = pod.id', 'left');
        // sl.source_doc_id seharusnya berisi ID PO
        $this->db->join('po_header po', 'gd.source_doc_type = "PO" AND gd.source_doc_header_id = po.id', 'left'); 
        
        $this->db->join('prod_return_header prh', 'sl.transaction_id = prh.id AND sl.transaction_type = "IN_RETURN_WIP"', 'left');
        $this->db->join('mr_header mr_h', 'sl.transaction_id = mr_h.id AND sl.transaction_type = "production_out"', 'left');
        $this->db->join('wo_header woh', '(sl.source_doc_id = woh.id AND sl.transaction_type IN ("production_out", "wip_out"))', 'left');
        $this->db->join('materials m', 'sl.item_id = m.id AND sl.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sl.item_id = sf.id AND sl.item_type = "semifinished"', 'left');
        $this->db->join('finished_goods fg', 'sl.item_id = fg.id AND sl.item_type = "finished"', 'left');
        $this->db->where('sl.id', $ledger_id);

        return $this->db->get()->result_array();
    }

} // End Class