<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Gunakan library dari Composer
use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Scrap_In extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Scrap_In_model');
        $this->load->library('form_validation');
        $this->load->library('session'); // Pastikan session di-load
        $this->load->helper('url');
        // $this->load->helper('auth_helper');
        // check_login();
    }

    public function index() {
        // if (!check_access('scrap_in', 'view')) redirect('denied');
        $data['title'] = lang_line('scrap_in_title', 'Penerimaan Scrap');
        
        // Ambil filter
        $filters = ['search' => $this->input->get('search')];
        $data['filters'] = $filters;

        $data['documents'] = $this->Scrap_In_model->get_all($filters);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_in/list', $data); 
        $this->load->view('templates/footer');
    }

    // --- REVISI: create() ---
    public function create() {
        // if (!check_access('scrap_in', 'add')) redirect('denied');
        $data['title'] = lang_line('scrap_in_create_new', 'Kumpulkan Scrap dari Produksi');
        // Ambil data scrap PER BARIS production_outputs
        $data['uncollected_scrap_rows'] = $this->Scrap_In_model->get_uncollected_production_scrap();
        $data['doc_no'] = $this->Scrap_In_model->generate_doc_no();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_in/collect_form', $data); 
        $this->load->view('templates/footer');
    }

    // --- REVISI: process_collection() ---
    public function process_collection() {
        // if (!check_access('scrap_in', 'add')) redirect('denied');

        $this->form_validation->set_rules('doc_date', 'Tanggal Dokumen', 'required');
        
        // Ambil ID production_outputs yang diceklis
        $selected_output_ids = $this->input->post('selected_outputs'); // Array dari checkbox
        
        if (empty($selected_output_ids) || !$this->form_validation->run()) {
             $this->session->set_flashdata('error', 'Tidak ada baris scrap yang dipilih atau tanggal tidak valid.');
             redirect('Scrap_In/create');
             return;
        }

        // Ambil detail data scrap HANYA untuk ID yang dipilih
        $collected_data = $this->Scrap_In_model->get_production_scrap_details_by_ids($selected_output_ids); 
        // ^^^ Anda perlu membuat fungsi ini di model ^^^ 
        // Fungsi ini mirip get_uncollected.., tapi di-filter by ID dan hasilnya lebih detail
        
        if (empty($collected_data['summary']) || empty($collected_data['sources_map'])) {
             $this->session->set_flashdata('error', 'Gagal mengambil detail scrap yang dipilih.');
             redirect('Scrap_In/create');
             return;
        }

        $header_data = [
            'doc_no' => $this->input->post('doc_no'),
            'doc_date' => $this->input->post('doc_date'),
            'notes' => $this->input->post('notes'),
            'status' => 'Draft',
            'created_by' => $this->session->userdata('user_id'),
        ];
        
        // Kirim summary (total per scrap_id) dan mapping sources ke model
        $result = $this->Scrap_In_model->create_draft_from_collection(
            $header_data, 
            $collected_data['summary'], 
            $collected_data['sources_map'] 
        );

        if ($result) {
            $this->session->set_flashdata('success', 'Draft penerimaan scrap ' . $header_data['doc_no'] . ' berhasil dibuat.');
            redirect('Scrap_In');
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat draft.');
            redirect('Scrap_In/create');
        }
    }

    // --- view(), confirm(), delete() tetap sama ---
    public function view($id) {
        // if (!check_access('scrap_in', 'view')) redirect('denied');
        $data['data'] = $this->Scrap_In_model->get_by_id($id);
        if (empty($data['data'])) {
            $this->session->set_flashdata('error', 'Data tidak ditemukan.');
            redirect('Scrap_In');
        }
        $data['title'] = 'View Penerimaan Scrap: ' . $data['data']['header']->doc_no;
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_in/view_detail', $data); 
        $this->load->view('templates/footer');
    }

    public function confirm($id) {
        // if (!check_access('scrap_in', 'confirm')) redirect('denied');
        $result = $this->Scrap_In_model->confirm_scrap_in($id, $this->session->userdata('user_id'));
        if ($result['status']) { $this->session->set_flashdata('success', $result['message']); } 
        else { $this->session->set_flashdata('error', $result['message']); }
        redirect('Scrap_In/view/' . $id);
    }
    
    public function delete($id) {
        // if (!check_access('scrap_in', 'delete')) redirect('denied');
        $result = $this->Scrap_In_model->delete_draft($id);
        if ($result['status']) { $this->session->set_flashdata('success', $result['message']); } 
        else { $this->session->set_flashdata('error', $result['message']); }
        redirect('Scrap_In');
    }

    // --- FUNGSI BARU: EXPORT PDF ---
    public function export_pdf($id) {
        $data['data'] = $this->Scrap_In_model->get_by_id($id);
        if (empty($data['data'])) {
            $this->session->set_flashdata('error', 'Data not found.');
            redirect('Scrap_In');
        }
        
        $data['title'] = 'Bukti Penerimaan Scrap - ' . $data['data']['header']->doc_no;
        $html = $this->load->view('scrap_in/print_pdf', $data, TRUE);
        
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('ScrapIn-' . $data['data']['header']->doc_no . '.pdf', 'I'); 
    }

    // --- FUNGSI BARU: EXPORT EXCEL ---
     public function export_excel() {
        $filters = ['search' => $this->input->get('search')];
        $documents = $this->Scrap_In_model->get_all($filters);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $sheet->setCellValue('A1', 'No. Dokumen');
        $sheet->setCellValue('B1', 'Tgl Dokumen');
        $sheet->setCellValue('C1', 'Dibuat Oleh');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Catatan');

        $row = 2;
        foreach ($documents as $doc) {
            $sheet->setCellValue('A' . $row, $doc->doc_no);
            $sheet->setCellValue('B' . $row, date('d-m-Y', strtotime($doc->doc_date)));
            $sheet->setCellValue('C' . $row, $doc->created_by_name);
            $sheet->setCellValue('D' . $row, $doc->status);
            $sheet->setCellValue('E' . $row, $doc->notes);
            $row++;
        }

        foreach(range('A','E') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'scrap_in_export_' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    // --- FUNGSI BARU: Ambil detail scrap by ID (untuk model) ---
    // Anda perlu membuat fungsi ini di model
    

}