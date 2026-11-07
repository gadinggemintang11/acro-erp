<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role_model extends CI_Model {

    public function get_all_roles()
    {
        return $this->db->get('roles')->result();
    }

    /**
     * ============================================================
     * KUNCI ROLE ACCESS — satu kunci untuk semua role
     * ------------------------------------------------------------
     * User 2026-09-17: "buat di role acces ada button kunci role, khusus
     * admin, jadi ketika sudah dikunci button edit akses dan edit posisi dll
     * itu ga ada", lalu "saya mau button lock ini 1 aja dan meng lock semua".
     *
     * Tabel satu baris (id = 1), dibuat migration_role_lock.sql. Selama
     * tabelnya belum ada, lock_info() mengembalikan null -> is_locked() false,
     * jadi halaman Role Access tetap berjalan seperti biasa di database yang
     * migrasinya belum dijalankan.
     * ============================================================
     */
    public function lock_info()
    {
        if (!$this->db->table_exists('role_access_lock')) return null;
        $row = $this->db->query('SELECT l.is_locked, l.locked_by, l.locked_at, u.fullname AS locked_by_name
                                   FROM role_access_lock l LEFT JOIN users u ON u.id = l.locked_by
                                  WHERE l.id = 1')->row();
        return $row ?: (object) ['is_locked' => 0, 'locked_by' => null, 'locked_at' => null, 'locked_by_name' => null];
    }

    public function is_locked()
    {
        $info = $this->lock_info();
        return $info && (int) $info->is_locked === 1;
    }

    public function set_lock($locked, $user_id)
    {
        return $this->db->replace('role_access_lock', [
            'id'        => 1,
            'is_locked' => $locked ? 1 : 0,
            'locked_by' => $locked ? (int) $user_id : null,
            'locked_at' => $locked ? date('Y-m-d H:i:s') : null,
        ]);
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
        $this->db->select('menus.id as menu_id, menus.menu_name, menus.menu_key, menus.department, rp.can_view, rp.can_add, rp.can_edit, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval, rp.can_delete');
        $this->db->from('menus');
        $this->db->join('role_permissions rp', "rp.menu_id = menus.id AND rp.role_id = $role_id", 'left');
        $this->db->order_by('menus.department', 'ASC');
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
                    'role_id'         => $role_id,
                    'menu_id'         => $menu_id,
                    'can_view'        => isset($perm['view']) ? 1 : 0,
                    'can_add'         => isset($perm['add']) ? 1 : 0,
                    'can_edit'        => isset($perm['edit']) ? 1 : 0,
                    'can_confirm'     => isset($perm['confirm']) ? 1 : 0,
                    'can_head_dept'   => isset($perm['head_dept']) ? 1 : 0,
                    'can_endorsement' => isset($perm['endorsement']) ? 1 : 0,
                    'can_approval'    => isset($perm['approval']) ? 1 : 0,
                    'can_delete'      => isset($perm['delete']) ? 1 : 0,
                ];
                $this->db->insert('role_permissions', $data);
            }
        }
    }
}
