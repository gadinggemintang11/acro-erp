<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Warehouse_out extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Warehouse_out_model', 'Warehouse_in_model', 'Stock_ledger_model', 'Commoncode_model']);
        $this->load->helper('url');
        $this->load->library('pagination');
    }

    public function index() {
        if (!check_access('warehouse_out', 'view')) { show_error('Akses ditolak', 403); }

        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date' => $this->input->get('end_date') ?: date('Y-m-t'),
            'status' => $this->input->get('status')
        ];

        $data = [
            'title' => 'Warehouse Out - Retur Supplier',
            'report_data' => $this->Warehouse_out_model->get_list($filters),
            'filters' => $filters,
            'bc_types' => array_column($this->Commoncode_model->get_by_group('BC_TYPE'), 'description', 'code')
        ];

        // Backfill missing out_number for drafts so index shows a reference instead of '-'
        foreach ($data['report_data'] as $row) {
            if (empty($row->out_number)) {
                $newnum = $this->Warehouse_out_model->generate_rtn_number();
                $this->Warehouse_out_model->update_header($row->id, ['out_number' => $newnum]);
                $row->out_number = $newnum;
            }
        }

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('warehouse_out/index', $data);
        $this->load->view('templates/footer');
    }

    public function create() {
        if (!check_access('warehouse_out', 'add')) { show_error('Akses ditolak', 403); }

        $data = [
            'title' => 'Buat Retur Supplier',
            'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE')
        ];

        // load full layout (header + sidebar + topbar) for consistent UI
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('warehouse_out/create', $data);
        $this->load->view('templates/footer');
    }

    public function save() {
        if (!check_access('warehouse_out', 'add')) { show_error('Akses ditolak', 403); }

        $post = $this->input->post();
        if (empty($post)) { show_error('No data', 400); }

        $header = [
            'out_number' => null, // assigned on confirm
            'base_on' => $post['base_on'] ?? 'MIR',
            'out_date' => $post['out_date'] ?? date('Y-m-d'),
            'reference_note' => $post['reference_note'] ?? null,
            'bc_type_code' => $post['bc_type_code'] ?? null,
            'bc_number' => $post['bc_number'] ?? null,
            'bc_date' => $post['bc_date'] ?? null,
            'status' => 'Draft',
            'created_by' => $this->session->userdata('user_id'),
            'created_at' => date('Y-m-d H:i:s')
        ];

        $header_id = $this->Warehouse_out_model->create_header($header);

        // If database schema doesn't have out_date column, remove it to avoid SQL errors
        // (This is a fallback for environments where DB migration hasn't been run yet)
        if (! $this->db->field_exists('out_date', 'warehouse_out_header')) {
            // recreate header without out_date
            $this->db->where('id', $header_id)->delete('warehouse_out_header');
            unset($header['out_date']);
            $header_id = $this->Warehouse_out_model->create_header($header);
        }

        // details expected as JSON string or array
        $details = $post['details'] ?? null;
        if ($details) {
            $decoded = is_string($details) ? json_decode($details, true) : $details;
            if (!empty($decoded)) {
                // basic sanity check: qty_return should not exceed qty_received
                foreach ($decoded as $d) {
                    $d['header_id'] = $header_id;
                    $qty_return = isset($d['qty_return']) ? (float)$d['qty_return'] : 0;
                    $qty_received = isset($d['qty_received']) ? (float)$d['qty_received'] : 0;
                    if ($qty_return > $qty_received) {
                        // rollback header and show error
                        $this->db->where('id', $header_id)->delete('warehouse_out_header');
                        $this->session->set_flashdata('error','Qty retur tidak boleh melebihi qty diterima untuk item '.$d['item_code']);
                        redirect('Warehouse_out/create');
                    }
                    $this->Warehouse_out_model->add_detail($d);
                }
            }
        }

        // redirect to detail page so user can pull GRN into the draft header
        redirect('Warehouse_out/detail/'.$header_id);
    }

    // show header detail and items; allows pulling GRN into Draft
    public function detail($id = null) {
        if (empty($id) || !check_access('warehouse_out', 'view')) { show_error('Akses ditolak', 403); }

        $header = $this->Warehouse_out_model->get_header($id);
        if (!$header) { show_error('Header tidak ditemukan', 404); }

        $details = $this->Warehouse_out_model->get_details($id);

        $data = [
            'title' => 'Detail Retur - ' . ($header->out_number ?? 'Draft'),
            'header' => $header,
            'details' => $details
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('warehouse_out/detail', $data);
        $this->load->view('templates/footer');
    }

    // AJAX: popup show GRN list to pick from
    public function popup_select_grn() {
        if (!$this->input->is_ajax_request()) { show_error('Direct access not allowed', 403); }
        if (!check_access('warehouse_out', 'add')) { echo json_encode(['success' => false, 'message' => 'Akses ditolak']); return; }

        $start = $this->input->get('start') ?: date('Y-m-01');
        $end = $this->input->get('end') ?: date('Y-m-t');
        $base_on = $this->input->get('base_on') ?: null;
        $supplier_id = $this->input->get('supplier_id') ?: null;
        $header_id = $this->input->get('header_id') ?: null;
        $filters = ['start_date'=>$start,'end_date'=>$end];
        if ($base_on) $filters['base_on'] = $base_on;
        if ($supplier_id) $filters['supplier_id'] = $supplier_id;
        $grns = $this->Warehouse_in_model->get_grn_list($filters);

        $html = $this->load->view('warehouse_out/modal_select_grn', ['grns'=>$grns, 'header_id'=>$header_id], true);
        echo json_encode(['success'=>true,'html'=>$html]);
    }

    // AJAX: load items for selected GRN ids
    public function load_grn_items() {
        if (!$this->input->is_ajax_request()) { show_error('Direct access not allowed', 403); }
        $grn_ids = $this->input->post('grn_ids');
        if (empty($grn_ids) || !is_array($grn_ids)) { echo json_encode(['success'=>false,'message'=>'No GRN selected']); return; }

        $items = $this->Warehouse_in_model->get_grn_items_by_ids($grn_ids);
        echo json_encode(['success'=>true,'data'=>$items]);
    }

    // AJAX: attach GRN items to an existing draft header
    public function pull_grn_items_to_header() {
        header('Content-Type: application/json');
        if (!$this->input->is_ajax_request()) { echo json_encode(['success'=>false,'message'=>'Direct access not allowed']); exit; }
        if (!check_access('warehouse_out', 'edit')) { echo json_encode(['success'=>false,'message'=>'Akses ditolak']); exit; }

        // Log raw payload for debugging
        $raw = $this->input->raw_input_stream;
        file_put_contents('/tmp/mingbao_debug.log', "[pull_grn_items_to_header] RAW_PAYLOAD: " . $raw . "\n", FILE_APPEND);

        $payload = json_decode($raw, true);
        $header_id = $payload['header_id'] ?? null;
        $items = $payload['items'] ?? [];

        if (empty($header_id) || empty($items) || !is_array($items)) {
            file_put_contents('/tmp/mingbao_debug.log', "[pull_grn_items_to_header] Invalid payload\n", FILE_APPEND);
            echo json_encode(['success'=>false,'message'=>'Invalid payload']); exit;
        }

        $header = $this->Warehouse_out_model->get_header($header_id);
        if (!$header) { file_put_contents('/tmp/mingbao_debug.log', "[pull_grn_items_to_header] Header not found id={$header_id}\n", FILE_APPEND); echo json_encode(['success'=>false,'message'=>'Header tidak ditemukan']); exit; }
        if ($header->status !== 'Draft') { file_put_contents('/tmp/mingbao_debug.log', "[pull_grn_items_to_header] Header not Draft id={$header_id} status={$header->status}\n", FILE_APPEND); echo json_encode(['success'=>false,'message'=>'Header harus berstatus Draft untuk menarik GRN']); exit; }

        $added = 0; $errors = [];
        $grn_header_ids = [];
        foreach ($items as $it) {
            // skip duplicate grn_detail for this header
            $grn_detail_id = $it['grn_detail_id'] ?? $it['id'] ?? null;
            if ($this->Warehouse_out_model->detail_exists($header_id, $grn_detail_id)) {
                $errors[] = 'Sudah ada item untuk grn_detail_id '.$grn_detail_id;
                continue;
            }

            $detail = [
                'header_id' => $header_id,
                // warehouse_out_details uses `grn_id` column historically; ensure we set that key so it matches table schema
                'grn_id' => $it['grn_header_id'] ?? $it['grn_id'] ?? null,
                'grn_detail_id' => $grn_detail_id,
                'item_id' => $it['item_id'] ?? null,
                'item_type' => $it['item_type'] ?? null,
                'item_code' => $it['item_code'] ?? ($it['kode'] ?? null),
                'item_name' => $it['item_name'] ?? ($it['nama_barang'] ?? null),
                'unit' => $it['unit'] ?? ($it['satuan'] ?? null),
                'qty_po' => isset($it['qty_po']) ? (float)$it['qty_po'] : 0,
                'qty_received' => isset($it['qty_received']) ? (float)$it['qty_received'] : (isset($it['receive_qty']) ? (float)$it['receive_qty'] : 0),
                // qty_return default 0 (user can enter later in detail edit)
                'qty_return' => isset($it['qty_return']) ? (float)$it['qty_return'] : 0,
                // unit value: prefer `unit_value` returned by Warehouse_in_model
                'unit_value_idr' => isset($it['unit_value']) ? (float)$it['unit_value'] : (float)($it['unit_value_idr'] ?? 0)
            ];

            $ok = $this->Warehouse_out_model->add_detail($detail);
            if ($ok) { 
                $added++; 
            } else { 
                $errors[] = 'Gagal tambah item '.($detail['item_code'] ?? ''); 
            }
            // collect grn header id from payload (detail uses 'grn_id' key)
            $grn_hdr = $it['grn_header_id'] ?? $it['grn_id'] ?? $detail['grn_id'] ?? null;
            if (!empty($grn_hdr)) $grn_header_ids[] = $grn_hdr;
        }

        // If we added items, try to update header's supplier/warehouse from first GRN
        if ($added > 0 && !empty($grn_header_ids)) {
            $first_grn_id = $grn_header_ids[0];
            $grn_header = $this->Warehouse_in_model->get_header_by_id($first_grn_id);
            $update_data = [];
            if ($grn_header) {
                // prefer storing supplier_id (FK) if the header has that column
                if ($this->db->field_exists('supplier_id', 'warehouse_out_header') && !empty($grn_header->supplier_id)) {
                    $update_data['supplier_id'] = $grn_header->supplier_id;
                }
                // keep legacy nama_supplier column if present as fallback/display
                if ($this->db->field_exists('nama_supplier', 'warehouse_out_header')) {
                    if (!empty($grn_header->nama_supplier)) {
                        $update_data['nama_supplier'] = $grn_header->nama_supplier;
                    } elseif (!empty($grn_header->supplier_id)) {
                        // if grn_header doesn't have nama_supplier but has supplier_id, lookup supplier name
                        $sup = $this->db->select('nama_supplier')->from('suppliers')->where('id', $grn_header->supplier_id)->limit(1)->get()->row();
                        if ($sup && !empty($sup->nama_supplier)) {
                            $update_data['nama_supplier'] = $sup->nama_supplier;
                        }
                    }
                }
                if ($this->db->field_exists('warehouse_type', 'warehouse_out_header') && !empty($grn_header->warehouse_type)) {
                    $update_data['warehouse_type'] = $grn_header->warehouse_type;
                }
                if (!empty($update_data)) {
                    $this->db->where('id', $header_id)->update('warehouse_out_header', $update_data);
                }
            }
        }

        file_put_contents('/tmp/mingbao_debug.log', "[pull_grn_items_to_header] added={$added} errors=" . json_encode($errors) . "\n", FILE_APPEND);
        // Decide success: if we actually added at least one item, consider success (partial errors returned in 'errors')
        // If nothing was added, return success=false so UI treats it as failure.
        $success = ($added > 0);
        // Clean any accidental output (php warnings/notices) so we return strict JSON
        while (ob_get_level() > 0) { ob_end_clean(); }
        header('Content-Type: application/json');
        echo json_encode(['success'=>$success,'added'=>$added,'errors'=>$errors]);
        exit;
    }

    // confirm and post stock ledger
    public function confirm($id) {
        if (!check_access('warehouse_out', 'edit')) { show_error('Akses ditolak', 403); }
        $result = $this->do_confirm($id);
        if ($result['success']) {
            $this->session->set_flashdata('success', $result['message']);
        } else {
            $this->session->set_flashdata('error', $result['message']);
        }
        redirect('Warehouse_out');
    }

    /**
     * Internal confirm logic: validate details, post stock ledger, update header
     * Returns array ['success'=>bool,'message'=>string]
     */
    private function do_confirm($id) {
        $header = $this->Warehouse_out_model->get_header($id);
        if (!$header) { return ['success'=>false,'message'=>'Header tidak ditemukan']; }
        if ($header->status === 'Confirmed') { return ['success'=>false,'message'=>'Sudah terkonfirmasi']; }

        $details = $this->Warehouse_out_model->get_details($id);
        if (empty($details)) { return ['success'=>false,'message'=>'Tidak ada detail untuk dikonfirmasi']; }

        $payload = [];
        foreach ($details as $d) { $payload[] = ['grn_detail_id' => $d->grn_detail_id, 'qty_return' => (float)$d->qty_return]; }
        $validation = $this->Warehouse_out_model->validate_return_details($payload);
        if (!$validation['valid']) { return ['success'=>false,'message'=>'Validasi retur gagal: ' . implode('; ', $validation['errors'])]; }

        $this->db->trans_start();
        $rtn = $this->Warehouse_out_model->generate_rtn_number();

        $update_data = ['out_number'=>$rtn,'status'=>'Confirmed','confirmed_by'=>$this->session->userdata('user_id'),'confirmed_at'=>date('Y-m-d H:i:s')];
        $update_ok = $this->Warehouse_out_model->update_header($id, $update_data);
        if (!$update_ok) { $this->db->trans_rollback(); return ['success'=>false,'message'=>'Gagal update header saat konfirmasi']; }

        $this->load->model('Stock_ledger_model');
        foreach ($details as $d) {
            $qty_out = (float)$d->qty_return;

            // Determine item_type. Details table historically may not store item_type,
            // so try: detail value -> latest stock_ledger row for item_id -> default 'material'
            $item_type = isset($d->item_type) && !empty($d->item_type) ? $d->item_type : null;
            if (empty($item_type)) {
                $row = $this->db->select('item_type')->from('stock_ledger')->where('item_id', $d->item_id)->order_by('id', 'DESC')->limit(1)->get()->row();
                $item_type = ($row && !empty($row->item_type)) ? $row->item_type : 'material';
            }

            $current_stock = (float)$this->Stock_ledger_model->get_last_balance($d->item_id, $item_type);

            // If the declared item_type has insufficient balance, attempt to detect
            // the real item_type from the latest stock_ledger row for this item_id
            // (this handles cases where details default to 'material' but ledger uses 'machine').
            $used_item_type = $item_type;
            if ($qty_out > 0 && $current_stock < $qty_out) {
                $ledger_row = $this->db->select('item_type,balance')->from('stock_ledger')->where('item_id', $d->item_id)->order_by('id', 'DESC')->limit(1)->get()->row();
                if ($ledger_row && !empty($ledger_row->item_type) && $ledger_row->item_type !== $item_type) {
                    $alt_balance = (float)$this->Stock_ledger_model->get_last_balance($d->item_id, $ledger_row->item_type);
                    if ($alt_balance >= $qty_out) {
                        // use alternative ledger item_type
                        $used_item_type = $ledger_row->item_type;
                        $current_stock = $alt_balance;
                    }
                }
            }

            if ($qty_out > 0 && $current_stock < $qty_out) {
                $this->db->trans_rollback();
                return ['success'=>false,'message'=>'Stok tidak mencukupi untuk item ' . ($d->item_code ?? $d->item_name) . ": tersedia " . number_format($current_stock,2) . ", dibutuhkan " . number_format($qty_out,2)];
            }

            // Prepare base insert data (we may create multiple inserts if consuming multiple batches)
            $base_insert = [
                'item_id' => $d->item_id,
                'item_type' => $used_item_type,
                'transaction_type' => 'RTN_SUPPLIER',
                'transaction_id' => $id,
                'source_doc_type' => 'GRN',
                    'source_doc_id' => $d->grn_header_id ?? null,
                'qty_in' => 0,
                // 'qty_out' set per-batch below
                'unit_value' => (float)($d->unit_value_idr ?? $d->unit_value ?? 0),
                'transaction_date' => date('Y-m-d H:i:s'),
                // created_by may not be a column in stock_ledger; it will be filtered by model if absent
                'created_by' => $this->session->userdata('user_id')
            ];
            if (!empty($d->bc_type_code)) { $base_insert['bc_type_code'] = $d->bc_type_code; }
            if (!empty($d->bc_number)) { $base_insert['bc_number'] = $d->bc_number; }
            if (!empty($d->bc_date)) { $base_insert['bc_date'] = $d->bc_date; }
            if (!empty($header->bc_type_code) && empty($base_insert['bc_type_code'])) { $base_insert['bc_type_code'] = $header->bc_type_code; }
            if (!empty($header->bc_number) && empty($base_insert['bc_number'])) { $base_insert['bc_number'] = $header->bc_number; }
            if (!empty($header->bc_date) && empty($base_insert['bc_date'])) { $base_insert['bc_date'] = $header->bc_date; }

            // If detail does not include grn_header_id but has grn_detail_id,
            // try to resolve the grn_header_id from grn_details so ledger.source_doc_id is set.
            if (empty($base_insert['source_doc_id']) && !empty($d->grn_detail_id)) {
                $grn_header = $this->db->select('grn_header_id')->from('grn_details')->where('id', $d->grn_detail_id)->limit(1)->get()->row();
                if ($grn_header && !empty($grn_header->grn_header_id)) {
                    $base_insert['source_doc_id'] = $grn_header->grn_header_id;
                }
            }

            $remaining = $qty_out;

            // Try to consume from stock_on_hand batches (FIFO) and create one ledger row per batch consumed
            $batches = $this->db->select('id, available_qty')->from('stock_on_hand')
                ->where('item_id', $d->item_id)
                ->where('item_type', $used_item_type)
                ->where('available_qty >', 0)
                ->order_by('id', 'ASC')
                ->get()->result();

            if (!empty($batches)) {
                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    $take = min((float)$batch->available_qty, $remaining);
                    if ($take <= 0) continue;

                    // decrement batch available_qty
                    $this->db->where('id', $batch->id)->set('available_qty', "available_qty - {$take}", false)->update('stock_on_hand');

                    $insert_data = $base_insert;
                    $insert_data['qty_out'] = $take;
                    $insert_data['stock_on_hand_id'] = $batch->id;

                    if (!$this->Stock_ledger_model->insert($insert_data)) {
                        $this->db->trans_rollback();
                        return ['success'=>false,'message'=>'Gagal insert stock ledger untuk item '.($d->item_code ?? $d->item_name)];
                    }

                    $remaining -= $take;
                }
            }

            // If still remaining (no batches or insufficient batch-level availability), insert remaining without stock_on_hand_id
            if ($remaining > 0) {
                $insert_data = $base_insert;
                $insert_data['qty_out'] = $remaining;
                // no stock_on_hand_id
                if (!$this->Stock_ledger_model->insert($insert_data)) {
                    $this->db->trans_rollback();
                    return ['success'=>false,'message'=>'Gagal insert stock ledger (sisa) untuk item '.($d->item_code ?? $d->item_name)];
                }
            }
        }

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) { return ['success'=>false,'message'=>'Transaksi konfirmasi gagal.']; }
        return ['success'=>true,'message'=>'Retur dikonfirmasi dengan nomor '.$rtn];
    }

    public function export_excel($id) {
        if (!check_access('warehouse_out', 'view')) { show_error('Akses ditolak', 403); }
        $header = $this->Warehouse_out_model->get_header($id);
        if (!$header) { show_error('Header tidak ditemukan', 404); }

        $details = $this->Warehouse_out_model->get_details($id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Retur_'.$header->out_number);

        $sheet->setCellValue('A1', 'Retur Supplier: '.$header->out_number);
        $sheet->setCellValue('A2', 'BC: '.($header->bc_type_code ?? '').' '.($header->bc_number ?? ''));

        $headers = ['No','Kode','Nama','Satuan','QTY PO/MIR','QTY DITERIMA','QTY RETUR'];
        $sheet->fromArray($headers,NULL,'A4');
        $row = 5; $no=1;
        foreach($details as $d) {
            $sheet->setCellValue('A'.$row,$no++);
            $sheet->setCellValue('B'.$row,$d->item_code);
            $sheet->setCellValue('C'.$row,$d->item_name);
            $sheet->setCellValue('D'.$row,$d->unit);
            $sheet->setCellValue('E'.$row, (float)$d->qty_po);
            $sheet->setCellValue('F'.$row, (float)$d->qty_received);
            $sheet->setCellValue('G'.$row, (float)$d->qty_return);
            $sheet->getStyle('E'.$row.':G'.$row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        foreach (range('A','G') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $writer = new Xlsx($spreadsheet);
        $filename = 'Retur_'.$header->out_number.'.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'"');
        $writer->save('php://output');
        exit;
    }

    public function export_pdf($id) {
        if (!check_access('warehouse_out', 'view')) { show_error('Akses ditolak', 403); }
        $header = $this->Warehouse_out_model->get_header($id);
        $details = $this->Warehouse_out_model->get_details($id);
        $data = ['header'=>$header,'details'=>$details];
        $html = $this->load->view('warehouse_out/pdf_detail', $data, true);
        $options = new Options(); $options->set('isHtml5ParserEnabled', true); $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','landscape');
        $dompdf->render();
        $dompdf->stream('Retur_'.$header->out_number.'.pdf',['Attachment'=>0]);
        exit;
    }

    public function delete_item($header_id = null, $detail_id = null) {
        if (!check_access('warehouse_out', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        if (empty($header_id) || empty($detail_id)) { redirect('warehouse_out'); }
        $header = $this->Warehouse_out_model->get_header($header_id);
        if (!$header || $header->status != 'Draft') { redirect('warehouse_out/detail/' . $header_id); }

        if ($this->Warehouse_out_model->delete_detail($detail_id)) {
            $this->session->set_flashdata('success', 'Item dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus item!');
        }
        redirect('warehouse_out/detail/' . $header_id);
    }

    // Delete header (only allowed when Draft and no details exist)
    public function delete_header($header_id = null) {
        if (!check_access('warehouse_out', 'delete')) { show_error('Anda tidak memiliki izin.', 403); }
        if (empty($header_id)) { redirect('warehouse_out'); }

        $header = $this->Warehouse_out_model->get_header($header_id);
        if (!$header) { $this->session->set_flashdata('error', 'Header tidak ditemukan.'); redirect('warehouse_out'); }

        if (strtolower($header->status) !== 'draft') {
            $this->session->set_flashdata('error', 'Hanya header dengan status Draft yang bisa dihapus.');
            redirect('warehouse_out');
        }

        $count = $this->Warehouse_out_model->get_details_count($header_id);
        if ($count > 0) {
            $this->session->set_flashdata('error', 'Header memiliki detail. Hapus semua detail terlebih dahulu sebelum menghapus header.');
            redirect('warehouse_out/detail/' . $header_id);
        }

        if ($this->Warehouse_out_model->delete_header($header_id)) {
            $this->session->set_flashdata('success', 'Header berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus header.');
        }
        redirect('warehouse_out');
    }

    // Submit a Draft header for approval (Draft -> Pending Approval)
    public function submit_for_approval($header_id = null) {
        if (!check_access('warehouse_out', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        if (empty($header_id)) { redirect('warehouse_out'); }

        $header = $this->Warehouse_out_model->get_header($header_id);
        if (!$header) { $this->session->set_flashdata('error', 'Header tidak ditemukan.'); redirect('warehouse_out'); }

        if (strtolower($header->status) !== 'draft') {
            $this->session->set_flashdata('error', 'Hanya header dengan status Draft yang dapat disubmit untuk approval.');
            redirect('warehouse_out/detail/' . $header_id);
        }

        $update = ['status' => 'Pending Approval'];
        if ($this->db->field_exists('submitted_by', 'warehouse_out_header')) {
            $update['submitted_by'] = $this->session->userdata('user_id');
        }
        if ($this->db->field_exists('submitted_at', 'warehouse_out_header')) {
            $update['submitted_at'] = date('Y-m-d H:i:s');
        }

        $this->db->where('id', $header_id)->update('warehouse_out_header', $update);
        $this->session->set_flashdata('success', 'Header berhasil disubmit untuk approval.');
        redirect('warehouse_out/detail/' . $header_id);
    }

    // Approve a header (Pending Approval -> Approved)
    public function approve($header_id = null) {
        // Debug: log approve attempts
        file_put_contents('/tmp/warehouse_out_approve_debug.log', "[approve] called header_id=".print_r($header_id, true)." POST=".json_encode($_POST)." user_id=".$this->session->userdata('user_id')."\n", FILE_APPEND);
        if (!check_access('warehouse_out', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        if (empty($header_id)) { redirect('warehouse_out'); }

        $header = $this->Warehouse_out_model->get_header($header_id);
        if (!$header) { $this->session->set_flashdata('error', 'Header tidak ditemukan.'); redirect('warehouse_out'); }

        if ($header->status !== 'Pending Approval') {
            $this->session->set_flashdata('error', 'Hanya header yang berstatus Pending Approval yang bisa di-approve.');
            redirect('warehouse_out/detail/' . $header_id);
        }

        $notes = $this->input->post('approval_notes') ?: null;
        $update = ['status' => 'Approved'];
        if ($this->db->field_exists('approved_by', 'warehouse_out_header')) { $update['approved_by'] = $this->session->userdata('user_id'); }
        if ($this->db->field_exists('approved_at', 'warehouse_out_header')) { $update['approved_at'] = date('Y-m-d H:i:s'); }
        if ($this->db->field_exists('approval_notes', 'warehouse_out_header') && $notes) { $update['approval_notes'] = $notes; }

        // Update to Approved first
        $this->db->where('id', $header_id)->update('warehouse_out_header', $update);

        // After approve, perform confirmation (posting) immediately
        file_put_contents('/tmp/warehouse_out_approve_debug.log', "[approve] running do_confirm for header_id={$header_id}\n", FILE_APPEND);
        $confirmResult = $this->do_confirm($header_id);
        if ($confirmResult['success']) {
            $this->session->set_flashdata('success', 'Header berhasil di-approve dan dikonfirmasi. ' . $confirmResult['message']);
            file_put_contents('/tmp/warehouse_out_approve_debug.log', "[approve] success: ".json_encode($confirmResult)."\n", FILE_APPEND);
            redirect('warehouse_out');
        } else {
            // rollback approval status back to Pending Approval so user can re-try or adjust
            $this->db->where('id', $header_id)->update('warehouse_out_header', ['status' => 'Pending Approval']);
            $this->session->set_flashdata('error', 'Approve gagal: ' . $confirmResult['message']);
            file_put_contents('/tmp/warehouse_out_approve_debug.log', "[approve] failed: ".json_encode($confirmResult)."\n", FILE_APPEND);
            redirect('warehouse_out/detail/' . $header_id);
        }
    }

    // Reject a header (Pending Approval -> Draft or Rejected)
    public function reject($header_id = null) {
        if (!check_access('warehouse_out', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        if (empty($header_id)) { redirect('warehouse_out'); }

        $header = $this->Warehouse_out_model->get_header($header_id);
        if (!$header) { $this->session->set_flashdata('error', 'Header tidak ditemukan.'); redirect('warehouse_out'); }

        if ($header->status !== 'Pending Approval') {
            $this->session->set_flashdata('error', 'Hanya header yang berstatus Pending Approval yang bisa di-reject.');
            redirect('warehouse_out/detail/' . $header_id);
        }

        $notes = $this->input->post('rejection_notes');
        $update = [];
        // Decide rejection outcome: back to Draft so user can edit
        $update['status'] = 'Draft';
        if ($this->db->field_exists('rejected_by', 'warehouse_out_header')) { $update['rejected_by'] = $this->session->userdata('user_id'); }
        if ($this->db->field_exists('rejected_at', 'warehouse_out_header')) { $update['rejected_at'] = date('Y-m-d H:i:s'); }
        if ($this->db->field_exists('rejection_notes', 'warehouse_out_header') && $notes) { $update['rejection_notes'] = $notes; }

        $this->db->where('id', $header_id)->update('warehouse_out_header', $update);
        $this->session->set_flashdata('success', 'Header telah direject dan dikembalikan ke Draft.');
        redirect('warehouse_out/detail/' . $header_id);
    }

    // AJAX: update a detail (qty_return) inline
    public function update_detail_ajax() {
        if (!$this->input->is_ajax_request()) { echo json_encode(['success'=>false,'message'=>'Direct access not allowed']); exit; }
        if (!check_access('warehouse_out', 'edit')) { echo json_encode(['success'=>false,'message'=>'Akses ditolak']); exit; }

        $detail_id = $this->input->post('detail_id');
        $qty_return = $this->input->post('qty_return');
        if (empty($detail_id)) { echo json_encode(['success'=>false,'message'=>'detail_id required']); exit; }

        $detail = $this->db->select('*')->from('warehouse_out_details')->where('id', $detail_id)->get()->row();
        if (!$detail) { echo json_encode(['success'=>false,'message'=>'Detail tidak ditemukan']); exit; }

        $header = $this->Warehouse_out_model->get_header($detail->header_id);
        if (!$header || strtolower($header->status) !== 'draft') { echo json_encode(['success'=>false,'message'=>'Header harus berstatus Draft untuk mengubah detail']); exit; }

        $qty_return = (float)$qty_return;

        // Validate qty_return does not exceed receive_qty minus already confirmed returns
        $grn_detail_id = $detail->grn_detail_id;
        $received_row = $this->db->select('receive_qty')->from('grn_details')->where('id', $grn_detail_id)->get()->row();
        $received = $received_row ? (float)$received_row->receive_qty : 0.0;
        $already_confirmed = $this->Warehouse_out_model->get_total_returned_for_grn_detail($grn_detail_id);
        $available = $received - $already_confirmed;
        if ($qty_return < 0) { echo json_encode(['success'=>false,'message'=>'Qty retur tidak boleh negatif']); exit; }
        if ($qty_return - $available > 0.0001) { echo json_encode(['success'=>false,'message'=>"Qty retur ({$qty_return}) melebihi tersedia ({$available})"]); exit; }

        // optionally accept unit value and currency from client
        $unit_value_input = $this->input->post('unit_value');
        $currency_input = $this->input->post('currency');

        $updateData = ['qty_return'=>$qty_return];
        if ($unit_value_input !== null && $unit_value_input !== '') {
            $unit_value_input = (float)$unit_value_input;
            // prefer storing into unit_value_idr if header/currency is IDR or if column exists
            if ($currency_input === 'IDR' && $this->db->field_exists('unit_value_idr', 'warehouse_out_details')) {
                $updateData['unit_value_idr'] = $unit_value_input;
            } elseif ($this->db->field_exists('unit_value', 'warehouse_out_details')) {
                $updateData['unit_value'] = $unit_value_input;
            } elseif ($this->db->field_exists('unit_value_idr', 'warehouse_out_details')) {
                // fallback
                $updateData['unit_value_idr'] = $unit_value_input;
            }
        }
        if ($currency_input !== null && $currency_input !== '') {
            if ($this->db->field_exists('currency_code', 'warehouse_out_details')) {
                $updateData['currency_code'] = $currency_input;
            }
            // also attempt to update header currency if column exists
            if ($this->db->field_exists('currency_code', 'warehouse_out_header')) {
                $this->db->where('id', $detail->header_id)->update('warehouse_out_header', ['currency_code' => $currency_input]);
            }
        }

        $update_ok = $this->Warehouse_out_model->update_detail($detail_id, $updateData);
        if ($update_ok === false) { echo json_encode(['success'=>false,'message'=>'Gagal menyimpan perubahan']); exit; }

        // determine unit_value to calculate totals (prefer updated value, else existing)
        $unit_value = 0;
        if (isset($updateData['unit_value_idr'])) $unit_value = (float)$updateData['unit_value_idr'];
        elseif (isset($updateData['unit_value'])) $unit_value = (float)$updateData['unit_value'];
        else {
            $unit_value = isset($detail->unit_value_idr) ? (float)$detail->unit_value_idr : (float)($detail->unit_value ?? 0);
        }
        $total = $unit_value * $qty_return;
        echo json_encode(['success'=>true,'detail_id'=>$detail_id,'qty_return'=>$qty_return,'total'=>$total]);
        exit;
    }

    // Unconfirm: rollback posted ledger entries for a confirmed retur
    public function unconfirm($header_id = null) {
        if (!check_access('warehouse_out', 'edit')) { show_error('Anda tidak memiliki izin.', 403); }
        if (empty($header_id)) { redirect('warehouse_out'); }

        $header = $this->Warehouse_out_model->get_header($header_id);
        if (!$header) { $this->session->set_flashdata('error', 'Header tidak ditemukan.'); redirect('warehouse_out'); }
        if ($header->status !== 'Confirmed') { $this->session->set_flashdata('error', 'Hanya header yang berstatus Confirmed yang bisa dibatalkan.'); redirect('warehouse_out/detail/' . $header_id); }

        $this->load->model('Stock_on_hand_model');

        $this->db->trans_start();

        // Find ledger rows created by this confirm
        $ledgers = $this->db->select('*')->from('stock_ledger')
            ->where('transaction_type', 'RTN_SUPPLIER')
            ->where('transaction_id', $header_id)
            ->get()->result();

        $affected_items = [];
        foreach ($ledgers as $l) {
            $item_id = $l->item_id;
            $item_type = $l->item_type;
            $qty_out = (float)$l->qty_out;
            // restore stock_on_hand if present
            if (!empty($l->stock_on_hand_id)) {
                $this->db->where('id', $l->stock_on_hand_id)->set('available_qty', 'available_qty + ' . $qty_out, FALSE)->update('stock_on_hand');
            }
            $affected_items[] = ['item_id' => $item_id, 'item_type' => $item_type];
        }

        // Delete the ledger rows for this transaction
        $this->db->where('transaction_type', 'RTN_SUPPLIER')->where('transaction_id', $header_id)->delete('stock_ledger');

        // Recalculate running balances for affected items
        if (!empty($affected_items)) {
            // unique items
            $unique = [];
            foreach ($affected_items as $ai) {
                $key = $ai['item_id'] . '|' . $ai['item_type'];
                if (isset($unique[$key])) continue;
                $unique[$key] = $ai;
            }
            foreach ($unique as $u) {
                $this->db->reset_query();
                $entries = $this->db->select('id, qty_in, qty_out')->from('stock_ledger')
                    ->where('item_id', $u['item_id'])->where('item_type', $u['item_type'])
                    ->order_by('id', 'ASC')->get()->result();
                $running = 0;
                foreach ($entries as $e) {
                    $running += ((float)$e->qty_in - (float)$e->qty_out);
                    $this->db->where('id', $e->id)->update('stock_ledger', ['balance' => $running]);
                }
            }
        }

        // revert header status to Approved (since it was Approved before Confirm)
        $this->db->where('id', $header_id)->update('warehouse_out_header', ['status' => 'Approved', 'confirmed_by' => NULL, 'confirmed_at' => NULL]);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Gagal membatalkan konfirmasi. Periksa log.');
        } else {
            $this->session->set_flashdata('success', 'Konfirmasi berhasil dibatalkan. Entri stock_ledger dihapus dan SOH dipulihkan.');
        }
        redirect('warehouse_out/detail/' . $header_id);
    }

}
