<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stock_ledger_model extends CI_Model {

    private $table = 'stock_ledger';

    public function __construct() {
        parent::__construct();
        // Load database jika belum di autoload
        // $this->load->database();
    }
    
    
    // --- Tipe Transaksi Gudang Utama ---
    // (Helper array agar insert dan get_history konsisten)
    private function get_warehouse_tx_types() {
        // Tipe yang MENAMBAH saldo Gudang Utama
        $types_in = [
            'in',               // GRN (Barang baru)
            'IN_RETURN_WIP',    // Retur dari Produksi
            'ADJ_IN'            // Adjustment Masuk
        ];
        
        // Tipe yang MENGURANGI saldo Gudang Utama
        $types_out = [
            'OUT_ISSUE',        // Issue Material ke Produksi
            'production_out',   // Issue Material/Semif ke Produksi
            'OUT_SHIPMENT',     // Kirim Barang Jadi
            'OUT_SCRAP_BC',     // Scrap Keluar Gudang
            'ADJ_OUT'           // Adjustment Keluar
        ];

        return array_merge($types_in, $types_out);
    }
    
    /**
     * === FUNGSI LAPORAN STOK (FINAL v6) ===
     * PERBAIKAN:
     * - Subquery Stok Awal (Subquery 1) sekarang juga difilter
     * agar HANYA mencari transaksi Gudang Utama.
     */
    // Di dalam Stock_ledger_model.php
// Di dalam Stock_ledger_model.php

    // Di dalam Stock_ledger_model.php

    public function get_item_master_detail($item_id_type_string) {
        
        // 1. Parse string input (misal: "10|material")
        if (strpos($item_id_type_string, '|') === false) {
            return null; // Format tidak valid
        }
        
        list($item_id, $item_type) = explode('|', $item_id_type_string);
        
        if (empty($item_id) || empty($item_type)) {
            return null;
        }

        // 2. Query ke tabel yang benar berdasarkan item_type
        switch ($item_type) {
            case 'material':
                $this->db->select("satuan as item_unit, 'material' as item_type");
                $this->db->where('id', $item_id);
                $query = $this->db->get('materials');
                break;
            case 'semifinished':
                $this->db->select("satuan as item_unit, 'semifinished' as item_type");
                $this->db->where('id', $item_id);
                $query = $this->db->get('semifinished_goods');
                break;
            case 'finished_goods':
                $this->db->select("satuan as item_unit, 'finished_goods' as item_type");
                $this->db->where('id', $item_id);
                $query = $this->db->get('finished_goods');
                break;
            case 'machine':
                $this->db->select("satuan as item_unit, 'machine' as item_type");
                $this->db->where('id', $item_id);
                $query = $this->db->get('machines');
                break;
            case 'inventory':
                $this->db->select("satuan as item_unit, 'inventory' as item_type");
                $this->db->where('id', $item_id);
                $query = $this->db->get('inventories');
                break;
            default:
                return null; // Tipe item tidak dikenal
        }

        // 3. Kembalikan hasilnya
        return $query->row();
    }
    public function get_stock_summary_report($filters = [], $limit = 20, $offset = 0) {
        // 1. INIASILISASI VARIABEL FILTER
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;
        $warehouse_type = $filters['warehouse_type'] ?? null;
        $search_term = $filters['search'] ?? null;
        $allowed_types = ['material', 'semifinished', 'machine', 'inventory'];

        // --- Subquery 1: Hitung Stok Awal (Saldo terakhir SEBELUM tgl mulai) ---
        $this->db->reset_query(); 
        $subquery_max_id_sql = $this->db->select("item_id, item_type, MAX(id) as max_id", FALSE)
                                        ->from($this->table)
                                        ->where_in('transaction_type', $this->get_warehouse_tx_types())
                                        ->group_by(['item_id', 'item_type'])
                                        ->get_compiled_select();
        $this->db->reset_query();
        
        $this->db->select("t1.item_id, t1.item_type, t1.balance as opening_stock", FALSE);
        $this->db->from($this->table . ' t1');
        $this->db->join("({$subquery_max_id_sql}) max_ids", 't1.id = max_ids.max_id', 'inner');
        if ($start_date) { 
            $this->db->where('t1.transaction_date <', $start_date . ' 00:00:00'); 
        }
        $subquery_opening_sql = $this->db->get_compiled_select();
        $this->db->reset_query(); 

        // --- Subquery 2: Mutasi di Periode yang Diminta ---
        $this->db->select(" item_id, item_type,
            SUM(CASE WHEN transaction_type = 'in' THEN qty_in ELSE 0 END) as total_receive,
            SUM(CASE WHEN transaction_type = 'IN_RETURN_WIP' THEN qty_in ELSE 0 END) as total_return,
            SUM(CASE WHEN transaction_type IN ('OUT_ISSUE', 'OUT_SHIPMENT', 'ADJ_OUT', 'production_out') THEN qty_out ELSE 0 END) as total_out
        ", FALSE);
        $this->db->from($this->table); 
        if ($start_date) { $this->db->where('transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('transaction_date <=', $end_date . ' 23:59:59'); }
        $this->db->where_in('transaction_type', $this->get_warehouse_tx_types()); 
        $this->db->group_by(['item_id', 'item_type']);
        $subquery_mutation_sql = $this->db->get_compiled_select();
        $this->db->reset_query(); 


        // --- Query Utama (FINAL) ---
        $all_items_sql = $this->_get_master_item_query_string();
        $this->db->select("
            items.item_id, items.item_type, items.item_code, items.item_name, items.item_unit,
            IFNULL(opening.opening_stock, 0) as opening_stock,
            IFNULL(mutation.total_receive, 0) as receive_qty,
            IFNULL(mutation.total_return, 0) as return_qty,
            IFNULL(mutation.total_out, 0) as out_qty
        ");
        
        $this->db->select("(IFNULL(opening.opening_stock, 0) + IFNULL(mutation.total_receive, 0) + IFNULL(mutation.total_return, 0) - IFNULL(mutation.total_out, 0)) as closing_stock", FALSE);

        $this->db->from("({$all_items_sql}) items");
        
        $this->db->join("({$subquery_opening_sql}) opening", "items.item_id = opening.item_id AND items.item_type = opening.item_type", 'left');
        $this->db->join("({$subquery_mutation_sql}) mutation", "items.item_id = mutation.item_id AND items.item_type = mutation.item_type", 'left');

        $this->db->where('items.status', 'Active'); 
        
        if ($warehouse_type) { 
            $this->db->where('items.item_type', $warehouse_type); 
        } else {
            $this->db->where_in('items.item_type', $allowed_types);
        }
        
        if ($search_term) { $this->db->group_start()->like('items.item_code', $search_term)->or_like('items.item_name', $search_term)->group_end(); }
        
        // MENGAKTIFKAN KEMBALI FILTER KETAT (HAVING logic)
        $this->db->where('(
            opening.opening_stock IS NOT NULL OR 
            mutation.total_receive IS NOT NULL OR 
            mutation.total_return IS NOT NULL OR 
            mutation.total_out IS NOT NULL 
        )');
        
        $this->db->order_by('items.item_name', 'ASC');
        if ($limit > 0) { $this->db->limit($limit, $offset); }

        $query = $this->db->get();
        
        if (!$query) { 
            log_message('error', 'SQL Utama GAGAL: ' . $this->db->last_query());
            return []; 
        }
        return $query->result();
    }

    // ========================================================
    // FUNGSI COUNT (FINAL - Mesin Wajib Muncul)
    // ========================================================
    public function count_stock_summary_report($filters = []) {
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;
        $warehouse_type = $filters['warehouse_type'] ?? null;
        $search_term = $filters['search'] ?? null;
        $allowed_types = ['material', 'semifinished', 'machine', 'inventory'];

        // --- Subquery 1: Stok Awal ---
        $this->db->reset_query();
        $this->db->select("item_id, item_type, MAX(id) as max_id")->from($this->table);
        if ($start_date) { $this->db->where('transaction_date <', $start_date . ' 00:00:00'); }
        $this->db->where_in('transaction_type', $this->get_warehouse_tx_types()); 
        $this->db->group_by(['item_id', 'item_type']);
        $subquery_max_id_sql = $this->db->get_compiled_select();
        $this->db->reset_query();
        $this->db->select("t1.item_id, t1.item_type, t1.balance as opening_stock");
        $this->db->from($this->table . ' t1')->join("({$subquery_max_id_sql}) max_ids", 't1.id = max_ids.max_id', 'inner');
        $subquery_opening_sql = $this->db->get_compiled_select();
        $this->db->reset_query();

        // --- Subquery 2: Mutasi ---
        $this->db->select("item_id, item_type,
            SUM(CASE WHEN transaction_type = 'in' THEN 1 ELSE 0 END) as total_receive,
            SUM(CASE WHEN transaction_type = 'IN_RETURN_WIP' THEN 1 ELSE 0 END) as total_return,
            SUM(CASE WHEN transaction_type IN ('OUT_ISSUE', 'OUT_SHIPMENT', 'ADJ_OUT', 'production_out') THEN 1 ELSE 0 END) as total_out
        ");
        $this->db->from($this->table);
        if ($start_date) { $this->db->where('transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('transaction_date <=', $end_date . ' 23:59:59'); }
        $this->db->where_in('transaction_type', $this->get_warehouse_tx_types());
        $this->db->group_by(['item_id', 'item_type']);
        $subquery_mutation_sql = $this->db->get_compiled_select();
        $this->db->reset_query();

        $all_items_sql = $this->_get_master_item_query_string();

        // Query Utama (Count)
        $this->db->reset_query();
        $this->db->select('COUNT(*) as total_rows');
        $this->db->from("({$all_items_sql}) items");
        $this->db->join("({$subquery_opening_sql}) opening", "items.item_id = opening.item_id AND items.item_type = opening.item_type", 'left');
        $this->db->join("({$subquery_mutation_sql}) mutation", "items.item_id = mutation.item_id AND items.item_type = mutation.item_type", 'left');

        $this->db->where('items.status', 'Active');
        
        if ($warehouse_type) { 
            $this->db->where('items.item_type', $warehouse_type); 
        } else {
            $this->db->where_in('items.item_type', $allowed_types);
        }

        if ($search_term) { $this->db->group_start()->like('items.item_code', $search_term)->or_like('items.item_name', $search_term)->group_end(); }
        
        // MENGAKTIFKAN KEMBALI FILTER KETAT (HAVING logic)
        $this->db->where('(
            opening.opening_stock IS NOT NULL OR 
            mutation.total_receive > 0 OR 
            mutation.total_return > 0 OR 
            mutation.total_out > 0 
        )');
        
        $query = $this->db->get();
        if (!$query) { return 0; }
        return (int)$query->row()->total_rows;
    }

    /**
     * === FUNGSI INSERT (FINAL v4 - Logika Saldo Sesuai Gudang Utama) ===
     */
    public function insert($data) {
        if (!isset($data['transaction_date'])) { $data['transaction_date'] = date('Y-m-d H:i:s'); }
        if (empty($data['item_id']) || empty($data['item_type']) || empty($data['transaction_type'])) {
            log_message('error', 'StockLedger->insert GAGAL: Data dasar (item_id, item_type, transaction_type) tidak lengkap.');
            return false;
        }

        $this->db->trans_start(); 

        // Hitung Saldo BARU
        // Transaksi GRN (tipe 'in') dan adjustment sudah mengirim 'balance'
        // Transaksi Produksi (wip_in/wip_out) perlu dihitung di sini
        if (!isset($data['balance'])) {
            
            // Dapatkan saldo terakhir (bisa dari Gudang atau WIP, tergantung item)
            $last_balance = $this->get_last_balance_for_tx($data['item_id'], $data['item_type'], $data['transaction_type']);
            
            $qty_in = (float)($data['qty_in'] ?? 0);
            $qty_out = (float)($data['qty_out'] ?? 0);
            $current_balance = $last_balance; // Default: saldo tidak berubah

            // Tentukan apakah transaksi ini mempengaruhi saldo GUDANG atau saldo WIP
            if (in_array($data['transaction_type'], $this->get_warehouse_tx_types())) {
                // Ini transaksi GUDANG
                $current_balance = $last_balance + $qty_in - $qty_out;
            } else if (in_array($data['transaction_type'], ['wip_in', 'wip_out'])) {
                // Ini transaksi WIP
                $current_balance = $last_balance + $qty_in - $qty_out;
            }
            // Jika tipe lain, saldo tidak berubah (current_balance = last_balance)

            $data['balance'] = round($current_balance, 5);
        }

        // Coba insert
        $insert_result = $this->db->insert($this->table, $data);
        
        // =========================================================
        // PERBAIKAN: Menambahkan Logging Error Detail
        // =========================================================
        if(!$insert_result){
            $db_error = $this->db->error(); // Ambil error database
            // INI BARIS 228 (YANG DIPERBAIKI)
            log_message('error', 'StockLedger->insert GAGAL: DB Error: ' . $db_error['message'] . ' | Kode: ' . $db_error['code'] . ' | Data: ' . json_encode($data));
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() && $insert_result);
    }


// Di dalam Stock_ledger_model.php

    /**
     * Mengambil satu baris detail transaksi lengkap (Traceability)
     */
    // Di dalam Stock_ledger_model.php

    /**
     * Mengambil satu baris detail transaksi lengkap (Traceability)
     */
    public function get_transaction_detail_by_id($ledger_id) {
        // PERHATIAN: Hapus fungsi lama, ganti dengan kueri Raw SQL ini
        $sql = "
            SELECT
                sl.*, sl.id AS ledger_id,
                
                
                COALESCE(m.kode_material, sf.kode_barang, fg.kode_barang, scr.scrap_code) AS item_code,
                COALESCE(m.nama_material, sf.nama_barang, fg.nama_barang, scr.scrap_name) AS item_name,
                COALESCE(m.satuan, sf.satuan, fg.satuan, scr.unit) AS item_unit, 
                
                -- FIX: Tambahkan kolom 'jumlah' dan 'nilai_barang_idr'
                sl.qty_out AS jumlah,
                CASE sl.transaction_type
                    WHEN 'fg_out' THEN (fdd.qty_shipped * sc.unit_price * 1) -- Asumsi kurs 1 jika tidak ada
                    WHEN 'OUT_SCRAP_BC' THEN (sl.qty_out * sod_scrap.sales_value_per_unit)
                    ELSE (sl.qty_out * sl.unit_value)                      
                END AS nilai_barang_idr,
                
                -- FIX: Tambahkan mata uang untuk referensi
                COALESCE(sc.currency_code, 'IDR') AS mata_uang,
                CASE sl.transaction_type
                    WHEN 'fg_out' THEN (fdd.qty_shipped * sc.unit_price)
                    ELSE NULL 
                END AS nilai_barang_fc,
               
                COALESCE(fdh.delivery_number, sout.subkon_out_no, soh.doc_no, adjh.doc_no, gh.grn_number) AS source_doc_no,
                COALESCE(fdh.delivery_date, sout.shipping_date, soh.doc_date, adjh.doc_date, gh.grn_date) AS source_doc_date,
                COALESCE(c_fg.nama_customer, s_sub.nama_supplier, c_scrap.nama_customer, s_grn.nama_supplier, 'N/A') AS partner_name,
                
                
                cc.description AS jenis_bc_desc,
                COALESCE(fdh.bc_number, soh.bc_number, sout.bc_number) AS nomor_bc_ref,
                COALESCE(fdh.bc_date, soh.bc_date, sout.bc_date) AS tanggal_bc_ref,
               
                COALESCE(sc.sc_number, sc_h.sc_number) AS sales_contract_ref,
                COALESCE(fdd.sales_contract_id, fdh.sales_contract_id) AS sales_contract_id
                
            FROM stock_ledger sl

         
            LEFT JOIN fg_delivery_details fdd ON sl.transaction_id = fdd.id AND sl.transaction_type = 'fg_out'
            LEFT JOIN fg_delivery_header fdh ON fdd.fg_delivery_header_id = fdh.id
            LEFT JOIN customers c_fg ON fdh.customer_id = c_fg.id
            LEFT JOIN sales_contracts sc ON fdd.sales_contract_id = sc.id 
            LEFT JOIN sales_contracts sc_h ON fdh.sales_contract_id = sc_h.id

            
            LEFT JOIN subkon_out_details sod ON sl.transaction_id = sod.id AND sl.source_doc_type = 'SUBKON_OUT'
            LEFT JOIN subkon_out_header sout ON sod.subkon_out_id = sout.id
            LEFT JOIN suppliers s_sub ON sout.supplier_id = s_sub.id

           
            LEFT JOIN scrap_out_detail sod_scrap ON sl.transaction_id = sod_scrap.id AND sl.transaction_type = 'OUT_SCRAP_BC'
            LEFT JOIN scrap_out_header soh ON sod_scrap.header_id = soh.id
            LEFT JOIN customers c_scrap ON soh.destination = c_scrap.id
            LEFT JOIN master_scrap scr ON sl.item_id = scr.id AND sl.item_type = 'scrap'

            
            LEFT JOIN adjustment_header adjh ON sl.transaction_id = adjh.id AND sl.transaction_type = 'ADJ_OUT'

           
            LEFT JOIN grn_header gh ON sl.transaction_id = gh.id AND sl.transaction_type = 'in'
            LEFT JOIN suppliers s_grn ON gh.supplier_id = s_grn.id

            
            LEFT JOIN materials m ON sl.item_id = m.id AND sl.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON sl.item_id = sf.id AND sl.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON sl.item_id = fg.id AND sl.item_type = 'finished_goods'

            
            LEFT JOIN common_code cc ON cc.group_code = 'BC_TYPE'
                AND (
                    (sl.transaction_type = 'fg_out' AND cc.id = fdh.bc_type_id) OR
                    (sl.transaction_type IN ('OUT_SCRAP_BC', 'production_out') AND cc.code = COALESCE(soh.bc_type_code, sout.bc_type_code))
                )

            WHERE sl.id = ?
            LIMIT 1;
        ";
        
        return $this->db->query($sql, [$ledger_id])->row();
    }
    private function get_last_balance_for_tx($item_id, $item_type, $transaction_type) {
        $this->db->reset_query();
        $this->db->select('balance');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        
        $wip_types = ['wip_in', 'wip_out', 'IN_RETURN_WIP', 'production_out'];

        if (in_array($transaction_type, $this->get_warehouse_tx_types())) {
            // Jika ini transaksi Gudang (GRN/ADJ), ambil saldo terakhir Gudang
            $this->db->where_in('transaction_type', $this->get_warehouse_tx_types());
        } else if (in_array($transaction_type, $wip_types)) {
            // Jika ini transaksi WIP, ambil saldo terakhir (bisa dari gudang atau wip)
            // PENTING: wip_in pertama harus lanjut dari warehouse balance, bukan 0
            // Jadi cari saldo TERAKHIR dari SEMUA transaksi (gudang + wip)
        }
        
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get($this->table);
        
        if(!$query){ return 0; }
        $result = $query->row();
        return $result ? (float)$result->balance : 0;
    }
    
    /**
     * FUNGSI BARU (Dibutuhkan oleh Subkon_out untuk mengambil HPP SFG)
     */
    public function get_last_unit_value($item_id, $item_type) {
        $this->db->select('unit_value');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        $this->db->where('unit_value >', 0); // Cari HPP yang valid
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get($this->table);
        $result = $query->row();
        return $result ? (float)$result->unit_value : 0;
    }


    private function _get_master_item_query_string() {
        $this->db->reset_query();
        
        $this->db->select("id as item_id, kode_material as item_code, nama_material as item_name, satuan as item_unit, 'material' as item_type, status", FALSE)->from('materials');
        $query_materials = $this->db->get_compiled_select();
        
        $this->db->select("id as item_id, kode_barang as item_code, nama_barang as item_name, satuan as item_unit, 'semifinished' as item_type, status", FALSE)->from('semifinished_goods');
        $query_semifinished = $this->db->get_compiled_select();
        
        // FIX KRITIS: Memastikan kolom 'status' mesin diambil
        $this->db->select("id as item_id, kode_mesin as item_code, nama_mesin as item_name, satuan as item_unit, 'machine' as item_type, status", FALSE)->from('machines');
        $query_machines = $this->db->get_compiled_select();
        
        $this->db->select("id as item_id, kode_barang as item_code, nama_barang as item_name, satuan as item_unit, 'inventory' as item_type, status", FALSE)->from('inventories');
        $query_inventories = $this->db->get_compiled_select();

        return "($query_materials) UNION ALL ($query_semifinished) UNION ALL ($query_machines) UNION ALL ($query_inventories)";
    }


    // === FUNGSI DETAIL HISTORY ===
    // REVISI: Pastikan return type adalah array
    public function get_item_transaction_history($item_id, $item_type, $start_date = null, $end_date = null) {
        $this->db->reset_query();
        
        $select_clause = "
            sl.id as ledger_id, sl.transaction_date, sl.transaction_type, sl.transaction_id,
            sl.qty_in, sl.qty_out, sl.balance, sl.unit_value,
            sl.source_doc_type, sl.source_doc_id,
            grn.grn_number, s.nama_supplier,
            grn.bc_type_code as grn_bc_type, grn.bc_number as grn_bc_no, grn.bc_date as grn_bc_date,
            prh.prod_return_no,
            mr_h.mr_number,
            sih.doc_no as scrap_in_doc_no,
            soh.doc_no as scrap_out_doc_no,
            soh.bc_type_code as scrap_out_bc_type, soh.bc_number as scrap_out_bc_no, soh.bc_date as scrap_out_bc_date
            /* Hapus referensi ke production_outputs, adjustment, dll untuk menyederhanakan */
        ";

        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table . ' sl');

        // --- JOIN BERDASARKAN TIPE TRANSAKSI ---
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id AND sl.transaction_type = "in"', 'left');
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');
        $this->db->join('prod_return_header prh', 'sl.transaction_id = prh.id AND sl.transaction_type = "IN_RETURN_WIP"', 'left');
        $this->db->join('mr_header mr_h', 'sl.transaction_id = mr_h.id AND sl.transaction_type IN ("OUT_ISSUE", "production_out")', 'left'); 
        $this->db->join('scrap_in_header sih', 'sl.transaction_id = sih.id AND sl.transaction_type = "IN_SCRAP_WH"', 'left');
        $this->db->join('scrap_out_header soh', 'sl.transaction_id = soh.id AND sl.transaction_type = "OUT_SCRAP_BC"', 'left');
        
        // --- FILTER ---
        $this->db->where('sl.item_id', $item_id);
        $this->db->where('sl.item_type', $item_type);
        if ($start_date) { $this->db->where('sl.transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('sl.transaction_date <=', $end_date . ' 23:59:59'); }
        
        // --- PERBAIKAN UTAMA: Filter hanya transaksi Gudang Utama ---
        $this->db->where_in('sl.transaction_type', $this->get_warehouse_tx_types());
        // --- AKHIR PERBAIKAN ---
        
        $this->db->group_by('sl.id'); 
        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');

        $query = $this->db->get();
        if(!$query){
            log_message('error', 'Query get_item_transaction_history GAGAL! DB Error: ' . print_r($this->db->error(), true));
            return [];
        }
        log_message('debug', 'Item History SQL (Success): ' . $this->db->last_query());
        return $query->result_array();
    }
    public function delete_by_transaction_id($transaction_id, $transaction_type) {
        $this->db->reset_query();
        log_message('warning', 'Menghapus Stock Ledger: Type=' . $transaction_type . ', Detail_ID=' . $transaction_id);
        
        $this->db->where('transaction_type', $transaction_type);
        $this->db->where('transaction_id', $transaction_id); // Ini adalah ID dari subkon_out_details
        
        // Ambil data ledger sebelum dihapus untuk update saldo
        $ledgers_to_delete = $this->db->get($this->table)->result();
        
        if (empty($ledgers_to_delete)) {
            log_message('error', 'Gagal delete_by_transaction_id: Ledger tidak ditemukan.');
            return false; // Tidak ada yang dihapus
        }

        foreach ($ledgers_to_delete as $ledger) {
            // Hapus ledger
            $this->db->where('id', $ledger->id);
            if (!$this->db->delete($this->table)) {
                log_message('error', 'Gagal delete_by_transaction_id: Gagal hapus ledger ID ' . $ledger->id);
                return false;
            }
            
            // Hitung ulang saldo setelahnya
            // (PENTING: Anda harus memiliki fungsi 'recalculate_balance_after'
            //          di Stock_ledger_model Anda)
            // $this->recalculate_balance_after($ledger->item_id, $ledger->item_type, $ledger->transaction_date);
        }
        
        return true;
    }

    /**
     * === FUNGSI INSERT (REVISI KRITIS) ===
     * Dibungkus transaksi dan logika saldo diperbarui 
     * untuk menangani semua tipe transaksi dari Production_model.
     * Ini adalah PERBAIKAN UTAMA untuk bug deadlock/loading.
     */
    

    public function get_last_balance($item_id, $item_type) {
        $this->db->reset_query();
        $this->db->select('balance');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        $this->db->order_by('id', 'DESC'); // Ini akan dicepatkan oleh INDEX
        $this->db->limit(1);
        $query = $this->db->get($this->table);
        if(!$query){ return 0; }
        $result = $query->row();
        return $result ? (float)$result->balance : 0;
    }

    public function delete_by_transaction($transaction_type, $transaction_id) {
        $this->db->reset_query();
        log_message('warning', 'Menghapus Stock Ledger: Type=' . $transaction_type . ', ID=' . $transaction_id);
        $this->db->where('transaction_type', $transaction_type);
        $this->db->where('transaction_id', $transaction_id);
        return $this->db->delete($this->table);
    }

} // End Class