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
            'title'     => lang_line('page_warehouse_in_title'),
            'grns'      => $this->Warehouse_in_model->get_all(),
            'bc_types'  => $this->Commoncode_model->get_by_group('BC_TYPE'),
            'warehouse_types' => [
                'material' => 'Material',
                'semifinished' => 'Semifinished Goods'
            ]
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('warehouse_in/index', $data);
        $this->load->view('templates/footer');
    }

    // =====================================================
    // GOODS RETURN — Receive replacement from supplier
    // =====================================================

    /**
     * AJAX GET: List confirmed Warehouse Out (NG) documents for Goods Return selection.
     */
    public function get_available_goods_return() {
        header('Content-Type: application/json');
        if (!check_access('warehouse_in', 'view')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); return;
        }
        $search = $this->input->get('search');
        $list   = $this->Warehouse_in_model->get_available_goods_return(['search' => $search]);
        echo json_encode(['status' => 'success', 'data' => $list]);
    }

    /**
     * AJAX POST: Load items from a confirmed WO (NG) into a Draft GRN.
     * Body: grn_id, wh_out_id
     */
    public function auto_load_goods_return_items() {
        header('Content-Type: application/json');
        if (!check_access('warehouse_in', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']); return;
        }
        $grn_id    = $this->input->post('grn_id');
        $wh_out_id = $this->input->post('wh_out_id');
        if (empty($grn_id) || empty($wh_out_id)) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid']); return;
        }
        $grn_header = $this->Warehouse_in_model->get_header_by_id($grn_id);
        if (!$grn_header || $grn_header->status !== 'Draft') {
            echo json_encode(['status' => 'error', 'message' => 'GRN tidak dalam status Draft']); return;
        }
        $items = $this->Warehouse_in_model->get_goods_return_items($wh_out_id);
        if (empty($items)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada item dari WO ini']); return;
        }

        $loaded = 0; $errors = [];
        $first_supplier = null;
        foreach ($items as $item) {
            // Cek duplikat (satu WO detail hanya boleh dimuat sekali per GRN)
            $exists = $this->db->where('grn_header_id', $grn_id)
                ->where('source_doc_type', 'WO')
                ->where('source_doc_header_id', $wh_out_id)
                ->where('source_doc_detail_id', $item->wh_out_detail_id)
                ->count_all_results('grn_details');
            if ($exists > 0) { $errors[] = ($item->item_name ?? '-') . ': sudah dimuat'; continue; }

            $detail = [
                'grn_header_id'        => $grn_id,
                'source_doc_type'      => 'WO',
                'source_doc_header_id' => $wh_out_id,
                'source_doc_detail_id' => $item->wh_out_detail_id,
                'item_id'              => $item->item_id,
                'item_type'            => $item->item_type,
                'receive_qty'          => (float)$item->qty_returned,
                'unit_value'           => (float)($item->unit_value ?? 0),
                'total_value'          => (float)$item->qty_returned * (float)($item->unit_value ?? 0),
            ];
            if ($this->Warehouse_in_model->insert_item($detail)) {
                $loaded++;
                if (!$first_supplier && !empty($item->supplier_id)) $first_supplier = $item->supplier_id;
            } else {
                $errors[] = 'Gagal load: ' . ($item->item_name ?? '-');
            }
        }

        // Update supplier + goods_return_wh_out_id di GRN header
        if ($loaded > 0) {
            $upd = ['goods_return_wh_out_id' => $wh_out_id];
            if ($first_supplier && empty($grn_header->supplier_id)) $upd['supplier_id'] = $first_supplier;
            $this->db->where('id', $grn_id)->update('grn_header', $upd);
        }

        echo json_encode([
            'status'       => $loaded > 0 ? 'success' : 'error',
            'message'      => $loaded . ' item berhasil dimuat dari Goods Return' . (empty($errors) ? '' : ' (' . count($errors) . ' gagal)'),
            'total_loaded' => $loaded,
            'errors'       => $errors
        ]);
    }

    // =====================================================

    public function add_header()
{
    // Atur rules validasi Anda di sini
    $this->form_validation->set_rules('grn_date', 'Tanggal', 'required');
    $this->form_validation->set_rules('base_on', 'Dasar Penerimaan', 'required');
    // warehouse_type hanya wajib untuk PO/MIR, tidak untuk Goods Return
    $base_on_val = $this->input->post('base_on');
    if ($base_on_val !== 'Goods Return') {
        $this->form_validation->set_rules('warehouse_type', 'Tipe Gudang', 'required');
    }
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
            'title'   => lang_line('title_wi_detail'),
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
     * Get available POs for auto-load (Confirmed or Partially Received status)
     * AJAX endpoint for PO selection modal
     */
    /**
     * AJAX: daftar PO untuk modal "Pilih PO", LENGKAP dengan status tiap baris.
     *
     * User 2026-09-14: modal lama cuma menampilkan nomor PO & jumlah item,
     * tidak kelihatan mana yang sudah diterima, tipe barangnya apa, dan tidak
     * bisa memilih item/qty tertentu padahal penerimaan sering partial.
     *
     * Tiap PO dikembalikan beserta SEMUA barisnya (termasuk barang inventory/
     * mesin yang diterima di modul lain, ditandai tidak bisa dipilih di sini),
     * supaya user melihat gambaran PO utuh. Perhitungan qty ada di
     * Warehouse_in_model::get_po_lines_with_receipt().
     */
    public function get_available_po_numbers() {
        header('Content-Type: application/json');
        if (!check_access('warehouse_in', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $grn_id      = (int) $this->input->get('grn_id');
        $search_term = trim((string) $this->input->get('q'));

        $header = $grn_id > 0 ? $this->Warehouse_in_model->get_header_by_id($grn_id) : null;
        if (!$header || $header->base_on != 'PO') {
            echo json_encode(['status' => 'error', 'message' => 'GRN tidak valid atau bukan berbasis PO']);
            return;
        }

        // PO yang sudah disetujui & punya MINIMAL satu baris material/semi-finished.
        $this->db->reset_query();
        $this->db->select('ph.id AS po_id, ph.po_number, ph.po_date, ph.status, ph.currency_code, s.nama_supplier AS supplier_name', false)
            ->from('po_header ph')
            ->join('suppliers s', 's.id = ph.supplier_id', 'left')
            ->where_in('ph.status', ['Confirmed', 'Partially Received'])
            ->where("EXISTS (SELECT 1 FROM po_details x WHERE x.po_header_id = ph.id AND x.item_type IN ('material','semifinished'))", null, false);
        if ($search_term !== '') {
            $this->db->group_start()
                ->like('ph.po_number', $search_term)
                ->or_like('s.nama_supplier', $search_term)
                ->group_end();
        }
        $pos = $this->db->order_by('ph.po_date', 'DESC')->limit(100)->get()->result();
        if (empty($pos)) {
            echo json_encode(['status' => 'success', 'data' => []]);
            return;
        }

        $lines = $this->Warehouse_in_model->get_po_lines_with_receipt(array_column($pos, 'po_id'), $grn_id);
        $by_po = [];
        foreach ($lines as $l) { $by_po[(int) $l->po_header_id][] = $l; }

        $results = [];
        foreach ($pos as $po) {
            $po_lines = $by_po[(int) $po->po_id] ?? [];
            $mat_qty = 0; $mat_received = 0; $material_lines = 0; $receivable_lines = 0; $other_lines = 0;
            $out_lines = [];
            foreach ($po_lines as $l) {
                if ($l->is_material) {
                    $material_lines++;
                    $mat_qty      += $l->qty_po;
                    $mat_received += $l->qty_received;
                    if ($l->receivable) $receivable_lines++;
                } else {
                    $other_lines++;
                }
                $out_lines[] = [
                    'po_detail_id'        => (int) $l->po_detail_id,
                    'item_code'           => $l->item_code,
                    'item_name'           => $l->item_name,
                    'unit'                => $l->unit,
                    'item_type'           => $l->item_type,
                    'unit_price'          => (float) $l->local_price,
                    'qty_po'              => $l->qty_po,
                    'qty_received'        => $l->qty_received,
                    'qty_draft_other'     => $l->qty_draft_other,
                    'qty_this_grn'        => $l->qty_this_grn,
                    'qty_remaining'       => max(0, $l->qty_remaining),
                    'draft_other_numbers' => $l->draft_other_numbers,
                    'receivable'          => $l->receivable,
                    'status_key'          => $l->status_key,
                ];
            }
            $results[] = [
                'po_id'            => (int) $po->po_id,
                'po_number'        => $po->po_number,
                'po_date'          => $po->po_date,
                'po_status'        => $po->status,
                'currency'         => $po->currency_code,
                'supplier_name'    => $po->supplier_name,
                'total_lines'      => count($po_lines),
                'material_lines'   => $material_lines,
                'other_lines'      => $other_lines,
                'receivable_lines' => $receivable_lines,
                // Progres penerimaan material dalam persen qty, hanya dari GRN Completed.
                'progress'         => $mat_qty > 0 ? round($mat_received / $mat_qty * 100) : 0,
                'fully_received'   => $receivable_lines === 0,
                'lines'            => $out_lines,
            ];
        }

        echo json_encode(['status' => 'success', 'data' => $results]);
    }

    /**
     * AJAX: muat item PO TERPILIH dengan qty tertentu ke GRN Draft.
     *
     * User 2026-09-14: "saat pilih PO-nya juga pilih itemnya, karena ada kondisi
     * penerimaan partial item dan qty". Input: grn_id + items (JSON)
     * [{po_detail_id, qty}, ...].
     *
     * SEMUA baris divalidasi dulu, baru disimpan. Kalau ada satu saja yang
     * salah, tidak ada yang tersimpan -- GRN tidak pernah tertinggal dalam
     * keadaan setengah termuat. Batas qty dihitung ulang di server, angka dari
     * browser tidak dipercaya.
     *
     * Kalau baris PO yang sama sudah ada di GRN ini, qty-nya DITAMBAHKAN ke
     * baris itu, bukan dibuat baris kembar.
     */
    public function load_selected_po_items() {
        header('Content-Type: application/json');
        if (!check_access('warehouse_in', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $grn_id = (int) $this->input->post('grn_id');
        $items  = json_decode((string) $this->input->post('items'), true);

        $header = $grn_id > 0 ? $this->Warehouse_in_model->get_header_by_id($grn_id) : null;
        if (!$header || $header->status != 'Draft' || $header->base_on != 'PO') {
            echo json_encode(['status' => 'error', 'message' => 'GRN harus berstatus Draft dan berbasis PO.']);
            return;
        }
        if (empty($items) || !is_array($items)) {
            echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu item.']);
            return;
        }

        // Kumpulkan & rapikan permintaan. Baris PO yang sama dijumlahkan.
        $wanted = [];
        foreach ($items as $it) {
            $pdid = (int) ($it['po_detail_id'] ?? 0);
            $qty  = round((float) ($it['qty'] ?? 0), 2);
            if ($pdid <= 0) continue;
            $wanted[$pdid] = ($wanted[$pdid] ?? 0) + $qty;
        }
        if (empty($wanted)) {
            echo json_encode(['status' => 'error', 'message' => 'Pilih minimal satu item.']);
            return;
        }

        // PO pemilik baris-baris itu, lalu status penerimaan terkininya.
        $po_rows = $this->db->select('pd.id, pd.po_header_id, ph.status AS po_status, ph.supplier_id, ph.po_number')
            ->from('po_details pd')->join('po_header ph', 'ph.id = pd.po_header_id')
            ->where_in('pd.id', array_keys($wanted))->get()->result();
        $po_of = [];
        foreach ($po_rows as $r) { $po_of[(int) $r->id] = $r; }

        $lines = $this->Warehouse_in_model->get_po_lines_with_receipt(
            array_unique(array_map(function ($r) { return (int) $r->po_header_id; }, $po_rows)), $grn_id);
        $line_of = [];
        foreach ($lines as $l) { $line_of[(int) $l->po_detail_id] = $l; }

        // ---- Tahap 1: validasi SEMUA baris ----
        $errors = [];
        foreach ($wanted as $pdid => $qty) {
            $l  = $line_of[$pdid] ?? null;
            $po = $po_of[$pdid] ?? null;
            $name = ($l && $l->item_name) ? (($l->item_code ? $l->item_code . ' - ' : '') . $l->item_name) : ('baris PO #' . $pdid);
            if (!$l || !$po) { $errors[] = "$name: baris PO tidak ditemukan."; continue; }
            if (!in_array($po->po_status, ['Confirmed', 'Partially Received'], true)) {
                $errors[] = "$name: PO {$po->po_number} berstatus {$po->po_status}, belum bisa diterima."; continue;
            }
            if (!$l->is_material) {
                $errors[] = "$name: barang tipe {$l->item_type} diterima lewat Receiving Item Form, bukan di sini."; continue;
            }
            if ($qty <= 0) { $errors[] = "$name: qty harus lebih dari 0."; continue; }
            if ($qty > $l->qty_remaining + 0.0001) {
                $errors[] = "$name: qty " . (0 + $qty) . " melebihi sisa yang boleh diterima (" . (0 + max(0, $l->qty_remaining)) . ").";
            }
        }
        if (!empty($errors)) {
            echo json_encode(['status' => 'error', 'message' => 'Tidak ada item yang dimuat.', 'errors' => $errors]);
            return;
        }

        // ---- Tahap 2: simpan dalam satu transaksi ----
        $this->db->trans_start();
        $added = 0; $merged = 0; $supplier = null; $suppliers = [];
        foreach ($wanted as $pdid => $qty) {
            $l = $line_of[$pdid]; $po = $po_of[$pdid];
            $suppliers[(int) $po->supplier_id] = true;
            if (!$supplier && !empty($po->supplier_id)) $supplier = (int) $po->supplier_id;

            $unit_value = (float) $l->local_price;
            $existing = $this->db->select('id, receive_qty')->from('grn_details')
                ->where(['grn_header_id' => $grn_id, 'source_doc_type' => 'PO', 'source_doc_detail_id' => $pdid])
                ->limit(1)->get()->row();

            if ($existing) {
                $new_qty = round((float) $existing->receive_qty + $qty, 2);
                $this->db->update('grn_details',
                    ['receive_qty' => $new_qty, 'total_value' => round($new_qty * $unit_value, 2)],
                    ['id' => $existing->id]);
                $merged++;
            } else {
                $this->Warehouse_in_model->insert_item([
                    'grn_header_id'        => $grn_id,
                    'source_doc_header_id' => (int) $l->po_header_id,
                    'source_doc_detail_id' => $pdid,
                    'source_doc_type'      => 'PO',
                    'item_id'              => (int) $l->item_id,
                    'item_type'            => $l->item_type,
                    'receive_qty'          => $qty,
                    'unit_value'           => $unit_value,
                    'total_value'          => round($qty * $unit_value, 2),
                    'bc_type_code'         => $header->bc_type_code ?? null,
                    'bc_number'            => $header->bc_number ?? null,
                    'bc_date'              => $header->bc_date ?? null,
                ]);
                $added++;
            }
        }
        // Supplier GRN diisi dari PO kalau masih kosong (dipakai AP Invoice).
        if ($supplier && empty($header->supplier_id)) {
            $this->Warehouse_in_model->update_header($grn_id, ['supplier_id' => $supplier]);
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database. Tidak ada item yang dimuat.']);
            return;
        }

        $msg = "$added item baru dimuat" . ($merged ? ", $merged item digabung ke baris yang sudah ada" : '') . '.';
        if (count($suppliers) > 1) {
            $msg .= ' Perhatian: item berasal dari lebih dari satu supplier.';
        }
        echo json_encode(['status' => 'success', 'message' => $msg, 'added' => $added, 'merged' => $merged]);
    }

    /**
     * Validasi & simpan perubahan satu baris item PO di GRN Draft.
     * Dipakai update_po_item() (modal edit baru) DAN update_po_item_qty()
     * (endpoint lama) -- supaya endpoint lama tidak jadi celah penerimaan
     * berlebih. Versi lamanya menerima qty berapa pun tanpa dicek ke PO.
     *
     * @param array|null $bc ['bc_type_code','bc_number','bc_date'] atau null = tidak diubah
     * @return array ['status' => 'success'|'error', 'message' => ..., ...]
     */
    private function _save_po_detail($detail_id, $new_qty, $bc = null) {
        $detail_id = (int) $detail_id;
        $new_qty   = round((float) $new_qty, 2);

        $d = $this->db->select('gd.*, gh.status AS grn_status, gh.base_on')
            ->from('grn_details gd')->join('grn_header gh', 'gh.id = gd.grn_header_id')
            ->where('gd.id', $detail_id)->get()->row();
        if (!$d || $d->grn_status != 'Draft' || $d->base_on == 'MIR' || $d->source_doc_type != 'PO') {
            return ['status' => 'error', 'message' => 'Item tidak ditemukan atau GRN sudah tidak Draft.'];
        }
        if ($new_qty <= 0) {
            return ['status' => 'error', 'message' => 'Qty harus lebih dari 0. Untuk membatalkan item, gunakan tombol hapus.'];
        }

        $lines = $this->Warehouse_in_model->get_po_lines_with_receipt([(int) $d->source_doc_header_id], (int) $d->grn_header_id);
        $line = null;
        foreach ($lines as $l) { if ((int) $l->po_detail_id === (int) $d->source_doc_detail_id) { $line = $l; break; } }
        if (!$line) {
            return ['status' => 'error', 'message' => 'Baris PO sumber tidak ditemukan.'];
        }

        // Sisa sudah memotong qty baris ini sendiri -> kembalikan dulu.
        $max = round($line->qty_remaining + (float) $d->receive_qty, 4);
        if ($new_qty > $max + 0.0001) {
            return ['status' => 'error', 'message' => 'Qty melebihi batas. Maksimum yang boleh diterima untuk baris ini: ' . (0 + $max) . '.', 'max' => $max];
        }

        $upd = ['receive_qty' => $new_qty, 'total_value' => round($new_qty * (float) $d->unit_value, 2)];
        if (is_array($bc)) {
            $upd['bc_type_code'] = ($bc['bc_type_code'] ?? '') !== '' ? $bc['bc_type_code'] : null;
            $upd['bc_number']    = ($bc['bc_number'] ?? '')    !== '' ? $bc['bc_number']    : null;
            $upd['bc_date']      = ($bc['bc_date'] ?? '')      !== '' ? $bc['bc_date']      : null;
        }
        if (!$this->db->update('grn_details', $upd, ['id' => $detail_id])) {
            return ['status' => 'error', 'message' => 'Gagal menyimpan perubahan.'];
        }
        return ['status' => 'success', 'message' => 'Item diperbarui.', 'receive_qty' => $new_qty, 'total_value' => $upd['total_value'], 'max' => $max];
    }

    /**
     * AJAX: data lengkap satu baris untuk modal Edit Item (qty PO, sudah
     * diterima, tertahan di draft lain, batas maksimum, data BC).
     */
    public function get_po_item_detail() {
        header('Content-Type: application/json');
        if (!check_access('warehouse_in', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        $detail_id = (int) $this->input->get('detail_id');
        $d = $this->db->select('gd.*, gh.status AS grn_status, gh.base_on, ph.po_number')
            ->from('grn_details gd')->join('grn_header gh', 'gh.id = gd.grn_header_id')
            ->join('po_header ph', 'ph.id = gd.source_doc_header_id', 'left')
            ->where('gd.id', $detail_id)->get()->row();
        if (!$d || $d->source_doc_type != 'PO') {
            echo json_encode(['status' => 'error', 'message' => 'Item tidak ditemukan.']);
            return;
        }
        $line = null;
        foreach ($this->Warehouse_in_model->get_po_lines_with_receipt([(int) $d->source_doc_header_id], (int) $d->grn_header_id) as $l) {
            if ((int) $l->po_detail_id === (int) $d->source_doc_detail_id) { $line = $l; break; }
        }
        if (!$line) {
            echo json_encode(['status' => 'error', 'message' => 'Baris PO sumber tidak ditemukan.']);
            return;
        }
        echo json_encode(['status' => 'success', 'data' => [
            'detail_id'           => (int) $d->id,
            'po_number'           => $d->po_number,
            'item_code'           => $line->item_code,
            'item_name'           => $line->item_name,
            'unit'                => $line->unit,
            'item_type'           => $line->item_type,
            'qty_po'              => $line->qty_po,
            'qty_received'        => $line->qty_received,
            'qty_draft_other'     => $line->qty_draft_other,
            'draft_other_numbers' => $line->draft_other_numbers,
            'receive_qty'         => (float) $d->receive_qty,
            'max_qty'             => round($line->qty_remaining + (float) $d->receive_qty, 4),
            'unit_value'          => (float) $d->unit_value,
            'bc_type_code'        => $d->bc_type_code,
            'bc_number'           => $d->bc_number,
            'bc_date'             => $d->bc_date,
        ]]);
    }

    /** AJAX: simpan modal Edit Item -- qty + data BC per baris. */
    public function update_po_item() {
        header('Content-Type: application/json');
        if (!check_access('warehouse_in', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        echo json_encode($this->_save_po_detail(
            $this->input->post('detail_id'),
            $this->input->post('receive_qty'),
            [
                'bc_type_code' => trim((string) $this->input->post('bc_type_code')),
                'bc_number'    => trim((string) $this->input->post('bc_number')),
                'bc_date'      => trim((string) $this->input->post('bc_date')),
            ]
        ));
    }

    /**
     * @deprecated 2026-09-14 -- modal Pilih PO sekarang memanggil
     * load_selected_po_items() yang bisa memilih item & qty tertentu.
     * Method ini memuat SEMUA sisa item sekaligus; dibiarkan supaya halaman
     * lama yang masih ter-cache tidak rusak. Tetap aman dari penerimaan
     * berlebih karena sisanya dihitung dari seluruh grn_details.
     */
    public function auto_load_multiple_po_items() {
        if (!check_access('warehouse_in', 'edit')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }

        $grn_id = $this->input->post('grn_id');
        $po_ids = $this->input->post('po_ids'); // Array of PO IDs

        if (empty($grn_id) || !is_numeric($grn_id) || empty($po_ids) || !is_array($po_ids)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            return;
        }

        $header = $this->Warehouse_in_model->get_header_by_id($grn_id);
        if (!$header || $header->status != 'Draft' || $header->base_on != 'PO') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'GRN tidak valid untuk auto-load PO items']);
            return;
        }

        // Start transaction
        $this->db->trans_start();

        $total_loaded = 0;
        $error_pos = [];
        $first_supplier = null;

        foreach ($po_ids as $po_id) {
            if (!is_numeric($po_id)) continue;

            $po_header = $this->Po_model->get_po_header_by_id($po_id);
            if (!$po_header) {
                $error_pos[] = 'PO ID ' . $po_id . ' tidak ditemukan';
                continue;
            }

            // Ambil supplier dari PO pertama yang punya (untuk diisi ke GRN header)
            if (!$first_supplier && !empty($po_header->supplier_id)) {
                $first_supplier = $po_header->supplier_id;
            }

            // Get items from this PO
            $all_po_items = $this->db
                ->select('pd.id as po_detail_id, pd.item_id, pd.item_type, pd.qty, pd.local_price')
                ->from('po_details pd')
                ->where('pd.po_header_id', $po_id)
                ->get()
                ->result();

            // Filter items that still need to be received
            $po_details = [];
            foreach ($all_po_items as $item) {
                $received_result = $this->db->select('SUM(receive_qty) as total_received')
                                            ->from('grn_details')
                                            ->where('source_doc_header_id', $po_id)
                                            ->where('source_doc_type', 'PO')
                                            ->where('source_doc_detail_id', $item->po_detail_id)
                                            ->get()
                                            ->row();

                $total_received = $received_result->total_received ?? 0;
                $receive_qty = $item->qty - $total_received;

                if ($receive_qty > 0.001) {
                    $item->remaining_qty = $receive_qty;
                    $po_details[] = $item;
                }
            }

            if (empty($po_details)) continue;

            // Load items from this PO
            foreach ($po_details as $pd) {
                $receive_qty = $pd->remaining_qty;
                $unit_value = (float)($pd->local_price ?? 0);
                $total_value = $receive_qty * $unit_value;

                $data = [
                    'grn_header_id' => $grn_id,
                    'source_doc_header_id' => $po_id,
                    'source_doc_detail_id' => $pd->po_detail_id,
                    'source_doc_type' => 'PO',
                    'item_id' => $pd->item_id,
                    'item_type' => $pd->item_type,
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

        // Isi supplier_id GRN header dari PO (jika belum terisi) supaya GRN muncul di AP Invoice
        if ($total_loaded > 0 && $first_supplier && empty($header->supplier_id)) {
            $this->Warehouse_in_model->update_header($grn_id, ['supplier_id' => $first_supplier]);
        }

        $this->db->trans_complete();

        header('Content-Type: application/json');
        if ($total_loaded > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Berhasil auto-load ' . $total_loaded . ' item(s) dari ' . count($po_ids) . ' PO',
                'total_loaded' => $total_loaded,
                'error_pos' => $error_pos
            ]);
        } else {
            echo json_encode([
                'status' => 'warning',
                'message' => 'Tidak ada item yang berhasil di-load',
                'error_pos' => $error_pos
            ]);
        }
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
        $first_supplier = null;

        foreach ($mir_ids as $mir_id) {
            if (!is_numeric($mir_id)) continue;

            $mir_header = $this->Mir_model->get_header_by_id($mir_id);
            if (!$mir_header) {
                $error_mirs[] = 'MIR ID ' . $mir_id . ' tidak ditemukan';
                continue;
            }

            // Ambil supplier dari MIR pertama yang punya (untuk diisi ke GRN header)
            if (!$first_supplier && !empty($mir_header->supplier_id)) {
                $first_supplier = $mir_header->supplier_id;
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

        // Isi supplier_id GRN header dari MIR (jika belum terisi) supaya GRN muncul di AP Invoice
        if ($total_loaded > 0 && $first_supplier && empty($header->supplier_id)) {
            $this->Warehouse_in_model->update_header($grn_id, ['supplier_id' => $first_supplier]);
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
        if (!check_access('warehouse_in', 'confirm')) { show_error('Anda tidak memiliki izin.', 403); return; }
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

        $user_id = $this->session->userdata('user_id') ?? null;

        // Modul Warehouse_in hanya untuk Material & Semifinished — semua via QA.
        // Penerimaan Machine/Inventory ditangani modul Supplier General Item Receipt.
        if (!$this->Warehouse_in_model->update_status($id, 'Completed', $user_id)) {
            $this->session->set_flashdata('error', 'Gagal mengkonfirmasi GRN.');
            redirect('warehouse_in/detail/' . $id);
            return;
        }

        $this->load->model('Quality_assurance_model');
        $qa_header_id = $this->Quality_assurance_model->create_qa_header_from_grn($id, $user_id);

        if ($qa_header_id) {
            $this->session->set_flashdata('success',
                'GRN dikonfirmasi! Quality Assurance dibuat dan siap di-check. Barang belum masuk stok sampai QC selesai.');
            redirect('warehouse_in');
        } else {
            $this->session->set_flashdata('warning',
                'GRN dikonfirmasi tapi gagal membuat QA header. Hubungi admin untuk investigate.');
            redirect('warehouse_in/detail/' . $id);
        }
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

        // ===== NEW: Check QA Status =====
        // Sebelum unconfirm, check apakah QA ada dan sudah di-check
        $this->load->model('Quality_assurance_model');
        $qa_header = $this->Quality_assurance_model->get_qa_header_by_grn_id($id);
        
        if ($qa_header) {
            // QA ada, check apakah sudah punya items yang di-check
            $checked_items = $this->db->select('COUNT(*) as count')
                ->from('qa_details')
                ->where('qa_header_id', $qa_header->id)
                ->where('qa_pass_qty > 0 OR qa_reject_qty > 0', null, false)
                ->get()
                ->row();
            
            if ($checked_items && $checked_items->count > 0) {
                // Ada items yang sudah di-check, tidak boleh unconfirm
                $this->session->set_flashdata('error', 
                    'Tidak bisa batalkan GRN! Quality Assurance sudah memulai QC check. ' .
                    'Hubungi QA Supervisor untuk reset QA jika ingin batalkan GRN.');
                redirect('warehouse_in/detail/' . $id);
                return;
            }
            
            // QA ada tapi belum ada items yang di-check, boleh delete QA
            // Delete QA details
            $this->db->delete('qa_details', ['qa_header_id' => $qa_header->id]);
            
            // Delete QA header
            $this->db->delete('qa_header', ['id' => $qa_header->id]);
            
            // Clear QA reference dari GRN header
            $this->db->update('grn_header', 
                ['qa_header_id' => null, 'qa_status' => 'Not Started'],
                ['id' => $id]
            );
            
            log_message('info', 'GRN: Deleted QA header ' . $qa_header->qa_number . ' untuk GRN ' . $header->grn_number);
        }
        // ===== END NEW: QA Check =====

        // 1. Hapus Stok (Model sudah validasi stok terpakai)
        $remove_result = $this->Warehouse_in_model->remove_stock_from_ledger_and_batch($id);

        if ($remove_result === true) { // Jika sukses (true)
            // 2. Update Status Header
            if ($this->Warehouse_in_model->update_status($id, 'Draft')) {
                $this->session->set_flashdata('success', 'Konfirmasi GRN dibatalkan. Stok batch dan ledger telah dikembalikan/dihapus. QA juga dihapus.');
            } else {
                log_message('error', 'GRN: Kritis! Gagal update status ke Draft setelah stok dihapus. GRN ID: ' . $id);
                $this->session->set_flashdata('error', 'Stok berhasil dihapus, tapi gagal update status header!');
            }
        } elseif ($remove_result === false) { // Jika gagal (false)
             $this->session->set_flashdata('error', 'Gagal membatalkan GRN! Sebagian stok dari batch ini sudah terpakai di produksi.');
        } else {
             $this->session->set_flashdata('error', 'Gagal mengembalikan stok batch (Database Error).');
        }

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
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('grn', 'grn_header', 'grn_number');
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
        // Dulu: nama role mengandung kata "admin". Sejak Role Level
        // (2026-09-15) ada posisi "Admin/Clerk" di tiap departemen, jadi
        // cara itu akan menjadikan semua clerk admin. Pakai is_admin().
        $is_admin = is_admin();
        
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
        // Dulu: nama role mengandung kata "admin". Sejak Role Level
        // (2026-09-15) ada posisi "Admin/Clerk" di tiap departemen, jadi
        // cara itu akan menjadikan semua clerk admin. Pakai is_admin().
        $is_admin = is_admin();
        
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

    /**
     * AJAX endpoint to update item quantity for PO and non-MIR items
     * Updates only receive_qty, keeps unit_value fixed
     */
    /**
     * @deprecated 2026-09-14 -- diganti update_po_item(). Dipertahankan untuk
     * halaman lama yang masih ter-cache, tapi SEKARANG lewat validasi yang sama:
     * versi lamanya menerima qty berapa pun tanpa dicek ke sisa PO, sehingga
     * satu baris PO bisa diterima melebihi yang dipesan.
     */
    public function update_po_item_qty() {
        header('Content-Type: application/json');
        if (!check_access('warehouse_in', 'edit')) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            return;
        }
        echo json_encode($this->_save_po_detail($this->input->post('detail_id'), $this->input->post('receive_qty'), null));
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