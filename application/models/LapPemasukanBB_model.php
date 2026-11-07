<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LapPemasukanBB_model extends CI_Model {

    private $table_ledger = 'stock_ledger';
    private $_has_grn_header_id = null; // cache hasil cek kolom

    public function __construct() {
        parent::__construct();
    }

    /**
     * Cek apakah kolom grn_header_id sudah ada di tabel adjustment_header
     * (untuk kompatibilitas sebelum migrasi dijalankan di server)
     */
    private function _has_grn_col() {
        if ($this->_has_grn_header_id === null) {
            $q = $this->db->query("SHOW COLUMNS FROM adjustment_header LIKE 'grn_header_id'");
            $this->_has_grn_header_id = ($q->num_rows() > 0);
        }
        return $this->_has_grn_header_id;
    }

    /**
     * Mengambil data Laporan Pemasukan Bahan Baku (Revisi: JOIN grn_details diperbaiki)
     */
    // Di dalam LapPemasukanBB_model.php

    public function get_report($filters = [], $limit = 25, $offset = 0)
    {
        $start_date = $filters['start_date'] ?? null;
        $end_date   = $filters['end_date'] ?? null;

        $has_grn_col = $this->_has_grn_col();

        if ($has_grn_col) {
            // Pakai grn_header_id: adj hanya muncul di GRN yang benar
            $adj_subquery = "(
                SELECT COALESCE(
                    SUM(CASE WHEN sl_adj.qty_in > 0 THEN sl_adj.qty_in ELSE 0 END)
                    - SUM(CASE WHEN sl_adj.qty_out > 0 THEN sl_adj.qty_out ELSE 0 END)
                , 0)
                FROM stock_ledger sl_adj
                JOIN adjustment_header ah_adj ON sl_adj.source_doc_id = ah_adj.id
                    AND sl_adj.transaction_type = 'adjustment'
                WHERE sl_adj.item_id   = sl.item_id
                  AND sl_adj.item_type = sl.item_type
                  AND ah_adj.grn_header_id = grn.id
                  AND ah_adj.status = 'Confirmed'
                  " . ($start_date ? "AND DATE(sl_adj.transaction_date) >= " . $this->db->escape($start_date) : "") . "
                  " . ($end_date   ? "AND DATE(sl_adj.transaction_date) <= " . $this->db->escape($end_date)   : "") . "
            )";
            $adj_ids_subquery = "(
                SELECT GROUP_CONCAT(DISTINCT ah_adj2.id ORDER BY ah_adj2.id SEPARATOR ',')
                FROM adjustment_header ah_adj2
                JOIN stock_ledger sl_adj2 ON sl_adj2.source_doc_id = ah_adj2.id
                    AND sl_adj2.transaction_type = 'adjustment'
                WHERE sl_adj2.item_id   = sl.item_id
                  AND sl_adj2.item_type = sl.item_type
                  AND ah_adj2.grn_header_id = grn.id
                  AND ah_adj2.status = 'Confirmed'
                  " . ($start_date ? "AND DATE(sl_adj2.transaction_date) >= " . $this->db->escape($start_date) : "") . "
                  " . ($end_date   ? "AND DATE(sl_adj2.transaction_date) <= " . $this->db->escape($end_date)   : "") . "
            )";
        } else {
            // Fallback: kolom belum ada di server, tampilkan 0 (hingga migrasi dijalankan)
            $adj_subquery     = "(0)";
            $adj_ids_subquery = "(NULL)";
        }

        $this->db->select("
            sl.id AS ledger_id,
            sl.item_id,
            sl.item_type,
            sl.transaction_date,
            grn.grn_number,
            grn.grn_date,
            grn.bc_type_code,
            grn.bc_number,
            grn.bc_date,
            COALESCE(s.nama_supplier, s_po.nama_supplier, s_mir.nama_supplier) as nama_supplier,
            {$adj_subquery}  AS adj_qty,
            {$adj_ids_subquery} AS adj_header_ids,
            
            
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
            (sl.qty_in + {$adj_subquery}) AS total_qty,
            COALESCE(po.currency_code, mir.currency_code, 'IDR') AS currency_code,

            CASE
                WHEN po.id IS NOT NULL THEN COALESCE(pd.local_price, 0) * sl.qty_in
                WHEN mir.id IS NOT NULL THEN COALESCE(gd.unit_value, 0) * sl.qty_in
                ELSE COALESCE(gd.unit_value, sl.unit_value, 0) * sl.qty_in
            END AS unit_value_idr,
            COALESCE(pd.base_price, 0) AS unit_value_foreign
        ");
        $this->db->from('stock_ledger sl');
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id AND sl.transaction_type = "in"', 'left');
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');
        $this->db->join('grn_details gd', 'grn.id = gd.grn_header_id AND sl.item_id = gd.item_id AND sl.item_type = gd.item_type', 'left');

        // JOIN ke PO/MIR header dan details (untuk ambil unit_cost dari source)
        $this->db->join('po_header po', 'gd.source_doc_type = "PO" AND gd.source_doc_header_id = po.id', 'left');
        $this->db->join('po_details pd', 'po.id = pd.po_header_id AND gd.item_id = pd.item_id', 'left');
        $this->db->join('mir_header mir', 'gd.source_doc_type = "MIR" AND gd.source_doc_header_id = mir.id', 'left');
        $this->db->join('mir_details md', 'mir.id = md.mir_header_id AND gd.item_id = md.item_id', 'left');
        
        // Add fallback suppliers for when GRN supplier_id is NULL (from PO or MIR)
        $this->db->join('suppliers s_po', 's_po.id = po.supplier_id', 'left');
        $this->db->join('suppliers s_mir', 's_mir.id = mir.supplier_id', 'left');

        // JOIN master item
        $this->db->join('materials m', 'sl.item_id = m.id AND sl.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sl.item_id = sf.id AND sl.item_type = "semifinished"', 'left');
        $this->db->join('machines mc', 'sl.item_id = mc.id AND sl.item_type = "machine"', 'left');       // <-- JOIN HILANG
        $this->db->join('inventories inv', 'sl.item_id = inv.id AND sl.item_type = "inventory"', 'left'); // FIX 2: Perbaiki item_type inventories

        // Filter berdasarkan tanggal dokumen BC (bukan tanggal penerimaan)
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $this->db->where('DATE(grn.bc_date) >=', $filters['start_date']);
            $this->db->where('DATE(grn.bc_date) <=', $filters['end_date']);
        }

        $this->db->where('grn.bc_type_code IS NOT NULL');
        $this->db->where('grn.bc_number IS NOT NULL');
        $this->db->where('grn.bc_type_code !=', '');
        $this->db->where('grn.bc_number !=', '');
        
        // Filter: Hanya tampilkan GRN yang sudah diverifikasi EXIM
        $this->db->where('grn.exim_verified', 1);

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

        $this->db->order_by('grn.bc_date', 'DESC');
        
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

        // Terapkan Filter berdasarkan tanggal dokumen BC (bukan tanggal penerimaan)
        if ($start_date) { $this->db->where('grn.bc_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('grn.bc_date <=', $end_date . ' 23:59:59'); }
        if ($bc_type) { $this->db->where('grn.bc_type_code', $bc_type); }
        
        // Filter: Hanya hitung GRN yang sudah diverifikasi EXIM
        $this->db->where('grn.exim_verified', 1);
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


    // === FUNGSI DETAIL ADJUSTMENT ===
    /**
     * Ambil semua adjustment detail untuk item tertentu dalam periode tertentu
     * Digunakan oleh modal detail adjustment di view laporan
     */
    public function get_adjustment_detail_for_item($item_id, $item_type, $start_date = null, $end_date = null)
    {
        $sql = "
            SELECT
                ah.id           AS header_id,
                ah.doc_no,
                ah.doc_date,
                ah.notes        AS header_notes,
                ad.id           AS detail_id,
                ad.adjustment_type,
                ad.qty,
                ad.notes        AS detail_notes,
                CASE
                    WHEN ad.item_type = 'material'      THEN m.nama_material
                    WHEN ad.item_type = 'semifinished'  THEN sf.nama_barang
                    WHEN ad.item_type = 'finished_goods' THEN fg.nama_barang
                    WHEN ad.item_type = 'inventory'     THEN i.nama_barang
                    ELSE 'Unknown'
                END AS item_name,
                CASE
                    WHEN ad.item_type = 'material'      THEN m.satuan
                    WHEN ad.item_type = 'semifinished'  THEN sf.satuan
                    WHEN ad.item_type = 'inventory'     THEN i.satuan
                    ELSE 'PCS'
                END AS item_unit
            FROM adjustment_details ad
            INNER JOIN adjustment_header ah ON ad.adjustment_header_id = ah.id AND ah.status = 'Confirmed'
            LEFT JOIN materials m          ON ad.item_id = m.id  AND ad.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON ad.item_id = sf.id AND ad.item_type = 'semifinished'
            LEFT JOIN finished_goods fg    ON ad.item_id = fg.id AND ad.item_type = 'finished_goods'
            LEFT JOIN inventories i        ON ad.item_id = i.id  AND ad.item_type = 'inventory'
            WHERE ad.item_id   = ?
              AND ad.item_type = ?
        ";
        $params = [$item_id, $item_type];

        if ($start_date) {
            $sql .= " AND DATE(ah.doc_date) >= ?";
            $params[] = $start_date;
        }
        if ($end_date) {
            $sql .= " AND DATE(ah.doc_date) <= ?";
            $params[] = $end_date;
        }
        $sql .= " ORDER BY ah.doc_date ASC, ah.id ASC";

        return $this->db->query($sql, $params)->result_array();
    }

    // === FUNGSI DETAIL HISTORY ===
    // PERBAIKAN: Tambah fallback supplier dari PO/MIR jika GRN supplier_id NULL
    /**
     * Ambil semua transaksi (history) untuk item yang sama dengan ledger_id tersebut.
     * Opsional: filter berdasarkan periode start/end.
     */
    public function get_trace_detail($ledger_id, $start_date = null, $end_date = null)
    {
        // Ambil item_id, item_type, dan transaction_id (grn_header_id) dari ledger entry yang diklik
        $ref = $this->db->select('item_id, item_type, transaction_id, transaction_type')
                        ->where('id', $ledger_id)
                        ->get('stock_ledger')
                        ->row();

        if (!$ref) return [];

        $item_id      = $ref->item_id;
        $item_type    = $ref->item_type;
        $grn_header_id = $ref->transaction_id; // transaction_id untuk tipe 'in' = grn_header.id

        $this->db->select("
            sl.*,
            grn.grn_number, grn.grn_date, grn.bc_type_code, grn.bc_number, grn.bc_date,
            COALESCE(NULLIF(s.nama_supplier, ''), NULLIF(s_po.nama_supplier, ''), NULLIF(s_mir.nama_supplier, '')) as nama_supplier,
            po.po_number,
            mir.mir_number,
            woh.wo_number,
            wh_src.out_number AS wo_out_number,
            po.currency_code,
            po.exchange_rate,
            COALESCE(gd.unit_value, pod.local_price) AS unit_value_idr,
            pod.base_price AS unit_value_foreign,
            ah.doc_no     AS adjustment_doc,
            ah.notes      AS adjustment_notes,
            ah.doc_date   AS adjustment_date,
            ah.id         AS adjustment_header_id,
            CASE
                WHEN sl.item_type = 'material'    THEN m.nama_material
                WHEN sl.item_type = 'semifinished' THEN sf.nama_barang
                WHEN sl.item_type = 'finished'    THEN fg.nama_barang
                ELSE 'Unknown Item'
            END AS item_name
        ");
        $this->db->from('stock_ledger sl');

        // JOIN GRN Header (untuk baris GRN 'in')
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id AND sl.transaction_type = "in"', 'left');
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');

        // JOIN ke GRN Details
        $this->db->join('grn_details gd', 'grn.id = gd.grn_header_id AND sl.item_id = gd.item_id AND sl.item_type = gd.item_type', 'left');

        // JOIN PO Header dan Detail
        $this->db->join('po_details pod', 'gd.source_doc_type = "PO" AND gd.source_doc_detail_id = pod.id', 'left');
        $this->db->join('po_header po', 'gd.source_doc_type = "PO" AND gd.source_doc_header_id = po.id', 'left');
        $this->db->join('suppliers s_po', 's_po.id = po.supplier_id', 'left');

        // JOIN MIR Header
        $this->db->join('mir_header mir', 'gd.source_doc_type = "MIR" AND gd.source_doc_header_id = mir.id', 'left');
        $this->db->join('suppliers s_mir', 's_mir.id = mir.supplier_id', 'left');

        // JOIN Warehouse Out Header (untuk Goods Return — replacement dari NG QA / Production Return)
        $this->db->join('warehouse_out_header wh_src', 'gd.source_doc_type = "WO" AND gd.source_doc_header_id = wh_src.id', 'left');

        $this->db->join('wo_header woh', '(sl.source_doc_id = woh.id AND sl.transaction_type IN ("production_out", "wip_out"))', 'left');
        $this->db->join('materials m', 'sl.item_id = m.id AND sl.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sl.item_id = sf.id AND sl.item_type = "semifinished"', 'left');
        $this->db->join('finished_goods fg', 'sl.item_id = fg.id AND sl.item_type = "finished"', 'left');
        // JOIN Adjustment Header
        $this->db->join('adjustment_header ah', 'sl.source_doc_id = ah.id AND sl.transaction_type = \'adjustment\'', 'left');

        // Filter: item yang sama
        $this->db->where('sl.item_id',   $item_id);
        $this->db->where('sl.item_type', $item_type);

        // Filter: hanya baris GRN milik grn_header ini, DAN adjustment yang dikaitkan ke GRN ini
        if ($this->_has_grn_col()) {
            // Kolom grn_header_id ada: filter adjustment per GRN
            $this->db->group_start();
                $this->db->group_start();
                    $this->db->where('sl.transaction_type', 'in');
                    $this->db->where('sl.transaction_id', $grn_header_id);
                $this->db->group_end();
                $this->db->or_group_start();
                    $this->db->where('sl.transaction_type', 'adjustment');
                    $this->db->where('ah.grn_header_id', $grn_header_id);
                $this->db->group_end();
            $this->db->group_end();
        } else {
            // Fallback: kolom belum ada, hanya tampilkan baris GRN ini saja (tanpa adjustment)
            $this->db->where('sl.transaction_type', 'in');
            $this->db->where('sl.transaction_id', $grn_header_id);
        }

        // Opsional filter periode
        if ($start_date) { $this->db->where('DATE(sl.transaction_date) >=', $start_date); }
        if ($end_date)   { $this->db->where('DATE(sl.transaction_date) <=', $end_date); }

        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');

        return $this->db->get()->result_array();
    }

} // End Class