<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'controllers/Purchase_order.php';

/**
 * PO Non-Produksi (General) — SUBCLASS TIPIS dari `Purchase_order` (lihat
 * docblock lengkap di sana utk penjelasan pola konsolidasi ini, user
 * 2026-09-05). Cuma override properti/hook yg beda dari tipe Produksi —
 * SEMUA logic (index/detail/CRUD/status/export/dst) diwarisi apa adanya
 * dari class dasar. URL (`purchase_order_general/...`), menu sidebar, dan
 * hak akses Role Access TIDAK BERUBAH sama sekali dari sebelumnya.
 */
class Purchase_order_general extends Purchase_order {

    protected $po_type = 'non_produksi';
    protected $menu_key = 'purchase_order_general';
    protected $approval_menu_key = 'purchase_order_approval_general';
    // Sebelumnya SALAH tertulis 'Purchase Order Jasa' (sisa copy-paste dari
    // Purchase_order_jasa) -- diperbaiki sekalian pas konsolidasi ini.
    protected $module_label = 'Purchase Order Non Produksi';

    // Item WAJIB ditarik dari PR -- tidak boleh input item manual tak berdasar
    // (user 2026-09-09). Gate lengkapnya di Purchase_order::$allow_manual_items.
    protected $allow_manual_items = false;

    protected function _title_index() {
        return lang_line('title_po_index_general', 'Purchase Order Non Produksi');
    }

    protected function _title_detail() {
        return lang_line('title_po_detail_general', 'Detail Purchase Order');
    }

    /**
     * User 2026-09-05 minta sidebar Jasa & Asset digabung ke bawah menu
     * GMPO/"GENERAL MATERIAL" — jadi halaman list GMPO ini menampilkan
     * gabungan 3 po_type (non_produksi/jasa/asset), TIDAK cuma non_produksi
     * lagi. Tiap baris tetap "milik" controller aslinya (link Detail/Delete
     * di view di-route ke purchase_order_general/_jasa/_asset sesuai
     * `$po->type` per baris — lihat views/purchase_order_general/index.php).
     */
    protected function _included_types() {
        return ['non_produksi', 'jasa', 'asset'];
    }

    /**
     * Form "Buat Header GMPO" — halaman TERSENDIRI (bukan modal di atas
     * list lagi), user 2026-09-05: "HARUSNYA SAAT KLIK BUAT GMPO LANGSUNG
     * MUNCUL FORM" — pola sama spt IRF/PRF/ERF/MRF (consumable_request/add,
     * prf/create, material_request/add). Py dropdown "Kategori"
     * (General/Jasa/Asset) — JS di view ganti `action` form ke
     * add_header() controller yang sesuai, KETIGANYA menerima field POST
     * yang identik jadi TIDAK PERLU endpoint baru sama sekali.
     */
    public function add() {
        if (!check_access($this->menu_key, 'add')) { show_error('Izin Ditolak.', 403); }

        $data = [
            'title'          => lang_line('po_create_new', 'Buat Header Purchase Order'),
            'suppliers'      => $this->Supplier_model->get_all(['status' => 'active']),
            'shipping_modes' => $this->Commoncode_model->get_by_group('SHIPPING_MODE'),
            'payment_modes'  => $this->Commoncode_model->get_by_group('PAYMENT_MODE'),
            'currencies'     => $this->Commoncode_model->get_by_group('CURRENCY'),
            // Sama utk ketiga kategori (General/Jasa/Asset) -- semuanya
            // wajib tarik dari PR, jadi baris input item manual di form ini
            // tidak dirender sama sekali.
            'allow_manual_items' => $this->allow_manual_items,
        ];

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('purchase_order_general/add', $data);
        $this->load->view('templates/footer');
    }
}
