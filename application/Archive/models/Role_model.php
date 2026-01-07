<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends CI_Model {

    public function get_all_roles()
    {
        return $this->db->get('roles')->result();
    }

    public function get_role_by_id($id)
    {
        return $this->db->get_where('roles', ['id' => $id])->row();
    }

    public function insert_role($data)
    {
        return $this->db->insert('roles', $data);
    }

    public function update_role($id, $data)
    {
        return $this->db->where('id', $id)->update('roles', $data);
    }

    public function delete_role($id)
    {
        return $this->db->delete('roles', ['id' => $id]);
    }

    public function get_permissions_by_role($role_id)
    {
        $this->db->select('menus.id as menu_id, menus.menu_name, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete');
        $this->db->from('menus');
        $this->db->join('role_permissions rp', "rp.menu_id = menus.id AND rp.role_id = $role_id", 'left');
        $this->db->order_by('menus.order_no', 'ASC');
        return $this->db->get()->result();
    }

    public function save_permissions($role_id, $permissions)
    {
        // Hapus semua dulu, lalu insert ulang agar bersih
        $this->db->where('role_id', $role_id)->delete('role_permissions');

        if (!empty($permissions)) {
            foreach ($permissions as $menu_id => $perm) {
                $data = [
                    'role_id' => $role_id,
                    'menu_id' => $menu_id,
                    'can_view' => isset($perm['view']) ? 1 : 0,
                    'can_add' => isset($perm['add']) ? 1 : 0,
                    'can_edit' => isset($perm['edit']) ? 1 : 0,
                    'can_delete' => isset($perm['delete']) ? 1 : 0,
                ];
                $this->db->insert('role_permissions', $data);
            }
        }
    }
}
