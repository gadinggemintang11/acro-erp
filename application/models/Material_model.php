<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_model extends CI_Model {

    private $table = 'materials';

    public function get_all() {
        return $this->db->get($this->table)->result();
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function get_satuan_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'SATUAN', // Pastikan nama grup ini ada di db
            'status' => 'active'
        ])->result();
    }

    // ✅ FUNGSI BARU: Mengambil daftar kategori dinamis
    public function get_kategori_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'MATERIAL_CATEGORY', // Pastikan nama grup ini ada di db
            'status' => 'active'
        ])->result();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    // ✅ DITAMBAHKAN: 'return' agar controller bisa cek status
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    // ✅ DIUBAH: Sekarang menggunakan Soft Delete
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, ['status' => 'inactive']);
    }

    public function generate_kode($kategori, $asal)
    {
        // Tentukan prefix kategori
        $prefixKategori = strtoupper(substr($kategori, 0, 2));
        // Prefix asal
        $prefixAsal = strtoupper(substr($asal, 0, 1));

        $prefix = $prefixKategori . $prefixAsal;

        $this->db->like('kode_material', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table)->row();

        if ($last) {
            $num = (int) substr($last->kode_material, -4);
            $num++;
        } else {
            $num = 1;
        }

        return sprintf("%s-%04d", $prefix, $num);
    }
}