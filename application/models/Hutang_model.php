<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Hutang_model
 * Sumber data laporan "Daftar Hutang" (Account Payable monitoring).
 *
 * Read-only. Tidak menulis ke DB. Menggabungkan:
 *  - Faktur AP yang sudah diposting (ap_invoice_header) -> outstanding & aging
 *  - GRN berbasis PO yang sudah Completed tapi BELUM difaktur (received not billed)
 *  - Rekap per supplier
 *
 * Catatan: di sistem ini hutang/AP dicatat dalam IDR (single currency).
 */
class Hutang_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('Ap_auto_model');
    }

    /**
     * Dropdown supplier (yang punya faktur AP posted ATAU GRN PO completed).
     */
    public function get_suppliers()
    {
        $sql = "
            SELECT s.id, s.kode_supplier, s.nama_supplier
            FROM suppliers s
            WHERE EXISTS (
                    SELECT 1 FROM ap_invoice_header ap
                    WHERE ap.supplier_id = s.id AND ap.status = 'Posted'
                  )
               OR EXISTS (
                    SELECT 1 FROM grn_header gh
                    WHERE gh.supplier_id = s.id AND gh.status = 'Completed' AND gh.base_on = 'PO'
                  )
            ORDER BY s.nama_supplier ASC
        ";
        return $this->db->query($sql)->result();
    }

    /**
     * Daftar faktur hutang (AP) yang sudah diposting beserta sisa & umur.
     *
     * @param array $filters supplier_id, payment_status, only_outstanding (bool)
     */
    public function get_invoice_list($filters = [])
    {
        $where  = "ap.status = 'Posted'";
        $params = [];

        if (!empty($filters['supplier_id'])) {
            $where .= " AND ap.supplier_id = ?";
            $params[] = (int) $filters['supplier_id'];
        }
        if (!empty($filters['payment_status'])) {
            $where .= " AND ap.payment_status = ?";
            $params[] = $filters['payment_status'];
        }
        if (!empty($filters['only_outstanding'])) {
            $where .= " AND (ap.total_invoice - ap.total_paid) > 0.01";
        }

        $sql = "
            SELECT
                ap.id,
                ap.invoice_no,
                ap.invoice_date,
                ap.due_date,
                ap.total_invoice,
                ap.total_paid,
                (ap.total_invoice - ap.total_paid) AS outstanding,
                ap.payment_status,
                ap.invoice_type,
                s.kode_supplier,
                s.nama_supplier,
                DATEDIFF(CURDATE(), ap.due_date) AS days_overdue,
                (SELECT GROUP_CONCAT(DISTINCT gh.grn_number ORDER BY gh.grn_number SEPARATOR ', ')
                   FROM grn_header gh
                  WHERE JSON_SEARCH(ap.grn_ids, 'one', CAST(gh.id AS CHAR)) IS NOT NULL
                ) AS grn_numbers,
                (SELECT GROUP_CONCAT(DISTINCT ph.po_number ORDER BY ph.po_number SEPARATOR ', ')
                   FROM grn_header gh
                   JOIN grn_details gd ON gd.grn_header_id = gh.id AND gd.source_doc_type = 'PO'
                   JOIN po_header ph   ON ph.id = gd.source_doc_header_id
                  WHERE JSON_SEARCH(ap.grn_ids, 'one', CAST(gh.id AS CHAR)) IS NOT NULL
                ) AS po_numbers,
                (SELECT GROUP_CONCAT(DISTINCT ph2.po_number ORDER BY ph2.po_number SEPARATOR ', ')
                   FROM ap_invoice_details x
                   JOIN po_details pd2 ON pd2.id = x.po_detail_id
                   JOIN po_header ph2 ON ph2.id = pd2.po_header_id
                  WHERE x.ap_invoice_header_id = ap.id
                ) AS po_numbers_direct
            FROM ap_invoice_header ap
            JOIN suppliers s ON s.id = ap.supplier_id
            WHERE {$where}
            ORDER BY (ap.total_invoice - ap.total_paid) DESC, ap.due_date ASC, ap.id DESC
        ";
        $rows = $this->db->query($sql, $params)->result();
        foreach ($rows as $r) {
            if (empty($r->po_numbers) && !empty($r->po_numbers_direct)) $r->po_numbers = $r->po_numbers_direct;
        }
        return $rows;
    }

    /**
     * GRN berbasis PO yang sudah Completed tetapi belum dibuat faktur hutang.
     * Ini adalah "pembelian sudah diterima tapi belum ditagih" (accrued payable).
     *
     * @param array $filters supplier_id
     */
    public function get_uninvoiced_grn($filters = [])
    {
        $where  = "gh.status = 'Completed' AND gh.base_on = 'PO' AND gh.supplier_id IS NOT NULL";
        $params = [];

        if (!empty($filters['supplier_id'])) {
            $where .= " AND gh.supplier_id = ?";
            $params[] = (int) $filters['supplier_id'];
        }

        $sql = "
            SELECT
                gh.id,
                gh.grn_number,
                gh.grn_date,
                gh.supplier_id,
                s.kode_supplier,
                s.nama_supplier,
                (SELECT COALESCE(SUM(gd.total_value), 0)
                   FROM grn_details gd WHERE gd.grn_header_id = gh.id
                ) AS grn_value,
                (SELECT GROUP_CONCAT(DISTINCT ph.po_number ORDER BY ph.po_number SEPARATOR ', ')
                   FROM grn_details gd
                   JOIN po_header ph ON ph.id = gd.source_doc_header_id AND gd.source_doc_type = 'PO'
                  WHERE gd.grn_header_id = gh.id
                ) AS po_numbers,
                DATEDIFF(CURDATE(), gh.grn_date) AS age_days
            FROM grn_header gh
            JOIN suppliers s ON s.id = gh.supplier_id
            WHERE {$where}
              AND NOT EXISTS (
                    SELECT 1 FROM ap_invoice_header ap
                    WHERE JSON_SEARCH(ap.grn_ids, 'one', CAST(gh.id AS CHAR)) IS NOT NULL
                      AND ap.status != 'Cancelled'
                  )
            ORDER BY gh.grn_date ASC, gh.id ASC
        ";
        $rows = $this->db->query($sql, $params)->result();
        return $this->_filter_unbilled_grn($rows);
    }

    /**
     * Buang / koreksi nilai GRN yang barangnya sudah ditagih lewat jalur lain
     * (AP berbasis PO atau Payment Request hutang). Sisa tagihan per baris PO
     * (Ap_auto_model::po_line_status) dialokasikan ke penerimaan TERBARU dulu --
     * tagihan dianggap melunasi penerimaan paling lama lebih dulu (FIFO).
     */
    private function _filter_unbilled_grn(array $rows)
    {
        if (empty($rows)) return [];
        $ids = array_map(function ($r) { return (int) $r->id; }, $rows);
        $lines = $this->db->query("
            SELECT gd.grn_header_id, gd.source_doc_detail_id, gd.receive_qty, gd.unit_value, gh.grn_date, pd.po_header_id
            FROM grn_details gd
            JOIN grn_header gh ON gh.id = gd.grn_header_id
            JOIN po_details pd ON pd.id = gd.source_doc_detail_id
            WHERE gd.source_doc_type = 'PO' AND gd.grn_header_id IN (" . implode(',', $ids) . ")
            ORDER BY gh.grn_date DESC, gd.grn_header_id DESC, gd.id DESC
        ")->result();
        $po_ids = array_unique(array_map(function ($l) { return (int) $l->po_header_id; }, $lines));
        $left = [];
        foreach ($this->Ap_auto_model->po_line_status($po_ids) as $pdid => $st) $left[$pdid] = $st->remaining_qty;

        $value = [];
        foreach ($lines as $l) {
            $pdid  = (int) $l->source_doc_detail_id;
            $alloc = min((float) $l->receive_qty, max(0, (float) ($left[$pdid] ?? 0)));
            $left[$pdid] = ($left[$pdid] ?? 0) - $alloc;
            $value[(int) $l->grn_header_id] = ($value[(int) $l->grn_header_id] ?? 0) + $alloc * (float) $l->unit_value;
        }
        $out = [];
        foreach ($rows as $r) {
            $v = round($value[(int) $r->id] ?? 0, 2);
            if ($v <= 0.01) continue;
            $r->grn_value = $v;
            $out[] = $r;
        }
        return $out;
    }

    /** Ringkasan GRN belum ditagih per supplier (dari get_uninvoiced_grn). */
    private function _uninvoiced_by_supplier($filters)
    {
        $map = [];
        foreach ($this->get_uninvoiced_grn($filters) as $g) {
            $sid = (int) ($g->supplier_id ?? 0);
            if (!isset($map[$sid])) $map[$sid] = ['count' => 0, 'value' => 0.0];
            $map[$sid]['count']++;
            $map[$sid]['value'] += (float) $g->grn_value;
        }
        return $map;
    }

    /**
     * Rekap per supplier: faktur, dibayar, sisa hutang + nilai GRN belum difaktur.
     *
     * @param array $filters supplier_id
     */
    public function get_summary_by_supplier($filters = [])
    {
        $where  = "(inv.inv_count > 0 OR ung.uninvoiced_count > 0)";
        $params = [];

        // GRN belum ditagih dihitung di PHP (lihat _filter_unbilled_grn) lalu
        // dijadikan tabel turunan supaya query rekap tetap satu SQL.
        $ung_parts = [];
        foreach ($this->_uninvoiced_by_supplier($filters) as $sid => $u) {
            $ung_parts[] = sprintf('SELECT %d AS supplier_id, %d AS uninvoiced_count, %.2F AS uninvoiced_value', $sid, $u['count'], $u['value']);
        }
        $ung_sql = $ung_parts ? implode(' UNION ALL ', $ung_parts) : 'SELECT 0 AS supplier_id, 0 AS uninvoiced_count, 0 AS uninvoiced_value';

        if (!empty($filters['supplier_id'])) {
            $where .= " AND s.id = ?";
            $params[] = (int) $filters['supplier_id'];
        }

        $sql = "
            SELECT
                s.id,
                s.kode_supplier,
                s.nama_supplier,
                COALESCE(inv.inv_count, 0)        AS inv_count,
                COALESCE(inv.total_invoice, 0)    AS total_invoice,
                COALESCE(inv.total_paid, 0)       AS total_paid,
                COALESCE(inv.outstanding, 0)      AS outstanding,
                COALESCE(ung.uninvoiced_count, 0) AS uninvoiced_count,
                COALESCE(ung.uninvoiced_value, 0) AS uninvoiced_value
            FROM suppliers s
            LEFT JOIN (
                SELECT supplier_id,
                       COUNT(*)                            AS inv_count,
                       SUM(total_invoice)                  AS total_invoice,
                       SUM(total_paid)                     AS total_paid,
                       SUM(total_invoice - total_paid)     AS outstanding
                FROM ap_invoice_header
                WHERE status = 'Posted'
                GROUP BY supplier_id
            ) inv ON inv.supplier_id = s.id
            LEFT JOIN ({$ung_sql}) ung ON ung.supplier_id = s.id
            WHERE {$where}
            ORDER BY (COALESCE(inv.outstanding,0) + COALESCE(ung.uninvoiced_value,0)) DESC, s.nama_supplier ASC
        ";
        return $this->db->query($sql, $params)->result();
    }

    /**
     * Total keseluruhan (untuk kartu ringkasan di atas halaman).
     *
     * @param array $filters supplier_id
     */
    public function get_totals($filters = [])
    {
        $inv_where = "status = 'Posted'";
        $grn_where = "gh.status = 'Completed' AND gh.base_on = 'PO' AND gh.supplier_id IS NOT NULL";
        $inv_params = [];
        $grn_params = [];

        if (!empty($filters['supplier_id'])) {
            $inv_where .= " AND supplier_id = ?";
            $inv_params[] = (int) $filters['supplier_id'];
            $grn_where .= " AND gh.supplier_id = ?";
            $grn_params[] = (int) $filters['supplier_id'];
        }

        $inv = $this->db->query("
            SELECT
                COALESCE(SUM(total_invoice), 0)              AS total_invoice,
                COALESCE(SUM(total_paid), 0)                 AS total_paid,
                COALESCE(SUM(total_invoice - total_paid), 0) AS outstanding,
                COUNT(*)                                     AS inv_count
            FROM ap_invoice_header WHERE {$inv_where}
        ", $inv_params)->row();

        $grn = (object) ['uninvoiced_value' => 0.0, 'uninvoiced_count' => 0];
        foreach ($this->_uninvoiced_by_supplier($filters) as $u) {
            $grn->uninvoiced_value += $u['value'];
            $grn->uninvoiced_count += $u['count'];
        }

        return (object) [
            'total_invoice'    => (float) $inv->total_invoice,
            'total_paid'       => (float) $inv->total_paid,
            'outstanding'      => (float) $inv->outstanding,
            'inv_count'        => (int)   $inv->inv_count,
            'uninvoiced_value' => (float) $grn->uninvoiced_value,
            'uninvoiced_count' => (int)   $grn->uninvoiced_count,
        ];
    }
}
