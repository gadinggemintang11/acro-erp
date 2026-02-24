<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class Reports extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        // Pastikan user sudah login (sesuai standar Anda)
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        
        $this->load->model('Report_model');
        $this->load->helper('form');
        $this->load->library('form_validation');
    }

    /**
     * Halaman Index - Menu Reports
     */
    public function index()
    {
        $data['title'] = lang_line('reports_title', 'Laporan');
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('reports/index', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Halaman Laporan Laba Rugi
     */
    public function profit_loss()
    {
        // Ambil filter tanggal dari URL (method GET)
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date');
        $export = $this->input->get('export');

        // Beri nilai default jika kosong (misal: bulan ini)
        if (empty($startDate)) {
            $startDate = date('Y-m-01'); // Hari pertama bulan ini
        }
        if (empty($endDate)) {
            $endDate = date('Y-m-t'); // Hari terakhir bulan ini
        }

        // Ambil data dari "otak" (Report_model)
        $data['report_data'] = $this->Report_model->get_profit_loss($startDate, $endDate);

        // Kirim data filter kembali ke view untuk ditampilkan di form
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        
        // Data untuk template
        $data['title'] = lang_line('reports_profit_loss', 'Laporan Laba Rugi');

        if ($export) {
            $this->_export_data('accounting/reports/laba_rugi', $data, $export);
        } else {
            // Load view
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('accounting/reports/laba_rugi', $data); // View utama kita
            $this->load->view('templates/footer');
        }
    }
    public function balance_sheet()
    {
        // Ambil filter tanggal dari URL (method GET)
        $asOfDate = $this->input->get('as_of_date');
        $export = $this->input->get('export');

        // Beri nilai default jika kosong (hari ini)
        if (empty($asOfDate)) {
            $asOfDate = date('Y-m-d'); // Hari ini
        }

        // Ambil data dari "otak" (Report_model)
        $data['report_data'] = $this->Report_model->get_balance_sheet($asOfDate);

        // Kirim data filter kembali ke view
        $data['as_of_date'] = $asOfDate;
        
        // Data untuk template
        $data['title'] = lang_line('reports_balance_sheet', 'Laporan Neraca');

        if ($export) {
            $this->_export_data('accounting/reports/neraca', $data, $export);
        } else {
            // Load view
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('accounting/reports/neraca', $data); // View baru kita
            $this->load->view('templates/footer');
        }
    }
    public function general_ledger($accountId)
    {
        // 1. Validasi ID Akun
        if (!$accountId || !is_numeric($accountId)) {
            show_404();
            return;
        }

        // 2. Ambil filter tanggal dari URL (method GET)
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date');
        $export = $this->input->get('export');

        // Beri nilai default jika kosong (misal: bulan ini)
        if (empty($startDate)) {
            $startDate = date('Y-m-01'); // Hari pertama bulan ini
        }
        if (empty($endDate)) {
            $endDate = date('Y-m-t'); // Hari terakhir bulan ini
        }

        try {
            // 3. Ambil data dari "otak" (Report_model)
            $data['report_data'] = $this->Report_model->get_general_ledger($accountId, $startDate, $endDate);
            
            // 4. Kirim data filter kembali ke view
            $data['start_date'] = $startDate;
            $data['end_date'] = $endDate;
            
            // Data untuk template
            $data['title'] = 'Buku Besar: ' . $data['report_data']['info_akun']->account_name;
            $data['subtitle'] = $data['report_data']['info_akun']->account_code;

            if ($export) {
                $this->_export_data('accounting/reports/general_ledger', $data, $export);
            } else {
                // 5. Load view
                $this->load->view('templates/header', $data);
                $this->load->view('templates/sidebar', $data);
                $this->load->view('templates/topbar', $data);
                $this->load->view('accounting/reports/general_ledger', $data); // View baru kita
                $this->load->view('templates/footer');
            }

        } catch (Exception $e) {
            // Jika akun tidak ditemukan atau ada error
            show_error($e->getMessage(), 500);
        }
    }
    public function trial_balance()
    {
        // Ambil filter tanggal dari URL (method GET)
        $asOfDate = $this->input->get('as_of_date');
        $export = $this->input->get('export');

        // Beri nilai default jika kosong (hari ini)
        if (empty($asOfDate)) {
            $asOfDate = date('Y-m-d'); // Hari ini
        }

        // Ambil data dari "otak" (Report_model)
        $data['report_data'] = $this->Report_model->get_trial_balance($asOfDate);

        // Kirim data filter kembali ke view
        $data['as_of_date'] = $asOfDate;
        
        // Data untuk template
        $data['title'] = lang_line('reports_trial_balance', 'Laporan Neraca Saldo (Trial Balance)');

        if ($export) {
            $this->_export_data('accounting/reports/trial_balance', $data, $export);
        } else {
            // Load view
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('accounting/reports/trial_balance', $data); // View baru kita
            $this->load->view('templates/footer');
        }
    }
    public function cash_flow()
    {
        // Ambil filter tanggal dari URL (method GET)
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date');
        $export = $this->input->get('export');

        // Beri nilai default jika kosong (misal: bulan ini)
        if (empty($startDate)) {
            $startDate = date('Y-m-01'); // Hari pertama bulan ini
        }
        if (empty($endDate)) {
            $endDate = date('Y-m-t'); // Hari terakhir bulan ini
        }

        // Ambil data dari "otak" (Report_model)
        $data['report_data'] = $this->Report_model->get_cash_flow($startDate, $endDate);

        // Kirim data filter kembali ke view
        $data['start_date'] = $startDate;
        $data['end_date'] = $endDate;
        
        // Data untuk template
        $data['title'] = lang_line('reports_cash_flow', 'Laporan Arus Kas (Metode Langsung)');

        if ($export) {
            $this->_export_data('accounting/reports/cash_flow', $data, $export);
        } else {
            // Load view
            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('templates/topbar', $data);
            $this->load->view('accounting/reports/cash_flow', $data); // View baru kita
            $this->load->view('templates/footer');
        }
    }

    /**
     * Halaman Detail (Traceability) untuk Arus Kas [BARU]
     * * URL: reports/cash_flow_detail
     * * Query Params: ?start_date=...&end_date=...&offset_account_id=...
     */
    public function cash_flow_detail()
    {
        $startDate = $this->input->get('start_date');
        $endDate = $this->input->get('end_date');
        $offset_account_id = $this->input->get('offset_account_id');

        if (!$startDate || !$endDate || !$offset_account_id) {
            show_error('Parameter tidak lengkap untuk detail arus kas.', 400);
            return;
        }

    
        $queryString = "?start_date={$startDate}&end_date={$endDate}";
        redirect('reports/general_ledger/' . $offset_account_id . $queryString);
    }

    private function _export_data($view, $data, $type)
    {
        $filename = url_title($data['title'], 'underscore', TRUE) . '_' . date('Ymd');

        if ($type == 'pdf') {
            // Load the specific PDF template view
            $view_pdf = 'export_templates/' . str_replace('accounting/reports/', '', $view) . '_pdf';
            $html = $this->load->view($view_pdf, $data, TRUE);
            
            $mpdf = new Mpdf(['mode' => 'utf-8', 'format' => 'A4-P']);
            $mpdf->WriteHTML($html);
            $mpdf->Output($filename . '.pdf', 'D');

        } else if ($type == 'excel') {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set Title
            $sheet->setCellValue('A1', $data['title']);
            $sheet->mergeCells('A1:D1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Set Period
            $period = isset($data['start_date']) ? date("d M Y", strtotime($data['start_date'])) . ' - ' . date("d M Y", strtotime($data['end_date'])) : date("d M Y", strtotime($data['as_of_date']));
            $sheet->setCellValue('A2', 'Periode: ' . $period);
            $sheet->mergeCells('A2:D2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension(1)->setRowHeight(20);
            $sheet->getRowDimension(2)->setRowHeight(20);

            // Logic for each report
            switch ($view) {
                case 'accounting/reports/laba_rugi':
                    $this->_export_laba_rugi_excel($sheet, $data['report_data']);
                    break;
                case 'accounting/reports/neraca':
                    $this->_export_neraca_excel($sheet, $data['report_data']);
                    break;
                case 'accounting/reports/trial_balance':
                    $this->_export_trial_balance_excel($sheet, $data['report_data']);
                    break;
                case 'accounting/reports/cash_flow':
                    $this->_export_cash_flow_excel($sheet, $data['report_data']);
                    break;
                // ... other cases for other reports will be added here
                default:
                    // Generic fallback if no specific handler
                    $sheet->setCellValue('A4', 'No specific excel export template for this report yet.');
                    break;
            }

            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit();
        }
    }



    private function _export_laba_rugi_excel($sheet, $data)
    {
        $row = 4;

        
        $headers = ['Deskripsi', 'Jumlah'];
        $sheet->fromArray($headers, NULL, 'A' . $row);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        $row++;

        // Styling for borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $startRow = $row;

        
        $writeRow = function($description, $amount, $isBold = false, $isSubtotal = false) use ($sheet, &$row) {
            $sheet->setCellValue('A' . $row, $description);
            $sheet->setCellValue('B' . $row, $amount);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('_("Rp"* #,##0.00_);_("Rp"* (#,##0.00);_("Rp"* "-"??_);_(@_)');
            
            if ($isBold) {
                $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            }
            if ($isSubtotal) {
                $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
            }
            $row++;
        };

        // Pendapatan
        $writeRow('Pendapatan', null, true);
        if (!empty($data['pendapatan']) && (is_array($data['pendapatan']) || $data['pendapatan'] instanceof Traversable)) {
            foreach ($data['pendapatan'] as $item) {
                $amt = isset($item['total']) ? $item['total'] : 0;
                $name = isset($item['account_name']) ? $item['account_name'] : '-';
                $writeRow($name, $amt);
            }
        }
        $writeRow('Total Pendapatan', isset($data['total_pendapatan']) ? $data['total_pendapatan'] : 0, true, true);

        // Beban Pokok Penjualan (HPP)
        $writeRow('Beban Pokok Penjualan', null, true);
        if (!empty($data['hpp']) && (is_array($data['hpp']) || $data['hpp'] instanceof Traversable)) {
            foreach ($data['hpp'] as $item) {
                $amt = isset($item['total']) ? $item['total'] : 0;
                $name = isset($item['account_name']) ? $item['account_name'] : '-';
                $writeRow($name, $amt);
            }
        }
        $writeRow('Total Beban Pokok Penjualan', isset($data['total_hpp']) ? $data['total_hpp'] : 0, true, true);

        // Laba Kotor
        $writeRow('Laba Kotor', isset($data['laba_kotor']) ? $data['laba_kotor'] : 0, true, true);
        $row++; // Add a blank row

        // Beban Operasional
        $writeRow('Beban Operasional', null, true);
        if (!empty($data['beban']) && (is_array($data['beban']) || $data['beban'] instanceof Traversable)) {
            foreach ($data['beban'] as $item) {
                $amt = isset($item['total']) ? $item['total'] : 0;
                $name = isset($item['account_name']) ? $item['account_name'] : '-';
                $writeRow($name, $amt);
            }
        }
        $writeRow('Total Beban Operasional', isset($data['total_beban']) ? $data['total_beban'] : 0, true, true);

        // Laba Bersih
        $writeRow('Laba Bersih', isset($data['laba_bersih']) ? $data['laba_bersih'] : 0, true, true);
        $sheet->getStyle('A' . ($row - 1) . ':B' . ($row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');


        // Apply borders to the entire table
        $endRow = $row - 1;
        $sheet->getStyle('A' . $startRow . ':B' . $endRow)->applyFromArray($styleArray);

        // Auto size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }



    private function _export_neraca_excel($sheet, $data)
    {
        $row = 4; // Start after title and period

        // Styling for borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        // Helper function to write a row, similar to laba rugi
        $writeRow = function($description, $amount, $isBold = false, $isSubtotal = false) use ($sheet, &$row) {
            $sheet->setCellValue('A' . $row, $description);
            $sheet->setCellValue('B' . $row, $amount);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('_("Rp"* #,##0.00_);_("Rp"* (#,##0.00);_("Rp"* "-"??_);_(@_)');
            
            if ($isBold) {
                $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            }
            if ($isSubtotal) {
                $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
            }
            $row++;
        };

        // ASET
        $startAset = $row;
        $writeRow('ASET', null, true);
        if (!empty($data['aset']) && (is_array($data['aset']) || $data['aset'] instanceof Traversable)) {
            foreach ($data['aset'] as $group => $items) {
                $group_name = isset($items['group_name']) ? $items['group_name'] : '-';
                $writeRow($group_name, null, true);
                if (!empty($items['accounts']) && (is_array($items['accounts']) || $items['accounts'] instanceof Traversable)) {
                    foreach ($items['accounts'] as $item) {
                        $acct_name = isset($item['account_name']) ? $item['account_name'] : '-';
                        $acct_balance = isset($item['balance']) ? $item['balance'] : 0;
                        $writeRow(' ' . $acct_name, $acct_balance);
                    }
                }
                $total_balance = isset($items['total_balance']) ? $items['total_balance'] : 0;
                $writeRow('Total ' . $group_name, $total_balance, true, true);
            }
        }
        $writeRow('TOTAL ASET', isset($data['total_aset']) ? $data['total_aset'] : 0, true, true);
        $sheet->getStyle('A' . ($row - 1) . ':B' . ($row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');
        $sheet->getStyle('A' . $startAset . ':B' . ($row - 1))->applyFromArray($styleArray);
        
        $row++; // Blank row

        // KEWAJIBAN DAN EKUITAS
        $startLiabilitas = $row;
        $writeRow('KEWAJIBAN DAN EKUITAS', null, true);
        
        // Kewajiban
        $writeRow('Kewajiban', null, true);
        if (!empty($data['kewajiban']) && (is_array($data['kewajiban']) || $data['kewajiban'] instanceof Traversable)) {
            foreach ($data['kewajiban'] as $group => $items) {
                $group_name = isset($items['group_name']) ? $items['group_name'] : '-';
                $writeRow($group_name, null, true);
                if (!empty($items['accounts']) && (is_array($items['accounts']) || $items['accounts'] instanceof Traversable)) {
                    foreach ($items['accounts'] as $item) {
                        $acct_name = isset($item['account_name']) ? $item['account_name'] : '-';
                        $acct_balance = isset($item['balance']) ? $item['balance'] : 0;
                        $writeRow(' ' . $acct_name, $acct_balance);
                    }
                }
                $total_balance = isset($items['total_balance']) ? $items['total_balance'] : 0;
                $writeRow('Total ' . $group_name, $total_balance, true, true);
            }
        }
        $writeRow('Total Kewajiban', isset($data['total_kewajiban']) ? $data['total_kewajiban'] : 0, true, true);
        
        $row++; // Blank row

        // Ekuitas
        $writeRow('Ekuitas', null, true);
        if (!empty($data['ekuitas']) && (is_array($data['ekuitas']) || $data['ekuitas'] instanceof Traversable)) {
            foreach ($data['ekuitas'] as $group => $items) {
                $group_name = isset($items['group_name']) ? $items['group_name'] : '-';
                $writeRow($group_name, null, true);
                if (!empty($items['accounts']) && (is_array($items['accounts']) || $items['accounts'] instanceof Traversable)) {
                    foreach ($items['accounts'] as $item) {
                        $acct_name = isset($item['account_name']) ? $item['account_name'] : '-';
                        $acct_balance = isset($item['balance']) ? $item['balance'] : 0;
                        $writeRow(' ' . $acct_name, $acct_balance);
                    }
                }
                $total_balance = isset($items['total_balance']) ? $items['total_balance'] : 0;
                $writeRow('Total ' . $group_name, $total_balance, true, true);
            }
        }
        $writeRow('Total Ekuitas', isset($data['total_ekuitas']) ? $data['total_ekuitas'] : 0, true, true);

        $row++; // Blank row

        // Total Kewajiban dan Ekuitas
    $writeRow('TOTAL KEWAJIBAN DAN EKUITAS', isset($data['total_kewajiban_ekuitas']) ? $data['total_kewajiban_ekuitas'] : 0, true, true);
        $sheet->getStyle('A' . ($row - 1) . ':B' . ($row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');
        $sheet->getStyle('A' . $startLiabilitas . ':B' . ($row - 1))->applyFromArray($styleArray);


        // Auto size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    private function _export_trial_balance_excel($sheet, $data)
    {
        $row = 4; // Start after title and period

        // Define headers
        $headers = ['Kode Akun', 'Nama Akun', 'Debit', 'Kredit'];
        $sheet->fromArray($headers, NULL, 'A' . $row);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        $row++;

        // Styling for borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $startRow = $row;

        // Data rows: be defensive about missing keys to avoid PHP warnings
        $accounts = [];
        if (!empty($data['accounts']) && (is_array($data['accounts']) || $data['accounts'] instanceof Traversable)) {
            $accounts = $data['accounts'];
        }

        foreach ($accounts as $item) {
            $acct_code = isset($item['account_code']) ? $item['account_code'] : '-';
            $acct_name = isset($item['account_name']) ? $item['account_name'] : '-';
            $debit = isset($item['debit']) ? $item['debit'] : 0;
            $kredit = isset($item['kredit']) ? $item['kredit'] : 0;

            $sheet->setCellValue('A' . $row, $acct_code);
            $sheet->setCellValue('B' . $row, $acct_name);
            $sheet->setCellValue('C' . $row, $debit);
            $sheet->setCellValue('D' . $row, $kredit);
            $sheet->getStyle('C' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('_("Rp"* #,##0.00_);_("Rp"* (#,##0.00);_("Rp"* "-"??_);_(@_)');
            $row++;
        }

        // Total row: use defaults if missing
        $total_debit = isset($data['total_debit']) ? $data['total_debit'] : 0;
        $total_kredit = isset($data['total_kredit']) ? $data['total_kredit'] : 0;

        $sheet->setCellValue('B' . $row, 'Total');
        $sheet->setCellValue('C' . $row, $total_debit);
        $sheet->setCellValue('D' . $row, $total_kredit);
        $sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('_("Rp"* #,##0.00_);_("Rp"* (#,##0.00);_("Rp"* "-"??_);_(@_)');
        $sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');

        // Apply borders to the entire table
        $endRow = $row;
        $sheet->getStyle('A' . $startRow . ':D' . $endRow)->applyFromArray($styleArray);

        // Auto size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
    }

    private function _export_cash_flow_excel($sheet, $data)
    {
        $row = 4; // Start after title and period

        // Define headers
        $headers = ['Deskripsi', 'Jumlah'];
        $sheet->fromArray($headers, NULL, 'A' . $row);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');

        $row++;

        // Styling for borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];

        $startRow = $row;

        // Helper function to write a row
        $writeRow = function($description, $amount, $isBold = false, $isSubtotal = false) use ($sheet, &$row) {
            $sheet->setCellValue('A' . $row, $description);
            $sheet->setCellValue('B' . $row, $amount);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('_("Rp"* #,##0.00_);_("Rp"* (#,##0.00);_("Rp"* "-"??_);_(@_)');
            
            if ($isBold) {
                $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            }
            if ($isSubtotal) {
                $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF2F2F2');
            }
            $row++;
        };

        // Arus Kas dari Aktivitas Operasi
        $writeRow('Arus Kas dari Aktivitas Operasi', null, true);
        if (!empty($data['operating']) && isset($data['operating']['details']) && (is_array($data['operating']['details']) || $data['operating']['details'] instanceof Traversable)) {
            foreach ($data['operating']['details'] as $item) {
                $desc = isset($item['description']) ? $item['description'] : '-';
                $amt = isset($item['amount']) ? $item['amount'] : 0;
                $writeRow($desc, $amt);
            }
        }
        $operating_total = isset($data['operating']['total']) ? $data['operating']['total'] : 0;
        $writeRow('Arus Kas Bersih dari Aktivitas Operasi', $operating_total, true, true);
        $row++;

        // Arus Kas dari Aktivitas Investasi
        $writeRow('Arus Kas dari Aktivitas Investasi', null, true);
        if (!empty($data['investing']) && isset($data['investing']['details']) && (is_array($data['investing']['details']) || $data['investing']['details'] instanceof Traversable)) {
            foreach ($data['investing']['details'] as $item) {
                $desc = isset($item['description']) ? $item['description'] : '-';
                $amt = isset($item['amount']) ? $item['amount'] : 0;
                $writeRow($desc, $amt);
            }
        }
        $investing_total = isset($data['investing']['total']) ? $data['investing']['total'] : 0;
        $writeRow('Arus Kas Bersih dari Aktivitas Investasi', $investing_total, true, true);
        $row++;

        // Arus Kas dari Aktivitas Pendanaan
        $writeRow('Arus Kas dari Aktivitas Pendanaan', null, true);
        if (!empty($data['financing']) && isset($data['financing']['details']) && (is_array($data['financing']['details']) || $data['financing']['details'] instanceof Traversable)) {
            foreach ($data['financing']['details'] as $item) {
                $desc = isset($item['description']) ? $item['description'] : '-';
                $amt = isset($item['amount']) ? $item['amount'] : 0;
                $writeRow($desc, $amt);
            }
        }
        $financing_total = isset($data['financing']['total']) ? $data['financing']['total'] : 0;
        $writeRow('Arus Kas Bersih dari Aktivitas Pendanaan', $financing_total, true, true);
        $row++;

        // Ringkasan (summary)
        $writeRow('Kenaikan (Penurunan) Bersih Kas', isset($data['net_cash_increase']) ? $data['net_cash_increase'] : 0, true, true);
        $writeRow('Kas dan Setara Kas Awal Periode', isset($data['beginning_cash_balance']) ? $data['beginning_cash_balance'] : 0, true, true);
        $writeRow('Kas dan Setara Kas Akhir Periode', isset($data['ending_cash_balance']) ? $data['ending_cash_balance'] : 0, true, true);
        $sheet->getStyle('A' . ($row - 1) . ':B' . ($row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');

        // Apply borders to the entire table
        $endRow = $row - 1;
        $sheet->getStyle('A' . $startRow . ':B' . $endRow)->applyFromArray($styleArray);

        // Auto size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }
}
