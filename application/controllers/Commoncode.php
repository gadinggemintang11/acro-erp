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

        $data['title'] = 'Common Code - Group Management';
        $data['groups'] = $this->Commoncode_model->get_all_groups();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('commoncode/index', $data);
        $this->load->view('templates/footer');
    }

    public function detail($group_code)
    {
        if (!check_access('commoncode', 'view')) {
            show_error('Anda tidak memiliki akses untuk melihat modul ini.', 403);
        }

        $data['title'] = 'Common Code - ' . htmlspecialchars($group_code);
        $data['group_code'] = $group_code;
        $data['codes'] = $this->Commoncode_model->get_by_group($group_code);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('commoncode/detail', $data);
        $this->load->view('templates/footer');
    }

    public function add_group()
    {
        if (!check_access('commoncode', 'add')) {
            show_error('Anda tidak memiliki izin menambah data.', 403);
        }

        $group_code = strtoupper($this->input->post('group_code'));
        $group_description = $this->input->post('group_description');
        
        // Insert first record for this group
        $data = [
            'group_code' => $group_code,
            'group_description' => $group_description,
            'code' => $group_code . '_DEFAULT',
            'description' => 'Default code for ' . $group_code,
            'status' => 'active'
        ];
        
        $this->Commoncode_model->insert($data);
        $this->session->set_flashdata('success', 'Group berhasil ditambahkan.');
        redirect('commoncode');
    }

    public function add_code($group_code)
    {
        if (!check_access('commoncode', 'add')) {
            show_error('Anda tidak memiliki izin menambah data.', 403);
        }

        $data = [
            'group_code' => $group_code,
            'code' => $this->input->post('code'),
            'description' => $this->input->post('description'),
            'status' => 'active'
        ];
        
        $this->Commoncode_model->insert($data);
        $this->session->set_flashdata('success', 'Code berhasil ditambahkan.');
        redirect('commoncode/detail/' . $group_code);
    }

    public function add()
    {
        // Kept for backward compatibility, redirect to add_group
        $this->add_group();
    }

    public function update($id, $group_code = null)
    {
        if (!check_access('commoncode', 'edit')) {
            show_error('Anda tidak memiliki izin mengedit data.', 403);
        }

        $data = [
            'code' => $this->input->post('code'),
            'description' => $this->input->post('description'),
            'status' => $this->input->post('status')
        ];

        $this->Commoncode_model->update($id, $data);
        $this->session->set_flashdata('success', 'Data berhasil diupdate.');
        
        if ($group_code) {
            redirect('commoncode/detail/' . $group_code);
        } else {
            redirect('commoncode');
        }
    }

    public function update_group($old_group_code)
    {
        if (!check_access('commoncode', 'edit')) {
            show_error('Anda tidak memiliki izin mengedit data.', 403);
        }

        $new_group_code = strtoupper($this->input->post('group_code'));
        $group_description = $this->input->post('group_description');

        // Update semua record dengan group_code ini
        $this->Commoncode_model->update_group($old_group_code, $new_group_code, $group_description);
        
        $this->session->set_flashdata('success', 'Group berhasil diupdate.');
        redirect('commoncode');
    }

    public function delete($id, $group_code = null)
    {
        if (!check_access('commoncode', 'delete')) {
            show_error('Anda tidak memiliki izin menghapus data.', 403);
        }

        $this->Commoncode_model->delete($id);
        $this->session->set_flashdata('success', 'Data berhasil dihapus.');
        
        if ($group_code) {
            redirect('commoncode/detail/' . $group_code);
        } else {
            redirect('commoncode');
        }
    }

    public function delete_group($group_code)
    {
        if (!check_access('commoncode', 'delete')) {
            show_error('Anda tidak memiliki izin menghapus data.', 403);
        }

        $this->Commoncode_model->delete_by_group($group_code);
        $this->session->set_flashdata('success', 'Group dan semua code di dalamnya berhasil dihapus.');
        redirect('commoncode');
    }

}
