<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LapMutasiWIP_model extends CI_Model {

    public function get_mutasi_wip($start_date, $end_date, $item_type_filter, $search_term) 
    {
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
                SELECT 
                    sl.item_id, 
                    sl.item_type, 
                    -- Saldo WIP = Total Masuk - Total Keluar
                    -- NOTE: Bug fix - wip_in bisa simpan qty di qty_in ATAU qty_out, ambil yang > 0
                    (SUM(CASE 
                        WHEN sl.transaction_type IN ('wip_in', 'IN_RETURN_WIP') THEN GREATEST(COALESCE(sl.qty_in, 0), COALESCE(sl.qty_out, 0))
                        ELSE 0 
                     END) - 
                     SUM(CASE WHEN sl.transaction_type = 'wip_out' THEN sl.qty_out ELSE 0 END)) as balance,
                    1 as has_history
                FROM stock_ledger sl
                WHERE sl.transaction_date < ?
                  AND sl.transaction_type IN ('wip_in', 'wip_out', 'IN_RETURN_WIP')
                GROUP BY sl.item_id, sl.item_type
            ),
            mutasi AS (
                SELECT
                    sl.item_id, 
                    sl.item_type,
                    -- MASUK WIP: wip_in + IN_RETURN_WIP
                    -- NOTE: Bug fix - wip_in bisa simpan qty di qty_in ATAU qty_out, ambil yang > 0
                    SUM(CASE 
                        WHEN sl.transaction_type IN ('wip_in', 'IN_RETURN_WIP') THEN GREATEST(COALESCE(sl.qty_in, 0), COALESCE(sl.qty_out, 0))
                        ELSE 0 
                    END) as total_masuk,
                    -- KELUAR WIP: wip_out
                    SUM(CASE WHEN sl.transaction_type = 'wip_out' THEN sl.qty_out ELSE 0 END) as total_keluar
                FROM stock_ledger sl
                WHERE sl.transaction_date >= ?
                  AND sl.transaction_date <= ?
                  AND sl.transaction_type IN ('wip_in', 'wip_out', 'IN_RETURN_WIP')
                GROUP BY sl.item_id, sl.item_type
            )
            
            SELECT 
                i.item_type, 
                i.id, 
                i.kode_barang, 
                i.nama_barang, 
                i.satuan,
                COALESCE(sa.balance, 0) AS saldo_awal,
                COALESCE(m.total_masuk, 0) AS total_masuk,
                COALESCE(m.total_keluar, 0) AS total_keluar,
                (COALESCE(sa.balance, 0) + COALESCE(m.total_masuk, 0) - COALESCE(m.total_keluar, 0)) AS saldo_akhir
                
            FROM common_items i
            LEFT JOIN saldo_awal sa ON i.id = sa.item_id AND i.item_type = sa.item_type
            LEFT JOIN mutasi m ON i.id = m.item_id AND i.item_type = m.item_type

            WHERE 1=1
            $sql_search

            HAVING 
                saldo_awal != 0 OR
                total_masuk != 0 OR
                total_keluar != 0
                
            ORDER BY i.kode_barang ASC
        ";
        
        return $this->db->query($sql, [
            $date_start_dt,
            $date_start_dt,
            $date_end_dt
        ])->result_array(); 
    }

    public function get_item_detail_wip($item_id, $item_type) 
    {
        $this->db->select("
            sl.transaction_date, sl.transaction_type, sl.qty_in, sl.qty_out, 
            sl.balance,
            
            mrh.mr_number,
            
            ph_in.production_number as prod_num_in, 
            po_in.stage_name as stage_in,
            
            ph_out.production_number as prod_num_out, 
            pi_out.stage_name as stage_out,
            
            prh.prod_return_no
        ");
        
        $this->db->from('stock_ledger sl');
        
        // wip_in material: transaction_id = mr_header.id (bukan source_doc_id)
        $this->db->join('mr_header mrh', 'sl.transaction_id = mrh.id AND sl.transaction_type = \'wip_in\' AND sl.item_type = \'material\'', 'left');
        
        $this->db->join('production_outputs po_in', 'sl.transaction_id = po_in.id AND sl.transaction_type = \'wip_in\' AND sl.item_type = \'semifinished\'', 'left');
        $this->db->join('production_header ph_in', 'po_in.production_header_id = ph_in.id', 'left');
        
        $this->db->join('production_inputs pi_out', 'sl.transaction_id = pi_out.id AND sl.transaction_type = \'wip_out\'', 'left');
        $this->db->join('production_header ph_out', 'pi_out.production_header_id = ph_out.id', 'left');
        
        $this->db->join('prod_return_detail prd', 'sl.transaction_id = prd.id AND sl.transaction_type = \'IN_RETURN_WIP\'', 'left');
        $this->db->join('prod_return_header prh', 'prd.prod_return_id = prh.id', 'left');
        
        $this->db->where('sl.item_id', $item_id);
        $this->db->where('sl.item_type', $item_type);
        
        $this->db->where_in('sl.transaction_type', ['wip_in', 'wip_out', 'IN_RETURN_WIP']);
        
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
                    WHEN po.item_type = 'scrap' THEN po.qty_scrap
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
            return $this->db->select('kode_material as code, nama_material as name, satuan')
                            ->where('id', $item_id)->get('materials')->row();
        } else {
            return $this->db->select('kode_barang as code, nama_barang as name, satuan')
                            ->where('id', $item_id)->get('semifinished_goods')->row();
        }
    }
}