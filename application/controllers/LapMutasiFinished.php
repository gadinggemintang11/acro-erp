<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Memuat pustaka ekspor dari vendor/autoload.php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class LapMutasiFinished extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        
        $this->load->model('LapMutasiFinished_model', 'model');
        $this->load->helper('form');
        $this->load->helper('date');
    }

    /**
     * HALAMAN 1: REKAPITULASI SEMUA BARANG JADI
     */
    public function index()
    {
        $data['title'] = lang_line('fg_mutation_title', 'Laporan Pertanggung Jawaban Mutasi Barang Jadi');
        
        $data['start_date'] = $this->input->get('start_date') ?: date('Y-m-01');
        $data['end_date'] = $this->input->get('end_date') ?: date('Y-m-t');
        
        $data['rekap_data'] = $this->model->get_stock_summary(
            $data['start_date'], 
            $data['end_date']
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data); // <-- Sudah Ditambahkan
        $this->load->view('it_inventory/lap_mutasi_finished/index', $data); 
        $this->load->view('templates/footer');
    }

    /**
     * HALAMAN 2: DETAIL LOG TRANSAKSI PER BARANG
     */
    public function detail($fg_id = 0)
    {
        if ($fg_id == 0) redirect('LapMutasiFinished');

        $data['start_date'] = $this->input->get('start_date') ?: date('Y-m-01');
        $data['end_date'] = $this->input->get('end_date') ?: date('Y-m-t');
        
        $data['item'] = $this->model->get_item_info($fg_id);
        if (!$data['item']) redirect('LapMutasiFinished');

        $data['title'] = 'Detail Mutasi: ' . $data['item']->nama_barang;
        $data['fg_id'] = $fg_id;

        $data['saldo_awal'] = $this->model->get_saldo_awal($fg_id, $data['start_date']);
        $data['mutasi_data'] = $this->model->get_item_mutation_log($fg_id, $data['start_date'], $data['end_date']);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data); // <-- Sudah Ditambahkan
        $this->load->view('it_inventory/lap_mutasi_finished/detail_log', $data);
        $this->load->view('templates/footer');
    }

    /**
     * HALAMAN 3: MODAL AJAX DEEP TRACEABILITY
     */
    public function ajax_traceability_detail()
    {
        // Suppress PHP 8.2+ deprecation warnings for AJAX response
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        ini_set('display_errors', 0);
        
        $type = $this->input->get('type');
        $id = $this->input->get('id');
        
        // Debug logging
        log_message('debug', "Traceability AJAX called - Type: $type, ID: $id");
        
        $data['detail'] = null;
        $view_path = '';

        try {
            switch ($type) {
                case 'fg_in':
                    log_message('debug', "Processing fg_in for ID: $id");
                    $data['detail'] = $this->model->get_detail_penerimaan($id);
                    log_message('debug', "Detail result: " . ($data['detail'] ? 'found' : 'not found'));
                    
                    if($data['detail']) {
                        log_message('debug', "Getting materials for WO: {$data['detail']->wo_header_id}, Prod: {$data['detail']->production_header_id}");
                        // Use the original function to avoid BC enhancement errors for now
                        $data['materials'] = $this->model->get_materials_for_production(
                            $data['detail']->wo_header_id, 
                            $data['detail']->production_header_id
                        );
                        log_message('debug', "Materials count: " . count($data['materials']));
                    }
                    $view_path = 'it_inventory/lap_mutasi_finished/partials/modal_penerimaan';
                    break;
                case 'fg_out':
                    log_message('debug', "Processing fg_out for ID: $id");
                    $data['detail'] = $this->model->get_detail_pengiriman($id);
                    $view_path = 'it_inventory/lap_mutasi_finished/partials/modal_pengiriman';
                    break;
                case 'adjustment':
                    log_message('debug', "Processing adjustment for ID: $id");
                    $data['detail'] = $this->model->get_detail_adjustment($id);
                    $view_path = 'it_inventory/lap_mutasi_finished/partials/modal_adjusment';
                    break;
                default:
                    echo "<div class='alert alert-danger'>Tipe transaksi tidak dikenal: " . htmlspecialchars($type) . "</div>";
                    log_message('error', "Unknown transaction type: $type");
                    return;
            }

            if (!$data['detail']) {
                echo "<div class='alert alert-danger'>Data detail tidak ditemukan (Type: " . htmlspecialchars($type) . ", ID: " . htmlspecialchars($id) . ").</div>";
                log_message('error', "Detail not found - Type: $type, ID: $id");
                return;
            }

            // Check if view file exists
            $view_file_path = APPPATH . 'views/' . $view_path . '.php';
            if (!file_exists($view_file_path)) {
                echo "<div class='alert alert-danger'>View file tidak ditemukan: " . htmlspecialchars($view_path) . ".php<br>Path: " . $view_file_path . "</div>";
                log_message('error', "View file not found: $view_file_path");
                return;
            }

            log_message('debug', "Loading view: $view_path");
            $this->load->view($view_path, $data);

        } catch (Exception $e) {
            log_message('error', 'Ajax Traceability Error: ' . $e->getMessage());
            echo "<div class='alert alert-danger'>Terjadi error: " . htmlspecialchars($e->getMessage()) . "<br>File: " . $e->getFile() . "<br>Line: " . $e->getLine() . "</div>";
        } catch (Error $e) {
            log_message('error', 'Ajax Traceability Fatal Error: ' . $e->getMessage());
            echo "<div class='alert alert-danger'>Fatal error: " . htmlspecialchars($e->getMessage()) . "<br>File: " . $e->getFile() . "<br>Line: " . $e->getLine() . "</div>";
        }
    }

    /**
     * FUNGSI EKSPOR
     */
    
    // (Fungsi export_rekap_excel, export_rekap_pdf, export_detail_excel, 
    //  export_detail_pdf, export_doc_penerimaan_pdf, export_doc_pengiriman_pdf, 
    //  dan generate_pdf SAMA SEPERTI SEBELUMNYA. Tidak perlu diubah.)
     
    // ... (Salin semua fungsi ekspor dari jawaban saya sebelumnya) ...

    // Ekspor Halaman 1 (Rekap) ke Excel
    public function export_rekap_excel()
    {
        $start_date = $this->input->get('start_date') ?: date('Y-m-01');
        $end_date = $this->input->get('end_date') ?: date('Y-m-t');

        $rekap_data = $this->model->get_stock_summary($start_date, $end_date);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mutasi Barang Jadi');
        
        // --- Header dengan Company Name ---
        $sheet->setCellValue('A1', 'PT MINGBAO LIANCHUANG INTERNATIONAL');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A2', 'LAPORAN PERTANGGUNG JAWABAN MUTASI BARANG JADI');
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        
        // --- Periode ---
        $start_fmt = date('d M Y', strtotime($start_date));
        $end_fmt = date('d M Y', strtotime($end_date));
        $sheet->setCellValue('A3', "Periode: $start_fmt s/d $end_fmt");
        $sheet->mergeCells('A3:L3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');
        
        $headers = [
            'NO', 'KODE BARANG', 'NAMA BARANG', 'SATUAN BARANG', 
            'SALDO AWAL', 'PEMASUKAN', 'PENGELUARAN', 'PENYESUAIAN', 'SALDO AKHIR (SISTEM)',
            'HASIL PENCACAHAN', 'JUMLAH SELISIH', 'KETERANGAN'
        ];
        
        // Style header table
        $sheet->fromArray($headers, null, 'A5');
        $headerStyle = $sheet->getStyle('A5:L5');
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF667eea');
        $headerStyle->getAlignment()->setHorizontal('center')->setVertical('center');
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle('thin');
        
        $sheet->freezePane('A6');

        $row = 6;
        $no = 1;
        foreach ($rekap_data as $data) {
            $sheet->fromArray([
                $no++,
                $data->kode_barang,
                $data->nama_barang,
                $data->satuan,
                $data->saldo_awal,
                $data->pemasukan,
                $data->pengeluaran,
                $data->penyesuaian,
                $data->saldo_akhir,
                ($data->hasil_pencacahan !== NULL) ? $data->hasil_pencacahan : '',
                ($data->jumlah_selisih !== NULL) ? $data->jumlah_selisih : '',
                ''
            ], null, "A$row");
            
            // Format numbers dengan 2 desimal
            $sheet->getStyle("E$row:K$row")->getNumberFormat()->setFormatCode('#,##0.00');
            
            // Border untuk semua cells
            $sheet->getStyle("A$row:L$row")->getBorders()->getAllBorders()->setBorderStyle('thin');
            
            $row++;
        }
        
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Center align untuk kolom No, Satuan
        $sheet->getStyle("A6:A" . ($row-1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle("D6:D" . ($row-1))->getAlignment()->setHorizontal('center');
        
        // Right align untuk numeric columns
        $sheet->getStyle("E6:K" . ($row-1))->getAlignment()->setHorizontal('right');

        $writer = new Xlsx($spreadsheet);
        $filename = 'rekap_stok_fg_' . date('Ymd_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    // Ekspor Halaman 1 (Rekap) ke PDF
    public function export_rekap_pdf()
    {
        $data['start_date'] = $this->input->get('start_date') ?: date('Y-m-01');
        $data['end_date'] = $this->input->get('end_date') ?: date('Y-m-t');
        $data['rekap_data'] = $this->model->get_stock_summary($data['start_date'], $data['end_date']);
        $data['title'] = 'LAPORAN PERTANGGUNG JAWABAN MUTASI BARANG JADI';

        $html = $this->load->view('it_inventory/lap_mutasi_finished/pdf/rekap_pdf', $data, true);
        $this->generate_pdf($html, 'rekap_stok_fg.pdf', 'A3', 'landscape');
    }

    // Ekspor Halaman 2 (Detail Log) ke Excel
    public function export_detail_excel($fg_id)
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $item = $this->model->get_item_info($fg_id);
        $saldo_awal = $this->model->get_saldo_awal($fg_id, $start_date);
        $mutasi_data = $this->model->get_item_mutation_log($fg_id, $start_date, $end_date);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Mutasi');
        
        // --- Header dengan Company Name ---
        $sheet->setCellValue('A1', 'PT MINGBAO LIANCHUANG INTERNATIONAL');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A2', 'Detail Mutasi Barang Jadi: ' . $item->nama_barang);
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A3', 'Kode: ' . $item->kode_barang . ' | Satuan: ' . $item->satuan);
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');
        
        // --- Periode ---
        $start_fmt = date('d M Y', strtotime($start_date));
        $end_fmt = date('d M Y', strtotime($end_date));
        $sheet->setCellValue('A4', "Periode: $start_fmt s/d $end_fmt");
        $sheet->mergeCells('A4:G4');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal('center');
        
        // Style header table
        $headers = ['No', 'Tanggal', 'Dokumen', 'Keterangan', 'Masuk', 'Keluar', 'Saldo'];
        $sheet->fromArray($headers, null, 'A6');
        $headerStyle = $sheet->getStyle('A6:G6');
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF667eea');
        $headerStyle->getAlignment()->setHorizontal('center')->setVertical('center');
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle('thin');
        
        // Saldo Awal
        $sheet->setCellValue('A7', 'SALDO AWAL');
        $sheet->mergeCells('A7:F7');
        $sheet->setCellValue('G7', $saldo_awal);
        $sheet->getStyle('A7:F7')->getFont()->setBold(true);
        $sheet->getStyle('A7:G7')->getBorders()->getAllBorders()->setBorderStyle('thin');
        $sheet->getStyle('G7')->getNumberFormat()->setFormatCode('#,##0.00');
        
        $row = 8;
        $no = 1;
        foreach ($mutasi_data as $data) {
            $sheet->fromArray([
                $no++,
                date('d M Y', strtotime($data->transaction_date)),
                $data->doc_number,
                $data->description,
                $data->qty_in,
                $data->qty_out,
                $data->saldo_akhir
            ], null, "A$row");
            
            // Format numbers
            $sheet->getStyle("E$row:G$row")->getNumberFormat()->setFormatCode('#,##0.00');
            
            // Border untuk semua cells
            $sheet->getStyle("A$row:G$row")->getBorders()->getAllBorders()->setBorderStyle('thin');
            
            $row++;
        }
        
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Center align untuk kolom No
        $sheet->getStyle("A8:A" . ($row-1))->getAlignment()->setHorizontal('center');
        
        // Right align untuk numeric columns
        $sheet->getStyle("E8:G" . ($row-1))->getAlignment()->setHorizontal('right');
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'detail_mutasi_' . $item->kode_barang . '_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    // Ekspor Halaman 2 (Detail Log) ke PDF
    public function export_detail_pdf($fg_id)
    {
        $data['start_date'] = $this->input->get('start_date');
        $data['end_date'] = $this->input->get('end_date');
        $data['item'] = $this->model->get_item_info($fg_id);
        $data['saldo_awal'] = $this->model->get_saldo_awal($fg_id, $data['start_date']);
        $data['mutasi_data'] = $this->model->get_item_mutation_log($fg_id, $data['start_date'], $data['end_date']);
        $data['title'] = 'Detail Mutasi: ' . $data['item']->nama_barang;

        $html = $this->load->view('it_inventory/lap_mutasi_finished/pdf/detail_log_pdf', $data, true);
        $this->generate_pdf($html, 'detail_mutasi_' . $data['item']->kode_barang . '.pdf', 'A4', 'landscape');
    }
    
    // Ekspor Halaman 3 (Modal Penerimaan) ke PDF
    public function export_doc_penerimaan_pdf($id)
    {
        $data['detail'] = $this->model->get_detail_penerimaan($id);
        if(!$data['detail']) die('Data tidak ditemukan');
        $data['materials'] = $this->model->get_materials_for_production(
            $data['detail']->wo_header_id, 
            $data['detail']->production_header_id
        );
        $data['title'] = 'Bukti Terima Barang Jadi';
        
        $html = $this->load->view('it_inventory/lap_mutasi_finished/pdf/doc_penerimaan_pdf', $data, true);
        $this->generate_pdf($html, 'btbj_' . $data['detail']->receipt_number . '.pdf', 'A4', 'portrait');
    }
    
    // Ekspor Halaman 3 (Modal Pengiriman) ke PDF
    public function export_doc_pengiriman_pdf($id)
    {
        $data['detail'] = $this->model->get_detail_pengiriman($id);
        if(!$data['detail']) die('Data tidak ditemukan');
        $data['title'] = 'Surat Jalan';
        
        $html = $this->load->view('it_inventory/lap_mutasi_finished/pdf/doc_pengiriman_pdf', $data, true);
        $this->generate_pdf($html, 'sj_' . $data['detail']->delivery_number . '.pdf', 'A4', 'portrait');
    }

    /**
     * Export Enhanced BC Traceability to Excel
     * This provides comprehensive BC information including arrival dates
     */
    public function export_bc_traceability_excel($fg_receipt_detail_id)
    {
        $detail = $this->model->get_detail_penerimaan($fg_receipt_detail_id);
        if (!$detail) {
            show_error('Data penerimaan tidak ditemukan', 404);
        }

        $materials = $this->model->get_materials_for_production(
            $detail->wo_header_id,
            $detail->production_header_id
        );

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BC Traceability');

        // Header
        $sheet->mergeCells('A1:N1')->setCellValue('A1', 'Deep BC Traceability Report');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        
        $sheet->mergeCells('A2:N2')->setCellValue('A2', 'WO: ' . ($detail->wo_number ?? '-') . ' | Receipt: ' . ($detail->receipt_number ?? '-'));
        $sheet->getStyle('A2')->getFont()->setBold(true);

        // Column headers
        $headers = [
            'No', 'Kode Material', 'Nama Material', 'Qty Issued', 'Qty Terpakai', 'Satuan',
            'No. GRN', 'Tgl. GRN', 'No. PO', 'Tgl. PO', 'Supplier',
            'Tipe BC', 'No. BC', 'Tgl. BC', 'Tgl. Kedatangan'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFE6E6FA');
            $col++;
        }

        // Data rows
        $row = 5;
        $no = 1;
        foreach ($materials as $material) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $material['kode_material']);
            $sheet->setCellValue('C' . $row, $material['nama_material']);
            $sheet->setCellValue('D' . $row, $material['issued_qty']);
            $sheet->setCellValue('E' . $row, $material['qty_terpakai']);
            $sheet->setCellValue('F' . $row, $material['satuan']);
            $sheet->setCellValue('G' . $row, $material['grn_number']);
            $sheet->setCellValue('H' . $row, $material['grn_date'] ? date('d-M-Y', strtotime($material['grn_date'])) : '');
            $sheet->setCellValue('I' . $row, $material['po_number']);
            $sheet->setCellValue('J' . $row, $material['po_date'] ? date('d-M-Y', strtotime($material['po_date'])) : '');
            $sheet->setCellValue('K' . $row, $material['nama_supplier']);
            $sheet->setCellValue('L' . $row, $material['bc_type_code']);
            $sheet->setCellValue('M' . $row, $material['bc_number']);
            $sheet->setCellValue('N' . $row, $material['bc_date'] ? date('d-M-Y', strtotime($material['bc_date'])) : '');
            $sheet->setCellValue('O' . $row, $material['arrival_date'] ? date('d-M-Y', strtotime($material['arrival_date'])) : '');
            
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'O') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'BC_Traceability_' . ($detail->receipt_number ?? 'Unknown') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
    }

    /**
     * Export Enhanced BC Traceability to PDF
     */
    public function export_bc_traceability_pdf($fg_receipt_detail_id)
    {
        $data['detail'] = $this->model->get_detail_penerimaan($fg_receipt_detail_id);
        if (!$data['detail']) {
            show_error('Data penerimaan tidak ditemukan', 404);
        }

        $data['data_trace'] = $this->model->get_materials_with_full_bc_trace(
            $data['detail']->wo_header_id,
            $data['detail']->production_header_id
        );
        
        $data['title'] = 'Deep BC Traceability Report';
        $data['wo_number'] = $data['detail']->wo_number ?? '-';
        $data['sc_number'] = $data['detail']->sc_number ?? '-';
        $data['fg_name'] = 'N/A'; // Would need additional join to get FG name
        $data['fg_code'] = 'N/A';
        $data['is_pdf'] = true;

        $html = $this->load->view('it_inventory/lap_mutasi_finished/modal_traceability', $data, true);
        $this->generate_pdf($html, 'BC_Traceability_' . ($data['detail']->receipt_number ?? 'Unknown') . '.pdf', 'A3', 'landscape');
    }
    
    // Helper Generate PDF
    private function generate_pdf($html, $filename, $paper, $orientation)
    {
        $options = new Options();
        $options->set('isRemoteEnabled', TRUE);
        $options->set('defaultFont', 'Arial');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->render();
        $dompdf->stream($filename, array("Attachment" => 0));
    }

    /**
     * ===== FITUR BARU: LIHAT DOKUMEN ADJUSTMENT =====
     */
    public function ajax_get_adjustment_documents()
    {
        if (!$this->input->is_ajax_request()) { show_error('Forbidden', 403); return; }
        
        $adjustment_id = (int)$this->input->get('adjustment_id');
        if (empty($adjustment_id)) {
            echo json_encode(['success' => FALSE, 'data' => []]);
            return;
        }

        $this->load->model('Adjustment_model');
        $documents = $this->Adjustment_model->get_adjustment_documents($adjustment_id);
        
        echo json_encode(['success' => TRUE, 'data' => $documents]);
    }

    /**
     * Modal untuk menampilkan dokumen adjustment
     */
    public function modal_adjustment_documents($adjustment_id = 0)
    {
        $adjustment_id = (int)$adjustment_id;
        if (empty($adjustment_id)) {
            show_404();
            return;
        }

        $this->load->model('Adjustment_model');
        $header = $this->Adjustment_model->get_header_by_id($adjustment_id);
        if (!$header) {
            show_404();
            return;
        }

        $data['header'] = $header;
        $data['documents'] = $this->Adjustment_model->get_adjustment_documents($adjustment_id);
        
        $this->load->view('it_inventory/lap_mutasi_finished/modal_adjustment_documents', $data);
    }
}