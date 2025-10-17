<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mir_model extends CI_Model {

    private $table_header = 'mir_header';
    private $table_details = 'mir_details';

    // === HEADER ===
    public function get_all() {
        $this->db->select('h.id, h.mir_number, h.request_date, h.status, s.nama_supplier');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id) {
        $this->db->select('h.*, s.nama_supplier, u.fullname as requester_name, cc.description as payment_type_desc');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->join('users u', 'h.requester_id = u.id', 'left');
        $this->db->join('common_code cc', 'h.payment_type_code = cc.code AND cc.group_code = "PAYMENT_TYPE"', 'left');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    // ✅ BARU: Fungsi untuk update header MIR
    public function update_header($id, $data) {
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    // ✅ BARU: Fungsi untuk hapus header MIR
    public function delete_header($id) {
        // ON DELETE CASCADE di database akan otomatis menghapus detailnya
        return $this->db->where('id', $id)->delete($this->table_header);
    }

    public function generate_mir_number() {
        $prefix = 'MIR/' . date('Ym') . '/';
        $this->db->like('mir_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table_header, 1)->row();
        $new_num = $last ? (int) substr($last->mir_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // === DETAILS ===
    public function get_details_by_id($mir_id) {
        $select_clause = "
            d.id, d.mir_header_id, d.qty, d.remarks,
            CASE
                WHEN d.item_type = 'material' THEN m.kode_material
                WHEN d.item_type = 'machine' THEN mc.kode_mesin
                WHEN d.item_type = 'inventory' THEN i.kode_barang
                WHEN d.item_type = 'semifinished' THEN sf.kode_barang
            END as item_code,
            CASE
                WHEN d.item_type = 'material' THEN m.nama_material
                WHEN d.item_type = 'machine' THEN mc.nama_mesin
                WHEN d.item_type = 'inventory' THEN i.nama_barang
                WHEN d.item_type = 'semifinished' THEN sf.nama_barang
            END as item_name,
            sc.sc_number
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('materials m', 'd.item_id = m.id AND d.item_type = "material"', 'left');
        $this->db->join('machines mc', 'd.item_id = mc.id AND d.item_type = "machine"', 'left');
        $this->db->join('inventories i', 'd.item_id = i.id AND d.item_type = "inventory"', 'left');
        $this->db->join('semifinished_goods sf', 'd.item_id = sf.id AND d.item_type = "semifinished"', 'left');
        $this->db->join('sales_contracts sc', 'd.sales_contract_id = sc.id', 'left');
        $this->db->where('d.mir_header_id', $mir_id);
        return $this->db->get()->result();
    }

    public function insert_item($data) {
        return $this->db->insert($this->table_details, $data);
    }

    public function delete_item($id) {
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // === HELPERS ===
    public function search_items_for_mir($term) {
        $term = $this->db->escape_like_str($term);
        $materials = $this->db->select("id, kode_material as code, nama_material as name, 'material' as type")->like('nama_material', $term)->or_like('kode_material', $term)->where('status', 'active')->limit(5)->get('materials')->result();
        $machines = $this->db->select("id, kode_mesin as code, nama_mesin as name, 'machine' as type")->like('nama_mesin', $term)->or_like('kode_mesin', $term)->where('status', 'Active')->limit(5)->get('machines')->result();
        $inventories = $this->db->select("id, kode_barang as code, nama_barang as name, 'inventory' as type")->like('nama_barang', $term)->or_like('kode_barang', $term)->where('status', 'Active')->limit(5)->get('inventories')->result();
        $semifinished = $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type")->like('nama_barang', $term)->or_like('kode_barang', $term)->where('status', 'active')->limit(5)->get('semifinished_goods')->result();
        
        return array_merge($materials, $semifinished, $machines, $inventories);
    }
}