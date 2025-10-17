<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
    }

    // ==============================
    // HALAMAN LOGIN
    // ==============================
    public function index() {
        // Jika sudah login, langsung ke dashboard
        if ($this->session->userdata('logged_in')) {
            redirect('dashboard');
        }

        $this->load->view('auth/login');
    }

    // ==============================
    // PROSES LOGIN
    // ==============================
    public function login_action() {
        $username = trim($this->input->post('username'));
        $password = $this->input->post('password');

        // Ambil data user berdasarkan username
        $user = $this->db->get_where('users', [
            'username' => $username,
            'status' => 'active'
        ])->row();

        // Validasi login
        if ($user && password_verify($password, $user->password)) {
            // Simpan data session
            $this->session->set_userdata([
                'user_id'   => $user->id,
                'username'  => $user->username,
                'fullname'  => $user->fullname,
                'role_id'   => $user->role_id,
                'logged_in' => true
            ]);

            // Update waktu last_login
            $this->User_model->update_last_login($user->username);

            // Redirect ke dashboard
            $this->session->set_flashdata('success', 'Selamat datang, '.$user->fullname.'!');
            redirect('dashboard');
        } else {
            // Login gagal
            $this->session->set_flashdata('error', 'Username atau password salah!');
            redirect('auth');
        }
    }

    // ==============================
    // LOGOUT
    // ==============================
    public function logout() {
        $this->session->sess_destroy();
        redirect('auth');
    }
}
