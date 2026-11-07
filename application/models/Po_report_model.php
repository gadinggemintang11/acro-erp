<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Po_report_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all POs with summary of received quantities, PR info, and requester
     */
    public function get_all($filters = []) {
        $sql = "
            SELECT
                po.id,
                po.po_number,
                po.po_date,
                po.status,
                po.type,
                po.has_ppn,
                po.ppn_rate,
                s.nama_supplier,
                pr.pr_number,
                u.fullname AS requester_name,
                SUM(pod.qty) AS total_po_qty,
                COALESCE(SUM(grnd.receive_qty), 0) AS total_qty_received,
                COUNT(DISTINCT grn.id) AS num_grns,
                MIN(grn.grn_date) AS first_grn_date,
                COALESCE(pod_total.total_value, 0) AS total_value,
                CASE
                    WHEN COALESCE(SUM(grnd.receive_qty), 0) = 0 THEN 'Not Received'
                    WHEN COALESCE(SUM(grnd.receive_qty), 0) < SUM(pod.qty) THEN 'Partial'
                    WHEN COALESCE(SUM(grnd.receive_qty), 0) >= SUM(pod.qty) THEN 'Complete'
                    ELSE 'Unknown'
                END AS receipt_status,
                CASE
                    WHEN MIN(grn.grn_date) IS NOT NULL THEN DATEDIFF(MIN(grn.grn_date), po.po_date)
                    ELSE DATEDIFF(CURDATE(), po.po_date)
                END AS days_to_warehouse_in
            FROM po_header po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN pr_header pr ON po.pr_header_id = pr.id
            LEFT JOIN users u ON pr.created_by = u.id
            LEFT JOIN po_details pod ON po.id = pod.po_header_id
            LEFT JOIN grn_details grnd ON pod.id = grnd.source_doc_detail_id AND grnd.source_doc_type = 'PO'
            LEFT JOIN grn_header grn ON grnd.grn_header_id = grn.id
            -- Nilai total (utk kolom 'Total' monitoring, [[erp_revision_monitoring_lists]])
            -- SENGAJA pakai derived-table terpisah (bukan nebeng SUM(pod...) di atas)
            -- krn join ke grn_details di atas bisa fan-out (1 po_detail bisa punya
            -- BANYAK baris grn_details kalau diterima partial berkali-kali) — kalau
            -- ikut SUM bareng pod.qty di atas, nilainya jadi ganda. total_po_qty di
            -- atas SUDAH py bug fan-out ini dari lama (di luar scope perbaikan ini).
            LEFT JOIN (
                SELECT po_header_id, SUM(total_local) AS total_value
                FROM po_details
                GROUP BY po_header_id
            ) pod_total ON pod_total.po_header_id = po.id
            WHERE 1=1
        ";

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $sql .= " AND (po.po_number LIKE '%$term%' OR s.nama_supplier LIKE '%$term%')";
        }

        if (!empty($filters['supplier_id'])) {
            $sql .= " AND po.supplier_id = " . (int)$filters['supplier_id'];
        }

        // 'jasa'/'asset' ditambahkan (ERP Revision Notes, [[erp_revision_monitoring_lists]])
        // — sebelumnya cuma produksi/non_produksi, jasa/asset diam-diam DIABAIKAN
        // (kondisi jadi false, filter type tidak ke-apply sama sekali, bukan
        // salah tapi menyesatkan kalau dipakai di monitoring list gabungan).
        $VALID_TYPES = ['produksi','non_produksi','jasa','asset'];
        // Array = gabungan beberapa tipe sekaligus. Dipakai GMPO (user
        // 2026-09-11: "harusnya juga ada PO jasa dll kan? karna kan jadi satu
        // kecuali material") — GMPO memayungi non_produksi + jasa + asset,
        // yang terpisah cuma produksi/material.
        if (!empty($filters['type']) && is_array($filters['type'])) {
            $safe = array_values(array_intersect($filters['type'], $VALID_TYPES));
            if (!empty($safe)) {
                $sql .= " AND po.type IN ('" . implode("','", $safe) . "')";
            }
        } elseif (!empty($filters['type']) && in_array($filters['type'], $VALID_TYPES, true)) {
            $sql .= " AND po.type = '" . $filters['type'] . "'";
        }

        if (!empty($filters['status'])) {
            $status = $this->db->escape_like_str($filters['status']);
            $sql .= " AND po.status = '$status'";
        }

        $sql .= " GROUP BY po.id";

        // receipt_status di-derive dari fungsi agregat (SUM), jadi filternya harus
        // di HAVING — bukan WHERE — agar tidak kena MySQL error 1111
        // "Invalid use of group function". Pakai alias hasil SELECT.
        if (!empty($filters['receipt_status'])) {
            $receipt_status = $this->db->escape_like_str($filters['receipt_status']);
            $sql .= " HAVING receipt_status = '$receipt_status'";
        }

        $sql .= " ORDER BY po.po_date DESC";

        $query = $this->db->query($sql);
        return $query ? $query->result() : [];
    }

    /**
     * Get detailed PO with line items, PR info, requester, and their receipt status
     */
    public function get_po_detail($po_id) {
        // PO Header with PR & Requester info
        $sql_header = "
            SELECT po.*, s.nama_supplier, s.alamat AS supplier_address,
                   pr.pr_number, pr.pr_date, pr.purpose AS pr_description,
                   u.fullname AS requester_name, u.email AS requester_email,
                   COALESCE(MIN(grn.grn_date), NULL) AS first_grn_date,
                   CASE
                       WHEN MIN(grn.grn_date) IS NOT NULL THEN DATEDIFF(MIN(grn.grn_date), po.po_date)
                       ELSE DATEDIFF(CURDATE(), po.po_date)
                   END AS days_to_warehouse_in
            FROM po_header po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN pr_header pr ON po.pr_header_id = pr.id
            LEFT JOIN users u ON pr.created_by = u.id
            LEFT JOIN po_details pod ON po.id = pod.po_header_id
            LEFT JOIN grn_details grnd ON pod.id = grnd.source_doc_detail_id AND grnd.source_doc_type = 'PO'
            LEFT JOIN grn_header grn ON grnd.grn_header_id = grn.id
            WHERE po.id = ?
            GROUP BY po.id
        ";
        $header = $this->db->query($sql_header, [$po_id])->row();

        if (!$header) {
            return null;
        }

        // PO Details with GRN receipt info - handle all item types
        $sql_details = "
            SELECT
                pod.id,
                pod.po_header_id,
                pod.item_id,
                pod.item_type,
                pod.description,
                COALESCE(m.kode_material, i.kode_barang, mm.kode_mesin, sf.kode_barang, sc.scrap_code, fg.kode_barang, j.kode_jasa, pod.description) AS item_code,
                COALESCE(m.nama_material, i.nama_barang, mm.nama_mesin, sf.nama_barang, sc.scrap_name, fg.nama_barang, j.nama_jasa, pod.description) AS item_name,
                COALESCE(m.satuan, i.satuan, mm.satuan, sf.satuan, sc.unit, fg.satuan, j.satuan, '-') AS satuan,
                pod.qty,
                pod.base_price,
                pod.local_price,
                (pod.qty * pod.base_price) AS line_total,
                COALESCE(grn_data.qty_received, 0) AS qty_received,
                (pod.qty - COALESCE(grn_data.qty_received, 0)) AS qty_pending,
                CASE
                    WHEN COALESCE(grn_data.qty_received, 0) = 0 THEN 'Not Received'
                    WHEN COALESCE(grn_data.qty_received, 0) < pod.qty THEN 'Partial'
                    WHEN COALESCE(grn_data.qty_received, 0) >= pod.qty THEN 'Complete'
                    ELSE 'Unknown'
                END AS item_receipt_status
            FROM po_details pod
            LEFT JOIN materials m ON pod.item_id = m.id AND pod.item_type = 'material'
            LEFT JOIN inventories i ON pod.item_id = i.id AND pod.item_type = 'inventory'
            LEFT JOIN machines mm ON pod.item_id = mm.id AND pod.item_type = 'machine'
            LEFT JOIN semifinished_goods sf ON pod.item_id = sf.id AND pod.item_type = 'semifinished'
            LEFT JOIN master_scrap sc ON pod.item_id = sc.id AND pod.item_type = 'scrap'
            LEFT JOIN finished_goods fg ON pod.item_id = fg.id AND pod.item_type = 'finished_goods'
            LEFT JOIN jasa j ON pod.item_id = j.id AND pod.item_type = 'jasa'
            LEFT JOIN (
                SELECT grnd.source_doc_detail_id, SUM(grnd.receive_qty) AS qty_received
                FROM grn_details grnd
                WHERE grnd.source_doc_type = 'PO'
                GROUP BY grnd.source_doc_detail_id
            ) grn_data ON pod.id = grn_data.source_doc_detail_id
            WHERE pod.po_header_id = ?
            GROUP BY pod.id
            ORDER BY pod.id ASC
        ";
        $details = $this->db->query($sql_details, [$po_id])->result();

        // GRNs related to this PO
        $sql_grns = "
            SELECT 
                grn.id,
                grn.grn_number,
                grn.grn_date,
                grn.status,
                COUNT(DISTINCT grnd.id) AS num_items,
                SUM(grnd.receive_qty) AS total_qty_received
            FROM grn_header grn
            JOIN grn_details grnd ON grn.id = grnd.grn_header_id
            JOIN po_details pod ON grnd.source_doc_detail_id = pod.id AND grnd.source_doc_type = 'PO'
            WHERE pod.po_header_id = ?
            GROUP BY grn.id
            ORDER BY grn.grn_date DESC
        ";
        $grns = $this->db->query($sql_grns, [$po_id])->result();

        return [
            'header' => $header,
            'details' => $details,
            'grns' => $grns
        ];
    }

    /**
     * Get GRN details for a specific PO - handle all item types
     */
    public function get_grn_details($po_id, $grn_id = null) {
        $sql = "
            SELECT
                grn.id,
                grn.grn_number,
                grn.grn_date,
                grn.status,
                grnd.id AS detail_id,
                grnd.source_doc_detail_id,
                grnd.item_id,
                grnd.item_type,
                COALESCE(m.kode_material, i.kode_barang, mm.kode_mesin, sf.kode_barang, sc.scrap_code, fg.kode_barang, j.kode_jasa) AS item_code,
                COALESCE(m.nama_material, i.nama_barang, mm.nama_mesin, sf.nama_barang, sc.scrap_name, fg.nama_barang, j.nama_jasa) AS item_name,
                COALESCE(m.satuan, i.satuan, mm.satuan, sf.satuan, sc.unit, fg.satuan, j.satuan, '-') AS satuan,
                grnd.receive_qty,
                grnd.unit_value,
                (grnd.receive_qty * grnd.unit_value) AS received_total
            FROM grn_header grn
            JOIN grn_details grnd ON grn.id = grnd.grn_header_id
            JOIN po_details pod ON grnd.source_doc_detail_id = pod.id AND grnd.source_doc_type = 'PO'
            LEFT JOIN materials m ON grnd.item_id = m.id AND grnd.item_type = 'material'
            LEFT JOIN inventories i ON grnd.item_id = i.id AND grnd.item_type = 'inventory'
            LEFT JOIN machines mm ON grnd.item_id = mm.id AND grnd.item_type = 'machine'
            LEFT JOIN semifinished_goods sf ON grnd.item_id = sf.id AND grnd.item_type = 'semifinished'
            LEFT JOIN master_scrap sc ON grnd.item_id = sc.id AND grnd.item_type = 'scrap'
            LEFT JOIN finished_goods fg ON grnd.item_id = fg.id AND grnd.item_type = 'finished_goods'
            LEFT JOIN jasa j ON grnd.item_id = j.id AND grnd.item_type = 'jasa'
            WHERE pod.po_header_id = ?
        ";

        if (!empty($grn_id) && is_numeric($grn_id)) {
            $sql .= " AND grn.id = ?";
            $query = $this->db->query($sql, [$po_id, $grn_id]);
        } else {
            $query = $this->db->query($sql, [$po_id]);
        }

        return $query ? $query->result() : [];
    }

    /**
     * Get summary statistics for dashboard
     */
    public function get_summary_stats() {
        $sql = "
            SELECT 
                COUNT(*) AS total_pos,
                SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) AS open_pos,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed_pos,
                SUM(CASE WHEN total_received = 0 THEN 1 ELSE 0 END) AS not_received,
                SUM(CASE WHEN total_received > 0 AND total_received < total_qty THEN 1 ELSE 0 END) AS partial_received,
                SUM(CASE WHEN total_received >= total_qty THEN 1 ELSE 0 END) AS complete_received
            FROM (
                SELECT 
                    po.id,
                    po.status,
                    SUM(pod.qty) AS total_qty,
                    COALESCE(SUM(grnd.receive_qty), 0) AS total_received
                FROM po_header po
                LEFT JOIN po_details pod ON po.id = pod.po_header_id
                LEFT JOIN grn_details grnd ON pod.id = grnd.source_doc_detail_id AND grnd.source_doc_type = 'PO'
                GROUP BY po.id, po.status
            ) po_summary
        ";

        return $this->db->query($sql)->row();
    }

    /**
     * Get suppliers for filter dropdown
     */
    public function get_suppliers() {
        return $this->db->order_by('nama_supplier', 'ASC')->get('suppliers')->result();
    }
}
?>
