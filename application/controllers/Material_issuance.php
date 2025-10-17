<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_issuance extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Mr_model', 'Material_issuance_model', 'Stock_ledger_model']);
        $this->load->helper('url');
    }

    // Halaman daftar MR (Submitted)
    public function index() {
        if (!check_access('material_issuance', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        
        // ✅ REVISI: Ambil filter status
        $filters = [
            'search' => $this->input->get('search'),
            'status' => $this->input->get('status')
        ];

        $data = [
            'title'   => 'Material Issuance (Pengeluaran Barang)',
            'mrs'     => $this->Material_issuance_model->get_all_mrs($filters), // ✅ REVISI: Panggil fungsi baru
            'filters' => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_issuance/index', $data);
        $this->load->view('templates/footer');
    }

    // Halaman detail untuk input Qty Gudang
    public function detail($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($mr_id)) show_404();

        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || !in_array($header->status, ['Submitted', 'Issued', 'Received'])) {
             $this->session->set_flashdata('error', 'Material Request ini tidak valid.');
             redirect('material_issuance');
        }

        $data = [
            'title'   => 'Proses Pengeluaran Material',
            'header'  => $header,
            'details' => $this->Material_issuance_model->get_mr_details_for_issuance($mr_id)
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_issuance/detail', $data);
    }
    
    // AJAX: Ambil stok on hand untuk validasi
    public function ajax_get_stock() {
        $item_id = $this->input->get('item_id');
        $item_type = $this->input->get('item_type');
        
        if(!$item_id || !$item_type){
             echo json_encode(['success' => false, 'stock' => 0]); return;
        }
        
        $stock_on_hand = $this->Stock_ledger_model->get_last_balance($item_id, $item_type);
        echo json_encode(['success' => true, 'stock' => $stock_on_hand]);
    }
    
    // Proses Konfirmasi Pengeluaran (Submit Form)
    public function process_issuance($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Submitted') {
             $this->session->set_flashdata('error', 'MR ini tidak bisa diproses.');
             redirect('material_issuance/detail/' . $mr_id);
        }

        $items = $this->input->post('items');
        if (empty($items)) {
             $this->session->set_flashdata('error', 'Tidak ada item yang diisi.');
             redirect('material_issuance/detail/' . $mr_id);
        }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        $result = $this->Material_issuance_model->process_issuance($mr_id, $items, $user_id);
        
        if ($result['success']) {
            $this->session->set_flashdata('success', 'Barang berhasil dikeluarkan dan stok dipotong.');
        } else {
            $this->session->set_flashdata('error', 'Gagal: ' . $result['message']);
        }
        redirect('material_issuance/detail/' . $mr_id);
    }
    
    // Proses Batal Pengeluaran (Un-Issue)
    public function reverse_issuance($mr_id) {
        if (!check_access('material_issuance', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Issued') {
             $this->session->set_flashdata('error', 'MR tidak bisa dibatalkan.');
             redirect('material_issuance/detail/' . $mr_id);
        }
        
        if ($header->status == 'Received') {
           $this->session->set_flashdata('error', 'Tidak bisa dibatalkan, barang sudah diterima Produksi!');
           redirect('material_issuance/detail/' . $mr_id);
        }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Material_issuance_model->reverse_issuance($mr_id, $user_id)) {
            $this->session->set_flashdata('success', 'Pengeluaran berhasil dibatalkan, stok dikembalikan.');
        } else {
             $this->session->set_flashdata('error', 'Gagal membatalkan pengeluaran.');
        }
        redirect('material_issuance/detail/' . $mr_id);
    }
}