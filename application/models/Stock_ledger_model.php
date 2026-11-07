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
    // NOTE: wip_in/wip_out adalah INTERNAL produksi, tidak termasuk warehouse mutations
    // PUBLIC: dipakai juga oleh StockOpname_model agar daftar tx type selalu konsisten
    public function get_warehouse_tx_types() {
        // Tipe yang MENAMBAH saldo Gudang Utama
        $types_in = [
            'in',               // GRN (Barang baru)
            'sgr_in',           // SGR — Supplier General Item Receipt (machine/inventory)
            'IN_RETURN_WIP',    // Retur dari Produksi
            'IN_RETURN_CONSUMABLE', // Retur barang habis pakai ke gudang (dulu TIDAK terdaftar -> tidak pernah terbaca laporan)
            'ADJ_IN',           // Adjustment Masuk
            'adjustment',       // Legacy adjustment type
            'INITIAL_STOCK'     // Stock Awal
        ];

        // Tipe yang MENGURANGI saldo Gudang Utama
        $types_out = [
            'OUT_ISSUE',        // Issue Material ke Produksi
            'production_out',   // Issue Material/Semif ke Produksi
            'OUT_SHIPMENT',     // Kirim Barang Jadi
            'OUT_SCRAP_BC',     // Scrap Keluar Gudang
            'ADJ_OUT',          // Adjustment Keluar
            'RTN_SUPPLIER',     // Retur ke Supplier (mengurangi stock)
            'IN_SCRAP_WH',      // Scrap Masuk ke Gudang
            'OUT_CONSUMABLE',   // Pengeluaran Barang Habis Pakai
            'NG',               // Barang Rusak/NG dari Warehouse
            'OUT_QA_SAMPLE'     // Sample diambil QA dari Warehouse
        ];

        return array_merge($types_in, $types_out);
    }

    /**
     * Saldo stok gudang saat ini untuk satu item — konsisten dengan
     * get_stock_summary_report() (warehouse tx, exclude wip_in/wip_out).
     */
    public function get_current_stock($item_id, $item_type) {
        if (empty($item_id) || empty($item_type)) return 0;
        $row = $this->db
            ->select('IFNULL(SUM(sl.qty_in),0) - IFNULL(SUM(sl.qty_out),0) as balance', FALSE)
            ->from($this->table . ' sl')
            ->where('sl.item_id', $item_id)
            ->where('sl.item_type', $item_type)
            ->where_in('sl.transaction_type', $this->get_warehouse_tx_types())
            ->where_not_in('sl.transaction_type', ['wip_in', 'wip_out'])
            ->where('sl.transaction_date <=', date('Y-m-d H:i:s'))
            ->get()->row();
        return $row ? (float)$row->balance : 0;
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
    /**
     * Saldo gudang sebuah barang SEBELUM tanggal tertentu — rumusnya sama
     * dengan kolom "Stok Awal" di laporan ringkasan (hanya transaksi gudang,
     * WIP dikecualikan). Dipakai modal riwayat per barang supaya saldonya
     * tidak selalu mulai dari 0.
     */
    public function get_opening_stock($item_id, $item_type, $start_date) {
        $types = "'" . implode("','", $this->get_warehouse_tx_types()) . "'";
        $row = $this->db->query("
            SELECT COALESCE(SUM(sl.qty_in) - SUM(sl.qty_out), 0) AS opening
            FROM {$this->table} sl
            WHERE sl.item_id = ? AND sl.item_type = ?
              AND sl.transaction_type IN ({$types})
              AND sl.transaction_type NOT IN ('wip_in', 'wip_out')
              AND sl.transaction_date < ?
        ", [(int) $item_id, (string) $item_type, $start_date . ' 00:00:00'])->row();
        return $row ? (float) $row->opening : 0.0;
    }

    public function get_stock_summary_report($filters = [], $limit = 20, $offset = 0) {
        // 1. INIASILISASI VARIABEL FILTER
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;
        $warehouse_type = $filters['warehouse_type'] ?? null;
        $search_term = $filters['search'] ?? null;
        $allowed_types = ['material', 'semifinished', 'machine', 'inventory'];

        // --- Subquery 1: RECALCULATE opening stock dari WAREHOUSE transactions ONLY (exclude WIP) ---
        $warehouse_types_list = "'" . implode("','", $this->get_warehouse_tx_types()) . "'";
        // Exclude wip_in, wip_out dari warehouse calculation
        $exclude_wip = "NOT IN ('wip_in', 'wip_out')";

        $subquery_opening_sql = "
            SELECT sl.item_id, sl.item_type,
                   SUM(sl.qty_in) - SUM(sl.qty_out) as opening_stock
            FROM {$this->table} sl
            WHERE sl.transaction_type IN ({$warehouse_types_list})
              AND sl.transaction_type {$exclude_wip}
            " . ($start_date ? "AND sl.transaction_date < '{$start_date} 00:00:00'" : "AND sl.transaction_date < NOW()") . "
            GROUP BY sl.item_id, sl.item_type
        "; 

        // --- Subquery 2: Mutasi di Periode yang Diminta (EXCLUDE WIP) ---
        // Total_receive: SEMUA tipe transaksi yang menambah stok gudang
        // Total_out: SEMUA tipe transaksi yang mengurangi stok gudang
        // (sebelumnya ADJ_IN, IN_SCRAP_WH, OUT_SCRAP_BC tidak ter-cover → bikin
        //  closing_stock laporan beda dengan SUM kanonik / STO).
        $this->db->select(" item_id, item_type,
            SUM(CASE WHEN transaction_type IN ('in', 'sgr_in', 'INITIAL_STOCK', 'ADJ_IN', 'IN_SCRAP_WH') THEN qty_in ELSE 0 END) as total_receive,
            SUM(CASE WHEN transaction_type IN ('IN_RETURN_WIP', 'IN_RETURN_CONSUMABLE') THEN qty_in ELSE 0 END) as total_return,
            SUM(CASE WHEN transaction_type IN ('OUT_ISSUE', 'OUT_SHIPMENT', 'ADJ_OUT', 'production_out', 'OUT_CONSUMABLE', 'out_consumable', 'RTN_SUPPLIER', 'NG', 'OUT_QA_SAMPLE', 'OUT_SCRAP_BC') THEN qty_out ELSE 0 END) as total_out,
            SUM(CASE WHEN transaction_type = 'adjustment' THEN (qty_in - qty_out) ELSE 0 END) as total_adjustment
        ", FALSE);
        $this->db->from($this->table . ' sl');
        if ($start_date) { $this->db->where('transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('transaction_date <=', $end_date . ' 23:59:59'); }
        $this->db->where_in('transaction_type', $this->get_warehouse_tx_types());
        $this->db->where_not_in('transaction_type', ['wip_in', 'wip_out']);  // Exclude WIP
        $this->db->group_by(['item_id', 'item_type']);
        $subquery_mutation_sql = $this->db->get_compiled_select();
        $this->db->reset_query(); 


        // --- Subquery 3: saldo per KATEGORI GUDANG (General / Produksi) ---
        // Laporan ini menjumlahkan semua kategori jadi satu angka, jadi barang
        // yang stoknya terpisah di gudang General & Produksi tidak kelihatan
        // ada di mana (user 2026-09-21: "harusnya ada keterangan dia di gudang
        // kategori general atau produksi"). Rinciannya memakai rumus yang SAMA
        // dengan kolom lain, jadi jumlahnya pas dengan Stok Akhir.
        $end_cond = $end_date ? "AND transaction_date <= '{$end_date} 23:59:59'" : '';
        $subquery_category_sql = "
            SELECT item_id, item_type,
                   GROUP_CONCAT(CONCAT(kategori, ':', saldo) ORDER BY kategori SEPARATOR '|') AS category_breakdown
            FROM (
                SELECT item_id, item_type,
                       COALESCE(NULLIF(category, ''), '-') AS kategori,
                       SUM(qty_in) - SUM(qty_out) AS saldo
                FROM {$this->table} sl
                WHERE transaction_type IN ({$warehouse_types_list})
                  AND transaction_type {$exclude_wip}
                  {$end_cond}
                GROUP BY item_id, item_type, COALESCE(NULLIF(category, ''), '-')
            ) per_kategori
            GROUP BY item_id, item_type
        ";

        // --- Query Utama (FINAL) ---
        $all_items_sql = $this->_get_master_item_query_string();
        $this->db->select("
            items.item_id, items.item_type, items.item_code, items.item_name, items.item_unit,
            IFNULL(opening.opening_stock, 0) as opening_stock,
            IFNULL(mutation.total_receive, 0) as receive_qty,
            IFNULL(mutation.total_return, 0) as return_qty,
            IFNULL(mutation.total_out, 0) as out_qty,
            IFNULL(mutation.total_adjustment, 0) as adjustment_qty,
            kat.category_breakdown
        ");
        
        $this->db->select("(IFNULL(opening.opening_stock, 0) + IFNULL(mutation.total_receive, 0) + IFNULL(mutation.total_return, 0) - IFNULL(mutation.total_out, 0) + IFNULL(mutation.total_adjustment, 0)) as closing_stock", FALSE);

        $this->db->from("({$all_items_sql}) items");
        
        $this->db->join("({$subquery_opening_sql}) opening", "items.item_id = opening.item_id AND items.item_type = opening.item_type", 'left');
        $this->db->join("({$subquery_mutation_sql}) mutation", "items.item_id = mutation.item_id AND items.item_type = mutation.item_type", 'left');
        $this->db->join("({$subquery_category_sql}) kat", "items.item_id = kat.item_id AND items.item_type = kat.item_type", 'left');

        $this->db->where('items.status', 'Active');

        if ($warehouse_type) {
            // Handle both single value (string) dan array of values
            if (is_array($warehouse_type)) {
                $this->db->where_in('items.item_type', $warehouse_type);
            } else {
                $this->db->where('items.item_type', $warehouse_type);
            }
        } else {
            $this->db->where_in('items.item_type', $allowed_types);
        }
        
        if ($search_term) { $this->db->group_start()->like('items.item_code', $search_term)->or_like('items.item_name', $search_term)->group_end(); }
        
        // MENGAKTIFKAN KEMBALI FILTER KETAT (HAVING logic)
        $this->db->where('(
            opening.opening_stock IS NOT NULL OR 
            mutation.total_receive IS NOT NULL OR 
            mutation.total_return IS NOT NULL OR 
            mutation.total_out IS NOT NULL OR 
            mutation.total_adjustment IS NOT NULL 
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
        $this->db->where_not_in('transaction_type', ['wip_in', 'wip_out']);  // Exclude WIP
        $this->db->group_by(['item_id', 'item_type']);
        $subquery_max_id_sql = $this->db->get_compiled_select();
        $this->db->reset_query();
        $this->db->select("t1.item_id, t1.item_type, t1.balance as opening_stock");
        $this->db->from($this->table . ' t1')->join("({$subquery_max_id_sql}) max_ids", 't1.id = max_ids.max_id', 'inner');
        $subquery_opening_sql = $this->db->get_compiled_select();
        $this->db->reset_query();

        // --- Subquery 2: Mutasi (untuk COUNT — pakai cakupan tx type yang sama dengan get_stock_summary_report) ---
        $this->db->select("item_id, item_type,
            SUM(CASE WHEN transaction_type IN ('in', 'sgr_in', 'INITIAL_STOCK', 'ADJ_IN', 'IN_SCRAP_WH') THEN 1 ELSE 0 END) as total_receive,
            SUM(CASE WHEN transaction_type = 'IN_RETURN_WIP' THEN 1 ELSE 0 END) as total_return,
            SUM(CASE WHEN transaction_type IN ('OUT_ISSUE', 'OUT_SHIPMENT', 'ADJ_OUT', 'production_out', 'RTN_SUPPLIER', 'NG', 'OUT_QA_SAMPLE', 'OUT_CONSUMABLE', 'out_consumable', 'OUT_SCRAP_BC') THEN 1 ELSE 0 END) as total_out
        ");
        $this->db->from($this->table);
        if ($start_date) { $this->db->where('transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('transaction_date <=', $end_date . ' 23:59:59'); }
        $this->db->where_in('transaction_type', $this->get_warehouse_tx_types());
        $this->db->where_not_in('transaction_type', ['wip_in', 'wip_out']);  // Exclude WIP
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
            // Handle both single value (string) dan array of values
            if (is_array($warehouse_type)) {
                $this->db->where_in('items.item_type', $warehouse_type);
            } else {
                $this->db->where('items.item_type', $warehouse_type);
            }
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

        // PERBAIKAN: Hapus kolom BC jika tidak ada di tabel
        // Ini untuk backward compatibility dengan database lama
        $allowed_columns = $this->db->list_fields($this->table);
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed_columns)) {
                unset($data[$key]);
                log_message('debug', "Stock_ledger->insert: Kolom '{$key}' tidak ada di tabel, dihapus dari INSERT.");
            }
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
                
                // VALIDASI KRITIS: Warehouse OUT tidak boleh melebihi stock
                if ($qty_out > 0 && $current_balance < 0) {
                    $this->db->trans_rollback();
                    log_message('error', 'StockLedger->insert REJECTED: Pengeluaran melebihi stock! Item: ' . $data['item_id'] . 
                        ', Type: ' . $data['item_type'] . ', TX: ' . $data['transaction_type'] . 
                        ', Last Balance: ' . $last_balance . ', Out: ' . $qty_out . ', Hasil: ' . $current_balance);
                    return false;
                }
            } else if (in_array($data['transaction_type'], ['wip_in', 'wip_out'])) {
                // Ini transaksi WIP
                $current_balance = $last_balance + $qty_in - $qty_out;
                
                // NOTE: WIP balance dihitung per WO, bukan global
                // Validasi WIP stock dilakukan di level aplikasi (Production_model->get_wip_materials_with_remaining)
                // TIDAK perlu validasi balance < 0 di sini karena bisa dari multiple WO
                
                // VALIDASI DISABLED: WIP tracking berbeda dengan warehouse
                // WIP bisa punya balance negative di level global karena tracking per-WO
                /*
                if ($qty_out > 0 && $current_balance < 0) {
                    $this->db->trans_rollback();
                    log_message('error', 'StockLedger->insert REJECTED: WIP out melebihi stock! Item: ' . $data['item_id'] . 
                        ', Last Balance: ' . $last_balance . ', Out: ' . $qty_out);
                    return false;
                }
                */
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
               
                
                
                cc.description AS jenis_bc_desc,
                COALESCE(fdh.delivery_number, sout.subkon_out_no, soh.doc_no, adjh.doc_no, wh.out_number, gh.grn_number, grn_src.grn_number) AS source_doc_no,
                COALESCE(fdh.delivery_date, sout.shipping_date, soh.doc_date, adjh.doc_date, wh.out_date, gh.grn_date, grn_src.grn_date) AS source_doc_date,
                COALESCE(c_fg.nama_customer, s_sub.nama_supplier, c_scrap.nama_customer, s_grn_src.nama_supplier, s_grn.nama_supplier, 'N/A') AS partner_name,
                
                            -- include BC from possible GRN source used by RTN
                            COALESCE(fdh.bc_number, soh.bc_number, sout.bc_number, wh.bc_number, gh.bc_number, grn_src.bc_number) AS nomor_bc_ref,
                            COALESCE(fdh.bc_date, soh.bc_date, sout.bc_date, wh.bc_date, gh.bc_date, grn_src.bc_date) AS tanggal_bc_ref,
               
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

            -- Retur Supplier (RTN)
            LEFT JOIN warehouse_out_header wh ON sl.transaction_id = wh.id AND sl.transaction_type = 'RTN_SUPPLIER'
            -- If ledger row references a source GRN (sl.source_doc_type = 'GRN'), join it to obtain supplier info
            LEFT JOIN grn_header grn_src ON sl.source_doc_type = 'GRN' AND sl.source_doc_id = grn_src.id
            LEFT JOIN suppliers s_grn_src ON grn_src.supplier_id = s_grn_src.id

            
            LEFT JOIN materials m ON sl.item_id = m.id AND sl.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON sl.item_id = sf.id AND sl.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON sl.item_id = fg.id AND sl.item_type = 'finished_goods'

            
            LEFT JOIN common_code cc ON cc.group_code = 'BC_TYPE'
                AND (
                    (sl.transaction_type = 'fg_out' AND cc.id = fdh.bc_type_id) OR
                    (sl.transaction_type IN ('OUT_SCRAP_BC', 'production_out') AND cc.code = COALESCE(soh.bc_type_code, sout.bc_type_code)) OR
                    (sl.transaction_type = 'RTN_SUPPLIER' AND cc.code = wh.bc_type_code)
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
            // Jika ini transaksi WIP, ambil saldo terakhir WIP ONLY (tidak campur dengan warehouse)
            // WIP balance chain harus terpisah dari warehouse balance
            $this->db->where_in('transaction_type', $wip_types);
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
    // REVISI: Include opening balance row di paling atas
    public function get_item_transaction_history($item_id, $item_type, $start_date = null, $end_date = null) {
        $results = [];

        // 1. HITUNG OPENING BALANCE dari transaksi TERAKHIR sebelum periode (ambil column balance, bukan recalculate)
        if ($start_date) {
            $item_id_esc = $this->db->escape($item_id);
            $item_type_esc = $this->db->escape($item_type);
            $start_date_esc = $this->db->escape($start_date . ' 00:00:00');
            $warehouse_types_list = "'" . implode("','", $this->get_warehouse_tx_types()) . "'";

            // PERBAIKAN: Ambil balance dari MAX id sebelum start_date
            // Ini memastikan kita menggunakan nilai balance yang sudah tercatat, bukan recalculated
            $opening_sql = "
                SELECT t1.balance as opening_balance
                FROM {$this->table} t1
                INNER JOIN (
                    SELECT MAX(id) as max_id
                    FROM {$this->table}
                    WHERE item_id = {$item_id_esc}
                      AND item_type = {$item_type_esc}
                      AND transaction_type IN ({$warehouse_types_list})
                      AND transaction_type NOT IN ('wip_in', 'wip_out')
                      AND transaction_date < {$start_date_esc}
                ) max_ids ON t1.id = max_ids.max_id
            ";

            log_message('debug', "Opening balance SQL: {$opening_sql}");

            $opening_row = $this->db->query($opening_sql)->row();
            $opening_balance = ($opening_row && $opening_row->opening_balance !== null) ? (float)$opening_row->opening_balance : 0;

            log_message('debug', "Opening balance result: {$opening_balance}");

            // Add opening balance row (show even if 0, untuk transparency)
            $results[] = [
                'ledger_id' => null,
                'transaction_date' => $start_date . ' 00:00:00',
                'transaction_type' => 'OPENING_BALANCE',
                'transaction_id' => null,
                'qty_in' => 0,
                'qty_out' => 0,
                'balance' => $opening_balance,
                'unit_value' => 0,
                'source_doc_type' => null,
                'source_doc_id' => null,
                'sl_bc_type' => null,
                'sl_bc_no' => null,
                'sl_bc_date' => null,
                'grn_number' => null,
                'rtn_out_number' => null,
                'nama_supplier' => null,
                'grn_bc_type' => null,
                'grn_bc_no' => null,
                'grn_bc_date' => null,
                'prod_return_no' => null,
                'mr_number' => null,
                'scrap_in_doc_no' => null,
                'scrap_out_doc_no' => null,
                'scrap_out_bc_type' => null,
                'scrap_out_bc_no' => null,
                'scrap_out_bc_date' => null,
                'adjustment_doc' => null,
                'adjustment_notes' => null,
                'consumable_out_doc_no' => null,
                'qa_sampling_doc_no' => null,
                'qa_sampling_purpose' => null,
                'qa_requester_name' => null,
                'qa_requester_dept' => null
            ];
        }

        // 2. AMBIL TRANSAKSI PERIODE
        $this->db->reset_query();

        $select_clause = "
            sl.id as ledger_id, sl.transaction_date, sl.transaction_type, sl.transaction_id,
            sl.issuance_id,
            sl.qty_in, sl.qty_out, sl.balance, sl.unit_value,
            sl.source_doc_type, sl.source_doc_id,
            sl.bc_type_code as sl_bc_type, sl.bc_number as sl_bc_no, sl.bc_date as sl_bc_date,
            grn.grn_number,
            wh.out_number as rtn_out_number,
            COALESCE(NULLIF(s.nama_supplier, ''), NULLIF(s_po.nama_supplier, ''), NULLIF(s_mir.nama_supplier, ''), NULLIF(s_grn_src.nama_supplier, ''), NULLIF(s_wh.nama_supplier, ''), NULLIF(wh.nama_supplier, '')) as nama_supplier,
            grn.bc_type_code as grn_bc_type, grn.bc_number as grn_bc_no, grn.bc_date as grn_bc_date,
            COALESCE(prh.prod_return_no, mri.issuance_number) as prod_return_no,
            mri.issuance_number,
            mr_h_wip.mr_number as mr_number_wip,
            mr_h.mr_number,
            sih.doc_no as scrap_in_doc_no,
            soh.doc_no as scrap_out_doc_no,
            soh.bc_type_code as scrap_out_bc_type, soh.bc_number as scrap_out_bc_no, soh.bc_date as scrap_out_bc_date,
            ah.doc_no as adjustment_doc,
            ah.notes as adjustment_notes,
            coh.doc_number as consumable_out_doc_no,
            qash.doc_no as qa_sampling_doc_no,
            qash.purpose as qa_sampling_purpose,
            qa_user.fullname as qa_requester_name,
            qash.requester_dept as qa_requester_dept
        ";

        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table . ' sl');

        // --- JOIN BERDASARKAN TIPE TRANSAKSI ---
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id AND sl.transaction_type = "in"', 'left');
        $this->db->join('consumable_out_header coh', 'sl.transaction_id = coh.id AND sl.transaction_type = "OUT_CONSUMABLE"', 'left');
        $this->db->join('grn_details gd', 'grn.id = gd.grn_header_id AND gd.item_id = sl.item_id', 'left');
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');
        $this->db->join('po_header poh', 'gd.source_doc_type = "PO" AND gd.source_doc_header_id = poh.id', 'left');
        $this->db->join('suppliers s_po', 's_po.id = poh.supplier_id', 'left');
        $this->db->join('mir_header mir', 'gd.source_doc_type = "MIR" AND gd.source_doc_header_id = mir.id', 'left');
        $this->db->join('suppliers s_mir', 's_mir.id = mir.supplier_id', 'left');
        $this->db->join('warehouse_out_header wh', 'sl.transaction_id = wh.id AND sl.transaction_type = "RTN_SUPPLIER"', 'left');
        $this->db->join('suppliers s_wh', 'wh.supplier_id = s_wh.id', 'left');
        $this->db->join('grn_header grn_src', 'sl.source_doc_type = "GRN" AND sl.source_doc_id = grn_src.id', 'left');
        $this->db->join('suppliers s_grn_src', 's_grn_src.id = grn_src.supplier_id', 'left');
        // Legacy prod_return flow
        $this->db->join('prod_return_detail prd', 'sl.transaction_id = prd.id AND sl.transaction_type = "IN_RETURN_WIP" AND sl.issuance_id IS NULL', 'left');
        $this->db->join('prod_return_header prh', 'prd.prod_return_id = prh.id', 'left');
        // New QC-based IN_RETURN_WIP: join via issuance_id → mr_issuances → mr_header
        $this->db->join('mr_issuances mri', 'sl.issuance_id = mri.id AND sl.transaction_type = "IN_RETURN_WIP"', 'left');
        $this->db->join('mr_header mr_h_wip', 'mri.mr_header_id = mr_h_wip.id', 'left');
        $this->db->join('mr_header mr_h', 'sl.transaction_id = mr_h.id AND sl.transaction_type IN ("OUT_ISSUE", "production_out")', 'left');
        $this->db->join('scrap_in_header sih', 'sl.transaction_id = sih.id AND (sl.transaction_type = "IN_SCRAP_WH" OR sl.transaction_type = "NG")', 'left');
        $this->db->join('scrap_out_header soh', 'sl.transaction_id = soh.id AND sl.transaction_type = "OUT_SCRAP_BC"', 'left');
        $this->db->join('adjustment_details ad', 'sl.transaction_id = ad.id AND sl.transaction_type = "adjustment"', 'left');
        $this->db->join('adjustment_header ah', 'ad.adjustment_header_id = ah.id', 'left');
        // QA Sampling (sample diambil QA dari warehouse)
        $this->db->join('qa_sampling_header qash', 'sl.source_doc_id = qash.id AND sl.transaction_type = "OUT_QA_SAMPLE"', 'left');
        $this->db->join('users qa_user', 'qa_user.id = qash.requester_id', 'left');

        // --- FILTER ---
        $this->db->where('sl.item_id', $item_id);
        $this->db->where('sl.item_type', $item_type);
        if ($start_date) { $this->db->where('sl.transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('sl.transaction_date <=', $end_date . ' 23:59:59'); }

        // --- Filter hanya transaksi Gudang Utama (exclude WIP) ---
        $this->db->where_in('sl.transaction_type', $this->get_warehouse_tx_types());
        $this->db->where_not_in('sl.transaction_type', ['wip_in', 'wip_out']);

        $this->db->group_by('sl.id');
        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');

        $query = $this->db->get();
        if(!$query){
            log_message('error', 'Query get_item_transaction_history GAGAL! DB Error: ' . print_r($this->db->error(), true));
            return $results; // Return opening balance jika ada, meski query gagal
        }

        // Merge opening balance + period transactions
        $results = array_merge($results, $query->result_array());

        log_message('debug', 'Item History SQL (Success): ' . $this->db->last_query());
        return $results;
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
    

    /**
     * @param string|null $category 'General'/'Produksi' — WAJIB diisi utk item_type
     *        inventory/machine (saldo mereka benar-benar dipecah per kategori,
     *        beda bucket = beda angka). Item type lain (material/semifinished/
     *        finished_goods/scrap) tidak pernah pakai kategori — biarkan null,
     *        supaya query tetap tanpa filter category seperti sebelum ini ada.
     */
    public function get_last_balance($item_id, $item_type, $category = null) {
        $this->db->reset_query();
        $this->db->select('balance');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        if ($category !== null) {
            $this->db->where('category', $category);
        }
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

    /**
     * FUNGSI PERBAIKAN: Recalculate Balance untuk Item Tertentu
     * PERBAIKAN: Mulai dari opening balance, bukan dari 0
     */
    public function recalculate_balance_for_item($item_id, $item_type) {
        log_message('info', "Recalculating balance for item_id={$item_id}, item_type={$item_type}");

        $this->db->trans_start();

        // Ambil semua transaksi untuk item ini, urut by date + id
        $this->db->select('id, qty_in, qty_out');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        $this->db->order_by('transaction_date', 'ASC');
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get($this->table);

        if (!$query || $query->num_rows() == 0) {
            log_message('warning', "No transactions found for item_id={$item_id}");
            return false;
        }

        $transactions = $query->result();

        // PERBAIKAN: Ambil opening balance (balance SEBELUM transaksi pertama)
        if (!empty($transactions)) {
            $first_tx_id = $transactions[0]->id;
            $this->db->reset_query();
            $this->db->select('balance');
            $this->db->where('item_id', $item_id);
            $this->db->where('item_type', $item_type);
            $this->db->where('id <', $first_tx_id);
            $this->db->order_by('id', 'DESC');
            $this->db->limit(1);
            $opening_row = $this->db->get($this->table)->row();
            $running_balance = $opening_row ? (float)$opening_row->balance : 0;
        } else {
            $running_balance = 0;
        }

        log_message('info', "Starting balance for item_id={$item_id}: {$running_balance}");

        // Recalculate balance untuk setiap transaksi
        foreach ($transactions as $tx) {
            $qty_in = (float)($tx->qty_in ?? 0);
            $qty_out = (float)($tx->qty_out ?? 0);
            $running_balance += ($qty_in - $qty_out);

            // Update balance di database
            $this->db->where('id', $tx->id);
            $update_result = $this->db->update($this->table, ['balance' => round($running_balance, 5)]);

            if (!$update_result) {
                log_message('error', "Failed to update balance for ledger_id={$tx->id}");
                $this->db->trans_rollback();
                return false;
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            log_message('error', "Transaction failed for item_id={$item_id}");
            return false;
        }

        log_message('info', "Successfully recalculated balance for item_id={$item_id}, total transactions={$query->num_rows()}, final balance={$running_balance}");
        return true;
    }

    /**
     * FUNGSI DIAGNOSTIC: Check Discrepancy antara Detail vs Summary
     * Return array dengan info tentang balance terakhir dan summary calculation
     * PERBAIKAN: Include opening balance dalam calculated_balance
     */
    public function check_balance_discrepancy($item_id, $item_type, $start_date = null, $end_date = null) {
        // 1. Ambil balance terakhir dari detail transaksi
        $this->db->select('balance');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        if ($end_date) {
            $this->db->where('transaction_date <=', $end_date . ' 23:59:59');
        }
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $last_tx = $this->db->get($this->table)->row();
        $last_balance = $last_tx ? (float)$last_tx->balance : 0;

        // 2. Hitung opening balance (balance SEBELUM start_date jika ada)
        $opening_balance = 0;
        if ($start_date) {
            $this->db->reset_query();
            $this->db->select('balance');
            $this->db->where('item_id', $item_id);
            $this->db->where('item_type', $item_type);
            $this->db->where('transaction_date <', $start_date . ' 00:00:00');
            $this->db->order_by('id', 'DESC');
            $this->db->limit(1);
            $opening_tx = $this->db->get($this->table)->row();
            $opening_balance = $opening_tx ? (float)$opening_tx->balance : 0;
        }

        // 3. Hitung summary calculation (opening + mutations dalam periode, EXCLUDE WIP)
        $this->db->reset_query();
        $this->db->select("
            SUM(CASE WHEN transaction_type IN ('" . implode("','", $this->get_warehouse_tx_types()) . "') AND transaction_type NOT IN ('wip_in', 'wip_out') THEN (qty_in - qty_out) ELSE 0 END) as period_mutations
        ", FALSE);
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        if ($start_date) {
            $this->db->where('transaction_date >=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $this->db->where('transaction_date <=', $end_date . ' 23:59:59');
        }
        $summary = $this->db->get($this->table)->row();
        $period_mutations = $summary ? (float)$summary->period_mutations : 0;

        // Calculated balance = opening + mutations
        $calculated_balance = $opening_balance + $period_mutations;

        // 4. Return hasil perbandingan
        return [
            'item_id' => $item_id,
            'item_type' => $item_type,
            'opening_balance' => $opening_balance,
            'period_mutations' => $period_mutations,
            'last_recorded_balance' => $last_balance,  // Balance dari detail transaksi terakhir
            'calculated_balance' => $calculated_balance,  // Balance dari summary calc (opening + mutations)
            'discrepancy' => abs($last_balance - $calculated_balance),  // Selisih
            'has_issue' => abs($last_balance - $calculated_balance) > 0.01  // Apakah ada masalah?
        ];
    }

} // End Class