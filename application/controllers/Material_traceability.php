<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_traceability extends MY_Controller {

    protected $module_name = 'Material Traceability';
    private $menu_key = 'material_traceability';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Material_traceability_model', 'trace');
        $this->load->library('pagination');
    }

    private function _guard() {
        if (!check_access($this->menu_key, 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }
    }

    /* ===== HALAMAN 1: List Master Material ===== */
    public function index() {
        $this->_guard();

        $keyword  = $this->input->get('q');
        $per_page = 20;
        $page     = max(1, (int) $this->input->get('page'));
        $offset   = ($page - 1) * $per_page;

        $total   = $this->trace->count_list($keyword);
        $rows    = $this->trace->get_list_paginated($per_page, $offset, $keyword);

        $config = [
            'base_url'             => site_url('material_traceability'),
            'total_rows'           => $total,
            'per_page'             => $per_page,
            'page_query_string'    => TRUE,
            'query_string_segment' => 'page',
            'reuse_query_string'   => TRUE,
            'use_page_numbers'     => TRUE,
            'full_tag_open'        => '<ul class="pagination justify-content-end mb-0">',
            'full_tag_close'       => '</ul>',
            'num_tag_open'         => '<li class="page-item">',
            'num_tag_close'        => '</li>',
            'cur_tag_open'         => '<li class="page-item active"><a class="page-link" href="#">',
            'cur_tag_close'        => '</a></li>',
            'prev_tag_open'        => '<li class="page-item">',
            'prev_tag_close'       => '</li>',
            'next_tag_open'        => '<li class="page-item">',
            'next_tag_close'       => '</li>',
            'attributes'           => ['class' => 'page-link'],
        ];
        $this->pagination->initialize($config);

        $data = [
            'title'      => lang_line('title_mt_index'),
            'rows'       => $rows,
            'keyword'    => $keyword,
            'offset'     => $offset,
            'total'      => $total,
            'pagination' => $this->pagination->create_links(),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_traceability/index', $data);
        $this->load->view('templates/footer');
    }

    /* ===== HALAMAN 2: Trace Overview (per material) ===== */
    public function detail($material_id = 0) {
        $this->_guard();
        $material_id = (int) $material_id;
        $material = $this->trace->get_material($material_id);
        if (!$material) show_404();

        $data = [
            'title'    => sprintf(lang_line('title_mt_trace'), $material->kode_material),
            'material' => $material,
            'summary'  => $this->trace->summary($material_id),
            'prs'      => $this->trace->pr_list($material_id),
            'pos'      => $this->trace->po_list($material_id),
            'grns'     => $this->trace->grn_list($material_id),
            'mrs'      => $this->trace->mr_list($material_id),
            'prods'    => $this->trace->production_list($material_id),
            'ledger'   => $this->trace->ledger($material_id),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('material_traceability/detail', $data);
        $this->load->view('templates/footer');
    }

    /* ===== HALAMAN 3: Drill-down per dokumen ===== */
    public function doc($type = '', $header_id = 0, $material_id = 0) {
        $this->_guard();
        $header_id   = (int) $header_id;
        $material_id = (int) $material_id;
        $material    = $material_id ? $this->trace->get_material($material_id) : null;

        $view = null; $payload = null;
        switch ($type) {
            case 'pr':
                $payload = $this->trace->pr_detail($header_id);
                $view = 'material_traceability/doc_pr';
                break;
            case 'po':
                $payload = $this->trace->po_detail($header_id);
                $view = 'material_traceability/doc_po';
                break;
            case 'grn':
                $payload = $this->trace->grn_detail($header_id);
                $view = 'material_traceability/doc_grn';
                break;
            case 'mr':
                $payload = $this->trace->mr_detail($header_id);
                $view = 'material_traceability/doc_mr';
                break;
            case 'prod':
                $payload = $this->trace->production_detail($header_id);
                $view = 'material_traceability/doc_production';
                break;
            default:
                show_404();
        }
        if (!$payload) show_404();

        $data = [
            'title'       => sprintf(lang_line('title_mt_type_detail'), strtoupper($type)),
            'material'    => $material,
            'material_id' => $material_id,
            'doc'         => $payload,
            'doc_type'    => $type,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }
}
