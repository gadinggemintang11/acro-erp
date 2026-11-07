<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Initial_stock_model extends CI_Model {

    private $draft_table = 'initial_stock_draft';
    private $ledger_table = 'stock_ledger';

    public function __construct() {
        parent::__construct();
    }

    /**
     * Get inventory items dengan draft & stock info
     */
    /**
     * Daftar barang aktif + status stock awalnya, DIPISAH per gudang
     * (General vs Produksi) — user 2026-09-10: "kan ada stock general dan
     * kebutuhan produksi, initial stock ini otomatis ke general, bisa ga
     * buatin untuk yang kebutuhan produksi".
     *
     * DUA perbaikan sekaligus di query ini:
     *
     * 1. PECAH PER KATEGORI. Sebelumnya cuma ada satu angka `current_stock`
     *    tanpa peduli kategori, jadi barang yang stock awal General-nya sudah
     *    diisi kelihatan "sudah selesai" padahal bucket Produksi-nya masih
     *    kosong. Sekarang stock_general & stock_produksi dihitung terpisah,
     *    begitu juga draft-nya (satu barang boleh punya 1 draft General DAN 1
     *    draft Produksi — save_draft() memang mengunci duplikat per kategori).
     *
     * 2. BUG LAMA: subquery `sl` dulu berbentuk
     *        (SELECT item_id, balance FROM stock_ledger WHERE ... LIMIT 1)
     *    — LIMIT 1 di derived table TIDAK berkorelasi ke `i.id`, jadi seluruh
     *    tabel cuma menghasilkan SATU baris. Akibatnya dari 154 barang yang
     *    punya stock awal, cuma 1 yang current_stock-nya keisi; 153 sisanya
     *    tampil seolah belum pernah diisi. Diganti agregasi GROUP BY item_id.
     */
    public function get_all_inventories() {
        $sql = "
            SELECT
                i.id,
                i.kode_barang,
                i.nama_barang,
                i.satuan,
                cc.description as satuan_desc,
                i.kategori,
                IFNULL(sl.stock_general, 0)  as stock_general,
                IFNULL(sl.stock_produksi, 0) as stock_produksi,
                IFNULL(sl.stock_general, 0) + IFNULL(sl.stock_produksi, 0) as current_stock,
                dg.id       as draft_general_id,
                dg.quantity as draft_general_qty,
                dp.id       as draft_produksi_id,
                dp.quantity as draft_produksi_qty,
                IFNULL(COALESCE(dg.status, dp.status), 'New') as draft_status,
                COALESCE(dg.id, dp.id)             as draft_id,
                COALESCE(dg.quantity, dp.quantity) as draft_quantity
            FROM inventories i
            LEFT JOIN common_code cc ON i.satuan = cc.code AND cc.group_code = 'SATUAN'
            LEFT JOIN (
                SELECT
                    item_id,
                    SUM(CASE WHEN COALESCE(category, 'General') = 'General'
                             THEN qty_in - qty_out ELSE 0 END) as stock_general,
                    SUM(CASE WHEN COALESCE(category, 'General') = 'Produksi'
                             THEN qty_in - qty_out ELSE 0 END) as stock_produksi
                FROM stock_ledger
                WHERE item_type = 'inventory'
                  AND transaction_type = 'INITIAL_STOCK'
                GROUP BY item_id
            ) sl ON sl.item_id = i.id
            LEFT JOIN initial_stock_draft dg
                   ON dg.item_id = i.id AND dg.status = 'Draft' AND dg.category = 'General'
            LEFT JOIN initial_stock_draft dp
                   ON dp.item_id = i.id AND dp.status = 'Draft' AND dp.category = 'Produksi'
            WHERE i.status = 'Active'
            ORDER BY i.kode_barang ASC
        ";
        return $this->db->query($sql)->result();
    }

    /**
     * Get draft initial stock (not yet confirmed)
     */
    public function get_draft_list($limit = 100) {
        $this->db->select('
            d.id,
            d.item_id,
            d.category,
            d.quantity,
            d.notes,
            d.status,
            d.transaction_date,
            d.created_at,
            i.kode_barang,
            i.nama_barang,
            i.satuan,
            cc.description as satuan_desc
        ');
        $this->db->from($this->draft_table . ' d');
        $this->db->join('inventories i', 'd.item_id = i.id', 'left');
        $this->db->join('common_code cc', 'i.satuan = cc.code AND cc.group_code = "SATUAN"', 'left');
        $this->db->where('d.status', 'Draft');
        $this->db->order_by('d.created_at', 'DESC');
        $this->db->limit($limit);

        return $this->db->get()->result();
    }

    /**
     * Get confirmed history (already in stock ledger)
     */
    public function get_confirmed_list($limit = null) {
        $this->db->select('
            d.id,
            d.item_id,
            d.category,
            d.quantity,
            d.notes,
            d.status,
            d.transaction_date,
            d.confirmed_at,
            i.kode_barang,
            i.nama_barang,
            i.satuan,
            cc.description as satuan_desc
        ');
        $this->db->from($this->draft_table . ' d');
        $this->db->join('inventories i', 'd.item_id = i.id', 'left');
        $this->db->join('common_code cc', 'i.satuan = cc.code AND cc.group_code = "SATUAN"', 'left');
        $this->db->where('d.status', 'Confirmed');
        $this->db->order_by('d.confirmed_at', 'DESC');
        if ($limit !== null) {
            $this->db->limit($limit);
        }

        return $this->db->get()->result();
    }

    /**
     * Get daftar barang aktif untuk template import (kode, nama, satuan)
     */
    public function get_active_inventories_for_template() {
        $sql = "
            SELECT
                i.id,
                i.kode_barang,
                i.nama_barang,
                cc.description as satuan_desc
            FROM inventories i
            LEFT JOIN common_code cc ON i.satuan = cc.code AND cc.group_code = 'SATUAN'
            WHERE i.status = 'Active'
            ORDER BY i.kode_barang ASC
        ";
        return $this->db->query($sql)->result();
    }

    /**
     * Cari barang berdasarkan kode_barang (untuk matching saat import)
     */
    public function get_inventory_by_kode($kode_barang) {
        return $this->db->get_where('inventories', [
            'kode_barang' => $kode_barang,
            'status' => 'Active'
        ])->row();
    }

    /**
     * Save ke DRAFT (tidak langsung stock ledger)
     */
    public function save_draft($item_id, $quantity, $transaction_date = '', $notes = '', $category = 'General') {
        if (!in_array($category, ['General', 'Produksi'], true)) { $category = 'General'; }
        $this->db->trans_start();

        // Check if item exists
        $item = $this->db->get_where('inventories', ['id' => $item_id])->row();
        if (!$item) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Item tidak ditemukan'];
        }

        // Check if already confirmed (per kategori — General & Produksi sekarang
        // 2 bucket independen, jadi item yg sama boleh punya initial stock
        // terpisah utk masing-masing kategori).
        $confirmed = $this->db->where('item_id', $item_id)
                             ->where('item_type', 'inventory')
                             ->where('category', $category)
                             ->where('status', 'Confirmed')
                             ->get($this->draft_table)
                             ->row();

        if ($confirmed) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Item ini (kategori ' . $category . ') sudah dikonfirmasi, gunakan Unconfirm untuk edit'];
        }

        // Delete existing draft (replace) — hanya draft di kategori yang sama.
        $this->db->delete($this->draft_table, [
            'item_id' => $item_id,
            'category' => $category,
            'status' => 'Draft'
        ]);

        // Insert draft baru
        $effective_date = !empty($transaction_date) ? $transaction_date : date('Y-m-d');

        $draft_data = [
            'item_id' => $item_id,
            'item_type' => 'inventory',
            'category' => $category,
            'quantity' => (float)$quantity,
            'notes' => $notes,
            'status' => 'Draft',
            'transaction_date' => $effective_date
        ];

        if (!$this->db->insert($this->draft_table, $draft_data)) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Gagal menyimpan draft'];
        }

        $draft_id = $this->db->insert_id();
        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return ['status' => false, 'message' => 'Terjadi kesalahan transaksi'];
        }

        return ['status' => true, 'message' => 'Draft tersimpan, silahkan confirm', 'draft_id' => $draft_id];
    }

    /**
     * Confirm draft → post ke stock ledger
     */
    public function confirm_draft($draft_id) {
        $this->db->trans_start();

        $draft = $this->db->get_where($this->draft_table, ['id' => $draft_id])->row();
        if (!$draft) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Draft tidak ditemukan'];
        }

        if ($draft->status != 'Draft') {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Hanya draft yang dapat dikonfirmasi'];
        }

        // Insert ke stock_ledger dengan transaction_date dari draft
        $tx_date = $draft->transaction_date ? $draft->transaction_date . ' 00:00:00' : date('Y-m-d H:i:s');
        $category = $draft->category ?? 'General';
        $qty      = (float) $draft->quantity;

        // SALDO BERJALAN — diperbaiki 2026-09-10.
        //
        // Sebelumnya baris ini menulis `'balance' => quantity` begitu saja,
        // seolah stock awal SELALU jadi baris pertama item itu. Padahal
        // stock_ledger di app ini pakai saldo berjalan: tiap penulis lain
        // (Consumable_out, Stock_transfer, SGR, dst) membaca saldo terakhir
        // lalu menambah/mengurangi. Kalau item-nya SUDAH punya mutasi di
        // kategori yang sama, menulis balance = quantity berarti MERESET
        // saldo — qty yang sudah ada hilang diam-diam.
        //
        // Ini bukan teori: di data sekarang ada 10 baris INITIAL_STOCK yang
        // dikonfirmasi setelah barangnya terlanjur dipakai, dan saldonya
        // memang ke-reset (mis. item 13, saldo 9 -> ditulis ulang jadi 12).
        //
        // Risiko itu makin nyata sejak stock awal Produksi dibuka: bucket
        // Produksi umumnya SUDAH terisi lebih dulu lewat Stock Transfer dari
        // General. Sekarang polanya disamakan dengan penulis lain:
        // saldo terakhir DI KATEGORI ITU + qty. Kalau memang belum ada mutasi
        // (kasus normal stock awal), saldo terakhir = 0, hasilnya sama persis
        // dengan perilaku lama.
        //
        // Baris lama TIDAK diubah — perbaikan ini berlaku maju saja.
        $last_balance = $this->_last_balance($draft->item_id, 'inventory', $category);

        $ledger_data = [
            'item_id' => $draft->item_id,
            'item_type' => 'inventory',
            'category' => $category,
            'transaction_type' => 'INITIAL_STOCK',
            'transaction_date' => $tx_date,
            'transaction_id' => 0,
            'qty_in' => $qty,
            'qty_out' => 0,
            'balance' => $last_balance + $qty,
            'source_doc_type' => 'INITIAL_STOCK',
            'source_doc_id' => $draft_id
        ];

        if (!$this->db->insert($this->ledger_table, $ledger_data)) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Gagal posting ke stock ledger'];
        }

        // Update draft status
        $this->db->where('id', $draft_id);
        if (!$this->db->update($this->draft_table, [
            'status' => 'Confirmed',
            'confirmed_at' => date('Y-m-d H:i:s')
        ])) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Gagal update status'];
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return ['status' => false, 'message' => 'Terjadi kesalahan transaksi'];
        }

        return ['status' => true, 'message' => 'Stock awal dikonfirmasi ✓'];
    }

    /**
     * Saldo berjalan terakhir suatu item DI SATU KATEGORI.
     *
     * Sengaja query sendiri (tidak load Stock_ledger_model) supaya model ini
     * tetap berdiri sendiri; logikanya identik dengan
     * Stock_ledger_model::get_last_balance($item_id, $item_type, $category)
     * yang dipakai Consumable_out & Stock_transfer — urut id DESC, karena
     * rantai saldo di app ini mengikuti urutan penulisan (id), bukan tanggal
     * transaksi (stock awal boleh di-backdate).
     */
    private function _last_balance($item_id, $item_type, $category) {
        $row = $this->db->select('balance')
            ->where('item_id', $item_id)
            ->where('item_type', $item_type)
            ->where('category', $category)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get($this->ledger_table)
            ->row();
        return $row ? (float) $row->balance : 0.0;
    }

    /**
     * Unconfirm → hapus dari stock ledger, kembali ke draft
     */
    public function unconfirm_draft($draft_id) {
        $this->db->trans_start();

        $draft = $this->db->get_where($this->draft_table, ['id' => $draft_id])->row();
        if (!$draft) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Draft tidak ditemukan'];
        }

        if ($draft->status != 'Confirmed') {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Hanya confirmed yang dapat di-unconfirm'];
        }

        // Ambil dulu baris ledger-nya SEBELUM dihapus — id & qty-nya dipakai
        // untuk merapikan saldo baris-baris sesudahnya.
        $ledger_row = $this->db->get_where($this->ledger_table, [
            'item_id' => $draft->item_id,
            'item_type' => 'inventory',
            'transaction_type' => 'INITIAL_STOCK',
            'source_doc_id' => $draft_id
        ])->row();

        // Delete dari stock_ledger
        $this->db->delete($this->ledger_table, [
            'item_id' => $draft->item_id,
            'item_type' => 'inventory',
            'transaction_type' => 'INITIAL_STOCK',
            'source_doc_id' => $draft_id
        ]);

        // Saldo berjalan baris SESUDAHNYA ikut turun (perbaikan 2026-09-10).
        // Dulu barisnya cuma dihapus; kalau item itu punya mutasi setelah
        // stock awal, saldo mereka jadi kelebihan sebesar qty yang dihapus —
        // dan angka stock yang dibaca app = saldo baris TERAKHIR, jadi
        // stock-nya ikut salah. Cukup dikurangi qty-nya, tidak perlu hitung
        // ulang seluruh rantai (baris lain tidak disentuh).
        if ($ledger_row) {
            $qty_removed = (float) $ledger_row->qty_in - (float) $ledger_row->qty_out;
            if (abs($qty_removed) > 0.0000001) {
                $this->db->set('balance', 'balance - ' . (float) $qty_removed, FALSE)
                         ->where('item_id', $draft->item_id)
                         ->where('item_type', 'inventory')
                         ->where('category', $ledger_row->category)
                         ->where('id >', (int) $ledger_row->id)
                         ->update($this->ledger_table);
            }
        }

        // Revert ke Draft
        $this->db->where('id', $draft_id);
        if (!$this->db->update($this->draft_table, [
            'status' => 'Draft',
            'confirmed_at' => null
        ])) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Gagal revert draft'];
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return ['status' => false, 'message' => 'Terjadi kesalahan transaksi'];
        }

        return ['status' => true, 'message' => 'Dikembalikan ke draft, dapat diedit kembali'];
    }

    /**
     * Confirm banyak draft sekaligus
     */
    public function confirm_bulk($ids) {
        $ok = 0; $fail = 0; $errors = [];
        foreach ($ids as $id) {
            $r = $this->confirm_draft($id);
            if (!empty($r['status'])) {
                $ok++;
            } else {
                $fail++;
                $errors[] = "#$id: " . ($r['message'] ?? 'gagal');
            }
        }
        return ['status' => $ok > 0, 'success' => $ok, 'failed' => $fail, 'errors' => $errors];
    }

    /**
     * Hapus banyak draft sekaligus
     */
    public function delete_bulk($ids) {
        $ok = 0; $fail = 0; $errors = [];
        foreach ($ids as $id) {
            $r = $this->delete_draft($id);
            if (!empty($r['status'])) {
                $ok++;
            } else {
                $fail++;
                $errors[] = "#$id: " . ($r['message'] ?? 'gagal');
            }
        }
        return ['status' => $ok > 0, 'success' => $ok, 'failed' => $fail, 'errors' => $errors];
    }

    /**
     * Delete draft
     */
    public function delete_draft($draft_id) {
        $draft = $this->db->get_where($this->draft_table, ['id' => $draft_id])->row();
        if (!$draft) {
            return ['status' => false, 'message' => 'Draft tidak ditemukan'];
        }

        if ($draft->status != 'Draft') {
            return ['status' => false, 'message' => 'Hanya draft dapat dihapus'];
        }

        if ($this->db->delete($this->draft_table, ['id' => $draft_id])) {
            return ['status' => true, 'message' => 'Draft dihapus'];
        }

        return ['status' => false, 'message' => 'Gagal hapus draft'];
    }
}
