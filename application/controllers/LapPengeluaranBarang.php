<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Mpdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LapPengeluaranBarang extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login(); 
        $this->load->model([
            'LapPengeluaranBarang_model', 
            'Commoncode_model',
            'Stock_ledger_model'
        ]);
        $this->load->library('pagination');
    }

    public function index() {
        if (!check_access('lap_pengeluaran_barang', 'view')) { show_error('Akses ditolak', 403); }

        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-t'),
            'tx_type'    => $this->input->get('tx_type'),
            'search'     => $this->input->get('search')
        ];
        
        $config['base_url'] = base_url('LapPengeluaranBarang/index');
        $config['total_rows'] = $this->LapPengeluaranBarang_model->count_report($filters);
        $config['per_page'] = 50; 
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE;
        $this->pagination->initialize($config);
        $page = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 1;
        $offset = ($page > 0) ? ($page - 1) * $config['per_page'] : 0;
        
        $data = [
            'title'      => 'LaporanPengeluaran Barang',
            'filters'    => $filters,
            'report_data'=> $this->LapPengeluaranBarang_model->get_report($filters, $config['per_page'], $offset),
            'pagination' => $this->pagination->create_links(),
            'start_no'   => $offset + 1,
            'tx_options' => [
                'production_out' => 'Pengeluaran Subkon (DO)',
                'fg_out'         => 'Pengiriman Customer (DO)',
                'OUT_SCRAP_BC'   => 'Pengeluaran Scrap (BC)',
                'ADJ_OUT'        => 'Penyesuaian Keluar'
            ],
            'bc_map'     => array_column($this->Commoncode_model->get_by_group('BC_TYPE'), 'description', 'code')
        ];
        $this->load_views('it_inventory/lap_pengeluaran_barang/index', $data);
    }
    
    /**
     * Modal Detail Transaksi (Traceability)
     */
    public function trace_detail() {
        if (!$this->input->is_ajax_request()) { show_error('Akses ditolak', 403); }

        $ledger_id = $this->input->get('ledger_id');
        if (!$ledger_id) { echo json_encode(['success' => false, 'message' => 'ID Ledger tidak ditemukan.']); return; }
        
        // PENTING: Asumsi Stock_ledger_model punya get_transaction_detail_by_id
        $data['trace'] = $this->Stock_ledger_model->get_transaction_detail_by_id($ledger_id); 
        
        $this->load->view('it_inventory/lap_pengeluaran_barang/trace_modal', $data);
    }
    
    /* ==========================================================
     EKSPOR
     ========================================================== */

    public function export_trace_excel($ledger_id) {
        if (!check_access('lap_pengeluaran_barang', 'view')) { show_error('Akses ditolak', 403); }
        if (empty($ledger_id)) { show_error('ID Ledger tidak valid.', 400); }

        $trace = $this->Stock_ledger_model->get_transaction_detail_by_id($ledger_id);
        if (!$trace) { show_error('Data trace tidak ditemukan.', 404); }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Transaksi');

        // --- Header ---
        $sheet->setCellValue('A1', 'Detail Transaksi Pengeluaran');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // --- Data Key-Value ---
        // Build key-value rows and write cells individually so we can write real Excel dates
        $dataRows = [
            ['Jenis Transaksi', $trace->transaction_type ?? '-'],
            ['Tanggal Transaksi', $trace->transaction_date ?? null],
            ['Dokumen Sumber', ($trace->source_doc_type ?? '-') . ' / ' . ($trace->source_doc_no ?? $trace->source_doc_id ?? '-')],
            ['Partner', $trace->partner_name ?? '-'],
            ['Kode Barang', $trace->item_code ?? '-'],
            ['Nama Barang', $trace->item_name ?? '-'],
            ['Jumlah Keluar', isset($trace->jumlah) ? (float)$trace->jumlah : 0],
            ['Satuan', $trace->item_unit ?? '-'],
            ['Nilai Barang (IDR)', isset($trace->nilai_barang_idr) ? (float)$trace->nilai_barang_idr : 0],
            ['Jenis BC', $trace->jenis_bc_desc ?? '-'],
            ['Nomor BC', $trace->nomor_bc_ref ?? '-'],
            ['Tanggal BC', $trace->tanggal_bc_ref ?? null],
            ['Sales Contract', $trace->sales_contract_ref ?? '-'],
        ];

        $rowStart = 3;
        foreach ($dataRows as $i => $pair) {
            $r = $rowStart + $i;
            $label = $pair[0];
            $value = $pair[1];
            $sheet->setCellValue('A' . $r, $label);
            // Write dates as Excel-native date values (date-only, no time)
            if ($label === 'Tanggal Transaksi' || $label === 'Tanggal BC') {
                if (!empty($value)) {
                    try {
                        $dt = new \DateTime($value);
                        $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dt);
                        $sheet->setCellValue('B' . $r, $excelDate);
                        $sheet->getStyle('B' . $r)->getNumberFormat()->setFormatCode('dd-MMM-yyyy');
                    } catch (Exception $e) {
                        $sheet->setCellValue('B' . $r, '-');
                    }
                } else {
                    $sheet->setCellValue('B' . $r, '-');
                }
            } else {
                $sheet->setCellValue('B' . $r, $value !== null ? $value : '-');
            }
        }
        $sheet->getStyle('A3:A15')->getFont()->setBold(true); // Kolom A bold

        // Auto-size columns
        foreach (range('A', 'B') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // --- Output File ---
        $writer = new Xlsx($spreadsheet);
        $filename = 'Trace_Detail_' . ($trace->item_code ?? $ledger_id) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function export_trace_pdf($ledger_id) {
        if (!check_access('lap_pengeluaran_barang', 'view')) { show_error('Akses ditolak', 403); }
        if (empty($ledger_id)) { show_error('ID Ledger tidak valid.', 400); }

        $trace = $this->Stock_ledger_model->get_transaction_detail_by_id($ledger_id);
        if (!$trace) { show_error('Data trace tidak ditemukan.', 404); }

        $data = [
            'title' => 'Detail Transaksi Pengeluaran',
            'trace' => $trace
        ];

        try {
            $mpdf = new Mpdf(['format' => 'A4-P']); // Portrait
            // Anda perlu membuat view template ini
            $html = $this->load->view('it_inventory/lap_pengeluaran_barang/pdf_trace_detail', $data, true);
            $mpdf->WriteHTML($html);
            $mpdf->Output('Detail_Transaksi_' . ($trace->item_code ?? $ledger_id) . '.pdf', 'I'); // 'I' for inline/preview
        } catch (\Mpdf\MpdfException $e) {
            log_message('error', 'PDF Export Trace Error: ' . $e->getMessage());
            show_error('Gagal membuat PDF.');
        }
    }

    /**
     * Ekspor ke Excel
     */
    public function export_excel() {
        if (!check_access('lap_pengeluaran_barang', 'view')) { show_error('Akses ditolak', 403); }
        
        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-t'),
            'tx_type'    => $this->input->get('tx_type'),
            'search'     => $this->input->get('search')
        ];
        // Ambil data sebagai array (bukan objek)
        $list_objects = $this->LapPengeluaranBarang_model->get_report($filters, 0, 0); 
        $list = json_decode(json_encode($list_objects), true); // Konversi ke array asosiatif untuk Excel
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Pengeluaran Barang');
        
        // Mapping BC Types (untuk display)
        $bc_codes = array_column($this->Commoncode_model->get_by_group('BC_TYPE'), 'description', 'code');

        // --- Header Laporan ---
        $sheet->setCellValue('A1', 'LAPORAN PENGELUARAN BARANG');
        $sheet->mergeCells('A1:M1'); 
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        $sheet->setCellValue('A2', company_info('company_name'));
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        $sheet->setCellValue('A3', 'Periode: ' . date('d M Y', strtotime($filters['start_date'])) . ' s/d ' . date('d M Y', strtotime($filters['end_date'])));
        $sheet->mergeCells('A3:M3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');
        
        // --- Header Tabel ---
        $headerRow = 5;
        $sheet->setCellValue('A'.$headerRow, 'NO')->mergeCells('A'.$headerRow.':A'.($headerRow+1));
        $sheet->setCellValue('B'.$headerRow, 'DATA DOKUMEN PABEAN')->mergeCells('B'.$headerRow.':D'.$headerRow);
        $sheet->setCellValue('E'.$headerRow, 'BUKTI PENGELUARAN BARANG')->mergeCells('E'.$headerRow.':G'.$headerRow);
        $sheet->setCellValue('H'.$headerRow, 'DETAIL BARANG')->mergeCells('H'.$headerRow.':K'.$headerRow);
        $sheet->setCellValue('L'.$headerRow, 'NILAI KELUAR')->mergeCells('L'.$headerRow.':M'.($headerRow));
        
        $sheet->setCellValue('B'.($headerRow+1), 'JENIS BC');
        $sheet->setCellValue('C'.($headerRow+1), 'NOMOR BC');
        $sheet->setCellValue('D'.($headerRow+1), 'TANGGAL BC');
        $sheet->setCellValue('E'.($headerRow+1), 'NOMOR PENGELUARAN');
        $sheet->setCellValue('F'.($headerRow+1), 'TANGGAL PENGELUARAN');
        $sheet->setCellValue('G'.($headerRow+1), 'PENERIMA BARANG');
        $sheet->setCellValue('H'.($headerRow+1), 'KODE BARANG');
        $sheet->setCellValue('I'.($headerRow+1), 'NAMA BARANG');
        $sheet->setCellValue('J'.($headerRow+1), 'JUMLAH');
        $sheet->setCellValue('K'.($headerRow+1), 'SATUAN');
        $sheet->setCellValue('L'.($headerRow+1), 'MATA UANG');
        $sheet->setCellValue('M'.($headerRow+1), 'NILAI BARANG');

        // Style header - Bold, Gray background, Center, Borders
        $sheet->getStyle('A'.$headerRow.':M'.($headerRow+1))->getFont()->setBold(true);
        $sheet->getStyle('A'.$headerRow.':M'.($headerRow+1))->getAlignment()->setHorizontal('center')->setVertical('center');
        $sheet->getStyle('A'.$headerRow.':M'.($headerRow+1))->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD3D3D3');
        $sheet->getStyle('A'.$headerRow.':M'.($headerRow+1))->getBorders()->getAllBorders()->setBorderStyle('thin');

        $rowNum = $headerRow + 2;
        $no = 1;
        // ▼ FIX: Looping aman menggunakan array asosiatif ($list) ▼
        foreach ($list as $row) { 
            // Tentukan Nilai yang akan ditampilkan (FC atau IDR)
            $nilai_barang_display = ($row['mata_uang'] != 'IDR' && $row['nilai_barang_fc'] > 0) ? $row['nilai_barang_fc'] : $row['nilai_barang_idr'];
            $bc_desc_display = $bc_codes[$row['jenis_bc']] ?? $row['jenis_bc']; // Handle jika BC Code masih string

            // Write cells individually so dates become Excel-native values
            $sheet->setCellValue('A' . $rowNum, $no++);
            $sheet->setCellValue('B' . $rowNum, $bc_desc_display);
            $sheet->setCellValue('C' . $rowNum, $row['nomor_bc'] ?? '-');

            // tanggal_bc -> Excel date (date-only)
            if (!empty($row['tanggal_bc'])) {
                try {
                    $d = new \DateTime($row['tanggal_bc']);
                    $sheet->setCellValue('D' . $rowNum, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($d));
                    $sheet->getStyle('D' . $rowNum)->getNumberFormat()->setFormatCode('dd-MMM-yyyy');
                } catch (Exception $e) {
                    $sheet->setCellValue('D' . $rowNum, '');
                }
            } else {
                $sheet->setCellValue('D' . $rowNum, '');
            }

            $sheet->setCellValue('E' . $rowNum, $row['nomor_pengeluaran'] ?? '-');

            // tanggal_pengeluaran -> Excel date (date-only)
            if (!empty($row['tanggal_pengeluaran'])) {
                try {
                    $d2 = new \DateTime($row['tanggal_pengeluaran']);
                    $sheet->setCellValue('F' . $rowNum, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($d2));
                    $sheet->getStyle('F' . $rowNum)->getNumberFormat()->setFormatCode('dd-MMM-yyyy');
                } catch (Exception $e) {
                    $sheet->setCellValue('F' . $rowNum, '');
                }
            } else {
                $sheet->setCellValue('F' . $rowNum, '');
            }

            $sheet->setCellValue('G' . $rowNum, $row['partner_name'] ?? '-');
            $sheet->setCellValue('H' . $rowNum, $row['item_code'] ?? '-');
            $sheet->setCellValue('I' . $rowNum, $row['item_name'] ?? '-');
            $sheet->setCellValue('J' . $rowNum, isset($row['jumlah']) ? (float)$row['jumlah'] : 0);
            $sheet->setCellValue('K' . $rowNum, $row['item_unit'] ?? '-');
            $sheet->setCellValue('L' . $rowNum, $row['mata_uang'] ?? 'IDR');
            $sheet->setCellValue('M' . $rowNum, (float)$nilai_barang_display);

            // Formatting
            $sheet->getStyle('J' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); // Jumlah
            $sheet->getStyle('M' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); // Nilai Barang
            $rowNum++;
        }

        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Lap_Pengeluaran_Barang_'.date('Ymd').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'. $filename.'"');
        $writer->save('php://output');
        exit;
    }
    
    /**
     * Ekspor ke PDF (Template)
     */
    public function export_pdf() {
        if (!check_access('lap_pengeluaran_barang', 'view')) { show_error('Akses ditolak', 403); }
        
        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-t'),
            'tx_type'    => $this->input->get('tx_type'),
            'search'     => $this->input->get('search')
        ];

        // Ambil data sebagai objek untuk view PDF
        $list = $this->LapPengeluaranBarang_model->get_report($filters, 0, 0); 
        
        $data = [
            'report_data' => $list,
            'filters' => $filters,
            'bc_map' => array_column($this->Commoncode_model->get_by_group('BC_TYPE'), 'description', 'code')
        ];
        
        // PENTING: Anda harus membuat file view ini: pdf_template.php
        $html = $this->load->view('it_inventory/lap_pengeluaran_barang/pdf_template', $data, true);
        $mpdf = new Mpdf(['format' => 'A4-L']); 
        $mpdf->WriteHTML($html);
        $mpdf->Output('Lap_Pengeluaran_Barang_'.date('Ymd').'.pdf', 'D');
    }
    
    private function load_views($main_view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($main_view, $data);
        $this->load->view('templates/footer');
    }
}