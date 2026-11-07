<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

/**
 * Purchase_request_general — CONTROLLER DASAR bersama utk PR "non-produksi"
 * (General/Jasa/Asset). User 2026-09-06 minta digabung ("kenapa masih ada
 * [Jasa PR, Asset PR] terpisah? bukannya IRF-nya General/Jasa/Asset/Produksi
 * sudah jadi 1, harusnya PR-nya juga jadi 1") setelah PO lebih dulu digabung
 * dgn pola yg sama (lihat memory po_controllers_merge.md).
 *
 * BEDA dgn PO: Produksi (`Purchase_request.php`/MPR) TIDAK ikut digabung ke
 * sini — PR Produksi punya sumber data & fitur yang genuinely berbeda (BOM/
 * Sales Contract "IPO reference" tracking dari MRF, bukan dari IRF Consumable,
 * lihat [[revision_mpr_from_mrf]]) dan TIDAK punya fitur admin-lock/PO-lock
 * di bawah ini sama sekali. `Purchase_request.php` dibiarkan berdiri sendiri
 * persis seperti sebelumnya, menu "MPR" tetap terpisah di sidebar
 * (PRO-WAREHOUSE). Base gabungan yang dibuat di sini KHUSUS utk keluarga
 * General/Jasa/Asset (IPR — semuanya sumbernya IRF Consumable, sudah
 * disatukan sejak [[revision_irf_auto_pr_at_issuance]]).
 *
 * `Purchase_request_jasa`/`_asset` (MASIH 2 file terpisah secara fisik!) jadi
 * SUBCLASS TIPIS dari class ini. URL, menu sidebar per-tipe, dan permission
 * Role Access SAMA SEKALI TIDAK BERUBAH — CI3 routing tetap berdasar nama
 * FILE/CLASS, jadi `purchase_request_jasa/index` dst tetap jalan persis
 * seperti sebelumnya.
 *
 * Properti yang WAJIB di-override tiap subclass:
 *   $pr_type       nilai kolom pr_header.type ('non_produksi'|'jasa'|'asset')
 *   $menu_key      menu_key utk check_access()/can_edit_pr()/dst
 *   $po_menu_key   menu_key PO tujuan saat create_po_from_pr() (jg dipakai
 *                   utk gate check_access sebelum convert — General
 *                   SEBELUMNYA salah cek 'purchase_order' generik, lihat
 *                   catatan bug di bawah)
 *   $log_module    label utk add_log() ADMIN_EDIT
 *   $search_item_type   null (General, cari lintas tipe barang minus
 *                   material) | 'jasa' | 'asset' (short-circuit ke 1 master)
 *
 * Properti OPSIONAL (default = milik General):
 *   $default_ppn_rate   rate PPN default saat checkbox include_ppn dicentang
 *                        tapi field kosong (default 10)
 *
 * Method yang boleh di-override:
 *   _included_types()   daftar pr_header.type yg MASUK ke listing (index()/
 *                        export_list_excel/pdf) — default cuma $this->pr_type,
 *                        General override jadi gabungan
 *                        ['non_produksi','jasa','asset'] (sidebar Jasa & Asset
 *                        digabung ke sini, pola sama spt GMPO). Method yang
 *                        operasi ke 1 PR spesifik TETAP strict cocok
 *                        $this->pr_type.
 *   _title_index()/_title_detail()/_form_label()/_export_labels()  teks judul
 *
 * BUG yang KETEMU+DIPERBAIKI sekalian pas konsolidasi ini (SENGAJA, dicatat
 * di memory pr_controllers_merge.md, pola sama spt po_controllers_merge.md):
 * - `create_po_from_pr()` General SEBELUMNYA cek permission generik
 *   `purchase_order` (bukan `purchase_order_general`) — sisa copy-paste dari
 *   Purchase_request.php (Produksi, yang menu PO-nya memang 'purchase_order').
 *   Jasa & Asset sudah benar dari awal. Sekarang SEMUA tipe konsisten cek
 *   menu_key PO miliknya sendiri.
 * - Jasa & Asset SEBELUMNYA TIDAK PUNYA sama sekali: manual-create block yg
 *   ramah (mereka 404 polos kalau endpoint create()/add_header() diakses
 *   krn methodnya tidak ada — General SUDAH py block dgn flashdata pesan
 *   jelas), fitur admin-lock (`can_edit_pr()`/`admin_edit_mode`/
 *   `can_unlock_admin`), PO-row-lock (`_row_locked_by_po` — cegah qty baris
 *   yg sudah ditarik ke PO diubah/dihapus), `load_bom_from_sc()`,
 *   `get_pr_items()`, `export_list_pdf()`. Sekarang SEMUA tipe konsisten
 *   kebagian kemampuan yang sama (Jasa/Asset tidak wajib PAKAI semuanya di
 *   view-nya, tapi endpoint-nya tersedia & konsisten — sama filosofinya dgn
 *   PO merge yg juga "naikkan" Jasa/Asset ke fitur lengkap Produksi/General).
 */
class Purchase_request_general extends MY_Controller {

    protected $pr_type = 'non_produksi';
    protected $menu_key = 'purchase_request_general';
    protected $po_menu_key = 'purchase_order_general';
    /** Controller approval pasangannya — tujuan balik setelah kirim balasan
     *  dari halaman approval (lihat reply()). */
    protected $approval_menu_key = 'purchase_request_approval_general';
    protected $log_module = 'Purchase Request Non Produksi';
    protected $search_item_type = null;
    protected $default_ppn_rate = 10;

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Purchase_request_model', 'Sc_model', 'Bom_model', 'Material_model', 'Inventory_model', 'Semifinished_model', 'Machine_model']);
        $this->load->helper('url');
    }

    /**
     * Daftar pr_type yang MASUK ke listing controller ini (index()/export
     * list). General (class dasar ini) py DEFAULT gabungan
     * non_produksi+jasa+asset (sidebar Jasa & Asset digabung ke sini, pola
     * sama spt GMPO) — Jasa & Asset (subclass) OVERRIDE balik ke cuma tipe
     * sendiri di file masing2, supaya URL langsung
     * `purchase_request_jasa`/`_asset` (kalau ada yg bookmark/akses manual)
     * tetap scoped ke tipe itu saja, bukan ikut nampilin gabungan.
     */
    protected function _included_types() {
        return ['non_produksi', 'jasa', 'asset'];
    }

    protected function _title_index() {
        return 'PR General';
    }

    protected function _title_detail() {
        return 'Detail PR General';
    }

    protected function _form_title() {
        return 'PURCHASE REQUEST - NON PRODUKSI';
    }

    protected function _form_subtitle() {
        return '采购申请单';
    }

    protected function _item_label() {
        return 'Item';
    }

    // ===================== LIST =====================

    /**
     * Filter listing IPR — dipakai bareng oleh index(), export_list_excel(),
     * dan export_list_pdf() supaya cakupan datanya SELALU sama (dulu ketiganya
     * menyalin array filter sendiri-sendiri, gampang beda kalau salah satu
     * diubah).
     *
     * ATURAN VISIBILITAS (user 2026-09-09, dikonfirmasi memang disengaja):
     * user biasa CUMA lihat PR yang dia buat sendiri (requester_id ATAU
     * created_by = dia). Admin (`is_admin()`, role_id=1) dapat tombol
     * "Lihat Semua PR" -> ?all=1 yang melepas batasan itu. Non-admin yang
     * iseng menambahkan ?all=1 di URL TETAP dibatasi, karena flag-nya
     * diperiksa ulang di sini, bukan cuma disembunyikan di view.
     */
    protected function _list_filters()
    {
        $view_all = $this->_list_view_all();

        return [
            'status'       => $this->input->get('status'),
            'search'       => $this->input->get('search'),
            'type'         => $this->_included_types(),
            'requester_id' => $view_all ? null : $this->session->userdata('user_id'),
        ];
    }

    /** TRUE kalau permintaan "lihat semua" sah (admin + ?all=1). */
    protected function _list_view_all()
    {
        return is_admin() && $this->input->get('all') == '1';
    }

    public function index() {
        if (!check_access($this->menu_key, 'view')) show_error(lang_line('purchase_request_msg_no_permission', 'Anda tidak memiliki izin.'), 403);

        $filters = $this->_list_filters();
        $prs     = $this->Purchase_request_model->get_all($filters);

        // "PR ini sudah jadi PO belum?" (user 2026-09-11). Diambil sekali
        // untuk seluruh baris (1 query), bukan per baris.
        $po_links = $this->Purchase_request_model->get_po_links_for_prs(
            array_map(function ($p) { return $p->id; }, $prs)
        );

        $data = [
            'title'    => $this->_title_index(),
            'prs'      => $prs,
            'po_links' => $po_links,
            'filters'  => $filters,
            'statuses' => ['Draft', 'Submitted', 'Approved', 'Rejected'],
            'suppliers'=> $this->db->order_by('nama_supplier','ASC')->get('suppliers')->result(),
            'pr_type'  => $this->pr_type,
            // Dipakai view utk menampilkan tombol "Lihat Semua PR" (admin saja)
            // & badge penanda kalau mode itu sedang aktif.
            'can_view_all' => is_admin(),
            'view_all'     => $this->_list_view_all(),
            'list_route'   => $this->menu_key,
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view($this->menu_key . '/index', $data);
        $this->load->view('templates/footer');
    }

    // ===================== DETAIL =====================

    public function detail($id) {
        if (!check_access($this->menu_key, 'view')) show_error(lang_line('purchase_request_msg_no_permission', 'Anda tidak memiliki izin.'), 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type) show_404();

        $data = [
            'title'          => $this->_title_detail(),
            'header'         => $header,
            'details'        => $this->Purchase_request_model->get_details_by_id($id),
            // Progres PO per baris: sudah masuk PO berapa, PO mana saja.
            'po_progress'    => $this->Purchase_request_model->get_po_progress_by_pr($id),
            'open_contracts' => $this->Sc_model->get_open_contracts(),
            'suppliers'      => $this->db->order_by('nama_supplier','ASC')->get('suppliers')->result(),
            // Draft = alur normal. Non-Draft cuma terbuka kalau Admin menekan
            // tombol "Buka Kunci Edit" (flag admin_edit=1 di URL) — status &
            // approval PR tidak ikut berubah.
            'can_edit'         => can_edit_pr($header, $this->menu_key),
            'admin_edit_mode'  => is_pr_admin_edit($header),
            'can_unlock_admin' => can_unlock_pr_admin_edit($header),
            'can_submit'       => $header->status === 'Draft' && check_access($this->menu_key, 'edit'),
            // Workflow approval sekarang 3-stage. Creator hanya bisa unsubmit selagi
            // PR masih di stage awal 'Submitted' (sebelum Head of Dept approve).
            'can_unsubmit'     => $header->status === 'Submitted' && check_access($this->menu_key, 'edit'),
            // Flag legacy — view tidak memanggilnya lagi, tapi tetap di-pass supaya
            // tidak meledak jika ada modul lain yang merefer.
            'can_approve'      => false,
            'in_approval_flow' => in_array($header->status, ['Submitted','Approved Head Dept','Endorsed']),
            // Riwayat approval + thread balasan Back to Draft (2026-09-06).
            // Ditaruh di halaman requestor juga — sebelumnya requestor sama
            // sekali tidak bisa lihat KENAPA PR-nya dikembalikan dari halaman
            // miliknya sendiri (persis temuan yang sama waktu PO dikerjakan).
            'approval_logs'    => $this->Purchase_request_model->get_approval_logs($id),
            'can_reply'        => can_reply_return_thread($header),
        ];

        $this->load->view('templates/header',  $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar',  $data);
        $this->load->view($this->menu_key . '/detail', $data);
        $this->load->view('templates/footer');
    }

    // ===================== CREATE (DIBLOK — semua PR "non-produksi" sekarang selalu dari IRF) =====================

    /**
     * Sejak 2026-11-05 (rebrand Acropoly) PR BOLEH dibuat manual lagi -- lalu
     * diisi item manual dan/atau "Tarik dari Permintaan Barang" -- dan wajib
     * di-approve 1 tahap. Jalur lama (dari Stock Alert) cukup membuka form
     * Buat PR di halaman daftar.
     */
    public function create() {
        if (!check_access($this->menu_key, 'add')) show_error(lang_line('purchase_request_msg_no_permission_add', 'Anda tidak memiliki izin.'), 403);
        redirect('purchase_request_general?open=create');
    }

    public function add_header() {
        if (!check_access($this->menu_key, 'add')) show_error(lang_line('purchase_request_msg_no_permission_add', 'Anda tidak memiliki izin.'), 403);

        $user_id = $this->session->userdata('user_id');
        $purpose = trim((string) $this->input->post('purpose'));
        if ($purpose === '') {
            $this->session->set_flashdata('error', 'Tujuan permintaan wajib diisi.');
            redirect('purchase_request_general?open=create');
        }
        $data = [
            'pr_number'    => $this->Purchase_request_model->generate_pr_number($this->pr_type),
            'pr_date'      => $this->input->post('pr_date') ?: date('Y-m-d'),
            'department'   => trim((string) $this->input->post('department')) ?: (string) $this->session->userdata('departemen'),
            'type'         => $this->pr_type,
            'purpose'      => $purpose,
            'remarks'      => $this->input->post('remarks'),
            'requester_id' => $user_id,
            'created_by'   => $user_id,
            'status'       => 'Draft',
        ];
        $id = $this->Purchase_request_model->insert_header($data);
        if ($id) {
            $this->session->set_flashdata('success', 'Purchase Request ' . $data['pr_number'] . ' dibuat (Draft). Tambahkan item manual atau tarik dari Permintaan Barang, lalu Submit untuk approval.');
            redirect($this->menu_key . '/detail/' . $id);
        }
        $this->session->set_flashdata('error', lang_line('purchase_request_msg_create_failed', 'Gagal membuat Purchase Request.'));
        redirect('purchase_request_general');
    }

    protected function _no_manual_create_message() {
        return 'PR General sekarang selalu berawal dari Permintaan Barang — tidak bisa dibuat manual lagi. Buat PR dari menu Outstanding Permintaan Barang untuk item yang stock-nya kurang.';
    }

    // ===================== CRUD HEADER =====================

    public function update_header($id) {
        if (!check_access($this->menu_key, 'edit')) show_error('Anda tidak memiliki izin.', 403);

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type) show_404();

        $admin_edit = is_pr_admin_edit($header);
        if (!can_edit_pr($header, $this->menu_key)) {
            $this->session->set_flashdata('error', $this->_locked_message($header));
            return redirect($this->menu_key . '/detail/' . $id);
        }

        $data = [
            'pr_date'     => $this->input->post('pr_date'),
            'department'  => $this->input->post('department'),
            'purpose'     => $this->input->post('purpose'),
            'remarks'     => $this->input->post('remarks'),
        ];

        if ($this->Purchase_request_model->update_header($id, $data, $admin_edit)) {
            if ($admin_edit) {
                add_log('ADMIN_EDIT', $this->log_module, 'Edit header PR ' . $header->pr_number . ' pada status ' . $header->status . ' (override Admin).');
            }
            $this->session->set_flashdata('success', lang_line('purchase_request_msg_update_header_success', 'Header PR berhasil diperbarui.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_update_header_failed', 'Gagal memperbarui header PR.'));
        }
        redirect($this->menu_key . '/detail/' . $id . ($admin_edit ? '?admin_edit=1' : ''));
    }

    public function delete_header($id) {
        if (!check_access($this->menu_key, 'delete')) show_error(lang_line('purchase_request_msg_no_permission_delete', 'Anda tidak memiliki izin.'), 403);

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type) show_404();

        if ($this->Purchase_request_model->delete_header($id)) {
            $this->session->set_flashdata('success', lang_line('purchase_request_msg_delete_success', 'Purchase Request berhasil dihapus.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_delete_failed', 'Gagal menghapus PR. Pastikan status Draft dan tidak ada item.'));
        }
        redirect($this->menu_key);
    }

    // ===================== ITEM CRUD (AJAX) =====================

    public function add_single_item($pr_id) {
        if (!check_access($this->menu_key, 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $header = $this->Purchase_request_model->get_header_by_id($pr_id);
        if (!$header || $header->type !== $this->pr_type) return $this->_json(['success' => false, 'message' => 'PR tidak ditemukan']);
        if (!can_edit_pr($header, $this->menu_key)) {
            return $this->_json(['success' => false, 'message' => $this->_locked_message($header)]);
        }

        $item_str = $this->input->post('item_id');
        if (!$item_str || strpos($item_str, '-') === false) {
            return $this->_json(['success' => false, 'message' => 'Item tidak valid']);
        }

        list($item_type, $item_id) = explode('-', $item_str, 2);
        $item_id = (int)$item_id;
        $qty     = (float)($this->input->post('qty') ?: 1);

        $existing = $this->Purchase_request_model->check_existing_item($pr_id, $item_id, $item_type);
        if ($existing) {
            // Item yang sama sudah ada → normalnya qty-nya ditambah. Tapi kalau
            // baris itu sudah ditarik ke PO, qty-nya jadi acuan PO dan terkunci.
            if ($this->_row_locked_by_po($header, $existing->id)) {
                return $this->_json(['success' => false, 'message' => 'Item ini sudah ada di baris yang terkunci karena sudah ditarik ke PO. Qty-nya tidak bisa diubah dari sini.']);
            }
            $new_qty = (float)$existing->qty + $qty;
            $this->Purchase_request_model->update_item_qty($existing->id, $new_qty);
            if (is_pr_admin_edit($header)) {
                add_log('ADMIN_EDIT', $this->log_module, 'Tambah qty item #' . $existing->id . ' pada PR ' . $header->pr_number . ' di status ' . $header->status . ' (qty=' . $new_qty . ', override Admin).');
            }
            return $this->_json(['success' => true, 'message' => 'Qty item diperbarui.', 'detail_id' => $existing->id, 'updated' => true, 'new_qty' => $new_qty]);
        }

        $insert = [
            'pr_header_id' => $pr_id,
            'item_id'      => $item_id,
            'item_type'    => $item_type,
            'qty'          => $qty,
            'description'  => $this->input->post('description'),
            'remarks'      => $this->input->post('remarks'),
        ];

        $new_id = $this->Purchase_request_model->insert_item($insert);
        if ($new_id) {
            if (is_pr_admin_edit($header)) {
                add_log('ADMIN_EDIT', $this->log_module, 'Tambah item ' . $item_type . '#' . $item_id . ' (qty=' . $qty . ') pada PR ' . $header->pr_number . ' di status ' . $header->status . ' (override Admin).');
            }
            return $this->_json(['success' => true, 'message' => 'Item berhasil ditambahkan.', 'detail_id' => $new_id, 'updated' => false]);
        }
        return $this->_json(['success' => false, 'message' => 'Gagal menambahkan item.']);
    }

    public function update_item() {
        if (!check_access($this->menu_key, 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $detail_id = (int)$this->input->post('detail_id');
        $qty       = (float)$this->input->post('qty');

        if ($qty <= 0) return $this->_json(['success' => false, 'message' => 'Qty harus > 0']);

        $header = $this->_header_of_detail($detail_id);
        if (!$header) return $this->_json(['success' => false, 'message' => 'Item tidak ditemukan']);
        if (!can_edit_pr($header, $this->menu_key)) {
            return $this->_json(['success' => false, 'message' => $this->_locked_message($header)]);
        }
        if ($this->_row_locked_by_po($header, $detail_id)) {
            return $this->_json(['success' => false, 'message' => 'Baris ini sudah ditarik ke PO, jadi qty-nya tidak bisa diubah. Tambahkan item baru kalau kebutuhannya bertambah.']);
        }

        // Ganti item/kode barang (opsional): item_id dikirim dengan format
        // "type-id" seperti di baris tambah item. Kalau tidak dikirim, cuma
        // qty/deskripsi/remarks yang berubah.
        $item_change = $this->Purchase_request_model->resolve_item_change($detail_id, $header->id, $this->input->post('item_id'));
        if (isset($item_change['error'])) {
            return $this->_json(['success' => false, 'message' => $item_change['error']]);
        }

        $this->Purchase_request_model->update_item($detail_id, array_merge([
            'qty'         => $qty,
            'description' => $this->input->post('description'),
            'remarks'     => $this->input->post('remarks'),
        ], $item_change['fields']));
        if (is_pr_admin_edit($header)) {
            $swap = empty($item_change['fields']) ? '' : ', item diganti ke ' . $item_change['fields']['item_type'] . '#' . $item_change['fields']['item_id'];
            add_log('ADMIN_EDIT', $this->log_module, 'Edit item #' . $detail_id . ' pada PR ' . $header->pr_number . ' di status ' . $header->status . ' (qty=' . $qty . $swap . ', override Admin).');
        }
        return $this->_json(['success' => true, 'message' => 'Item diperbarui.']);
    }

    public function delete_item($detail_id) {
        if (!check_access($this->menu_key, 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $header = $this->_header_of_detail($detail_id);
        if (!$header) return $this->_json(['success' => false, 'message' => 'Item tidak ditemukan']);
        if (!can_edit_pr($header, $this->menu_key)) {
            return $this->_json(['success' => false, 'message' => $this->_locked_message($header)]);
        }
        if ($this->_row_locked_by_po($header, $detail_id)) {
            return $this->_json(['success' => false, 'message' => 'Baris ini sudah ditarik ke PO, jadi tidak bisa dihapus. Batalkan dulu PO-nya kalau memang salah.']);
        }

        if ($this->Purchase_request_model->delete_item($detail_id)) {
            if (is_pr_admin_edit($header)) {
                add_log('ADMIN_EDIT', $this->log_module, 'Hapus item #' . $detail_id . ' dari PR ' . $header->pr_number . ' di status ' . $header->status . ' (override Admin).');
            }
            return $this->_json(['success' => true, 'message' => 'Item dihapus.']);
        }
        return $this->_json(['success' => false, 'message' => 'Gagal menghapus item.']);
    }

    public function search_items() {
        $term = $this->input->get('term') ?: $this->input->post('term');
        $results = $this->Purchase_request_model->search_items($term, $this->search_item_type);
        $out = [];
        foreach ($results as $item) {
            if ($this->search_item_type === null && $item->type === 'material') continue;
            $out[] = [
                'id'   => $item->type . '-' . $item->id,
                'text' => $this->search_item_type
                    ? ($item->code . ' - ' . $item->name)
                    : ('[' . strtoupper($item->type) . '] ' . $item->code . ' - ' . $item->name),
                'code' => $item->code,
                'name' => $item->name,
                'type' => $item->type,
                'unit' => $item->unit,
            ];
        }
        $this->output->set_content_type('application/json')->set_output(json_encode(['results' => $out]));
    }

    // ===================== GET PR ITEMS (FOR AJAX RENDERING) =====================

    public function get_pr_items($pr_id) {
        if (!check_access($this->menu_key, 'view')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $details = $this->Purchase_request_model->get_details_by_id($pr_id);
        if (empty($details)) {
            return $this->_json(['success' => true, 'items' => []]);
        }

        $items = [];
        foreach ($details as $d) {
            $items[] = [
                'id'              => $d->id,
                'item_id'         => $d->item_id,
                'item_type'       => $d->item_type,
                'item_code'       => $d->item_code,
                'item_name'       => $d->item_name,
                'item_unit'       => $d->item_unit,
                'qty'             => $d->qty,
                'description'     => $d->description,
                'remarks'         => $d->remarks,
                'sc_number'       => $d->sc_number,
            ];
        }

        return $this->_json(['success' => true, 'items' => $items]);
    }

    // ===================== BOM LOADING (khusus item ber-BOM, mis. material) =====================

    public function load_bom_from_sc($pr_id) {
        if (!check_access($this->menu_key, 'edit')) {
            return $this->_json(['success' => false, 'message' => 'Izin ditolak']);
        }

        $header = $this->Purchase_request_model->get_header_by_id($pr_id);
        if (!$header || $header->type !== $this->pr_type) return $this->_json(['success' => false, 'message' => 'PR tidak ditemukan']);
        if (!can_edit_pr($header, $this->menu_key)) {
            return $this->_json(['success' => false, 'message' => $this->_locked_message($header)]);
        }

        $sc_id = $this->input->post('sc_id');
        if (!$sc_id) return $this->_json(['success' => false, 'message' => 'Sales Contract tidak valid']);

        $sc_details = $this->Sc_model->get_items_by_contract($sc_id);
        if (empty($sc_details)) {
            return $this->_json(['success' => false, 'message' => 'Sales Contract tidak memiliki item']);
        }

        $inserted = 0;
        $updated  = 0;
        $errors   = [];

        foreach ($sc_details as $sc_item) {
            if ($sc_item->item_type !== 'finished') continue;

            $fg_id    = $sc_item->fg_id;
            $sc_qty   = (float)$sc_item->qty;
            $bom_items = $this->Bom_model->get_bom_by_fg($fg_id);

            if (empty($bom_items)) {
                $errors[] = 'FG ' . $sc_item->nama_barang . ' tidak memiliki BOM';
                continue;
            }

            foreach ($bom_items as $bom) {
                $calc_qty = (float)$bom->qty * $sc_qty;
                $existing = $this->Purchase_request_model->check_existing_item($pr_id, $bom->material_id, $bom->item_type);

                if ($existing) {
                    // Baris yang sudah masuk PO qty-nya terkunci — lewati, jangan
                    // sampai load BOM diam-diam menggeser acuan PO.
                    if ($this->_row_locked_by_po($header, $existing->id)) {
                        $errors[] = 'Item baris #' . $existing->id . ' dilewati (sudah masuk PO)';
                        continue;
                    }
                    $this->Purchase_request_model->update_item_qty($existing->id, $existing->qty + $calc_qty);
                    $updated++;
                } else {
                    $this->Purchase_request_model->insert_item([
                        'pr_header_id'     => $pr_id,
                        'item_id'          => $bom->material_id,
                        'item_type'        => $bom->item_type,
                        'sales_contract_id'=> $sc_id,
                        'qty'              => $calc_qty,
                        'remarks'          => 'Auto dari BOM FG: ' . ($sc_item->nama_barang ?? ''),
                    ]);
                    $inserted++;
                }
            }
        }

        $msg = $inserted > 0 ? "$inserted item baru ditambahkan. " : '';
        $msg .= $updated > 0  ? "$updated item qty diperbarui. " : '';
        $msg .= !empty($errors) ? implode(', ', $errors) : '';

        if ($inserted + $updated > 0) {
            $updated_details = $this->Purchase_request_model->get_details_by_id($pr_id);
            $items = [];
            foreach ($updated_details as $d) {
                $items[] = [
                    'id'              => $d->id,
                    'item_id'         => $d->item_id,
                    'item_type'       => $d->item_type,
                    'item_code'       => $d->item_code,
                    'item_name'       => $d->item_name,
                    'item_unit'       => $d->item_unit,
                    'qty'             => $d->qty,
                    'description'     => $d->description,
                    'remarks'         => $d->remarks,
                    'sc_number'       => $d->sc_number,
                ];
            }
            return $this->_json(['success' => true, 'message' => trim($msg), 'items' => $items]);
        }
        return $this->_json(['success' => false, 'message' => trim($msg) ?: 'Tidak ada material yang ditambahkan.']);
    }

    // ===================== APPROVAL FLOW =====================

    public function submit_pr($id) {
        if (!check_access($this->menu_key, 'edit')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin ditolak.'), 403);

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type || $header->status !== 'Draft') {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_invalid_status_submit', 'PR tidak bisa disubmit dalam status ini.'));
            return redirect($this->menu_key . '/detail/' . $id);
        }

        $details = $this->Purchase_request_model->get_details_by_id($id);
        if (empty($details)) {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_requires_items', 'PR harus memiliki minimal 1 item sebelum disubmit.'));
            return redirect($this->menu_key . '/detail/' . $id);
        }

        $user_id = $this->session->userdata('user_id');
        if ($this->Purchase_request_model->update_status($id, 'Submitted', $user_id)) {
            $this->session->set_flashdata('success', lang_line('purchase_request_msg_submit_success', 'PR berhasil disubmit ke atasan untuk disetujui.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_submit_failed', 'Gagal submit PR.'));
        }
        redirect($this->menu_key . '/detail/' . $id);
    }

    public function unsubmit_pr($id) {
        if (!check_access($this->menu_key, 'edit')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin ditolak.'), 403);

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type || $header->status !== 'Submitted') {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_invalid_status_unsubmit', 'PR tidak bisa dibatalkan submitnya.'));
            return redirect($this->menu_key . '/detail/' . $id);
        }

        if ($this->Purchase_request_model->update_status($id, 'Draft')) {
            $this->session->set_flashdata('success', lang_line('purchase_request_msg_unsubmit_success', 'PR dikembalikan ke Draft.'));
        } else {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_unsubmit_failed', 'Gagal membatalkan submit PR.'));
        }
        redirect($this->menu_key . '/detail/' . $id);
    }

    // ===================== THREAD BALASAN BACK TO DRAFT =====================

    /**
     * Kirim balasan pada thread Back to Draft (2026-09-06, user: "untuk PR okee").
     *
     * Sengaja ditaruh di controller INI (requestor-facing), bukan di controller
     * approval seperti PO — karena `Purchase_request_approval_*` punya gate
     * `check_access(view)` di constructor-nya, yang akan menolak requestor biasa.
     * Controller ini tidak punya gate constructor, dan otorisasi sebenarnya
     * memang ada di can_reply_return_thread(): cuma pembuat PR & approver yang
     * mengembalikannya, selagi PR masih Draft hasil Back to Draft.
     * Halaman approval mem-POST ke sini lintas-controller.
     */
    public function reply($id) {
        $id = (int) $id;
        if ($id <= 0) show_404();

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type) show_404();

        $back = $this->input->post('redirect') === 'approval'
            ? $this->approval_menu_key . '/detail/' . $id
            : $this->menu_key . '/detail/' . $id;

        if (!can_reply_return_thread($header)) {
            $this->session->set_flashdata('error', 'Balasan cuma bisa dikirim oleh pembuat PR & approver yang mengembalikannya, selagi PR masih Draft hasil Back to Draft.');
            return redirect($back);
        }

        $message = trim((string) $this->input->post('message'));
        if ($message === '') {
            $this->session->set_flashdata('error', 'Pesan balasan tidak boleh kosong.');
            return redirect($back);
        }

        $res = $this->Purchase_request_model->add_reply($id, $this->session->userdata('user_id'), $message);
        if (empty($res['success'])) {
            $this->session->set_flashdata('error', $res['message'] ?? 'Gagal mengirim balasan.');
            return redirect($back);
        }

        $this->session->set_flashdata('success', 'Balasan terkirim.');
        redirect($back . '#riwayat-approval');
    }

    // ===================== CREATE PO FROM PR =====================

    public function create_po_from_pr($id) {
        if (!check_access($this->po_menu_key, 'add')) show_error(lang_line('purchase_request_msg_no_permission_po', 'Anda tidak memiliki izin membuat PO.'), 403);

        $header = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type || $header->status !== 'Approved') {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_only_approved_po', 'Hanya PR yang sudah Approved yang bisa dikonversi ke PO.'));
            return redirect($this->menu_key . '/detail/' . $id);
        }

        $supplier_id = $this->input->post('supplier_id');
        if (!$supplier_id) {
            $this->session->set_flashdata('error', lang_line('purchase_request_msg_supplier_required', 'Supplier harus dipilih sebelum membuat PO.'));
            return redirect($this->menu_key . '/detail/' . $id);
        }

        $this->load->model('Po_model');

        // Item yang dicentang di modal. Kalau form tidak mengirim pilihan sama
        // sekali, ambil semua baris PR — qty tetap diklamp ke sisa yang belum
        // masuk PO lain, jadi tidak bisa dobel pesan.
        $selected = $this->input->post('pr_detail_ids');
        if (empty($selected)) {
            $selected = array_column($this->Purchase_request_model->get_details_by_id($id), 'id');
        }
        $rows = Po_model::build_pull_rows($selected, $this->input->post('pull_qty'));

        $include_ppn = $this->input->post('include_ppn') ? 1 : 0;
        $ppn_rate = $include_ppn ? (float)($this->input->post('ppn_rate') ?: $this->default_ppn_rate) : 0;

        $result = $this->Po_model->create_po_from_pr($id, [
            'supplier_id'  => $supplier_id,
            'po_date'      => date('Y-m-d'),
            'type'         => $this->pr_type,
            'currency_code'=> 'IDR',
            'exchange_rate'=> 1,
            'has_ppn'      => $include_ppn,
            'ppn_rate'     => $ppn_rate,
            'remarks'      => 'Dibuat dari PR: ' . $header->pr_number,
            'created_by'   => $this->session->userdata('user_id'),
        ], $rows);

        if (empty($result['success'])) {
            $this->session->set_flashdata('error', $result['message']);
            return redirect($this->menu_key . '/detail/' . $id);
        }

        $message = 'PO dibuat dari PR ' . $header->pr_number . '. ' . $result['message'];
        if ($include_ppn) {
            $message .= ' (PPN ' . number_format($ppn_rate, 2) . '% akan ditambahkan)';
        }
        $this->session->set_flashdata('success', $message);
        redirect($this->po_menu_key . '/detail/' . $result['po_id']);
    }

    // ===================== EXPORT =====================

    public function export_list_excel()
    {
        if (!check_access($this->menu_key, 'view')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin Ditolak.'), 403);

        // Ikut scope listing (termasuk ?all=1 kalau admin) — lihat _list_filters().
        $prs = $this->Purchase_request_model->get_all($this->_list_filters());

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
        if (!check_access($this->menu_key, 'view')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin Ditolak.'), 403);

        // Ikut scope listing (termasuk ?all=1 kalau admin) — lihat _list_filters().
        $prs = $this->Purchase_request_model->get_all($this->_list_filters());

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
        if (!check_access($this->menu_key, 'view')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin Ditolak.'), 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $header  = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type) show_404();
        $details = $this->Purchase_request_model->get_details_by_id($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'PURCHASE REQUEST');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'PR Number:');   $sheet->setCellValue('B3', $header->pr_number);
        $sheet->setCellValue('E3', 'Tanggal:');      $sheet->setCellValue('F3', date('d-m-Y', strtotime($header->pr_date)));
        $sheet->setCellValue('A4', 'Requester:');    $sheet->setCellValue('B4', $header->requester_name ?? '-');
        $sheet->setCellValue('E4', 'Status:');       $sheet->setCellValue('F4', $header->status);
        $sheet->setCellValue('A5', 'Supplier:');     $sheet->setCellValue('B5', $header->nama_supplier ?? '-');
        $sheet->setCellValue('A6', 'Keperluan:');    $sheet->setCellValue('B6', $header->purpose ?? '-');
        $sheet->mergeCells('B6:H6');
        $sheet->setCellValue('A7', 'Dibuat Oleh:');  $sheet->setCellValue('B7', $header->requester_name ?? '-');
        $sheet->setCellValue('E7', 'Disetujui Oleh:'); $sheet->setCellValue('F7', $header->approver_name ?? '-');

        $startRow = 9;
        $sheet->setCellValue('A'.$startRow, 'No');
        $sheet->setCellValue('B'.$startRow, 'Kode Item');
        $sheet->setCellValue('C'.$startRow, 'Nama Item');
        $sheet->setCellValue('D'.$startRow, 'Satuan');
        $sheet->setCellValue('E'.$startRow, 'Qty');
        $sheet->setCellValue('F'.$startRow, 'Deskripsi');
        $sheet->setCellValue('G'.$startRow, 'Remarks');
        $sheet->setCellValue('H'.$startRow, 'Sales Contract');

        $sheet->getStyle('A'.$startRow.':H'.$startRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$startRow.':H'.$startRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A'.$startRow.':H'.$startRow)->getFont()->getColor()->setARGB('FFFFFFFF');

        $rowNum = $startRow + 1;
        $no = 1;
        foreach ($details as $d) {
            $sheet->setCellValue('A'.$rowNum, $no++);
            $sheet->setCellValue('B'.$rowNum, $d->item_code ?? '-');
            $sheet->setCellValue('C'.$rowNum, $d->item_name ?? '-');
            $sheet->setCellValue('D'.$rowNum, $d->item_unit ?? '-');
            $sheet->setCellValue('E'.$rowNum, $d->qty);
            $sheet->setCellValue('F'.$rowNum, $d->description ?? '-');
            $sheet->setCellValue('G'.$rowNum, $d->remarks ?? '-');
            $sheet->setCellValue('H'.$rowNum, $d->sc_number ?? '-');
            $rowNum++;
        }

        $sheet->getStyle('A'.$startRow.':H'.($rowNum-1))->getBorders()
            ->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        foreach (range('A','H') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
        // Remarks bisa berisi URL panjang — batasi lebar & aktifkan wrap text
        $sheet->getColumnDimension('G')->setAutoSize(false)->setWidth(45);
        $sheet->getStyle('G'.($startRow+1).':G'.($rowNum-1))->getAlignment()->setWrapText(true);

        $filename = str_replace(['/', '\\'], '-', $header->pr_number) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function export_detail_pdf($id)
    {
        if (!check_access($this->menu_key, 'view')) show_error(lang_line('purchase_request_msg_permission_denied', 'Izin Ditolak.'), 403);
        if (empty($id) || !is_numeric($id)) show_404();

        $header  = $this->Purchase_request_model->get_header_by_id($id);
        if (!$header || $header->type !== $this->pr_type) show_404();
        $details = $this->Purchase_request_model->get_details_by_id($id);

        $items = [];
        $no = 1;
        foreach ($details as $d) {
            // Gabungkan deskripsi + remarks supaya keduanya tampil di PDF.
            $note_parts = [];
            if (!empty($d->description)) $note_parts[] = $d->description;
            if (!empty($d->remarks))     $note_parts[] = $d->remarks;
            $items[] = [
                'no'         => $no++,
                'item_code'  => $d->item_code,
                'item_name'  => $d->item_name,
                'qty'        => (float)$d->qty,
                'unit'       => $d->item_unit ?? '',
                'unit_price' => 0,
                'amount'     => 0,
                'notes'      => implode("\n", $note_parts),
            ];
        }

        // Signatures ikut pola 3-stage IRF: Applicant (creator), Head of Dept,
        // Endorsement, Approve by (final). Receiver dihilangkan.
        $data = [
            'irf_number'    => $header->pr_number,
            'request_date'  => $header->pr_date,
            'department'    => resolve_department_name($header->department ?? ''),
            'purpose'       => $header->purpose ?? $header->remarks ?? '',
            'items'         => $items,
            'form_title'    => $this->_form_title(),
            'form_subtitle' => $this->_form_subtitle(),
            'number_label'  => 'PR', // dulu 'IRF' (kebiasaan NSI); judulnya memang PURCHASE REQUEST
            // Approval PR 1 tahap (2026-11-05): cuma Pemohon + Penyetuju.
            'signatures'    => [
                build_signature_slot(
                    $header->requester_id ?? ($header->created_by ?? null),
                    $header->submitted_at ?? ($header->created_at ?? null)
                ),
                build_signature_slot(
                    $header->final_approved_by ?? ($header->approved_by ?? null),
                    $header->final_approved_at ?? ($header->approved_at ?? null)
                ),
            ],
            'signature_labels' => [
                ['Applicant',     '申請人'],
                ['Approve by',    '核准人'],
            ],
        ];

        $filename = 'PR_' . str_replace([':', ' ', '/', '\\'], ['', '_', '-', '-'], $header->pr_number) . '.pdf';
        render_document_pdf($data, $filename);
    }

    // ===================== HELPER =====================

    /**
     * Header PR pemilik satu baris detail — endpoint item (update/delete) cuma
     * dikirimi detail_id, padahal izin tulisnya ditentukan status header.
     */
    // ---------- Tarik dari Permintaan Barang (2026-11-05) ----------
    // Lihat Purchase_request_model::get_pullable_rows() / pull_rows_into_pr().

    /** JSON: baris Permintaan Barang Approved yang masih butuh PR & cocok dgn tipe PR ini. */
    public function pull_list($pr_id) {
        $header = $this->Purchase_request_model->get_header_by_id($pr_id);
        if (!$header || $header->type !== $this->pr_type) return $this->_json(['success' => false, 'message' => 'PR tidak ditemukan']);
        if (!can_edit_pr($header, $this->menu_key) || $header->status !== 'Draft') {
            return $this->_json(['success' => false, 'message' => 'Item hanya bisa ditarik saat PR berstatus Draft.']);
        }
        $rows = [];
        foreach ($this->Purchase_request_model->get_pullable_rows('irf', $this->pr_type) as $r) {
            $rows[] = [
                'id'              => (int) $r->cr_detail_id,
                'request_number'  => $r->request_number,
                'department'      => $r->department,
                'item_code'       => $r->item_code,
                'item_name'       => $r->item_name,
                'item_unit'       => $r->item_unit,
                'category'        => $r->category ?? '',
                'qty_outstanding' => (float) $r->qty_outstanding,
                'qty_from_stock'  => (float) $r->qty_from_stock,
                'qty_on_pr'       => (float) $r->qty_on_pr,
                'qty_pr'          => (float) $r->qty_pr,
            ];
        }
        return $this->_json(['success' => true, 'rows' => $rows]);
    }

    /** POST rows[<cr_detail_id>] = qty -> masukkan ke PR (Draft). */
    public function pull_items($pr_id) {
        $header = $this->Purchase_request_model->get_header_by_id($pr_id);
        if (!$header || $header->type !== $this->pr_type) return $this->_json(['success' => false, 'message' => 'PR tidak ditemukan']);
        if (!can_edit_pr($header, $this->menu_key)) {
            return $this->_json(['success' => false, 'message' => $this->_locked_message($header)]);
        }
        $rows = $this->input->post('rows');
        $res  = $this->Purchase_request_model->pull_rows_into_pr($pr_id, 'irf', is_array($rows) ? $rows : []);
        if ($res['success']) {
            add_log('UPDATE', 'Purchase Request', 'Tarik dari Permintaan Barang ke ' . $header->pr_number . ': ' . implode('; ', $res['added']));
        }
        return $this->_json($res);
    }

    protected function _header_of_detail($detail_id) {
        $detail = $this->Purchase_request_model->get_detail_by_id((int) $detail_id);
        if (!$detail) return null;
        $header = $this->Purchase_request_model->get_header_by_id($detail->pr_header_id);
        if (!$header || $header->type !== $this->pr_type) return null;
        return $header;
    }

    /**
     * Baris terkunci karena sudah ditarik ke PO. Cuma berlaku di luar Draft
     * (alias mode edit Admin) supaya alur Draft normal tidak berubah perilakunya.
     */
    protected function _row_locked_by_po($header, $detail_id) {
        if ($header->status === 'Draft') return false;
        return $this->Purchase_request_model->detail_has_po($detail_id);
    }

    /**
     * Pesan penolakan tulis: beda antara "statusnya memang terkunci" dan
     * "Admin belum membuka kuncinya" supaya jelas apa yang harus dilakukan.
     */
    protected function _locked_message($header) {
        if (can_unlock_pr_admin_edit($header)) {
            return 'Mode edit Admin belum aktif. Buka halaman detail PR lalu klik "Buka Kunci Edit (Admin)" dulu.';
        }
        return 'PR berstatus ' . $header->status . ' terkunci dan tidak bisa diubah.';
    }

    protected function _json($data) {
        while (ob_get_level()) ob_end_clean();
        @ini_set('display_errors', '0');
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
