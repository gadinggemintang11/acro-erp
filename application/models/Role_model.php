<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends CI_Model {

    private $_table = "roles";

    public function getAll()
    {
        // Mengurutkan berdasarkan ID dari yang terkecil
        $this->db->order_by('id', 'ASC');
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
            'role_name' => $post["role_name"],
            'description' => $post["description"],
        ];
        return $this->db->insert($this->_table, $data);
    }

    public function update()
    {
        $post = $this->input->post();
        $data = [
            'role_name' => $post["role_name"],
            'description' => $post["description"],
        ];
        return $this->db->update($this->_table, $data, array('id' => $post['id']));
    }

    public function delete($id)
    {
        return $this->db->delete($this->_table, array("id" => $id));
    }
    public function getAccessByRoleId($role_id)
    {
        $this->db->where('role_id', $role_id);
        $query = $this->db->get('role_access');
        return $query->result_array();
    }

    /**
     * Mengupdate izin akses untuk sebuah role
     */
    public function updateAccess($role_id, $controllers)
    {
        // 1. Hapus semua izin lama untuk role ini
        $this->db->where('role_id', $role_id);
        $this->db->delete('role_access');

        // 2. Siapkan data baru untuk dimasukkan
        $data = [];
        foreach ($controllers as $controller) {
            $data[] = [
                'role_id' => $role_id,
                'controller_name' => $controller
            ];
        }

        // 3. Masukkan semua izin baru (jika ada)
        if (!empty($data)) {
            return $this->db->insert_batch('role_access', $data);
        }

        return true; // Kembalikan true jika tidak ada izin yang dipilih
    }
}