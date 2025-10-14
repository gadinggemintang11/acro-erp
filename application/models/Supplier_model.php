<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Supplier_model extends CI_Model {

    private $_table = "suppliers";

    public function getAll()
    {
        return $this->db->get($this->_table)->result_array();
    }

    public function getById($id)
    {
        return $this->db->get_where($this->_table, ["id" => $id])->row_array();
    }

    public function save()
    {
        $post = $this->input->post();
        $this->supplier_code = $post["supplier_code"];
        $this->supplier_name = $post["supplier_name"];
        $this->address = $post["address"];
        $this->phone = $post["phone"];
        $this->npwp = $post["npwp"];
        $this->created_at = date('Y-m-d H:i:s');
        return $this->db->insert($this->_table, $this);
    }

    public function update()
    {
        $post = $this->input->post();
        $this->id = $post["id"];
        $this->supplier_code = $post["supplier_code"];
        $this->supplier_name = $post["supplier_name"];
        $this->address = $post["address"];
        $this->phone = $post["phone"];
        $this->npwp = $post["npwp"];
        $this->updated_at = date('Y-m-d H:i:s');
        return $this->db->update($this->_table, $this, array('id' => $post['id']));
    }

    public function delete($id)
    {
        return $this->db->delete($this->_table, array("id" => $id));
    }
}