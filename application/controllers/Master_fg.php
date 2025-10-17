<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_fg extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Fg_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
        $this->load->helper(['form', 'url']);
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_fg', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'         => 'Master Finished Goods',
            'fg'            => $this->Fg_model->get_all(),
            'satuan_list'   => $this->Commoncode_model->get_by_group('SATUAN'),
            'kategori_list' => $this->Commoncode_model->get_by_group('FINISHED_CATEGORY'),
            // ✅ DINAMIS: Mengambil data asal dari common code
            'asal_list'     => $this->Commoncode_model->get_by_group('ITEM_ORIGIN')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('finished_goods/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        // ✅ KEAMANAN: Check akses add
        if (!check_access('master_fg', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            show_error('Invalid request method', 405);
            return;
        }

        $data = [
            'kode_barang' => $this->Fg_model->generate_kode($this->input->post('kategori'), $this->input->post('asal')),
            'nama_barang' => $this->input->post('nama_barang'),
            'kategori'    => $this->input->post('kategori'),
            'asal'        => $this->input->post('asal'),
            'satuan'      => $this->input->post('satuan'),
            'status'      => 'active',
        ];

        if($this->Fg_model->insert($data)) {
            $this->session->set_flashdata('success', 'Data finished goods berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_fg');
    }

    public function update($id) {
        // ✅ KEAMANAN: Check akses edit
        if (!check_access('master_fg', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id)) show_404(); // ✅ Pengecekan ID

        $data = [
            'nama_barang' => $this->input->post('nama_barang'),
            'kategori'    => $this->input->post('kategori'),
            'asal'        => $this->input->post('asal'),
            'satuan'      => $this->input->post('satuan'),
            'status'      => $this->input->post('status'),
        ];

        if ($this->Fg_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_fg');
    }

    public function delete($id) {
        // ✅ KEAMANAN: Check akses delete
        if (!check_access('master_fg', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id)) show_404(); // ✅ Pengecekan ID

        if ($this->Fg_model->delete($id)) {
            // ✅ DIUBAH: Pesan disesuaikan dengan soft delete
            $this->session->set_flashdata('success', 'Data berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_fg');
    }
}