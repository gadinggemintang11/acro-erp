<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Document Sharing (fitur baru, permintaan user 2026-09-04) — menu upload
 * dokumen dgn folder per departemen + akses per-file (private/roles).
 * Lihat memory document_sharing_feature.md utk detail desain lengkap.
 */
class Document_sharing_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Daftar folder = daftar departemen (reuse master data yang SUDAH ADA
     * di common_code, dipakai jg oleh roles.department dkk — TIDAK bikin
     * tabel folder baru).
     */
    public function get_departments() {
        return $this->db->select('code, description')
            ->from('common_code')
            ->where('group_code', 'Department')
            ->order_by('description', 'ASC')
            ->get()->result();
    }

    public function get_department_by_code($code) {
        return $this->db->select('code, description')
            ->from('common_code')
            ->where('group_code', 'Department')
            ->where('code', $code)
            ->get()->row();
    }

    public function count_documents_by_department() {
        $rows = $this->db->select('department_code, COUNT(*) as total')
            ->from('doc_documents')
            ->group_by('department_code')
            ->get()->result();
        $map = [];
        foreach ($rows as $r) { $map[$r->department_code] = (int) $r->total; }
        return $map;
    }

    public function get_all_roles() {
        return $this->db->select('id, role_name, department')
            ->from('roles')
            ->where('status', 'active')
            ->order_by('department', 'ASC')
            ->order_by('role_name', 'ASC')
            ->get()->result();
    }

    /**
     * Semua dokumen di 1 folder (departemen) yang BOLEH DILIHAT user ini —
     * filter visibilitas dilakukan di PHP (bukan SQL) supaya logika
     * can_user_view_document() cuma ada di SATU tempat (dipakai lagi utk
     * cek view/download individual).
     */
    public function get_documents_for_folder($department_code, $user_id, $role_id, $is_admin, $search = null) {
        $this->db->select("d.*, u.fullname as uploader_name,
            (SELECT GROUP_CONCAT(r.role_name SEPARATOR ', ')
                FROM doc_document_roles dr JOIN roles r ON r.id = dr.role_id
                WHERE dr.document_id = d.id) as role_names", FALSE)
            ->from('doc_documents d')
            ->join('users u', 'u.id = d.uploaded_by', 'left')
            ->where('d.department_code', $department_code)
            ->order_by('d.uploaded_at', 'DESC');
        if (!empty($search)) {
            $this->db->group_start()
                ->like('d.title', $search)
                ->or_like('d.original_name', $search)
                ->group_end();
        }
        $rows = $this->db->get()->result();

        if ($is_admin) {
            return $rows; // Admin (is_admin(), role_id=1) lihat semua — konsisten pola override admin yang sudah ada di app ini.
        }

        $visible = [];
        foreach ($rows as $r) {
            if ($this->can_view_document($r, $user_id, $role_id, $is_admin)) {
                $visible[] = $r;
            }
        }
        return $visible;
    }

    /**
     * Aturan akses per-dokumen — SATU-SATUNYA tempat logika ini boleh
     * ditulis (dipanggil dari get_documents_for_folder() & controller
     * view()/download()/delete() supaya konsisten):
     *   - Admin (is_admin()) atau uploader sendiri -> selalu boleh.
     *   - visibility='roles' -> boleh kalau role user ADA di doc_document_roles.
     *   - visibility='private' & bukan uploader/admin -> TIDAK boleh.
     */
    public function can_view_document($doc, $user_id, $role_id, $is_admin) {
        if ($is_admin) return true;
        if ($doc->uploaded_by && (int) $doc->uploaded_by === (int) $user_id) return true;
        if ($doc->visibility === 'roles') {
            // $role_id boleh 1 id atau array semua role user.
            $ids = array_filter(array_map('intval', (array) $role_id));
            if (empty($ids)) return false;
            return $this->db->where('document_id', $doc->id)->where_in('role_id', $ids)
                ->count_all_results('doc_document_roles') > 0;
        }
        return false; // private & bukan pemilik/admin
    }

    public function can_manage_document($doc, $user_id, $is_admin) {
        if ($is_admin) return true;
        return $doc->uploaded_by && (int) $doc->uploaded_by === (int) $user_id;
    }

    public function get_document_by_id($id) {
        return $this->db->select('d.*, u.fullname as uploader_name')
            ->from('doc_documents d')
            ->join('users u', 'u.id = d.uploaded_by', 'left')
            ->where('d.id', $id)
            ->get()->row();
    }

    public function get_role_ids_for_document($document_id) {
        $rows = $this->db->select('role_id')->from('doc_document_roles')->where('document_id', $document_id)->get()->result();
        return array_map(function($r) { return (int) $r->role_id; }, $rows);
    }

    public function insert_document($data, $role_ids = []) {
        $this->db->trans_start();
        $this->db->insert('doc_documents', $data);
        $doc_id = $this->db->insert_id();

        if (!empty($role_ids) && $doc_id) {
            $batch = [];
            foreach (array_unique(array_map('intval', $role_ids)) as $rid) {
                if ($rid > 0) $batch[] = ['document_id' => $doc_id, 'role_id' => $rid];
            }
            if (!empty($batch)) $this->db->insert_batch('doc_document_roles', $batch);
        }

        $this->db->trans_complete();
        return $this->db->trans_status() ? $doc_id : false;
    }

    public function delete_document($id) {
        // doc_document_roles ikut kehapus otomatis (FK ON DELETE CASCADE).
        return $this->db->where('id', $id)->delete('doc_documents');
    }
}
