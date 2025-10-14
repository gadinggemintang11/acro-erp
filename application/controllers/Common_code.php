<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Common_code extends MY_Controller {
    public function __construct() { parent::__construct(); $this->load->model('Common_code_model'); }
    public function index() {
        $data = $this->data;
        $data['title'] = 'Common Codes';
        $data['content'] = 'common_code/index';
        $data['codes'] = $this->Common_code_model->getAll();
        $this->load->view('layouts/main_template', $data);
    }
    public function add() {
        $data = $this->data;
        $data['title'] = 'Tambah Common Code';
        $data['content'] = 'common_code/add_form';
        $this->load->view('layouts/main_template', $data);
    }
    public function save() {
        $this->Common_code_model->save();
        $this->session->set_flashdata('success', 'Common code berhasil ditambahkan.');
        redirect('common_code');
    }
    public function edit($id = null) {
        if (!$id) redirect('common_code');
        $data = $this->data;
        $data['title'] = 'Edit Common Code';
        $data['content'] = 'common_code/edit_form';
        $data['code'] = $this->Common_code_model->getById($id);
        if (!$data['code']) show_404();
        $this->load->view('layouts/main_template', $data);
    }
    public function update() {
        $this->Common_code_model->update();
        $this->session->set_flashdata('success', 'Common code berhasil diperbarui.');
        redirect('common_code');
    }
    public function delete($id = null) {
        if (!$id) redirect('common_code');
        if ($this->Common_code_model->delete($id)) {
            $this->session->set_flashdata('success', 'Common code berhasil dihapus.');
        }
        redirect('common_code');
    }
}