<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Common_code_model extends CI_Model {
    private $_table = "common_codes";
    public function getAll() { return $this->db->get($this->_table)->result_array(); }
    public function getById($id) { return $this->db->get_where($this->_table, ["id" => $id])->row_array(); }
    public function getByGroup($group) {
        $this->db->where('code_group', $group);
        return $this->db->get($this->_table)->result_array();
    }
    public function save() {
        $post = $this->input->post();
        $data = ['code_group' => $post["code_group"], 'code_name' => $post["code_name"], 'description' => $post["description"]];
        return $this->db->insert($this->_table, $data);
    }
    public function update() {
        $post = $this->input->post();
        $data = ['code_group' => $post["code_group"], 'code_name' => $post["code_name"], 'description' => $post["description"]];
        return $this->db->update($this->_table, $data, array('id' => $post['id']));
    }
    public function delete($id) { return $this->db->delete($this->_table, array("id" => $id)); }
}