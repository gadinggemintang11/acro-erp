<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class LapMutasiMesin extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('LapMutasiMesin_model');
        $this->load->helper(['url', 'file']);
    }

    public function index() {
        $data = [
            'page_title' => 'Laporan Mutasi Mesin & Peralatan Mekanik',
            'start'      => date('Y-m-01'),
            'end'        => date('Y-m-t')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('it_inventory/lap_mutasi_mesin/index', $data);
        $this->load->view('templates/footer');
    }

    public function ajax_list() {
        $start  = $this->input->get('start') ?: date('Y-m-01');
        $end    = $this->input->get('end') ?: date('Y-m-t');
        $search = $this->input->get('search');

        $rows = $this->LapMutasiMesin_model->get_mutasi_mesin($start, $end, $search);
        
        // ========================================================
        // LOGGING KRITIS UNTUK DEBUGGING
        // ========================================================
        if (empty($rows)) {
            // Ambil query SQL terakhir yang dijalankan model
            $last_query = $this->db->last_query();
            log_message('error', 'LapMutasiMesin KOSONG. Cek query: ' . $last_query);
        }
        // ========================================================
        
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function detail($item_id = null) {
        if (empty($item_id)) {
            show_error('Parameter tidak lengkap.', 400);
            return;
        }
        $item_id = (int) $item_id;

        $data['item']  = $this->LapMutasiMesin_model->get_item_basic_mesin($item_id);
        $data['trace'] = $this->LapMutasiMesin_model->get_item_detail_mesin($item_id);

        $this->load->view('it_inventory/lap_mutasi_mesin/detail_modal', $data);
    }

    public function export_excel() {
        $start  = $this->input->get('start') ?: date('Y-m-01');
        $end    = $this->input->get('end') ?: date('Y-m-t');
        $search = $this->input->get('search');

        $rows = $this->LapMutasiMesin_model->get_mutasi_mesin($start, $end, $search);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Sheet title max 31 characters
        $sheet->setTitle('Mutasi Mesin');

        // --- Header dengan Company Name ---
        $sheet->setCellValue('A1', 'PT NEW SPECIALIFE INDUSTRIES');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A2', 'Laporan Mutasi Mesin & Peralatan Mekanik');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        
        // --- Periode ---
        $start_fmt = date('d M Y', strtotime($start));
        $end_fmt = date('d M Y', strtotime($end));
        $sheet->setCellValue('A3', "Periode: $start_fmt s/d $end_fmt");
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');

        // --- Header Table ---
        $header = ['No', 'Kode Mesin', 'Nama Mesin', 'Kategori', 'Saldo Awal', 'Masuk', 'Penyesuaian', 'Saldo Akhir'];
        $sheet->fromArray($header, null, 'A5');
        
        // Style header table
        $headerStyle = $sheet->getStyle('A5:H5');
        $headerStyle->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF667eea');
        $headerStyle->getAlignment()->setHorizontal('center')->setVertical('center');
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle('thin');

        // --- Data rows ---
        $no = 1;
        $rowNum = 6;
        foreach ($rows as $r) {
            $sheet->fromArray([
                $no++,
                $r['kode_mesin'],
                $r['nama_mesin'],
                $r['kategori'],
                $r['saldo_awal'],
                $r['total_masuk'],
                $r['total_penyesuaian'],
                $r['saldo_akhir']
            ], null, "A$rowNum");
            
            // Format numbers dengan 2 desimal
            $sheet->getStyle("E$rowNum:H$rowNum")->getNumberFormat()->setFormatCode('#,##0.00');
            
            // Border untuk semua cells
            $sheet->getStyle("A$rowNum:H$rowNum")->getBorders()->getAllBorders()->setBorderStyle('thin');
            
            $rowNum++;
        }

        // --- Column width dan alignment ---
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Center align untuk kolom No dan Kategori
        $sheet->getStyle("A6:A" . ($rowNum-1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle("D6:D" . ($rowNum-1))->getAlignment()->setHorizontal('center');
        
        // Right align untuk numeric columns
        $sheet->getStyle("E6:H" . ($rowNum-1))->getAlignment()->setHorizontal('right');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan_Mutasi_Mesin_' . date('Ymd_His') . '.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function export_pdf() {
        $start  = $this->input->get('start') ?: date('Y-m-01');
        $end    = $this->input->get('end') ?: date('Y-m-t');
        $search = $this->input->get('search');

        $rows = $this->LapMutasiMesin_model->get_mutasi_mesin($start, $end, $search);

        $data = [
            'rows'  => $rows,
            'start' => $start,
            'end'   => $end
        ];

        $html = $this->load->view('it_inventory/lap_mutasi_mesin/pdf_view', $data, true); 

        try {
            $mpdf = new Mpdf([
                'format' => 'A4-L',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 10,
                'margin_right' => 10,
            ]);

            $mpdf->SetTitle('Laporan Mutasi Mesin & Peralatan Mekanik');
            $mpdf->WriteHTML($html);
            $mpdf->Output('Laporan_Mutasi_Mesin_' . date('Ymd_His') . '.pdf', 'D');
        } catch (\Mpdf\MpdfException $e) {
            log_message('error', 'PDF Export Error (LapMutasiMesin): ' . $e->getMessage());
            show_error('Gagal membuat PDF.');
        }
    }
}