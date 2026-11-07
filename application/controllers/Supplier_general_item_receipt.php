<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Supplier_general_item_receipt
 *
 * Modul penerimaan barang general (machine & inventory) dari supplier.
 * Tidak melewati QA — langsung masuk stock_ledger setelah di-approve.
 * Approve/unapprove memakai izin Confirm (sejak Role Level tahap 2, 2026-09-15; dulu Add).
 */
class Supplier_general_item_receipt extends MY_Controller {

    private $menu_key = 'supplier_general_item_receipt';

    /**
     * Kategori HEADER SGR (ERP Revision Notes 2026-09-02, Fase 2 poin 9).
     * 1 dokumen = 1 kategori (dulu per-item, tidak pernah benar-benar
     * dipakai — 0 baris terisi di data existing). 'Lainnya' = kategori
     * ke-3 KHUSUS SGR (tidak ada di Consumable_out) utk dokumen yang sama
     * sekali bukan dari alur IRF consumable (mis. terima mesin yang dibeli
     * langsung) — TIDAK dapat menu kategori sendiri, tetap di menu blanket lama.
     */
    const CATEGORIES = ['General', 'Produksi', 'Lainnya'];

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model([
            'Supplier_general_item_receipt_model',
            'Commoncode_model',
            'Po_model',
            'Stock_ledger_model',
        ]);
        $this->load->helper('url');
    }

    // ============================================================
    // PERMISSION PER-KATEGORI (khusus SGR, TIDAK pakai helper generik
    // can_category()/category_menu_key() di MY_Controller — itu binary
    // General/Produksi, sedangkan SGR punya kategori ke-3 'Lainnya' yang
    // TIDAK dapat menu sendiri, jadi butuh percabangan eksplisit).
    // ============================================================

    /** 'supplier_general_item_receipt_general'/'_produksi', atau menu blanket lama utk 'Lainnya'. */
    private function _sgr_menu_key($category) {
        if ($category === 'General')  return 'supplier_general_item_receipt_general';
        if ($category === 'Produksi') return 'supplier_general_item_receipt_produksi';
        return $this->menu_key; // Lainnya -> tidak ada menu kategori sendiri
    }

    /** OR-fallback: menu spesifik kategori ATAU permission blanket lama. */
    private function _can_sgr($category, $action) {
        $specific = $this->_sgr_menu_key($category);
        if ($specific !== $this->menu_key && check_access($specific, $action)) {
            return true;
        }
        return check_access($this->menu_key, $action);
    }

    /** URL segment ('general'/'produksi') -> 'General'/'Produksi', atau null (blanket/semua). */
    private function _normalize_sgr_category_slug($slug) {
        $slug = strtolower((string) $slug);
        if ($slug === 'general')  return 'General';
        if ($slug === 'produksi') return 'Produksi';
        return null;
    }

    // ============================================================
    // INDEX
    // Diakses lewat 2 pintu sidebar terpisah (mirip Consumable_out):
    //   supplier_general_item_receipt/index/general  -> khusus General
    //   supplier_general_item_receipt/index/produksi -> khusus Produksi
    // URL lama tanpa segment tetap jalan sebagai gabungan (SEMUA kategori
    // termasuk 'Lainnya') — dibatasi ke kategori yang boleh dilihat user.
    // ============================================================
    public function index($category_slug = null) {
        $category = $this->_normalize_sgr_category_slug($category_slug);

        if ($category !== null) {
            if (!$this->_can_sgr($category, 'view')) {
                show_error('Anda tidak memiliki izin untuk kategori ini.', 403);
                return;
            }
            $categories_filter = [$category];
        } else {
            // Blanket: kumpulkan kategori yang boleh dilihat (termasuk 'Lainnya'
            // kalau punya permission blanket lama).
            $categories_filter = [];
            foreach (['General', 'Produksi'] as $c) {
                if ($this->_can_sgr($c, 'view')) $categories_filter[] = $c;
            }
            if (check_access($this->menu_key, 'view')) $categories_filter[] = 'Lainnya';
            if (empty($categories_filter)) {
                show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
                return;
            }
        }

        $data = [
            'title'    => lang_line('sgr_page_title', 'Supplier General Item Receipt'),
            'sgrs'     => $this->Supplier_general_item_receipt_model->get_all($categories_filter),
            'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE'),
            'warehouse_types' => [
                'machine'   => 'Machine / Equipment',
                'inventory' => 'Inventory',
            ],
            'category_slug' => $category_slug,
            'active_category' => $category,
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('supplier_general_item_receipt/index', $data);
        $this->load->view('templates/footer');
    }

    // ============================================================
    // HEADER CRUD
    // ============================================================
    public function add_header() {
        $category = $this->input->post('category');
        if (!in_array($category, self::CATEGORIES, true)) {
            $category = 'Lainnya';
        }
        if (!$this->_can_sgr($category, 'add')) {
            show_error('Anda tidak memiliki izin untuk membuat SGR kategori ' . $category . '.', 403);
        }
        $this->form_validation->set_rules('sgr_date', 'Tanggal', 'required');
        $this->form_validation->set_rules('warehouse_type', 'Tipe Gudang', 'required|in_list[machine,inventory]');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', 'Input tidak valid. ' . validation_errors());
            redirect('supplier_general_item_receipt');
            return;
        }

        $this->db->trans_start();
        $sgr_number = $this->Supplier_general_item_receipt_model->generate_sgr_number();
        $data = [
            'sgr_number'           => $sgr_number,
            'sgr_date'             => $this->input->post('sgr_date'),
            'base_on'              => 'PO',
            'warehouse_type'       => $this->input->post('warehouse_type'),
            'category'             => $category,
            'delivery_note_number' => $this->input->post('delivery_note_number'),
            'bc_type_code'         => $this->input->post('bc_type_code') ?: null,
            'bc_date'              => $this->input->post('bc_date') ?: null,
            'bc_number'            => $this->input->post('bc_number') ?: null,
            'remarks'              => $this->input->post('remarks'),
            'status'               => 'Draft',
            'created_by'           => $this->session->userdata('user_id'),
        ];
        $insert_id = $this->Supplier_general_item_receipt_model->add_header($data);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE || !$insert_id) {
            $this->session->set_flashdata('error', 'Gagal membuat SGR.');
            redirect('supplier_general_item_receipt');
            return;
        }
        $this->session->set_flashdata('success', 'Header SGR ' . $sgr_number . ' berhasil dibuat. Silakan tambahkan item.');
        redirect('supplier_general_item_receipt/detail/' . $insert_id);
    }

    public function update_header($id) {
        $header = $this->Supplier_general_item_receipt_model->get_header_by_id($id);
        if (!$header || !$this->_can_sgr($header->category ?? 'Lainnya', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        if ($header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Header SGR tidak bisa diedit!');
            redirect('supplier_general_item_receipt');
            return;
        }
        $data = [
            'sgr_date'             => $this->input->post('sgr_date'),
            'delivery_note_number' => $this->input->post('delivery_note_number'),
            'bc_type_code'         => $this->input->post('bc_type_code'),
            'bc_date'              => $this->input->post('bc_date') ?: null,
            'bc_number'            => $this->input->post('bc_number'),
            'remarks'              => $this->input->post('remarks'),
        ];
        if ($this->Supplier_general_item_receipt_model->update_header($id, $data)) {
            $this->session->set_flashdata('success', 'Header SGR diperbarui.');
        } else {
            $this->session->set_flashdata('error', 'Gagal update header SGR.');
        }
        redirect('supplier_general_item_receipt');
    }

    public function delete_header($id) {
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Supplier_general_item_receipt_model->get_header_by_id($id);
        if (!$header || !$this->_can_sgr($header->category ?? 'Lainnya', 'delete')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }
        if ($header->status != 'Draft') {
            $this->session->set_flashdata('error', 'SGR tidak bisa dihapus (status bukan Draft)!');
            redirect('supplier_general_item_receipt');
            return;
        }
        if ($this->Supplier_general_item_receipt_model->delete_header($id)) {
            $this->session->set_flashdata('success', 'SGR ' . $header->sgr_number . ' beserta itemnya berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus SGR ' . $header->sgr_number . '.');
        }
        redirect('supplier_general_item_receipt');
    }

    public function detail($id) {
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Supplier_general_item_receipt_model->get_header_by_id($id);
        if (!$header) show_404();
        if (!$this->_can_sgr($header->category ?? 'Lainnya', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $data = [
            'title'     => 'Detail Supplier General Item Receipt',
            'header'    => $header,
            'details'   => $this->Supplier_general_item_receipt_model->get_details_by_id($id),
            'bc_types'  => $this->Commoncode_model->get_by_group('BC_TYPE'),
            'user_role' => $this->session->userdata('role_id'),
            // Izin per kategori, sama dengan gerbang di endpoint-endpoint-nya.
            'can_edit'    => $this->_can_sgr($header->category ?? 'Lainnya', 'edit'),
            // Approve = izin Confirm (dulu Add, jadi pembuat dokumen otomatis bisa approve).
            'can_approve' => $this->_can_sgr($header->category ?? 'Lainnya', 'confirm'),
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('supplier_general_item_receipt/detail', $data);
    }

    // ============================================================
    // ITEM ACTIONS
    // ============================================================
    /**
     * Endpoint form "Tambah Item" lama (1 item). Tombolnya di halaman detail
     * sekarang membuka modal Pilih Item PO, tapi endpoint dibiarkan untuk
     * halaman lama yang masih ter-cache -- dan kini lewat validasi yang sama
     * dengan modal baru. Versi lamanya tidak mengecek qty ke sisa PO dan
     * mengambil harga dari input browser (item_price_hidden).
     */
    public function add_item($sgr_id) {
        $header = $this->Supplier_general_item_receipt_model->get_header_by_id($sgr_id);
        if (!$header || !$this->_can_sgr($header->category ?? 'Lainnya', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $item_parts = explode('|', (string) $this->input->post('item_selector'));
        $pdid = (int) ($item_parts[0] ?? 0);
        $qty  = (float) $this->input->post('receive_qty');

        // Baris yang dipilih harus memang milik PO yang dipilih.
        $belongs = $pdid > 0 && $this->db->where(['id' => $pdid, 'po_header_id' => (int) $this->input->post('source_doc_id')])
            ->count_all_results('po_details') > 0;
        if (!$belongs) {
            $this->session->set_flashdata('error', 'Item tidak valid.');
            redirect('supplier_general_item_receipt/detail/' . $sgr_id);
            return;
        }

        $res = $this->_load_items($header, [$pdid => $qty]);
        $this->session->set_flashdata($res['status'] === 'success' ? 'success' : 'error',
            $res['status'] === 'success' ? $res['message'] : implode(' ', $res['errors'] ?: [$res['message']]));
        redirect('supplier_general_item_receipt/detail/' . $sgr_id);
    }

    public function delete_item($sgr_id, $detail_id) {
        $header = $this->Supplier_general_item_receipt_model->get_header_by_id($sgr_id);
        if (!$header || !$this->_can_sgr($header->category ?? 'Lainnya', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        if ($header->status != 'Draft') {
            redirect('supplier_general_item_receipt/detail/' . $sgr_id);
            return;
        }
        if ($this->Supplier_general_item_receipt_model->delete_item($detail_id)) {
            $this->session->set_flashdata('success', 'Item dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus item!');
        }
        redirect('supplier_general_item_receipt/detail/' . $sgr_id);
    }

    /**
     * @deprecated 2026-09-14 -- diganti update_po_item(). Dipertahankan untuk
     * halaman lama, tapi sekarang lewat validasi sisa PO yang sama. Versi
     * lamanya menerima qty berapa pun (termasuk 0 dan melebihi PO).
     */
    public function update_item_qty() {
        header('Content-Type: application/json');
        echo json_encode($this->_save_po_detail($this->input->post('detail_id'), $this->input->post('receive_qty'), null));
    }

    // ============================================================
    // APPROVAL / UNAPPROVAL
    // ============================================================
    public function approve($id) {
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Supplier_general_item_receipt_model->get_header_by_id($id);
        if (!$header || !$this->_can_sgr($header->category ?? 'Lainnya', 'confirm')) {
            show_error('Anda tidak memiliki izin untuk approve.', 403);
            return;
        }
        if ($header->status != 'Draft') {
            $this->session->set_flashdata('warning', 'Hanya SGR status Draft yang bisa di-approve.');
            redirect('supplier_general_item_receipt/detail/' . $id);
            return;
        }
        $details = $this->Supplier_general_item_receipt_model->get_details_by_id($id);
        if (empty($details)) {
            $this->session->set_flashdata('error', 'SGR kosong, tidak bisa di-approve.');
            redirect('supplier_general_item_receipt/detail/' . $id);
            return;
        }

        $user_id = $this->session->userdata('user_id');
        if (!$this->Supplier_general_item_receipt_model->update_status($id, 'Completed', $user_id)) {
            $this->session->set_flashdata('error', 'Gagal approve SGR.');
            redirect('supplier_general_item_receipt/detail/' . $id);
            return;
        }
        if ($this->Supplier_general_item_receipt_model->add_stock_to_ledger($id)) {
            $this->session->set_flashdata('success', 'SGR di-approve! Barang masuk ke stock.');
            redirect('supplier_general_item_receipt');
        } else {
            // Rollback status
            $this->Supplier_general_item_receipt_model->update_status($id, 'Draft');
            $this->session->set_flashdata('error', 'Gagal menambahkan ke stock. Status SGR dikembalikan ke Draft.');
            redirect('supplier_general_item_receipt/detail/' . $id);
        }
    }

    public function unapprove($id) {
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Supplier_general_item_receipt_model->get_header_by_id($id);
        if (!$header || !$this->_can_sgr($header->category ?? 'Lainnya', 'confirm')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }
        if ($header->status != 'Completed') {
            $this->session->set_flashdata('warning', 'Hanya SGR status Completed yang bisa di-unapprove.');
            redirect('supplier_general_item_receipt/detail/' . $id);
            return;
        }
        if (!$this->Supplier_general_item_receipt_model->remove_stock_from_ledger($id)) {
            $this->session->set_flashdata('error', 'Gagal unapprove! Sebagian stok dari SGR ini sudah terpakai.');
            redirect('supplier_general_item_receipt/detail/' . $id);
            return;
        }
        if ($this->Supplier_general_item_receipt_model->update_status($id, 'Draft')) {
            $this->session->set_flashdata('success', 'Approval SGR dibatalkan. Stok dikembalikan.');
        } else {
            $this->session->set_flashdata('error', 'Stok dihapus, tapi gagal update status header!');
        }
        redirect('supplier_general_item_receipt/detail/' . $id);
    }

    // ============================================================
    // AJAX
    // ============================================================
    public function get_available_pos() {
        header('Content-Type: application/json');
        $sgr_id = (int) $this->input->get('sgr_id');
        $header = $sgr_id > 0 ? $this->Supplier_general_item_receipt_model->get_header_by_id($sgr_id) : null;
        if (!$header || !$this->_can_sgr($header->category ?? 'Lainnya', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'SGR tidak ditemukan atau Anda tidak memiliki izin.']);
            return;
        }
        $search = trim((string) $this->input->get('q'));

        // PO disetujui yang punya minimal satu baris bertipe gudang SGR ini.
        $this->db->reset_query();
        $this->db->select('ph.id AS po_id, ph.po_number, ph.po_date, ph.status, ph.currency_code, s.nama_supplier AS supplier_name', false)
            ->from('po_header ph')
            ->join('suppliers s', 's.id = ph.supplier_id', 'left')
            ->where_in('ph.status', ['Confirmed', 'Partially Received'])
            ->where('EXISTS (SELECT 1 FROM po_details x WHERE x.po_header_id = ph.id AND x.item_type = ' . $this->db->escape($header->warehouse_type) . ')', null, false);
        if ($search !== '') {
            $this->db->group_start()->like('ph.po_number', $search)->or_like('s.nama_supplier', $search)->group_end();
        }
        $pos = $this->db->order_by('ph.po_date', 'DESC')->limit(100)->get()->result();
        if (empty($pos)) {
            echo json_encode(['status' => 'success', 'data' => []]);
            return;
        }

        $by_po = [];
        foreach ($this->Supplier_general_item_receipt_model->get_po_lines_with_receipt(array_column($pos, 'po_id'), $header) as $l) {
            $by_po[(int) $l->po_header_id][] = $l;
        }

        $results = [];
        foreach ($pos as $po) {
            $po_lines = $by_po[(int) $po->po_id] ?? [];
            $qty_here = 0; $received_here = 0; $allowed = 0; $receivable = 0; $other = 0;
            $out = [];
            foreach ($po_lines as $l) {
                if ($l->allowed_here) {
                    $allowed++;
                    $qty_here      += $l->qty_po;
                    $received_here += $l->qty_received;
                    if ($l->receivable) $receivable++;
                } else {
                    $other++;
                }
                $out[] = [
                    'po_detail_id'        => (int) $l->po_detail_id,
                    'item_code'           => $l->item_code,
                    'item_name'           => $l->item_name,
                    'unit'                => $l->unit,
                    'item_type'           => $l->item_type,
                    'category'            => $l->category,
                    'unit_price'          => (float) $l->local_price,
                    'qty_po'              => $l->qty_po,
                    'qty_received'        => $l->qty_received,
                    'qty_draft_other'     => $l->qty_draft_other,
                    'qty_this_doc'        => $l->qty_this_doc,
                    'qty_remaining'       => max(0, $l->qty_remaining),
                    'draft_other_numbers' => $l->draft_other_numbers,
                    'receivable'          => $l->receivable,
                    'status_key'          => $l->status_key,
                ];
            }
            $results[] = [
                'po_id'            => (int) $po->po_id,
                'po_number'        => $po->po_number,
                'po_date'          => $po->po_date,
                'po_status'        => $po->status,
                'currency'         => $po->currency_code,
                'supplier_name'    => $po->supplier_name,
                'total_lines'      => count($po_lines),
                'allowed_lines'    => $allowed,
                'other_lines'      => $other,
                'receivable_lines' => $receivable,
                // Progres dalam persen qty, hanya dari SGR Completed, hanya baris yang relevan untuk SGR ini.
                'progress'         => $qty_here > 0 ? round($received_here / $qty_here * 100) : 0,
                'fully_received'   => $receivable === 0,
                'lines'            => $out,
            ];
        }
        echo json_encode(['status' => 'success', 'data' => $results]);
    }

    /** Dropdown item untuk form "Tambah Item" lama -- sisa dihitung dengan cara yang sama seperti modal baru. */
    public function get_available_items() {
        $po_id  = (int) $this->input->get('source_id');
        $header = $this->Supplier_general_item_receipt_model->get_header_by_id((int) $this->input->get('sgr_id'));
        $options = '<option value="">-- Pilih Item --</option>';
        if ($po_id <= 0 || !$header) {
            echo '<option value="">-- Tidak ada item --</option>';
            return;
        }
        foreach ($this->Supplier_general_item_receipt_model->get_po_lines_with_receipt([$po_id], $header) as $l) {
            if (!$l->receivable) continue;
            $options .= '<option value="' . $l->po_detail_id . '|' . $l->item_id . '|' . $l->item_type . '" '
                      . 'data-maxqty="' . $l->qty_remaining . '" data-price="' . (float) $l->local_price . '">'
                      . htmlspecialchars(($l->item_name ?? '-') . ' (' . ($l->item_code ?? '-') . ') - Sisa: ' . number_format($l->qty_remaining, 2))
                      . '</option>';
        }
        echo $options;
    }

    /**
     * AJAX: muat item PO TERPILIH dengan qty tertentu ke SGR Draft.
     * User 2026-09-14: sama seperti Warehouse In -- penerimaan bisa parsial
     * per item dan per qty. Input: sgr_id + items (JSON) [{po_detail_id, qty}].
     */
    public function load_selected_po_items() {
        header('Content-Type: application/json');
        $header = $this->Supplier_general_item_receipt_model->get_header_by_id((int) $this->input->post('sgr_id'));
        if (!$header || !$this->_can_sgr($header->category ?? 'Lainnya', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        $items  = json_decode((string) $this->input->post('items'), true);
        $wanted = [];
        foreach ((is_array($items) ? $items : []) as $it) {
            $pdid = (int) ($it['po_detail_id'] ?? 0);
            if ($pdid > 0) $wanted[$pdid] = ($wanted[$pdid] ?? 0) + (float) ($it['qty'] ?? 0);
        }
        echo json_encode($this->_load_items($header, $wanted));
    }

    /**
     * Inti muat item PO ke SGR, dipakai modal baru, form lama (add_item) dan
     * auto_load_po_items. SEMUA baris divalidasi dulu (all-or-nothing); batas
     * qty & harga dihitung ulang dari database, bukan dari browser. Baris PO
     * yang sudah ada di SGR ini qty-nya DITAMBAHKAN, bukan dibuat baris kembar.
     *
     * @param array $wanted [po_detail_id => qty]
     */
    private function _load_items($header, array $wanted) {
        if ($header->status != 'Draft') {
            return ['status' => 'error', 'message' => 'SGR sudah tidak Draft.', 'errors' => []];
        }
        if (empty($wanted)) {
            return ['status' => 'error', 'message' => 'Pilih minimal satu item.', 'errors' => []];
        }
        foreach ($wanted as $k => $q) { $wanted[$k] = round((float) $q, 2); }

        $po_rows = $this->db->select('pd.id, pd.po_header_id, ph.status AS po_status, ph.supplier_id, ph.po_number')
            ->from('po_details pd')->join('po_header ph', 'ph.id = pd.po_header_id')
            ->where_in('pd.id', array_keys($wanted))->get()->result();
        $po_of = [];
        foreach ($po_rows as $r) { $po_of[(int) $r->id] = $r; }

        $line_of = [];
        $po_ids = array_unique(array_map(function ($r) { return (int) $r->po_header_id; }, $po_rows));
        foreach ($this->Supplier_general_item_receipt_model->get_po_lines_with_receipt($po_ids, $header) as $l) {
            $line_of[(int) $l->po_detail_id] = $l;
        }

        $type_label = ['machine' => 'Mesin', 'inventory' => 'Inventory'];
        $errors = [];
        foreach ($wanted as $pdid => $qty) {
            $l  = $line_of[$pdid] ?? null;
            $po = $po_of[$pdid] ?? null;
            $name = ($l && $l->item_name) ? (($l->item_code ? $l->item_code . ' - ' : '') . $l->item_name) : ('baris PO #' . $pdid);
            if (!$l || !$po) { $errors[] = "$name: baris PO tidak ditemukan."; continue; }
            if (!in_array($po->po_status, ['Confirmed', 'Partially Received'], true)) {
                $errors[] = "$name: PO {$po->po_number} berstatus {$po->po_status}, belum bisa diterima."; continue;
            }
            if ($l->status_key === 'other_module') {
                $errors[] = "$name: barang tipe {$l->item_type} diterima lewat Receiving Material Form, bukan di sini."; continue;
            }
            if ($l->status_key === 'other_warehouse') {
                $errors[] = "$name: tipe " . ($type_label[$l->item_type] ?? $l->item_type) . ", sedangkan SGR ini untuk " . ($type_label[$header->warehouse_type] ?? $header->warehouse_type) . '.'; continue;
            }
            if ($l->status_key === 'other_category') {
                $errors[] = "$name: kategori PO {$l->category}, sedangkan SGR ini kategori {$header->category}."; continue;
            }
            if ($qty <= 0) { $errors[] = "$name: qty harus lebih dari 0."; continue; }
            if ($qty > $l->qty_remaining + 0.0001) {
                $errors[] = "$name: qty " . (0 + $qty) . " melebihi sisa yang boleh diterima (" . (0 + max(0, $l->qty_remaining)) . ").";
            }
        }
        if (!empty($errors)) {
            return ['status' => 'error', 'message' => 'Tidak ada item yang dimuat.', 'errors' => $errors];
        }

        $detail_category = ($header->category === 'Lainnya') ? null : $header->category;
        $this->db->trans_start();
        $added = 0; $merged = 0; $supplier = null; $suppliers = [];
        foreach ($wanted as $pdid => $qty) {
            $l = $line_of[$pdid]; $po = $po_of[$pdid];
            $suppliers[(int) $po->supplier_id] = true;
            if (!$supplier && !empty($po->supplier_id)) $supplier = (int) $po->supplier_id;
            $unit_value = (float) $l->local_price;

            $existing = $this->db->select('id, receive_qty')->from('sgr_details')
                ->where(['sgr_header_id' => (int) $header->id, 'source_doc_type' => 'PO', 'source_doc_detail_id' => $pdid])
                ->limit(1)->get()->row();
            if ($existing) {
                $new_qty = round((float) $existing->receive_qty + $qty, 2);
                $this->db->update('sgr_details', ['receive_qty' => $new_qty, 'total_value' => round($new_qty * $unit_value, 2)], ['id' => $existing->id]);
                $merged++;
            } else {
                $this->Supplier_general_item_receipt_model->insert_item([
                    'sgr_header_id'        => (int) $header->id,
                    'source_doc_header_id' => (int) $l->po_header_id,
                    'source_doc_detail_id' => $pdid,
                    'source_doc_type'      => 'PO',
                    'item_id'              => (int) $l->item_id,
                    'item_type'            => $l->item_type,
                    'category'             => $detail_category,
                    'receive_qty'          => $qty,
                    'unit_value'           => $unit_value,
                    'total_value'          => round($qty * $unit_value, 2),
                    'bc_type_code'         => $header->bc_type_code ?: null,
                    'bc_number'            => $header->bc_number ?: null,
                    'bc_date'              => $header->bc_date ?: null,
                ]);
                $added++;
            }
        }
        if ($supplier && empty($header->supplier_id)) {
            $this->Supplier_general_item_receipt_model->update_header($header->id, ['supplier_id' => $supplier]);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return ['status' => 'error', 'message' => 'Gagal menyimpan ke database. Tidak ada item yang dimuat.', 'errors' => []];
        }
        $msg = "$added item baru dimuat" . ($merged ? ", $merged item digabung ke baris yang sudah ada" : '') . '.';
        if (count($suppliers) > 1) $msg .= ' Perhatian: item berasal dari lebih dari satu supplier.';
        return ['status' => 'success', 'message' => $msg, 'added' => $added, 'merged' => $merged, 'errors' => []];
    }

    /**
     * Validasi & simpan perubahan satu baris SGR Draft (qty, dan BC kalau $bc
     * berupa array). Qty dibatasi sisa PO + qty baris itu sendiri.
     */
    private function _save_po_detail($detail_id, $new_qty, $bc = null) {
        $detail_id = (int) $detail_id;
        $new_qty   = round((float) $new_qty, 2);

        $d = $this->db->select('sd.*, sh.status AS sgr_status, sh.category AS header_category')
            ->from('sgr_details sd')->join('sgr_header sh', 'sh.id = sd.sgr_header_id')
            ->where('sd.id', $detail_id)->get()->row();
        if (!$d || !$this->_can_sgr($d->header_category ?? 'Lainnya', 'edit')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }
        if ($d->sgr_status != 'Draft' || $d->source_doc_type != 'PO') {
            return ['status' => 'error', 'message' => 'Item tidak bisa diedit karena SGR sudah tidak Draft.'];
        }
        if ($new_qty <= 0) {
            return ['status' => 'error', 'message' => 'Qty harus lebih dari 0. Untuk membatalkan item, gunakan tombol hapus.'];
        }
        $line = $this->_line_for_detail($d);
        if (!$line) {
            return ['status' => 'error', 'message' => 'Baris PO sumber tidak ditemukan.'];
        }
        $max = round(max(0, $line->qty_remaining) + (float) $d->receive_qty, 4);
        if ($new_qty > $max + 0.0001) {
            return ['status' => 'error', 'message' => 'Qty melebihi batas. Maksimum yang boleh diterima untuk baris ini: ' . (0 + $max) . '.', 'max' => $max];
        }

        $upd = ['receive_qty' => $new_qty, 'total_value' => round($new_qty * (float) $d->unit_value, 2)];
        if (is_array($bc)) {
            $upd['bc_type_code'] = ($bc['bc_type_code'] ?? '') !== '' ? $bc['bc_type_code'] : null;
            $upd['bc_number']    = ($bc['bc_number'] ?? '')    !== '' ? $bc['bc_number']    : null;
            $upd['bc_date']      = ($bc['bc_date'] ?? '')      !== '' ? $bc['bc_date']      : null;
        }
        if (!$this->db->update('sgr_details', $upd, ['id' => $detail_id])) {
            return ['status' => 'error', 'message' => 'Gagal menyimpan perubahan.'];
        }
        return ['status' => 'success', 'message' => 'Item diperbarui.', 'receive_qty' => $new_qty, 'total_value' => $upd['total_value'], 'max' => $max];
    }

    /** Baris PO (dengan status penerimaan) untuk satu baris sgr_details. */
    private function _line_for_detail($d) {
        $header = $this->Supplier_general_item_receipt_model->get_header_by_id((int) $d->sgr_header_id);
        foreach ($this->Supplier_general_item_receipt_model->get_po_lines_with_receipt([(int) $d->source_doc_header_id], $header) as $l) {
            if ((int) $l->po_detail_id === (int) $d->source_doc_detail_id) return $l;
        }
        return null;
    }

    /** AJAX: data lengkap satu baris untuk modal Edit Item. */
    public function get_po_item_detail() {
        header('Content-Type: application/json');
        $d = $this->db->select('sd.*, sh.category AS header_category, ph.po_number')
            ->from('sgr_details sd')->join('sgr_header sh', 'sh.id = sd.sgr_header_id')
            ->join('po_header ph', 'ph.id = sd.source_doc_header_id', 'left')
            ->where('sd.id', (int) $this->input->get('detail_id'))->get()->row();
        if (!$d || !$this->_can_sgr($d->header_category ?? 'Lainnya', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Item tidak ditemukan atau Anda tidak memiliki izin.']);
            return;
        }
        $line = $d->source_doc_type === 'PO' ? $this->_line_for_detail($d) : null;
        if (!$line) {
            echo json_encode(['status' => 'error', 'message' => 'Baris PO sumber tidak ditemukan.']);
            return;
        }
        echo json_encode(['status' => 'success', 'data' => [
            'detail_id'           => (int) $d->id,
            'po_number'           => $d->po_number,
            'item_code'           => $line->item_code,
            'item_name'           => $line->item_name,
            'unit'                => $line->unit,
            'item_type'           => $line->item_type,
            'qty_po'              => $line->qty_po,
            'qty_received'        => $line->qty_received,
            'qty_draft_other'     => $line->qty_draft_other,
            'draft_other_numbers' => $line->draft_other_numbers,
            'receive_qty'         => (float) $d->receive_qty,
            'max_qty'             => round(max(0, $line->qty_remaining) + (float) $d->receive_qty, 4),
            'unit_value'          => (float) $d->unit_value,
            'bc_type_code'        => $d->bc_type_code,
            'bc_number'           => $d->bc_number,
            'bc_date'             => $d->bc_date,
        ]]);
    }

    /** AJAX: simpan modal Edit Item -- qty + data BC per baris. */
    public function update_po_item() {
        header('Content-Type: application/json');
        echo json_encode($this->_save_po_detail(
            $this->input->post('detail_id'),
            $this->input->post('receive_qty'),
            [
                'bc_type_code' => trim((string) $this->input->post('bc_type_code')),
                'bc_number'    => trim((string) $this->input->post('bc_number')),
                'bc_date'      => trim((string) $this->input->post('bc_date')),
            ]
        ));
    }

    /**
     * @deprecated 2026-09-14 -- modal sekarang memanggil load_selected_po_items().
     * Dibiarkan untuk halaman lama: memuat SEMUA sisa baris yang boleh diterima
     * dari PO terpilih, lewat validasi yang sama (tidak lagi membuat baris kembar).
     */
    public function auto_load_po_items() {
        header('Content-Type: application/json');
        $header = $this->Supplier_general_item_receipt_model->get_header_by_id((int) $this->input->post('sgr_id'));
        $po_ids = $this->input->post('po_ids');
        if (!$header || !$this->_can_sgr($header->category ?? 'Lainnya', 'edit') || empty($po_ids) || !is_array($po_ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            return;
        }
        $wanted = [];
        foreach ($this->Supplier_general_item_receipt_model->get_po_lines_with_receipt($po_ids, $header) as $l) {
            if ($l->receivable) $wanted[(int) $l->po_detail_id] = $l->qty_remaining;
        }
        if (empty($wanted)) {
            echo json_encode(['status' => 'warning', 'message' => 'Tidak ada item tersisa yang bisa diterima di SGR ini.', 'total_loaded' => 0]);
            return;
        }
        $res = $this->_load_items($header, $wanted);
        $res['total_loaded'] = ($res['added'] ?? 0) + ($res['merged'] ?? 0);
        if ($res['status'] !== 'success') $res['message'] .= ' ' . implode(' ', $res['errors']);
        echo json_encode($res);
    }

    // ============================================================
    // EXPORT PDF
    // ============================================================
    public function export_pdf($id) {
        $header = $this->Supplier_general_item_receipt_model->get_header_by_id($id);
        if (!$header) show_404();
        if (!$this->_can_sgr($header->category ?? 'Lainnya', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $details = $this->Supplier_general_item_receipt_model->get_details_by_id($id);

        $data = ['header' => $header, 'details' => $details];
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('supplier_general_item_receipt/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'SGR_' . str_replace('/', '-', $header->sgr_number) . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 0]);
    }
}
