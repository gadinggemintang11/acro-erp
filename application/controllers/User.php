<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('User_model');
        $this->load->library('session');
    }

    public function index() {
        check_access('user', 'view');
        $data['title'] = 'User Management';
        $data['users'] = $this->User_model->get_all();
        $data['roles'] = $this->User_model->get_roles();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('user/index', $data);
        $this->load->view('templates/footer');
    }

    public function add() {
        $data = [
            'fullname' => $this->input->post('fullname'),
            'username' => $this->input->post('username'),
            'password' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
            'email'    => $this->input->post('email'),
            'role_id'  => $this->input->post('role_id'),
            'status'   => 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->User_model->insert($data);
        $this->session->set_flashdata('success', 'User baru berhasil ditambahkan!');
        redirect('user');
    }


    public function update($id) {
        check_access('user', 'edit');
        $data = [
            'fullname' => $this->input->post('fullname'),
            'username' => $this->input->post('username'),
            'email'    => $this->input->post('email'),
            'role_id'  => $this->input->post('role_id'),
            'status'   => $this->input->post('status')
        ];
        $this->User_model->update($id, $data);
        $this->session->set_flashdata('success', 'Data user berhasil diperbarui.');
        redirect('user');
    }

    public function delete($id) {
        check_access('user', 'delete');
        $this->User_model->delete($id);
        $this->session->set_flashdata('success', 'User berhasil dinonaktifkan.');
        redirect('user');
    }

    public function reset_password($id) {
        check_access('user', 'edit');
        $new_pass = password_hash('123456', PASSWORD_DEFAULT);
        $this->User_model->update($id, ['password' => $new_pass]);
        $this->session->set_flashdata('success', 'Password berhasil direset (123456).');
        redirect('user');
    }
}
