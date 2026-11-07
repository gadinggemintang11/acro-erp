<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_inventory extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Inventory_model');
        // Commoncode_model sudah di-load di kode Anda sebelumnya
        $this->load->model('Commoncode_model'); 
        $this->load->helper(['form', 'url']);
        $this->load->library('session'); // Load session
        $this->load->library('form_validation'); // Load validation
    }

    public function index() {
        if (!check_access('master_inventory', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'         => 'Master HRGA (ATK, Furniture, Tools)',
            'inventaris'    => $this->Inventory_model->get_all(),
            'kategori_list' => $this->Inventory_model->get_kategori_list(), // Ambil dari Inventory_model
            'kondisi_list'  => $this->Commoncode_model->get_by_group('INVENTORY_CONDITION'),
            // ✅ BARU: Ambil daftar satuan
            'satuan_list'   => $this->Inventory_model->get_satuan_list() // Ambil dari Inventory_model
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('inventory/index', $data); // Pastikan nama view ini benar
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('master_inventory', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        // --- Validasi Input ---
        $this->form_validation->set_rules('nama_barang', 'Nama Barang', 'required|trim');
        $this->form_validation->set_rules('kategori', 'Kategori', 'required');
        $this->form_validation->set_rules('satuan', 'Satuan', 'required'); // ✅ Validasi Satuan
        // Tambahkan validasi lain jika perlu (lokasi, kondisi)

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('master_inventory'); // Kembali ke index jika validasi gagal
            return;
        }
        // --- Akhir Validasi ---

        $kategori_code = $this->input->post('kategori'); // Ambil kode kategori

        $data = [
            // Panggil generate_kode dengan KODE kategori
            'kode_barang' => $this->Inventory_model->generate_kode($kategori_code), 
            'nama_barang' => $this->input->post('nama_barang'),
            'kategori'    => $kategori_code, // Simpan KODE kategori
            'satuan'      => $this->input->post('satuan'), // ✅ Simpan KODE satuan
            'merk'        => $this->input->post('merk'),
            'lokasi'      => $this->input->post('lokasi'),
            'kondisi'     => $this->input->post('kondisi'),
            'status'      => 'Active', 
        ];

        if ($this->Inventory_model->insert($data)) {
            $this->session->set_flashdata('success', 'Data inventaris berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_inventory');
    }

    public function update($id) {
        if (!check_access('master_inventory', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404(); // Validasi ID

        // --- Validasi Input ---
        $this->form_validation->set_rules('nama_barang', 'Nama Barang', 'required|trim');
        $this->form_validation->set_rules('kategori', 'Kategori', 'required');
        $this->form_validation->set_rules('satuan', 'Satuan', 'required'); // ✅ Validasi Satuan
        $this->form_validation->set_rules('status', 'Status', 'required');
        // Tambahkan validasi lain jika perlu

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('master_inventory'); 
            return;
        }
        // --- Akhir Validasi ---

        $data = [
            'nama_barang' => $this->input->post('nama_barang'),
            'kategori'    => $this->input->post('kategori'), // Simpan KODE kategori
            'satuan'      => $this->input->post('satuan'), // ✅ Simpan KODE satuan
            'merk'        => $this->input->post('merk'),
            'lokasi'      => $this->input->post('lokasi'),
            'kondisi'     => $this->input->post('kondisi'),
            'status'      => $this->input->post('status'), // Status bisa diubah
        ];

        // Kode barang tidak diupdate
        // unset($data['kode_barang']); 

        if ($this->Inventory_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data inventaris berhasil diperbarui!');
        } else {
             // Beri pesan warning jika tidak ada perubahan
            if ($this->db->affected_rows() == 0) {
                 $this->session->set_flashdata('warning', 'Tidak ada perubahan data.');
            } else {
                 $this->session->set_flashdata('error', 'Gagal memperbarui data!');
            }
        }
        redirect('master_inventory');
    }

    public function delete($id) {
        if (!check_access('master_inventory', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id) || !is_numeric($id)) show_404(); 

        if ($this->Inventory_model->delete($id)) {
            $this->session->set_flashdata('success', 'Data inventaris berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_inventory');
    }
}