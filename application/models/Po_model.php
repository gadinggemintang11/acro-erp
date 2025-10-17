<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Po_model extends CI_Model {

    private $table_header = 'po_header';
    private $table_details = 'po_details';

    // ... (Fungsi get_all_po, get_po_header_by_id, insert_header, update_header, delete_header, generate_po_number tetap sama) ...
    public function get_all_po() {
        $this->db->select('h.id, h.po_number, h.po_date, h.status, s.nama_supplier');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_po_header_by_id($id) {
        $this->db->select('
            h.*, s.nama_supplier, s.alamat, s.telepon,
            cc_ship.description as shipping_mode_desc,
            cc_pay.description as payment_mode_desc,
            cc_curr.description as currency_desc
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join('suppliers s', 'h.supplier_id = s.id', 'left');
        $this->db->join('common_code cc_ship', 'h.shipping_mode_code = cc_ship.code AND cc_ship.group_code = "SHIPPING_MODE"', 'left');
        $this->db->join('common_code cc_pay', 'h.payment_mode_code = cc_pay.code AND cc_pay.group_code = "PAYMENT_MODE"', 'left');
        $this->db->join('common_code cc_curr', 'h.currency_code = cc_curr.code AND cc_curr.group_code = "CURRENCY"', 'left');
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

    public function generate_po_number() {
        $prefix = 'PO/' . date('Ym') . '/';
        $this->db->like('po_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table_header, 1)->row();
        $new_num = $last ? (int) substr($last->po_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }


    // ✅ BARU: Fungsi untuk update status PO
    public function update_status($id, $new_status) {
        return $this->db->where('id', $id)->update($this->table_header, ['status' => $new_status]);
    }


    // === DETAILS ===
    // ... (Fungsi get_po_details_by_id, insert_item, delete_item, search_items_for_po tetap sama) ...
     public function get_po_details_by_id($po_id) {
        $select_clause = "
            d.id, d.po_header_id, d.item_id, d.item_type, d.description, d.qty,
            d.base_price, d.local_price, d.total_local,
            CASE
                WHEN d.item_type = 'material' THEN m.kode_material
                WHEN d.item_type = 'machine' THEN mc.kode_mesin
                WHEN d.item_type = 'inventory' THEN i.kode_barang
            END as item_code,
            CASE
                WHEN d.item_type = 'material' THEN m.nama_material
                WHEN d.item_type = 'machine' THEN mc.nama_mesin
                WHEN d.item_type = 'inventory' THEN i.nama_barang
            END as item_name,
            CASE
                WHEN d.item_type = 'material' THEN m.satuan
                ELSE 'Unit'
            END as item_unit,
            sc.sc_number
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from('po_details d'); // Alias ditambahkan di sini
        $this->db->join('materials m', 'd.item_id = m.id AND d.item_type = "material"', 'left');
        $this->db->join('machines mc', 'd.item_id = mc.id AND d.item_type = "machine"', 'left');
        $this->db->join('inventories i', 'd.item_id = i.id AND d.item_type = "inventory"', 'left');
        $this->db->join('sales_contracts sc', 'd.sales_contract_id = sc.id', 'left');
        $this->db->where('d.po_header_id', $po_id);
        return $this->db->get()->result();
    }


    public function insert_item($data) {
        return $this->db->insert('po_details', $data); // Nama tabel ditambahkan di sini
    }

    public function delete_item($id) {
        return $this->db->where('id', $id)->delete('po_details'); // Nama tabel ditambahkan di sini
    }

    public function search_items_for_po($term) {
        $term = $this->db->escape_like_str($term);
        $materials = $this->db->select("id, kode_material as code, nama_material as name, 'material' as type")->like('nama_material', $term)->or_like('kode_material', $term)->where('status', 'active')->limit(10)->get('materials')->result();
        $machines = $this->db->select("id, kode_mesin as code, nama_mesin as name, 'machine' as type")->like('nama_mesin', $term)->or_like('kode_mesin', $term)->where('status', 'Active')->limit(10)->get('machines')->result();
        $inventories = $this->db->select("id, kode_barang as code, nama_barang as name, 'inventory' as type")->like('nama_barang', $term)->or_like('kode_barang', $term)->where('status', 'Active')->limit(10)->get('inventories')->result();
        return array_merge($materials, $machines, $inventories);
    }
}