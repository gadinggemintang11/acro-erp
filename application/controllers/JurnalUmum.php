<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class JurnalUmum extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('JurnalUmum_model', 'model');
        $this->load->model('Coa_model'); 
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    public function index()
    {
        $data['title'] = 'Jurnal Umum (Jurnal Entry)';
        $data['journals'] = $this->model->get_all_headers();
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/index', $data);
        $this->load->view('templates/footer');
    }

    public function create()
    {
        $data['title'] = 'Buat Jurnal Umum Baru';
        $data['journal_no'] = $this->model->get_new_journal_number();
        $data['accounts'] = $this->model->get_postable_accounts();
        $data['currencies'] = $this->model->get_currencies(); 
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/create', $data);
        $this->load->view('templates/footer');
    }

    public function store()
    {
        $this->_validate_journal();
        $this->form_validation->set_rules('currency', 'Mata Uang', 'required');
        $this->form_validation->set_rules('exchange_rate', 'Kurs', 'required|numeric|greater_than[0]');

        if ($this->form_validation->run() == FALSE) {
            $this->create();
        } else {
            $details = $this->input->post('details');
            $rate = (float) $this->input->post('exchange_rate');

            $totals = $this->_calculate_totals($details, $rate);
            
            if ($totals['debit'] != $totals['kredit']) {
                $this->session->set_flashdata('error', 'Gagal: Total Debit ('.$totals['debit'].') dan Kredit ('.$totals['kredit'].') tidak seimbang.');
                $this->create();
                return;
            }

            $header = [
                'journal_no' => $this->input->post('journal_no'),
                'journal_date' => $this->input->post('journal_date'),
                'description' => $this->input->post('description'),
                'currency' => $this->input->post('currency'),
                'exchange_rate' => $rate,
                'total_debit' => $totals['debit'],
                'total_kredit' => $totals['kredit'],
                'total_debit_local' => $totals['debit_local'],
                'total_kredit_local' => $totals['kredit_local'],
                'created_by' => $this->session->userdata('user_id'),
                'status' => 'Draft'
            ];

            $result = $this->model->insert_journal($header, $details);

            if ($result) {
                $this->session->set_flashdata('success', 'Jurnal berhasil disimpan sebagai Draft.');
                redirect('jurnalumum/view/' . $result);
            } else {
                $this->session->set_flashdata('error', 'Gagal menyimpan jurnal.');
                redirect('jurnalumum/create');
            }
        }
    }

    public function view($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = 'View Jurnal: ' . $data['header']->journal_no;
        $data['details'] = $this->model->get_details_by_header_id($id);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/view', $data);
        $this->load->view('templates/footer');
    }

    public function post($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya jurnal status Draft yang bisa di-posting.');
            redirect('jurnalumum/view/' . $id);
        }

        if ($header->total_debit != $header->total_kredit) {
             $this->session->set_flashdata('error', 'Jurnal tidak seimbang (Unbalanced). Posting dibatalkan.');
             redirect('jurnalumum/view/' . $id);
        }

        $result = $this->model->post_journal($id, $this->session->userdata('user_id'));
        if ($result) {
            $this->session->set_flashdata('success', 'Jurnal berhasil di-posting.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mem-posting jurnal.');
        }
        redirect('jurnalumum/view/' . $id);
    }
    
    public function delete($id)
    {
        $result = $this->model->delete_draft_journal($id);
        if ($result) {
            $this->session->set_flashdata('success', 'Jurnal Draft berhasil dihapus.');
            redirect('jurnalumum');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus. Hanya status Draft yang bisa dihapus.');
            redirect('jurnalumum/view/' . $id);
        }
    }

    public function import()
    {
        $data['title'] = 'Impor Detail Jurnal';
        $data['journal_no'] = $this->input->get('journal_no');
        $data['journal_date'] = $this->input->get('journal_date');
        $data['description'] = $this->input->get('description');

        if(empty($data['journal_no'])) {
            $this->session->set_flashdata('error', 'Sesi impor tidak valid. Harap mulai dari form Jurnal Umum.');
            redirect('jurnalumum/create');
        }

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/import', $data);
        $this->load->view('templates/footer');
    }
    
    public function process_import()
    {
        $config['upload_path']   = './uploads/temp/';
        $config['allowed_types'] = 'xls|xlsx|csv';
        $config['max_size']      = 5120; // 5MB
        $config['encrypt_name']  = TRUE;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, TRUE);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file_import')) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('jurnalumum/import?' . http_build_query($this->input->post()));
        } else {
            $file_data = $this->upload->data();
            $file_path = $config['upload_path'] . $file_data['file_name'];

            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
            if ('csv' == $extension) {
                $reader = new Csv();
            } else if ('xls' == $extension) {
                $reader = new Xls();
            } else {
                $reader = new Xlsx();
            }

            try {
                $spreadsheet = $reader->load($file_path);
                $sheet_data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                
                $details = $this->model->process_import_data($sheet_data);
                
                unlink($file_path);

                if (empty($details)) {
                     $this->session->set_flashdata('error', 'File berhasil dibaca tetapi tidak ada data valid yang ditemukan. Pastikan format kolom benar.');
                     redirect('jurnalumum/import?' . http_build_query($this->input->post()));
                }

                $this->session->set_flashdata('imported_details', $details);
                $this->session->set_flashdata('success', 'File berhasil diimpor. Silakan periksa detail di bawah.');
                redirect('jurnalumum/create?' . http_build_query($this->input->post()));

            } catch (Exception $e) {
                unlink($file_path);
                $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
                redirect('jurnalumum/import?' . http_build_query($this->input->post()));
            }
        }
    }

    private function _validate_journal()
    {
        $this->form_validation->set_rules('journal_date', 'Tanggal Jurnal', 'required');
        $this->form_validation->set_rules('description', 'Keterangan', 'required');
        
        $details = $this->input->post('details');
        if (empty($details)) {
            $this->form_validation->set_rules('details_dummy', 'Detail Jurnal', 'required', ['required' => 'Detail Jurnal tidak boleh kosong.']);
        } else {
            foreach ($details as $i => $detail) {
                $this->form_validation->set_rules("details[{$i}][account_id]", "Baris " . ($i+1) . " Akun", 'required');
                $this->form_validation->set_rules("details[{$i}][debit]", "Baris " . ($i+1) . " Debit", 'numeric');
                $this->form_validation->set_rules("details[{$i}][kredit]", "Baris " . ($i+1) . " Kredit", 'numeric');
            }
        }
    }

    private function _calculate_totals($details, $rate = 1.0)
    {
        $total_debit = 0;
        $total_kredit = 0;
        $total_debit_local = 0;
        $total_kredit_local = 0;

        if (!empty($details)) {
            foreach ($details as $d) {
                $debit = (float) $d['debit'];
                $kredit = (float) $d['kredit'];
                
                $total_debit += $debit;
                $total_kredit += $kredit;
                $total_debit_local += $debit * $rate;
                $total_kredit_local += $kredit * $rate;
            }
        }
        return [
            'debit' => $total_debit, 
            'kredit' => $total_kredit,
            'debit_local' => $total_debit_local,
            'kredit_local' => $total_kredit_local
        ];
    }
}