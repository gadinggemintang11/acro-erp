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
                // Jika base_on = MIR, redirect ke auto-load items endpoint dengan MIR ID
                $base_on = $this->input->post('base_on');
                if ($base_on === 'MIR') {
                    $this->session->set_flashdata('info', 'GRN dibuat. Memuat items dari MIR...');
                    // Redirect ke detail page dengan pesan untuk trigger auto-load
                    // User akan melihat modal untuk memilih MIR nomor di detail page
                    redirect('warehouse_in/detail/' . $insert_id);
                } else {
                    $this->session->set_flashdata('success', 'Header GRN ' . $grn_number . ' berhasil dibuat. Silakan tambahkan item.');
                    // Langsung arahkan ke halaman detail untuk tambah item
                    redirect('warehouse_in/detail/' . $insert_id);
                }
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
            'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE'),
            'user_role' => $this->session->userdata('role_id') // Pass user role to view
            // Tambahkan data lain jika perlu untuk modal edit
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('warehouse_in/detail', $data);
        // Footer dipanggil di view detail.php
    }

    /**
     * AJAX: Get list of available MIR numbers for selection
     * Used in Multi-MIR selection modal
     */
    public function get_available_mir_numbers() {
        if (!check_access('warehouse_in', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $grn_id = $this->input->get('grn_id');
        $search_term = $this->input->get('q') ?? '';

        if (empty($grn_id) || !is_numeric($grn_id)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid GRN ID']);
            return;
        }

        $header = $this->Warehouse_in_model->get_header_by_id($grn_id);
        if (!$header || $header->base_on != 'MIR') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'GRN is not MIR-based']);
            return;
        }

        // Get MIR list with available items
        $this->db->select('
            mh.id as mir_id,
            mh.mir_number,
            mh.request_date,
            mh.status as mir_status,
            COUNT(md.id) as total_items,
            SUM(CASE WHEN gd.id IS NULL THEN 1 ELSE 0 END) as available_items
        ')
        ->from('mir_header mh')
        ->join('mir_details md', 'md.mir_header_id = mh.id', 'left')
        ->join('grn_details gd', 'gd.source_doc_detail_id = md.id AND gd.source_doc_type = "MIR"', 'left')
        ->where('mh.status', 'Approved') // Only approved MIR
        ->group_by('mh.id');

        // Search filter
        if (!empty($search_term)) {
            $this->db->like('mh.mir_number', $search_term);
        }

        $this->db->order_by('mh.request_date', 'DESC');
        $mirs = $this->db->get()->result();

        $results = [];
        foreach ($mirs as $mir) {
            // Get item details for tooltip
            $items = $this->db
                ->select('md.id, md.item_id, md.item_type, md.qty, md.allowance, md.total_qty')
                ->from('mir_details md')
                ->join('grn_details gd', 'gd.source_doc_detail_id = md.id AND gd.source_doc_type = "MIR"', 'left')
                ->where('md.mir_header_id', $mir->mir_id)
                ->where('gd.id', NULL) // Only items not yet received
                ->get()
                ->result();

            $item_count = count($items);
            if ($item_count > 0) { // Only show MIR with remaining items
                $results[] = [
                    'mir_id' => $mir->mir_id,
                    'mir_number' => $mir->mir_number,
                    'mir_date' => $mir->request_date,
                    'total_items' => $mir->total_items,
                    'available_items' => $item_count,
                    'items_summary' => $items
                ];
            }
        }

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $results]);
    }

    /**
     * Auto-load items from MULTIPLE MIR numbers at once
     * AJAX endpoint for multi-MIR selection feature
     */
    public function auto_load_multiple_mir_items() {
        if (!check_access('warehouse_in', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $grn_id = $this->input->post('grn_id');
        $mir_ids = $this->input->post('mir_ids'); // Array of MIR IDs

        if (empty($grn_id) || !is_numeric($grn_id) || empty($mir_ids) || !is_array($mir_ids)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            return;
        }

        $header = $this->Warehouse_in_model->get_header_by_id($grn_id);
        if (!$header || $header->status != 'Draft' || $header->base_on != 'MIR') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'GRN tidak valid untuk auto-load MIR items']);
            return;
        }

        // Start transaction
        $this->db->trans_start();

        $total_loaded = 0;
        $error_mirs = [];

        foreach ($mir_ids as $mir_id) {
            if (!is_numeric($mir_id)) continue;

            $mir_header = $this->Mir_model->get_header_by_id($mir_id);
            if (!$mir_header) {
                $error_mirs[] = 'MIR ID ' . $mir_id . ' tidak ditemukan';
                continue;
            }

            // Get items from this MIR
            $all_mir_items = $this->db
                ->select('md.id as mir_detail_id, md.item_id, md.item_type, md.qty, md.allowance, md.total_qty, md.unit_value')
                ->from('mir_details md')
                ->where('md.mir_header_id', $mir_id)
                ->get()
                ->result();

            // Filter items that still need to be received
            $mir_details = [];
            foreach ($all_mir_items as $item) {
                $received_result = $this->db->select('SUM(receive_qty) as total_received')
                                            ->from('grn_details')
                                            ->where('source_doc_header_id', $mir_id)
                                            ->where('source_doc_type', 'MIR')
                                            ->where('source_doc_detail_id', $item->mir_detail_id)
                                            ->get()
                                            ->row();
                
                $total_received = $received_result->total_received ?? 0;
                $receive_qty = $item->total_qty - $total_received;
                
                if ($receive_qty > 0.001) {
                    $item->remaining_qty = $receive_qty;
                    $mir_details[] = $item;
                }
            }

            if (empty($mir_details)) continue;

            // Load items from this MIR
            foreach ($mir_details as $md) {
                $receive_qty = $md->remaining_qty;
                $unit_value = (float)($md->unit_value ?? 0);
                $total_value = $receive_qty * $unit_value;

                // Get item name and code
                $item_code = '';
                $item_name = '';
                if ($md->item_type == 'material') {
                    $item = $this->db->select('kode_material, nama_material')->from('materials')->where('id', $md->item_id)->get()->row();
                    if ($item) {
                        $item_code = $item->kode_material ?? '';
                        $item_name = $item->nama_material ?? '';
                    }
                } else if ($md->item_type == 'semifinished') {
                    $item = $this->db->select('kode_barang, nama_barang')->from('semifinished_goods')->where('id', $md->item_id)->get()->row();
                    if ($item) {
                        $item_code = $item->kode_barang ?? '';
                        $item_name = $item->nama_barang ?? '';
                    }
                } else if ($md->item_type == 'machine') {
                    $item = $this->db->select('kode_mesin, nama_mesin')->from('machines')->where('id', $md->item_id)->get()->row();
                    if ($item) {
                        $item_code = $item->kode_mesin ?? '';
                        $item_name = $item->nama_mesin ?? '';
                    }
                } else if ($md->item_type == 'inventory') {
                    $item = $this->db->select('kode_barang, nama_barang')->from('inventories')->where('id', $md->item_id)->get()->row();
                    if ($item) {
                        $item_code = $item->kode_barang ?? '';
                        $item_name = $item->nama_barang ?? '';
                    }
                }

                $data = [
                    'grn_header_id' => $grn_id,
                    'source_doc_header_id' => $mir_id,
                    'source_doc_detail_id' => $md->mir_detail_id,
                    'source_doc_type' => 'MIR',
                    'item_id' => $md->item_id,
                    'item_type' => $md->item_type,
                    'receive_qty' => $receive_qty,
                    'unit_value' => $unit_value,
                    'total_value' => $total_value,
                    'bc_type_code' => isset($header->bc_type_code) ? $header->bc_type_code : null,
                    'bc_number' => isset($header->bc_number) ? $header->bc_number : null,
                    'bc_date' => isset($header->bc_date) ? $header->bc_date : null
                ];

                if ($this->Warehouse_in_model->insert_item($data)) {
                    $total_loaded++;
                }
            }
        }

        $this->db->trans_complete();

        header('Content-Type: application/json');
        if ($total_loaded > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Berhasil auto-load ' . $total_loaded . ' item(s) dari ' . count($mir_ids) . ' MIR',
                'total_loaded' => $total_loaded,
                'error_mirs' => $error_mirs
            ]);
        } else {
            echo json_encode([
                'status' => 'warning',
                'message' => 'Tidak ada item yang berhasil di-load',
                'error_mirs' => $error_mirs
            ]);
        }
    }

    /**
     * Auto-load all items from a MIR when base_on is MIR
     * Called after GRN header is created with base_on = 'MIR'
     */
    public function auto_load_mir_items($grn_id, $mir_id) {
        if (!check_access('warehouse_in', 'edit')) {
            show_error('Anda tidak memiliki izin.', 403);
        }
        if (empty($grn_id) || !is_numeric($grn_id) || empty($mir_id) || !is_numeric($mir_id)) {
            $this->session->set_flashdata('error', 'Invalid parameters: grn_id=' . $grn_id . ', mir_id=' . $mir_id);
            redirect('warehouse_in/detail/' . $grn_id);
            return;
        }

        // DEBUG: Log the received parameters
        log_message('debug', '[auto_load_mir_items] Called with grn_id=' . $grn_id . ', mir_id=' . $mir_id);

        $header = $this->Warehouse_in_model->get_header_by_id($grn_id);
        if (!$header || $header->status != 'Draft' || $header->base_on != 'MIR') {
            $this->session->set_flashdata('error', 'GRN tidak valid untuk auto-load MIR items.');
            redirect('warehouse_in/detail/' . $grn_id);
            return;
        }

        // Check if MIR exists
        $mir_header = $this->Mir_model->get_header_by_id($mir_id);
        if (!$mir_header) {
            $this->session->set_flashdata('error', 'MIR tidak ditemukan.');
            redirect('warehouse_in/detail/' . $grn_id);
            return;
        }

        // Fetch ALL items from the MIR first (without qty filter) to check if items exist at all
        // Using mir_details table (same as get_available_items method)
        // Use total_qty which includes allowance (qty + allowance)
        $all_mir_items = $this->db
            ->select('md.id as mir_detail_id, md.item_id, md.item_type, md.qty, md.allowance, md.total_qty, md.unit_value')
            ->from('mir_details md')
            ->where('md.mir_header_id', $mir_id)
            ->get()
            ->result();

        log_message('debug', '[Warehouse_in] Auto-load MIR ' . $mir_id . ': Found ' . count($all_mir_items) . ' total items in mir_details');

        // Check how many items have already been received
        $received_subquery = $this->db->select('source_doc_detail_id, SUM(receive_qty) as total_received')
                                        ->from('grn_details')
                                        ->where('source_doc_header_id', $mir_id)
                                        ->where('source_doc_type', 'MIR')
                                        ->group_by('source_doc_detail_id')
                                        ->get_compiled_select();
        
        $this->db->reset_query();
        
        // Filter items that still need to be received (same logic as get_available_items)
        $mir_details = [];
        foreach ($all_mir_items as $item) {
            // Get total already received for this detail
            $received_result = $this->db->select('SUM(receive_qty) as total_received')
                                        ->from('grn_details')
                                        ->where('source_doc_header_id', $mir_id)
                                        ->where('source_doc_type', 'MIR')
                                        ->where('source_doc_detail_id', $item->mir_detail_id)
                                        ->get()
                                        ->row();
            
            $total_received = $received_result->total_received ?? 0;
            $receive_qty = $item->total_qty - $total_received; // Use total_qty (includes allowance)
            
            if ($receive_qty > 0.001) {
                $item->remaining_qty = $receive_qty;
                $mir_details[] = $item;
            }
        }

        log_message('debug', '[Warehouse_in] Auto-load MIR ' . $mir_id . ': After filtering, ' . count($mir_details) . ' items need to be received');

        if (empty($mir_details)) {
            if (empty($all_mir_items)) {
                $this->session->set_flashdata('error', 'MIR tidak memiliki item yang diminta.');
            } else {
                $this->session->set_flashdata('warning', 'Semua item di MIR sudah diterima atau dikembalikan. Tidak ada yang perlu di-load.');
            }
            redirect('warehouse_in/detail/' . $grn_id);
            return;
        }

        // Get item names for GRN details
        $success_count = 0;
        foreach ($mir_details as $md) {
            $receive_qty = $md->remaining_qty;

            // Get unit_value from MIR
            $unit_value = (float)($md->unit_value ?? 0);
            $total_value = $receive_qty * $unit_value;

            // Get item name and code based on item_type (same logic as get_available_items)
            $item_code = '';
            $item_name = '';
            if ($md->item_type == 'material') {
                $item = $this->db->select('kode_material, nama_material')->from('materials')->where('id', $md->item_id)->get()->row();
                if ($item) {
                    $item_code = $item->kode_material ?? '';
                    $item_name = $item->nama_material ?? '';
                }
            } else if ($md->item_type == 'semifinished') {
                $item = $this->db->select('kode_barang, nama_barang')->from('semifinished_goods')->where('id', $md->item_id)->get()->row();
                if ($item) {
                    $item_code = $item->kode_barang ?? '';
                    $item_name = $item->nama_barang ?? '';
                }
            } else if ($md->item_type == 'machine') {
                $item = $this->db->select('kode_mesin, nama_mesin')->from('machines')->where('id', $md->item_id)->get()->row();
                if ($item) {
                    $item_code = $item->kode_mesin ?? '';
                    $item_name = $item->nama_mesin ?? '';
                }
            } else if ($md->item_type == 'inventory') {
                $item = $this->db->select('kode_barang, nama_barang')->from('inventories')->where('id', $md->item_id)->get()->row();
                if ($item) {
                    $item_code = $item->kode_barang ?? '';
                    $item_name = $item->nama_barang ?? '';
                }
            }

            $data = [
                'grn_header_id' => $grn_id,
                'source_doc_header_id' => $mir_id,
                'source_doc_detail_id' => $md->mir_detail_id,
                'source_doc_type' => 'MIR',
                'item_id' => $md->item_id,
                'item_type' => $md->item_type,
                'receive_qty' => $receive_qty,
                'unit_value' => $unit_value,
                'total_value' => $total_value,
                // Tambah BC info dari GRN header
                'bc_type_code' => isset($header->bc_type_code) ? $header->bc_type_code : null,
                'bc_number' => isset($header->bc_number) ? $header->bc_number : null,
                'bc_date' => isset($header->bc_date) ? $header->bc_date : null
            ];

            if ($this->Warehouse_in_model->insert_item($data)) {
                $success_count++;
                log_message('debug', '[Warehouse_in] Inserted item: ' . $item_name . ' (qty: ' . $receive_qty . ')');
            }
        }

        if ($success_count > 0) {
            $this->session->set_flashdata('success', 'Auto-loaded ' . $success_count . ' item(s) dari MIR.');
        } else {
            $this->session->set_flashdata('warning', 'Tidak ada item yang berhasil di-load.');
        }

        redirect('warehouse_in/detail/' . $grn_id);
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
            // Cek log error untuk detail masalah
            $db_error = $this->db->error();
            $error_msg = 'Gagal menambahkan stok batch/ledger! Transaksi dibatalkan.';
            if (!empty($db_error['message'])) {
                $error_msg .= ' (DB: ' . $db_error['message'] . ')';
                log_message('error', 'GRN Confirm Error - DB Error: ' . print_r($db_error, true));
            }
            $this->session->set_flashdata('error', $error_msg);
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

        // Validasi input
        $this->form_validation->set_rules('source_doc_id', 'Source Document', 'required');
        $this->form_validation->set_rules('item_selector', 'Item', 'required');
        $this->form_validation->set_rules('receive_qty', 'Receive Qty', 'required|numeric|greater_than[0]');
        if ($source_doc_type == 'MIR') {
            $this->form_validation->set_rules('unit_value_mir', 'Unit Value', 'required|numeric|greater_than[0]');
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            redirect('warehouse_in/detail/' . $grn_id);
            return;
        }

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
            'unit_value' => $unit_value, 'total_value' => $total_value,
            // Tambah BC info dari GRN header (dapat di-override per detail jika perlu)
            'bc_type_code' => isset($header->bc_type_code) ? $header->bc_type_code : null,
            'bc_number' => isset($header->bc_number) ? $header->bc_number : null,
            'bc_date' => isset($header->bc_date) ? $header->bc_date : null
        ];
        
        // DEBUG: Log BC values  - FORCE WRITE TO FILE
        $debug_msg = '[ADD_ITEM] GRN=' . $grn_id . 
                     ' | Header Object: ' . json_encode((array)$header) .
                     ' | Data Array: ' . json_encode($data);
        file_put_contents('/tmp/mingbao_debug.log', $debug_msg . "\n", FILE_APPEND);
        
        if ($this->Warehouse_in_model->insert_item($data)) { $this->session->set_flashdata('success', 'Item diterima.'); }
        else { $this->session->set_flashdata('error', 'Gagal menerima item!'); }
        redirect('warehouse_in/detail/' . $grn_id);
    }

    public function delete_item($grn_id, $detail_id) {
        if (!check_access('warehouse_in', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        $header = $this->Warehouse_in_model->get_header_by_id($grn_id);
        if (!$header || $header->status != 'Draft') { redirect('warehouse_in/detail/' . $grn_id); }
        
        // For MIR items, all users with warehouse_in edit access can delete
        // (since they can at least edit unit_value)
        
        if ($this->Warehouse_in_model->delete_item($detail_id)) { $this->session->set_flashdata('success', 'Item dihapus.'); }
        else { $this->session->set_flashdata('error', 'Gagal menghapus item!'); }
        redirect('warehouse_in/detail/' . $grn_id);
    }

    /**
     * AJAX endpoint to update MIR item (both quantity and unit_value)
     * Only accessible by admin users for MIR items
     */
    public function update_mir_item() {
        if (!check_access('warehouse_in', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        // Check if user is admin for MIR item editing
        $user_role = $this->session->userdata('role_id');
        $role = $this->db->select('role_name')->from('roles')->where('id', $user_role)->get()->row();
        $is_admin = ($role && stripos($role->role_name, 'admin') !== false);
        
        if (!$is_admin) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Only admin can edit MIR item quantities']);
            return;
        }

        $detail_id = $this->input->post('detail_id');
        $new_qty = (float) $this->input->post('receive_qty');
        $new_unit_value = (float) $this->input->post('unit_value');

        if (empty($detail_id) || $new_qty < 0 || $new_unit_value < 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
            return;
        }

        // Get the detail to verify GRN is Draft and is MIR
        $detail = $this->db->select('gd.*, gh.status, gh.base_on')
            ->from('grn_details gd')
            ->join('grn_header gh', 'gh.id = gd.grn_header_id')
            ->where('gd.id', $detail_id)
            ->get()
            ->row();

        if (!$detail || $detail->status != 'Draft' || $detail->base_on != 'MIR') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Item not found or not editable']);
            return;
        }

        // Update quantity, unit_value and recalculate total_value
        $total_value = $new_qty * $new_unit_value;
        if ($this->db->update('grn_details', [
            'receive_qty' => $new_qty,
            'unit_value' => $new_unit_value,
            'total_value' => $total_value
        ], ['id' => $detail_id])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Item updated']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
        }
    }

    /**
     * AJAX endpoint to update MIR item unit_value only (for non-admin users)
     */
    public function update_mir_unit_value() {
        if (!check_access('warehouse_in', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $detail_id = $this->input->post('detail_id');
        $new_unit_value = (float) $this->input->post('unit_value');

        if (empty($detail_id) || $new_unit_value < 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
            return;
        }

        // Get the detail to verify GRN is Draft and is MIR
        $detail = $this->db->select('gd.*, gh.status, gh.base_on')
            ->from('grn_details gd')
            ->join('grn_header gh', 'gh.id = gd.grn_header_id')
            ->where('gd.id', $detail_id)
            ->get()
            ->row();

        if (!$detail || $detail->status != 'Draft' || $detail->base_on != 'MIR') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Item not found or not editable']);
            return;
        }

        // Update unit_value only and recalculate total_value (keep existing qty)
        $total_value = $detail->receive_qty * $new_unit_value;
        if ($this->db->update('grn_details', [
            'unit_value' => $new_unit_value,
            'total_value' => $total_value
        ], ['id' => $detail_id])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Unit value updated']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
        }
    }

    /**
     * AJAX endpoint to update item quantity
     * Only accessible by admin users for MIR items
     */
    public function update_item_qty() {
        if (!check_access('warehouse_in', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        // Check if user is admin for MIR item editing  
        $user_role = $this->session->userdata('role_id');
        $role = $this->db->select('role_name')->from('roles')->where('id', $user_role)->get()->row();
        $is_admin = ($role && stripos($role->role_name, 'admin') !== false);
        
        if (!$is_admin) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Only admin can edit MIR items']);
            return;
        }

        $detail_id = $this->input->post('detail_id');
        $new_qty = (float) $this->input->post('receive_qty');

        if (empty($detail_id) || $new_qty < 0) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid input']);
            return;
        }

        // Get the detail to verify GRN is Draft and is MIR
        $detail = $this->db->select('gd.*, gh.status, gh.base_on')
            ->from('grn_details gd')
            ->join('grn_header gh', 'gh.id = gd.grn_header_id')
            ->where('gd.id', $detail_id)
            ->get()
            ->row();

        if (!$detail || $detail->status != 'Draft' || $detail->base_on != 'MIR') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Item not found or not editable']);
            return;
        }

        // Update quantity and recalculate total_value
        $total_value = $new_qty * $detail->unit_value;
        if ($this->db->update('grn_details', [
            'receive_qty' => $new_qty,
            'total_value' => $total_value
        ], ['id' => $detail_id])) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Quantity updated']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Failed to update']);
        }
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