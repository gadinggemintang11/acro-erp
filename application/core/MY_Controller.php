<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

    // Kita siapkan variabel untuk menampung data yang akan dikirim ke semua view
    public $data = [];

    public function __construct()
    {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');

        // --- TAHAP 1: PENJAGA GERBANG UTAMA ---
        if (!$this->session->userdata('user_id')) {
            $this->session->set_flashdata('error', 'Anda harus login terlebih dahulu!');
            redirect('auth');
        }

        // --- TAHAP 2: MENYIAPKAN DAFTAR IZIN UNTUK SIDEBAR ---
        $role_id = $this->session->userdata('role_id');
        $this->load->model('Role_model'); // Pastikan Role_model di-load
        
        $access_data = $this->Role_model->getAccessByRoleId($role_id);
        
        // Ubah array multidimensi menjadi array sederhana berisi nama controller
        // Contoh: ['dashboard', 'material', 'supplier']
        $allowed_controllers = array_column($access_data, 'controller_name');

        // Simpan daftar izin ini ke dalam variabel $data agar bisa diakses di view
        $this->data['allowed_controllers'] = $allowed_controllers;


        // --- TAHAP 3: PENJAGA KARTU AKSES (TETAP SAMA) ---
        $controller = $this->uri->segment(1);
        
        if ($role_id != 1) { // Jika bukan Admin
            if ($controller != 'dashboard') { // dan bukan halaman dashboard
                // Cek apakah controller yang diakses ada di dalam daftar izin
                if (!in_array($controller, $allowed_controllers)) {
                    $this->session->set_flashdata('error', 'Anda tidak memiliki hak akses ke halaman tersebut!');
                    redirect('dashboard');
                }
            }
        }
    }
}