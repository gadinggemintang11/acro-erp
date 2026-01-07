<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warehouse_in_detail_model extends CI_Model {

    public function get_by_header($header_id)
    {
        $this->db->where('header_id', $header_id);
        return $this->db->get('warehouse_in_detail')->result();
    }

    public function insert($data)
    {
        $this->db->insert('warehouse_in_detail', $data);
        return $this->db->insert_id();
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        return $this->db->delete('warehouse_in_detail');
    }
}
