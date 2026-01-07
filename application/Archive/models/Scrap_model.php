<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Scrap_model extends CI_Model {

    private $table = 'master_scrap';

    public function __construct() {
        parent::__construct();
        $this->load->database(); // ✅ FIX
        $this->load->model('Commoncode_model'); // ✅ FIX
    }

    public function get_all($filters = []) {
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']); // ✅ FIX
             $this->db->group_start(); // ✅ FIX
             $this->db->like('scrap_code', $term); // ✅ FIX
             $this->db->or_like('scrap_name', $term); // ✅ FIX
             $this->db->group_end(); // ✅ FIX
        }
        return $this->db->get($this->table)->result(); // ✅ FIX
    }
    
    public function get_active_scrap() {
        return $this->db->where('status', 'active')->get($this->table)->result(); // ✅ FIX
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row(); // ✅ FIX
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data); // ✅ FIX
    }

    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data); // ✅ FIX
    }

    // Soft delete
    public function delete($id) {
        return $this->db->where('id', $id)->update($this->table, ['status' => 'inactive']); // ✅ FIX
    }
    
    public function generate_scrap_code() {
        $prefix = 'SCRP-';
        $this->db->like('scrap_code', $prefix, 'after'); // ✅ FIX
        $this->db->order_by('id', 'DESC'); // ✅ FIX
        $last = $this->db->get($this->table, 1)->row(); // ✅ FIX
        $new_num = $last ? (int) substr($last->scrap_code, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }
}