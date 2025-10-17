<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fg_model extends CI_Model {

    private $table = 'finished_goods';

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

    public function get_satuan_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'SATUAN',
            'status' => 'active'
        ])->result();
    }

    public function get_kategori_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'FINISHED_CATEGORY',
            'status' => 'active'
        ])->result();
    }

    public function generate_kode($kategori, $asal)
    {
        $prefix = 'FG';
        $kat = strtoupper(substr($kategori, 0, 3));
        $asl = strtoupper(substr($asal, 0, 3)); // Menggunakan 3 huruf asal (LOK / IMP)

        $this->db->like('kode_barang', "{$prefix}-{$kat}-{$asl}-", 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table)->row();

        if ($last) {
            $last_number = (int)substr($last->kode_barang, -4);
            $new_number = str_pad($last_number + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $new_number = '0001';
        }

        return "{$prefix}-{$kat}-{$asl}-{$new_number}";
    }
    public function get_fgs_without_bom() {
        $this->db->select('fg.id, fg.kode_barang, fg.nama_barang');
        $this->db->from('finished_goods fg');
        // LEFT JOIN ke bom_header untuk mencari FG yang TIDAK ADA pasangannya di bom_header
        $this->db->join('bom_header bh', 'fg.id = bh.fg_id', 'left');
        $this->db->where('fg.status', 'active'); // Hanya FG aktif
        $this->db->where('bh.id IS NULL');      // Filter hanya yang TIDAK punya pasangan (belum ada BOM)
        $this->db->order_by('fg.nama_barang', 'ASC');
        return $this->db->get()->result();
    }
}