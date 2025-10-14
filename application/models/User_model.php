<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    private $_table = "users";

    public function getAll()
    {
        $this->db->select('users.*, roles.role_name');
        $this->db->from($this->_table);
        $this->db->join('roles', 'users.role_id = roles.id');
        $this->db->order_by('users.id', 'ASC');
        return $this->db->get()->result_array();
    }

    public function getById($id)
    {
        return $this->db->get_where($this->_table, ["id" => $id])->row_array();
    }

    public function save()
    {
        $post = $this->input->post();
        $data = [
            'username' => $post["username"],
            'full_name' => $post["full_name"],
            'role_id' => $post["role_id"],
            'is_active' => $post["is_active"],
            'password' => password_hash($post["password"], PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->insert($this->_table, $data);
    }

    public function update()
    {
        $post = $this->input->post();
        $data = [
            'username' => $post["username"],
            'full_name' => $post["full_name"],
            'role_id' => $post["role_id"],
            'is_active' => $post["is_active"],
        ];

        // Hanya update password jika diisi
        if (!empty($post["password"])) {
            $data['password'] = password_hash($post["password"], PASSWORD_DEFAULT);
        }

        return $this->db->update($this->_table, $data, array('id' => $post['id']));
    }

    public function delete($id)
    {
        return $this->db->delete($this->_table, array("id" => $id));
    }
    public function login($username)
    {
        // Cari user berdasarkan username dan pastikan statusnya aktif
        $this->db->where('username', $username);
        $this->db->where('is_active', 1);
        $query = $this->db->get($this->_table);
        return $query->row_array();
    }
}