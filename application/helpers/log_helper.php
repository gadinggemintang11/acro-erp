<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fungsi untuk menambahkan log aktivitas
 *
 * @param string $action      Aksi yang dilakukan (e.g., 'CREATE', 'UPDATE', 'DELETE')
 * @param string $module      Modul tempat aksi dilakukan (e.g., 'Purchase Order')
 * @param string $description Deskripsi detail dari aksi
 */
function add_log($action, $module, $description) {
    // Ambil instance CodeIgniter
    $CI =& get_instance();

    // Pastikan session dan database sudah ter-load
    $CI->load->library('session');
    $CI->load->database();

    // Ambil data dari session
    $user_id = $CI->session->userdata('user_id'); // Pastikan 'user_id' ada di session
    $username = $CI->session->userdata('username'); // Pastikan 'username' ada di session

    // Siapkan data untuk di-insert
    $data = [
        'user_id'     => $user_id ? $user_id : 0,
        'username'    => $username ? $username : 'System',
        'action'      => $action,
        'module'      => $module,
        'description' => $description
    ];

    // Insert ke database
    $CI->db->insert('user_logs', $data);
}