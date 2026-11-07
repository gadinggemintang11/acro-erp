<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * PR List (monitoring) — ERP Revision Notes, [[erp_revision_monitoring_lists]].
 *
 * View GABUNGAN lintas-tipe PR (produksi/non_produksi/jasa/asset) — 4 tipe
 * itu masing2 sudah punya controller+list sendiri (Purchase_request,
 * Purchase_request_general, _jasa, _asset), read-only murni monitoring,
 * tidak menduplikasi CRUD-nya. Baris di-link balik ke detail controller
 * ASLI sesuai tipe row itu (bukan detail sendiri) — beda dari Po_monitoring
 * yang detail-nya reuse Po_report_model (type-agnostic); PR tidak punya
 * model laporan generik serupa, jadi paling aman & konsisten link balik
 * ke halaman detail yang memang sudah lengkap per tipe.
 */
class Pr_monitoring extends MY_Controller {

    const TYPE_DETAIL_ROUTE = [
        'produksi'     => 'purchase_request/detail',
        'non_produksi' => 'purchase_request_general/detail',
        'jasa'         => 'purchase_request_jasa/detail',
        'asset'        => 'purchase_request_asset/detail',
    ];

    const TYPE_LABEL = [
        'produksi'     => 'PR Material',
        'non_produksi' => 'PR General / Jasa / Asset',
        'jasa'         => 'Jasa',
        'asset'        => 'Asset',
    ];

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Purchase_request_model');
        $this->load->helper(['url', 'form']);
    }

    public function index() {
        if (!check_access('pr_monitoring', 'view')) {
            show_error('Tidak ada izin untuk mengakses PR List (monitoring).', 403);
        }

        $filters = [
            'search'     => $this->input->get('search', TRUE),
            'status'     => $this->input->get('status', TRUE),
            'type'       => $this->input->get('type', TRUE),
            'department' => $this->input->get('department', TRUE),
        ];

        $data = [
            'title'          => lang_line('title_pr_monitoring', 'PR List (Monitoring)'),
            'filters'        => $filters,
            'records'        => $this->Purchase_request_model->get_all_monitoring($filters),
            'type_labels'    => self::TYPE_LABEL,
            'type_routes'    => self::TYPE_DETAIL_ROUTE,
            'statuses'       => ['Draft', 'Submitted', 'Approved', 'Rejected'],
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('pr_monitoring/index', $data);
        $this->load->view('templates/footer');
    }
}
