<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        // Ini penting agar base_url() dikenali
        $this->load->helper('url');
    }

    public function index()
    {
        // 1. Ambil "nampan" yang sudah disiapkan oleh MY_Controller
        $data = $this->data;

        // 2. Tambahkan barang spesifik untuk halaman ini
        $data['title'] = 'Dashboard';
        $data['content'] = 'dashboard_view'; 

        // 3. Kirim nampan yang sudah lengkap ke view
        $this->load->view('layouts/main_template', $data);
    }
}