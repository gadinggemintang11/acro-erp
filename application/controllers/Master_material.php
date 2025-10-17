<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_material extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Material_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_material', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data['title'] = 'Master Material';
        $data['materials'] = $this->Material_model->get_all();
        // ✅ DINAMIS: Menggunakan Commoncode_model yang lebih umum
        $data['satuan_list'] = $this->Commoncode_model->get_by_group('SATUAN');
        $data['kategori_list'] = $this->Commoncode_model->get_by_group('MATERIAL_CATEGORY');
        $data['asal_list'] = $this->Commoncode_model->get_by_group('ITEM_ORIGIN'); // Asumsi 'Lokal'/'Impor'

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        // ✅ KEAMANAN: Check akses add
        if (!check_access('master_material', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $kategori = $this->input->post('kategori');
        $asal = $this->input->post('asal');

        $data = [
            'kode_material' => $this->Material_model->generate_kode($kategori, $asal),
            'nama_material' => $this->input->post('nama_material'),
            'kategori'      => $kategori,
            'asal'          => $asal,
            'satuan'        => $this->input->post('satuan'),
            'status'        => 'active'
        ];

        if ($this->Material_model->insert($data)) {
            $this->session->set_flashdata('success', 'Material berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan material!');
        }
        redirect('master_material');
    }

    public function update($id) {
        // ✅ KEAMANAN: Check akses edit
        if (!check_access('master_material', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }

        $data = [
            'nama_material' => $this->input->post('nama_material'),
            'kategori'      => $this->input->post('kategori'),
            'asal'          => $this->input->post('asal'),
            'satuan'        => $this->input->post('satuan'),
            'status'        => $this->input->post('status')
        ];

        if ($this->Material_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data material berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_material');
    }

    public function delete($id) {
        // ✅ KEAMANAN: Check akses delete
        if (!check_access('master_material', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }

        if ($this->Material_model->delete($id)) {
            // ✅ DIUBAH: Pesan disesuaikan dengan soft delete
            $this->session->set_flashdata('success', 'Data material berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_material');
    }
}