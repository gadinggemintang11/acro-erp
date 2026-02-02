<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Warehouse_in_model extends CI_Model {

    private $table_header = 'grn_header';
    private $table_details = 'grn_details';

    // === HEADER ===
    public function add_header($data)
    {
        $this_db = $this->db;
        $this_db->insert('grn_header', $data);
        
        if ($this_db->affected_rows() > 0) {
            return $this_db->insert_id();
        } else {
            return false;
        }
    }
    public function get_all() {
        $this->db->select('h.id, h.grn_number, h.grn_date, h.status, s.nama_supplier, h.base_on');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id) {
        $this->db->reset_query();
        $this->db->select('
            h.*, s.nama_supplier,
            creator.fullname as creator_name,
            confirmer.fullname as confirmer_name,
            cc_bc.description as bc_type_desc
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->join('users creator', 'h.created_by = creator.id', 'left');
        $this->db->join('users confirmer', 'h.confirmed_by = confirmer.id', 'left');
        $this->db->join('common_code cc_bc', 'h.bc_type_code = cc_bc.code AND cc_bc.group_code = "BC_TYPE"', 'left');
        $this->db->where('h.id', $id);
        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    public function insert_header($data) {
        if (empty($data['status'])) { $data['status'] = 'Draft'; }
        $this->db->reset_query();
        $insert_success = $this->db->insert($this->table_header, $data);
        return $insert_success ? $this->db->insert_id() : false;
    }

    public function update_header($id, $data) {
        unset($data['status'], $data['confirmed_by'], $data['confirmed_at']);
        $this->db->reset_query();
        return $this->db->where('id', $id)->where('status', 'Draft')->update($this->table_header, $data);
    }

    public function delete_header($id) {
        $this->db->reset_query();
        $details_count = $this->db->where('grn_header_id', $id)->count_all_results($this->table_details);
        if ($details_count > 0) {
            log_message('warning', 'GRN Delete Header Gagal: Masih ada item detail. ID: '.$id);
            return false;
        }
        
        $this->db->reset_query();
        $delete_success = $this->db->where('id', $id)->where('status', 'Draft')->delete($this->table_header);
        return ($delete_success && $this->db->affected_rows() > 0);
    }

    public function update_status($id, $new_status, $user_id = null) {
        $this->db->reset_query();
        $data = ['status' => $new_status];
        if ($new_status == 'Completed' && $user_id) {
            $data['confirmed_by'] = $user_id;
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif ($new_status == 'Draft') {
            $data['confirmed_by'] = NULL;
            $data['confirmed_at'] = NULL;
        }
        
        $update_result = $this->db->where('id', $id)->update($this->table_header, $data);
        return ($update_result && $this->db->affected_rows() > 0);
    }

    public function generate_grn_number() {
        $this->db->reset_query();
        $prefix = 'GRN/' . date('Ym') . '/';
        $this->db->select_max('SUBSTRING(grn_number, 10, 4)', 'max_num');
        $this->db->like('grn_number', $prefix, 'after');
        $last = $this->db->get($this->table_header)->row();
        $new_num = $last && $last->max_num ? (int)$last->max_num + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // === DETAILS ===
    public function get_details_by_id($grn_id) {
        $this->db->reset_query();
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
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function insert_item($data) {
        $this->db->reset_query();
        return $this->db->insert($this->table_details, $data);
    }

    public function delete_item($id) {
        $this->db->reset_query();
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // === STOCK LEDGER INTEGRATION ===
    public function add_stock_to_ledger_and_batch($grn_id) {
        $this->db->reset_query();
        $details = $this->get_details_by_id($grn_id);
        if (empty($details)) return true;

        // =======================================================
        // PERBAIKAN 1: Aktifkan baris ini
        // =======================================================
        $this->load->model('Stock_ledger_model');

        $this->db->trans_start();
        $grn_header = $this->get_header_by_id($grn_id);
        if (!$grn_header) {
            log_message('error', "GRN Header $grn_id tidak ditemukan saat add_stock_to_ledger");
            $this->db->trans_rollback();
            return false;
        }

        foreach ($details as $item) {
            if (!isset($item->receive_qty) || (float)$item->receive_qty <= 0) continue;

            $stock_on_hand_id = null; // Default null

            // =======================================================
            // PERBAIKAN LOGIKA: 
            // Hanya 'material' dan 'semifinished' yang masuk SOH (Batch)
            // 'machine' dan 'inventory' tidak perlu SOH, tapi tetap masuk ledger
            // =======================================================
            if ($item->item_type == 'material' || $item->item_type == 'semifinished') 
            {
                // 1. Catat di Stock on Hand (Batch)
                $batch_data = [
                    'grn_detail_id' => $item->id,
                    'grn_header_id' => $item->grn_header_id,
                    'item_id'       => $item->item_id,
                    'item_type'     => $item->item_type,
                    'initial_qty'   => (float)$item->receive_qty,
                    'available_qty' => (float)$item->receive_qty,
                    'unit_value'    => (float)($item->unit_value ?? 0)
                ];
                $this->db->reset_query();
                $insert_soh_success = $this->db->insert('stock_on_hand', $batch_data);
                if (!$insert_soh_success) {
                    log_message('error', "Gagal insert SOH untuk GRN $grn_id");
                    $this->db->trans_rollback();
                    return false;
                }
                $stock_on_hand_id = $this->db->insert_id(); // KUNCI TRACEABILITY (Hanya utk material/sf)
            }
            
            // 2. Catat di Stock Ledger (Berlaku untuk SEMUA item_type)
            $last_balance = $this->Stock_ledger_model->get_last_balance($item->item_id, $item->item_type);
            $new_balance = $last_balance + (float)$item->receive_qty;
            
            $ledger_data = [
                'item_id'           => $item->item_id,
                'item_type'         => $item->item_type,
                'transaction_date'  => ($grn_header->grn_date ?? date('Y-m-d')) . ' ' . date('H:i:s'),
                'transaction_type'  => 'in',
                'transaction_id'    => $grn_id,
                'source_doc_type'   => $item->source_doc_type,
                'source_doc_id'     => $item->source_doc_header_id,
                'qty_in'            => (float)$item->receive_qty,
                'qty_out'           => 0,
                'unit_value'        => (float)($item->unit_value ?? 0),
                'balance'           => $new_balance,
                'stock_on_hand_id'  => $stock_on_hand_id // Akan NULL jika 'machine' atau 'inventory'
            ];

            if (!$this->Stock_ledger_model->insert($ledger_data)) {
                 log_message('error', "Gagal insert Stock Ledger untuk GRN $grn_id");
                 $this->db->trans_rollback();
                 return false;
            }
        }
        
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
             log_message('error', "Gagal transaksi DB untuk GRN $grn_id");
             return false;
        }
        return true;
    }

    public function remove_stock_from_ledger_and_batch($grn_id) {
        
        // =======================================================
        // PERBAIKAN 2: Aktifkan baris ini
        // =======================================================
        $this->load->model('Stock_ledger_model');

        $this->db->trans_start();

        // 1. Ambil batch SOH yang dibuat oleh GRN ini
        $this->db->reset_query();
        $this->db->select('id as stock_on_hand_id, item_id, item_type, initial_qty, available_qty');
        $this->db->where('grn_header_id', $grn_id);
        $batches_to_delete = $this->db->get('stock_on_hand')->result();

        if (!empty($batches_to_delete)) {
            foreach ($batches_to_delete as $batch) {
                // Validasi: Pastikan stok di batch belum terpakai
                if (abs((float)$batch->available_qty - (float)$batch->initial_qty) > 0.0001) {
                    log_message('error', 'GRN: Gagal unconfirm! Batch SOH ID ' . $batch->stock_on_hand_id . ' sudah terpakai (Initial: '.$batch->initial_qty.', Available: '.$batch->available_qty.').');
                    $this->db->trans_rollback();
                    return false;
                }
                // 2. Hapus dari Stock on Hand
                $this->db->where('id', $batch->stock_on_hand_id)->delete('stock_on_hand');
            }
        }
        
        // 3. Hapus dari Stock Ledger (berdasarkan transaction_id = grn_id DAN tipe 'in')
        $delete_ledger_success = $this->Stock_ledger_model->delete_by_transaction('in', $grn_id);
        if (!$delete_ledger_success) {
             $this->db->trans_rollback();
             return false;
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // === HELPERS for AJAX ===
    public function search_source_docs($type, $term) {
        $this->db->reset_query();
        log_message('debug', 'AJAX search_source_docs dipanggil. Tipe: ' . $type . ' | Term: ' . $term);
        $term = $this->db->escape_like_str($term);
        if ($type == 'PO') {
            $this->db->select('id, po_number as number, supplier_id');
            $this->db->like('po_number', $term);
            $this->db->where_in('status', ['Confirmed', 'Partially Received']);
            $query = $this->db->limit(10)->get('po_header');
            log_message('debug', 'SQL untuk cari PO: ' . $this->db->last_query());
            return $query ? $query->result() : [];
        } elseif ($type == 'MIR') {
            $this->db->select('id, mir_number as number, supplier_id');
            $this->db->like('mir_number', $term);
            $this->db->where_in('status', ['Approved', 'Partially Received']);
            $query = $this->db->limit(10)->get('mir_header');
             log_message('debug', 'SQL untuk cari MIR: ' . $this->db->last_query());
            return $query ? $query->result() : [];
        }
        return [];
    }

    public function get_available_items($source_type, $source_header_id, $warehouse_type) {
        $this->db->reset_query();
        $received_subquery = $this->db->select('source_doc_detail_id, SUM(receive_qty) as total_received')
                                        ->from($this->table_details)
                                        ->where('source_doc_header_id', $source_header_id)
                                        ->where('source_doc_type', $source_type)
                                        ->group_by('source_doc_detail_id')
                                        ->get_compiled_select();
        $this->db->reset_query();

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
            $query = $this->db->get();
            return $query ? $query->result() : [];

        } elseif ($source_type == 'MIR') {
            $select_clause = "
                md.id as source_detail_id, md.item_id, md.item_type, md.qty, md.allowance, md.total_qty as source_qty, 0 as source_unit_price,
                GREATEST(0, md.total_qty - IFNULL(received.total_received, 0)) as remaining_qty,
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
            $query = $this->db->get();
            return $query ? $query->result() : [];
        }
        return [];
    }
}