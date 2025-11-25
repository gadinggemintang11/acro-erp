<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FinishedGoods_model extends CI_Model { // Nama Class harus sama persis dengan nama file

    private $table = 'finished_goods'; // Sesuaikan nama tabel jika berbeda

    public function __construct() {
        parent::__construct();
        $this->load->database(); // Load database
    }

    /**
     * Mengambil daftar Finished Goods yang aktif untuk dropdown
     * (Fungsi ini dipanggil oleh Production_process controller)
     *
     * @return array Array of objects (finished goods)
     */
    public function get_by_id($id) {
        if (empty($id) || !is_numeric($id)) {
            return null;
        }
        $query = $this->db->get_where($this->table, ['id' => $id]); // $this->table = 'finished_goods'
        
        if (!$query) {
             log_message('error', '[FinishedGoods_model] DB Error get_by_id: ' . print_r($this->db->error(), true));
             return null;
        }
        
        return $query->row(); // Kembalikan 1 baris hasil
    }
    public function get_finished_goods_list() {
        $this->db->select('id, kode_barang, nama_barang'); // Ambil kolom yang dibutuhkan
        // Tambahkan filter jika perlu, misal hanya yang statusnya 'active'
        // $this->db->where('status', 'active');
        $this->db->order_by('nama_barang', 'ASC'); // Urutkan berdasarkan nama
        $query = $this->db->get($this->table);
        return $query->result(); // Kembalikan hasil sebagai array of objects
    }
    public function get_all($filters = []) {
        $this->db->select('id, kode_barang, nama_barang, satuan');
        $this->db->from($this->table);
        
        // Filter opsional
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('kode_barang', $filters['search']);
            $this->db->or_like('nama_barang', $filters['search']);
            $this->db->group_end();
        }

        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }

        $this->db->order_by('nama_barang', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Ambil data FG berdasarkan ID
     */


    /**
     * Ambil FG berdasarkan kode
     */
    public function get_by_kode($kode) {
        return $this->db->get_where($this->table, ['kode_barang' => $kode])->row();
    }

    // --- Tambahkan fungsi model Finished Goods lainnya di sini ---
    // Contoh: get_by_id(), insert(), update(), delete(), dll.

}