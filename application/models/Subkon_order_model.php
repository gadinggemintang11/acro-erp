<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subkon_order_model extends CI_Model {

    private $table_header = 'subkon_order_header';
    private $table_outputs = 'subkon_order_outputs';
    private $table_bom = 'subkon_order_bom';
    private $table_out_header = 'subkon_out_header';

    // ========================================================================
    // HEADER (KONTRAK)
    // ========================================================================

    public function generate_order_no() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('subkon_order', 'subkon_order_header', 'subkon_order_no');
    }

    public function insert_header($data) {
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }
    
    public function update_header_totals($order_id, $data) {
        return $this->db->where('id', $order_id)->update($this->table_header, $data);
    }

    public function get_all($filters, $limit, $offset) {
        $this->db->select('soh.*, s.nama_supplier');
        $this->db->from($this->table_header . ' soh');
        $this->db->join('suppliers s', 'soh.supplier_id = s.id', 'left');

        if (!empty($filters['start_date'])) { $this->db->where('soh.order_date >=', $filters['start_date']); }
        if (!empty($filters['end_date'])) { $this->db->where('soh.order_date <=', $filters['end_date']); }
        if (!empty($filters['status'])) { $this->db->where('soh.status', $filters['status']); }
        
        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('soh.subkon_order_no', $term);
            $this->db->or_like('s.nama_supplier', $term);
            $this->db->group_end();
        }

        $this->db->order_by('soh.order_date', 'DESC');
        if ($limit > 0) { $this->db->limit($limit, $offset); }
        
        return $this->db->get()->result();
    }
    
    public function count_all($filters) {
        $this->db->from($this->table_header . ' soh');
        $this->db->join('suppliers s', 'soh.supplier_id = s.id', 'left');
        
        if (!empty($filters['start_date'])) { $this->db->where('soh.order_date >=', $filters['start_date']); }
        if (!empty($filters['end_date'])) { $this->db->where('soh.order_date <=', $filters['end_date']); }
        if (!empty($filters['status'])) { $this->db->where('soh.status', $filters['status']); }
        
        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('soh.subkon_order_no', $term);
            $this->db->or_like('s.nama_supplier', $term);
            $this->db->group_end();
        }
        return $this->db->count_all_results();
    }
    
    public function get_header_by_id($id) {
        $this->db->select('soh.*, s.nama_supplier');
        $this->db->from($this->table_header . ' soh');
        $this->db->join('suppliers s', 'soh.supplier_id = s.id', 'left');
        $this->db->where('soh.id', $id);
        return $this->db->get()->row();
    }

    public function update_status($id, $status, $user_id) {
        $data = ['status' => $status];
        if ($status == 'Confirmed') {
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } else {
            $data['confirmed_at'] = NULL;
        }
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    public function check_if_used($order_id) {
        $count = $this->db->where('subkon_order_id', $order_id)
                          ->from($this->table_out_header)
                          ->count_all_results();
        return ($count > 0);
    }

    // ========================================================================
    // ITEM HASIL (OUTPUT)
    // ========================================================================
    
    public function insert_output_item($data) {
        $this->db->insert($this->table_outputs, $data);
        return $this->db->insert_id();
    }

    public function get_output_details($order_id) {
        $this->db->select('soo.*, 
            CASE 
                WHEN soo.item_type = "semifinished" THEN sf.nama_barang
                WHEN soo.item_type = "finished_goods" THEN fg.nama_barang
            END as item_name
        ', FALSE); // <-- FALSE untuk fix 1064
        $this->db->from($this->table_outputs . ' soo');
        $this->db->join('semifinished_goods sf', 'soo.item_id = sf.id AND soo.item_type = "semifinished"', 'left');
        $this->db->join('finished_goods fg', 'soo.item_id = fg.id AND soo.item_type = "finished_goods"', 'left');
        $this->db->where('soo.subkon_order_id', $order_id);
        return $this->db->get()->result();
    }
    
    public function get_output_item_by_id($output_item_id) {
        return $this->db->where('id', $output_item_id)->get($this->table_outputs)->row();
    }
    
    public function delete_output_item($output_item_id) {
        return $this->db->where('id', $output_item_id)->delete($this->table_outputs);
    }

    public function recalculate_total_jasa($order_id) {
        $this->db->select_sum('total_jasa_item', 'total_jasa_all_items');
        $this->db->where('subkon_order_id', $order_id);
        $result = $this->db->get($this->table_outputs)->row();
        $new_total_jasa = (float)($result->total_jasa_all_items ?? 0);
        return $this->db->where('id', $order_id)->update($this->table_header, ['total_jasa' => $new_total_jasa]);
    }
    
    // ========================================================================
    // MATERIAL BOM (INPUT)
    // ========================================================================

    public function get_bom_details($order_id) {
        $this->db->select('sob.*, 
            CASE 
                WHEN sob.item_type = "material" THEN m.kode_material
                WHEN sob.item_type = "semifinished" THEN sf.kode_barang
            END as item_code,
            CASE 
                WHEN sob.item_type = "material" THEN m.nama_material
                WHEN sob.item_type = "semifinished" THEN sf.nama_barang
            END as item_name
        ', FALSE); // <-- FALSE untuk fix 1064
        $this->db->from($this->table_bom . ' sob');
        $this->db->join('materials m', 'sob.item_id = m.id AND sob.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sob.item_id = sf.id AND sob.item_type = "semifinished"', 'left');
        $this->db->where('sob.subkon_order_id', $order_id);
        return $this->db->get()->result();
    }

    public function get_bom_details_by_output($output_item_id) {
        $this->db->select('sob.*, 
            CASE 
                WHEN sob.item_type = "material" THEN m.kode_material
                WHEN sob.item_type = "semifinished" THEN sf.kode_barang
            END as item_code,
            CASE 
                WHEN sob.item_type = "material" THEN m.nama_material
                WHEN sob.item_type = "semifinished" THEN sf.nama_barang
            END as item_name
        ', FALSE); // <-- FALSE untuk fix 1064
        $this->db->from($this->table_bom . ' sob');
        $this->db->join('materials m', 'sob.item_id = m.id AND sob.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sob.item_id = sf.id AND sob.item_type = "semifinished"', 'left');
        $this->db->where('sob.output_item_id', $output_item_id);
        return $this->db->get()->result();
    }

    public function insert_bom_item($data) {
        return $this->db->insert($this->table_bom, $data);
    }

    public function delete_bom_item($bom_id) {
        return $this->db->where('id', $bom_id)->delete($this->table_bom);
    }
    
    // ========================================================================
    // AJAX SEARCH HELPERS
    // ========================================================================

    public function search_suppliers($term) {
        $this->db->select('id, nama_supplier as text');
        $this->db->like('nama_supplier', $term);
        $this->db->where('status', 'active');
        $query = $this->db->get('suppliers', 10);
        return $query->result();
    }

    public function search_hasil_items($term) {
        $this->db->select("id, CONCAT(nama_barang, ' (', kode_barang, ')') as text, 'semifinished' as item_type", FALSE);
        $this->db->from('semifinished_goods');
        $this->db->like('nama_barang', $term)->or_like('kode_barang', $term);
        $this->db->where('status', 'active');
        $q1 = $this->db->get_compiled_select();

        $this->db->reset_query();
        
        $this->db->select("id, CONCAT(nama_barang, ' (', kode_barang, ')') as text, 'finished_goods' as item_type", FALSE);
        $this->db->from('finished_goods');
        $this->db->like('nama_barang', $term)->or_like('kode_barang', $term);
        $this->db->where('status', 'active');
        $q2 = $this->db->get_compiled_select();

        $query = $this->db->query("($q1) UNION ALL ($q2) LIMIT 20");
        return $query->result();
    }

    public function search_bom_items($term) {
        $this->db->select("id, CONCAT(nama_material, ' (', kode_material, ')') as text, 'material' as item_type", FALSE);
        $this->db->from('materials');
        $this->db->like('nama_material', $term)->or_like('kode_material', $term);
        $this->db->where('status', 'active');
        $q1 = $this->db->get_compiled_select();

        $this->db->reset_query();
        
        $this->db->select("id, CONCAT(nama_barang, ' (', kode_barang, ')') as text, 'semifinished' as item_type", FALSE);
        $this->db->from('semifinished_goods');
        $this->db->like('nama_barang', $term)->or_like('kode_barang', $term);
        $this->db->where('status', 'active');
        $q2 = $this->db->get_compiled_select();

        $query = $this->db->query("($q1) UNION ALL ($q2) LIMIT 20");
        return $query->result();
    }
}