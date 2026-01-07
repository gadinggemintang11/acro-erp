<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Scrap_Stock extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Scrap_Stock_model');
        $this->load->library(['pagination', 'session']);
        $this->load->helper(['url', 'form']);
    }

    /* ============================================================
       🔹 LAPORAN RINCI STOK SCRAP (PER BATCH)
       ============================================================ */
    public function index() {
        check_access('scrap_stock', 'view');
        $data['title'] = 'Laporan Rinci Stok Scrap (per Batch)';

        $filters = ['search' => trim($this->input->get('search', TRUE))];
        $per_page = 25;
        $offset   = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 0;

        $total_rows = $this->Scrap_Stock_model->count_detailed_stock($filters);
        $data['total_rows'] = $total_rows;

        // Pagination config
        $config = [
            'base_url' => base_url('scrap_stock/index'),
            'total_rows' => $total_rows,
            'per_page' => $per_page,
            'uri_segment' => 3,
            'attributes' => ['class' => 'page-link btn-load'],
            'full_tag_open' => '<ul class="pagination justify-content-end">',
            'full_tag_close' => '</ul>',
            'cur_tag_open' => '<li class="page-item active"><span class="page-link">',
            'cur_tag_close' => '</span></li>',
            'num_tag_open' => '<li class="page-item">',
            'num_tag_close' => '</li>',
        ];
        $this->pagination->initialize($config);

        $data['pagination'] = $this->pagination->create_links();
        $data['stock_data'] = $this->Scrap_Stock_model->get_detailed_stock($filters, $per_page, $offset);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_stock/list', $data);
        $this->load->view('templates/footer');
    }

    /* ============================================================
       🔹 RINGKASAN STOK SCRAP
       ============================================================ */
    public function summary() {
        check_access('scrap_stock', 'view');
        $data['title'] = 'Ringkasan Stok Scrap';

        $filters = ['search' => trim($this->input->get('search', TRUE))];
        $per_page = 25;
        $offset   = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 0;

        $total_rows = $this->Scrap_Stock_model->count_summary_stock($filters);
        $data['total_rows'] = $total_rows;

        // Pagination setup
        $config = [
            'base_url' => base_url('scrap_stock/summary'),
            'total_rows' => $total_rows,
            'per_page' => $per_page,
            'uri_segment' => 3,
            'attributes' => ['class' => 'page-link btn-load'],
        ];
        $this->pagination->initialize($config);
        $data['pagination'] = $this->pagination->create_links();

        // Ambil data ringkasan stok
        $data['stock_summary'] = $this->Scrap_Stock_model->get_stock_summary();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_stock/summary_list', $data);
        $this->load->view('templates/footer');
    }

    /* ============================================================
       🔹 DETAIL TRACEABILITY SCRAP
       ============================================================ */
    public function detail($scrap_id = null) {
        check_access('scrap_stock', 'view');
        // Debug
        log_message('debug', 'Scrap_Stock::detail() called with scrap_id = ' . $scrap_id);
        
        if (empty($scrap_id) || !is_numeric($scrap_id)) {
            log_message('error', 'Scrap_Stock::detail() - Invalid scrap_id');
            show_404();
            return;
        }

        $data['title'] = 'Detail Traceability Scrap';

        // Ambil data summary berdasarkan ID (sama persis dengan perhitungan di list)
        $data['scrap'] = $this->Scrap_Stock_model->get_summary_stock_by_id($scrap_id);
        
        log_message('debug', 'Scrap data: ' . json_encode($data['scrap']));

        // Jika tidak ditemukan
        if (empty($data['scrap'])) {
            $data['scrap'] = null;
        }

        // Ambil riwayat masuk & keluar
        $data['in_history']  = $this->Scrap_Stock_model->get_in_history($scrap_id);
        $data['out_history'] = $this->Scrap_Stock_model->get_out_history($scrap_id);
        
        log_message('debug', 'Loading view scrap_stock/detail_trace');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('scrap_stock/detail_trace', $data);
        $this->load->view('templates/footer');
    }

    /* ============================================================
       🔹 EXPORT EXCEL
       ============================================================ */
    public function export_excel() {
        $filters = ['search' => trim($this->input->get('search', TRUE))];
        $rows = $this->Scrap_Stock_model->get_detailed_stock($filters, 0, 0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Laporan Rinci Stok Scrap (per Batch)');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Kode Scrap', 'Nama Scrap', 'Satuan', 'Qty Masuk', 'Qty Keluar', 'Qty Tersedia'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '3', $h);
            $col++;
        }

        $row = 4;
        foreach ($rows as $r) {
            $sheet->setCellValue("A{$row}", $r->scrap_code ?? '-');
            $sheet->setCellValue("B{$row}", $r->scrap_name ?? '-');
            $sheet->setCellValue("C{$row}", $r->satuan_desc ?? '-');
            $sheet->setCellValue("D{$row}", (float)($r->total_in ?? 0));
            $sheet->setCellValue("E{$row}", (float)($r->total_out ?? 0));
            $sheet->setCellValue("F{$row}", (float)($r->total_available ?? 0));
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'laporan_rinci_stok_scrap_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $writer->save('php://output');
        exit;
    }

    /* ============================================================
       🔹 EXPORT PDF
       ============================================================ */
    public function export_pdf() {
        $filters = ['search' => trim($this->input->get('search', TRUE))];
        $data = [
            'title' => 'Laporan Rinci Stok Scrap (per Batch)',
            'stock_data' => $this->Scrap_Stock_model->get_detailed_stock($filters, 0, 0)
        ];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $html = $this->load->view('scrap_stock/print_pdf', $data, true);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('laporan_rinci_stok_scrap.pdf', ['Attachment' => 0]);
        exit;
    }
}
