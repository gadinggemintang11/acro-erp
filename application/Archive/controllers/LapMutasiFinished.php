<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Memuat pustaka ekspor dari vendor/autoload.php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class LapMutasiFinished extends CI_Controller {

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
        $data['title'] = 'Laporan Pertanggung Jawaban Mutasi Barang Jadi';
        
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
        $type = $this->input->get('type');
        $id = $this->input->get('id');
        
        $data['detail'] = null;
        $view_path = '';

        try {
            switch ($type) {
                case 'fg_in':
                    $data['detail'] = $this->model->get_detail_penerimaan($id);
                    if($data['detail']) {
                        $data['materials'] = $this->model->get_materials_for_production(
                            $data['detail']->wo_header_id, 
                            $data['detail']->production_header_id
                        );
                    }
                    $view_path = 'it_inventory/lap_mutasi_finished/partials/modal_penerimaan';
                    break;
                case 'fg_out':
                    $data['detail'] = $this->model->get_detail_pengiriman($id);
                    $view_path = 'it_inventory/lap_mutasi_finished/partials/modal_pengiriman';
                    break;
                case 'adjustment':
                     $data['detail'] = $this->model->get_detail_adjustment($id);
                     $view_path = 'it_inventory/lap_mutasi_finished/partials/modal_adjustment';
                    break;
                default:
                    echo "<div class='alert alert-danger'>Tipe transaksi tidak dikenal.</div>";
                    return;
            }

            if (!$data['detail']) {
                echo "<div class='alert alert-danger'>Data detail tidak ditemukan (ID: $id).</div>";
                return;
            }

            $this->load->view($view_path, $data);

        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Terjadi error: " . $e->getMessage() . "</div>";
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
        $sheet->setTitle('Rekapitulasi Stok FG');
        
        $sheet->mergeCells('A1:L1')->setCellValue('A1', 'Laporan PERTANGGUNG JAWABAN MUTASI Barang Jadi');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        
        $sheet->mergeCells('A2:L2')->setCellValue('A2', 'Periode: ' . date('d M Y', strtotime($start_date)) . ' s/d ' . date('d M Y', strtotime($end_date)));
        
        $headers = [
            'NO', 'KODE BARANG', 'NAMA BARANG', 'SATUAN BARANG', 
            'SALDO AWAL', 'PEMASUKAN', 'PENGELUARAN', 'PENYESUAIAN', 'SALDO AKHIR (SISTEM)',
            'HASIL PENCACAHAN', 'JUMLAH SELISIH', 'KETERANGAN'
        ];
        $col = 'A';
        $sheet->freezePane('A5');
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $sheet->getStyle($col . '4')->getFont()->setBold(true);
            $sheet->getStyle($col . '4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFE0');
            $sheet->getStyle($col . '4')->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $col++;
        }

        $row = 5;
        $no = 1;
        foreach ($rekap_data as $data) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $data->kode_barang);
            $sheet->setCellValue('C' . $row, $data->nama_barang);
            $sheet->setCellValue('D' . $row, $data->satuan);
            $sheet->setCellValue('E' . $row, $data->saldo_awal);
            $sheet->setCellValue('F' . $row, $data->pemasukan);
            $sheet->setCellValue('G' . $row, $data->pengeluaran);
            $sheet->setCellValue('H' . $row, $data->penyesuaian);
            $sheet->setCellValue('I' . $row, $data->saldo_akhir);
            
            $sheet->setCellValue('J' . $row, ($data->hasil_pencacahan !== NULL) ? $data->hasil_pencacahan : '');
            $sheet->setCellValue('K' . $row, ($data->jumlah_selisih !== NULL) ? $data->jumlah_selisih : '');
            
            $sheet->setCellValue('L' . $row, '');
            
            $sheet->getStyle('E' . $row . ':K' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            $row++;
        }
        
        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $sheet->getStyle('I5:I'.$row)->getFont()->setBold(true);

        $writer = new Xlsx($spreadsheet);
        $filename = 'rekap_stok_fg_' . $start_date . '_sd_' . $end_date . '.xlsx';
        
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
        
        $sheet->mergeCells('A1:G1')->setCellValue('A1', 'Detail Mutasi: ' . $item->nama_barang);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->mergeCells('A2:G2')->setCellValue('A2', 'Kode: ' . $item->kode_barang);
        $sheet->mergeCells('A3:G3')->setCellValue('A3', 'Periode: ' . $start_date . ' s/d ' . $end_date);
        
        $sheet->setCellValue('A5', 'No');
        $sheet->setCellValue('B5', 'Tanggal');
        $sheet->setCellValue('C5', 'Dokumen');
        $sheet->setCellValue('D5', 'Keterangan');
        $sheet->setCellValue('E5', 'Masuk');
        $sheet->setCellValue('F5', 'Keluar');
        $sheet->setCellValue('G5', 'Saldo');
        
        $sheet->mergeCells('A6:F6')->setCellValue('A6', 'SALDO AWAL');
        $sheet->setCellValue('G6', $saldo_awal);
        
        $row = 7;
        $no = 1;
        foreach ($mutasi_data as $data) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $data->transaction_date);
            $sheet->setCellValue('C' . $row, $data->doc_number);
            $sheet->setCellValue('D' . $row, $data->description);
            $sheet->setCellValue('E' . $row, $data->qty_in);
            $sheet->setCellValue('F' . $row, $data->qty_out);
            $sheet->setCellValue('G' . $row, $data->saldo_akhir);
            $row++;
        }
        
        foreach (range('A', 'G') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'detail_mutasi_' . $item->kode_barang . '.xlsx';
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
}