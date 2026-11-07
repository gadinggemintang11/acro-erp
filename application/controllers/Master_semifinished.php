<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_semifinished extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Semifinished_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
        $this->load->helper(['form', 'url', 'export']);
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_semifinished', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'         => lang_line('title_mst_sf'),
            'semifinished'  => $this->Semifinished_model->get_all(),
            'satuan_list'   => $this->Commoncode_model->get_by_group('SATUAN'),
            'kategori_list' => $this->Commoncode_model->get_by_group('SEMIFINISHED_CATEGORY'),
            // ✅ DINAMIS: Mengambil data asal dari common code
            'asal_list'     => $this->Commoncode_model->get_by_group('ITEM_ORIGIN')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('semifinished/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        // ✅ KEAMANAN: Check akses add
        if (!check_access('master_semifinished', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            show_error('Invalid request method', 405);
            return;
        }

        $kode_barang = $this->input->post('kode_barang');

        // Jika kode_barang kosong, generate otomatis
        if (empty($kode_barang)) {
            $kode_barang = $this->Semifinished_model->generate_kode($this->input->post('kategori'), $this->input->post('asal'));
        }

        $data = [
            'kode_barang' => $kode_barang,
            'nama_barang' => $this->input->post('nama_barang'),
            'kategori'    => $this->input->post('kategori'),
            'asal'        => $this->input->post('asal'),
            'satuan'      => $this->input->post('satuan'),
            'status'      => 'active',
        ];

        if($this->Semifinished_model->insert($data)) {
            $this->session->set_flashdata('success', 'Data semifinished berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_semifinished');
    }

    public function update($id) {
        // ✅ KEAMANAN: Check akses edit
        if (!check_access('master_semifinished', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id)) show_404();

        $data = [
            'nama_barang' => $this->input->post('nama_barang', true),
            'kategori'    => $this->input->post('kategori', true),
            'asal'        => $this->input->post('asal', true),
            'satuan'      => $this->input->post('satuan', true),
            'status'      => $this->input->post('status', true),
        ];

        if ($this->Semifinished_model->update($id, $data)) {
            $this->session->set_flashdata('success', 'Data berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_semifinished');
    }

    public function delete($id) {
        // ✅ KEAMANAN: Check akses delete
        if (!check_access('master_semifinished', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id)) show_404();

        if ($this->Semifinished_model->delete($id)) {
            // ✅ DIUBAH: Pesan disesuaikan dengan soft delete
            $this->session->set_flashdata('success', 'Data berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_semifinished');
    }

    // ✅ EXPORT EXCEL
    public function export_excel() {
        if (!check_access('master_semifinished', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $semifinished = $this->Semifinished_model->get_all();
        
        $headers = ['Kode', 'Nama Barang', 'Kategori', 'Asal', 'Satuan', 'Status'];
        $data = [];

        foreach ($semifinished as $s) {
            $data[] = [
                $s->kode_barang,
                $s->nama_barang,
                $s->kategori,
                $s->asal,
                $s->satuan,
                ucfirst($s->status)
            ];
        }

        $filename = 'Master_Semifinished_' . date('Ymd_His');
        export_to_excel($filename, 'Semifinished', $headers, $data);
    }

    // ✅ EXPORT PDF
    public function export_pdf() {
        if (!check_access('master_semifinished', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $semifinished = $this->Semifinished_model->get_all();
        
        $headers = ['Kode', 'Nama Barang', 'Kategori', 'Asal', 'Satuan', 'Status'];
        $data = [];

        foreach ($semifinished as $s) {
            $data[] = [
                $s->kode_barang,
                $s->nama_barang,
                $s->kategori,
                $s->asal,
                $s->satuan,
                ucfirst($s->status)
            ];
        }

        $filename = 'Master_Semifinished_' . date('Ymd_His');
        $title = 'Master Semifinished Goods - ' . date('d-m-Y H:i:s');
        export_to_pdf($filename, $title, $headers, $data);
    }
}