<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PMPO Status — status tracker untuk Production Material Purchase Order
 * (po_header.type = 'produksi'). Read-only.
 */
class Pmpo_status extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Po_report_model');
        $this->load->helper(['url', 'form']);
    }

    public function index() {
        if (!check_access('pmpo_status', 'view')) {
            show_error('Tidak ada izin untuk mengakses PO Material Status.', 403);
        }

        $filters = [
            'search'         => $this->input->get('search', TRUE),
            'supplier_id'    => $this->input->get('supplier_id', TRUE),
            'status'         => $this->input->get('status', TRUE),
            'receipt_status' => $this->input->get('receipt_status', TRUE),
            'type'           => 'produksi',
        ];

        $data = [
            'title'         => lang_line('title_pmpo_status'),
            'page_kind'     => 'status',
            'po_type'       => 'produksi',
            'po_type_label' => 'PO Material',
            'status_route'  => 'pmpo_status', // rute eksplisit: label bukan lagi nama rute (rebrand Acropoly)
            'detail_route'  => 'pmpo_list/detail',
            'delete_route'  => 'pmpo_list/delete',
            'can_delete'    => check_access('pmpo_list', 'delete') || check_access('purchase_order', 'delete'),
            'filters'       => $filters,
            'pos'           => $this->Po_report_model->get_all($filters),
            'suppliers'     => $this->Po_report_model->get_suppliers(),
        ];

        $this->_render('purchase_dashboard/status', $data);
    }

    private function _render($view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }
}
