<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Product_model extends CI_Model {

    private $_table = "products";

    public function getAll()
    {
        return $this->db->get($this->_table)->result_array();
    }

    public function getById($id)
    {
        return $this->db->get_where($this->_table, ["id" => $id])->row_array();
    }

    public function save($data)
    {
        return $this->db->insert($this->_table, $data);
    }

    public function update($data, $id)
    {
        return $this->db->update($this->_table, $data, array('id' => $id));
    }

    public function delete($id)
    {
        return $this->db->delete($this->_table, array("id" => $id));
    }

    public function getLastByPrefix($prefix)
    {
        $this->db->like('product_code', $prefix, 'after');
        $this->db->order_by('product_code', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get($this->_table);
        return $query->row_array();
    }
}