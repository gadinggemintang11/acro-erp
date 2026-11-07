<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Qa_sampling_model extends CI_Model {

    private $tbl_header = 'qa_sampling_header';
    private $tbl_detail = 'qa_sampling_detail';
    private $tbl_ledger = 'stock_ledger';

    // Transaction types untuk perhitungan saldo (HARUS konsisten dengan
    // Stock_ledger_model & LapMutasiWIP_model)
    private $warehouse_in_types  = ['in', 'IN_RETURN_WIP', 'ADJ_IN', 'adjustment', 'INITIAL_STOCK'];
    private $warehouse_out_types = ['OUT_ISSUE', 'production_out', 'OUT_SHIPMENT', 'OUT_SCRAP_BC',
                                    'ADJ_OUT', 'RTN_SUPPLIER', 'IN_SCRAP_WH', 'OUT_CONSUMABLE',
                                    'NG', 'OUT_QA_SAMPLE'];

    public function __construct() {
        parent::__construct();
    }

    // ============================================================
    // DOC NO
    // ============================================================
    public function generate_doc_no() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('qa_sampling', 'qa_sampling_header', 'doc_no');
    }

    // ============================================================
    // SALDO GUDANG (untuk pemilihan item warehouse)
    // ============================================================
    public function get_warehouse_balance($item_id, $item_type) {
        $in_list  = "'" . implode("','", $this->warehouse_in_types) . "'";
        $out_list = "'" . implode("','", $this->warehouse_out_types) . "'";
        $sql = "
            SELECT
                IFNULL(SUM(CASE WHEN transaction_type IN ({$in_list}) THEN qty_in ELSE 0 END), 0)
              - IFNULL(SUM(CASE WHEN transaction_type IN ({$out_list}) THEN qty_out ELSE 0 END), 0)
              AS balance
            FROM {$this->tbl_ledger}
            WHERE item_id = ? AND item_type = ?
              AND transaction_type NOT IN ('wip_in', 'wip_out', 'OUT_QA_SAMPLE_WIP')
        ";
        $row = $this->db->query($sql, [$item_id, $item_type])->row();
        return $row ? (float)$row->balance : 0;
    }

    // ============================================================
    // SALDO WIP (formula identik LapMutasiWIP_model)
    // ============================================================
    public function get_wip_balance($item_id, $item_type) {
        $sql = "
            SELECT
                SUM(CASE WHEN transaction_type = 'wip_in'
                    THEN GREATEST(COALESCE(qty_in,0), COALESCE(qty_out,0)) ELSE 0 END)
              - SUM(CASE WHEN transaction_type = 'wip_out' THEN qty_out ELSE 0 END)
              - SUM(CASE WHEN transaction_type = 'IN_RETURN_WIP' THEN qty_in ELSE 0 END)
              - SUM(CASE WHEN transaction_type = 'OUT_QA_SAMPLE_WIP' THEN qty_out ELSE 0 END)
              AS balance
            FROM {$this->tbl_ledger}
            WHERE item_id = ? AND item_type = ?
              AND transaction_type IN ('wip_in', 'wip_out', 'IN_RETURN_WIP', 'OUT_QA_SAMPLE_WIP')
        ";
        $row = $this->db->query($sql, [$item_id, $item_type])->row();
        return $row ? (float)$row->balance : 0;
    }

    // ============================================================
    // CARI ITEM (search) — warehouse
    // ============================================================
    public function search_warehouse_items($keyword, $limit = 25) {
        $kw = '%' . $this->db->escape_like_str($keyword) . '%';
        $in_list  = "'" . implode("','", $this->warehouse_in_types) . "'";
        $out_list = "'" . implode("','", $this->warehouse_out_types) . "'";

        $sql = "
            SELECT * FROM (
                (SELECT 'material' AS item_type, id AS item_id,
                        kode_material AS code, nama_material AS name, satuan AS uom
                 FROM materials WHERE status='active'
                   AND (kode_material LIKE ? OR nama_material LIKE ?))
                UNION ALL
                (SELECT 'semifinished' AS item_type, id AS item_id,
                        kode_barang AS code, nama_barang AS name, satuan AS uom
                 FROM semifinished_goods WHERE status='active'
                   AND (kode_barang LIKE ? OR nama_barang LIKE ?))
                UNION ALL
                (SELECT 'finished_goods' AS item_type, id AS item_id,
                        kode_barang AS code, nama_barang AS name, satuan AS uom
                 FROM finished_goods WHERE status='active'
                   AND (kode_barang LIKE ? OR nama_barang LIKE ?))
                UNION ALL
                (SELECT 'inventory' AS item_type, id AS item_id,
                        kode_barang AS code, nama_barang AS name, satuan AS uom
                 FROM inventories WHERE status='Active'
                   AND (kode_barang LIKE ? OR nama_barang LIKE ?))
            ) AS items
            LIMIT " . (int)$limit;

        $items = $this->db->query($sql, [$kw,$kw,$kw,$kw,$kw,$kw,$kw,$kw])->result();

        // Attach balance
        foreach ($items as $it) {
            $it->balance = $this->get_warehouse_balance($it->item_id, $it->item_type);
        }
        // Hanya tampilkan yang ada stock
        return array_values(array_filter($items, function($x){ return $x->balance > 0; }));
    }

    // ============================================================
    // CARI ITEM (search) — WIP
    // ============================================================
    public function search_wip_items($keyword, $limit = 25) {
        $kw = '%' . $this->db->escape_like_str($keyword) . '%';

        $sql = "
            SELECT * FROM (
                (SELECT 'material' AS item_type, id AS item_id,
                        kode_material AS code, nama_material AS name, satuan AS uom
                 FROM materials WHERE status='active'
                   AND (kode_material LIKE ? OR nama_material LIKE ?))
                UNION ALL
                (SELECT 'semifinished' AS item_type, id AS item_id,
                        kode_barang AS code, nama_barang AS name, satuan AS uom
                 FROM semifinished_goods WHERE status='active'
                   AND (kode_barang LIKE ? OR nama_barang LIKE ?))
            ) AS items
            LIMIT " . (int)$limit;

        $items = $this->db->query($sql, [$kw,$kw,$kw,$kw])->result();
        foreach ($items as $it) {
            $it->balance = $this->get_wip_balance($it->item_id, $it->item_type);
        }
        return array_values(array_filter($items, function($x){ return $x->balance > 0; }));
    }

    // ============================================================
    // CRUD
    // ============================================================
    public function create_header($data) {
        $this->db->insert($this->tbl_header, $data);
        return $this->db->insert_id();
    }

    public function update_header($id, $data) {
        return $this->db->where('id', $id)->update($this->tbl_header, $data);
    }

    public function delete_header($id) {
        return $this->db->where('id', $id)->delete($this->tbl_header);
    }

    public function insert_detail($data) {
        $this->db->insert($this->tbl_detail, $data);
        return $this->db->insert_id();
    }

    public function delete_details_by_header($header_id) {
        return $this->db->where('header_id', $header_id)->delete($this->tbl_detail);
    }

    public function get_header($id) {
        return $this->db->select('h.*, u.fullname AS requester_name, ua.fullname AS approver_name')
                        ->from($this->tbl_header . ' h')
                        ->join('users u', 'u.id = h.requester_id', 'left')
                        ->join('users ua', 'ua.id = h.approved_by', 'left')
                        ->where('h.id', $id)
                        ->get()->row();
    }

    public function get_details($header_id) {
        $sql = "
            SELECT d.*,
                CASE d.item_type
                    WHEN 'material'       THEN (SELECT kode_material  FROM materials         WHERE id = d.item_id)
                    WHEN 'semifinished'   THEN (SELECT kode_barang    FROM semifinished_goods WHERE id = d.item_id)
                    WHEN 'finished_goods' THEN (SELECT kode_barang    FROM finished_goods    WHERE id = d.item_id)
                    WHEN 'inventory'      THEN (SELECT kode_barang FROM inventories      WHERE id = d.item_id)
                END AS item_code,
                CASE d.item_type
                    WHEN 'material'       THEN (SELECT nama_material  FROM materials         WHERE id = d.item_id)
                    WHEN 'semifinished'   THEN (SELECT nama_barang    FROM semifinished_goods WHERE id = d.item_id)
                    WHEN 'finished_goods' THEN (SELECT nama_barang    FROM finished_goods    WHERE id = d.item_id)
                    WHEN 'inventory'      THEN (SELECT nama_barang FROM inventories      WHERE id = d.item_id)
                END AS item_name,
                ph.production_number,
                (SELECT fullname FROM users WHERE id = d.tested_by) AS tester_name
            FROM {$this->tbl_detail} d
            LEFT JOIN production_header ph ON ph.id = d.production_header_id
            WHERE d.header_id = ?
            ORDER BY d.id ASC
        ";
        return $this->db->query($sql, [$header_id])->result();
    }

    public function get_detail($id) {
        return $this->db->where('id', $id)->get($this->tbl_detail)->row();
    }

    // ============================================================
    // LIST (index)
    // ============================================================
    public function get_all($filters = [], $limit = 25, $offset = 0) {
        $this->db->select('h.*, u.fullname AS requester_name')
                 ->from($this->tbl_header . ' h')
                 ->join('users u', 'u.id = h.requester_id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('h.status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $this->db->where('h.doc_date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('h.doc_date <=', $filters['date_to']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start()
                     ->like('h.doc_no', $filters['search'])
                     ->or_like('h.purpose', $filters['search'])
                     ->group_end();
        }

        $this->db->order_by('h.doc_date', 'DESC')
                 ->order_by('h.id', 'DESC');
        if ($limit > 0) $this->db->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_all($filters = []) {
        $this->db->from($this->tbl_header . ' h');
        if (!empty($filters['status']))    $this->db->where('h.status', $filters['status']);
        if (!empty($filters['date_from'])) $this->db->where('h.doc_date >=', $filters['date_from']);
        if (!empty($filters['date_to']))   $this->db->where('h.doc_date <=', $filters['date_to']);
        if (!empty($filters['search'])) {
            $this->db->group_start()
                     ->like('h.doc_no', $filters['search'])
                     ->or_like('h.purpose', $filters['search'])
                     ->group_end();
        }
        return $this->db->count_all_results();
    }

    // ============================================================
    // POST (kurangi stock) — transactional
    // ============================================================
    public function post_sampling($header_id, $user_id) {
        $header = $this->get_header($header_id);
        if (!$header) return ['status' => false, 'message' => 'Header tidak ditemukan.'];
        if ($header->status !== 'Approved') {
            return ['status' => false, 'message' => 'Hanya dokumen status Approved yang bisa di-post.'];
        }

        $details = $this->get_details($header_id);
        if (empty($details)) {
            return ['status' => false, 'message' => 'Tidak ada item untuk di-post.'];
        }

        $this->db->trans_begin();

        $now = date('Y-m-d H:i:s');
        $tx_date = $header->doc_date . ' ' . date('H:i:s');

        foreach ($details as $d) {
            // Validasi qty available
            if ($d->source === 'warehouse') {
                $available = $this->get_warehouse_balance($d->item_id, $d->item_type);
                $tx_type = 'OUT_QA_SAMPLE';
            } else {
                $available = $this->get_wip_balance($d->item_id, $d->item_type);
                $tx_type = 'OUT_QA_SAMPLE_WIP';
            }

            if ((float)$d->qty_sample > $available + 0.0001) {
                $this->db->trans_rollback();
                return ['status' => false,
                        'message' => 'Stok tidak cukup untuk item '. $d->item_id .
                                     ' ('. $d->source .'). Tersedia: '. $available .
                                     ', diminta: '. $d->qty_sample];
            }

            // Hitung balance baru
            $last_balance = $this->_get_last_balance($d->item_id, $d->item_type, $d->source);
            $new_balance = $last_balance - (float)$d->qty_sample;

            $ledger = [
                'item_id'          => $d->item_id,
                'item_type'        => $d->item_type,
                'transaction_date' => $tx_date,
                'transaction_type' => $tx_type,
                'transaction_id'   => $d->id,
                'source_doc_type'  => 'QA_SAMPLE',
                'source_doc_id'    => $header_id,
                'qty_in'           => 0,
                'qty_out'          => (float)$d->qty_sample,
                'unit_value'       => 0,
                'balance'          => $new_balance
            ];
            $this->db->insert($this->tbl_ledger, $ledger);
            $ledger_id = $this->db->insert_id();

            $this->db->where('id', $d->id)
                     ->update($this->tbl_detail, ['ledger_id' => $ledger_id]);
        }

        $this->db->where('id', $header_id)
                 ->update($this->tbl_header, [
                    'status'    => 'Posted',
                    'posted_by' => $user_id,
                    'posted_at' => $now
                 ]);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Transaksi DB gagal.'];
        }
        $this->db->trans_commit();
        return ['status' => true, 'message' => 'Sampling berhasil di-post.'];
    }

    /**
     * Get last balance untuk item, scope sesuai source.
     */
    private function _get_last_balance($item_id, $item_type, $source) {
        if ($source === 'warehouse') {
            $in_list  = "'" . implode("','", $this->warehouse_in_types) . "'";
            $out_list = "'" . implode("','", $this->warehouse_out_types) . "'";
            $sql = "SELECT
                      IFNULL(SUM(CASE WHEN transaction_type IN ({$in_list}) THEN qty_in ELSE 0 END), 0)
                    - IFNULL(SUM(CASE WHEN transaction_type IN ({$out_list}) THEN qty_out ELSE 0 END), 0)
                      AS bal
                    FROM {$this->tbl_ledger}
                    WHERE item_id=? AND item_type=?
                      AND transaction_type NOT IN ('wip_in','wip_out','OUT_QA_SAMPLE_WIP')";
        } else {
            $sql = "SELECT
                      SUM(CASE WHEN transaction_type='wip_in'
                          THEN GREATEST(COALESCE(qty_in,0),COALESCE(qty_out,0)) ELSE 0 END)
                    - SUM(CASE WHEN transaction_type='wip_out' THEN qty_out ELSE 0 END)
                    - SUM(CASE WHEN transaction_type='IN_RETURN_WIP' THEN qty_in ELSE 0 END)
                    - SUM(CASE WHEN transaction_type='OUT_QA_SAMPLE_WIP' THEN qty_out ELSE 0 END)
                    AS bal
                    FROM {$this->tbl_ledger}
                    WHERE item_id=? AND item_type=?
                      AND transaction_type IN ('wip_in','wip_out','IN_RETURN_WIP','OUT_QA_SAMPLE_WIP')";
        }
        $row = $this->db->query($sql, [$item_id, $item_type])->row();
        return $row ? (float)$row->bal : 0;
    }

    // ============================================================
    // UPDATE TEST RESULT (per detail)
    // ============================================================
    public function update_test_result($detail_id, $status, $notes, $user_id) {
        return $this->db->where('id', $detail_id)
                        ->update($this->tbl_detail, [
                            'test_status'       => $status,
                            'test_result_notes' => $notes,
                            'tested_by'         => $user_id,
                            'tested_at'         => date('Y-m-d H:i:s')
                        ]);
    }

    public function detail_counts($header_id) {
        $sql = "SELECT
                  COUNT(*) AS total,
                  SUM(test_status='Pending') AS pending,
                  SUM(test_status='Pass') AS pass_cnt,
                  SUM(test_status='Fail') AS fail_cnt
                FROM {$this->tbl_detail} WHERE header_id=?";
        return $this->db->query($sql, [$header_id])->row();
    }

    // ============================================================
    // REPORT (laporan apa saja yang sudah disampling)
    // ============================================================
    public function get_report($filters = []) {
        $sql = "
            SELECT
                h.doc_no, h.doc_date, h.purpose, h.status AS doc_status,
                u.fullname AS requester_name,
                d.source, d.item_id, d.item_type, d.qty_sample, d.uom,
                d.test_status, d.test_result_notes, d.notes,
                d.tested_at, d.production_header_id,
                ph.production_number,
                tu.fullname AS tester_name,
                CASE d.item_type
                    WHEN 'material'       THEN (SELECT kode_material  FROM materials         WHERE id = d.item_id)
                    WHEN 'semifinished'   THEN (SELECT kode_barang    FROM semifinished_goods WHERE id = d.item_id)
                    WHEN 'finished_goods' THEN (SELECT kode_barang    FROM finished_goods    WHERE id = d.item_id)
                    WHEN 'inventory'      THEN (SELECT kode_barang FROM inventories      WHERE id = d.item_id)
                END AS item_code,
                CASE d.item_type
                    WHEN 'material'       THEN (SELECT nama_material  FROM materials         WHERE id = d.item_id)
                    WHEN 'semifinished'   THEN (SELECT nama_barang    FROM semifinished_goods WHERE id = d.item_id)
                    WHEN 'finished_goods' THEN (SELECT nama_barang    FROM finished_goods    WHERE id = d.item_id)
                    WHEN 'inventory'      THEN (SELECT nama_barang FROM inventories      WHERE id = d.item_id)
                END AS item_name
            FROM {$this->tbl_detail} d
            JOIN {$this->tbl_header} h ON h.id = d.header_id
            LEFT JOIN users u ON u.id = h.requester_id
            LEFT JOIN users tu ON tu.id = d.tested_by
            LEFT JOIN production_header ph ON ph.id = d.production_header_id
            WHERE h.status IN ('Posted','Closed')
        ";
        $params = [];
        if (!empty($filters['date_from'])) { $sql .= " AND h.doc_date >= ?"; $params[] = $filters['date_from']; }
        if (!empty($filters['date_to']))   { $sql .= " AND h.doc_date <= ?"; $params[] = $filters['date_to']; }
        if (!empty($filters['source']))    { $sql .= " AND d.source = ?";    $params[] = $filters['source']; }
        if (!empty($filters['test_status'])) { $sql .= " AND d.test_status = ?"; $params[] = $filters['test_status']; }
        if (!empty($filters['item_type'])) { $sql .= " AND d.item_type = ?"; $params[] = $filters['item_type']; }

        $sql .= " ORDER BY h.doc_date DESC, h.id DESC, d.id ASC";
        return $this->db->query($sql, $params)->result();
    }
}
