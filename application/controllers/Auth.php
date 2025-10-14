<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('session');
        $this->load->helper('url');
    }

    // 1. Menampilkan Halaman Login
    public function index()
    {
        // Jika user sudah login, tendang ke dashboard
        if ($this->session->userdata('user_id')) {
            redirect('dashboard');
        }
        $this->load->view('auth/login'); // Memuat view login
    }

    // 2. Memproses Usaha Login
    public function process_login()
    {
        $username = $this->input->post('username');
        $password = $this->input->post('password');

        // Panggil model untuk mencari user
        $user = $this->User_model->login($username);

        // Cek apakah user ada dan password cocok
        if ($user && password_verify($password, $user['password'])) {
            // Jika berhasil, siapkan data session
            $session_data = [
                'user_id'   => $user['id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
                'role_id'   => $user['role_id'],
                'logged_in' => TRUE
            ];
            $this->session->set_userdata($session_data);

            // Arahkan ke dashboard
            redirect('dashboard');
        } else {
            // Jika gagal, buat notifikasi error dan kembalikan ke halaman login
            $this->session->set_flashdata('error', 'Username atau Password salah!');
            redirect('auth');
        }
    }

    // 3. Proses Logout
    public function logout()
    {
        // Hapus semua data session
        $this->session->sess_destroy();
        // Arahkan kembali ke halaman login
        redirect('auth');
    }
}