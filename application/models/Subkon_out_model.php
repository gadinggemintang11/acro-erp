<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subkon_out_model extends CI_Model {

    private $table_header = 'subkon_out_header';
    private $table_details = 'subkon_out_details';
    private $table_order_header = 'subkon_order_header';
    private $table_order_bom = 'subkon_order_bom';

    // ========================================================================
    // HEADER
    // ========================================================================
    
    public function generate_do_no() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('subkon_out', 'subkon_out_header', 'subkon_out_no');
    }

    public function insert_header($data) {
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    public function get_all($filters, $limit, $offset) {
        $this->db->select('soh.*, s.nama_supplier, sko.subkon_order_no');
        $this->db->from($this->table_header . ' soh');
        $this->db->join('suppliers s', 'soh.supplier_id = s.id', 'left');
        $this->db->join($this->table_order_header . ' sko', 'soh.subkon_order_id = sko.id', 'left');
        
        if (!empty($filters['start_date'])) { $this->db->where('soh.shipping_date >=', $filters['start_date']); }
        if (!empty($filters['end_date'])) { $this->db->where('soh.shipping_date <=', $filters['end_date']); }
        if (!empty($filters['status'])) { $this->db->where('soh.status', $filters['status']); }
        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('soh.subkon_out_no', $term);
            $this->db->or_like('sko.subkon_order_no', $term);
            $this->db->or_like('s.nama_supplier', $term);
            $this->db->group_end();
        }
        $this->db->order_by('soh.shipping_date', 'DESC');
        if ($limit > 0) { $this->db->limit($limit, $offset); }
        return $this->db->get()->result();
    }
    
    public function count_all($filters) {
        $this->db->from($this->table_header . ' soh');
        $this->db->join('suppliers s', 'soh.supplier_id = s.id', 'left');
        $this->db->join($this->table_order_header . ' sko', 'soh.subkon_order_id = sko.id', 'left');
        if (!empty($filters['start_date'])) { $this->db->where('soh.shipping_date >=', $filters['start_date']); }
        if (!empty($filters['end_date'])) { $this->db->where('soh.shipping_date <=', $filters['end_date']); }
        if (!empty($filters['status'])) { $this->db->where('soh.status', $filters['status']); }
        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('soh.subkon_out_no', $term);
            $this->db->or_like('sko.subkon_order_no', $term);
            $this->db->or_like('s.nama_supplier', $term);
            $this->db->group_end();
        }
        return $this->db->count_all_results();
    }
    
    public function get_header_by_id($id) {
        $this->db->select('soh.*, s.nama_supplier, sko.subkon_order_no');
        $this->db->from($this->table_header . ' soh');
        $this->db->join('suppliers s', 'soh.supplier_id = s.id', 'left');
        $this->db->join($this->table_order_header . ' sko', 'soh.subkon_order_id = sko.id', 'left');
        $this->db->where('soh.id', $id);
        return $this->db->get()->row();
    }

    public function update_status($id, $status, $user_id = null) {
        $data = ['status' => $status];
        if ($status == 'Confirmed' && $user_id) {
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif ($status == 'Draft') {
            $data['confirmed_at'] = NULL;
        }
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }
    
    // ========================================================================
    // DETAIL (Item DO)
    // ========================================================================

    public function get_details_by_id($do_id) {
        $this->db->select('sod.*, 
            CASE 
                WHEN sod.item_type = "material" THEN m.kode_material
                WHEN sod.item_type = "semifinished" THEN sf.kode_barang
            END as item_code,
            CASE 
                WHEN sod.item_type = "material" THEN m.nama_material
                WHEN sod.item_type = "semifinished" THEN sf.nama_barang
            END as item_name
        ', FALSE);
        $this->db->from($this->table_details . ' sod');
        $this->db->join('materials m', 'sod.item_id = m.id AND sod.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sod.item_id = sf.id AND sod.item_type = "semifinished"', 'left');
        $this->db->where('sod.subkon_out_id', $do_id);
        return $this->db->get()->result();
    }

    public function insert_item_to_do($data) {
        return $this->db->insert($this->table_details, $data);
    }

    public function delete_item_from_do($detail_id) {
        return $this->db->where('id', $detail_id)->delete($this->table_details);
    }

    // ========================================================================
    // LOGIKA KONFIRMASI (Pemotongan Stok)
    // ========================================================================

    public function confirm_do($do_id, $user_id) {
        $this->load->model('Stock_ledger_model');
        $header = $this->get_header_by_id($do_id);
        $details = $this->get_details_by_id($do_id);
        
        $this->db->trans_start();
        
        foreach ($details as $item) {
            $ledger_type = '';
            
            if ($item->item_type == 'material') {
                $ledger_type = 'production_out'; // Keluar dari Gudang Utama
            } else if ($item->item_type == 'semifinished') {
                $ledger_type = 'wip_out'; // Keluar dari Stok WIP
            }

            if (empty($ledger_type)) continue;

            $ledger_data = [
                'item_id'           => $item->item_id,
                'item_type'         => $item->item_type,
                'transaction_date'  => $header->shipping_date . ' ' . date('H:i:s'),
                'transaction_type'  => $ledger_type,
                'transaction_id'    => $item->id, 
                'source_doc_type'   => 'SUBKON_OUT',
                'source_doc_id'     => $do_id,
                'qty_in'            => 0,
                'qty_out'           => (float)$item->qty_kirim,
                'unit_value'        => (float)$item->unit_value,
                'stock_on_hand_id'  => $item->stock_on_hand_id 
            ];
            
            if (!$this->Stock_ledger_model->insert($ledger_data)) {
                $this->db->trans_rollback();
                return false;
            }
        }
        
        $this->update_status($do_id, 'Confirmed', $user_id);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function unconfirm_do($do_id) {
        $this->load->model('Stock_ledger_model');
        $details = $this->get_details_by_id($do_id);

        $this->db->trans_start();

        foreach ($details as $item) {
            $ledger_type = ($item->item_type == 'material') ? 'production_out' : 'wip_out';
            
            // === STEP 1: Restore stock_on_hand if used ===
            if (!empty($item->stock_on_hand_id)) {
                $this->db->where('id', $item->stock_on_hand_id)
                    ->set('available_qty', 'available_qty + ' . (float)$item->qty_kirim, FALSE)
                    ->update('stock_on_hand');
            }
            
            // === STEP 2: Delete ledger entries ===
            if (!$this->Stock_ledger_model->delete_by_transaction_id($item->id, $ledger_type)) {
                $this->db->trans_rollback();
                return false;
            }
        }
        
        $this->update_status($do_id, 'Draft');
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    // ========================================================================
    // AJAX HELPERS
    // ========================================================================

    public function get_needed_bom_items($order_id) {
        $this->db->select('sob.item_id, sob.item_type, SUM(sob.total_qty) as total_butuh, 
            CASE WHEN sob.item_type = "material" THEN m.nama_material ELSE sf.nama_barang END as item_name,
            sob.satuan
        ', FALSE);
        $this->db->from($this->table_order_bom . ' sob');
        $this->db->join('materials m', 'sob.item_id = m.id AND sob.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sob.item_id = sf.id AND sob.item_type = "semifinished"', 'left');
        $this->db->where('sob.subkon_order_id', $order_id);
        $this->db->group_by('sob.item_id, sob.item_type, item_name, sob.satuan');
        $bom_kebutuhan = $this->db->get()->result();

        $this->db->select('sod.item_id, sod.item_type, SUM(sod.qty_kirim) as total_terkirim');
        $this->db->from($this->table_details . ' sod');
        $this->db->join($this->table_header . ' soh', 'sod.subkon_out_id = soh.id');
        $this->db->where('soh.subkon_order_id', $order_id);
        $this->db->where('soh.status', 'Confirmed');
        $this->db->group_by('sod.item_id, sod.item_type');
        $terkirim_query = $this->db->get()->result();
        
        $terkirim = [];
        foreach($terkirim_query as $row) {
            $terkirim[$row->item_id][$row->item_type] = (float)$row->total_terkirim;
        }

        foreach ($bom_kebutuhan as $item) {
            $sudah_kirim = $terkirim[$item->item_id][$item->item_type] ?? 0;
            $item->total_butuh = (float)$item->total_butuh;
            $item->total_terkirim = $sudah_kirim;
            $item->sisa_butuh = $item->total_butuh - $sudah_kirim;
        }
        return $bom_kebutuhan;
    }

    public function search_confirmed_orders($term = '') {
        $this->db->select('sko.id, CONCAT(sko.subkon_order_no, " - ", s.nama_supplier) as text, sko.supplier_id', FALSE);
        $this->db->from($this->table_order_header . ' sko');
        $this->db->join('suppliers s', 'sko.supplier_id = s.id', 'left');
        $this->db->where('sko.status', 'Confirmed');

        if (!empty($term)) {
            $this->db->group_start();
            $this->db->like('sko.subkon_order_no', $term);
            $this->db->or_like('s.nama_supplier', $term);
            $this->db->group_end();
        }
        $query = $this->db->get('', 10); // Limit 10
        return $query->result();
    }

    /**
     * =========================================================
     * INI FUNGSI YANG DIPERBAIKI (BUG SINTAKS 1064)
     * =========================================================
     */
    public function get_available_stock_material($item_id) {
        $this->db->select("
            soh.id as stock_on_hand_id, 
            soh.available_qty, 
            soh.unit_value, 
            
            CASE 
                WHEN grn.grn_number IS NOT NULL THEN grn.grn_number
                WHEN soh.grn_header_id IS NULL THEN '(Retur Produksi)'
                ELSE '(Lainnya)'
            END AS grn_number 
        ", FALSE); // <-- FALSE ditambahkan untuk CASE WHEN
        
        $this->db->from('stock_on_hand soh');
        $this->db->join('grn_header grn', 'soh.grn_header_id = grn.id', 'left');
        $this->db->where('soh.item_id', $item_id);
        $this->db->where('soh.item_type', 'material');
        $this->db->where('soh.available_qty >', 0);
        $this->db->order_by('soh.id', 'ASC'); // FIFO berdasarkan ID SOH
        return $this->db->get()->result();
    }
    
    public function get_material_batch_info($stock_on_hand_id) {
        return $this->db->select('available_qty, unit_value')
                        ->where('id', $stock_on_hand_id)
                        ->get('stock_on_hand')
                        ->row();
    }

    public function get_sfg_wip_balance($item_id) {
        $this->db->select('balance');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', 'semifinished');
        $this->db->where_in('transaction_type', ['wip_in', 'wip_out', 'IN_RETURN_WIP', 'production_out']); 
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $result = $this->db->get('stock_ledger')->row();
        return $result ? (float)$result->balance : 0;
    }
}