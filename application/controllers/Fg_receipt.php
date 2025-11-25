<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Dompdf\Dompdf;
use Dompdf\Options;

class Fg_receipt extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->library('user_agent');
        $this->load->model([
            'Fg_receipt_model',
            'Production_model',
            'FinishedGoods_model',
            'Stock_ledger_model'
        ]);
        $this->load->helper(['url', 'form']);
        $this->load->library('form_validation');
    }

    // ======================================================
    // INDEX
    // ======================================================
    public function index() {
        if (!check_access('fg_receipt', 'view')) { show_error('Tidak ada izin.', 403); }

        $filters = ['search' => $this->input->get('search')];
        $data = [
            'title'    => 'Penerimaan Barang Jadi (FG)',
            'receipts' => $this->Fg_receipt_model->get_all($filters),
            'filters'  => $filters
        ];

        $this->_render('fg_receipt/index', $data);
    }

    // ======================================================
    // RENDER VIEW WRAPPER
    // ======================================================
    private function _render($view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }

    // ======================================================
    // AJAX: SEARCH WO
    // ======================================================
    public function ajax_search_wo() {
        $term = $this->input->get('q');
        $wos = $this->Fg_receipt_model->search_wo_for_receipt($term);
        $results = [];

        foreach ($wos as $wo) {
            $results[] = [
                'id'                     => $wo->wo_id,
                'text'                   => htmlspecialchars($wo->wo_number . ' - ' . $wo->fg_name),
                'sc_number'              => htmlspecialchars($wo->sc_number),
                'production_header_id'   => $wo->production_header_id,
                'production_number'      => htmlspecialchars($wo->production_number)
            ];
        }

        header('Content-Type: application/json');
        echo json_encode(['results' => $results]);
    }

    // ======================================================
    // AJAX: GET RECEIVABLE FG OUTPUT
    // ======================================================
    public function ajax_get_receivable_items($production_header_id) {
        if (empty($production_header_id) || !is_numeric($production_header_id)) {
            echo json_encode(['success' => false, 'items' => []]);
            return;
        }

        $items = $this->Fg_receipt_model->get_receivable_fg_outputs_by_prod_id($production_header_id);
        echo json_encode(['success' => true, 'items' => $items]);
    }

    // ======================================================
    // CREATE FORM
    // ======================================================
    public function create() {
        if (!check_access('fg_receipt', 'add')) { show_error('Tidak ada izin.', 403); }

        $data['title'] = 'Buat Penerimaan Barang Jadi Baru (by WO)';
        $this->_render('fg_receipt/create_by_wo', $data);
    }

    // ======================================================
    // STORE NEW RECEIPT
    // ======================================================
    public function store() {
        if (!check_access('fg_receipt', 'add')) { show_error('Tidak ada izin.', 403); }

        $this->form_validation->set_rules('receipt_date', 'Tanggal Terima', 'required');
        $this->form_validation->set_rules('wo_header_id', 'Work Order', 'required|numeric');
        $this->form_validation->set_rules('production_header_id', 'Production Header', 'required|numeric');

        $details = $this->input->post('details');
        $has_valid_qty = false;

        if (!empty($details)) {
            foreach ($details as $detail) {
                if (!empty($detail['qty_received']) && $detail['qty_received'] > 0) {
                    $has_valid_qty = true;
                    break;
                }
            }
        }

        if (!$has_valid_qty) {
            $this->session->set_flashdata('error', 'Minimal 1 item harus memiliki Qty Diterima > 0.');
            $this->create();
            return;
        }

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            $this->create();
            return;
        }

        // Header
        $wo_header_id = $this->input->post('wo_header_id');
        log_message('debug', 'Fg_receipt_controller::store - wo_header_id from POST: ' . $wo_header_id);

        $sales_contract_id = $this->Fg_receipt_model->get_sales_contract_id_by_wo_id($wo_header_id);
        log_message('debug', 'Fg_receipt_controller::store - sales_contract_id from model: ' . $sales_contract_id);

        $headerData = [
            'receipt_number'       => $this->Fg_receipt_model->generate_receipt_number(),
            'receipt_date'         => $this->input->post('receipt_date'),
            'production_header_id' => $this->input->post('production_header_id'),
            'wo_header_id'         => $wo_header_id,
            'sales_contract_id'    => $sales_contract_id, // Add sales_contract_id here
            'received_by'          => $this->session->userdata('user_id') ?? null,
            'remarks'              => $this->input->post('remarks'),
            'status'               => 'Draft'
        ];

        // Details
        $detailsData = [];
        foreach ($details as $prod_output_id => $detail) {
            if (isset($detail['qty_received']) && $detail['qty_received'] > 0) {
                $detailsData[] = [
                    'prod_output_id' => $prod_output_id,
                    'fg_id'          => $detail['fg_id'],
                    'qty_received'   => (float)$detail['qty_received'],
                    'uom'            => $detail['uom'],
                    'notes'          => $detail['notes'] ?? null
                ];
            }
        }

        $insertedId = $this->Fg_receipt_model->insert_receipt($headerData, $detailsData);
        if ($insertedId) {
            $this->session->set_flashdata('success', 'Penerimaan FG berhasil disimpan (Status: Draft).');
            redirect('fg_receipt/show/' . $insertedId);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan penerimaan.');
            $this->create();
        }
    }

    // ======================================================
    // SHOW DETAIL
    // ======================================================
    public function show($id) {
        if (!check_access('fg_receipt', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header  = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Fg_receipt_model->get_details_by_header_id($id);

        $data = [
            'title'           => 'Detail Penerimaan Barang Jadi',
            'receipt_header'  => $header,
            'receipt_details' => $details
        ];

        $this->_render('fg_receipt/show', $data);
    }

    // ======================================================
    // EDIT FORM
    // ======================================================
    public function edit($id) {
        if (!check_access('fg_receipt', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya dokumen Draft yang bisa diedit.');
            redirect('fg_receipt/show/' . $id);
        }

        $details = $this->Fg_receipt_model->get_details_by_header_id($id);
        $data = [
            'title'           => 'Edit Penerimaan Barang Jadi',
            'receipt_header'  => $header,
            'receipt_details' => $details
        ];

        $this->_render('fg_receipt/edit', $data);
    }

    // ======================================================
    // UPDATE
    // ======================================================
    public function update($id) {
        if (!check_access('fg_receipt', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($id)) show_404();

        $header = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Draft yang bisa diupdate.');
            redirect('fg_receipt/show/' . $id);
        }

        $this->form_validation->set_rules('receipt_date', 'Tanggal Terima', 'required');
        $details = $this->input->post('details');

        $detailsData = [];
        foreach ($details as $detail) {
            if (!empty($detail['fg_id']) && !empty($detail['qty_received']) && $detail['qty_received'] > 0) {
                $detailsData[] = [
                    'prod_output_id' => !empty($detail['prod_output_id']) ? $detail['prod_output_id'] : NULL,
                    'fg_id'          => $detail['fg_id'],
                    'qty_received'   => (float)$detail['qty_received'],
                    'uom'            => $detail['uom'],
                    'notes'          => $detail['notes'] ?? null
                ];
            }
        }

        if (empty($detailsData)) {
            $this->session->set_flashdata('error', 'Item detail tidak boleh kosong.');
            redirect('fg_receipt/edit/' . $id);
            return;
        }

        $headerData = [
            'receipt_date' => $this->input->post('receipt_date'),
            'remarks'      => $this->input->post('remarks'),
            'received_by'  => $this->session->userdata('user_id') ?? null
        ];

        if ($this->Fg_receipt_model->update_receipt($id, $headerData, $detailsData)) {
            $this->session->set_flashdata('success', 'Penerimaan berhasil diupdate.');
        } else {
            $this->session->set_flashdata('error', 'Gagal update penerimaan.');
        }

        redirect('fg_receipt/show/' . $id);
    }

    // ======================================================
    // DESTROY / CANCEL
    // ======================================================
    public function destroy($id) {
        if (empty($id)) show_404();
        $header = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header) show_404();

        if ($header->status == 'Draft') {
            if (!check_access('fg_receipt', 'delete')) { show_error('Tidak ada izin.', 403); }
            $success = $this->Fg_receipt_model->delete_receipt($id);
            $msg = $success ? 'Draft berhasil dihapus.' : 'Gagal menghapus draft.';
            $this->session->set_flashdata($success ? 'success' : 'error', $msg);
        } elseif ($header->status == 'Confirmed') {
            if (!check_access('fg_receipt', 'delete')) { show_error('Tidak ada izin.', 403); }
            if ($this->Fg_receipt_model->cancel_receipt($id)) {
                $this->session->set_flashdata('success', 'Dokumen Confirmed dibatalkan (stok dikembalikan).');
            } else {
                $this->session->set_flashdata('error', 'Gagal membatalkan dokumen.');
            }
        } else {
            $this->session->set_flashdata('warning', 'Hanya status Draft/Confirmed yang dapat dihapus.');
        }

        redirect('fg_receipt');
    }

    // ======================================================
    // CONFIRM
    // ======================================================
    public function confirm($id) {
        if (!check_access('fg_receipt', 'edit')) { show_error('Tidak ada izin.', 403); }
        $userId = $this->session->userdata('user_id');
        if ($this->Fg_receipt_model->confirm_receipt($id, $userId)) {
            $this->session->set_flashdata('success', 'Penerimaan dikonfirmasi (stok bertambah).');
        } else {
            $this->session->set_flashdata('error', 'Gagal konfirmasi dokumen.');
        }
        redirect('fg_receipt/show/' . $id);
    }

    // ======================================================
    // UNCONFIRM
    // ======================================================
    public function unconfirm($id) {
        if (!check_access('fg_receipt', 'delete')) { show_error('Tidak ada izin.', 403); }
        if ($this->Fg_receipt_model->unconfirm_receipt($id)) {
            $this->session->set_flashdata('success', 'Konfirmasi dibatalkan (stok dikurangi).');
        } else {
            $this->session->set_flashdata('error', 'Gagal membatalkan konfirmasi.');
        }
        redirect('fg_receipt/show/' . $id);
    }

    // ======================================================
    // EXPORT PDF
    // ======================================================
    public function export_pdf($id) {
        if (!check_access('fg_receipt', 'view')) { show_error('Tidak ada izin.', 403); }

        $header = $this->Fg_receipt_model->get_header_by_id($id);
        if (!$header) show_404();

        $details = $this->Fg_receipt_model->get_details_by_header_id($id);

        $data = [
            'receipt_header'  => $header,
            'receipt_details' => $details
        ];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $html = $this->load->view('fg_receipt/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "FGReceipt_" . str_replace('/', '-', $header->receipt_number) . ".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
        exit;
    }
}
