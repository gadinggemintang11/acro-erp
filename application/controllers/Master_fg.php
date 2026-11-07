<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_fg extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Fg_model');
        // ✅ Load Commoncode_model untuk data dinamis
        $this->load->model('Commoncode_model');
        $this->load->helper(['form', 'url', 'export']);
    }

    public function index() {
        // ✅ KEAMANAN: Check akses view
        if (!check_access('master_fg', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'         => lang_line('title_mst_fg'),
            'fg'            => $this->Fg_model->get_all(),
            'satuan_list'   => $this->Commoncode_model->get_by_group('SATUAN')
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

        $kode_barang = $this->input->post('kode_barang');
        $nama_barang = $this->input->post('nama_barang');
        $satuan = $this->input->post('satuan');
        $konversi_satuan = $this->input->post('konversi_satuan');

        if (empty($kode_barang) || empty($nama_barang)) {
            $this->session->set_flashdata('error', 'Kode barang dan nama barang (model) wajib diisi.');
            return redirect('master_fg');
        }

        $data = [
            'kode_barang' => $kode_barang,
            'nama_barang' => $nama_barang,
            'satuan'      => $satuan,
            'konversi_satuan' => $konversi_satuan,
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
            'kode_barang' => $this->input->post('kode_barang'),
            'nama_barang' => $this->input->post('nama_barang'),
            'satuan'      => $this->input->post('satuan'),
            'konversi_satuan' => $this->input->post('konversi_satuan'),
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

    public function export_excel() {
        if (!check_access('master_fg', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $data = $this->Fg_model->get_all();
        $headers = ['Kode', 'Model', 'Satuan', 'Konversi Satuan', 'Status'];
        
        $export_data = [];
        foreach ($data as $row) {
            $export_data[] = [
                $row->kode_barang,
                $row->nama_barang,
                $row->satuan,
                $row->konversi_satuan,
                $row->is_active ? 'Aktif' : 'Tidak Aktif'
            ];
        }

        $filename = 'Master_FG_' . date('YmdHis');
        export_to_excel($headers, $export_data, $filename);
    }

    public function export_pdf() {
        if (!check_access('master_fg', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $data = $this->Fg_model->get_all();
        $headers = ['Kode', 'Model', 'Satuan', 'Konversi Satuan', 'Status'];
        
        $export_data = [];
        foreach ($data as $row) {
            $export_data[] = [
                $row->kode_barang,
                $row->nama_barang,
                $row->satuan,
                $row->konversi_satuan,
                $row->is_active ? 'Aktif' : 'Tidak Aktif'
            ];
        }

        $filename = 'Master_FG_' . date('YmdHis');
        $title = 'Master Finished Goods - ' . date('Y-m-d H:i:s');
        export_to_pdf($headers, $export_data, $filename, $title);
    }
}