<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class YearEndClosing extends MY_Controller {

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
        $data['title'] = lang_line('year_end_closing_title', 'Tutup Buku Tahunan (Year-End Closing)');
        $data['closings'] = $this->model->get_all_closings();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/year_end_closing/index', $data);
        $this->load->view('templates/footer');
    }

    public function create()
    {
        $data['title'] = lang_line('year_end_closing_create', 'Buat Year-End Closing');
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
            $this->session->set_flashdata('error', lang_line('year_end_closing_err_year_re'));
            redirect('yearendclosing/create');
        }

        $data['title'] = lang_line('year_end_closing_preview');
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
            $this->session->set_flashdata('error', lang_line('year_end_closing_err_incomplete'));
            redirect('yearendclosing/create');
        }

        // Cek apakah sudah pernah closing untuk tahun ini
        $existing = $this->model->check_existing_closing($year);
        if ($existing) {
            $this->session->set_flashdata('error', sprintf(lang_line('year_end_closing_err_exists'), $year));
            redirect('yearendclosing');
        }

        $result = $this->model->process_closing($year, $retained_earnings_account_id, $this->session->userdata('user_id'));

        if ($result['success']) {
            $this->session->set_flashdata('success', sprintf(lang_line('year_end_closing_ok_done'), $result['journal_no']));
            redirect('yearendclosing/view/' . $result['closing_id']);
        } else {
            $this->session->set_flashdata('error', sprintf(lang_line('year_end_closing_err_failed'), $result['message']));
            redirect('yearendclosing/create');
        }
    }

    public function view($id)
    {
        $data['closing'] = $this->model->get_closing_by_id($id);
        if (!$data['closing']) show_404();

        $data['title'] = lang_line('year_end_closing_detail');
        $data['journal_header'] = $this->db->get_where('journal_header', ['id' => $data['closing']->journal_header_id])->row();
        $data['journal_details'] = $this->db->get_where('journal_details', ['journal_header_id' => $data['closing']->journal_header_id])->result();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/year_end_closing/view', $data);
        $this->load->view('templates/footer');
    }
}
