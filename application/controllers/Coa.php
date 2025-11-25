<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coa extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('Coa_model', 'model');
        $this->load->model('JurnalUmum_model'); 
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    public function index()
    {
        $data['title'] = 'Chart of Accounts (COA)';
        $data['accounts_tree'] = $this->model->get_all_as_tree();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/coa/index', $data);
        $this->load->view('templates/footer');
    }

    public function create()
    {
        $data['title'] = 'Buat Akun Baru (COA)';
        $data['header_accounts'] = $this->model->get_header_accounts();
        $data['currencies'] = $this->JurnalUmum_model->get_currencies(); // <-- REVISI
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/coa/create', $data);
        $this->load->view('templates/footer');
    }

    public function store()
    {
        $this->_validate_form();

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $data = [
                'account_code' => $this->input->post('account_code'),
                'account_name' => $this->input->post('account_name'),
                'parent_id' => $this->input->post('parent_id'),
                'account_type' => $this->input->post('account_type'),
                'currency' => $this->input->post('currency'), // <-- REVISI
                // normal_balance: expected values 'Debit' or 'Kredit' (nullable)
                'normal_balance' => $this->input->post('normal_balance') ? $this->input->post('normal_balance') : $this->_default_normal_by_type($this->input->post('account_type')),
                'is_header' => $this->input->post('is_header'),
                'status' => $this->input->post('status')
            ];

            if ($this->model->insert($data)) {
                $this->session->set_flashdata('success', 'Akun baru berhasil ditambahkan.');
                redirect('coa');
            } else {
                $this->session->set_flashdata('error', 'Gagal menambahkan akun.');
                redirect('coa/create');
            }
        }
    }

    public function edit($id)
    {
        $data['title'] = 'Edit Akun (COA)';
        $data['account'] = $this->model->get_by_id($id);
        $data['header_accounts'] = $this->model->get_header_accounts($id);
        $data['currencies'] = $this->JurnalUmum_model->get_currencies(); // <-- REVISI
        
        if (!$data['account']) show_404();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/coa/edit', $data);
        $this->load->view('templates/footer');
    }

    public function update($id)
    {
        $this->_validate_form(true, $id);

        if ($this->form_validation->run() == FALSE) {
            $this->edit($id);
        } else {
            $data = [
                'account_code' => $this->input->post('account_code'),
                'account_name' => $this->input->post('account_name'),
                'parent_id' => $this->input->post('parent_id'),
                'account_type' => $this->input->post('account_type'),
                'currency' => $this->input->post('currency'), // <-- REVISI
                'normal_balance' => $this->input->post('normal_balance') ? $this->input->post('normal_balance') : $this->_default_normal_by_type($this->input->post('account_type')),
                'is_header' => $this->input->post('is_header'),
                'status' => $this->input->post('status')
            ];

            if ($this->model->update($id, $data)) {
                $this->session->set_flashdata('success', 'Akun berhasil diperbarui.');
                redirect('coa');
            } else {
                $this->session->set_flashdata('error', 'Gagal memperbarui akun.');
                redirect('coa/edit/' . $id);
            }
        }
    }

    public function delete($id)
    {
        $check = $this->model->is_deletable($id);
        if ($check['status'] == TRUE) {
            $this->model->delete($id);
            $this->session->set_flashdata('success', 'Akun berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus: ' . $check['message']);
        }
        redirect('coa');
    }

    private function _validate_form($is_edit = false, $id = null)
    {
        $this->form_validation->set_rules('account_name', 'Nama Akun', 'required');
        
        $original_code = $is_edit ? $this->model->get_by_id($id)->account_code : '';
        $is_unique = ($this->input->post('account_code') === $original_code) ? '' : '|is_unique[chart_of_accounts.account_code]';
        $this->form_validation->set_rules('account_code', 'Kode Akun', 'required|trim' . $is_unique);
        
        $this->form_validation->set_rules('parent_id', 'Induk Akun', 'required');
        $this->form_validation->set_rules('account_type', 'Tipe Akun', 'required');
        $this->form_validation->set_rules('currency', 'Mata Uang', 'required'); // <-- REVISI
        $this->form_validation->set_rules('is_header', 'Jenis Akun', 'required');
        $this->form_validation->set_rules('status', 'Status', 'required');
    }

    // Tentukan posisi saldo normal default berdasarkan tipe akun
    private function _default_normal_by_type($account_type)
    {
        $debit_normal = ['Aset', 'Biaya', 'HPP'];
        return in_array($account_type, $debit_normal) ? 'Debit' : 'Kredit';
    }
}
