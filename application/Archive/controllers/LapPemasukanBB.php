<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Muat library Composer
require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class LapPemasukanBB extends MY_Controller {

    private $report_code = 'pemasukan_bb'; // Kode unik laporan ini
    private $report_title = 'Laporan Pertanggung Jawaban Pemasukan Barang'; // Judul Laporan

    public function __construct() {
        parent::__construct();
        check_login(); // Pastikan helper login sudah di-load
        // Load model spesifik laporan ini dan model umum
        $this->load->model(['LapPemasukanBB_model', 'Commoncode_model']);
        $this->load->library(['pagination', 'session']);
        $this->load->helper('url');
        // $this->load->library('form_validation'); // Uncomment jika perlu validasi form lebih detail
    }

    /**
     * Halaman utama: Form pemilihan filter laporan
     */
    public function index() {
        if (!check_access('lap_pemasukan_bb', 'view')) { // Sesuaikan permission key
            show_error('Anda tidak memiliki izin.', 403); return;
        }

        // Ambil nilai filter dari GET request (jika ada)
        $start_date = $this->input->get('start_date') ?: date('Y-m-01'); // Default awal bulan ini
        $end_date = $this->input->get('end_date') ?: date('Y-m-d'); // Default hari ini
        $bc_type = $this->input->get('bc_type');
        $search = $this->input->get('search'); // Filter pencarian (opsional)

        $filters = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'bc_type' => $bc_type,
            'search' => $search
            // Tambahkan filter lain jika perlu
        ];

        // --- Pagination Config ---
        $config['base_url'] = base_url('LapPemasukanBB/index'); // Targetkan method index
        $config['total_rows'] = $this->LapPemasukanBB_model->count_report($filters);
        $config['per_page'] = 25; // Item per halaman
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE;
        $config['num_links'] = 2;
        // ... (Styling pagination Bootstrap 4 seperti sebelumnya) ...
        $config['full_tag_open'] = '<nav aria-label="Page navigation"><ul class="pagination pagination-sm justify-content-center flex-wrap">'; /* ... */ $config['full_tag_close'] = '</ul></nav>';
        $config['first_link'] = '&laquo;'; $config['first_tag_open'] = '<li class="page-item">'; $config['first_tag_close'] = '</li>';
        $config['last_link'] = '&raquo;'; $config['last_tag_open'] = '<li class="page-item">'; $config['last_tag_close'] = '</li>';
        $config['next_link'] = '&rsaquo;'; $config['next_tag_open'] = '<li class="page-item">'; $config['next_tag_close'] = '</li>';
        $config['prev_link'] = '&lsaquo;'; $config['prev_tag_open'] = '<li class="page-item">'; $config['prev_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">'; $config['cur_tag_close'] = '</span></li>';
        $config['num_tag_open'] = '<li class="page-item">'; $config['num_tag_close'] = '</li>';
        $config['attributes'] = array('class' => 'page-link');

        $this->pagination->initialize($config);
        $page = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 1; // Ambil nomor halaman dari segment 3
        $offset = ($page > 0) ? ($page - 1) * $config['per_page'] : 0;

        // --- Get Data Laporan ---
        $report_data = $this->LapPemasukanBB_model->get_report($filters, $config['per_page'], $offset);

        $data = [
            'title'        => $this->report_title,
            'report_code'  => $this->report_code,
            'filters'      => $filters,
            'bc_types'     => $this->Commoncode_model->get_by_group('BC_TYPE'), // Untuk dropdown filter
            'report_data'  => $report_data,
            'pagination'   => $this->pagination->create_links(),
            'total_rows'   => $config['total_rows'],
            'start_no'     => $offset + 1
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('it_inventory/templates/filter_form', $data); // View filter umum
        $this->load->view('it_inventory/templates/display_report', $data); // View display umum
        $this->load->view('templates/footer');
    }


    /**
     * Endpoint AJAX untuk Traceability
     */
    public function traceability() {
        if (!$this->input->is_ajax_request()) { show_error('Direct access not allowed', 403); return; }
        if (!check_access('lap_pemasukan_bb', 'view')) { // Sesuaikan permission
             echo json_encode(['success' => false, 'message' => 'Akses ditolak']); return;
        }

        $ledger_id = $this->input->get('ledger_id'); // ID unik dari baris stock_ledger

        if (empty($ledger_id) || !is_numeric($ledger_id)) {
            echo json_encode(['success' => false, 'message' => 'Parameter ledger_id tidak valid.']); return;
        }

        // Panggil model untuk data traceability (gunakan model laporan ini atau model terpusat)
        $trace_data = $this->LapPemasukanBB_model->get_trace_detail($ledger_id);

        if (empty($trace_data)) {
            echo json_encode(['success' => false, 'message' => 'Data traceability tidak ditemukan.']); return;
        }

        // Buat HTML tabel untuk ditampilkan di modal
        $html = $this->_build_traceability_table($trace_data); // Panggil fungsi helper

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'html' => $html]);
    }

    /**
     * Fungsi Private: Membuat HTML tabel untuk modal traceability
     * (Sama seperti sebelumnya, pastikan join di model get_trace_detail lengkap)
     */
    private function _build_traceability_table($trace_data) {
        // Logika untuk membuat tabel HTML dari $trace_data
        $html = '<div class="table-responsive"><table class="table table-sm table-bordered" style="font-size: 0.8rem;">';
        $html .= '<thead class="thead-light"><tr><th>Tanggal</th><th>Tipe</th><th>Dok. Sumber</th><th>No. Ref</th><th>Partner</th><th>Qty Masuk</th><th>Qty Keluar</th><th>Saldo</th><th>BC Info</th><th>No. WO</th></tr></thead>';
        $html .= '<tbody>';
        if (empty($trace_data)) {
            $html .= '<tr><td colspan="10" class="text-center">Tidak ada riwayat untuk item ini.</td></tr>';
        } else {
            foreach ($trace_data as $row) {
                // Tentukan No. Ref
                $main_ref = $row['grn_number'] ?? '-'; // Default GRN Number

                // Tentukan Dok. Sumber
                $doc_source_text = '';
                if ($row['source_doc_type'] == 'PO') {
                    // PERBAIKAN KRITIS: Gunakan po_number yang baru diambil
                    $doc_source_text = "PO: " . htmlspecialchars($row['po_number'] ?? $row['source_doc_id']); 
                } elseif ($row['source_doc_type'] == 'MIR') {
                    $doc_source_text = "MIR: " . htmlspecialchars($row['mir_number'] ?? $row['source_doc_id']);
                } else {
                    $doc_source_text = htmlspecialchars($row['source_doc_type']).': '.htmlspecialchars($row['source_doc_id']);
                }

                $partner = $row['nama_supplier'] ?? ($row['nama_customer'] ?? '-');
                $bc_info = isset($row['bc_type_code']) ? ($row['bc_type_code'].'/'.$row['bc_number']) : '-';
                $wo_number = $row['wo_number'] ?? '-';

                $html .= '<tr>';
                $html .= '<td>'.date('d/m/y H:i', strtotime($row['transaction_date'])).'</td>';
                $html .= '<td>'.htmlspecialchars($row['transaction_type']).'</td>';
                $html .= '<td>'.$doc_source_text.'</td>'; // Dok. Sumber
                $html .= '<td>'.htmlspecialchars($main_ref).'</td>'; // No. Ref
                $html .= '<td>'.htmlspecialchars($partner).'</td>';
                $html .= '<td class="text-right">'.($row['qty_in'] != 0 ? number_format($row['qty_in'], 2) : '-').'</td>';
                $html .= '<td class="text-right">'.($row['qty_out'] != 0 ? number_format($row['qty_out'], 2) : '-').'</td>';
                $html .= '<td class="text-right font-weight-bold">'.number_format($row['balance'], 2).'</td>';
                $html .= '<td>'.htmlspecialchars($bc_info).'</td>';
                $html .= '<td>'.htmlspecialchars($wo_number).'</td>';
                $html .= '</tr>';
            }
        }
        $html .= '</tbody></table></div>';
        return $html;
    }

    /**
     * Fungsi Export PDF
     */
    public function export_pdf() {
       if (!check_access('lap_pemasukan_bb', 'view')) { show_error('Tidak ada izin.', 403); return;}

       // Ambil filter dari GET
       $start_date = $this->input->get('start_date') ?: date('Y-m-01');
       $end_date = $this->input->get('end_date') ?: date('Y-m-d');
       $bc_type = $this->input->get('bc_type');
       $search = $this->input->get('search');
       $filters = ['start_date' => $start_date, 'end_date' => $end_date, 'bc_type' => $bc_type, 'search' => $search];

       // Ambil SEMUA data (limit = 0)
       $report_data = $this->LapPemasukanBB_model->get_report($filters, 0, 0);

       $data = [
           'title' => $this->report_title,
           'filters' => $filters,
           'report_data' => $report_data,
           'bc_types_map' => array_column($this->Commoncode_model->get_by_group('BC_TYPE'), 'description', 'code') // Untuk nama BC di PDF
       ];

       try {
           $options = new Options(); $options->set('isHtml5ParserEnabled', true); $options->set('isRemoteEnabled', true);
           $dompdf = new Dompdf($options);
           // Gunakan view template PDF spesifik untuk laporan ini
           $html = $this->load->view('it_inventory/lap_pemasukan_bb/pdf_template', $data, true);
           $dompdf->loadHtml($html);
           $dompdf->setPaper('A4', 'landscape'); // Landscape karena kolom banyak
           $dompdf->render();
           $filename = "Lap_Pemasukan_BB_".date('Ymd', strtotime($start_date))."_sd_".date('Ymd', strtotime($end_date)).".pdf";
           $dompdf->stream($filename, ["Attachment" => 0]);
           exit;
       } catch (Exception $e) { log_message('error', 'PDF Export Error (LapPemasukanBB): ' . $e->getMessage()); show_error('Gagal membuat PDF.'); }
    }

    /**
     * Fungsi Export Excel
     */
    public function export_excel() {
         if (!check_access('lap_pemasukan_bb', 'view')) { show_error('Tidak ada izin.', 403); return;}

        // Ambil filter dari GET
        $start_date = $this->input->get('start_date') ?: date('Y-m-01');
        $end_date = $this->input->get('end_date') ?: date('Y-m-d');
        $bc_type = $this->input->get('bc_type');
        $search = $this->input->get('search');
        $filters = ['start_date' => $start_date, 'end_date' => $end_date, 'bc_type' => $bc_type, 'search' => $search];

        // Ambil SEMUA data (limit = 0)
        $report_data = $this->LapPemasukanBB_model->get_report($filters, 0, 0);
        $bc_types_map = array_column($this->Commoncode_model->get_by_group('BC_TYPE'), 'description', 'code');


        try {
            $rowNum = $headerRow + 1; // Inisialisasi rowNum di sini
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle("Pemasukan BB");

            // --- Header Laporan ---
            $rowIdx = 1;
            $sheet->setCellValue('A'.$rowIdx, $this->report_title); $sheet->mergeCells('A'.$rowIdx.':J'.$rowIdx); // Sesuaikan range J jika kolom bertambah
            $sheet->getStyle('A'.$rowIdx)->getFont()->setBold(true)->setSize(14); $sheet->getStyle('A'.$rowIdx)->getAlignment()->setHorizontal('center'); $rowIdx++;
            $sheet->setCellValue('A'.$rowIdx, 'Periode: ' . date('d M Y', strtotime($start_date)) . ' s/d ' . date('d M Y', strtotime($end_date))); $sheet->mergeCells('A'.$rowIdx.':J'.$rowIdx); $sheet->getStyle('A'.$rowIdx)->getAlignment()->setHorizontal('center'); $rowIdx++;
            if($bc_type) { $sheet->setCellValue('A'.$rowIdx, 'Filter BC Type: ' . ($bc_types_map[$bc_type] ?? $bc_type)); $rowIdx++; }
            if($search) { $sheet->setCellValue('A'.$rowIdx, 'Pencarian: ' . $search); $rowIdx++; }
            $rowIdx++;

            // --- Header Tabel ---
            $headerRow = $rowIdx;
            // Sesuaikan header ini dengan query di model get_report
            $headers = ['Tanggal', 'No. Pendaf.', 'Tgl Pendaf.', 'No. Masuk (GRN)', 'Supplier', 'Kode Barang', 'Nama Barang', 'Satuan', 'Jumlah', 'Nilai Barang (Valuta?)', 'Nilai Barang (IDR)'];
            $sheet->fromArray($headers, NULL, 'A'.$headerRow);
            $lastHeaderCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
            $sheet->getStyle('A'.$headerRow.':'.$lastHeaderCol.$headerRow)->getFont()->setBold(true);
            $sheet->getStyle('A'.$headerRow.':'.$lastHeaderCol.$headerRow)->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet->getStyle('A'.$headerRow.':'.$lastHeaderCol.$headerRow)->getBorders()->getBottom()->setBorderStyle('thick');
            $rowIdx++;

            // --- Isi Data ---
            if (!empty($report_data)) {
                foreach ($report_data as $row) {
                    $rowData = [
                        isset($row->transaction_date) ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($row->transaction_date) : '', // Format tanggal Excel
                        $row->bc_number ?? '',
                        isset($row->bc_date) ? \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($row->bc_date) : '', // Format tanggal Excel
                        $row->grn_number ?? ($row->source_doc_id ?? ''),
                        $row->nama_supplier ?? '',
                        $row->item_code ?? '',
                        $row->item_name ?? '',
                        $row->item_unit ?? '',
                        $row->qty_in ?? 0,
                        $row->unit_value_foreign ?? null, // Ambil nilai valuta jika ada di query
                        $row->unit_value_idr ?? ($row->unit_value ?? 0) // Ambil nilai IDR
                    ];
                    $sheet->fromArray($rowData, NULL, 'A' . $rowNum);
                    // Format Angka & Tanggal
                    $sheet->getStyle('A'.$rowNum)->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
                    $sheet->getStyle('C'.$rowNum)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                    $sheet->getStyle('I'.$rowNum.':K'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00'); // Kolom Jumlah & Nilai
                    $rowNum++;
                }
            } else {
                 $sheet->setCellValue('A'.$rowNum, 'Tidak ada data.');
                 $sheet->mergeCells('A'.$rowNum.':'.$lastHeaderCol.$rowNum);
            }

            // --- Styling Akhir ---
             $lastRow = $rowNum - 1;
             if ($lastRow >= $headerRow + 1) {
                 $sheet->getStyle('A'.$headerRow.':'.$lastHeaderCol.$lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
             }
            foreach (range('A', $lastHeaderCol) as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }
            $sheet->freezePane('A'.($headerRow + 1));

            // --- Output File ---
            $writer = new Xlsx($spreadsheet);
            $safe_report_name = 'Pemasukan_BB';
            $filename = "IT_Inventory_{$safe_report_name}_".date('Ymd', strtotime($start_date))."_sd_".date('Ymd', strtotime($end_date)).".xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'. $filename.'"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;

        } catch (Exception $e) { log_message('error', 'Excel Export Error (LapPemasukanBB): ' . $e->getMessage()); show_error('Gagal membuat Excel.'); }
    }


} // End Class