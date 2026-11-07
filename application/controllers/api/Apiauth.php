<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ============================================================
 * API: AUTH (mobile approval)
 * ------------------------------------------------------------
 * POST api/login      {username, password, device_name?} → token + user + permissions
 * GET  api/me         (Bearer token) → user + permissions
 * POST api/logout     (Bearer token) → hapus token
 * POST api/fcm-token  (Bearer token) {fcm_token} → simpan FCM token device
 *
 * Routing ramah ada di config/routes.php (blok Mobile Approval API).
 * ============================================================
 */
class Apiauth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library(['session', 'api_auth']);
    }

    // ---------- helpers ----------

    private function _body() {
        $raw  = $this->input->raw_input_stream;
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $this->input->post();
    }

    private function _json($payload, $code = 200) {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function _user_payload($user) {
        return [
            'id'         => (int) $user->id,
            'username'   => $user->username,
            'fullname'   => $user->fullname,
            'role_id'    => (int) $user->role_id,
            'role_name'  => isset($user->role_name) ? $user->role_name : null,
            'departemen' => $user->departemen,
        ];
    }

    // ---------- endpoints ----------

    public function login() {
        if (strtolower($this->input->method()) !== 'post') {
            return $this->_json(['success' => false, 'message' => 'Gunakan method POST.'], 405);
        }

        $b        = $this->_body();
        $username = trim(isset($b['username']) ? $b['username'] : '');
        $password = isset($b['password']) ? $b['password'] : '';

        if ($username === '' || $password === '') {
            return $this->_json(['success' => false, 'message' => 'Username dan password wajib diisi.'], 400);
        }

        $user = $this->db->get_where('users', ['username' => $username])->row();

        if (!$user || !password_verify($password, $user->password)) {
            return $this->_json(['success' => false, 'message' => 'Username atau password salah.'], 401);
        }
        if ($user->status === 'inactive') {
            return $this->_json(['success' => false, 'message' => 'Akun Anda sudah tidak aktif. Hubungi administrator.'], 403);
        }

        $role = $this->db->get_where('roles', ['id' => $user->role_id])->row();
        $user->role_name = $role ? $role->role_name : null;

        $token = $this->api_auth->issue_token(
            $user->id,
            isset($b['device_name']) ? $b['device_name'] : null
        );

        return $this->_json([
            'success' => true,
            'data'    => [
                'token'       => $token,
                'user'        => $this->_user_payload($user),
                'permissions' => $this->api_auth->approval_permissions(user_role_ids($user->id)),
            ],
        ]);
    }

    public function me() {
        $user = $this->api_auth->user();
        if (!$user) {
            return $this->_json(['success' => false, 'message' => 'Sesi tidak valid. Silakan login ulang.'], 401);
        }
        return $this->_json([
            'success' => true,
            'data'    => [
                'user'        => $this->_user_payload($user),
                'permissions' => $this->api_auth->approval_permissions(user_role_ids($user->id)),
            ],
        ]);
    }

    public function logout() {
        $user = $this->api_auth->user();
        if (!$user) {
            return $this->_json(['success' => false, 'message' => 'Sesi tidak valid.'], 401);
        }
        $this->api_auth->revoke_current();
        return $this->_json(['success' => true, 'message' => 'Berhasil logout.']);
    }

    public function fcm_token() {
        $user = $this->api_auth->user();
        if (!$user) {
            return $this->_json(['success' => false, 'message' => 'Sesi tidak valid.'], 401);
        }
        $b   = $this->_body();
        $fcm = isset($b['fcm_token']) ? trim($b['fcm_token']) : '';
        if ($fcm === '') {
            return $this->_json(['success' => false, 'message' => 'fcm_token wajib diisi.'], 400);
        }
        $this->api_auth->save_fcm_token($fcm);
        return $this->_json(['success' => true, 'message' => 'FCM token tersimpan.']);
    }
}
