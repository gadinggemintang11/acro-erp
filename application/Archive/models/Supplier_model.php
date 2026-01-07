<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Supplier_model extends CI_Model {

    private $table = 'suppliers';

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
        $prefix = strtoupper(substr($jenis, 0, 3)); // LOK / IMP
        $this->db->like('kode_supplier', $prefix . '-', 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table)->row();

        if ($last) {
            $last_number = (int)substr($last->kode_supplier, -4);
            $new_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $new_number = '0001';
        }

        return $prefix . '-' . $new_number;
    }
    public function get_all_suppliers() {
        $this->db->select('id, nama_supplier, jenis');
        $this->db->where('status', 'active');
        $this->db->order_by('nama_supplier', 'ASC');
        return $this->db->get('suppliers')->result();
    }
}