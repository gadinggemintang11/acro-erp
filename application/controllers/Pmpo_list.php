<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PMPO List — flat list semua Production Material PO (type=produksi).
 * Detail page reuse Po_report::detail().
 */
class Pmpo_list extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Po_report_model', 'Po_model']);
        $this->load->helper(['url', 'form']);
    }

    public function index() {
        if (!check_access('pmpo_list', 'view')) {
            show_error('Tidak ada izin untuk mengakses PO Material List.', 403);
        }

        $filters = [
            'search'         => $this->input->get('search', TRUE),
            'supplier_id'    => $this->input->get('supplier_id', TRUE),
            'status'         => $this->input->get('status', TRUE),
            'receipt_status' => $this->input->get('receipt_status', TRUE),
            'type'           => 'produksi',
        ];

        $data = [
            'title'         => lang_line('title_pmpo_list'),
            'page_kind'     => 'list',
            'po_type'       => 'produksi',
            'po_type_label' => 'PO Material',
            'reset_url'     => 'pmpo_list', // rute eksplisit: label bukan lagi nama rute (rebrand Acropoly)
            'detail_route'  => 'pmpo_list/detail',
            'delete_route'  => 'pmpo_list/delete',
            // Tombol Edit menuju halaman PO aslinya (bukan view laporan di sini),
            // karena di sanalah form edit header & item berada.
            'edit_route'    => 'purchase_order/detail',
            'can_edit'      => check_access('purchase_order', 'edit'),
            'can_delete'    => check_access('pmpo_list', 'delete') || check_access('purchase_order', 'delete'),
            'filters'       => $filters,
            'pos'           => $this->Po_report_model->get_all($filters),
            'suppliers'     => $this->Po_report_model->get_suppliers(),
        ];

        $this->_render('purchase_dashboard/list', $data);
    }

    /**
     * Delete Draft PMPO. Reuse Po_model::delete_header dengan guard status.
     */
    public function delete($po_id = null) {
        if (!check_access('pmpo_list', 'delete') && !check_access('purchase_order', 'delete')) {
            show_error('Tidak ada izin menghapus PO Material.', 403);
        }
        $po_id = (int)$po_id;
        if ($po_id <= 0) show_404();

        $header = $this->Po_model->get_po_header_by_id($po_id);
        if (!$header) {
            $this->session->set_flashdata('error', 'PO Material tidak ditemukan.');
            redirect('pmpo_list'); return;
        }
        if ($header->type !== 'produksi') {
            $this->session->set_flashdata('error', 'PO ini bukan PO Material.');
            redirect('pmpo_list'); return;
        }
        if ($header->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Hanya PO Material status Draft yang bisa dihapus.');
            redirect('pmpo_list'); return;
        }

        if ($this->Po_model->delete_header($po_id)) {
            $this->session->set_flashdata('success', "PO Material {$header->po_number} berhasil dihapus.");
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus PO Material. Pastikan tidak ada item detail atau cek DB error.');
        }
        redirect('pmpo_list');
    }

    /**
     * Detail page — delegate ke Po_report::detail (view tetap reuse).
     */
    public function detail($po_id = null) {
        if (!check_access('pmpo_list', 'view')) show_error('Tidak ada izin.', 403);
        if (empty($po_id) || !is_numeric($po_id)) show_404();

        $po_data = $this->Po_report_model->get_po_detail($po_id);
        if (!$po_data) show_404();

        $data = [
            'title'      => sprintf(lang_line('title_pmpo_detail'), $po_data['header']->po_number),
            'po_header'  => $po_data['header'],
            'po_details' => $po_data['details'],
            'grns'       => $po_data['grns'],
        ];

        $this->_render('po_report/detail', $data);
    }

    private function _render($view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }
}
