<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Stock_material extends MY_Controller {

    private $item_types = ['material', 'semifinished'];

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Stock_ledger_model');
        $this->load->library('pagination');
        $this->load->helper('url');
        $this->load->library('session');
    }

    public function index() {
        if (!check_access('stock_ledger', 'view')) {
            show_error('Anda tidak memiliki izin untuk mengakses halaman ini.', 403);
            return;
        }

        $current_month = date('m');
        $current_year = date('Y');
        $month = $this->input->get('month') ?: $current_month;
        $year = $this->input->get('year') ?: $current_year;
        $search = $this->input->get('search');

        if (!checkdate((int)$month, 1, (int)$year)) {
            $month = $current_month;
            $year = $current_year;
        }

        $start_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        $filters = [
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'warehouse_type' => $this->item_types,
            'search'         => $search
        ];

        $report_data = $this->Stock_ledger_model->get_stock_summary_report($filters, 0, 0);

        $data = [
            'title'        => lang_line('title_stock_material'),
            'report'       => $report_data,
            'filters'      => $filters,
            'selected_month' => $month,
            'selected_year' => $year,
            'item_types'   => ['material' => 'Material', 'semifinished' => 'Semifinished Goods'],
            'total_rows'   => count($report_data)
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stock_ledger/index', $data);
        $this->load->view('templates/footer');
    }

    public function export_excel() {
        if (!check_access('stock_ledger', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
            return;
        }

        $current_month = date('m');
        $current_year = date('Y');
        $month = $this->input->get('month') ?: $current_month;
        $year = $this->input->get('year') ?: $current_year;
        $search = $this->input->get('search');

        $start_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        $filters = [
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'warehouse_type' => $this->item_types,
            'search'         => $search
        ];

        $report_data = $this->Stock_ledger_model->get_stock_summary_report($filters, 0, 0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock Material');

        $sheet->setCellValue('A1', 'LAPORAN STOK MATERIAL & BARANG SETENGAH JADI');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $sheet->setCellValue('A2', 'Periode: ' . date('F Y', strtotime($start_date)));
        $sheet->mergeCells('A2:J2');

        $headers = ['Kode Item', 'Nama Item', 'Tipe', 'Satuan', 'Saldo Awal', 'Masuk', 'Keluar', 'Saldo Akhir', 'Nilai (Rp)', 'Status'];
        $col = 'A';
        $row = 4;
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $row, $header);
            $col++;
        }

        $row = 5;
        foreach ($report_data as $item) {
            $sheet->setCellValue('A' . $row, $item->item_code ?? '-');
            $sheet->setCellValue('B' . $row, $item->item_name ?? '-');
            $sheet->setCellValue('C' . $row, $item->item_type ?? '-');
            $sheet->setCellValue('D' . $row, $item->unit ?? '-');
            $sheet->setCellValue('E' . $row, $item->beginning_balance ?? 0);
            $sheet->setCellValue('F' . $row, $item->total_in ?? 0);
            $sheet->setCellValue('G' . $row, $item->total_out ?? 0);
            $sheet->setCellValue('H' . $row, $item->ending_balance ?? 0);
            $sheet->setCellValue('I' . $row, $item->total_value ?? 0);
            $sheet->setCellValue('J' . $row, $item->status ?? '-');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Stock_Material_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
    }

    public function export_pdf() {
        if (!check_access('stock_ledger', 'view')) {
            show_error('Anda tidak memiliki izin untuk export data.', 403);
            return;
        }

        $current_month = date('m');
        $current_year = date('Y');
        $month = $this->input->get('month') ?: $current_month;
        $year = $this->input->get('year') ?: $current_year;

        $start_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        $filters = [
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'warehouse_type' => $this->item_types,
            'search'         => ''
        ];

        $report_data = $this->Stock_ledger_model->get_stock_summary_report($filters, 0, 0);

        $data = [
            'title'  => lang_line('title_stock_material'),
            'period' => date('F Y', strtotime($start_date)),
            'report' => $report_data
        ];

        $html = $this->load->view('stock_ledger/pdf_template', $data, true);

        $options = new Options();
        $options->set(['isRemoteEnabled' => true]);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'Stock_Material_' . date('Ymd_His') . '.pdf';
        $dompdf->stream($filename);
    }
}
?>
