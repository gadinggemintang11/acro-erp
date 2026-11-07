<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Inventory_traceability_model — padanan Material_traceability_model untuk
 * BARANG GENERAL (master `inventories`, item_type 'inventory').
 *
 * User 2026-09-16: "material traceability ini hanya untuk item type material
 * ya, untuk type item general seperti Master_inventory?" -> modul itu memang
 * terkunci ke tabel `materials`; barang general punya alur dokumen sendiri:
 *
 *   Master General Item
 *     -> IRF (consumable_request_details)
 *     -> PR  (pr_details)            <- sisa IRF yang stoknya kurang
 *     -> PO  (po_details)
 *     -> Penerimaan SGR (sgr_details)   <- BUKAN GRN; GRN dipakai material
 *     -> Pengeluaran barang (consumable_out_details)
 *     -> Kartu stok (stock_ledger)
 *
 * Catatan data yang menentukan bentuk query di sini:
 * - `stock_on_hand` TIDAK dipakai barang general (kosong) -> stok berjalan
 *   dibaca dari saldo terakhir `stock_ledger`, dipecah per kategori
 *   (General/Produksi) karena gudangnya memang terpisah.
 * - Yang benar-benar memotong/menambah stok hanya dokumen berstatus
 *   Confirmed/Completed; dokumen lain tetap ditampilkan (dengan statusnya)
 *   supaya jejaknya utuh, tapi TIDAK ikut dihitung sebagai qty terpakai.
 */
class Inventory_traceability_model extends CI_Model {

    /* ===== MASTER + INDEX LIST ===== */

    public function get_item($id) {
        return $this->db->get_where('inventories', ['id' => (int) $id])->row();
    }

    public function get_list_paginated($limit, $offset, $keyword = null) {
        $this->db->select("
            i.id, i.kode_barang, i.nama_barang, i.kategori, i.item_type, i.satuan, i.merk, i.status,
            (SELECT COUNT(*) FROM consumable_request_details crd WHERE crd.item_type='inventory' AND crd.item_id = i.id) AS cnt_irf,
            (SELECT COUNT(*) FROM pr_details prd WHERE prd.item_type='inventory' AND prd.item_id = i.id) AS cnt_pr,
            (SELECT COUNT(*) FROM po_details pod WHERE pod.item_type='inventory' AND pod.item_id = i.id) AS cnt_po,
            (SELECT COUNT(*) FROM sgr_details sd WHERE sd.item_type='inventory' AND sd.item_id = i.id) AS cnt_sgr,
            (SELECT COUNT(*) FROM consumable_out_details cod WHERE cod.item_type='inventory' AND cod.item_id = i.id) AS cnt_out
        ", FALSE);
        $this->db->from('inventories i');
        $this->_apply_keyword($keyword);
        $this->db->order_by('i.kode_barang', 'ASC');
        $this->db->limit((int) $limit, (int) $offset);
        return $this->db->get()->result();
    }

    public function count_list($keyword = null) {
        $this->db->from('inventories i');
        $this->_apply_keyword($keyword);
        return (int) $this->db->count_all_results();
    }

    private function _apply_keyword($keyword) {
        $keyword = trim((string) $keyword);
        if ($keyword === '') return;
        $this->db->group_start()
            ->like('i.kode_barang', $keyword)
            ->or_like('i.nama_barang', $keyword)
            ->or_like('i.kategori', $keyword)
            ->or_like('i.merk', $keyword)
        ->group_end();
    }

    /* ===== RINGKASAN ===== */

    public function summary($item_id) {
        $id = (int) $item_id;

        $irf = $this->db->query("
            SELECT COUNT(*) AS docs, COALESCE(SUM(d.qty_requested),0) AS qty
            FROM consumable_request_details d
            INNER JOIN consumable_request_header h ON h.id = d.header_id
            WHERE d.item_type='inventory' AND d.item_id = ? AND h.status NOT IN ('Rejected','Cancelled')
        ", [$id])->row();

        $pr  = $this->db->query("SELECT COUNT(*) AS docs, COALESCE(SUM(qty),0) AS qty FROM pr_details WHERE item_type='inventory' AND item_id = ?", [$id])->row();
        $po  = $this->db->query("SELECT COUNT(*) AS docs, COALESCE(SUM(qty),0) AS qty, COALESCE(SUM(total_local),0) AS value FROM po_details WHERE item_type='inventory' AND item_id = ?", [$id])->row();

        // Penerimaan: hanya SGR Completed yang benar-benar menambah stok.
        $sgr = $this->db->query("
            SELECT COUNT(*) AS docs, COALESCE(SUM(d.receive_qty),0) AS qty, COALESCE(SUM(d.total_value),0) AS value
            FROM sgr_details d
            INNER JOIN sgr_header h ON h.id = d.sgr_header_id
            WHERE d.item_type='inventory' AND d.item_id = ? AND h.status = 'Completed'
        ", [$id])->row();

        // Pengeluaran: hanya dokumen Confirmed yang memotong stok.
        $out = $this->db->query("
            SELECT COUNT(*) AS docs, COALESCE(SUM(d.qty_issued),0) AS qty
            FROM consumable_out_details d
            INNER JOIN consumable_out_header h ON h.id = d.header_id
            WHERE d.item_type='inventory' AND d.item_id = ? AND h.status = 'Confirmed'
        ", [$id])->row();

        return [
            'irf'   => $irf,
            'pr'    => $pr,
            'po'    => $po,
            'sgr'   => $sgr,
            'out'   => $out,
            'stock' => $this->stock_per_category($id),
        ];
    }

    /**
     * Saldo stok berjalan per kategori gudang. Dibaca dari baris TERAKHIR
     * stock_ledger tiap kategori (pola yang sama dengan Stock_ledger_model::
     * get_last_balance()) -- barang general tidak memakai stock_on_hand.
     */
    public function stock_per_category($item_id) {
        $rows = $this->db->query("
            SELECT l.category, l.balance, l.transaction_date
            FROM stock_ledger l
            INNER JOIN (
                SELECT category, MAX(id) AS last_id
                FROM stock_ledger
                WHERE item_type='inventory' AND item_id = ?
                GROUP BY category
            ) x ON x.last_id = l.id
            ORDER BY l.category ASC
        ", [(int) $item_id])->result();

        $total = 0.0;
        foreach ($rows as $r) { $total += (float) $r->balance; }
        return ['per_category' => $rows, 'total' => $total];
    }

    /* ===== IRF ===== */

    public function irf_list($item_id) {
        return $this->db->query("
            SELECT
                h.id AS header_id, h.request_number, h.request_date, h.department, h.category,
                h.requestor_name, h.purpose, h.status, h.approval_route,
                d.id AS detail_id, d.qty_requested, d.unit_price, d.notes
            FROM consumable_request_details d
            INNER JOIN consumable_request_header h ON h.id = d.header_id
            WHERE d.item_type='inventory' AND d.item_id = ?
            ORDER BY h.request_date DESC, h.id DESC
        ", [(int) $item_id])->result();
    }

    public function irf_detail($header_id) {
        $header = $this->db->query("
            SELECT h.*, u.fullname AS created_by_name
            FROM consumable_request_header h
            LEFT JOIN users u ON u.id = h.created_by
            WHERE h.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $items = $this->db->query("
            SELECT d.*, " . $this->_item_name_sql('d') . " AS item_display
            FROM consumable_request_details d
            WHERE d.header_id = ?
            ORDER BY d.id ASC
        ", [(int) $header_id])->result();

        // Pengeluaran yang memenuhi IRF ini (jejak lanjutannya).
        $issuances = $this->db->query("
            SELECT h.id, h.doc_number, h.issuance_date, h.status, h.recipient
            FROM consumable_out_header h
            WHERE h.request_id = ?
            ORDER BY h.issuance_date DESC, h.id DESC
        ", [(int) $header_id])->result();

        return ['header' => $header, 'items' => $items, 'issuances' => $issuances];
    }

    /* ===== PR ===== */

    public function pr_list($item_id) {
        return $this->db->query("
            SELECT
                h.id AS header_id, h.pr_number, h.pr_date, h.department, h.type, h.purpose, h.status,
                u.fullname AS requester_name,
                d.id AS detail_id, d.qty, d.description, d.remarks
            FROM pr_details d
            INNER JOIN pr_header h ON h.id = d.pr_header_id
            LEFT JOIN users u ON u.id = h.requester_id
            WHERE d.item_type='inventory' AND d.item_id = ?
            ORDER BY h.pr_date DESC, h.id DESC
        ", [(int) $item_id])->result();
    }

    public function pr_detail($header_id) {
        $header = $this->db->query("
            SELECT h.*, u.fullname AS requester_name
            FROM pr_header h
            LEFT JOIN users u ON u.id = h.requester_id
            WHERE h.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $items = $this->db->query("
            SELECT d.*, " . $this->_item_name_sql('d') . " AS item_display
            FROM pr_details d
            WHERE d.pr_header_id = ?
            ORDER BY d.id ASC
        ", [(int) $header_id])->result();

        $pos = $this->db->query("
            SELECT DISTINCT ph.id, ph.po_number, ph.po_date, ph.status
            FROM po_details pd
            INNER JOIN po_header ph ON ph.id = pd.po_header_id
            WHERE pd.pr_header_id = ? OR pd.pr_detail_id IN (SELECT id FROM pr_details WHERE pr_header_id = ?)
            ORDER BY ph.po_date DESC, ph.id DESC
        ", [(int) $header_id, (int) $header_id])->result();

        // IRF asal baris PR ini (PR barang general selalu lahir dari IRF).
        $irfs = $this->db->query("
            SELECT DISTINCT crh.id, crh.request_number, crh.request_date, crh.status
            FROM pr_detail_cr_links l
            INNER JOIN pr_details pd ON pd.id = l.pr_detail_id
            INNER JOIN consumable_request_details crd ON crd.id = l.cr_detail_id
            INNER JOIN consumable_request_header crh ON crh.id = crd.header_id
            WHERE pd.pr_header_id = ?
            ORDER BY crh.request_date DESC, crh.id DESC
        ", [(int) $header_id])->result();

        return ['header' => $header, 'items' => $items, 'pos' => $pos, 'irfs' => $irfs];
    }

    /* ===== PO ===== */

    public function po_list($item_id) {
        return $this->db->query("
            SELECT
                h.id AS header_id, h.po_number, h.po_date, h.type, h.status, h.currency_code, h.exchange_rate,
                s.nama_supplier AS supplier_name,
                d.id AS detail_id, d.qty, d.base_price, d.local_price, d.total_local, d.description
            FROM po_details d
            INNER JOIN po_header h ON h.id = d.po_header_id
            LEFT JOIN suppliers s ON s.id = h.supplier_id
            WHERE d.item_type='inventory' AND d.item_id = ?
            ORDER BY h.po_date DESC, h.id DESC
        ", [(int) $item_id])->result();
    }

    public function po_detail($header_id) {
        $header = $this->db->query("
            SELECT h.*, s.nama_supplier AS supplier_name
            FROM po_header h
            LEFT JOIN suppliers s ON s.id = h.supplier_id
            WHERE h.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $items = $this->db->query("
            SELECT d.*, " . $this->_item_name_sql('d') . " AS item_display
            FROM po_details d
            WHERE d.po_header_id = ?
            ORDER BY d.id ASC
        ", [(int) $header_id])->result();

        $receipts = $this->db->query("
            SELECT DISTINCT sh.id, sh.sgr_number, sh.sgr_date, sh.status, sh.warehouse_type
            FROM sgr_details sd
            INNER JOIN sgr_header sh ON sh.id = sd.sgr_header_id
            WHERE sd.source_doc_type = 'PO' AND sd.source_doc_header_id = ?
            ORDER BY sh.sgr_date DESC, sh.id DESC
        ", [(int) $header_id])->result();

        return ['header' => $header, 'items' => $items, 'receipts' => $receipts];
    }

    /* ===== PENERIMAAN (SGR) ===== */

    public function sgr_list($item_id) {
        return $this->db->query("
            SELECT
                h.id AS header_id, h.sgr_number, h.sgr_date, h.base_on, h.status, h.warehouse_type, h.category,
                s.nama_supplier AS supplier_name,
                d.id AS detail_id, d.receive_qty, d.unit_value, d.total_value,
                d.source_doc_type, d.source_doc_header_id,
                ph.po_number
            FROM sgr_details d
            INNER JOIN sgr_header h ON h.id = d.sgr_header_id
            LEFT JOIN suppliers s ON s.id = h.supplier_id
            LEFT JOIN po_header ph ON ph.id = d.source_doc_header_id AND d.source_doc_type = 'PO'
            WHERE d.item_type='inventory' AND d.item_id = ?
            ORDER BY h.sgr_date DESC, h.id DESC
        ", [(int) $item_id])->result();
    }

    public function sgr_detail($header_id) {
        $header = $this->db->query("
            SELECT h.*, s.nama_supplier AS supplier_name
            FROM sgr_header h
            LEFT JOIN suppliers s ON s.id = h.supplier_id
            WHERE h.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $items = $this->db->query("
            SELECT d.*, " . $this->_item_name_sql('d') . " AS item_display, ph.po_number
            FROM sgr_details d
            LEFT JOIN po_header ph ON ph.id = d.source_doc_header_id AND d.source_doc_type = 'PO'
            WHERE d.sgr_header_id = ?
            ORDER BY d.id ASC
        ", [(int) $header_id])->result();

        return ['header' => $header, 'items' => $items];
    }

    /* ===== PENGELUARAN BARANG ===== */

    public function out_list($item_id) {
        return $this->db->query("
            SELECT
                h.id AS header_id, h.doc_number, h.issuance_date, h.department, h.category,
                h.recipient, h.purpose, h.status, h.request_id,
                crh.request_number,
                d.id AS detail_id, d.qty_issued, d.notes
            FROM consumable_out_details d
            INNER JOIN consumable_out_header h ON h.id = d.header_id
            LEFT JOIN consumable_request_header crh ON crh.id = h.request_id
            WHERE d.item_type='inventory' AND d.item_id = ?
            ORDER BY h.issuance_date DESC, h.id DESC
        ", [(int) $item_id])->result();
    }

    public function out_detail($header_id) {
        $header = $this->db->query("
            SELECT h.*, crh.request_number, u.fullname AS created_by_name
            FROM consumable_out_header h
            LEFT JOIN consumable_request_header crh ON crh.id = h.request_id
            LEFT JOIN users u ON u.id = h.created_by
            WHERE h.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $items = $this->db->query("
            SELECT d.* FROM consumable_out_details d WHERE d.header_id = ? ORDER BY d.id ASC
        ", [(int) $header_id])->result();

        return ['header' => $header, 'items' => $items];
    }

    /* ===== KARTU STOK ===== */

    public function ledger($item_id, $limit = 300) {
        return $this->db->query("
            SELECT id, transaction_date, transaction_type, category, source_doc_type, source_doc_id,
                   qty_in, qty_out, balance, unit_value
            FROM stock_ledger
            WHERE item_type='inventory' AND item_id = ?
            ORDER BY transaction_date DESC, id DESC
            LIMIT ?
        ", [(int) $item_id, (int) $limit])->result();
    }

    /**
     * Nama barang untuk baris dokumen yang isinya bisa campur beberapa master
     * (PR/PO/SGR/IRF memuat inventory, material, mesin, asset, jasa).
     */
    private function _item_name_sql($alias) {
        return "CASE {$alias}.item_type
                    WHEN 'inventory' THEN (SELECT CONCAT(x.kode_barang, ' - ', x.nama_barang) FROM inventories x WHERE x.id = {$alias}.item_id)
                    WHEN 'material'  THEN (SELECT CONCAT(x.kode_material, ' - ', x.nama_material) FROM materials x WHERE x.id = {$alias}.item_id)
                    WHEN 'machine'   THEN (SELECT CONCAT(x.kode_mesin, ' - ', x.nama_mesin) FROM machines x WHERE x.id = {$alias}.item_id)
                    WHEN 'asset'     THEN (SELECT CONCAT(x.kode_asset, ' - ', x.nama_asset) FROM assets x WHERE x.id = {$alias}.item_id)
                    WHEN 'jasa'      THEN (SELECT CONCAT(x.kode_jasa, ' - ', x.nama_jasa) FROM jasa x WHERE x.id = {$alias}.item_id)
                    ELSE NULL
                END";
    }
}
