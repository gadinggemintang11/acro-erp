<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Master_asset extends MY_Controller {

    protected $module_name = 'Master Asset';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Asset_model');
        $this->load->model('Commoncode_model');
    }

    public function index() {
        if (!check_access('master_asset', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $keyword = $this->input->get('q');
        $per_page = 20;
        $page = (int) $this->input->get('page');
        $offset = $page > 0 ? ($page - 1) * $per_page : 0;

        $total_rows = $this->Asset_model->count_filtered($keyword);
        $assets = $this->Asset_model->get_paginated($per_page, $offset, $keyword);

        $this->load->library('pagination');
        $config['base_url'] = site_url('master_asset');
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

        $data['title'] = 'Master Asset';
        $data['assets'] = $assets;
        $data['pagination'] = $this->pagination->create_links();
        $data['keyword'] = $keyword;
        $data['offset'] = $offset;
        $data['per_page'] = $per_page;

        $data['satuan_list'] = $this->Commoncode_model->get_by_group('SATUAN');
        $data['kategori_list'] = $this->Commoncode_model->get_by_group('ASSET_CATEGORY');
        $data['metode_list'] = $this->Commoncode_model->get_by_group('DEPRECIATION_METHOD');

        // Dropdown mapping COA. Akumulasi penyusutan bertipe 'Aset' tapi
        // bersaldo normal Kredit (contra-asset), jadi dipisah dari akun aset.
        // Prefix mengelompokkan akun yang relevan ke bagian "Disarankan"
        // supaya akun seperti 'Accounts Receivable' atau 'PPh 25' tidak
        // sejajar dengan kandidat yang benar.
        // Prefix COA Acropoly (2026-11-07): 1.109 Aset Tetap, 1.202 Akumulasi
        // Penyusutan, beban penyusutan 5.300.02x (pabrik, tipe HPP) & 6.300 (kantor).
        $data['coa_asset_list'] = $this->Asset_model->get_coa_grouped('Aset', 'Debit',  '1.109');
        $data['coa_akum_list']  = $this->Asset_model->get_coa_grouped('Aset', 'Kredit', '1.202');
        $data['coa_beban_list'] = $this->Asset_model->get_coa_grouped(['Biaya', 'HPP'], null, ['5.300.02', '6.300']);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('asset/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        if (!check_access('master_asset', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $nama_asset = trim($this->input->post('nama_asset'));
        $kode_asset = trim($this->input->post('kode_asset'));

        if (empty($nama_asset)) {
            $this->session->set_flashdata('error', 'Nama asset wajib diisi.');
            return redirect('master_asset');
        }

        if (empty($kode_asset)) {
            $kode_asset = $this->Asset_model->generate_kode();
        } else {
            $exists = $this->db->get_where('assets', ['kode_asset' => $kode_asset])->row();
            if ($exists) {
                $this->session->set_flashdata('error', "Kode asset '$kode_asset' sudah ada.");
                return redirect('master_asset');
            }
        }

        $data = array_merge([
            'kode_asset' => $kode_asset,
            'nama_asset' => $nama_asset,
            'status'     => 'active',
        ], $this->_collect_common_input());

        if ($this->Asset_model->insert($data)) {
            $this->_log('CREATE', "Membuat asset baru: {$data['nama_asset']} (Kode: {$data['kode_asset']})");
            $this->session->set_flashdata('success', 'Asset berhasil ditambahkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menambahkan asset!');
        }
        redirect('master_asset');
    }

    public function update($id) {
        if (!check_access('master_asset', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }

        $old_data = $this->Asset_model->get_by_id($id);
        if (!$old_data) show_404();

        $kode_asset = trim($this->input->post('kode_asset'));
        if (empty($kode_asset)) {
            $this->session->set_flashdata('error', 'Kode asset wajib diisi.');
            return redirect('master_asset');
        }

        // Kode unik di DB -- dicek dulu supaya user dapat pesan yang jelas
        // daripada error SQL duplicate key.
        $dupe = $this->db->where('kode_asset', $kode_asset)->where('id !=', $id)->get('assets')->row();
        if ($dupe) {
            $this->session->set_flashdata('error', "Kode asset '$kode_asset' sudah dipakai asset lain.");
            return redirect('master_asset');
        }

        $data = array_merge([
            'kode_asset' => $kode_asset,
            'nama_asset' => trim($this->input->post('nama_asset')),
            'status'     => $this->input->post('status'),
        ], $this->_collect_common_input());

        if ($this->Asset_model->update($id, $data)) {
            $this->_log('UPDATE', "Memperbarui asset: {$old_data->nama_asset} (ID: $id) menjadi {$data['nama_asset']}");
            $this->session->set_flashdata('success', 'Data asset berhasil diperbarui!');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui data!');
        }
        redirect('master_asset');
    }

    public function delete($id) {
        if (!check_access('master_asset', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }

        $asset_data = $this->Asset_model->get_by_id($id);

        if ($this->Asset_model->delete($id)) {
            $this->_log('UPDATE', "Menonaktifkan asset: " . ($asset_data->nama_asset ?? "ID: $id"));
            $this->session->set_flashdata('success', 'Data asset berhasil dinonaktifkan!');
        } else {
            $this->session->set_flashdata('error', 'Gagal menonaktifkan data!');
        }
        redirect('master_asset');
    }

    /**
     * Field yang bentuknya sama di form Tambah dan Edit.
     *
     * Ketiga coa_* dibiarkan NULL kalau belum dipilih -- modul ini harus bisa
     * dipakai penuh sebelum Finance menentukan mapping akunnya.
     */
    private function _collect_common_input() {
        $umur = (int) $this->input->post('umur_manfaat_bulan');

        return [
            'kategori'            => $this->input->post('kategori'),
            'satuan'              => $this->input->post('satuan'),
            'umur_manfaat_bulan'  => $umur > 0 ? $umur : 48,
            'metode_penyusutan'   => $this->input->post('metode_penyusutan') ?: 'GARIS_LURUS',
            'nilai_residu_persen' => (float) $this->input->post('nilai_residu_persen'),
            'coa_asset_id'        => $this->input->post('coa_asset_id') ?: NULL,
            'coa_akum_id'         => $this->input->post('coa_akum_id') ?: NULL,
            'coa_beban_id'        => $this->input->post('coa_beban_id') ?: NULL,
            'keterangan'          => trim($this->input->post('keterangan')),
        ];
    }
}
