<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Muat library Composer
require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class LapPemasukanBB extends MY_Controller {

    private $report_code = 'pemasukan_bb'; 
    private $report_title = 'Laporan  Pemasukan Barang'; 

    public function __construct() {
        parent::__construct();
        check_login(); 
        $this->load->model(['LapPemasukanBB_model', 'Commoncode_model', 'Adjustment_model']);
        $this->load->library(['pagination', 'session']);
        $this->load->helper('url');
        
    }

   
    public function index() {
        if (!check_access('lap_pemasukan_bb', 'view')) { // Sesuaikan permission key
            show_error('Anda tidak memiliki izin.', 403); return;
        }

        
        $start_date = $this->input->get('start_date') ?: date('Y-m-01'); // Default awal bulan ini
        $end_date = $this->input->get('end_date') ?: date('Y-m-d'); // Default hari ini
        $bc_type = $this->input->get('bc_type');
        $search = $this->input->get('search'); // Filter pencarian (opsional)

        $filters = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'bc_type' => $bc_type,
            'search' => $search
            
        ];

        $config['base_url'] = base_url('LapPemasukanBB/index'); // Targetkan method index
        $config['total_rows'] = $this->LapPemasukanBB_model->count_report($filters);
        $config['per_page'] = 25; 
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE;
        $config['num_links'] = 
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
     * Endpoint AJAX: Dokumen terlampir untuk satu adjustment header (dari tombol di traceability)
     */
    public function ajax_get_adjustment_documents() {
        if (!$this->input->is_ajax_request()) { show_error('Direct access not allowed', 403); return; }

        $adj_id = $this->input->get('adj_id');
        if (empty($adj_id) || !is_numeric($adj_id)) {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']); return;
        }

        $header = $this->Adjustment_model->get_header_by_id($adj_id);
        $docs   = $this->Adjustment_model->get_adjustment_documents($adj_id);
        $details = $this->Adjustment_model->get_details_by_header_id($adj_id);

        $html  = '<div class="card border">';
        $html .= '<div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">';
        $html .= '<span class="fw-bold"><i class="fas fa-file-alt me-1 text-warning"></i> ' . htmlspecialchars($header->doc_no ?? '-') . '</span>';
        $html .= '<span class="text-muted small">' . ($header ? date('d/m/Y', strtotime($header->doc_date)) : '-') . '</span>';
        $html .= '</div>';
        $html .= '<div class="card-body p-2">';

        // Detail items
        if (!empty($details)) {
            $html .= '<div class="table-responsive mb-2"><table class="table table-sm table-bordered mb-0" style="font-size:0.82rem;">';
            $html .= '<thead class="thead-light"><tr><th>Nama Barang</th><th class="text-center">Tipe</th><th class="text-right">Qty</th><th class="text-center">Satuan</th></tr></thead><tbody>';
            foreach ($details as $d) {
                $badge = $d->adjustment_type === 'IN'
                    ? '<span class="badge badge-success">IN</span>'
                    : '<span class="badge badge-danger">OUT</span>';
                $qty_signed = $d->adjustment_type === 'IN'
                    ? '+' . number_format($d->qty, 2)
                    : '-' . number_format($d->qty, 2);
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($d->item_name ?? '-') . '</td>';
                $html .= '<td class="text-center">' . $badge . '</td>';
                $html .= '<td class="text-right font-weight-bold">' . $qty_signed . '</td>';
                $html .= '<td class="text-center">' . htmlspecialchars($d->item_unit ?? '-') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Catatan
        if (!empty($header->notes)) {
            $html .= '<p class="text-muted small mb-2"><i class="fas fa-sticky-note me-1"></i>' . htmlspecialchars($header->notes) . '</p>';
        }

        // Dokumen
        if (!empty($docs)) {
            $html .= '<div class="mt-1"><strong class="small"><i class="fas fa-paperclip me-1"></i>Dokumen Terlampir:</strong><ul class="list-unstyled mb-0 mt-1">';
            foreach ($docs as $doc) {
                $file_url = base_url('adjustment/view_document/' . $doc->id);
                $html .= '<li class="small"><a href="' . $file_url . '" target="_blank" class="text-primary">';
                $html .= '<i class="fas fa-file me-1"></i>' . htmlspecialchars($doc->file_name ?? basename($doc->file_path));
                $html .= '</a></li>';
            }
            $html .= '</ul></div>';
        } else {
            $html .= '<p class="text-muted small mb-0"><i class="fas fa-paperclip me-1"></i>Tidak ada dokumen terlampir.</p>';
        }

        $html .= '</div></div>';

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'html' => $html]);
    }

    /**
     * Endpoint AJAX: Detail Adjustment untuk item tertentu dalam periode tertentu
     */
    public function ajax_get_adjustment_detail() {
        if (!$this->input->is_ajax_request()) { show_error('Direct access not allowed', 403); return; }
        if (!check_access('lap_pemasukan_bb', 'view')) {
            echo json_encode(['success' => false, 'message' => 'Akses ditolak']); return;
        }

        $item_id    = $this->input->get('item_id');
        $item_type  = $this->input->get('item_type');
        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        if (empty($item_id) || !is_numeric($item_id) || empty($item_type)) {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']); return;
        }

        $this->load->model('Adjustment_model');
        $rows = $this->LapPemasukanBB_model->get_adjustment_detail_for_item($item_id, $item_type, $start_date, $end_date);

        if (empty($rows)) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada data adjustment untuk item ini dalam periode yang dipilih.']); return;
        }

        // Kelompokkan rows per header
        $headers = [];
        foreach ($rows as $r) {
            $hid = $r['header_id'];
            if (!isset($headers[$hid])) {
                $headers[$hid] = [
                    'header_id'    => $hid,
                    'doc_no'       => $r['doc_no'],
                    'doc_date'     => $r['doc_date'],
                    'header_notes' => $r['header_notes'],
                    'details'      => [],
                    'documents'    => $this->Adjustment_model->get_adjustment_documents($hid)
                ];
            }
            $headers[$hid]['details'][] = $r;
        }

        // Build HTML
        $html = '';
        foreach ($headers as $h) {
            $html .= '<div class="card mb-3 border">';
            $html .= '<div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">';
            $html .= '<span class="fw-bold"><i class="fas fa-file-alt me-1 text-warning"></i> ' . htmlspecialchars($h['doc_no']) . '</span>';
            $html .= '<span class="text-muted small">' . date('d/m/Y', strtotime($h['doc_date'])) . '</span>';
            $html .= '</div>';
            $html .= '<div class="card-body p-2">';

            // Detail items tabel
            $html .= '<div class="table-responsive mb-2"><table class="table table-sm table-bordered mb-0" style="font-size:0.82rem;">';
            $html .= '<thead class="thead-light"><tr><th>Nama Barang</th><th class="text-center">Tipe</th><th class="text-right">Qty</th><th class="text-center">Satuan</th><th>Keterangan</th></tr></thead><tbody>';
            foreach ($h['details'] as $d) {
                $badge = $d['adjustment_type'] === 'IN'
                    ? '<span class="badge badge-success">IN</span>'
                    : '<span class="badge badge-danger">OUT</span>';
                $qty_signed = $d['adjustment_type'] === 'IN'
                    ? '+' . number_format($d['qty'], 2)
                    : '-' . number_format($d['qty'], 2);
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($d['item_name']) . '</td>';
                $html .= '<td class="text-center">' . $badge . '</td>';
                $html .= '<td class="text-right font-weight-bold">' . $qty_signed . '</td>';
                $html .= '<td class="text-center">' . htmlspecialchars($d['item_unit']) . '</td>';
                $html .= '<td>' . htmlspecialchars($d['detail_notes'] ?? '-') . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';

            // Catatan header
            if (!empty($h['header_notes'])) {
                $html .= '<p class="text-muted small mb-2"><i class="fas fa-sticky-note me-1"></i>' . htmlspecialchars($h['header_notes']) . '</p>';
            }

            // Dokumen terlampir
            if (!empty($h['documents'])) {
                $html .= '<div class="mt-1"><strong class="small"><i class="fas fa-paperclip me-1"></i>Dokumen Terlampir:</strong><ul class="list-unstyled mb-0 mt-1">';
                foreach ($h['documents'] as $doc) {
                    $file_url = base_url('adjustment/view_document/' . $doc->id);
                    $html .= '<li class="small"><a href="' . $file_url . '" target="_blank" class="text-primary">';
                    $html .= '<i class="fas fa-file me-1"></i>' . htmlspecialchars($doc->file_name ?? basename($doc->file_path));
                    $html .= '</a> <span class="text-muted">(' . htmlspecialchars($doc->uploaded_by_name ?? '') . ')</span></li>';
                }
                $html .= '</ul></div>';
            } else {
                $html .= '<p class="text-muted small mb-0"><i class="fas fa-paperclip me-1"></i>Tidak ada dokumen terlampir.</p>';
            }

            $html .= '</div></div>'; // card-body + card
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'html' => $html]);
    }

    /**
     * Endpoint AJAX untuk Traceability
     */
    public function traceability() {
        if (!$this->input->is_ajax_request()) { show_error('Direct access not allowed', 403); return; }
        if (!check_access('lap_pemasukan_bb', 'view')) { // Sesuaikan permission
             echo json_encode(['success' => false, 'message' => 'Akses ditolak']); return;
        }

        $ledger_id  = $this->input->get('ledger_id');
        $start_date = $this->input->get('start_date') ?: null;
        $end_date   = $this->input->get('end_date')   ?: null;

        if (empty($ledger_id) || !is_numeric($ledger_id)) {
            echo json_encode(['success' => false, 'message' => 'Parameter ledger_id tidak valid.']); return;
        }

        // Panggil model untuk data traceability
        $trace_data = $this->LapPemasukanBB_model->get_trace_detail($ledger_id, $start_date, $end_date);

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
        
        $html = '<div class="table-responsive"><table class="table table-sm table-bordered" style="font-size: 0.8rem;">';
        $html .= '<thead class="thead-light"><tr><th>Tanggal</th><th>Tipe</th><th>Dok. Sumber</th><th>No. Ref</th><th>Partner</th><th>Qty Masuk</th><th>Qty Keluar</th><th>Saldo</th><th>BC Info</th><th>No. WO</th></tr></thead>';
        $html .= '<tbody>';
        if (empty($trace_data)) {
            $html .= '<tr><td colspan="10" class="text-center">Tidak ada riwayat untuk item ini.</td></tr>';
        } else {
            foreach ($trace_data as $row) {
                
                $is_adj = ($row['transaction_type'] === 'adjustment');

                // --- No. Ref & Dok. Sumber ---
                if ($is_adj) {
                    $main_ref      = $row['adjustment_doc'] ?? ('-ADJ-'.$row['source_doc_id']);
                    $doc_source_text = '<span class="badge badge-warning text-dark">ADJ</span>';
                } else {
                    $main_ref = $row['grn_number'] ?? '-';
                    $doc_source_text = '';
                    if ($row['source_doc_type'] == 'PO') {
                        $doc_source_text = 'PO: ' . htmlspecialchars($row['po_number'] ?? $row['source_doc_id']);
                    } elseif ($row['source_doc_type'] == 'MIR') {
                        $doc_source_text = 'MIR: ' . htmlspecialchars($row['mir_number'] ?? 'ID-'.$row['source_doc_id']);
                    } elseif ($row['source_doc_type'] == 'WO') {
                        $doc_source_text = 'RTN: ' . htmlspecialchars($row['wo_out_number'] ?? 'ID-'.$row['source_doc_id']);
                    } else {
                        $doc_source_text = htmlspecialchars($row['source_doc_type']).': '.htmlspecialchars($row['source_doc_id']);
                    }
                }

                $partner   = $row['nama_supplier'] ?? ($row['nama_customer'] ?? '-');
                $bc_info   = ($is_adj)
                    ? (!empty($row['adjustment_notes']) ? htmlspecialchars($row['adjustment_notes']) : '-')
                    : (isset($row['bc_type_code']) ? ($row['bc_type_code'].'/'.$row['bc_number']) : '-');
                $wo_number = $row['wo_number'] ?? '-';

                // --- Qty columns (color adjustment) ---
                $qty_in_html  = '-';
                $qty_out_html = '-';
                if ($is_adj) {
                    if ($row['qty_in'] != 0) {
                        $qty_in_html = '<span class="text-success font-weight-bold">+'.number_format($row['qty_in'], 2).'</span>';
                    }
                    if ($row['qty_out'] != 0) {
                        $qty_out_html = '<span class="text-danger font-weight-bold">-'.number_format($row['qty_out'], 2).'</span>';
                    }
                } else {
                    $qty_in_html  = ($row['qty_in']  != 0) ? number_format($row['qty_in'],  2) : '-';
                    $qty_out_html = ($row['qty_out'] != 0) ? number_format($row['qty_out'], 2) : '-';
                }

                // --- Row highlight for adjustment ---
                $tr_style = $is_adj ? ' style="background-color:#fffde7;"' : '';

                // --- Dokumen button for adjustment ---
                $adj_doc_btn = '';
                if ($is_adj && !empty($row['adjustment_header_id'])) {
                    $adj_doc_btn = ' <button class="btn btn-info btn-xs btn-adj-trace-doc"
                        data-adjid="'.htmlspecialchars($row['adjustment_header_id']).'" 
                        title="Lihat Dokumen Adjustment" style="font-size:0.7rem;padding:1px 5px;">
                        <i class="fas fa-paperclip"></i>
                    </button>';
                }

                $html .= '<tr'.$tr_style.'>';
                $html .= '<td>'.date('d/m/y H:i', strtotime($row['transaction_date'])).'</td>';
                $html .= '<td>';
                if ($is_adj) {
                    $html .= '<span class="badge badge-warning text-dark">Adjustment</span>';
                } else {
                    $html .= htmlspecialchars($row['transaction_type']);
                }
                $html .= '</td>';
                $html .= '<td>'.$doc_source_text.'</td>';
                $html .= '<td>'.htmlspecialchars($main_ref).$adj_doc_btn.'</td>';
                $html .= '<td>'.htmlspecialchars($partner).'</td>';
                $html .= '<td class="text-right">'.$qty_in_html.'</td>';
                $html .= '<td class="text-right">'.$qty_out_html.'</td>';
                $html .= '<td class="text-right font-weight-bold">'.number_format($row['balance'], 2).'</td>';
                $html .= '<td>'.$bc_info.'</td>';
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
        if (!check_access('lap_pemasukan_bb', 'view')) { show_error('Tidak ada izin.', 403); return; }

        $start_date = $this->input->get('start_date') ?: date('Y-m-01');
        $end_date = $this->input->get('end_date') ?: date('Y-m-d');
        $bc_type = $this->input->get('bc_type');
        $search = $this->input->get('search');
        $filters = ['start_date' => $start_date, 'end_date' => $end_date, 'bc_type' => $bc_type, 'search' => $search];

        // Ambil data (model mengembalikan array)
        $report_data = $this->LapPemasukanBB_model->get_report($filters, 0, 0);
        $bc_types_map = array_column($this->Commoncode_model->get_by_group('BC_TYPE'), 'description', 'code');

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle("Pemasukan BB");

            // --- Header Laporan ---
            $sheet->setCellValue('A1', $this->report_title);
            $sheet->mergeCells('A1:O1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
            
            // Tambah nama perusahaan
            $sheet->setCellValue('A2', company_info('company_name'));
            $sheet->mergeCells('A2:O2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');
            
            // Format periode dengan format tanggal yang lebih readable
            $start_display = date('d M Y', strtotime($start_date));
            $end_display = date('d M Y', strtotime($end_date));
            $sheet->setCellValue('A3', 'Periode: ' . $start_display . ' s/d ' . $end_display);
            $sheet->mergeCells('A3:O3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal('center');

            // --- Header Tabel ---
            $headerRow = 5;
            $sheet->setCellValue('A'.$headerRow, 'NO');
            $sheet->setCellValue('B'.$headerRow, 'JENIS BC');
            $sheet->setCellValue('C'.$headerRow, 'NOMOR BC');
            $sheet->setCellValue('D'.$headerRow, 'TANGGAL BC');
            $sheet->setCellValue('E'.$headerRow, 'NOMOR GRN');
            $sheet->setCellValue('F'.$headerRow, 'TANGGAL GRN');
            $sheet->setCellValue('G'.$headerRow, 'SUPPLIER');
            $sheet->setCellValue('H'.$headerRow, 'KODE BARANG');
            $sheet->setCellValue('I'.$headerRow, 'NAMA BARANG');
            $sheet->setCellValue('J'.$headerRow, 'JUMLAH');
            $sheet->setCellValue('K'.$headerRow, 'ADJUSTMENT');
            $sheet->setCellValue('L'.$headerRow, 'TOTAL QTY');
            $sheet->setCellValue('M'.$headerRow, 'SATUAN');
            $sheet->setCellValue('N'.$headerRow, 'MATA UANG');
            $sheet->setCellValue('O'.$headerRow, 'NILAI BARANG');

            // Style header
            $sheet->getStyle('A'.$headerRow.':O'.$headerRow)->getFont()->setBold(true);
            $sheet->getStyle('A'.$headerRow.':O'.$headerRow)->getAlignment()->setHorizontal('center')->setVertical('center');
            $sheet->getStyle('A'.$headerRow.':O'.$headerRow)->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD3D3D3');

            // --- Isi Data ---
            $rowNum = $headerRow + 1;
            $no = 1;
            
            if (!empty($report_data)) {
                foreach ($report_data as $row) {
                    // $row adalah array, gunakan index
                    $bc_code = isset($row['bc_type_code']) ? $row['bc_type_code'] : '';
                    $bc_desc = isset($bc_types_map[$bc_code]) ? $bc_types_map[$bc_code] : $bc_code;
                    
                    // Prepare tanggal sebagai Excel-native dates (date-only, no time)
                    $bc_date_excel = null;
                    if (isset($row['bc_date']) && !empty($row['bc_date'])) {
                        try {
                            $dt = new \DateTime($row['bc_date']);
                            $bc_date_excel = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dt);
                        } catch (Exception $e) {
                            $bc_date_excel = null;
                        }
                    }

                    $grn_date_excel = null;
                    if (isset($row['grn_date']) && !empty($row['grn_date'])) {
                        try {
                            $dt2 = new \DateTime($row['grn_date']);
                            $grn_date_excel = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dt2);
                        } catch (Exception $e) {
                            $grn_date_excel = null;
                        }
                    }
                    
                    // Tulis setiap cell untuk menghindari issue dengan data type
                    $sheet->setCellValue('A'.$rowNum, $no++);
                    $sheet->setCellValue('B'.$rowNum, $bc_desc ? $bc_desc : '-');
                    $sheet->setCellValue('C'.$rowNum, isset($row['bc_number']) ? $row['bc_number'] : '-');
                    if ($bc_date_excel !== null) {
                        $sheet->setCellValue('D'.$rowNum, $bc_date_excel);
                        $sheet->getStyle('D'.$rowNum)->getNumberFormat()->setFormatCode('dd-MMM-yyyy');
                    } else {
                        $sheet->setCellValue('D'.$rowNum, '-');
                    }
                    $sheet->setCellValue('E'.$rowNum, isset($row['grn_number']) ? $row['grn_number'] : '-');
                    if ($grn_date_excel !== null) {
                        $sheet->setCellValue('F'.$rowNum, $grn_date_excel);
                        $sheet->getStyle('F'.$rowNum)->getNumberFormat()->setFormatCode('dd-MMM-yyyy');
                    } else {
                        $sheet->setCellValue('F'.$rowNum, '-');
                    }
                    $sheet->setCellValue('G'.$rowNum, isset($row['nama_supplier']) ? $row['nama_supplier'] : '-');
                    $sheet->setCellValue('H'.$rowNum, isset($row['item_code']) ? $row['item_code'] : '-');
                    $sheet->setCellValue('I'.$rowNum, isset($row['item_name']) ? $row['item_name'] : '-');
                    $sheet->setCellValue('J'.$rowNum, isset($row['qty_in']) ? (float)$row['qty_in'] : 0);
                    $adj_qty_val = isset($row['adj_qty']) ? (float)$row['adj_qty'] : 0;
                    $total_qty_val = isset($row['total_qty']) ? (float)$row['total_qty'] : (float)($row['qty_in'] ?? 0);
                    $sheet->setCellValue('K'.$rowNum, $adj_qty_val);
                    $sheet->setCellValue('L'.$rowNum, $total_qty_val);
                    $sheet->setCellValue('M'.$rowNum, isset($row['item_unit']) ? $row['item_unit'] : '-');
                    $sheet->setCellValue('N'.$rowNum, isset($row['currency_code']) ? $row['currency_code'] : 'IDR');
                    $sheet->setCellValue('O'.$rowNum, isset($row['unit_value_idr']) ? (float)$row['unit_value_idr'] : 0);
                    
                    // Format nomor untuk lebih readable
                    $sheet->getStyle('J'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('K'.$rowNum)->getNumberFormat()->setFormatCode('+#,##0.00;-#,##0.00;"-"');
                    $sheet->getStyle('L'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('O'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
                    
                    // Color adjustment cell
                    if ($adj_qty_val > 0) {
                        $sheet->getStyle('K'.$rowNum)->getFont()->getColor()->setARGB('FF228B22');
                    } elseif ($adj_qty_val < 0) {
                        $sheet->getStyle('K'.$rowNum)->getFont()->getColor()->setARGB('FFDC143C');
                    }
                    
                    $rowNum++;
                }
            } else {
                $sheet->setCellValue('A'.$rowNum, 'Tidak ada data.');
                $sheet->mergeCells('A'.$rowNum.':O'.$rowNum);
            }

            // --- Styling Akhir ---
            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            // Add borders
            $lastRow = $rowNum - 1;
            if ($lastRow >= $headerRow) {
                $sheet->getStyle('A'.$headerRow.':O'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
            }

            // --- Output File ---
            $writer = new Xlsx($spreadsheet);
            $filename = "Lap_Pemasukan_BB_".date('Ymd', strtotime($start_date))."_sd_".date('Ymd', strtotime($end_date)).".xlsx";
            
            // Clear output buffer
            if (ob_get_contents()) ob_end_clean();
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'. $filename.'"');
            header('Cache-Control: max-age=0');
            header('Pragma: public');
            
            $writer->save('php://output');
            exit;

        } catch (Exception $e) { 
            log_message('error', 'Excel Export Error (LapPemasukanBB): ' . $e->getMessage()); 
            show_error('Gagal membuat Excel: ' . $e->getMessage()); 
        }
    }


} // End Class