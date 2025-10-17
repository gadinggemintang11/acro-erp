<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_ledger_model extends CI_Model {

    private $table = 'stock_ledger';

    // === FUNGSI UTAMA: MENGAMBIL LAPORAN STOK ===
    public function get_stock_summary_report($filters = [], $limit = 20, $offset = 0) {
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;
        $warehouse_type = $filters['warehouse_type'] ?? null;
        $search_term = $filters['search'] ?? null;

        // Subquery 1: Hitung Stok Awal
        $this->db->select("item_id, item_type, MAX(id) as max_id");
        if ($start_date) { $this->db->where('transaction_date <', $start_date . ' 00:00:00'); }
        if ($warehouse_type) { $this->db->where('item_type', $warehouse_type); }
        $this->db->group_by(['item_id', 'item_type']);
        $subquery_max_id = $this->db->get_compiled_select($this->table);

        $this->db->select("sl.item_id, sl.item_type, sl.balance as opening_stock");
        $this->db->from($this->table . ' sl');
        $this->db->join("({$subquery_max_id}) max_ids", 'sl.id = max_ids.max_id', 'inner');
        $subquery_opening = $this->db->get_compiled_select();

        // Subquery 2: Hitung Mutasi
        $this->db->select(" item_id, item_type,
            SUM(CASE WHEN transaction_type = 'in' THEN qty_in ELSE 0 END) as total_receive,
            SUM(CASE WHEN transaction_type = 'return_in' THEN qty_in ELSE 0 END) as total_return,
            SUM(qty_out) as total_out ");
        if ($start_date) { $this->db->where('transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('transaction_date <=', $end_date . ' 23:59:59'); }
        if ($warehouse_type) { $this->db->where('item_type', $warehouse_type); }
        $this->db->group_by(['item_id', 'item_type']);
        $subquery_mutation = $this->db->get_compiled_select($this->table);

        // --- Query Utama ---
        $this->db->select("
            items.*, -- Kolom dari master data (ditentukan di bawah)
            IFNULL(opening.opening_stock, 0) as opening_stock,
            IFNULL(mutation.total_receive, 0) as receive_qty,
            IFNULL(mutation.total_return, 0) as return_qty,
            IFNULL(mutation.total_out, 0) as out_qty,
            (IFNULL(opening.opening_stock, 0) + IFNULL(mutation.total_receive, 0) + IFNULL(mutation.total_return, 0) - IFNULL(mutation.total_out, 0)) as closing_stock
        ", FALSE);

        // Tentukan tabel master data dan literal item_type
        $current_item_type_literal = ''; // Variabel untuk menyimpan literal tipe item
        if ($warehouse_type == 'material') {
             $this->db->from('materials items');
             $this->db->select("items.id as item_id, 'material' as item_type, items.kode_material as item_code, items.nama_material as item_name, items.satuan as item_unit", FALSE);
             $this->db->where('items.status', 'active');
             if ($search_term) { $this->db->group_start()->like('items.kode_material', $search_term)->or_like('items.nama_material', $search_term)->group_end(); }
             $current_item_type_literal = "'material'";
        } elseif ($warehouse_type == 'machine') {
             $this->db->from('machines items');
             $this->db->select("items.id as item_id, 'machine' as item_type, items.kode_mesin as item_code, items.nama_mesin as item_name, 'Unit' as item_unit", FALSE);
             $this->db->where('items.status', 'Active');
             if ($search_term) { $this->db->group_start()->like('items.kode_mesin', $search_term)->or_like('items.nama_mesin', $search_term)->group_end(); }
             $current_item_type_literal = "'machine'";
        } elseif ($warehouse_type == 'inventory') {
             $this->db->from('inventories items');
             $this->db->select("items.id as item_id, 'inventory' as item_type, items.kode_barang as item_code, items.nama_barang as item_name, 'Unit' as item_unit", FALSE);
             $this->db->where('items.status', 'Active');
              if ($search_term) { $this->db->group_start()->like('items.kode_barang', $search_term)->or_like('items.nama_barang', $search_term)->group_end(); }
              $current_item_type_literal = "'inventory'";
        } elseif ($warehouse_type == 'semifinished') {
             $this->db->from('semifinished_goods items');
             $this->db->select("items.id as item_id, 'semifinished' as item_type, items.kode_barang as item_code, items.nama_barang as item_name, items.satuan as item_unit", FALSE);
              $this->db->where('items.status', 'active');
              if ($search_term) { $this->db->group_start()->like('items.kode_barang', $search_term)->or_like('items.nama_barang', $search_term)->group_end(); }
              $current_item_type_literal = "'semifinished'";
        } else {
             // Default jika tidak ada filter warehouse (contoh: material)
             $this->db->from('materials items');
             $this->db->select("items.id as item_id, 'material' as item_type, items.kode_material as item_code, items.nama_material as item_name, items.satuan as item_unit", FALSE);
             $this->db->where('items.status', 'active');
             if ($search_term) { $this->db->group_start()->like('items.kode_material', $search_term)->or_like('items.nama_material', $search_term)->group_end(); }
             $current_item_type_literal = "'material'";
        }

        // ✅ FIX: JOIN dengan kondisi item_type yang eksplisit
        if ($current_item_type_literal) { // Pastikan literal tidak kosong
            $this->db->join("({$subquery_opening}) opening", "items.id = opening.item_id AND opening.item_type = {$current_item_type_literal}", 'left');
            $this->db->join("({$subquery_mutation}) mutation", "items.id = mutation.item_id AND mutation.item_type = {$current_item_type_literal}", 'left');
        } else {
             // Fallback jika $warehouse_type tidak valid (seharusnya tidak terjadi)
             $this->db->join("({$subquery_opening}) opening", 'items.id = opening.item_id', 'left');
             $this->db->join("({$subquery_mutation}) mutation", 'items.id = mutation.item_id', 'left');
        }


        // Filter hanya item yang relevan
        $this->db->where('(opening.opening_stock IS NOT NULL OR mutation.total_receive IS NOT NULL OR mutation.total_return IS NOT NULL OR mutation.total_out IS NOT NULL)');
        $this->db->order_by('item_name', 'ASC');
        if ($limit > 0) { $this->db->limit($limit, $offset); }

        return $this->db->get()->result();
    }

    // === FUNGSI COUNT (TIDAK BERUBAH) ===
    public function count_stock_summary_report($filters = []) {
        // ... (Kode count_stock_summary_report tetap sama) ...
        $warehouse_type = $filters['warehouse_type'] ?? null;
        $search_term = $filters['search'] ?? null;

        if ($warehouse_type == 'material') {
             $this->db->from('materials items');
             if ($search_term) { $this->db->group_start()->like('items.kode_material', $search_term)->or_like('items.nama_material', $search_term)->group_end(); }
             $this->db->where('items.status', 'active');
             $item_type_literal = "'material'"; // Definisikan literal
        } elseif ($warehouse_type == 'machine') {
             $this->db->from('machines items');
             if ($search_term) { $this->db->group_start()->like('items.kode_mesin', $search_term)->or_like('items.nama_mesin', $search_term)->group_end(); }
             $this->db->where('items.status', 'Active');
              $item_type_literal = "'machine'";
        } elseif ($warehouse_type == 'inventory') {
             $this->db->from('inventories items');
             if ($search_term) { $this->db->group_start()->like('items.kode_barang', $search_term)->or_like('items.nama_barang', $search_term)->group_end(); }
             $this->db->where('items.status', 'Active');
              $item_type_literal = "'inventory'";
        } elseif ($warehouse_type == 'semifinished') {
             $this->db->from('semifinished_goods items');
             if ($search_term) { $this->db->group_start()->like('items.kode_barang', $search_term)->or_like('items.nama_barang', $search_term)->group_end(); }
             $this->db->where('items.status', 'active');
              $item_type_literal = "'semifinished'";
        } else {
             $this->db->from('materials items'); // Default
             if ($search_term) { $this->db->group_start()->like('items.kode_material', $search_term)->or_like('items.nama_material', $search_term)->group_end(); }
             $this->db->where('items.status', 'active');
              $item_type_literal = "'material'"; // Default literal
        }

        // Join minimal ke ledger untuk filter item yang relevan (pernah transaksi)
        // ✅ FIX: Kondisi join diperjelas
        if ($item_type_literal) {
            $this->db->join($this->table.' sl', "items.id = sl.item_id AND sl.item_type = {$item_type_literal}", 'inner'); // Inner join lebih efisien di sini
        } else {
             $this->db->join($this->table.' sl', "items.id = sl.item_id", 'inner'); // Fallback (kurang ideal)
        }

        // Group by item unik sebelum menghitung
        $this->db->group_by('items.id');

         $query = $this->db->get();
         return $query->num_rows(); // Jumlah item unik yang cocok
    }

    // === FUNGSI DETAIL HISTORY (TIDAK BERUBAH) ===
    public function get_item_transaction_history($item_id, $item_type, $start_date = null, $end_date = null) {
        // ... (Kode get_item_transaction_history tetap sama) ...
        $select_clause = "
            sl.transaction_date, sl.transaction_type, sl.transaction_id,
            sl.qty_in, sl.qty_out, sl.balance,
            CASE WHEN sl.transaction_type = 'in' THEN grn.grn_number ELSE CAST(sl.transaction_id AS CHAR) END as reference_doc,
            CASE WHEN sl.transaction_type = 'in' THEN s.nama_supplier ELSE NULL END as partner_name,
            grn.bc_type_code, grn.bc_number
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table . ' sl');
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id AND sl.transaction_type = "in"', 'left');
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');
        $this->db->where('sl.item_id', $item_id); $this->db->where('sl.item_type', $item_type);
        if ($start_date) { $this->db->where('sl.transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('sl.transaction_date <=', $end_date . ' 23:59:59'); }
        $this->db->order_by('sl.id', 'ASC');
        return $this->db->get()->result();
    }

    // --- Fungsi insert, get_last_balance, delete_by_transaction tetap sama ---
    public function insert($data) {
        if (!isset($data['transaction_date'])) {
            $data['transaction_date'] = date('Y-m-d H:i:s');
        }
        if (!$this->db->table_exists($this->table)) {
             log_message('debug', 'Stock Ledger Insert (Skipped): ' . json_encode($data));
             return true; 
        }
        return $this->db->insert($this->table, $data);
    }
    public function get_last_balance($item_id, $item_type) {
         if (!$this->db->table_exists($this->table)) {
             return 0;
        }
        $this->db->select('balance');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $result = $this->db->get($this->table)->row();
        return $result ? (float)$result->balance : 0;
    }
    public function delete_by_transaction($transaction_type, $transaction_id) {
         if (!$this->db->table_exists($this->table)) {
             return true;
        }
        $this->db->where('transaction_type', $transaction_type);
        $this->db->where('transaction_id', $transaction_id);
        return $this->db->delete($this->table);
    }

}