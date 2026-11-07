<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_request_general.php';

/**
 * PR Jasa — SUBCLASS TIPIS dari `Purchase_request_general` (lihat docblock
 * lengkap di sana utk penjelasan pola konsolidasi ini, user 2026-09-06).
 * SEMUA logic (index/detail/CRUD/status/export/dst) diwarisi apa adanya dari
 * class dasar. URL (`purchase_request_jasa/...`), menu sidebar (sekarang
 * digabung ke bawah link "General Item Purchase Request", lihat
 * templates/sidebar.php), dan hak akses Role Access TIDAK BERUBAH.
 */
class Purchase_request_jasa extends Purchase_request_general {

    protected $pr_type = 'jasa';
    protected $menu_key = 'purchase_request_jasa';
    protected $po_menu_key = 'purchase_order_jasa';
    protected $approval_menu_key = 'purchase_request_approval_jasa';
    protected $log_module = 'Purchase Request Jasa';
    protected $search_item_type = 'jasa';

    /** Halaman sendiri (akses langsung/bookmark) scoped ke Jasa saja — beda
     *  dari General yg jadi listing gabungan (lihat docblock base class). */
    protected function _included_types() {
        return ['jasa'];
    }

    protected function _title_index() {
        return 'Purchase Request Jasa';
    }

    protected function _title_detail() {
        return 'Detail Purchase Request Jasa';
    }

    protected function _form_title() {
        return 'PURCHASE REQUEST - JASA';
    }

    protected function _form_subtitle() {
        return '服务请购单';
    }

    protected function _item_label() {
        return 'Jasa';
    }

    protected function _no_manual_create_message() {
        return 'PR Jasa sekarang selalu berawal dari Permintaan Barang — tidak bisa dibuat manual lagi. Buat PR dari menu Outstanding Permintaan Barang untuk item Jasa yang sudah di-approve.';
    }
}
