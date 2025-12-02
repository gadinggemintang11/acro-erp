<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mir_model extends CI_Model {

    private $table_header = 'mir_header';
    private $table_details = 'mir_details';
    private $table_grn_details = 'grn_details'; // Sesuaikan jika nama tabel GRN detail beda

    public function __construct() {
        parent::__construct();
        // $this->load->database(); // Load jika belum
    }

    // === HEADER ===
    public function get_all() {
        $this->db->reset_query();
        $this->db->select('h.id, h.mir_number, h.request_date, h.status, s.nama_supplier');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->order_by('h.id', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function get_header_by_id($id) {
        $this->db->reset_query();
        $this->db->select('
            h.*, s.nama_supplier,
            u.fullname as requester_name,
            cc.description as payment_type_desc,
            approver.fullname as approver_name /* Nama approver */
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->join('users u', 'h.requester_id = u.id', 'left');
        $this->db->join('common_code cc', 'h.payment_type_code = cc.code AND cc.group_code = "PAYMENT_TYPE"', 'left');
        $this->db->join('users approver', 'h.approved_by = approver.id', 'left'); // Join ke user approver
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
        // Hanya bisa edit saat Draft
        unset($data['status'], $data['approved_by'], $data['approved_at'], $data['requester_id'], $data['mir_number']);
        $this->db->reset_query();
        return $this->db->where('id', $id)->where('status', 'Draft')->update($this->table_header, $data);
    }

    public function delete_header($id) {
        // Cek dulu apakah ada detail
        $this->db->reset_query();
        $details_count = $this->db->where('mir_header_id', $id)->count_all_results($this->table_details);
        if ($details_count > 0) {
            log_message('warning', 'MIR Delete Header Gagal: Masih ada item detail. ID: '.$id);
            return false; // Jangan hapus jika ada detail
        }
        $this->db->reset_query();
        // Hapus header (hanya jika Draft)
        $delete_success = $this->db->where('id', $id)->where('status', 'Draft')->delete($this->table_header);
        return ($delete_success && $this->db->affected_rows() > 0);
    }

    public function generate_mir_number() {
        
        $this->db->reset_query(); $prefix = 'MIR/' . date('Ym') . '/';
        $this->db->like('mir_number', $prefix, 'after')->order_by('id', 'DESC');
        $last = $this->db->get($this->table_header, 1)->row();
        $new_num = $last ? (int) substr($last->mir_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    /**
     * FUNGSI BARU: Update status MIR (Approve, Unapprove)
     * Flow: Draft <-> Approved
     */
    public function update_status($id, $new_status, $userId = NULL) {
        $this->db->reset_query();
        $data = ['status' => $new_status];
        $allowed_to_reset = ['Draft']; // Status tujuan yg mereset approval

        // --- Logika Approval ---
        if ($new_status == 'Approved') {
            if (!$userId) { // User ID wajib ada saat approve
                 log_message('error', 'MIR Approve Gagal: User ID tidak ada. MIR ID: '.$id);
                 return false;
            }
            // Cek status saat ini (harus Draft)
            $current = $this->db->select('status')->where('id', $id)->get($this->table_header)->row();
            if (!$current || $current->status != 'Draft') {
                 log_message('warning', 'MIR Approve Gagal: Status awal bukan Draft. ID: '.$id);
                return false;
            }
            $data['approved_by'] = $userId;
            $data['approved_at'] = date('Y-m-d H:i:s');
            log_message('info', 'MIR Approved. ID: '.$id.', User: '.$userId);

        // --- Logika Unapproval ---
        } elseif (in_array($new_status, $allowed_to_reset)) {
            // Cek status saat ini (harus Approved)
             $current = $this->db->select('status')->where('id', $id)->get($this->table_header)->row();
            if (!$current || $current->status != 'Approved') {
                 log_message('warning', 'MIR Unapprove Gagal: Status awal bukan Approved. ID: '.$id);
                return false;
            }
            // Cek apakah sudah dipakai GRN
            if ($this->check_if_mir_has_receipts($id)) {
                 log_message('warning', 'MIR Unapprove Gagal: Sudah ada penerimaan barang. ID: '.$id);
                 return 'received'; // Kembalikan 'received' sebagai penanda error spesifik
            }
            $data['approved_by'] = NULL;
            $data['approved_at'] = NULL;
            log_message('info', 'MIR Unapproved (Back to Draft). ID: '.$id);
        }
        // Tambahkan else if untuk status 'Cancelled' jika perlu

        $this->db->where('id', $id);
        $update_result = $this->db->update($this->table_header, $data);

        return ($update_result && $this->db->affected_rows() > 0);
    }

     /**
     * FUNGSI BARU: Cek apakah MIR sudah ada penerimaan barang di GRN
     */
    public function check_if_mir_has_receipts($mir_id) {
        $this->db->reset_query();
        $this->db->where('source_doc_type', 'MIR');
        $this->db->where('source_doc_header_id', $mir_id);
        $this->db->limit(1);
        $count = $this->db->count_all_results($this->table_grn_details);
        return ($count > 0); // Return TRUE jika sudah ada penerimaan
    }


    // === DETAILS ===
    // (Fungsi get_details_by_id, insert_item, delete_item - Tidak berubah signifikan)
    public function get_details_by_id($mir_id) {
        $this->db->reset_query(); // Reset
        $select_clause = "
            d.id, d.mir_header_id, d.qty, d.allowance, d.total_qty, d.remarks, d.item_id, d.item_type, d.sales_contract_id,
            CASE
                WHEN d.item_type = 'material' THEN m.kode_material
                WHEN d.item_type = 'machine' THEN mc.kode_mesin
                WHEN d.item_type = 'inventory' THEN i.kode_barang
                WHEN d.item_type = 'semifinished' THEN sf.kode_barang
                ELSE 'N/A'
            END as item_code,
            CASE
                WHEN d.item_type = 'material' THEN m.nama_material
                WHEN d.item_type = 'machine' THEN mc.nama_mesin
                WHEN d.item_type = 'inventory' THEN i.nama_barang
                WHEN d.item_type = 'semifinished' THEN sf.nama_barang
                ELSE 'Unknown'
            END as item_name,
             CASE
                WHEN d.item_type = 'material' THEN m.satuan
                WHEN d.item_type = 'machine' THEN 'Unit' /* Asumsi */
                WHEN d.item_type = 'inventory' THEN 'Unit' /* Asumsi */
                WHEN d.item_type = 'semifinished' THEN sf.satuan
                ELSE 'N/A'
            END as item_unit,
            sc.sc_number
        "; 
        // ... (Lengkapi SELECT & JOIN seperti kode Anda sebelumnya) ...
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('materials m', 'd.item_id = m.id AND d.item_type = "material"', 'left');
        $this->db->join('machines mc', 'd.item_id = mc.id AND d.item_type = "machine"', 'left');
        $this->db->join('inventories i', 'd.item_id = i.id AND d.item_type = "inventory"', 'left');
        $this->db->join('semifinished_goods sf', 'd.item_id = sf.id AND d.item_type = "semifinished"', 'left');
        $this->db->join('sales_contracts sc', 'd.sales_contract_id = sc.id', 'left');
        $this->db->where('d.mir_header_id', $mir_id);
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }
    public function insert_item($data) {
        // Cek duplikasi?
        $this->db->reset_query();
        return $this->db->insert($this->table_details, $data);
    }
    public function delete_item($id) {
        $this->db->reset_query();
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // ✅ BARU: Cek apakah item sudah ada di MIR
    public function check_existing_item($mir_id, $item_id, $item_type) {
        $this->db->reset_query();
        $this->db->where('mir_header_id', $mir_id);
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        return $this->db->get($this->table_details)->row();
    }

    // ✅ BARU: Update qty item yang sudah ada
    public function update_item_qty($detail_id, $new_qty) {
        $this->db->reset_query();
        $this->db->where('id', $detail_id);
        return $this->db->update($this->table_details, ['qty' => $new_qty]);
    }

    // ✅ BARU: Update item detail (flexible untuk multiple fields)
    public function update_item($detail_id, $data) {
        $this->db->reset_query();
        $this->db->where('id', $detail_id);
        return $this->db->update($this->table_details, $data);
    }

    // ✅ BARU: Get all available items untuk ditambahkan
    public function get_available_items_for_mir() {
        $items = [];
        
        // Materials
        $this->db->reset_query();
        $materials = $this->db->select("id, kode_material as code, nama_material as name, 'material' as type, satuan as unit")
                              ->where('status', 'active')
                              ->order_by('nama_material', 'ASC')
                              ->get('materials')->result();
        
        // Semifinished
        $this->db->reset_query();
        $semifinished = $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type, satuan as unit")
                                 ->where('status', 'active')
                                 ->order_by('nama_barang', 'ASC')
                                 ->get('semifinished_goods')->result();
        
        // Machines
        $this->db->reset_query();
        $machines = $this->db->select("id, kode_mesin as code, nama_mesin as name, 'machine' as type, 'Unit' as unit")
                             ->where('status', 'Active')
                             ->order_by('nama_mesin', 'ASC')
                             ->get('machines')->result();
        
        // Inventories
        $this->db->reset_query();
        $inventories = $this->db->select("id, kode_barang as code, nama_barang as name, 'inventory' as type, 'Unit' as unit")
                                ->where('status', 'Active')
                                ->order_by('nama_barang', 'ASC')
                                ->get('inventories')->result();
        
        return array_merge($materials, $semifinished, $machines, $inventories);
    }

    // === HELPERS ===
    // (Fungsi search_items_for_mir - Tidak berubah)
    public function search_items_for_mir($term) {
        // ... (Kode search_items_for_mir tetap sama) ...
        $term = $this->db->escape_like_str($term);
        $this->db->reset_query(); $materials = $this->db->select("id, kode_material as code, nama_material as name, 'material' as type")->like('nama_material', $term)->or_like('kode_material', $term)->where('status', 'active')->limit(5)->get('materials')->result();
        $this->db->reset_query(); $machines = $this->db->select("id, kode_mesin as code, nama_mesin as name, 'machine' as type")->like('nama_mesin', $term)->or_like('kode_mesin', $term)->where('status', 'Active')->limit(5)->get('machines')->result();
        $this->db->reset_query(); $inventories = $this->db->select("id, kode_barang as code, nama_barang as name, 'inventory' as type")->like('nama_barang', $term)->or_like('kode_barang', $term)->where('status', 'Active')->limit(5)->get('inventories')->result();
        $this->db->reset_query(); $semifinished = $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type")->like('nama_barang', $term)->or_like('kode_barang', $term)->where('status', 'active')->limit(5)->get('semifinished_goods')->result();
        return array_merge($materials, $semifinished, $machines, $inventories);
    }

} // End Class