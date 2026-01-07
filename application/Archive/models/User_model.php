<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function get_all() {
        $this->db->select('users.id, users.username, users.fullname, users.email, users.status, users.last_login, roles.role_name, users.role_id');
        $this->db->from('users');
        $this->db->join('roles', 'users.role_id = roles.id', 'left');
        $this->db->order_by('users.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_by_id($id) {
        return $this->db->get_where('users', ['id' => $id])->row();
    }

    public function insert($data) {
        return $this->db->insert('users', $data);
    }

    public function update($id, $data) {
        return $this->db->where('id', $id)->update('users', $data);
    }

    public function delete($id) {
        // Soft delete: ubah status jadi inactive
        return $this->db->where('id', $id)->update('users', ['status' => 'inactive']);
    }

    public function get_roles() {
        return $this->db->where('status', 'active')->get('roles')->result();
    }
    public function get_user_name($user_id) {
        $query = $this->db->select('fullname')
                          ->where('id', $user_id)
                          ->get('users')
                          ->row_array();

        return $query['fullname'] ?? null;
    }

    /**
     * Update last_login by username (dipanggil saat login sukses)
     * Pastikan hanya ada 1 method ini di model.
     *
     * @param string $username
     * @return bool
     */
    public function update_last_login($username) {
        return $this->db->where('username', $username)
                        ->update('users', ['last_login' => date('Y-m-d H:i:s')]);
    }
}
