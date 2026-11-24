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
        // Ditulis ulang 2026-11-09 (redesign Status PO). Dua bug lama:
        //  1. join langsung po_details x grn_details membuat SUM(pod.qty) ganda
        //     kalau satu baris diterima lebih dari sekali (fan-out);
        //  2. hanya GRN yang dihitung -- penerimaan barang general (SGR) dan unit
        //     di Asset Register diabaikan, jadi PO General/Asset selalu
        //     "Not Received".
        // "Diterima" sekarang = GRN Completed + SGR Completed + unit Asset Register,
        // definisi yang sama dgn Ap_auto_model::po_line_status() & detail PO.
        // Dokumen Draft dihitung terpisah (qty_pending). Status 'Complete' kalau
        // SETIAP baris sudah terpenuhi (bukan sekadar total qty campur satuan).
        // Nama kolom lama dipertahankan (dipakai Laporan PO, PO Monitoring,
        // daftar & status PMPO/GMPO).
        $recv_sql = "
            SELECT did, SUM(recv) AS recv, SUM(pend) AS pend FROM (
                SELECT gd.source_doc_detail_id AS did,
                       IF(gh.status = 'Completed', gd.receive_qty, 0) AS recv,
                       IF(gh.status = 'Completed', 0, gd.receive_qty) AS pend
                FROM grn_details gd JOIN grn_header gh ON gh.id = gd.grn_header_id
                WHERE gd.source_doc_type = 'PO'
                UNION ALL
                SELECT sd.source_doc_detail_id,
                       IF(sh.status = 'Completed', sd.receive_qty, 0),
                       IF(sh.status = 'Completed', 0, sd.receive_qty)
                FROM sgr_details sd JOIN sgr_header sh ON sh.id = sd.sgr_header_id
                WHERE sd.source_doc_type = 'PO'
                UNION ALL
                SELECT po_detail_id, 1, 0 FROM asset_register WHERE po_detail_id IS NOT NULL
            ) x GROUP BY did";
        $docs_sql = "
            SELECT po_id, SUM(k <> 'A') AS num_docs, MIN(doc_date) AS first_date FROM (
                SELECT DISTINCT gd.source_doc_header_id AS po_id, 'G' AS k, gh.id AS doc_id, gh.grn_date AS doc_date
                FROM grn_details gd JOIN grn_header gh ON gh.id = gd.grn_header_id
                WHERE gd.source_doc_type = 'PO'
                UNION
                SELECT DISTINCT sd.source_doc_header_id, 'S', sh.id, sh.sgr_date
                FROM sgr_details sd JOIN sgr_header sh ON sh.id = sd.sgr_header_id
                WHERE sd.source_doc_type = 'PO'
                UNION
                -- unit aset: ikut menentukan tanggal penerimaan pertama, tapi
                -- tidak dihitung sebagai dokumen penerimaan (num_grns)
                SELECT po_header_id, 'A', id, tanggal_perolehan
                FROM asset_register WHERE po_header_id IS NOT NULL
            ) y GROUP BY po_id";

        $inner = "
            SELECT
                po.id, po.po_number, po.po_date, po.status, po.type, po.has_ppn, po.ppn_rate, po.currency_code,
                s.nama_supplier,
                pr.pr_number,
                u.fullname AS requester_name,
                COALESCE(ln.qty_po, 0)          AS total_po_qty,
                COALESCE(ln.qty_recv, 0)        AS total_qty_received,
                COALESCE(ln.qty_recv_capped, 0) AS qty_received_capped,
                COALESCE(ln.qty_pending, 0)     AS qty_pending,
                COALESCE(ln.n_lines, 0)         AS n_lines,
                COALESCE(ln.n_full, 0)          AS n_lines_full,
                COALESCE(dc.num_docs, 0)        AS num_grns,
                dc.first_date                   AS first_grn_date,
                COALESCE(ln.total_value, 0)     AS total_value,
                CASE
                    WHEN COALESCE(ln.qty_recv_capped, 0) <= 0 THEN 'Not Received'
                    WHEN ln.n_full >= ln.n_lines THEN 'Complete'
                    ELSE 'Partial'
                END AS receipt_status,
                CASE
                    WHEN dc.first_date IS NOT NULL THEN DATEDIFF(dc.first_date, po.po_date)
                    ELSE DATEDIFF(CURDATE(), po.po_date)
                END AS days_to_warehouse_in
            FROM po_header po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN pr_header pr ON po.pr_header_id = pr.id
            LEFT JOIN users u ON pr.created_by = u.id
            LEFT JOIN (
                SELECT pod.po_header_id,
                       COUNT(*) AS n_lines,
                       SUM(pod.qty) AS qty_po,
                       SUM(pod.total_local) AS total_value,
                       SUM(COALESCE(r.recv, 0)) AS qty_recv,
                       SUM(LEAST(COALESCE(r.recv, 0), pod.qty)) AS qty_recv_capped,
                       SUM(COALESCE(r.pend, 0)) AS qty_pending,
                       SUM(CASE WHEN COALESCE(r.recv, 0) >= pod.qty - 0.0001 THEN 1 ELSE 0 END) AS n_full
                FROM po_details pod
                LEFT JOIN ($recv_sql) r ON r.did = pod.id
                GROUP BY pod.po_header_id
            ) ln ON ln.po_header_id = po.id
            LEFT JOIN ($docs_sql) dc ON dc.po_id = po.id
            WHERE 1=1
        ";

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $inner .= " AND (po.po_number LIKE '%$term%' ESCAPE '!' OR s.nama_supplier LIKE '%$term%' ESCAPE '!' OR pr.pr_number LIKE '%$term%' ESCAPE '!')";
        }

        if (!empty($filters['supplier_id'])) {
            $inner .= " AND po.supplier_id = " . (int)$filters['supplier_id'];
        }

        // Array = gabungan beberapa tipe sekaligus (GMPO = non_produksi + jasa
        // + asset, user 2026-09-11); string = satu tipe.
        $VALID_TYPES = ['produksi','non_produksi','jasa','asset'];
        if (!empty($filters['type']) && is_array($filters['type'])) {
            $safe = array_values(array_intersect($filters['type'], $VALID_TYPES));
            if (!empty($safe)) {
                $inner .= " AND po.type IN ('" . implode("','", $safe) . "')";
            }
        } elseif (!empty($filters['type']) && in_array($filters['type'], $VALID_TYPES, true)) {
            $inner .= " AND po.type = '" . $filters['type'] . "'";
        }

        if (!empty($filters['status'])) {
            $inner .= " AND po.status = " . $this->db->escape((string) $filters['status']);
        }
        // Rentang tanggal PO (Laporan PO, 2026-11-09)
        if (!empty($filters['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) {
            $inner .= " AND po.po_date >= " . $this->db->escape($filters['date_from']);
        }
        if (!empty($filters['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'])) {
            $inner .= " AND po.po_date <= " . $this->db->escape($filters['date_to']);
        }

        // receipt_status adalah kolom turunan -> difilter di query luar.
        $sql = "SELECT * FROM ($inner) t WHERE 1=1";
        if (!empty($filters['receipt_status'])) {
            $sql .= " AND t.receipt_status = " . $this->db->escape((string) $filters['receipt_status']);
        }
        $sql .= " ORDER BY t.po_date DESC, t.id DESC";

        $query = $this->db->query($sql);
        return $query ? $query->result() : [];
    }

    /**
     * Data halaman Status PO (pmpo_status / gmpo_status), redesign 2026-11-09.
     * Kartu ringkasan dihitung dari semua PO dalam cakupan pencarian/supplier --
     * TIDAK ikut filter status/penerimaan (dulu dihitung dari hasil filter, jadi
     * kartu lain jadi 0 begitu satu status dipilih). Daftar dipaginasi.
     */
    public function get_status_page($filters, $page = 1, $per_page = 25) {
        $scope = $filters;
        unset($scope['status'], $scope['receipt_status']);
        $all = $this->get_all($scope);

        $status_counts  = [];
        $receipt_counts = ['Not Received' => 0, 'Partial' => 0, 'Complete' => 0];
        $open_receipts  = 0; // PO disetujui yang barangnya belum lengkap
        foreach ($all as $r) {
            $status_counts[$r->status] = ($status_counts[$r->status] ?? 0) + 1;
            if (in_array($r->status, ['Confirmed', 'Closed'], true)) {
                $receipt_counts[$r->receipt_status] = ($receipt_counts[$r->receipt_status] ?? 0) + 1;
                if ($r->status === 'Confirmed' && $r->receipt_status !== 'Complete') $open_receipts++;
            }
        }

        $st = (string) ($filters['status'] ?? '');
        $rs = (string) ($filters['receipt_status'] ?? '');
        $rows = array_values(array_filter($all, function ($r) use ($st, $rs) {
            if ($st === 'waiting') { if (!in_array($r->status, ['Submitted', 'Approved Head Dept', 'Endorsed'], true)) return false; }
            elseif ($st !== '' && $r->status !== $st) return false;
            if ($rs === 'open') { if ($r->receipt_status === 'Complete') return false; }   // belum lengkap
            elseif ($rs !== '' && $r->receipt_status !== $rs) return false;
            return true;
        }));

        $total = count($rows);
        $pages = max(1, (int) ceil($total / $per_page));
        $page  = min(max(1, (int) $page), $pages);
        return [
            'pos'            => array_slice($rows, ($page - 1) * $per_page, $per_page),
            'total'          => $total,
            'all_count'      => count($all),
            'page'           => $page,
            'total_pages'    => $pages,
            'status_counts'  => $status_counts,
            'receipt_counts' => $receipt_counts,
            'open_receipts'  => $open_receipts,
        ];
    }

    public function get_po_detail($po_id) {
        // Ditulis ulang 2026-11-09 (redesign Laporan PO), definisi penerimaan
        // sama dgn get_all(): GRN/SGR Completed + unit Asset Register; dokumen
        // Draft terpisah (qty_draft). Dulu: GRN berstatus apa pun dihitung,
        // SGR & aset diabaikan, nama item aset kosong, header ikut fan-out.
        // Kolom lama dipertahankan (pdf_export.php): qty_received, qty_pending
        // (= SISA, bukan draft), item_receipt_status, satuan, line_total, grns.
        $po_id = (int) $po_id;
        $sql_header = "
            SELECT po.*, s.nama_supplier, s.alamat AS supplier_address, s.telepon AS supplier_phone,
                   pr.pr_number, pr.pr_date, pr.purpose AS pr_description,
                   u.fullname AS requester_name, u.email AS requester_email,
                   cu.fullname AS creator_name, cf.fullname AS confirmer_name
            FROM po_header po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            LEFT JOIN pr_header pr ON po.pr_header_id = pr.id
            LEFT JOIN users u ON pr.created_by = u.id
            LEFT JOIN users cu ON po.created_by = cu.id
            LEFT JOIN users cf ON po.confirmed_by = cf.id
            WHERE po.id = ?";
        $header = $this->db->query($sql_header, [$po_id])->row();
        if (!$header) {
            return null;
        }

        // Penerimaan per baris
        $recv = [];
        $add = function ($did, $qty, $done) use (&$recv) {
            $did = (int) $did;
            if (!isset($recv[$did])) $recv[$did] = ['done' => 0.0, 'draft' => 0.0];
            $recv[$did][$done ? 'done' : 'draft'] += (float) $qty;
        };
        foreach ($this->db->query("SELECT gd.source_doc_detail_id AS did, gd.receive_qty, gh.status FROM grn_details gd JOIN grn_header gh ON gh.id = gd.grn_header_id WHERE gd.source_doc_type = 'PO' AND gd.source_doc_header_id = ?", [$po_id])->result() as $r) $add($r->did, $r->receive_qty, $r->status === 'Completed');
        foreach ($this->db->query("SELECT sd.source_doc_detail_id AS did, sd.receive_qty, sh.status FROM sgr_details sd JOIN sgr_header sh ON sh.id = sd.sgr_header_id WHERE sd.source_doc_type = 'PO' AND sd.source_doc_header_id = ?", [$po_id])->result() as $r) $add($r->did, $r->receive_qty, $r->status === 'Completed');
        foreach ($this->db->query("SELECT po_detail_id AS did, COUNT(*) AS n FROM asset_register WHERE po_header_id = ? GROUP BY po_detail_id", [$po_id])->result() as $r) $add($r->did, $r->n, true);

        $sql_details = "
            SELECT
                pod.id, pod.po_header_id, pod.item_id, pod.item_type, pod.description,
                COALESCE(m.kode_material, i.kode_barang, mm.kode_mesin, sf.kode_barang, sc.scrap_code, fg.kode_barang, j.kode_jasa, a.kode_asset, '-') AS item_code,
                COALESCE(m.nama_material, i.nama_barang, mm.nama_mesin, sf.nama_barang, sc.scrap_name, fg.nama_barang, j.nama_jasa, a.nama_asset, pod.description) AS item_name,
                COALESCE(m.satuan, i.satuan, mm.satuan, sf.satuan, sc.unit, fg.satuan, j.satuan, a.satuan, '-') AS satuan,
                pod.qty, pod.base_price, pod.local_price, pod.total_local,
                (pod.qty * pod.base_price) AS line_total,
                pr.pr_number
            FROM po_details pod
            LEFT JOIN materials m ON pod.item_id = m.id AND pod.item_type = 'material'
            LEFT JOIN inventories i ON pod.item_id = i.id AND pod.item_type = 'inventory'
            LEFT JOIN machines mm ON pod.item_id = mm.id AND pod.item_type = 'machine'
            LEFT JOIN semifinished_goods sf ON pod.item_id = sf.id AND pod.item_type = 'semifinished'
            LEFT JOIN master_scrap sc ON pod.item_id = sc.id AND pod.item_type = 'scrap'
            LEFT JOIN finished_goods fg ON pod.item_id = fg.id AND pod.item_type = 'finished_goods'
            LEFT JOIN jasa j ON pod.item_id = j.id AND pod.item_type = 'jasa'
            LEFT JOIN assets a ON pod.item_id = a.id AND pod.item_type = 'asset'
            LEFT JOIN pr_header pr ON pr.id = pod.pr_header_id
            WHERE pod.po_header_id = ?
            ORDER BY pod.id ASC";
        $details = $this->db->query($sql_details, [$po_id])->result();
        foreach ($details as $d) {
            $r = $recv[(int) $d->id] ?? ['done' => 0.0, 'draft' => 0.0];
            $d->qty_received = $r['done'];
            $d->qty_draft    = $r['draft'];
            $d->qty_pending  = max(0, (float) $d->qty - $r['done']);   // sisa
            $d->item_receipt_status = $r['done'] <= 0 ? 'Not Received'
                : ($r['done'] >= (float) $d->qty - 0.0001 ? 'Complete' : 'Partial');
        }

        // GRN (dipakai pdf_export -> get_grn_details per GRN)
        $grns = $this->db->query("
            SELECT gh.id, gh.grn_number, gh.grn_date, gh.status, gh.warehouse_type, gh.qa_status,
                   COUNT(gd.id) AS num_items, SUM(gd.receive_qty) AS total_qty_received
            FROM grn_header gh JOIN grn_details gd ON gd.grn_header_id = gh.id
            WHERE gd.source_doc_type = 'PO' AND gd.source_doc_header_id = ?
            GROUP BY gh.id ORDER BY gh.grn_date DESC, gh.id DESC", [$po_id])->result();

        // Semua dokumen penerimaan (GRN + SGR) utk halaman detail
        $receipts = [];
        foreach ($grns as $g) {
            $receipts[] = (object) ['kind' => $g->warehouse_type === 'jasa' ? 'service' : 'grn', 'id' => $g->id, 'number' => $g->grn_number,
                'date' => $g->grn_date, 'status' => $g->status, 'qa_status' => $g->qa_status, 'num_items' => $g->num_items, 'qty' => $g->total_qty_received];
        }
        foreach ($this->db->query("
            SELECT sh.id, sh.sgr_number, sh.sgr_date, sh.status, COUNT(sd.id) AS num_items, SUM(sd.receive_qty) AS qty
            FROM sgr_header sh JOIN sgr_details sd ON sd.sgr_header_id = sh.id
            WHERE sd.source_doc_type = 'PO' AND sd.source_doc_header_id = ?
            GROUP BY sh.id", [$po_id])->result() as $g) {
            $receipts[] = (object) ['kind' => 'sgr', 'id' => $g->id, 'number' => $g->sgr_number, 'date' => $g->sgr_date,
                'status' => $g->status, 'qa_status' => null, 'num_items' => $g->num_items, 'qty' => $g->qty];
        }
        usort($receipts, function ($x, $y) { return strcmp((string) $y->date, (string) $x->date); });

        // Tanggal penerimaan pertama (dokumen apa pun + unit aset) -> lead time
        $dates = array_filter(array_map(function ($r) { return $r->date; }, $receipts));
        $ar = $this->db->query("SELECT MIN(tanggal_perolehan) AS d FROM asset_register WHERE po_header_id = ?", [$po_id])->row();
        if ($ar && $ar->d) $dates[] = $ar->d;
        $header->first_grn_date = $dates ? min($dates) : null;
        $header->days_to_warehouse_in = (int) floor((strtotime($header->first_grn_date ?: date('Y-m-d')) - strtotime($header->po_date)) / 86400);

        return [
            'header'   => $header,
            'details'  => $details,
            'grns'     => $grns,
            'receipts' => $receipts,
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
     * Get suppliers for filter dropdown
     */
    public function get_suppliers() {
        return $this->db->order_by('nama_supplier', 'ASC')->get('suppliers')->result();
    }
}
?>
