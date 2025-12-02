<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Po_report_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Get all POs with summary of received quantities
     */
    public function get_all($filters = []) {
        $sql = "
            SELECT 
                po.id,
                po.po_number,
                po.po_date,
                po.status,
                s.nama_supplier,
                SUM(pod.qty) AS total_po_qty,
                COALESCE(SUM(grnd.receive_qty), 0) AS total_qty_received,
                COUNT(DISTINCT grn.id) AS num_grns,
                CASE 
                    WHEN COALESCE(SUM(grnd.receive_qty), 0) = 0 THEN 'Not Received'
                    WHEN COALESCE(SUM(grnd.receive_qty), 0) < SUM(pod.qty) THEN 'Partial'
                    WHEN COALESCE(SUM(grnd.receive_qty), 0) >= SUM(pod.qty) THEN 'Complete'
                    ELSE 'Unknown'
                END AS receipt_status
            FROM po_header po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN po_details pod ON po.id = pod.po_header_id
            LEFT JOIN grn_details grnd ON pod.id = grnd.source_doc_detail_id AND grnd.source_doc_type = 'PO'
            LEFT JOIN grn_header grn ON grnd.grn_header_id = grn.id
            WHERE 1=1
        ";

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $sql .= " AND (po.po_number LIKE '%$term%' OR s.nama_supplier LIKE '%$term%')";
        }

        if (!empty($filters['supplier_id'])) {
            $sql .= " AND po.supplier_id = " . (int)$filters['supplier_id'];
        }

        if (!empty($filters['status'])) {
            $status = $this->db->escape_like_str($filters['status']);
            $sql .= " AND po.status = '$status'";
        }

        if (!empty($filters['receipt_status'])) {
            $receipt_status = $this->db->escape_like_str($filters['receipt_status']);
            $sql .= " AND CASE 
                WHEN COALESCE(SUM(grnd.receive_qty), 0) = 0 THEN 'Not Received'
                WHEN COALESCE(SUM(grnd.receive_qty), 0) < SUM(pod.qty) THEN 'Partial'
                WHEN COALESCE(SUM(grnd.receive_qty), 0) >= SUM(pod.qty) THEN 'Complete'
                ELSE 'Unknown'
            END = '$receipt_status'";
        }

        $sql .= " GROUP BY po.id ORDER BY po.po_date DESC";

        $query = $this->db->query($sql);
        return $query ? $query->result() : [];
    }

    /**
     * Get detailed PO with line items and their receipt status
     */
    public function get_po_detail($po_id) {
        // PO Header
        $sql_header = "
            SELECT po.*, s.nama_supplier, s.alamat AS supplier_address
            FROM po_header po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            WHERE po.id = ?
        ";
        $header = $this->db->query($sql_header, [$po_id])->row();

        if (!$header) {
            return null;
        }

        // PO Details with GRN receipt info
        $sql_details = "
            SELECT 
                pod.id,
                pod.po_header_id,
                pod.item_id,
                pod.item_type,
                pod.description,
                m.kode_material,
                m.nama_material,
                m.satuan,
                pod.qty,
                pod.base_price,
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
            LEFT JOIN (
                SELECT grnd.source_doc_detail_id, SUM(grnd.receive_qty) AS qty_received
                FROM grn_details grnd
                WHERE grnd.source_doc_type = 'PO'
                GROUP BY grnd.source_doc_detail_id
            ) grn_data ON pod.id = grn_data.source_doc_detail_id
            WHERE pod.po_header_id = ?
            GROUP BY pod.id
            ORDER BY pod.id ASC
        ";;;
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
     * Get GRN details for a specific PO
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
                m.kode_material,
                m.nama_material,
                m.satuan,
                grnd.receive_qty,
                grnd.unit_value,
                (grnd.receive_qty * grnd.unit_value) AS received_total
            FROM grn_header grn
            JOIN grn_details grnd ON grn.id = grnd.grn_header_id
            JOIN po_details pod ON grnd.source_doc_detail_id = pod.id AND grnd.source_doc_type = 'PO'
            LEFT JOIN materials m ON grnd.item_id = m.id AND grnd.item_type = 'material'
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
