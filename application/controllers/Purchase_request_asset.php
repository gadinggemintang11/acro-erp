<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_request_general.php';

/**
 * PR Asset — SUBCLASS TIPIS dari `Purchase_request_general` (lihat docblock
 * lengkap di sana utk penjelasan pola konsolidasi ini, user 2026-09-06).
 * SEMUA logic (index/detail/CRUD/status/export/dst) diwarisi apa adanya dari
 * class dasar. URL (`purchase_request_asset/...`), menu sidebar (sekarang
 * digabung ke bawah link "General Item Purchase Request", lihat
 * templates/sidebar.php), dan hak akses Role Access TIDAK BERUBAH.
 */
class Purchase_request_asset extends Purchase_request_general {

    protected $pr_type = 'asset';
    protected $menu_key = 'purchase_request_asset';
    protected $po_menu_key = 'purchase_order_asset';
    protected $approval_menu_key = 'purchase_request_approval_asset';
    protected $log_module = 'Purchase Request Asset';
    protected $search_item_type = 'asset';

    /** Halaman sendiri (akses langsung/bookmark) scoped ke Asset saja — beda
     *  dari General yg jadi listing gabungan (lihat docblock base class). */
    protected function _included_types() {
        return ['asset'];
    }

    protected function _title_index() {
        return 'Purchase Request Asset';
    }

    protected function _title_detail() {
        return 'Detail Purchase Request Asset';
    }

    protected function _form_title() {
        return 'PURCHASE REQUEST - ASET';
    }

    protected function _form_subtitle() {
        return '固定资产请购单';
    }

    protected function _item_label() {
        return 'Asset';
    }

    protected function _no_manual_create_message() {
        return 'PR Asset sekarang selalu berawal dari Permintaan Barang — tidak bisa dibuat manual lagi. Buat PR dari menu Outstanding Permintaan Barang (centang "Reklasifikasi jadi Asset" kalau perlu).';
    }
}
