<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * User Management.
 *
 * Rebrand Acropoly 2026-11-05 (user: "approval user baru itu hapus aja, lalu
 * tipe user executive itu hilangkan aja"):
 *  - Tambah user LANGSUNG AKTIF & bisa login (dulu masuk antrian
 *    `user_pending_changes` dan harus disetujui Accounting / Direktur).
 *  - Tipe user Executive DIHAPUS -> tinggal super_admin, accounting, user.
 *  - Edit user langsung berlaku (sudah begitu sejak 2026-09-09); jejaknya di
 *    Log Activity.
 * Role ASLI (`role_id` / user_roles) tidak ada hubungannya dgn `user_type` --
 * keduanya independen.
 */
class User extends MY_Controller {

    const USER_TYPES = ['super_admin', 'accounting', 'user'];

    /** Password awal user baru / hasil reset (wajib diganti saat login pertama). Menggantikan password default lama era NSI. */
    const DEFAULT_PASSWORD = 'ACRO123';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('User_model');
        $this->load->model('Role_model');
        $this->load->library('session');
    }

    public function index() {
        if (!check_access('user', 'view')) show_error('Anda tidak memiliki akses.', 403);

        $data['title'] = lang_line('title_usr_index');
        $data['users'] = $this->User_model->get_all();
        // Role aktif lengkap dgn departemen & posisi, urut departemen -> posisi (Role Level 2026-09-15).
        $data['roles'] = array_values(array_filter($this->Role_model->get_all_roles(), function ($r) { return $r->status === 'active'; }));
        $data['user_roles_map'] = $this->User_model->get_user_roles_map();
        $data['departments'] = $this->User_model->get_departments();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('user/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('user', 'add')) show_error('Anda tidak memiliki akses.', 403);

        $username = $this->input->post('username');
        $email = $this->input->post('email');

        if ($this->User_model->check_username_exists($username)) {
            $this->session->set_flashdata('error', 'Username "' . $username . '" sudah digunakan. Silakan pilih username lain.');
            redirect('user');
            return;
        }
        if (!empty($email) && $this->User_model->check_email_exists($email)) {
            $this->session->set_flashdata('error', 'Email "' . $email . '" sudah digunakan. Silakan gunakan email lain.');
            redirect('user');
            return;
        }

        $user_type = $this->input->post('user_type');
        if (!in_array($user_type, self::USER_TYPES, true)) $user_type = 'user';

        $role_ids = $this->_posted_role_ids();
        if (empty($role_ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal satu role untuk user ini.');
            redirect('user');
            return;
        }

        $default_password = self::DEFAULT_PASSWORD;
        $fullname = $this->input->post('fullname');

        $data = [
            'fullname'        => $fullname,
            'username'        => $username,
            'password'        => password_hash($default_password, PASSWORD_DEFAULT),
            'email'           => $email,
            'role_id'         => $role_ids[0],
            'user_type'       => $user_type,
            'approval_status' => 'approved', // tanpa approval (2026-11-05)
            'departemen'      => $this->input->post('departemen'),
            'tanggal_masuk'   => $this->input->post('tanggal_masuk'),
            'status'          => 'active',
            'first_login'     => 1,
            'created_at'      => date('Y-m-d H:i:s')
        ];

        $this->User_model->insert($data);
        $new_id = $this->db->insert_id();
        $this->User_model->set_roles($new_id, $role_ids);
        add_log('CREATE', 'User', 'Tambah user "' . $fullname . '" (ID ' . (int) $new_id . ', tipe ' . $user_type . ').');

        $this->session->set_flashdata('success', 'User baru berhasil dibuat dan langsung bisa login (password default: ' . self::DEFAULT_PASSWORD . ', wajib diganti saat login pertama).');
        redirect('user');
    }

    public function update($id) {
        if (!check_access('user', 'edit')) show_error('Anda tidak memiliki akses.', 403);

        $target = $this->User_model->get_by_id($id);
        if (!$target) show_404();

        $username = $this->input->post('username');
        $email = $this->input->post('email');

        if ($this->User_model->check_username_exists($username, $id)) {
            $this->session->set_flashdata('error', 'Username "' . $username . '" sudah digunakan oleh user lain. Silakan pilih username lain.');
            redirect('user');
            return;
        }
        if (!empty($email) && $this->User_model->check_email_exists($email, $id)) {
            $this->session->set_flashdata('error', 'Email "' . $email . '" sudah digunakan oleh user lain. Silakan gunakan email lain.');
            redirect('user');
            return;
        }

        $user_type = $this->input->post('user_type');
        if (!in_array($user_type, self::USER_TYPES, true)) {
            $user_type = in_array($target->user_type, self::USER_TYPES, true) ? $target->user_type : 'user';
        }

        $role_ids = $this->_posted_role_ids();
        if (empty($role_ids)) {
            $this->session->set_flashdata('error', 'Pilih minimal satu role untuk user ini.');
            redirect('user');
            return;
        }

        $proposed = [
            'fullname'      => $this->input->post('fullname'),
            'username'      => $username,
            'email'         => $email,
            'user_type'     => $user_type,
            'departemen'    => $this->input->post('departemen'),
            'tanggal_masuk' => $this->input->post('tanggal_masuk'),
            'status'        => $this->input->post('status'),
        ];

        // Edit user langsung berlaku (tanpa approval). Jejak audit di Log Activity.
        $this->User_model->update($id, $proposed);
        $this->User_model->set_roles($id, $role_ids);
        add_log('UPDATE', 'User', 'Edit data user "' . $target->fullname . '" (ID ' . (int) $id . ').');

        $this->session->set_flashdata('success', 'Perubahan data user berhasil disimpan.');
        redirect('user');
    }

    public function delete($id) {
        if (!check_access('user', 'delete')) show_error('Anda tidak memiliki akses.', 403);

        if ($id == $this->session->userdata('user_id')) {
            $this->session->set_flashdata('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
            redirect('user');
            return;
        }

        // Nonaktifkan langsung (bukan lewat alur approval) — approval cuma
        // utk CREATE/EDIT data sesuai dokumen, nonaktifkan/hapus akun tetap
        // wewenang langsung siapa pun yg py permission 'user.delete'.
        $this->User_model->update($id, ['status' => 'inactive']);
        $this->session->set_flashdata('success', 'User berhasil di-nonaktifkan.');
        redirect('user');
    }

    public function reset_password($id) {
        if (!check_access('user', 'edit')) show_error('Anda tidak memiliki akses.', 403);
        $new_pass = password_hash(self::DEFAULT_PASSWORD, PASSWORD_DEFAULT);
        $this->User_model->update($id, [
            'password'    => $new_pass,
            'first_login' => 1
        ]);
        $this->session->set_flashdata('success', 'Password berhasil direset ke ' . self::DEFAULT_PASSWORD . '. User akan diminta membuat password baru saat login.');
        redirect('user');
    }

    public function upload_signature($id) {
        if (!check_access('user', 'edit')) show_error('Anda tidak memiliki akses.', 403);
        header('Content-Type: application/json');

        if (!isset($_FILES['signature']) || $_FILES['signature']['error'] != 0) {
            $err_code = $_FILES['signature']['error'] ?? 'no file';
            log_message('error', "[upload_signature] no file or error code: " . print_r($err_code, true));
            echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau terjadi error (code: ' . $err_code . ').']);
            return;
        }

        $file = $_FILES['signature'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Tipe file harus berupa gambar (JPG, PNG, GIF, WebP). Diterima: ' . $file['type']]);
            return;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar (max 2MB).']);
            return;
        }

        $upload_dir = FCPATH . 'uploads/signatures/';
        if (!is_dir($upload_dir)) {
            if (!@mkdir($upload_dir, 0777, true)) {
                log_message('error', "[upload_signature] mkdir failed: $upload_dir");
                echo json_encode(['success' => false, 'message' => 'Gagal membuat folder upload. Hubungi admin.']);
                return;
            }
        }
        if (!is_writable($upload_dir)) {
            log_message('error', "[upload_signature] dir not writable: $upload_dir");
            echo json_encode(['success' => false, 'message' => 'Folder upload tidak bisa ditulis. Hubungi admin (perm: ' . substr(sprintf('%o', fileperms($upload_dir)), -4) . ').']);
            return;
        }

        $user = $this->User_model->get_by_id($id);
        if ($user && $user->signature_path && file_exists(FCPATH . $user->signature_path)) {
            @unlink(FCPATH . $user->signature_path);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png');
        $filename = 'user_' . $id . '_' . time() . '.' . $ext;
        $filepath = $upload_dir . $filename;
        $relative_path = 'uploads/signatures/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->User_model->update($id, ['signature_path' => $relative_path]);
            echo json_encode(['success' => true, 'message' => 'Tanda tangan berhasil diunggah.', 'path' => $relative_path]);
        } else {
            $err = error_get_last();
            log_message('error', "[upload_signature] move_uploaded_file failed -> $filepath | last_error: " . print_r($err, true));
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file: ' . ($err['message'] ?? 'unknown')]);
        }
    }

    public function delete_signature($id) {
        if (!check_access('user', 'edit')) show_error('Anda tidak memiliki akses.', 403);

        $user = $this->User_model->get_by_id($id);
        if ($user && $user->signature_path && file_exists(FCPATH . $user->signature_path)) {
            unlink(FCPATH . $user->signature_path);
        }

        $this->User_model->update($id, ['signature_path' => NULL]);
        echo json_encode(['success' => true, 'message' => 'Tanda tangan berhasil dihapus.']);
    }

    /** role_ids[] dari form (checkbox), hanya role aktif yang valid. */
    private function _posted_role_ids() {
        return $this->User_model->filter_valid_role_ids((array) $this->input->post('role_ids'));
    }
}
