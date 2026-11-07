<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penelusuran Barang General — padanan Material_traceability untuk master
 * `inventories` (user 2026-09-16). Alur dokumennya berbeda dari material:
 * IRF -> PR -> PO -> Penerimaan (SGR) -> Pengeluaran -> Kartu stok.
 *
 * Struktur controller sengaja dibuat sama persis dengan Material_traceability
 * (index / detail / doc) supaya dua modul ini terasa satu keluarga.
 */
class Inventory_traceability extends MY_Controller {

    protected $module_name = 'Penelusuran Barang General';
    private $menu_key = 'inventory_traceability';

    public function __construct() {
        parent::__construct();
        check_login();
        $this->load->model('Inventory_traceability_model', 'trace');
        $this->load->library('pagination');
    }

    private function _guard() {
        if (!check_access($this->menu_key, 'view')) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
        }
    }

    /* ===== HALAMAN 1: daftar barang general ===== */
    public function index() {
        $this->_guard();

        $keyword  = $this->input->get('q');
        $per_page = 20;
        $page     = max(1, (int) $this->input->get('page'));
        $offset   = ($page - 1) * $per_page;

        $total = $this->trace->count_list($keyword);
        $rows  = $this->trace->get_list_paginated($per_page, $offset, $keyword);

        $this->pagination->initialize([
            'base_url'             => site_url('inventory_traceability'),
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
        ]);

        $data = [
            'title'      => lang_line('title_it_index', 'Penelusuran Barang General'),
            'rows'       => $rows,
            'keyword'    => $keyword,
            'offset'     => $offset,
            'total'      => $total,
            'pagination' => $this->pagination->create_links(),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('inventory_traceability/index', $data);
        $this->load->view('templates/footer');
    }

    /* ===== HALAMAN 2: jejak 1 barang ===== */
    public function detail($item_id = 0) {
        $this->_guard();
        $item_id = (int) $item_id;
        $item = $this->trace->get_item($item_id);
        if (!$item) show_404();

        $data = [
            'title'   => sprintf(lang_line('title_it_trace', 'Jejak Barang - %s'), $item->kode_barang),
            'item'    => $item,
            'summary' => $this->trace->summary($item_id),
            'irfs'    => $this->trace->irf_list($item_id),
            'prs'     => $this->trace->pr_list($item_id),
            'pos'     => $this->trace->po_list($item_id),
            'sgrs'    => $this->trace->sgr_list($item_id),
            'outs'    => $this->trace->out_list($item_id),
            'ledger'  => $this->trace->ledger($item_id),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('inventory_traceability/detail', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Tautan ke modul asal sebuah dokumen. PR & PO punya 4 varian controller
     * (produksi/general/jasa/asset) yang dipilih dari kolom `type`, jadi
     * pemetaannya dikumpulkan di sini. Tombolnya hanya ditampilkan kalau user
     * memang punya izin melihat modul itu — kalau tidak, dia cuma akan
     * mendarat di halaman 403.
     *
     * @return array|null ['url' => ..., 'label' => ...]
     */
    private function _module_link($type, $header) {
        $pr_map = [
            'non_produksi' => ['purchase_request_general', 'PR General'],
            'jasa'         => ['purchase_request_jasa',    'PR Jasa'],
            'asset'        => ['purchase_request_asset',   'PR Asset'],
            'produksi'     => ['purchase_request',         'PR Produksi'],
        ];
        $po_map = [
            'non_produksi' => ['purchase_order_general', 'PO General'],
            'jasa'         => ['purchase_order_jasa',    'PO Jasa'],
            'asset'        => ['purchase_order_asset',   'PO Asset'],
            'produksi'     => ['purchase_order',         'PO Produksi'],
        ];

        switch ($type) {
            case 'irf': $menu = 'consumable_request'; $label = 'Permintaan Barang'; break;
            case 'out': $menu = 'consumable_out'; $label = 'Pengeluaran Barang'; break;
            case 'sgr': $menu = 'supplier_general_item_receipt'; $label = 'Penerimaan Barang'; break;
            case 'pr':
                [$menu, $label] = $pr_map[$header->type ?? ''] ?? $pr_map['non_produksi'];
                break;
            case 'po':
                [$menu, $label] = $po_map[$header->type ?? ''] ?? $po_map['non_produksi'];
                break;
            default: return null;
        }
        if (!check_access($menu, 'view')) return null;

        return ['url' => site_url($menu . '/detail/' . (int) $header->id), 'label' => $label];
    }

    /* ===== HALAMAN 3: isi 1 dokumen ===== */
    public function doc($type = '', $header_id = 0, $item_id = 0) {
        $this->_guard();
        $header_id = (int) $header_id;
        $item_id   = (int) $item_id;
        $item      = $item_id ? $this->trace->get_item($item_id) : null;

        $map = [
            'irf' => ['irf_detail', 'inventory_traceability/doc_irf'],
            'pr'  => ['pr_detail',  'inventory_traceability/doc_pr'],
            'po'  => ['po_detail',  'inventory_traceability/doc_po'],
            'sgr' => ['sgr_detail', 'inventory_traceability/doc_sgr'],
            'out' => ['out_detail', 'inventory_traceability/doc_out'],
        ];
        if (!isset($map[$type])) show_404();

        [$method, $view] = $map[$type];
        $payload = $this->trace->$method($header_id);
        if (!$payload) show_404();

        $data = [
            'title'    => sprintf(lang_line('title_it_type_detail', 'Detail Dokumen %s'), strtoupper($type)),
            'item'     => $item,
            'item_id'  => $item_id,
            'doc'      => $payload,
            'doc_type' => $type,
            // Tombol "buka di modul aslinya" (user 2026-09-16: "ketika klik
            // view di detailnya misal di po, ada button untuk liat po nya").
            'module_link' => $this->_module_link($type, $payload['header']),
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view($view, $data);
        $this->load->view('templates/footer');
    }
}
