<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Material_request extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Mr_model', 'Wo_model']);
        $this->load->helper('url');
    }

    // ============================================================
    // CAKUPAN DEPARTEMEN (Role Level tahap 3)
    // ============================================================

    /** Departemen MRF: kolom department_code, fallback departemen profil pemohon (MRF lama). */
    private function _doc_dept($header) {
        if (!empty($header->department_code)) return $header->department_code;
        if (empty($header->requester_id)) return null;
        $u = $this->db->query('SELECT departemen FROM users WHERE id = ?', [(int) $header->requester_id])->row();
        return $u ? normalize_department_code($u->departemen) : null;
    }

    /** Boleh $action di MRF ini? (Admin & role lama tetap semua departemen.) */
    private function _in_scope($header, $action, $menu = 'material_request') {
        return can_dept_access($menu, $action, $this->_doc_dept($header));
    }

    /** 403 kalau MRF bukan departemen yang boleh ditangani di tahap approval ini. */
    private function _require_approval_scope($header, $action = null) {
        $action = $action ?: approval_action_for_status($header->status, Mr_model::APPROVAL_STAGE_MAP);
        if ($action === null || !$this->_in_scope($header, $action, 'material_request_approval')) {
            show_error('Material Request ini bukan departemen yang bisa Anda tangani di tahap approval ini.', 403);
        }
    }

    public function index() {
        if (!check_access('material_request', 'view')) {
            show_error('Anda tidak memiliki akses ke modul ini.', 403);
        }

        // Visibility approval stage diambil dari MENU baru `material_request_approval`,
        // sementara hak buat MR tetap di `material_request`.
        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-t'),
            'search'     => $this->input->get('search'),
            'visibility' => [
                'user_id'         => $this->session->userdata('user_id'),
                'can_head_dept'   => check_access('material_request_approval', 'head_dept'),
                'can_endorsement' => check_access('material_request_approval', 'endorsement'),
                'can_approval'    => check_access('material_request_approval', 'approval'),
            ]
        ];

        $data = [
            'title'   => lang_line('title_mr_list', 'Material Request List - Production Material Requisition'),
            'mrs'     => $this->Mr_model->get_all($filters),
            'filters' => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_request/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * MRF APPROVAL — list MR yang menunggu aksi approval user (3-stage).
     * Visibility eksklusif:
     * - can_head_dept   → status 'Submitted'
     * - can_endorsement → status 'Approved Head Dept'
     * - can_approval    → status 'Endorsed'
     */
    public function approval() {
        if (!check_access('material_request_approval', 'view')) {
            show_error('Anda tidak memiliki akses ke modul Material Request Approval.', 403);
        }

        $can_head_dept   = check_access('material_request_approval', 'head_dept');
        $can_endorsement = check_access('material_request_approval', 'endorsement');
        $can_approval    = check_access('material_request_approval', 'approval');

        $data = [
            'title'           => lang_line('title_mr_approval'),
            'mrs'             => $this->Mr_model->get_pending_approvals($can_head_dept, $can_endorsement, $can_approval),
            'can_head_dept'   => $can_head_dept,
            'can_endorsement' => $can_endorsement,
            'can_approval'    => $can_approval,
            // Riwayat approval milik user yg sedang login (user 2026-09-06:
            // "di bagian bawah ada riwayat yang user itu approve") — kartu
            // kedua di bawah antrian, partial templates/my_approval_history.php.
            'my_approval_history' => my_approval_history([
                'table'  => 'mr_header',
                'number' => 'mr_number',
                'date'   => 'request_date',
            ]),
            'my_history_route'    => 'material_request/detail',
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_request/approval', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Form "Buat MRF Baru" — 1 HALAMAN, header (tanggal+catatan) DAN item
     * (WO Reference + tabel item) digabung jadi satu (user 2026-09-04:
     * "header dan form itemnya jadikan 1", dipilih via AskUserQuestion opsi
     * "Staging penuh" — semua item ditahan di browser, TIDAK langsung
     * ke-insert, baru dikirim sekaligus pas klik "Simpan MRF"). Lihat
     * material_request/add.php + assets/js/mr_inline_input_staged.js +
     * store() di bawah. create_header()/add_item_ajax() LAMA tetap ada
     * tidak diubah — masih dipakai detail.php utk tambah item ke MR yang
     * SUDAH ada (bukan dead code, beda use-case).
     */
    public function add() {
        if (!check_access('material_request', 'add')) {
            show_error('Anda tidak memiliki akses untuk membuat MR.', 403);
        }

        $data = [
            'title' => lang_line('title_mr_create'),
            // Departemen dokumen dari role user (tahap 3, poin 8).
            'dept_options' => dept_options_for('material_request', 'add'),
        ];
        $data['dept_default'] = default_dept_for($data['dept_options']);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_request/add', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Simpan MRF baru — header + SEMUA item sekaligus dalam 1 submit,
     * dikirim dari form staged di material_request/add.php (field POST
     * `items` = JSON array [{wo_header_id, component_id, component_type,
     * qty, notes}, ...]). Reuse penuh Mr_model::insert_header()/insert_item()
     * yang sudah ada (sama persis dipakai create_header()/add_item_ajax()
     * lama) — cuma titik pemanggilannya digabung jadi 1 request.
     */
    public function store() {
        if (!check_access('material_request', 'add')) {
            show_error('Anda tidak memiliki akses untuk membuat MR.', 403);
        }

        $request_date = $this->input->post('request_date');
        $remarks      = $this->input->post('remarks');
        $items        = json_decode((string) $this->input->post('items'), true);

        // Departemen dari form; kalau tidak terkirim (halaman lama ter-cache) pakai default user.
        $mr_dept_options = dept_options_for('material_request', 'add');
        $department_code = normalize_department_code($this->input->post('department_code')) ?: default_dept_for($mr_dept_options);
        if ($department_code === null || !isset($mr_dept_options[$department_code])) {
            $this->session->set_flashdata('error', 'Departemen Material Request tidak valid atau di luar departemen role Anda.');
            redirect('material_request/add');
            return;
        }

        if (empty($request_date)) {
            $this->session->set_flashdata('error', 'Tanggal request wajib diisi.');
            redirect('material_request/add');
            return;
        }

        if (empty($items) || !is_array($items)) {
            $this->session->set_flashdata('error', 'Minimal satu item harus ditambahkan.');
            redirect('material_request/add');
            return;
        }

        $header_data = [
            'mr_number'    => $this->Mr_model->generate_mr_number($department_code),
            'request_date' => $request_date,
            'remarks'      => $remarks,
            'status'       => 'Draft',
            'requester_id' => $this->session->userdata('user_id') ?? null,
            'requested_at' => date('Y-m-d H:i:s'),
            'department_code' => $department_code,
        ];

        $new_mr_id = $this->Mr_model->insert_header($header_data);

        if (!$new_mr_id) {
            $this->session->set_flashdata('error', 'Gagal membuat header MR!');
            redirect('material_request/add');
            return;
        }

        $success_count = 0;
        foreach ($items as $it) {
            $qty = isset($it['qty']) ? (float) $it['qty'] : 0;
            if ($qty <= 0 || empty($it['wo_header_id']) || empty($it['component_id']) || empty($it['component_type'])) {
                continue;
            }
            $inserted = $this->Mr_model->insert_item([
                'mr_header_id'        => $new_mr_id,
                'wo_header_id'        => $it['wo_header_id'],
                'component_item_id'   => $it['component_id'],
                'component_item_type' => $it['component_type'],
                'requested_qty'       => $qty,
                'notes'               => $it['notes'] ?? null,
            ]);
            if ($inserted) $success_count++;
        }

        if ($success_count > 0) {
            $this->session->set_flashdata('success', "Material Request berhasil dibuat dengan {$success_count} item.");
        } else {
            $this->session->set_flashdata('error', 'Material Request dibuat tapi semua item gagal disimpan. Silakan tambahkan item manual di halaman ini.');
        }
        redirect('material_request/detail/' . $new_mr_id);
    }

    /**
     * @deprecated Dead code sejak 2026-09-04 — dulu dipanggil modal "Buat MR
     * Baru" di material_request/index.php (header-only, redirect ke detail()
     * utk tambah item satu2). Modal itu sudah dihapus, diganti halaman penuh
     * add()+store() di atas. DIBIARKAN (bukan dihapus) sesuai konvensi
     * proyek ini — gampang rollback, tidak ada resiko krn tidak ada UI yang
     * memanggilnya lagi.
     */
    public function create_header() {
        if (!check_access('material_request', 'add')) { show_error('Anda tidak memiliki akses untuk membuat MR.', 403); }

        $data = [
            'mr_number'     => $this->Mr_model->generate_mr_number(),
            'request_date'  => $this->input->post('request_date'),
            'remarks'       => $this->input->post('remarks'),
            'status'        => 'Draft',
            'requester_id'  => $this->session->userdata('user_id') ?? null, // Langsung set Dibuat Oleh
            'requested_at'  => date('Y-m-d H:i:s')
        ];

        $new_mr_id = $this->Mr_model->insert_header($data);

        if ($new_mr_id) {
            $this->session->set_flashdata('success', 'Header MR berhasil dibuat. Silakan tambahkan item.');
            redirect('material_request/detail/' . $new_mr_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat header MR!');
            redirect('material_request');
        }
    }

    public function delete_mr($id) {
         if (!check_access('material_request', 'delete')) { show_error('Anda tidak memiliki akses untuk menghapus MR.', 403); }
         $header = $this->Mr_model->get_header_by_id($id);
         if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('error', 'MR hanya dapat dihapus jika status masih Draft.');
             redirect('material_request');
         }
         if (!$this->_in_scope($header, 'delete')) { show_error('Material Request ini milik departemen lain — Anda tidak berhak menghapusnya.', 403); }
         if ($this->Mr_model->delete_header($id)) { $this->session->set_flashdata('success', 'MR berhasil dihapus.'); }
         else { $this->session->set_flashdata('error', 'Gagal menghapus MR. Silakan coba lagi.'); }
         redirect('material_request');
    }

    public function detail($id) {
        if (!check_access('material_request', 'view')) { show_error('Anda tidak memiliki akses ke modul ini.', 403); }
        if (empty($id)) show_404();

        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header) show_404();
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). MRF final tetap terbuka (sama dengan daftar).
        if (!can_view_dept_doc('material_request', 'material_request_approval', $this->_doc_dept($header),
                (int) $header->requester_id === (int) $this->session->userdata('user_id'),
                in_array($header->status, ['Approved', 'Partial Issued', 'Issued', 'Received', 'Closed'], true))) {
            show_error('Material Request ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }

        $data = [
            'title'   => lang_line('title_mr_detail'),
            'header'  => $header,
            'details' => $this->Mr_model->get_details_by_id($id),
            // Tombol edit/submit mengikuti departemen MRF (tahap 3).
            'doc_dept' => $this->_doc_dept($header),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_request/detail', $data);
        // Footer dipanggil di view
    }

    /**
     * Isi daftar WO pada modal "Load from WO Materials" di form Buat MRF.
     * Mengembalikan SELURUH WO In Progress (tidak dipotong limit 10 seperti
     * select2 lama) beserta keterangannya, supaya user bisa memilih tanpa
     * harus tahu nomornya lebih dulu.
     */
    public function ajax_wo_list() {
        header('Content-Type: application/json');

        // Gate-nya mengikuti pemakaiannya: dipakai saat membuat/mengubah MRF.
        if (!check_access('material_request', 'add') && !check_access('material_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin.']);
            return;
        }

        $rows = $this->Mr_model->get_wo_list_for_mrf([
            'search' => trim((string) $this->input->get('search', TRUE)),
        ]);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'                 => (int) $r->id,
                'wo_number'          => $r->wo_number,
                'sc_number'          => $r->sc_number,
                'fg_code'            => $r->fg_code,
                'fg_name'            => $r->fg_name,
                'planned_qty'        => (float) $r->planned_qty,
                'planned_start_date' => $r->planned_start_date,
                'material_count'     => (int) $r->material_count,
            ];
        }

        echo json_encode(['success' => true, 'rows' => $out]);
    }

    public function search_open_wos() {
        $term = $this->input->get('q');
        $wos = $this->Mr_model->search_open_wos($term);
        $results = [];
        foreach ($wos as $wo) {
            $results[] = ['id' => $wo->id, 'text' => htmlspecialchars($wo->wo_number . ' - ' . $wo->fg_name)];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }
    
    public function get_wo_references_json() {
        $wo_id = $this->input->get('wo_id');
        if (!$wo_id) { echo json_encode(['success' => false]); return; }
        
        $references = $this->Mr_model->get_wo_references($wo_id);
        if ($references['header']) {
            $data['header'] = $references['header'];
            $data['bom'] = $references['bom'];
            $data['plan'] = $references['plan'];
            $html = $this->load->view('material_request/ajax_wo_reference', $data, true);
            echo json_encode(['success' => true, 'html' => $html]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
    
    public function search_components() {
        $term = $this->input->get('q');
        $items = $this->Mr_model->search_components($term);
        $results = [];
        foreach ($items as $item) {
            $stock_str = number_format((float)$item->stock, 2, '.', ',');
            $unit = $item->unit ?: '';
            $results[] = [
                'id'    => $item->id . '|' . $item->type,
                'text'  => htmlspecialchars($item->name . ' (' . $item->code . ') - Stock: ' . $stock_str . ' ' . $unit),
                'stock' => (float)$item->stock,
                'unit'  => $unit,
                'code'  => $item->code,
                'name'  => $item->name,
            ];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    public function add_item_ajax($mr_id) {
        header('Content-Type: application/json');
        
        if (!check_access('material_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengedit MR.']);
            return;
        }
        
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') {
            echo json_encode(['success' => false, 'message' => 'MR tidak valid atau sudah disubmit']);
            return;
        }
        if (!$this->_in_scope($header, 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Material Request ini milik departemen lain — Anda tidak berhak mengubahnya.']);
            return;
        }
        
        $component_id = $this->input->post('component_id');
        $component_type = $this->input->post('component_type');
        
        if (!$component_id || !$component_type) {
            echo json_encode(['success' => false, 'message' => 'Data komponen tidak lengkap atau tidak valid']);
            return;
        }
        
        $data = [
            'mr_header_id'        => $mr_id,
            'wo_header_id'        => $this->input->post('wo_header_id'),
            'component_item_id'   => $component_id,
            'component_item_type' => $component_type,
            'requested_qty'       => (float) $this->input->post('requested_qty'),
            'notes'               => $this->input->post('notes')
        ];
        
        $inserted_id = $this->Mr_model->insert_item($data);
        
        if ($inserted_id) {
            $full_item = $this->Mr_model->get_item_by_id($inserted_id);
            echo json_encode([
                'success' => true,
                'item' => $full_item,
                'message' => 'Item berhasil ditambahkan'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menambahkan item (mungkin item sudah ada)'
            ]);
        }
    }
    
    public function add_item($mr_id) {
        if (!check_access('material_request', 'edit')) { show_error('Anda tidak memiliki akses untuk mengedit MR.', 403); }
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') { redirect('material_request/detail/' . $mr_id); }
        if (!$this->_in_scope($header, 'edit')) { show_error('Material Request ini milik departemen lain — Anda tidak berhak mengubahnya.', 403); }

        $item_data = explode('|', $this->input->post('component_id'));
        if (count($item_data) < 2) { redirect('material_request/detail/' . $mr_id); }

        $data = [
            'mr_header_id'        => $mr_id,
            'wo_header_id'        => $this->input->post('wo_header_id'),
            'component_item_id'   => $item_data[0],
            'component_item_type' => $item_data[1],
            'requested_qty'       => (float) $this->input->post('requested_qty'),
            'notes'               => $this->input->post('notes')
        ];

        if ($this->Mr_model->insert_item($data)) { $this->session->set_flashdata('success', 'Item berhasil ditambahkan.'); }
        else { $this->session->set_flashdata('error', 'Item ini sudah ada dalam daftar untuk WO yang dipilih.'); }
        redirect('material_request/detail/' . $mr_id);
    }
    
    public function add_items_batch($mr_id) {
        header('Content-Type: application/json');
        
        if (!check_access('material_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengedit MR.']);
            return;
        }
        
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') {
            echo json_encode(['success' => false, 'message' => 'MR tidak valid atau sudah disubmit']);
            return;
        }
        if (!$this->_in_scope($header, 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Material Request ini milik departemen lain — Anda tidak berhak mengubahnya.']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada item yang dikirim']);
            return;
        }
        
        $inserted_items = [];
        $success_count = 0;
        
        foreach ($items as $item) {
            $item_data = explode('|', $item['component_id']);
            if (count($item_data) < 2) continue;
            
            $data = [
                'mr_header_id'        => $mr_id,
                'wo_header_id'        => $item['wo_id'],
                'component_item_id'   => $item_data[0],
                'component_item_type' => $item_data[1],
                'requested_qty'       => (float) $item['qty'],
                'notes'               => $item['notes'] ?? ''
            ];
            
            $inserted_id = $this->Mr_model->insert_item($data);
            if ($inserted_id) {
                $success_count++;
                // Get full item data untuk return
                $full_item = $this->Mr_model->get_item_by_id($inserted_id);
                if ($full_item) {
                    $inserted_items[] = $full_item;
                }
            }
        }
        
        if ($success_count > 0) {
            echo json_encode([
                'success' => true,
                'count' => $success_count,
                'items' => $inserted_items,
                'message' => "$success_count item berhasil ditambahkan"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menambahkan item. Silakan coba lagi.'
            ]);
        }
    }

    public function ajax_get_wo_materials() {
        header('Content-Type: application/json');
        
        $wo_id = $this->input->get('wo_id');
        if (!$wo_id) {
            echo json_encode(['success' => false, 'message' => 'ID Work Order tidak valid']);
            return;
        }

        // Load Wo_model dan ambil planned materials
        $this->load->model('Wo_model');
        $wo_header = $this->Wo_model->get_header_by_id($wo_id);
        
        if (!$wo_header) {
            echo json_encode(['success' => false, 'message' => 'Work Order tidak ditemukan']);
            return;
        }

        $planned_materials = $this->Wo_model->get_planned_materials($wo_id);
        
        if (empty($planned_materials)) {
            echo json_encode([
                'success' => false,
                'message' => 'Belum ada material yang direncanakan untuk WO ini'
            ]);
            return;
        }

        // Format materials untuk display
        $materials = [];
        foreach ($planned_materials as $mat) {
            $materials[] = [
                'id' => $mat->item_id,
                'type' => $mat->item_type,
                'code' => $mat->item_code,
                'name' => $mat->item_name,
                'planned_qty' => (float)$mat->planned_qty,
                'unit' => $mat->unit_measure,
                'stock' => $this->Mr_model->get_stock_for_item($mat->item_id, $mat->item_type)
            ];
        }

        echo json_encode([
            'success' => true,
            'wo_number' => $wo_header->wo_number,
            'materials' => $materials
        ]);
    }

    public function ajax_add_materials_from_wo($mr_id) {
        header('Content-Type: application/json');
        
        if (!check_access('material_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengedit MR.']);
            return;
        }

        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') {
            echo json_encode(['success' => false, 'message' => 'MR tidak valid atau sudah disubmit']);
            return;
        }
        if (!$this->_in_scope($header, 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Material Request ini milik departemen lain — Anda tidak berhak mengubahnya.']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $wo_id = $input['wo_id'] ?? null;
        $selected_items = $input['selected_items'] ?? [];

        if (!$wo_id || empty($selected_items)) {
            echo json_encode(['success' => false, 'message' => 'WO atau materials tidak valid']);
            return;
        }

        $inserted_items = [];
        $success_count = 0;
        $duplicate_count = 0;

        foreach ($selected_items as $item) {
            $component_id = $item['id'];
            $component_type = $item['type'];
            $requested_qty = (float)($item['qty'] ?? 0);

            if ($requested_qty <= 0) continue;

            $data = [
                'mr_header_id'        => $mr_id,
                'wo_header_id'        => $wo_id,
                'component_item_id'   => $component_id,
                'component_item_type' => $component_type,
                'requested_qty'       => $requested_qty,
                'notes'               => ''
            ];

            $inserted_id = $this->Mr_model->insert_item($data);
            if ($inserted_id) {
                $success_count++;
                $full_item = $this->Mr_model->get_item_by_id($inserted_id);
                if ($full_item) {
                    $inserted_items[] = $full_item;
                }
            } else {
                $duplicate_count++;
            }
        }

        if ($success_count > 0) {
            echo json_encode([
                'success' => true,
                'count' => $success_count,
                'duplicates' => $duplicate_count,
                'items' => $inserted_items,
                'message' => "$success_count item berhasil ditambahkan" . ($duplicate_count > 0 ? " ($duplicate_count item sudah ada)" : "")
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menambahkan item. Semua item mungkin sudah ada dalam daftar.'
            ]);
        }
    }
    
    public function delete_item_ajax() {
        header('Content-Type: application/json');
        
        if (!check_access('material_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengedit MR.']);
            return;
        }
        
        $item_id = $this->input->post('item_id');
        if (!$item_id) {
            echo json_encode(['success' => false, 'message' => 'Item ID tidak valid']);
            return;
        }
        
        // Cek apakah item masih dalam status Draft
        $item = $this->Mr_model->get_item_by_id($item_id);
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
            return;
        }
        
        $header = $this->Mr_model->get_header_by_id($item->mr_header_id);
        if (!$header || $header->status != 'Draft') {
            echo json_encode(['success' => false, 'message' => 'Item tidak dapat dihapus karena MR sudah disubmit.']);
            return;
        }
        if (!$this->_in_scope($header, 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Material Request ini milik departemen lain — Anda tidak berhak mengubahnya.']);
            return;
        }
        
        if ($this->Mr_model->delete_item($item_id)) {
            echo json_encode(['success' => true, 'message' => 'Item berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus item']);
        }
    }
    
    public function delete_item($mr_id, $detail_id) {
        if (!check_access('material_request', 'edit')) { show_error('Anda tidak memiliki akses untuk mengedit MR.', 403); }
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') { redirect('material_request/detail/' . $mr_id); }
        if (!$this->_in_scope($header, 'edit')) { show_error('Material Request ini milik departemen lain — Anda tidak berhak mengubahnya.', 403); }

        if ($this->Mr_model->delete_item($detail_id)) { $this->session->set_flashdata('success', 'Item berhasil dihapus.'); }
        else { $this->session->set_flashdata('error', 'Gagal menghapus item. Silakan coba lagi.'); }
        redirect('material_request/detail/' . $mr_id);
    }
    
    public function submit_mr($id) {
        if (!check_access('material_request', 'submit')) { show_error('Anda tidak memiliki izin submit Material Request.', 403); }
        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') { redirect('material_request/detail/' . $id); }
        if (!$this->_in_scope($header, 'submit')) { show_error('Anda tidak berhak submit Material Request departemen ini.', 403); }
        $details = $this->Mr_model->get_details_by_id($id);
        if (empty($details)) {
             $this->session->set_flashdata('error', 'MR tidak dapat disubmit. Silakan tambahkan minimal satu item.');
             redirect('material_request/detail/' . $id);
        }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Mr_model->update_status($id, 'Submitted', $user_id)) {
            $this->session->set_flashdata('success', 'MR berhasil disubmit ke Gudang.');
        } else { $this->session->set_flashdata('error', 'Gagal submit MR. Silakan coba lagi.'); }
        redirect('material_request/detail/' . $id);
    }

    public function unsubmit_mr($id) {
        if (!check_access('material_request', 'edit')) { show_error('Anda tidak memiliki akses untuk mengedit MR.', 403); }
        $header = $this->Mr_model->get_status_by_id($id);
        if (!$header || $header->status != 'Submitted') { redirect('material_request/detail/' . $id); }
        $full = $this->Mr_model->get_header_by_id($id);
        if (!$this->_in_scope($full, 'edit')) { show_error('Material Request ini milik departemen lain — Anda tidak berhak membatalkan submit-nya.', 403); }

        // Batal Submit = hapus MR sepenuhnya dari DB agar nomor IRF tidak "terpakai" / dapat di-reuse.
        $user_id = $this->session->userdata('user_id') ?? null;

        $this->db->trans_start();

        // 1) Clear FK di production_scrap_sources (kalau MR ini auto-generated dari scrap)
        $detail_ids = array_map(fn($r) => (int)$r->id,
            $this->db->select('id')->where('mr_header_id', (int)$id)->get('mr_details')->result()
        );
        if (!empty($detail_ids)) {
            $this->db->set('mr_detail_id', null)
                     ->where_in('mr_detail_id', $detail_ids)
                     ->update('production_scrap_sources');
        }

        // 2) Hapus mr_details
        $this->db->where('mr_header_id', (int)$id)->delete('mr_details');

        // 3) Revert ke Draft → delete header (delete_header model hanya allow Draft)
        $this->Mr_model->update_status($id, 'Draft', $user_id);
        $deleted = $this->Mr_model->delete_header($id);

        $this->db->trans_complete();

        if ($this->db->trans_status() !== FALSE && $deleted) {
            $this->session->set_flashdata('success', 'Submit MR dibatalkan dan MR dihapus dari sistem.');
            redirect('material_request');
            return;
        }

        $this->session->set_flashdata('error', 'Gagal membatalkan submit. Silakan coba lagi.');
        redirect('material_request/detail/' . $id);
    }

    // === Approval workflow: 3 stage (Head of Dept -> Endorsement -> Final Approval) ===
    // Permission check: pakai menu `material_request_approval` (BUKAN `material_request`).
    // Redirect: parameter `redirect` (POST/GET) menentukan kembali ke detail atau ke list approval.
    private function _approval_redirect($id) {
        $back = $this->input->post('redirect') ?: $this->input->get('redirect');
        if ($back === 'approval') {
            redirect('material_request/approval');
        }
        redirect('material_request/detail/' . $id);
    }

    public function approve_hod($id) {
        if (!check_access('material_request_approval', 'head_dept')) {
            show_error('Anda tidak memiliki akses untuk approve sebagai Head of Dept.', 403);
        }
        $full = $this->Mr_model->get_header_by_id($id);
        if ($full) $this->_require_approval_scope($full, 'head_dept');
        $header = $this->Mr_model->get_status_by_id($id);
        if (!$header || $header->status != 'Submitted') {
            $this->session->set_flashdata('error', 'MR hanya dapat di-approve Head of Dept jika status Submitted.');
            $this->_approval_redirect($id); return;
        }
        $user_id = $this->session->userdata('user_id') ?? null;
        // Sejak 2026-11-05 approval Material Request cukup 1 tahap: Head of Dept /
        // atasan langsung final -> MR masuk ke Material Issuance.
        if ($this->Mr_model->update_status($id, 'Approved', $user_id, ['hod_approved_by' => $user_id, 'hod_approved_at' => date('Y-m-d H:i:s')])) {
            $this->session->set_flashdata('success', 'Material Request di-approve (1 tahap). MR sekarang masuk ke Material Issuance.');
        } else {
            $this->session->set_flashdata('error', 'Gagal approve MR. Silakan coba lagi.');
        }
        $this->_approval_redirect($id);
    }

    public function approve_endorsement($id) {
        if (!check_access('material_request_approval', 'endorsement')) {
            show_error('Anda tidak memiliki akses untuk endorsement.', 403);
        }
        $full = $this->Mr_model->get_header_by_id($id);
        if ($full) $this->_require_approval_scope($full, 'endorsement');
        $header = $this->Mr_model->get_status_by_id($id);
        if (!$header || $header->status != 'Approved Head Dept') {
            $this->session->set_flashdata('error', 'MR hanya dapat di-endorse jika sudah di-approve Head of Dept.');
            $this->_approval_redirect($id); return;
        }
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Mr_model->update_status($id, 'Endorsed', $user_id)) {
            $this->session->set_flashdata('success', 'MR berhasil di-endorse.');
        } else {
            $this->session->set_flashdata('error', 'Gagal endorse MR. Silakan coba lagi.');
        }
        $this->_approval_redirect($id);
    }

    public function approve_final($id) {
        if (!check_access('material_request_approval', 'approval')) {
            show_error('Anda tidak memiliki akses untuk final approval.', 403);
        }
        $full = $this->Mr_model->get_header_by_id($id);
        if ($full) $this->_require_approval_scope($full, 'approval');
        $header = $this->Mr_model->get_status_by_id($id);
        if (!$header || $header->status != 'Endorsed') {
            $this->session->set_flashdata('error', 'MR hanya dapat di-approve final jika sudah di-endorse.');
            $this->_approval_redirect($id); return;
        }
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Mr_model->update_status($id, 'Approved', $user_id)) {
            $this->session->set_flashdata('success', 'MR final di-approve. MR sekarang masuk ke Material Issuance.');
        } else {
            $this->session->set_flashdata('error', 'Gagal final approve MR. Silakan coba lagi.');
        }
        $this->_approval_redirect($id);
    }

    /**
     * Return to Draft — di setiap tahap approval, approver bisa balikin ke Draft.
     * Status balik ke 'Draft', creator bisa revisi & submit ulang.
     * Audit approval di-reset (mulai dari 0 lagi).
     */
    public function return_to_draft($id) {
        $can_action = check_access('material_request_approval', 'head_dept')
                   || check_access('material_request_approval', 'endorsement')
                   || check_access('material_request_approval', 'approval');
        if (!$can_action) {
            show_error('Anda tidak memiliki akses untuk return MR.', 403);
        }

        $full = $this->Mr_model->get_header_by_id($id);
        if ($full && in_array($full->status, ['Submitted', 'Approved Head Dept', 'Endorsed'], true)) $this->_require_approval_scope($full);
        $header = $this->Mr_model->get_status_by_id($id);
        if (!$header || !in_array($header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'])) {
            $this->session->set_flashdata('error', 'MR ini tidak dalam tahap approval.');
            $this->_approval_redirect($id); return;
        }

        $reason = trim($this->input->post('return_reason'));
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Alasan return wajib diisi.');
            $this->_approval_redirect($id); return;
        }

        $user_id = $this->session->userdata('user_id') ?? null;
        $ok = $this->Mr_model->update_status($id, 'Returned to Draft', $user_id, ['return_reason' => $reason]);
        if ($ok) {
            $this->session->set_flashdata('success', "MR {$header->mr_number} dikembalikan ke Draft. Creator dapat merevisi & submit ulang.");
        } else {
            $this->session->set_flashdata('error', 'Gagal return MR.');
        }
        $this->_approval_redirect($id);
    }

    /**
     * Reject final — status → Rejected, tidak bisa di-revisi (creator harus bikin baru).
     */
    public function reject($id) {
        $can_action = check_access('material_request_approval', 'head_dept')
                   || check_access('material_request_approval', 'endorsement')
                   || check_access('material_request_approval', 'approval');
        if (!$can_action) {
            show_error('Anda tidak memiliki akses untuk reject MR.', 403);
        }

        $full = $this->Mr_model->get_header_by_id($id);
        if ($full && in_array($full->status, ['Submitted', 'Approved Head Dept', 'Endorsed'], true)) $this->_require_approval_scope($full);
        $header = $this->Mr_model->get_status_by_id($id);
        if (!$header || !in_array($header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'])) {
            $this->session->set_flashdata('error', 'MR ini tidak dalam tahap approval.');
            $this->_approval_redirect($id); return;
        }

        $reason = trim($this->input->post('reject_reason'));
        if (empty($reason)) {
            $this->session->set_flashdata('error', 'Alasan reject wajib diisi.');
            $this->_approval_redirect($id); return;
        }

        $user_id = $this->session->userdata('user_id') ?? null;
        $ok = $this->Mr_model->update_status($id, 'Rejected', $user_id, ['reject_reason' => $reason]);
        if ($ok) {
            $this->session->set_flashdata('success', "MR {$header->mr_number} berhasil di-reject.");
        } else {
            $this->session->set_flashdata('error', 'Gagal reject MR.');
        }
        $this->_approval_redirect($id);
    }
    
    public function export_pdf($id) {
        if (!check_access('material_request', 'view')) { show_error('Anda tidak memiliki akses ke modul ini.', 403); }
        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header) show_404();
        // Dokumen departemen lain tidak bisa dibuka lewat URL (tahap 3). MRF final tetap terbuka (sama dengan daftar).
        if (!can_view_dept_doc('material_request', 'material_request_approval', $this->_doc_dept($header),
                (int) $header->requester_id === (int) $this->session->userdata('user_id'),
                in_array($header->status, ['Approved', 'Partial Issued', 'Issued', 'Received', 'Closed'], true))) {
            show_error('Material Request ini milik departemen lain — Anda tidak berhak melihatnya.', 403);
        }
        $details = $this->Mr_model->get_details_by_id($id);

        // Trace IRF -> PR -> PO -> GRN only used to populate unit prices on the items table.
        // Signatures use the MR's own approval columns (applicant + 3 approvers).
        $trace = $this->_get_irf_trace($header->mr_number);
        $po_id = $trace['po'] ? (int)$trace['po']->id : 0;

        $items = [];
        $no = 1;
        foreach ($details as $d) {
            // Unit price comes from the PO line linked to this IRF (if a PO exists).
            $unit_price = 0;
            if ($po_id) {
                $po_line = $this->db->select('local_price')
                    ->from('po_details')
                    ->where('po_header_id', $po_id)
                    ->where('item_id', $d->component_item_id)
                    ->where('item_type', $d->component_item_type)
                    ->limit(1)->get()->row();
                if ($po_line) { $unit_price = (float)$po_line->local_price; }
            }
            $qty = (float)$d->requested_qty;
            $items[] = [
                'no'         => $no++,
                'item_code'  => $d->item_code,
                'item_name'  => $d->item_name,
                'qty'        => $qty,
                'unit'       => $d->item_unit ?? $d->unit_measure,
                'unit_price' => $unit_price,
                'amount'     => $unit_price * $qty,
                'notes'      => $d->notes,
                // Untuk MRF PDF: tampil di kolom IPO Number (gantikan Unit Price kolom).
                'sc_number'  => $d->sc_number ?? '',
            ];
        }

        // Department: show common_code description (e.g. "Production"), not its short code.
        $department = '';
        if (!empty($header->requester_id)) {
            $u = $this->db->select('departemen')->from('users')->where('id', $header->requester_id)->get()->row();
            if ($u && !empty($u->departemen)) {
                $cc = $this->db->select('description')->from('common_code')
                    ->where('group_code', 'Department')
                    ->group_start()
                        ->where('code', $u->departemen)
                        ->or_where('description', $u->departemen)
                    ->group_end()
                    ->limit(1)->get()->row();
                $department = $cc ? $cc->description : $u->departemen;
            }
        }
        // Fallback: extract dept code from MR number prefix (e.g. "PROD" from "PROD00001")
        if ($department === '' && !empty($header->mr_number)) {
            $dept_code = preg_replace('/\d+$/', '', $header->mr_number);
            if ($dept_code !== '') {
                $cc = $this->db->select('description')->from('common_code')
                    ->where('group_code', 'Department')
                    ->group_start()
                        ->where('code', $dept_code)
                        ->or_where('description', $dept_code)
                    ->group_end()
                    ->limit(1)->get()->row();
                $department = $cc ? $cc->description : $dept_code;
            }
        }

        $signatures = $this->_build_irf_signatures($header, $trace);

        $data = [
            'irf_number'    => $header->mr_number,
            'request_date'  => $header->request_date,
            'department'    => $department,
            'purpose'       => $header->remarks,
            'items'         => $items,
            'form_type'     => 'Material',
            'signatures'    => $signatures,
            // Approval 1 tahap (2026-11-05): Applicant + Head of Dept / atasan.
            'signature_labels' => [
                ['Applicant',    '申請人'],
                ['Approve by (Head of Dept.)', '部門主管核准'],
            ],
            'form_title'    => 'MATERIAL REQUEST',
            'number_label'  => 'Material Request',
        ];

        $filename = "MR_" . str_replace([':', ' ', '/'], ['', '_', '-'], $header->mr_number) . ".pdf";
        render_document_pdf($data, $filename);
    }

    /**
     * Trace IRF (mr_header) downstream:
     *   IRF -> PR (created from outstanding, identified via pr_header.remarks LIKE %mr_number%)
     *        -> PO (po_header.pr_header_id atau po_details.pr_header_id)
     *        -> GRN (grn linked to PO via grn_details.source_doc_type='PO')
     * Returns the latest record at each stage, or null if not yet reached.
     */
    private function _get_irf_trace($mr_number) {
        $result = ['pr' => null, 'po' => null, 'grn' => null];
        if (empty($mr_number)) return $result;

        $pr = $this->db->select('id, created_by, created_at, approved_by, approved_at, status, pr_number')
            ->from('pr_header')
            ->like('remarks', $mr_number)
            ->order_by('id', 'DESC')->limit(1)->get()->row();
        if (!$pr) return $result;
        $result['pr'] = $pr;

        // Lewat model, bukan query langsung: PR bisa jadi cuma menyumbang
        // sebagian baris ke PO gabungan, sehingga jejaknya ada di po_details
        // dan bukan di po_header.pr_header_id.
        $this->load->model('Po_model');
        $po = $this->Po_model->get_latest_po_for_pr($pr->id);
        if (!$po) return $result;
        $result['po'] = $po;

        $grn = $this->db->select('grn.id, grn.confirmed_by, grn.confirmed_at, grn.created_by, grn.created_at, grn.grn_date, grn.grn_number')
            ->from('grn_header grn')
            ->join('grn_details gd', 'gd.grn_header_id = grn.id', 'inner')
            ->join('po_details pod', "pod.id = gd.source_doc_detail_id AND gd.source_doc_type = 'PO'", 'inner')
            ->where('pod.po_header_id', $po->id)
            ->order_by('grn.id', 'DESC')->limit(1)->get()->row();
        if ($grn) { $result['grn'] = $grn; }

        return $result;
    }

    /**
     * Build the 4-signature array for the Material Request IRF PDF.
     * Slots: Applicant, Head of Dept, Endorsement, Approve by.
     * Sumber data: kolom approval di mr_header (diisi oleh aksi approve_hod / approve_endorsement / approve_final).
     */
    private function _build_irf_signatures($header, $trace) {
        $applicant    = build_signature_slot($header->requester_id      ?? null, $header->requested_at      ?? ($header->created_at ?? null));
        $head_of_dept = build_signature_slot($header->hod_approved_by   ?? null, $header->hod_approved_at   ?? null);

        return [$applicant, $head_of_dept];
    }
}