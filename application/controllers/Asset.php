<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Asset extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Asset_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data = $this->data; 
        $data['title'] = 'Master Aset';
        $data['content'] = 'asset/index';
        $data['assets'] = $this->Asset_model->getAll();
        $this->load->view('layouts/main_template', $data);
    }

    public function add()
    {
        $data = $this->data; 
        $data['title'] = 'Tambah Aset Baru';
        $data['content'] = 'asset/add_form';
        $this->load->view('layouts/main_template', $data);
    }

    public function save() {
        $this->Asset_model->save();
        add_log("Menambah aset baru: " . $this->input->post('asset_name'));
        $this->session->set_flashdata('success', 'Data aset berhasil ditambahkan.');
        redirect('asset');
    }

    public function edit($id = null)
    {
        if (!$id) redirect('asset');
        $data = $this->data; 
        $data['title'] = 'Edit Aset';
        $data['content'] = 'asset/edit_form';
        $data['asset'] = $this->Asset_model->getById($id);

        if (!$data['asset']) show_404();
        
        $this->load->view('layouts/main_template', $data);
    }

    public function update() {
        $this->Asset_model->update();
        add_log("Mengubah data aset: " . $this->input->post('asset_name'));
        $this->session->set_flashdata('success', 'Data aset berhasil diperbarui.');
        redirect('asset');
    }

    public function delete($id = null) {
        if (!$id) redirect('asset');
        $asset = $this->Asset_model->getById($id); // Ambil data dulu
        if ($this->Asset_model->delete($id)) {
            add_log("Menghapus aset: " . $asset['asset_name']);
            $this->session->set_flashdata('success', 'Data aset berhasil dihapus.');
        }
        redirect('asset');
    }
}