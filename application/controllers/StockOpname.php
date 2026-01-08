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
            // Data sesi baru
            $header_data = [
                'opname_no' => $this->model->get_new_doc_number(),
                'opname_date' => $this->input->post('opname_date'),
                'item_type' => $this->input->post('item_type'),
                'notes' => $this->input->post('notes'),
                'created_by' => $this->session->userdata('user_id'),
                'status' => 'Draft' // Dimulai sebagai Draft
            ];
            $item_type = $this->input->post('item_type');

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

        $data['title'] = 'Input Hasil Opname: ' . $header->opname_no;
        $data['header'] = $header;
        $data['details'] = $this->model->get_details_by_header_id($id);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stockopname/counting', $data);
        $this->load->view('templates/footer');
    }

    // Proses Simpan Hasil Perhitungan
    public function save_counts($id)
    {
        $header = $this->model->get_header_by_id($id);
        if ($header->status != 'Draft' && $header->status != 'Counting') {
             $this->session->set_flashdata('error', 'Sesi opname ini sudah selesai.');
             redirect('stockopname/view/' . $id);
        }

        $details_data = $this->input->post('details');
        $this->model->update_counts($id, $details_data);

        // Update status ke 'Counting'
        $this->model->update_header_status($id, 'Counting');

        $this->session->set_flashdata('success', 'Hasil perhitungan berhasil disimpan.');
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