<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inventory_model extends CI_Model {

    // ✅ REVISI: Ganti nama tabel jika perlu, misal 'master_inventory'
    private $table = 'inventories'; // Pastikan ini nama tabel master inventory Anda

    /**
     * Jenis pemakaian barang (user 2026-10-06). JANGAN dikira sama dengan
     * kolom `kategori` yang sudah ada -- itu sub-kategori kode barang (OFC,
     * MTC, PROD, ...). Kolom ini menentukan barang muncul di pencarian item
     * IRF kategori mana.
     */
    const USAGE_CATEGORIES = ['General', 'Produksi'];
    const USAGE_DEFAULT    = 'General';

    /** Potongan kode barang untuk tiap jenis pemakaian (user 2026-10-06). */
    const USAGE_CODES = ['General' => 'GNR', 'Produksi' => 'PRD'];

    /** Kolom baru dari migration_inventory_usage_category.sql. */
    public function has_usage_category() {
        return function_exists('db_has_column') && db_has_column($this->table, 'usage_category');
    }

    /**
     * Nilai apa pun dari luar dipaksa ke salah satu nilai yang sah.
     *
     * Pencocokan SENGAJA tidak peduli huruf besar/kecil: nilainya juga datang
     * dari kolom Excel yang diketik tangan, dan "produksi" yang diam-diam
     * berubah jadi General adalah kesalahan yang tidak kelihatan sampai
     * barangnya hilang dari pencarian IRF Produksi.
     */
    public static function clean_usage_category($value) {
        $value = trim((string) $value);
        foreach (self::USAGE_CATEGORIES as $valid) {
            if (strcasecmp($value, $valid) === 0) return $valid;
        }
        return self::USAGE_DEFAULT;
    }

    /**
     * Buang kolom yang belum ada di DB ini supaya halaman tetap hidup di
     * environment yang migrasinya belum dijalankan (pola yang sama dipakai
     * replaced_by_id di get_with_pagination_and_search()).
     */
    private function _drop_unsupported_columns($data) {
        if (isset($data['usage_category']) && !$this->has_usage_category()) {
            unset($data['usage_category']);
        }
        return $data;
    }

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
    /**
     * Kandidat item PENGGANTI untuk dropdown "diganti oleh" di modal Edit:
     * semua item ber-status Active (id + kode + nama saja, secukupnya untuk
     * dropdown). Dipakai saat item lama dinonaktifkan karena sudah ada item
     * berkode baru (user 2026-09-09).
     *
     * Sengaja TIDAK memfilter "kecuali dirinya sendiri" / "nama sama" di sini:
     * modal Edit di view cuma SATU dan dipakai bergantian untuk semua baris,
     * jadi daftar ini dikirim sekali lalu JS yang membuang baris yang sedang
     * diedit & mengelompokkan yang namanya sama ke atas.
     */
    public function get_active_items_min() {
        $this->db->select('id, kode_barang, nama_barang');
        $this->db->from($this->table);
        $this->db->where('status', 'Active');
        $this->db->order_by('nama_barang', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function get_kategori_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'INVENTORY_CATEGORY', // Pastikan grup ini ada
            'status' => 'active'
        ])->result();
    }

    /**
     * Sub kategori BARU (kode master barang v2, format MAIN-SUB-NUMBER,
     * ERP Revision Notes 2026-09-02) — dipakai KHUSUS di form Tambah Barang
     * (item baru). Sengaja grup common_code TERPISAH dari INVENTORY_CATEGORY
     * lama supaya kategori lama milik item existing tidak tercampur/hilang.
     */
    public function get_sub_kategori_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'INVENTORY_SUB_CATEGORY',
            'status' => 'active'
        ])->result();
    }

    /** Jenis Item (Stock/Non-Stock) — sama pola common_code, dipakai form Tambah Barang. */
    public function get_item_type_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'INVENTORY_ITEM_TYPE',
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
        $data = $this->_drop_unsupported_columns($data);
        if ($this->db->insert($this->table, $data)) {
            return $this->db->insert_id(); // Kembalikan ID jika sukses
        } else {
            log_message('error', 'Inventory_model::insert() - DB Error: ' . print_r($this->db->error(), true));
            return false; // Kembalikan false jika gagal
        }
    }

    public function update($id, $data) {
        $data = $this->_drop_unsupported_columns($data);
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
    
    /**
     * Filter tambahan list Master Inventory (user 2026-09-09): kode kategori
     * & status. Dipakai bareng oleh query data DAN query hitung total supaya
     * pagination-nya tidak meleset.
     *
     * @param array  $filters ['kategori' => code, 'status' => Active|Nonaktif]
     * @param string $alias   alias tabel inventories di query pemanggil
     */
    private function _apply_list_filters($filters, $alias) {
        if (!empty($filters['kategori'])) {
            $this->db->where($alias . '.kategori', $filters['kategori']);
        }
        if (!empty($filters['status'])) {
            $this->db->where($alias . '.status', $filters['status']);
        }
    }

    /**
     * Isi dropdown filter "Kode Kategori": kode yang BENAR-BENAR dipakai item
     * (bukan seluruh master kategori) + jumlah itemnya.
     *
     * Deskripsi diambil lewat subquery, bukan JOIN, karena satu kode bisa
     * muncul di DUA grup common_code sekaligus (INVENTORY_CATEGORY lama &
     * INVENTORY_SUB_CATEGORY baru) — dgn JOIN, barisnya dobel dan COUNT-nya
     * ikut menggelembung.
     */
    public function get_used_kategori_codes() {
        $sql = "
            SELECT k.code, k.total,
                   (SELECT cc.description FROM common_code cc
                     WHERE cc.code = k.code
                       AND cc.group_code IN ('INVENTORY_CATEGORY', 'INVENTORY_SUB_CATEGORY')
                     LIMIT 1) AS description
            FROM (
                SELECT kategori AS code, COUNT(*) AS total
                FROM " . $this->table . "
                WHERE kategori IS NOT NULL AND kategori <> ''
                GROUP BY kategori
            ) k
            ORDER BY k.code ASC";
        $query = $this->db->query($sql);
        return $query ? $query->result() : [];
    }

    public function get_with_pagination_and_search($limit, $offset, $search = '', $filters = []) {
        // Kolom pengganti (replaced_by_id) baru ada setelah
        // migration_inventory_replacement.sql — di-guard supaya halaman tetap
        // hidup di environment yang belum dimigrasi.
        $has_repl = function_exists('db_has_column') && db_has_column($this->table, 'replaced_by_id');

        $select = 'i.*, cc_kat.description as kategori_desc, cc_sat.description as satuan_desc';
        if ($has_repl) {
            $select .= ', repl.kode_barang as replaced_by_kode, repl.nama_barang as replaced_by_nama';
        }
        $this->db->select($select);
        $this->db->from($this->table . ' i');
        $this->db->join('common_code cc_kat', "i.kategori = cc_kat.code AND cc_kat.group_code = 'INVENTORY_CATEGORY'", 'left');
        $this->db->join('common_code cc_sat', "i.satuan = cc_sat.code AND cc_sat.group_code = 'SATUAN'", 'left');
        if ($has_repl) {
            $this->db->join($this->table . ' repl', 'repl.id = i.replaced_by_id', 'left');
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('i.kode_barang', $search);
            $this->db->or_like('i.nama_barang', $search);
            $this->db->group_end();
        }
        $this->_apply_list_filters($filters, 'i');

        $this->db->order_by('i.id', 'DESC');
        $this->db->limit($limit, $offset);

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function count_all_search($search = '', $filters = []) {
        $this->db->select('COUNT(*) as total', false);
        $this->db->from($this->table . ' inv');

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('inv.kode_barang', $search);
            $this->db->or_like('inv.nama_barang', $search);
            $this->db->group_end();
        }
        $this->_apply_list_filters($filters, 'inv');

        $result = $this->db->get()->row();
        return $result->total ?? 0;
    }

    /**
     * Kode master barang v3 (user 2026-10-06): format MAIN-USG-SUB-NUMBER,
     * contoh STK-GNR-STA-000001 / ITM-PRD-COM-000001.
     * MAIN = item_type ('Stock' -> STK, 'Non-Stock' -> ITM).
     * USG  = jenis pemakaian ('General' -> GNR, 'Produksi' -> PRD).
     * SUB  = kode dari get_sub_kategori_list() (STA/CVL/MEC/ELC/APL/FUR/COM/ENT).
     * Nomor urut 6 digit, per kombinasi MAIN+USG+SUB (bukan global).
     *
     * v2 (2026-09-02) dulu MAIN-SUB-NUMBER tanpa segmen USG. Prefix pencarian
     * nomor terakhir memakai keempat segmen, jadi kode v2 (STK-STA-000001)
     * TIDAK ikut terjaring saat menghitung nomor baru -- deret v3 mulai dari
     * 000001 lagi dan tidak bisa bentrok dgn kode v2 mana pun (panjang &
     * susunan segmennya beda).
     *
     * HANYA dipakai utk item BARU -- kode_barang item lama (v2 maupun format
     * v1 {KATEGORI}{4digit} spt OFC0001) tidak diubah/dimigrasikan. Mengganti
     * Jenis Pemakaian lewat Edit pun TIDAK menulis ulang kode, sejalan dgn
     * aturan yang sudah ada utk Jenis Item (kode dibuat sekali seumur item;
     * mengubahnya belakangan memutus rujukan dokumen yang sudah terbit).
     */
    public function generate_kode($item_type, $sub_code, $usage_category = self::USAGE_DEFAULT) {
        $main = ($item_type === 'Non-Stock') ? 'ITM' : 'STK';
        $usg  = self::USAGE_CODES[self::clean_usage_category($usage_category)];
        $sub  = strtoupper($sub_code);
        $prefix = $main . '-' . $usg . '-' . $sub . '-';
        $prefix_length = strlen($prefix);

        $this->db->select('kode_barang');
        $this->db->like('kode_barang', $prefix, 'after');
        $this->db->order_by('kode_barang', 'DESC');
        $this->db->limit(1);
        $last = $this->db->get($this->table)->row();

        if ($last) {
            $last_number_str = substr($last->kode_barang, $prefix_length);
            if (is_numeric($last_number_str)) {
                $last_number = (int)$last_number_str;
                $new_number = str_pad($last_number + 1, 6, '0', STR_PAD_LEFT);
            } else {
                log_message('warning', 'Inventory_model::generate_kode() - Format nomor terakhir tidak valid: ' . $last->kode_barang);
                $new_number = '000001';
            }
        } else {
            $new_number = '000001';
        }

        return $prefix . $new_number;
    }

    // ✅ BARU: Cek apakah inventory mencapai/melampaui minimum stock
    public function check_stock_status($inventory_id) {
        $inventory = $this->db->get_where($this->table, ['id' => $inventory_id])->row();
        
        if (!$inventory) {
            return [
                'status' => 'error',
                'message' => 'Inventory tidak ditemukan'
            ];
        }

        // Ambil current stock dari stock_ledger
        $this->db->select('balance');
        $this->db->from('stock_ledger');
        $this->db->where('item_id', $inventory_id);
        $this->db->where('item_type', 'inventory');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $ledger = $this->db->get()->row();
        
        $current_stock = $ledger ? (float)$ledger->balance : 0;
        $minimum_stock = isset($inventory->minimum_stock) ? (float)$inventory->minimum_stock : 0;
        
        if ($current_stock <= $minimum_stock && $minimum_stock > 0) {
            return [
                'status' => 'warning',
                'alert' => true,
                'message' => "⚠️ ALERT: Stock inventory '{$inventory->nama_barang}' mencapai atau di bawah minimum ({$current_stock} ≤ {$minimum_stock})",
                'kode_barang' => $inventory->kode_barang,
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