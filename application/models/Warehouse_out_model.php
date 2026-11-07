<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warehouse_out_model extends CI_Model {

    private $table_header = 'warehouse_out_header';
    private $table_detail = 'warehouse_out_details';

    public function __construct() {
        parent::__construct();
    }

    public function create_header($data) {
        // If out_number not provided, generate one so drafts have a reference number
        if (empty($data['out_number'])) {
            $data['out_number'] = $this->generate_rtn_number();
        }
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    public function get_header($id) {
        $this->db->reset_query();
        $this->db->select('h.*, creator.fullname as created_by_name, creator.signature_path as created_by_signature, confirmer.fullname as confirmed_by_name, confirmer.signature_path as confirmed_by_signature');
        $this->db->from($this->table_header . ' h');
        $this->db->join('users creator', 'h.created_by = creator.id', 'left');
        $this->db->join('users confirmer', 'h.confirmed_by = confirmer.id', 'left');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    /**
     * Check if a detail with given grn_detail_id already exists for header
     */
    public function detail_exists($header_id, $grn_detail_id) {
        if (empty($grn_detail_id) || empty($header_id)) return false;
        $this->db->reset_query();
        $this->db->from($this->table_detail);
        $this->db->where('header_id', $header_id);
        $this->db->where('grn_detail_id', $grn_detail_id);
        return $this->db->count_all_results() > 0;
    }

    public function add_detail($data) {
        // Filter data to only include columns that exist in the details table
        $allowed = $this->db->list_fields($this->table_detail);
        $insert = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed)) {
                $insert[$k] = $v;
            } else {
                log_message('debug', "Warehouse_out_model->add_detail: column '{$k}' not present in {$this->table_detail}, skipping.");
            }
        }

        if (empty($insert)) {
            log_message('error', 'Warehouse_out_model->add_detail: no valid columns to insert');
            return false;
        }

        $this->db->insert($this->table_detail, $insert);
        return $this->db->insert_id();
    }

    public function get_list($filters = []) {
        // Select header fields plus supplier name (from supplier FK or legacy nama_supplier)
        // and item_count (number of details) for UI display
        $this->db->select('h.*, COALESCE(s.nama_supplier, h.nama_supplier) AS nama_supplier, COALESCE(dcount.item_count, 0) AS item_count');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 's.id = h.supplier_id', 'left');

        // derived table for detail counts
        $this->db->join('(SELECT header_id, COUNT(*) AS item_count FROM ' . $this->table_detail . ' GROUP BY header_id) dcount', 'dcount.header_id = h.id', 'left');

        if (!empty($filters['start_date'])) { $this->db->where('h.created_at >=', $filters['start_date'].' 00:00:00'); }
        if (!empty($filters['end_date'])) { $this->db->where('h.created_at <=', $filters['end_date'].' 23:59:59'); }
        if (!empty($filters['status'])) { $this->db->where('h.status', $filters['status']); }
        $this->db->order_by('h.id','desc');
        return $this->db->get()->result();
    }

    public function update_header($id, $data) {
        return $this->db->update($this->table_header, $data, ['id'=>$id]);
    }

    public function get_details($header_id) {
        $this->db->select('
            d.*,
            gh.grn_number,
            gd.source_doc_detail_id, gd.source_doc_type,
            pd.qty as po_qty,
            poh.po_number, poh.id as po_header_id,
            s_po.nama_supplier as po_supplier_name,
            s_mir.nama_supplier as mir_supplier_name,
            md.total_qty as mir_total_qty,
            qad.qa_pass_qty, qad.qa_reject_qty, qad.ng_category,
            qh.qa_number,
            COALESCE(s_po.nama_supplier, s_mir.nama_supplier) as supplier_name
        ', FALSE);
        $this->db->from($this->table_detail . ' d');
        $this->db->join('grn_header gh',          'd.grn_id = gh.id',                                               'left');
        $this->db->join('grn_details gd',         'd.grn_detail_id = gd.id',                                        'left');
        $this->db->join('po_details pd',          'gd.source_doc_type = "PO" AND gd.source_doc_detail_id = pd.id',  'left');
        $this->db->join('po_header poh',          'gd.source_doc_type = "PO" AND gd.source_doc_header_id = poh.id', 'left');
        $this->db->join('suppliers s_po',         'poh.supplier_id = s_po.id',                                       'left');
        $this->db->join('mir_header mirh',        'gd.source_doc_type = "MIR" AND gd.source_doc_header_id = mirh.id','left');
        $this->db->join('suppliers s_mir',        'mirh.supplier_id = s_mir.id',                                     'left');
        $this->db->join('mir_details md',         'gd.source_doc_type = "MIR" AND gd.source_doc_detail_id = md.id', 'left');
        $this->db->join('qa_details qad',         'd.qa_detail_id = qad.id',                                        'left');
        $this->db->join('qa_header qh',           'qad.qa_header_id = qh.id',                                       'left');
        $this->db->where('d.header_id', $header_id);
        $rows = $this->db->get()->result();

        foreach ($rows as $r) {
            if (!isset($r->qty_po) || (float)$r->qty_po == 0.0) {
                $r->qty_po = isset($r->po_qty) ? (float)$r->po_qty : (isset($r->mir_total_qty) ? (float)$r->mir_total_qty : 0.0);
            }
        }

        return $rows;
    }

    public function delete_detail($id) {
        $this->db->reset_query();
        $detail = $this->db->select('*')->from($this->table_detail)->where('id', $id)->get()->row();
        if (!$detail) { log_message('error', 'Warehouse_out_model->delete_detail: detail not found id=' . $id); return false; }
        $hdr = $this->db->select('id,status')->from($this->table_header)->where('id', $detail->header_id)->get()->row();
        if (!$hdr || strtolower($hdr->status) !== 'draft') { log_message('error', 'Warehouse_out_model->delete_detail: header not draft id=' . ($hdr->id ?? 'NULL')); return false; }
        $this->db->where('id', $id)->delete($this->table_detail);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Get total qty_return already confirmed for a given grn_detail_id
     */
    public function get_total_returned_for_grn_detail($grn_detail_id) {
        $this->db->reset_query();
        $this->db->select('IFNULL(SUM(d.qty_return),0) as total_returned');
        $this->db->from($this->table_detail . ' d');
        $this->db->join($this->table_header . ' h', 'd.header_id = h.id', 'left');
        $this->db->where('d.grn_detail_id', $grn_detail_id);
        $this->db->where('h.status', 'Confirmed');
        $row = $this->db->get()->row();
        return $row ? (float)$row->total_returned : 0.0;
    }

    /**
     * Validate an array of details before confirm.
     * $details: array of arrays/objects with grn_detail_id and qty_return
     * Returns array with keys: valid(bool), errors(array)
     */
    public function validate_return_details($details = []) {
        $errors = [];
        if (empty($details) || !is_array($details)) {
            return ['valid' => false, 'errors' => ['No details provided']];
        }
        foreach ($details as $idx => $d) {
            $grn_detail_id = isset($d['grn_detail_id']) ? $d['grn_detail_id'] : ($d->grn_detail_id ?? null);
            $qty_return = isset($d['qty_return']) ? (float)$d['qty_return'] : (float)($d->qty_return ?? 0);
            if (empty($grn_detail_id)) { $errors[] = "Detail #{$idx}: missing grn_detail_id"; continue; }

            // get received qty from grn_details
            $gd = $this->db->select('receive_qty')->from('grn_details')->where('id', $grn_detail_id)->get()->row();
            $received = $gd ? (float)$gd->receive_qty : 0.0;
            $already = $this->get_total_returned_for_grn_detail($grn_detail_id);
            $available = $received - $already;
            if ($qty_return <= 0) { $errors[] = "Detail #{$idx}: qty_return must be > 0"; }
            if ($qty_return - $available > 0.0001) { $errors[] = "Detail #{$idx}: qty_return ({$qty_return}) exceeds available ({$available})"; }
        }
        return ['valid' => empty($errors), 'errors' => $errors];
    }

    public function generate_rtn_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('return_supplier', 'warehouse_out_header', 'out_number');
    }

    /**
     * Get count of details for a header
     */
    public function get_details_count($header_id) {
        return (int)$this->db->from($this->table_detail)->where('header_id', $header_id)->count_all_results();
    }

    /**
     * Delete header if it is draft and has no details.
     * Returns true on success, false otherwise.
     */
    public function delete_header($header_id) {
        $hdr = $this->get_header($header_id);
        if (!$hdr) return false;
        if (strtolower($hdr->status) !== 'draft') return false;
        $count = $this->get_details_count($header_id);
        if ($count > 0) return false;
        $this->db->where('id', $header_id)->delete($this->table_header);
        return $this->db->affected_rows() > 0;
    }

    // =====================================================
    // PRODUCTION RETURN — Return to Supplier from Production QC Reject
    // =====================================================

    /**
     * Get issuances that have QC reject items available for supplier return.
     */
    public function get_production_reject_issuances($filters = []) {
        // Pakai raw SQL untuk menghindari row multiplication akibat JOIN stock_ledger
        // (satu issuance bisa punya banyak OUT_ISSUE entries per item)
        $search_where = '';
        if (!empty($filters['search'])) {
            $term = $this->db->escape('%' . $filters['search'] . '%');
            $search_where = " AND (mri.issuance_number LIKE {$term} OR mrh.mr_number LIKE {$term})";
        }

        $sql = "
            SELECT
                mri.id          AS issuance_id,
                mri.issuance_number,
                mri.qc_done_at,
                mrh.mr_number,
                SUM(qr.reject_qty)           AS total_reject_qty,
                COUNT(DISTINCT qr.item_id)   AS total_items,
                -- Correlated subquery: supplier dari 1 OUT_ISSUE batch saja (LIMIT 1)
                COALESCE(
                    (SELECT s2.nama_supplier
                     FROM stock_ledger sl2
                     JOIN stock_on_hand soh2 ON sl2.stock_on_hand_id = soh2.id
                     JOIN grn_details gd2    ON soh2.grn_detail_id = gd2.id
                     JOIN po_header poh2     ON gd2.source_doc_type = 'PO'
                                           AND gd2.source_doc_header_id = poh2.id
                     JOIN suppliers s2       ON poh2.supplier_id = s2.id
                     WHERE sl2.issuance_id = mri.id
                       AND sl2.transaction_type = 'OUT_ISSUE'
                     LIMIT 1
                    ), '-'
                ) AS nama_supplier
            FROM mr_issuances mri
            JOIN mr_header mrh            ON mri.mr_header_id = mrh.id
            JOIN issuance_qc_results qr   ON qr.issuance_id = mri.id AND qr.reject_qty > 0
            WHERE mri.qc_status = 'QC Done'
            {$search_where}
            GROUP BY mri.id
            HAVING total_reject_qty > 0
            ORDER BY mri.qc_done_at DESC
        ";

        return $this->db->query($sql)->result();
    }

    /**
     * Get reject items detail for selected issuances, with GRN/SOH traceability.
     * Path: OUT_ISSUE → stock_on_hand → grn_details/grn_header → po_header → suppliers
     */
    public function get_production_reject_items_by_issuances($issuance_ids) {
        if (empty($issuance_ids)) return [];

        $ids_str = implode(',', array_map('intval', $issuance_ids));

        $sql = "
            SELECT
                qr.issuance_id,
                qr.item_id,
                qr.item_type,
                qr.reject_qty,
                qr.qc_notes,
                mri.issuance_number,
                mrh.mr_number,
                -- Item names
                CASE WHEN qr.item_type='material'    THEN m.kode_material
                     WHEN qr.item_type='semifinished' THEN sf.kode_barang END AS item_code,
                CASE WHEN qr.item_type='material'    THEN m.nama_material
                     WHEN qr.item_type='semifinished' THEN sf.nama_barang END AS item_name,
                CASE WHEN qr.item_type='material'    THEN m.satuan
                     WHEN qr.item_type='semifinished' THEN sf.satuan      END AS unit,
                -- GRN traceability via SOH (per item per issuance, satu SOH batch)
                (SELECT soh2.id
                 FROM stock_ledger sl2
                 JOIN stock_on_hand soh2 ON sl2.stock_on_hand_id = soh2.id
                 WHERE sl2.issuance_id = qr.issuance_id
                   AND sl2.item_id = qr.item_id AND sl2.item_type = qr.item_type
                   AND sl2.transaction_type = 'OUT_ISSUE'
                 LIMIT 1) AS stock_on_hand_id,
                (SELECT gh2.id
                 FROM stock_ledger sl2
                 JOIN stock_on_hand soh2 ON sl2.stock_on_hand_id = soh2.id
                 JOIN grn_header gh2 ON soh2.grn_header_id = gh2.id
                 WHERE sl2.issuance_id = qr.issuance_id
                   AND sl2.item_id = qr.item_id AND sl2.item_type = qr.item_type
                   AND sl2.transaction_type = 'OUT_ISSUE'
                 LIMIT 1) AS grn_header_id,
                (SELECT gh2.grn_number
                 FROM stock_ledger sl2
                 JOIN stock_on_hand soh2 ON sl2.stock_on_hand_id = soh2.id
                 JOIN grn_header gh2 ON soh2.grn_header_id = gh2.id
                 WHERE sl2.issuance_id = qr.issuance_id
                   AND sl2.item_id = qr.item_id AND sl2.item_type = qr.item_type
                   AND sl2.transaction_type = 'OUT_ISSUE'
                 LIMIT 1) AS grn_number,
                (SELECT soh2.grn_detail_id
                 FROM stock_ledger sl2
                 JOIN stock_on_hand soh2 ON sl2.stock_on_hand_id = soh2.id
                 WHERE sl2.issuance_id = qr.issuance_id
                   AND sl2.item_id = qr.item_id AND sl2.item_type = qr.item_type
                   AND sl2.transaction_type = 'OUT_ISSUE'
                 LIMIT 1) AS grn_detail_id,
                (SELECT poh2.id
                 FROM stock_ledger sl2
                 JOIN stock_on_hand soh2 ON sl2.stock_on_hand_id = soh2.id
                 JOIN grn_details gd2    ON soh2.grn_detail_id = gd2.id
                 JOIN po_header poh2     ON gd2.source_doc_type = 'PO'
                                       AND gd2.source_doc_header_id = poh2.id
                 WHERE sl2.issuance_id = qr.issuance_id
                   AND sl2.item_id = qr.item_id AND sl2.item_type = qr.item_type
                   AND sl2.transaction_type = 'OUT_ISSUE'
                 LIMIT 1) AS po_header_id,
                (SELECT poh2.supplier_id
                 FROM stock_ledger sl2
                 JOIN stock_on_hand soh2 ON sl2.stock_on_hand_id = soh2.id
                 JOIN grn_details gd2    ON soh2.grn_detail_id = gd2.id
                 JOIN po_header poh2     ON gd2.source_doc_type = 'PO'
                                       AND gd2.source_doc_header_id = poh2.id
                 WHERE sl2.issuance_id = qr.issuance_id
                   AND sl2.item_id = qr.item_id AND sl2.item_type = qr.item_type
                   AND sl2.transaction_type = 'OUT_ISSUE'
                 LIMIT 1) AS supplier_id,
                (SELECT poh2.po_number
                 FROM stock_ledger sl2
                 JOIN stock_on_hand soh2 ON sl2.stock_on_hand_id = soh2.id
                 JOIN grn_details gd2    ON soh2.grn_detail_id = gd2.id
                 JOIN po_header poh2     ON gd2.source_doc_type = 'PO'
                                       AND gd2.source_doc_header_id = poh2.id
                 WHERE sl2.issuance_id = qr.issuance_id
                   AND sl2.item_id = qr.item_id AND sl2.item_type = qr.item_type
                   AND sl2.transaction_type = 'OUT_ISSUE'
                 LIMIT 1) AS po_number,
                (SELECT s2.nama_supplier
                 FROM stock_ledger sl2
                 JOIN stock_on_hand soh2 ON sl2.stock_on_hand_id = soh2.id
                 JOIN grn_details gd2    ON soh2.grn_detail_id = gd2.id
                 JOIN po_header poh2     ON gd2.source_doc_type = 'PO'
                                       AND gd2.source_doc_header_id = poh2.id
                 JOIN suppliers s2       ON poh2.supplier_id = s2.id
                 WHERE sl2.issuance_id = qr.issuance_id
                   AND sl2.item_id = qr.item_id AND sl2.item_type = qr.item_type
                   AND sl2.transaction_type = 'OUT_ISSUE'
                 LIMIT 1) AS nama_supplier,
                (SELECT sl2.unit_value
                 FROM stock_ledger sl2
                 WHERE sl2.issuance_id = qr.issuance_id
                   AND sl2.item_id = qr.item_id AND sl2.item_type = qr.item_type
                   AND sl2.transaction_type = 'OUT_ISSUE'
                 LIMIT 1) AS unit_value
            FROM issuance_qc_results qr
            JOIN mr_issuances mri     ON qr.issuance_id = mri.id
            JOIN mr_header mrh        ON mri.mr_header_id = mrh.id
            LEFT JOIN materials m     ON qr.item_id = m.id AND qr.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON qr.item_id = sf.id AND qr.item_type = 'semifinished'
            WHERE qr.issuance_id IN ({$ids_str})
              AND qr.reject_qty > 0
            ORDER BY qr.issuance_id, qr.item_id
        ";

        return $this->db->query($sql)->result();
    }

    // =====================================================
    // NG RETURN (Return to Supplier from QA NG)
    // =====================================================

    /**
     * Get QA headers that have NG items available for return to supplier.
     */
    public function get_ng_qa_list($filters = []) {
        $this->db->reset_query();
        $this->db->select('
            qh.id, qh.qa_number, qh.grn_number, qh.qa_date, qh.status as qa_status,
            COALESCE(s_direct.nama_supplier, s_po.nama_supplier, s_mir.nama_supplier, \'-\') as nama_supplier,
            COUNT(DISTINCT qad.id) as total_ng_items,
            SUM(qad.qa_reject_qty) as total_ng_qty
        ', FALSE);
        $this->db->from('qa_header qh');
        $this->db->join('qa_details qad', 'qh.id = qad.qa_header_id AND qad.qa_reject_qty > 0', 'left');
        $this->db->join('grn_header gh', 'gh.id = COALESCE(qh.grn_header_id, qad.grn_header_id)', 'left');
        $this->db->join('grn_details gd', 'qad.grn_detail_id = gd.id', 'left');
        $this->db->join('po_header poh', 'gd.source_doc_type = "PO" AND gd.source_doc_header_id = poh.id', 'left');
        $this->db->join('mir_header mirh', 'gd.source_doc_type = "MIR" AND gd.source_doc_header_id = mirh.id', 'left');
        $this->db->join('suppliers s_direct', 'gh.supplier_id = s_direct.id', 'left');
        $this->db->join('suppliers s_po', 'poh.supplier_id = s_po.id', 'left');
        $this->db->join('suppliers s_mir', 'mirh.supplier_id = s_mir.id', 'left');
        $this->db->where('qh.status', 'Completed');
        if (!empty($filters['search'])) {
            $this->db->group_start()
                ->like('qh.qa_number', $filters['search'])
                ->or_like('qh.grn_number', $filters['search'])
                ->or_like('s_direct.nama_supplier', $filters['search'])
                ->or_like('s_po.nama_supplier', $filters['search'])
                ->or_like('s_mir.nama_supplier', $filters['search'])
                ->group_end();
        }
        $this->db->group_by('qh.id');
        $this->db->having('total_ng_items >', 0);
        $this->db->order_by('qh.qa_date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Get NG items from QA headers with full traceability (PO, qty order, receive, pass, NG).
     */
    public function get_ng_items_by_qa_ids($qa_ids) {
        if (empty($qa_ids) || !is_array($qa_ids)) return [];
        $this->db->reset_query();
        $this->db->select('
            qad.id as qa_detail_id,
            qad.qa_header_id,
            qad.grn_header_id,
            qad.grn_detail_id,
            qad.item_id,
            qad.item_type,
            qad.receive_qty,
            qad.qa_pass_qty,
            qad.qa_reject_qty,
            qad.reject_reason,
            qad.ng_category,
            qh.qa_number,
            qh.grn_number,
            COALESCE(gh.supplier_id, poh.supplier_id, mirh.supplier_id) as supplier_id,
            COALESCE(s_direct.nama_supplier, s_po.nama_supplier, s_mir.nama_supplier) as nama_supplier,
            gd.source_doc_type,
            gd.source_doc_header_id,
            gd.source_doc_detail_id,
            gd.unit_value,
            poh.po_number,
            CASE WHEN gd.source_doc_type = "PO" THEN pd.qty ELSE md.total_qty END as qty_ordered,
            CASE
                WHEN qad.item_type = "material"    THEN m.kode_material
                WHEN qad.item_type = "machine"     THEN mc.kode_mesin
                WHEN qad.item_type = "inventory"   THEN i.kode_barang
                WHEN qad.item_type = "semifinished" THEN sf.kode_barang
            END as item_code,
            CASE
                WHEN qad.item_type = "material"    THEN m.nama_material
                WHEN qad.item_type = "machine"     THEN mc.nama_mesin
                WHEN qad.item_type = "inventory"   THEN i.nama_barang
                WHEN qad.item_type = "semifinished" THEN sf.nama_barang
            END as item_name,
            CASE
                WHEN qad.item_type = "material"    THEN m.satuan
                WHEN qad.item_type = "machine"     THEN mc.satuan
                WHEN qad.item_type = "inventory"   THEN i.satuan
                WHEN qad.item_type = "semifinished" THEN sf.satuan
            END as unit
        ', FALSE);
        $this->db->from('qa_details qad');
        $this->db->join('qa_header qh',          'qad.qa_header_id = qh.id',                                       'left');
        $this->db->join('grn_header gh',          'qad.grn_header_id = gh.id',                                    'left');
        $this->db->join('grn_details gd',         'qad.grn_detail_id = gd.id',                                    'left');
        $this->db->join('po_header poh',          'gd.source_doc_type = "PO" AND gd.source_doc_header_id = poh.id',  'left');
        $this->db->join('po_details pd',          'gd.source_doc_type = "PO" AND gd.source_doc_detail_id = pd.id',   'left');
        $this->db->join('mir_header mirh',        'gd.source_doc_type = "MIR" AND gd.source_doc_header_id = mirh.id','left');
        $this->db->join('mir_details md',         'gd.source_doc_type = "MIR" AND gd.source_doc_detail_id = md.id',  'left');
        $this->db->join('suppliers s_direct',     'gh.supplier_id = s_direct.id',                                 'left');
        $this->db->join('suppliers s_po',         'poh.supplier_id = s_po.id',                                    'left');
        $this->db->join('suppliers s_mir',        'mirh.supplier_id = s_mir.id',                                  'left');
        $this->db->join('materials m',            'qad.item_id = m.id AND qad.item_type = "material"',           'left');
        $this->db->join('machines mc',            'qad.item_id = mc.id AND qad.item_type = "machine"',           'left');
        $this->db->join('inventories i',          'qad.item_id = i.id AND qad.item_type = "inventory"',          'left');
        $this->db->join('semifinished_goods sf',  'qad.item_id = sf.id AND qad.item_type = "semifinished"',      'left');
        $this->db->where_in('qad.qa_header_id', $qa_ids);
        $this->db->where('qad.qa_reject_qty >', 0);
        $this->db->order_by('qad.id', 'ASC');

        $rows = $this->db->get()->result();
        foreach ($rows as $row) {
            $row->already_returned  = $this->get_total_returned_for_qa_detail($row->qa_detail_id);
            $row->available_return  = max(0, (float)$row->qa_reject_qty - $row->already_returned);
        }
        return $rows;
    }

    /**
     * Sum qty_return already Confirmed for a qa_detail_id.
     */
    public function get_total_returned_for_qa_detail($qa_detail_id) {
        $this->db->reset_query();
        $this->db->select('IFNULL(SUM(d.qty_return), 0) as total_returned');
        $this->db->from($this->table_detail . ' d');
        $this->db->join($this->table_header . ' h', 'd.header_id = h.id', 'left');
        $this->db->where('d.qa_detail_id', $qa_detail_id);
        $this->db->where('h.status', 'Confirmed');
        $row = $this->db->get()->row();
        return $row ? (float)$row->total_returned : 0.0;
    }

    /**
     * Check if a detail with given qa_detail_id already exists for this header (prevent duplicate).
     */
    public function detail_exists_by_qa_detail($header_id, $qa_detail_id) {
        if (empty($qa_detail_id) || empty($header_id)) return false;
        $this->db->reset_query();
        return $this->db->from($this->table_detail)
            ->where('header_id', $header_id)
            ->where('qa_detail_id', $qa_detail_id)
            ->count_all_results() > 0;
    }

    // =====================================================

    /**
     * Update a detail row by id with provided data array.
     */
    public function update_detail($detail_id, $data) {
        if (empty($detail_id) || empty($data) || !is_array($data)) return false;
        $allowed = $this->db->list_fields($this->table_detail);
        $update = [];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed)) $update[$k] = $v;
        }
        if (empty($update)) return false;
        $result = $this->db->where('id', $detail_id)->update($this->table_detail, $update);
        // affected_rows() = 0 bisa berarti nilai sama (bukan error), jadi cukup cek query berhasil
        return $result !== false;
    }

}
