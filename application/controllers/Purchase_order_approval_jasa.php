<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_order_approval.php';

/**
 * Approval PO Jasa — SUBCLASS TIPIS dari `Purchase_order_approval` (lihat
 * docblock lengkap di sana, user 2026-09-05).
 */
class Purchase_order_approval_jasa extends Purchase_order_approval {

    protected $po_type = 'jasa';
    protected $menu_key = 'purchase_order_approval_jasa';
    protected $main_menu_key = 'purchase_order_jasa';
    protected $po_type_label = 'PO Jasa';
}
