<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class Mir extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Mir_model', 'Commoncode_model', 'Sc_model', 'Supplier_model']);
        $this->load->helper('url');
        $this->load->library('session'); // Load session
        $this->load->library('user_agent'); // Load user agent untuk redirect back
    }

    public function index() {
        if (!check_access('mir', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }

        $data = [
            'title'         => 'Material Import Request (MIR)',
            'mirs'          => $this->Mir_model->get_all(),
            'suppliers'     => $this->Supplier_model->get_all(['status' => 'active', 'jenis' => 'Impor']),
            'payment_types' => $this->Commoncode_model->get_by_group('PAYMENT_TYPE'),
            'currencies'    => $this->Commoncode_model->get_by_group('CURRENCY')
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('mir/index', $data);
        $this->load->view('templates/footer');
    }

    public function add_header() {
        if (!check_access('mir', 'add')) {
            show_error('Anda tidak memiliki izin untuk menambah data.', 403);
        }

        $data = [
            'mir_number'        => $this->Mir_model->generate_mir_number(),
            'request_date'      => $this->input->post('request_date'),
            'supplier_id'       => $this->input->post('supplier_id'),
            'payment_type_code' => $this->input->post('payment_type_code'),
            'currency_code'     => $this->input->post('currency_code'),
            'remarks'           => $this->input->post('remarks'),
            'status'            => 'Draft',
            'requester_id'      => $this->session->userdata('user_id') ?? null
        ];

        $new_mir_id = $this->Mir_model->insert_header($data);

        if ($new_mir_id) {
            $this->session->set_flashdata('success', 'Header MIR berhasil dibuat. Silakan tambahkan item.');
            redirect('mir/detail/' . $new_mir_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat header MIR!');
            redirect('mir');
        }
    }
    
    // ✅ BARU: Fungsi update header
    public function update_header($id) {
        if (!check_access('mir', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        $header = $this->Mir_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('error', 'Header MIR tidak bisa diedit!');
             redirect('mir');
        }
        
        $data = [
            'request_date'      => $this->input->post('request_date'),
            'supplier_id'       => $this->input->post('supplier_id'),
            'payment_type_code' => $this->input->post('payment_type_code'),
            'currency_code'     => $this->input->post('currency_code'),
            'remarks'           => $this->input->post('remarks'),
        ];
        
        if ($this->Mir_model->update_header($id, $data)) {
            $this->session->set_flashdata('success', 'Header MIR berhasil diperbarui.');
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui header MIR.');
        }
        redirect('mir');
    }

    // ✅ BARU: Fungsi delete header
    public function delete_header($id) {
        if (!check_access('mir', 'delete')) {
            show_error('Anda tidak memiliki izin untuk menghapus data.', 403);
        }
        $header = $this->Mir_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('error', 'MIR tidak bisa dihapus!');
             redirect('mir');
        }

        if ($this->Mir_model->delete_header($id)) {
            $this->session->set_flashdata('success', 'MIR berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus MIR.');
        }
        redirect('mir');
    }

    public function detail($id) {
        if (!check_access('mir', 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }
        $header = $this->Mir_model->get_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'         => 'Detail Material Import Request',
            'header'        => $header,
            'details'       => $this->Mir_model->get_details_by_id($id),
            'open_contracts'=> $this->Sc_model->get_open_contracts(),
            'available_items' => $this->Mir_model->get_available_items_for_mir() // Semua item yang bisa ditambahkan
        ];
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('mir/detail', $data);
    }

    // ✅ BARU: Load BOM from Sales Contract
    public function load_bom_from_sc($mir_id) {
        if (!check_access('mir', 'edit')) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Izin ditolak']));
            return;
        }

        $sc_id = $this->input->post('sc_id');
        
        if (!$sc_id) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Sales Contract tidak valid']));
            return;
        }

        // Load model BOM jika belum
        $this->load->model('Bom_model');
        
        // Get SC details to get FG and quantity
        $sc_details = $this->Sc_model->get_items_by_contract($sc_id);
        
        log_message('debug', '[MIR Load BOM] SC Details: ' . json_encode($sc_details));
        
        if (empty($sc_details)) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Sales Contract tidak memiliki item']));
            return;
        }

        $inserted_count = 0;
        $errors = [];

        foreach ($sc_details as $sc_item) {
            log_message('debug', '[MIR Load BOM] Processing SC Item: ' . json_encode($sc_item));
            
            // Hanya ambil FG (finished goods)
            if ($sc_item->item_type !== 'finished') continue;

            $fg_id = $sc_item->fg_id;
            $sc_qty = (float) $sc_item->qty;

            // Get BOM for this FG
            $bom_items = $this->Bom_model->get_bom_by_fg($fg_id);

            log_message('debug', '[MIR Load BOM] BOM Items for FG '.$fg_id.': ' . json_encode($bom_items));

            if (empty($bom_items)) {
                $errors[] = "FG " . $sc_item->nama_barang . " tidak memiliki BOM";
                continue;
            }

            // Insert each BOM material into MIR
            foreach ($bom_items as $bom) {
                // Cek apakah item sudah ada di MIR
                $existing = $this->Mir_model->check_existing_item($mir_id, $bom->material_id, $bom->item_type);
                
                $calculated_qty = (float) $bom->qty * $sc_qty;

                if ($existing) {
                    // Update qty jika sudah ada
                    $new_qty = (float) $existing->qty + $calculated_qty;
                    $this->Mir_model->update_item_qty($existing->id, $new_qty);
                } else {
                    // Insert baru
                    $data = [
                        'mir_header_id'     => $mir_id,
                        'item_id'           => $bom->material_id,
                        'item_type'         => $bom->item_type,
                        'sales_contract_id' => $sc_id,
                        'qty'               => $calculated_qty,
                        'remarks'           => 'Auto dari BOM FG: ' . $sc_item->nama_barang
                    ];
                    $this->Mir_model->insert_item($data);
                    $inserted_count++;
                }
            }
        }

        if ($inserted_count > 0 || !empty($errors)) {
            $message = $inserted_count > 0 ? "$inserted_count material berhasil ditambahkan dari BOM. " : "";
            $message .= !empty($errors) ? implode(", ", $errors) : "";
            
            // TEMPORARY DEBUG INFO
            $debug_info = [
                'sc_details_count' => count($sc_details),
                'inserted' => $inserted_count,
                'errors' => $errors
            ];
            log_message('debug', '[MIR Load BOM] Final result: ' . json_encode($debug_info));
            
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'message' => $message]));
        } else {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => false, 
                'message' => 'Tidak ada material yang ditambahkan',
                'debug' => 'SC items: ' . count($sc_details) . ', Check logs for details'
            ]));
        }
    }

    public function export_pdf($id) {
        if (!check_access('mir', 'view')) {
            show_error('Anda tidak memiliki izin untuk mengekspor data.', 403);
        }
        $header = $this->Mir_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Mir_model->get_details_by_id($id);

        $data = ['header' => $header, 'details' => $details];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        
        $html = $this->load->view('mir/pdf_template', $data, true); 
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = "MIR_".str_replace('/', '-', $header->mir_number).".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
    }

    // ... (Fungsi search_items, add_item, delete_item tetap sama) ...
    public function search_items() {
        $term = $this->input->get('q');
        $items = $this->Mir_model->search_items_for_mir($term);
        
        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'id' => $item->id . '|' . $item->type,
                'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')
            ];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    public function add_item($mir_id) {
        if (!check_access('mir', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        
        // Terima multiple items
        $items = $this->input->post('items'); // Array dari checkbox
        $quantities = $this->input->post('qty'); // Array qty dengan key = item_id|item_type
        
        if (empty($items)) {
            $this->session->set_flashdata('error', 'Tidak ada item yang dipilih!');
            redirect('mir/detail/' . $mir_id);
            return;
        }

        $inserted = 0;
        foreach ($items as $item_data) {
            $parts = explode('|', $item_data);
            $item_id = $parts[0];
            $item_type = $parts[1];
            
            // Get qty dari array
            $qty = isset($quantities[$item_data]) ? (float) $quantities[$item_data] : 1;
            if ($qty <= 0) $qty = 1;
            
            // Cek apakah sudah ada
            $existing = $this->Mir_model->check_existing_item($mir_id, $item_id, $item_type);
            
            if (!$existing) {
                $data = [
                    'mir_header_id'     => $mir_id,
                    'item_id'           => $item_id,
                    'item_type'         => $item_type,
                    'sales_contract_id' => NULL,
                    'qty'               => $qty,
                    'remarks'           => ''
                ];
                $this->Mir_model->insert_item($data);
                $inserted++;
            }
        }
        
        $this->session->set_flashdata('success', "$inserted item berhasil ditambahkan.");
        redirect('mir/detail/' . $mir_id);
    }

    // Add single item (untuk inline add)
    public function add_single_item($mir_id) {
        if (!check_access('mir', 'edit')) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Tidak ada izin']));
            return;
        }

        $item_id = $this->input->post('item_id');
        $item_type = $this->input->post('item_type');
        $qty = $this->input->post('qty');
        $allowance = $this->input->post('allowance');
        $unit_value = $this->input->post('unit_value');
        $remarks = $this->input->post('remarks');

        // Debug logging
        log_message('debug', '[MIR Add Item] Data received: ' . json_encode([
            'item_id' => $item_id,
            'item_type' => $item_type,
            'qty' => $qty,
            'allowance' => $allowance,
            'unit_value' => $unit_value,
            'remarks' => $remarks
        ]));

        if (!$item_id || !$item_type || !$qty || $qty <= 0) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Data tidak lengkap']));
            return;
        }

        // Cek duplikat
        $existing = $this->Mir_model->check_existing_item($mir_id, $item_id, $item_type);
        if ($existing) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Item sudah ada di MIR']));
            return;
        }

        $data = [
            'mir_header_id'     => $mir_id,
            'item_id'           => $item_id,
            'item_type'         => $item_type,
            'sales_contract_id' => NULL,
            'qty'               => $qty,
            'allowance'         => !empty($allowance) ? $allowance : 0,
            'unit_value'        => !empty($unit_value) ? (float)$unit_value : 0,
            'remarks'           => $remarks ?? ''
        ];

        log_message('debug', '[MIR Add Item] Data to insert: ' . json_encode($data));

        $result = $this->Mir_model->insert_item($data);

        if ($result) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'message' => 'Item berhasil ditambahkan']));
        } else {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Gagal menambahkan item']));
        }
    }

    // Get item detail (untuk menampilkan kode dan tipe)
    public function get_item_detail() {
        $item_id = $this->input->get('item_id');
        $item_type = $this->input->get('item_type');

        if (!$item_id || !$item_type) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false]));
            return;
        }

        $data = null;
        switch ($item_type) {
            case 'material':
                $this->db->select('id, kode_material as code, nama_material as name');
                $data = $this->db->get_where('materials', ['id' => $item_id])->row();
                break;
            case 'semifinished':
                $this->db->select('id, kode_barang as code, nama_barang as name');
                $data = $this->db->get_where('semifinished_goods', ['id' => $item_id])->row();
                break;
            case 'machine':
                $this->db->select('id, kode_mesin as code, nama_mesin as name');
                $data = $this->db->get_where('machines', ['id' => $item_id])->row();
                break;
            case 'inventory':
                $this->db->select('id, kode_barang as code, nama_barang as name');
                $data = $this->db->get_where('inventories', ['id' => $item_id])->row();
                break;
        }

        if ($data) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'success' => true,
                'data' => [
                    'code' => $data->code,
                    'name' => $data->name,
                    'type' => $item_type
                ]
            ]));
        } else {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false]));
        }
    }

    // Update qty item existing
    public function update_item_qty() {
        if (!check_access('mir', 'edit')) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Tidak ada izin']));
            return;
        }

        $detail_id = $this->input->post('detail_id');
        $qty = $this->input->post('qty');
        $allowance = $this->input->post('allowance');
        $unit_value = $this->input->post('unit_value');

        if (!$detail_id || !$qty || $qty <= 0) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Data tidak valid']));
            return;
        }

        $update_data = [
            'qty' => $qty,
            'allowance' => $allowance ?? 0,
            'unit_value' => (float)($unit_value ?? 0)
        ];

        $result = $this->Mir_model->update_item($detail_id, $update_data);

        if ($result) {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => true, 'message' => 'Data item berhasil diupdate']));
        } else {
            $this->output->set_content_type('application/json')->set_output(json_encode(['success' => false, 'message' => 'Gagal update data item']));
        }
    }

    public function approve_mir($id) {
        // Gunakan izin 'edit' atau buat izin 'approve_mir'
        if (!check_access('mir', 'edit')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Mir_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('warning', 'Hanya MIR status Draft yang bisa diapprove!');
            redirect($this->agent->referrer() ?? 'mir/detail/' . $id); // Redirect back
            return;
        }
        // Validasi: Pastikan ada item detail
        $details = $this->Mir_model->get_details_by_id($id);
        if (empty($details)) {
             $this->session->set_flashdata('error', 'MIR tidak bisa diapprove karena belum memiliki item detail!');
             redirect('mir/detail/' . $id);
             return;
        }

        $userId = $this->session->userdata('user_id') ?? null;
        if (!$userId) { // User ID harus ada
             $this->session->set_flashdata('error', 'Gagal approve: User ID tidak ditemukan!');
             redirect('mir/detail/' . $id);
             return;
        }

        $update_status = $this->Mir_model->update_status($id, 'Approved', $userId);

        if ($update_status === true) { // Cek boolean true
            $this->session->set_flashdata('success', 'Material Import Request berhasil diapprove.');
        } else {
            // Error sudah di-log di model, beri pesan umum
            $this->session->set_flashdata('error', 'Gagal mengapprove Material Import Request!');
        }
        redirect('mir/detail/' . $id);
    }
    public function unapprove_mir($id) {
        // Gunakan izin 'edit' atau buat izin 'approve_mir'
        if (!check_access('mir', 'edit')) { show_error('Izin Ditolak.', 403); return; }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Mir_model->get_header_by_id($id);
        if (!$header || $header->status != 'Approved') {
            $this->session->set_flashdata('warning', 'Hanya MIR status Approved yang bisa dibatalkan approvalnya!');
            redirect($this->agent->referrer() ?? 'mir/detail/' . $id); // Redirect back
            return;
        }

        // Panggil model update_status (model akan cek penerimaan & handle error)
        $update_status = $this->Mir_model->update_status($id, 'Draft'); // Target status Draft

        if ($update_status === true) {
            $this->session->set_flashdata('success', 'Approval Material Import Request berhasil dibatalkan (Status: Draft).');
        } elseif ($update_status === 'received') { // Cek return spesifik dari model
            $this->session->set_flashdata('error', 'Gagal membatalkan approval: MIR sudah digunakan dalam Penerimaan Barang!');
        } else {
            // Error lain (status awal salah, DB error)
             $this->session->set_flashdata('error', 'Gagal membatalkan approval Material Import Request!');
        }
        redirect('mir/detail/' . $id);
    }
    
    public function delete_item($mir_id, $item_id) {
        if (!check_access('mir', 'edit')) {
            show_error('Anda tidak memiliki izin untuk mengedit data.', 403);
        }
        $this->Mir_model->delete_item($item_id);
        $this->session->set_flashdata('success', 'Item berhasil dihapus.');
        redirect('mir/detail/' . $mir_id);
    }
}