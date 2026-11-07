<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

/**
 * Purchase_order_approval — CONTROLLER DASAR bersama utk approval SEMUA 4
 * tipe PO. Bagian ke-2 dari konsolidasi PO (lihat docblock lengkap pola ini
 * di `Purchase_order.php` — controller UTAMA-nya, sudah lebih dulu
 * digabung). User 2026-09-05 minta digabung SEKALIGUS krn approval PO
 * ternyata simpel (praktis cuma 1 tahap `approve_final()`, HOD/Endorsement
 * dead code sejak PO disederhanakan jadi 1-stage).
 *
 * `Purchase_order_approval_general`/`_jasa`/`_asset` (MASIH 3 file terpisah
 * secara fisik!) jadi SUBCLASS TIPIS — cuma override properti. URL, menu
 * sidebar, permission Role Access TIDAK BERUBAH.
 *
 * Properti yang WAJIB di-override tiap subclass:
 *   $po_type        nilai kolom po_header.type
 *   $menu_key       menu_key approval ('purchase_order_approval_general' dst)
 *   $main_menu_key  menu_key controller UTAMA terkait (utk redirect
 *                    export_detail_excel/pdf ke sana — 'purchase_order_general' dst)
 *   $po_type_label  label singkat dipakai di judul & pesan sukses
 *                    ('PMPO'/'GMPO'/'PO Jasa'/'PO Asset')
 *
 * BUG yang KETEMU+DIPERBAIKI sekalian (SENGAJA, dicatat di memory
 * po_controllers_merge.md):
 * - `_get_pos()` (dipakai export_list_excel/pdf) SEBELUMNYA cuma filter
 *   `type` kalau parameter `$filters['type']` diisi eksplisit — TAPI
 *   `export_list_excel()`/`export_list_pdf()` TIDAK PERNAH mengisi itu,
 *   jadi PMPO/GMPO Approval "Export List" bocor nampilin PO tipe LAIN juga
 *   (Jasa/Asset ikut kebawa). Jasa & Asset SUDAH menambal ini duluan
 *   (hardcode `where('h.type','jasa')` tanpa syarat) — sekarang perilaku
 *   yang benar itu (SELALU filter ke `$this->po_type`) berlaku SEMUA tipe.
 * - Produksi & General SEBELUMNYA TIDAK validasi `$header->type` di
 *   `detail()` (Jasa & Asset sudah) — sekarang SEMUA tipe konsisten.
 * - `export_list_pdf()` SEBELUMNYA cuma ada di Produksi & General (Jasa &
 *   Asset tidak punya sama sekali) — sekarang berlaku semua tipe.
 */
class Purchase_order_approval extends MY_Controller {

    protected $po_type = 'produksi';
    protected $menu_key = 'purchase_order_approval';
    protected $main_menu_key = 'purchase_order';
    protected $po_type_label = 'PO Material';

    public function __construct() {
        parent::__construct();
        check_login();
        if (!check_access($this->menu_key, 'view')) {
            show_error('Anda tidak memiliki izin mengakses halaman ini.', 403);
        }
        $this->load->model(['Po_model', 'Commoncode_model']);
        $this->load->helper('url');
    }

    protected function _title_index() {
        return $this->po_type_label . ' Approval';
    }

    protected function _title_detail() {
        return $this->po_type_label . ' Approval - Detail';
    }

    /**
     * Daftar po_type yang MASUK ke listing (index()/_get_pos()). Default:
     * cuma tipe sendiri. Di-override General jadi gabungan
     * non_produksi+jasa+asset (lihat catatan sama di Purchase_order.php).
     * Method yang operasi ke 1 PO spesifik (detail/approve_hod/dst) TETAP
     * strict cocok $this->po_type.
     */
    protected function _included_types() {
        return [$this->po_type];
    }

    // ===================== LIST =====================

    public function index() {
        $can_hod         = check_access($this->menu_key, 'head_dept');
        $can_endorsement = check_access($this->menu_key, 'endorsement');
        $can_approval    = check_access($this->menu_key, 'approval');

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
            'type'   => $this->po_type,
        ];

        // Default: kalau user tidak filter manual, tampilkan PO yang
        // PENDING di stage user sesuai permissionnya. $this->_included_types()
        // -- General gabungkan non_produksi+jasa+asset, lihat docblock class.
        $pos = $this->Po_model->get_pending_approvals($can_hod, $can_endorsement, $can_approval, $this->_included_types());

        // Allow status override via filter (untuk lihat Confirmed/Rejected/dll history).
        if (!empty($filters['status']) || !empty($filters['search'])) {
            $pos = $this->_get_pos($filters);
        }

        $data = [
            'title'           => $this->_title_index(),
            'pos'             => $pos,
            'filters'         => $filters,
            'statuses'        => ['Submitted', 'Approved Head Dept', 'Endorsed', 'Confirmed', 'Rejected', 'Cancelled'],
            'po_type'         => $this->po_type,
            'po_type_label'   => $this->po_type_label,
            'can_hod'         => $can_hod,
            'can_endorsement' => $can_endorsement,
            'can_approval'    => $can_approval,
            // Riwayat approval milik user yg sedang login (user 2026-09-06:
            // "di bagian bawah ada riwayat yang user itu approve") — kartu
            // kedua di bawah antrian, partial templates/my_approval_history.php.
            'my_approval_history' => my_approval_history([
                'table'  => 'po_header',
                'number' => 'po_number',
                'date'   => 'po_date',
                'where'  => ['type' => $this->_included_types()],
            ]),
            'my_history_route'    => $this->main_menu_key . '/detail',
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view($this->menu_key . '/index', $data);
        $this->load->view('templates/footer');
    }

    private function _get_pos($filters) {
        $this->db->select('h.id, h.po_number, h.po_date, h.status, h.currency_code, h.type,
            s.nama_supplier,
            u.fullname as creator_name,
            COALESCE(SUM(d.qty * d.base_price), 0) as subtotal');
        $this->db->from('po_header h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->join('users u', 'h.created_by = u.id', 'left');
        $this->db->join('po_details d', 'h.id = d.po_header_id', 'left');

        // SELALU filter ke tipe controller ini (dulu cuma filter kalau
        // $filters['type'] diisi eksplisit -- export_list_excel/pdf tidak
        // pernah mengisi itu, jadi bocor nampilin tipe PO lain, lihat
        // catatan bug di docblock class). General gabungkan 3 tipe sekaligus
        // (lihat _included_types()) -- `h.type` ikut di-select supaya view
        // list bisa nge-route tiap baris ke controller aslinya.
        $this->db->where_in('h.type', $this->_included_types());

        if (!empty($filters['status'])) {
            $this->db->where('h.status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start()
                ->like('h.po_number', $term)
                ->or_like('s.nama_supplier', $term)
                ->or_like('u.fullname', $term)
                ->group_end();
        }

        $this->db->group_by('h.id');
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    // ===================== DETAIL =====================

    public function detail($id) {
        if (empty($id) || !is_numeric($id)) show_404();
        $id = (int)$id;

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type) show_404();

        // Load nama user audit trail kalau ada
        $audit = $this->_load_audit_users($header);

        $can_hod         = check_access($this->menu_key, 'head_dept');
        $can_endorsement = check_access($this->menu_key, 'endorsement');
        $can_approval    = check_access($this->menu_key, 'approval');

        $data = [
            'title'           => $this->_title_detail(),
            'header'          => $header,
            'details'         => $this->Po_model->get_po_details_by_id($id),
            'audit'           => $audit,
            'approval_logs'   => $this->Po_model->get_approval_logs($id),
            'po_type_label'   => $this->po_type_label,
            // Tombol "Edit PO" di halaman approval (user 2026-09-10: "list view
            // saya hilangkan, 1-nya cara agar saya bisa liat PO saya lewat
            // alamat itu, beri button di situ ya ... untuk edit PO").
            // Halaman PO utama menyimpan semua aksi edit, tapi menunya bisa
            // dicopot dari sidebar lewat fitur copot/pasang — kalau begitu,
            // halaman approval ini jadi satu-satunya pintu ke sebuah PO.
            // Jadi pintu ke sana disediakan dari sini.
            //
            // Dua bentuk, mengikuti aturan yang SAMA persis dengan halaman PO
            // itu sendiri (auth_helper: can_edit_po_header / can_unlock_po_admin_edit) —
            // sengaja tidak bikin aturan baru di sini supaya tidak ada jalur
            // edit yang lolos dari gate aslinya:
            //   - PO Draft & user punya izin edit  -> "Edit PO" (biasa)
            //   - PO terkunci & user Admin         -> "Buka Kunci Edit (Admin)"
            //                                         (?admin_edit=1, sama spt
            //                                          tombol di halaman PO)
            'po_edit_route'    => $this->main_menu_key . '/detail/' . $header->id,
            // Tombol edit/hapus item DI DALAM tabel halaman ini dulu cuma
            // digating $can_approve — artinya siapa pun pemegang izin approval
            // bisa mengubah/menghapus baris PO yang sedang menunggu approval
            // TANPA membuka kunci apa pun (user 2026-09-10: "ketika statusnya
            // submitted kenapa button edit dan hapus nya tanpa buka kunci kok
            // muncul?"). Sekarang ikut aturan kunci yang sama dgn halaman PO.
            'can_edit_po_item_inline' => is_po_admin_edit($header)
                                         && can_edit_po_items($header, $this->main_menu_key, $this->_has_receipts($header->id)),
            'can_edit_po_draft' => ($header->status === 'Draft')
                                   && check_access($this->main_menu_key, 'edit'),
            'can_unlock_po_admin' => can_unlock_po_admin_edit($header),
            // Strict order: stage button hanya muncul kalau status sesuai DAN user punya permission stage tsb.
            // PO = 1 STAGE saja (ERP Revision Notes Fase 2 poin 7). Dulu dua
            // stage ini "dilewati" dengan cara melompatkan status Submit ->
            // 'Endorsed' + mengisi kolom approval pakai id creator sendiri;
            // per 2026-09-09 lompatan itu dihapus (lihat Po_model::update_status()),
            // jadi penonaktifannya dipindah ke sini — eksplisit, bukan efek
            // samping status. Tombolnya hilang sendiri karena view sudah
            // mengecek kedua flag ini.
            // Method approve_hod()/approve_endorsement() SENGAJA tidak dihapus
            // supaya gampang dihidupkan lagi kalau PO nanti balik 3-stage.
            'can_approve_hod'         => false,
            'can_approve_endorsement' => false,
            // Gate tunggal: 'Submitted' = alur normal sekarang, 'Endorsed' =
            // PO lama yang terlanjur dilompatkan sistem.
            'can_approve_final'       => in_array($header->status, ['Submitted', 'Endorsed'], true) && $can_approval,
            // Return-to-draft & Reject — siapa pun yang punya salah satu permission approval, asalkan status masih dalam tahap approval
            'can_return_reject' => in_array($header->status, ['Submitted','Approved Head Dept','Endorsed'])
                                && ($can_hod || $can_endorsement || $can_approval),
            'can_unsubmit'    => (
                $header->status === 'Submitted'
                // PO LAMA (sebelum 2026-09-09) terlanjur dilompatkan ke 'Endorsed'
                // otomatis saat submit. Yang begitu masih boleh di-unsubmit karena
                // sebenarnya belum di-review siapa pun — penandanya hod_approved_by
                // == created_by sendiri. Kalau endorsed_by-nya orang lain berarti
                // approval sungguhan (skema 3-stage lama), jangan boleh di-unsubmit.
                || ($header->status === 'Endorsed'
                    && (int) ($header->hod_approved_by ?? 0) === (int) ($header->created_by ?? -1))
            ) && check_access($this->menu_key, 'edit'),
            // Back to Draft 2-arah (ERP Revision Notes, [[erp_revision_cross_cutting]]):
            // requestor & approver mana pun bisa reply selagi status Draft.
            // Lihat catatan di Purchase_order::detail() — thread balasan cuma
            // terbuka setelah Back to Draft, pesertanya pembuat + approver
            // yang mengembalikan saja.
            'can_reply' => can_reply_return_thread($header),
            'status_colors'   => [
                'Draft' => 'warning', 'Submitted' => 'info',
                'Approved Head Dept' => 'primary', 'Endorsed' => 'primary',
                'Confirmed' => 'success', 'Closed' => 'dark',
                'Cancelled' => 'secondary', 'Rejected' => 'danger',
            ],
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view($this->menu_key . '/detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Back to Draft 2-arah (ERP Revision Notes, [[erp_revision_cross_cutting]]).
     * Gate identik Consumable_request/Prf/Erf::reply() — status HARUS Draft,
     * actor HARUS requestor ATAU py salah satu permission approval.
     */
    public function reply($id) {
        $id = (int) $id;
        if ($id <= 0) show_404();

        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header || $header->type !== $this->po_type) show_404();

        $user_id = $this->session->userdata('user_id');

        // Cuma pembuat PO & approver yang mengembalikannya, dan cuma selama
        // PO ini masih Draft hasil Back to Draft (bukan draft baru).
        if (!can_reply_return_thread($header)) {
            $this->session->set_flashdata('error', 'Balasan cuma bisa dikirim oleh pembuat PO & approver yang mengembalikannya, selagi PO masih Draft hasil Back to Draft.');
            redirect($this->menu_key . '/detail/' . $id);
            return;
        }

        $message = trim((string) $this->input->post('message'));
        if ($message === '') {
            $this->session->set_flashdata('error', 'Pesan balasan tidak boleh kosong.');
            redirect($this->menu_key . '/detail/' . $id);
            return;
        }

        $this->Po_model->add_reply($id, $user_id, $message);
        $this->session->set_flashdata('success', 'Balasan terkirim.');
        redirect($this->menu_key . '/detail/' . $id . '#riwayat-approval');
    }

    private function _load_audit_users($header) {
        $ids = array_filter([
            $header->submitted_by ?? null,
            $header->hod_approved_by ?? null,
            $header->endorsed_by ?? null,
            $header->final_approved_by ?? null,
            $header->rejected_by ?? null,
            $header->returned_to_draft_by ?? null,
        ]);
        if (empty($ids)) return [];
        $users = $this->db->select('id, fullname')->where_in('id', $ids)->get('users')->result();
        $map = [];
        foreach ($users as $u) $map[$u->id] = $u->fullname;
        return $map;
    }

    // ===================== APPROVAL ACTIONS (3-stage, praktis 1-stage) =====================

    public function approve_hod($id) {
        if (!check_access($this->menu_key, 'head_dept')) {
            show_error('Anda tidak memiliki izin approve Head of Dept.', 403);
        }
        $res = $this->Po_model->approve_hod((int)$id, $this->session->userdata('user_id'));
        $this->_set_flash($res, $this->po_type_label . ' berhasil di-approve Head of Dept.');
        redirect($this->menu_key . '/detail/' . (int)$id);
    }

    public function approve_endorsement($id) {
        if (!check_access($this->menu_key, 'endorsement')) {
            show_error('Anda tidak memiliki izin endorsement.', 403);
        }
        $res = $this->Po_model->approve_endorsement((int)$id, $this->session->userdata('user_id'));
        $this->_set_flash($res, $this->po_type_label . ' berhasil di-endorse.');
        redirect($this->menu_key . '/detail/' . (int)$id);
    }

    public function approve_final($id) {
        if (!check_access($this->menu_key, 'approval')) {
            show_error('Anda tidak memiliki izin approve final.', 403);
        }
        $res = $this->Po_model->approve_final((int)$id, $this->session->userdata('user_id'));
        $this->_set_flash($res, $this->po_type_label . ' berhasil di-approve final (Confirmed).');
        if (!empty($res['success'])) { $this->_notify_po_approved((int) $id); }
        redirect($this->menu_key . '/detail/' . (int)$id);
    }

    /**
     * Notifikasi "PO Approved" + "PO Released" (ERP Revision Notes,
     * [[erp_revision_cross_cutting]]) -> creator PO. Digabung jadi 1 event
     * krn di sistem ini tidak ada aksi "release" terpisah dari approve final
     * (begitu Confirmed, PO langsung actionable/siap diterima gudang).
     */
    private function _notify_po_approved($id) {
        $header = $this->Po_model->get_po_header_by_id($id);
        if (!$header) return;
        $this->notify_users(
            [(int) $header->created_by],
            'PO Disetujui',
            'PO ' . $header->po_number . ' telah di-approve final (Confirmed) dan siap diproses/diterima.',
            $this->main_menu_key . '/detail/' . $id,
            'success', 'fa-check-circle',
            $this->main_menu_key, $id
        );
    }

    public function return_to_draft($id) {
        $can_action = check_access($this->menu_key, 'head_dept')
                   || check_access($this->menu_key, 'endorsement')
                   || check_access($this->menu_key, 'approval');
        if (!$can_action) show_error('Tidak ada izin return.', 403);

        $reason = trim($this->input->post('return_reason') ?? '');
        if (!$reason) {
            $this->session->set_flashdata('error', 'Alasan return wajib diisi.');
            redirect($this->menu_key . '/detail/' . (int)$id);
        }
        $res = $this->Po_model->return_to_draft((int)$id, $this->session->userdata('user_id'), $reason);
        $this->_set_flash($res, $this->po_type_label . ' dikembalikan ke Draft.');
        redirect($this->menu_key . '/detail/' . (int)$id);
    }

    public function reject($id) {
        $can_action = check_access($this->menu_key, 'head_dept')
                   || check_access($this->menu_key, 'endorsement')
                   || check_access($this->menu_key, 'approval');
        if (!$can_action) show_error('Tidak ada izin reject.', 403);

        $reason = trim($this->input->post('reject_reason') ?? '');
        if (!$reason) {
            $this->session->set_flashdata('error', 'Alasan reject wajib diisi.');
            redirect($this->menu_key . '/detail/' . (int)$id);
        }
        $res = $this->Po_model->reject_approval((int)$id, $this->session->userdata('user_id'), $reason);
        $this->_set_flash($res, $this->po_type_label . ' ditolak.');
        redirect($this->menu_key . '/detail/' . (int)$id);
    }

    // Backward-compat aliases (kalau ada link/form lama yang panggil endpoint lama)
    // Alias URL lama. Diarahkan ke approve_final() (bukan approve_hod lagi)
    // karena PO cuma punya 1 gate — lihat catatan di detail().
    public function approve_po($id) { return $this->approve_final($id); }
    public function reject_po($id)  { return $this->reject($id); }

    public function unsubmit_po($id) {
        if (!check_access($this->menu_key, 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $res = $this->Po_model->return_to_draft((int)$id, $this->session->userdata('user_id'), '[unsubmit by user]');
        $this->_set_flash($res, $this->po_type_label . ' dikembalikan ke Draft.');
        redirect($this->menu_key . '/detail/' . (int)$id);
    }

    private function _set_flash($res, $success_message) {
        if (!empty($res['success'])) {
            $this->session->set_flashdata('success', $success_message);
        } else {
            $this->session->set_flashdata('error', $res['message'] ?? 'Aksi gagal.');
        }
    }

    // ===================== ITEM EDIT/DELETE =====================
    //
    // Dulu berjudul "by approver": siapa pun pemegang izin approval boleh
    // mengubah/menghapus baris PO selama PO ada di tahap approval. Itu
    // menembus seluruh rancangan kunci-edit PO (auth_helper: mode Admin +
    // ?admin_edit=1 + baris terkunci kalau sudah ada GRN), dan tombolnya
    // muncul begitu saja tanpa membuka kunci apa pun.
    // Sejak 2026-09-10 keduanya tunduk pada aturan yang sama.

    /**
     * "Barang PO ini sudah diterima?" — penanda yang mengunci baris item.
     * Cerminan Purchase_order::_has_receipts(); di-override subclass Asset
     * karena aset tidak lewat GRN melainkan asset_register.
     */
    protected function _has_receipts($id) {
        return $this->Po_model->check_if_po_has_receipts($id);
    }

    /** Boleh mengubah/menghapus baris item PO ini dari halaman approval? */
    private function _inline_item_edit_allowed($header) {
        return is_po_admin_edit($header)
            && can_edit_po_items($header, $this->main_menu_key, $this->_has_receipts($header->id));
    }


    public function edit_item($detail_id) {
        $can_edit = check_access($this->menu_key, 'head_dept')
                 || check_access($this->menu_key, 'endorsement')
                 || check_access($this->menu_key, 'approval');
        if (!$can_edit) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $detail_id  = (int)$detail_id;
        $detail     = $this->Po_model->get_detail_by_id($detail_id);
        if (!$detail) return $this->_json(['success' => false, 'message' => 'Item tidak ditemukan']);

        $header = $this->Po_model->get_po_header_by_id($detail->po_header_id);
        if (!$header || $header->type !== $this->po_type || !in_array($header->status, ['Submitted','Approved Head Dept','Endorsed'])) {
            return $this->_json(['success' => false, 'message' => 'PO tidak dalam tahap approval']);
        }

        // Wajib mode Buka Kunci Admin — sebelumnya izin approval saja sudah
        // cukup untuk mengubah qty & harga PO yang sedang diproses.
        if (!$this->_inline_item_edit_allowed($header)) {
            return $this->_json(['success' => false, 'message' => 'PO terkunci. Buka "Buka Kunci Edit (Admin)" dulu untuk mengubah item.']);
        }

        $qty        = (float)$this->input->post('qty');
        $base_price = (float)preg_replace('/[^\d.]/', '', $this->input->post('base_price') ?? '0');
        $description = trim($this->input->post('description') ?? '');

        if ($qty <= 0) return $this->_json(['success' => false, 'message' => 'Qty harus lebih dari 0']);

        $exchange_rate = (float)($header->exchange_rate ?: 1);
        $local_price   = $base_price * $exchange_rate;

        $this->Po_model->update_item($detail_id, [
            'qty'         => $qty,
            'base_price'  => round($base_price, 4),   // 4 desimal (migration_po_price_4dec)
            'local_price' => round($local_price, 4),
            'total_local' => round($qty * $local_price, 2),
            'total'       => round($qty * $local_price, 2),
            'description' => $description,
        ]);

        return $this->_json([
            'success'   => true,
            'message'   => 'Item berhasil diperbarui.',
            'new_qty'   => $qty,
            'new_price' => $base_price,
            'new_total' => $qty * $base_price,
        ]);
    }

    public function delete_item($detail_id) {
        $can_edit = check_access($this->menu_key, 'head_dept')
                 || check_access($this->menu_key, 'endorsement')
                 || check_access($this->menu_key, 'approval');
        if (!$can_edit) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $detail_id = (int)$detail_id;

        // LUBANG LAMA (ditambal 2026-09-10): method ini SAMA SEKALI tidak
        // memeriksa PO induknya — tidak status, tidak tipe. Siapa pun yang
        // punya salah satu izin approval PO bisa menghapus baris PO MANA PUN,
        // termasuk PO Confirmed yang barangnya sudah diterima (GRN) atau PO
        // tipe lain, cukup dengan menebak detail_id di URL. Sekarang dijaga
        // sama persis dengan edit_item().
        $detail = $this->Po_model->get_detail_by_id($detail_id);
        if (!$detail) return $this->_json(['success' => false, 'message' => 'Item tidak ditemukan']);

        $header = $this->Po_model->get_po_header_by_id($detail->po_header_id);
        if (!$header || $header->type !== $this->po_type || !in_array($header->status, ['Submitted','Approved Head Dept','Endorsed'])) {
            return $this->_json(['success' => false, 'message' => 'PO tidak dalam tahap approval']);
        }
        if (!$this->_inline_item_edit_allowed($header)) {
            return $this->_json(['success' => false, 'message' => 'PO terkunci. Buka "Buka Kunci Edit (Admin)" dulu untuk menghapus item.']);
        }

        if ($this->Po_model->delete_item($detail_id)) {
            return $this->_json(['success' => true, 'message' => 'Item dihapus dari PO.']);
        }
        return $this->_json(['success' => false, 'message' => 'Gagal menghapus item.']);
    }

    // ===================== EXPORT =====================

    public function export_list_excel()
    {
        if (!check_access($this->menu_key, 'view')) show_error('Izin Ditolak.', 403);

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
        ];
        $pos = $this->_get_pos($filters);

        $sheet_title = in_array($this->po_type, ['jasa', 'asset'], true)
            ? 'DAFTAR PURCHASE ORDER ' . strtoupper($this->po_type) . ' - APPROVAL'
            : 'DAFTAR PURCHASE ORDER - APPROVAL';
        $filename_prefix = in_array($this->po_type, ['jasa', 'asset'], true)
            ? 'PO_' . ucfirst($this->po_type) . '_Approval_List_'
            : 'PO_Approval_List_';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', $sheet_title);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headers = ['No', 'PO Number', 'Tanggal', 'Supplier', 'Dibuat Oleh', 'Mata Uang', 'Subtotal', 'Status'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col.'3', $h);
        }

        $sheet->getStyle('A3:H3')->getFont()->setBold(true);
        $sheet->getStyle('A3:H3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A3:H3')->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 4;
        $no  = 1;
        foreach ($pos as $po) {
            $sheet->setCellValue('A'.$row, $no++);
            $sheet->setCellValue('B'.$row, $po->po_number);
            $sheet->setCellValue('C'.$row, date('d/m/Y', strtotime($po->po_date)));
            $sheet->setCellValue('D'.$row, $po->nama_supplier ?? '-');
            $sheet->setCellValue('E'.$row, $po->creator_name ?? '-');
            $sheet->setCellValue('F'.$row, $po->currency_code ?? 'IDR');
            $sheet->setCellValue('G'.$row, (float)$po->subtotal);
            $sheet->setCellValue('H'.$row, $po->status);
            $sheet->getStyle('G'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        foreach (range('A','H') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $filename = $filename_prefix . date('Ymd') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Dulu cuma ada di Produksi & General (Jasa & Asset tidak punya sama
     * sekali) -- sekarang berlaku semua tipe, lihat catatan bug di docblock
     * class.
     */
    public function export_list_pdf()
    {
        if (!check_access($this->menu_key, 'view')) show_error('Izin Ditolak.', 403);

        $filters = [
            'status' => $this->input->get('status'),
            'search' => $this->input->get('search'),
        ];
        $pos = $this->_get_pos($filters);

        $status_colors = [
            'Draft'     => 'orange',
            'Submitted' => 'blue',
            'Confirmed' => 'green',
            'Completed' => 'green',
            'Cancelled' => 'red',
            'Rejected'  => 'red',
        ];

        $rows = '';
        $no   = 1;
        foreach ($pos as $po) {
            $color = $status_colors[$po->status] ?? '#333';
            $rows .= '<tr>
                <td style="text-align:center">' . $no++ . '</td>
                <td>' . htmlspecialchars($po->po_number) . '</td>
                <td>' . date('d/m/Y', strtotime($po->po_date)) . '</td>
                <td>' . htmlspecialchars($po->nama_supplier ?? '-') . '</td>
                <td>' . htmlspecialchars($po->creator_name ?? '-') . '</td>
                <td style="text-align:center">' . htmlspecialchars($po->currency_code ?? 'IDR') . '</td>
                <td style="text-align:right">' . number_format((float)$po->subtotal, 2, ',', '.') . '</td>
                <td style="text-align:center;color:' . $color . ';font-weight:bold">' . htmlspecialchars($po->status) . '</td>
            </tr>';
        }

        $title = in_array($this->po_type, ['jasa', 'asset'], true)
            ? 'DAFTAR PURCHASE ORDER ' . strtoupper($this->po_type) . ' - APPROVAL'
            : 'DAFTAR PURCHASE ORDER - APPROVAL';

        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            h2 { text-align: center; margin-bottom: 6px; font-size: 14px; }
            table { width: 100%; border-collapse: collapse; }
            th { background: #4472C4; color: #fff; padding: 5px 4px; text-align: left; }
            td { padding: 4px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) td { background: #f5f5f5; }
        </style>
        <h2>' . htmlspecialchars($title) . '</h2>
        <p style="text-align:center;font-size:9px">Dicetak: ' . date('d/m/Y H:i') . '</p>
        <table>
            <thead><tr>
                <th width="4%">No</th>
                <th width="13%">PO Number</th>
                <th width="9%">Tanggal</th>
                <th width="20%">Supplier</th>
                <th width="15%">Dibuat Oleh</th>
                <th width="7%">Mata Uang</th>
                <th width="16%">Subtotal</th>
                <th width="10%">Status</th>
            </tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>';

        $filename_prefix = in_array($this->po_type, ['jasa', 'asset'], true)
            ? 'PO_' . ucfirst($this->po_type) . '_Approval_List_'
            : 'PO_Approval_List_';

        $temp_dir = APPPATH . 'cache/pdf_temp/';
        if (!is_dir($temp_dir)) { @mkdir($temp_dir, 0777, true); }
        $mpdf = new Mpdf(['tempDir' => $temp_dir, 'mode' => 'utf-8', 'format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename_prefix . date('Ymd') . '.pdf', 'D');
        exit;
    }

    public function export_detail_excel($id)
    {
        redirect($this->main_menu_key . '/export_excel/' . $id);
    }

    public function export_detail_pdf($id)
    {
        redirect($this->main_menu_key . '/export_pdf/' . $id);
    }

    // ===================== HELPER =====================

    protected function _json($data) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
