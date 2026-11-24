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
 *
 * Redesign 2026-11-09 (user: "ini juga"): tiap tile menampilkan pekerjaan
 * yang menunggu -- jumlah PO disetujui yang barangnya belum diterima penuh
 * dan dokumen penerimaan yang masih Draft -- supaya gudang langsung tahu
 * harus ke mana. Hitungan "belum diterima" memakai semua dokumen penerimaan
 * (termasuk Draft), sama dengan sisa yang ditawarkan form penerimaannya.
 */
class Goods_receiving extends MY_Controller {

    public function __construct() {
        parent::__construct();
        check_login();
    }

    public function index() {
        $tiles = [
            [
                'key'      => 'sgr_general',
                'title'    => lang_line('gr2_t_general', 'General Warehouse'),
                'subtitle' => lang_line('gr2_s_rif', 'Receiving Item Form'),
                'desc'     => lang_line('gr2_d_general', 'Penerimaan barang general (mesin & inventaris) untuk gudang umum.'),
                'icon'     => 'fa-dolly-flatbed',
                'tone'     => 'tone-blue',
                'url'      => 'supplier_general_item_receipt/index/general',
                'can'      => check_access('supplier_general_item_receipt_general', 'view') || check_access('supplier_general_item_receipt', 'view'),
            ],
            [
                'key'      => 'sgr_produksi',
                'title'    => lang_line('gr2_t_production', 'Production Warehouse'),
                'subtitle' => lang_line('gr2_s_rif', 'Receiving Item Form'),
                'desc'     => lang_line('gr2_d_production_item', 'Penerimaan barang general (mesin & inventaris) untuk kebutuhan produksi.'),
                'icon'     => 'fa-dolly-flatbed',
                'tone'     => 'tone-slate',
                'url'      => 'supplier_general_item_receipt/index/produksi',
                'can'      => check_access('supplier_general_item_receipt_produksi', 'view') || check_access('supplier_general_item_receipt', 'view'),
            ],
            [
                'key'      => 'grn',
                'title'    => lang_line('gr2_t_production', 'Production Warehouse'),
                'subtitle' => lang_line('gr2_s_rmf', 'Receiving Material Form'),
                'desc'     => lang_line('gr2_d_material', 'Penerimaan material produksi (GRN). Setelah dikonfirmasi lanjut ke Quality Assurance sebelum masuk stok.'),
                'icon'     => 'fa-boxes',
                'tone'     => 'tone-green',
                'url'      => 'warehouse_in',
                'can'      => check_access('warehouse_in', 'view'),
            ],
            [
                'key'      => 'sgr_all',
                'title'    => lang_line('gr2_t_other', 'Lainnya'),
                'subtitle' => lang_line('gr2_s_other', 'Receiving Assets Form'),
                'desc'     => lang_line('gr2_d_other', 'Semua Receiving Item Form, termasuk kategori Lainnya.'),
                'icon'     => 'fa-box',
                'tone'     => 'tone-amber',
                'url'      => 'supplier_general_item_receipt',
                'can'      => check_access('supplier_general_item_receipt', 'view'),
            ],
        ];
        $tiles = array_values(array_filter($tiles, function ($t) { return $t['can']; }));

        if (empty($tiles)) {
            show_error('Anda tidak memiliki izin untuk melihat modul ini.', 403);
            return;
        }

        $stats = $this->_stats();
        foreach ($tiles as &$t) {
            $t['stats'] = $stats[$t['key']] ?? ['waiting' => 0, 'draft' => 0];
        }
        unset($t);

        $data = [
            'title'   => lang_line('gc_receiving_title', 'Pemasukan Barang'),
            'tiles'   => $tiles,
            // Panduan: Jasa & Aset tidak lewat tile di atas, melainkan dari halaman PO-nya.
            'can_po_general' => check_access('purchase_order_general', 'view'),
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('goods_chooser/receiving', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Pekerjaan menunggu per tile:
     *   waiting = PO Confirmed yang masih punya baris belum diterima penuh
     *             (material/semifinished utk GRN; mesin/inventaris utk RIF,
     *             disaring kategori baris PO bila diisi)
     *   draft   = dokumen penerimaan berstatus Draft
     */
    private function _stats() {
        $db = $this->db;
        $remaining_po = function ($item_types, $doc_table, $category = null) use ($db) {
            $types = "'" . implode("','", $item_types) . "'";
            $cat_sql = $category ? " AND (pd.category IS NULL OR pd.category = " . $db->escape($category) . ")" : '';
            $sql = "SELECT COUNT(DISTINCT pd.po_header_id) AS n
                    FROM po_details pd
                    JOIN po_header ph ON ph.id = pd.po_header_id AND ph.status = 'Confirmed'
                    LEFT JOIN (SELECT source_doc_detail_id AS did, SUM(receive_qty) AS q
                               FROM {$doc_table} WHERE source_doc_type = 'PO' GROUP BY source_doc_detail_id) r ON r.did = pd.id
                    WHERE pd.item_type IN ({$types}){$cat_sql}
                      AND COALESCE(r.q, 0) < pd.qty - 0.0001";
            $row = $db->query($sql)->row();
            return (int) ($row->n ?? 0);
        };
        $drafts = function ($table, $category = null) use ($db) {
            $db->from($table)->where('status', 'Draft');
            if ($category) $db->where('category', $category);
            return (int) $db->count_all_results();
        };

        return [
            'grn'          => ['waiting' => $remaining_po(['material', 'semifinished'], 'grn_details'), 'draft' => $drafts('grn_header')],
            'sgr_general'  => ['waiting' => $remaining_po(['machine', 'inventory'], 'sgr_details', 'General'), 'draft' => $drafts('sgr_header', 'General')],
            'sgr_produksi' => ['waiting' => $remaining_po(['machine', 'inventory'], 'sgr_details', 'Produksi'), 'draft' => $drafts('sgr_header', 'Produksi')],
            'sgr_all'      => ['waiting' => $remaining_po(['machine', 'inventory'], 'sgr_details'), 'draft' => $drafts('sgr_header')],
        ];
    }
}
