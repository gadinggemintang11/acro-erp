<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require FCPATH . 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

class Consumable_out extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Consumable_out_model', 'Inventory_model']);
        $this->load->helper(['url', 'form']);
        $this->load->library('session');
    }

    // -------------------------------------------------------
    // Daftar dokumen pengeluaran barang habis pakai
    // -------------------------------------------------------
    public function index() {
        if (!check_access('consumable_out', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $filters = [
            'search'     => $this->input->get('search'),
            'status'     => $this->input->get('status'),
            'date_from'  => $this->input->get('date_from'),
            'date_to'    => $this->input->get('date_to'),
        ];

        $data = [
            'title'   => 'Pengeluaran Barang Habis Pakai',
            'records' => $this->Consumable_out_model->get_all($filters),
            'filters' => $filters,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_out/index', $data);
        $this->load->view('templates/footer');
    }

    // -------------------------------------------------------
    // Form buat dokumen baru
    // -------------------------------------------------------
    public function add() {
        if (!check_access('consumable_out', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $data = [
            'title'      => 'Buat Pengeluaran Barang Habis Pakai',
            'items_list' => $this->Consumable_out_model->get_inventory_items(),
            'doc_number' => $this->Consumable_out_model->generate_doc_number(),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_out/form', $data);
        $this->load->view('templates/footer');
    }

    // -------------------------------------------------------
    // Simpan dokumen baru
    // -------------------------------------------------------
    public function store() {
        if (!check_access('consumable_out', 'add')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        $user_id       = $this->session->userdata('user_id');
        $issuance_date = $this->input->post('issuance_date');
        $department    = $this->input->post('department');
        $recipient     = $this->input->post('recipient');
        $purpose       = $this->input->post('purpose');
        $notes         = $this->input->post('notes');
        $items         = $this->input->post('items'); // array

        if (empty($issuance_date)) {
            $this->session->set_flashdata('error', 'Tanggal pengeluaran wajib diisi.');
            redirect('consumable_out/add');
            return;
        }

        if (empty($items) || !is_array($items)) {
            $this->session->set_flashdata('error', 'Minimal satu item harus ditambahkan.');
            redirect('consumable_out/add');
            return;
        }

        // Filter item kosong
        $valid_items = array_filter($items, function($i) {
            return !empty($i['item_id']) && isset($i['qty']) && (float)$i['qty'] > 0;
        });

        if (empty($valid_items)) {
            $this->session->set_flashdata('error', 'Minimal satu item dengan qty > 0 harus diisi.');
            redirect('consumable_out/add');
            return;
        }

        $header_data = [
            'doc_number'    => $this->Consumable_out_model->generate_doc_number(),
            'issuance_date' => $issuance_date,
            'department'    => $department,
            'recipient'     => $recipient,
            'purpose'       => $purpose,
            'notes'         => $notes,
            'status'        => 'Issued',
            'created_by'    => $user_id,
            'created_at'    => date('Y-m-d H:i:s'),
            'issued_by'     => $user_id,
            'issued_at'     => date('Y-m-d H:i:s'),
        ];

        $result = $this->Consumable_out_model->create($header_data, $valid_items);

        if ($result['success']) {
            $this->session->set_flashdata('success', 'Pengeluaran barang berhasil disimpan. No. Dok: ' . $result['doc_number']);
            redirect('consumable_out/detail/' . $result['id']);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan: ' . ($result['message'] ?? 'Terjadi kesalahan.'));
            redirect('consumable_out/add');
        }
    }

    // -------------------------------------------------------
    // Detail dokumen
    // -------------------------------------------------------
    public function detail($id) {
        if (!check_access('consumable_out', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Consumable_out_model->get_header($id);
        if (!$header) { show_404(); }

        $data = [
            'title'   => 'Detail Pengeluaran - ' . $header->doc_number,
            'header'  => $header,
            'details' => $this->Consumable_out_model->get_details($id),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('consumable_out/detail', $data);
        $this->load->view('templates/footer');
    }

    // -------------------------------------------------------
    // Hapus dokumen (hanya Draft)
    // -------------------------------------------------------
    public function delete($id) {
        if (!check_access('consumable_out', 'delete')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Consumable_out_model->get_header($id);
        if (!$header) { show_404(); }

        if ($header->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Hanya dokumen Draft yang bisa dihapus.');
            redirect('consumable_out');
            return;
        }

        $this->Consumable_out_model->delete($id);
        $this->session->set_flashdata('success', 'Dokumen berhasil dihapus.');
        redirect('consumable_out');
    }

    // -------------------------------------------------------
    // Export PDF
    // -------------------------------------------------------
    public function export_pdf($id) {
        if (!check_access('consumable_out', 'view')) {
            show_error('Anda tidak memiliki izin.', 403);
            return;
        }

        if (empty($id) || !is_numeric($id)) { show_404(); }

        $header = $this->Consumable_out_model->get_header($id);
        if (!$header) { show_404(); }

        $data = [
            'header'  => $header,
            'details' => $this->Consumable_out_model->get_details($id),
        ];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);
        $html = $this->load->view('consumable_out/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $filename = 'Consumable_Out_' . str_replace('/', '-', $header->doc_number) . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 0]);
    }

    // -------------------------------------------------------
    // AJAX: Ambil detail item inventaris
    // -------------------------------------------------------
    public function ajax_get_item($item_id) {
        if (!$this->input->is_ajax_request()) { show_404(); }

        $item = $this->Consumable_out_model->get_inventory_item($item_id);
        if ($item) {
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false]);
        }
    }
}
