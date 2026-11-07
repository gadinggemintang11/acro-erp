<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Goods_issuance — halaman chooser "Pengeluaran" (ERP Revision Notes
 * 2026-09-02, Fase 2 poin 10; konsep di [[erp_revision_sidebar_consolidation_pattern]]).
 *
 * BUKAN modul baru — cuma 1 titik masuk sidebar yang menampilkan beberapa
 * kotak (tile) ke modul-modul yang SUDAH ADA (Consumable Out General &
 * Produksi, Material Issuance). Modul di baliknya TIDAK berubah sama sekali.
 *
 * Label tile di-rename 2026-09-06 atas permintaan user jadi "Item Release -
 * General/Produksi" & "Material Release" — menyesuaikan nama resmi yang
 * memang sudah dipakai di tabel `menus` ("Stock Item Release", "Production
 * Material Release") & daftar 9 nama form. URL/menu_key/permission TIDAK
 * berubah, cuma teks yang tampil.
 *
 * Tidak ada menu_key/permission sendiri utk halaman ini — visibilitas
 * tiap tile & link sidebar-nya murni union dari permission modul tujuan
 * masing-masing.
 */
class Goods_issuance extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
    }

    public function index() {
        $tiles = [
            [
                'title'    => 'General Warehouse',
                'subtitle' => 'Pengeluaran barang habis pakai',
                'icon'     => 'fa-box-open',
                'color'    => 'primary',
                'url'      => 'consumable_out/index/general',
                'can'      => check_access('consumable_out_general', 'view') || check_access('consumable_out', 'view'),
            ],
            [
                'title'    => 'Production Warehouse',
                'subtitle' => 'Pengeluaran barang habis pakai (Production)',
                'icon'     => 'fa-box-open',
                'color'    => 'dark',
                'url'      => 'consumable_out/index/produksi',
                'can'      => check_access('consumable_out_produksi', 'view') || check_access('consumable_out', 'view'),
            ],
            [
                'title'    => 'Material Release',
                'subtitle' => 'Pengeluaran material produksi (FIFO, dari MR)',
                'icon'     => 'fa-dolly',
                'color'    => 'success',
                'url'      => 'material_issuance',
                'can'      => check_access('material_issuance', 'view'),
            ],
        ];
        $tiles = array_values(array_filter($tiles, function ($t) { return $t['can']; }));

        if (empty($tiles)) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
            return;
        }

        $data = [
            'title' => 'Pengeluaran Barang',
            'tiles' => $tiles,
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('goods_chooser/issuance', $data);
        $this->load->view('templates/footer');
    }
}
