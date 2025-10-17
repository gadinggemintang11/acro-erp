<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_issuance_model extends CI_Model {

    private $table_header = 'mr_header';
    private $table_details = 'mr_details';

    public function __construct() {
        parent::__construct();
        $this->load->database(); 
    }

    /**
     * ✅ REVISI: Ganti nama fungsi & tambahkan filter status
     * Ambil semua MR yang relevan untuk gudang (Submitted, Issued, Received)
     */
    public function get_all_mrs($filters = []) {
        $this->db->select('mr.id, mr.mr_number, mr.request_date, mr.status, u.fullname as requester_name');
        $this->db->from('mr_header mr');
        $this->db->join('users u', 'mr.requester_id = u.id', 'left');

        // Filter Status
        if (!empty($filters['status'])) {
             $this->db->where('mr.status', $filters['status']);
        } else {
            // Default: Tampilkan semua yang relevan untuk gudang
             $this->db->where_in('mr.status', ['Submitted', 'Issued', 'Received']);
        }

        // Filter Search
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->like('mr.mr_number', $term);
        }
        
        $this->db->order_by('mr.request_date', 'DESC');
        return $this->db->get()->result();
    }

    // Ambil detail item MR untuk halaman pengeluaran
    public function get_mr_details_for_issuance($mr_id) {
        $select_clause = "
            d.id as detail_id, d.requested_qty, d.issued_qty,
            d.component_item_id as item_id, d.component_item_type as item_type,
            wo.wo_number,
            CASE
                WHEN d.component_item_type = 'material' THEN m.kode_material
                WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang
            END as item_code,
            CASE
                WHEN d.component_item_type = 'material' THEN m.nama_material
                WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang
            END as item_name,
            CASE
                WHEN d.component_item_type = 'material' THEN m.satuan
                WHEN d.component_item_type = 'semifinished' THEN sf.satuan
                ELSE 'PCS'
            END as item_unit
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from('mr_details d');
        $this->db->join('wo_header wo', 'd.wo_header_id = wo.id', 'left');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.mr_header_id', $mr_id);
        $this->db->order_by('wo.wo_number', 'ASC');
        $this->db->order_by('item_name', 'ASC');
        return $this->db->get()->result();
    }

    // Generate Nomor Pengeluaran
    public function generate_issuance_number() {
        $prefix = 'MI/' . date('Ym') . '/'; // MI = Material Issuance
        $this->db->like('issuance_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get('mr_header', 1)->row();
        $new_num = $last && $last->issuance_number ? (int) substr($last->issuance_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // Proses inti: Konfirmasi pengeluaran (validasi & update DB)
    public function process_issuance($mr_id, $items, $user_id) {
        $this->load->model('Stock_ledger_model');
        
        $this->db->trans_start(); 
        $mr_header = $this->db->get_where('mr_header', ['id' => $mr_id])->row();
        $issuance_number = $this->generate_issuance_number();

        foreach ($items as $detail_id => $data) {
            $issued_qty = (float) $data['qty'];
            if ($issued_qty <= 0) continue; 
            $detail = $this->db->get_where('mr_details', ['id' => $detail_id])->row();
            if (!$detail) continue;

            $stock_on_hand = $this->Stock_ledger_model->get_last_balance($detail->component_item_id, $detail->component_item_type);
            if ($issued_qty > $stock_on_hand) {
                $this->db->trans_rollback(); 
                return ['success' => false, 'message' => 'Stok tidak cukup untuk item ' . $detail->component_item_type . ' ID ' . $detail->component_item_id . '. Sisa stok: ' . $stock_on_hand];
            }
            
            $this->db->where('id', $detail_id)->update('mr_details', ['issued_qty' => $issued_qty]);
            
            $ledger_data = [
                'item_id'         => $detail->component_item_id,
                'item_type'       => $detail->component_item_type,
                'transaction_date'=> $mr_header->request_date . ' ' . date('H:i:s'),
                'transaction_type'=> 'production_out', 
                'transaction_id'  => $mr_id, 'source_doc_type' => 'MR',
                'source_doc_id'   => $mr_id, 'qty_in'          => 0,
                'qty_out'         => $issued_qty, 'unit_value'      => 0, 
                'balance'         => $stock_on_hand - $issued_qty 
            ];
            $this->Stock_ledger_model->insert($ledger_data);
        }

        $header_data = [
            'status'     => 'Issued',
            'issuer_id'  => $user_id,
            'issued_at'  => date('Y-m-d H:i:s'),
            'issuance_number' => $issuance_number
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $header_data);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database.'];
        }
        return ['success' => true];
    }
    
    // Proses Un-Issue (Batal Pengeluaran)
    public function reverse_issuance($mr_id, $user_id) {
        $this->load->model('Stock_ledger_model');
        $this->db->trans_start();

        $this->Stock_ledger_model->delete_by_transaction('production_out', $mr_id);
        $this->db->where('mr_header_id', $mr_id)->update('mr_details', ['issued_qty' => 0]);
        
        $header_data = [
            'status'     => 'Submitted',
            'issuer_id'  => NULL,
            'issued_at'  => NULL,
            'issuance_number' => NULL
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $header_data);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}