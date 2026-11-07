<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notifikasi topbar per-event (ERP Revision Notes, [[erp_revision_cross_cutting]]).
 *
 * REVISI 2026-09-03: sebelumnya method2 di bawah ini baca dari `user_logs`
 * (feed generik CRUD, difilter per role, "mark as read" cuma no-op krn
 * user_logs tidak punya kolom is_read). Sekarang baca/tulis ke tabel
 * `notifications` (db/migration_notifications.sql) yang memang didesain
 * utk ini — 1 baris = 1 notifikasi bertarget ke 1 user, is_read beneran,
 * link beneran bisa diklik. Dibuat via MY_Controller::notify_users() dari
 * titik-titik trigger approval (submit/approve stage/back-to-draft/dst).
 *
 * Method lama yang basis user_logs (module_alias, allowed_module_labels_for_role,
 * get_important_notifications) DIHAPUS krn sudah tidak dipakai jalur manapun
 * (dicek: cuma Notification.php controller yang pakai model ini, method2
 * lama itu cuma dipanggil dari get_notifications/count_unread lama yang
 * sekarang sudah diganti).
 */
class Notification_model extends CI_Model {

    private $table = 'notifications';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Buat 1 notifikasi baru. Dipanggil dari MY_Controller::notify_users().
     */
    public function create($data) {
        $insert = [
            'user_id'       => (int) ($data['user_id'] ?? 0),
            'title'         => $data['title'] ?? '',
            'message'       => $data['message'] ?? null,
            'type'          => $data['type'] ?? 'info',
            'icon'          => $data['icon'] ?? 'fa-bell',
            'link'          => $data['link'] ?? null,
            'source_module' => $data['source_module'] ?? null,
            'source_id'     => isset($data['source_id']) ? (int) $data['source_id'] : null,
            'is_read'       => 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ];
        if ($insert['user_id'] <= 0 || $insert['title'] === '') return false;

        $this->db->insert($this->table, $insert);
        return $this->db->insert_id();
    }

    /**
     * Daftar notifikasi milik 1 user, terbaru dulu.
     */
    public function get_notifications($user_id = null, $limit = 10, $unread_only = false) {
        if (empty($user_id)) return [];

        $this->db->reset_query();
        $this->db->where('user_id', $user_id);
        if ($unread_only) {
            $this->db->where('is_read', 0);
        }
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        return $this->db->get($this->table)->result();
    }

    /**
     * Jumlah notifikasi BELUM DIBACA milik 1 user (utk badge count topbar).
     */
    public function count_unread($user_id = null) {
        if (empty($user_id)) return 0;

        $this->db->reset_query();
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        return $this->db->count_all_results($this->table);
    }

    public function mark_as_read($notification_id) {
        $this->db->where('id', (int) $notification_id);
        return $this->db->update($this->table, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function mark_all_as_read($user_id = null) {
        if (empty($user_id)) return false;

        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        return $this->db->update($this->table, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function delete_notification($notification_id) {
        $this->db->where('id', (int) $notification_id);
        return $this->db->delete($this->table);
    }

    /**
     * Housekeeping: hapus notifikasi lama (dipanggil manual/cron kalau perlu
     * — belum ada cron otomatis dibangun utk ini).
     */
    public function delete_old_notifications($days = 60) {
        $date = date('Y-m-d H:i:s', strtotime("-$days days"));
        $this->db->where('created_at <', $date);
        return $this->db->delete($this->table);
    }

    public function table_exists() {
        return $this->db->table_exists($this->table);
    }
}
