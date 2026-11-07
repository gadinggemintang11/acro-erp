<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * IRF Status — status & analisis per dokumen IRF (consumable request).
 *
 * Berbeda dengan Consumable_outstanding_model yang meng-agregasi per ITEM,
 * model ini meng-agregasi per NOMOR IRF: berapa yang diminta, berapa yang
 * sudah di-release gudang, berapa yang masih dalam proses, dan berapa
 * yang masih outstanding.
 *
 * Definisi qty per baris IRF (consumable_request_details):
 *   qty_requested   = yang diminta
 *   qty_released    = SUM(consumable_out_details.qty_issued) pada dokumen
 *                     Consumable Out berstatus 'Confirmed' yang request_id-nya
 *                     mengarah ke IRF ini (item sama)
 *   qty_pending     = idem tapi dokumen masih 'Created' (belum dikonfirmasi)
 *   qty_outstanding = GREATEST(qty_requested - qty_released, 0)
 *   qty_unprocessed = GREATEST(qty_requested - qty_released - qty_pending, 0)
 *
 * Outstanding di-clamp per baris (GREATEST(...,0)) supaya kelebihan issue pada
 * satu item tidak menutupi kekurangan item lain di IRF yang sama.
 */
class Irf_status_model extends CI_Model {

    /** Status IRF yang dianggap "hidup" (sudah/masih bisa diproses gudang). */
    const ACTIVE_STATUSES = ['Approved', 'Released'];

    /**
     * Sub-query qty yang sudah di-release (dokumen Confirmed).
     */
    private function _sql_issued() {
        return "
            SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty
            FROM consumable_out_details co_d
            INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
            WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
            GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
        ";
    }

    /**
     * Sub-query qty yang sedang diproses (dokumen masih 'Created' / belum confirm).
     */
    private function _sql_pending() {
        return "
            SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty
            FROM consumable_out_details co_d
            INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
            WHERE co_h.status = 'Created' AND co_h.request_id IS NOT NULL
            GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
        ";
    }

    /**
     * Sub-query qty Jasa yang sudah "diselesaikan" via PO Jasa
     * (Purchase_order_jasa::complete_service() -> grn_header warehouse_type='jasa').
     * Item Jasa TIDAK PERNAH punya baris di consumable_out_details (tidak ada
     * gudang yang mengeluarkan jasa) — tanpa ini, baris Jasa akan permanen
     * dianggap "belum released" di modul ini walau PO-nya sudah selesai.
     * Ditelusuri lewat pr_detail_cr_links -> pr_details -> po_details.pr_detail_id
     * -> grn_details. Pola identik Consumable_outstanding_model::_jasa_completed_subquery()
     * & Consumable_request_model::check_outstanding_by_item() — kalau formula
     * berubah, sinkronkan di ketiga tempat. Lihat db/migration_pr_detail_cr_links.sql
     * & [[revision_irf_auto_pr_at_issuance]].
     */
    private function _sql_jasa_done() {
        return "
            SELECT
                link.cr_detail_id,
                SUM(LEAST(link.qty, link.qty * (COALESCE(comp.completed_qty, 0) / pr_d.qty))) AS qty
            FROM pr_detail_cr_links link
            INNER JOIN pr_details pr_d ON pr_d.id = link.pr_detail_id AND pr_d.qty > 0
            LEFT JOIN (
                SELECT po_d.pr_detail_id, SUM(grn_d.receive_qty) AS completed_qty
                FROM po_details po_d
                INNER JOIN grn_details grn_d ON grn_d.source_doc_type = 'PO' AND grn_d.source_doc_detail_id = po_d.id
                INNER JOIN grn_header grn_h ON grn_h.id = grn_d.grn_header_id
                WHERE grn_h.warehouse_type = 'jasa' AND grn_h.status = 'Completed'
                GROUP BY po_d.pr_detail_id
            ) comp ON comp.pr_detail_id = pr_d.id
            GROUP BY link.cr_detail_id
        ";
    }

    /**
     * Agregat per header IRF (dipakai list & summary).
     */
    private function _sql_aggregate() {
        $issued  = $this->_sql_issued();
        $pending = $this->_sql_pending();
        $jasa    = $this->_sql_jasa_done();

        return "
            SELECT
                d.header_id,
                COUNT(*)                                                          AS total_lines,
                SUM(d.qty_requested)                                              AS total_requested,
                SUM(COALESCE(i.qty, 0) + COALESCE(j.qty, 0))                      AS total_released,
                SUM(COALESCE(p.qty, 0))                                           AS total_pending,
                SUM(GREATEST(d.qty_requested - COALESCE(i.qty, 0) - COALESCE(j.qty, 0), 0))            AS total_outstanding,
                SUM(CASE WHEN (d.qty_requested - COALESCE(i.qty, 0) - COALESCE(j.qty, 0)) <= 0 THEN 1 ELSE 0 END) AS lines_complete,
                SUM(CASE WHEN (d.qty_requested - COALESCE(i.qty, 0) - COALESCE(j.qty, 0)) >  0 THEN 1 ELSE 0 END) AS lines_outstanding
            FROM consumable_request_details d
            LEFT JOIN ($issued)  i ON i.request_id = d.header_id AND i.item_id = d.item_id AND i.item_type = d.item_type
            LEFT JOIN ($pending) p ON p.request_id = d.header_id AND p.item_id = d.item_id AND p.item_type = d.item_type
            LEFT JOIN ($jasa)    j ON j.cr_detail_id = d.id
            GROUP BY d.header_id
        ";
    }

    /**
     * Bangun klausa WHERE dari filter (dipakai list, summary, & export).
     *
     * @return array [sql_where_fragment, params]
     */
    private function _build_where($filters = []) {
        $where  = ' WHERE 1=1';
        $params = [];

        if (!empty($filters['search'])) {
            $like = '%' . $filters['search'] . '%';
            $where .= " AND (h.request_number LIKE ? OR h.department LIKE ? OR h.requestor_name LIKE ?
                        OR EXISTS (SELECT 1 FROM consumable_request_details sd
                                   WHERE sd.header_id = h.id
                                     AND (sd.item_code LIKE ? OR sd.item_name LIKE ?)))";
            $params = array_merge($params, [$like, $like, $like, $like, $like]);
        }
        if (!empty($filters['department'])) {
            $where .= ' AND h.department = ?';
            $params[] = $filters['department'];
        }
        if (!empty($filters['status'])) {
            $where .= ' AND h.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            if (is_array($filters['category'])) {
                $placeholders = implode(',', array_fill(0, count($filters['category']), '?'));
                $where .= " AND h.category IN ($placeholders)";
                $params = array_merge($params, array_values($filters['category']));
            } else {
                $where .= ' AND h.category = ?';
                $params[] = $filters['category'];
            }
        }
        if (!empty($filters['date_from'])) {
            $where .= ' AND h.request_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= ' AND h.request_date <= ?';
            $params[] = $filters['date_to'];
        }

        // Filter progres pemenuhan (dihitung dari agregat).
        switch ($filters['fulfillment'] ?? '') {
            case 'not_started':
                $where .= ' AND agg.total_released = 0 AND agg.total_outstanding > 0';
                break;
            case 'partial':
                $where .= ' AND agg.total_released > 0 AND agg.total_outstanding > 0';
                break;
            case 'complete':
                $where .= ' AND agg.total_outstanding = 0';
                break;
            case 'outstanding':
                $where .= ' AND agg.total_outstanding > 0';
                break;
        }

        return [$where, $params];
    }

    /**
     * Daftar IRF beserta ringkasan qty & progres.
     */
    public function get_list($filters = []) {
        $agg = $this->_sql_aggregate();
        list($where, $params) = $this->_build_where($filters);

        $sql = "
            SELECT
                h.id,
                h.request_number,
                h.request_date,
                h.department,
                h.requestor_name,
                h.purpose,
                h.status,
                h.created_at,
                h.submitted_at,
                h.approved_at,
                h.final_approved_at,
                h.rejected_at,
                u.fullname AS created_by_name,
                agg.total_lines,
                agg.total_requested,
                agg.total_released,
                agg.total_pending,
                agg.total_outstanding,
                agg.lines_complete,
                agg.lines_outstanding,
                COALESCE(iss.doc_count, 0) AS issuance_count,
                iss.first_issued_at,
                iss.last_issued_at,
                DATEDIFF(CURDATE(), h.request_date) AS age_days,
                CASE WHEN agg.total_outstanding > 0
                     THEN DATEDIFF(CURDATE(), COALESCE(h.approved_at, h.request_date))
                     ELSE NULL END AS outstanding_age_days,
                CASE WHEN agg.total_outstanding = 0 AND iss.last_issued_at IS NOT NULL
                     THEN DATEDIFF(iss.last_issued_at, COALESCE(h.approved_at, h.request_date))
                     ELSE NULL END AS lead_time_days
            FROM consumable_request_header h
            INNER JOIN ($agg) agg ON agg.header_id = h.id
            LEFT JOIN users u ON u.id = h.created_by
            LEFT JOIN (
                SELECT co_h.request_id,
                       COUNT(*) AS doc_count,
                       MIN(COALESCE(co_h.confirmed_at, co_h.issuance_date)) AS first_issued_at,
                       MAX(COALESCE(co_h.confirmed_at, co_h.issuance_date)) AS last_issued_at
                FROM consumable_out_header co_h
                WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id
            ) iss ON iss.request_id = h.id
            $where
            ORDER BY h.request_date DESC, h.id DESC
        ";

        $query = $this->db->query($sql, $params);
        return $query ? $query->result() : [];
    }

    /**
     * Ringkasan angka untuk kartu statistik di halaman index.
     */
    public function get_summary($filters = []) {
        $agg = $this->_sql_aggregate();
        list($where, $params) = $this->_build_where($filters);

        $sql = "
            SELECT
                COUNT(*)                                     AS irf_count,
                SUM(agg.total_requested)                     AS qty_requested,
                SUM(agg.total_released)                      AS qty_released,
                SUM(agg.total_pending)                       AS qty_pending,
                SUM(agg.total_outstanding)                   AS qty_outstanding,
                SUM(CASE WHEN agg.total_outstanding = 0 THEN 1 ELSE 0 END) AS irf_complete,
                SUM(CASE WHEN agg.total_released > 0 AND agg.total_outstanding > 0 THEN 1 ELSE 0 END) AS irf_partial,
                SUM(CASE WHEN agg.total_released = 0 AND agg.total_outstanding > 0 THEN 1 ELSE 0 END) AS irf_not_started
            FROM consumable_request_header h
            INNER JOIN ($agg) agg ON agg.header_id = h.id
            $where
        ";

        $query = $this->db->query($sql, $params);
        $row = $query ? $query->row() : null;

        return $row ?: (object) [
            'irf_count' => 0, 'qty_requested' => 0, 'qty_released' => 0,
            'qty_pending' => 0, 'qty_outstanding' => 0,
            'irf_complete' => 0, 'irf_partial' => 0, 'irf_not_started' => 0,
        ];
    }

    /**
     * Header IRF + nama-nama pelaku approval.
     */
    public function get_header($id) {
        $sql = "
            SELECT h.*,
                   u.fullname   AS created_by_name,
                   us.fullname  AS submitted_by_name,
                   uh.fullname  AS hod_approver_name,
                   ue.fullname  AS endorser_name,
                   uf.fullname  AS final_approver_name,
                   ua.fullname  AS approved_by_name,
                   ur.fullname  AS rejected_by_name,
                   urd.fullname AS returned_by_name
            FROM consumable_request_header h
            LEFT JOIN users u   ON u.id   = h.created_by
            LEFT JOIN users us  ON us.id  = h.submitted_by
            LEFT JOIN users uh  ON uh.id  = h.hod_approved_by
            LEFT JOIN users ue  ON ue.id  = h.endorsed_by
            LEFT JOIN users uf  ON uf.id  = h.final_approved_by
            LEFT JOIN users ua  ON ua.id  = h.approved_by
            LEFT JOIN users ur  ON ur.id  = h.rejected_by
            LEFT JOIN users urd ON urd.id = h.returned_to_draft_by
            WHERE h.id = ?
        ";
        $query = $this->db->query($sql, [(int)$id]);
        return $query ? $query->row() : null;
    }

    /**
     * Rincian per item pada satu IRF: diminta / released / pending / outstanding
     * + stok on-hand terkini sebagai bahan analisis.
     */
    public function get_item_breakdown($header_id) {
        $issued  = $this->_sql_issued();
        $pending = $this->_sql_pending();
        $jasa    = $this->_sql_jasa_done();

        $sql = "
            SELECT
                d.id AS cr_detail_id,
                d.header_id,
                d.item_id,
                d.item_type,
                d.item_code,
                d.item_name,
                d.item_unit,
                d.notes,
                d.qty_requested,
                (COALESCE(i.qty, 0) + COALESCE(j.qty, 0))                                     AS qty_released,
                COALESCE(p.qty, 0)                                     AS qty_pending,
                GREATEST(d.qty_requested - COALESCE(i.qty, 0) - COALESCE(j.qty, 0), 0)      AS qty_outstanding,
                GREATEST(d.qty_requested - COALESCE(i.qty, 0) - COALESCE(j.qty, 0) - COALESCE(p.qty, 0), 0) AS qty_unprocessed,
                COALESCE((SELECT sl.balance FROM stock_ledger sl
                          WHERE sl.item_id = d.item_id AND sl.item_type = d.item_type
                          ORDER BY sl.id DESC LIMIT 1), 0)             AS stock_on_hand
            FROM consumable_request_details d
            LEFT JOIN ($issued)  i ON i.request_id = d.header_id AND i.item_id = d.item_id AND i.item_type = d.item_type
            LEFT JOIN ($pending) p ON p.request_id = d.header_id AND p.item_id = d.item_id AND p.item_type = d.item_type
            LEFT JOIN ($jasa)    j ON j.cr_detail_id = d.id
            WHERE d.header_id = ?
            ORDER BY d.id ASC
        ";

        $query = $this->db->query($sql, [(int)$header_id]);
        return $query ? $query->result() : [];
    }

    /**
     * IRF Tracking (user 2026-09-15): "IRF itu sampai mana — masih IRF saja,
     * sampai di PR, atau sudah di PO".
     *
     * Rantai per baris item:
     *   consumable_request_details
     *     -> pr_detail_cr_links (qty IRF yang dibawa ke baris PR itu)
     *     -> pr_details / pr_header
     *     -> po_details.pr_detail_id / po_header
     *     -> penerimaan: sgr_details (inventory/mesin) atau grn_details
     *        (material, penyelesaian jasa)
     * Pengeluaran ke user dibaca dari get_item_breakdown()/get_issuance_docs().
     *
     * Satu baris PR bisa menggabungkan beberapa IRF, jadi qty PO/penerimaan
     * adalah qty BARIS PR/PO tersebut (bisa lebih besar dari qty IRF ini);
     * link_qty menunjukkan porsi IRF ini.
     *
     * @return array [cr_detail_id => ['prs' => [ {pr..., 'pos' => [ {po..., 'receipts' => [...]} ]} ]]]
     */
    public function get_tracking($header_id) {
        $links = $this->db->query("
            SELECT d.id AS cr_detail_id, l.qty AS link_qty,
                   pd.id AS pr_detail_id, pd.qty AS pr_qty,
                   pr.id AS pr_id, pr.pr_number, pr.type AS pr_type, pr.status AS pr_status, pr.pr_date
            FROM consumable_request_details d
            JOIN pr_detail_cr_links l ON l.cr_detail_id = d.id
            JOIN pr_details pd ON pd.id = l.pr_detail_id
            JOIN pr_header pr ON pr.id = pd.pr_header_id
            WHERE d.header_id = ?
            ORDER BY pr.id, pd.id
        ", [(int) $header_id])->result();
        if (empty($links)) return [];

        $pr_detail_ids = array_unique(array_map(function ($r) { return (int) $r->pr_detail_id; }, $links));
        $po_lines = $this->db->query("
            SELECT pod.id AS po_detail_id, pod.pr_detail_id, pod.qty AS po_qty,
                   po.id AS po_id, po.po_number, po.type AS po_type, po.status AS po_status, po.po_date
            FROM po_details pod
            JOIN po_header po ON po.id = pod.po_header_id
            WHERE pod.pr_detail_id IN (" . implode(',', $pr_detail_ids) . ")
            ORDER BY po.id, pod.id
        ")->result();

        $receipts_by_po_detail = [];
        if ($po_lines) {
            $po_detail_ids = implode(',', array_unique(array_map(function ($r) { return (int) $r->po_detail_id; }, $po_lines)));
            $rows = $this->db->query("
                SELECT 'sgr' AS kind, sd.source_doc_detail_id AS po_detail_id, s.id AS doc_id, s.sgr_number AS doc_number,
                       s.status, s.sgr_date AS doc_date, sd.receive_qty, s.warehouse_type
                FROM sgr_details sd JOIN sgr_header s ON s.id = sd.sgr_header_id
                WHERE sd.source_doc_type = 'PO' AND sd.source_doc_detail_id IN ($po_detail_ids)
                UNION ALL
                SELECT 'grn', gd.source_doc_detail_id, g.id, g.grn_number,
                       g.status, g.grn_date, gd.receive_qty, g.warehouse_type
                FROM grn_details gd JOIN grn_header g ON g.id = gd.grn_header_id
                WHERE gd.source_doc_type = 'PO' AND gd.source_doc_detail_id IN ($po_detail_ids)
                ORDER BY doc_date, doc_id
            ")->result();
            foreach ($rows as $r) $receipts_by_po_detail[(int) $r->po_detail_id][] = $r;
        }

        $pos_by_pr_detail = [];
        foreach ($po_lines as $po) {
            $po->receipts = $receipts_by_po_detail[(int) $po->po_detail_id] ?? [];
            $po->received_qty = 0.0;
            foreach ($po->receipts as $rc) {
                if ($rc->status === 'Completed') $po->received_qty += (float) $rc->receive_qty;
            }
            $pos_by_pr_detail[(int) $po->pr_detail_id][] = $po;
        }

        $out = [];
        foreach ($links as $l) {
            $l->pos = $pos_by_pr_detail[(int) $l->pr_detail_id] ?? [];
            $out[(int) $l->cr_detail_id]['prs'][] = $l;
        }
        return $out;
    }

    /**
     * Riwayat dokumen pengeluaran (Consumable Out) yang menarik dari IRF ini,
     * termasuk baris item tiap dokumen.
     */
    public function get_issuance_docs($header_id) {
        $sql = "
            SELECT co_h.id, co_h.doc_number, co_h.issuance_date, co_h.recipient,
                   co_h.department, co_h.purpose, co_h.notes, co_h.status,
                   co_h.confirmed_at, co_h.cancelled_at,
                   u.fullname  AS created_by_name,
                   uc.fullname AS confirmed_by_name,
                   COALESCE(SUM(co_d.qty_issued), 0) AS total_qty,
                   COUNT(co_d.id) AS total_lines
            FROM consumable_out_header co_h
            LEFT JOIN consumable_out_details co_d ON co_d.header_id = co_h.id
            LEFT JOIN users u  ON u.id  = co_h.created_by
            LEFT JOIN users uc ON uc.id = co_h.confirmed_by
            WHERE co_h.request_id = ?
            GROUP BY co_h.id
            ORDER BY co_h.issuance_date ASC, co_h.id ASC
        ";
        $query = $this->db->query($sql, [(int)$header_id]);
        $docs  = $query ? $query->result() : [];

        if (empty($docs)) return [];

        $ids = array_map(function ($d) { return (int)$d->id; }, $docs);
        $ids_csv = implode(',', $ids);

        $lines = $this->db->query("
            SELECT header_id, item_id, item_type, item_code, item_name, item_unit, qty_issued, notes
            FROM consumable_out_details
            WHERE header_id IN ($ids_csv)
            ORDER BY id ASC
        ")->result();

        $by_doc = [];
        foreach ($lines as $l) {
            $by_doc[(int)$l->header_id][] = $l;
        }
        foreach ($docs as $d) {
            $d->lines = $by_doc[(int)$d->id] ?? [];
        }

        return $docs;
    }

    /**
     * Distinct department untuk dropdown filter.
     */
    public function get_departments() {
        $query = $this->db->query("
            SELECT DISTINCT department
            FROM consumable_request_header
            WHERE department IS NOT NULL AND department != ''
            ORDER BY department ASC
        ");
        return $query ? $query->result() : [];
    }

    /**
     * Distinct status IRF untuk dropdown filter.
     */
    public function get_statuses() {
        $query = $this->db->query("
            SELECT DISTINCT status
            FROM consumable_request_header
            WHERE status IS NOT NULL AND status != ''
            ORDER BY status ASC
        ");
        return $query ? $query->result() : [];
    }
}
