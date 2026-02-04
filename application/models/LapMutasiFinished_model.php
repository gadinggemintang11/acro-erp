<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LapMutasiFinished_model extends CI_Model {

    public function get_all_finished_goods()
    {
        $this->db->where('status', 'active');
        $this->db->order_by('nama_barang', 'ASC');
        return $this->db->get('finished_goods')->result();
    }

    public function get_stock_summary($start_date, $end_date)
    {
        $date_awal_minus_1_dt = $start_date . ' 00:00:00';
        $date_start_dt = $start_date . ' 00:00:00';
        $date_end_dt = $end_date . ' 23:59:59';

        $sql = "
            SELECT 
                fg.id, 
                fg.kode_barang, 
                fg.nama_barang, 
                fg.satuan,
                COALESCE(sa.balance, 0) AS saldo_awal,
                COALESCE(mutasi.total_masuk, 0) AS pemasukan,
                COALESCE(mutasi.total_keluar, 0) AS pengeluaran,
                COALESCE(mutasi.total_penyesuaian, 0) AS penyesuaian,
                (COALESCE(sa.balance, 0) + COALESCE(mutasi.total_masuk, 0) - COALESCE(mutasi.total_keluar, 0) + COALESCE(mutasi.total_penyesuaian, 0)) AS saldo_akhir,
                op.qty_fisik AS hasil_pencacahan,
                CASE
                    WHEN op.qty_fisik IS NOT NULL THEN
                        ( (COALESCE(sa.balance, 0) + COALESCE(mutasi.total_masuk, 0) - COALESCE(mutasi.total_keluar, 0) + COALESCE(mutasi.total_penyesuaian, 0)) - op.qty_fisik )
                    ELSE
                        NULL
                END AS jumlah_selisih,
                COALESCE(sa.has_history, 0) AS has_history
                
            FROM finished_goods fg

            LEFT JOIN (
                SELECT sl.item_id, sl.balance, 1 as has_history
                FROM stock_ledger sl
                JOIN (
                    SELECT item_id, MAX(id) as max_id
                    FROM stock_ledger
                    WHERE transaction_date < ?
                      AND item_type = 'finished_goods'
                      -- Hanya hitung transaksi fisik FG (fg_in, fg_out, adjustment)
                      AND transaction_type IN ('fg_in', 'fg_out', 'adjustment')
                    GROUP BY item_id
                ) sa_max ON sl.id = sa_max.max_id
            ) sa ON fg.id = sa.item_id

            LEFT JOIN (
                SELECT
                    item_id,
                    SUM(CASE WHEN transaction_type = 'fg_in' THEN qty_in ELSE 0 END) as total_masuk,
                    SUM(CASE WHEN transaction_type = 'fg_out' THEN qty_out ELSE 0 END) as total_keluar,
                    SUM(CASE WHEN transaction_type = 'adjustment' THEN (qty_in - qty_out) ELSE 0 END) as total_penyesuaian
                FROM stock_ledger
                WHERE transaction_date >= ?
                  AND transaction_date <= ?
                  AND item_type = 'finished_goods'
                GROUP BY item_id
            ) mutasi ON fg.id = mutasi.item_id
            
            LEFT JOIN (
                SELECT 
                    od.item_id, 
                    od.qty_fisik
                FROM opname_details od
                JOIN opname_header oh ON od.opname_header_id = oh.id
                JOIN (
                    SELECT 
                        od_inner.item_id, 
                        MAX(oh_inner.id) as max_header_id
                    FROM opname_details od_inner
                    JOIN opname_header oh_inner ON od_inner.opname_header_id = oh_inner.id
                    WHERE oh_inner.item_type = 'finished_goods'
                      AND oh_inner.status = 'Completed'
                      AND oh_inner.opname_date >= ?
                      AND oh_inner.opname_date <= ?
                    GROUP BY od_inner.item_id
                ) op_max ON od.item_id = op_max.item_id AND od.opname_header_id = op_max.max_header_id
            ) op ON fg.id = op.item_id

            WHERE fg.status = 'active'
            
            HAVING 
                -- Item muncul jika pernah ada transaksi fisik sebelumnya
                has_history = 1 OR
                -- Atau jika ada mutasi di periode ini
                pemasukan != 0 OR
                pengeluaran != 0 OR
                penyesuaian != 0 OR
                hasil_pencacahan IS NOT NULL
                
            ORDER BY fg.kode_barang ASC
        ";

        return $this->db->query($sql, [
            $date_awal_minus_1_dt,
            $date_start_dt,
            $date_end_dt,
            $date_start_dt,
            $date_end_dt
        ])->result();
    }

    public function get_item_info($fg_id)
    {
        return $this->db->get_where('finished_goods', ['id' => $fg_id])->row();
    }
    
    public function get_saldo_awal($item_id, $start_date)
    {
        $this->db->select('balance');
        $this->db->from('stock_ledger');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', 'finished_goods');
        $this->db->where('transaction_date <', $start_date . ' 00:00:00');
        $this->db->order_by('transaction_date', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        
        $result = $this->db->get()->row();
        return $result ? (float) $result->balance : 0;
    }

    public function get_item_mutation_log($item_id, $start_date, $end_date)
    {
        $this->db->select([
            'sl.id as ledger_id',
            'sl.transaction_date',
            'sl.qty_in',
            'sl.qty_out',
            'sl.balance AS saldo_akhir',
            'sl.transaction_type',
            'sl.transaction_id',
            
            "CASE 
                WHEN sl.transaction_type = 'fg_in' THEN fgh.receipt_number
                WHEN sl.transaction_type = 'fg_out' THEN fdh.delivery_number
                WHEN sl.transaction_type = 'adjustment' THEN adj.doc_no
                ELSE sl.transaction_id 
            END AS doc_number",
            
            "CASE 
                WHEN sl.transaction_type = 'fg_in' THEN CONCAT('Penerimaan dari Produksi (WO: ', wo.wo_number, ')')
                WHEN sl.transaction_type = 'fg_out' THEN CONCAT('Pengiriman ke Customer: ', c.nama_customer)
                WHEN sl.transaction_type = 'adjustment' THEN adj.notes
                ELSE 'Transaksi Lainnya' 
            END AS description"
        ]);
        
        $this->db->from('stock_ledger AS sl');
        $this->db->join('fg_receipt_details AS fgd', "sl.transaction_id = fgd.id AND sl.transaction_type = 'fg_in'", 'left');
        $this->db->join('fg_receipt_header AS fgh', 'fgd.fg_receipt_header_id = fgh.id', 'left');
        $this->db->join('wo_header AS wo', 'fgh.wo_header_id = wo.id', 'left');
        $this->db->join('fg_delivery_details AS fdd', "sl.transaction_id = fdd.id AND sl.transaction_type = 'fg_out'", 'left');
        $this->db->join('fg_delivery_header AS fdh', 'fdd.fg_delivery_header_id = fdh.id', 'left');
        $this->db->join('customers AS c', 'fdh.customer_id = c.id', 'left');
        $this->db->join('adjustment_header AS adj', "sl.transaction_id = adj.id AND sl.transaction_type = 'adjustment'", 'left');
        
        $this->db->where('sl.item_id', $item_id);
        $this->db->where('sl.item_type', 'finished_goods');
        $this->db->where('sl.transaction_date >=', $start_date . ' 00:00:00');
        $this->db->where('sl.transaction_date <=', $end_date . ' 23:59:59');
        
        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');
        
        return $this->db->get()->result();
    }
     
    public function get_detail_penerimaan($fg_receipt_detail_id)
    {
        $this->db->select('
            fgh.receipt_number, fgh.receipt_date, fgh.confirmed_at, fgh.wo_header_id, fgh.production_header_id,
            fgd.qty_received, fgd.size_breakdown, fgd.notes, fgd.id as fg_receipt_detail_id,
            wo.wo_number, 
            sc.sc_number,
            ph.production_number,
            u.fullname as confirmed_by_user
        ');
        $this->db->from('fg_receipt_details AS fgd');
        $this->db->join('fg_receipt_header AS fgh', 'fgd.fg_receipt_header_id = fgh.id');
        $this->db->join('wo_header AS wo', 'fgh.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts AS sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('production_header AS ph', 'fgh.production_header_id = ph.id', 'left');
        $this->db->join('users AS u', 'fgh.confirmed_by = u.id', 'left');
        $this->db->where('fgd.id', $fg_receipt_detail_id);
        return $this->db->get()->row();
    }

    public function get_materials_for_production($wo_id, $prod_header_id)
    {
        $this->db->select('
            m.kode_material, m.nama_material, m.satuan,
            mrd.issued_qty,
            mrd.component_item_id as item_id
        ');
        $this->db->from('mr_details AS mrd');
        $this->db->join('materials AS m', "mrd.component_item_id = m.id AND mrd.component_item_type = 'material'");
        $this->db->where('mrd.wo_header_id', $wo_id);
        $issued_materials = $this->db->get()->result_array();

        $this->db->select('item_id, SUM(qty_used) as total_used');
        $this->db->from('production_inputs');
        $this->db->where('production_header_id', $prod_header_id);
        $this->db->where('item_type', 'material');
        $this->db->group_by('item_id');
        $used_query = $this->db->get();
        
        $used_materials = [];
        foreach($used_query->result() as $row) {
            $used_materials[$row->item_id] = $row->total_used;
        }

        $result = [];
        foreach($issued_materials as $item) {
            $item_id = $item['item_id'];
            $item['qty_terpakai'] = isset($used_materials[$item_id]) ? $used_materials[$item_id] : 0;
            
            // Enhanced: Add BC information and GRN details from the last incoming transaction
            $bc_info = $this->get_material_bc_info($item_id, $prod_header_id);
            $item = array_merge($item, $bc_info);
            
            $result[] = $item;
        }
        
        return $result;
    }

    /**
     * Get BC information and GRN details for a specific material based on production date
     * This provides deep traceability showing BC number, type, date and arrival information
     */
    public function get_material_bc_info($material_id, $production_header_id)
    {
        try {
            // Get production date to find the relevant material receipt
            $this->db->select('production_date');
            $this->db->from('production_header');
            $this->db->where('id', $production_header_id);
            $prod_header = $this->db->get()->row();
            
            if (!$prod_header) {
                return $this->get_default_bc_info();
            }

            $prod_date = $prod_header->production_date;

            // Find the most recent GRN for this material before production date
            $this->db->select('
                gh.grn_number,
                gh.grn_date,
                gh.bc_type_code,
                gh.bc_number, 
                gh.bc_date,
                ph.po_number,
                ph.po_date,
                mh.mir_number,
                COALESCE(s_po.nama_supplier, s_mir.nama_supplier) as nama_supplier,
                sl.transaction_date as arrival_date
            ');
            $this->db->from('stock_ledger sl');
            $this->db->join('grn_header gh', 'sl.transaction_id = gh.id AND sl.transaction_type = "in"', 'left');
            $this->db->join('grn_details gd', 'gd.grn_header_id = gh.id AND gd.item_id = sl.item_id', 'left');
            $this->db->join('po_header ph', 'gd.source_doc_header_id = ph.id AND gd.source_doc_type = "PO"', 'left');
            $this->db->join('mir_header mh', 'gd.source_doc_header_id = mh.id AND gd.source_doc_type = "MIR"', 'left');
            $this->db->join('suppliers s_po', 's_po.id = ph.supplier_id', 'left');
            $this->db->join('suppliers s_mir', 's_mir.id = mh.supplier_id', 'left');
            $this->db->where('sl.item_id', $material_id);
            $this->db->where('sl.item_type', 'material');
            $this->db->where('sl.transaction_type', 'in');
            $this->db->where('sl.qty_in >', 0);
            $this->db->where('sl.transaction_date <=', $prod_date . ' 23:59:59');
            $this->db->order_by('sl.transaction_date', 'DESC');
            $this->db->order_by('sl.id', 'DESC');
            $this->db->limit(1);
            
            $bc_data = $this->db->get()->row();

            if ($bc_data) {
                return [
                    'grn_number' => $bc_data->grn_number ?? '-',
                    'grn_date' => $bc_data->grn_date ?? null,
                    'bc_type_code' => $bc_data->bc_type_code ?? '-',
                    'bc_number' => $bc_data->bc_number ?? '-',
                    'bc_date' => $bc_data->bc_date ?? null,
                    'po_number' => $bc_data->po_number ?? '-',
                    'po_date' => $bc_data->po_date ?? null,
                    'nama_supplier' => $bc_data->nama_supplier ?? '-',
                    'arrival_date' => $bc_data->arrival_date ?? null,
                    'qty_used_from_this_batch' => $this->calculate_batch_usage($material_id, $production_header_id, $bc_data->arrival_date ?? null)
                ];
            }

            return $this->get_default_bc_info();
        } catch (Exception $e) {
            // Log error but don't fail, return default info
            log_message('error', 'Error in get_material_bc_info: ' . $e->getMessage());
            return $this->get_default_bc_info();
        }
    }

    /**
     * Calculate how much of this material was used from a specific batch/arrival
     */
    private function calculate_batch_usage($material_id, $production_header_id, $arrival_date)
    {
        try {
            if (!$arrival_date) {
                // If no specific arrival date, return total used
                $this->db->select('SUM(qty_used) as total');
                $this->db->from('production_inputs');
                $this->db->where('production_header_id', $production_header_id);
                $this->db->where('item_id', $material_id);
                $this->db->where('item_type', 'material');
                $result = $this->db->get()->row();
                return $result ? (float)$result->total : 0;
            }

            // For more accurate tracking, we could implement FIFO logic here
            // For now, return the total usage as batch tracking might need additional tables
            $this->db->select('SUM(qty_used) as total');
            $this->db->from('production_inputs');
            $this->db->where('production_header_id', $production_header_id);
            $this->db->where('item_id', $material_id);
            $this->db->where('item_type', 'material');
            $result = $this->db->get()->row();
            return $result ? (float)$result->total : 0;
        } catch (Exception $e) {
            log_message('error', 'Error in calculate_batch_usage: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Default BC info structure when no data found
     */
    private function get_default_bc_info()
    {
        return [
            'grn_number' => '-',
            'grn_date' => null,
            'bc_type_code' => '-',
            'bc_number' => '-', 
            'bc_date' => null,
            'po_number' => '-',
            'po_date' => null,
            'nama_supplier' => '-',
            'arrival_date' => null,
            'qty_used_from_this_batch' => 0
        ];
    }

    /**
     * Enhanced get_materials_for_production that returns object format for modal_traceability.php
     * This provides full BC traceability information for deep traceability reports
     */
    public function get_materials_with_full_bc_trace($wo_id, $prod_header_id)
    {
        $materials_array = $this->get_materials_for_production($wo_id, $prod_header_id);
        
        // Convert array format to object format for compatibility with existing modal
        $materials_objects = [];
        foreach ($materials_array as $material) {
            $obj = new stdClass();
            $obj->kode_material = $material['kode_material'];
            $obj->nama_material = $material['nama_material'];
            $obj->issued_qty = $material['issued_qty'];
            $obj->qty_used_from_this_batch = $material['qty_used_from_this_batch'] ?? $material['qty_terpakai'];
            $obj->satuan = $material['satuan'];
            $obj->grn_number = $material['grn_number'];
            $obj->grn_date = $material['grn_date'];
            $obj->po_number = $material['po_number'];
            $obj->po_date = $material['po_date'];
            $obj->nama_supplier = $material['nama_supplier'];
            $obj->bc_type_code = $material['bc_type_code'];
            $obj->bc_number = $material['bc_number'];
            $obj->bc_date = $material['bc_date'];
            $obj->arrival_date = $material['arrival_date'];
            
            $materials_objects[] = $obj;
        }
        
        return $materials_objects;
    }

    public function get_detail_pengiriman($fg_delivery_detail_id)
    {
        $this->db->select("
            fdh.delivery_number, fdh.delivery_date, fdh.shipped_at,
            fdh.bc_type_id, fdh.bc_number, fdh.bc_date,
            cc.description as bc_type_description,
            fdd.qty_shipped, fdd.notes, fdd.id as fg_delivery_detail_id,
            c.nama_customer, c.alamat as customer_address,
            sc.sc_number,
            u.fullname as shipped_by_user
        ");
        $this->db->from('fg_delivery_details AS fdd');
        $this->db->join('fg_delivery_header AS fdh', 'fdd.fg_delivery_header_id = fdh.id');
        $this->db->join('customers AS c', 'fdh.customer_id = c.id', 'left');
        $this->db->join('sales_contracts AS sc', 'fdd.sales_contract_id = sc.id', 'left');
        $this->db->join('users AS u', 'fdh.shipped_by_user = u.id', 'left');
        $this->db->join('common_code AS cc', "cc.id = fdh.bc_type_id AND cc.group_code = 'BC_TYPE'", 'left');
        $this->db->where('fdd.id', $fg_delivery_detail_id);
        return $this->db->get()->row();
    }
    
    public function get_detail_adjustment($adjustment_header_id)
    {
        $this->db->select('
            adj.id, adj.doc_no, adj.doc_date, adj.notes,
            u.fullname as created_by_user
        ');
        $this->db->from('adjustment_header AS adj');
        $this->db->join('users AS u', 'adj.created_by = u.id', 'left');
        $this->db->where('adj.id', $adjustment_header_id);
        return $this->db->get()->row();
    }
}