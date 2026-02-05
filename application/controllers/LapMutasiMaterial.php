<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Mpdf\Mpdf;

class LapMutasiMaterial extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('LapMutasiMaterial_model');
        $this->load->helper(['url', 'file']);
    }

    public function index() {
        // Ambil parameter dari GET request, atau gunakan default jika tidak ada
        $start = $this->input->get('start') ?: date('Y-m-01');
        $end = $this->input->get('end') ?: date('Y-m-t');
        
        $data = [
            'page_title' => 'Laporan Pertanggung Jawaban Mutasi Bahan Baku dan Bahan Penolong',
            'start'      => $start,
            'end'        => $end
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('it_inventory/lap_mutasi_material/index', $data);
        $this->load->view('templates/footer');
    }

    public function ajax_list() {
        $start  = $this->input->get('start') ?: date('Y-m-01');
        $end    = $this->input->get('end') ?: date('Y-m-t');
        $type   = $this->input->get('item_type');
        $search = $this->input->get('search');

        $rows = $this->LapMutasiMaterial_model->get_mutasi($start, $end, $type, $search);
        echo json_encode(['success' => true, 'data' => $rows]);
    }

    public function detail($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) {
            show_error('Parameter tidak lengkap untuk detail material.', 400);
            return;
        }

        $item_id = (int) $item_id;

        $data['item_type'] = $item_type;
        $data['item']      = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $data['trace']     = $this->LapMutasiMaterial_model->get_item_detail($item_id, $item_type);

        $this->load->view('it_inventory/lap_mutasi_material/detail_modal', $data);
    }

    public function trace_recursive($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) {
            show_error('Parameter tidak lengkap untuk recursive trace.', 400);
            return;
        }

        $item_id = (int) $item_id;

        $data['item_type'] = $item_type;
        $data['item']    = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $data['trace']   = $this->LapMutasiMaterial_model->trace_recursive($item_id, $item_type, 25);

        $this->load->view('it_inventory/lap_mutasi_material/trace_recursive_modal', $data);
    }

    // ======================================================
    // EXPORT FUNCTIONS FOR MODALS
    // ======================================================

    public function export_detail_excel($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) { show_error('Parameter tidak lengkap.', 400); }
        $item_id = (int) $item_id;

        $item_info = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $trace_data = $this->LapMutasiMaterial_model->get_item_detail($item_id, $item_type);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Kartu Stok');

        // Header
        $sheet->setCellValue('A1', 'Kartu Stok Gudang: ' . ($item_info->name ?? 'N/A'));
        $sheet->setCellValue('A2', 'Kode: ' . ($item_info->code ?? 'N/A'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Table Headers
        $headers = ['Tgl. Transaksi', 'Tipe', 'No. Dokumen', 'Ref. Sumber', 'Tipe BC', 'No. BC', 'Tgl. BC', 'Keterangan', 'Masuk', 'Keluar', 'Saldo'];
        $sheet->fromArray($headers, NULL, 'A4');
        $sheet->getStyle('A4:K4')->getFont()->setBold(true);

        // Data
        $rowNum = 5;
        foreach ($trace_data as $t) {
            $doc_num = ''; $doc_ref = ''; $bc_type = ''; $bc_num = ''; $bc_date_formatted = ''; $notes = '';
            switch($t->transaction_type) {
                case 'in': $doc_num = $t->grn_number; $doc_ref = $t->po_number; $bc_type = $t->bc_type_code; $bc_num = $t->bc_number; $bc_date_formatted = $t->bc_date ? date('Y-m-d', strtotime($t->bc_date)) : ''; break;
                case 'production_out':
                case 'OUT_ISSUE': $doc_num = $t->issuance_number ?: $t->mr_number; $doc_ref = $t->mr_number; break;
                case 'IN_RETURN_WIP': $doc_num = $t->prod_return_no; break;
                case 'adjustment': $doc_num = $t->adjustment_doc; $notes = $t->adjustment_notes; break;
            }
            $sheet->fromArray([
                date('Y-m-d H:i', strtotime($t->transaction_date)), $t->transaction_type, $doc_num, $doc_ref, $bc_type, $bc_num, $bc_date_formatted, $notes,
                (float)$t->qty_in, (float)$t->qty_out, (float)$t->balance
            ], NULL, 'A'.$rowNum);
            $sheet->getStyle('I'.$rowNum.':K'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum++;
        }

        foreach(range('A','K') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Kartu_Stok_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $item_info->code) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function export_detail_pdf($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) { show_error('Parameter tidak lengkap.', 400); }
        $item_id = (int) $item_id;

        $data['item'] = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $data['trace'] = $this->LapMutasiMaterial_model->get_item_detail($item_id, $item_type);
        $data['title'] = 'Kartu Stok Gudang';

        $html = $this->load->view('it_inventory/lap_mutasi_material/pdf_detail_template', $data, true);
        $mpdf = new Mpdf(['format' => 'A4-L']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('Kartu_Stok_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['item']->code) . '.pdf', 'I');
    }

    public function export_trace_excel($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) { show_error('Parameter tidak lengkap.', 400); }
        $item_id = (int) $item_id;

        $item_info = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $trace_data = $this->LapMutasiMaterial_model->trace_recursive($item_id, $item_type, 1000); // Limit besar untuk ekspor

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
        $filename = 'Jejak_Produksi_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $item_info->code) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function export_trace_pdf($item_type = null, $item_id = null) {
        if (empty($item_type) || empty($item_id)) { show_error('Parameter tidak lengkap.', 400); }
        $item_id = (int) $item_id;

        $data['item'] = $this->LapMutasiMaterial_model->get_item_basic($item_type, $item_id);
        $data['trace'] = $this->LapMutasiMaterial_model->trace_recursive($item_id, $item_type, 1000);
        $data['title'] = 'Jejak Produksi (WIP)';

        $html = $this->load->view('it_inventory/lap_mutasi_material/pdf_trace_template', $data, true);
        $mpdf = new Mpdf(['format' => 'A4-P']);
        $mpdf->WriteHTML($html);
        $mpdf->Output('Jejak_Produksi_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['item']->code) . '.pdf', 'I');
    }

    public function get_item_detail($item_id, $item_type) 
    {
        $this->db->select("
            sl.transaction_date, sl.transaction_type, sl.qty_in, sl.qty_out, sl.balance,
            gh.grn_number, gh.bc_type_code, gh.bc_number, gh.bc_date, ph.po_number,
            mrh.mr_number, mrh.issuance_number,
            prh.prod_return_no,
            ah.doc_no as adjustment_doc, ah.notes as adjustment_notes, ah.doc_date as adjustment_date
        ");
        
        $this->db->from('stock_ledger sl');
        
        $this->db->join('grn_header gh', 'sl.transaction_id = gh.id AND sl.transaction_type = \'in\'', 'left');
        $this->db->join('po_header ph', 'sl.source_doc_id = ph.id AND sl.source_doc_type = \'PO\'', 'left');
        
        $this->db->join('mr_header mrh', 'sl.transaction_id = mrh.id AND sl.transaction_type IN (\'production_out\', \'OUT_ISSUE\')', 'left');
        
        $this->db->join('prod_return_detail prd', 'sl.transaction_id = prd.id', 'left');
        $this->db->join('prod_return_header prh', 'prd.prod_return_id = prh.id AND sl.transaction_type = \'IN_RETURN_WIP\'', 'left');
        
        $this->db->join('adjustment_details ad', 'sl.transaction_id = ad.id', 'left');
        $this->db->join('adjustment_header ah', 'ad.adjustment_header_id = ah.id AND sl.transaction_type = \'adjustment\'', 'left');
        
        $this->db->where('sl.item_id', $item_id);
        $this->db->where('sl.item_type', $item_type);
        
        // Exclude transaksi WIP internal yang tidak mempengaruhi stok fisik gudang
        $this->db->where_not_in('sl.transaction_type', ['wip_in', 'wip_out', 'wip_out_reversal', 'production_out']);
        
        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');
        
        return $this->db->get()->result();
    }

    public function export_excel() {
        $start  = $this->input->get('start') ?: date('Y-m-01');
        $end    = $this->input->get('end') ?: date('Y-m-t');
        $type   = $this->input->get('item_type');
        $search = $this->input->get('search');

        $rows = $this->LapMutasiMaterial_model->get_mutasi($start, $end, $type, $search);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Sheet title max 31 characters
        $sheet->setTitle('Mutasi Material');

        // --- Header dengan Company Name ---
        $sheet->setCellValue('A1', 'PT MINGBAO LIANCHUANG INTERNATIONAL');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A2', 'Laporan Mutasi Bahan Baku dan Bahan Penolong');
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
        
        // --- Periode ---
        $start_fmt = date('d M Y', strtotime($start));
        $end_fmt = date('d M Y', strtotime($end));
        $sheet->setCellValue('A3', "Periode: $start_fmt s/d $end_fmt");
        $sheet->mergeCells('A3:M3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');

        // --- Header Table ---
        $header = [
            'No', 'Kode Barang', 'Nama Barang', 'Satuan', 'Jenis Item',
            'Saldo Awal', 'Pemasukan', 'Retur Prod.', 'Pengeluaran Prod.', 
            'Penyesuaian', 'Saldo Akhir (Sistem)', 'Hasil Pencacahan', 'Jumlah Selisih'
        ];
        $sheet->fromArray($header, null, 'A5');
        
        // Style header table
        $headerStyle = $sheet->getStyle('A5:M5');
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
                $r['kode_barang'],
                $r['nama_barang'],
                $r['satuan'],
                $r['item_type'],
                $r['saldo_awal'],
                $r['pemasukan'],
                $r['retur'],
                $r['pengeluaran'],
                $r['penyesuaian'],
                $r['saldo_akhir'],
                ($r['hasil_pencacahan'] !== NULL) ? $r['hasil_pencacahan'] : '',
                ($r['jumlah_selisih'] !== NULL) ? $r['jumlah_selisih'] : '',
            ], null, "A$rowNum");
            
            // Format numbers dengan 2 desimal
            $sheet->getStyle("F$rowNum:M$rowNum")->getNumberFormat()->setFormatCode('#,##0.00');
            
            // Border untuk semua cells
            $sheet->getStyle("A$rowNum:M$rowNum")->getBorders()->getAllBorders()->setBorderStyle('thin');
            
            $rowNum++;
        }

        // --- Column width dan alignment ---
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Center align untuk kolom No, Satuan, Jenis Item
        $sheet->getStyle("A6:A" . ($rowNum-1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle("D6:D" . ($rowNum-1))->getAlignment()->setHorizontal('center');
        $sheet->getStyle("E6:E" . ($rowNum-1))->getAlignment()->setHorizontal('center');
        
        // Right align untuk numeric columns
        $sheet->getStyle("F6:M" . ($rowNum-1))->getAlignment()->setHorizontal('right');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Laporan_Mutasi_Material_' . date('Ymd_His') . '.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    public function export_pdf() {
        $start  = $this->input->get('start') ?: date('Y-m-01');
        $end    = $this->input->get('end') ?: date('Y-m-t');
        $type   = $this->input->get('item_type');
        $search = $this->input->get('search');

        $rows = $this->LapMutasiMaterial_model->get_mutasi($start, $end, $type, $search);

        $data = [
            'rows'  => $rows,
            'start' => $start,
            'end'   => $end
        ];

        $html = $this->load->view('it_inventory/lap_mutasi_material/pdf_view', $data, true); 

        try {
            $mpdf = new Mpdf([
                'format' => 'A4-L',
                'margin_top' => 15,
                'margin_bottom' => 15,
                'margin_left' => 10,
                'margin_right' => 10,
            ]);

            $mpdf->SetTitle('Laporan Mutasi Bahan Baku dan Bahan Penolong');
            $mpdf->WriteHTML($html);
            $mpdf->Output('Laporan_Mutasi_Material_' . date('Ymd_His') . '.pdf', 'D');
        } catch (\Mpdf\MpdfException $e) {
            log_message('error', 'PDF Export Error (LapMutasiMaterial): ' . $e->getMessage());
            show_error('Gagal membuat PDF.');
        }
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
        
        $this->load->view('it_inventory/lap_mutasi_material/modal_adjustment_documents', $data);
    }
}