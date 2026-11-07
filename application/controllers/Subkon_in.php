<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subkon_in extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model([
            'Subkon_in_model', 
            'Subkon_order_model',
            'Stock_ledger_model',
            'Commoncode_model'
        ]);
        $this->load->library(['pagination', 'form_validation']);
        $this->load->helper('url');
    }

    /* ==========================================================
     INDEX & CREATE
     ========================================================== */
    public function index() {
        if (!check_access('subkon_in', 'view')) show_error('Akses ditolak', 403);
        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-t'),
            'status'     => $this->input->get('status')
        ];
        // ... (Logika Pagination) ...
        $config['base_url'] = base_url('subkon_in/index');
        $config['total_rows'] = $this->Subkon_in_model->count_all($filters);
        $config['per_page'] = 25;
        $this->pagination->initialize($config);
        $page = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 1;
        $offset = ($page > 0) ? ($page - 1) * $config['per_page'] : 0;
        
        $data = [
            'title'      => lang_line('title_subkon_in_index'),
            'filters'    => $filters,
            'grns'       => $this->Subkon_in_model->get_all($filters, $config['per_page'], $offset),
            'pagination' => $this->pagination->create_links(),
            'start_no'   => $offset + 1
        ];
        $this->load_views('subkon_in/index', $data);
    }

    public function create() {
        if (!check_access('subkon_in', 'add')) show_error('Akses ditolak', 403);
        $data = [
            'title'    => lang_line('title_subkon_in_create'),
            'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE')
        ];
        $this->load_views('subkon_in/create', $data);
    }

    public function save_header() {
        if (!check_access('subkon_in', 'add')) show_error('Akses ditolak', 403);

        $this->form_validation->set_rules('subkon_order_id', 'Kontrak', 'required|numeric');
        $this->form_validation->set_rules('receipt_date', 'Tanggal Terima', 'required');
        $this->form_validation->set_rules('bc_type_code', 'Jenis BC', 'required');
        $this->form_validation->set_rules('bc_number', 'Nomor BC', 'required');
        $this->form_validation->set_rules('bc_date', 'Tanggal BC', 'required');

        if ($this->form_validation->run() === FALSE) {
            // ... (Error handling) ...
        }

        $order = $this->Subkon_order_model->get_header_by_id($this->input->post('subkon_order_id'));
        if (!$order || $order->status != 'Confirmed') { /* ... (Error handling) ... */ }

        $data = [
            'subkon_in_no'    => $this->Subkon_in_model->generate_grn_subkon_no(),
            'subkon_order_id' => $order->id,
            'supplier_id'     => $order->supplier_id,
            'receipt_date'    => $this->input->post('receipt_date'),
            'bc_type_code'    => $this->input->post('bc_type_code'),
            'bc_number'       => $this->input->post('bc_number'),
            'bc_date'         => $this->input->post('bc_date'),
            'status'          => 'Draft',
            'created_by'      => $this->session->userdata('user_id')
        ];

        $new_id = $this->Subkon_in_model->insert_header($data);
        if ($new_id) {
            $this->session->set_flashdata('success', 'GRN Subkon berhasil dibuat. Lanjut Item.');
            redirect('subkon_in/detail/' . $new_id);
        } else { /* ... (Error handling) ... */ }
    }

    /* ==========================================================
     DETAIL & ADD ITEM
     ========================================================== */
    public function detail($id) {
        if (!check_access('subkon_in', 'view')) show_error('Akses ditolak', 403);

        $header = $this->Subkon_in_model->get_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'           => sprintf(lang_line('title_subkon_in_detail'), $header->subkon_in_no),
            'header'          => $header,
            'detail_items'    => $this->Subkon_in_model->get_details_by_id($id),
            // Item yang dipesan di kontrak dan belum diterima
            'output_items'    => $this->Subkon_in_model->get_available_output_items($header->subkon_order_id)
        ];
        $this->load_views('subkon_in/detail', $data);
    }

    public function add_item($in_id) {
        if (!check_access('subkon_in', 'edit')) show_error('Akses ditolak', 403);

        $this->form_validation->set_rules('output_id', 'Item Hasil', 'required|numeric');
        $this->form_validation->set_rules('qty_received', 'Qty Diterima', 'required|numeric|greater_than[0]');
        if ($this->form_validation->run() === FALSE) { /* ... (Error handling) ... */ }

        $output_id = $this->input->post('output_id');
        $qty_received = (float) $this->input->post('qty_received');
        
        // Ambil data item dan jasa dari output kontrak
        $output_contract = $this->Subkon_order_model->get_output_item_by_id($output_id);
        if (!$output_contract) { /* ... (Error handling) ... */ }
        
        // Ambil sisa kuantitas
        $available_items = $this->Subkon_in_model->get_available_output_items($output_contract->subkon_order_id);
        $item_sisa = array_filter($available_items, fn($item) => $item->id == $output_id);
        $remaining = $item_sisa ? reset($item_sisa)->remaining_qty : 0;
        
        if ($qty_received > $remaining) { /* ... (Error handling) ... */ }

        $data = [
            'subkon_in_id'      => $in_id,
            'order_output_id'   => $output_id,
            'item_id'           => $output_contract->item_id,
            'item_type'         => $output_contract->item_type,
            'qty_received'      => $qty_received,
            'satuan'            => $output_contract->satuan,
            'jasa_per_pcs'      => $output_contract->jasa_per_pcs,
            'total_value_jasa'  => $qty_received * $output_contract->jasa_per_pcs
        ];

        if ($this->Subkon_in_model->insert_item_to_grn($data)) {
            $this->session->set_flashdata('success', 'Item berhasil ditambahkan.');
        } else { /* ... (Error handling) ... */ }
        redirect('subkon_in/detail/' . $in_id);
    }
    public function confirm($id) {
        // PERBAIKAN: Teruskan ID pengguna yang sedang login
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) { show_error('Sesi pengguna tidak valid.', 403); }
        return $this->confirm_grn($id, $user_id); 
    }
    
    // Fungsi Alias: dipanggil dari URL /subkon_in/unconfirm/ID
    public function unconfirm($id) {
        // PERBAIKAN: Teruskan ID pengguna yang sedang login
        $user_id = $this->session->userdata('user_id');
        if (!$user_id) { show_error('Sesi pengguna tidak valid.', 403); }
        return $this->unconfirm_grn($id, $user_id);
    }
    // Tambahkan fungsi ini di dalam class Subkon_in

    /* ==========================================================
     DELETE ITEM
     ========================================================== */
    public function delete_item($in_id, $detail_id) {
        if (!check_access('subkon_in', 'delete')) {
            $this->session->set_flashdata('error', 'Akses ditolak.');
            redirect('subkon_in/detail/' . $in_id);
            return;
        }

        $header = $this->Subkon_in_model->get_header_by_id($in_id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hapus Gagal! Hanya GRN status DRAFT yang itemnya bisa dihapus.');
            redirect('subkon_in/detail/' . $in_id);
            return;
        }

        if ($this->Subkon_in_model->delete_item_from_grn($detail_id)) {
            $this->session->set_flashdata('success', 'Item berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus item dari database.');
        }
        redirect('subkon_in/detail/' . $in_id);
    }
    
    // Fungsi Konfirmasi Stok (Logika Utama)
    public function confirm_grn($id, $user_id) { // <-- Fungsi ini Menerima $user_id
        if (!check_access('subkon_in', 'confirm')) show_error('Akses ditolak', 403);

        $header = $this->Subkon_in_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya status DRAFT yang bisa dikonfirmasi.');
            redirect('subkon_in/detail/' . $id);
            return;
        }

        $result = $this->Subkon_in_model->confirm_grn($id, $user_id);

        if ($result) {
            $this->session->set_flashdata('success', 'GRN Subkon berhasil dikonfirmasi. Stok dan HPP Jasa dicatat.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mengonfirmasi. Cek log.');
        }
        redirect('subkon_in/detail/' . $id);
    }

    // Fungsi Batal Konfirmasi Stok (Logika Utama)
    public function unconfirm_grn($id, $user_id) { // <-- Fungsi ini Menerima $user_id
        if (!check_access('subkon_in', 'edit')) show_error('Akses ditolak', 403);

        $header = $this->Subkon_in_model->get_header_by_id($id);
        if (!$header || $header->status != 'Completed') {
            $this->session->set_flashdata('error', 'Hanya status COMPLETED yang bisa dibatalkan.');
            redirect('subkon_in/detail/' . $id);
            return;
        }

        // Cek validasi stok belum terpakai ada di dalam model
        $result = $this->Subkon_in_model->unconfirm_grn($id);

        if ($result) {
            $this->session->set_flashdata('success', 'Konfirmasi GRN berhasil dibatalkan. Stok telah dikembalikan.');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan konfirmasi. Cek log atau pastikan stok belum terpakai.');
        }
        redirect('subkon_in/detail/' . $id);
    }
    // (Tambahkan fungsi confirm_grn, unconfirm_grn, delete_item seperti di Subkon_out)

    /* ==========================================================
     AJAX
     ========================================================== */
    public function ajax_search_confirmed_orders() {
        if (!$this->input->is_ajax_request()) show_error('No direct access', 403);
        $term = $this->input->get('q', true);
        $data = $this->Subkon_in_model->get_completed_orders_for_receipt($term);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['results' => $data]);
    }

    /* ==========================================================
     UTIL
     ========================================================== */
    private function load_views($main_view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }
}