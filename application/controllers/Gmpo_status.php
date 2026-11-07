<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GMPO Status — status tracker untuk General Material Purchase Order
 * (po_header.type = 'non_produksi'). Read-only.
 */
class Gmpo_status extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Po_report_model');
        $this->load->helper(['url', 'form']);
    }

    public function index() {
        if (!check_access('gmpo_status', 'view')) {
            show_error('Tidak ada izin untuk mengakses PO General Status.', 403);
        }

        $filters = [
            'search'         => $this->input->get('search', TRUE),
            'supplier_id'    => $this->input->get('supplier_id', TRUE),
            'status'         => $this->input->get('status', TRUE),
            'receipt_status' => $this->input->get('receipt_status', TRUE),
            // GMPO = gabungan General + Jasa + Asset (user 2026-09-11).
            // Yang berdiri sendiri cuma Produksi/material (PMPO).
            'type'           => ['non_produksi', 'jasa', 'asset'],
        ];

        $data = [
            'title'         => lang_line('title_gmpo_status'),
            'page_kind'     => 'status',
            // Label halaman tetap "General Material" (GMPO). Barisnya campur
            // 3 tipe, jadi kolom "Tipe" dimunculkan lewat $mixed_types.
            'po_type'       => 'non_produksi',
            'mixed_types'   => true,
            'po_type_label' => 'PO General',
            'status_route'  => 'gmpo_status', // rute eksplisit: label bukan lagi nama rute (rebrand Acropoly)
            'detail_route'  => 'gmpo_list/detail',
            'delete_route'  => 'gmpo_list/delete',
            'can_delete'    => check_access('gmpo_list', 'delete') || check_access('purchase_order_general', 'delete'),
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
