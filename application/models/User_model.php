<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_model extends CI_Model {

    public function get_all() {
        // user_type & approval_status baru ada setelah migrasi #35 — di DB
        // yang belum dimigrasi kolomnya dilewati supaya login tetap jalan
        // (lihat db_has_column() di auth_helper).
        $sel = 'users.id, users.username, users.fullname, users.email, users.departemen, users.tanggal_masuk, users.signature_path, users.status, users.last_login, roles.role_name, users.role_id';
        if (db_has_column('users', 'user_type'))       $sel .= ', users.user_type';
        if (db_has_column('users', 'approval_status')) $sel .= ', users.approval_status';
        $this->db->select($sel);
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
        // Hard delete: hapus data dari database
        return $this->db->where('id', $id)->delete('users');
    }

    /**
     * Cek apakah username sudah ada (termasuk yang inactive)
     * @param string $username
     * @param int|null $exclude_id ID user yang dikecualikan (untuk update)
     * @return bool
     */
    public function check_username_exists($username, $exclude_id = null) {
        $this->db->where('username', $username);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get('users');
        return $query->num_rows() > 0;
    }

    /**
     * Cek apakah email sudah ada (termasuk yang inactive)
     * @param string $email
     * @param int|null $exclude_id ID user yang dikecualikan (untuk update)
     * @return bool
     */
    public function check_email_exists($email, $exclude_id = null) {
        if (empty($email)) return false;
        
        $this->db->where('email', $email);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        $query = $this->db->get('users');
        return $query->num_rows() > 0;
    }

    public function get_roles() {
        return $this->db->where('status', 'active')->get('roles')->result();
    }

    // ------------------------------------------------------------
    // ROLE LEVEL — 1 user bisa punya banyak role (klien 2026-09-15)
    // ------------------------------------------------------------

    /** Peta user_id => [role_id, ...] untuk semua user (gabungan user_roles + users.role_id). */
    public function get_user_roles_map() {
        $map = [];
        foreach ($this->db->query('SELECT DISTINCT user_id, role_id FROM ' . user_roles_source_sql() . ' x')->result() as $r) {
            $map[(int) $r->user_id][] = (int) $r->role_id;
        }
        return $map;
    }

    /**
     * Ganti seluruh role user dengan $role_ids. users.role_id ("role utama",
     * dipakai label & API lama) dipertahankan kalau masih termasuk pilihan,
     * kalau tidak diganti role pertama.
     */
    public function set_roles($user_id, array $role_ids) {
        $user_id  = (int) $user_id;
        $role_ids = array_values(array_unique(array_filter(array_map('intval', $role_ids))));
        if ($user_id <= 0 || empty($role_ids)) return false;

        $current = $this->db->select('role_id')->where('id', $user_id)->get('users')->row();
        $primary = ($current && in_array((int) $current->role_id, $role_ids, true)) ? (int) $current->role_id : $role_ids[0];

        $this->db->trans_start();
        $this->db->where('id', $user_id)->update('users', ['role_id' => $primary]);
        if (db_table_exists('user_roles')) {
            $this->db->where('user_id', $user_id)->delete('user_roles');
            foreach ($role_ids as $rid) {
                $this->db->insert('user_roles', ['user_id' => $user_id, 'role_id' => $rid]);
            }
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /** Hanya id role yang benar-benar ada & aktif. */
    public function filter_valid_role_ids(array $role_ids) {
        $role_ids = array_values(array_unique(array_filter(array_map('intval', $role_ids))));
        if (empty($role_ids)) return [];
        $rows = $this->db->select('id')->where_in('id', $role_ids)->where('status', 'active')->get('roles')->result();
        $valid = array_map(function ($r) { return (int) $r->id; }, $rows);
        return array_values(array_intersect($role_ids, $valid));
    }

    public function get_departments() {
        return $this->db->where('group_code', 'Department')
                        ->order_by('code', 'ASC')
                        ->get('common_code')
                        ->result();
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

    public function update_session_token($user_id, $token) {
        return $this->db->where('id', $user_id)
                        ->update('users', ['session_token' => $token]);
    }

    public function get_session_token($user_id) {
        $result = $this->db->select('session_token')
                           ->where('id', $user_id)
                           ->get('users')
                           ->row();
        return $result ? $result->session_token : null;
    }

    public function clear_session_token($user_id) {
        return $this->db->where('id', $user_id)
                        ->update('users', ['session_token' => NULL]);
    }
}
