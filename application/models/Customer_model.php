<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Customer_model extends CI_Model {

    private $table = 'customers';

    public function get_all() {
        return $this->db->order_by('id', 'DESC')->get($this->table)->result();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    // ✅ DITAMBAHKAN: 'return' agar controller bisa cek status
    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    // ✅ DIUBAH: Sekarang menggunakan Soft Delete
    public function delete($id) {
        return $this->db->where('id', $id)->update($this->table, ['status' => 'inactive']);
    }

    public function generate_kode($jenis) {
        // ✅ Disederhanakan: Menggunakan data dari common_code
        // Pastikan 'Lokal' -> 'LOK', 'Ekspor' -> 'EKS' di common_code atau sesuaikan logika ini
        $prefix = strtoupper(substr($jenis, 0, 3)); 
        if ($jenis == 'Lokal') $prefix = 'CUS'; // Menjaga logika lama jika 'Lokal' -> 'CUS'
        
        $this->db->like('kode_customer', $prefix . '-', 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table)->row();

        if ($last) {
            $last_number = (int)substr($last->kode_customer, -4);
            $new_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $new_number = '0001';
        }

        return $prefix . '-' . $new_number;
    }
}