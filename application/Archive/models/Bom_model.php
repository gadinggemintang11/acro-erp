<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bom_model extends CI_Model {

    // --- Definisi Nama Tabel ---
    private $table_header = 'bom_header';
    private $table_details = 'bom_details';
    private $table_fg = 'finished_goods'; // Nama tabel Finished Goods
    private $table_materials = 'materials';
    private $table_semifinished = 'semifinished_goods';
    // --- Akhir Definisi ---

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // === HEADER ===
    public function get_all($filters = []) {
        $this->db->select('h.id, h.status, h.revision, fg.kode_barang as fg_code, fg.nama_barang as fg_name');
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_fg . ' fg', 'h.fg_id = fg.id', 'inner');

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
        $this->db->join($this->table_fg .' fg', 'h.fg_id = fg.id', 'inner');
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    public function check_bom_exists_for_fg($fg_id) {
        return $this->db->where('fg_id', $fg_id)->count_all_results($this->table_header) > 0;
    }

    public function insert_header($data) {
        if ($this->check_bom_exists_for_fg($data['fg_id'])) {
            return false;
        }
        // Pastikan status default adalah Draft jika tidak diset
        if (empty($data['status'])) {
            $data['status'] = 'Draft';
        }
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    // Update Header (Tidak mengizinkan ubah FG atau status secara langsung)
    public function update_header($id, $data) {
        unset($data['fg_id'], $data['status']); // Hapus field yang tidak boleh diubah di sini
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    /**
     * Delete Header (MODIFIKASI: Menggunakan Transaction)
     * Hanya bisa hapus jika status Draft
     */
    public function delete_header($id) {
        $this->db->trans_start();
        // Hapus detail dulu
        $this->db->where('bom_header_id', $id)->delete($this->table_details);
        // Hapus header (hanya jika Draft)
        $this->db->where('id', $id)->where('status', 'Draft')->delete($this->table_header);
        $affected_header = $this->db->affected_rows(); // Cek apakah header terhapus
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Gagal menghapus BOM (ID: ' . $id . ') dari database.');
        }

        // Return true hanya jika transaksi berhasil DAN header benar-benar terhapus
        return ($this->db->trans_status() && $affected_header > 0);
    }

    public function update_status($id, $new_status) {
        $this->db->where('id', $id);
        $update_result = $this->db->update($this->table_header, ['status' => $new_status]);
        return ($update_result && $this->db->affected_rows() > 0);
    }


    // === DETAILS ===
    public function get_details_by_header_id($bom_header_id) {
        $select_clause = "
            d.id, d.bom_header_id, d.quantity_required,
            d.component_item_id as item_id, d.component_item_type as item_type,
            CASE
                WHEN d.component_item_type = 'material' THEN m.kode_material
                WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang
                ELSE 'N/A'
            END as component_code,
            CASE
                WHEN d.component_item_type = 'material' THEN m.nama_material
                WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang
                ELSE 'Unknown Item'
            END as component_name,
             CASE
                WHEN d.component_item_type = 'material' THEN m.satuan
                WHEN d.component_item_type = 'semifinished' THEN sf.satuan
                ELSE 'N/A'
            END as component_unit /* Ambil satuan dari master */
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join($this->table_materials . ' m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join($this->table_semifinished . ' sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.bom_header_id', $bom_header_id);
        $this->db->order_by('component_name', 'ASC');
        return $this->db->get()->result();
    }

    public function insert_item($data) {
        // Cek duplikasi sebelum insert
        $exists = $this->db->where('bom_header_id', $data['bom_header_id'])
                             ->where('component_item_id', $data['component_item_id'])
                             ->where('component_item_type', $data['component_item_type'])
                             ->count_all_results($this->table_details);
        if ($exists > 0) {
            return false; // Komponen sudah ada
        }
        return $this->db->insert($this->table_details, $data);
    }

    /**
     * Update Item Detail (FUNGSI BARU)
     * Hanya bisa update jika status header Draft
     */
    public function update_item($detail_id, $data) {
        // 1. Dapatkan header_id dari detail
        $this->db->select('bom_header_id');
        $this->db->where('id', $detail_id);
        $detail = $this->db->get($this->table_details)->row();
        if (!$detail) {
            log_message('error', 'Update BOM item gagal: Detail ID ' . $detail_id . ' tidak ditemukan.');
            return false;
        }

        // 2. Cek status header
        $this->db->select('status');
        $this->db->where('id', $detail->bom_header_id);
        $header = $this->db->get($this->table_header)->row();

        // ================================================================
        // PERBAIKAN: Gunakan trim() dan strtoupper()
        // Ini memperbaiki bug di mana 'Draft ' (dengan spasi) dianggap tidak valid
        // ================================================================
        if (!$header || strtoupper(trim($header->status)) != 'DRAFT') {
            log_message('error', 'Update BOM item gagal: Status header bukan Draft (ID Header: ' . $detail->bom_header_id . '). Status terdeteksi: "' . $header->status . '"');
            return false;
        }
        // ================================================================
        // AKHIR PERBAIKAN
        // ================================================================

        // 3. Lakukan Update (Hanya kolom yang diizinkan, misal quantity)
        $allowed_updates = ['quantity_required']; // Kolom yang boleh diupdate
        $update_data = array_intersect_key($data, array_flip($allowed_updates));

        if (empty($update_data)) {
             log_message('error', 'Update BOM item gagal: Tidak ada data valid untuk diupdate.');
             return false; // Tidak ada data valid untuk diupdate
        }

        $this->db->where('id', $detail_id);
        $update_result = $this->db->update($this->table_details, $update_data);
        log_message('debug', 'Update BOM Item SQL: ' . $this->db->last_query());

        // Cek jika update berhasil ATAU jika data tidak berubah (affected_rows == 0 tapi tidak error)
        if ($update_result) {
            return true; // Berhasil, atau tidak ada yg berubah
        } else {
            return false; // Error saat update
        }
    }

    public function delete_item($id) {
        // Sebaiknya cek status header di controller sebelum panggil ini
        return $this->db->where('id', $id)->delete($this->table_details);
    }
    
    // Fungsi baru untuk mengambil info detail item (untuk log)
    public function get_detail_item_by_id($id) {
        return $this->db->where('id', $id)->get($this->table_details)->row();
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

    // ✅ BARU: Get BOM by FG ID (untuk MIR auto-load)
    public function get_bom_by_fg($fg_id) {
        // Get BOM header for this FG
        $this->db->select('id');
        $this->db->where('fg_id', $fg_id);
        // Status Active atau Confirm dianggap sebagai BOM yang sudah approved
        $this->db->where_in('status', ['Active', 'Confirm']);
        $header = $this->db->get($this->table_header)->row();

        if (!$header) {
            return []; // Return empty jika tidak ada BOM atau belum approved
        }

        // Get BOM details
        $select_clause = "
            d.id, d.quantity_required as qty, 
            d.component_item_id as material_id, 
            d.component_item_type as item_type,
            CASE
                WHEN d.component_item_type = 'material' THEN m.kode_material
                WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang
                ELSE 'N/A'
            END as code,
            CASE
                WHEN d.component_item_type = 'material' THEN m.nama_material
                WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang
                ELSE 'Unknown'
            END as name
        ";

        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join($this->table_materials . ' m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join($this->table_semifinished . ' sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.bom_header_id', $header->id);
        
        return $this->db->get()->result();
    }

} // End Class
