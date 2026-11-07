<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Jasa_model extends CI_Model {

    private $table = 'jasa';

    public function get_all() {
        return $this->db->where('status', 'active')->get($this->table)->result();
    }

    public function get_paginated($limit, $offset, $keyword = null) {
        if ($keyword) {
            $this->db->group_start()
                ->like('kode_jasa', $keyword)
                ->or_like('nama_jasa', $keyword)
                ->or_like('kategori', $keyword)
                ->or_like('satuan', $keyword)
            ->group_end();
        }

        $this->db->order_by('id', 'DESC');
        $query = $this->db->get($this->table, $limit, $offset);
        return $query->result();
    }

    public function count_filtered($keyword = null) {
        if ($keyword) {
            $this->db->group_start()
                ->like('kode_jasa', $keyword)
                ->or_like('nama_jasa', $keyword)
                ->or_like('kategori', $keyword)
                ->or_like('satuan', $keyword)
            ->group_end();
        }
        return $this->db->count_all_results($this->table);
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    // Soft delete, sama seperti Material_model::delete()
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->update($this->table, ['status' => 'inactive']);
    }

    /**
     * Kode master barang v2 (ERP Revision Notes 2026-09-02): MAIN "SRV"
     * (Service), contoh SRV-000001. Tidak ada SUB code yang didefinisikan
     * user utk Service, jadi cuma MAIN+NUMBER (beda dgn Inventory/Material
     * yang punya SUB). Nomor urut 6 digit.
     *
     * HANYA dipakai utk jasa BARU — kode_jasa lama (format lama JS-0001)
     * tidak diubah/dimigrasikan.
     */
    public function generate_kode() {
        $prefix = 'SRV-';

        $this->db->like('kode_jasa', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table)->row();

        if ($last) {
            $num = (int) substr($last->kode_jasa, strlen($prefix));
            $num++;
        } else {
            $num = 1;
        }

        return sprintf("%s%06d", $prefix, $num);
    }
}
