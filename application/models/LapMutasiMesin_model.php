<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LapMutasiMesin_model extends CI_Model {


    public function get_mutasi_mesin($start_date, $end_date, $search_term) 
    {
        $date_awal_minus_1_dt = $start_date . ' 00:00:00';
        $date_start_dt = $start_date . ' 00:00:00';
        $date_end_dt = $end_date . ' 23:59:59';
        
        $sql_search = "";
        if (!empty($search_term)) {
            $safe_search = $this->db->escape_like_str($search_term);
            $sql_search = " AND (m.kode_mesin LIKE '%{$safe_search}%' OR m.nama_mesin LIKE '%{$safe_search}%') ";
        }

        $sql = "
            WITH saldo_awal AS (
                SELECT sl.item_id, sl.balance
                FROM stock_ledger sl
                JOIN (
                    SELECT item_id, MAX(id) as max_id
                    FROM stock_ledger
                    WHERE transaction_date < ?
                    AND item_type = 'machine'
                    GROUP BY item_id
                ) sa_max ON sl.id = sa_max.max_id
            ),
            mutasi AS (
                SELECT
                    item_id,
                    SUM(CASE WHEN transaction_type IN ('in', 'wip_in', 'IN_RETURN_WIP') THEN qty_in ELSE 0 END) as total_masuk,
                    SUM(CASE WHEN transaction_type IN ('out', 'production_out', 'wip_out', 'RTN_SUPPLIER') THEN qty_out ELSE 0 END) as total_keluar,
                    SUM(CASE WHEN transaction_type = 'adjustment' THEN (qty_in - qty_out) ELSE 0 END) as total_penyesuaian
                FROM stock_ledger
                WHERE transaction_date >= ?
                AND transaction_date <= ?
                AND item_type = 'machine'
                AND transaction_type IN ('in', 'wip_in', 'IN_RETURN_WIP', 'out', 'adjustment', 'production_out', 'wip_out', 'RTN_SUPPLIER')
                GROUP BY item_id
            ),
            opname AS (
                SELECT 
                    od.item_id,
                    od.item_type,
                    od.qty_fisik
                FROM opname_details od
                JOIN opname_header oh ON od.opname_header_id = oh.id
                WHERE oh.status = 'Completed'
                    AND oh.opname_date >= '$start_date' 
                    AND oh.opname_date <= '$end_date'
                    AND od.item_type = 'machine'
                    AND oh.opname_date = (
                        SELECT MAX(oh_inner.opname_date)
                        FROM opname_details od_inner
                        JOIN opname_header oh_inner ON od_inner.opname_header_id = oh_inner.id
                        WHERE od_inner.item_id = od.item_id 
                          AND od_inner.item_type = od.item_type
                          AND oh_inner.status = 'Completed'
                          AND oh_inner.opname_date >= '$start_date'
                          AND oh_inner.opname_date <= '$end_date'
                    )
            )
            
            SELECT 
                m.id, 
                m.kode_mesin, 
                m.nama_mesin, 
                m.satuan,
                COALESCE(sa.balance, 0) AS saldo_awal,
                COALESCE(mut.total_masuk, 0) AS total_masuk,
                COALESCE(mut.total_keluar, 0) AS total_keluar,
                COALESCE(mut.total_penyesuaian, 0) AS total_penyesuaian,
                (COALESCE(sa.balance, 0) + COALESCE(mut.total_masuk, 0) - COALESCE(mut.total_keluar, 0) + COALESCE(mut.total_penyesuaian, 0)) AS saldo_akhir,
                op.qty_fisik AS hasil_pencacahan,
                CASE 
                    WHEN op.qty_fisik IS NOT NULL THEN 
                        (COALESCE(sa.balance, 0) + COALESCE(mut.total_masuk, 0) - COALESCE(mut.total_keluar, 0) + COALESCE(mut.total_penyesuaian, 0)) - op.qty_fisik
                    ELSE NULL
                END AS jumlah_selisih
                
            FROM machines m
            LEFT JOIN saldo_awal sa ON m.id = sa.item_id
            LEFT JOIN mutasi mut ON m.id = mut.item_id
            LEFT JOIN opname op ON m.id = op.item_id AND op.item_type = 'machine'

            WHERE m.status = 'Active' 
            $sql_search

            ORDER BY m.kode_mesin ASC
        ";
        
        $query = $this->db->query($sql, [
            $date_awal_minus_1_dt,
            $date_start_dt,
            $date_end_dt
        ]);

        $result = $query->result_array();

        // Jika perhitungan mutasi menghasilkan saldo_akhir = 0,
        // tetapi stock_ledger menyimpan balance terakhir > 0, gunakan nilai ledger terakhir.
        foreach ($result as &$row) {
            $computed = (float)($row['saldo_akhir'] ?? 0);
            if ($computed === 0.0) {
                $this->db->select('balance')->from('stock_ledger')
                         ->where('item_id', $row['id'])
                         ->where('item_type', 'machine')
                         ->order_by('id', 'DESC')
                         ->limit(1);
                $bal_q = $this->db->get();
                if ($bal_q && $bal_q->num_rows() > 0) {
                    $bal = (float)$bal_q->row()->balance;
                    if ($bal !== 0.0) {
                        log_message('debug', 'LapMutasiMesin: fallback ledger balance used for item_id ' . $row['id'] . ' => ' . $bal);
                        $row['saldo_akhir'] = $bal;
                    }
                }
            }
        }
        unset($row);

        log_message('debug', 'LapMutasiMesin Query: ' . $sql);
        log_message('debug', 'Params: ' . json_encode([$date_awal_minus_1_dt, $date_start_dt, $date_end_dt]));
        log_message('debug', 'Result count: ' . count($result));

        return $result;
    }

    public function get_item_basic_mesin($item_id) {
        // Tambahkan 'satuan' di sini juga
        return $this->db->select('id, kode_mesin, nama_mesin, kategori, satuan')
                        ->where('id', $item_id)
                        ->get('machines')
                        ->row();
    }

    public function get_item_detail_mesin($item_id) 
    {
        $this->db->select("sl.transaction_date, sl.transaction_type, sl.qty_in, sl.qty_out, sl.balance,
            sl.bc_type_code,
            sl.bc_number,
            sl.bc_date,
            grn.grn_number,
            adj.doc_no as adjustment_doc, adj.notes as adjustment_notes, adj.id as adjustment_header_id,
            woh.wo_number,
            wh.out_number as rtn_number");
        
        $this->db->from('stock_ledger sl');
        
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id AND sl.transaction_type = \'in\'', 'left');
        
        $this->db->join('adjustment_details ad', 'sl.transaction_id = ad.id AND sl.transaction_type = \'adjustment\'', 'left');
        $this->db->join('adjustment_header adj', 'ad.adjustment_header_id = adj.id', 'left');
        
        $this->db->join('wo_header woh', '(sl.source_doc_id = woh.id AND sl.transaction_type IN (\'production_out\', \'wip_out\'))', 'left');
        // RTN_SUPPLIER: link to warehouse_out_header to obtain return number
        $this->db->join('warehouse_out_header wh', "sl.transaction_id = wh.id AND sl.transaction_type = 'RTN_SUPPLIER'", 'left');
        
        $this->db->where('sl.item_id', $item_id);
        $this->db->where('sl.item_type', 'machine');
        $this->db->where_in('sl.transaction_type', ['in', 'wip_in', 'IN_RETURN_WIP', 'out', 'adjustment', 'production_out', 'wip_out', 'RTN_SUPPLIER']);
        
        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');
        
        return $this->db->get()->result();
    }
}