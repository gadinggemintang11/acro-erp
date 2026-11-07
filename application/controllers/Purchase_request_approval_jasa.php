<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_request_approval_general.php';

/**
 * Approval PR Jasa — SUBCLASS TIPIS dari `Purchase_request_approval_general`
 * (lihat docblock lengkap di sana, user 2026-09-06). Tidak ada method unik
 * di sini — approval PR "non-produksi" genuinely identik lintas tipe.
 */
class Purchase_request_approval_jasa extends Purchase_request_approval_general {

    protected $pr_type = 'jasa';
    protected $menu_key = 'purchase_request_approval_jasa';
    protected $main_menu_key = 'purchase_request_jasa';
    protected $log_module = 'Purchase Request Jasa';

    protected function _included_types() {
        return ['jasa'];
    }

    protected function _title_index() {
        return 'Purchase Request Approval Jasa';
    }

    protected function _title_approval() {
        return 'PR Jasa Approval';
    }

    protected function _title_detail() {
        return 'Approval - Purchase Request Jasa';
    }
}
