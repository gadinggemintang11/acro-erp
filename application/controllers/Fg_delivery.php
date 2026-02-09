<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Dompdf\Dompdf;
use Dompdf\Options;

class Fg_delivery extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model([
            'Fg_delivery_model',
            'Sc_model',
            'FinishedGoods_model',
            'Fg_stock_ledger_model',
            'Commoncode_model',
        ]);
        $this->load->helper(['url', 'form']);
        $this->load->library('form_validation');
    }

    /**
     * Index - list pengiriman
     */
    public function index() {
        if (!check_access('fg_delivery', 'view')) { show_error('Tidak ada izin.', 403); }

        $filters = [
            'search' => $this->input->get('search', TRUE),
            'start_date' => $this->input->get('start_date', TRUE),
            'end_date' => $this->input->get('end_date', TRUE),
            'customer_id' => $this->input->get('customer_id', TRUE),
            'status' => $this->input->get('status', TRUE),
        ];

        $data = [
            'title' => 'Bukti Pengeluaran Barang Jadi',
            'filters' => $filters,
            'deliveries' => $this->Fg_delivery_model->get_all($filters)
        ];

        $this->_render('fg_delivery/index', $data);
    }

    private function _render($view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }

    /**
     * Create header
     */
    public function create() {
        if (!check_access('fg_delivery', 'add')) { show_error('Tidak ada izin.', 403); }

        $data = [
            'title' => 'Buat Pengiriman - Header',
            'customers' => $this->Fg_delivery_model->get_customers(), // get list customers
            'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE')
        ];
        $this->_render('fg_delivery/create_header', $data);
    }

    /**
     * Store header
     */
    public function store_header() {
        if (!check_access('fg_delivery', 'add')) { show_error('Tidak ada izin.', 403); }

        $this->form_validation->set_rules('delivery_date', 'Tanggal Pengeluaran', 'required');
        $this->form_validation->set_rules('customer_id', 'Customer', 'required|numeric');

        if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors());
            $this->create();
            return;
        }

        $headerData = [
            'delivery_number' => $this->Fg_delivery_model->generate_delivery_number(),
            'delivery_date' => $this->input->post('delivery_date', TRUE),
            'sales_contract_id' => $this->input->post('sales_contract_id', TRUE) ?: NULL,
            'customer_id' => $this->input->post('customer_id', TRUE),
            'shipping_address' => $this->input->post('shipping_address', TRUE) ?: NULL,
            'remarks' => $this->input->post('remarks', TRUE) ?: NULL,
            'bc_type_id' => $this->input->post('bc_type_id', TRUE) ?: NULL,
            'bc_number' => $this->input->post('bc_number', TRUE) ?: NULL,
            'bc_date' => $this->input->post('bc_date', TRUE) ?: NULL,
            'shipping_method' => $this->input->post('shipping_method', TRUE) ?: NULL,
            'status' => 'Draft'
        ];

        $id = $this->Fg_delivery_model->insert_header($headerData);
        if ($id) {
            $this->session->set_flashdata('success', 'Header pengiriman berhasil dibuat.');
            redirect('fg_delivery/create_items/' . $id);
        } else {
            $this->session->set_flashdata('error', 'Gagal membuat header.');
            redirect('fg_delivery/create');
        }
    }

    /**
     * Create items - pilih FG ready based on customer (header id)
     */
    public function create_items($header_id = null) {
        if (!check_access('fg_delivery', 'add')) { show_error('Tidak ada izin.', 403); }
        if (empty($header_id) || !is_numeric($header_id)) show_404();

        $header = $this->Fg_delivery_model->get_header_by_id($header_id);
        if (!$header) {
            $this->session->set_flashdata('error', 'Header tidak ditemukan.');
            redirect('fg_delivery/create');
        }

        // fetch FG ready based on customer (from stock_ledger + receipt)
        $fg_ready = $this->Fg_delivery_model->get_fg_ready_for_customer($header->customer_id);

        $data = [
            'title' => 'Isi Detail Pengiriman',
            'header' => $header,
            'fg_ready' => $fg_ready
        ];

        $this->_render('fg_delivery/create_detail', $data);
    }

    /**
     * Store delivery with details (Draft)
     */
    public function store() {
        if (!check_access('fg_delivery', 'add')) { show_error('Tidak ada izin.', 403); }

        $this->form_validation->set_rules('fg_delivery_header_id', 'Header', 'required|numeric');

        $header_id = $this->input->post('fg_delivery_header_id', TRUE);
        $details_input = $this->input->post('details', TRUE); // array of details [fg_id, qty_shipped, uom, notes, sc_id, so_detail_id]

        if (empty($header_id)) {
            $this->session->set_flashdata('error', 'Header tidak valid.');
            redirect('fg_delivery');
        }

        // validate at least one qty > 0
        $has = false;
        $detailsData = [];
        if (!empty($details_input) && is_array($details_input)) {
            foreach ($details_input as $d) {
                $qty = isset($d['qty_shipped']) ? (float)$d['qty_shipped'] : 0;
                if ($qty > 0 && !empty($d['fg_id'])) {
                    $has = true;
                    $detailsData[] = [
                        'fg_delivery_header_id' => $header_id,
                        'sales_contract_id' => $d['sales_contract_id'] ?? NULL,
                        'so_detail_id' => $d['so_detail_id'] ?? NULL,
                        'fg_id' => $d['fg_id'],
                        'qty_shipped' => $qty,
                        'uom' => $d['uom'] ?? NULL,
                        'notes' => $d['notes'] ?? NULL
                    ];
                }
            }
        }

        if (!$has) {
            $this->session->set_flashdata('error', 'Minimal 1 item dengan qty pengiriman > 0.');
            redirect('fg_delivery/create_items/' . $header_id);
        }

        $result = $this->Fg_delivery_model->insert_delivery($header_id, $detailsData);
        if ($result) {
            $this->session->set_flashdata('success', 'Pengiriman tersimpan (Draft).');
            redirect('fg_delivery/show/' . $header_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan pengiriman.');
            redirect('fg_delivery/create_items/' . $header_id);
        }
    }

    /**
     * Edit items in a Draft delivery
     */
    public function edit_items($header_id = null) {
        if (!check_access('fg_delivery', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($header_id) || !is_numeric($header_id)) show_404();

        $header = $this->Fg_delivery_model->get_header_by_id($header_id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya pengiriman dengan status Draft yang bisa diubah itemnya.');
            redirect('fg_delivery/show/' . $header_id);
        }

        // Get existing details to pre-fill the form
        $existing_details = $this->Fg_delivery_model->get_details_by_header_id($header_id);
        $existing_details_map = [];
        foreach ($existing_details as $detail) {
            $existing_details_map[$detail->fg_id] = $detail;
        }

        // Get all ready FG for the customer to allow adding/removing items
        $fg_ready = $this->Fg_delivery_model->get_fg_ready_for_customer($header->customer_id);

        $data = [
            'title' => 'Edit Detail Pengiriman',
            'header' => $header,
            'fg_ready' => $fg_ready,
            'existing_details_map' => $existing_details_map
        ];

        $this->_render('fg_delivery/edit_items', $data);
    }

    /**
     * Update delivery details (from edit_items view)
     */
    public function update_items() {
        if (!check_access('fg_delivery', 'edit')) { show_error('Tidak ada izin.', 403); }

        $this->form_validation->set_rules('fg_delivery_header_id', 'Header', 'required|numeric');

        $header_id = $this->input->post('fg_delivery_header_id', TRUE);
        $details_input = $this->input->post('details', TRUE);

        if (empty($header_id)) {
            $this->session->set_flashdata('error', 'Header tidak valid.');
            redirect('fg_delivery');
        }

        $header = $this->Fg_delivery_model->get_header_by_id($header_id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Draft yang bisa diupdate.');
            redirect('fg_delivery/show/' . $header_id);
        }

        $has_item = false;
        $detailsData = [];
        if (!empty($details_input) && is_array($details_input)) {
            foreach ($details_input as $d) {
                $qty = isset($d['qty_shipped']) ? (float)$d['qty_shipped'] : 0;
                if ($qty > 0 && !empty($d['fg_id'])) {
                    $has_item = true;
                    $detailsData[] = [
                        'fg_delivery_header_id' => $header_id,
                        'sales_contract_id' => $d['sales_contract_id'] ?? NULL,
                        'so_detail_id' => $d['so_detail_id'] ?? NULL,
                        'fg_id' => $d['fg_id'],
                        'qty_shipped' => $qty,
                        'uom' => $d['uom'] ?? NULL,
                        'notes' => $d['notes'] ?? NULL
                    ];
                }
            }
        }

        if (!$has_item) {
            $this->session->set_flashdata('error', 'Minimal harus ada 1 item dengan qty pengiriman > 0.');
            redirect('fg_delivery/edit_items/' . $header_id);
        }

        // The insert_delivery method handles update (delete existing then insert new)
        $result = $this->Fg_delivery_model->insert_delivery($header_id, $detailsData);
        if ($result) {
            $this->session->set_flashdata('success', 'Item pengiriman berhasil diupdate.');
            redirect('fg_delivery/show/' . $header_id);
        } else {
            $this->session->set_flashdata('error', 'Gagal mengupdate item pengiriman.');
            redirect('fg_delivery/edit_items/' . $header_id);
        }
    }

    /**
     * Show detail
     */
    public function show($id) {
        if (!check_access('fg_delivery', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Fg_delivery_model->get_header_by_id($id);
        if (!$header) show_404();

        $details = $this->Fg_delivery_model->get_details_by_header_id($id);
        $data = [
            'title' => 'Detail Pengiriman',
            'delivery_header' => $header,
            'delivery_details' => $details
        ];

        $this->_render('fg_delivery/show', $data);
    }

    /**
     * Edit header (only Draft)
     */
    public function edit($id) {
        if (!check_access('fg_delivery', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Fg_delivery_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Draft yang bisa diedit.');
            redirect('fg_delivery/show/' . $id);
        }

        $data = [
            'title' => 'Edit Header Pengiriman',
            'header' => $header,
            'customers' => $this->Fg_delivery_model->get_customers(),
            'bc_types' => $this->Commoncode_model->get_by_group('BC_TYPE')
        ];
        $this->_render('fg_delivery/edit_header', $data);
    }

    /**
     * Update header
     */
    public function update_header($id) {
        if (!check_access('fg_delivery', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Fg_delivery_model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya Draft yang bisa diupdate.');
            redirect('fg_delivery/show/' . $id);
        }

        $data = [
            'delivery_date' => $this->input->post('delivery_date', TRUE),
            'customer_id' => $this->input->post('customer_id', TRUE),
            'shipping_address' => $this->input->post('shipping_address', TRUE),
            'remarks' => $this->input->post('remarks', TRUE),
            'bc_type_id' => $this->input->post('bc_type_id', TRUE) ?: NULL,
            'bc_number' => $this->input->post('bc_number', TRUE) ?: NULL,
            'bc_date' => $this->input->post('bc_date', TRUE) ?: NULL,
            'shipping_method' => $this->input->post('shipping_method', TRUE) ?: NULL,
        ];

        if ($this->Fg_delivery_model->update_header($id, $data)) {
            $this->session->set_flashdata('success', 'Header berhasil diupdate.');
        } else {
            $this->session->set_flashdata('error', 'Gagal update header.');
        }
        redirect('fg_delivery/show/' . $id);
    }

    /**
     * Delete header (Draft) or Cancel (Confirmed)
     */
    public function destroy($id) {
        if (empty($id) || !is_numeric($id)) show_404();
        $header = $this->Fg_delivery_model->get_header_by_id($id);
        if (!$header) show_404();

        if ($header->status == 'Draft') {
            if (!check_access('fg_delivery', 'delete')) { show_error('Tidak ada izin.', 403); }
            $ok = $this->Fg_delivery_model->delete_delivery($id);
            $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Draft berhasil dihapus.' : 'Gagal menghapus draft.');
        } elseif ($header->status == 'Confirmed') {
            if (!check_access('fg_delivery', 'delete')) { show_error('Tidak ada izin.', 403); }
            $ok = $this->Fg_delivery_model->cancel_delivery($id);
            $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Dokumen dibatalkan (stok dikembalikan).' : 'Gagal membatalkan dokumen.');
        } else {
            $this->session->set_flashdata('warning', 'Hanya Draft atau Confirmed yang bisa dihapus/dibatalkan.');
        }

        redirect('fg_delivery');
    }

    public function delete_detail($detail_id, $header_id) {
        if (!check_access('fg_delivery', 'delete')) { show_error('Tidak ada izin.', 403); }
        if (empty($detail_id) || !is_numeric($detail_id) || empty($header_id) || !is_numeric($header_id)) show_404();

        $ok = $this->Fg_delivery_model->delete_detail($detail_id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Item detail berhasil dihapus.' : 'Gagal menghapus item detail. Pastikan status Draft.');

        redirect('fg_delivery/show/' . $header_id);
    }

    /**
     * Confirm (Ship) => kurangi stok
     */
    public function ship($id) {
        if (!check_access('fg_delivery', 'edit')) { show_error('Tidak ada izin.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();

        $userId = $this->session->userdata('user_id') ?? null;
        $ok = $this->Fg_delivery_model->ship_delivery($id, $userId);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Pengiriman dikonfirmasi.' : 'Gagal konfirmasi. Cek stok atau log.');
        redirect('fg_delivery/show/' . $id);
    }

    /**
     * Unconfirm (rollback stok)
     */
    public function unship($id) {
        if (!check_access('fg_delivery', 'delete')) { show_error('Tidak ada izin.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();

        // Check if document is exim verified
        $header = $this->Fg_delivery_model->get_header_by_id($id);
        if ($header && $header->exim_verified) {
            $this->session->set_flashdata('error', 'Tidak dapat membatalkan konfirmasi dokumen yang sudah diverifikasi EXIM. Batalkan verifikasi EXIM terlebih dahulu.');
            redirect('fg_delivery/show/' . $id);
            return;
        }

        $ok = $this->Fg_delivery_model->unship_delivery($id);
        $this->session->set_flashdata($ok ? 'success' : 'error', $ok ? 'Konfirmasi dibatalkan (stok dikembalikan).' : 'Gagal membatalkan konfirmasi.');
        redirect('fg_delivery/show/' . $id);
    }

    /**
     * Export delivery to PDF
     */
    public function export_pdf($id) {
        if (!check_access('fg_delivery', 'view')) { show_error('Tidak ada izin.', 403); }
        if (empty($id) || !is_numeric($id)) show_404();

        $header = $this->Fg_delivery_model->get_header_by_id($id);
        if (!$header) show_404();
        $details = $this->Fg_delivery_model->get_details_by_header_id($id);

        $data = [
            'title' => 'Delivery Order',
            'header' => $header,
            'details' => $details
        ];

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $html = $this->load->view('fg_delivery/pdf_template', $data, true);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = "DeliveryOrder_" . str_replace('/', '-', $header->delivery_number) . ".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
        exit;
    }

    // AJAX: get SC by customer (for UI convenience)
    public function ajax_get_sc_by_customer($customer_id) {
        $res = $this->Sc_model->get_by_customer($customer_id, 'Open');
        header('Content-Type: application/json');
        echo json_encode($res);
    }

    // AJAX: get customer address
    public function ajax_get_customer($customer_id) {
        $cust = $this->Fg_delivery_model->get_customer_by_id($customer_id);
        header('Content-Type: application/json');
        echo json_encode($cust);
    }

    // AJAX: Select2 customer search (server-side)
    public function customer_search()
    {
        $q = $this->input->get('q', TRUE);
        $this->db->select('id, nama_customer');
        $this->db->from('customers');
        if (!empty($q)) {
            $this->db->like('nama_customer', $q);
        }
        $this->db->order_by('nama_customer', 'ASC');
        $this->db->limit(50);
        $rows = $this->db->get()->result();

        $out = [];
        foreach ($rows as $r) {
            $out[] = ['id' => $r->id, 'text' => $r->nama_customer];
        }
        header('Content-Type: application/json');
        echo json_encode(['results' => $out]);
    }

}
