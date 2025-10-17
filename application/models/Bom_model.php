<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bom_model extends CI_Model {

    private $table_header = 'bom_header';
    private $table_details = 'bom_details';

    // === HEADER ===
    public function get_all($filters = []) {
        $this->db->select('h.id, h.status, h.revision, fg.kode_barang as fg_code, fg.nama_barang as fg_name');
        $this->db->from($this->table_header . ' h');
        $this->db->join('finished_goods fg', 'h.fg_id = fg.id', 'inner'); // Inner join karena BOM harus punya FG

        // Filter Search
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->group_start();
             $this->db->like('fg.kode_barang', $term);
             $this->db->or_like('fg.nama_barang', $term);
             $this->db->group_end();
        }

        $this->db->order_by('fg.nama_barang', 'ASC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id) {
        $this->db->select('h.*, fg.kode_barang as fg_code, fg.nama_barang as fg_name');
        $this->db->from($this->table_header . ' h');
        $this->db->join('finished_goods fg', 'h.fg_id = fg.id', 'inner');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    // Cek apakah FG sudah punya BOM
    public function check_bom_exists_for_fg($fg_id) {
        return $this->db->where('fg_id', $fg_id)->count_all_results($this->table_header) > 0;
    }

    public function insert_header($data) {
        // Pastikan FG belum punya BOM
        if ($this->check_bom_exists_for_fg($data['fg_id'])) {
            return false; // Gagal insert jika sudah ada
        }
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    public function update_header($id, $data) {
        // FG tidak boleh diubah
        unset($data['fg_id']);
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    public function delete_header($id) {
        // ON DELETE CASCADE akan hapus detail
        return $this->db->where('id', $id)->delete($this->table_header);
    }

    // === DETAILS ===
    public function get_details_by_header_id($bom_header_id) {
        $select_clause = "
            d.id, d.bom_header_id, d.quantity_required, d.unit_measure,
            d.component_item_id as item_id, d.component_item_type as item_type,
            CASE
                WHEN d.component_item_type = 'material' THEN m.kode_material
                WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang
            END as item_code,
            CASE
                WHEN d.component_item_type = 'material' THEN m.nama_material
                WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang
            END as item_name
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.bom_header_id', $bom_header_id);
        $this->db->order_by('item_name', 'ASC');
        return $this->db->get()->result();
    }

    public function insert_item($data) {
        // Ambil satuan dari master data
        $component = null;
        if($data['component_item_type'] == 'material'){
            $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('materials')->row();
        } else if ($data['component_item_type'] == 'semifinished'){
             $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('semifinished_goods')->row();
        }
        $data['unit_measure'] = $component ? $component->satuan : null;

        return $this->db->insert($this->table_details, $data);
    }

    // Opsional: Fungsi update item jika diperlukan
    // public function update_item($id, $data) { ... }

    public function delete_item($id) {
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // === HELPERS ===
    public function search_components($term) {
        $term = $this->db->escape_like_str($term);
        // Hanya cari Material & Semifinished
        $materials = $this->db->select("id, kode_material as code, nama_material as name, 'material' as type")
                              ->group_start()->like('kode_material', $term)->or_like('nama_material', $term)->group_end()
                              ->where('status', 'active')->limit(10)->get('materials')->result();
        $semifinished = $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type")
                                 ->group_start()->like('kode_barang', $term)->or_like('nama_barang', $term)->group_end()
                                 ->where('status', 'active')->limit(10)->get('semifinished_goods')->result();
        return array_merge($materials, $semifinished);
    }
    public function update_status($id, $new_status) {
        return $this->db->where('id', $id)->update($this->table_header, ['status' => $new_status]);
    }
}