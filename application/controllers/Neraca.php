<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Neraca extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        check_login(); // 2026-11-05: halaman ini sebelumnya bisa dibuka TANPA login
        $this->load->model('Neraca_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $data['title'] = lang_line('title_neraca', 'Neraca');
        $data['users'] = $this->db->get_where('users', ['email' => $this->session->userdata('email')])->row_array();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('neraca/index', $data);
        $this->load->view('templates/footer');
    }

    public function get_neraca_data()
    {
        $date = $this->input->post('date');
        $data = $this->Neraca_model->get_data($date);
        echo json_encode($data);
    }

    public function detail($account_id, $date)
    {
        $data['title'] = lang_line('title_neraca_detail', 'Detail Ledger');
        $data['users'] = $this->db->get_where('users', ['email' => $this->session->userdata('email')])->row_array();
        $data['detail'] = $this->Neraca_model->get_detail_ledger($account_id, $date);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('neraca/detail', $data);
        $this->load->view('templates/footer');
    }

    public function pdf($date)
    {
        $this->load->library('pdf');
        $data['neraca'] = $this->Neraca_model->get_data($date);
        $data['date'] = $date;

        $this->pdf->setPaper('A4', 'potrait');
        $this->pdf->filename = "laporan-neraca.pdf";
        $this->pdf->load_view('neraca/pdf', $data);
    }

    public function excel($date)
    {
        $this->load->library('excel');
        $data['neraca'] = $this->Neraca_model->get_data($date);
        $data['date'] = $date;

        $this->load->view('neraca/excel', $data);
    }
}