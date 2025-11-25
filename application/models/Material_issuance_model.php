<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_issuance_model extends CI_Model {

    private $table_header = 'mr_header';
    private $table_details = 'mr_details';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        // Load Stock_ledger_model di construct agar selalu tersedia
        $this->load->model('Stock_ledger_model');
    }

    /**
     * Ambil semua MR yang relevan untuk gudang (Submitted, Issued, Received)
     * (Tidak berubah)
     */
    public function get_all_mrs($filters = []) {
        $this->db->reset_query();
        $this->db->select('mr.id, mr.mr_number, mr.request_date, mr.status, u.fullname as requester_name');
        $this->db->from('mr_header mr');
        $this->db->join('users u', 'mr.requester_id = u.id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('mr.status', $filters['status']);
        } else {
            $this->db->where_in('mr.status', ['Submitted', 'Issued', 'Received']);
        }
        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->like('mr.mr_number', $term);
        }
        $this->db->order_by('mr.request_date', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Ambil detail item MR untuk halaman pengeluaran
     * (Revisi: Tambah sisa stok on hand aktual)
     */
    public function get_mr_details_for_issuance($mr_id) {
        $this->db->reset_query();
        $select_clause = "
            d.id as detail_id, d.requested_qty, d.issued_qty,
            d.component_item_id as item_id, d.component_item_type as item_type,
            wo.wo_number,
            CASE
                WHEN d.component_item_type = 'material' THEN m.kode_material
                WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang
            END as item_code,
            CASE
                WHEN d.component_item_type = 'material' THEN m.nama_material
                WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang
            END as item_name,
            CASE
                WHEN d.component_item_type = 'material' THEN m.satuan
                WHEN d.component_item_type = 'semifinished' THEN sf.satuan
                ELSE 'PCS'
            END as item_unit,
            
            /* (BARU) Ambil total stok tersedia dari stock_on_hand */
            (SELECT SUM(soh.available_qty) 
             FROM stock_on_hand soh 
             WHERE soh.item_id = d.component_item_id 
             AND soh.item_type = d.component_item_type) as total_available_stock
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from('mr_details d');
        $this->db->join('wo_header wo', 'd.wo_header_id = wo.id', 'left');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.mr_header_id', $mr_id);
        $this->db->order_by('wo.wo_number', 'ASC');
        $this->db->order_by('item_name', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    /**
     * Generate Nomor Pengeluaran
     * (Revisi: Ambil dari kolom issuance_number)
     */
    public function generate_issuance_number() {
        $this->db->reset_query();
        $prefix = 'MI/' . date('Ym') . '/'; // MI = Material Issuance (10 karakter)
        
        // --- PERBAIKAN DI SINI ---
        // Angka urut '0001' dimulai dari karakter ke-11
        $this->db->select_max('SUBSTRING(issuance_number, 11, 4)', 'max_num'); 
        // --- AKHIR PERBAIKAN ---

        $this->db->like('issuance_number', $prefix, 'after');
        $last = $this->db->get($this->table_header)->row();
        $new_num = $last && $last->max_num ? (int)$last->max_num + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Proses inti: Konfirmasi pengeluaran (REVISI BESAR: Terapkan FIFO)
     */
    public function process_issuance($mr_id, $items, $user_id) {
        $this->db->trans_start();
        
        $mr_header = $this->db->get_where('mr_header', ['id' => $mr_id])->row();
        if (!$mr_header) {
             $this->db->trans_rollback();
             return ['success' => false, 'message' => 'MR Header tidak ditemukan.'];
        }
        $issuance_number = $this->generate_issuance_number(); // Buat nomor MI

        foreach ($items as $detail_id => $data) {
            $issued_qty_total = (float) $data['qty']; // Total qty yg ingin dikeluarkan
            if ($issued_qty_total <= 0) continue; // Lewati jika qty 0

            $detail = $this->db->get_where('mr_details', ['id' => $detail_id])->row();
            if (!$detail) continue; // Lewati jika detail MR tidak ada

            $item_id = $detail->component_item_id;
            $item_type = $detail->component_item_type;
            
            // --- Logika FIFO Dimulai ---
            // 1. Ambil semua batch stok yang tersedia untuk item ini, urutkan dari yg Paling Lama (FIFO)
            $this->db->reset_query();
            $available_batches = $this->db
                ->select('id, available_qty, unit_value, grn_detail_id, grn_header_id') // Ambil info batch
                ->from('stock_on_hand')
                ->where('item_id', $item_id)
                ->where('item_type', $item_type)
                ->where('available_qty >', 0)
                ->order_by('id', 'ASC') // Asumsi ID lebih kecil = lebih lama (FIFO)
                ->get()->result();

            if (empty($available_batches)) {
                 $this->db->trans_rollback();
                 return ['success' => false, 'message' => 'Stok tidak ditemukan di stock_on_hand untuk item ID ' . $item_id];
            }

            $qty_needed = $issued_qty_total; // Sisa qty yg masih dibutuhkan

            foreach ($available_batches as $batch) {
                if ($qty_needed <= 0) break; // Kebutuhan sudah terpenuhi

                $batch_soh_id = $batch->id;
                $batch_available = (float)$batch->available_qty;
                $batch_cost = (float)$batch->unit_value; // Cost dari batch ini

                $qty_to_take_from_this_batch = 0;

                if ($batch_available >= $qty_needed) {
                    // Batch ini cukup, ambil sisanya
                    $qty_to_take_from_this_batch = $qty_needed;
                    $new_batch_qty = $batch_available - $qty_needed;
                    $qty_needed = 0; // Kebutuhan terpenuhi
                } else {
                    // Batch ini tidak cukup, habiskan batch ini
                    $qty_to_take_from_this_batch = $batch_available;
                    $new_batch_qty = 0; // Stok di batch ini habis
                    $qty_needed = $qty_needed - $batch_available; // Kurangi kebutuhan
                }

                // 2. Update stock_on_hand (kurangi stok batch)
                $this->db->where('id', $batch_soh_id)->update('stock_on_hand', ['available_qty' => $new_batch_qty]);

                // 3. Insert ke stock_ledger
                // Ambil saldo terakhir DARI LEDGER
                $last_ledger_balance = $this->Stock_ledger_model->get_last_balance($item_id, $item_type);
                $new_ledger_balance = $last_ledger_balance - $qty_to_take_from_this_batch; // Hitung saldo baru

                $ledger_data = [
                    'item_id'           => $item_id,
                    'item_type'         => $item_type,
                    'transaction_date'  => date('Y-m-d H:i:s'), // Tanggal saat ini
                    'transaction_type'  => 'production_out', // Sesuai definisi Anda
                    'transaction_id'    => $mr_id, // ID MR Header
                    'source_doc_type'   => 'MR',
                    'source_doc_id'     => $detail->wo_header_id, // Simpan WO ID di source_doc_id
                    'qty_in'            => 0,
                    'qty_out'           => $qty_to_take_from_this_batch,
                    'unit_value'        => $batch_cost, // <<<--- AMBIL COST DARI BATCH
                    'balance'           => $new_ledger_balance, // <<<--- SALDO BARU
                    'stock_on_hand_id'  => $batch_soh_id // <<<--- KUNCI TRACEABILITY
                ];
                
                if (!$this->Stock_ledger_model->insert($ledger_data)) {
                    $this->db->trans_rollback();
                    return ['success' => false, 'message' => 'Gagal insert stock ledger.'];
                }
                // --- Tambahkan juga entri WIP (masuk ke WIP) agar ledger WIP langsung tercatat ---
                // Beberapa sistem menyimpan WIP sebagai transaksi terpisah dengan tipe 'wip_in'
                // Kita masukkan entri wip_in untuk menunjukkan material masuk ke WIP
                $wip_ledger = [
                    'item_id'           => $item_id,
                    'item_type'         => $item_type,
                    'transaction_date'  => date('Y-m-d H:i:s'),
                    'transaction_type'  => 'wip_in',
                    'transaction_id'    => $mr_id,
                    'source_doc_type'   => 'MR',
                    'source_doc_id'     => $detail->wo_header_id,
                    'qty_in'            => $qty_to_take_from_this_batch,
                    'qty_out'           => 0,
                    'unit_value'        => $batch_cost,
                    // balance akan dihitung oleh Stock_ledger_model->insert jika tidak diberikan
                    'stock_on_hand_id'  => $batch_soh_id
                ];

                if (!$this->Stock_ledger_model->insert($wip_ledger)) {
                    // Jika gagal menulis entri WIP, rollback juga
                    $this->db->trans_rollback();
                    return ['success' => false, 'message' => 'Gagal insert stock ledger WIP.'];
                }
            } // End foreach $available_batches

            // 4. Cek apakah semua kebutuhan terpenuhi
            if ($qty_needed > 0) {
                 // Ini berarti stok di semua batch dijumlahkan tidak cukup
                 $this->db->trans_rollback();
                 return ['success' => false, 'message' => 'Stok tidak cukup untuk item ID ' . $item_id . '. Kebutuhan ' . $issued_qty_total . ', tapi total tersedia hanya ' . ($issued_qty_total - $qty_needed)];
            }
            // --- Akhir Logika FIFO ---

            // 5. Update issued_qty di mr_details
            $this->db->reset_query(); // Reset
            $this->db->where('id', $detail_id)->update('mr_details', ['issued_qty' => $issued_qty_total]);

        } // End foreach $items

        // 6. Update status mr_header
        $header_data = [
            'status'          => 'Issued', // Atau 'Partially Issued' jika perlu logika lebih kompleks
            'issuer_id'       => $user_id,
            'issued_at'       => date('Y-m-d H:i:s'),
            'issuance_number' => $issuance_number
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $header_data);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database (trans_status FALSE).'];
        }
        return ['success' => true];
    }
    
    /**
     * Proses Un-Issue (Batal Pengeluaran)
     * (REVISI BESAR: Terapkan Reversal FIFO)
     */
    public function reverse_issuance($mr_id, $user_id) {
        $this->db->trans_start();

        // 1. Ambil semua entri ledger yang terkait MR ini
        $this->db->reset_query();
        $ledger_entries = $this->db->get_where('stock_ledger', [
            'transaction_type' => 'production_out', // Sesuai tipe saat issue
            'transaction_id' => $mr_id
        ])->result();

        if (empty($ledger_entries)) {
            $this->db->trans_rollback();
            return false; // Tidak ada yang direverse
        }

        foreach ($ledger_entries as $entry) {
            $qty_to_return = (float)$entry->qty_out;
            $stock_on_hand_id = $entry->stock_on_hand_id; // ID batch yang akan dikembalikan

            // 2. Kembalikan stok ke batch stock_on_hand
            if ($stock_on_hand_id) {
                $this->db->where('id', $stock_on_hand_id);
                $this->db->set('available_qty', "available_qty + $qty_to_return", FALSE);
                $this->db->update('stock_on_hand');
            }

            // 3. Buat entri ledger reversal (IN_REVERSAL)
            $last_ledger_balance = $this->Stock_ledger_model->get_last_balance($entry->item_id, $entry->item_type);
            $new_ledger_balance = $last_ledger_balance + $qty_to_return;

            $ledger_reversal_data = [
                'item_id'           => $entry->item_id,
                'item_type'         => $entry->item_type,
                'transaction_date'  => date('Y-m-d H:i:s'),
                'transaction_type'  => 'IN_REVERSAL_ISSUE', // Tipe baru untuk reversal
                'transaction_id'    => $entry->ledger_id, // Referensi ke ID ledger asli
                'source_doc_type'   => 'MR_REVERSE',
                'source_doc_id'     => $mr_id,
                'qty_in'            => $qty_to_return,
                'qty_out'           => 0,
                'unit_value'        => $entry->unit_value, // Pakai cost yang sama
                'balance'           => $new_ledger_balance,
                'stock_on_hand_id'  => $stock_on_hand_id
            ];
            $this->Stock_ledger_model->insert($ledger_reversal_data);
        }

        // 4. Update mr_details (kembalikan issued_qty ke 0)
        $this->db->where('mr_header_id', $mr_id)->update('mr_details', ['issued_qty' => 0]);
        
        // 5. Update mr_header (kembalikan status)
        $header_data = [
            'status'          => 'Submitted', // Kembali ke Submitted
            'issuer_id'       => NULL,
            'issued_at'       => NULL,
            'issuance_number' => NULL
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $header_data);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}