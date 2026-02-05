<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StockOpname extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('StockOpname_model', 'model');
        // Kita akan panggil model Adjustment untuk proses konfirmasi
        $this->load->model('Adjustment_model'); 
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    // Halaman Utama: Daftar Sesi Opname
    public function index()
    {
        $data['title'] = 'Stock Opname';
        $data['sessions'] = $this->model->get_all_headers();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stockopname/index', $data);
        $this->load->view('templates/footer');
    }

    // Halaman Buat Sesi Baru (Hanya Header)
    public function create()
    {
        $this->form_validation->set_rules('opname_date', 'Tanggal Opname', 'required');
        $this->form_validation->set_rules('item_type', 'Tipe Item', 'required');
        $this->form_validation->set_rules('notes', 'Catatan', 'required');

        if ($this->form_validation->run() == FALSE) {
            $data['title'] = 'Buat Sesi Stock Opname Baru';
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('stockopname/create', $data);
            $this->load->view('templates/footer');
        } else {
            $opname_date = $this->input->post('opname_date');
            $item_type = $this->input->post('item_type');
            
            // Validasi: Cek apakah ada opname aktif untuk item_type dan bulan yang sama
            $existing_active = $this->model->check_active_opname($item_type, $opname_date);
            if ($existing_active) {
                $this->session->set_flashdata('error', 
                    'Sudah ada Stock Opname aktif untuk ' . ucfirst($item_type) . ' pada bulan ' . 
                    date('F Y', strtotime($opname_date)) . ' (Doc: ' . $existing_active->opname_no . '). ' .
                    'Selesaikan atau batalkan opname tersebut terlebih dahulu.');
                redirect('stockopname/create');
                return;
            }
            
            // Info: Berikan warning jika sudah ada yang completed (optional)
            $completed_count = $this->model->count_completed_opname($item_type, $opname_date);
            if ($completed_count > 0) {
                $this->session->set_flashdata('warning', 
                    'Perhatian: Sudah ada ' . $completed_count . ' Stock Opname yang telah selesai untuk ' . 
                    ucfirst($item_type) . ' pada bulan ' . date('F Y', strtotime($opname_date)) . '. ' .
                    'Pastikan opname ulang ini memang diperlukan.');
            }
            
            // Data sesi baru
            $header_data = [
                'opname_no' => $this->model->get_new_doc_number(),
                'opname_date' => $opname_date,
                'item_type' => $item_type,
                'notes' => $this->input->post('notes'),
                'created_by' => $this->session->userdata('user_id'),
                'status' => 'Draft' // Dimulai sebagai Draft
            ];

            // Simpan header, lalu generate list item
            $header_id = $this->model->insert_opname_session($header_data, $item_type);

            if ($header_id) {
                $this->session->set_flashdata('success', 'Sesi Opname berhasil dibuat. Silakan mulai menghitung.');
                redirect('stockopname/counting/' . $header_id);
            } else {
                $this->session->set_flashdata('error', 'Gagal membuat sesi opname.');
                redirect('stockopname/create');
            }
        }
    }

    // Halaman Input Hasil Perhitungan
    public function counting($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();

        // Tidak bisa input jika sudah 'Completed'
        if ($header->status == 'Completed' || $header->status == 'Cancelled') {
             $this->session->set_flashdata('error', 'Sesi opname ini sudah selesai dan tidak bisa diubah.');
             redirect('stockopname/view/' . $id);
        }

        // Load pagination library
        $this->load->library('pagination');
        
        // Pagination config
        $config['base_url'] = base_url('stockopname/counting/' . $id);
        $config['per_page'] = 20;
        $config['uri_segment'] = 4;
        $config['use_page_numbers'] = TRUE;
        $config['page_query_string'] = FALSE;
        $config['num_links'] = 3;
        
        // Bootstrap styling
        $config['full_tag_open'] = '<nav><ul class="pagination justify-content-center">';
        $config['full_tag_close'] = '</ul></nav>';
        $config['first_link'] = 'First';
        $config['first_tag_open'] = '<li class="page-item">';
        $config['first_tag_close'] = '</li>';
        $config['last_link'] = 'Last';
        $config['last_tag_open'] = '<li class="page-item">';
        $config['last_tag_close'] = '</li>';
        $config['next_link'] = 'Next';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['prev_link'] = 'Previous';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link">';
        $config['cur_tag_close'] = '</a></li>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');
        
        // Get total records
        $total_records = $this->model->get_details_count($id);
        $config['total_rows'] = $total_records;
        
        // Initialize pagination
        $this->pagination->initialize($config);
        
        // Get current page
        $page = ($this->uri->segment(4)) ? $this->uri->segment(4) : 1;
        $start = ($page - 1) * $config['per_page'];
        
        // Load session data if exists and merge with current data
        $session_key = 'opname_data_' . $id;
        $session_data = $this->session->userdata($session_key) ?: array();
        
        // Merge session data with database data
        $merged_details = array();
        foreach ($this->model->get_details_by_header_id_paginated($id, $config['per_page'], $start) as $detail) {
            // Apply session data if exists
            if (isset($session_data[$detail->id])) {
                $detail->qty_fisik = $session_data[$detail->id]['qty_fisik'] ?? $detail->qty_fisik;
                $detail->notes = $session_data[$detail->id]['notes'] ?? $detail->notes;
                // Recalculate selisih
                if ($detail->qty_fisik !== NULL && $detail->qty_fisik !== '') {
                    $detail->selisih = (float)$detail->qty_fisik - (float)$detail->qty_sistem;
                } else {
                    $detail->selisih = 0;
                }
            }
            $merged_details[] = $detail;
        }
        
        $data['title'] = 'Input Hasil Opname: ' . $header->opname_no;
        $data['header'] = $header;
        $data['details'] = $merged_details;
        $data['pagination'] = $this->pagination->create_links();
        $data['current_page'] = $page;
        $data['total_pages'] = ceil($total_records / $config['per_page']);
        $data['session_data'] = $session_data;
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stockopname/counting', $data);
        $this->load->view('templates/footer');
    }

    // AJAX method untuk auto-save data saat pindah halaman
    public function auto_save_data($id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $session_key = 'opname_data_' . $id;
        $session_data = $this->session->userdata($session_key) ?: array();
        
        $page_data = $this->input->post('details');
        if ($page_data) {
            // Filter hanya data yang benar-benar diisi
            $filtered_data = array();
            foreach ($page_data as $detail_id => $data) {
                // Hanya simpan jika qty_fisik diisi atau notes ada
                if ((isset($data['qty_fisik']) && $data['qty_fisik'] !== '') || 
                    (isset($data['notes']) && trim($data['notes']) !== '')) {
                    $filtered_data[$detail_id] = $data;
                }
            }
            
            // Merge dengan data session existing
            $session_data = array_merge($session_data, $filtered_data);
            $this->session->set_userdata($session_key, $session_data);
        }
        
        echo json_encode(['success' => true]);
    }

    // Proses Simpan Hasil Perhitungan
    public function save_counts($id)
    {
        $header = $this->model->get_header_by_id($id);
        if ($header->status != 'Draft' && $header->status != 'Counting') {
             $this->session->set_flashdata('error', 'Sesi opname ini sudah selesai.');
             redirect('stockopname/view/' . $id);
        }

        // Auto-save data from current page first
        $current_page_data = $this->input->post('details') ?: array();
        if (!empty($current_page_data)) {
            $session_key = 'opname_data_' . $id;
            $session_data = $this->session->userdata($session_key) ?: array();
            
            // Filter dan simpan data yang ada isinya
            foreach ($current_page_data as $detail_id => $data) {
                if ((isset($data['qty_fisik']) && $data['qty_fisik'] !== '') || 
                    (isset($data['notes']) && trim($data['notes']) !== '')) {
                    $session_data[$detail_id] = $data;
                }
            }
            $this->session->set_userdata($session_key, $session_data);
        }

        // Ambil semua data dari session untuk diproses
        $session_key = 'opname_data_' . $id;
        $all_session_data = $this->session->userdata($session_key) ?: array();
        
        // Proses semua data yang ada di session
        if (!empty($all_session_data)) {
            $this->model->update_counts($id, $all_session_data);
        }

        // Update status ke 'Counting'
        $this->model->update_header_status($id, 'Counting');
        
        // Clear session data setelah berhasil disimpan
        $this->session->unset_userdata($session_key);

        $this->session->set_flashdata('success', 'Semua hasil perhitungan berhasil disimpan.');
        redirect('stockopname/counting/' . $id);
    }

    // Halaman View (Read-only)
    public function view($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = 'View Stock Opname: ' . $data['header']->opname_no;
        $data['details'] = $this->model->get_details_by_header_id($id);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stockopname/view', $data);
        $this->load->view('templates/footer');
    }

    // Proses Konfirmasi Sesi & Buat Adjustment Otomatis
    // Proses Konfirmasi Sesi & Buat Adjustment Otomatis
    public function confirm($id)
    {
        $header = $this->model->get_header_by_id($id);
        if ($header->status != 'Counting') {
             $this->session->set_flashdata('error', 'Hanya sesi berstatus "Counting" yang bisa dikonfirmasi.');
             redirect('stockopname/view/' . $id);
        }

        $user_id = $this->session->userdata('user_id');
        $result = $this->model->process_opname_adjustment($id, $user_id);

        if ($result['status'] == TRUE) {
            // INI YANG DIPERBAIKI: $this_session -> $this->session
            $this->session->set_flashdata('success', $result['message']);
            redirect('stockopname/view/' . $id);
        } else {
            $this->session->set_flashdata('error', 'Gagal konfirmasi: ' . $result['message']);
            redirect('stockopname/counting/' . $id);
        }
    }

    // Hapus sesi (hanya Draft)
    public function delete($id)
    {
        $result = $this->model->delete_draft_opname($id);
        if ($result) {
            $this->session->set_flashdata('success', 'Sesi Opname Draft berhasil dihapus.');
            redirect('stockopname');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus. Hanya status Draft yang bisa dihapus.');
            redirect('stockopname/view/' . $id);
        }
    }
}