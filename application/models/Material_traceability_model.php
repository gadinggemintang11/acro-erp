<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Material_traceability_model
 * Cross-module traceability for a single material:
 * Master -> PR -> PO -> GRN -> MR/Issuance -> Production (input + output FG/SFG) -> FG Receipt -> Stock Ledger
 */
class Material_traceability_model extends CI_Model {

    /* ===== MASTER + INDEX LIST ===== */

    public function get_material($id) {
        return $this->db->get_where('materials', ['id' => (int) $id])->row();
    }

    public function get_list_paginated($limit, $offset, $keyword = null) {
        $this->db->select("
            m.id, m.kode_material, m.nama_material, m.kategori, m.kategorirm, m.satuan, m.asal,
            (SELECT COUNT(*) FROM pr_details prd WHERE prd.item_type='material' AND prd.item_id = m.id) AS cnt_pr,
            (SELECT COUNT(*) FROM po_details pod WHERE pod.item_type='material' AND pod.item_id = m.id) AS cnt_po,
            (SELECT COUNT(*) FROM grn_details grd WHERE grd.item_type='material' AND grd.item_id = m.id) AS cnt_grn,
            (SELECT COUNT(*) FROM mr_details mrd WHERE mrd.component_item_type='material' AND mrd.component_item_id = m.id) AS cnt_mr,
            (SELECT COUNT(DISTINCT pin.production_header_id) FROM production_inputs pin WHERE pin.item_type='material' AND pin.item_id = m.id) AS cnt_prod
        ", false);
        $this->db->from('materials m');
        if ($keyword) {
            $this->db->group_start()
                ->like('m.kode_material', $keyword)
                ->or_like('m.nama_material', $keyword)
                ->or_like('m.kategori', $keyword)
                ->or_like('m.kategorirm', $keyword)
            ->group_end();
        }
        $this->db->order_by('m.kode_material', 'ASC');
        return $this->db->get('', $limit, $offset)->result();
    }

    public function count_list($keyword = null) {
        $this->db->from('materials m');
        if ($keyword) {
            $this->db->group_start()
                ->like('m.kode_material', $keyword)
                ->or_like('m.nama_material', $keyword)
                ->or_like('m.kategori', $keyword)
                ->or_like('m.kategorirm', $keyword)
            ->group_end();
        }
        return $this->db->count_all_results();
    }

    /* ===== SUMMARY (cards) ===== */

    public function summary($material_id) {
        $mid = (int) $material_id;

        $pr   = $this->db->query("SELECT COUNT(*) AS docs, COALESCE(SUM(qty),0) AS qty FROM pr_details WHERE item_type='material' AND item_id = ?", [$mid])->row();
        $po   = $this->db->query("SELECT COUNT(*) AS docs, COALESCE(SUM(qty),0) AS qty, COALESCE(SUM(total_local),0) AS value FROM po_details WHERE item_type='material' AND item_id = ?", [$mid])->row();
        $grn  = $this->db->query("SELECT COUNT(*) AS docs, COALESCE(SUM(receive_qty),0) AS qty, COALESCE(SUM(total_value),0) AS value FROM grn_details WHERE item_type='material' AND item_id = ?", [$mid])->row();
        $mr   = $this->db->query("SELECT COUNT(*) AS docs, COALESCE(SUM(requested_qty),0) AS qty_req, COALESCE(SUM(issued_qty),0) AS qty_issued FROM mr_details WHERE component_item_type='material' AND component_item_id = ?", [$mid])->row();
        $prod = $this->db->query("SELECT COUNT(DISTINCT production_header_id) AS docs, COALESCE(SUM(qty_used),0) AS qty FROM production_inputs WHERE item_type='material' AND item_id = ?", [$mid])->row();
        $stock = $this->db->query("SELECT COALESCE(SUM(available_qty),0) AS on_hand FROM stock_on_hand WHERE item_type='material' AND item_id = ?", [$mid])->row();

        return compact('pr','po','grn','mr','prod','stock');
    }

    /* ===== PR ===== */

    public function pr_list($material_id) {
        return $this->db->query("
            SELECT
                h.id AS header_id, h.pr_number, h.pr_date, h.department, h.purpose, h.status,
                u.fullname AS requester_name,
                d.id AS detail_id, d.qty, d.description, d.remarks
            FROM pr_details d
            INNER JOIN pr_header h ON h.id = d.pr_header_id
            LEFT JOIN users u ON u.id = h.requester_id
            WHERE d.item_type='material' AND d.item_id = ?
            ORDER BY h.pr_date DESC, h.id DESC
        ", [(int) $material_id])->result();
    }

    public function pr_detail($header_id) {
        $header = $this->db->query("
            SELECT h.*, u.fullname AS requester_name, ua.fullname AS approver_name
            FROM pr_header h
            LEFT JOIN users u ON u.id = h.requester_id
            LEFT JOIN users ua ON ua.id = h.approved_by
            WHERE h.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $items = $this->db->query("
            SELECT d.*,
                CASE d.item_type
                    WHEN 'material' THEN m.kode_material
                    WHEN 'inventory' THEN inv.kode_barang
                    WHEN 'machine' THEN mc.kode_mesin
                END AS item_code,
                CASE d.item_type
                    WHEN 'material' THEN m.nama_material
                    WHEN 'inventory' THEN inv.nama_barang
                    WHEN 'machine' THEN mc.nama_mesin
                END AS item_name,
                CASE d.item_type
                    WHEN 'material' THEN m.satuan
                    WHEN 'inventory' THEN inv.satuan
                END AS satuan
            FROM pr_details d
            LEFT JOIN materials m ON m.id = d.item_id AND d.item_type='material'
            LEFT JOIN inventories inv ON inv.id = d.item_id AND d.item_type='inventory'
            LEFT JOIN machines mc ON mc.id = d.item_id AND d.item_type='machine'
            WHERE d.pr_header_id = ?
            ORDER BY d.id ASC
        ", [(int) $header_id])->result();

        return ['header' => $header, 'items' => $items];
    }

    /* ===== PO ===== */

    public function po_list($material_id) {
        return $this->db->query("
            SELECT
                h.id AS header_id, h.po_number, h.po_date, h.status, h.currency_code,
                s.nama_supplier AS supplier_name,
                d.id AS detail_id, d.qty, d.base_price, d.local_price, d.total_local
            FROM po_details d
            INNER JOIN po_header h ON h.id = d.po_header_id
            LEFT JOIN suppliers s ON s.id = h.supplier_id
            WHERE d.item_type='material' AND d.item_id = ?
            ORDER BY h.po_date DESC, h.id DESC
        ", [(int) $material_id])->result();
    }

    public function po_detail($header_id) {
        $header = $this->db->query("
            SELECT h.*, s.nama_supplier AS supplier_name, s.alamat AS supplier_address
            FROM po_header h
            LEFT JOIN suppliers s ON s.id = h.supplier_id
            WHERE h.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $items = $this->db->query("
            SELECT d.*,
                CASE d.item_type
                    WHEN 'material' THEN m.kode_material
                    WHEN 'inventory' THEN inv.kode_barang
                    WHEN 'machine' THEN mc.kode_mesin
                END AS item_code,
                CASE d.item_type
                    WHEN 'material' THEN m.nama_material
                    WHEN 'inventory' THEN inv.nama_barang
                    WHEN 'machine' THEN mc.nama_mesin
                END AS item_name,
                CASE d.item_type
                    WHEN 'material' THEN m.satuan
                    WHEN 'inventory' THEN inv.satuan
                END AS satuan
            FROM po_details d
            LEFT JOIN materials m ON m.id = d.item_id AND d.item_type='material'
            LEFT JOIN inventories inv ON inv.id = d.item_id AND d.item_type='inventory'
            LEFT JOIN machines mc ON mc.id = d.item_id AND d.item_type='machine'
            WHERE d.po_header_id = ?
            ORDER BY d.id ASC
        ", [(int) $header_id])->result();

        $grn = $this->db->query("
            SELECT DISTINCT gh.id, gh.grn_number, gh.grn_date, gh.status
            FROM grn_details gd
            INNER JOIN grn_header gh ON gh.id = gd.grn_header_id
            WHERE gd.source_doc_type = 'PO' AND gd.source_doc_header_id = ?
            ORDER BY gh.grn_date DESC, gh.id DESC
        ", [(int) $header_id])->result();

        return ['header' => $header, 'items' => $items, 'grn_receipts' => $grn];
    }

    /* ===== GRN ===== */

    public function grn_list($material_id) {
        return $this->db->query("
            SELECT
                h.id AS header_id, h.grn_number, h.grn_date, h.base_on, h.status, h.warehouse_type,
                s.nama_supplier AS supplier_name,
                d.id AS detail_id, d.receive_qty, d.unit_value, d.total_value,
                d.source_doc_type, d.source_doc_header_id,
                soh.id AS stock_batch_id, soh.available_qty AS batch_remaining, soh.initial_qty AS batch_initial,
                ph.po_number
            FROM grn_details d
            INNER JOIN grn_header h ON h.id = d.grn_header_id
            LEFT JOIN suppliers s ON s.id = h.supplier_id
            LEFT JOIN stock_on_hand soh ON soh.grn_detail_id = d.id
            LEFT JOIN po_header ph ON ph.id = d.source_doc_header_id AND d.source_doc_type='PO'
            WHERE d.item_type='material' AND d.item_id = ?
            ORDER BY h.grn_date DESC, h.id DESC
        ", [(int) $material_id])->result();
    }

    public function grn_detail($header_id) {
        $header = $this->db->query("
            SELECT h.*, s.nama_supplier AS supplier_name
            FROM grn_header h
            LEFT JOIN suppliers s ON s.id = h.supplier_id
            WHERE h.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $items = $this->db->query("
            SELECT d.*,
                m.kode_material, m.nama_material, m.satuan,
                soh.id AS stock_batch_id, soh.available_qty AS batch_remaining, soh.initial_qty AS batch_initial,
                ph.po_number, ph.id AS po_header_id
            FROM grn_details d
            LEFT JOIN materials m ON m.id = d.item_id AND d.item_type='material'
            LEFT JOIN stock_on_hand soh ON soh.grn_detail_id = d.id
            LEFT JOIN po_header ph ON ph.id = d.source_doc_header_id AND d.source_doc_type='PO'
            WHERE d.grn_header_id = ?
            ORDER BY d.id ASC
        ", [(int) $header_id])->result();

        return ['header' => $header, 'items' => $items];
    }

    /* ===== MR / Issuance ===== */

    public function mr_list($material_id) {
        return $this->db->query("
            SELECT
                h.id AS header_id, h.mr_number, h.issuance_number, h.request_date, h.status,
                u.fullname AS requester_name,
                wo.wo_number,
                d.id AS detail_id, d.requested_qty, d.issued_qty, d.unit_measure
            FROM mr_details d
            INNER JOIN mr_header h ON h.id = d.mr_header_id
            LEFT JOIN users u ON u.id = h.requester_id
            LEFT JOIN wo_header wo ON wo.id = d.wo_header_id
            WHERE d.component_item_type='material' AND d.component_item_id = ?
            ORDER BY h.request_date DESC, h.id DESC
        ", [(int) $material_id])->result();
    }

    public function mr_detail($header_id) {
        $header = $this->db->query("
            SELECT h.*,
                u.fullname AS requester_name,
                u2.fullname AS issuer_name,
                u3.fullname AS receiver_name
            FROM mr_header h
            LEFT JOIN users u ON u.id = h.requester_id
            LEFT JOIN users u2 ON u2.id = h.issuer_id
            LEFT JOIN users u3 ON u3.id = h.receiver_id
            WHERE h.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $items = $this->db->query("
            SELECT d.*,
                CASE d.component_item_type
                    WHEN 'material' THEN m.kode_material
                    WHEN 'semifinished' THEN sf.kode_barang
                END AS item_code,
                CASE d.component_item_type
                    WHEN 'material' THEN m.nama_material
                    WHEN 'semifinished' THEN sf.nama_barang
                END AS item_name,
                wo.wo_number,
                fg.kode_barang AS fg_code, fg.nama_barang AS fg_name
            FROM mr_details d
            LEFT JOIN materials m ON m.id = d.component_item_id AND d.component_item_type='material'
            LEFT JOIN semifinished_goods sf ON sf.id = d.component_item_id AND d.component_item_type='semifinished'
            LEFT JOIN wo_header wo ON wo.id = d.wo_header_id
            LEFT JOIN finished_goods fg ON fg.id = wo.fg_id
            WHERE d.mr_header_id = ?
            ORDER BY d.id ASC
        ", [(int) $header_id])->result();

        return ['header' => $header, 'items' => $items];
    }

    /* ===== PRODUCTION ===== */

    public function production_list($material_id) {
        $rows = $this->db->query("
            SELECT
                ph.id AS header_id, ph.production_number, ph.production_date, ph.planned_qty, ph.status,
                wo.id AS wo_header_id, wo.wo_number,
                fg.kode_barang AS fg_code, fg.nama_barang AS fg_name,
                COALESCE(SUM(pin.qty_used),0) AS qty_used
            FROM production_inputs pin
            INNER JOIN production_header ph ON ph.id = pin.production_header_id
            LEFT JOIN wo_header wo ON wo.id = ph.wo_header_id
            LEFT JOIN finished_goods fg ON fg.id = ph.fg_id
            WHERE pin.item_type='material' AND pin.item_id = ?
            GROUP BY ph.id, ph.production_number, ph.production_date, ph.planned_qty, ph.status, wo.id, wo.wo_number, fg.kode_barang, fg.nama_barang
            ORDER BY ph.production_date DESC, ph.id DESC
        ", [(int) $material_id])->result();

        foreach ($rows as $r) {
            $r->outputs = $this->db->query("
                SELECT po.id, po.item_id, po.item_type, po.qty_produced, po.qty_scrap, po.stage_name,
                    CASE po.item_type
                        WHEN 'finished_goods' THEN fg.kode_barang
                        WHEN 'semifinished'   THEN sf.kode_barang
                        WHEN 'scrap'          THEN sc.scrap_code
                    END AS item_code,
                    CASE po.item_type
                        WHEN 'finished_goods' THEN fg.nama_barang
                        WHEN 'semifinished'   THEN sf.nama_barang
                        WHEN 'scrap'          THEN sc.scrap_name
                    END AS item_name
                FROM production_outputs po
                LEFT JOIN finished_goods fg ON fg.id = po.item_id AND po.item_type='finished_goods'
                LEFT JOIN semifinished_goods sf ON sf.id = po.item_id AND po.item_type='semifinished'
                LEFT JOIN master_scrap sc ON sc.id = po.item_id AND po.item_type='scrap'
                WHERE po.production_header_id = ?
                ORDER BY po.id ASC
            ", [(int) $r->header_id])->result();
        }
        return $rows;
    }

    public function production_detail($header_id) {
        $header = $this->db->query("
            SELECT ph.*, wo.wo_number, fg.kode_barang AS fg_code, fg.nama_barang AS fg_name,
                uc.fullname AS created_by_name, ucf.fullname AS confirmed_by_name
            FROM production_header ph
            LEFT JOIN wo_header wo ON wo.id = ph.wo_header_id
            LEFT JOIN finished_goods fg ON fg.id = ph.fg_id
            LEFT JOIN users uc ON uc.id = ph.created_by
            LEFT JOIN users ucf ON ucf.id = ph.confirmed_by
            WHERE ph.id = ?
        ", [(int) $header_id])->row();
        if (!$header) return null;

        $inputs = $this->db->query("
            SELECT pin.*,
                CASE pin.item_type
                    WHEN 'material' THEN m.kode_material
                    WHEN 'semifinished' THEN sf.kode_barang
                END AS item_code,
                CASE pin.item_type
                    WHEN 'material' THEN m.nama_material
                    WHEN 'semifinished' THEN sf.nama_barang
                END AS item_name,
                CASE pin.item_type
                    WHEN 'material' THEN m.satuan
                    WHEN 'semifinished' THEN sf.satuan
                END AS satuan,
                soh.id AS batch_id, gh.grn_number, gh.id AS grn_header_id
            FROM production_inputs pin
            LEFT JOIN materials m ON m.id = pin.item_id AND pin.item_type='material'
            LEFT JOIN semifinished_goods sf ON sf.id = pin.item_id AND pin.item_type='semifinished'
            LEFT JOIN stock_on_hand soh ON soh.id = pin.stock_on_hand_id
            LEFT JOIN grn_header gh ON gh.id = soh.grn_header_id
            WHERE pin.production_header_id = ?
            ORDER BY pin.id ASC
        ", [(int) $header_id])->result();

        $outputs = $this->db->query("
            SELECT po.*,
                CASE po.item_type
                    WHEN 'finished_goods' THEN fg.kode_barang
                    WHEN 'semifinished'   THEN sf.kode_barang
                    WHEN 'scrap'          THEN sc.scrap_code
                END AS item_code,
                CASE po.item_type
                    WHEN 'finished_goods' THEN fg.nama_barang
                    WHEN 'semifinished'   THEN sf.nama_barang
                    WHEN 'scrap'          THEN sc.scrap_name
                END AS item_name
            FROM production_outputs po
            LEFT JOIN finished_goods fg ON fg.id = po.item_id AND po.item_type='finished_goods'
            LEFT JOIN semifinished_goods sf ON sf.id = po.item_id AND po.item_type='semifinished'
            LEFT JOIN master_scrap sc ON sc.id = po.item_id AND po.item_type='scrap'
            WHERE po.production_header_id = ?
            ORDER BY po.id ASC
        ", [(int) $header_id])->result();

        $fg_receipts = $this->db->query("
            SELECT frh.id, frh.receipt_number, frh.receipt_date, frh.status,
                frd.qty_received,
                fg.kode_barang AS fg_code, fg.nama_barang AS fg_name
            FROM fg_receipt_details frd
            INNER JOIN fg_receipt_header frh ON frh.id = frd.fg_receipt_header_id
            LEFT JOIN finished_goods fg ON fg.id = frd.fg_id
            WHERE frh.production_header_id = ?
            ORDER BY frh.receipt_date DESC, frh.id DESC
        ", [(int) $header_id])->result();

        return [
            'header'      => $header,
            'inputs'      => $inputs,
            'outputs'     => $outputs,
            'fg_receipts' => $fg_receipts,
        ];
    }

    /* ===== STOCK LEDGER ===== */

    public function ledger($material_id, $limit = 300) {
        return $this->db->query("
            SELECT id, transaction_date, transaction_type, source_doc_type, source_doc_id,
                qty_in, qty_out, balance, unit_value, stock_on_hand_id
            FROM stock_ledger
            WHERE item_type='material' AND item_id = ?
            ORDER BY transaction_date DESC, id DESC
            LIMIT ?
        ", [(int) $material_id, (int) $limit])->result();
    }
}
