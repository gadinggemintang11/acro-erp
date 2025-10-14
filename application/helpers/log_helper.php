<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('add_log')) {
    /**
     * Fungsi untuk menambahkan log aktivitas ke database.
     * @param string $activity Deskripsi aktivitas yang dilakukan.
     */
    function add_log($activity) {
        // Mendapatkan instance CodeIgniter
        $CI =& get_instance();

        // Memuat library session dan database jika belum dimuat
        $CI->load->library('session');
        $CI->load->database();

        // Menyiapkan data untuk disimpan
        $log_data = array(
            'user_id'   => $CI->session->userdata('user_id'),
            'username'  => $CI->session->userdata('username'),
            'activity'  => $activity,
            'timestamp' => date('Y-m-d H:i:s')
        );

        // Menyimpan data ke tabel activity_logs
        $CI->db->insert('activity_logs', $log_data);
    }
}