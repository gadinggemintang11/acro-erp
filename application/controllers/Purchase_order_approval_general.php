<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_order_approval.php';

/**
 * Approval PO Non-Produksi (General) — SUBCLASS TIPIS dari
 * `Purchase_order_approval` (lihat docblock lengkap di sana, user
 * 2026-09-05). Tidak ada method unik sama sekali di sini — approval PO
 * genuinely identik lintas tipe.
 */
class Purchase_order_approval_general extends Purchase_order_approval {

    protected $po_type = 'non_produksi';
    protected $menu_key = 'purchase_order_approval_general';
    protected $main_menu_key = 'purchase_order_general';
    protected $po_type_label = 'PO General';

    /**
     * User 2026-09-05 minta sidebar Jasa & Asset digabung ke bawah menu
     * GMPO Approval — jadi antrian approval gabungan 3 po_type, tiap baris
     * di-route ke controller approval aslinya (jasa/asset) berdasarkan
     * `$po->type`-nya sendiri (lihat views/purchase_order_approval_general/index.php).
     */
    protected function _included_types() {
        return ['non_produksi', 'jasa', 'asset'];
    }
}
