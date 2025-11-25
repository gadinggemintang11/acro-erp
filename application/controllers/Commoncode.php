<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Commoncode extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        check_login();
        $this->load->model('Commoncode_model');
        $this->load->helper('auth'); // ✅ tambahkan ini
    }

    public function index()
    {
        if (!check_access('commoncode', 'view')) {
            show_error('Anda tidak memiliki akses untuk melihat modul ini.', 403);
        }

        $data['title'] = 'Common Code Management';
        $data['common'] = $this->Commoncode_model->get_all();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('commoncode/index', $data);
        $this->load->view('templates/footer');
    }

    public function add()
    {
        if (!check_access('commoncode', 'add')) {
            show_error('Anda tidak memiliki izin menambah data.', 403);
        }

        $data = [
            'group_code' => $this->input->post('group_code'),
            'code' => $this->input->post('code'),
            'description' => $this->input->post('description'),
            'status' => 'active'
        ];
        $this->Commoncode_model->insert($data);
        $this->session->set_flashdata('success', 'Data berhasil ditambahkan.');
        redirect('commoncode');
    }

    public function update($id)
    {
        if (!check_access('commoncode', 'edit')) {
            show_error('Anda tidak memiliki izin mengedit data.', 403);
        }

        $data = [
            'description' => $this->input->post('description'),
            'status' => $this->input->post('status')
        ];

        $this->Commoncode_model->update($id, $data);
        $this->session->set_flashdata('success', 'Data berhasil diupdate.');
        redirect('commoncode');
    }

    public function delete($id)
    {
        if (!check_access('commoncode', 'delete')) {
            show_error('Anda tidak memiliki izin menghapus data.', 403);
        }

        $this->Commoncode_model->delete($id);
        $this->session->set_flashdata('success', 'Data berhasil dihapus.');
        redirect('commoncode');
    }

}
