<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_jasa extends MY_Controller {

    protected $module_name = 'Master Jasa';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Jasa_model');
        $this->load->model('Commoncode_model');
    }

    public function index() {
        if (!check_access('master_jasa', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $keyword = $this->input->get('q');
        $per_page = 20;
        $page = (int) $this->input->get('page');
        $offset = $page > 0 ? ($page - 1) * $per_page : 0;

        $total_rows = $this->Jasa_model->count_filtered($keyword);
        $jasas = $this->Jasa_model->get_paginated($per_page, $offset, $keyword);

        $this->load->library('pagination');
        $config['base_url'] = site_url('master_jasa');
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $per_page;
        $config['page_query_string'] = TRUE;
        $config['query_string_segment'] = 'page';
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE;

        $config['full_tag_open'] = '<ul class="pagination justify-content-end">';
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li class="page-item">';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><a class="page-link" href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['prev_tag_open'] = '<li class="page-item">';
        $config['prev_tag_close'] = '</li>';
        $config['next_tag_open'] = '<li class="page-item">';
        $config['next_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');

        $this->pagination->initialize($config);

        $data['title'] = 'Master Jasa';
        $data['jasas'] = $jasas;
        $data['pagination'] = $this->pagination->create_links();
        $data['keyword'] = $keyword;
        $data['offset'] = $offset;
        $data['per_page'] = $per_page;

        $data['satuan_list'] = $this->Commoncode_model->get_by_group('SATUAN');
        $data['kategori_list'] = $this->Commoncode_model->get_by_group('JASA_CATEGORY');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('jasa/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('master_jasa', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $nama_jasa = trim($this->input->post('nama_jasa'));
        $kategori  = $this->input->post('kategori');
        $satuan    = $this->input->post('satuan');
        $kode_jasa = trim($this->input->post('kode_jasa'));

        if (empty($nama_jasa)) {
            $this->session->set_flashdata('error', 'Nama jasa wajib diisi.');
            return redirect('master_jasa');
        }

        if (empty($kode_jasa)) {
            $kode_jasa = $this->Jasa_model->generate_kode();
        } else {
            $exists = $this->db->get_where('jasa', ['kode_jasa' => $kode_jasa])->row();
            if ($exists) {
                $this->session->set_flashdata('error', "Kode jasa '$kode_jasa' sudah ada.");
                return redirect('master_jasa');
            }
        }

        $data = [
            'kode_jasa' => $kode_jasa,
            'nama_jasa' => $nama_jasa,
            'kategori'  => $kategori,
            'satuan'    => $satuan,
            'status'    => 'active',
        ];

        if ($this->Jasa_model->insert($data)) {
            $this->_log('CREATE', "Membuat jasa baru: {$data['nama_jasa']} (Kode: {$data['kode_jasa']})");
            $this->session->set_flashdata('success', 'Jasa berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan jasa!');
        }
        redirect('master_jasa');
    }

    public function update($id) {
        if (!check_access('master_jasa', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }

        $old_data = $this->Jasa_model->get_by_id($id);
        if (!$old_data) show_404();

        $data = [
            'kode_jasa' => trim($this->input->post('kode_jasa')),
            'nama_jasa' => trim($this->input->post('nama_jasa')),
            'kategori'  => $this->input->post('kategori'),
            'satuan'    => $this->input->post('satuan'),
            'status'    => $this->input->post('status'),
        ];

        if ($this->Jasa_model->update($id, $data)) {
            $this->_log('UPDATE', "Memperbarui jasa: {$old_data->nama_jasa} (ID: $id) menjadi {$data['nama_jasa']}");
            $this->session->set_flashdata('success', 'Data jasa berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_jasa');
    }

    public function delete($id) {
        if (!check_access('master_jasa', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }

        $jasa_data = $this->Jasa_model->get_by_id($id);

        if ($this->Jasa_model->delete($id)) {
            $this->_log('UPDATE', "Menonaktifkan jasa: " . ($jasa_data->nama_jasa ?? "ID: $id"));
            $this->session->set_flashdata('success', 'Data jasa berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_jasa');
    }
}
