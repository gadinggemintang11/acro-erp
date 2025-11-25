<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_scrap extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        // ✅ FIX: $this->load
        $this->load->model(['Scrap_model', 'Commoncode_model']);
        $this->load->helper('url');
    }

    public function index() {
        if (!check_access('master_scrap', 'view')) { show_error('Tidak ada izin.', 403); }
        
        // ✅ FIX: $this->input
        $filters = ['search' => $this->input->get('search')];
        $data = [
            'title'   => 'Master Scrap',
            'scraps'  => $this->Scrap_model->get_all($filters),
            'satuan_list' => $this->Commoncode_model->get_by_group('SATUAN'),
            'filters' => $filters
        ];
        
        // ✅ FIX: $this->load (Ini baris 24 yang error)
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('master_scrap/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('master_scrap', 'add')) { show_error('Tidak ada izin.', 403); }
        $data = [
            'scrap_code' => $this->Scrap_model->generate_scrap_code(),
            'scrap_name' => $this->input->post('scrap_name'), // ✅ FIX
            'unit'       => $this->input->post('unit'), // ✅ FIX
            'status'     => 'active'
        ];
        if ($this->Scrap_model->insert($data)) { $this->session->set_flashdata('success', 'Data scrap ditambahkan.'); } // ✅ FIX
        else { $this->session->set_flashdata('error', 'Gagal menambah data.'); } // ✅ FIX
        redirect('master_scrap');
    }

    public function update($id) {
        if (!check_access('master_scrap', 'edit')) { show_error('Tidak ada izin.', 403); }
        $data = [
            'scrap_name' => $this->input->post('scrap_name'), // ✅ FIX
            'unit'       => $this->input->post('unit'), // ✅ FIX
            'status'     => $this->input->post('status') // ✅ FIX
        ];
        if ($this->Scrap_model->update($id, $data)) { $this->session->set_flashdata('success', 'Data scrap diperbarui.'); } // ✅ FIX
        else { $this->session->set_flashdata('error', 'Gagal memperbarui data.'); } // ✅ FIX
        redirect('master_scrap');
    }

    public function delete($id) {
        if (!check_access('master_scrap', 'delete')) { show_error('Tidak ada izin.', 403); }
        if ($this->Scrap_model->delete($id)) { $this->session->set_flashdata('success', 'Data scrap dinonaktifkan.'); } // ✅ FIX
        else { $this->session->set_flashdata('error', 'Gagal menonaktifkan data.'); } // ✅ FIX
        redirect('master_scrap');
    }
}