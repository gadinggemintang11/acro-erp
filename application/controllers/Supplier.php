<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Supplier extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Supplier_model');
        $this->load->helper('url');
        // Session sudah di-autoload, jadi tidak perlu di-load lagi
    }

    public function index()
    {
        $data = $this->data; 
        $data['title'] = 'Master Supplier';
        $data['content'] = 'supplier/index';
        $data['suppliers'] = $this->Supplier_model->getAll();
        $this->load->view('layouts/main_template', $data);
    }

    public function add()
    {
        $data = $this->data; 
        $data['title'] = 'Tambah Supplier Baru';
        $data['content'] = 'supplier/add_form';
        $this->load->view('layouts/main_template', $data);
    }

    public function save() {
        $this->Supplier_model->save();
        add_log("Menambah supplier baru: " . $this->input->post('supplier_name'));
        $this->session->set_flashdata('success', 'Data supplier berhasil ditambahkan.');
        redirect('supplier');
    }

    public function edit($id = null)
    {

        if (!$id) redirect('supplier');
        $data = $this->data; 
        $data['title'] = 'Edit Supplier';
        $data['content'] = 'supplier/edit_form';
        $data['supplier'] = $this->Supplier_model->getById($id);

        if (!$data['supplier']) show_404();
        
        $this->load->view('layouts/main_template', $data);
    }

    public function update() {
        $this->Supplier_model->update();
        add_log("Mengubah data supplier: " . $this->input->post('supplier_name'));
        $this->session->set_flashdata('success', 'Data supplier berhasil diperbarui.');
        redirect('supplier');
    }

    public function delete($id = null) {
        if (!$id) redirect('supplier');
        $supplier = $this->Supplier_model->getById($id); // Ambil data dulu
        if ($this->Supplier_model->delete($id)) {
            add_log("Menghapus supplier: " . $supplier['supplier_name']);
            $this->session->set_flashdata('success', 'Data supplier berhasil dihapus.');
        }
        redirect('supplier');
    }
}