<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_model extends CI_Model {

    private $table = 'materials';

    public function get_all() {
        return $this->db->get($this->table)->result();
    }

    /**
     * Ambil data dengan pagination & pencarian
     */
    public function get_paginated($limit, $offset, $keyword = null) {
        if ($keyword) {
            $this->db->group_start()
                ->like('kode_material', $keyword)
                ->or_like('nama_material', $keyword)
                ->or_like('kategori', $keyword)
                ->or_like('kategorirm', $keyword)
                ->or_like('asal', $keyword)
                ->or_like('satuan', $keyword)
            ->group_end();
        }

        $this->db->order_by('id', 'DESC');
        $query = $this->db->get($this->table, $limit, $offset);
        return $query->result();
    }

    /**
     * Hitung total data untuk pagination dengan filter pencarian
     */
    public function count_filtered($keyword = null) {
        if ($keyword) {
            $this->db->group_start()
                ->like('kode_material', $keyword)
                ->or_like('nama_material', $keyword)
                ->or_like('kategori', $keyword)
                ->or_like('kategorirm', $keyword)
                ->or_like('asal', $keyword)
                ->or_like('satuan', $keyword)
            ->group_end();
        }
        return $this->db->count_all_results($this->table);
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
    public function get_kategorirm_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'RM_CATEGORY', // Pastikan nama grup ini ada di db
            'status' => 'active'
        ])->result();
    }
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

    /**
     * Kode material LAMA (format {2huruf kategori}{1huruf asal}-{2huruf kategorirm}-{4digit}).
     * Dipertahankan untuk referensi/backward-compat, TIDAK dipakai lagi oleh form baru.
     * @deprecated pakai generate_kode() (format MTR-SUB-ORIGIN-NUMBER, ERP Revision Notes 2026-09-02)
     */
    public function generate_kode_legacy($kategori, $asal, $kategorirm = '')
    {
        $prefixKategori = strtoupper(substr($kategori, 0, 2));
        $prefixasal = strtoupper(substr($asal, 0, 1));
        $prefixRm = strtoupper(substr($kategorirm, 0, 2));
        $prefix = $prefixKategori . $prefixasal . '-' . $prefixRm;

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

    /**
     * Kode master barang v2 (ERP Revision Notes 2026-09-02): format
     * MTR-SUB-ORIGIN-NUMBER, contoh MTR-RAW-L-00001 / MTR-RAW-I-00001.
     * MAIN selalu "MTR" (Material Production). SUB = sub_class (RAW/SUP/CON/WIP).
     * ORIGIN = L (Lokal) / I (Import), dari kolom `asal` yang sudah ada.
     * Nomor urut 5 digit, per kombinasi SUB+ORIGIN.
     *
     * HANYA dipakai utk material BARU — kode_material lama (format lama
     * dari generate_kode_legacy()) tidak diubah/dimigrasikan.
     */
    public function generate_kode($sub_class, $asal) {
        $sub    = strtoupper($sub_class);
        $origin = (stripos((string) $asal, 'impor') === 0) ? 'I' : 'L';
        $prefix = 'MTR-' . $sub . '-' . $origin . '-';
        $prefix_length = strlen($prefix);

        $this->db->select('kode_material');
        $this->db->like('kode_material', $prefix, 'after');
        $this->db->order_by('kode_material', 'DESC');
        $this->db->limit(1);
        $last = $this->db->get($this->table)->row();

        if ($last) {
            $last_number_str = substr($last->kode_material, $prefix_length);
            $num = is_numeric($last_number_str) ? ((int) $last_number_str + 1) : 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, 5, '0', STR_PAD_LEFT);
    }

    // ✅ BARU: Cek apakah material mencapai/melampaui minimum stock
    public function check_stock_status($material_id) {
        $material = $this->get_by_id($material_id);
        
        if (!$material) {
            return [
                'status' => 'error',
                'message' => 'Material tidak ditemukan'
            ];
        }

        // Ambil current stock dari stock_ledger
        $this->db->select('balance');
        $this->db->from('stock_ledger');
        $this->db->where('item_id', $material_id);
        $this->db->where('item_type', 'material');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $ledger = $this->db->get()->row();
        
        $current_stock = $ledger ? (float)$ledger->balance : 0;
        $minimum_stock = isset($material->minimum_stock) ? (float)$material->minimum_stock : 0;
        
        if ($current_stock <= $minimum_stock && $minimum_stock > 0) {
            return [
                'status' => 'warning',
                'alert' => true,
                'message' => "⚠️ ALERT: Stock material '{$material->nama_material}' mencapai atau di bawah minimum ({$current_stock} ≤ {$minimum_stock})",
                'kode_material' => $material->kode_material,
                'current_stock' => $current_stock,
                'minimum_stock' => $minimum_stock
            ];
        }
        
        return [
            'status' => 'ok',
            'alert' => false,
            'current_stock' => $current_stock,
            'minimum_stock' => $minimum_stock
        ];
    }
}