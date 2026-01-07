<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Model Stock_on_hand_model
 * * Model ini bertanggung jawab untuk semua interaksi DENGAN
 * tabel 'stock_on_hand'.
 * * Catatan:
 * - find_or_create_and_increase_stock: Menambah stok, membuat baris baru jika perlu.
 * - check_and_decrease_stock: Mengurangi stok, memvalidasi stok cukup.
 * - find_batch_for_item: Mencari SOH (bisa LIFO/FIFO)
 */
class Stock_on_hand_model extends CI_Model {

    private $tbl_soh = 'stock_on_hand';
    private $tbl_mat = 'materials';
    private $tbl_sf  = 'semifinished_goods';

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Helper internal untuk mendapatkan info item (cost, dll.)
     * Berdasarkan tebakan LIFO dari GRN (bisa diganti jika Anda punya master cost)
     */
    private function _get_item_cost_info($item_id, $item_type) {
        $cost = 0.00;
        // Logika "Tebakan LIFO" dari GRN terakhir untuk cost
        // Sesuaikan jika Anda punya master harga
        $this->db->select('gd.unit_value');
        $this->db->from('grn_details gd');
        $this->db->where('gd.item_id', $item_id);
        $this->db->where('gd.item_type', $item_type);
        $this->db->order_by('gd.id', 'DESC');
        $this->db->limit(1);
        $grn_info = $this->db->get()->row();
        
        if ($grn_info && $grn_info->unit_value > 0) {
            $cost = (float)$grn_info->unit_value;
        }
        return $cost;
    }

    /**
     * Mencari satu batch SOH untuk item tertentu (bisa LIFO/FIFO/FEFO)
     * Saat ini di-set LIFO (Last In First Out) berdasarkan ID
     */
    public function find_batch_for_item($item_id, $item_type) {
        $this->db->from($this->tbl_soh);
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        // $this->db->where('available_qty >', 0); // Opsi: hanya cari yg ada stok?
        $this->db->order_by('id', 'DESC'); // LIFO
        $this->db->limit(1);
        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * MENAMBAH stok ke SOH. Membuat baris baru jika SOH belum ada.
     * Dipakai oleh: Confirm Return
     */
    public function find_or_create_and_increase_stock($item_id, $item_type, $qty) {
        if ($qty <= 0) {
            return ['success' => false, 'message' => 'Qty harus lebih dari 0.'];
        }

        // Cari SOH yang ada (tanpa GRN link, untuk barang 'Loose')
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        $this->db->where('grn_detail_id IS NULL'); // Kunci: cari SOH "Loose"
        $existing_soh = $this->db->get($this->tbl_soh)->row();

        $soh_id = null;
        $unit_value = 0.00;

        if ($existing_soh) {
            // SOH SUDAH ADA: Update Qty
            $soh_id = $existing_soh->id;
            $unit_value = $existing_soh->unit_value; // Pakai cost yang ada
            
            $this->db->set('available_qty', 'available_qty + ' . (float)$qty, FALSE);
            // (Opsional) Update initial_qty juga? Tergantung aturan bisnis
            // $this->db->set('initial_qty', 'initial_qty + ' . (float)$qty, FALSE); 
            $this->db->where('id', $soh_id);
            if (!$this->db->update($this->tbl_soh)) {
                 return ['success' => false, 'message' => 'DB Error saat update SOH.'];
            }

        } else {
            // SOH BELUM ADA: Buat baru
            $unit_value = $this->_get_item_cost_info($item_id, $item_type); // Tebak cost
            $new_soh_data = [
                'item_id'       => $item_id,
                'item_type'     => $item_type,
                'initial_qty'   => $qty,
                'available_qty' => $qty,
                'unit_value'    => $unit_value,
                'grn_detail_id' => NULL, // Karena ini retur, tidak terikat GRN
                'grn_header_id' => NULL
            ];
            if (!$this->db->insert($this->tbl_soh, $new_soh_data)) {
                 return ['success' => false, 'message' => 'DB Error saat insert SOH baru.'];
            }
            $soh_id = $this->db->insert_id();
        }

        return [
            'success'    => true,
            'soh_id'     => $soh_id,
            'unit_value' => $unit_value
        ];
    }

    /**
     * MENAMBAH stok ke SOH berdasarkan ID SOH SPESIFIK.
     * (Versi lain dari fungsi di atas, dipakai jika Anda tahu SOH ID-nya)
     */
    public function increase_stock($soh_id, $qty) {
        if ($qty <= 0 || empty($soh_id)) {
            return ['success' => false, 'message' => 'ID SOH atau Qty tidak valid.'];
        }
        
        $this->db->set('available_qty', 'available_qty + ' . (float)$qty, FALSE);
        $this->db->where('id', $soh_id);
        if ($this->db->update($this->tbl_soh)) {
             $soh_data = $this->db->select('unit_value')->where('id', $soh_id)->get($this->tbl_soh)->row();
             return [
                 'success' => true,
                 'soh_id' => $soh_id,
                 'unit_value' => $soh_data ? $soh_data->unit_value : 0
             ];
        }
        return ['success' => false, 'message' => 'Gagal update SOH.'];
    }

    /**
     * MENGECEK & MENGURANGI stok dari SOH berdasarkan ID SOH SPESIFIK.
     * Dipakai oleh: Unconfirm Return, MR Issuance, Production Consumed
     */
    public function check_and_decrease_stock($soh_id, $qty) {
        if ($qty <= 0 || empty($soh_id)) {
            return ['success' => false, 'message' => 'ID SOH atau Qty tidak valid.'];
        }

        // Cek stok
        $soh = $this->db->select('available_qty, unit_value')->where('id', $soh_id)->get($this->tbl_soh)->row();

        if (!$soh) {
            return ['success' => false, 'message' => "Stok batch ID {$soh_id} tidak ditemukan."];
        }

        if ((float)$soh->available_qty < (float)$qty) {
             return ['success' => false, 'message' => "Stok batch ID {$soh_id} tidak cukup (Sisa: {$soh->available_qty}, Dibutuhkan: {$qty})."];
        }

        // Stok cukup, kurangi
        $this->db->set('available_qty', 'available_qty - ' . (float)$qty, FALSE);
        $this->db->where('id', $soh_id);
        
        if ($this->db->update($this->tbl_soh)) {
            return [
                'success'    => true,
                'soh_id'     => $soh_id,
                'unit_value' => (float)$soh->unit_value
            ];
        }
        return ['success' => false, 'message' => 'Gagal update SOH saat mengurangi stok.'];
    }

    /**
     * MENGECEK & MENGURANGI stok dari SOH (TANPA SOH ID SPESIFIK).
     * Akan mencari batch LIFO/FIFO sendiri.
     * Dipakai oleh: Unconfirm Return
     */
     public function find_and_decrease_stock($item_id, $item_type, $qty) {
        if ($qty <= 0) return ['success' => false, 'message' => 'Qty tidak valid.'];

        // Cari batch LIFO yang stoknya mencukupi
        // (Anda bisa ganti 'id DESC' ke 'id ASC' untuk FIFO)
        $soh_batch = $this->db->select('id, available_qty, unit_value')
                        ->from($this->tbl_soh)
                        ->where('item_id', $item_id)
                        ->where('item_type', $item_type)
                        ->where('available_qty >=', (float)$qty) // Cari yg cukup
                        ->order_by('id', 'DESC') // LIFO
                        ->limit(1)
                        ->get()
                        ->row();
        
        if (!$soh_batch) {
             // Coba cari batch "Loose" (tanpa GRN) jika batch utama tidak ada/cukup
             $soh_batch = $this->db->select('id, available_qty, unit_value')
                        ->from($this->tbl_soh)
                        ->where('item_id', $item_id)
                        ->where('item_type', $item_type)
                        ->where('available_qty >=', (float)$qty)
                        ->where('grn_detail_id IS NULL') // Prioritaskan SOH "Loose"
                        ->order_by('id', 'DESC')
                        ->limit(1)
                        ->get()
                        ->row();
        }

        if (!$soh_batch) {
             return ['success' => false, 'message' => "Stok (gabungan) untuk item {$item_id} ({$item_type}) tidak mencukupi."];
        }

        // Stok ditemukan, panggil fungsi check_and_decrease_stock
        return $this->check_and_decrease_stock($soh_batch->id, $qty);
     }

} // End Class