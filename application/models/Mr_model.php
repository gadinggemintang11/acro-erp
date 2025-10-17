<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mr_model extends CI_Model {

    private $table_header = 'mr_header';
    private $table_details = 'mr_details';

    // === HEADER ===
    public function get_all($filters = []) {
        $this->db->select('mr.id, mr.mr_number, mr.request_date, mr.status, u.fullname as requester_name');
        $this->db->from($this->table_header . ' mr');
        $this->db->join('users u', 'mr.requester_id = u.id', 'left');

        if (!empty($filters['start_date'])) { $this->db->where('mr.request_date >=', $filters['start_date']); }
        if (!empty($filters['end_date'])) { $this->db->where('mr.request_date <=', $filters['end_date']); }
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->like('mr.mr_number', $term);
        }
        $this->db->order_by('mr.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id) {
         $this->db->select('
            mr.*,
            req.fullname as requester_name,
            iss.fullname as issuer_name,
            rec.fullname as receiver_name
        ');
        $this->db->from($this->table_header . ' mr');
        $this->db->join('users req', 'mr.requester_id = req.id', 'left');
        $this->db->join('users iss', 'mr.issuer_id = iss.id', 'left');
        $this->db->join('users rec', 'mr.receiver_id = rec.id', 'left');
        $this->db->where('mr.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    public function delete_header($id) {
        return $this->db->where('id', $id)->delete($this->table_header);
    }
    
    // Fungsi untuk update status (Confirm/Unconfirm)
    public function update_status($id, $new_status, $user_type, $user_id) {
        $data = ['status' => $new_status];
        $now = date('Y-m-d H:i:s');
        if ($user_type == 'requester' && $new_status == 'Submitted') {
            $data['requester_id'] = $user_id; $data['requested_at'] = $now;
        } elseif ($user_type == 'requester' && $new_status == 'Draft') {
            $data['requester_id'] = NULL; $data['requested_at'] = NULL;
        }
        // (Logika issuer_id & receiver_id di-handle oleh controller masing-masing)
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    public function generate_mr_number() {
        $prefix = 'MR/' . date('Ym') . '/';
        $this->db->like('mr_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table_header, 1)->row();
        $new_num = $last ? (int) substr($last->mr_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // === DETAILS ===
    public function get_details_by_id($mr_id) {
        // ✅ FIX: Menambahkan CASE WHEN untuk item_unit
        $select_clause = "
            d.id, d.mr_header_id, d.requested_qty, d.issued_qty, d.unit_measure, d.notes,
            wo.wo_number, sc.sc_number, fg.nama_barang as style_name,
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
        $this->db->from($this->table_details . ' d');
        $this->db->join('wo_header wo', 'd.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.mr_header_id', $mr_id);
        $this->db->order_by('wo.wo_number', 'ASC');
        $this->db->order_by('item_name', 'ASC');
        return $this->db->get()->result();
    }

    public function insert_item($data) {
        $component = null; $satuan = null;
        if($data['component_item_type'] == 'material'){ $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('materials')->row(); }
        else if ($data['component_item_type'] == 'semifinished'){ $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('semifinished_goods')->row(); }
        $data['unit_measure'] = $component ? $component->satuan : 'PCS';
        return $this->db->insert($this->table_details, $data);
    }
    
    public function delete_item($id) {
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // === HELPERS ===
    // Cari WO yang statusnya 'In Progress'
    public function search_open_wos($term) {
        $term = $this->db->escape_like_str($term);
        $this->db->select('wo.id, wo.wo_number, fg.nama_barang as fg_name');
        $this->db->from('wo_header wo');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->where('wo.status', 'In Progress');
        $this->db->group_start();
        $this->db->like('wo.wo_number', $term);
        $this->db->or_like('fg.nama_barang', $term);
        $this->db->group_end();
        $this->db->limit(10);
        return $this->db->get()->result();
    }
    
    // Ambil detail referensi (SC, Style, BOM, Plan) dari WO
    public function get_wo_references($wo_id) {
        $this->load->model('Wo_model'); // Panggil model WO
        $header = $this->Wo_model->get_header_by_id($wo_id);
        $bom_components = $this->Wo_model->get_bom_components_by_fg($header->fg_id);
        $wo_plan = $this->Wo_model->get_planned_materials($wo_id);
        
        return [
            'header' => $header,
            'bom' => $bom_components,
            'plan' => $wo_plan
        ];
    }
    
    // Cari komponen (material/semifinished) untuk ditambah ke MR
    public function search_components($term) {
        $term = $this->db->escape_like_str($term);
        $materials = $this->db->select("id, kode_material as code, nama_material as name, 'material' as type")
                              ->group_start()->like('kode_material', $term)->or_like('nama_material', $term)->group_end()
                              ->where('status', 'active')->limit(10)->get('materials')->result();
        $semifinished = $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type")
                                 ->group_start()->like('kode_barang', $term)->or_like('nama_barang', $term)->group_end()
                                 ->where('status', 'active')->limit(10)->get('semifinished_goods')->result();
        return array_merge($materials, $semifinished);
    }
    public function get_mrs_for_production($filters = []) {
        $this->db->select('mr.id, mr.mr_number, mr.issuance_number, mr.request_date, mr.status, u.fullname as requester_name, i.fullname as issuer_name');
        $this->db->from('mr_header mr');
        $this->db->join('users u', 'mr.requester_id = u.id', 'left');
        $this->db->join('users i', 'mr.issuer_id = i.id', 'left'); 
        if (!empty($filters['status'])) {
             $this->db->where('mr.status', $filters['status']);
        } else {
             $this->db->where_in('mr.status', ['Issued', 'Received']);
        }
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->group_start();
             $this->db->like('mr.mr_number', $term);
             $this->db->or_like('mr.issuance_number', $term);
             $this->db->group_end();
        }
        $this->db->order_by('mr.request_date', 'DESC');
        return $this->db->get()->result();
    }

    // ✅ BARU: Fungsi untuk Konfirmasi Terima oleh Produksi
    public function process_reception($mr_id, $user_id) {
        $this->db->trans_start();
        
        // 1. Update status MR Header
        $data = [
            'status' => 'Received',
            'receiver_id' => $user_id,
            'received_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $data);
        
        // 2. Pindahkan stok ke WIP (Work In Progress)
        // Ambil semua item yang di-issue
        $items = $this->db->get_where('mr_details', ['mr_header_id' => $mr_id])->result();
        $mr_header = $this->db->get_where('mr_header', ['id' => $mr_id])->row();
        
        $this->load->model('Stock_ledger_model');

        foreach ($items as $item) {
            if ($item->issued_qty > 0) {
                // Catat sebagai 'wip_in' (Masuk ke WIP)
                $last_balance = $this->Stock_ledger_model->get_last_balance($item->component_item_id, $item->component_item_type);
                $ledger_data = [
                    'item_id'         => $item->component_item_id,
                    'item_type'       => $item->component_item_type,
                    'transaction_date'=> date('Y-m-d H:i:s'),
                    'transaction_type'=> 'wip_in', // Tipe baru: Masuk WIP
                    'transaction_id'  => $mr_id,
                    'source_doc_type' => 'MR',
                    'source_doc_id'   => $mr_id,
                    'qty_in'          => $item->issued_qty, // Qty yg masuk WIP
                    'qty_out'         => 0,
                    'unit_value'      => 0, // Ambil dari ledger sebelumnya jika perlu
                    'balance'         => $last_balance // Saldo total TIDAK BERUBAH, hanya pindah "lokasi"
                ];
                // $this->Stock_ledger_model->insert($ledger_data); // <-- AKTIFKAN JIKA SUDAH SIAP MELACAK WIP
                // Untuk saat ini, kita anggap konfirmasi ini hanya update status MR
            }
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // ✅ BARU: Fungsi untuk Batal Terima oleh Produksi
    public function reverse_reception($mr_id) {
        $this->db->trans_start();
        
        // 1. Update status MR Header
        $data = [
            'status' => 'Issued',
            'receiver_id' => NULL,
            'received_at' => NULL
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $data);
        
        // 2. Hapus/reverse entri 'wip_in' dari stock_ledger
        // $this->load->model('Stock_ledger_model');
        // $this->Stock_ledger_model->delete_by_transaction('wip_in', $mr_id);
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}