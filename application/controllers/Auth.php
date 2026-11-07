<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends MY_Controller {

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

    /**
     * Backwards-compatible route: /auth/login
     * Some parts of the app or client JS may redirect to auth/login —
     * provide this method to avoid 404 and behave like index().
     */
    public function login()
    {
        $this->index();
    }

    // ==============================
    // PROSES LOGIN
    // ==============================
    public function login_action() {
        $username = trim($this->input->post('username'));
        $password = $this->input->post('password');

        // Ambil data user berdasarkan username (tanpa filter status dulu)
        $user = $this->db->get_where('users', [
            'username' => $username
        ])->row();

        // Cek apakah akun inactive
        if ($user && $user->status == 'inactive') {
            // Log attempt
            $this->load->helper('log');
            add_log('LOGIN_INACTIVE', 'User', 'User dengan username: ' . $username . ' yang sudah inactive mencoba login');

            // Tampilkan error
            $this->session->set_flashdata('error', 'Maaf, akun Anda sudah tidak aktif. Silakan hubungi administrator.');
            redirect('auth');
            return;
        }

        // Akun baru/hasil edit yang MASIH menunggu approval Director/Accounting
        // (ERP Revision Notes "TYPE OF USER" — lihat memory
        // user_type_approval_feature.md) belum boleh login sama sekali.
        if ($user && ($user->approval_status ?? '') === 'pending') {
            $this->session->set_flashdata('error', 'Akun Anda masih menunggu persetujuan. Silakan hubungi Administrator/Director/Accounting.');
            redirect('auth');
            return;
        }
        if ($user && ($user->approval_status ?? '') === 'rejected') {
            $this->session->set_flashdata('error', 'Pembuatan akun Anda ditolak. Silakan hubungi Administrator.');
            redirect('auth');
            return;
        }

        // Validasi login
        if ($user && password_verify($password, $user->password)) {
            // Generate session token unik (single device login)
            $token = bin2hex(random_bytes(32));
            $this->User_model->update_session_token($user->id, $token);

            // Ambil role_name dari roles table
            $role = $this->db->get_where('roles', ['id' => $user->role_id])->row();
            $role_name = $role ? $role->role_name : 'Guest';

            // Simpan data session
            $this->session->set_userdata([
                'user_id'       => $user->id,
                'username'      => $user->username,
                'fullname'      => $user->fullname,
                'role_id'       => $user->role_id,
                'role_name'     => $role_name,
                'departemen'    => $user->departemen,
                'session_token' => $token,
                'logged_in'     => true
            ]);

            // Cek apakah ini login pertama (first_login = 1)
            if ($user->first_login == 1) {
                $this->session->set_userdata('must_change_password', true);
            }

            // Update waktu last_login
            $this->User_model->update_last_login($user->username);

            // Redirect ke halaman ganti password jika pertama kali login
            if ($user->first_login == 1) {
                redirect('auth/change_password');
            } else {
                // Redirect ke dashboard jika login normal
                $this->session->set_flashdata('success', 'Selamat datang, '.$user->fullname.'!');
                redirect('dashboard');
            }
        } else {
            // Login gagal
            $this->session->set_flashdata('error', 'Username atau password salah!');
            redirect('auth');
        }
    }

    // ==============================
    // CHANGE PASSWORD (FIRST LOGIN)
    // ==============================
    public function change_password() {
        // Hanya user yang sedang first login yang bisa akses halaman ini
        if (!$this->session->userdata('must_change_password')) {
            redirect('dashboard');
        }

        $this->load->view('auth/change_password');
    }

    public function save_password() {
        // Cek apakah user sedang dalam proses first login
        if (!$this->session->userdata('must_change_password')) {
            $this->session->set_flashdata('error', lang_line('auth_msg_access_denied'));
            redirect('auth');
            return;
        }

        $new_password = $this->input->post('new_password');
        $confirm_password = $this->input->post('confirm_password');

        // Validasi form
        if (empty($new_password) || empty($confirm_password)) {
            $this->session->set_flashdata('error', lang_line('auth_msg_pw_empty'));
            redirect('auth/change_password');
            return;
        }

        if ($new_password !== $confirm_password) {
            $this->session->set_flashdata('error', lang_line('auth_msg_pw_mismatch'));
            redirect('auth/change_password');
            return;
        }

        if (strlen($new_password) < 6) {
            $this->session->set_flashdata('error', lang_line('auth_msg_pw_min'));
            redirect('auth/change_password');
            return;
        }

        // Update password di database
        $user_id = $this->session->userdata('user_id');
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $this->User_model->update($user_id, [
            'password' => $hashed_password,
            'first_login' => 0
        ]);

        // Hapus session flag
        $this->session->unset_userdata('must_change_password');

        // Set flash success dan redirect
        $this->session->set_flashdata('success', lang_line('auth_msg_pw_changed'));
        redirect('dashboard');
    }

    // ==============================
    // LOGOUT
    // ==============================
    public function logout() {
        $user_id = $this->session->userdata('user_id');
        if ($user_id) {
            $this->User_model->clear_session_token($user_id);
        }
        $this->session->sess_destroy();
        redirect('auth');
    }
}
