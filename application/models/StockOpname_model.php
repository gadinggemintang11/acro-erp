<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class StockOpname_model extends CI_Model {

    public function __construct()
    {
        parent::__construct();
        // Load model adjustment karena kita membutuhkannya
        $this->load->model('Adjustment_model');
        // Load Stock_ledger_model untuk reuse get_warehouse_tx_types() — single source of truth
        $this->load->model('Stock_ledger_model');
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
        // Query ini mengambil nama & kode barang dari tabel yang berbeda-beda
        $sql = $this->_build_details_select_sql() . "
            WHERE od.opname_header_id = ?
            ORDER BY item_name ASC
        ";
        return $this->db->query($sql, [$header_id])->result();
    }

    // Method untuk pagination - mengambil data dengan limit dan offset
    public function get_details_by_header_id_paginated($header_id, $limit, $start)
    {
        $sql = $this->_build_details_select_sql() . "
            WHERE od.opname_header_id = ?
            ORDER BY item_name ASC
            LIMIT ? OFFSET ?
        ";
        return $this->db->query($sql, [$header_id, $limit, $start])->result();
    }

    // Hitung jumlah item yang sudah/belum dihitung untuk progress bar
    public function get_counting_progress($header_id)
    {
        $total = (int)$this->db->where('opname_header_id', $header_id)
                               ->count_all_results('opname_details');
        $counted = (int)$this->db->where('opname_header_id', $header_id)
                                 ->where('qty_fisik IS NOT NULL', NULL, FALSE)
                                 ->count_all_results('opname_details');
        return [
            'total'    => $total,
            'counted'  => $counted,
            'pending'  => max(0, $total - $counted),
        ];
    }

    private function _build_details_select_sql()
    {
        return "
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
                    WHEN od.item_type = 'material' THEN m.kode_material
                    WHEN od.item_type = 'semifinished' THEN sf.kode_barang
                    WHEN od.item_type = 'finished_goods' THEN fg.kode_barang
                    WHEN od.item_type = 'inventory' THEN i.kode_barang
                    WHEN od.item_type = 'machine' THEN mc.kode_mesin
                    WHEN od.item_type = 'scrap' THEN sc.scrap_code
                    ELSE ''
                END as item_code,
                CASE
                    WHEN od.item_type = 'material' THEN m.satuan
                    WHEN od.item_type = 'semifinished' THEN sf.satuan
                    WHEN od.item_type = 'finished_goods' THEN fg.satuan
                    WHEN od.item_type = 'inventory' THEN i.satuan
                    WHEN od.item_type = 'machine' THEN mc.satuan
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
        ";
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
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('stock_opname', 'opname_header', 'opname_no');
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

        // 2. Get Item List - snapshot per opname_date supaya konsisten dengan tanggal opname
        $opname_date = $header_data['opname_date'] ?? null;
        if ($item_type === 'scrap') {
            $items = $this->get_scrap_items_with_balance($opname_date);
        } else {
            $items = $this->get_items_with_transactions($item_type, $opname_date);
        }

        // 3. Generate opname_details
        foreach ($items as $item) {
            $detail_data = [
                'opname_header_id' => $header_id,
                'item_id' => $item->item_id,
                'item_type' => $item_type,
                'qty_sistem' => (float)$item->current_balance,
                'qty_fisik' => NULL, // Kosongkan, tunggu input
                'selisih' => 0
            ];
            $this->db->insert('opname_details', $detail_data);
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }

    /**
     * Ambil item yang punya history transaksi gudang, beserta saldo as-of opname_date.
     * - Daftar transaction_type konsisten dengan Stock_ledger_model::get_warehouse_tx_types()
     *   sehingga inventory (sgr_in / OUT_CONSUMABLE / INITIAL_STOCK), machine, dst ikut terjaring.
     * - Saldo dihitung ulang via SUM(qty_in) - SUM(qty_out) — lebih tahan terhadap
     *   anomali pada kolom `balance` daripada sekadar baca balance baris terakhir.
     * - Snapshot pakai `transaction_date <= opname_date 23:59:59` agar opname tanggal lampau
     *   tetap akurat.
     */
    public function get_items_with_transactions($item_type, $opname_date = null)
    {
        $warehouse_tx_types = $this->Stock_ledger_model->get_warehouse_tx_types();
        $snapshot_date = $opname_date ? $opname_date . ' 23:59:59' : date('Y-m-d H:i:s');

        $this->db->select("item_id, item_type, (IFNULL(SUM(qty_in),0) - IFNULL(SUM(qty_out),0)) as current_balance", FALSE);
        $this->db->from('stock_ledger');
        $this->db->where('item_type', $item_type);
        $this->db->where_in('transaction_type', $warehouse_tx_types);
        $this->db->where_not_in('transaction_type', ['wip_in', 'wip_out']);
        $this->db->where('transaction_date <=', $snapshot_date);
        $this->db->group_by(['item_id', 'item_type']);

        return $this->db->get()->result();
    }

    /**
     * Scrap tidak pakai stock_ledger — ambil saldo dari scrap_in/scrap_out yang Confirmed.
     * Sumber kebenarannya sama dengan Adjustment_model::get_latest_balance() untuk scrap.
     */
    private function get_scrap_items_with_balance($opname_date = null)
    {
        $snapshot_date = $opname_date ? $opname_date : date('Y-m-d');
        $sql = "
            SELECT
                s.id as item_id,
                'scrap' as item_type,
                (
                    IFNULL((
                        SELECT SUM(d_in.qty)
                        FROM scrap_in_detail d_in
                        JOIN scrap_in_header h_in ON h_in.id = d_in.header_id
                        WHERE d_in.scrap_id = s.id
                          AND h_in.status = 'Confirmed'
                          AND h_in.doc_date <= ?
                    ), 0)
                    -
                    IFNULL((
                        SELECT SUM(d_out.qty)
                        FROM scrap_out_detail d_out
                        JOIN scrap_out_header h_out ON h_out.id = d_out.header_id
                        WHERE d_out.scrap_id = s.id
                          AND h_out.status = 'Confirmed'
                          AND h_out.doc_date <= ?
                    ), 0)
                ) as current_balance
            FROM master_scrap s
            WHERE s.status = 'active'
        ";
        return $this->db->query($sql, [$snapshot_date, $snapshot_date])->result();
    }

    /*  \
    *
     * Refresh detail item untuk sesi yang belum Completed.
     * Tambahkan item baru yang sebelumnya belum ada di detail tanpa menghapus
     * input qty_fisik yang sudah dikerjakan user.
     */
    public function regenerate_items($header_id)
    {
        $header = $this->get_header_by_id($header_id);
        if (!$header || in_array($header->status, ['Completed', 'Cancelled'])) {
            return ['status' => FALSE, 'added' => 0, 'message' => 'Sesi tidak bisa di-refresh.'];
        }

        $this->db->trans_start();

        if ($header->item_type === 'scrap') {
            $items = $this->get_scrap_items_with_balance($header->opname_date);
        } else {
            $items = $this->get_items_with_transactions($header->item_type, $header->opname_date);
        }

        $existing = $this->db->select('item_id')
            ->where('opname_header_id', $header_id)
            ->get('opname_details')->result();
        $existing_ids = array_map(function($r){ return (int)$r->item_id; }, $existing);

        $added = 0;
        foreach ($items as $item) {
            if (in_array((int)$item->item_id, $existing_ids, true)) continue;
            $this->db->insert('opname_details', [
                'opname_header_id' => $header_id,
                'item_id' => $item->item_id,
                'item_type' => $header->item_type,
                'qty_sistem' => (float)$item->current_balance,
                'qty_fisik' => NULL,
                'selisih' => 0,
            ]);
            $added++;
        }

        $this->db->trans_complete();
        return [
            'status' => $this->db->trans_status() !== FALSE,
            'added' => $added,
            'message' => $added . ' item baru ditambahkan.'
        ];
    }

    // Simpan hasil hitungan (qty_fisik) — HANYA update item yang user input.
    // Item yang tidak disentuh user tetap qty_fisik = NULL (Belum diisi) dengan selisih = 0.
    // Ini lebih jujur secara data: hanya item yang benar-benar dihitung yang dianggap "counted".
    // Filter selisih != 0 saat confirm akan otomatis skip item NULL (selisih masih 0).
    public function update_counts($header_id, $details_data)
    {
        if (empty($details_data)) {
            return TRUE;
        }

        $this->db->trans_start();

        foreach ($details_data as $detail_id => $data) {
            $detail_id = (int)$detail_id; // SQL safety
            if ($detail_id <= 0) continue;

            $qty_fisik_input = $data['qty_fisik'] ?? '';
            $notes = isset($data['notes']) ? trim($data['notes']) : '';

            $detail = $this->db->get_where('opname_details',
                        ['id' => $detail_id, 'opname_header_id' => $header_id])->row();
            if (!$detail) continue;

            if ($qty_fisik_input === NULL || $qty_fisik_input === '') {
                // User clear field — anggap belum dihitung
                $qty_fisik = NULL;
                $selisih   = 0;
            } else {
                $qty_fisik = (float)$qty_fisik_input;
                $selisih   = $qty_fisik - (float)$detail->qty_sistem;
            }

            $this->db->where('id', $detail_id);
            $this->db->update('opname_details', [
                'qty_fisik' => $qty_fisik,
                'notes'     => $notes,
                'selisih'   => $selisih,
            ]);
        }

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