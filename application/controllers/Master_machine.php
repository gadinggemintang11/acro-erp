<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_machine extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Machine_model');
        $this->load->model('Commoncode_model');
        $this->load->helper(['form', 'url', 'export']);
    }

    public function index() {
        if (!check_access('master_machine', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'         => lang_line('title_mch_index'),
            'machines'      => $this->Machine_model->get_all(),
            'kategori_list' => $this->Commoncode_model->get_by_group('MACHINE_CATEGORY'),
            'status_list'   => $this->Commoncode_model->get_by_group('MACHINE_STATUS'),
            'satuan_list'   => $this->Commoncode_model->get_by_group('SATUAN') // <-- TAMBAHKAN INI
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('machine/index', $data); // Asumsi form ada di file index
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('master_machine', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }
        
        $kode_mesin = $this->input->post('kode_mesin');
        $nama_mesin = $this->input->post('nama_mesin');
        
        if (empty($kode_mesin) || empty($nama_mesin)) {
            $this->session->set_flashdata('error', 'Kode mesin dan nama mesin wajib diisi.');
            return redirect('master_machine');
        }
        
        $data = [
            'kode_mesin' => $kode_mesin,
            'nama_mesin' => $nama_mesin,
            'kategori'   => $this->input->post('kategori'),
            'merk'       => $this->input->post('merk'),
            'tipe'       => $this->input->post('tipe'),
            'tahun_pengadaan' => $this->input->post('tahun_pengadaan'),
            'bulan_pengadaan' => $this->input->post('bulan_pengadaan'),
            'pic_departemen' => $this->input->post('pic_departemen'),
            'satuan'     => $this->input->post('satuan'),
            'status'     => 'Active', 
        ];

        if ($this->Machine_model->insert($data)) {
            $this->session->set_flashdata('success', 'Data mesin/peralatan berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan data!');
        }
        redirect('master_machine');
    }

    public function update($id) {
        if (!check_access('master_machine', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        if (empty($id)) show_404();

        $data = [
            'kode_mesin' => $this->input->post('kode_mesin'),
            'nama_mesin' => $this->input->post('nama_mesin'),
            'kategori'   => $this->input->post('kategori'),
            'merk'       => $this->input->post('merk'),
            'tipe'       => $this->input->post('tipe'),
            'tahun_pengadaan' => $this->input->post('tahun_pengadaan'),
            'bulan_pengadaan' => $this->input->post('bulan_pengadaan'),
            'pic_departemen' => $this->input->post('pic_departemen'),
            'satuan'     => $this->input->post('satuan'),
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
        if (!check_access('master_machine', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        if (empty($id)) show_404();

        if ($this->Machine_model->delete($id)) {
            $this->session->set_flashdata('success', 'Data berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_machine');
    }

    // ✅ EXPORT EXCEL
    public function export_excel() {
        if (!check_access('master_machine', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $machines = $this->Machine_model->get_all();
        
        $headers = ['Kode', 'Nama Mesin', 'Kategori', 'Merk', 'Tipe', 'Tahun Pengadaan', 'Bulan Pengadaan', 'PIC Departemen', 'Satuan', 'Status'];
        $data = [];

        foreach ($machines as $m) {
            $data[] = [
                $m->kode_mesin,
                $m->nama_mesin,
                $m->kategori_desc ?? $m->kategori,
                $m->merk ?? '',
                $m->tipe ?? '',
                $m->tahun_pengadaan ?? '',
                $m->bulan_pengadaan ?? '',
                $m->pic_departemen ?? '',
                $m->satuan_desc ?? $m->satuan,
                ucfirst($m->status)
            ];
        }

        $filename = 'Master_Machine_' . date('Ymd_His');
        export_to_excel($filename, 'Machines', $headers, $data);
    }

    // ✅ EXPORT PDF
    public function export_pdf() {
        if (!check_access('master_machine', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
        }

        $machines = $this->Machine_model->get_all();
        
        $headers = ['Kode', 'Nama Mesin', 'Kategori', 'Merk', 'Tipe', 'Tahun Pengadaan', 'Bulan Pengadaan', 'PIC Departemen', 'Satuan', 'Status'];
        $data = [];

        foreach ($machines as $m) {
            $data[] = [
                $m->kode_mesin,
                $m->nama_mesin,
                $m->kategori_desc ?? $m->kategori,
                $m->merk ?? '',
                $m->tipe ?? '',
                $m->tahun_pengadaan ?? '',
                $m->bulan_pengadaan ?? '',
                $m->pic_departemen ?? '',
                $m->satuan_desc ?? $m->satuan,
                ucfirst($m->status)
            ];
        }

        $filename = 'Master_Machine_' . date('Ymd_His');
        $title = 'Master Mesin & Peralatan - ' . date('d-m-Y H:i:s');
        export_to_pdf($filename, $title, $headers, $data);
    }
}