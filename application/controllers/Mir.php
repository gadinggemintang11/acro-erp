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
            'open_contracts'=> $this->Sc_model->get_open_contracts()
        ];
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('mir/detail', $data);
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
        $item_data = explode('|', $this->input->post('item_id'));
        $item_id = $item_data[0];
        $item_type = $item_data[1];
        
        $data = [
            'mir_header_id'     => $mir_id,
            'item_id'           => $item_id,
            'item_type'         => $item_type,
            'sales_contract_id' => $this->input->post('sales_contract_id') ?: NULL,
            'qty'               => (float) $this->input->post('qty'),
            'remarks'           => $this->input->post('remarks')
        ];
        $this->Mir_model->insert_item($data);
        $this->session->set_flashdata('success', 'Item berhasil ditambahkan.');
        redirect('mir/detail/' . $mir_id);
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