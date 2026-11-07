<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Outstanding for Material Requests (mirror of Consumable_outstanding_model).
 *
 * Outstanding = mr_details.requested_qty - mr_details.issued_qty
 * Counted only for MRs that are sudah lolos final approval & masih ada qty yang belum di-issue:
 *   - 'Approved'         → lolos 3-stage approval, gudang belum mulai issue
 *   - 'Partial Issued'   → gudang sudah issue sebagian, sisa masih perlu
 *   - 'Partial Received' → sebagian sudah issued+received, sisa masih perlu
 * 'Issued' & 'Received' tidak di-include karena issued_qty = requested_qty (no outstanding).
 * 'Submitted', 'Approved Head Dept', 'Endorsed' tidak di-include karena masih dalam
 * approval pipeline — gudang belum boleh fulfill.
 *
 * Items are joined from `materials` or `semifinished_goods` based on
 * mr_details.component_item_type ('material' or 'semifinished').
 */
class Material_outstanding_model extends CI_Model {

    private function item_select($alias = 'd') {
        return "
            CASE WHEN $alias.component_item_type = 'material' THEN m.kode_material
                 WHEN $alias.component_item_type = 'semifinished' THEN sf.kode_barang
                 ELSE NULL END AS item_code,
            CASE WHEN $alias.component_item_type = 'material' THEN m.nama_material
                 WHEN $alias.component_item_type = 'semifinished' THEN sf.nama_barang
                 ELSE NULL END AS item_name,
            CASE WHEN $alias.component_item_type = 'material' THEN m.satuan
                 WHEN $alias.component_item_type = 'semifinished' THEN sf.satuan
                 ELSE 'PCS' END AS item_unit
        ";
    }

    /**
     * Ekspresi SQL untuk kolom "department" pada daftar outstanding MRF.
     *
     * Dulu selalu `u.departemen` -- departemen PEMOHON. Kalau akun pemohon
     * tidak diisi departemennya (akun Admin sering begitu), PR yang lahir dari
     * outstanding ini kehilangan departemen: kolom Departemen kosong dan
     * nomornya jatuh ke 'XXX' walau MRF-nya sudah ACC (dilaporkan user
     * 2026-10-01). Yang benar adalah departemen MRF-nya sendiri; pemohon cuma
     * cadangan.
     *
     * `mr_header.department_code` baru ada setelah migrasi Role Level tahap 3,
     * jadi keberadaannya dicek dulu -- di database yang belum dimigrasi
     * perilakunya kembali seperti semula, bukan query gagal.
     */
    private function _dept_expr() {
        return db_has_column('mr_header', 'department_code')
            ? "COALESCE(NULLIF(TRIM(mr_h.department_code), ''), u.departemen)"
            : "u.departemen";
    }

    public function get_grouped_by_item($filters = []) {
        $where_extra = '';
        $params = [];

        if (!empty($filters['department'])) {
            $where_extra .= ' AND u.departemen = ?';
            $params[] = $filters['department'];
        }
        if (!empty($filters['date_from'])) {
            $where_extra .= ' AND mr_h.request_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where_extra .= ' AND mr_h.request_date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $where_extra .= ' AND (m.kode_material LIKE ? OR m.nama_material LIKE ? OR sf.kode_barang LIKE ? OR sf.nama_barang LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql = "
            SELECT
                d.component_item_id AS item_id,
                d.component_item_type AS item_type,
                MAX(CASE WHEN d.component_item_type = 'material' THEN m.kode_material
                         WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang END) AS item_code,
                MAX(CASE WHEN d.component_item_type = 'material' THEN m.nama_material
                         WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang END) AS item_name,
                MAX(CASE WHEN d.component_item_type = 'material' THEN m.satuan
                         WHEN d.component_item_type = 'semifinished' THEN sf.satuan ELSE 'PCS' END) AS item_unit,
                SUM(d.requested_qty) AS total_requested,
                SUM(d.issued_qty)    AS total_issued,
                SUM(d.requested_qty - d.issued_qty) AS total_outstanding
            FROM mr_details d
            INNER JOIN mr_header mr_h ON mr_h.id = d.mr_header_id
            LEFT JOIN users u ON u.id = mr_h.requester_id
            LEFT JOIN materials m ON m.id = d.component_item_id AND d.component_item_type = 'material'
            LEFT JOIN semifinished_goods sf ON sf.id = d.component_item_id AND d.component_item_type = 'semifinished'
            WHERE mr_h.status IN ('Approved', 'Partial Issued', 'Partial Received')
              AND (d.requested_qty - d.issued_qty) > 0
            $where_extra
            GROUP BY d.component_item_id, d.component_item_type
            HAVING total_outstanding > 0
            ORDER BY item_name ASC
        ";

        $query = $this->db->query($sql, $params);
        return $query ? $query->result() : [];
    }

    public function get_breakdown_by_item($item_id, $item_type) {
        $sql = "
            SELECT
                d.id AS mr_detail_id,
                d.mr_header_id,
                d.component_item_id AS item_id,
                d.component_item_type AS item_type,
                CASE WHEN d.component_item_type = 'material' THEN m.kode_material
                     WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang END AS item_code,
                CASE WHEN d.component_item_type = 'material' THEN m.nama_material
                     WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang END AS item_name,
                CASE WHEN d.component_item_type = 'material' THEN m.satuan
                     WHEN d.component_item_type = 'semifinished' THEN sf.satuan ELSE 'PCS' END AS item_unit,
                d.requested_qty AS qty_requested,
                d.issued_qty    AS qty_issued,
                (d.requested_qty - d.issued_qty) AS qty_outstanding,
                mr_h.mr_number AS request_number,
                mr_h.request_date,
                {$this->_dept_expr()} AS department,
                u.fullname AS requestor_name,
                mr_h.status AS mr_status
            FROM mr_details d
            INNER JOIN mr_header mr_h ON mr_h.id = d.mr_header_id
            LEFT JOIN users u ON u.id = mr_h.requester_id
            LEFT JOIN materials m ON m.id = d.component_item_id AND d.component_item_type = 'material'
            LEFT JOIN semifinished_goods sf ON sf.id = d.component_item_id AND d.component_item_type = 'semifinished'
            WHERE mr_h.status IN ('Approved', 'Partial Issued', 'Partial Received')
              AND d.component_item_id = ?
              AND d.component_item_type = ?
              AND (d.requested_qty - d.issued_qty) > 0
            ORDER BY mr_h.request_date ASC, mr_h.id ASC
        ";

        $query = $this->db->query($sql, [(int)$item_id, $item_type]);
        return $query ? $query->result() : [];
    }

    public function get_departments() {
        $sql = "
            SELECT DISTINCT u.departemen AS department
            FROM mr_header mr_h
            LEFT JOIN users u ON u.id = mr_h.requester_id
            WHERE mr_h.status IN ('Approved', 'Partial Issued', 'Partial Received')
              AND u.departemen IS NOT NULL
              AND u.departemen != ''
            ORDER BY u.departemen ASC
        ";
        $query = $this->db->query($sql);
        return $query ? $query->result() : [];
    }

    public function get_current_outstanding_map($mr_detail_ids) {
        if (empty($mr_detail_ids)) return [];
        $ids_safe = array_map('intval', $mr_detail_ids);
        $ids_csv  = implode(',', $ids_safe);

        $sql = "
            SELECT
                d.id AS mr_detail_id,
                d.mr_header_id,
                d.component_item_id AS item_id,
                d.component_item_type AS item_type,
                CASE WHEN d.component_item_type = 'material' THEN m.kode_material
                     WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang END AS item_code,
                CASE WHEN d.component_item_type = 'material' THEN m.nama_material
                     WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang END AS item_name,
                CASE WHEN d.component_item_type = 'material' THEN m.satuan
                     WHEN d.component_item_type = 'semifinished' THEN sf.satuan ELSE 'PCS' END AS item_unit,
                mr_h.mr_number AS request_number,
                {$this->_dept_expr()} AS department,
                (d.requested_qty - d.issued_qty) AS qty_outstanding
            FROM mr_details d
            INNER JOIN mr_header mr_h ON mr_h.id = d.mr_header_id
            LEFT JOIN users u ON u.id = mr_h.requester_id
            LEFT JOIN materials m ON m.id = d.component_item_id AND d.component_item_type = 'material'
            LEFT JOIN semifinished_goods sf ON sf.id = d.component_item_id AND d.component_item_type = 'semifinished'
            WHERE d.id IN ($ids_csv)
        ";

        $query = $this->db->query($sql);
        $rows = $query ? $query->result() : [];
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->mr_detail_id] = $r;
        }
        return $map;
    }

    /**
     * Qty yang benar-benar perlu di-PR-kan per baris MRF — padanan
     * Consumable_outstanding_model::get_pr_need_map() untuk jalur material
     * (user 2026-09-21: "logikanya harusnya sama dengan general kan?").
     *
     *   qty_on_pr      = qty yang sudah ada di PR aktif & belum diterima
     *   qty_from_stock = bagian sisa yang masih bisa dipenuhi stok gudang
     *   qty_pr         = outstanding - qty_on_pr - qty_from_stock (min 0)
     *
     * Stok tidak di-booking: kalau keburu dipakai MRF lain, sisanya muncul
     * lagi sebagai kebutuhan PR. MRF paling lama kebagian stok lebih dulu.
     *
     * @return array [mr_detail_id => row get_current_outstanding_map() + kolom di atas
     *               + stock_total & stock_available]
     */
    public function get_pr_need_map($mr_detail_ids) {
        $map = $this->get_current_outstanding_map($mr_detail_ids);
        if (empty($map)) return [];

        $ids_csv = implode(',', array_map('intval', array_keys($map)));

        // Qty yang sudah ada di PR aktif, dikurangi yang sudah diterima lewat
        // PO -> GRN/SGR. Bentuk query-nya sama dengan jalur IRF.
        $on_pr = [];
        $query = $this->db->query("
            SELECT link.mr_detail_id,
                   SUM(link.qty - LEAST(link.qty, link.qty * COALESCE(rcv.qty_received, 0) / pr_d.qty)) AS qty_on_pr
            FROM pr_detail_mr_links link
            INNER JOIN pr_details pr_d ON pr_d.id = link.pr_detail_id AND pr_d.qty > 0
            INNER JOIN pr_header pr_h ON pr_h.id = pr_d.pr_header_id AND pr_h.status NOT IN ('Rejected', 'Cancelled')
            LEFT JOIN (
                SELECT po_d.pr_detail_id, SUM(r.receive_qty) AS qty_received
                FROM po_details po_d
                INNER JOIN (
                    SELECT sd.source_doc_detail_id AS po_detail_id, sd.receive_qty
                    FROM sgr_details sd INNER JOIN sgr_header s ON s.id = sd.sgr_header_id
                    WHERE sd.source_doc_type = 'PO' AND s.status = 'Completed'
                    UNION ALL
                    SELECT gd.source_doc_detail_id, gd.receive_qty
                    FROM grn_details gd INNER JOIN grn_header g ON g.id = gd.grn_header_id
                    WHERE gd.source_doc_type = 'PO' AND g.status = 'Completed'
                ) r ON r.po_detail_id = po_d.id
                WHERE po_d.pr_detail_id IS NOT NULL
                GROUP BY po_d.pr_detail_id
            ) rcv ON rcv.pr_detail_id = pr_d.id
            WHERE link.mr_detail_id IN ($ids_csv)
            GROUP BY link.mr_detail_id
        ");
        foreach (($query ? $query->result() : []) as $r) {
            $on_pr[(int) $r->mr_detail_id] = max(0, (float) $r->qty_on_pr);
        }

        // MRF paling lama dapat stok lebih dulu.
        $rows = array_values($map);
        usort($rows, function ($a, $b) {
            return [(int) $a->mr_header_id, (int) $a->mr_detail_id] <=> [(int) $b->mr_header_id, (int) $b->mr_detail_id];
        });

        $this->load->model('Stock_ledger_model');
        $stock_left  = [];
        $stock_total = [];
        $out = [];
        foreach ($rows as $r) {
            $id          = (int) $r->mr_detail_id;
            $outstanding = max(0, (float) $r->qty_outstanding);
            $itype       = $r->item_type ?: 'material';

            $r->qty_on_pr = min($outstanding, $on_pr[$id] ?? 0);
            $after_pr     = $outstanding - $r->qty_on_pr;

            $key = $itype . '-' . (int) $r->item_id;
            if (!isset($stock_left[$key])) {
                // Sumber stok sama dengan yang ditampilkan halaman pengeluaran.
                $stock_left[$key]  = max(0, (float) $this->Stock_ledger_model->get_current_stock((int) $r->item_id, $itype));
                $stock_total[$key] = $stock_left[$key];
            }
            $r->stock_total     = $stock_total[$key];
            $r->stock_available = $stock_left[$key];
            $r->qty_from_stock  = min($after_pr, $stock_left[$key]);
            $stock_left[$key]  -= $r->qty_from_stock;

            $r->qty_pr = max(0, $after_pr - $r->qty_from_stock);
            $out[$id]  = $r;
        }
        return $out;
    }

    /**
     * Dokumen PR yang sudah dibuat untuk baris MRF ini (keterangan + link).
     * Padanan Consumable_outstanding_model::get_pr_docs_map().
     *
     * @return array [mr_detail_id => [ {pr_id, pr_number, type, status, qty}, ... ]]
     */
    public function get_pr_docs_map($mr_detail_ids) {
        $ids = array_filter(array_map('intval', (array) $mr_detail_ids));
        if (empty($ids)) return [];
        $ids_csv = implode(',', $ids);

        $rows = $this->db->query("
            SELECT link.mr_detail_id,
                   pr_h.id AS pr_id, pr_h.pr_number, pr_h.type, pr_h.status,
                   SUM(link.qty) AS qty
            FROM pr_detail_mr_links link
            INNER JOIN pr_details pr_d ON pr_d.id = link.pr_detail_id
            INNER JOIN pr_header pr_h  ON pr_h.id = pr_d.pr_header_id
                                      AND pr_h.status NOT IN ('Rejected', 'Cancelled')
            WHERE link.mr_detail_id IN ($ids_csv)
            GROUP BY link.mr_detail_id, pr_h.id, pr_h.pr_number, pr_h.type, pr_h.status
            ORDER BY pr_h.id
        ")->result();

        $out = [];
        foreach ($rows as $r) $out[(int) $r->mr_detail_id][] = $r;
        return $out;
    }
}
