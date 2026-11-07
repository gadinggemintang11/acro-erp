<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * GMPO List — flat list semua General Material PO (type=non_produksi).
 * Detail page reuse Po_report view.
 */
class Gmpo_list extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Po_report_model', 'Po_model']);
        $this->load->helper(['url', 'form']);
    }
    
    public function index() {
        if (!check_access('gmpo_list', 'view')) {
            show_error('Tidak ada izin untuk mengakses PO General List.', 403);
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
            'title'         => lang_line('title_gmpo_list'),
            'page_kind'     => 'list',
            'po_type'       => 'non_produksi',
            'po_type_label' => 'PO General',
            'reset_url'     => 'gmpo_list', // rute eksplisit: label bukan lagi nama rute (rebrand Acropoly)
            'detail_route'  => 'gmpo_list/detail',
            'delete_route'  => 'gmpo_list/delete',
            // Tombol Edit menuju halaman PO aslinya (bukan view laporan di sini),
            // karena di sanalah form edit header & item berada.
            'edit_route'    => 'purchase_order_general/detail',
            'can_edit'      => check_access('purchase_order_general', 'edit'),
            // Sejak GMPO memayungi 3 tipe (2026-09-11), barisnya campur —
            // jadi kolom "Tipe" dimunculkan, dan tombol Edit tiap baris harus
            // menuju controller PO ASLI tipe itu (pola sama dgn Po_monitoring).
            'type_locked'   => false,
            'edit_route_map' => [
                'non_produksi' => 'purchase_order_general/detail',
                'jasa'         => 'purchase_order_jasa/detail',
                'asset'        => 'purchase_order_asset/detail',
            ],
            'can_edit_map'  => [
                'non_produksi' => check_access('purchase_order_general', 'edit'),
                'jasa'         => check_access('purchase_order_jasa', 'edit'),
                'asset'        => check_access('purchase_order_asset', 'edit'),
            ],
            'can_delete'    => check_access('gmpo_list', 'delete') || check_access('purchase_order_general', 'delete'),
            'filters'       => $filters,
            'pos'           => $this->Po_report_model->get_all($filters),
            'suppliers'     => $this->Po_report_model->get_suppliers(),
        ];

        $this->_render('purchase_dashboard/list', $data);
    }

    /**
     * Delete Draft GMPO. Reuse Po_model::delete_header dengan guard status.
     */
    public function delete($po_id = null) {
        if (!check_access('gmpo_list', 'delete') && !check_access('purchase_order_general', 'delete')) {
            show_error('Tidak ada izin menghapus PO General.', 403);
        }
        $po_id = (int)$po_id;
        if ($po_id <= 0) show_404();

        $header = $this->Po_model->get_po_header_by_id($po_id);
        if (!$header) {
            $this->session->set_flashdata('error', 'PO General tidak ditemukan.');
            redirect('gmpo_list'); return;
        }
        if ($header->type !== 'non_produksi') {
            $this->session->set_flashdata('error', 'PO ini bukan PO General.');
            redirect('gmpo_list'); return;
        }
        if ($header->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Hanya PO General status Draft yang bisa dihapus.');
            redirect('gmpo_list'); return;
        }

        if ($this->Po_model->delete_header($po_id)) {
            $this->session->set_flashdata('success', "PO General {$header->po_number} berhasil dihapus.");
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus PO General. Pastikan tidak ada item detail atau cek DB error.');
        }
        redirect('gmpo_list');
    }

    public function detail($po_id = null) {
        if (!check_access('gmpo_list', 'view')) show_error('Tidak ada izin.', 403);
        if (empty($po_id) || !is_numeric($po_id)) show_404();

        $po_data = $this->Po_report_model->get_po_detail($po_id);
        if (!$po_data) show_404();

        $data = [
            'title'      => sprintf(lang_line('title_gmpo_detail'), $po_data['header']->po_number),
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
