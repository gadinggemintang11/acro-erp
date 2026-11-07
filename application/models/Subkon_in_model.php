<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subkon_in_model extends CI_Model {

    private $table_header = 'subkon_in_header';
    private $table_details = 'subkon_in_details';
    private $table_order_outputs = 'subkon_order_outputs';
    private $table_order_header = 'subkon_order_header';

    // ========================================================================
    // HEADER
    // ========================================================================
    
    public function generate_grn_subkon_no() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('subkon_in', 'subkon_in_header', 'subkon_in_no');
    }

    public function get_all($filters, $limit, $offset) {
        // --- INIASILISASI FILTER AMAN ---
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;
        // --- AKHIR INIASILISASI ---

        $this->db->select('sih.*, s.nama_supplier, sko.subkon_order_no');
        $this->db->from($this->table_header . ' sih');
        $this->db->join('suppliers s', 'sih.supplier_id = s.id', 'left');
        $this->db->join('subkon_order_header sko', 'sih.subkon_order_id = sko.id', 'left');
        
        // Perbaikan Logic Tanggal (Hanya apply WHERE jika nilai filter ADA)
        if (!empty($start_date)) { $this->db->where('sih.receipt_date >=', $start_date); }
        if (!empty($end_date)) { $this->db->where('sih.receipt_date <=', $end_date); } // Cuma tanggal, bukan waktu
        
        if (!empty($status)) { $this->db->where('sih.status', $status); }
        
        if (!empty($search)) {
            $term = $this->db->escape_like_str($search);
            $this->db->group_start();
            $this->db->like('sih.subkon_in_no', $term);
            $this->db->or_like('sko.subkon_order_no', $term);
            $this->db->or_like('s.nama_supplier', $term);
            $this->db->group_end();
        }
        $this->db->order_by('sih.receipt_date', 'DESC');
        if ($limit > 0) { $this->db->limit($limit, $offset); }
        return $this->db->get()->result();
    }
    
    public function count_all($filters) {
        // --- INIASILISASI FILTER AMAN ---
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;
        $status = $filters['status'] ?? null;
        $search = $filters['search'] ?? null;
        // --- AKHIR INIASILISASI ---

        $this->db->from($this->table_header . ' sih');
        $this->db->join('suppliers s', 'sih.supplier_id = s.id', 'left');
        $this->db->join('subkon_order_header sko', 'sih.subkon_order_id = sko.id', 'left');
        
        // Perbaikan Logic Tanggal (Hanya apply WHERE jika nilai filter ADA)
        if (!empty($start_date)) { $this->db->where('sih.receipt_date >=', $start_date); }
        if (!empty($end_date)) { $this->db->where('sih.receipt_date <=', $end_date); }
        
        if (!empty($status)) { $this->db->where('sih.status', $status); }
        
        if (!empty($search)) {
            $term = $this->db->escape_like_str($search);
            $this->db->group_start();
            $this->db->like('sih.subkon_in_no', $term);
            $this->db->or_like('sko.subkon_order_no', $term);
            $this->db->or_like('s.nama_supplier', $term);
            $this->db->group_end();
        }
        return $this->db->count_all_results();
    }
    
    public function get_header_by_id($id) {
        $this->db->select('sih.*, s.nama_supplier, sko.subkon_order_no');
        $this->db->from($this->table_header . ' sih');
        $this->db->join('suppliers s', 'sih.supplier_id = s.id', 'left');
        $this->db->join('subkon_order_header sko', 'sih.subkon_order_id = sko.id', 'left');
        $this->db->where('sih.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }
    public function update_status($id, $status, $user_id = null) {
        $data = ['status' => $status];
        if ($status == 'Completed' && $user_id) {
            $data['confirmed_by'] = $user_id;
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif ($status == 'Draft') {
            $data['confirmed_by'] = NULL;
            $data['confirmed_at'] = NULL;
        }
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }
    
    // (Tambahkan get_header_by_id, get_all, count_all, update_status seperti di model lainnya)

    // ========================================================================
    // DETAIL & LOGIKA STOK
    // ========================================================================

    public function get_details_by_id($in_id) {
        $this->db->select("sid.*, 
           
            soo.qty as qty_ordered, 
            soo.item_id, 
            soo.item_type,
            
            
            CASE 
                WHEN soo.item_type = 'semifinished' THEN sf.nama_barang
                WHEN soo.item_type = 'finished_goods' THEN fg.nama_barang
                ELSE 'N/A'
            END as output_name
        ", FALSE); // <-- Tambahkan FALSE untuk CASE WHEN
        
        $this->db->from($this->table_details . ' sid');
        $this->db->join($this->table_order_outputs . ' soo', 'sid.order_output_id = soo.id');
        
        // --- TAMBAH JOIN KE MASTER ITEM ---
        $this->db->join('semifinished_goods sf', 'soo.item_id = sf.id AND soo.item_type = "semifinished"', 'left');
        $this->db->join('finished_goods fg', 'soo.item_id = fg.id AND soo.item_type = "finished_goods"', 'left');
        
        $this->db->where('sid.subkon_in_id', $in_id);
        return $this->db->get()->result();
    }

    
    public function insert_item_to_grn($data) {
        return $this->db->insert($this->table_details, $data);
    }
    
    // Tambahkan fungsi delete_item_from_grn

    // ========================================================================
    // AJAX HELPERS
    // ========================================================================
    
    // Fungsi ini mengambil Item Hasil yang masih ada sisa kuantitas yang belum diterima
    public function get_available_output_items($order_id) {
        // 1. Hitung total yang sudah diterima
        $received_subquery = $this->db->select('order_output_id, SUM(qty_received) as total_received')
                                        ->from($this->table_details . ' sid')
                                        ->join($this->table_header . ' sih', 'sid.subkon_in_id = sih.id')
                                        ->where('sih.subkon_order_id', $order_id)
                                        ->where('sih.status', 'Completed')
                                        ->group_by('order_output_id')
                                        ->get_compiled_select();

        // 2. Ambil detail Item Hasil dari Kontrak, bandingkan dengan yang sudah diterima
        $this->db->select('soo.id, soo.item_id, soo.item_type, soo.qty as qty_ordered, soo.satuan, soo.jasa_per_pcs,
            IFNULL(received.total_received, 0) as total_received,
            (soo.qty - IFNULL(received.total_received, 0)) as remaining_qty,
            CASE 
                WHEN soo.item_type = "semifinished" THEN sf.nama_barang
                WHEN soo.item_type = "finished_goods" THEN fg.nama_barang
            END as item_name
        ', FALSE);
        $this->db->from($this->table_order_outputs . ' soo');
        $this->db->join('semifinished_goods sf', 'soo.item_id = sf.id AND soo.item_type = "semifinished"', 'left');
        $this->db->join('finished_goods fg', 'soo.item_id = fg.id AND soo.item_type = "finished_goods"', 'left');
        $this->db->join("({$received_subquery}) received", 'soo.id = received.order_output_id', 'left');
        
        $this->db->where('soo.subkon_order_id', $order_id);
        $this->db->having('remaining_qty >', 0);
        $this->db->where('soo.qty >', 0); // Hanya item yang dipesan > 0
        
        return $this->db->get()->result();
    }
    
    public function get_completed_orders_for_receipt($term) {
        $this->db->select('sko.id, CONCAT(sko.subkon_order_no, " - ", s.nama_supplier) as text, sko.supplier_id', FALSE);
        $this->db->from($this->table_order_header . ' sko');
        $this->db->join('suppliers s', 'sko.supplier_id = s.id', 'left');
        $this->db->where('sko.status', 'Confirmed');
        $this->db->group_start();
        $this->db->like('sko.subkon_order_no', $term);
        $this->db->or_like('s.nama_supplier', $term);
        $this->db->group_end();
        $query = $this->db->limit(10)->get();
        return $query->result();
    }
    public function unconfirm_grn($in_id) {
        $this->load->model('Stock_ledger_model');
        $header = $this->get_header_by_id($in_id);
        $details = $this->get_details_by_id($in_id);

        $this->db->trans_start();

        foreach ($details as $item) {
            // Hapus SOH (Batch) yang dibuat saat konfirmasi
            // Menggunakan subkon_in_detail_id sebagai referensi
            $this->db->where('subkon_in_detail_id', $item->id)->delete('stock_on_hand');

            // Hapus Ledger berdasarkan ID detail
            if (!$this->Stock_ledger_model->delete_by_transaction_id($item->id, 'in')) {
                log_message('error', 'Gagal hapus ledger detail ID: ' . $item->id);
                $this->db->trans_rollback();
                return false;
            }
        }
        
        // Update status kembali ke Draft
        $this->update_status($in_id, 'Draft');
        $this->db->trans_complete();
        
        if (!$this->db->trans_status()) {
             log_message('error', 'Transaksi unconfirm GRN Subkon GAGAL.');
        }
        return $this->db->trans_status();
    }
    // Tambahkan fungsi ini di dalam class Subkon_in_model

    public function delete_item_from_grn($detail_id) {
        // Logika sederhana: hapus detail, dan hitung ulang total jasa saat ada perubahan
        $detail = $this->db->where('id', $detail_id)->get($this->table_details)->row();
        if (!$detail) return false;
        
        $order_id = $detail->subkon_in_id;

        $this->db->trans_start();
        $delete_success = $this->db->where('id', $detail_id)->delete($this->table_details);

        if ($delete_success) {
            // Recalculate total jasa di header in_header (jika diperlukan)
            // Namun, untuk Subkon IN, total jasa dihitung dan dikunci saat konfirmasi. 
            // Cukup delete detailnya.
        }
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    // ========================================================================
    // LOGIKA KONFIRMASI (Pemasukan Stok & HPP)
    // ========================================================================
    
    public function confirm_grn($in_id, $user_id) {
        $this->load->model('Stock_ledger_model');
        $header = $this->get_header_by_id($in_id);
        $details = $this->get_details_by_id($in_id);
        
        // Cek jika tidak ada detail, konfirmasi gagal
        if (empty($details)) { return false; } 

        $this->db->trans_start();

        foreach ($details as $item) {
            
            // Mengubah objek menjadi array untuk memastikan properti diakses dengan benar
            $item = (array) $item; 
            
            $unit_cost_jasa = (float)($item['jasa_per_pcs'] ?? 0); 
            $unit_cost_final = $unit_cost_jasa; // HPP barang masuk adalah nilai jasa
            
            // --- DEKLARASI LEDGER DATA ---
            $ledger_data = [
                'item_id'           => $item['item_id'] ?? NULL,
                'item_type'         => $item['item_type'] ?? NULL,
                'transaction_date'  => $header->receipt_date . ' ' . date('H:i:s'),
                'transaction_type'  => 'in',
                'transaction_id'    => $item['id'] ?? NULL, // ID detail subkon_in_details
                'source_doc_type'   => 'SUBKON_IN',
                'source_doc_id'     => $in_id,
                'qty_in'            => (float)($item['qty_received'] ?? 0),
                'qty_out'           => 0,
                'unit_value'        => $unit_cost_final,
                'bc_type_code'      => $header->bc_type_code,
                'bc_number'         => $header->bc_number,
                'bc_date'           => $header->bc_date
            ];
            
            // FINAL CHECK DATA PENTING SEBELUM INSERT
            if (empty($ledger_data['item_id']) || empty($ledger_data['item_type']) || empty($ledger_data['transaction_type'])) {
                log_message('error', 'Konfirmasi Subkon GAGAL: Data kunci (ID/Type) kosong. Item ID: ' . $ledger_data['item_id']);
                $this->db->trans_rollback();
                return false;
            }

            if (!$this->Stock_ledger_model->insert($ledger_data)) {
                $this->db->trans_rollback();
                return false;
            }
            
            // Masukkan ke STOCK ON HAND (Batch)
            $this->db->insert('stock_on_hand', [
                'grn_detail_id'       => NULL, 
                'grn_header_id'       => NULL, 
                'subkon_in_detail_id' => $item['id'],
                'subkon_in_header_id' => $in_id,
                'item_id'       => $item['item_id'],
                'item_type'     => $item['item_type'],
                'initial_qty'   => (float)($item['qty_received'] ?? 0),
                'available_qty' => (float)($item['qty_received'] ?? 0),
                'unit_value'    => $unit_cost_final
            ]);
        }
        
        $this->update_status($in_id, 'Completed', $user_id);
        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    public function search_confirmed_orders($term = '') {
    // Definisikan alias tabel di sini agar SELECT dan WHERE menggunakannya
    $table = $this->table_order_header;
    $alias = 'sko';
    
    $this->db->select("{$alias}.id, CONCAT({$alias}.subkon_order_no, ' - ', s.nama_supplier) as text, {$alias}.supplier_id", FALSE);
    $this->db->from("{$table} {$alias}"); // Mendefinisikan tabel dan alias yang benar
    $this->db->join('suppliers s', "{$alias}.supplier_id = s.id", 'left');
    $this->db->where("{$alias}.status", 'Confirmed');

    if (!empty($term)) {
        $term = $this->db->escape_like_str($term);
        $this->db->group_start();
        $this->db->like("{$alias}.subkon_order_no", $term);
        $this->db->or_like('s.nama_supplier', $term);
        $this->db->group_end();
    }
    
    $query = $this->db->limit(10)->get();
    
    // Hasil dikembalikan dalam format yang diharapkan Select2
    $results = [];
    foreach ($query->result() as $row) {
        $results[] = [
            'id'          => $row->id,
            // Perlu ditambahkan nama supplier dan nomor kontrak di text agar mudah dibaca di Select2
            'text'        => $row->text, 
            'supplier_id' => $row->supplier_id
        ];
    }
    return $results;
}
    
    
    // Tambahkan fungsi unconfirm_grn
}