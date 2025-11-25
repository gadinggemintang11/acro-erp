<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Warehouse_in extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model([
            'Warehouse_in_model', 'Commoncode_model', 'Po_model',
            'Mir_model', 'Supplier_model', 'Stock_ledger_model' // Pastikan Stock_ledger_model ada
        ]);
        $this->load->helper('url');
    }

    public function index() {
        if (!check_access('warehouse_in', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $data = [
            'title'     => 'Penerimaan Barang (GRN)',
            'grns'      => $this->Warehouse_in_model->get_all(),
            'bc_types'  => $this->Commoncode_model->get_by_group('BC_TYPE'),
            'warehouse_types' => [
                'material' => 'Material', 'machine' => 'Machine / Equipment',
                'inventory' => 'Inventory', 'semifinished' => 'Semifinished Goods'
            ]
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('warehouse_in/index', $data);
        $this->load->view('templates/footer');
    }

    public function add_header()
{
    // Atur rules validasi Anda di sini
    $this->form_validation->set_rules('grn_date', 'Tanggal', 'required');
    $this->form_validation->set_rules('base_on', 'Dasar Penerimaan', 'required');
    $this->form_validation->set_rules('warehouse_type', 'Tipe Gudang', 'required');
    // Tambahkan validasi lain jika perlu

    if ($this->form_validation->run() == FALSE) {
        // Jika validasi gagal
        $this->session->set_flashdata('error', 'Input tidak valid. ' . validation_errors());
        redirect('warehouse_in');
    } else {
        
        // =========================================================
        // MULAI DATABASE TRANSACTION
        // Ini untuk mengunci proses dan mencegah "race condition"
        // =========================================================
        $this->db->trans_start();

        // 1. Panggil fungsi generator nomor
        $grn_number = $this->_generate_grn_number();

        // 2. Siapkan data untuk insert
        $data = [
            'grn_number'          => $grn_number, // Gunakan nomor yang baru dibuat
            'grn_date'            => $this->input->post('grn_date'),
            'base_on'             => $this->input->post('base_on'),
            'warehouse_type'      => $this->input->post('warehouse_type'),
            // 'supplier_id'      => NULL, // Biarkan NULL, akan diisi saat add item PO
            'delivery_note_number' => $this->input->post('delivery_note_number'),
            'bc_type_code'        => $this->input->post('bc_type_code') ?: NULL, // Set NULL jika kosong
            'bc_date'             => $this->input->post('bc_date') ?: NULL, // Set NULL jika kosong
            'bc_number'           => $this->input->post('bc_number') ?: NULL, // Set NULL jika kosong
            'remarks'             => $this->input->post('remarks'),
            'status'              => 'Draft',
            'created_by'          => $this->session->userdata('user_id') // Pastikan session ini ada
        ];

        // 3. Panggil model untuk insert
        // Ganti 'Warehouse_in_model' dengan nama model Anda yang benar
        $insert_id = $this->Warehouse_in_model->add_header($data); 

        // =========================================================
        // SELESAI DATABASE TRANSACTION
        // =========================================================
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            // Jika transaksi gagal (termasuk jika ada error duplicate lagi)
            $this->session->set_flashdata('error', 'Gagal membuat GRN. Terjadi kesalahan database.');
            redirect('warehouse_in');
        } else {
            // Jika transaksi berhasil
            if ($insert_id) {
                $this->session->set_flashdata('success', 'Header GRN ' . $grn_number . ' berhasil dibuat. Silakan tambahkan item.');
                // Langsung arahkan ke halaman detail untuk tambah item
                redirect('warehouse_in/detail/' . $insert_id);
            } else {
                $this->session->set_flashdata('error', 'Gagal membuat GRN. Model tidak mengembalikan ID.');
                redirect('warehouse_in');
            }
        }
    }
}

    public function update_header($id) {
        if (!check_access('warehouse_in', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $header = $this->Warehouse_in_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('error', 'Header GRN tidak bisa diedit!');
             redirect('warehouse_in');
        }
        $data = [
            'grn_date'             => $this->input->post('grn_date'),
            'delivery_note_number' => $this->input->post('delivery_note_number'),
            'bc_type_code'         => $this->input->post('bc_type_code'),
            'bc_date'              => $this->input->post('bc_date') ?: NULL,
            'bc_number'            => $this->input->post('bc_number'),
            'remarks'              => $this->input->post('remarks'),
        ];
        if ($this->Warehouse_in_model->update_header($id, $data)) {
            $this->session->set_flashdata('success', 'Header GRN diperbarui.');
        } else {
            $this->session->set_flashdata('error', 'Gagal update header GRN.');
        }
        redirect('warehouse_in');
    }

    public function delete_header($id) {
        if (!check_access('warehouse_in', 'delete')) { show_error('Anda tidak memiliki izin.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Warehouse_in_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'GRN tidak bisa dihapus (status bukan Draft)!');
            redirect('warehouse_in');
            return;
        }
        
        // Model delete_header() yang baru sudah mengecek detail
        if ($this->Warehouse_in_model->delete_header($id)) {
            $this->session->set_flashdata('success', 'GRN Draft berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus GRN (pastikan tidak ada item detail).');
        }
        redirect('warehouse_in');
    }

    public function detail($id) {
        if (!check_access('warehouse_in', 'view')) { show_error('Anda tidak memiliki izin.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Warehouse_in_model->get_header_by_id($id);
        if (!$header) { show_404(); }

        $data = [
            'title'   => 'Detail Penerimaan Barang',
            'header'  => $header,
            'details' => $this->Warehouse_in_model->get_details_by_id($id),
            'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE')
            // Tambahkan data lain jika perlu untuk modal edit
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('warehouse_in/detail', $data);
        // Footer dipanggil di view detail.php
    }

    // === Confirm / Unconfirm ===
    public function confirm_grn($id) {
        if (!check_access('warehouse_in', 'edit')) { show_error('Anda tidak memiliki izin.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Warehouse_in_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('warning', 'Hanya GRN status Draft yang bisa dikonfirmasi.');
            redirect('warehouse_in/detail/' . $id);
            return;
        }
        $details = $this->Warehouse_in_model->get_details_by_id($id);
        if (empty($details)) {
            $this->session->set_flashdata('error', 'GRN kosong, tidak bisa dikonfirmasi.');
            redirect('warehouse_in/detail/' . $id);
            return;
        }

        // 1. Tambah Stok (Model sudah FIFO & Traceability)
        if ($this->Warehouse_in_model->add_stock_to_ledger_and_batch($id)) {
            // 2. Update Status Header
            $user_id = $this->session->userdata('user_id') ?? null;
            if ($this->Warehouse_in_model->update_status($id, 'Completed', $user_id)) {
                $this->session->set_flashdata('success', 'GRN dikonfirmasi. Stok batch dan ledger telah ditambahkan.');
            } else {
                // Rollback manual (jarang terjadi, tapi untuk keamanan)
                log_message('error', 'GRN: Kritis! Gagal update status ke Completed setelah stok ditambah. GRN ID: ' . $id);
                $this->Warehouse_in_model->remove_stock_from_ledger_and_batch($id); // Rollback stok
                $this->session->set_flashdata('error', 'Gagal update status GRN! Stok dibatalkan.');
            }
        } else {
            // Model add_stock... return false (kemungkinan DB error)
            $this->session->set_flashdata('error', 'Gagal menambahkan stok batch/ledger! Transaksi dibatalkan.');
        }
        redirect('warehouse_in/detail/' . $id);
    }

    public function unconfirm_grn($id) {
        if (!check_access('warehouse_in', 'edit')) { show_error('Anda tidak memiliki izin.', 403); return; }
        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Warehouse_in_model->get_header_by_id($id);
        if (!$header || $header->status != 'Completed') {
            $this->session->set_flashdata('warning', 'Hanya GRN status Completed yang bisa dibatalkan.');
            redirect('warehouse_in/detail/' . $id);
            return;
        }

        // 1. Hapus Stok (Model sudah validasi stok terpakai)
        $remove_result = $this->Warehouse_in_model->remove_stock_from_ledger_and_batch($id);

        // --- REVISI DI SINI ---
        if ($remove_result === true) { // Jika sukses (true)
            // 2. Update Status Header
            if ($this->Warehouse_in_model->update_status($id, 'Draft')) {
                $this->session->set_flashdata('success', 'Konfirmasi GRN dibatalkan. Stok batch dan ledger telah dikembalikan/dihapus.');
            } else {
                // Kritis: Stok sudah dihapus tapi status gagal update
                log_message('error', 'GRN: Kritis! Gagal update status ke Draft setelah stok dihapus. GRN ID: ' . $id);
                $this->session->set_flashdata('error', 'Stok berhasil dihapus, tapi gagal update status header!');
            }
        } elseif ($remove_result === false) { // Jika gagal (false)
             // Model mengembalikan false, artinya stok sudah terpakai
             $this->session->set_flashdata('error', 'Gagal membatalkan GRN! Sebagian stok dari batch ini sudah terpakai di produksi.');
        } else {
             // Handle jika ada return lain (meskipun model saat ini hanya true/false)
             $this->session->set_flashdata('error', 'Gagal mengembalikan stok batch (Database Error).');
        }
        // --- AKHIR REVISI ---

        redirect('warehouse_in/detail/' . $id);
    }

    // === AJAX Endpoints ===
    public function search_source_docs() {
        $type = $this->input->get('type');
        $term = $this->input->get('q');
        $docs = $this->Warehouse_in_model->search_source_docs($type, $term);
        $results = [];
        foreach ($docs as $doc) {
            $results[] = [
                'id' => $doc->id,
                'text' => htmlspecialchars($doc->number),
                'supplier_id' => $doc->supplier_id
            ];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }
    public function get_available_items() {
        $source_type = $this->input->get('source_type');
        $source_id   = $this->input->get('source_id');
        $warehouse_type = $this->input->get('warehouse_type');
        $items = $this->Warehouse_in_model->get_available_items($source_type, $source_id, $warehouse_type);
        $options = '<option value="">-- Pilih Item --</option>';
        foreach ($items as $item) {
            $options .= '<option value="' . $item->source_detail_id . '|' . $item->item_id . '|' . $item->item_type . '" '
                      . 'data-maxqty="' . $item->remaining_qty . '" '
                      . 'data-price="' . ($source_type == 'PO' ? $item->source_unit_price : 0) . '">'
                      . htmlspecialchars($item->item_name . ' (' . $item->item_code . ') - Sisa: ' . number_format($item->remaining_qty, 2))
                      . '</option>';
        }
        echo $options;
    }
    private function _generate_grn_number()
    {
        $prefix = 'GRN';
        $period = date('Ym'); // e.g., 202510
        $full_prefix = $prefix . '/' . $period . '/';
        $padding_length = 4; // 0001, 0002, ... 9999

        // 1. Cari nomor MAX di periode yang sama
        // Query: SELECT MAX(grn_number) as max_num FROM grn_header WHERE grn_number LIKE 'GRN/202510/%'
        $this->db->select_max('grn_number', 'max_num');
        $this->db->like('grn_number', $full_prefix, 'after'); // 'after' = LIKE 'prefix%'
        $query = $this->db->get('grn_header');
        $row = $query->row();

        $running_number = 1; // Default nomor 1 jika ini yang pertama di bulan ini

        if ($row && $row->max_num) {
            // 2. Jika sudah ada data (e.g., GRN/202510/0001)
            // Ambil bagian nomor urutnya saja ("0001")
            $last_number_str = substr($row->max_num, strlen($full_prefix)); 
            
            // Ubah jadi integer (1), lalu tambah 1 (jadi 2)
            $running_number = (int)$last_number_str + 1;
        }

        // 3. Tambahkan padding nol di depan (e.g., 2 -> "0002")
        $new_running_number = str_pad($running_number, $padding_length, '0', STR_PAD_LEFT);

        // 4. Gabungkan (e.g., "GRN/202510/0002")
        return $full_prefix . $new_running_number;
    }

    // === Item Actions ===
    public function add_item($grn_id) {
        if (!check_access('warehouse_in', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        $header = $this->Warehouse_in_model->get_header_by_id($grn_id);
        if (!$header || $header->status != 'Draft') { redirect('warehouse_in/detail/' . $grn_id); }

        $source_doc_type = $header->base_on;
        $source_doc_header_id = $this->input->post('source_doc_id');

        // Update supplier_id
        if (empty($header->supplier_id) && $source_doc_header_id) {
            $source_header = ($source_doc_type == 'PO')
                                ? $this->Po_model->get_po_header_by_id($source_doc_header_id)
                                : $this->Mir_model->get_header_by_id($source_doc_header_id);
            if ($source_header && !empty($source_header->supplier_id)) {
                $this->Warehouse_in_model->update_header($grn_id, ['supplier_id' => $source_header->supplier_id]);
            }
        }

        $item_data = explode('|', $this->input->post('item_selector'));
        $source_doc_detail_id = $item_data[0]; $item_id = $item_data[1]; $item_type = $item_data[2];
        $receive_qty = (float) $this->input->post('receive_qty');
        $unit_value = ($source_doc_type == 'PO')
                        ? (float) $this->input->post('item_price_hidden')
                        : (float) str_replace(',', '', $this->input->post('unit_value_mir'));
        $total_value = $receive_qty * $unit_value;

        $data = [
            'grn_header_id' => $grn_id, 'source_doc_header_id' => $source_doc_header_id,
            'source_doc_detail_id' => $source_doc_detail_id, 'source_doc_type' => $source_doc_type,
            'item_id' => $item_id, 'item_type' => $item_type, 'receive_qty' => $receive_qty,
            'unit_value' => $unit_value, 'total_value' => $total_value
        ];
        if ($this->Warehouse_in_model->insert_item($data)) { $this->session->set_flashdata('success', 'Item diterima.'); }
        else { $this->session->set_flashdata('error', 'Gagal menerima item!'); }
        redirect('warehouse_in/detail/' . $grn_id);
    }

    public function delete_item($grn_id, $detail_id) {
        if (!check_access('warehouse_in', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        $header = $this->Warehouse_in_model->get_header_by_id($grn_id);
        if (!$header || $header->status != 'Draft') { redirect('warehouse_in/detail/' . $grn_id); }
        if ($this->Warehouse_in_model->delete_item($detail_id)) { $this->session->set_flashdata('success', 'Item dihapus.'); }
        else { $this->session->set_flashdata('error', 'Gagal menghapus item!'); }
        redirect('warehouse_in/detail/' . $grn_id);
    }
    public function export_excel($id) {
       if (!check_access('warehouse_in', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        $header = $this->Warehouse_in_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Warehouse_in_model->get_details_by_id($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // --- Header GRN ---
        $sheet->setCellValue('A1', 'GOODS RECEIPT NOTE');
        $sheet->mergeCells('A1:G1'); // Sesuaikan range jika kolom bertambah
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_DARKBLUE);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Nomor GRN:'); $sheet->setCellValue('B3', $header->grn_number);
        $sheet->setCellValue('D3', 'Tanggal:'); $sheet->setCellValue('E3', date('d-m-Y', strtotime($header->grn_date)));
        $sheet->setCellValue('A4', 'Supplier:'); $sheet->setCellValue('B4', $header->nama_supplier ?? '-');
        $sheet->setCellValue('D4', 'Dasar:'); $sheet->setCellValue('E4', $header->base_on);
        $sheet->setCellValue('A5', 'No. SJ:'); $sheet->setCellValue('B5', $header->delivery_note_number ?? '-');
        $sheet->setCellValue('D5', 'Gudang:'); $sheet->setCellValue('E5', ucfirst($header->warehouse_type));
        // Tambahkan BC Info jika perlu

        // --- Detail Item ---
        $startRow = 8;
        $sheet->setCellValue('A'.$startRow, 'No');
        $sheet->setCellValue('B'.$startRow, 'No. Sumber');
        $sheet->setCellValue('C'.$startRow, 'Kode Item');
        $sheet->setCellValue('D'.$startRow, 'Nama Item');
        $sheet->setCellValue('E'.$startRow, 'Qty Terima');
        $sheet->setCellValue('F'.$startRow, 'Nilai Satuan (IDR)');
        $sheet->setCellValue('G'.$startRow, 'Total Nilai (IDR)');
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getFont()->setBold(true);
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getStyle('A'.$startRow.':G'.$startRow)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);

        $rowNum = $startRow + 1;
        $no = 1;
        $grand_total_value = 0;
        foreach ($details as $d) {
            $sheet->setCellValue('A'.$rowNum, $no++);
            $sheet->setCellValue('B'.$rowNum, $d->source_doc_number);
            $sheet->setCellValue('C'.$rowNum, $d->item_code);
            $sheet->setCellValue('D'.$rowNum, $d->item_name);
            $sheet->setCellValue('E'.$rowNum, $d->receive_qty);
            $sheet->setCellValue('F'.$rowNum, $d->unit_value ?? 0);
            $sheet->setCellValue('G'.$rowNum, $d->total_value ?? 0);

            $sheet->getStyle('E'.$rowNum.':G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $grand_total_value += ($d->total_value ?? 0);
            $rowNum++;
        }
        $sheet->getStyle('A'.$startRow.':G'.($rowNum-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);


        // --- Grand Total ---
        $sheet->setCellValue('F'.$rowNum, 'Grand Total Nilai (IDR):');
        $sheet->setCellValue('G'.$rowNum, $grand_total_value);
        $sheet->getStyle('F'.$rowNum.':G'.$rowNum)->getFont()->setBold(true);
        $sheet->getStyle('G'.$rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('A'.$rowNum.':G'.$rowNum)->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);


        // Atur lebar kolom otomatis
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // --- Output File ---
        $writer = new Xlsx($spreadsheet);
        $filename = 'GRN_'.str_replace('/', '-', $header->grn_number).'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'. $filename.'"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
    public function export_pdf($id) {
        if (!check_access('warehouse_in', 'view')) {
             show_error('Anda tidak memiliki izin.', 403);
        }
        $header = $this->Warehouse_in_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Warehouse_in_model->get_details_by_id($id);

        $data = ['header' => $header, 'details' => $details];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        
        $html = $this->load->view('warehouse_in/pdf_template', $data, true); 
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = "GRN_".str_replace('/', '-', $header->grn_number).".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]); // Preview
    }
    
}