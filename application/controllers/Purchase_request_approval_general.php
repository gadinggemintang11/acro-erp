<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

/**
 * Purchase_request_approval_general — CONTROLLER DASAR bersama utk approval
 * PR "non-produksi" (General/Jasa/Asset). Bagian ke-2 dari konsolidasi PR
 * (lihat docblock lengkap pola ini di `Purchase_request_general.php` —
 * controller UTAMA-nya, digabung bareng). Approval PR Produksi
 * (`Purchase_request_approval.php`) TIDAK ikut — itu masih 1-stage sederhana
 * (approve_pr/reject_pr), sedangkan General/Jasa/Asset sudah 3-stage
 * (HOD → Endorsement → Final) sejak awal, jadi keduanya genuinely beda alur,
 * bukan cuma beda properti.
 *
 * `Purchase_request_approval_jasa`/`_asset` (MASIH 2 file terpisah secara
 * fisik!) jadi SUBCLASS TIPIS — cuma override properti. URL, menu sidebar
 * (Jasa & Asset approval sekarang digabung ke link "General Item PR
 * Approval", lihat templates/sidebar.php), permission Role Access TIDAK
 * BERUBAH.
 *
 * Properti yang WAJIB di-override tiap subclass:
 *   $pr_type        nilai kolom pr_header.type ('non_produksi'|'jasa'|'asset')
 *   $menu_key       menu_key approval ('purchase_request_approval_general' dst)
 *   $main_menu_key  menu_key controller UTAMA terkait (utk redirect
 *                    export_detail_excel/pdf ke sana)
 *   $log_module     label utk add_log() ADMIN_EDIT
 *
 * BUG yang KETEMU+DIPERBAIKI sekalian pas konsolidasi ini (SENGAJA, dicatat
 * di memory pr_controllers_merge.md):
 * - Jasa & Asset SEBELUMNYA TIDAK PUNYA sama sekali: fitur admin-lock
 *   (`_can_write_items()`/`is_pr_admin_edit()`/`can_unlock_admin`), PO-lock
 *   item (`_row_locked_by_po`), `add_single_item()` (tambah item dari halaman
 *   approval — pintu "buka kunci" tanpa reset approval), `search_items()`,
 *   `export_list_pdf()`. Sekarang SEMUA tipe konsisten kebagian kemampuan
 *   yang sama (pola sama spt PO merge yg jg menaikkan Jasa/Asset ke fitur
 *   lengkap General).
 */
class Purchase_request_approval_general extends MY_Controller {

    /** Stage di mana approver (bukan Admin) masih boleh mengubah item. */
    const APPROVAL_STAGES = ['Submitted', 'Approved Head Dept', 'Endorsed'];

    protected $pr_type = 'non_produksi';
    protected $menu_key = 'purchase_request_approval_general';
    protected $main_menu_key = 'purchase_request_general';
    protected $log_module = 'Purchase Request Non Produksi';

    public function __construct() {
        parent::__construct();
        check_login();
        if (!check_access($this->menu_key, 'view')) {
            show_error(lang_line('purchase_request_approval_msg_no_permission_access', 'Anda tidak memiliki izin mengakses halaman ini.'), 403);
        }
        $this->load->model(['Purchase_request_model', 'Sc_model']);
        $this->load->helper('url');
    }

    /**
     * Daftar pr_type yang MASUK ke listing (index()/approval()). General
     * (class dasar ini) py DEFAULT gabungan non_produksi+jasa+asset — Jasa &
     * Asset (subclass) override balik ke cuma tipe sendiri (lihat catatan
     * sama persis di Purchase_request_general::_included_types()). Method
     * yang operasi ke 1 PR spesifik (detail/approve_hod/dst) TETAP strict
     * cocok $this->pr_type.
     */
    protected function _included_types() {
        return ['non_produksi', 'jasa', 'asset'];
    }

    protected function _title_index() {
        return 'Persetujuan PR General';
    }

    protected function _title_approval() {
        return 'Persetujuan PR General';
    }

    protected function _title_detail() {
        return 'Detail Persetujuan PR General';
    }

    // ===================== LIST - APPROVAL =====================

    public function index() {
        if (!check_access($this->menu_key, 'view')) {
            show_error(lang_line('purchase_request_approval_msg_no_permission', 'Anda tidak memiliki izin.'), 403);
        }

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
            'type'   => $this->_included_types(),
        ];

        $data = [
            'title'    => $this->_title_index(),
            'prs'      => $this->Purchase_request_model->get_all($filters),
            'filters'  => $filters,
            'statuses' => ['Submitted', 'Approved Head Dept', 'Endorsed', 'Approved', 'Rejected'],
            'pr_type'  => $this->pr_type,
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view($this->menu_key . '/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * PR Approval — list PR yang menunggu aksi approval user (3-stage).
     * Pola identik dengan Consumable_request::approval() (IRF).
     */
    public function approval() {
        if (!check_access($this->menu_key, 'view')) {
            show_error(lang_line('purchase_request_approval_msg_no_permission', 'Anda tidak memiliki izin.'), 403);
        }

        $can_head_dept   = check_access($this->menu_key, 'head_dept');
        $can_endorsement = check_access($this->menu_key, 'endorsement');
        $can_approval    = check_access($this->menu_key, 'approval');

        $filters = ['search' => $this->input->get('search'), 'type' => $this->_included_types()];

        $data = [
            'title'           => $this->_title_approval(),
            'prs'             => $this->Purchase_request_model->get_pending_approvals(
                                    $can_head_dept, $can_endorsement, $can_approval, $filters
                                ),
            'filters'         => $filters,
            'can_head_dept'   => $can_head_dept,
            'can_endorsement' => $can_endorsement,
            'can_approval'    => $can_approval,
            // Riwayat approval milik user yg sedang login (user 2026-09-06:
            // "di bagian bawah ada riwayat yang user itu approve") — kartu
            // kedua di bawah antrian, partial templates/my_approval_history.php.
            'my_approval_history' => my_approval_history([
                'table'  => 'pr_header',
                'number' => 'pr_number',
                'date'   => 'pr_date',
                'where'  => ['type' => $this->_included_types()],
            ]),
            'my_history_route'    => $this->main_menu_key . '/detail',
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view($this->menu_key . '/approval', $data);
        $this->load->view('templates/footer');
    }

    // ===================== DETAIL - APPROVAL =====================

    public function detail($id) {
        if (!check_access($this->menu_key, 'view')) {
            show_error(lang_line('purchase_request_approval_msg_no_permission', 'Anda tidak memiliki izin.'), 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type) show_404();

        // 3-stage approval flags (workflow baru). Status legacy 'Submitted'
        // mengikuti flow baru — masuk ke stage HOD.
        $can_head_dept   = $header->status === 'Submitted'         && check_access($this->menu_key, 'head_dept');
        $can_endorsement = $header->status === 'Approved Head Dept'&& check_access($this->menu_key, 'endorsement');
        $can_approval    = $header->status === 'Endorsed'          && check_access($this->menu_key, 'approval');
        $in_approval     = in_array($header->status, ['Submitted','Approved Head Dept','Endorsed']);
        $has_any_approval_perm = check_access($this->menu_key, 'head_dept')
                              || check_access($this->menu_key, 'endorsement')
                              || check_access($this->menu_key, 'approval');

        // can_approve dipakai oleh view lama untuk action edit/delete item ketika
        // PR masih bisa disetujui. Sekarang berarti: "user bisa melakukan minimal
        // satu aksi approval pada PR ini".
        $can_act_now = ($can_head_dept || $can_endorsement || $can_approval);

        // Admin override (role Admin): force return-to-draft / force delete dari
        // status apa pun (termasuk Rejected/Approved) untuk koreksi. Delete diblok
        // kalau PR sudah dikonversi ke PO supaya PO tidak jadi orphan.
        $has_linked_po = $this->Purchase_request_model->has_linked_po($id);

        // Mode edit Admin: buka kunci item PR yang sudah lewat stage-nya (termasuk
        // yang sudah Approved) TANPA mereset approval. Harus diminta eksplisit
        // lewat tombol (flag admin_edit=1), tidak otomatis aktif untuk Admin.
        $admin_edit = is_pr_admin_edit($header);

        $data = [
            'title'           => $this->_title_detail(),
            'header'          => $header,
            'details'         => $this->Purchase_request_model->get_details_by_id($id),
            // Progres PO per baris: sudah masuk PO berapa, PO mana saja.
            'po_progress'         => $this->Purchase_request_model->get_po_progress_by_pr($id),
            'suppliers'       => $this->db->order_by('nama_supplier','ASC')->get('suppliers')->result(),
            'can_approve'     => $can_act_now,
            'can_head_dept'   => $can_head_dept,
            'can_endorsement' => $can_endorsement,
            'can_approval'    => $can_approval,
            'can_return'      => $in_approval && $has_any_approval_perm,
            'can_reject'      => $in_approval && $has_any_approval_perm,
            'can_unsubmit'    => $header->status === 'Submitted' && check_access($this->menu_key, 'edit'),
            'is_admin'         => is_admin(),
            'can_admin_return' => is_admin() && $header->status !== 'Draft',
            'can_admin_delete' => is_admin() && $header->status !== 'Draft' && !$has_linked_po,
            'has_linked_po'    => $has_linked_po,
            'admin_edit_mode'  => $admin_edit,
            'can_unlock_admin' => can_unlock_pr_admin_edit($header),
            // Kolom aksi item tampil untuk approver di stage-nya (perilaku lama)
            // maupun Admin yang sudah membuka kunci.
            'can_edit_items'   => $can_act_now || $admin_edit,
            // Riwayat approval + thread balasan Back to Draft (2026-09-06).
            // Form balasannya POST lintas-controller ke controller UTAMA
            // (`$this->main_menu_key . '/reply/'`) — lihat alasannya di
            // Purchase_request_general::reply().
            'approval_logs'    => $this->Purchase_request_model->get_approval_logs($id),
            'can_reply'        => can_reply_return_thread($header),
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view($this->menu_key . '/detail', $data);
        $this->load->view('templates/footer');
    }

    // ===================== APPROVAL ACTIONS =====================

    public function approve_pr($id) {
        if (!check_access($this->menu_key, 'approve')) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_no_permission_approve', 'Izin ditolak')]);
        }

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type || $header->status !== 'Submitted') {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_invalid_status_approve', 'PR tidak bisa disetujui dalam status ini')]);
        }

        $user_id = $this->session->userdata('user_id');
        if ($this->Purchase_request_model->update_status($id, 'Approved', $user_id)) {
            return $this->_json(['success' => true, 'message' => lang_line('purchase_request_approval_msg_approve_success', 'PR berhasil disetujui.')]);
        }
        return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_approve_failed', 'Gagal menyetujui PR.')]);
    }

    public function reject_pr($id) {
        if (!check_access($this->menu_key, 'approve')) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_no_permission_approve', 'Izin ditolak')]);
        }

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type || $header->status !== 'Submitted') {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_invalid_status_reject', 'PR tidak bisa ditolak dalam status ini')]);
        }

        $notes   = $this->input->post('rejection_notes');
        if (!$notes || strlen(trim($notes)) === 0) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_rejection_reason_required', 'Alasan penolakan harus diisi')]);
        }

        $user_id = $this->session->userdata('user_id');

        if ($this->Purchase_request_model->update_status($id, 'Rejected', $user_id, $notes)) {
            return $this->_json(['success' => true, 'message' => lang_line('purchase_request_approval_msg_reject_success', 'PR ditolak.')]);
        }
        return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_reject_failed', 'Gagal menolak PR.')]);
    }

    public function unsubmit_pr($id) {
        if (!check_access($this->menu_key, 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type || $header->status !== 'Submitted') {
            $this->session->set_flashdata('error', lang_line('purchase_request_approval_msg_invalid_status_unsubmit', 'PR tidak bisa dibatalkan submitnya.'));
            return redirect($this->menu_key . '/detail/' . $id);
        }

        if ($this->Purchase_request_model->update_status($id, 'Draft')) {
            $this->session->set_flashdata('success', lang_line('purchase_request_approval_msg_unsubmit_success', 'PR dikembalikan ke Draft.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_approval_msg_unsubmit_failed', 'Gagal membatalkan submit PR.'));
        }
        redirect($this->menu_key . '/detail/' . $id);
    }

    // ===================== 3-STAGE APPROVAL (HOD → Endorsement → Final) =====================
    // Pola identik dengan Consumable_request (IRF) — POST + flashdata + redirect.

    protected function _approval_redirect($id) {
        $back = $this->input->post('redirect') ?: $this->input->get('redirect');
        if ($back === 'approval') {
            redirect($this->menu_key . '/approval');
        }
        if ($back === 'index') {
            redirect($this->menu_key);
        }
        redirect($this->menu_key . '/detail/' . $id);
    }

    /**
     * Tahap Head of Dept DINONAKTIFKAN sejak 2026-11-05: approval PR sekarang 1
     * tahap (Submitted -> Approved lewat approve_final()), seperti PO.
     */
    public function approve_hod($id) {
        $this->session->set_flashdata('error', 'Approval PR sekarang 1 tahap -- gunakan tombol Approve.');
        $this->_approval_redirect($id);
    }

    /**
     * Tahap Endorsement DINONAKTIFKAN sejak 2026-11-05: approval PR sekarang 1
     * tahap (Submitted -> Approved lewat approve_final()), seperti PO.
     */
    public function approve_endorsement($id) {
        $this->session->set_flashdata('error', 'Approval PR sekarang 1 tahap -- gunakan tombol Approve.');
        $this->_approval_redirect($id);
    }

    public function approve_final($id) {
        if (!check_access($this->menu_key, 'approval')) {
            show_error('Anda tidak memiliki akses untuk final approval.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404();
        $user_id = $this->session->userdata('user_id');
        $res = $this->Purchase_request_model->approve_final($id, $user_id);
        if ($res['success']) {
            $this->session->set_flashdata('success', 'PR final di-approve.');
        } else {
            $this->session->set_flashdata('error', $res['message']);
        }
        $this->_approval_redirect($id);
    }

    public function return_to_draft($id) {
        // Admin boleh return dari status apa pun (override). Approver biasa hanya
        // saat PR masih di tahap approval (dijaga oleh model).
        $admin = is_admin();
        $can_action = $admin
                   || check_access($this->menu_key, 'head_dept')
                   || check_access($this->menu_key, 'endorsement')
                   || check_access($this->menu_key, 'approval');
        if (!$can_action) show_error('Anda tidak memiliki akses untuk return PR.', 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $reason = trim($this->input->post('return_reason'));
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Alasan return wajib diisi.');
            $this->_approval_redirect($id); return;
        }

        $user_id = $this->session->userdata('user_id');
        $res = $this->Purchase_request_model->return_to_draft($id, $user_id, $reason, $admin);
        if ($res['success']) {
            $this->session->set_flashdata('success', $admin
                ? 'PR dikembalikan ke Draft (Admin override). Semua approval direset.'
                : 'PR dikembalikan ke Draft. Creator dapat merevisi & submit ulang.');
        } else {
            $this->session->set_flashdata('error', $res['message']);
        }
        $this->_approval_redirect($id);
    }

    /**
     * Admin override: hapus PR permanen dari status apa pun (beserta detail item).
     * Diblok kalau PR sudah dikonversi ke PO supaya PO tidak jadi orphan.
     */
    public function delete_pr($id) {
        if (!is_admin()) show_error('Hanya Admin yang dapat menghapus PR.', 403);
        if (empty($id) || !is_numeric($id)) show_404();

        if ($this->Purchase_request_model->has_linked_po($id)) {
            $this->session->set_flashdata('error', 'PR sudah dikonversi ke Purchase Order — tidak bisa dihapus. Hapus atau batalkan PO terkait terlebih dahulu.');
            return redirect($this->menu_key . '/detail/' . $id);
        }

        if ($this->Purchase_request_model->delete_header($id, true)) {
            $this->session->set_flashdata('success', 'Purchase Request dihapus (Admin override).');
            redirect($this->menu_key);
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus PR.');
            redirect($this->menu_key . '/detail/' . $id);
        }
    }

    public function reject($id) {
        $can_action = check_access($this->menu_key, 'head_dept')
                   || check_access($this->menu_key, 'endorsement')
                   || check_access($this->menu_key, 'approval');
        if (!$can_action) show_error('Anda tidak memiliki akses untuk reject PR.', 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $reason = trim($this->input->post('reject_reason'));
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Alasan reject wajib diisi.');
            $this->_approval_redirect($id); return;
        }

        $user_id = $this->session->userdata('user_id');
        $res = $this->Purchase_request_model->reject_3stage($id, $user_id, $reason);
        if ($res['success']) {
            $this->session->set_flashdata('success', 'PR berhasil di-reject.');
        } else {
            $this->session->set_flashdata('error', $res['message']);
        }
        $this->_approval_redirect($id);
    }

    // ===================== ITEM EDIT/DELETE (by approver) =====================

    public function edit_item($detail_id) {
        $detail_id = (int)$detail_id;
        $header    = $this->_header_of_detail($detail_id);
        if (!$header) return $this->_json(['success' => false, 'message' => 'Item tidak ditemukan']);
        if (!$this->_can_write_items($header)) {
            return $this->_json(['success' => false, 'message' => $this->_locked_message($header)]);
        }
        if ($this->_row_locked_by_po($detail_id)) {
            return $this->_json(['success' => false, 'message' => 'Baris ini sudah ditarik ke PO, jadi qty-nya tidak bisa diubah. Tambahkan item baru kalau kebutuhannya bertambah.']);
        }

        $qty = (float)$this->input->post('qty');
        if ($qty <= 0) {
            return $this->_json(['success' => false, 'message' => lang_line('purchase_request_approval_msg_qty_invalid', 'Qty harus lebih dari 0')]);
        }

        // Ganti item/kode barang (opsional, dipakai mode edit Admin): item_id
        // format "type-id" seperti di baris tambah item. Tidak dikirim = item tetap.
        $item_change = $this->Purchase_request_model->resolve_item_change($detail_id, $header->id, $this->input->post('item_id'));
        if (isset($item_change['error'])) {
            return $this->_json(['success' => false, 'message' => $item_change['error']]);
        }

        $this->Purchase_request_model->update_item($detail_id, array_merge(['qty' => $qty], $item_change['fields']));
        if (is_pr_admin_edit($header)) {
            $swap = empty($item_change['fields']) ? '' : ', item diganti ke ' . $item_change['fields']['item_type'] . '#' . $item_change['fields']['item_id'];
            add_log('ADMIN_EDIT', $this->log_module, 'Edit item #' . $detail_id . ' pada PR ' . $header->pr_number . ' di status ' . $header->status . ' (qty=' . $qty . $swap . ', override Admin).');
        }
        return $this->_json(['success' => true, 'message' => 'Item diperbarui.', 'new_qty' => $qty]);
    }

    public function delete_item($detail_id) {
        $detail_id = (int)$detail_id;
        $header    = $this->_header_of_detail($detail_id);
        if (!$header) return $this->_json(['success' => false, 'message' => 'Item tidak ditemukan']);
        if (!$this->_can_write_items($header)) {
            return $this->_json(['success' => false, 'message' => $this->_locked_message($header)]);
        }
        if ($this->_row_locked_by_po($detail_id)) {
            return $this->_json(['success' => false, 'message' => 'Baris ini sudah ditarik ke PO, jadi tidak bisa dihapus. Batalkan dulu PO-nya kalau memang salah.']);
        }

        if ($this->Purchase_request_model->delete_item($detail_id)) {
            if (is_pr_admin_edit($header)) {
                add_log('ADMIN_EDIT', $this->log_module, 'Hapus item #' . $detail_id . ' dari PR ' . $header->pr_number . ' di status ' . $header->status . ' (override Admin).');
            }
            return $this->_json(['success' => true, 'message' => 'Item dihapus dari PR.']);
        }
        return $this->_json(['success' => false, 'message' => 'Gagal menghapus item.']);
    }

    /**
     * Tambah item ke PR dari halaman approval. Ini pintu utama fitur "buka kunci":
     * PR yang sudah di-approve normalnya tidak bisa ditambah item, dan satu-satunya
     * jalan adalah force return ke Draft yang mereset semua approval. Di sini
     * status PR tidak disentuh sama sekali.
     */
    public function add_single_item($pr_id) {
        $header = $this->Purchase_request_model->get_header_by_id($pr_id);
        if (!$header || $header->type !== $this->pr_type) return $this->_json(['success' => false, 'message' => 'PR tidak ditemukan']);
        if (!$this->_can_write_items($header)) {
            return $this->_json(['success' => false, 'message' => $this->_locked_message($header)]);
        }

        $item_str = $this->input->post('item_id');
        if (!$item_str || strpos($item_str, '-') === false) {
            return $this->_json(['success' => false, 'message' => 'Item tidak valid']);
        }

        list($item_type, $item_id) = explode('-', $item_str, 2);
        $item_id = (int)$item_id;
        $qty     = (float)($this->input->post('qty') ?: 1);
        if ($qty <= 0) return $this->_json(['success' => false, 'message' => 'Qty harus lebih dari 0']);

        $existing = $this->Purchase_request_model->check_existing_item($pr_id, $item_id, $item_type);
        if ($existing) {
            // Item sama sudah ada → qty-nya ditambah, kecuali barisnya sudah
            // jadi acuan PO (lihat _row_locked_by_po).
            if ($this->_row_locked_by_po($existing->id)) {
                return $this->_json(['success' => false, 'message' => 'Item ini sudah ada di baris yang terkunci karena sudah ditarik ke PO. Qty-nya tidak bisa diubah dari sini.']);
            }
            $new_qty = (float)$existing->qty + $qty;
            $this->Purchase_request_model->update_item_qty($existing->id, $new_qty);
            if (is_pr_admin_edit($header)) {
                add_log('ADMIN_EDIT', $this->log_module, 'Tambah qty item #' . $existing->id . ' pada PR ' . $header->pr_number . ' di status ' . $header->status . ' (qty=' . $new_qty . ', override Admin).');
            }
            return $this->_json(['success' => true, 'message' => 'Qty item diperbarui.', 'detail_id' => $existing->id, 'updated' => true, 'new_qty' => $new_qty]);
        }

        $new_id = $this->Purchase_request_model->insert_item([
            'pr_header_id' => $pr_id,
            'item_id'      => $item_id,
            'item_type'    => $item_type,
            'qty'          => $qty,
            'description'  => $this->input->post('description'),
            'remarks'      => $this->input->post('remarks'),
        ]);

        if (!$new_id) return $this->_json(['success' => false, 'message' => 'Gagal menambahkan item.']);

        if (is_pr_admin_edit($header)) {
            add_log('ADMIN_EDIT', $this->log_module, 'Tambah item ' . $item_type . '#' . $item_id . ' (qty=' . $qty . ') pada PR ' . $header->pr_number . ' di status ' . $header->status . ' (override Admin).');
        }
        return $this->_json(['success' => true, 'message' => 'Item berhasil ditambahkan.', 'detail_id' => $new_id, 'updated' => false]);
    }

    /**
     * Pencarian item untuk select2 di baris tambah item. Sama seperti modul PR
     * non produksi: material dikecualikan (itu jalur PR produksi).
     */
    public function search_items() {
        $term = $this->input->get('term') ?: $this->input->post('term');
        $out  = [];
        foreach ($this->Purchase_request_model->search_items($term) as $item) {
            if ($item->type === 'material') continue;
            $out[] = [
                'id'   => $item->type . '-' . $item->id,
                'text' => '[' . strtoupper($item->type) . '] ' . $item->code . ' - ' . $item->name,
                'code' => $item->code,
                'name' => $item->name,
                'type' => $item->type,
                'unit' => $item->unit,
            ];
        }
        $this->output->set_content_type('application/json')->set_output(json_encode(['results' => $out]));
    }

    // ===================== EXPORT =====================

    public function export_list_excel()
    {
        if (!check_access($this->menu_key, 'view')) show_error('Izin Ditolak.', 403);

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
            'type'   => $this->_included_types(),
        ];
        $prs = $this->Purchase_request_model->get_all($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'DAFTAR PURCHASE REQUEST');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'No');
        $sheet->setCellValue('B3', 'PR Number');
        $sheet->setCellValue('C3', 'Tanggal');
        $sheet->setCellValue('D3', 'Requester');
        $sheet->setCellValue('E3', 'Supplier');
        $sheet->setCellValue('F3', 'Keperluan / Purpose');
        $sheet->setCellValue('G3', 'Status');

        $sheet->getStyle('A3:G3')->getFont()->setBold(true);
        $sheet->getStyle('A3:G3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A3:G3')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 4;
        $no  = 1;
        foreach ($prs as $pr) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $pr->pr_number);
            $sheet->setCellValue('C'.$row, date('d/m/Y', strtotime($pr->pr_date)));
            $sheet->setCellValue('D'.$row, $pr->requester_name ?? '-');
            $sheet->setCellValue('E'.$row, $pr->nama_supplier ?? '-');
            $sheet->setCellValue('F'.$row, $pr->purpose ?? '-');
            $sheet->setCellValue('G'.$row, $pr->status);
            $row++;
        }

        foreach (range('A','G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $filename = 'PR_List_' . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function export_list_pdf()
    {
        if (!check_access($this->menu_key, 'view')) show_error('Izin Ditolak.', 403);

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
            'type'   => $this->_included_types(),
        ];
        $prs = $this->Purchase_request_model->get_all($filters);

        $status_colors = [
            'Draft'     => 'orange',
            'Submitted' => 'blue',
            'Approved'  => 'green',
            'Rejected'  => 'red',
        ];

        $rows = '';
        $no   = 1;
        foreach ($prs as $pr) {
            $color = $status_colors[$pr->status] ?? '#333';
            $rows .= '<tr>
                <td style="text-align:center">' . $no++ . '</td>
                <td>' . htmlspecialchars($pr->pr_number) . '</td>
                <td>' . date('d/m/Y', strtotime($pr->pr_date)) . '</td>
                <td>' . htmlspecialchars($pr->requester_name ?? '-') . '</td>
                <td>' . htmlspecialchars($pr->nama_supplier ?? '-') . '</td>
                <td>' . htmlspecialchars($pr->purpose ?? '-') . '</td>
                <td style="text-align:center;color:' . $color . ';font-weight:bold">' . htmlspecialchars($pr->status) . '</td>
            </tr>';
        }

        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h2 { text-align: center; margin-bottom: 6px; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #4472C4; color: #fff; padding: 5px 4px; text-align: left; }
            td { padding: 4px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) td { background: #f5f5f5; }
        </style>
        <h2>DAFTAR PURCHASE REQUEST</h2>
        <p style="text-align:center;font-size:9px">Dicetak: ' . date('d/m/Y H:i') . '</p>
        <table>
            <thead><tr>
                <th width="4%">No</th>
                <th width="14%">PR Number</th>
                <th width="10%">Tanggal</th>
                <th width="14%">Requester</th>
                <th width="16%">Supplier</th>
                <th width="30%">Keperluan</th>
                <th width="12%">Status</th>
            </tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>';

        $temp_dir = APPPATH . 'cache/pdf_temp/';
        if (!is_dir($temp_dir)) { @mkdir($temp_dir, 0777, true); }
        $mpdf = new Mpdf(['tempDir' => $temp_dir, 'mode' => 'utf-8', 'format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('PR_List_' . date('Ymd') . '.pdf', 'D');
        exit;
    }

    public function export_detail_excel($id)
    {
        redirect($this->main_menu_key . '/export_detail_excel/' . $id);
    }

    public function export_detail_pdf($id)
    {
        redirect($this->main_menu_key . '/export_detail_pdf/' . $id);
    }

    // ===================== HELPER =====================

    /**
     * Siapa yang boleh mengubah daftar item dari halaman approval:
     *  - approver, selagi PR masih di stage approval (perilaku lama), ATAU
     *  - Admin yang sudah menekan "Buka Kunci Edit" (flag admin_edit=1),
     *    termasuk saat PR sudah Approved — approval tidak ikut direset.
     */
    protected function _can_write_items($header) {
        if (is_pr_admin_edit($header)) return true;
        return in_array($header->status, self::APPROVAL_STAGES, true)
            && check_access($this->menu_key, 'approve');
    }

    /**
     * Header PR pemilik satu baris detail — endpoint item cuma dikirimi
     * detail_id, padahal izin tulisnya ditentukan status header.
     */
    protected function _header_of_detail($detail_id) {
        $detail = $this->Purchase_request_model->get_detail_by_id((int) $detail_id);
        if (!$detail) return null;
        $header = $this->Purchase_request_model->get_header_by_id($detail->pr_header_id);
        if (!$header || $header->type !== $this->pr_type) return null;
        return $header;
    }

    /**
     * Baris yang sudah ditarik ke PO: qty-nya jadi acuan PO, jadi dikunci dari
     * edit & hapus. Tambah item baru tetap bebas.
     */
    protected function _row_locked_by_po($detail_id) {
        return $this->Purchase_request_model->detail_has_po($detail_id);
    }

    /**
     * Pesan penolakan tulis: bedakan "Admin belum buka kunci" dari "statusnya
     * memang terkunci" supaya jelas apa langkah berikutnya.
     */
    protected function _locked_message($header) {
        if (can_unlock_pr_admin_edit($header)) {
            return 'Mode edit Admin belum aktif. Klik "Buka Kunci Edit (Admin)" dulu di halaman ini.';
        }
        return 'PR berstatus ' . $header->status . ' terkunci dan item-nya tidak bisa diubah.';
    }

    protected function _json($data) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
