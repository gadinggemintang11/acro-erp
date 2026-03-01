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
        $this->db->select('h.*, creator.fullname as created_by_name, confirmer.fullname as confirmed_by_name');
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
        // Join to grn_details and source doc details to surface source qty (PO/MIR) when qty_po not present
        $this->db->select('d.*, gh.grn_number, gd.source_doc_detail_id, gd.source_doc_type, pd.qty as po_qty, md.total_qty as mir_total_qty');
        $this->db->from($this->table_detail . ' d');
        $this->db->join('grn_header gh', 'd.grn_id = gh.id', 'left');
        $this->db->join('grn_details gd', 'd.grn_detail_id = gd.id', 'left');
        $this->db->join('po_details pd', 'gd.source_doc_type = "PO" AND gd.source_doc_detail_id = pd.id', 'left');
        $this->db->join('mir_details md', 'gd.source_doc_type = "MIR" AND gd.source_doc_detail_id = md.id', 'left');
        $this->db->where('d.header_id', $header_id);
        $rows = $this->db->get()->result();

        // Normalize: if qty_po missing (0), fill from source detail
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
        $date = date('Ymd');
        $prefix = 'RTN-'.$date.'-';
        // Count existing today
        $this->db->like('out_number', 'RTN-'.$date, 'after');
        $this->db->from($this->table_header);
        $count = $this->db->count_all_results();
        $seq = str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        return $prefix.$seq;
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

    /**
     * Update a detail row by id with provided data array.
     */
    public function update_detail($detail_id, $data) {
        if (empty($detail_id) || empty($data) || !is_array($data)) return false;
        // ensure we only update allowed columns
        $allowed = $this->db->list_fields($this->table_detail);
        $update = [];
        foreach ($data as $k=>$v) {
            if (in_array($k, $allowed)) $update[$k] = $v;
        }
        if (empty($update)) return false;
        $this->db->where('id', $detail_id)->update($this->table_detail, $update);
        return $this->db->affected_rows() > 0;
    }

}
