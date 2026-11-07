<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Stock_transfer — pindahin saldo stock antar bucket kategori
 * (General <-> Produksi) untuk item yang sama. Lihat Stock_transfer_model
 * utk detail mekanisme (sepasang baris stock_ledger OUT/IN).
 */
class Stock_transfer extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Stock_transfer_model');
        $this->load->helper(['url', 'form']);
    }

    public function index() {
        if (!check_access('stock_transfer', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $data = [
            'title'   => lang_line('title_st_index', 'Stock Transfer'),
            'records' => $this->Stock_transfer_model->get_all([
                'search' => $this->input->get('search'),
            ]),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stock_transfer/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('stock_transfer', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $from_category = $this->input->get('from_category');
        if (!in_array($from_category, Stock_transfer_model::CATEGORIES, true)) {
            $from_category = 'General';
        }

        $data = [
            'title'         => lang_line('title_st_create', 'Buat Stock Transfer'),
            'transfer_number' => $this->Stock_transfer_model->generate_transfer_number(),
            'categories'    => Stock_transfer_model::CATEGORIES,
            'from_category' => $from_category,
            'items_list'    => $this->Stock_transfer_model->get_items_with_stock($from_category),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stock_transfer/form', $data);
        $this->load->view('templates/footer');
    }

    /**
     * AJAX: refresh daftar item+stock ketika dropdown "Dari Kategori" diganti.
     */
    public function ajax_items_by_category() {
        if (!$this->input->is_ajax_request()) { show_404(); }

        $from_category = $this->input->get('from_category');
        if (!in_array($from_category, Stock_transfer_model::CATEGORIES, true)) {
            $from_category = 'General';
        }
        $items = $this->Stock_transfer_model->get_items_with_stock($from_category);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'items' => $items]);
    }

    public function store() {
        if (!check_access('stock_transfer', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        $user_id       = $this->session->userdata('user_id');
        $transfer_date = $this->input->post('transfer_date');
        $from_category = $this->input->post('from_category');
        $to_category   = $this->input->post('to_category');
        $notes         = $this->input->post('notes');
        $items         = $this->input->post('items');

        if (!in_array($from_category, Stock_transfer_model::CATEGORIES, true)
            || !in_array($to_category, Stock_transfer_model::CATEGORIES, true)) {
            $this->session->set_flashdata('error', 'Kategori asal/tujuan tidak valid.');
            redirect('stock_transfer/add');
            return;
        }

        if (empty($transfer_date)) {
            $this->session->set_flashdata('error', 'Tanggal transfer wajib diisi.');
            redirect('stock_transfer/add');
            return;
        }

        $valid_items = is_array($items) ? array_filter($items, function($i) {
            return !empty($i['item_id']) && isset($i['qty']) && (float)$i['qty'] > 0;
        }) : [];

        if (empty($valid_items)) {
            $this->session->set_flashdata('error', 'Minimal satu item dengan qty > 0 harus diisi.');
            redirect('stock_transfer/add');
            return;
        }

        $header_data = [
            'transfer_date' => $transfer_date,
            'from_category' => $from_category,
            'to_category'   => $to_category,
            'notes'         => $notes,
            'created_by'    => $user_id,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $result = $this->Stock_transfer_model->create_transfer($header_data, $valid_items);

        if ($result['success']) {
            $this->session->set_flashdata('success', 'Stock Transfer ' . $result['transfer_number'] . ' berhasil. Saldo ' . $from_category . ' berkurang, ' . $to_category . ' bertambah.');
            redirect('stock_transfer/detail/' . $result['id']);
        } else {
            $this->session->set_flashdata('error', 'Gagal: ' . ($result['message'] ?? 'Terjadi kesalahan.'));
            redirect('stock_transfer/add');
        }
    }

    public function detail($id) {
        if (!check_access('stock_transfer', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Stock_transfer_model->get_header($id);
        if (!$header) { show_404(); }

        $data = [
            'title'   => sprintf(lang_line('title_st_detail', 'Detail Stock Transfer %s'), $header->transfer_number),
            'header'  => $header,
            'details' => $this->Stock_transfer_model->get_details($id),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stock_transfer/detail', $data);
        $this->load->view('templates/footer');
    }
}
