<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Po_report extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Po_report_model');
        $this->load->helper(['url', 'form']);
    }

    /**
     * 
     * PO Report listing - summary view
     */
    public function index() {
        if (!check_access('po_report', 'view')) {
            show_error('Tidak ada izin untuk mengakses PO Report.', 403);
        }

        $filters = [
            'search'         => $this->input->get('search', TRUE),
            'supplier_id'    => $this->input->get('supplier_id', TRUE),
            'status'         => $this->input->get('status', TRUE),
            'receipt_status' => $this->input->get('receipt_status', TRUE),
            'type'           => $this->input->get('type', TRUE),
            'date_from'      => $this->input->get('date_from', TRUE),
            'date_to'        => $this->input->get('date_to', TRUE),
        ];

        // Redesign 2026-11-09: ringkasan = rekap hasil filter saat ini (laporan),
        // bukan lagi get_summary_stats() yang menghitung ganda & memakai status
        // 'Open' yang tidak pernah ada. Daftar dipaginasi 25 baris.
        $all = $this->Po_report_model->get_all($filters);
        $summary = ['count' => count($all), 'value' => 0.0, 'approved' => 0, 'Not Received' => 0, 'Partial' => 0, 'Complete' => 0, 'lead_sum' => 0, 'lead_n' => 0];
        foreach ($all as $r) {
            $summary['value'] += (float) $r->total_value;
            if (in_array($r->status, ['Confirmed', 'Closed'], true)) {
                $summary['approved']++;
                $summary[$r->receipt_status] = ($summary[$r->receipt_status] ?? 0) + 1;
            }
            if (!empty($r->first_grn_date)) { $summary['lead_sum'] += (int) $r->days_to_warehouse_in; $summary['lead_n']++; }
        }
        $summary['lead_avg'] = $summary['lead_n'] ? round($summary['lead_sum'] / $summary['lead_n'], 1) : null;

        $per_page = 25;
        $total_pages = max(1, (int) ceil(count($all) / $per_page));
        $page = min(max(1, (int) $this->input->get('page')), $total_pages);

        $data = [
            'title'       => lang_line('page_po_report_title'),
            'filters'     => $filters,
            'pos'         => array_slice($all, ($page - 1) * $per_page, $per_page),
            'total'       => count($all),
            'page'        => $page,
            'total_pages' => $total_pages,
            'summary'     => $summary,
            'suppliers'   => $this->Po_report_model->get_suppliers(),
        ];

        $this->_render('po_report/index', $data);
    }

    /**
     * PO detail - show line items and receipt history
     */
    public function detail($po_id = null) {
        if (!check_access('po_report', 'view')) {
            show_error('Tidak ada izin.', 403);
        }

        if (empty($po_id) || !is_numeric($po_id)) {
            show_404();
        }

        $po_data = $this->Po_report_model->get_po_detail($po_id);
        if (!$po_data) {
            show_404();
        }

        $data = [
            'title' => lang_line('po_report_detail_title', 'Detail PO') . ' - ' . $po_data['header']->po_number,
            'po_header' => $po_data['header'],
            'po_details' => $po_data['details'],
            'grns' => $po_data['grns'],
            'receipts' => $po_data['receipts'],
        ];

        $this->_render('po_report/detail', $data);
    }

    /**
     * GRN detail modal / page
     */
    public function grn_detail($po_id = null, $grn_id = null) {
        if (empty($po_id) || !is_numeric($po_id)) {
            show_404();
        }

        $grn_items = $this->Po_report_model->get_grn_details($po_id, $grn_id);
        if (empty($grn_items)) {
            show_404();
        }

        header('Content-Type: application/json');
        echo json_encode($grn_items);
    }

    /**
     * Export to PDF (optional)
     */
    public function export_pdf($po_id = null) {
        if (!check_access('po_report', 'view')) {
            show_error('Tidak ada izin.', 403);
        }

        if (empty($po_id) || !is_numeric($po_id)) {
            show_404();
        }

        $po_data = $this->Po_report_model->get_po_detail($po_id);
        if (!$po_data) {
            show_404();
        }

        // Basic PDF export (you can enhance this with dompdf if needed)
        $data = [
            'po_header' => $po_data['header'],
            'po_details' => $po_data['details'],
            'grns' => $po_data['grns'],
            'title' => 'PO Report - ' . $po_data['header']->po_number
        ];

        $this->load->view('po_report/pdf_export', $data);
    }

    private function _render($view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }
}
?>
