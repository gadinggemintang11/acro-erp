<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PO List (monitoring) — ERP Revision Notes, [[erp_revision_monitoring_lists]].
 *
 * Versi GABUNGAN lintas-tipe dari Pmpo_list (type=produksi)/Gmpo_list
 * (type=non_produksi) yang sudah ada — di sini type TIDAK dipaksa, bisa
 * difilter bebas termasuk jasa/asset (2 tipe itu sebelumnya tidak ke-cover
 * di modul manapun). Reuse penuh Po_report_model + view `purchase_dashboard/list`
 * (view sama, cuma di-generalize dgn kolom Type + Total value + edit-route
 * per-baris supaya tetap benar walau barisnya beda-beda tipe).
 */
class Po_monitoring extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model(['Po_report_model', 'Po_model']);
        $this->load->helper(['url', 'form']);
    }

    public function index() {
        if (!check_access('po_monitoring', 'view')) {
            show_error('Tidak ada izin untuk mengakses PO List (monitoring).', 403);
        }

        $filters = [
            'search'         => $this->input->get('search', TRUE),
            'supplier_id'    => $this->input->get('supplier_id', TRUE),
            'status'         => $this->input->get('status', TRUE),
            'receipt_status' => $this->input->get('receipt_status', TRUE),
            'type'           => $this->input->get('type', TRUE),
        ];

        // Edit tiap baris harus ke controller PO ASLI sesuai tipe baris itu
        // (beda dari Pmpo_list/Gmpo_list yang semua barisnya 1 tipe, jadi
        // edit_route-nya bisa 1 string tetap).
        $edit_route_map = [
            'produksi'     => 'purchase_order/detail',
            'non_produksi' => 'purchase_order_general/detail',
            'jasa'         => 'purchase_order_jasa/detail',
            'asset'        => 'purchase_order_asset/detail',
        ];
        $can_edit_map = [
            'produksi'     => check_access('purchase_order', 'edit'),
            'non_produksi' => check_access('purchase_order_general', 'edit'),
            'jasa'         => check_access('purchase_order_jasa', 'edit'),
            'asset'        => check_access('purchase_order_asset', 'edit'),
        ];

        $data = [
            'title'           => lang_line('title_po_monitoring', 'PO List (Monitoring)'),
            'page_kind'       => 'list',
            'po_type'         => null,          // null = gabungan, bukan 1 tipe terkunci
            'po_type_label'   => 'PO',
            'type_locked'     => false,
            'detail_route'    => 'po_monitoring/detail',
            'delete_route'    => 'po_monitoring/delete',
            'reset_url'       => 'po_monitoring',
            'edit_route'      => null,          // dipakai fallback kalau edit_route_map tidak ke-set utk tipe itu
            'edit_route_map'  => $edit_route_map,
            'can_edit_map'    => $can_edit_map,
            'can_edit'        => false,          // tidak dipakai langsung di mode gabungan (lihat can_edit_map)
            'can_delete'      => check_access('po_monitoring', 'delete'),
            'filters'         => $filters,
            'pos'             => $this->Po_report_model->get_all($filters),
            'suppliers'       => $this->Po_report_model->get_suppliers(),
        ];

        $this->_render('purchase_dashboard/list', $data);
    }

    /**
     * Detail — reuse Po_report::detail (type-agnostic, cukup po_id).
     */
    public function detail($po_id = null) {
        if (!check_access('po_monitoring', 'view')) show_error('Tidak ada izin.', 403);
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

    /**
     * Hapus PO Draft — tanpa batasan tipe (beda dari Pmpo_list/Gmpo_list
     * yang masing2 cuma boleh hapus tipe sendiri), krn di sini memang
     * gabungan semua tipe.
     */
    public function delete($po_id = null) {
        if (!check_access('po_monitoring', 'delete')) {
            show_error('Tidak ada izin menghapus PO.', 403);
        }
        $po_id = (int) $po_id;
        if ($po_id <= 0) show_404();

        $header = $this->Po_model->get_po_header_by_id($po_id);
        if (!$header) {
            $this->session->set_flashdata('error', 'PO tidak ditemukan.');
            redirect('po_monitoring'); return;
        }
        if ($header->status !== 'Draft') {
            $this->session->set_flashdata('error', 'Hanya PO status Draft yang bisa dihapus.');
            redirect('po_monitoring'); return;
        }

        if ($this->Po_model->delete_header($po_id)) {
            $this->session->set_flashdata('success', "PO {$header->po_number} berhasil dihapus.");
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus PO. Pastikan tidak ada item detail atau cek DB error.');
        }
        redirect('po_monitoring');
    }

    private function _render($view, $data = []) {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }
}
