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
     * Ambil semua MR yang relevan untuk gudang.
     * MR baru masuk ke gudang setelah lolos 3 stage approval (status = Approved).
     */
    public function get_all_mrs($filters = []) {
        $this->db->reset_query();
        $this->db->select('mr.id, mr.mr_number, mr.request_date, mr.status, u.fullname as requester_name');
        $this->db->from('mr_header mr');
        $this->db->join('users u', 'mr.requester_id = u.id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('mr.status', $filters['status']);
        } else {
            $this->db->where_in('mr.status', ['Approved', 'Partial Issued', 'Partial Received', 'Issued', 'Received']);
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
     * Hitung stok tersedia — delegasi ke Stock_ledger_model.
     * Satu sumber kebenaran: kalau daftar transaction_type warehouse berubah
     * di Stock_ledger_model::get_warehouse_tx_types(), modul ini ikut otomatis.
     */
    private function get_stock_from_ledger($item_id, $item_type) {
        return (float) $this->Stock_ledger_model->get_current_stock($item_id, $item_type);
    }

    /**
     * Generate Nomor Pengeluaran
     * (Revisi: Ambil dari kolom issuance_number)
     */
    public function generate_issuance_number() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('material_issue', 'mr_header', 'issuance_number');
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

        // Create entry in mr_issuances table (per-issuance tracking)
        $issuance_data = [
            'mr_header_id' => $mr_id,
            'issuance_number' => $issuance_number,
            'issuer_id' => $user_id,
            'issued_at' => date('Y-m-d H:i:s'),
            'status' => 'Issued'
        ];
        $this->db->insert('mr_issuances', $issuance_data);
        $issuance_id = $this->db->insert_id();

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

            // Hitung warehouse stock (exclude WIP)
            $warehouse_stock = $this->db->query("
                SELECT COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as balance
                FROM stock_ledger
                WHERE item_id = {$item_id}
                AND item_type = '{$item_type}'
                AND transaction_type NOT IN ('wip_in', 'wip_out')
            ")->row()->balance;
            
            if ($warehouse_stock < $issued_qty_total) {
                $this->db->trans_rollback();
                return ['success' => false, 'message' => "Stok di gudang tidak cukup untuk {$item_type} ID {$item_id}. Tersedia di gudang: {$warehouse_stock}, Diminta: {$issued_qty_total}"];
            }

            // FIFO: Ambil batch dari stock_on_hand untuk traceability
            $this->db->reset_query();
            $available_batches = $this->db
                ->select('id, available_qty, unit_value')
                ->from('stock_on_hand')
                ->where('item_id', $item_id)
                ->where('item_type', $item_type)
                ->order_by('id', 'ASC') // FIFO: batch lama dulu
                ->get()->result();

            $qty_needed = $issued_qty_total;
            $has_batches = !empty($available_batches);

            if ($has_batches) {
                // Ada stock_on_hand, gunakan FIFO
                foreach ($available_batches as $batch) {
                    if ($qty_needed <= 0) break;

                    $batch_soh_id = $batch->id;
                    $batch_cost = (float)$batch->unit_value;
                    
                    // Hitung available warehouse qty untuk batch ini (exclude WIP)
                    $warehouse_batch_qty = $this->db->query("
                        SELECT COALESCE(SUM(qty_in), 0) - COALESCE(SUM(qty_out), 0) as balance
                        FROM stock_ledger
                        WHERE item_id = {$item_id}
                        AND item_type = '{$item_type}'
                        AND stock_on_hand_id = {$batch_soh_id}
                        AND transaction_type NOT IN ('wip_in', 'wip_out')
                    ")->row()->balance;
                    
                    $batch_available = max(0, (float)$warehouse_batch_qty);
                    
                    // Skip batch jika tidak ada stok di gudang
                    if ($batch_available <= 0) {
                        continue;
                    }

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
                        'issuance_id'       => $issuance_id,
                        'source_doc_type'   => 'MR',
                        'source_doc_id'     => $detail->wo_header_id,
                        'qty_in'            => 0,
                        'qty_out'           => $qty_to_take,
                        'unit_value'        => $batch_cost,
                        'balance'           => $new_balance,
                        'stock_on_hand_id'  => $batch_soh_id
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
                    'issuance_id'       => $issuance_id,
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

            // Update issued_qty di mr_details (ADD ke existing, bukan replace)
            $current_detail = $this->db->get_where('mr_details', ['id' => $detail_id])->row();
            $new_issued_qty = (float)($current_detail->issued_qty ?? 0) + $issued_qty_total;
            $this->db->where('id', $detail_id)->update('mr_details', ['issued_qty' => $new_issued_qty]);
        }

        // Hitung status MR header pakai helper Mr_model — single source of truth.
        // Possible result setelah issue: Partial Issued, Issued, Partial Received (kalau ada
        // issuance sebelumnya yang sudah received), atau Received.
        $this->load->model('Mr_model');
        $new_status = $this->Mr_model->calculate_header_status($mr_id);

        $header_data = [
            'status'          => $new_status,
            'issuer_id'       => $user_id,
            'issued_at'       => date('Y-m-d H:i:s'),
            'issuance_number' => $issuance_number,
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $header_data);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Terjadi kesalahan database.'];
        }
        return ['success' => true];
    }
    
    /**
     * Proses Un-Issue (Batal Pengeluaran) - Menghapus OUT_ISSUE entries (bukan menambah reversal)
     * PERBAIKAN: Hapus OUT_ISSUE entries langsung, bukan buat IN_RETURN_WIP reversal
     */
    public function reverse_issuance($mr_id, $user_id) {
        $this->db->trans_start();

        // Ambil semua entri OUT_ISSUE yang terkait MR ini
        $out_issue_entries = $this->db->get_where('stock_ledger', [
            'transaction_type' => 'OUT_ISSUE',
            'transaction_id' => $mr_id
        ])->result();

        if (empty($out_issue_entries)) {
            $this->db->trans_rollback();
            return false;
        }

        // Restore stock_on_hand dan hapus OUT_ISSUE entries
        foreach ($out_issue_entries as $entry) {
            $qty_to_restore = (float)$entry->qty_out;
            $stock_on_hand_id = $entry->stock_on_hand_id ?? null;
            
            // Restore available_qty karena OUT_ISSUE sudah menguranginya
            if ($stock_on_hand_id) {
                $this->db->where('id', $stock_on_hand_id);
                $this->db->set('available_qty', "available_qty + $qty_to_restore", FALSE);
                $this->db->update('stock_on_hand');
            }
        }

        // Hapus semua OUT_ISSUE entries untuk MR ini (clean slate, bukan reversal)
        $this->db->where('transaction_type', 'OUT_ISSUE')
                 ->where('transaction_id', $mr_id)
                 ->delete('stock_ledger');

        // Update mr_details - reset issued_qty
        $this->db->where('mr_header_id', $mr_id)->update('mr_details', ['issued_qty' => 0]);
        
        // Recalc status MR header pakai helper Mr_model.
        $this->load->model('Mr_model');
        $new_status = $this->Mr_model->calculate_header_status($mr_id);
        $is_approved = ($new_status === 'Approved');

        $header_data = [
            'status'          => $new_status,
            'issuer_id'       => $is_approved ? NULL : $user_id,
            'issued_at'       => $is_approved ? NULL : date('Y-m-d H:i:s'),
            'issuance_number' => $is_approved ? NULL : null,
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $header_data);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // ============================================================
    // ATTACHMENTS — "Bukti Pengeluaran" (ERP Revision Notes 2026-09-02,
    // Fase 2 poin 8, Item Release). Pola sama persis dgn erf_attachments.
    // ============================================================

    public function get_attachments($mr_header_id) {
        return $this->db->where('mr_header_id', $mr_header_id)
            ->order_by('id', 'DESC')->get('material_issuance_attachments')->result();
    }

    public function get_attachment($id) {
        return $this->db->where('id', $id)->get('material_issuance_attachments')->row();
    }

    public function add_attachment($data) {
        $this->db->insert('material_issuance_attachments', $data);
        return $this->db->insert_id();
    }

    public function delete_attachment($id) {
        return $this->db->where('id', $id)->delete('material_issuance_attachments');
    }
}