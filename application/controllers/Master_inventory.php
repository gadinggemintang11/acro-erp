<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_inventory extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Inventory_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
        $this->load->helper(['form', 'url']);
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_inventory', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'         => 'Master Inventaris (ATK, Furniture, Tools)',
            'inventaris'    => $this->Inventory_model->get_all(),
            // ✅ DINAMIS: Mengambil data kategori & kondisi dari common code
            'kategori_list' => $this->Commoncode_model->get_by_group('INVENTORY_CATEGORY'),
            'kondisi_list'  => $this->Commoncode_model->get_by_group('INVENTORY_CONDITION') // Pastikan grup ini ada
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('inventory/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        // ✅ KEAMANAN: Check akses add
        if (!check_access('master_inventory', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $data = [
            'kode_barang' => $this->Inventory_model->generate_kode($this->input->post('kategori')),
            'nama_barang' => $this->input->post('nama_barang'),
            'kategori'    => $this->input->post('kategori'),
            'merk'        => $this->input->post('merk'),
            'lokasi'      => $this->input->post('lokasi'),
            'kondisi'     => $this->input->post('kondisi'),
            'status'      => 'Active', // Default status saat tambah
        ];

        if ($this->Inventory_model->insert($data)) {
            $this->session->set_flashdata('success', 'Data inventaris berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_inventory');
    }

    public function update($id) {
        // ✅ KEAMANAN: Check akses edit
        if (!check_access('master_inventory', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id)) show_404(); // ✅ Pengecekan ID

        $data = [
            'nama_barang' => $this->input->post('nama_barang'),
            'kategori'    => $this->input->post('kategori'),
            'merk'        => $this->input->post('merk'),
            'lokasi'      => $this->input->post('lokasi'),
            'kondisi'     => $this->input->post('kondisi'),
            'status'      => $this->input->post('status'),
        ];

        if ($this->Inventory_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data inventaris berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_inventory');
    }

    public function delete($id) {
        // ✅ KEAMANAN: Check akses delete
        if (!check_access('master_inventory', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id)) show_404(); // ✅ Pengecekan ID

        if ($this->Inventory_model->delete($id)) {
            // ✅ DIUBAH: Pesan disesuaikan dengan soft delete
            $this->session->set_flashdata('success', 'Data inventaris berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_inventory');
    }
}