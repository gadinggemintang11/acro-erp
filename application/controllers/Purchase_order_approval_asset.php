<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_order_approval.php';

/**
 * Approval PO Asset — SUBCLASS TIPIS dari `Purchase_order_approval` (lihat
 * docblock lengkap di sana, user 2026-09-05).
 */
class Purchase_order_approval_asset extends Purchase_order_approval {

    protected $po_type = 'asset';
    protected $menu_key = 'purchase_order_approval_asset';
    protected $main_menu_key = 'purchase_order_asset';
    protected $po_type_label = 'PO Asset';

    /**
     * Aset tidak lewat GRN — penanda "sudah diterima" adalah baris di
     * asset_register. Sama persis dgn Purchase_order_asset::_has_receipts().
     */
    protected function _has_receipts($id) {
        return $this->Po_model->check_if_po_has_asset_register($id);
    }
}
