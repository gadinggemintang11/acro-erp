<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_machine extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Machine_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
        $this->load->helper(['form', 'url']);
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_machine', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'         => 'Master Mesin & Peralatan',
            'machines'      => $this->Machine_model->get_all(),
            // ✅ DINAMIS: Mengambil data kategori & status dari common code
            'kategori_list' => $this->Commoncode_model->get_by_group('MACHINE_CATEGORY'),
            'status_list'   => $this->Commoncode_model->get_by_group('MACHINE_STATUS') // Pastikan grup ini ada
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('machine/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        // ✅ KEAMANAN: Check akses add
        if (!check_access('master_machine', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }
        
        $data = [
            'kode_mesin' => $this->Machine_model->generate_kode($this->input->post('kategori')),
            'nama_mesin' => $this->input->post('nama_mesin'),
            'kategori'   => $this->input->post('kategori'),
            'merk'       => $this->input->post('merk'),
            'tahun'      => $this->input->post('tahun'),
            'lokasi'     => $this->input->post('lokasi'),
            'status'     => 'Active', // Default status saat tambah
        ];

        if ($this->Machine_model->insert($data)) {
            $this->session->set_flashdata('success', 'Data mesin/peralatan berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_machine');
    }

    public function update($id) {
        // ✅ KEAMANAN: Check akses edit
        if (!check_access('master_machine', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id)) show_404(); // ✅ Pengecekan ID

        $data = [
            'nama_mesin' => $this->input->post('nama_mesin'),
            'kategori'   => $this->input->post('kategori'),
            'merk'       => $this->input->post('merk'),
            'tahun'      => $this->input->post('tahun'),
            'lokasi'     => $this->input->post('lokasi'),
            'status'     => $this->input->post('status'),
        ];

        if ($this->Machine_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_machine');
    }

    public function delete($id) {
        // ✅ KEAMANAN: Check akses delete
        if (!check_access('master_machine', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id)) show_404(); // ✅ Pengecekan ID

        if ($this->Machine_model->delete($id)) {
            // ✅ DIUBAH: Pesan disesuaikan dengan soft delete
            $this->session->set_flashdata('success', 'Data berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_machine');
    }
}