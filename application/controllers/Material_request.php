<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Material_request extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Mr_model', 'Wo_model']); // Load MR dan WO model
        $this->load->helper('url');
    }

    public function index() {
        if (!check_access('material_request', 'view')) { // Sesuaikan menu_key
            show_error('Anda tidak memiliki izin.', 403);
        }
        
        $filters = [
            'start_date' => $this->input->get('start_date') ?: date('Y-m-01'),
            'end_date'   => $this->input->get('end_date') ?: date('Y-m-t'),
            'search'     => $this->input->get('search')
        ];

        $data = [
            'title'   => 'Material Request (MR)',
            'mrs'     => $this->Mr_model->get_all($filters),
            'filters' => $filters
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_request/index', $data);
        $this->load->view('templates/footer');
    }

    // Proses buat header MR baru
    public function create_header() {
        if (!check_access('material_request', 'add')) { show_error('Tidak ada izin.', 403); }

        $data = [
            'mr_number'     => $this->Mr_model->generate_mr_number(),
            'request_date'  => $this->input->post('request_date'),
            'remarks'       => $this->input->post('remarks'),
            'status'        => 'Draft',
            'requester_id'  => $this->session->userdata('user_id') ?? null, // Langsung set Dibuat Oleh
            'requested_at'  => date('Y-m-d H:i:s')
        ];

        $new_mr_id = $this->Mr_model->insert_header($data);

        if ($new_mr_id) {
            $this->session->set_flashdata('success', 'Header MR berhasil dibuat. Silakan tambahkan item.');
            redirect('material_request/detail/' . $new_mr_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat header MR!');
            redirect('material_request');
        }
    }
    
    // Hapus header MR (hanya jika Draft)
    public function delete_mr($id) {
         if (!check_access('material_request', 'delete')) { show_error('Tidak ada izin.', 403); }
         $header = $this->Mr_model->get_header_by_id($id);
         if (!$header || $header->status != 'Draft') {
             $this->session->set_flashdata('error', 'MR tidak bisa dihapus (status bukan Draft).');
             redirect('material_request');
         }
         if ($this->Mr_model->delete_header($id)) { $this->session->set_flashdata('success', 'MR berhasil dihapus.'); }
         else { $this->session->set_flashdata('error', 'Gagal menghapus MR.'); }
         redirect('material_request');
    }

    // Halaman detail MR
    public function detail($id) {
        if (!check_access('material_request', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header) show_404();

        $data = [
            'title'   => 'Detail Material Request',
            'header'  => $header,
            'details' => $this->Mr_model->get_details_by_id($id)
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_request/detail', $data);
        // Footer dipanggil di view
    }

    // === AJAX Endpoints ===
    // AJAX: Cari WO (status 'In Progress')
    public function search_open_wos() {
        $term = $this->input->get('q');
        $wos = $this->Mr_model->search_open_wos($term);
        $results = [];
        foreach ($wos as $wo) {
            $results[] = ['id' => $wo->id, 'text' => htmlspecialchars($wo->wo_number . ' - ' . $wo->fg_name)];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }
    
    // AJAX: Ambil referensi (SC, Style, BOM, Plan) dari WO
    public function get_wo_references_json() {
        $wo_id = $this->input->get('wo_id');
        if (!$wo_id) { echo json_encode(['success' => false]); return; }
        
        $references = $this->Mr_model->get_wo_references($wo_id);
        if ($references['header']) {
            // Render HTML referensi di server
            $data['header'] = $references['header'];
            $data['bom'] = $references['bom'];
            $data['plan'] = $references['plan'];
            $html = $this->load->view('material_request/ajax_wo_reference', $data, true);
            echo json_encode(['success' => true, 'html' => $html]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
    
    // AJAX: Cari komponen (material/semifinished)
    public function search_components() {
        $term = $this->input->get('q');
        $items = $this->Mr_model->search_components($term);
        $results = [];
        foreach ($items as $item) {
            $results[] = ['id' => $item->id . '|' . $item->type, 'text' => htmlspecialchars($item->name . ' (' . $item->code . ')')];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    // === Item Actions (PPIC) ===
    // AJAX: Add single item (inline)
    public function add_item_ajax($mr_id) {
        header('Content-Type: application/json');
        
        if (!check_access('material_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin']);
            return;
        }
        
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') {
            echo json_encode(['success' => false, 'message' => 'MR tidak valid atau sudah disubmit']);
            return;
        }
        
        $component_id = $this->input->post('component_id');
        $component_type = $this->input->post('component_type');
        
        if (!$component_id || !$component_type) {
            echo json_encode(['success' => false, 'message' => 'Data component tidak valid']);
            return;
        }
        
        $data = [
            'mr_header_id'        => $mr_id,
            'wo_header_id'        => $this->input->post('wo_header_id'),
            'component_item_id'   => $component_id,
            'component_item_type' => $component_type,
            'requested_qty'       => (float) $this->input->post('requested_qty'),
            'notes'               => $this->input->post('notes')
        ];
        
        $inserted_id = $this->Mr_model->insert_item($data);
        
        if ($inserted_id) {
            $full_item = $this->Mr_model->get_item_by_id($inserted_id);
            echo json_encode([
                'success' => true,
                'item' => $full_item,
                'message' => 'Item berhasil ditambahkan'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menambahkan item (mungkin item sudah ada)'
            ]);
        }
    }
    
    public function add_item($mr_id) {
        if (!check_access('material_request', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') { redirect('material_request/detail/' . $mr_id); }

        $item_data = explode('|', $this->input->post('component_id'));
        if (count($item_data) < 2) { redirect('material_request/detail/' . $mr_id); }

        $data = [
            'mr_header_id'        => $mr_id,
            'wo_header_id'        => $this->input->post('wo_header_id'),
            'component_item_id'   => $item_data[0],
            'component_item_type' => $item_data[1],
            'requested_qty'       => (float) $this->input->post('requested_qty'),
            'notes'               => $this->input->post('notes')
        ];

        if ($this->Mr_model->insert_item($data)) { $this->session->set_flashdata('success', 'Item berhasil ditambahkan.'); }
        else { $this->session->set_flashdata('error', 'Gagal, item ini sudah ada untuk WO tersebut.'); }
        redirect('material_request/detail/' . $mr_id);
    }
    
    // AJAX: Batch add items
    public function add_items_batch($mr_id) {
        header('Content-Type: application/json');
        
        if (!check_access('material_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin']);
            return;
        }
        
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') {
            echo json_encode(['success' => false, 'message' => 'MR tidak valid atau sudah disubmit']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $items = $input['items'] ?? [];
        
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada item yang dikirim']);
            return;
        }
        
        $inserted_items = [];
        $success_count = 0;
        
        foreach ($items as $item) {
            $item_data = explode('|', $item['component_id']);
            if (count($item_data) < 2) continue;
            
            $data = [
                'mr_header_id'        => $mr_id,
                'wo_header_id'        => $item['wo_id'],
                'component_item_id'   => $item_data[0],
                'component_item_type' => $item_data[1],
                'requested_qty'       => (float) $item['qty'],
                'notes'               => $item['notes'] ?? ''
            ];
            
            $inserted_id = $this->Mr_model->insert_item($data);
            if ($inserted_id) {
                $success_count++;
                // Get full item data untuk return
                $full_item = $this->Mr_model->get_item_by_id($inserted_id);
                if ($full_item) {
                    $inserted_items[] = $full_item;
                }
            }
        }
        
        if ($success_count > 0) {
            echo json_encode([
                'success' => true,
                'count' => $success_count,
                'items' => $inserted_items,
                'message' => "$success_count item berhasil ditambahkan"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menambahkan item'
            ]);
        }
    }

    /**
     * ✅ NEW: AJAX untuk load planned materials dari WO
     */
    public function ajax_get_wo_materials() {
        header('Content-Type: application/json');
        
        $wo_id = $this->input->get('wo_id');
        if (!$wo_id) {
            echo json_encode(['success' => false, 'message' => 'WO ID tidak valid']);
            return;
        }

        // Load Wo_model dan ambil planned materials
        $this->load->model('Wo_model');
        $wo_header = $this->Wo_model->get_header_by_id($wo_id);
        
        if (!$wo_header) {
            echo json_encode(['success' => false, 'message' => 'Work Order tidak ditemukan']);
            return;
        }

        $planned_materials = $this->Wo_model->get_planned_materials($wo_id);
        
        if (empty($planned_materials)) {
            echo json_encode([
                'success' => false,
                'message' => 'Belum ada material yang direncanakan untuk WO ini'
            ]);
            return;
        }

        // Format materials untuk display
        $materials = [];
        foreach ($planned_materials as $mat) {
            $materials[] = [
                'id' => $mat->item_id,
                'type' => $mat->item_type,
                'code' => $mat->item_code,
                'name' => $mat->item_name,
                'planned_qty' => (float)$mat->planned_qty,
                'unit' => $mat->unit_measure
            ];
        }

        echo json_encode([
            'success' => true,
            'wo_number' => $wo_header->wo_number,
            'materials' => $materials
        ]);
    }

    /**
     * ✅ NEW: AJAX untuk batch add materials dari WO popup
     */
    public function ajax_add_materials_from_wo($mr_id) {
        header('Content-Type: application/json');
        
        if (!check_access('material_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin']);
            return;
        }

        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') {
            echo json_encode(['success' => false, 'message' => 'MR tidak valid atau sudah disubmit']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $wo_id = $input['wo_id'] ?? null;
        $selected_items = $input['selected_items'] ?? [];

        if (!$wo_id || empty($selected_items)) {
            echo json_encode(['success' => false, 'message' => 'WO atau materials tidak valid']);
            return;
        }

        $inserted_items = [];
        $success_count = 0;
        $duplicate_count = 0;

        foreach ($selected_items as $item) {
            $component_id = $item['id'];
            $component_type = $item['type'];
            $requested_qty = (float)($item['qty'] ?? 0);

            if ($requested_qty <= 0) continue;

            $data = [
                'mr_header_id'        => $mr_id,
                'wo_header_id'        => $wo_id,
                'component_item_id'   => $component_id,
                'component_item_type' => $component_type,
                'requested_qty'       => $requested_qty,
                'notes'               => ''
            ];

            $inserted_id = $this->Mr_model->insert_item($data);
            if ($inserted_id) {
                $success_count++;
                $full_item = $this->Mr_model->get_item_by_id($inserted_id);
                if ($full_item) {
                    $inserted_items[] = $full_item;
                }
            } else {
                $duplicate_count++;
            }
        }

        if ($success_count > 0) {
            echo json_encode([
                'success' => true,
                'count' => $success_count,
                'duplicates' => $duplicate_count,
                'items' => $inserted_items,
                'message' => "$success_count item berhasil ditambahkan" . ($duplicate_count > 0 ? " ($duplicate_count item sudah ada)" : "")
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal menambahkan item (mungkin semua sudah ada)'
            ]);
        }
    }
    
    // AJAX: Delete item
    public function delete_item_ajax() {
        header('Content-Type: application/json');
        
        if (!check_access('material_request', 'edit')) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada izin']);
            return;
        }
        
        $item_id = $this->input->post('item_id');
        if (!$item_id) {
            echo json_encode(['success' => false, 'message' => 'Item ID tidak valid']);
            return;
        }
        
        // Cek apakah item masih dalam status Draft
        $item = $this->Mr_model->get_item_by_id($item_id);
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item tidak ditemukan']);
            return;
        }
        
        $header = $this->Mr_model->get_header_by_id($item->mr_header_id);
        if (!$header || $header->status != 'Draft') {
            echo json_encode(['success' => false, 'message' => 'Item tidak bisa dihapus (MR sudah disubmit)']);
            return;
        }
        
        if ($this->Mr_model->delete_item($item_id)) {
            echo json_encode(['success' => true, 'message' => 'Item berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus item']);
        }
    }
    
    public function delete_item($mr_id, $detail_id) {
        if (!check_access('material_request', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Mr_model->get_header_by_id($mr_id);
        if (!$header || $header->status != 'Draft') { redirect('material_request/detail/' . $mr_id); }

        if ($this->Mr_model->delete_item($detail_id)) { $this->session->set_flashdata('success', 'Item berhasil dihapus.'); }
        else { $this->session->set_flashdata('error', 'Gagal menghapus item.'); }
        redirect('material_request/detail/' . $mr_id);
    }
    
    // === Confirm / Unconfirm (PPIC) ===
    public function submit_mr($id) {
        if (!check_access('material_request', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') { redirect('material_request/detail/' . $id); }
        $details = $this->Mr_model->get_details_by_id($id);
        if (empty($details)) {
             $this->session->set_flashdata('error', 'MR tidak bisa disubmit karena belum ada item.');
             redirect('material_request/detail/' . $id);
        }
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Mr_model->update_status($id, 'Submitted', $user_id)) {
            $this->session->set_flashdata('success', 'MR berhasil disubmit ke Gudang.');
        } else { $this->session->set_flashdata('error', 'Gagal submit MR.'); }
        redirect('material_request/detail/' . $id);
    }

    public function unsubmit_mr($id) {
        if (!check_access('material_request', 'edit')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header || $header->status != 'Submitted') { redirect('material_request/detail/' . $id); }
        
        // Di modul selanjutnya, kita harus cek apakah status sudah 'Issued'. Jika sudah, tidak bisa di-unsubmit.
        
        $user_id = $this->session->userdata('user_id') ?? null;
        if ($this->Mr_model->update_status($id, 'Draft', $user_id)) { // user_id di sini opsional
            $this->session->set_flashdata('success', 'Submit MR berhasil dibatalkan.');
        } else { $this->session->set_flashdata('error', 'Gagal membatalkan submit.'); }
        redirect('material_request/detail/' . $id);
    }
    
    // === Exports ===
    public function export_pdf($id) {
        if (!check_access('material_request', 'view')) { show_error('Tidak ada izin.', 403); }
        $header = $this->Mr_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Mr_model->get_details_by_id($id);
        $data = ['header' => $header, 'details' => $details];

        $options = new Options(); $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('material_request/pdf_template', $data, true);
        $dompdf->loadHtml($html); $dompdf->setPaper('A4', 'portrait'); $dompdf->render();
        $filename = "MR_".str_replace('/', '-', $header->mr_number).".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
    }
}