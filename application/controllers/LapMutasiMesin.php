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
            'page_title' => 'Laporan Mutasi Mesin',
            'start'      => '2025-10-01', // Tanggal mulai
            'end'        => '2025-11-01'
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
        log_message('debug', 'LapMutasiMesin ajax_list: start=' . $start . ', end=' . $end . ', search=' . $search);
        log_message('debug', 'Rows count: ' . count($rows));
        if (!empty($rows)) {
            log_message('debug', 'First row keys: ' . json_encode(array_keys($rows[0])));
        }
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
        
        $sheet->setTitle('Laporan Mutasi Mesin');
        $sheet->mergeCells('A1:H1')->setCellValue('A1', 'Laporan Mutasi Mesin');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->mergeCells('A2:H2')->setCellValue('A2', "Periode: $start s/d $end");

        $header = ['No', 'Kode Mesin', 'Nama Mesin', 'Kategori', 'Saldo Awal', 'Masuk', 'Penyesuaian', 'Saldo Akhir'];
        $sheet->fromArray($header, null, 'A4');
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);

        $no = 1;
        $rowNum = 5;
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
            
            $sheet->getStyle("E$rowNum:H$rowNum")->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum++;
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

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

        $mpdf = new Mpdf(['format' => 'A4-L']);
        $mpdf->SetTitle('Laporan Mutasi Mesin');
        $mpdf->WriteHTML($html);
        $mpdf->Output('Laporan_Mutasi_Mesin_' . date('Ymd_His') . '.pdf', 'D');
    }
}