<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LapPengeluaranBarang_model extends CI_Model {

    private $table_ledger = 'stock_ledger';
    
    private function get_outgoing_tx_types() {
        return ['fg_out', 'OUT_SCRAP_BC', 'ADJ_OUT', 'production_out', 'RTN_SUPPLIER']; 
    }

    /**
     * Mengambil daftar semua pengeluaran eksternal (dengan detail Nilai/BC).
     */
    public function get_report($filters = [], $limit = 25, $offset = 0) {
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;
        $tx_type = $filters['tx_type'] ?? null;
        $search = $filters['search'] ?? null;

        $sql = "
            SELECT
                sl.id AS ledger_id,
                sl.transaction_date,
                sl.qty_out AS jumlah,
                sl.unit_value,
                sl.item_type,
                sl.transaction_type AS tx_type_raw,
                sl.source_doc_type,
                
                -- 1. DOKUMEN PABEAN (BC) - Ambil dari masing-masing sumber
                COALESCE(cc_scrap.description, cc_fg.description, cc_sub.description, cc_wh.description, '-') AS jenis_bc, 
                COALESCE(fdh.bc_number, soh.bc_number, sout.bc_number, wh.bc_number, '') AS nomor_bc,
                COALESCE(fdh.bc_date, soh.bc_date, sout.bc_date, wh.bc_date, NULL) AS tanggal_bc,
                
                -- 2. BUKTI PENGELUARAN & PENERIMA BARANG
                COALESCE(s_sub.nama_supplier, c_fg.nama_customer, s_scrap.nama_supplier, sup.nama_supplier, sup_gh.nama_supplier, wh.nama_supplier, 'N/A') AS partner_name,
                
                CASE sl.transaction_type
                    WHEN 'production_out' THEN sout.subkon_out_no
                    WHEN 'fg_out' THEN fdh.delivery_number
                    WHEN 'OUT_SCRAP_BC' THEN soh.doc_no
                    WHEN 'RTN_SUPPLIER' THEN wh.out_number
                    ELSE adjh.doc_no
                END AS nomor_pengeluaran,
                
                COALESCE(fdh.shipped_at, sout.shipping_date, soh.doc_date, wh.out_date, sl.transaction_date) AS tanggal_pengeluaran,
                
                -- 3. DETAIL ITEM & NILAI
                COALESCE(m.kode_material, sf.kode_barang, fg.kode_barang, mc.kode_mesin, scr.scrap_code) AS item_code,
                COALESCE(m.nama_material, sf.nama_barang, fg.nama_barang, mc.nama_mesin, scr.scrap_name) AS item_name,
                COALESCE(m.satuan, sf.satuan, fg.satuan, mc.satuan, scr.unit) AS item_unit,

                -- FIX KRITIS: MATA UANG & NILAI VALUTA ASING
                COALESCE(sc.currency_code, 'IDR') AS mata_uang,
                CASE sl.transaction_type
                    WHEN 'fg_out' THEN (fdd.qty_shipped * sc.unit_price) -- Nilai Valuta Asing (FC)
                    ELSE NULL 
                END AS nilai_barang_fc,
                
                -- NILAI BARANG (IDR)
                CASE sl.transaction_type
                    WHEN 'fg_out' THEN (fdd.qty_shipped * sc.unit_price * 1) -- Harga Jual Konversi
                    WHEN 'OUT_SCRAP_BC' THEN (sl.qty_out * sod_scrap.sales_value_per_unit)
                    ELSE (sl.qty_out * sl.unit_value)                      
                END AS nilai_barang_idr

            FROM stock_ledger sl

            -- =================== KLAUSA JOIN LENGKAP ===================
            
            LEFT JOIN fg_delivery_details fdd ON sl.transaction_id = fdd.id AND sl.transaction_type = 'fg_out'
            LEFT JOIN fg_delivery_header fdh ON fdd.fg_delivery_header_id = fdh.id
            LEFT JOIN customers c_fg ON fdh.customer_id = c_fg.id
            LEFT JOIN sales_contracts sc ON fdd.sales_contract_id = sc.id 

            LEFT JOIN subkon_out_details sod ON sl.transaction_id = sod.id AND sl.source_doc_type = 'SUBKON_OUT'
            LEFT JOIN subkon_out_header sout ON sod.subkon_out_id = sout.id
            LEFT JOIN suppliers s_sub ON sout.supplier_id = s_sub.id

            LEFT JOIN scrap_out_detail sod_scrap ON sl.transaction_id = sod_scrap.id AND sl.transaction_type = 'OUT_SCRAP_BC'
            LEFT JOIN scrap_out_header soh ON sod_scrap.header_id = soh.id
            LEFT JOIN common_code cc_scrap ON cc_scrap.group_code = 'BC_TYPE' AND cc_scrap.code = soh.bc_type_code
            LEFT JOIN master_scrap scr ON sl.item_id = scr.id AND sl.item_type = 'scrap'
            LEFT JOIN suppliers s_scrap ON soh.destination = s_scrap.id 

            LEFT JOIN warehouse_out_header wh ON sl.transaction_id = wh.id AND sl.transaction_type = 'RTN_SUPPLIER'
            LEFT JOIN suppliers sup ON wh.supplier_id = sup.id
            -- If header supplier not set, try to read supplier from the linked GRN via warehouse_out_details
            LEFT JOIN warehouse_out_details wod ON wh.id = wod.header_id AND wod.item_id = sl.item_id AND wod.item_type = sl.item_type
            LEFT JOIN grn_header gh ON wod.grn_id = gh.id
            LEFT JOIN suppliers sup_gh ON gh.supplier_id = sup_gh.id


            LEFT JOIN adjustment_details ad ON sl.transaction_id = ad.id AND sl.transaction_type = 'ADJ_OUT'
            LEFT JOIN adjustment_header adjh ON ad.adjustment_header_id = adjh.id

            LEFT JOIN materials m ON sl.item_id = m.id AND sl.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON sl.item_id = sf.id AND sl.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON sl.item_id = fg.id AND sl.item_type = 'finished_goods'
            LEFT JOIN machines mc ON sl.item_id = mc.id AND sl.item_type = 'machine'

            LEFT JOIN common_code cc_fg ON cc_fg.group_code = 'BC_TYPE' AND cc_fg.id = fdh.bc_type_id
            LEFT JOIN common_code cc_sub ON cc_sub.group_code = 'BC_TYPE' AND cc_sub.code = sout.bc_type_code
            LEFT JOIN common_code cc_wh ON cc_wh.group_code = 'BC_TYPE' AND cc_wh.code = wh.bc_type_code
            
            -- Combine BC Type dari berbagai sumber
            LEFT JOIN common_code cc ON cc.id = COALESCE(cc_fg.id, cc_scrap.id, cc_sub.id, cc_wh.id)


            -- FILTER UTAMA (Eksternal Out)
            WHERE (
                sl.transaction_type IN ('fg_out', 'OUT_SCRAP_BC', 'ADJ_OUT')
                OR (sl.transaction_type = 'production_out' AND sl.source_doc_type = 'SUBKON_OUT')
                OR sl.transaction_type = 'RTN_SUPPLIER'
            )
            
            -- Filter EXIM Verification: Tampilkan yang sudah Confirmed DAN sudah diverifikasi EXIM
            AND (
                (sl.transaction_type = 'fg_out' AND fdh.status = 'Confirmed' AND fdh.exim_verified = 1)
                OR (sl.transaction_type = 'OUT_SCRAP_BC' AND soh.status = 'Confirmed' AND soh.exim_verified = 1)
                OR (sl.transaction_type = 'production_out' AND sl.source_doc_type = 'SUBKON_OUT' AND sout.status = 'Confirmed' AND sout.exim_verified = 1)
                OR (sl.transaction_type = 'ADJ_OUT' AND adjh.status = 'Confirmed')
                OR (sl.transaction_type = 'RTN_SUPPLIER' AND wh.status = 'Confirmed' AND wh.exim_verified = 1)
            )
            
            " . $this->_build_tx_type_where($filters) . "
            " . $this->_build_search_where($filters) . "

            AND DATE(COALESCE(fdh.bc_date, soh.bc_date, sout.bc_date, sl.transaction_date)) >= ?
            AND DATE(COALESCE(fdh.bc_date, soh.bc_date, sout.bc_date, sl.transaction_date)) <= ?
        ";

        // === UNION: NG QA Return & Production Return dari warehouse_out_header langsung ===
        // Why: dua tipe ini tidak punya row di stock_ledger (skip ledger di Warehouse_out::do_confirm)
        // tapi tetap perlu muncul di laporan BC pengeluaran untuk Kawasan Berikat.
        $sql .= "
            UNION ALL
            SELECT
                NULL AS ledger_id,
                wh2.out_date AS transaction_date,
                wod2.qty_return AS jumlah,
                wod2.unit_value_idr AS unit_value,
                wod2.item_type AS item_type,
                'RTN_SUPPLIER' AS tx_type_raw,
                wh2.base_on AS source_doc_type,
                COALESCE(cc_wh2.description, '-') AS jenis_bc,
                COALESCE(wh2.bc_number, '') AS nomor_bc,
                wh2.bc_date AS tanggal_bc,
                COALESCE(sup2.nama_supplier, wh2.nama_supplier, 'N/A') AS partner_name,
                wh2.out_number AS nomor_pengeluaran,
                COALESCE(wh2.bc_date, wh2.out_date) AS tanggal_pengeluaran,
                COALESCE(m2.kode_material, sf2.kode_barang) AS item_code,
                COALESCE(m2.nama_material, sf2.nama_barang) AS item_name,
                COALESCE(m2.satuan, sf2.satuan) AS item_unit,
                'IDR' AS mata_uang,
                NULL AS nilai_barang_fc,
                (wod2.qty_return * COALESCE(wod2.unit_value_idr, 0)) AS nilai_barang_idr
            FROM warehouse_out_header wh2
            JOIN warehouse_out_details wod2 ON wod2.header_id = wh2.id
            LEFT JOIN suppliers sup2 ON sup2.id = wh2.supplier_id
            LEFT JOIN materials m2 ON m2.id = wod2.item_id AND wod2.item_type = 'material'
            LEFT JOIN semifinished_goods sf2 ON sf2.id = wod2.item_id AND wod2.item_type = 'semifinished'
            LEFT JOIN common_code cc_wh2 ON cc_wh2.group_code = 'BC_TYPE' AND cc_wh2.code = wh2.bc_type_code
            WHERE wh2.base_on IN ('NG', 'Production Return')
              AND wh2.status = 'Confirmed'
              AND wh2.exim_verified = 1
              AND wod2.qty_return > 0
              " . $this->_build_tx_type_where_union($filters) . "
              " . $this->_build_search_where_union($filters) . "
              AND DATE(COALESCE(wh2.bc_date, wh2.out_date)) >= ?
              AND DATE(COALESCE(wh2.bc_date, wh2.out_date)) <= ?
        ";

        $params = [
            $filters['start_date'], $filters['end_date'],
            // params untuk UNION (urutan harus sama dgn ? di union part)
            $filters['start_date'], $filters['end_date'],
        ];

        // Tambahkan LIMIT dan OFFSET hanya jika limit > 0
        if ($limit > 0) {
            $sql .= " ORDER BY tanggal_pengeluaran DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        } else {
            $sql .= " ORDER BY tanggal_pengeluaran DESC";
        }

        return $this->db->query($sql, $params)->result();
    }

    /**
     * Helper: tx_type filter untuk UNION part (NG/Production Return).
     * Hanya tampilkan jika filter kosong atau RTN_SUPPLIER, karena ini tipe pengeluaran retur.
     */
    private function _build_tx_type_where_union($filters) {
        if (empty($filters['tx_type'])) return '';
        if ($filters['tx_type'] === 'RTN_SUPPLIER') return '';
        // Filter lain (fg_out, OUT_SCRAP_BC, dll) → exclude UNION rows
        return ' AND 1=0 ';
    }

    private function _build_search_where_union($filters) {
        if (empty($filters['search'])) return '';
        $search = $this->db->escape_like_str($filters['search']);
        return " AND (COALESCE(m2.kode_material, sf2.kode_barang) LIKE '%{$search}%'
            OR COALESCE(m2.nama_material, sf2.nama_barang) LIKE '%{$search}%'
            OR wh2.out_number LIKE '%{$search}%'
            OR COALESCE(sup2.nama_supplier, wh2.nama_supplier) LIKE '%{$search}%') ";
    }

    // --- Helper Functions (Sama) ---
    private function _build_tx_type_where($filters) {
        if (empty($filters['tx_type'])) { return ''; }
        $type = $filters['tx_type'];
        $where = ' AND (';
        if ($type == 'production_out') {
             $where .= '(sl.transaction_type = "production_out" AND sl.source_doc_type = "SUBKON_OUT")';
        } else {
            $where .= 'sl.transaction_type = "' . $this->db->escape_str($type) . '"';
        }
        return $where . ')';
    }

    private function _build_search_where($filters) {
        if (empty($filters['search'])) { return ''; }
        $search = $this->db->escape_like_str($filters['search']);
        $where = " AND (COALESCE(m.kode_material, sf.kode_barang, fg.kode_barang, mc.kode_mesin, scr.scrap_code) LIKE '%{$search}%' ";
        $where .= " OR COALESCE(m.nama_material, sf.nama_barang, fg.nama_barang, mc.nama_mesin, scr.scrap_name) LIKE '%{$search}%' ";
        $where .= " OR COALESCE(fdh.delivery_number, sout.subkon_out_no, soh.doc_no, wh.out_number, adjh.doc_no) LIKE '%{$search}%' ";
        $where .= " OR COALESCE(s_sub.nama_supplier, c_fg.nama_customer, s_scrap.nama_supplier, sup.nama_supplier, wh.nama_supplier) LIKE '%{$search}%' )";
        return $where;
    }
    
    public function count_report($filters = []) {
        // Hitung baris dari hasil get_report (limit=0 → seluruh baris)
        // Lebih reliable daripada count terpisah karena query utama pakai UNION
        // dengan beberapa filter dinamis. Performance trade-off acceptable untuk laporan BC.
        $rows = $this->get_report($filters, 0, 0);
        return is_array($rows) ? count($rows) : 0;
    }

    /**
     * @deprecated kept for reference; count_report sekarang re-use get_report.
     */
    private function _count_report_legacy($filters = []) {
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;

        $this->db->from($this->table_ledger . ' sl');
        
        // JOIN untuk filter EXIM verification
        $this->db->join('fg_delivery_details fdd', 'sl.transaction_id = fdd.id AND sl.transaction_type = "fg_out"', 'left');
        $this->db->join('fg_delivery_header fdh', 'fdd.fg_delivery_header_id = fdh.id', 'left');
        $this->db->join('scrap_out_detail sod_scrap', 'sl.transaction_id = sod_scrap.id AND sl.transaction_type = "OUT_SCRAP_BC"', 'left');
        $this->db->join('scrap_out_header soh', 'sod_scrap.header_id = soh.id', 'left');
        // JOIN untuk subkon_out verification
        $this->db->join('subkon_out_details sod_sub', 'sl.transaction_id = sod_sub.id AND sl.transaction_type = "production_out" AND sl.source_doc_type = "SUBKON_OUT"', 'left');
        $this->db->join('subkon_out_header sout', 'sod_sub.subkon_out_id = sout.id', 'left');
        // JOIN untuk adjustment
        $this->db->join('adjustment_details ad', 'sl.transaction_id = ad.id AND sl.transaction_type = "ADJ_OUT"', 'left');
        $this->db->join('adjustment_header adjh', 'ad.adjustment_header_id = adjh.id', 'left');
        
        $this->db->where_in('sl.transaction_type', $this->get_outgoing_tx_types());
        // join warehouse_out for RTN filters/counts
        $this->db->join('warehouse_out_header wh', 'sl.transaction_id = wh.id AND sl.transaction_type = "RTN_SUPPLIER"', 'left');
        $this->db->group_start();
            $this->db->where_in('sl.transaction_type', ['fg_out', 'OUT_SCRAP_BC', 'ADJ_OUT']);
            $this->db->or_where('(sl.transaction_type = "production_out" AND sl.source_doc_type = "SUBKON_OUT")');
        $this->db->group_end();
        
        // Filter EXIM Verification - Tampilkan yang Confirmed (tidak harus verified dulu)
        $this->db->group_start();
            $this->db->where('(sl.transaction_type = "fg_out" AND fdh.status = "Confirmed")');
            $this->db->or_where('(sl.transaction_type = "OUT_SCRAP_BC" AND soh.status = "Confirmed")');
            $this->db->or_where('(sl.transaction_type = "production_out" AND sl.source_doc_type = "SUBKON_OUT" AND sout.status = "Confirmed")');
            $this->db->or_where('(sl.transaction_type = "ADJ_OUT" AND adjh.status = "Confirmed")');
            $this->db->or_where('(sl.transaction_type = "RTN_SUPPLIER" AND wh.status = "Confirmed")');
        $this->db->group_end();
        
        // Filter tanggal dokumen BC (bukan tanggal pengeluaran)
        if ($start_date) {
            $this->db->where('COALESCE(fdh.bc_date, soh.bc_date, sout.bc_date, sl.transaction_date) >=', $start_date . ' 00:00:00');
        }
        if ($end_date) {
            $this->db->where('COALESCE(fdh.bc_date, soh.bc_date, sout.bc_date, sl.transaction_date) <=', $end_date . ' 23:59:59');
        }
        
        return $this->db->count_all_results(); 
    }
}