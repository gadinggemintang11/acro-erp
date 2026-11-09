<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class QC_production extends MY_Controller {

    protected $module_name = 'QC Production';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['QC_model', 'Production_model']);
        $this->load->helper('url');
        $this->load->library('session');
    }

    /**
     * Halaman index - daftar QC Production
     */
    public function index() {
        if (!check_access('qc_production', 'view')) {
            show_error('Tidak ada izin.', 403);
            return;
        }

        // Get filters dari input
        $filters = [
            'search' => $this->input->get('search'),
            'stage' => $this->input->get('stage'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];

        // Get data QC
        $qc_records = $this->QC_model->get_all_qc($filters);
        $qc_stats = $this->QC_model->get_qc_statistics();

        $data = [
            'title' => lang_line('title_qcp_index'),
            'module_name' => $this->module_name,
            'qc_records' => $qc_records,
            'qc_stats' => $qc_stats,
            'filters' => $filters,
            'stages' => ['cutting', 'sewing', 'assembling', 'finishing']
        ];

        $this->_load_views('qc_production/index', $data);
    }

    /**
     * Halaman detail QC
     */
    public function view($output_id) {
        if (!check_access('qc_production', 'view')) {
            show_error('Tidak ada izin.', 403);
            return;
        }

        if (empty($output_id) || !is_numeric($output_id)) {
            show_404();
            return;
        }

        // Get detail QC
        $qc_detail = $this->QC_model->get_qc_detail($output_id);
        if (!$qc_detail) {
            show_404();
            return;
        }

        // Get input materials untuk stage ini
        $stage_inputs = $this->QC_model->get_qc_stage_inputs(
            $qc_detail->production_header_id,
            $qc_detail->stage_name
        );

        // Parse size breakdown jika ada
        $size_breakdown = [];
        if (!empty($qc_detail->size_breakdown)) {
            $size_breakdown = json_decode($qc_detail->size_breakdown, true) ?? [];
        }

        $data = [
            'title' => lang_line('title_qcp_detail'),
            'qc_detail' => $qc_detail,
            'stage_inputs' => $stage_inputs,
            'size_breakdown' => $size_breakdown
        ];

        $this->_load_views('qc_production/detail', $data);
    }

    /**
     * AJAX - Get QC photo preview
     */
    public function ajax_get_photo($output_id) {
        header('Content-Type: application/json');

        if (!check_access('qc_production', 'view')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin.']);
            exit;
        }

        $qc_detail = $this->QC_model->get_qc_detail($output_id);
        if (!$qc_detail || empty($qc_detail->qc_photo_url)) {
            echo json_encode(['success' => false, 'message' => 'Foto tidak ditemukan.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'photo_url' => $qc_detail->qc_photo_url,
            'description' => $qc_detail->qc_description
        ]);
        exit;
    }

    /**
     * Export QC ke PDF
     */
    public function export_pdf($output_id) {
        if (!check_access('qc_production', 'view')) {
            show_error('Tidak ada izin.', 403);
            return;
        }

        $qc_detail = $this->QC_model->get_qc_detail($output_id);
        if (!$qc_detail) {
            show_404();
            return;
        }

        $stage_inputs = $this->QC_model->get_qc_stage_inputs(
            $qc_detail->production_header_id,
            $qc_detail->stage_name
        );

        $size_breakdown = [];
        if (!empty($qc_detail->size_breakdown)) {
            $size_breakdown = json_decode($qc_detail->size_breakdown, true) ?? [];
        }

        $data = [
            'qc_detail' => $qc_detail,
            'stage_inputs' => $stage_inputs,
            'size_breakdown' => $size_breakdown
        ];

        $this->_generate_pdf('qc_production/qc_pdf_template', $data, 'QC_' . str_replace('/', '-', $qc_detail->production_number) . '_' . $qc_detail->stage_name);
    }

    /**
     * Helper untuk generate PDF
     */
    private function _generate_pdf($view_path, $data, $filename_prefix) {
        $this->load->library('pdf');
        
        try {
            $html = $this->load->view($view_path, $data, true);
            
            // Gunakan mPDF untuk generate PDF
            $old_error = error_reporting();
            error_reporting(E_ERROR | E_PARSE);
            
            $temp_dir = FCPATH . 'uploads/temp';
            if (!is_dir($temp_dir)) {
                @mkdir($temp_dir, 0777, true);
            }
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'tempDir' => $temp_dir,
                'default_font' => 'dejavusans'
            ]);
            
            $mpdf->WriteHTML($html);
            
            error_reporting($old_error);
            
            $filename = $filename_prefix . ".pdf";
            $mpdf->Output($filename, 'I');
            exit;
        } catch (Exception $e) {
            log_message('error', 'PDF Exception: ' . $e->getMessage());
            show_error('Gagal membuat file PDF: ' . $e->getMessage());
        }
    }

    /**
     * Helper untuk load views dengan template
     */
    private function _load_views($main_view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }
}
