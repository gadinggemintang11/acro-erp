<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_request_approval_general.php';

/**
 * Approval PR Asset — SUBCLASS TIPIS dari `Purchase_request_approval_general`
 * (lihat docblock lengkap di sana, user 2026-09-06). Tidak ada method unik
 * di sini — approval PR "non-produksi" genuinely identik lintas tipe.
 */
class Purchase_request_approval_asset extends Purchase_request_approval_general {

    protected $pr_type = 'asset';
    protected $menu_key = 'purchase_request_approval_asset';
    protected $main_menu_key = 'purchase_request_asset';
    protected $log_module = 'Purchase Request Asset';

    protected function _included_types() {
        return ['asset'];
    }

    protected function _title_index() {
        return 'Purchase Request Approval Asset';
    }

    protected function _title_approval() {
        return 'PR Asset Approval';
    }

    protected function _title_detail() {
        return 'Approval - Purchase Request Asset';
    }
}
