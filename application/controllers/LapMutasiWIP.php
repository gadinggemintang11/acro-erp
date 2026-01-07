<?php
defined('BASEPATH') OR exit('No direct script access allowed');



use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class LapMutasiWIP extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('LapMutasiWIP_model'); 
        $this->load->helper(['url']);
    }
    
    public function index() {
        $data = [
            'page_title' => 'Laporan Pertanggung Jawaban Mutasi WIP (Work In Progress)', 
            'start'      => date('Y-m-01'),
            'end'        => date('Y-m-t')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('it_inventory/lap_mutasi_wip/index', $data); 
        $this->load->view('templates/footer');
    }

    public function ajax_list() {
        $start  = $this->input->get('start') ?: date('Y-m-01');
        $end    = $this->input->get('end') ?: date('Y-m-t');
        $type   = $this->input->get('item_type');
        $search = $this->input->get('search');

        // Memanggil fungsi get_mutasi_wip dari model baru
        $rows = $this->LapMutasiWIP_model->get_mutasi_wip($start, $end, $type, $search);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    
    public function detail($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) {
            show_error('Parameter tidak lengkap.', 400);
            return;
        }
        $item_id = (int) $item_id;

        // Kita bisa pinjam fungsi dari model LAMA untuk mengambil data item
        // agar tidak duplikat kode.
        $this->load->model('LapMutasiMaterial_model');
        
        $data['item_type'] = $item_type;
        $data['item']      = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        
        
        $data['trace']     = $this->LapMutasiWIP_model->get_item_detail_wip($item_id, $item_type);

        
        $this->load->view('it_inventory/lap_mutasi_wip/detail_modal', $data);
    }
    
   
    public function trace_recursive($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) {
            show_error('Parameter tidak lengkap.', 400);
            return;
        }
        $item_id = (int) $item_id;

        
        $this->load->model('LapMutasiMaterial_model');
        $data['item_type'] = $item_type;
        $data['item'] = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        
        
        $data['trace'] = $this->LapMutasiWIP_model->trace_recursive($item_id, $item_type, 25);

        // Menggunakan ulang view modal trace recursive yang sudah kita buat
        $this->load->view('it_inventory/lap_mutasi_material/trace_recursive_modal', $data);
    }

    public function export_detail_excel($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) { show_error('Parameter tidak lengkap.', 400); }
        $item_id = (int) $item_id;

        $this->load->model('LapMutasiMaterial_model');
        $item_info = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $trace_data = $this->LapMutasiWIP_model->get_item_detail_wip($item_id, $item_type);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kartu Stok WIP');

     
        $sheet->setCellValue('A1', 'Kartu Stok WIP: ' . ($item_info->name ?? 'N/A'));
        $sheet->setCellValue('A2', 'Kode: ' . ($item_info->code ?? 'N/A'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        
        $headers = ['Tgl. Transaksi', 'Tipe', 'Dokumen Sumber (Traceability)', 'Masuk', 'Keluar', 'Saldo'];
        $sheet->fromArray($headers, NULL, 'A4');
        $sheet->getStyle('A4:F4')->getFont()->setBold(true);

        
        $rowNum = 5;
        foreach ($trace_data as $t) {
            $doc_info = '';
            switch($t->transaction_type) {
                case 'wip_in': $doc_info = ($item_type == 'material') ? "MR No: {$t->mr_number}" : "Prod. No: {$t->prod_num_in} (Stage: {$t->stage_in})"; break;
                case 'wip_out': $doc_info = "Prod. No: {$t->prod_num_out} (Stage: {$t->stage_out})"; break;
                case 'IN_RETURN_WIP': $doc_info = "Return No: {$t->prod_return_no}"; break;
            }
            $sheet->fromArray([
                date('Y-m-d H:i', strtotime($t->transaction_date)), $t->transaction_type, $doc_info,
                (float)$t->qty_in, (float)$t->qty_out, (float)$t->balance
            ], NULL, 'A'.$rowNum);
            $sheet->getStyle('D'.$rowNum.':F'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum++;
        }

        foreach(range('A','F') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Kartu_Stok_WIP_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $item_info->code ?? $item_id) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function export_detail_pdf($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) { show_error('Parameter tidak lengkap.', 400); }
        $item_id = (int) $item_id;

        $this->load->model('LapMutasiMaterial_model');
        $data['item'] = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $data['trace'] = $this->LapMutasiWIP_model->get_item_detail_wip($item_id, $item_type);
        $data['title'] = 'Kartu Stok WIP';
        $data['item_type'] = $item_type;

        $html = $this->load->view('it_inventory/lap_mutasi_wip/pdf_detail_template', $data, true);
        $mpdf = new Mpdf(['format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('Kartu_Stok_WIP_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['item']->code ?? $item_id) . '.pdf', 'I');
    }

    public function export_trace_excel($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) { show_error('Parameter tidak lengkap.', 400); }
        $item_id = (int) $item_id;

        $this->load->model('LapMutasiMaterial_model');
        $item_info = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $trace_data = $this->LapMutasiWIP_model->trace_recursive($item_id, $item_type, 1000);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jejak Produksi');

        $sheet->setCellValue('A1', 'Jejak Produksi (WIP): ' . ($item_info->name ?? 'N/A'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Tgl. Produksi', 'No. Produksi', 'No. WO', 'Arah Jejak', 'Item Terkait', 'Qty Terkait', 'Satuan'];
        $sheet->fromArray($headers, NULL, 'A3');
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);

        $rowNum = 4;
        foreach ($trace_data as $t) {
            $sheet->fromArray([
                date('Y-m-d', strtotime($t->transaction_date)), $t->production_number, $t->wo_number,
                ($t->direction == 'input' ? 'DIBUAT DARI' : 'DIPAKAI UNTUK'),
                $t->related_item_name, (float)$t->related_qty, $t->related_satuan
            ], NULL, 'A'.$rowNum++);
        }

        foreach(range('A','G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Jejak_Produksi_WIP_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $item_info->code ?? $item_id) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function export_trace_pdf($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) { show_error('Parameter tidak lengkap.', 400); }
        $item_id = (int) $item_id;

        $this->load->model('LapMutasiMaterial_model');
        $data['item'] = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $data['trace'] = $this->LapMutasiWIP_model->trace_recursive($item_id, $item_type, 1000);
        $data['title'] = 'Jejak Produksi (WIP)';
        $data['item_type'] = $item_type;

        
        $html = $this->load->view('it_inventory/lap_mutasi_wip/pdf_trace_template', $data, true);
        
        try {
            $mpdf = new Mpdf(['format' => 'A4-P']);
            $mpdf->WriteHTML($html);
            $mpdf->Output('Jejak_Produksi_WIP_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['item']->code ?? $item_id) . '.pdf', 'I');
        } catch (\Mpdf\MpdfException $e) {
            log_message('error', 'PDF Export Error (LapMutasiWIP): ' . $e->getMessage());
            show_error('Gagal membuat PDF.');
        }
    }

   
}