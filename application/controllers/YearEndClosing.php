<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class YearEndClosing extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('YearEndClosing_model', 'model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $data['title'] = 'Year-End Closing (Tutup Buku Tahunan)';
        $data['closings'] = $this->model->get_all_closings();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/year_end_closing/index', $data);
        $this->load->view('templates/footer');
    }

    public function create()
    {
        $data['title'] = 'Buat Year-End Closing';
        $data['years'] = $this->model->get_available_years();
        $data['retained_earnings_accounts'] = $this->model->get_retained_earnings_accounts();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/year_end_closing/create', $data);
        $this->load->view('templates/footer');
    }

    public function preview()
    {
        $year = $this->input->post('year');
        $retained_earnings_account_id = $this->input->post('retained_earnings_account_id');

        if (empty($year) || empty($retained_earnings_account_id)) {
            $this->session->set_flashdata('error', 'Tahun dan akun Retained Earnings harus dipilih.');
            redirect('yearendclosing/create');
        }

        $data['title'] = 'Preview Year-End Closing';
        $data['year'] = $year;
        $data['retained_earnings_account'] = $this->db->get_where('chart_of_accounts', ['id' => $retained_earnings_account_id])->row();
        $data['summary'] = $this->model->calculate_closing_summary($year);
        $data['revenue_accounts'] = $this->model->get_revenue_accounts($year);
        $data['expense_accounts'] = $this->model->get_expense_accounts($year);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/year_end_closing/preview', $data);
        $this->load->view('templates/footer');
    }

    public function process()
    {
        $year = $this->input->post('year');
        $retained_earnings_account_id = $this->input->post('retained_earnings_account_id');

        if (empty($year) || empty($retained_earnings_account_id)) {
            $this->session->set_flashdata('error', 'Data tidak lengkap.');
            redirect('yearendclosing/create');
        }

        // Cek apakah sudah pernah closing untuk tahun ini
        $existing = $this->model->check_existing_closing($year);
        if ($existing) {
            $this->session->set_flashdata('error', 'Year-End Closing untuk tahun ' . $year . ' sudah pernah dilakukan.');
            redirect('yearendclosing');
        }

        $result = $this->model->process_closing($year, $retained_earnings_account_id, $this->session->userdata('user_id'));

        if ($result['success']) {
            $this->session->set_flashdata('success', 'Year-End Closing berhasil! Jurnal Penutup: ' . $result['journal_no']);
            redirect('yearendclosing/view/' . $result['closing_id']);
        } else {
            $this->session->set_flashdata('error', 'Gagal melakukan Year-End Closing: ' . $result['message']);
            redirect('yearendclosing/create');
        }
    }

    public function view($id)
    {
        $data['closing'] = $this->model->get_closing_by_id($id);
        if (!$data['closing']) show_404();

        $data['title'] = 'Detail Year-End Closing';
        $data['journal_header'] = $this->db->get_where('journal_header', ['id' => $data['closing']->journal_header_id])->row();
        $data['journal_details'] = $this->db->get_where('journal_details', ['journal_header_id' => $data['closing']->journal_header_id])->result();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/year_end_closing/view', $data);
        $this->load->view('templates/footer');
    }
}
