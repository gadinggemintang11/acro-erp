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
            END as item_unit
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
        
        $results = $query ? $query->result() : [];
        
        // Hitung stok untuk setiap item dari stock_ledger secara terpisah
        foreach ($results as $row) {
            $row->total_available_stock = $this->get_stock_from_ledger($row->item_id, $row->item_type);
        }
        
        return $results;
    }
    
    /**
     * Hitung stok tersedia dari stock_ledger
     */
    private function get_stock_from_ledger($item_id, $item_type) {
        $this->db->select('COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as balance');
        $this->db->from('stock_ledger');
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        $query = $this->db->get();
        
        if ($query && $query->num_rows() > 0) {
            return (float) $query->row()->balance;
        }
        
        return 0.0;
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
     * Proses inti: Konfirmasi pengeluaran (HYBRID: Stock_ledger + FIFO via stock_on_hand untuk traceability)
     */
    public function process_issuance($mr_id, $items, $user_id) {
        $this->db->trans_start();
        
        $mr_header = $this->db->get_where('mr_header', ['id' => $mr_id])->row();
        if (!$mr_header) {
             $this->db->trans_rollback();
             return ['success' => false, 'message' => 'MR Header tidak ditemukan.'];
        }
        $issuance_number = $this->generate_issuance_number();

        foreach ($items as $detail_id => $data) {
            $issued_qty_total = (float) $data['qty'];
            if ($issued_qty_total <= 0) continue;

            $detail = $this->db->get_where('mr_details', ['id' => $detail_id])->row();
            if (!$detail) continue;

            $item_id = $detail->component_item_id;
            $item_type = $detail->component_item_type;
            
            // Cek stok tersedia
            $current_stock = $this->get_stock_from_ledger($item_id, $item_type);
            
            if ($current_stock < $issued_qty_total) {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => "Stok tidak cukup untuk {$item_type} ID {$item_id}. Tersedia: {$current_stock}, Diminta: {$issued_qty_total}"];
            }

            // FIFO: Ambil batch dari stock_on_hand untuk traceability
            $this->db->reset_query();
            $available_batches = $this->db
                ->select('id, available_qty, unit_value')
                ->from('stock_on_hand')
                ->where('item_id', $item_id)
                ->where('item_type', $item_type)
                ->where('available_qty >', 0)
                ->order_by('id', 'ASC') // FIFO: batch lama dulu
                ->get()->result();

            $qty_needed = $issued_qty_total;
            $has_batches = !empty($available_batches);

            if ($has_batches) {
                // Ada stock_on_hand, gunakan FIFO
                foreach ($available_batches as $batch) {
                    if ($qty_needed <= 0) break;

                    $batch_soh_id = $batch->id;
                    $batch_available = (float)$batch->available_qty;
                    $batch_cost = (float)$batch->unit_value;

                    $qty_to_take = min($batch_available, $qty_needed);
                    $new_batch_qty = $batch_available - $qty_to_take;

                    // Update stock_on_hand
                    $this->db->where('id', $batch_soh_id)->update('stock_on_hand', ['available_qty' => $new_batch_qty]);

                    // Insert ke stock_ledger dengan traceability
                    $last_balance = $this->Stock_ledger_model->get_last_balance($item_id, $item_type);
                    $new_balance = $last_balance - $qty_to_take;

                    $ledger_data = [
                        'item_id'           => $item_id,
                        'item_type'         => $item_type,
                        'transaction_date'  => date('Y-m-d H:i:s'),
                        'transaction_type'  => 'OUT_ISSUE',
                        'transaction_id'    => $mr_id,
                        'source_doc_type'   => 'MR',
                        'source_doc_id'     => $detail->wo_header_id,
                        'qty_in'            => 0,
                        'qty_out'           => $qty_to_take,
                        'unit_value'        => $batch_cost,
                        'balance'           => $new_balance,
                        'stock_on_hand_id'  => $batch_soh_id // Traceability ke batch
                    ];
                    
                    if (!$this->Stock_ledger_model->insert($ledger_data)) {
                        $this->db->trans_rollback();
                        return ['success' => false, 'message' => 'Gagal insert stock ledger.'];
                    }

                    $qty_needed -= $qty_to_take;
                }

                if ($qty_needed > 0) {
                    $this->db->trans_rollback();
                    return ['success' => false, 'message' => "Stok batch tidak cukup untuk {$item_type} ID {$item_id}"];
                }
            } else {
                // Tidak ada stock_on_hand, langsung insert ke ledger tanpa batch
                $last_balance = $this->Stock_ledger_model->get_last_balance($item_id, $item_type);
                $new_balance = $last_balance - $issued_qty_total;

                $ledger_data = [
                    'item_id'           => $item_id,
                    'item_type'         => $item_type,
                    'transaction_date'  => date('Y-m-d H:i:s'),
                    'transaction_type'  => 'OUT_ISSUE',
                    'transaction_id'    => $mr_id,
                    'source_doc_type'   => 'MR',
                    'source_doc_id'     => $detail->wo_header_id,
                    'qty_in'            => 0,
                    'qty_out'           => $issued_qty_total,
                    'unit_value'        => 0,
                    'balance'           => $new_balance
                ];
                
                if (!$this->Stock_ledger_model->insert($ledger_data)) {
                    $this->db->trans_rollback();
                    return ['success' => false, 'message' => 'Gagal insert stock ledger.'];
                }
            }

            // Update issued_qty di mr_details
            $this->db->where('id', $detail_id)->update('mr_details', ['issued_qty' => $issued_qty_total]);
        }

        // Update status mr_header
        $header_data = [
            'status'          => 'Issued',
            'issuer_id'       => $user_id,
            'issued_at'       => date('Y-m-d H:i:s'),
            'issuance_number' => $issuance_number
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $header_data);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database.'];
        }
        return ['success' => true];
    }
    
    /**
     * Proses Un-Issue (Batal Pengeluaran) - HYBRID dengan traceability
     */
    public function reverse_issuance($mr_id, $user_id) {
        $this->db->trans_start();

        // Ambil semua entri ledger yang terkait MR ini
        $ledger_entries = $this->db->get_where('stock_ledger', [
            'transaction_type' => 'OUT_ISSUE',
            'transaction_id' => $mr_id
        ])->result();

        if (empty($ledger_entries)) {
            $this->db->trans_rollback();
            return false;
        }

        foreach ($ledger_entries as $entry) {
            $qty_to_return = (float)$entry->qty_out;
            $stock_on_hand_id = $entry->stock_on_hand_id ?? null;
            
            // Jika ada stock_on_hand_id, kembalikan ke batch
            if ($stock_on_hand_id) {
                $this->db->where('id', $stock_on_hand_id);
                $this->db->set('available_qty', "available_qty + $qty_to_return", FALSE);
                $this->db->update('stock_on_hand');
            }
            
            // Ambil saldo terakhir
            $last_balance = $this->Stock_ledger_model->get_last_balance($entry->item_id, $entry->item_type);
            $new_balance = $last_balance + $qty_to_return;

            // Buat entri ledger reversal
            $ledger_reversal_data = [
                'item_id'           => $entry->item_id,
                'item_type'         => $entry->item_type,
                'transaction_date'  => date('Y-m-d H:i:s'),
                'transaction_type'  => 'IN_RETURN_WIP',
                'transaction_id'    => $mr_id,
                'source_doc_type'   => 'MR_REVERSE',
                'source_doc_id'     => $mr_id,
                'qty_in'            => $qty_to_return,
                'qty_out'           => 0,
                'unit_value'        => $entry->unit_value,
                'balance'           => $new_balance,
                'stock_on_hand_id'  => $stock_on_hand_id
            ];
            $this->Stock_ledger_model->insert($ledger_reversal_data);
        }

        // Update mr_details
        $this->db->where('mr_header_id', $mr_id)->update('mr_details', ['issued_qty' => 0]);
        
        // Update mr_header
        $header_data = [
            'status'          => 'Submitted',
            'issuer_id'       => NULL,
            'issued_at'       => NULL,
            'issuance_number' => NULL
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $header_data);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}