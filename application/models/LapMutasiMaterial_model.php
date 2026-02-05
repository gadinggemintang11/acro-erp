<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LapMutasiMaterial_model extends CI_Model {

    public function get_mutasi($start_date, $end_date, $item_type_filter, $search_term) 
    {
        $date_awal_minus_1_dt = $start_date . ' 00:00:00';
        $date_start_dt = $start_date . ' 00:00:00';
        $date_end_dt = $end_date . ' 23:59:59';
        
        $sql_items = "
            (
                SELECT 'material' as item_type, id, kode_material as kode_barang, nama_material as nama_barang, satuan
                FROM materials
                WHERE status = 'active'
            )
            UNION ALL
            (
                SELECT 'semifinished' as item_type, id, kode_barang, nama_barang, satuan
                FROM semifinished_goods
                WHERE status = 'active'
            )
        ";

        if (!empty($item_type_filter)) {
            $sql_items = "
                (
                    SELECT '{$item_type_filter}' as item_type, id, 
                           " . ($item_type_filter == 'material' ? 'kode_material' : 'kode_barang') . " as kode_barang, 
                           " . ($item_type_filter == 'material' ? 'nama_material' : 'nama_barang') . " as nama_barang, 
                           satuan
                    FROM " . ($item_type_filter == 'material' ? 'materials' : 'semifinished_goods') . "
                    WHERE status = 'active'
                )
            ";
        }
        
        $sql_search = "";
        if (!empty($search_term)) {
            $safe_search = $this->db->escape_like_str($search_term);
            $sql_search = " AND (i.kode_barang LIKE '%{$safe_search}%' OR i.nama_barang LIKE '%{$safe_search}%') ";
        }

        $sql = "
            WITH common_items AS (
                $sql_items
            ),
            saldo_awal AS (
                SELECT sl.item_id, sl.item_type, sl.balance,
                       1 as has_history  -- Penanda: item ini pernah ada transaksi FISIK sebelumnya
                FROM stock_ledger sl
                JOIN (
                    SELECT item_id, item_type, MAX(id) as max_id
                    FROM stock_ledger
                    WHERE transaction_date < ?
                      AND item_type IN ('material', 'semifinished')
                      -- Hanya hitung transaksi fisik, bukan WIP internal
                      AND transaction_type NOT IN ('wip_in', 'wip_out', 'wip_out_reversal', 'production_out')
                    GROUP BY item_id, item_type
                ) sa_max ON sl.id = sa_max.max_id
            ),
            mutasi AS (
                SELECT
                    item_id, 
                    item_type,
                    -- Pemasukan: GRN dari supplier
                    SUM(CASE WHEN transaction_type IN ('in') THEN qty_in ELSE 0 END) as pemasukan,
                    -- Retur: Material dikembalikan dari produksi
                    SUM(CASE WHEN transaction_type IN ('IN_RETURN_WIP') THEN qty_in ELSE 0 END) as retur,
                    -- Pengeluaran: Hanya OUT_ISSUE (issuance ke produksi), bukan production_out (pemakaian aktual WIP)
                    SUM(CASE WHEN transaction_type IN ('OUT_ISSUE') THEN qty_out ELSE 0 END) as pengeluaran,
                    -- Penyesuaian: Adjustment stok
                    SUM(CASE WHEN transaction_type = 'adjustment' THEN (qty_in - qty_out) ELSE 0 END) as penyesuaian
                FROM stock_ledger
                WHERE transaction_date >= ?
                  AND transaction_date <= ?
                  AND item_type IN ('material', 'semifinished')
                  -- Exclude WIP tracking transactions (hanya untuk internal WIP tracking)
                  AND transaction_type NOT IN ('wip_in', 'wip_out', 'wip_out_reversal', 'production_out')
                GROUP BY item_id, item_type
            ),
            opname AS (
                SELECT 
                    od.item_id, 
                    od.item_type,
                    od.qty_fisik
                FROM opname_details od
                JOIN opname_header oh ON od.opname_header_id = oh.id
                JOIN (
                    SELECT 
                        od_inner.item_id, 
                        od_inner.item_type,
                        MAX(oh_inner.id) as max_header_id
                    FROM opname_details od_inner
                    JOIN opname_header oh_inner ON od_inner.opname_header_id = oh_inner.id
                    WHERE oh_inner.item_type IN ('material', 'semifinished')
                      AND oh_inner.status = 'Completed'
                      AND oh_inner.opname_date >= ?
                      AND oh_inner.opname_date <= ?
                    GROUP BY od_inner.item_id, od_inner.item_type
                ) op_max ON od.item_id = op_max.item_id AND od.item_type = op_max.item_type AND od.opname_header_id = op_max.max_header_id
            )
            
            SELECT 
                i.item_type, 
                i.id, 
                i.kode_barang, 
                i.nama_barang, 
                i.satuan,
                COALESCE(sa.balance, 0) AS saldo_awal,
                COALESCE(m.pemasukan, 0) AS pemasukan,
                COALESCE(m.retur, 0) AS retur,
                COALESCE(m.pengeluaran, 0) AS pengeluaran,
                COALESCE(m.penyesuaian, 0) AS penyesuaian,
                (COALESCE(sa.balance, 0) + COALESCE(m.pemasukan, 0) + COALESCE(m.retur, 0) - COALESCE(m.pengeluaran, 0) + COALESCE(m.penyesuaian, 0)) AS saldo_akhir,
                op.qty_fisik AS hasil_pencacahan,
                CASE
                    WHEN op.qty_fisik IS NOT NULL THEN
                        ( (COALESCE(sa.balance, 0) + COALESCE(m.pemasukan, 0) + COALESCE(m.retur, 0) - COALESCE(m.pengeluaran, 0) + COALESCE(m.penyesuaian, 0)) - op.qty_fisik )
                    ELSE
                        NULL
                END AS jumlah_selisih,
                COALESCE(sa.has_history, 0) AS has_history  -- Indikator pernah ada transaksi
                
            FROM common_items i
            LEFT JOIN saldo_awal sa ON i.id = sa.item_id AND i.item_type = sa.item_type
            LEFT JOIN mutasi m ON i.id = m.item_id AND i.item_type = m.item_type
            LEFT JOIN opname op ON i.id = op.item_id AND i.item_type = op.item_type

            WHERE 1=1
            $sql_search

            HAVING 
                -- Item muncul jika pernah ada transaksi sebelumnya (walaupun saldo = 0)
                has_history = 1 OR
                -- Atau jika ada mutasi di periode ini
                pemasukan != 0 OR
                retur != 0 OR
                pengeluaran != 0 OR
                penyesuaian != 0 OR
                hasil_pencacahan IS NOT NULL
                
            ORDER BY i.kode_barang ASC
        ";
        
        return $this->db->query($sql, [
            $date_awal_minus_1_dt,
            $date_start_dt,
            $date_end_dt,
            $date_start_dt,
            $date_end_dt
        ])->result_array(); 
    }

    /**
     * =========================================================
     * INI FUNGSI YANG ANDA BUTUHKAN: get_item_detail
     * =========================================================
     */
    public function get_item_detail($item_id, $item_type) 
    {
        $this->db->select("
            sl.transaction_date, sl.transaction_type, sl.qty_in, sl.qty_out, sl.balance,
            gh.grn_number, gh.bc_type_code, gh.bc_number, gh.bc_date, ph.po_number,
            mrh.mr_number, mrh.issuance_number,
            prh.prod_return_no,
            ah.doc_no as adjustment_doc, ah.notes as adjustment_notes, ah.doc_date as adjustment_date, ah.id as adjustment_header_id
        ");
        
        $this->db->from('stock_ledger sl');
        
        $this->db->join('grn_header gh', 'sl.transaction_id = gh.id AND sl.transaction_type = \'in\'', 'left');
        $this->db->join('po_header ph', 'sl.source_doc_id = ph.id AND sl.source_doc_type = \'PO\'', 'left');
        
        // Join untuk OUT_ISSUE dan production_out
        $this->db->join('mr_header mrh', 'sl.transaction_id = mrh.id AND sl.transaction_type IN (\'production_out\', \'OUT_ISSUE\')', 'left');
        
        $this->db->join('prod_return_detail prd', 'sl.transaction_id = prd.id', 'left');
        $this->db->join('prod_return_header prh', 'prd.prod_return_id = prh.id AND sl.transaction_type = \'IN_RETURN_WIP\'', 'left');
        
        $this->db->join('adjustment_details ad', 'sl.transaction_id = ad.id', 'left');
        $this->db->join('adjustment_header ah', 'ad.adjustment_header_id = ah.id AND sl.transaction_type = \'adjustment\'', 'left');
        
        $this->db->where('sl.item_id', $item_id);
        $this->db->where('sl.item_type', $item_type);
        
        // Exclude transaksi WIP internal yang tidak mempengaruhi stok fisik gudang
        $this->db->where_not_in('sl.transaction_type', ['wip_in', 'wip_out', 'wip_out_reversal', 'production_out']);
        
        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');
        
        return $this->db->get()->result();
    }

    public function trace_recursive($item_id, $item_type, $limit = 25) 
    {
        $sql = "
        (
            SELECT 
                'input' as direction,
                ph.production_date as transaction_date,
                ph.production_number, 
                wo.wo_number,
                pi.stage_name,
                
                pi.item_type as related_item_type,
                pi.item_id as related_item_id,
                COALESCE(m.nama_material, sf.nama_barang) as related_item_name,
                pi.qty_used as related_qty,
                COALESCE(m.satuan, sf.satuan) as related_satuan,
                
                po.qty_produced as original_qty,
                COALESCE(po_sf.satuan, po_fg.satuan, po_scr.unit, po_m.satuan) as original_satuan
                
            FROM production_outputs po
            JOIN production_header ph ON po.production_header_id = ph.id
            LEFT JOIN wo_header wo ON ph.wo_header_id = wo.id
            JOIN production_inputs pi ON pi.production_header_id = ph.id
            LEFT JOIN materials m ON pi.item_id = m.id AND pi.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON pi.item_id = sf.id AND pi.item_type = 'semifinished'
            LEFT JOIN materials po_m ON po.item_id = po_m.id AND po.item_type = 'material'
            LEFT JOIN semifinished_goods po_sf ON po.item_id = po_sf.id AND po.item_type = 'semifinished'
            LEFT JOIN finished_goods po_fg ON po.item_id = po_fg.id AND po.item_type = 'finished_goods'
            LEFT JOIN master_scrap po_scr ON po.item_id = po_scr.id AND po.item_type = 'scrap'
            
            WHERE po.item_id = ?
              AND po.item_type = ?
        )
        UNION ALL
        (
            SELECT 
                'output' as direction,
                ph.production_date as transaction_date,
                ph.production_number, 
                wo.wo_number,
                pi.stage_name,
                
                po.item_type as related_item_type,
                po.item_id as related_item_id,
                COALESCE(sf.nama_barang, fg.nama_barang, scr.scrap_name, 'Unknown') as related_item_name,
                
                CASE 
                    WHEN po.item_type = 'scrap' THEN po.qty_produced
                    ELSE po.qty_produced 
                END as related_qty,

                COALESCE(sf.satuan, fg.satuan, scr.unit) as related_satuan, 
                
                pi.qty_used as original_qty,
                COALESCE(pi_m.satuan, pi_sf.satuan) as original_satuan
                
            FROM production_inputs pi
            JOIN production_header ph ON pi.production_header_id = ph.id
            LEFT JOIN wo_header wo ON ph.wo_header_id = wo.id
            JOIN production_outputs po ON po.production_header_id = ph.id
            LEFT JOIN semifinished_goods sf ON po.item_id = sf.id AND po.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON po.item_id = fg.id AND po.item_type = 'finished_goods'
            LEFT JOIN master_scrap scr ON po.item_id = scr.id AND po.item_type = 'scrap'
            LEFT JOIN materials pi_m ON pi.item_id = pi_m.id AND pi.item_type = 'material'
            LEFT JOIN semifinished_goods pi_sf ON pi.item_id = pi_sf.id AND pi.item_type = 'semifinished'
            
            WHERE pi.item_id = ?
              AND pi.item_type = ?
        )
        ORDER BY transaction_date ASC, production_number ASC
        LIMIT ?
        ";

        return $this->db->query($sql, [
            $item_id, $item_type,
            $item_id, $item_type,
            (int)$limit
        ])->result();
    }
    
    public function get_item_basic($item_type, $item_id) {
        if ($item_type == 'material') {
            return $this->db->select('id, kode_material as code, nama_material as name, satuan')
                            ->where('id', $item_id)->get('materials')->row();
        } else {
            return $this->db->select('id, kode_barang as code, nama_barang as name, satuan')
                            ->where('id', $item_id)->get('semifinished_goods')->row();
        }
    }
}