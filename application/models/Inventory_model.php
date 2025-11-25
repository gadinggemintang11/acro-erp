<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_model extends CI_Model {

    // ✅ REVISI: Ganti nama tabel jika perlu, misal 'master_inventory'
    private $table = 'inventories'; // Pastikan ini nama tabel master inventory Anda

    /**
     * ✅ REVISI: get_all() sekarang join ke common_code untuk satuan
     */
    public function get_all() {
        $this->db->select('inv.*, cc_kat.description as kategori_desc, cc_sat.description as satuan_desc');
        $this->db->from($this->table . ' inv');
        // Join ke common_code untuk Kategori
        $this->db->join('common_code cc_kat', "inv.kategori = cc_kat.code AND cc_kat.group_code = 'INVENTORY_CATEGORY'", 'left');
        // Join ke common_code untuk Satuan
        $this->db->join('common_code cc_sat', "inv.satuan = cc_sat.code AND cc_sat.group_code = 'SATUAN'", 'left');
        $this->db->order_by('inv.id', 'DESC');
        
        $query = $this->db->get();
        // Cek jika query error
        if (!$query) {
            log_message('error', 'Inventory_model::get_all() - DB Error: ' . print_r($this->db->error(), true));
            return []; // Kembalikan array kosong jika error
        }
        return $query->result();
    }

    // Fungsi get_kategori_list() sudah benar
    public function get_kategori_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'INVENTORY_CATEGORY', // Pastikan grup ini ada
            'status' => 'active'
        ])->result();
    }
    
    // ✅ FUNGSI BARU: Mengambil daftar satuan dinamis
     public function get_satuan_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'SATUAN', // Grup untuk satuan
            'status' => 'active'
        ])->result();
    }


    public function insert($data) {
        if ($this->db->insert($this->table, $data)) {
            return $this->db->insert_id(); // Kembalikan ID jika sukses
        } else {
            log_message('error', 'Inventory_model::insert() - DB Error: ' . print_r($this->db->error(), true));
            return false; // Kembalikan false jika gagal
        }
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        $update_result = $this->db->update($this->table, $data);
        if (!$update_result) {
             log_message('error', 'Inventory_model::update() - DB Error: ' . print_r($this->db->error(), true));
        }
        // Kembalikan true jika update berhasil DAN ada baris terpengaruh
        return ($update_result && $this->db->affected_rows() > 0); 
    }

    public function delete($id) {
        // Soft delete (mengubah status)
        $update_result = $this->db->where('id', $id)->update($this->table, ['status' => 'Nonaktif']);
         if (!$update_result) {
             log_message('error', 'Inventory_model::delete() - DB Error: ' . print_r($this->db->error(), true));
         }
        return ($update_result && $this->db->affected_rows() > 0);
    }
    
    // ✅ REVISI: generate_kode() disesuaikan dengan nama tabel $this->table
    public function generate_kode($kategori_code) { // Ubah parameter ke kode kategori
        // Mapping prefix berdasarkan KODE dari common_code, BUKAN deskripsi
        // Anda mungkin perlu menyesuaikan KODE di common_code (misal: 'ATK', 'FUR', 'ELK', 'TLS', 'OTH')
         $map = [
            'ATK' => 'ATK', 
            'FUR' => 'FUR', 
            'ELK' => 'ELK', 
            'TLS' => 'TLS', 
            'OTH' => 'OTH' 
            // Tambahkan mapping lain jika perlu
        ];
        $prefix = $map[$kategori_code] ?? 'INV'; // Default 'INV' jika kode tidak ditemukan

        $full_prefix = 'INV-' . $prefix . '-';
        $prefix_length = strlen($full_prefix);

        $this->db->select('kode_barang'); // Hanya select kolom kode
        $this->db->like('kode_barang', $full_prefix, 'after'); // Cari yang awalannya sama
        $this->db->order_by('kode_barang', 'DESC'); // Urutkan berdasarkan kode DESC
        $this->db->limit(1);
        $last = $this->db->get($this->table)->row(); // Gunakan $this->table

        if ($last) {
            $last_number_str = substr($last->kode_barang, $prefix_length); // Ambil bagian angka
            if (is_numeric($last_number_str)) {
                 $last_number = (int)$last_number_str;
                 $new_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
            } else {
                 // Fallback jika format nomor lama tidak sesuai
                 log_message('warning', 'Inventory_model::generate_kode() - Format nomor terakhir tidak valid: ' . $last->kode_barang);
                 $new_number = '0001';
            }
        } else {
            $new_number = '0001'; // Nomor pertama
        }

        return $full_prefix . $new_number;
    }
}