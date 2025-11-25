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
                    ELSE 'UNKNOWN'
                END as item_name,
                CASE
                    WHEN od.item_type = 'material' THEN m.satuan
                    WHEN od.item_type = 'semifinished' THEN sf.satuan
                    WHEN od.item_type = 'finished_goods' THEN fg.satuan
                    WHEN od.item_type = 'inventory' THEN i.satuan
                    ELSE 'PCS'
                END as item_unit
            FROM opname_details od
            LEFT JOIN materials m ON od.item_id = m.id AND od.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON od.item_id = sf.id AND od.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON od.item_id = fg.id AND od.item_type = 'finished_goods'
            LEFT JOIN inventories i ON od.item_id = i.id AND od.item_type = 'inventory'
            LEFT JOIN machines mc ON od.item_id = mc.id AND od.item_type = 'machine'
            WHERE od.opname_header_id = ?
            ORDER BY item_name ASC
        ";
        return $this->db->query($sql, [$header_id])->result();
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

    // Buat Sesi Opname dan Generate List Item
    public function insert_opname_session($header_data, $item_type)
    {
        $this->db->trans_start();
        
        // 1. Insert Header
        $this->db->insert('opname_header', $header_data);
        $header_id = $this->db->insert_id();

        // 2. Get Item List based on type
        $items = $this->Adjustment_model->get_item_list_by_type($item_type);

        // 3. Generate opname_details
        foreach ($items as $item) {
            // Ambil saldo sistem terakhir
            $qty_sistem = $this->Adjustment_model->get_latest_balance($item->id, $item_type);

            // Masukkan ke detail opname
            $detail_data = [
                'opname_header_id' => $header_id,
                'item_id' => $item->id,
                'item_type' => $item_type,
                'qty_sistem' => $qty_sistem,
                'qty_fisik' => NULL, // Kosongkan, tunggu input
                'selisih' => 0 - $qty_sistem // Asumsi fisik 0 dulu
            ];
            $this->db->insert('opname_details', $detail_data);
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }

    // Simpan hasil hitungan (qty_fisik)
    public function update_counts($header_id, $details_data)
    {
        $this->db->trans_start();
        foreach ($details_data as $detail_id => $data) {
            
            // === PERBAIKAN BUG DI SINI ===
            // Jika inputan kosong atau NULL, simpan sebagai NULL
            // Jika diisi (termasuk "0"), simpan sebagai angka.
            $qty_fisik_input = $data['qty_fisik'];
            $qty_fisik = ($qty_fisik_input === NULL || $qty_fisik_input === '') ? NULL : (float)$qty_fisik_input;
            // === AKHIR PERBAIKAN ===
            
            $notes = $data['notes'];
            
            $detail = $this->db->get_where('opname_details', ['id' => $detail_id])->row();
            if ($detail) {
                // Hitung selisih HANYA JIKA fisik diisi
                if ($qty_fisik !== NULL) {
                    $selisih = $qty_fisik - $detail->qty_sistem;
                } else {
                    // Jika fisik NULL, selisih di-reset (bisa 0 atau -qty_sistem, tergantung kebutuhan)
                    // Kita set 0 agar tidak bingung
                    $selisih = 0 - $detail->qty_sistem; // atau biarkan 0
                }
                
                $this->db->where('id', $detail_id);
                $this->db->update('opname_details', [
                    'qty_fisik' => $qty_fisik,
                    'notes' => $notes,
                    'selisih' => $selisih
                ]);
            }
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
        $details = $this->db->where('opname_header_id', $header_id)
                            ->where('selisih !=', 0)
                            ->get('opname_details')
                            ->result();
        
        // Jika tidak ada selisih
        if (empty($details)) {
            $this->update_header_status($header_id, 'Completed');
            $this->db->trans_complete();
            return ['status' => TRUE, 'message' => 'Opname selesai. Tidak ada selisih ditemukan.'];
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
                    // Cari batch tertua (FIFO-like) untuk dikurangi
                    $batch = $this->find_batch_for_opname_out($d->item_id, $qty);
                    if (!$batch) {
                        // Jika tidak ada batch yg cukup, transaksi akan gagal (ini bagus)
                        // Kita coba ambil batch tertua yg ada
                        $batch = $this->find_batch_for_opname_out($d->item_id, 0); 
                         if (!$batch) {
                            throw new Exception("Tidak ada batch stok on hand ditemukan untuk item ID: {$d->item_id}.");
                         }
                    }
                    $stock_on_hand_id = $batch->id;
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
    
    // Helper: Cari batch material tertua untuk dikurangi
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