<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_supplier extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Supplier_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
        $this->load->helper(['form', 'url']);
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_supplier', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'     => 'Master Supplier',
            'suppliers' => $this->Supplier_model->get_all(),
            // ✅ DINAMIS: Mengambil data jenis supplier dari common code
            'jenis_list' => $this->Commoncode_model->get_by_group('SUPPLIER_TYPE') // Pastikan grup ini ada
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('supplier/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        // ✅ KEAMANAN: Check akses add
        if (!check_access('master_supplier', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $data = [
            'kode_supplier' => $this->Supplier_model->generate_kode($this->input->post('jenis')),
            'nama_supplier' => $this->input->post('nama_supplier'),
            'jenis'         => $this->input->post('jenis'),
            'npwp'          => $this->input->post('npwp'),
            'alamat'        => $this->input->post('alamat'),
            'telepon'       => $this->input->post('telepon'),
            'status'        => 'active',
        ];

        if ($this->Supplier_model->insert($data)) {
            $this->session->set_flashdata('success', 'Data supplier berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_supplier');
    }

    public function update($id) {
        // ✅ KEAMANAN: Check akses edit
        if (!check_access('master_supplier', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }

        $data = [
            'nama_supplier' => $this->input->post('nama_supplier'),
            'jenis'         => $this->input->post('jenis'),
            'npwp'          => $this->input->post('npwp'),
            'alamat'        => $this->input->post('alamat'),
            'telepon'       => $this->input->post('telepon'),
            'status'        => $this->input->post('status'),
        ];

        if ($this->Supplier_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data supplier berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_supplier');
    }

    public function delete($id) {
        // ✅ KEAMANAN: Check akses delete
        if (!check_access('master_supplier', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }

        if ($this->Supplier_model->delete($id)) {
            // ✅ DIUBAH: Pesan disesuaikan dengan soft delete
            $this->session->set_flashdata('success', 'Data supplier berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_supplier');
    }
}