<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Customer_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data = $this->data; 
        $data['title'] = 'Master Pelanggan';
        $data['content'] = 'customer/index';
        $data['customers'] = $this->Customer_model->getAll();
        $this->load->view('layouts/main_template', $data);
    }

    public function add()
    {
        $data = $this->data; 
        $data['title'] = 'Tambah Pelanggan Baru';
        $data['content'] = 'customer/add_form';
        $this->load->view('layouts/main_template', $data);
    }

    public function save() {
        $this->Customer_model->save();
        add_log("Menambah pelanggan baru: " . $this->input->post('customer_name'));
        $this->session->set_flashdata('success', 'Data pelanggan berhasil ditambahkan.');
        redirect('customer');
    }

    public function edit($id = null)
    {
        if (!$id) redirect('customer');
        $data = $this->data;    
        $data['title'] = 'Edit Pelanggan';
        $data['content'] = 'customer/edit_form';
        $data['customer'] = $this->Customer_model->getById($id);

        if (!$data['customer']) show_404();
        
        $this->load->view('layouts/main_template', $data);
    }

    public function update() {
        $this->Customer_model->update();
        add_log("Mengubah data pelanggan: " . $this->input->post('customer_name'));
        $this->session->set_flashdata('success', 'Data pelanggan berhasil diperbarui.');
        redirect('customer');
    }

    public function delete($id = null) {
        if (!$id) redirect('customer');
        $customer = $this->Customer_model->getById($id); // Ambil data dulu
        if ($this->Customer_model->delete($id)) {
            add_log("Menghapus pelanggan: " . $customer['customer_name']);
            $this->session->set_flashdata('success', 'Data pelanggan berhasil dihapus.');
        }
        redirect('customer');
    }
}