<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Machine_model extends CI_Model {

    private $table = 'machines';

    public function get_all() {
        $this->db->select('
            m.*, 
            cc_cat.description as kategori_desc,
            cc_sat.description as satuan_desc 
        ');
        $this->db->from('machines m');
        $this->db->join('common_code cc_cat', 'm.kategori = cc_cat.code AND cc_cat.group_code = "MACHINE_CATEGORY"', 'left');
        
        // ▼ TAMBAHKAN JOIN INI ▼
        $this->db->join('common_code cc_sat', 'm.satuan = cc_sat.code AND cc_sat.group_code = "SATUAN"', 'left');

        // Tambahkan ini jika Anda hanya ingin menampilkan yang 'Active'
        // $this->db->where('m.status', 'Active'); 

        $this->db->order_by('m.kode_mesin', 'ASC');
        return $this->db->get()->result();
    }

    // ✅ FUNGSI BARU: Mengambil daftar kategori dinamis
    public function get_kategori_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'MACHINE_CATEGORY', // Pastikan grup ini ada
            'status' => 'active'
        ])->result();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    // ✅ DITAMBAHKAN: 'return' agar controller bisa cek status
    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    // ✅ DIUBAH: Sekarang menggunakan Soft Delete (mengubah status, bukan menghapus)
    public function delete($id) {
        return $this->db->where('id', $id)->update($this->table, ['status' => 'Nonaktif']);
    }

    public function generate_kode($kategori) {
        // Logika ini sudah bagus dan bisa tetap digunakan
        $prefix = strtoupper(substr($kategori, 0, 3)); // MES / PER
        $this->db->like('kode_mesin', $prefix . '-', 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table)->row();

        if ($last) {
            $last_number = (int)substr($last->kode_mesin, -4);
            $new_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $new_number = '0001';
        }

        return $prefix . '-' . $new_number;
    }
}