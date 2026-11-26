<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Biaya Dibayar Dimuka (mis. Sewa Dibayar Dimuka) — jadwal amortisasi.
 * Logika di Prepaid_expense_model; amortisasi diposting lewat batch
 * Penyusutan & Amortisasi. Menu key: prepaid_expense.
 */
class Prepaid_expense extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) redirect('auth');
        if (!check_access('prepaid_expense', 'view')) show_error('Anda tidak memiliki akses ke Biaya Dibayar Dimuka.', 403, 'Akses Ditolak');
        $this->load->model('Prepaid_expense_model', 'ppd');
        $this->load->model('Fixed_asset_model', 'fa');
    }

    private function _render($view, $data)
    {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/prepaid_expense/' . $view, $data);
        $this->load->view('templates/footer');
    }

    private function _need($action)
    {
        if (!check_access('prepaid_expense', $action)) show_error('Anda tidak memiliki izin untuk aksi ini.', 403, 'Akses Ditolak');
    }

    public function index()
    {
        $filters = ['q' => trim((string) $this->input->get('q')), 'status' => (string) $this->input->get('status')];
        $this->_render('index', ['title' => lang_line('prepaid_expense_title', 'Biaya Dibayar Dimuka'), 'rows' => $this->ppd->get_list($filters), 'filters' => $filters]);
    }

    private function _form($title, $row = null)
    {
        $this->_render('form', [
            'title' => $title, 'row' => $row, 'accounts' => $this->fa->get_accounts(), 'departments' => $this->fa->get_departments(),
            'defaults' => $this->ppd->default_accounts(), 'locked' => $row ? $this->ppd->has_posted_amortization($row->id) : false,
        ]);
    }

    public function add() { $this->_need('add'); $this->_form(lang_line('prepaid_expense_add', 'Tambah Biaya Dibayar Dimuka')); }

    public function store()
    {
        $this->_need('add');
        if ($this->input->method() !== 'post') redirect('prepaid_expense/add');
        try {
            $id = $this->ppd->create($this->input->post(), $this->session->userdata('user_id'));
            $p = $this->ppd->get($id);
            add_log('CREATE', 'Prepaid Expense', 'Tambah biaya dibayar dimuka ' . $p->doc_no . ' ' . $p->nama . ' ' . number_format((float) $p->nilai, 2) . ' / ' . $p->jumlah_bulan . ' bln');
            $this->session->set_flashdata('success', 'Biaya dibayar dimuka ' . $p->doc_no . ' tersimpan.');
            redirect('prepaid_expense/view/' . $id);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('prepaid_expense/add');
        }
    }

    public function view($id)
    {
        $p = $this->ppd->get($id);
        if (!$p) show_404();
        $this->_render('view', ['title' => $p->doc_no . ' - ' . $p->nama, 'p' => $p, 'history' => $this->ppd->get_history($id), 'monthly' => $this->ppd->monthly($p), 'has_posted' => $this->ppd->has_posted_amortization($id)]);
    }

    public function edit($id)
    {
        $this->_need('edit');
        $p = $this->ppd->get($id);
        if (!$p) show_404();
        $this->_form('Edit ' . $p->doc_no, $p);
    }

    public function update($id)
    {
        $this->_need('edit');
        if ($this->input->method() !== 'post') redirect('prepaid_expense/edit/' . (int) $id);
        try {
            $this->ppd->update($id, $this->input->post());
            add_log('UPDATE', 'Prepaid Expense', 'Edit biaya dibayar dimuka #' . (int) $id);
            $this->session->set_flashdata('success', 'Perubahan tersimpan.');
            redirect('prepaid_expense/view/' . (int) $id);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('prepaid_expense/edit/' . (int) $id);
        }
    }

    public function delete($id)
    {
        $this->_need('delete');
        if ($this->input->method() !== 'post') show_error('Metode ilegal.', 405);
        try {
            $no = $this->ppd->delete($id);
            add_log('DELETE', 'Prepaid Expense', 'Hapus biaya dibayar dimuka ' . $no);
            $this->session->set_flashdata('success', $no . ' dihapus.');
            redirect('prepaid_expense');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('prepaid_expense/view/' . (int) $id);
        }
    }
}
