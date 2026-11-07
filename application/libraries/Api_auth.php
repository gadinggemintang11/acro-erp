<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * ============================================================
 * API_AUTH — Autentikasi token untuk REST API mobile approval
 * ------------------------------------------------------------
 * Dipakai oleh controller di application/controllers/api/.
 * Token dikirim client via header "Authorization: Bearer <token>"
 * (fallback: header X-Api-Token atau parameter ?token=).
 *
 * Setelah token valid, session userdata diisi seperti login web
 * (user_id, role_id, dst) supaya check_access() dan model approval
 * existing yang membaca session jalan tanpa perubahan apa pun.
 *
 * Token disimpan sebagai SHA-256 hash di tabel api_tokens —
 * token asli hanya diketahui device. Login mobile TIDAK menyentuh
 * users.session_token, jadi tidak menendang sesi web user.
 * ============================================================
 */
class Api_auth {

    const TOKEN_TTL = 2592000; // 30 hari, sliding (diperpanjang tiap dipakai)

    protected $CI;
    protected $current_token_id = null;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
        if (!isset($this->CI->session)) {
            $this->CI->load->library('session');
        }
    }

    /** Ambil token dari request (Bearer / X-Api-Token / ?token=). */
    public function get_token() {
        $hdr = $this->CI->input->get_request_header('Authorization', TRUE);
        if ($hdr && stripos($hdr, 'Bearer ') === 0) {
            return trim(substr($hdr, 7));
        }
        $alt = $this->CI->input->get_request_header('X-Api-Token', TRUE);
        if ($alt) return trim($alt);
        return $this->CI->input->get_post('token');
    }

    /**
     * Validasi token. Bila valid: isi session userdata (impersonate
     * seperti login web) dan return object user (+role_name, token_id).
     * Bila tidak valid: return NULL.
     */
    public function user() {
        $token = $this->get_token();
        if (!$token) return null;

        $hash = hash('sha256', $token);
        $row = $this->CI->db
            ->select('t.id AS token_id, t.expires_at AS token_expires_at, u.*')
            ->from('api_tokens t')
            ->join('users u', 't.user_id = u.id', 'inner')
            ->where('t.token_hash', $hash)
            ->get()->row();

        if (!$row) return null;
        if ($row->status !== 'active') return null;
        if (strtotime($row->token_expires_at) < time()) {
            $this->CI->db->where('id', $row->token_id)->delete('api_tokens');
            return null;
        }

        // Sliding expiration + jejak pemakaian terakhir.
        $this->CI->db->where('id', $row->token_id)->update('api_tokens', [
            'last_used_at' => date('Y-m-d H:i:s'),
            'expires_at'   => date('Y-m-d H:i:s', time() + self::TOKEN_TTL),
        ]);

        $role = $this->CI->db->get_where('roles', ['id' => $row->role_id])->row();
        $row->role_name = $role ? $role->role_name : null;

        // Impersonate session web supaya check_access() & model existing jalan.
        // Sengaja tanpa 'session_token' — itu khusus proteksi single-device web.
        $this->CI->session->set_userdata([
            'user_id'    => $row->id,
            'username'   => $row->username,
            'fullname'   => $row->fullname,
            'role_id'    => $row->role_id,
            'role_name'  => $row->role_name,
            'departemen' => $row->departemen,
            'logged_in'  => true,
        ]);

        $this->current_token_id = (int) $row->token_id;
        return $row;
    }

    /** Terbitkan token baru untuk user (dipanggil saat login mobile). */
    public function issue_token($user_id, $device_name = null) {
        $token = bin2hex(random_bytes(32));
        $this->CI->db->insert('api_tokens', [
            'user_id'     => (int) $user_id,
            'token_hash'  => hash('sha256', $token),
            'device_name' => $device_name !== null && $device_name !== ''
                ? substr((string) $device_name, 0, 150) : null,
            'created_at'  => date('Y-m-d H:i:s'),
            'expires_at'  => date('Y-m-d H:i:s', time() + self::TOKEN_TTL),
        ]);
        return $token;
    }

    /** Hapus token yang sedang dipakai request ini (logout mobile). */
    public function revoke_current() {
        if ($this->current_token_id) {
            $this->CI->db->where('id', $this->current_token_id)->delete('api_tokens');
            $this->current_token_id = null;
            return true;
        }
        return false;
    }

    /** Simpan FCM token device (untuk push notification ke depannya). */
    public function save_fcm_token($fcm_token) {
        if (!$this->current_token_id) return false;
        $this->CI->db->where('id', $this->current_token_id)->update('api_tokens', [
            'fcm_token' => substr((string) $fcm_token, 0, 512),
        ]);
        return true;
    }

    /**
     * Matrix permission approval per modul untuk role user.
     * Bentuk: { menu_key: {view, head_dept, endorsement, approval} }
     * OR-merge bila ada baris permission duplikat (mirror check_access()).
     *
     * @param int|int[] $role_id satu role, atau semua role user (Role Level 2026-09-15)
     */
    public function approval_permissions($role_id) {
        $role_ids = array_values(array_filter(array_map('intval', (array) $role_id)));
        if (empty($role_ids)) $role_ids = [0];
        $keys = [
            'purchase_order_approval',
            'purchase_order_approval_general',
            'consumable_request_approval',
            'prf_approval',
            'erf_approval',
        ];

        $out = [];
        foreach ($keys as $k) {
            $out[$k] = ['view' => false, 'head_dept' => false, 'endorsement' => false, 'approval' => false];
        }

        $rows = $this->CI->db
            ->select('m.menu_key, rp.can_view, rp.can_head_dept, rp.can_endorsement, rp.can_approval')
            ->from('role_permissions rp')
            ->join('menus m', 'rp.menu_id = m.id', 'inner')
            ->where_in('rp.role_id', $role_ids)
            ->where_in('m.menu_key', $keys)
            ->get()->result();

        foreach ($rows as $r) {
            $k = strtolower($r->menu_key);
            if (!isset($out[$k])) continue;
            $out[$k]['view']        = $out[$k]['view']        || $r->can_view == 1;
            $out[$k]['head_dept']   = $out[$k]['head_dept']   || $r->can_head_dept == 1;
            $out[$k]['endorsement'] = $out[$k]['endorsement'] || $r->can_endorsement == 1;
            $out[$k]['approval']    = $out[$k]['approval']    || $r->can_approval == 1;
        }
        return $out;
    }
}
