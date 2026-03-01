<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ============================================================
 * AUTH HELPER UNTUK NEOKABER SYSTEM
 * ------------------------------------------------------------
 * Berisi fungsi:
 *  - check_login()   → memastikan user login
 *  - check_access()  → validasi hak akses role ke modul
 * ============================================================
 */

if (!function_exists('check_login')) {
    /**
     * Cek apakah user sudah login.
     * Jika belum, redirect ke halaman login.
     */
    function check_login()
    {
        $CI =& get_instance();
        if (!$CI->session->userdata('logged_in')) {
            $CI->session->set_flashdata('error', 'Silakan login terlebih dahulu.');
            redirect('auth/login');
        }
    }
}

if (!function_exists('check_access')) {
    /**
     * Cek hak akses role terhadap modul
     * @param string $menu_key - key modul misal 'commoncode'
     * @param string $action - view|add|edit|delete
     */
    function check_access($menu_key, $action = 'view')
    {
        $CI =& get_instance();
        $role_id = $CI->session->userdata('role_id');

        // DEBUG: tampilkan info ke log (tidak ke user)
        log_message('debug', "check_access: role_id=$role_id, menu_key=$menu_key, action=$action");

        if (!$role_id) return false;

        $CI->db->select("rp.can_view, rp.can_add, rp.can_edit, rp.can_delete");
        $CI->db->from("role_permissions rp");
        $CI->db->join("menus m", "rp.menu_id = m.id", "inner");
        $CI->db->where("rp.role_id", $role_id);
        $CI->db->where("m.menu_key", $menu_key);

        $permission = $CI->db->get()->row();

        log_message('debug', 'permission result: ' . json_encode($permission));

        if (!$permission) return false;

        switch ($action) {
            case 'view': return $permission->can_view == 1;
            case 'add': return $permission->can_add == 1;
            case 'edit': return $permission->can_edit == 1;
            case 'delete': return $permission->can_delete == 1;
            // Common aliases: allow 'approve' and 'submit' to use the edit permission
            case 'approve':
            case 'submit':
                return $permission->can_edit == 1;
            default: return false;
        }
    }
}
