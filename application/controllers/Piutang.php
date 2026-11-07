<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Piutang — Laporan Daftar Piutang (Account Receivable monitoring).
 *
 * Read-only. Menampilkan faktur piutang outstanding + aging, pengiriman
 * (FG Delivery / Scrap Out) yang sudah dikirim tapi belum difaktur, dan rekap
 * per customer. Penerimaan tetap lewat modul ArReceipt.
 */
class Piutang extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        if (!check_access('piutang', 'view')) {
            show_error('Anda tidak memiliki akses ke Daftar Piutang.', 403, 'Akses Ditolak');
        }
        $this->load->model('Piutang_model', 'model');
    }

    private function _filters()
    {
        $submitted = $this->input->get('f') !== null;
        return [
            'customer_id'      => $this->input->get('customer_id'),
            'payment_status'   => $this->input->get('payment_status'),
            'only_outstanding' => $submitted ? ($this->input->get('only_outstanding') === '1') : true,
        ];
    }

    public function index()
    {
        $filters = $this->_filters();

        $data['title']               = lang_line('sidebar_piutang_list', 'Daftar Piutang') . ' (Account Receivable)';
        $data['filters']             = $filters;
        $data['customers']           = $this->model->get_customers();
        $data['totals']              = $this->model->get_totals($filters);
        $data['summary']             = $this->model->get_summary_by_customer($filters);
        $data['invoices']            = $this->model->get_invoice_list($filters);
        $data['uninvoiced_delivery'] = $this->model->get_uninvoiced_delivery($filters);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/piutang/index', $data);
        $this->load->view('templates/footer');
    }

    public function export_excel()
    {
        $filters  = $this->_filters();
        $invoices = $this->model->get_invoice_list($filters);
        $pending  = $this->model->get_uninvoiced_delivery($filters);

        $spreadsheet = new Spreadsheet();

        // --- Sheet 1: Faktur Piutang ---
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Faktur Piutang');
        $sheet->setCellValue('A1', 'DAFTAR PIUTANG - FAKTUR (ACCOUNT RECEIVABLE)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $head = ['No. Faktur', 'Tgl Faktur', 'Jatuh Tempo', 'Customer', 'Sumber', 'Curr', 'Total Tagihan', 'Diterima', 'Sisa Piutang', 'Sisa (IDR)', 'Status', 'Umur (hari)'];
        $col = 'A'; $row = 3;
        foreach ($head as $h) { $sheet->setCellValue($col . $row, $h); $col++; }
        $sheet->getStyle('A3:L3')->getFont()->setBold(true);

        $row = 4;
        foreach ($invoices as $i) {
            $sheet->setCellValueExplicit('A' . $row, $i->invoice_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('B' . $row, $i->invoice_date);
            $sheet->setCellValue('C' . $row, $i->due_date);
            $sheet->setCellValue('D' . $row, $i->nama_customer);
            $sheet->setCellValue('E' . $row, $i->source_type);
            $sheet->setCellValue('F' . $row, $i->currency);
            $sheet->setCellValue('G' . $row, (float) $i->total_invoice);
            $sheet->setCellValue('H' . $row, (float) $i->total_paid);
            $sheet->setCellValue('I' . $row, (float) $i->outstanding);
            $sheet->setCellValue('J' . $row, (float) $i->outstanding_local);
            $sheet->setCellValue('K' . $row, $i->payment_status);
            $sheet->setCellValue('L' . $row, ($i->outstanding > 0.01 && $i->days_overdue > 0) ? (int) $i->days_overdue : 0);
            $row++;
        }
        $sheet->getStyle('G4:J' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'L') as $c) { $sheet->getColumnDimension($c)->setAutoSize(true); }

        // --- Sheet 2: Pengiriman belum difaktur ---
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Belum Difaktur');
        $sheet2->setCellValue('A1', 'PENGIRIMAN SUDAH DIKIRIM TAPI BELUM DIFAKTUR');
        $sheet2->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $head2 = ['Sumber', 'No. Dokumen', 'Tanggal', 'Customer', 'Referensi', 'Curr', 'Nilai (estimasi)', 'Umur (hari)'];
        $col = 'A'; $row = 3;
        foreach ($head2 as $h) { $sheet2->setCellValue($col . $row, $h); $col++; }
        $sheet2->getStyle('A3:H3')->getFont()->setBold(true);
        $row = 4;
        foreach ($pending as $d) {
            $sheet2->setCellValue('A' . $row, $d->source_type);
            $sheet2->setCellValueExplicit('B' . $row, $d->doc_no, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet2->setCellValue('C' . $row, $d->doc_date);
            $sheet2->setCellValue('D' . $row, $d->nama_customer);
            $sheet2->setCellValue('E' . $row, $d->ref_no);
            $sheet2->setCellValue('F' . $row, $d->currency);
            $sheet2->setCellValue('G' . $row, (float) $d->est_value);
            $sheet2->setCellValue('H' . $row, (int) $d->age_days);
            $row++;
        }
        $sheet2->getStyle('G4:G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'H') as $c) { $sheet2->getColumnDimension($c)->setAutoSize(true); }

        $spreadsheet->setActiveSheetIndex(0);
        $writer = new Xlsx($spreadsheet);
        $filename = 'Daftar-Piutang-' . date('Ymd-His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
