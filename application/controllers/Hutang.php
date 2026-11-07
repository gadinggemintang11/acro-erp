<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Hutang — Laporan Daftar Hutang (Account Payable monitoring).
 *
 * Read-only. Menampilkan faktur hutang outstanding + aging, GRN (PO) yang sudah
 * diterima tapi belum difaktur, dan rekap per supplier. Pembayaran tetap lewat
 * modul ApPayment.
 */
class Hutang extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        if (!check_access('hutang', 'view')) {
            show_error('Anda tidak memiliki akses ke Daftar Hutang.', 403, 'Akses Ditolak');
        }
        $this->load->model('Hutang_model', 'model');
    }

    /**
     * Build filter array dari query string GET.
     */
    private function _filters()
    {
        // 'f=1' menandakan form filter sudah disubmit. Saat pertama buka (belum submit)
        // default-nya tampilkan hanya yang belum lunas; setelah submit, ikuti checkbox apa adanya.
        $submitted = $this->input->get('f') !== null;
        return [
            'supplier_id'      => $this->input->get('supplier_id'),
            'payment_status'   => $this->input->get('payment_status'),
            'only_outstanding' => $submitted ? ($this->input->get('only_outstanding') === '1') : true,
        ];
    }

    public function index()
    {
        $filters = $this->_filters();

        $data['title']          = lang_line('sidebar_hutang_list', 'Daftar Hutang') . ' (Account Payable)';
        $data['filters']        = $filters;
        $data['suppliers']      = $this->model->get_suppliers();
        $data['totals']         = $this->model->get_totals($filters);
        $data['summary']        = $this->model->get_summary_by_supplier($filters);
        $data['invoices']       = $this->model->get_invoice_list($filters);
        $data['uninvoiced_grn'] = $this->model->get_uninvoiced_grn($filters);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/hutang/index', $data);
        $this->load->view('templates/footer');
    }

    public function export_excel()
    {
        $filters  = $this->_filters();
        $invoices = $this->model->get_invoice_list($filters);
        $pending  = $this->model->get_uninvoiced_grn($filters);

        $spreadsheet = new Spreadsheet();

        // --- Sheet 1: Faktur Hutang ---
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Faktur Hutang');
        $sheet->setCellValue('A1', 'DAFTAR HUTANG - FAKTUR (ACCOUNT PAYABLE)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $head = ['No. Faktur', 'Tgl Faktur', 'Jatuh Tempo', 'Supplier', 'No. PO', 'No. GRN', 'Total Tagihan', 'Dibayar', 'Sisa Hutang', 'Status', 'Umur (hari)'];
        $col = 'A'; $row = 3;
        foreach ($head as $h) { $sheet->setCellValue($col . $row, $h); $col++; }
        $sheet->getStyle('A3:K3')->getFont()->setBold(true);

        $row = 4;
        foreach ($invoices as $i) {
            $sheet->setCellValueExplicit('A' . $row, $i->invoice_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $row, $i->invoice_date);
            $sheet->setCellValue('C' . $row, $i->due_date);
            $sheet->setCellValue('D' . $row, $i->nama_supplier);
            $sheet->setCellValue('E' . $row, $i->po_numbers);
            $sheet->setCellValue('F' . $row, $i->grn_numbers);
            $sheet->setCellValue('G' . $row, (float) $i->total_invoice);
            $sheet->setCellValue('H' . $row, (float) $i->total_paid);
            $sheet->setCellValue('I' . $row, (float) $i->outstanding);
            $sheet->setCellValue('J' . $row, $i->payment_status);
            $sheet->setCellValue('K' . $row, ($i->outstanding > 0.01 && $i->days_overdue > 0) ? (int) $i->days_overdue : 0);
            $row++;
        }
        $sheet->getStyle('G4:I' . $row)->getNumberFormat()->setFormatCode('#,##0');
        foreach (range('A', 'K') as $c) { $sheet->getColumnDimension($c)->setAutoSize(true); }

        // --- Sheet 2: GRN belum difaktur ---
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('GRN Belum Difaktur');
        $sheet2->setCellValue('A1', 'GRN SUDAH DITERIMA TAPI BELUM DIFAKTUR');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $head2 = ['No. GRN', 'Tgl GRN', 'Supplier', 'No. PO', 'Nilai GRN', 'Umur (hari)'];
        $col = 'A'; $row = 3;
        foreach ($head2 as $h) { $sheet2->setCellValue($col . $row, $h); $col++; }
        $sheet2->getStyle('A3:F3')->getFont()->setBold(true);
        $row = 4;
        foreach ($pending as $g) {
            $sheet2->setCellValueExplicit('A' . $row, $g->grn_number, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet2->setCellValue('B' . $row, $g->grn_date);
            $sheet2->setCellValue('C' . $row, $g->nama_supplier);
            $sheet2->setCellValue('D' . $row, $g->po_numbers);
            $sheet2->setCellValue('E' . $row, (float) $g->grn_value);
            $sheet2->setCellValue('F' . $row, (int) $g->age_days);
            $row++;
        }
        $sheet2->getStyle('E4:E' . $row)->getNumberFormat()->setFormatCode('#,##0');
        foreach (range('A', 'F') as $c) { $sheet2->getColumnDimension($c)->setAutoSize(true); }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $filename = 'Daftar-Hutang-' . date('Ymd-His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
