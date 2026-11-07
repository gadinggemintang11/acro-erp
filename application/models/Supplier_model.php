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
        // bank_account_name baru ada setelah migration_supplier_account_name.sql;
        // dijaga supaya DB yang belum dimigrasi tidak membuat query gagal.
        $this->db->select('id, nama_supplier, jenis, bank_name, bank_account'
            . (db_has_column('suppliers', 'bank_account_name') ? ', bank_account_name' : ''));
        $this->db->where('status', 'active');
        $this->db->order_by('nama_supplier', 'ASC');
        return $this->db->get('suppliers')->result();
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    // ✅ BARU: Pagination dengan Search & Filter
    public function get_paginated($search = '', $jenis = '', $status = '', $limit = 10, $offset = 0, $kategori = '') {
        $query = $this->db->select('*')
            ->from($this->table);

        // Search: nama_supplier, kode_supplier, npwp, alamat
        if (!empty($search)) {
            $query->group_start()
                ->like('nama_supplier', $search)
                ->or_like('kode_supplier', $search)
                ->or_like('npwp', $search)
                ->or_like('alamat', $search)
                ->group_end();
        }

        // Filter: jenis
        if (!empty($jenis)) {
            $query->where('jenis', $jenis);
        }

        // Filter: kategori (general / produksi)
        if (!empty($kategori)) {
            $query->where('kategori', $kategori);
        }

        // Filter: status
        if (!empty($status)) {
            $query->where('status', $status);
        } else {
            // Default: show only active
            $query->where('status', 'active');
        }

        $query->order_by('id', 'DESC')
            ->limit($limit, $offset);

        return $query->get()->result();
    }

    // ✅ BARU: Count total records (untuk pagination)
    public function count_all($search = '', $jenis = '', $status = '', $kategori = '') {
        $query = $this->db->select('id')
            ->from($this->table);

        // Search
        if (!empty($search)) {
            $query->group_start()
                ->like('nama_supplier', $search)
                ->or_like('kode_supplier', $search)
                ->or_like('npwp', $search)
                ->or_like('alamat', $search)
                ->group_end();
        }

        // Filter: jenis
        if (!empty($jenis)) {
            $query->where('jenis', $jenis);
        }

        // Filter: kategori (general / produksi)
        if (!empty($kategori)) {
            $query->where('kategori', $kategori);
        }

        // Filter: status
        if (!empty($status)) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'active');
        }

        return $query->count_all_results();
    }

    // ✅ BARU: Get untuk export (all records)
    public function get_for_export($search = '', $jenis = '', $status = '', $kategori = '') {
        // Nama Akun ikut diekspor kalau kolomnya sudah ada (migrasi sudah jalan).
        $query = $this->db->select('kode_supplier, nama_supplier, jenis, kategori, npwp, alamat, telepon, bank_name, bank_account'
                . (db_has_column('suppliers', 'bank_account_name') ? ', bank_account_name' : '')
                . ', supply_description, status')
            ->from($this->table);

        // Search
        if (!empty($search)) {
            $query->group_start()
                ->like('nama_supplier', $search)
                ->or_like('kode_supplier', $search)
                ->or_like('npwp', $search)
                ->or_like('alamat', $search)
                ->group_end();
        }

        // Filter: jenis
        if (!empty($jenis)) {
            $query->where('jenis', $jenis);
        }

        // Filter: kategori (general / produksi)
        if (!empty($kategori)) {
            $query->where('kategori', $kategori);
        }

        // Filter: status
        if (!empty($status)) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'active');
        }

        $query->order_by('id', 'DESC');

        return $query->get()->result();
    }
}