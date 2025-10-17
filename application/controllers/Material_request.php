<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

class Material_request extends CI_Controller {

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
        if ($this->Mr_model->update_status($id, 'Submitted', 'requester', $user_id)) {
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
        if ($this->Mr_model->update_status($id, 'Draft', 'requester', $user_id)) { // user_id di sini opsional
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