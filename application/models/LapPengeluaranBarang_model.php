<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LapPengeluaranBarang_model extends CI_Model {

    private $table_ledger = 'stock_ledger';
    
    private function get_outgoing_tx_types() {
        return ['fg_out', 'OUT_SCRAP_BC', 'ADJ_OUT', 'production_out']; 
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
                
                -- 1. DOKUMEN PABEAN (BC)
                cc.description AS jenis_bc, 
                COALESCE(fdh.bc_number, soh.bc_number, sout.bc_number, '') AS nomor_bc,
                COALESCE(fdh.bc_date, soh.bc_date, sout.bc_date, NULL) AS tanggal_bc,
                
                -- 2. BUKTI PENGELUARAN & PENERIMA BARANG
                COALESCE(s_sub.nama_supplier, c_fg.nama_customer, s_scrap.nama_supplier, 'N/A') AS partner_name,
                
                CASE sl.transaction_type
                    WHEN 'production_out' THEN sout.subkon_out_no
                    WHEN 'fg_out' THEN fdh.delivery_number
                    WHEN 'OUT_SCRAP_BC' THEN soh.doc_no
                    ELSE adjh.doc_no
                END AS nomor_pengeluaran,
                
                COALESCE(fdh.shipped_at, sout.shipping_date, soh.doc_date, sl.transaction_date) AS tanggal_pengeluaran,
                
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
            LEFT JOIN master_scrap scr ON sl.item_id = scr.id AND sl.item_type = 'scrap'
            LEFT JOIN suppliers s_scrap ON soh.destination = s_scrap.id 

            LEFT JOIN adjustment_details ad ON sl.transaction_id = ad.id AND sl.transaction_type = 'ADJ_OUT'
            LEFT JOIN adjustment_header adjh ON ad.adjustment_header_id = adjh.id

            LEFT JOIN materials m ON sl.item_id = m.id AND sl.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON sl.item_id = sf.id AND sl.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON sl.item_id = fg.id AND sl.item_type = 'finished_goods'
            LEFT JOIN machines mc ON sl.item_id = mc.id AND sl.item_type = 'machine'

            -- FIX KRITIS: JOIN COMMON CODE (Menggunakan Raw SQL untuk BC Type)
            LEFT JOIN common_code cc ON cc.group_code = 'BC_TYPE'
                AND (
                    (sl.transaction_type = 'fg_out' AND cc.id = fdh.bc_type_id) 
                    OR
                    (sl.transaction_type IN ('OUT_SCRAP_BC', 'production_out') AND cc.code = COALESCE(soh.bc_type_code, sout.bc_type_code))
                )


            -- FILTER UTAMA (Eksternal Out)
            WHERE sl.transaction_type IN ('fg_out', 'OUT_SCRAP_BC', 'ADJ_OUT')
            OR (sl.transaction_type = 'production_out' AND sl.source_doc_type = 'SUBKON_OUT')
            
            " . $this->_build_tx_type_where($filters) . "
            " . $this->_build_search_where($filters) . "

            AND DATE(sl.transaction_date) >= ? 
            AND DATE(sl.transaction_date) <= ?
        ";
        
        $params = [
            $filters['start_date'], $filters['end_date']
        ];

        // Tambahkan LIMIT dan OFFSET hanya jika limit > 0
        if ($limit > 0) {
            $sql .= " ORDER BY sl.transaction_date DESC LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
        } else {
            $sql .= " ORDER BY sl.transaction_date DESC";
        }

        return $this->db->query($sql, $params)->result();
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
        $where .= " OR COALESCE(fdh.delivery_number, sout.subkon_out_no, soh.doc_no, adjh.doc_no) LIKE '%{$search}%' ";
        $where .= " OR COALESCE(s_sub.nama_supplier, c_fg.nama_customer, s_scrap.nama_supplier) LIKE '%{$search}%' )";
        return $where;
    }
    
    public function count_report($filters = []) {
        $this->db->from($this->table_ledger . ' sl');
        $this->db->where_in('sl.transaction_type', $this->get_outgoing_tx_types());
        $this->db->group_start();
            $this->db->where_in('sl.transaction_type', ['fg_out', 'OUT_SCRAP_BC', 'ADJ_OUT']);
            $this->db->or_where('(sl.transaction_type = "production_out" AND sl.source_doc_type = "SUBKON_OUT")');
        $this->db->group_end();
        return $this->db->count_all_results(); 
    }
}