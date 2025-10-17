<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php'; // Muat Composer
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;


class Stock_ledger extends CI_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Stock_ledger_model');
        $this->load->library('pagination');
        $this->load->helper('url');
    }

    public function index() {
        if (!check_access('stock_ledger', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }

        // --- Handle Filter ---
        $month = $this->input->get('month') ?: date('m'); // Default bulan ini
        $year = $this->input->get('year') ?: date('Y'); // Default tahun ini
        $warehouse_type = $this->input->get('warehouse_type');
        $search = $this->input->get('search');

        // Tentukan tanggal awal dan akhir berdasarkan bulan & tahun
        $start_date = $year . '-' . $month . '-01';
        $end_date = date('Y-m-t', strtotime($start_date)); // Tanggal terakhir di bulan itu

        $filters = [
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'warehouse_type' => $warehouse_type,
            'search'         => $search
        ];

        // --- Pagination Config ---
        $config['base_url'] = base_url('stock_ledger/index');
        $config['total_rows'] = $this->Stock_ledger_model->count_stock_summary_report($filters);
        $config['per_page'] = 20; // 20 item per halaman
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE; // Menggunakan nomor halaman
        $config['num_links'] = 2; // Jumlah link halaman di sekitar halaman aktif

        // Styling Pagination Bootstrap 4
        $config['full_tag_open'] = '<nav><ul class="pagination pagination-sm justify-content-center flex-wrap">'; // flex-wrap untuk mobile
        $config['full_tag_close'] = '</ul></nav>';
        $config['first_link'] = '&laquo; First'; $config['first_tag_open'] = '<li class="page-item">'; $config['first_tag_close'] = '</li>';
        $config['last_link'] = 'Last &raquo;'; $config['last_tag_open'] = '<li class="page-item">'; $config['last_tag_close'] = '</li>';
        $config['next_link'] = 'Next &rsaquo;'; $config['next_tag_open'] = '<li class="page-item">'; $config['next_tag_close'] = '</li>';
        $config['prev_link'] = '&lsaquo; Prev'; $config['prev_tag_open'] = '<li class="page-item">'; $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">'; $config['cur_tag_close'] = '</span></li>';
        $config['num_tag_open'] = '<li class="page-item">'; $config['num_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');

        $this->pagination->initialize($config);
        $page = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 1;
        $offset = ($page > 0) ? ($page - 1) * $config['per_page'] : 0;

        // --- Get Data ---
        $data = [
            'title'     => 'Laporan Stok Barang',
            'report'    => $this->Stock_ledger_model->get_stock_summary_report($filters, $config['per_page'], $offset),
            'pagination'=> $this->pagination->create_links(),
            'filters'   => $filters, // Kirim filter ke view
            'selected_month' => $month, // Untuk dropdown bulan
            'selected_year' => $year,   // Untuk dropdown tahun
            'item_types'=> [
                'material' => 'Material', 'machine' => 'Machine', 'inventory' => 'Inventory',
                'semifinished' => 'Semifinished', 'finished' => 'Finished'
            ]
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stock_ledger/index', $data);
        // Footer dipanggil di view
    }

    // ✅ BARU: Endpoint AJAX untuk detail transaksi (modal)
    public function get_item_details() {
        $item_id = $this->input->get('item_id');
        $item_type = $this->input->get('item_type');
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');

        if (!$item_id || !$item_type) {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap.']);
            return;
        }

        $history = $this->Stock_ledger_model->get_item_transaction_history($item_id, $item_type, $start_date, $end_date);

        // Buat HTML tabel untuk modal
        $html = '<table class="table table-sm table-bordered">';
        $html .= '<thead class="thead-light"><tr><th>Tanggal</th><th>Tipe</th><th>Ref Doc</th><th>Partner</th><th>BC Info</th><th>Qty In</th><th>Qty Out</th><th>Saldo</th></tr></thead><tbody>';
        if (!empty($history)) {
            foreach ($history as $row) {
                $html .= '<tr>';
                $html .= '<td>' . date('d/m/y H:i', strtotime($row->transaction_date)) . '</td>';
                $html .= '<td>' . ucfirst($row->transaction_type) . '</td>';
                $html .= '<td>' . htmlspecialchars($row->reference_doc) . '</td>';
                $html .= '<td>' . htmlspecialchars($row->partner_name ?? '-') . '</td>';
                $html .= '<td>' . htmlspecialchars(($row->bc_type_code && $row->bc_number) ? $row->bc_type_code.'/'.$row->bc_number : '-') . '</td>';
                $html .= '<td class="text-right">' . ($row->qty_in > 0 ? number_format($row->qty_in, 2) : '-') . '</td>';
                $html .= '<td class="text-right">' . ($row->qty_out > 0 ? number_format($row->qty_out, 2) : '-') . '</td>';
                $html .= '<td class="text-right">' . number_format($row->balance, 2) . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="8" class="text-center">Tidak ada histori transaksi pada periode ini.</td></tr>';
        }
        $html .= '</tbody></table>';

        echo json_encode(['success' => true, 'html' => $html]);
    }

    // ✅ BARU: Fungsi Export PDF
    public function export_pdf() {
       if (!check_access('stock_ledger', 'view')) { show_error('Tidak ada izin.', 403); }
        // Ambil filter dari GET
        $month = $this->input->get('month') ?: date('m'); $year = $this->input->get('year') ?: date('Y');
        $warehouse_type = $this->input->get('warehouse_type'); $search = $this->input->get('search');
        $start_date = $year . '-' . $month . '-01'; $end_date = date('Y-m-t', strtotime($start_date));
        $filters = ['start_date' => $start_date, 'end_date' => $end_date, 'warehouse_type' => $warehouse_type, 'search' => $search];

        // Ambil SEMUA data sesuai filter (limit = 0)
        $report_data = $this->Stock_ledger_model->get_stock_summary_report($filters, 0, 0);

        $data = [
            'report' => $report_data,
            'periode' => date('F Y', strtotime($start_date)),
            'warehouse_type' => $warehouse_type ? ($this->config->item('item_types')[$warehouse_type] ?? ucfirst($warehouse_type)) : 'Semua Gudang',
            'search' => $search
        ];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('stock_ledger/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // Landscape untuk tabel lebar
        $dompdf->render();
        $filename = "Laporan_Stok_".date('M_Y', strtotime($start_date)).".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
    }

    // ✅ BARU: Fungsi Export Excel
    public function export_excel() {
         if (!check_access('stock_ledger', 'view')) { show_error('Tidak ada izin.', 403); }
         // Ambil filter dari GET
        $month = $this->input->get('month') ?: date('m'); $year = $this->input->get('year') ?: date('Y');
        $warehouse_type = $this->input->get('warehouse_type'); $search = $this->input->get('search');
        $start_date = $year . '-' . $month . '-01'; $end_date = date('Y-m-t', strtotime($start_date));
        $filters = ['start_date' => $start_date, 'end_date' => $end_date, 'warehouse_type' => $warehouse_type, 'search' => $search];

        // Ambil SEMUA data sesuai filter
        $report_data = $this->Stock_ledger_model->get_stock_summary_report($filters, 0, 0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $periode = date('F Y', strtotime($start_date));
        $sheet->setTitle("Laporan Stok " . date('M Y', strtotime($start_date)));

        // --- Header Laporan ---
        $sheet->setCellValue('A1', 'LAPORAN STOK BARANG'); $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A2', 'Periode: ' . $periode); $sheet->mergeCells('A2:I2');
        // Tambahkan info filter lain jika perlu
        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // --- Header Tabel ---
        $headerRow = 4;
        $headers = ['Gudang', 'Kode Barang', 'Nama Barang', 'Satuan', 'Stok Awal', 'Masuk', 'Return', 'Keluar', 'Stok Akhir'];
        $sheet->fromArray($headers, NULL, 'A'.$headerRow);
        $sheet->getStyle('A'.$headerRow.':I'.$headerRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$headerRow.':I'.$headerRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.$headerRow.':I'.$headerRow)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

        // --- Isi Data ---
        $rowNum = $headerRow + 1;
        foreach ($report_data as $row) {
             $sheet->setCellValue('A'.$rowNum, ucfirst($row->item_type));
             $sheet->setCellValue('B'.$rowNum, $row->item_code);
             $sheet->setCellValue('C'.$rowNum, $row->item_name);
             $sheet->setCellValue('D'.$rowNum, $row->item_unit);
             $sheet->setCellValue('E'.$rowNum, $row->opening_stock);
             $sheet->setCellValue('F'.$rowNum, $row->receive_qty);
             $sheet->setCellValue('G'.$rowNum, $row->return_qty);
             $sheet->setCellValue('H'.$rowNum, $row->out_qty);
             $sheet->setCellValue('I'.$rowNum, $row->closing_stock);
             // Format Angka
             $sheet->getStyle('E'.$rowNum.':I'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
             $rowNum++;
        }

        // --- Styling Akhir ---
         $sheet->getStyle('A'.$headerRow.':I'.($rowNum-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
         foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
         }

        // --- Output File ---
        $writer = new Xlsx($spreadsheet);
        $filename = 'Laporan_Stok_'.date('My', strtotime($start_date)).'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'. $filename.'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

} // End Class