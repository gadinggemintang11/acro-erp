<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Role_model'); // Load Role_model untuk ambil data role
        $this->load->helper('url');
    }

    public function index()
    {
        $data = $this->data; 
        $data['title'] = 'Manajemen User';
        $data['content'] = 'user/index';
        $data['users'] = $this->User_model->getAll();
        $this->load->view('layouts/main_template', $data);
    }

    public function add()
    {
        $data = $this->data; 
        $data['title'] = 'Tambah User Baru';
        $data['content'] = 'user/add_form';
        $data['roles'] = $this->Role_model->getAll(); // Ambil data role untuk dropdown
        $this->load->view('layouts/main_template', $data);
    }

    public function save()
    {
        $this->User_model->save();
        $this->session->set_flashdata('success', 'User baru berhasil ditambahkan.');
        redirect('user');
    }

    public function edit($id = null)
    {
        if (!$id) redirect('user');
        $data = $this->data;    
        $data['title'] = 'Edit User';
        $data['content'] = 'user/edit_form';
        $data['user'] = $this->User_model->getById($id);
        $data['roles'] = $this->Role_model->getAll(); // Ambil data role untuk dropdown

        if (!$data['user']) show_404();
        
        $this->load->view('layouts/main_template', $data);
    }

    public function update()
    {
        $this->User_model->update();
        $this->session->set_flashdata('success', 'Data user berhasil diperbarui.');
        redirect('user');
    }

    public function delete($id = null)
    {
        if (!$id) redirect('user');
        
        if ($this->User_model->delete($id)) {
            $this->session->set_flashdata('success', 'Data user berhasil dihapus.');
        }
        redirect('user');
    }
}