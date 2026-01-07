<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Mpdf\Mpdf;

require FCPATH.'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LapMutasiScrap extends MY_Controller {

    private $report_title = 'Laporan Pertanggung Jawaban Mutasi Scrap / Sisa Produksi';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['LapMutasiScrap_model', 'Commoncode_model']);
        $this->load->library(['pagination', 'session']);
        $this->load->helper(['url','form']);
    }

    /** ============================================================
     *  HALAMAN UTAMA LAPORAN MUTASI SCRAP
     * ============================================================ */
    public function index() {
        if (!check_access('lap_mutasi_scrap', 'view')) show_error('Tidak ada izin', 403);

        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-d'),
            'search'     => trim($this->input->get('search', TRUE)),
            'bc_type'    => $this->input->get('bc_type') ?: null
        ];

        // Pagination
        $config['base_url'] = base_url('LapMutasiScrap/index');
        $config['total_rows'] = $this->LapMutasiScrap_model->count_report($filters);
        $config['per_page'] = 25;
        $config['reuse_query_string'] = TRUE;
        $config['use_page_numbers'] = TRUE;
        $config['num_links'] = 2;
        $config['attributes'] = ['class' => 'page-link'];
        $this->pagination->initialize($config);

        $page = (int)$this->uri->segment(3);
        $offset = ($page > 0) ? ($page -1) * $config['per_page'] : 0;

        $report_data = $this->LapMutasiScrap_model->get_mutation_report($filters, $config['per_page'], $offset);

        $data = [
            'title' => $this->report_title,
            'filters' => $filters,
            'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE'),
            'report_data' => $report_data,
            'pagination' => $this->pagination->create_links(),
            'total_rows' => $config['total_rows'],
            'start_no' => $offset + 1
        ];

        // Load template
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('it_inventory/templates/display_report_mutasi', $data);
        $this->load->view('templates/footer');
    }
    
    public function ajax_get_material_trace()
    {
        $prod_output_id = $this->input->get('prod_output_id');
        if (empty($prod_output_id)) {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap.']);
            return;
        }

        $this->load->model('LapMutasiScrap_model');
        $trace_data = $this->LapMutasiScrap_model->get_material_trace($prod_output_id);

        if (!$trace_data) {
            echo json_encode(['success' => true, 'html' => '<div class="alert alert-warning">Data asal material tidak ditemukan.</div>']);
            return;
        }

        $html = $this->load->view('it_inventory/lap_mutasi_scrap/ajax_get_material_trace', ['trace_data' => $trace_data], true);
        echo json_encode(['success' => true, 'html' => $html]);
    }


    /** ============================================================
     *  TRACEABILITY PER ITEM SCRAP (AJAX)
     * ============================================================ */
    public function traceability() {
        if (!$this->input->is_ajax_request()) { show_error('Forbidden', 403); return; }
        if (!check_access('lap_mutasi_scrap', 'view')) { 
            header('Content-Type: application/json'); 
            echo json_encode(['success'=>false,'message'=>'Akses ditolak']); 
            return;
        }

        $item_id = (int)$this->input->get('item_id');
        if ($item_id <= 0) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Parameter item invalid']); return; }

        $rows = $this->LapMutasiScrap_model->get_traceability($item_id);

        $html = '<div class="table-responsive"><table class="table table-sm table-bordered" style="font-size:0.85rem">';
        $html .= '<thead class="thead-light"><tr>
                    <th>Tanggal</th><th>Tipe</th><th>Dokumen Sumber</th><th>No.Ref</th>
                    <th>Partner</th><th class="text-right">Qty In</th><th class="text-right">Qty Out</th>
                    <th class="text-right">Saldo</th><th>BC</th><th>WO</th><th>Aksi</th></tr></thead><tbody>';

        if (empty($rows)) {
            $html .= '<tr><td colspan="11" class="text-center">Tidak ada riwayat.</td></tr>';
        } else {
            foreach ($rows as $r) {
                $tgl = !empty($r['transaction_date']) ? date('d/m/Y H:i', strtotime($r['transaction_date'])) : '-';
                $type = $r['transaction_type'] ?? '-';
                $src = ($r['source_doc_type'] ?? '-') . ' / ' . ($r['source_doc_id'] ?? '-');
                $ref = $r['transaction_id'] ?? '-';
                $partner = $r['scrap_out_destination_name'] ?? ($r['partner_name'] ?? '-');
                $qty_in = (float)($r['qty_in'] ?? 0);
                $qty_out = (float)($r['qty_out'] ?? $r['qty'] ?? 0); // fallback qty
                $bal = (float)($r['balance'] ?? 0);
                $bc_info = (!empty($r['bc_type_code']) ? $r['bc_type_code'] . '/' . ($r['bc_number'] ?? '-') : '-');
                $wo = $r['wo_number'] ?? '-';

                $action = '-';

                // Deep trace produksi OR WH
                if (
                    (strtoupper($type) === 'IN_SCRAP_PROD' && !empty($r['production_output_id'])) ||
                    (strtoupper($type) === 'IN_SCRAP_WH')
                ) {
                    $ledgerId = htmlspecialchars($r['ledger_id'] ?? $r['id'] ?? '');
                    if (strtoupper($type) === 'IN_SCRAP_WH') {
                        $action = '<button class="btn btn-sm btn-warning btn-deep-trace-wh" data-ledgerid="'.$ledgerId.'" 
                                    title="Lihat Asal Warehouse"><i class="fas fa-warehouse fa-sm"></i></button>';
                    } else {
                        $prodId = htmlspecialchars($r['production_output_id']);
                        $action = '<button class="btn btn-sm btn-primary btn-deep-trace" data-prodid="'.$prodId.'" 
                                    title="Deep Trace Produksi"><i class="fas fa-industry fa-sm"></i></button>';
                    }
                }

                // Detail pengeluaran scrap (deteksi doc id)
                if (in_array(strtoupper($type), ['OUT_SCRAP_BC','OUT_SCRAP_SALE','OUT_SCRAP'])) {
                    $docId = '';
                    if (!empty($r['scrap_out_id'])) $docId = $r['scrap_out_id'];
                    elseif (!empty($r['transaction_id']) && is_numeric($r['transaction_id'])) $docId = (int)$r['transaction_id'];
                    elseif (!empty($r['source_doc_id']) && is_numeric($r['source_doc_id'])) $docId = (int)$r['source_doc_id'];
                    elseif (!empty($r['scrap_out_docno'])) {
                        $row = $this->db->select('id')->where('doc_no', $r['scrap_out_docno'])->get('scrap_out_header')->row();
                        if ($row) $docId = $row->id;
                    }
                    $action = '<button class="btn btn-sm btn-info btn-show-out" data-docid="'.$docId.'" 
                               title="Detail Pengeluaran Scrap"><i class="fas fa-truck fa-sm"></i></button>';
                }

                $html .= '<tr>
                    <td>'.$tgl.'</td>
                    <td>'.htmlspecialchars($type).'</td>
                    <td>'.htmlspecialchars($src).'</td>
                    <td>'.htmlspecialchars($ref).'</td>
                    <td>'.htmlspecialchars($partner).'</td>
                    <td class="text-right">'.($qty_in ? number_format($qty_in,2) : '-').'</td>
                    <td class="text-right">'.($qty_out ? number_format($qty_out,2) : '-').'</td>
                    <td class="text-right font-weight-bold">'.number_format($bal,2).'</td>
                    <td>'.htmlspecialchars($bc_info).'</td>
                    <td>'.htmlspecialchars($wo).'</td>
                    <td class="text-center">'.$action.'</td>
                </tr>';
            }
        }
        $html .= '</tbody></table></div><div id="deepTraceResult" class="mt-3"></div>';

        header('Content-Type: application/json');
        echo json_encode(['success'=>true,'html'=>$html]);
    }

    /** ============================================================
     *  DEEP TRACE PRODUKSI — Asal Material Scrap (AJAX)
     * ============================================================ */
    public function ajax_get_production_trace() {
        if (!$this->input->is_ajax_request()) { show_error('Forbidden',403); return; }
        if (!check_access('lap_mutasi_scrap', 'view')) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Akses ditolak']); return; }

        $prod_id = (int)$this->input->get('prod_id');
        if ($prod_id <= 0) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Parameter prod_id invalid']); return; }

        $trace = $this->LapMutasiScrap_model->get_deep_production($prod_id);
        if (empty($trace) || (empty($trace['header_info']) && empty($trace['materials_used']))) {
            header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Data produksi tidak ditemukan']); return;
        }

        $h = $trace['header_info'];
        $materials = $trace['materials_used'] ?? [];

        ob_start(); ?>
            <div class="d-flex justify-content-end mb-2">
                <a href="<?= base_url('LapMutasiScrap/export_production_trace_pdf/' . $prod_id); ?>" class="btn btn-sm btn-danger me-2" target="_blank">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <a href="<?= base_url('LapMutasiScrap/export_production_trace_excel/' . $prod_id); ?>" class="btn btn-sm btn-success me-2" target="_blank">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
            </div>

            <h6 class="mb-2"><i class="fas fa-industry"></i> Detail Produksi</h6>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><th width="40%">No. Produksi</th><td><?= htmlspecialchars($h->production_number ?? '-'); ?></td></tr>
                        <tr><th>Nama FG</th><td><?= htmlspecialchars($h->fg_name ?? '-'); ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><th width="40%">No. WO</th><td><?= htmlspecialchars($h->wo_number ?? '-'); ?></td></tr>
                        <tr><th>No. Sales Contract</th><td><?= htmlspecialchars($h->sales_contract ?? '-'); ?></td></tr>
                    </table>
                </div>
            </div>

            <h6 class="mt-3">Material yang menghasilkan scrap (input)</h6>
            <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="thead-light"><tr><th>Kode</th><th>Nama</th><th class="text-right">Qty Terpakai</th><th>Satuan</th><th>BC Asal</th></tr></thead>
                <tbody>
                    <?php if (empty($materials)): ?>
                        <tr><td colspan="5" class="text-center">Tidak ada data material.</td></tr>
                    <?php else: foreach ($materials as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['item_code'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($m['item_name'] ?? '-'); ?></td>
                            <td class="text-right"><?= number_format((float)($m['qty_used'] ?? 0),2); ?></td>
                            <td><?= htmlspecialchars($m['unit'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars(($m['ex_bc_type'] ?? '-') . '/' . ($m['ex_bc_number'] ?? '-')); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table></div>
        <?php
        $html = ob_get_clean();
        header('Content-Type: application/json');
        echo json_encode(['success'=>true,'html'=>$html]);
    }

    /** ============================================================
     *  DETAIL PENGELUARAN SCRAP (AJAX)
     * ============================================================ */
    public function ajax_get_out_trace()
    {
        if (!$this->input->is_ajax_request()) { show_error('Forbidden',403); return; }
        if (!check_access('lap_mutasi_scrap', 'view')) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Akses ditolak']); return; }

        $doc_id = (int)$this->input->get('doc_id');
        if (empty($doc_id)) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Parameter doc_id kosong']); return; }

        $q = $this->db->select('
                    h.id, h.doc_no, h.doc_date, h.destination, c.nama_customer AS customer_name,
                    h.bc_type_code, h.bc_number, h.bc_date,
                    d.scrap_id, m.scrap_name, d.qty, m.unit
                ')
                ->from('scrap_out_header h')
                ->join('scrap_out_detail d', 'h.id = d.header_id', 'left')
                ->join('master_scrap m', 'd.scrap_id = m.id', 'left')
                ->join('customers c', 'h.destination = c.id', 'left')
                ->where('h.id', $doc_id)
                ->get();

        if (!$q || $q->num_rows() == 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Data pengeluaran tidak ditemukan']);
            return;
        }

        $data = $q->result_array();
        $header = $data[0];

        ob_start(); ?>
        <div class="d-flex justify-content-end mb-2">
            <a href="<?= base_url('LapMutasiScrap/export_out_trace_pdf/' . $doc_id); ?>" class="btn btn-sm btn-danger me-2" target="_blank">
                <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            <a href="<?= base_url('LapMutasiScrap/export_out_trace_excel/' . $doc_id); ?>" class="btn btn-sm btn-success me-2" target="_blank">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <tr><th width="180">Nomor Dokumen</th><td><?= htmlspecialchars($header['doc_no']); ?></td></tr>
                <tr><th>Tanggal Dokumen</th><td><?= htmlspecialchars($header['doc_date']); ?></td></tr>
                <tr><th>Customer / Tujuan</th><td><?= htmlspecialchars($header['customer_name']); ?></td></tr>
                <tr><th>BC Type</th><td><?= htmlspecialchars($header['bc_type_code']); ?></td></tr>
                <tr><th>BC Number</th><td><?= htmlspecialchars($header['bc_number']); ?></td></tr>
                <tr><th>BC Date</th><td><?= htmlspecialchars($header['bc_date']); ?></td></tr>
            </table>
            <hr>
            <table class="table table-sm table-bordered table-hover">
                <thead class="thead-light text-center">
                    <tr>
                        <th>No</th>
                        <th>Nama Scrap</th>
                        <th>Qty Keluar</th>
                        <th>Satuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($data as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d['scrap_name']); ?></td>
                            <td class="text-right"><?= number_format((float)$d['qty'], 2); ?></td>
                            <td class="text-center"><?= htmlspecialchars($d['unit']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $html = ob_get_clean();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'html' => $html]);
    }

    /** ============================================================
     *  AJAX: deep trace untuk IN_SCRAP_WH (mutasi antar warehouse)
     * ============================================================ */
    /** ============================================================
 *  AJAX: deep trace untuk IN_SCRAP_WH (mutasi antar warehouse)
 * ============================================================ */
    public function ajax_get_wh_trace()
    {
        $ledger_id = $this->input->get('ledger_id');
        if (empty($ledger_id)) {
            echo json_encode(['success' => false, 'html' => '<div class="alert alert-warning">Parameter ledger_id tidak ditemukan.</div>']);
            return;
        }

        // Ambil data ledger
        $ledger = $this->db->get_where('stock_ledger', ['id' => $ledger_id])->row_array();
        if (!$ledger) {
            echo json_encode(['success' => false, 'html' => '<div class="alert alert-warning">Data ledger tidak ditemukan.</div>']);
            return;
        }

        // Jika jenis transaksi adalah IN_SCRAP_WH, ambil asal dari scrap_in_header
        if ($ledger['transaction_type'] === 'IN_SCRAP_WH') {
            $scrap_header = $this->db->get_where('scrap_in_header', ['id' => $ledger['transaction_id']])->row_array();
            if (!$scrap_header) {
                echo json_encode(['success' => true, 'html' => '<div class="alert alert-warning">Data scrap_in_header tidak ditemukan.</div>']);
                return;
            }

            // Ambil salah satu detail scrap untuk tahu sumber produksi
            $scrap_detail = $this->db->get_where('scrap_in_detail', ['header_id' => $scrap_header['id']])->row_array();
            if (!$scrap_detail) {
                echo json_encode(['success' => true, 'html' => '<div class="alert alert-warning">Detail scrap tidak ditemukan.</div>']);
                return;
            }

            // Gunakan scrap_in_detail.id untuk menelusuri sumber produksi dari scrap_in_detail_sources
            $source = $this->db->get_where('scrap_in_detail_sources', ['scrap_in_detail_id' => $scrap_detail['id']])->row_array();
            if (!$source) {
                echo json_encode(['success' => true, 'html' => '<div class="alert alert-warning">Data asal produksi untuk scrap ini belum ada.</div>']);
                return;
            }

            $production_output_id = $source['production_output_id'];
        } else {
            // Jika bukan IN_SCRAP_WH langsung ambil dari ledger
            $production_output_id = $ledger['transaction_id'];
        }

        // Ambil data produksi
        $sql = "
            SELECT 
                ph.id AS production_header_id,
                ph.production_number,
                wo.wo_number,
                po.stage_name,
                (
                    SELECT fg.nama_barang 
                    FROM production_outputs po2
                    LEFT JOIN finished_goods fg ON fg.id = po2.item_id
                    WHERE po2.production_header_id = ph.id 
                    AND po2.item_type = 'finished_goods'
                    LIMIT 1
                ) AS fg_name
            FROM production_outputs po
            LEFT JOIN production_header ph ON ph.id = po.production_header_id
            LEFT JOIN wo_header wo ON wo.id = ph.wo_header_id
            WHERE po.id = ?
        ";
        $production = $this->db->query($sql, [$production_output_id])->row_array();

        if (!$production) {
            echo json_encode(['success' => true, 'html' => '<div class="alert alert-warning">Data asal scrap tidak ditemukan.</div>']);
            return;
        }

        // Ambil material asal produksi
        $this->db->select("
            COALESCE(mm.kode_material, sf.kode_barang, fg.kode_barang) AS item_code,
            COALESCE(mm.nama_material, sf.nama_barang, fg.nama_barang) AS item_name,
            COALESCE(mm.satuan, sf.satuan, fg.satuan, 'PCS') AS unit,
            pi.qty_used,
            gh.bc_type_code,
            gh.bc_number,
            gh.bc_date
        ");
        $this->db->from('production_inputs pi');
        $this->db->join('materials mm', 'mm.id = pi.item_id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'sf.id = pi.item_id AND pi.item_type = "semifinished"', 'left');
        $this->db->join('finished_goods fg', 'fg.id = pi.item_id AND pi.item_type = "finished_goods"', 'left');
        $this->db->join('grn_details gd', 'gd.item_id = pi.item_id', 'left');
        $this->db->join('grn_header gh', 'gh.id = gd.grn_header_id', 'left');
        $this->db->where('pi.production_header_id', $production['production_header_id']);
        $materials = $this->db->get()->result_array();

        // Kirim ke view
        $scrap_sources = [[
            'production_number' => $production['production_number'],
            'wo_number'         => $production['wo_number'] ?? '-',
            'process_name'      => $production['stage_name'] ?? '-',
            'fg_name'           => $production['fg_name'] ?? '-',
            'materials'         => $materials
        ]];

        $html = $this->load->view('it_inventory/lap_mutasi_scrap/ajax_get_wh_trace', ['scrap_sources' => $scrap_sources], TRUE);
        echo json_encode(['success' => true, 'html' => $html]);
    }


    /** ============================================================
     *  EKSPOR TRACEABILITY
     * ============================================================ */

    public function export_pdf() {
        if (!check_access('lap_mutasi_scrap','view')) show_error('Forbidden',403);

        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-d'),
            'search'     => trim($this->input->get('search', TRUE)),
        ];

        $report_data = $this->LapMutasiScrap_model->get_mutation_report($filters, 0, 0);

        $data = [
            'title' => $this->report_title,
            'filters' => $filters,
            'report_data' => $report_data
        ];

        try {
            $mpdf = new Mpdf(['format' => 'A4-L']); // Landscape
            $html = $this->load->view('it_inventory/lap_mutasi_scrap/pdf_main_report', $data, true);
            $mpdf->WriteHTML($html);
            $mpdf->Output('Laporan_Mutasi_Scrap_'.date('Ymd').'.pdf', 'I'); // I for inline
        } catch (\Mpdf\MpdfException $e) {
            log_message('error', 'PDF Export Error (LapMutasiScrap): ' . $e->getMessage());
            show_error('Gagal membuat PDF.');
        }
    }

    public function export_production_trace_pdf($prod_id) {
        if (!check_access('lap_mutasi_scrap', 'view')) { show_error('Akses ditolak', 403); return; }
        $trace = $this->LapMutasiScrap_model->get_deep_production($prod_id);
        if (empty($trace) || empty($trace['header_info'])) { show_error('Data produksi tidak ditemukan', 404); return; }

        $h = $trace['header_info'];
        $materials = $trace['materials_used'] ?? [];

        // Menyiapkan data agar cocok dengan view template PDF
        $data = [
            'title' => 'Trace Produksi - ' . ($h->production_number ?? $prod_id),
            'production' => [
                'production_number' => $h->production_number ?? '-',
                'wo_number' => $h->wo_number ?? '-',
                'fg_name' => $h->fg_name ?? '-'
            ],
            'materials' => $materials // Asumsi view bisa handle ex_bc_type, dll.
        ];

        // Menggunakan view template yang sudah ada
        $html = $this->load->view('it_inventory/lap_mutasi_scrap/pdf_template', $data, true);

        try {
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P' // Portrait
            ]);
            $mpdf->SetTitle('Traceability Produksi');
            $mpdf->WriteHTML($html);
            $filename = 'trace_produksi_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $h->production_number ?? $prod_id) . '.pdf';
            $mpdf->Output($filename, 'I'); // 'I' untuk inline/preview
        } catch (\Mpdf\MpdfException $e) {
            log_message('error', 'PDF Export Error (LapMutasiScrap): ' . $e->getMessage());
            show_error('Gagal membuat PDF.');
        }
    }
    // Ekspor Excel untuk Deep Trace Produksi
    public function export_production_trace_excel($prod_id) {
        if (!check_access('lap_mutasi_scrap', 'view')) { show_error('Akses ditolak', 403); return; }
        $trace = $this->LapMutasiScrap_model->get_deep_production($prod_id);
        if (empty($trace)) { show_error('Data produksi tidak ditemukan', 404); return; }

        $h = $trace['header_info'];
        $materials = $trace['materials_used'] ?? [];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Trace Produksi');

        // Header
        $sheet->setCellValue('A1', 'Detail Produksi');
        $sheet->setCellValue('A2', 'No. Produksi:')->setCellValue('B2', $h->production_number ?? '-');
        $sheet->setCellValue('A3', 'Nama FG:')->setCellValue('B3', $h->fg_name ?? '-');
        $sheet->setCellValue('D2', 'No. WO:')->setCellValue('E2', $h->wo_number ?? '-');
        $sheet->setCellValue('D3', 'No. Sales Contract:')->setCellValue('E3', $h->sales_contract ?? '-');

        // Header Tabel Material
        $sheet->setCellValue('A5', 'Material yang menghasilkan scrap (input)');
        $sheet->getStyle('A5')->getFont()->setBold(true);
        $headers = ['Kode', 'Nama', 'Qty Terpakai', 'Satuan', 'BC Asal'];
        $sheet->fromArray($headers, NULL, 'A6');
        $sheet->getStyle('A6:E6')->getFont()->setBold(true);

        // Data Material
        $rowNum = 7;
        foreach ($materials as $m) {
            $sheet->fromArray([
                $m['item_code'] ?? '-',
                $m['item_name'] ?? '-',
                (float)($m['qty_used'] ?? 0),
                $m['unit'] ?? '-',
                ($m['ex_bc_type'] ?? '-') . '/' . ($m['ex_bc_number'] ?? '-')
            ], NULL, 'A'.$rowNum);
            $sheet->getStyle('C'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum++;
        }

        foreach(range('A','E') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'trace_produksi_' . ($h->production_number ?? $prod_id) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    // Ekspor Excel untuk Detail Pengeluaran Scrap
    public function export_out_trace_excel($doc_id) {
        if (!check_access('lap_mutasi_scrap', 'view')) { show_error('Akses ditolak', 403); return; }
        
        $q = $this->db->select('h.id, h.doc_no, h.doc_date, h.destination, c.nama_customer AS customer_name, h.bc_type_code, h.bc_number, h.bc_date, d.scrap_id, m.scrap_name, d.qty, m.unit')
                ->from('scrap_out_header h')
                ->join('scrap_out_detail d', 'h.id = d.header_id', 'left')
                ->join('master_scrap m', 'd.scrap_id = m.id', 'left')
                ->join('customers c', 'h.destination = c.id', 'left')
                ->where('h.id', $doc_id)->get();

        if (!$q || $q->num_rows() == 0) { show_error('Data pengeluaran tidak ditemukan', 404); return; }
        
        $data = $q->result_array();
        $header = $data[0];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Pengeluaran Scrap');

        // Header
        $sheet->setCellValue('A1', 'Detail Pengeluaran Scrap');
        $sheet->setCellValue('A2', 'Nomor Dokumen:')->setCellValue('B2', $header['doc_no']);
        $sheet->setCellValue('A3', 'Tanggal Dokumen:')->setCellValue('B3', $header['doc_date']);
        $sheet->setCellValue('A4', 'Customer / Tujuan:')->setCellValue('B4', $header['customer_name']);
        $sheet->setCellValue('D2', 'BC Type:')->setCellValue('E2', $header['bc_type_code']);
        $sheet->setCellValue('D3', 'BC Number:')->setCellValue('E3', $header['bc_number']);
        $sheet->setCellValue('D4', 'BC Date:')->setCellValue('E4', $header['bc_date']);

        // Header Tabel
        $sheet->fromArray(['No', 'Nama Scrap', 'Qty Keluar', 'Satuan'], NULL, 'A6');
        $sheet->getStyle('A6:D6')->getFont()->setBold(true);

        // Data
        $rowNum = 7;
        $no = 1;
        foreach ($data as $d) {
            $sheet->fromArray([
                $no++,
                $d['scrap_name'],
                (float)$d['qty'],
                $d['unit']
            ], NULL, 'A'.$rowNum);
            $sheet->getStyle('C'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $rowNum++;
        }

        foreach(range('A','E') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'detail_pengeluaran_scrap_' . $header['doc_no'] . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function export_out_trace_pdf($doc_id) {
        if (!check_access('lap_mutasi_scrap', 'view')) { show_error('Akses ditolak', 403); return; }
        
        $q = $this->db->select('h.id, h.doc_no, h.doc_date, h.destination, c.nama_customer AS customer_name, h.bc_type_code, h.bc_number, h.bc_date, d.scrap_id, m.scrap_name, d.qty, m.unit')
                ->from('scrap_out_header h')
                ->join('scrap_out_detail d', 'h.id = d.header_id', 'left')
                ->join('master_scrap m', 'd.scrap_id = m.id', 'left')
                ->join('customers c', 'h.destination = c.id', 'left')
                ->where('h.id', $doc_id)->get();

        if (!$q || $q->num_rows() == 0) { show_error('Data pengeluaran tidak ditemukan', 404); return; }
        
        $data['rows'] = $q->result_array();
        $data['header'] = $data['rows'][0];
        $data['title'] = 'Detail Pengeluaran Scrap';

        try {
            $mpdf = new Mpdf(['format' => 'A4']);
            $html = $this->load->view('it_inventory/lap_mutasi_scrap/pdf_out_trace', $data, true);
            $mpdf->WriteHTML($html);
            $mpdf->Output('Detail_Pengeluaran_Scrap_'.$data['header']['doc_no'].'.pdf', 'I');
        } catch (\Mpdf\MpdfException $e) {
            log_message('error', 'PDF Export Error (export_out_trace_pdf): ' . $e->getMessage());
            show_error('Gagal membuat PDF.');
        }
    }

    public function export_wh_trace_pdf($ledger_id) {
        // Placeholder for WH Trace PDF Export
        // Logic would be similar to export_wh_trace_excel
        // 1. Get data using the same logic as ajax_get_wh_trace
        // 2. Load a specific PDF view template
        // 3. Generate PDF using mPDF
        show_error('Fungsi ekspor PDF untuk Warehouse Trace belum diimplementasikan.', 501);
    }


    // Ekspor Excel untuk Trace Asal Warehouse
    public function export_wh_trace_excel($ledger_id) {
        if (!check_access('lap_mutasi_scrap', 'view')) { show_error('Akses ditolak', 403); return; }
        
        // Logika pengambilan data disalin dari ajax_get_wh_trace
        $ledger = $this->db->get_where('stock_ledger', ['id' => $ledger_id])->row_array();
        if (!$ledger) { show_error('Data ledger tidak ditemukan', 404); return; }
        $production_output_id = ($ledger['transaction_type'] === 'IN_SCRAP_WH') ? $this->db->get_where('scrap_in_detail_sources', ['scrap_in_detail_id' => $this->db->get_where('scrap_in_detail', ['header_id' => $ledger['transaction_id']])->row('id')])->row('production_output_id') : $ledger['transaction_id'];
        $production = $this->db->query("SELECT ph.id AS production_header_id, ph.production_number, wo.wo_number, po.stage_name, (SELECT fg.nama_barang FROM production_outputs po2 LEFT JOIN finished_goods fg ON fg.id = po2.item_id WHERE po2.production_header_id = ph.id AND po2.item_type = 'finished_goods' LIMIT 1) AS fg_name FROM production_outputs po LEFT JOIN production_header ph ON ph.id = po.production_header_id LEFT JOIN wo_header wo ON wo.id = ph.wo_header_id WHERE po.id = ?", [$production_output_id])->row_array();
        if (!$production) { show_error('Data asal scrap tidak ditemukan', 404); return; }
        $materials = $this->db->select("COALESCE(mm.kode_material, sf.kode_barang, fg.kode_barang) AS item_code, COALESCE(mm.nama_material, sf.nama_barang, fg.nama_barang) AS item_name, COALESCE(mm.satuan, sf.satuan, fg.satuan, 'PCS') AS unit, pi.qty_used, gh.bc_type_code, gh.bc_number, gh.bc_date")->from('production_inputs pi')->join('materials mm', 'mm.id = pi.item_id AND pi.item_type = "material"', 'left')->join('semifinished_goods sf', 'sf.id = pi.item_id AND pi.item_type = "semifinished"', 'left')->join('finished_goods fg', 'fg.id = pi.item_id AND pi.item_type = "finished_goods"', 'left')->join('grn_details gd', 'gd.item_id = pi.item_id', 'left')->join('grn_header gh', 'gh.id = gd.grn_header_id', 'left')->where('pi.production_header_id', $production['production_header_id'])->get()->result_array();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Trace Asal Scrap');

        // Header
        $sheet->setCellValue('A1', 'Asal Scrap dari Produksi');
        $sheet->setCellValue('A2', 'No. Produksi:')->setCellValue('B2', $production['production_number']);
        $sheet->setCellValue('A3', 'No. WO:')->setCellValue('B3', $production['wo_number'] ?? '-');
        $sheet->setCellValue('A4', 'Proses:')->setCellValue('B4', $production['stage_name'] ?? '-');
        $sheet->setCellValue('A5', 'Nama FG:')->setCellValue('B5', $production['fg_name'] ?? '-');

        // Header Tabel
        $sheet->fromArray(['Kode Material', 'Nama Material', 'Qty Terpakai', 'Satuan', 'BC Type', 'BC Number', 'BC Date'], NULL, 'A7');
        $sheet->getStyle('A7:G7')->getFont()->setBold(true);

        // Data
        $sheet->fromArray($materials, NULL, 'A8');
        foreach(range('A','G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'trace_asal_scrap_wh_' . $ledger_id . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    public function ajax_get_bc_trace()
    {
        $ledger_id = $this->input->get('ledger_id');
        $this->load->model('LapMutasiScrap_model');

        // Ambil data BC (misalnya dari stock_ledger, grn_header, po_header, dan bc_document)
        $bcData = $this->LapMutasiScrap_model->get_bc_trace($ledger_id);

        if (!$bcData) {
            echo json_encode([
                'success' => true,
                'html' => '
                    <h6 class="mb-2"><i class="fas fa-file-import"></i> Trace Dokumen BC</h6>
                    <div class="alert alert-warning">Data dokumen BC tidak ditemukan.</div>
                '
            ]);
            return;
        }

        $html = $this->load->view('it_inventory/lap_mutasi_scrap/ajax_get_bc_trace', $bcData, true);

        echo json_encode([
            'success' => true,
            'html' => $html
        ]);
    }









    /** ============================================================
     *  AJAX: ASAL MATERIAL SCRAP (TRACE SISI MATERIAL)
     * ============================================================ */
    public function ajax_get_scrap_origin() {
        if (!$this->input->is_ajax_request()) { show_error('Forbidden',403); return; }
        if (!check_access('lap_mutasi_scrap', 'view')) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Akses ditolak']); return; }

        $detail_id = (int)$this->input->get('scrap_in_detail_id');
        if ($detail_id <= 0) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Parameter scrap_in_detail_id kosong']); return; }

        $rows = $this->LapMutasiScrap_model->get_scrap_origin_trace($detail_id);
        if (empty($rows)) { header('Content-Type: application/json'); echo json_encode(['success'=>false,'message'=>'Tidak ditemukan data asal material.']); return; }

        ob_start(); ?>
        <h6><i class="fas fa-history"></i> Asal Material Scrap</h6>
        <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="thead-light text-center">
                <tr><th>Material</th><th>Supplier</th><th>BC</th><th>BC Number</th><th>Tanggal</th><th>WO</th></tr>
            </thead><tbody>
            <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['material_name']); ?></td>
                    <td><?= htmlspecialchars($r['nama_supplier']); ?></td>
                    <td><?= htmlspecialchars($r['bc_type_code']); ?></td>
                    <td><?= htmlspecialchars($r['bc_number']); ?></td>
                    <td><?= htmlspecialchars($r['bc_date']); ?></td>
                    <td><?= htmlspecialchars($r['wo_number']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
        <?php
        $html = ob_get_clean();
        header('Content-Type: application/json');
        echo json_encode(['success'=>true,'html'=>$html]);
    }

    /** ============================================================
     *  EXPORT EXCEL
     * ============================================================ */
    public function export_excel() {
        if (!check_access('lap_mutasi_scrap','view')) show_error('Forbidden',403);
        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-d'),
            'search'     => trim($this->input->get('search', TRUE)),
            'bc_type'    => $this->input->get('bc_type') ?: null
        ];

        $data = $this->LapMutasiScrap_model->get_mutation_report($filters, 0, 0);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', $this->report_title);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $headers = ['Kode Scrap','Nama Scrap','Satuan','Saldo Awal','Masuk','Keluar','Adjustment','Saldo Akhir'];
        $sheet->fromArray($headers,null,'A3');
        $row = 4;

        foreach ($data as $d) {
            $sheet->fromArray([
                $d['item_code'],
                $d['item_name'],
                $d['item_unit'],
                $d['opening_stock'],
                $d['qty_in'],
                $d['qty_out'],
                $d['qty_adj'],
                $d['closing_stock']
            ], null, 'A'.$row);
            $row++;
        }

        foreach (range('A','H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);
        $filename = 'lap_mutasi_scrap_'.date('Ymd_His').'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $writer->save('php://output');
        exit;
    }

}
