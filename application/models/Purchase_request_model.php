<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Purchase_request_model extends CI_Model {

    private $table_header  = 'pr_header';
    private $table_details = 'pr_details';
    private $table_jasa    = 'jasa';
    private $table_asset   = 'assets';

    public function __construct() {
        parent::__construct();
    }

    // ===================== HEADER =====================

    /**
     * Nomor PR: <KODE_DEPT><5 digit urut>, format mengikuti IRF/MRF.
     *
     * @param string|null $type      dipertahankan supaya pemanggil lama tidak rusak
     * @param string|null $dept_code departemen SUMBER dokumen (kode atau nama).
     *
     * $dept_code ditambahkan 2026-10-01: PR yang lahir dari outstanding MRF/IRF
     * dulu selalu menebak departemen dari USER yang menekan tombol, bukan dari
     * dokumen asalnya. Kalau kolom `users.departemen` user itu kosong -- dan
     * akun Admin memang sering kosong -- nomornya jatuh ke 'XXX'. Dilaporkan
     * user: MRF-nya ACC00003 tapi PR-nya jadi XXX00004. Dokumen asalnya sudah
     * menyimpan departemennya sendiri, jadi itu yang dipakai; user hanya
     * dipakai sebagai cadangan kalau sumbernya memang tidak punya.
     */
    public function generate_pr_number($type = null, $dept_code = null) {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        // $type & $dept_code tidak dipakai lagi: semua tipe PR berbagi 1 urutan.
        return doc_number_next('purchase_request', 'pr_header', 'pr_number');
    }

    public function get_all($filters = []) {
        $this->db->reset_query();
        $this->db->select('
            h.*,
            s.nama_supplier,
            u.fullname as requester_name,
            u.signature_path as requester_signature,
            approver.fullname as approver_name,
            approver.signature_path as approver_signature
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s',       'h.supplier_id = s.id',       'left');
        $this->db->join('users u',           'h.requester_id = u.id',      'left');
        $this->db->join('users approver',    'h.approved_by = approver.id','left');

        if (!empty($filters['status'])) {
            $this->db->where('h.status', $filters['status']);
        }
        if (!empty($filters['type'])) {
            // Array = gabungan beberapa type sekaligus (dipakai listing gabungan
            // General+Jasa+Asset — lihat Purchase_request_general::_included_types(),
            // pola sama spt get_all_monitoring() di bawah).
            if (is_array($filters['type'])) {
                $this->db->where_in('h.type', $filters['type']);
            } else {
                $this->db->where('h.type', $filters['type']);
            }
        }
        if (!empty($filters['requester_id'])) {
            $this->db->group_start()
                ->where('h.requester_id', $filters['requester_id'])
                ->or_where('h.created_by', $filters['requester_id'])
                ->group_end();
        }
        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('h.pr_number', $filters['search'])
                ->or_like('s.nama_supplier', $filters['search'])
                ->or_like('h.purpose', $filters['search'])
                ->group_end();
        }

        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id) {
        $this->db->reset_query();
        $this->db->select('
            h.*,
            s.nama_supplier,
            u.fullname as requester_name,
            u.signature_path as requester_signature,
            approver.fullname as approver_name,
            approver.signature_path as approver_signature,
            uh.fullname  as hod_approver_name,
            uh.signature_path as hod_approver_signature,
            ue.fullname  as endorser_name,
            ue.signature_path as endorser_signature,
            uf.fullname  as final_approver_name,
            uf.signature_path as final_approver_signature,
            ur.fullname  as rejecter_name,
            urtd.fullname as returner_name
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s',    'h.supplier_id = s.id',       'left');
        $this->db->join('users u',        'h.requester_id = u.id',      'left');
        $this->db->join('users approver', 'h.approved_by = approver.id','left');
        $this->db->join('users uh',       'h.hod_approved_by = uh.id',  'left');
        $this->db->join('users ue',       'h.endorsed_by = ue.id',      'left');
        $this->db->join('users uf',       'h.final_approved_by = uf.id','left');
        $this->db->join('users ur',       'h.rejected_by = ur.id',      'left');
        $this->db->join('users urtd',     'h.returned_to_draft_by = urtd.id','left');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        if (empty($data['status'])) $data['status'] = 'Draft';
        if (empty($data['type'])) $data['type'] = 'produksi';
        if (empty($data['pr_number'])) {
            $data['pr_number'] = $this->generate_pr_number($data['type']);
        }
        $this->db->reset_query();
        return $this->db->insert($this->table_header, $data) ? $this->db->insert_id() : false;
    }

    /**
     * @param bool $allow_non_draft TRUE cuma dipakai mode edit Admin (buka kunci
     *        PR yang sudah lewat Draft). Status & approval tetap tidak disentuh
     *        karena kolomnya sudah di-unset di atas.
     */
    public function update_header($id, $data, $allow_non_draft = FALSE) {
        unset($data['status'], $data['approved_by'], $data['approved_at'], $data['requester_id'], $data['pr_number']);
        $this->db->reset_query();
        $this->db->where('id', $id);
        if (!$allow_non_draft) $this->db->where('status', 'Draft');
        return $this->db->update($this->table_header, $data);
    }

    public function delete_header($id, $admin_override = FALSE) {
        // Admin override: hapus PR di status apa pun beserta child rows (details +
        // ipo refs). Diblok kalau PR sudah dikonversi ke PO supaya PO tidak orphan.
        if ($admin_override) {
            if ($this->has_linked_po($id)) return false;

            $this->db->reset_query();
            $detail_ids = $this->db->select('id')
                                   ->where('pr_header_id', $id)
                                   ->get($this->table_details)->result();
            foreach ($detail_ids as $d) {
                $this->db->reset_query();
                $this->db->where('pr_detail_id', $d->id)->delete('pr_detail_ipo_refs');
            }
            $this->db->reset_query();
            $this->db->where('pr_header_id', $id)->delete($this->table_details);
            $this->db->reset_query();
            $this->db->where('id', $id)->delete($this->table_header);
            return $this->db->affected_rows() > 0;
        }

        // Default (non-admin): hanya status Draft & tidak punya item.
        $this->db->reset_query();
        $count = $this->db->where('pr_header_id', $id)->count_all_results($this->table_details);
        if ($count > 0) return false;
        $this->db->reset_query();
        $this->db->where('id', $id)->where('status', 'Draft')->delete($this->table_header);
        return $this->db->affected_rows() > 0;
    }

    /**
     * TRUE kalau PR sudah pernah dikonversi ke (minimal satu) Purchase Order.
     * Dipakai untuk memblok force-delete admin supaya PO tidak jadi orphan.
     *
     * Dicek di dua tempat: po_header.pr_header_id (PR sumber utama) DAN
     * po_details.pr_header_id (PR yang ditarik sebagai sumber kedua ke PO
     * gabungan). Tanpa cek kedua, PR yang itemnya nebeng di PO orang lain
     * masih bisa dihapus dan meninggalkan baris PO yatim.
     */
    public function has_linked_po($id) {
        $this->db->reset_query();
        if ($this->db->where('pr_header_id', $id)->count_all_results('po_header') > 0) {
            return true;
        }
        $this->db->reset_query();
        return $this->db->where('pr_header_id', $id)->count_all_results('po_details') > 0;
    }

    public function update_status($id, $new_status, $user_id = null, $notes = null) {
        $this->db->reset_query();
        $data = ['status' => $new_status];

        if ($new_status === 'Submitted') {
            $now = date('Y-m-d H:i:s');
            $data['submitted_at'] = $now;
            $data['submitted_by'] = $user_id;

            // Direct-final requester (mis. Johanes) hanya berlaku utk PR Non-Produksi
            // yang memang 3-stage. PR Produksi single-stage (Submitted→Approved) jadi
            // tidak butuh skip & malah akan stuck kalau dilompatkan ke 'Endorsed'.
            // Dulu requester tertentu (user ID 37, aturan NSI) melompat ke
            // 'Endorsed' di sini. Sejak 2026-11-05 approval PR cuma 1 tahap
            // (Submitted -> Approved, seperti PO), jadi jalan pintas itu dihapus.
        } elseif ($new_status === 'Approved') {
            $data['approved_by'] = $user_id;
            $data['approved_at'] = date('Y-m-d H:i:s');
            $data['rejection_notes'] = null;
        } elseif ($new_status === 'Rejected') {
            $data['rejection_notes'] = $notes;
            $data['approved_by']     = $user_id;
            $data['approved_at']     = date('Y-m-d H:i:s');
        } elseif ($new_status === 'Draft') {
            $data['submitted_at']    = null;
            $data['submitted_by']    = null;
            $data['approved_by']     = null;
            $data['approved_at']     = null;
            $data['rejection_notes'] = null;
        }

        $this->db->where('id', $id);
        $this->db->update($this->table_header, $data);
        $ok = $this->db->affected_rows() > 0;

        // Riwayat approval (2026-09-06). Jalur 3-stage punya method sendiri yang
        // sudah nge-log; di sini yang tercatat: submit dari requestor, plus
        // approve/reject jalur 1-stage (PR Produksi & method legacy
        // approve_pr()/reject_pr()). Unsubmit ke Draft sengaja TIDAK dicatat —
        // itu aksi requestor membatalkan submit-nya sendiri, bukan approval.
        if ($ok && !empty($user_id)) {
            if ($new_status === 'Submitted')     $this->_log($id, 'Submit', $user_id);
            elseif ($new_status === 'Approved')  $this->_log($id, 'Approve Final', $user_id);
            elseif ($new_status === 'Rejected')  $this->_log($id, 'Reject', $user_id, $notes);
        }
        return $ok;
    }

    // ===================== 3-STAGE APPROVAL =====================
    // Workflow: Draft → Submitted → Approved Head Dept → Endorsed → Approved
    //         + Rejected (final) / Return to Draft (revisi creator)
    //
    // Catatan kompatibilitas:
    //   approve_final menyinkronkan kolom legacy `approved_by`/`approved_at`
    //   supaya kode lama yang membaca kolom itu tetap konsisten.

    /**
     * List PR (Non-Produksi) yang menunggu aksi approval user — pola mirror IRF
     * (Consumable_request_model::get_pending_approvals).
     *
     * Stage yang ditampilkan ditentukan oleh permission user:
     *   can_head_dept   → status 'Submitted'
     *   can_endorsement → status 'Approved Head Dept'
     *   can_approval    → status 'Endorsed'
     */
    public function get_pending_approvals($can_head_dept, $can_endorsement, $can_approval, $filters = []) {
        // Approval PR 1 tahap (2026-11-05, seperti PO): cukup izin 'approval'.
        // 'Approved Head Dept'/'Endorsed' tetap ikut supaya PR lama yang
        // terlanjur di tengah alur 3 tahap tidak nyangkut.
        $stages = $can_approval ? ['Submitted', 'Approved Head Dept', 'Endorsed'] : [];

        if (empty($stages)) return [];

        $this->db->reset_query();
        $this->db->select('
            h.*,
            s.nama_supplier,
            u.fullname as requester_name,
            u.signature_path as requester_signature,
            uh.fullname as hod_approver_name,
            ue.fullname as endorser_name,
            uf.fullname as final_approver_name
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s',  'h.supplier_id = s.id',          'left');
        $this->db->join('users u',      'h.requester_id = u.id',         'left');
        $this->db->join('users uh',     'h.hod_approved_by = uh.id',     'left');
        $this->db->join('users ue',     'h.endorsed_by = ue.id',         'left');
        $this->db->join('users uf',     'h.final_approved_by = uf.id',   'left');
        $this->db->where_in('h.status', $stages);
        // Array = gabungan General+Jasa+Asset (lihat catatan di get_all()).
        $type_filter = $filters['type'] ?? 'non_produksi';
        if (is_array($type_filter)) {
            $this->db->where_in('h.type', $type_filter);
        } else {
            $this->db->where('h.type', $type_filter);
        }

        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('h.pr_number', $filters['search'])
                ->or_like('s.nama_supplier', $filters['search'])
                ->or_like('h.purpose', $filters['search'])
                ->or_like('u.fullname', $filters['search'])
                ->group_end();
        }

        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function approve_hod($id, $user_id) {
        $header = $this->get_header_by_id($id);
        if (!$header || $header->status !== 'Submitted') {
            return ['success' => false, 'message' => 'PR hanya dapat di-approve Head of Dept jika status Submitted.'];
        }
        $now = date('Y-m-d H:i:s');
        $this->db->reset_query();
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'          => 'Approved Head Dept',
            'hod_approved_by' => $user_id,
            'hod_approved_at' => $now,
        ]);
        if ($ok) $this->_log($id, 'Approve HOD', $user_id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal approve Head of Dept.'];
    }

    public function approve_endorsement($id, $user_id) {
        $header = $this->get_header_by_id($id);
        if (!$header || $header->status !== 'Approved Head Dept') {
            return ['success' => false, 'message' => 'PR hanya dapat di-endorse jika sudah di-approve Head of Dept.'];
        }
        $now = date('Y-m-d H:i:s');
        $this->db->reset_query();
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'      => 'Endorsed',
            'endorsed_by' => $user_id,
            'endorsed_at' => $now,
        ]);
        if ($ok) $this->_log($id, 'Endorse', $user_id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal endorse.'];
    }

    public function approve_final($id, $user_id) {
        $header = $this->get_header_by_id($id);
        // 1 tahap: langsung dari Submitted. Status tengah alur lama tetap diterima.
        if (!$header || !in_array($header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'], true)) {
            return ['success' => false, 'message' => 'PR hanya dapat di-approve kalau statusnya Submitted.'];
        }
        $now = date('Y-m-d H:i:s');
        $this->db->reset_query();
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'            => 'Approved',
            'final_approved_by' => $user_id,
            'final_approved_at' => $now,
            'approved_by'       => $user_id,
            'approved_at'       => $now,
        ]);
        if ($ok) $this->_log($id, 'Approve Final', $user_id);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal approve final.'];
    }

    public function return_to_draft($id, $user_id, $reason, $admin_override = FALSE) {
        $header = $this->get_header_by_id($id);
        if (!$header) {
            return ['success' => false, 'message' => 'PR tidak ditemukan.'];
        }
        if ($header->status === 'Draft') {
            return ['success' => false, 'message' => 'PR sudah dalam status Draft.'];
        }
        // Alur normal hanya boleh return saat masih di tahap approval. Admin override
        // memperluas ke status apa pun (Rejected, Approved, dll) untuk koreksi data.
        $allowed = ['Submitted', 'Approved Head Dept', 'Endorsed'];
        if (!$admin_override && !in_array($header->status, $allowed)) {
            return ['success' => false, 'message' => 'PR tidak dalam tahap approval.'];
        }
        $now = date('Y-m-d H:i:s');
        $this->db->reset_query();
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'               => 'Draft',
            'returned_to_draft_by' => $user_id,
            'returned_to_draft_at' => $now,
            'return_reason'        => $reason,
            'submitted_by'         => NULL,
            'submitted_at'         => NULL,
            'hod_approved_by'      => NULL,
            'hod_approved_at'      => NULL,
            'endorsed_by'          => NULL,
            'endorsed_at'          => NULL,
            'final_approved_by'    => NULL,
            'final_approved_at'    => NULL,
            'approved_by'          => NULL,
            'approved_at'          => NULL,
            'rejected_by'          => NULL,
            'rejected_at'          => NULL,
            'reject_reason'        => NULL,
            'rejection_notes'      => NULL,
        ]);
        // Log ini yang membuka thread balasan di halaman detail (lihat
        // can_reply_return_thread() di auth_helper) — alasan return-nya jadi
        // pesan pertama, bukan cuma nempel di kolom header yang ketimpa terus.
        if ($ok) $this->_log($id, 'Return to Draft', $user_id, $reason);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal return ke Draft.'];
    }

    public function reject_3stage($id, $user_id, $reason) {
        $header = $this->get_header_by_id($id);
        if (!$header || !in_array($header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'])) {
            return ['success' => false, 'message' => 'PR tidak dalam tahap approval.'];
        }
        $now = date('Y-m-d H:i:s');
        $this->db->reset_query();
        $ok = $this->db->where('id', $id)->update($this->table_header, [
            'status'          => 'Rejected',
            'rejected_by'     => $user_id,
            'rejected_at'     => $now,
            'reject_reason'   => $reason,
            'rejection_notes' => $reason,
        ]);
        if ($ok) $this->_log($id, 'Reject', $user_id, $reason);
        return $ok ? ['success' => true] : ['success' => false, 'message' => 'Gagal reject PR.'];
    }

    // ===================== RIWAYAT APPROVAL + REPLY =====================
    // Ditambahkan 2026-09-06 (user: "untuk PR okee") — replikasi persis pola
    // Po_model/Erf_model/Prf_model. Tabel `pr_approval_logs` dibuat oleh
    // db/migration_pr_approval_logs.sql.

    public function get_approval_logs($pr_header_id) {
        if (!$this->_logs_table_ready()) return [];
        $this->db->reset_query();
        return $this->db->where('pr_header_id', $pr_header_id)
            ->order_by('id', 'ASC')->get('pr_approval_logs')->result();
    }

    /**
     * Balasan pada thread Back to Draft. Siapa yang boleh membalas & kapan
     * thread terbuka DITENTUKAN DI CONTROLLER lewat can_reply_return_thread()
     * (auth_helper) — di sini cuma jaring pengaman terakhir soal status.
     */
    public function add_reply($pr_header_id, $actor_id, $message) {
        $header = $this->get_header_by_id($pr_header_id);
        if (!$header || $header->status !== 'Draft') {
            return ['success' => false, 'message' => 'Balasan cuma bisa dikirim selagi PR berstatus Draft (baru dikembalikan).'];
        }
        $this->_log($pr_header_id, 'Reply', $actor_id, $message);
        return ['success' => true];
    }

    /**
     * Tabelnya baru (migration_pr_approval_logs.sql). Dicek dulu supaya di
     * environment yang migration-nya belum dijalankan, submit/approve PR tidak
     * ikut gagal cuma gara-gara logging — fiturnya sekadar tidak aktif.
     * Hasilnya di-cache per-request.
     */
    private function _logs_table_ready() {
        static $ready = null;
        if ($ready === null) $ready = $this->db->table_exists('pr_approval_logs');
        return $ready;
    }

    private function _log($pr_header_id, $action, $actor_id, $notes = null) {
        if (!$this->_logs_table_ready()) return;
        $this->db->reset_query();
        $actor = $this->db->select('fullname')->where('id', $actor_id)->get('users')->row();
        $this->db->reset_query();
        $this->db->insert('pr_approval_logs', [
            'pr_header_id' => $pr_header_id,
            'action'       => $action,
            'actor_id'     => (int) $actor_id,
            'actor_name'   => $actor ? $actor->fullname : null,
            'notes'        => $notes,
        ]);
    }

    // ===================== DETAILS =====================

    public function get_details_by_id($pr_id) {
        $this->db->reset_query();
        $select = "
            d.id, d.pr_header_id, d.item_id, d.item_type, d.qty, d.description, d.remarks,
            d.sales_contract_id, d.fg_id,
            CASE
                WHEN d.item_type = 'material'    THEN m.kode_material
                WHEN d.item_type = 'machine'     THEN mc.kode_mesin
                WHEN d.item_type = 'inventory'   THEN i.kode_barang
                WHEN d.item_type = 'semifinished' THEN sf.kode_barang
                WHEN d.item_type = 'jasa'        THEN j.kode_jasa
                WHEN d.item_type = 'asset'       THEN ast.kode_asset
                ELSE 'N/A'
            END as item_code,
            CASE
                WHEN d.item_type = 'material'    THEN m.nama_material
                WHEN d.item_type = 'machine'     THEN mc.nama_mesin
                WHEN d.item_type = 'inventory'   THEN i.nama_barang
                WHEN d.item_type = 'semifinished' THEN sf.nama_barang
                WHEN d.item_type = 'jasa'        THEN j.nama_jasa
                WHEN d.item_type = 'asset'       THEN ast.nama_asset
                ELSE 'Unknown'
            END as item_name,
            CASE
                WHEN d.item_type = 'material'    THEN m.satuan
                WHEN d.item_type = 'machine'     THEN COALESCE(mc.satuan, 'UNIT')
                WHEN d.item_type = 'inventory'   THEN i.satuan
                WHEN d.item_type = 'semifinished' THEN sf.satuan
                WHEN d.item_type = 'jasa'        THEN j.satuan
                WHEN d.item_type = 'asset'       THEN ast.satuan
                ELSE '-'
            END as item_unit,
            COALESCE(d.ipo_references, sc.sc_number) as sc_number,
            fg_model.kode_barang as fg_code,
            fg_model.nama_barang as fg_name
        ";
        $this->db->select($select, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('materials m',         'd.item_id = m.id  AND d.item_type = "material"',     'left');
        $this->db->join('machines mc',         'd.item_id = mc.id AND d.item_type = "machine"',      'left');
        $this->db->join('inventories i',       'd.item_id = i.id  AND d.item_type = "inventory"',    'left');
        $this->db->join('semifinished_goods sf','d.item_id = sf.id AND d.item_type = "semifinished"','left');
        $this->db->join($this->table_jasa . ' j', 'd.item_id = j.id AND d.item_type = "jasa"',       'left');
        $this->db->join($this->table_asset . ' ast', 'd.item_id = ast.id AND d.item_type = "asset"','left');
        $this->db->join('sales_contracts sc',  'd.sales_contract_id = sc.id',                        'left');
        $this->db->join('finished_goods fg_model', 'd.fg_id = fg_model.id',                          'left');
        $this->db->where('d.pr_header_id', $pr_id);
        return $this->db->get()->result();
    }

    /**
     * PR List (monitoring, lintas-tipe) — ERP Revision Notes,
     * [[erp_revision_monitoring_lists]]. Method TERPISAH dari get_all() (yang
     * sudah dipakai 4 controller berbeda dgn ekspektasi kolom lama) supaya
     * tidak ada resiko efek samping ke halaman lain.
     *
     * "Item/Material" cukup JUMLAH baris (bukan daftar nama) — 1 PR bisa
     * campur item dari 5 tabel master beda (inventory/material/machine/
     * asset/jasa), resolve nama semuanya per baris list terlalu berat utk
     * list monitoring (klik row utk lihat detail lengkap). Dikonfirmasi user.
     * "Total" = SUM qty (PR TIDAK punya kolom harga sama sekali di
     * pr_details — beda dari PO — jadi "Total" moneter tidak applicable).
     * "PO Status" = status PO (GROUP_CONCAT kalau lebih dari satu) yang
     * pernah menarik item dari PR ini, via po_details.pr_header_id (diisi
     * saat Purchase_order*::create_po_from_pr()/pull_from_pr()).
     */
    public function get_all_monitoring($filters = []) {
        // Asal IRF per PR. Rantainya: pr_details -> pr_detail_cr_links ->
        // consumable_request_details -> consumable_request_header (lihat
        // db/migration_pr_detail_cr_links.sql).
        //
        // Hasilnya "id:nomor" dipisah "|" -- id ikut dibawa supaya view bisa
        // menautkan tiap IRF tanpa query kedua, dan pasangan id<->nomor
        // dijamin tidak tertukar (kalau dikirim sbg dua GROUP_CONCAT terpisah,
        // urutannya belum tentu sama).
        //
        // Tabel penghubungnya berasal dari migrasi. Kalau migrasi itu belum
        // dijalankan di sebuah database, subquery-nya DILEWATI supaya halaman
        // PR List tidak ikut mati -- kolom Asal IRF cuma jadi kosong.
        $irf_select = $this->db->table_exists('pr_detail_cr_links')
            ? "(SELECT GROUP_CONCAT(DISTINCT CONCAT(crh.id, ':', crh.request_number)
                              ORDER BY CONCAT(crh.id, ':', crh.request_number) SEPARATOR '|')
                  FROM pr_details pd_irf
                  INNER JOIN pr_detail_cr_links pcl ON pcl.pr_detail_id = pd_irf.id
                  INNER JOIN consumable_request_details crd ON crd.id = pcl.cr_detail_id
                  INNER JOIN consumable_request_header crh ON crh.id = crd.header_id
                 WHERE pd_irf.pr_header_id = h.id)"
            : 'NULL';

        $this->db->reset_query();
        $this->db->select("
            h.*,
            u.fullname as requester_name,
            {$irf_select} AS irf_refs,
            (SELECT COUNT(*) FROM pr_details pd WHERE pd.pr_header_id = h.id) AS item_count,
            (SELECT COALESCE(SUM(pd.qty), 0) FROM pr_details pd WHERE pd.pr_header_id = h.id) AS total_qty,
            (SELECT GROUP_CONCAT(DISTINCT po_h.status SEPARATOR ', ')
                FROM po_details po_d
                INNER JOIN po_header po_h ON po_h.id = po_d.po_header_id
                WHERE po_d.pr_header_id = h.id) AS po_status,
            (SELECT MAX(po_d.po_header_id)
                FROM po_details po_d
                WHERE po_d.pr_header_id = h.id) AS latest_po_id
        ", false);
        $this->db->from($this->table_header . ' h');
        $this->db->join('users u', 'h.requester_id = u.id', 'left');

        if (!empty($filters['type'])) {
            if (is_array($filters['type'])) {
                $this->db->where_in('h.type', $filters['type']);
            } else {
                $this->db->where('h.type', $filters['type']);
            }
        }
        if (!empty($filters['status'])) {
            $this->db->where('h.status', $filters['status']);
        }
        if (!empty($filters['department'])) {
            $this->db->where('h.department', $filters['department']);
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $this->db->group_start()
                ->like('h.pr_number', $term)
                ->or_like('h.purpose', $term)
                ->group_end();
        }

        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function insert_item($data) {
        $this->db->reset_query();
        return $this->db->insert($this->table_details, $data) ? $this->db->insert_id() : false;
    }

    /**
     * Link satu pr_detail ke cr_detail (consumable_request_details) asalnya,
     * beserta qty yang disumbang cr_detail itu ke pr_detail (satu pr_detail
     * bisa mengagregasi banyak cr_detail). Ditulis oleh
     * Consumable_outstanding::create_pr(). Dipakai baca-saja oleh
     * Consumable_outstanding_model / Consumable_request_model utk menelusuri
     * penyelesaian Jasa balik ke IRF asal — lihat
     * db/migration_pr_detail_cr_links.sql & [[revision_irf_auto_pr_at_issuance]].
     */
    public function link_cr_detail($pr_detail_id, $cr_detail_id, $qty) {
        $this->db->reset_query();
        return $this->db->insert('pr_detail_cr_links', [
            'pr_detail_id' => (int) $pr_detail_id,
            'cr_detail_id' => (int) $cr_detail_id,
            'qty'          => (float) $qty,
        ]);
    }

    public function update_item($detail_id, $data) {
        $this->db->reset_query();
        $this->db->where('id', $detail_id);
        return $this->db->update($this->table_details, $data);
    }

    public function update_item_qty($detail_id, $qty) {
        return $this->update_item($detail_id, ['qty' => $qty]);
    }

    /**
     * True kalau baris PR ini sudah pernah ditarik ke PO (termasuk PO yang
     * sudah dibatalkan — baris po_details-nya tetap menunjuk pr_detail ini).
     * Dipakai untuk mengunci baris dari edit/hapus, baik di alur normal
     * maupun di mode edit Admin.
     */
    public function detail_has_po($detail_id) {
        $this->db->reset_query();
        return $this->db->where('pr_detail_id', (int) $detail_id)->count_all_results('po_details') > 0;
    }

    public function delete_item($id) {
        // Tolak kalau baris ini sudah ditarik ke PO. Bisa terjadi kalau PR
        // di-return ke Draft setelah sebagian itemnya masuk PO — menghapusnya
        // akan meninggalkan baris PO yang menunjuk pr_detail yang tidak ada.
        if ($this->detail_has_po($id)) {
            return false;
        }

        $this->db->reset_query();
        $this->db->where('pr_detail_id', $id)->delete('pr_detail_ipo_refs');
        $this->db->reset_query();
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    /**
     * Insert atau tambah qty pada pr_detail_ipo_refs untuk satu IPO.
     */
    public function upsert_ipo_ref($pr_detail_id, $sc_id, $sc_number, $qty) {
        $this->db->reset_query();
        $existing = $this->db->where('pr_detail_id', $pr_detail_id)
                             ->where('sc_number', $sc_number)
                             ->get('pr_detail_ipo_refs')->row();
        if ($existing) {
            $this->db->reset_query();
            return $this->db->where('id', $existing->id)
                            ->update('pr_detail_ipo_refs', ['qty_contribution' => $existing->qty_contribution + $qty]);
        }
        return $this->db->insert('pr_detail_ipo_refs', [
            'pr_detail_id'     => $pr_detail_id,
            'sc_id'            => $sc_id,
            'sc_number'        => $sc_number,
            'qty_contribution' => $qty,
        ]);
    }

    /**
     * Ambil semua IPO breakdown untuk satu PR detail row.
     */
    public function get_ipo_refs_by_detail_id($pr_detail_id) {
        $this->db->reset_query();
        return $this->db->where('pr_detail_id', $pr_detail_id)
                        ->order_by('sc_number', 'ASC')
                        ->get('pr_detail_ipo_refs')->result();
    }

    public function check_existing_item($pr_id, $item_id, $item_type) {
        $this->db->reset_query();
        $this->db->where('pr_header_id', $pr_id)
                 ->where('item_id',      $item_id)
                 ->where('item_type',    $item_type);
        return $this->db->get($this->table_details)->row();
    }

    public function get_detail_by_id($id) {
        $this->db->reset_query();
        return $this->db->where('id', $id)->get($this->table_details)->row();
    }

    /**
     * Validasi permintaan ganti item pada satu baris PR yang sudah ada.
     * $item_str memakai format select2 di UI: "type-id" (mis. "inventory-12").
     *
     * Dipakai modul PR non produksi & halaman approval-nya waktu Admin membuka
     * kunci edit: bukan cuma qty yang boleh dibetulkan, tapi juga item/kode
     * barangnya kalau requester salah pilih.
     *
     * @return array ['fields' => [...]] kolom yang perlu di-update (array kosong
     *               berarti item tidak berubah), atau ['error' => 'pesan'].
     */
    public function resolve_item_change($detail_id, $pr_header_id, $item_str) {
        $item_str = trim((string) $item_str);
        if ($item_str === '' || strpos($item_str, '-') === false) return ['fields' => []];

        list($item_type, $item_id) = explode('-', $item_str, 2);
        $item_type = trim($item_type);
        $item_id   = (int) $item_id;
        if ($item_type === '' || $item_id <= 0) return ['error' => 'Item tidak valid'];

        $current = $this->get_detail_by_id($detail_id);
        if (!$current) return ['error' => 'Item tidak ditemukan'];
        if ((int) $current->item_id === $item_id && $current->item_type === $item_type) {
            return ['fields' => []];
        }

        // Satu item cuma boleh muncul sekali per PR — kalau tidak, qty-nya
        // terpecah dua baris dan progres PO jadi ambigu.
        $duplicate = $this->check_existing_item($pr_header_id, $item_id, $item_type);
        if ($duplicate && (int) $duplicate->id !== (int) $detail_id) {
            return ['error' => 'Item tersebut sudah ada di baris lain PR ini. Ubah qty pada baris tersebut saja.'];
        }

        return ['fields' => ['item_id' => $item_id, 'item_type' => $item_type]];
    }

    /**
     * Tambahkan SC/IPO number ke kolom ipo_references (comma-separated) tanpa duplikat.
     */
    public function append_ipo_reference($detail_id, $sc_number) {
        $this->db->reset_query();
        $row = $this->db->select('ipo_references')->where('id', $detail_id)->get($this->table_details)->row();
        $existing = trim($row->ipo_references ?? '');
        $refs = $existing ? array_map('trim', explode(',', $existing)) : [];
        if (!in_array($sc_number, $refs)) {
            $refs[] = $sc_number;
        }
        return $this->update_item($detail_id, ['ipo_references' => implode(', ', $refs)]);
    }

    public function get_available_items() {
        $materials = $this->db->reset_query()
            ->select("id, kode_material as code, nama_material as name, 'material' as type, satuan as unit")
            ->where('status', 'active')->order_by('nama_material','ASC')->get('materials')->result();

        $semifinished = $this->db->reset_query()
            ->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type, satuan as unit")
            ->where('status', 'active')->order_by('nama_barang','ASC')->get('semifinished_goods')->result();

        $machines = $this->db->reset_query()
            ->select("id, kode_mesin as code, nama_mesin as name, 'machine' as type, 'Unit' as unit")
            ->where('status', 'Active')->order_by('nama_mesin','ASC')->get('machines')->result();

        $inventories = $this->db->reset_query()
            ->select("id, kode_barang as code, nama_barang as name, 'inventory' as type, satuan as unit")
            ->where('status', 'Active')->order_by('nama_barang','ASC')->get('inventories')->result();

        $jasas = $this->db->reset_query()
            ->select("id, kode_jasa as code, nama_jasa as name, 'jasa' as type, satuan as unit")
            ->where('status', 'active')->order_by('nama_jasa','ASC')->get($this->table_jasa)->result();

        return array_merge($materials, $semifinished, $machines, $inventories, $jasas);
    }

    public function search_items($term, $only_type = null) {
        $t = $this->db->escape_like_str(trim($term));

        // PR Jasa hanya boleh pilih dari master jasa — short-circuit ke satu tabel saja
        // (tidak mengubah perilaku pemanggil lama yang tidak mengirim $only_type).
        if ($only_type === 'jasa') {
            $this->db->reset_query();
            return $this->db->select("id, kode_jasa as code, nama_jasa as name, 'jasa' as type, satuan as unit")
                ->like('nama_jasa', $t)->or_like('kode_jasa', $t)
                ->where('status', 'active')->limit(15)->get($this->table_jasa)->result();
        }

        // Idem untuk PR Asset: hanya dari master asset.
        if ($only_type === 'asset') {
            $this->db->reset_query();
            return $this->db->select("id, kode_asset as code, nama_asset as name, 'asset' as type, satuan as unit")
                ->like('nama_asset', $t)->or_like('kode_asset', $t)
                ->where('status', 'active')->limit(15)->get($this->table_asset)->result();
        }

        $this->db->reset_query();
        $q1 = $this->db->select("id, kode_material as code, nama_material as name, 'material' as type, satuan as unit")->like('nama_material',$t)->or_like('kode_material',$t)->where('status','active')->limit(5)->get_compiled_select('materials');
        $this->db->reset_query();
        $q2 = $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type, satuan as unit")->like('nama_barang',$t)->or_like('kode_barang',$t)->where('status','active')->limit(5)->get_compiled_select('semifinished_goods');
        $this->db->reset_query();
        $q3 = $this->db->select("id, kode_barang as code, nama_barang as name, 'inventory' as type, satuan as unit")->like('nama_barang',$t)->or_like('kode_barang',$t)->where('status','Active')->limit(5)->get_compiled_select('inventories');
        $this->db->reset_query();
        $q4 = $this->db->select("id, kode_mesin as code, nama_mesin as name, 'machine' as type, 'Unit' as unit")->like('nama_mesin',$t)->or_like('kode_mesin',$t)->where('status','Active')->limit(5)->get_compiled_select('machines');
        return $this->db->query("($q1) UNION ALL ($q2) UNION ALL ($q3) UNION ALL ($q4) LIMIT 15")->result();
    }

    // ============================================================
    // PR → PO : TARIK ITEM (MULTI-PR, PARSIAL)
    //
    // Sisa qty PR tidak disimpan di kolom mana pun — selalu dihitung
    // qty PR dikurangi SUM(po_details.qty) dari PO yang masih hidup.
    // Konsekuensinya: PO dibatalkan/ditolak, atau barisnya dihapus,
    // qty-nya otomatis kembali tersedia untuk ditarik lagi. Tidak ada
    // status turunan yang bisa desync dari kenyataan.
    // ============================================================

    /** Status PO yang MELEPAS qty PR (dianggap tidak jadi memesan). */
    const PO_RELEASED_STATUSES = ['Cancelled', 'Rejected'];

    /** Toleransi pembulatan; qty di DB decimal(12,2). */
    const QTY_EPSILON = 0.005;

    /**
     * Subquery terkompilasi: pr_detail_id => total qty yang sudah masuk PO hidup.
     */
    private function _ordered_qty_subquery() {
        $this->db->reset_query();
        $sql = $this->db
            ->select('pd.pr_detail_id, SUM(pd.qty) AS ordered_qty', FALSE)
            ->from('po_details pd')
            ->join('po_header ph', 'ph.id = pd.po_header_id', 'inner')
            ->where('pd.pr_detail_id IS NOT NULL', NULL, FALSE)
            ->where_not_in('ph.status', self::PO_RELEASED_STATUSES)
            ->group_by('pd.pr_detail_id')
            ->get_compiled_select();
        $this->db->reset_query();
        return $sql;
    }

    /** CASE kode/nama/satuan item — sama persis dengan get_details_by_id(). */
    private function _item_master_select($a = 'd') {
        return "
            CASE
                WHEN {$a}.item_type = 'material'     THEN m.kode_material
                WHEN {$a}.item_type = 'machine'      THEN mc.kode_mesin
                WHEN {$a}.item_type = 'inventory'    THEN i.kode_barang
                WHEN {$a}.item_type = 'semifinished' THEN sf.kode_barang
                WHEN {$a}.item_type = 'jasa'         THEN j.kode_jasa
                WHEN {$a}.item_type = 'asset'        THEN ast.kode_asset
                ELSE 'N/A'
            END as item_code,
            CASE
                WHEN {$a}.item_type = 'material'     THEN m.nama_material
                WHEN {$a}.item_type = 'machine'      THEN mc.nama_mesin
                WHEN {$a}.item_type = 'inventory'    THEN i.nama_barang
                WHEN {$a}.item_type = 'semifinished' THEN sf.nama_barang
                WHEN {$a}.item_type = 'jasa'         THEN j.nama_jasa
                WHEN {$a}.item_type = 'asset'        THEN ast.nama_asset
                ELSE 'Unknown'
            END as item_name,
            CASE
                WHEN {$a}.item_type = 'material'     THEN m.satuan
                WHEN {$a}.item_type = 'machine'      THEN COALESCE(mc.satuan, 'UNIT')
                WHEN {$a}.item_type = 'inventory'    THEN i.satuan
                WHEN {$a}.item_type = 'semifinished' THEN sf.satuan
                WHEN {$a}.item_type = 'jasa'         THEN j.satuan
                WHEN {$a}.item_type = 'asset'        THEN ast.satuan
                ELSE '-'
            END as item_unit
        ";
    }

    /** Join master item pasangan _item_master_select(). */
    private function _join_item_masters($a = 'd') {
        $this->db->join('materials m',               "{$a}.item_id = m.id   AND {$a}.item_type = 'material'",     'left');
        $this->db->join('machines mc',               "{$a}.item_id = mc.id  AND {$a}.item_type = 'machine'",      'left');
        $this->db->join('inventories i',             "{$a}.item_id = i.id   AND {$a}.item_type = 'inventory'",    'left');
        $this->db->join('semifinished_goods sf',     "{$a}.item_id = sf.id  AND {$a}.item_type = 'semifinished'", 'left');
        $this->db->join($this->table_jasa . ' j',    "{$a}.item_id = j.id   AND {$a}.item_type = 'jasa'",         'left');
        $this->db->join($this->table_asset . ' ast', "{$a}.item_id = ast.id AND {$a}.item_type = 'asset'",        'left');
    }

    /**
     * Baris PR yang masih bisa ditarik ke PO: PR sudah Approved, tipenya cocok
     * dengan tipe PO, dan masih punya sisa qty.
     *
     * @param string $type    produksi|non_produksi|asset|jasa — wajib, supaya
     *                        PO Jasa tidak bisa menarik item PR Produksi.
     * @param array  $filters pr_header_id, search, date_from, date_to, department
     */
    public function get_pullable_items($type, $filters = []) {
        $ordered = $this->_ordered_qty_subquery();

        $this->db->reset_query();
        $this->db->select("
            d.id AS pr_detail_id, d.pr_header_id, d.item_id, d.item_type,
            d.qty AS pr_qty, d.description, d.remarks, d.sales_contract_id,
            h.pr_number, h.pr_date, h.department, h.purpose,
            h.supplier_id AS pr_supplier_id,
            s.nama_supplier AS pr_supplier_name,
            u.fullname AS requester_name,
            COALESCE(ord.ordered_qty, 0) AS ordered_qty,
            (d.qty - COALESCE(ord.ordered_qty, 0)) AS remaining_qty,
            COALESCE(d.ipo_references, sc.sc_number) AS sc_number,
            " . $this->_item_master_select('d') . "
        ", FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join($this->table_header . ' h', 'h.id = d.pr_header_id',  'inner');
        $this->db->join('suppliers s',              'h.supplier_id = s.id',   'left');
        $this->db->join('users u',                  'h.requester_id = u.id',  'left');
        $this->db->join("({$ordered}) ord",         'ord.pr_detail_id = d.id','left');
        $this->_join_item_masters('d');
        $this->db->join('sales_contracts sc', 'd.sales_contract_id = sc.id', 'left');

        $this->db->where('h.status', 'Approved');
        $this->db->where('h.type', $type);

        if (!empty($filters['pr_header_id'])) {
            $this->db->where('h.id', (int) $filters['pr_header_id']);
        }
        if (!empty($filters['department'])) {
            $this->db->where('h.department', $filters['department']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('h.pr_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('h.pr_date <=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $this->db->group_start()
                ->like('h.pr_number', $term)
                ->or_like('h.purpose', $term)
                ->or_like('d.description', $term)
                ->or_like('m.nama_material', $term)
                ->or_like('i.nama_barang', $term)
                ->or_like('sf.nama_barang', $term)
                ->or_like('mc.nama_mesin', $term)
                ->or_like('j.nama_jasa', $term)
                ->or_like('ast.nama_asset', $term)
                ->group_end();
        }

        $this->db->having('remaining_qty >', self::QTY_EPSILON);
        $this->db->order_by('h.pr_date', 'DESC');
        $this->db->order_by('h.id', 'DESC');
        $this->db->order_by('d.id', 'ASC');

        return $this->db->get()->result();
    }

    /**
     * Sisa qty untuk sekumpulan pr_detail_id. WAJIB dipanggil ulang di titik
     * simpan (bukan percaya angka yang dikirim form) supaya dua orang yang
     * menarik baris sama bersamaan tidak bisa over-order.
     *
     * @return array pr_detail_id => row (remaining_qty, pr_status, pr_type, ...)
     */
    public function get_remaining_qty_map(array $pr_detail_ids) {
        $pr_detail_ids = array_values(array_unique(array_filter(array_map('intval', $pr_detail_ids))));
        if (empty($pr_detail_ids)) return [];

        $ordered = $this->_ordered_qty_subquery();

        $this->db->reset_query();
        $this->db->select("
            d.id AS pr_detail_id, d.pr_header_id, d.item_id, d.item_type, d.category,
            d.qty AS pr_qty, d.description, d.sales_contract_id,
            h.pr_number, h.status AS pr_status, h.type AS pr_type,
            COALESCE(ord.ordered_qty, 0) AS ordered_qty,
            (d.qty - COALESCE(ord.ordered_qty, 0)) AS remaining_qty
        ", FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join($this->table_header . ' h', 'h.id = d.pr_header_id',   'inner');
        $this->db->join("({$ordered}) ord",         'ord.pr_detail_id = d.id', 'left');
        $this->db->where_in('d.id', $pr_detail_ids);

        $map = [];
        foreach ($this->db->get()->result() as $row) {
            $map[(int) $row->pr_detail_id] = $row;
        }
        return $map;
    }

    /**
     * Progress PO per baris untuk satu PR — dipakai di halaman detail PR untuk
     * menandai tiap item: belum di-PO / sebagian / penuh, plus PO mana saja.
     *
     * @return array pr_detail_id => ['ordered'=>float, 'pos'=>[['po_id','po_number','status','qty'], ...]]
     */
    /**
     * "PR ini sudah jadi PO belum?" untuk SEKUMPULAN PR sekaligus.
     *
     * Dipakai halaman list IPR (user 2026-09-11: "apakah bisa melihat PR itu
     * sudah dibuat PO belum dan bisa diklik dia di PO berapa langsung menuju
     * halaman PO-nya?"). Sengaja versi BATCH (1 query untuk semua baris),
     * bukan get_po_progress_by_pr() yang dipanggil per baris — list bisa
     * berisi puluhan PR, itu jadi puluhan query.
     *
     * @param  int[] $pr_ids
     * @return array  pr_header_id => [ ['po_id','po_number','type','status'], ... ]
     */
    public function get_po_links_for_prs(array $pr_ids) {
        $pr_ids = array_values(array_unique(array_filter(array_map('intval', $pr_ids))));
        if (empty($pr_ids)) return [];

        $this->db->reset_query();
        $rows = $this->db
            ->distinct()
            ->select('pd.pr_header_id, ph.id AS po_id, ph.po_number, ph.type, ph.status', FALSE)
            ->from('po_details pd')
            ->join('po_header ph', 'ph.id = pd.po_header_id', 'inner')
            ->where_in('pd.pr_header_id', $pr_ids)
            ->order_by('ph.id', 'ASC')
            ->get()->result();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->pr_header_id][] = [
                'po_id'     => (int) $r->po_id,
                'po_number' => $r->po_number,
                'type'      => $r->type,
                'status'    => $r->status,
                // PO batal tetap ditampilkan sebagai jejak, tapi ditandai
                // supaya tidak dikira PR-nya sudah benar-benar dibelikan.
                'void'      => in_array($r->status, self::PO_RELEASED_STATUSES, TRUE),
            ];
        }
        return $out;
    }

    public function get_po_progress_by_pr($pr_header_id) {
        $this->db->reset_query();
        $rows = $this->db
            ->select('pd.pr_detail_id, pd.qty, ph.id AS po_id, ph.po_number, ph.status, ph.type', FALSE)
            ->from('po_details pd')
            ->join('po_header ph', 'ph.id = pd.po_header_id', 'inner')
            ->where('pd.pr_header_id', (int) $pr_header_id)
            ->where('pd.pr_detail_id IS NOT NULL', NULL, FALSE)
            ->order_by('ph.id', 'ASC')
            ->get()->result();

        $progress = [];
        foreach ($rows as $r) {
            $key = (int) $r->pr_detail_id;
            if (!isset($progress[$key])) {
                $progress[$key] = ['ordered' => 0.0, 'pos' => []];
            }
            // PO batal tetap ditampilkan sebagai jejak, tapi qty-nya tidak dihitung.
            if (!in_array($r->status, self::PO_RELEASED_STATUSES, TRUE)) {
                $progress[$key]['ordered'] += (float) $r->qty;
            }
            $progress[$key]['pos'][] = [
                'po_id'     => (int) $r->po_id,
                'po_number' => $r->po_number,
                'status'    => $r->status,
                'type'      => $r->type,
                'qty'       => (float) $r->qty,
            ];
        }
        return $progress;
    }

    // ============================================================
    // TARIK ITEM DARI PERMINTAAN BARANG / MATERIAL REQUEST (2026-11-05)
    // ------------------------------------------------------------
    // Keputusan user (rebrand Acropoly): PR WAJIB di-approve (1 tahap, spt
    // PO), boleh dibuat MANUAL, dan bisa MENARIK baris dari Permintaan Barang
    // (PR General/Jasa/Asset) atau Material Request (PR Material) yang sudah
    // Approved -- polanya sama dgn PO "Tarik dari PR".
    //
    // Qty yang boleh ditarik = qty_pr dari get_pr_need_map() di model
    // outstanding masing2 (sisa permintaan - stok gudang - qty yang sudah ada
    // di PR aktif lain), jadi satu baris permintaan tidak bisa di-PR-kan
    // dobel. PR Draft/Submitted ikut "mengunci" qty-nya; PR Rejected
    // melepaskannya lagi (aturan yang sama dengan halaman Outstanding).
    // ============================================================

    /** Tipe PR utk 1 jenis item Permintaan Barang (sama dgn Consumable_outstanding::create_pr). */
    public static function pr_type_for_item_type($item_type) {
        if ($item_type === 'jasa')  return 'jasa';
        if ($item_type === 'asset') return 'asset';
        return 'non_produksi';
    }

    /**
     * Baris permintaan yang masih bisa ditarik ke PR (qty_pr > 0).
     * @param string      $source  'irf' (Permintaan Barang) | 'mr' (Material Request)
     * @param string|null $pr_type khusus 'irf': hanya item yang cocok dgn tipe PR ini
     */
    public function get_pullable_rows($source, $pr_type = null) {
        if ($source === 'irf') {
            $sql   = "SELECT d.id FROM consumable_request_details d
                        JOIN consumable_request_header h ON h.id = d.header_id
                       WHERE h.status = 'Approved'";
            $model = 'Consumable_outstanding_model';
        } else {
            $sql   = "SELECT d.id FROM mr_details d
                        JOIN mr_header h ON h.id = d.mr_header_id
                       WHERE h.status IN ('Approved', 'Partial Issued', 'Partial Received')";
            $model = 'Material_outstanding_model';
        }
        $ids = array_map(function ($r) { return (int) $r->id; }, $this->db->query($sql)->result());
        if (empty($ids)) return [];

        $this->load->model($model);
        $out = [];
        foreach ($this->$model->get_pr_need_map($ids) as $r) {
            if ((float) $r->qty_pr <= 0) continue;
            if ($source === 'irf' && $pr_type !== null
                && self::pr_type_for_item_type($r->item_type ?: 'inventory') !== $pr_type) continue;
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Masukkan baris permintaan terpilih ke PR (Draft). Item yang sama dgn
     * baris PR yang sudah ada digabung qty-nya; tiap baris sumber dicatat di
     * pr_detail_cr_links / pr_detail_mr_links.
     *
     * @param array $wanted [source_detail_id => qty diminta]
     * @return array ['success'=>bool, 'message'=>string, 'added'=>[], 'skipped'=>[]]
     */
    public function pull_rows_into_pr($pr_id, $source, array $wanted) {
        $header = $this->get_header_by_id($pr_id);
        if (!$header || $header->status !== 'Draft') {
            return ['success' => false, 'message' => 'Item hanya bisa ditarik saat PR berstatus Draft.'];
        }
        if ($source === 'mr' && $header->type !== 'produksi') {
            return ['success' => false, 'message' => 'Material Request hanya bisa ditarik ke PR Material.'];
        }
        if ($source === 'irf' && $header->type === 'produksi') {
            return ['success' => false, 'message' => 'Permintaan Barang hanya bisa ditarik ke PR General / Jasa / Asset.'];
        }

        $clean = [];
        foreach ($wanted as $id => $qty) {
            if ((int) $id > 0 && (float) $qty > 0) $clean[(int) $id] = (float) $qty;
        }
        if (empty($clean)) return ['success' => false, 'message' => 'Pilih minimal satu baris dengan qty > 0.'];

        $model = $source === 'irf' ? 'Consumable_outstanding_model' : 'Material_outstanding_model';
        $this->load->model($model);
        $need = $this->$model->get_pr_need_map(array_keys($clean));
        $doc_label = $source === 'irf' ? 'Permintaan Barang' : 'Material Request';

        $added = []; $skipped = [];
        $this->db->trans_start();
        foreach ($clean as $src_id => $qty_req) {
            $r = $need[$src_id] ?? null;
            if (!$r) { $skipped[] = "baris #$src_id tidak ditemukan"; continue; }
            $item_type = !empty($r->item_type) ? $r->item_type : ($source === 'irf' ? 'inventory' : 'material');
            if ($source === 'irf' && self::pr_type_for_item_type($item_type) !== $header->type) {
                $skipped[] = $r->item_name . ': jenis item tidak cocok dengan tipe PR ini';
                continue;
            }
            $max = (float) $r->qty_pr;
            if ($max <= 0) {
                $skipped[] = $r->item_name . ' (' . $r->request_number . '): sudah tertutup stok / PR lain';
                continue;
            }
            $qty = round(min($qty_req, $max), 4);
            $ref = $r->request_number;

            $existing = $this->check_existing_item($pr_id, (int) $r->item_id, $item_type);
            if ($existing) {
                $remarks = (string) $existing->remarks;
                if ($ref !== '' && strpos($remarks, $ref) === false) {
                    $remarks = trim($remarks . ($remarks !== '' ? ', ' : 'Ditarik dari ' . $doc_label . ': ') . $ref);
                }
                $this->update_item($existing->id, ['qty' => (float) $existing->qty + $qty, 'remarks' => $remarks]);
                $detail_id = (int) $existing->id;
            } else {
                $row = [
                    'pr_header_id' => (int) $pr_id,
                    'item_id'      => (int) $r->item_id,
                    'item_type'    => $item_type,
                    'qty'          => $qty,
                    'description'  => '',
                    'remarks'      => 'Ditarik dari ' . $doc_label . ': ' . $ref,
                ];
                if ($source === 'irf') $row['category'] = !empty($r->category) ? $r->category : 'General';
                $detail_id = (int) $this->insert_item($row);
            }
            if ($detail_id <= 0) { $skipped[] = $r->item_name . ': gagal disimpan'; continue; }

            if ($source === 'irf') {
                $this->link_cr_detail($detail_id, $src_id, $qty);
            } else {
                $this->db->insert('pr_detail_mr_links', [
                    'pr_detail_id' => $detail_id,
                    'mr_detail_id' => (int) $src_id,
                    'qty'          => $qty,
                ]);
            }
            $added[] = $r->item_name . ' ' . (float) $qty . ($qty < $qty_req ? ' (dibatasi sisa kebutuhan ' . (float) $max . ')' : '');
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database saat menarik item.'];
        }
        if (empty($added)) {
            return ['success' => false, 'message' => 'Tidak ada item yang ditarik. ' . implode('; ', $skipped), 'added' => [], 'skipped' => $skipped];
        }
        return [
            'success' => true,
            'message' => count($added) . ' baris ditarik dari ' . $doc_label . ': ' . implode('; ', $added)
                       . (!empty($skipped) ? '. Dilewati: ' . implode('; ', $skipped) : ''),
            'added'   => $added,
            'skipped' => $skipped,
        ];
    }
}
