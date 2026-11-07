<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper "Riwayat Approval Saya" — dipakai SEMUA halaman antrian approval
 * (IRF/MRF/PRF/ERF/IPO/PMPO/GMPO/MPR/IPR/FG Receipt).
 *
 * Dibuat 2026-09-06 (user: halaman approval "hanya menampilkan list daftar yang
 * akan di approve, seharusnya di bagian bawah ada riwayat yang user itu
 * approve"). Sengaja dibungkus helper — bukan dipanggil langsung sebagai model
 * di tiap controller — supaya 9 controller yang memakainya TIDAK perlu diubah
 * constructor-nya satu per satu (beberapa di antaranya controller besar yang
 * sebaiknya tidak disentuh lebih dari perlu).
 *
 * Query-nya sendiri ada di Approval_history_model::get_my_history().
 * Kartunya dirender partial `templates/my_approval_history.php`.
 */

if (!function_exists('my_approval_history')) {
    /**
     * Riwayat aksi approval milik user yang SEDANG LOGIN pada satu modul.
     *
     * @param array $cfg   ['table','number','date','where'] — lihat
     *                     Approval_history_model::get_my_history()
     * @param int   $limit jumlah aksi terakhir yang ditampilkan
     * @return array
     */
    function my_approval_history(array $cfg, $limit = 15) {
        $CI =& get_instance();
        $CI->load->model('Approval_history_model');
        return $CI->Approval_history_model->get_my_history(
            $cfg,
            $CI->session->userdata('user_id'),
            $limit
        );
    }
}
