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

        // Optional debug: dump report data to PHP error log if requested
        if ($this->input->get('debug')) {
            error_log("REPORT_DEBUG profit_loss for {$startDate} - {$endDate}: " . print_r($data['report_data'], true));
        }

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

        // Recursive node writer: handle tree nodes produced by Report_model
        $write_node = function($node) use (&$write_node, $writeRow) {
            $name = isset($node['account_name']) ? $node['account_name'] : (isset($node['group_name']) ? $node['group_name'] : '-');
            if (isset($node['is_header']) && $node['is_header']) {
                $writeRow($name, null, true);
                if (!empty($node['children']) && (is_array($node['children']) || $node['children'] instanceof Traversable)) {
                    foreach ($node['children'] as $child) {
                        $write_node($child);
                    }
                }
            } else {
                $amt = isset($node['balance']) ? $this->_parse_amount($node['balance']) : $this->_get_item_amount($node, ['total','amount','balance','value']);
                $writeRow($name, $amt);
            }
        };

        // Normalize keys: older model uses 'biaya' for both HPP and Beban operasional.
        $pendapatan_nodes = !empty($data['pendapatan']) && (is_array($data['pendapatan']) || $data['pendapatan'] instanceof Traversable) ? $data['pendapatan'] : [];
        $biaya_nodes = !empty($data['biaya']) && (is_array($data['biaya']) || $data['biaya'] instanceof Traversable) ? $data['biaya'] : [];

        // Split biaya into HPP and Beban (operasional) by inspecting top-level node account_type when possible.
        $hpp_nodes = [];
        $beban_nodes = [];
        foreach ($biaya_nodes as $n) {
            $type = isset($n['account_type']) ? $n['account_type'] : (isset($n['type']) ? $n['type'] : null);
            if ($type === 'HPP') {
                $hpp_nodes[] = $n;
            } else {
                $beban_nodes[] = $n;
            }
        }

        // Helper sums for totals (fallback to provided totals when available)
        $total_pendapatan = isset($data['total_pendapatan']) ? $this->_parse_amount($data['total_pendapatan']) : null;
        $total_biaya = isset($data['total_biaya']) ? $this->_parse_amount($data['total_biaya']) : null;

        // Pendapatan
        $writeRow('Pendapatan', null, true);
        if (!empty($pendapatan_nodes)) {
            foreach ($pendapatan_nodes as $item) {
                $write_node($item);
            }
        }
        $writeRow('Total Pendapatan', $total_pendapatan !== null ? $total_pendapatan : 0, true, true);

        // Beban Pokok Penjualan (HPP)
        $writeRow('Beban Pokok Penjualan', null, true);
        if (!empty($hpp_nodes)) {
            foreach ($hpp_nodes as $item) {
                $write_node($item);
            }
        }
        // compute total hpp from nodes if not provided
        $total_hpp = isset($data['total_hpp']) ? $this->_parse_amount($data['total_hpp']) : null;
        if ($total_hpp === null) {
            $s = 0;
            foreach ($hpp_nodes as $n) { $s += isset($n['balance']) ? $this->_parse_amount($n['balance']) : 0; }
            $total_hpp = $s;
        }
        $writeRow('Total Beban Pokok Penjualan', $total_hpp, true, true);

        // Laba Kotor
        $writeRow('Laba Kotor', isset($data['laba_kotor']) ? $this->_parse_amount($data['laba_kotor']) : 0, true, true);
        $row++; // Add a blank row

        // Beban Operasional
        $writeRow('Beban Operasional', null, true);
        if (!empty($beban_nodes)) {
            foreach ($beban_nodes as $item) {
                $write_node($item);
            }
        }
        // compute total beban operasional if not provided
        $total_beban_operasional = isset($data['total_beban']) ? $this->_parse_amount($data['total_beban']) : null;
        if ($total_beban_operasional === null) {
            if ($total_biaya !== null && $total_hpp !== null) {
                $total_beban_operasional = $total_biaya - $total_hpp;
            } else {
                $s = 0; foreach ($beban_nodes as $n) { $s += isset($n['balance']) ? $this->_parse_amount($n['balance']) : 0; };
                $total_beban_operasional = $s;
            }
        }
        $writeRow('Total Beban Operasional', $total_beban_operasional, true, true);

        // Laba Bersih
        $writeRow('Laba Bersih', isset($data['laba_bersih']) ? $this->_parse_amount($data['laba_bersih']) : 0, true, true);
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

        // Shared recursive node writer for balance sheet sections
        $write_node = function($node) use (&$write_node, $writeRow) {
            $name = isset($node['account_name']) ? $node['account_name'] : (isset($node['group_name']) ? $node['group_name'] : '-');
            if (isset($node['is_header']) && $node['is_header']) {
                $writeRow($name, null, true);
                if (!empty($node['children']) && (is_array($node['children']) || $node['children'] instanceof Traversable)) {
                    foreach ($node['children'] as $child) {
                        $write_node($child);
                    }
                }
                $total = isset($node['balance']) ? $this->_parse_amount($node['balance']) : 0;
                $writeRow('Total ' . $name, $total, true, true);
            } else {
                $amt = isset($node['balance']) ? $this->_parse_amount($node['balance']) : $this->_get_item_amount($node, ['balance','total','amount','value']);
                $writeRow(' ' . $name, $amt);
            }
        };

        // ASET
        $startAset = $row;
        $writeRow('ASET', null, true);
        if (!empty($data['aset']) && (is_array($data['aset']) || $data['aset'] instanceof Traversable)) {
            foreach ($data['aset'] as $node) {
                $write_node($node);
            }
        }
        $writeRow('TOTAL ASET', isset($data['total_aset']) ? $this->_parse_amount($data['total_aset']) : 0, true, true);
        $sheet->getStyle('A' . ($row - 1) . ':B' . ($row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');
        $sheet->getStyle('A' . $startAset . ':B' . ($row - 1))->applyFromArray($styleArray);
        
        $row++; // Blank row

        // KEWAJIBAN DAN EKUITAS
        $startLiabilitas = $row;
        $writeRow('KEWAJIBAN DAN EKUITAS', null, true);

        // Kewajiban (model uses key 'liabilitas') - support both keys for backward compatibility
        $liabilities_nodes = [];
        if (!empty($data['liabilitas']) && (is_array($data['liabilitas']) || $data['liabilitas'] instanceof Traversable)) {
            $liabilities_nodes = $data['liabilitas'];
        } elseif (!empty($data['kewajiban']) && (is_array($data['kewajiban']) || $data['kewajiban'] instanceof Traversable)) {
            $liabilities_nodes = $data['kewajiban'];
        }

        $writeRow('Kewajiban', null, true);
        if (!empty($liabilities_nodes)) {
            foreach ($liabilities_nodes as $node) {
                $write_node($node);
            }
        }
        $total_liabilitas = isset($data['total_liabilitas']) ? $this->_parse_amount($data['total_liabilitas']) : (isset($data['total_kewajiban']) ? $this->_parse_amount($data['total_kewajiban']) : 0);
        $writeRow('Total Kewajiban', $total_liabilitas, true, true);

        $row++; // Blank row

        // Ekuitas
        $writeRow('Ekuitas', null, true);
        if (!empty($data['ekuitas']) && (is_array($data['ekuitas']) || $data['ekuitas'] instanceof Traversable)) {
            foreach ($data['ekuitas'] as $node) {
                $write_node($node);
            }
        }
        $total_ekuitas = isset($data['total_ekuitas']) ? $this->_parse_amount($data['total_ekuitas']) : 0;
        $writeRow('Total Ekuitas', $total_ekuitas, true, true);

        $row++; // Blank row

        // Total Kewajiban dan Ekuitas (compute if missing)
        $total_kewajiban_ekuitas = isset($data['total_kewajiban_ekuitas']) ? $this->_parse_amount($data['total_kewajiban_ekuitas']) : ($total_liabilitas + $total_ekuitas);
        $writeRow('TOTAL KEWAJIBAN DAN EKUITAS', $total_kewajiban_ekuitas, true, true);
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

        // Data rows: support both forms:
        // - $data is an associative array with key 'accounts' => [...]
        // - or $data is itself the numeric-indexed array of accounts returned by the model
        $accounts = [];
        if (!empty($data) && (is_array($data) || $data instanceof Traversable)) {
            if (isset($data['accounts']) && (is_array($data['accounts']) || $data['accounts'] instanceof Traversable)) {
                $accounts = $data['accounts'];
            } else {
                // Maybe $data is already the accounts list (numeric-indexed)
                $first = reset($data);
                if (is_array($first) && (isset($first['account_code']) || isset($first['account_name']))) {
                    $accounts = $data;
                }
            }
        }

        $sum_debit = 0;
        $sum_kredit = 0;
        foreach ($accounts as $item) {
            $acct_code = isset($item['account_code']) ? $item['account_code'] : '-';
            $acct_name = isset($item['account_name']) ? $item['account_name'] : '-';
            $debit = $this->_get_item_amount($item, ['debit', 'debit_amount', 'amount', 'total']);
            $kredit = $this->_get_item_amount($item, ['kredit', 'credit', 'kredit_amount', 'total']);

            $sheet->setCellValue('A' . $row, $acct_code);
            $sheet->setCellValue('B' . $row, $acct_name);
            $sheet->setCellValue('C' . $row, $debit);
            $sheet->setCellValue('D' . $row, $kredit);
            $sheet->getStyle('C' . $row . ':D' . $row)->getNumberFormat()->setFormatCode('_("Rp"* #,##0.00_);_("Rp"* (#,##0.00);_("Rp"* "-"??_);_(@_)');
            $row++;

            $sum_debit += floatval($debit);
            $sum_kredit += floatval($kredit);
        }

        // Total row: prefer explicit totals if provided, otherwise use computed sums
        $total_debit = isset($data['total_debit']) ? $this->_parse_amount($data['total_debit']) : $sum_debit;
        $total_kredit = isset($data['total_kredit']) ? $this->_parse_amount($data['total_kredit']) : $sum_kredit;

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
        // Support two shapes: new model returns 'cfo'/'total_cfo' and 'saldo_awal_kas',
        // earlier code expected 'operating' => ['details'=>..., 'total'=>...]
        $writeRow('Arus Kas dari Aktivitas Operasi', null, true);
        $operating_details = [];
        $operating_total = null;
        if (!empty($data['operating']) && isset($data['operating']['details']) && (is_array($data['operating']['details']) || $data['operating']['details'] instanceof Traversable)) {
            $operating_details = $data['operating']['details'];
            $operating_total = isset($data['operating']['total']) ? $this->_parse_amount($data['operating']['total']) : null;
        } elseif (!empty($data['cfo']) && (is_array($data['cfo']) || $data['cfo'] instanceof Traversable)) {
            $operating_details = $data['cfo'];
            $operating_total = isset($data['total_cfo']) ? $this->_parse_amount($data['total_cfo']) : null;
        }
        foreach ($operating_details as $item) {
            $desc = isset($item['description']) ? $item['description'] : (isset($item['account_name']) ? $item['account_name'] : '-');
            $amt = $this->_get_item_amount($item, ['cash_impact','amount', 'total', 'value']);
            $writeRow($desc, $amt);
        }
        if ($operating_total === null) $operating_total = 0;
        $writeRow('Arus Kas Bersih dari Aktivitas Operasi', $operating_total, true, true);
        $row++;

        // Arus Kas dari Aktivitas Investasi
        $writeRow('Arus Kas dari Aktivitas Investasi', null, true);
        $investing_details = [];
        $investing_total = null;
        if (!empty($data['investing']) && isset($data['investing']['details']) && (is_array($data['investing']['details']) || $data['investing']['details'] instanceof Traversable)) {
            $investing_details = $data['investing']['details'];
            $investing_total = isset($data['investing']['total']) ? $this->_parse_amount($data['investing']['total']) : null;
        } elseif (!empty($data['cfi']) && (is_array($data['cfi']) || $data['cfi'] instanceof Traversable)) {
            $investing_details = $data['cfi'];
            $investing_total = isset($data['total_cfi']) ? $this->_parse_amount($data['total_cfi']) : null;
        }
        foreach ($investing_details as $item) {
            $desc = isset($item['description']) ? $item['description'] : (isset($item['account_name']) ? $item['account_name'] : '-');
            $amt = $this->_get_item_amount($item, ['cash_impact','amount', 'total', 'value']);
            $writeRow($desc, $amt);
        }
        if ($investing_total === null) $investing_total = 0;
        $writeRow('Arus Kas Bersih dari Aktivitas Investasi', $investing_total, true, true);
        $row++;

        // Arus Kas dari Aktivitas Pendanaan
        $writeRow('Arus Kas dari Aktivitas Pendanaan', null, true);
        $financing_details = [];
        $financing_total = null;
        if (!empty($data['financing']) && isset($data['financing']['details']) && (is_array($data['financing']['details']) || $data['financing']['details'] instanceof Traversable)) {
            $financing_details = $data['financing']['details'];
            $financing_total = isset($data['financing']['total']) ? $this->_parse_amount($data['financing']['total']) : null;
        } elseif (!empty($data['cff']) && (is_array($data['cff']) || $data['cff'] instanceof Traversable)) {
            $financing_details = $data['cff'];
            $financing_total = isset($data['total_cff']) ? $this->_parse_amount($data['total_cff']) : null;
        }
        foreach ($financing_details as $item) {
            $desc = isset($item['description']) ? $item['description'] : (isset($item['account_name']) ? $item['account_name'] : '-');
            $amt = $this->_get_item_amount($item, ['cash_impact','amount', 'total', 'value']);
            $writeRow($desc, $amt);
        }
        if ($financing_total === null) $financing_total = 0;
        $writeRow('Arus Kas Bersih dari Aktivitas Pendanaan', $financing_total, true, true);
        $row++;

        // Ringkasan (summary)
        // Map model keys: 'peningkatan_kas' -> net increase, 'saldo_awal_kas' -> beginning, 'saldo_akhir_kas' -> ending
        $net_increase = isset($data['net_cash_increase']) ? $this->_parse_amount($data['net_cash_increase']) : (isset($data['peningkatan_kas']) ? $this->_parse_amount($data['peningkatan_kas']) : 0);
        $beginning_cash = isset($data['beginning_cash_balance']) ? $this->_parse_amount($data['beginning_cash_balance']) : (isset($data['saldo_awal_kas']) ? $this->_parse_amount($data['saldo_awal_kas']) : 0);
        $ending_cash = isset($data['ending_cash_balance']) ? $this->_parse_amount($data['ending_cash_balance']) : (isset($data['saldo_akhir_kas']) ? $this->_parse_amount($data['saldo_akhir_kas']) : 0);

        $writeRow('Kenaikan (Penurunan) Bersih Kas', $net_increase, true, true);
        $writeRow('Kas dan Setara Kas Awal Periode', $beginning_cash, true, true);
        $writeRow('Kas dan Setara Kas Akhir Periode', $ending_cash, true, true);
        $sheet->getStyle('A' . ($row - 1) . ':B' . ($row - 1))->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3');

        // Apply borders to the entire table
        $endRow = $row - 1;
        $sheet->getStyle('A' . $startRow . ':B' . $endRow)->applyFromArray($styleArray);

        // Auto size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }

    /**
     * Parse a possibly-formatted amount into a numeric (float) value.
     * Handles numbers like "1.000.000,00", "-1.000.000,00", "(1.000.000,00)", or plain numeric values.
     */
    private function _parse_amount($val)
    {
        if ($val === null || $val === '') {
            return 0;
        }

        if (is_numeric($val)) {
            return $val + 0;
        }

        $s = (string) $val;
        $negative = false;

        // Detect parentheses (accounting negative format)
        if (preg_match('/^\s*\(.+\)\s*$/', $s)) {
            $negative = true;
            $s = trim($s);
            $s = trim($s, '()');
        }

        // Remove any currency symbols and spaces, keep digits, separators, minus and dot/comma
        $s = preg_replace('/[^0-9,\.\-]/u', '', $s);

        // If both dot and comma exist assume dot thousands and comma decimal: 1.234.567,89
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (strpos($s, ',') !== false && strpos($s, '.') === false) {
            // only comma present, likely decimal separator
            $s = str_replace(',', '.', $s);
        } else {
            // no comma or only dot present: remove any commas (safety)
            $s = str_replace(',', '', $s);
        }

        if ($s === '' || $s === null) {
            return 0;
        }

        $num = floatval($s);
        return $negative ? -$num : $num;
    }

    /**
     * Try several possible keys on an item and return the first numeric value parsed.
     * Keys should be provided in order of preference.
     */
    private function _get_item_amount($item, $keys = [])
    {
        if (!is_array($item) && !is_object($item)) {
            return $this->_parse_amount($item);
        }

        foreach ($keys as $k) {
            if (is_array($item) && array_key_exists($k, $item)) {
                $v = $item[$k];
                if ($v !== null && $v !== '') {
                    $num = $this->_parse_amount($v);
                    if ($num != 0 || $v === "0" || $v === 0) {
                        return $num;
                    }
                }
            }
            if (is_object($item) && property_exists($item, $k)) {
                $v = $item->$k;
                if ($v !== null && $v !== '') {
                    $num = $this->_parse_amount($v);
                    if ($num != 0 || $v === "0" || $v === 0) {
                        return $num;
                    }
                }
            }
        }

        return 0;
    }
}
