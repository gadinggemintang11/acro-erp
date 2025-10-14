<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_model extends CI_Model {

    private $_table = "customers";

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
        // Menggunakan array untuk insert agar lebih rapi
        $data = [
            'customer_code' => $post["customer_code"],
            'customer_name' => $post["customer_name"],
            'address' => $post["address"],
            'phone' => $post["phone"],
            'npwp' => $post["npwp"],
            'country' => $post["country"],
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->insert($this->_table, $data);
    }

    public function update()
    {
        $post = $this->input->post();
        // Menggunakan array untuk update
        $data = [
            'customer_code' => $post["customer_code"],
            'customer_name' => $post["customer_name"],
            'address' => $post["address"],
            'phone' => $post["phone"],
            'npwp' => $post["npwp"],
            'country' => $post["country"],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->update($this->_table, $data, array('id' => $post['id']));
    }

    public function delete($id)
    {
        return $this->db->delete($this->_table, array("id" => $id));
    }
}