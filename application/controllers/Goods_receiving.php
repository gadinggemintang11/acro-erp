<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Goods_receiving — halaman chooser "Pemasukan" (ERP Revision Notes
 * 2026-09-02, Fase 2 poin 10; konsep di [[erp_revision_sidebar_consolidation_pattern]]).
 *
 * BUKAN modul baru — cuma 1 titik masuk sidebar yang menampilkan beberapa
 * kotak (tile) ke modul-modul yang SUDAH ADA (SGR/RIF General & Produksi,
 * Warehouse In/RMF). Modul di baliknya TIDAK berubah sama sekali.
 *
 * Tidak ada menu_key/permission sendiri utk halaman ini — visibilitas
 * tiap tile & link sidebar-nya murni union dari permission modul tujuan
 * masing-masing (pola sama seperti flag $hasGenWarehouse dkk di sidebar.php).
 */
class Goods_receiving extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
    }

    public function index() {
        $tiles = [
            [
                'title'    => 'General Warehouse ',
                'subtitle' => 'Receiving Item Form ',
                'icon'     => 'fa-dolly-flatbed',
                'color'    => 'primary',
                'url'      => 'supplier_general_item_receipt/index/general',
                'can'      => check_access('supplier_general_item_receipt_general', 'view') || check_access('supplier_general_item_receipt', 'view'),
            ],
            [
                'title'    => 'Production Warehouse',
                'subtitle' => 'Receiving Item Form',
                'icon'     => 'fa-dolly-flatbed',
                'color'    => 'dark',
                'url'      => 'supplier_general_item_receipt/index/produksi',
                'can'      => check_access('supplier_general_item_receipt_produksi', 'view') || check_access('supplier_general_item_receipt', 'view'),
            ],
            [
                'title'    => 'Production Warehouse',
                'subtitle' => 'Receiving Material Form',
                'icon'     => 'fa-arrow-down',
                'color'    => 'success',
                'url'      => 'warehouse_in',
                'can'      => check_access('warehouse_in', 'view'),
            ],
            [
                'title'    => 'Lainnya',
                'subtitle' => 'Receiving Assets Form)',
                'icon'     => 'fa-box',
                'color'    => 'secondary',
                'url'      => 'supplier_general_item_receipt',
                'can'      => check_access('supplier_general_item_receipt', 'view'),
            ],
        ];
        $tiles = array_values(array_filter($tiles, function ($t) { return $t['can']; }));

        if (empty($tiles)) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
            return;
        }

        $data = [
            'title' => 'Pemasukan Barang',
            'tiles' => $tiles,
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('goods_chooser/receiving', $data);
        $this->load->view('templates/footer');
    }
}
