<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php'; // Muat Composer
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Stock_ledger extends MY_Controller {

    // Definisikan tipe item di satu tempat agar konsisten
    private $item_types = [
        'material' => 'Material',
        'machine' => 'Machine', // Sesuaikan label jika perlu
        'inventory' => 'Inventory',
        'semifinished' => 'Semifinished Goods', // Sesuaikan label
        'finished' => 'Finished Goods' // Tambahkan jika ada
        // Tambahkan tipe lain jika ada
    ];

    public function __construct() {
        parent::__construct();
        check_login(); // Pastikan helper login sudah di-load
        $this->load->model('Stock_ledger_model');
        $this->load->library('pagination');
        $this->load->helper('url');
        $this->load->library('session'); // Load session untuk flashdata jika perlu
    }

    public function index() {
        if (!check_access('stock_ledger', 'view')) {
            show_error('Anda tidak memiliki izin untuk mengakses halaman ini.', 403);
            return;
        }

        // --- Handle Filter (Lebih Rapi) ---
        // Gunakan nilai default jika GET kosong atau tidak valid
        $current_month = date('m');
        $current_year = date('Y');
        $month = $this->input->get('month') ?: $current_month;
        $year = $this->input->get('year') ?: $current_year;
        $warehouse_type = $this->input->get('warehouse_type');
        $warehouse_type = ($warehouse_type === '' ? null : $warehouse_type); // FIX
 // Bisa kosong (semua tipe)
        $search = $this->input->get('search');

        // Validasi dasar bulan dan tahun (opsional tapi bagus)
        if (!checkdate((int)$month, 1, (int)$year)) {
            $month = $current_month;
            $year = $current_year;
            // Set flashdata error jika perlu
        }

        // Tentukan tanggal awal dan akhir
        $start_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));

        $filters = [
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'warehouse_type' => $warehouse_type,
            'search'         => $search
        ];

        // --- Get ALL Data (DataTables akan handle pagination) ---
        $report_data = $this->Stock_ledger_model->get_stock_summary_report($filters, 0, 0);

        // --- Get Data ---
        $data = [
            'title'        => 'Laporan Ringkasan Stok Barang',
            'report'       => $report_data, // Kirim SEMUA data
            'filters'      => $filters, // Kirim filter ke view untuk ditampilkan
            'selected_month' => $month, // Untuk nilai default dropdown bulan
            'selected_year' => $year,   // Untuk nilai default dropdown tahun
            'item_types'   => $this->item_types, // Gunakan array dari properti class
            'total_rows'   => count($report_data) // Total item
        ];

        // --- Load Views ---
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('stock_ledger/index', $data); // Pastikan view ini ada
        $this->load->view('templates/footer'); // Footer dipanggil di controller agar konsisten
    }
    // === FUNGSI DETAIL HISTORY (FIXED - Menghapus join 'adjustment_header') ===
    public function get_item_transaction_history($item_id, $item_type, $start_date = null, $end_date = null) {
        $this->db->reset_query();
        
        // PERBAIKAN: Menghapus adj_h.doc_no dan adj_h.notes
        $select_clause = "
            DISTINCT sl.id as ledger_id, sl.transaction_date, sl.transaction_type, sl.transaction_id,
            sl.qty_in, sl.qty_out, sl.balance, sl.unit_value,
            sl.source_doc_type, sl.source_doc_id,
            
            -- Info GRN (Pemasukan Material Biasa)
            grn.grn_number, 
            COALESCE(NULLIF(s.nama_supplier, ''), NULLIF(s_po.nama_supplier, ''), NULLIF(s_mir.nama_supplier, '')) as nama_supplier,
            grn.bc_type_code as grn_bc_type, grn.bc_number as grn_bc_no, grn.bc_date as grn_bc_date,
            
            -- Info Retur (Pemasukan dari WIP)
            prh.prod_return_no,
            
            -- Info Pengeluaran (Issue ke WO)
            mr_h.mr_number,
            
            -- Info Scrap In (Pemasukan dari Produksi)
            sih.doc_no as scrap_in_doc_no,
            prod_out.production_header_id, prod_out.stage_name, 
            ph.production_number, -- Sumber dari production_header
            wo_prod.wo_number as production_wo_number, 
            
            -- Info Scrap Out (Pengeluaran BC)
            soh.doc_no as scrap_out_doc_no,
            soh.bc_type_code as scrap_out_bc_type,
            soh.bc_number as scrap_out_bc_no,
            soh.bc_date as scrap_out_bc_date,
            
            -- Info Adjustment
            ah.doc_no as adjustment_doc,
            ah.notes as adjustment_notes
        ";

        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table . ' sl');

        // --- JOIN BERDASARKAN TIPE TRANSAKSI ---
        
        // Pemasukan Material (GRN)
        $this->db->join('grn_header grn', 'sl.transaction_id = grn.id AND sl.transaction_type = "in"', 'left');
        
        // Join with grn_details - for MIR-based GRN supplier lookup
        // Use both PO and MIR joins directly, GRN details will only match relevant source
        $this->db->join('grn_details gd', 'grn.id = gd.grn_header_id AND gd.item_id = sl.item_id', 'left');
        
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');
        
        // Fallback suppliers from PO/MIR when GRN supplier_id is NULL
        $this->db->join('po_header poh', 'gd.source_doc_type = "PO" AND gd.source_doc_header_id = poh.id', 'left');
        $this->db->join('suppliers s_po', 's_po.id = poh.supplier_id', 'left');
        $this->db->join('mir_header mir', 'gd.source_doc_type = "MIR" AND gd.source_doc_header_id = mir.id', 'left');
        $this->db->join('suppliers s_mir', 's_mir.id = mir.supplier_id', 'left');
        
        // Pemasukan Retur WIP
        $this->db->join('prod_return_header prh', 'sl.transaction_id = prh.id AND sl.transaction_type = "IN_RETURN_WIP"', 'left');
        
        // Pengeluaran Issue Material (ke Produksi)
        $report_data = $this->Stock_ledger_model->get_stock_summary_report($filters, 0, 0);
        $this->db->join('mr_header mr_h', 'sl.transaction_id = mr_h.id AND sl.transaction_type IN ("OUT_ISSUE", "production_out")', 'left'); 

        // Pemasukan Scrap (dari Produksi via Scrap In)
        $this->db->join('scrap_in_header sih', 'sl.transaction_id = sih.id AND sl.transaction_type = "IN_SCRAP_WH"', 'left');
        $this->db->join('scrap_in_detail sid', 'sid.header_id = sih.id AND sid.scrap_id = sl.item_id', 'left'); 
        $this->db->join('scrap_in_detail_sources sds', 'sds.scrap_in_detail_id = sid.id', 'left');
        $this->db->join('production_outputs prod_out', 'sds.production_output_id = prod_out.id', 'left');
        $this->db->join('production_header ph', 'prod_out.production_header_id = ph.id', 'left');
        $this->db->join('wo_header wo_prod', 'ph.wo_header_id = wo_prod.id', 'left');

        // Pengeluaran Scrap (via Scrap Out)
        $this->db->join('scrap_out_header soh', 'sl.transaction_id = soh.id AND sl.transaction_type = "OUT_SCRAP_BC"', 'left');
        
        // Adjustment (IN/OUT)
        $this->db->join('adjustment_details ad', 'sl.transaction_id = ad.id AND sl.transaction_type = "adjustment"', 'left');
        $this->db->join('adjustment_header ah', 'ad.adjustment_header_id = ah.id', 'left');
        
        // --- FILTER ---
        $this->db->where('sl.item_id', $item_id);
        $this->db->where('sl.item_type', $item_type);
        
        // PERBAIKAN KRITIS: Filter warehouse transaction types ONLY (jangan WIP)
        $warehouse_tx_types = [
            'in',               // GRN (Barang baru)
            'IN_RETURN_WIP',    // Retur dari Produksi
            'ADJ_IN',           // Adjustment Masuk
            'adjustment',       // Legacy adjustment type (also warehouse)
            'OUT_ISSUE',        // Issue Material ke Produksi
            'production_out',   // Issue Material/Semif ke Produksi
            'OUT_SHIPMENT',     // Kirim Barang Jadi
            'OUT_SCRAP_BC',     // Scrap Keluar Gudang
            'ADJ_OUT',          // Adjustment Keluar
            'IN_SCRAP_WH',       // Scrap Masuk ke Gudang
            'OUT_CONSUMABLE'    // Pengeluaran Barang Habis Pakai
        ];
        $this->db->where_in('sl.transaction_type', $warehouse_tx_types);
        
        if ($start_date) { $this->db->where('sl.transaction_date >=', $start_date . ' 00:00:00'); }
        if ($end_date) { $this->db->where('sl.transaction_date <=', $end_date . ' 23:59:59'); }
        
        // FIX: GROUP BY sl.id to eliminate duplicate rows from multiple JOINs (especially grn_details, supplier tables)
        // DISTINCT sl.id ensures we get one row per ledger entry, not duplicated across suppliers
        $this->db->group_by('sl.id');
        
        $this->db->order_by('sl.transaction_date', 'ASC');
        $this->db->order_by('sl.id', 'ASC');

        $query = $this->db->get();
        if(!$query){
            log_message('error', 'Query get_item_transaction_history GAGAL! DB Error: ' . print_r($this->db->error(), true));
            return [];
        }
        log_message('debug', 'Item History SQL (Success): ' . $this->db->last_query());
        
        $result_array = $query->result_array();
        
        // DEBUG: Log raw result to see what query returns
        if (!empty($result_array)) {
            log_message('debug', 'Stock Ledger raw result count: ' . count($result_array));
            log_message('debug', 'First row nama_supplier: ' . ($result_array[0]['nama_supplier'] ?? 'NULL'));
        }
        
        // Deduplicate by ledger_id and keep first row with supplier info
        $deduplicated = [];
        foreach ($result_array as $row) {
            $ledger_id = $row['ledger_id'];
            if (!isset($deduplicated[$ledger_id])) {
                // First occurrence - save it
                $deduplicated[$ledger_id] = $row;
            } else {
                // Duplicate found - merge supplier name if current row has it
                // Check explicitly for non-empty supplier
                if ((!isset($deduplicated[$ledger_id]['nama_supplier']) || 
                     $deduplicated[$ledger_id]['nama_supplier'] === null || 
                     $deduplicated[$ledger_id]['nama_supplier'] === '') && 
                    isset($row['nama_supplier']) && 
                    $row['nama_supplier'] !== null && 
                    $row['nama_supplier'] !== '') {
                    $deduplicated[$ledger_id]['nama_supplier'] = $row['nama_supplier'];
                }
            }
        }
        
        // DEBUG: Log deduplicated result
        if (!empty($deduplicated)) {
            log_message('debug', 'Deduplicated count: ' . count($deduplicated));
            $first_dedup = reset($deduplicated);
            log_message('debug', 'First dedup nama_supplier: ' . ($first_dedup['nama_supplier'] ?? 'NULL'));
        }
        
        return array_values($deduplicated); // Return as reindexed array
    }

    /**
     * Endpoint AJAX untuk detail transaksi (modal)
     */
    public function get_item_details() {
        // Keamanan: Hanya izinkan request AJAX
        if (!$this->input->is_ajax_request()) {
           show_error('Direct script access is not allowed.', 403);
           return;
        }

        $item_id = $this->input->get('item_id');
        $item_type = $this->input->get('item_type');
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');

        // Validasi input
        if (empty($item_id) || empty($item_type) || empty($start_date) || empty($end_date)) {
            // Kirim respons error JSON
            header('Content-Type: application/json');
            $this->output->set_status_header(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap (item_id, item_type, start_date, end_date wajib ada).']);
            return;
        }

        // Ambil data histori dari model
        // Model akan mengembalikan array, bukan object
        $history = $this->Stock_ledger_model->get_item_transaction_history($item_id, $item_type, $start_date, $end_date);

        // --- Mulai Buat HTML ---
        $html = '<div class="table-responsive"><table class="table table-sm table-bordered table-striped" style="font-size: 0.85rem;">';
        $html .= '<thead class="thead-light text-center"><tr>
                    <th>Tanggal</th>
                    <th>Tipe Transaksi</th>
                    <th>No. Dokumen</th>
                    <th>Partner</th>
                    <th>Info BC</th>
                    <th>Qty Masuk</th>
                    <th>Qty Keluar</th>
                    <th class="text-nowrap">Saldo Akhir</th>
                  </tr></thead><tbody>';

        if (!empty($history)) {
            $running_balance = 0; // Hitung ulang balance hanya dari warehouse transactions
            foreach ($history as &$row) {
                // Hitung balance hanya dari warehouse transactions (bukan WIP)
                $running_balance = (float)$running_balance + (float)$row['qty_in'] - (float)$row['qty_out'];
                $row['balance'] = $running_balance; // Override balance dengan kalkulasi ulang
                
                // --- PERBAIKAN: Logika untuk menentukan info baris ---
                $main_ref = '-';
                $partner_info = '-';
                $bc_info = '-';

                $type = $row['transaction_type'];

                if ($type == 'in') {
                    $main_ref = $row['grn_number'] ?? '-';
                    $partner_info = $row['nama_supplier'] ?? '-';
                    // Prefer BC info coming from the ledger row itself if present
                    if (!empty($row['sl_bc_no'])) {
                        $bc_info = ($row['sl_bc_type'] ?? '') . '/' . $row['sl_bc_no'] . ' (' . ($row['sl_bc_date'] ? date('d/m/y', strtotime($row['sl_bc_date'])) : '-') . ')';
                    } elseif (!empty($row['grn_bc_no'])) {
                        $bc_info = ($row['grn_bc_type'] ?? '') . '/' . $row['grn_bc_no'] . ' (' . ($row['grn_bc_date'] ? date('d/m/y', strtotime($row['grn_bc_date'])) : '-') . ')';
                    }
                } elseif ($type == 'IN_RETURN_WIP') {
                    $main_ref = $row['prod_return_no'] ?? '-';
                    $partner_info = 'Produksi (WIP)';
                } elseif ($type == 'OUT_ISSUE' || $type == 'production_out') {
                    $main_ref = $row['mr_number'] ?? '-';
                    $partner_info = 'Produksi (MR)';
                } elseif ($type == 'IN_SCRAP_WH') {
                     $main_ref = $row['scrap_in_doc_no'] ?? '-';
                     $partner_info = 'Produksi (Scrap)';
                } elseif ($type == 'OUT_SCRAP_BC') {
                    $main_ref = $row['scrap_out_doc_no'] ?? '-';
                    if ($row['scrap_out_bc_no']) {
                         $bc_info = ($row['scrap_out_bc_type'] ?? '') . '/' . $row['scrap_out_bc_no'] . ' (' . ($row['scrap_out_bc_date'] ? date('d/m/y', strtotime($row['scrap_out_bc_date'])) : '-') . ')';
                    }
                } elseif ($type == 'adjustment') {
                    $main_ref = $row['adjustment_doc'] ?? '-';
                    $partner_info = $row['adjustment_notes'] ?? '-';
                } elseif (strpos($type, 'ADJ_') === 0) {
                    $main_ref = $row['adjustment_doc'] ?? 'Adjustment';
                    $partner_info = $row['adjustment_notes'] ?? '-';
                } else if ($type == 'OUT_CONSUMABLE') {
                    $main_ref = $row['consumable_out_doc_no'] ?? '-';
                    $partner_info = '-';
                } else if ($type == 'RTN_SUPPLIER') {
                    $main_ref = $row['rtn_out_number'] ?? ($row['grn_number'] ?? '-');
                    // Partner: try ledger supplier (from GRN source), then fallback to other joins
                    $partner_info = $row['nama_supplier'] ?? '-';
                    // Prefer BC info stored on the ledger row (sl_bc_*) then GRN or RTN header
                    if (!empty($row['sl_bc_no'])) {
                        $bc_info = ($row['sl_bc_type'] ?? '') . '/' . $row['sl_bc_no'] . ' (' . ($row['sl_bc_date'] ? date('d/m/y', strtotime($row['sl_bc_date'])) : '-') . ')';
                    } elseif (!empty($row['grn_bc_no'])) {
                        $bc_info = ($row['grn_bc_type'] ?? '') . '/' . $row['grn_bc_no'] . ' (' . ($row['grn_bc_date'] ? date('d/m/y', strtotime($row['grn_bc_date'])) : '-') . ')';
                    } elseif (!empty($row['nomor_bc_ref'])) {
                        $bc_info = ($row['jenis_bc_desc'] ?? '') . '/' . ($row['nomor_bc_ref'] ?? '') . ' (' . ($row['tanggal_bc_ref'] ? date('d/m/y', strtotime($row['tanggal_bc_ref'])) : '-') . ')';
                    }
                    
                }
                // --- AKHIR PERBAIKAN LOGIKA ---


                // --- PERBAIKAN: Menggunakan $row['...'] ---
                $html .= '<tr>';
                $html .= '<td class="text-nowrap">' . date('d/m/y H:i', strtotime($row['transaction_date'])) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['transaction_type']) . '</td>';
                $html .= '<td class="text-nowrap">' . htmlspecialchars($main_ref) . '</td>';
                $html .= '<td>' . htmlspecialchars($partner_info) . '</td>';
                $html .= '<td class="text-nowrap">' . htmlspecialchars($bc_info) . '</td>';
                $html .= '<td class="text-right">' . ($row['qty_in'] != 0 ? number_format($row['qty_in'], 4, ',', '.') : '-') . '</td>';
                $html .= '<td class="text-right">' . ($row['qty_out'] != 0 ? number_format($row['qty_out'], 4, ',', '.') : '-') . '</td>';
                $html .= '<td class="text-right font-weight-bold">' . number_format($row['balance'], 4, ',', '.') . '</td>';
                $html .= '</tr>';
                // --- AKHIR PERBAIKAN ---
            }
        } else {
            $html .= '<tr><td colspan="8" class="text-center">Tidak ada histori transaksi detail pada periode ini.</td></tr>'; // Sesuaikan colspan
        }
        $html .= '</tbody></table></div>'; // Tutup div responsive

        // Kirim respons JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'html' => $html]);
        return;
    }

    /**
     * Fungsi Export PDF (Mengambil semua data)
     */
    public function export_pdf() {
       // Pastikan user sudah login
       if (!$this->session->userdata('user_id')) {
           show_error('Silakan login terlebih dahulu.', 403);
           return;
       }

       // Ambil filter dari GET (identik dengan index)
       $month = $this->input->get('month') ?: date('m'); $year = $this->input->get('year') ?: date('Y');
       $warehouse_type = $this->input->get('warehouse_type'); $search = $this->input->get('search');
       if (!checkdate((int)$month, 1, (int)$year)) { $month = date('m'); $year = date('Y');}
       $start_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
       $end_date = date('Y-m-t', strtotime($start_date));
       $filters = ['start_date' => $start_date, 'end_date' => $end_date, 'warehouse_type' => $warehouse_type, 'search' => $search];

       // Ambil SEMUA data (limit = 0)
       $report_data = $this->Stock_ledger_model->get_stock_summary_report($filters, 0, 0);

       // Siapkan data untuk view PDF
       $data = [
           'report' => $report_data,
           'periode' => date('F Y', strtotime($start_date)),
           'warehouse_type_label' => $warehouse_type ? ($this->item_types[$warehouse_type] ?? ucfirst($warehouse_type)) : 'Semua Gudang', // Gunakan array item_types
           'search' => $search,
           'filters' => $filters // Kirim filter lengkap jika perlu ditampilkan di PDF
       ];

       try {
           $options = new Options();
           $options->set('isHtml5ParserEnabled', true);
           $options->set('isRemoteEnabled', true); // Untuk gambar/CSS eksternal jika ada
           $dompdf = new Dompdf($options);
           $html = $this->load->view('stock_ledger/pdf_template', $data, true); // Pastikan view template PDF ada
           $dompdf->loadHtml($html);
           $dompdf->setPaper('A4', 'landscape'); // Landscape karena tabel lebar
           $dompdf->render();
           $filename = "Laporan_Stok_".($warehouse_type ? str_replace(' ', '_', $warehouse_type).'_' : '').date('M_Y', strtotime($start_date)).".pdf"; // Nama file lebih deskriptif
           $dompdf->stream($filename, ["Attachment" => 0]); // 0 = preview
           exit; // Hentikan eksekusi setelah stream
       } catch (Exception $e) {
            log_message('error', 'PDF Export Error: ' . $e->getMessage());
            show_error('Gagal membuat PDF. Cek log error.');
       }
    }

    /**
     * Fungsi Export Excel (Mengambil semua data)
     */
    public function export_excel() {
        // Pastikan user sudah login
        if (!$this->session->userdata('user_id')) {
            show_error('Silakan login terlebih dahulu.', 403);
            return;
        }

        // Ambil filter dari GET (identik dengan index)
        $month = $this->input->get('month') ?: date('m'); $year = $this->input->get('year') ?: date('Y');
        $warehouse_type = $this->input->get('warehouse_type'); $search = $this->input->get('search');
        if (!checkdate((int)$month, 1, (int)$year)) { $month = date('m'); $year = date('Y');}
        $start_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-01';
        $end_date = date('Y-m-t', strtotime($start_date));
        $filters = ['start_date' => $start_date, 'end_date' => $end_date, 'warehouse_type' => $warehouse_type, 'search' => $search];

        // Ambil SEMUA data (limit = 0)
        $report_data = $this->Stock_ledger_model->get_stock_summary_report($filters, 0, 0);

        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $periode = date('F Y', strtotime($start_date));
            $sheet->setTitle("Stok " . date('My', strtotime($start_date))); // Judul sheet lebih pendek

            // --- Header Laporan ---
            $sheet->setCellValue('A1', 'LAPORAN RINGKASAN STOK BARANG'); $sheet->mergeCells('A1:I1');
            $sheet->setCellValue('A2', 'Periode: ' . $periode); $sheet->mergeCells('A2:I2');
            $warehouse_label = $warehouse_type ? ($this->item_types[$warehouse_type] ?? ucfirst($warehouse_type)) : 'Semua Gudang';
            $sheet->setCellValue('A3', 'Gudang: ' . $warehouse_label); $sheet->mergeCells('A3:I3');
            if ($search) {
                $sheet->setCellValue('A4', 'Pencarian: ' . $search); $sheet->mergeCells('A4:I4');
            }
            $sheet->getStyle('A1:A4')->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // --- Header Tabel ---
            $headerRow = $search ? 6 : 5; // Geser header jika ada baris pencarian
            $headers = ['Gudang', 'Kode Barang', 'Nama Barang', 'Satuan', 'Stok Awal', 'Masuk (GRN)', 'Masuk (Retur)', 'Keluar', 'Stok Akhir'];
            $sheet->fromArray($headers, NULL, 'A'.$headerRow);
            $sheet->getStyle('A'.$headerRow.':I'.$headerRow)->getFont()->setBold(true);
            $sheet->getStyle('A'.$headerRow.':I'.$headerRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            $sheet->getStyle('A'.$headerRow.':I'.$headerRow)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
            $sheet->getRowDimension($headerRow)->setRowHeight(20); // Tinggi baris header

            // --- Isi Data ---
            $rowNum = $headerRow + 1;
            foreach ($report_data as $row) {
                $rowData = [
                    $this->item_types[$row->item_type] ?? ucfirst($row->item_type), // Ambil label dari array
                    $row->item_code,
                    $row->item_name,
                    $row->item_unit,
                    $row->opening_stock,
                    $row->receive_qty, // Masuk GRN
                    $row->return_qty, // Masuk Retur
                    $row->out_qty,
                    $row->closing_stock
                ];
                $sheet->fromArray($rowData, NULL, 'A'.$rowNum);
                // Format Angka untuk kolom E sampai I
                $sheet->getStyle('E'.$rowNum.':I'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00_);[Red](#,##0.00)');
                $rowNum++;
            }

            // --- Styling Akhir ---
            $lastRow = $rowNum - 1;
            if ($lastRow >= $headerRow + 1) { // Hanya format jika ada data
                 $sheet->getStyle('A'.$headerRow.':I'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }
            // Atur lebar kolom otomatis
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            // Freeze header row
            $sheet->freezePane('A'.($headerRow + 1));


            // --- Output File ---
            $writer = new Xlsx($spreadsheet);
            $filename = 'Laporan_Stok_'.($warehouse_type ? str_replace(' ', '_', $warehouse_type).'_' : '').date('My', strtotime($start_date)).'.xlsx';
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="'. $filename.'"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit; // Hentikan eksekusi

        } catch (Exception $e) {
            log_message('error', 'Excel Export Error: ' . $e->getMessage());
            show_error('Gagal membuat Excel. Cek log error.');
        }
    }

    /**
     * Debug method to check Stock Ledger supplier data
     */
    public function debug_supplier($item_id = null, $item_type = 'material') {
        if (!$item_id) {
            echo "Usage: /Stock_ledger/debug_supplier/[item_id]/[item_type]\n";
            return;
        }

        echo "<h2>DEBUG: Stock Ledger Supplier Data</h2>";
        echo "Item ID: $item_id, Type: $item_type<br><br>";

        // Test query
        $query = "
        SELECT 
            sl.id, sl.transaction_date, sl.transaction_type, sl.transaction_id,
            grn.grn_number, grn.supplier_id as grn_supplier_id,
            s.nama_supplier as from_grn_supplier,
            gd.source_doc_type, gd.source_doc_header_id,
            po.supplier_id as po_supplier_id, po_s.nama_supplier as from_po,
            mir.supplier_id as mir_supplier_id, mir_s.nama_supplier as from_mir,
            COALESCE(s.nama_supplier, po_s.nama_supplier, mir_s.nama_supplier) as nama_supplier_final
        FROM stock_ledger sl
        LEFT JOIN grn_header grn ON sl.transaction_id = grn.id AND sl.transaction_type = 'in'
        LEFT JOIN suppliers s ON grn.supplier_id = s.id
        LEFT JOIN grn_details gd ON grn.id = gd.grn_header_id AND gd.item_id = sl.item_id
        LEFT JOIN po_header po ON gd.source_doc_type = 'PO' AND gd.source_doc_header_id = po.id
        LEFT JOIN suppliers po_s ON po_s.id = po.supplier_id
        LEFT JOIN mir_header mir ON gd.source_doc_type = 'MIR' AND gd.source_doc_header_id = mir.id
        LEFT JOIN suppliers mir_s ON mir_s.id = mir.supplier_id
        WHERE sl.item_id = {$item_id} AND sl.item_type = '{$item_type}' AND sl.transaction_type = 'in'
        LIMIT 5
        ";

        $result = $this->db->query($query)->result();

        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>GRN</th><th>GRN Supplier</th><th>Source Doc</th><th>From PO</th><th>From MIR</th><th>COALESCE Result</th></tr>";

        foreach ($result as $row) {
            echo "<tr>";
            echo "<td>{$row->grn_number}</td>";
            echo "<td>{$row->from_grn_supplier} (ID: {$row->grn_supplier_id})</td>";
            echo "<td>{$row->source_doc_type} #{$row->source_doc_header_id}</td>";
            echo "<td>{$row->from_po}</td>";
            echo "<td>{$row->from_mir}</td>";
            echo "<td><strong>{$row->nama_supplier_final}</strong></td>";
            echo "</tr>";
        }

        echo "</table>";
    }

} // End Class