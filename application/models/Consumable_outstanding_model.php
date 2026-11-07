<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Computes outstanding consumable requests on-the-fly.
 *
 * Outstanding = qty_requested (from consumable_request_details)
 *             - SUM(qty_issued from consumable_out_details)
 *               where the parent consumable_out_header has request_id = CR header id,
 *                     same item_id, and status = 'Confirmed'.
 *
 * Only CRs with status 'Approved' count — yaitu yang sudah lolos 3-stage approval
 * (HOD → Endorsement → Final Approve). 'Submitted' / 'Approved Head Dept' / 'Endorsed'
 * di-exclude karena masih dalam approval pipeline, belum boleh diproses warehouse.
 */
class Consumable_outstanding_model extends CI_Model {

    /**
     * Qty Jasa yang sudah "diselesaikan" via PO Jasa (Purchase_order_jasa::
     * complete_service() -> grn_header warehouse_type='jasa'), ditelusuri
     * balik ke cr_detail_id asalnya lewat pr_detail_cr_links -> pr_details
     * -> po_details.pr_detail_id -> grn_details.source_doc_detail_id.
     * Diperlakukan setara qty_issued utk barang fisik: item Jasa TIDAK
     * pernah punya baris di consumable_out_details (tidak ada gudang yang
     * mengeluarkan jasa), jadi tanpa ini outstanding Jasa permanen 100%
     * walau PO-nya sudah selesai. Kalau satu pr_detail mengagregasi
     * beberapa cr_detail (batch PR), qty completed didistribusi
     * proporsional lewat rasio (qty selesai PO / qty total PR baris itu).
     * Lihat db/migration_pr_detail_cr_links.sql & [[revision_irf_auto_pr_at_issuance]].
     *
     * @return string SQL derived-table, alias `jasa_done`, kolom (cr_detail_id, qty_completed)
     */
    private function _jasa_completed_subquery() {
        return "
            SELECT
                link.cr_detail_id,
                SUM(LEAST(link.qty, link.qty * (COALESCE(comp.completed_qty, 0) / pr_d.qty))) AS qty_completed
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
     * Aggregated list grouped by item across all open CRs.
     * Returns rows with: item_id, item_code, item_name, item_unit,
     *                    total_requested, total_issued, total_outstanding.
     */
    public function get_grouped_by_item($filters = []) {
        $where_extra = '';
        $params = [];

        if (!empty($filters['department'])) {
            $where_extra .= ' AND cr_h.department = ?';
            $params[] = $filters['department'];
        }
        if (!empty($filters['category'])) {
            if (is_array($filters['category'])) {
                $placeholders = implode(',', array_fill(0, count($filters['category']), '?'));
                $where_extra .= " AND cr_h.category IN ($placeholders)";
                $params = array_merge($params, array_values($filters['category']));
            } else {
                $where_extra .= ' AND cr_h.category = ?';
                $params[] = $filters['category'];
            }
        }
        if (!empty($filters['date_from'])) {
            $where_extra .= ' AND cr_h.request_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where_extra .= ' AND cr_h.request_date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['search'])) {
            $where_extra .= ' AND (cr_d.item_code LIKE ? OR cr_d.item_name LIKE ?)';
            $like = '%' . $filters['search'] . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "
            SELECT
                cr_d.item_id,
                cr_d.item_type,
                cr_h.category,
                MAX(cr_d.item_code) AS item_code,
                MAX(cr_d.item_name) AS item_name,
                MAX(cr_d.item_unit) AS item_unit,
                SUM(cr_d.qty_requested) AS total_requested,
                (COALESCE(SUM(issued.qty_issued_sum), 0) + COALESCE(SUM(jasa_done.qty_completed), 0)) AS total_issued,
                (SUM(cr_d.qty_requested) - COALESCE(SUM(issued.qty_issued_sum), 0) - COALESCE(SUM(jasa_done.qty_completed), 0)) AS total_outstanding
            FROM consumable_request_details cr_d
            INNER JOIN consumable_request_header cr_h ON cr_h.id = cr_d.header_id
            LEFT JOIN (
                SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_issued_sum
                FROM consumable_out_details co_d
                INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
            ) issued ON issued.request_id = cr_d.header_id AND issued.item_id = cr_d.item_id AND issued.item_type = cr_d.item_type
            LEFT JOIN (" . $this->_jasa_completed_subquery() . ") jasa_done ON jasa_done.cr_detail_id = cr_d.id
            WHERE cr_h.status = 'Approved'
            $where_extra
            GROUP BY cr_d.item_id, cr_d.item_type, cr_h.category
            HAVING total_outstanding > 0
            ORDER BY MAX(cr_d.item_name) ASC
        ";

        $query = $this->db->query($sql, $params);
        return $query ? $query->result() : [];
    }

    /**
     * Per-CR breakdown for a single item: every CR detail row that still has outstanding.
     * Returns: cr_detail_id, header_id, request_number, request_date, department,
     *          requestor_name, qty_requested, qty_issued, qty_outstanding, item info.
     */
    public function get_breakdown_by_item($item_id, $item_type = 'inventory', $category = null) {
        $params = [(int)$item_id, $item_type];
        $category_filter = '';
        if (!empty($category)) {
            if (is_array($category)) {
                $placeholders = implode(',', array_fill(0, count($category), '?'));
                $category_filter = " AND cr_h.category IN ($placeholders)";
                $params = array_merge($params, array_values($category));
            } else {
                $category_filter = ' AND cr_h.category = ?';
                $params[] = $category;
            }
        }

        $sql = "
            SELECT
                cr_d.id AS cr_detail_id,
                cr_d.header_id,
                cr_d.item_id,
                cr_d.item_type,
                cr_d.item_code,
                cr_d.item_name,
                cr_d.item_unit,
                cr_d.qty_requested,
                (COALESCE(issued.qty_issued_sum, 0) + COALESCE(jasa_done.qty_completed, 0)) AS qty_issued,
                (cr_d.qty_requested - COALESCE(issued.qty_issued_sum, 0) - COALESCE(jasa_done.qty_completed, 0)) AS qty_outstanding,
                cr_h.request_number,
                cr_h.request_date,
                cr_h.department,
                cr_h.category,
                cr_h.requestor_name,
                cr_h.status AS cr_status
            FROM consumable_request_details cr_d
            INNER JOIN consumable_request_header cr_h ON cr_h.id = cr_d.header_id
            LEFT JOIN (
                SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_issued_sum
                FROM consumable_out_details co_d
                INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
            ) issued ON issued.request_id = cr_d.header_id AND issued.item_id = cr_d.item_id AND issued.item_type = cr_d.item_type
            LEFT JOIN (" . $this->_jasa_completed_subquery() . ") jasa_done ON jasa_done.cr_detail_id = cr_d.id
            WHERE cr_h.status = 'Approved'
              AND cr_d.item_id = ?
              AND cr_d.item_type = ?
              $category_filter
              AND (cr_d.qty_requested - COALESCE(issued.qty_issued_sum, 0) - COALESCE(jasa_done.qty_completed, 0)) > 0
            ORDER BY cr_h.request_date ASC, cr_h.id ASC
        ";

        $query = $this->db->query($sql, $params);
        return $query ? $query->result() : [];
    }

    /**
     * Get a single CR detail row with computed outstanding (for issuance pre-fill).
     */
    public function get_outstanding_for_cr($cr_header_id) {
        $sql = "
            SELECT
                cr_d.id AS cr_detail_id,
                cr_d.item_id,
                cr_d.item_type,
                cr_d.item_code,
                cr_d.item_name,
                cr_d.item_unit,
                cr_d.qty_requested,
                COALESCE(issued.qty_issued_sum, 0) AS qty_issued,
                (cr_d.qty_requested - COALESCE(issued.qty_issued_sum, 0)) AS qty_outstanding,
                cr_d.notes
            FROM consumable_request_details cr_d
            LEFT JOIN (
                SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_issued_sum
                FROM consumable_out_details co_d
                INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
            ) issued ON issued.request_id = cr_d.header_id AND issued.item_id = cr_d.item_id AND issued.item_type = cr_d.item_type
            WHERE cr_d.header_id = ?
        ";

        $query = $this->db->query($sql, [(int)$cr_header_id]);
        return $query ? $query->result() : [];
    }

    /**
     * Distinct department list for filter dropdown.
     */
    public function get_departments() {
        $sql = "
            SELECT DISTINCT cr_h.department
            FROM consumable_request_header cr_h
            WHERE cr_h.status = 'Approved'
              AND cr_h.department IS NOT NULL
              AND cr_h.department != ''
            ORDER BY cr_h.department ASC
        ";
        $query = $this->db->query($sql);
        return $query ? $query->result() : [];
    }

    /**
     * Validate qty_outstanding for given CR detail IDs (used before creating PR).
     * Returns array keyed by cr_detail_id with current outstanding values.
     */
    public function get_current_outstanding_map($cr_detail_ids) {
        if (empty($cr_detail_ids)) return [];
        $ids_safe = array_map('intval', $cr_detail_ids);
        $ids_csv  = implode(',', $ids_safe);

        $sql = "
            SELECT
                cr_d.id AS cr_detail_id,
                cr_d.header_id,
                cr_d.item_id,
                cr_d.item_type,
                cr_d.item_code,
                cr_d.item_name,
                cr_d.item_unit,
                cr_h.request_number,
                cr_h.department,
                cr_h.category,
                cr_h.created_by,
                (cr_d.qty_requested - COALESCE(issued.qty_issued_sum, 0) - COALESCE(jasa_done.qty_completed, 0)) AS qty_outstanding
            FROM consumable_request_details cr_d
            INNER JOIN consumable_request_header cr_h ON cr_h.id = cr_d.header_id
            LEFT JOIN (
                SELECT co_h.request_id, co_d.item_id, co_d.item_type, SUM(co_d.qty_issued) AS qty_issued_sum
                FROM consumable_out_details co_d
                INNER JOIN consumable_out_header co_h ON co_h.id = co_d.header_id
                WHERE co_h.status = 'Confirmed' AND co_h.request_id IS NOT NULL
                GROUP BY co_h.request_id, co_d.item_id, co_d.item_type
            ) issued ON issued.request_id = cr_d.header_id AND issued.item_id = cr_d.item_id AND issued.item_type = cr_d.item_type
            LEFT JOIN (" . $this->_jasa_completed_subquery() . ") jasa_done ON jasa_done.cr_detail_id = cr_d.id
            WHERE cr_d.id IN ($ids_csv)
        ";

        $query = $this->db->query($sql);
        $rows = $query ? $query->result() : [];
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->cr_detail_id] = $r;
        }
        return $map;
    }

    /**
     * Qty yang BENAR-BENAR perlu dibelikan lewat PR untuk baris IRF terpilih.
     *
     * User 2026-09-15: "item B minta 10 sedangkan stocknya hanya ada 2
     * otomatis harusnya PR nya 8 bukan 10". Sebelumnya qty PR = outstanding
     * (diminta - sudah dikeluarkan), tanpa melihat stok gudang maupun PR yang
     * sudah pernah dibuat -> PR kebesaran, dan tombol "Buat PR Sisanya" bisa
     * diklik berulang (data lokal: satu baris IRF nyangkut di 3 PR).
     *
     * Per baris:
     *   qty_on_pr      = qty baris ini yang sudah ada di PR aktif (bukan
     *                    Rejected/Cancelled) tapi BELUM diterima. Yang sudah
     *                    diterima tidak dihitung lagi di sini karena sudah
     *                    masuk stok (atau, utk Jasa, sudah mengurangi outstanding).
     *   qty_from_stock = bagian yang bisa dipenuhi stok gudang kategori IRF-nya
     *                    (inventory/mesin saja; Asset & Jasa tidak punya stok).
     *                    Stok dibagi urut IRF paling lama lebih dulu.
     *   qty_pr         = outstanding - qty_on_pr - qty_from_stock (min 0)
     *
     * Stok tidak di-booking: kalau stok itu keburu dipakai IRF lain sebelum
     * dikeluarkan, sisanya muncul lagi sebagai kebutuhan PR.
     *
     * @return array [cr_detail_id => row get_current_outstanding_map() + kolom di atas
     *               + stock_total (stok gudang) & stock_available (sisa stok saat baris ini dihitung)]
     */
    /**
     * Dokumen PR yang sudah dibuat untuk baris outstanding ini (user
     * 2026-09-20: "jika atas outstanding itu sudah dibuat PR harusnya dikasih
     * keterangan, dan ada link ke dokumen PR nya"). Dipakai untuk keterangan
     * di kolom "Sudah di PR".
     *
     * PR Rejected/Cancelled sengaja TIDAK ikut — sama dengan perhitungan
     * qty_on_pr di get_pr_need_map(), supaya angka & keterangannya sejalan.
     *
     * @return array [cr_detail_id => [ {pr_id, pr_number, type, status, qty}, ... ]]
     */
    public function get_pr_docs_map($cr_detail_ids) {
        $ids = array_filter(array_map('intval', (array) $cr_detail_ids));
        if (empty($ids)) return [];
        $ids_csv = implode(',', $ids);

        $rows = $this->db->query("
            SELECT link.cr_detail_id,
                   pr_h.id AS pr_id, pr_h.pr_number, pr_h.type, pr_h.status,
                   SUM(link.qty) AS qty
            FROM pr_detail_cr_links link
            INNER JOIN pr_details pr_d ON pr_d.id = link.pr_detail_id
            INNER JOIN pr_header pr_h  ON pr_h.id = pr_d.pr_header_id
                                      AND pr_h.status NOT IN ('Rejected', 'Cancelled')
            WHERE link.cr_detail_id IN ($ids_csv)
            GROUP BY link.cr_detail_id, pr_h.id, pr_h.pr_number, pr_h.type, pr_h.status
            ORDER BY pr_h.id
        ")->result();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->cr_detail_id][] = $r;
        }
        return $out;
    }

    public function get_pr_need_map($cr_detail_ids) {
        $map = $this->get_current_outstanding_map($cr_detail_ids);
        if (empty($map)) return [];

        $ids_csv = implode(',', array_map('intval', array_keys($map)));
        $on_pr = [];
        $sql = "
            SELECT link.cr_detail_id,
                   SUM(link.qty - LEAST(link.qty, link.qty * COALESCE(rcv.qty_received, 0) / pr_d.qty)) AS qty_on_pr
            FROM pr_detail_cr_links link
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
            WHERE link.cr_detail_id IN ($ids_csv)
            GROUP BY link.cr_detail_id
        ";
        $query = $this->db->query($sql);
        foreach (($query ? $query->result() : []) as $r) {
            $on_pr[(int) $r->cr_detail_id] = max(0, (float) $r->qty_on_pr);
        }

        // IRF paling lama dapat stok lebih dulu.
        $rows = array_values($map);
        usort($rows, function ($a, $b) {
            return [(int) $a->header_id, (int) $a->cr_detail_id] <=> [(int) $b->header_id, (int) $b->cr_detail_id];
        });

        $this->load->model('Stock_ledger_model');
        $stock_left  = []; // sisa stok setelah dibagi ke baris sebelumnya
        $stock_total = []; // stok gudang utuh
        $out = [];
        foreach ($rows as $r) {
            $id          = (int) $r->cr_detail_id;
            $outstanding = max(0, (float) $r->qty_outstanding);
            $itype       = !empty($r->item_type) ? $r->item_type : 'inventory';
            $category    = !empty($r->category) ? $r->category : 'General';

            $r->qty_on_pr = min($outstanding, $on_pr[$id] ?? 0);
            $after_pr     = $outstanding - $r->qty_on_pr;

            $r->stock_total     = 0.0;
            $r->stock_available = 0.0;
            $r->qty_from_stock  = 0.0;
            if (in_array($itype, ['inventory', 'machine'], true) && in_array($category, ['General', 'Produksi'], true)) {
                $key = $itype . '-' . (int) $r->item_id . '-' . $category;
                if (!isset($stock_left[$key])) {
                    $stock_left[$key] = max(0, (float) $this->Stock_ledger_model->get_last_balance((int) $r->item_id, $itype, $category));
                    $stock_total[$key] = $stock_left[$key];
                }
                $r->stock_total     = $stock_total[$key];
                $r->stock_available = $stock_left[$key];
                $r->qty_from_stock  = min($after_pr, $stock_left[$key]);
                $stock_left[$key]  -= $r->qty_from_stock;
            }

            $r->qty_pr = round(max(0, $after_pr - $r->qty_from_stock), 4);
            $out[$id] = $r;
        }
        return $out;
    }
}
