<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warehouse_in_model extends CI_Model {

    private $table_header = 'grn_header';
    private $table_details = 'grn_details';

    // === HEADER ===
    public function get_all() {
        $this->db->select('h.id, h.grn_number, h.grn_date, h.status, s.nama_supplier, h.base_on');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id) {
        $this->db->select('
            h.*, s.nama_supplier,
            creator.fullname as creator_name,
            confirmer.fullname as confirmer_name,
            cc_bc.description as bc_type_desc  /* Ambil deskripsi BC Type */
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->join('users creator', 'h.created_by = creator.id', 'left');
        $this->db->join('users confirmer', 'h.confirmed_by = confirmer.id', 'left');
        $this->db->join('common_code cc_bc', 'h.bc_type_code = cc_bc.code AND cc_bc.group_code = "BC_TYPE"', 'left');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    public function update_header($id, $data) {
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    public function delete_header($id) {
        return $this->db->where('id', $id)->delete($this->table_header);
    }

    public function update_status($id, $new_status, $user_id = null) {
        $data = ['status' => $new_status];
        if ($new_status == 'Completed' && $user_id) {
            $data['confirmed_by'] = $user_id;
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif ($new_status == 'Draft') {
            $data['confirmed_by'] = NULL;
            $data['confirmed_at'] = NULL;
        }
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    public function generate_grn_number() {
        $prefix = 'GRN/' . date('Ym') . '/';
        $this->db->like('grn_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table_header, 1)->row();
        $new_num = $last ? (int) substr($last->grn_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // === DETAILS ===
    public function get_details_by_id($grn_id) {
        $select_clause = "
            d.id, d.grn_header_id, d.receive_qty, d.unit_value, d.total_value, d.source_doc_type, d.source_doc_header_id, d.item_id, d.item_type,
            CASE WHEN d.source_doc_type = 'PO' THEN poh.po_number WHEN d.source_doc_type = 'MIR' THEN mirh.mir_number END as source_doc_number,
            CASE WHEN d.item_type = 'material' THEN m.kode_material WHEN d.item_type = 'machine' THEN mc.kode_mesin WHEN d.item_type = 'inventory' THEN i.kode_barang WHEN d.item_type = 'semifinished' THEN sf.kode_barang END as item_code,
            CASE WHEN d.item_type = 'material' THEN m.nama_material WHEN d.item_type = 'machine' THEN mc.nama_mesin WHEN d.item_type = 'inventory' THEN i.nama_barang WHEN d.item_type = 'semifinished' THEN sf.nama_barang END as item_name
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('materials m', 'd.item_id = m.id AND d.item_type = "material"', 'left');
        $this->db->join('machines mc', 'd.item_id = mc.id AND d.item_type = "machine"', 'left');
        $this->db->join('inventories i', 'd.item_id = i.id AND d.item_type = "inventory"', 'left');
        $this->db->join('semifinished_goods sf', 'd.item_id = sf.id AND d.item_type = "semifinished"', 'left');
        $this->db->join('po_header poh', 'd.source_doc_header_id = poh.id AND d.source_doc_type = "PO"', 'left');
        $this->db->join('mir_header mirh', 'd.source_doc_header_id = mirh.id AND d.source_doc_type = "MIR"', 'left');
        $this->db->where('d.grn_header_id', $grn_id);
        return $this->db->get()->result();
    }

    public function insert_item($data) {
        return $this->db->insert($this->table_details, $data);
    }

    public function delete_item($id) {
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // === STOCK LEDGER INTEGRATION ===
    public function add_stock_to_ledger_and_batch($grn_id) {
        $details = $this->get_details_by_id($grn_id);
        if (empty($details)) return true;

        $this->load->model('Stock_ledger_model');
        $this->db->trans_start();
        $grn_header = $this->get_header_by_id($grn_id);

        foreach ($details as $item) {
            // 1. Catat di Stock on Hand (untuk Ketersediaan Batch)
            $batch_data = [
                'grn_detail_id' => $item->id,
                'grn_header_id' => $item->grn_header_id,
                'item_id'       => $item->item_id,
                'item_type'     => $item->item_type,
                'initial_qty'   => $item->receive_qty,
                'available_qty' => $item->receive_qty, // Awalnya sisa = diterima
                'unit_value'    => $item->unit_value
            ];
            $this->db->insert('stock_on_hand', $batch_data);

            // 2. Catat di Stock Ledger (untuk Histori)
            $last_balance = $this->Stock_ledger_model->get_last_balance($item->item_id, $item->item_type);
            $ledger_data = [
                'item_id'         => $item->item_id, 'item_type'       => $item->item_type,
                'transaction_date'=> $grn_header->grn_date . ' ' . date('H:i:s'),
                'transaction_type'=> 'in', 'transaction_id'  => $grn_id,
                'source_doc_type' => $item->source_doc_type, 'source_doc_id'   => $item->source_doc_header_id,
                'qty_in'          => $item->receive_qty, 'qty_out'         => 0,
                'unit_value'      => $item->unit_value,
                'balance'         => $last_balance + $item->receive_qty
            ];
            $this->Stock_ledger_model->insert($ledger_data);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function remove_stock_from_ledger_and_batch($grn_id) {
        $this->load->model('Stock_ledger_model');
        $this_db->trans_start();

        // 1. Hapus dari Stock on Hand
        $this->db->where('grn_header_id', $grn_id)->delete('stock_on_hand');
        
        // 2. Hapus dari Stock Ledger
        $this->Stock_ledger_model->delete_by_transaction('in', $grn_id);
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // === HELPERS for AJAX ===
    public function search_source_docs($type, $term) {
        $term = $this->db->escape_like_str($term);
        if ($type == 'PO') {
            $this->db->select('id, po_number as number, supplier_id');
            $this->db->like('po_number', $term);
            // ✅ PASTIKAN STATUS PO BENAR! (Submitted atau Partially Received)
            $this->db->where_in('status', ['Submitted', 'Partially Received']);
            return $this->db->limit(10)->get('po_header')->result();
        } elseif ($type == 'MIR') {
            $this->db->select('id, mir_number as number, supplier_id');
            $this->db->like('mir_number', $term);
            // ✅ PASTIKAN STATUS MIR BENAR! (Approved atau Partially Received)
            $this->db->where_in('status', ['Approved', 'Partially Received']);
            return $this->db->limit(10)->get('mir_header')->result();
        }
        return [];
    }

    public function get_available_items($source_type, $source_header_id, $warehouse_type) {
        $received_subquery = $this->db->select('source_doc_detail_id, SUM(receive_qty) as total_received')
                                      ->from($this->table_details)
                                      ->where('source_doc_header_id', $source_header_id)
                                      ->where('source_doc_type', $source_type)
                                      ->group_by('source_doc_detail_id')
                                      ->get_compiled_select();

        if ($source_type == 'PO') {
             $select_clause = "
                pd.id as source_detail_id, pd.item_id, pd.item_type, pd.qty as source_qty, pd.local_price as source_unit_price,
                GREATEST(0, pd.qty - IFNULL(received.total_received, 0)) as remaining_qty,
                CASE WHEN pd.item_type = 'material' THEN m.kode_material WHEN pd.item_type = 'machine' THEN mc.kode_mesin WHEN pd.item_type = 'inventory' THEN i.kode_barang END as item_code,
                CASE WHEN pd.item_type = 'material' THEN m.nama_material WHEN pd.item_type = 'machine' THEN mc.nama_mesin WHEN pd.item_type = 'inventory' THEN i.nama_barang END as item_name
            ";
            $this->db->select($select_clause, FALSE);
            $this->db->from('po_details pd');
            $this->db->join('materials m', 'pd.item_id = m.id AND pd.item_type = "material"', 'left');
            $this->db->join('machines mc', 'pd.item_id = mc.id AND pd.item_type = "machine"', 'left');
            $this->db->join('inventories i', 'pd.item_id = i.id AND pd.item_type = "inventory"', 'left');
            $this->db->join("({$received_subquery}) received", 'pd.id = received.source_doc_detail_id', 'left');
            $this->db->where('pd.po_header_id', $source_header_id);
            $this->db->where('pd.item_type', $warehouse_type);
            $this->db->having('remaining_qty >', 0.001);
            return $this->db->get()->result();

        } elseif ($source_type == 'MIR') {
             $select_clause = "
                md.id as source_detail_id, md.item_id, md.item_type, md.qty as source_qty, 0 as source_unit_price,
                GREATEST(0, md.qty - IFNULL(received.total_received, 0)) as remaining_qty,
                CASE WHEN md.item_type = 'material' THEN m.kode_material WHEN md.item_type = 'machine' THEN mc.kode_mesin WHEN md.item_type = 'inventory' THEN i.kode_barang WHEN md.item_type = 'semifinished' THEN sf.kode_barang END as item_code,
                CASE WHEN md.item_type = 'material' THEN m.nama_material WHEN md.item_type = 'machine' THEN mc.nama_mesin WHEN md.item_type = 'inventory' THEN i.nama_barang WHEN md.item_type = 'semifinished' THEN sf.nama_barang END as item_name
            ";
            $this->db->select($select_clause, FALSE);
            $this->db->from('mir_details md');
            $this->db->join('materials m', 'md.item_id = m.id AND md.item_type = "material"', 'left');
            $this->db->join('machines mc', 'md.item_id = mc.id AND md.item_type = "machine"', 'left');
            $this->db->join('inventories i', 'md.item_id = i.id AND md.item_type = "inventory"', 'left');
            $this->db->join('semifinished_goods sf', 'md.item_id = sf.id AND md.item_type = "semifinished"', 'left');
            $this->db->join("({$received_subquery}) received", 'md.id = received.source_doc_detail_id', 'left');
            $this->db->where('md.mir_header_id', $source_header_id);
            $this->db->where('md.item_type', $warehouse_type);
            $this->db->having('remaining_qty >', 0.001);
            return $this->db->get()->result();
        }
        return [];
    }
}