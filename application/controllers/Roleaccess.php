<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Roleaccess extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        check_login(); // middleware login
        $this->load->model('Role_model');
    }

    public function index()
    {
        if (!check_access('roleaccess', 'view')) {
        show_error('Anda tidak memiliki izin untuk mengakses modul ini.', 403);
    }
        $data['title'] = 'Role Access Management';
        $data['roles'] = $this->Role_model->get_all_roles();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('roleaccess/index', $data);
        $this->load->view('templates/footer');
    }

    public function manage_permission($role_id)
    {
        $data['title'] = 'Kelola Hak Akses Role';
        $data['role'] = $this->Role_model->get_role_by_id($role_id);
        $data['permissions'] = $this->Role_model->get_permissions_by_role($role_id);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('roleaccess/manage', $data);
        $this->load->view('templates/footer');
    }

    public function save_permission($role_id)
    {
        $permissions = $this->input->post('perm');
        $this->Role_model->save_permissions($role_id, $permissions);

        $this->session->set_flashdata('success', 'Hak akses role berhasil diperbarui.');
        redirect('roleaccess/manage_permission/'.$role_id);
    }

    public function add()
    {
        $data = [
            'role_name' => $this->input->post('role_name'),
            'description' => $this->input->post('description'),
            'status' => 'active'
        ];
        $this->Role_model->insert_role($data);
        $this->session->set_flashdata('success', 'Role berhasil ditambahkan.');
        redirect('roleaccess');
    }

    public function update($id)
    {
        $data = [
            'role_name' => $this->input->post('role_name'),
            'description' => $this->input->post('description'),
            'status' => $this->input->post('status')
        ];
        $this->Role_model->update_role($id, $data);
        $this->session->set_flashdata('success', 'Role berhasil diperbarui.');
        redirect('roleaccess');
    }

    public function delete($id)
    {
        $this->Role_model->delete_role($id);
        $this->session->set_flashdata('success', 'Role berhasil dihapus.');
        redirect('roleaccess');
    }
}
