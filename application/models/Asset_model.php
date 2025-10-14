<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Asset_model extends CI_Model {

    private $_table = "assets";

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
        $data = [
            'asset_code' => $post["asset_code"],
            'asset_name' => $post["asset_name"],
            'asset_type' => $post["asset_type"],
            'acquisition_date' => $post["acquisition_date"],
            'description' => $post["description"],
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->insert($this->_table, $data);
    }

    public function update()
    {
        $post = $this->input->post();
        $data = [
            'asset_code' => $post["asset_code"],
            'asset_name' => $post["asset_name"],
            'asset_type' => $post["asset_type"],
            'acquisition_date' => $post["acquisition_date"],
            'description' => $post["description"],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->update($this->_table, $data, array('id' => $post['id']));
    }

    public function delete($id)
    {
        return $this->db->delete($this->_table, array("id" => $id));
    }
}