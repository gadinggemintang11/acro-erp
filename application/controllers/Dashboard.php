<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login(); // Pastikan user login dulu

        $this->load->model('Role_model');
        $this->load->library('session');
    }

    public function index() {
        $data['title'] = 'Dashboard';
        $data['username'] = $this->session->userdata('full_name');
        $data['role_name'] = $this->db->get_where('roles', ['id' => $this->session->userdata('role_id')])->row('role_name');
        $data['menu_count'] = $this->db->count_all('menus');
        $data['user_count'] = $this->db->count_all('users');
        $data['role_count'] = $this->db->count_all('roles');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('dashboard/index', $data);
        $this->load->view('templates/footer');
    }
}
