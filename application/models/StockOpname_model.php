<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StockOpname_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        // Load model adjustment karena kita membutuhkannya
        $this->load->model('Adjustment_model');
    }

    public function get_all_headers()
    {
        $this->db->select('op.*, u.fullname as creator_name, adj.doc_no as adjustment_doc');
        $this->db->from('opname_header as op');
        $this->db->join('users as u', 'op.created_by = u.id', 'left');
        $this->db->join('adjustment_header as adj', 'op.adjustment_header_id = adj.id', 'left');
        $this->db->order_by('op.opname_date', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id)
    {
        $this->db->select('op.*, u.fullname as creator_name, c.fullname as confimer_name, adj.doc_no as adjustment_doc');
        $this->db->from('opname_header as op');
        $this->db->join('users as u', 'op.created_by = u.id', 'left');
        $this->db->join('users as c', 'op.confirmed_by = c.id', 'left');
        $this->db->join('adjustment_header as adj', 'op.adjustment_header_id = adj.id', 'left');
        $this->db->where('op.id', $id);
        return $this->db->get()->row();
    }

    public function get_details_by_header_id($header_id)
    {
        // Query ini mengambil nama barang dari tabel yang berbeda-beda
        $sql = "
            SELECT 
                od.*,
                CASE
                    WHEN od.item_type = 'material' THEN m.nama_material
                    WHEN od.item_type = 'semifinished' THEN sf.nama_barang
                    WHEN od.item_type = 'finished_goods' THEN fg.nama_barang
                    WHEN od.item_type = 'inventory' THEN i.nama_barang
                    WHEN od.item_type = 'machine' THEN mc.nama_mesin
                    WHEN od.item_type = 'scrap' THEN sc.scrap_name
                    ELSE 'UNKNOWN'
                END as item_name,
                CASE
                    WHEN od.item_type = 'material' THEN m.satuan
                    WHEN od.item_type = 'semifinished' THEN sf.satuan
                    WHEN od.item_type = 'finished_goods' THEN fg.satuan
                    WHEN od.item_type = 'inventory' THEN i.satuan
                    WHEN od.item_type = 'scrap' THEN sc.unit
                    ELSE 'PCS'
                END as item_unit
            FROM opname_details od
            LEFT JOIN materials m ON od.item_id = m.id AND od.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON od.item_id = sf.id AND od.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON od.item_id = fg.id AND od.item_type = 'finished_goods'
            LEFT JOIN inventories i ON od.item_id = i.id AND od.item_type = 'inventory'
            LEFT JOIN machines mc ON od.item_id = mc.id AND od.item_type = 'machine'
            LEFT JOIN master_scrap sc ON od.item_id = sc.id AND od.item_type = 'scrap'
            WHERE od.opname_header_id = ?
            ORDER BY item_name ASC
        ";
        return $this->db->query($sql, [$header_id])->result();
    }

    // Method untuk pagination - mengambil data dengan limit dan offset
    public function get_details_by_header_id_paginated($header_id, $limit, $start)
    {
        $sql = "
            SELECT 
                od.*,
                CASE
                    WHEN od.item_type = 'material' THEN m.nama_material
                    WHEN od.item_type = 'semifinished' THEN sf.nama_barang
                    WHEN od.item_type = 'finished_goods' THEN fg.nama_barang
                    WHEN od.item_type = 'inventory' THEN i.nama_barang
                    WHEN od.item_type = 'machine' THEN mc.nama_mesin
                    WHEN od.item_type = 'scrap' THEN sc.scrap_name
                    ELSE 'UNKNOWN'
                END as item_name,
                CASE
                    WHEN od.item_type = 'material' THEN m.satuan
                    WHEN od.item_type = 'semifinished' THEN sf.satuan
                    WHEN od.item_type = 'finished_goods' THEN fg.satuan
                    WHEN od.item_type = 'inventory' THEN i.satuan
                    WHEN od.item_type = 'scrap' THEN sc.unit
                    ELSE 'PCS'
                END as item_unit
            FROM opname_details od
            LEFT JOIN materials m ON od.item_id = m.id AND od.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON od.item_id = sf.id AND od.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON od.item_id = fg.id AND od.item_type = 'finished_goods'
            LEFT JOIN inventories i ON od.item_id = i.id AND od.item_type = 'inventory'
            LEFT JOIN machines mc ON od.item_id = mc.id AND od.item_type = 'machine'
            LEFT JOIN master_scrap sc ON od.item_id = sc.id AND od.item_type = 'scrap'
            WHERE od.opname_header_id = ?
            ORDER BY item_name ASC
            LIMIT ? OFFSET ?
        ";
        return $this->db->query($sql, [$header_id, $limit, $start])->result();
    }

    // Method untuk mendapatkan total jumlah records
    public function get_details_count($header_id)
    {
        return $this->db->where('opname_header_id', $header_id)->count_all_results('opname_details');
    }

    // Method untuk mendapatkan semua detail (tanpa pagination) untuk auto-fill
    public function get_all_details_by_header_id($header_id)
    {
        return $this->db->where('opname_header_id', $header_id)->get('opname_details')->result();
    }

    public function get_new_doc_number()
    {
        $this->db->select('RIGHT(opname_no, 4) as kode', FALSE);
        $this->db->order_by('opname_no', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get_where('opname_header', ['YEAR(opname_date)' => date('Y')]);
        
        $kode = ($query->num_rows() > 0) ? intval($query->row()->kode) + 1 : 1;
        $kode_max = str_pad($kode, 4, "0", STR_PAD_LEFT);
        return "OPN/" . date('Ym') . "/" . $kode_max;
    }

    // Validasi: Cek apakah sudah ada opname aktif untuk item_type dan bulan yang sama
    public function check_active_opname($item_type, $opname_date)
    {
        $year_month = date('Y-m', strtotime($opname_date));
        
        $this->db->where('item_type', $item_type);
        $this->db->where("DATE_FORMAT(opname_date, '%Y-%m') =", $year_month);
        $this->db->where_in('status', ['Draft', 'Counting']); // Status aktif
        $existing = $this->db->get('opname_header')->row();
        
        return $existing; // Return object jika ada, NULL jika tidak
    }
    
    // Validasi: Hitung berapa opname completed untuk item_type dan bulan yang sama
    public function count_completed_opname($item_type, $opname_date)
    {
        $year_month = date('Y-m', strtotime($opname_date));
        
        $this->db->where('item_type', $item_type);
        $this->db->where("DATE_FORMAT(opname_date, '%Y-%m') =", $year_month);
        $this->db->where('status', 'Completed');
        return $this->db->count_all_results('opname_header');
    }

    // Buat Sesi Opname dan Generate List Item (HANYA yang pernah ada transaksi)
    public function insert_opname_session($header_data, $item_type)
    {
        $this->db->trans_start();
        
        // 1. Insert Header
        $this->db->insert('opname_header', $header_data);
        $header_id = $this->db->insert_id();

        // 2. Get Item List - HANYA yang pernah ada transaksi di stock_ledger
        $items = $this->get_items_with_transactions($item_type);

        // 3. Generate opname_details
        foreach ($items as $item) {
            // Masukkan ke detail opname
            $detail_data = [
                'opname_header_id' => $header_id,
                'item_id' => $item->item_id,
                'item_type' => $item_type,
                'qty_sistem' => $item->current_balance,
                'qty_fisik' => NULL, // Kosongkan, tunggu input
                'selisih' => 0
            ];
            $this->db->insert('opname_details', $detail_data);
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }
    
    // Method baru: Ambil item yang sudah pernah ada transaksi
    private function get_items_with_transactions($item_type)
    {
        // Warehouse transaction types (sesuai get_warehouse_tx_types di Stock_ledger_model)
        $warehouse_tx_types = [
            'in', 'IN_RETURN_WIP', 'ADJ_IN', 
            'OUT_ISSUE', 'production_out', 'OUT_SHIPMENT', 'OUT_SCRAP_BC', 'ADJ_OUT'
        ];
        $warehouse_tx_list = "'" . implode("','", $warehouse_tx_types) . "'";
        
        // PERBAIKAN: Ambil final balance dari warehouse transaction TERAKHIR
        // Ini adalah nilai yang SEHARUSNYA ada di stock opname
        $sql = "
            SELECT 
                sl.item_id,
                sl.item_type,
                -- Ambil balance dari warehouse tx terakhir (paling reliable)
                (SELECT balance
                 FROM stock_ledger sl2
                 WHERE sl2.item_id = sl.item_id 
                   AND sl2.item_type = sl.item_type
                   AND sl2.transaction_type IN ($warehouse_tx_list)
                 ORDER BY sl2.id DESC
                 LIMIT 1) as current_balance
            FROM (
                SELECT DISTINCT item_id, item_type 
                FROM stock_ledger 
                WHERE item_type = ?
                AND transaction_type IN ($warehouse_tx_list)
            ) sl
        ";
        
        return $this->db->query($sql, [$item_type])->result();
    }

    // Simpan hasil hitungan (qty_fisik)
    public function update_counts($header_id, $details_data)
    {
        $this->db->trans_start();
        
        // Update hanya item yang ada di details_data (yang memang diinput user)
        foreach ($details_data as $detail_id => $data) {
            $qty_fisik_input = $data['qty_fisik'];
            $qty_fisik = ($qty_fisik_input === NULL || $qty_fisik_input === '') ? NULL : (float)$qty_fisik_input;
            $notes = $data['notes'] ?? '';
            
            $detail = $this->db->get_where('opname_details', ['id' => $detail_id])->row();
            if ($detail) {
                if ($qty_fisik !== NULL) {
                    // Jika diisi, hitung selisih normal
                    $selisih = $qty_fisik - $detail->qty_sistem;
                } else {
                    // Jika tidak diisi, set qty_fisik = qty_sistem (tidak ada selisih)
                    $qty_fisik = $detail->qty_sistem;
                    $selisih = 0;
                }
                
                $this->db->where('id', $detail_id);
                $this->db->update('opname_details', [
                    'qty_fisik' => $qty_fisik,
                    'notes' => $notes,
                    'selisih' => $selisih
                ]);
            }
        }
        
        // PENTING: Untuk item yang tidak ada di details_data (tidak pernah ditouch user)
        // Set qty_fisik = qty_sistem dan selisih = 0
        $this->db->query("
            UPDATE opname_details 
            SET qty_fisik = qty_sistem, selisih = 0 
            WHERE opname_header_id = ? 
            AND id NOT IN (" . (empty($details_data) ? "0" : implode(',', array_keys($details_data))) . ")
        ", [$header_id]);
        
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function update_header_status($header_id, $status, $adj_id = NULL)
    {
        $data = ['status' => $status];
        if ($adj_id) {
            $data['adjustment_header_id'] = $adj_id;
            $data['confirmed_by'] = $this->session->userdata('user_id');
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        }
        $this->db->where('id', $header_id);
        $this->db->update('opname_header', $data);
    }
    
    public function delete_draft_opname($header_id)
    {
        // Hanya bisa hapus jika status 'Draft'
        $this->db->trans_start();
        $this->db->delete('opname_details', ['opname_header_id' => $header_id]);
        $this->db->delete('opname_header', ['id' => $header_id, 'status' => 'Draft']);
        $affected_rows = $this->db->affected_rows();
        $this->db->trans_complete();
        return ($this->db->trans_status() && $affected_rows > 0);
    }

    // PROSES UTAMA: Konfirmasi Opname dan Buat Adjustment Otomatis
    public function process_opname_adjustment($header_id, $user_id)
    {
        $this->db->trans_start();
        
        $header = $this->get_header_by_id($header_id);
        
        // Query yang lebih strict - hanya ambil selisih yang benar-benar tidak 0
        $details = $this->db->where('opname_header_id', $header_id)
                            ->group_start()
                                ->where('selisih >', 0)
                                ->or_where('selisih <', 0)
                            ->group_end()
                            ->get('opname_details')
                            ->result();
        
        // Jika tidak ada selisih
        if (empty($details)) {
            $this->update_header_status($header_id, 'Completed');
            $this->db->trans_complete();
            return ['status' => TRUE, 'message' => 'Opname selesai. Tidak ada selisih ditemukan (semua qty fisik = qty sistem).'];
        }

        // Jika ADA selisih, buat dokumen Adjustment
        $adj_header_data = [
            'doc_no' => $this->Adjustment_model->get_new_doc_number(),
            'doc_date' => $header->opname_date,
            'notes' => 'Penyesuaian otomatis dari Stock Opname No: ' . $header->opname_no,
            'created_by' => $user_id,
            'status' => 'Draft' // Akan langsung dikonfirmasi
        ];
        
        $adj_details_data = [];
        foreach ($details as $d) {
            $adj_type = ($d->selisih > 0) ? 'IN' : 'OUT';
            $qty = abs($d->selisih);
            $unit_value = 0;
            $stock_on_hand_id = NULL;

            if ($adj_type == 'IN') {
                // Jika barang "ditemukan", ambil nilai terakhirnya
                $last_ledger = $this->Adjustment_model->get_latest_ledger_entry($d->item_id, $d->item_type);
                $unit_value = $last_ledger ? $last_ledger->unit_value : 0;
            
            } else { // Tipe 'OUT'
                if ($d->item_type == 'material') {
                    // Untuk opname adjustment OUT, kita perlu handle kasus stok tidak cukup
                    // Opsi 1: Split ke multiple batches jika perlu
                    // Opsi 2: Allow negative untuk opname correction
                    
                    $batches = $this->find_batches_for_opname_out($d->item_id, $qty);
                    if (empty($batches)) {
                        // Jika sama sekali tidak ada stok, buat adjustment dengan batch NULL
                        // Sistem akan create negative entry
                        $adj_details_data[] = [
                            'item_id' => $d->item_id,
                            'item_type' => $d->item_type,
                            'adjustment_type' => $adj_type,
                            'qty' => $qty,
                            'unit_value' => 0,
                            'stock_on_hand_id' => NULL,
                            'remarks' => 'Selisih Opname (No Stock): ' . $d->notes
                        ];
                        continue; // Skip ke item berikutnya
                    }
                    
                    // Jika ada batches, split adjustment sesuai available qty
                    foreach ($batches as $batch) {
                        $batch_qty = min($qty, $batch->available_qty);
                        $adj_details_data[] = [
                            'item_id' => $d->item_id,
                            'item_type' => $d->item_type,
                            'adjustment_type' => $adj_type,
                            'qty' => $batch_qty,
                            'unit_value' => 0,
                            'stock_on_hand_id' => $batch->id,
                            'remarks' => 'Selisih Opname (Batch): ' . $d->notes
                        ];
                        $qty -= $batch_qty;
                        if ($qty <= 0) break;
                    }
                    
                    // Jika masih ada sisa qty setelah semua batch habis
                    if ($qty > 0) {
                        $adj_details_data[] = [
                            'item_id' => $d->item_id,
                            'item_type' => $d->item_type,
                            'adjustment_type' => $adj_type,
                            'qty' => $qty,
                            'unit_value' => 0,
                            'stock_on_hand_id' => NULL,
                            'remarks' => 'Selisih Opname (Sisa): ' . $d->notes
                        ];
                    }
                    continue; // Skip ke item berikutnya karena sudah diproses
                } else {
                    // Untuk non-material (scrap, fg, etc) tidak perlu batch tracking
                    $stock_on_hand_id = NULL;
                }
            }

            $adj_details_data[] = [
                'item_id' => $d->item_id,
                'item_type' => $d->item_type,
                'adjustment_type' => $adj_type,
                'qty' => $qty,
                'unit_value' => $unit_value,
                'stock_on_hand_id' => $stock_on_hand_id,
                'remarks' => 'Selisih Opname: ' . $d->notes
            ];
        }

        // 1. Buat Dokumen Adjustment
        $adj_header_id = $this->Adjustment_model->insert_adjustment($adj_header_data, $adj_details_data);
        if (!$adj_header_id) {
            throw new Exception("Gagal membuat header adjustment.");
        }

        // 2. Langsung Konfirmasi Dokumen Adjustment
        $confirm_result = $this->Adjustment_model->confirm_adjustment($adj_header_id, $user_id);
        if ($confirm_result['status'] == FALSE) {
            // Jika konfirmasi gagal (misal: stok batch tidak cukup), batalkan semua
            throw new Exception("Gagal konfirmasi adjustment: " . $confirm_result['message']);
        }

        // 3. Update status Opname dan link ke Adjustment ID
        $this->update_header_status($header_id, 'Completed', $adj_header_id);

        $this->db->trans_complete();
        return ['status' => TRUE, 'message' => 'Opname berhasil dikonfirmasi. Dokumen adjustment ' . $adj_header_data['doc_no'] . ' telah dibuat.'];
    }
    
    // Helper: Cari multiple batches untuk opname OUT (FIFO approach)
    private function find_batches_for_opname_out($item_id, $qty_needed)
    {
        $batches = [];
        $remaining_qty = $qty_needed;
        
        // Ambil semua batch yang masih ada stok, urutkan FIFO
        $this->db->where('item_id', $item_id);
        $this->db->where('available_qty >', 0);
        $this->db->order_by('id', 'ASC'); // FIFO
        $query = $this->db->get('stock_on_hand');
        
        if ($query->num_rows() > 0) {
            foreach ($query->result() as $batch) {
                $batches[] = $batch;
                $remaining_qty -= $batch->available_qty;
                if ($remaining_qty <= 0) break; // Sudah cukup
            }
        }
        
        return $batches;
    }

    // Helper: Cari batch material tertua untuk dikurangi (legacy method - simplified)
    private function find_batch_for_opname_out($item_id, $qty_needed)
    {
        // Coba cari batch yg cukup
        $this->db->where('item_id', $item_id);
        $this->db->where('available_qty >=', $qty_needed);
        $this->db->order_by('id', 'ASC'); // FIFO
        $this->db->limit(1);
        $query = $this->db->get('stock_on_hand');

        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            // Jika tidak ada yg cukup, ambil saja batch tertua yg masih ada stok
            $this->db->where('item_id', $item_id);
            $this->db->where('available_qty >', 0);
            $this->db->order_by('id', 'ASC'); // FIFO
            $this->db->limit(1);
            return $this->db->get('stock_on_hand')->row();
        }
    }
}