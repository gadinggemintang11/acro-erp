<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Role extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Role_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data = $this->data; 
        $data['title'] = 'Manajemen Role';
        $data['content'] = 'role/index';
        $data['roles'] = $this->Role_model->getAll();
        $this->load->view('layouts/main_template', $data);
    }

    public function add()
    {
        $data = $this->data; 
        $data['title'] = 'Tambah Role Baru';
        $data['content'] = 'role/add_form';
        $this->load->view('layouts/main_template', $data);
    }

    public function save()
    {

        $this->Role_model->save();
        $this->session->set_flashdata('success', 'Role baru berhasil ditambahkan.');
        redirect('role');
    }

    public function edit($id = null)
    {
        
        if (!$id) redirect('role');
        $data = $this->data; 
        $data['title'] = 'Edit Role';
        $data['content'] = 'role/edit_form';
        $data['role'] = $this->Role_model->getById($id);

        if (!$data['role']) show_404();
        
        $this->load->view('layouts/main_template', $data);
    }

    public function update()
    {
        $this->Role_model->update();
        $this->session->set_flashdata('success', 'Data role berhasil diperbarui.');
        redirect('role');
    }

    public function delete($id = null)
    {
        if (!$id) redirect('role');
        
        if ($this->Role_model->delete($id)) {
            $this->session->set_flashdata('success', 'Data role berhasil dihapus.');
        }
        redirect('role');
    }
    public function access($role_id = null)
    {
        if (!$role_id) redirect('role');

        // Ambil data role yang akan diubah
        $data['role'] = $this->Role_model->getById($role_id);
        if (!$data['role']) show_404();

        // Ambil data akses yang sudah ada
        $existing_access = $this->Role_model->getAccessByRoleId($role_id);
        $data['existing_controllers'] = array_column($existing_access, 'controller_name');

        // Daftar semua controller yang bisa diberi akses
        $data['controllers'] = [
            'dashboard', 'material', 'supplier', 'customer', 
            'product', 'asset', 'role', 'user', 'log', 'goods_receipt',
        ];
        
        $data['title'] = 'Hak Akses Role';
        $data['content'] = 'role/access_form';
        $this->load->view('layouts/main_template', $data);
    }

    /**
     * Menyimpan perubahan hak akses
     */
    public function save_access()
    {
        $role_id = $this->input->post('role_id');
        $controllers = $this->input->post('controllers'); // Ini akan berupa array

        if (!$controllers) {
            $controllers = []; // Pastikan array kosong jika tidak ada yang dicentang
        }

        $this->Role_model->updateAccess($role_id, $controllers);
        $this->session->set_flashdata('success', 'Hak akses berhasil diperbarui.');
        redirect('role/access/' . $role_id);
    }
}