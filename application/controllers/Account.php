<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Account: My Profile (read-only) + Settings (change password)
 */
class Account extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        check_login();
        $this->load->library('session');
        $this->load->database();
        $this->load->model('User_model');
    }

    /**
     * My Profile — read-only display of current user info.
     */
    public function profile()
    {
        $user_id = $this->session->userdata('user_id');

        $user = $this->db
            ->select('u.id, u.fullname, u.username, u.email, u.departemen, u.tanggal_masuk, u.last_login, u.created_at, r.role_name, COALESCE(cc.description, u.departemen) AS department_name')
            ->from('users u')
            ->join('roles r', 'r.id = u.role_id', 'left')
            ->join("common_code cc", "cc.code = u.departemen AND cc.group_code = 'Department'", 'left')
            ->where('u.id', $user_id)
            ->get()->row();

        if (!$user) {
            show_error('User tidak ditemukan.');
            return;
        }
        // Tampilkan semua role user (Role Level 2026-09-15), bukan cuma role utama.
        if (current_role_names()) $user->role_name = implode(', ', current_role_names());

        $data = [
            'title'     => lang_line('title_acc_profile'),
            'username'  => $this->session->userdata('fullname'),
            'role_name' => $this->db->get_where('roles', ['id' => $this->session->userdata('role_id')])->row('role_name'),
            'user'      => $user,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('account/profile', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Settings — currently only Change Password.
     */
    public function settings()
    {
        $data = [
            'title'     => lang_line('title_acc_settings'),
            'username'  => $this->session->userdata('fullname'),
            'role_name' => $this->db->get_where('roles', ['id' => $this->session->userdata('role_id')])->row('role_name'),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('account/settings', $data);
        $this->load->view('templates/footer');
    }

    /**
     * POST handler: update password (requires current password).
     * Responds with JSON when called via AJAX, otherwise falls back to redirect+flash.
     */
    public function update_password()
    {
        $is_ajax = $this->input->is_ajax_request();

        if (strtolower($this->input->method()) !== 'post') {
            return $this->_pw_response($is_ajax, false, 'Metode tidak valid.');
        }

        $user_id          = (int) $this->session->userdata('user_id');
        $current_password = (string) $this->input->post('current_password');
        $new_password     = (string) $this->input->post('new_password');
        $confirm_password = (string) $this->input->post('confirm_password');

        log_message('debug', "[Account/update_password] user_id={$user_id}");

        if (!$user_id) {
            return $this->_pw_response($is_ajax, false, 'Session tidak valid. Silakan login ulang.');
        }

        if ($current_password === '' || $new_password === '' || $confirm_password === '') {
            return $this->_pw_response($is_ajax, false, 'Semua field password harus diisi.');
        }

        if ($new_password !== $confirm_password) {
            return $this->_pw_response($is_ajax, false, 'Password baru dan konfirmasi tidak cocok.');
        }

        if (strlen($new_password) < 6) {
            return $this->_pw_response($is_ajax, false, 'Password baru minimal 6 karakter.');
        }

        $user = $this->db->select('password')->where('id', $user_id)->get('users')->row();
        if (!$user || empty($user->password)) {
            log_message('error', "[Account/update_password] user not found / no pw, user_id={$user_id}");
            return $this->_pw_response($is_ajax, false, 'Akun tidak ditemukan.');
        }

        // STRICT: must verify current password before any change.
        if (!password_verify($current_password, $user->password)) {
            log_message('debug', "[Account/update_password] wrong current pw user_id={$user_id}");
            return $this->_pw_response($is_ajax, false, 'Password saat ini salah.');
        }

        if (password_verify($new_password, $user->password)) {
            return $this->_pw_response($is_ajax, false, 'Password baru tidak boleh sama dengan password lama.');
        }

        $updated = $this->User_model->update($user_id, [
            'password' => password_hash($new_password, PASSWORD_DEFAULT),
        ]);

        if (!$updated) {
            log_message('error', "[Account/update_password] DB update failed user_id={$user_id}");
            return $this->_pw_response($is_ajax, false, 'Gagal menyimpan password. Silakan coba lagi.');
        }

        log_message('debug', "[Account/update_password] password updated user_id={$user_id}");
        return $this->_pw_response($is_ajax, true, 'Password berhasil diubah.');
    }

    /**
     * Send the password-update response: JSON for AJAX, otherwise redirect+flash.
     */
    private function _pw_response($is_ajax, $success, $message)
    {
        if ($is_ajax) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status'  => (bool) $success,
                    'message' => $message,
                ]));
            return;
        }
        $this->session->set_flashdata($success ? 'success' : 'error', $message);
        redirect('account/settings');
    }
}
