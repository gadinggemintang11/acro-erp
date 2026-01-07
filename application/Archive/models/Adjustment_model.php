<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Adjustment_model extends CI_Model {

    // (Fungsi get_all_headers, get_header_by_id, get_details_by_header_id, get_new_doc_number SAMA SEPERTI SEBELUMNYA)
    // ... (copy dari jawaban saya sebelumnya) ...
    public function get_all_headers()
    {
        $this->db->select('adj.*, u.fullname as creator_name');
        $this->db->from('adjustment_header as adj');
        $this->db->join('users as u', 'adj.created_by = u.id', 'left');
        $this->db->order_by('adj.doc_date', 'DESC');
        $this->db->order_by('adj.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id)
    {
        $this->db->select('adj.*, u.fullname as creator_name, c.fullname as confimer_name');
        $this->db->from('adjustment_header as adj');
        $this->db->join('users as u', 'adj.created_by = u.id', 'left');
        $this->db->join('users as c', 'adj.updated_by = c.id', 'left'); 
        $this->db->where('adj.id', $id);
        return $this->db->get()->row();
    }

    public function get_details_by_header_id($header_id)
    {
        $sql = "
            SELECT 
                ad.*,
                CASE
                    WHEN ad.item_type = 'material' THEN m.nama_material
                    WHEN ad.item_type = 'semifinished' THEN sf.nama_barang
                    WHEN ad.item_type = 'finished_goods' THEN fg.nama_barang
                    WHEN ad.item_type = 'inventory' THEN i.nama_barang
                    WHEN ad.item_type = 'machine' THEN mc.nama_mesin
                    ELSE 'UNKNOWN'
                END as item_name,
                CASE
                    WHEN ad.item_type = 'material' THEN m.satuan
                    WHEN ad.item_type = 'semifinished' THEN sf.satuan
                    WHEN ad.item_type = 'finished_goods' THEN fg.satuan
                    WHEN ad.item_type = 'inventory' THEN i.satuan
                    ELSE 'PCS'
                END as item_unit,
                soh.available_qty as batch_qty
            FROM adjustment_details ad
            LEFT JOIN materials m ON ad.item_id = m.id AND ad.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON ad.item_id = sf.id AND ad.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON ad.item_id = fg.id AND ad.item_type = 'finished_goods'
            LEFT JOIN inventories i ON ad.item_id = i.id AND ad.item_type = 'inventory'
            LEFT JOIN machines mc ON ad.item_id = mc.id AND ad.item_type = 'machine'
            LEFT JOIN stock_on_hand soh ON ad.stock_on_hand_id = soh.id AND ad.item_type = 'material'
            WHERE ad.adjustment_header_id = ?
        ";
        return $this->db->query($sql, [$header_id])->result();
    }

    public function get_new_doc_number()
    {
        $this->db->select('RIGHT(doc_no, 4) as kode', FALSE);
        $this->db->order_by('doc_no', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get_where('adjustment_header', ['YEAR(doc_date)' => date('Y')]);
        
        if ($query->num_rows() > 0) {
            $data = $query->row();
            $kode = intval($data->kode) + 1;
        } else {
            $kode = 1;
        }
        $kode_max = str_pad($kode, 4, "0", STR_PAD_LEFT);
        return "ADJ/" . date('Ym') . "/" . $kode_max;
    }
    
    // Menyimpan data ke DB (Status Draft)
    public function insert_adjustment($header_data, $details_data)
    {
        $this->db->trans_start();

        $this->db->insert('adjustment_header', $header_data);
        $header_id = $this->db->insert_id();

        foreach ($details_data as $detail) {
            if (!empty($detail['item_id'])) {
                $detail['adjustment_header_id'] = $header_id;
                if ($detail['adjustment_type'] == 'IN') {
                    $detail['stock_on_hand_id'] = NULL;
                } else {
                    $detail['unit_value'] = 0;
                }
                $this->db->insert('adjustment_details', $detail);
            }
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }

    // FUNGSI BARU: Update Dokumen (Hanya Draft)
    public function update_adjustment($header_id, $header_data, $details_data)
    {
        $this->db->trans_start();
        
        // 1. Cek status
        $header = $this->db->get_where('adjustment_header', ['id' => $header_id])->row();
        if (!$header || $header->status != 'Draft') {
            $this->db->trans_rollback();
            return FALSE;
        }

        // 2. Update Header
        $this->db->update('adjustment_header', $header_data, ['id' => $header_id]);
        
        // 3. Hapus Detail Lama
        $this->db->delete('adjustment_details', ['adjustment_header_id' => $header_id]);

        // 4. Masukkan Detail Baru
        foreach ($details_data as $detail) {
            if (!empty($detail['item_id'])) {
                $detail['adjustment_header_id'] = $header_id;
                if ($detail['adjustment_type'] == 'IN') {
                    $detail['stock_on_hand_id'] = NULL;
                } else {
                    $detail['unit_value'] = 0;
                }
                $this->db->insert('adjustment_details', $detail);
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
    
    // FUNGSI BARU: Delete Dokumen (Hanya Draft)
    public function delete_draft_adjustment($header_id)
    {
        $this->db->trans_start();
        
        // 1. Hapus Detail
        $this->db->delete('adjustment_details', ['adjustment_header_id' => $header_id]);
        
        // 2. Hapus Header (hanya jika statusnya Draft)
        $this->db->delete('adjustment_header', ['id' => $header_id, 'status' => 'Draft']);
        $affected_rows = $this->db->affected_rows();

        $this->db->trans_complete();
        
        // Jika affected_rows > 0, berarti header berhasil dihapus (statusnya Draft)
        return ($this->db->trans_status() && $affected_rows > 0);
    }
    
    // Membatalkan dokumen (hanya jika Draft)
    public function cancel_adjustment($header_id, $user_id)
    {
        $this->db->where('id', $header_id);
        $this->db->where('status', 'Draft');
        $this->db->update('adjustment_header', [
            'status' => 'Cancelled',
            'updated_by' => $user_id,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        return $this->db->affected_rows() > 0;
    }

    // Konfirmasi Dokumen -> Update Stok (MAIN LOGIC)
    public function confirm_adjustment($header_id, $user_id)
    {
        $this->db->trans_start();

        $header = $this->get_header_by_id($header_id);
        if ($header->status != 'Draft') {
            return ['status' => FALSE, 'message' => 'Hanya status Draft yang bisa dikonfirmasi.'];
        }

        $details = $this->get_details_by_header_id($header_id); // Gunakan fungsi yg sudah ada
        $now = date('Y-m-d H:i:s');
        $date = $header->doc_date . ' ' . date('H:i:s'); 

        foreach ($details as $item) {
            $qty_in = 0;
            $qty_out = 0;
            $unit_value = 0;
            $stock_on_hand_id = NULL;

            if ($item->adjustment_type == 'IN') {
                $qty_in = $item->qty;
                $unit_value = $item->unit_value; 

                if ($item->item_type == 'material') {
                    $batch_data = [
                        'grn_detail_id' => NULL,
                        'grn_header_id' => NULL,
                        'item_id' => $item->item_id,
                        'item_type' => 'material',
                        'initial_qty' => $item->qty,
                        'available_qty' => $item->qty,
                        'unit_value' => $unit_value
                    ];
                    $this->db->insert('stock_on_hand', $batch_data);
                    $stock_on_hand_id = $this->db->insert_id();
                }

            } else { // Tipe 'OUT'
                $qty_out = $item->qty;
                
                if ($item->item_type == 'material') {
                    $stock_on_hand_id = $item->stock_on_hand_id;
                    if(empty($stock_on_hand_id)) {
                        $this->db->trans_rollback();
                        return ['status' => FALSE, 'message' => 'Batch material wajib dipilih untuk ' . $item->item_name];
                    }
                    
                    $batch = $this->get_batch_info($stock_on_hand_id);
                    
                    if (!$batch) {
                        $this->db->trans_rollback();
                        return ['status' => FALSE, 'message' => 'Batch material tidak ditemukan.'];
                    }
                    if ($batch->available_qty < $item->qty) {
                        $this->db->trans_rollback();
                        return ['status' => FALSE, 'message' => 'Stok batch ' . $item->item_name . ' tidak mencukupi (Tersisa: '.$batch->available_qty.').'];
                    }

                    $unit_value = $batch->unit_value;
                    $this->db->where('id', $stock_on_hand_id);
                    $this->db->set('available_qty', 'available_qty - ' . $item->qty, FALSE);
                    $this->db->update('stock_on_hand');
                } else {
                    $last_ledger = $this->get_latest_ledger_entry($item->item_id, $item->item_type);
                    $unit_value = $last_ledger ? $last_ledger->unit_value : 0;
                }
            }

            // Update Saldo Terakhir
            $last_balance = $this->get_latest_balance($item->item_id, $item->item_type);
            $new_balance = $last_balance + $qty_in - $qty_out;
            
            // Catat di Stock Ledger
            $ledger_data = [
                'item_id' => $item->item_id,
                'item_type' => $item->item_type,
                'transaction_date' => $date,
                'transaction_type' => 'adjustment',
                'bc_type_code' => NULL,
                'bc_number' => NULL,
                'bc_date' => NULL,
                'transaction_id' => $item->id, // ID dari adjustment_details
                'source_doc_type' => 'ADJ',
                'source_doc_id' => $header_id,
                'qty_in' => $qty_in,
                'qty_out' => $qty_out,
                'unit_value' => $unit_value,
                'balance' => $new_balance,
                'stock_on_hand_id' => ($item->item_type == 'material') ? $stock_on_hand_id : NULL
            ];
            $this->db->insert('stock_ledger', $ledger_data);
        }

        // 3. Update Status Header
        $this->db->where('id', $header_id);
        $this->db->update('adjustment_header', [
            'status' => 'Confirmed',
            'updated_by' => $user_id,
            'updated_at' => $now
        ]);

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? 
            ['status' => FALSE, 'message' => 'Terjadi kesalahan DB.'] : 
            ['status' => TRUE, 'message' => 'OK'];
    }


    /**
     * HELPERS UNTUK AJAX & LOGIC
     */

    public function get_item_list_by_type($type)
    {
        switch ($type) {
            case 'material':
                return $this->db->select('id, kode_material as code, nama_material as name, satuan as unit')
                                ->where('status', 'active')->get('materials')->result();
            case 'semifinished':
                return $this->db->select('id, kode_barang as code, nama_barang as name, satuan as unit')
                                ->where('status', 'active')->get('semifinished_goods')->result();
            case 'finished_goods':
                return $this->db->select('id, kode_barang as code, nama_barang as name, satuan as unit')
                                ->where('status', 'active')->get('finished_goods')->result();
            case 'inventory':
                return $this->db->select('id, kode_barang as code, nama_barang as name, satuan as unit')
                                ->where('status', 'Active')->get('inventories')->result();
            case 'machine':
                return $this->db->select('id, kode_mesin as code, nama_mesin as name, "PCS" as unit')
                                ->where('status', 'Active')->get('machines')->result();
            default:
                return [];
        }
    }

    // Revisi: Ambil batch yang available ATAU batch yang sedang dipilih (untuk edit)
    public function get_batches_for_item($material_id, $include_selected_id = NULL)
    {
        $this->db->select('id, available_qty, unit_value, grn_header_id');
        $this->db->where('item_id', $material_id);
        
        if ($include_selected_id) {
            $this->db->group_start();
            $this->db->where('available_qty >', 0);
            $this->db->or_where('id', $include_selected_id);
            $this->db->group_end();
        } else {
            $this->db->where('available_qty >', 0);
        }
        
        return $this->db->get('stock_on_hand')->result();
    }
    
    public function get_batch_info($stock_on_hand_id)
    {
        return $this->db->get_where('stock_on_hand', ['id' => $stock_on_hand_id])->row();
    }
    
    public function get_latest_ledger_entry($item_id, $item_type)
    {
        $this->db->where('item_id', $item_id);
        $this->db->where('item_type', $item_type);
        $this->db->order_by('transaction_date', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        return $this->db->get('stock_ledger')->row();
    }

    public function get_latest_balance($item_id, $item_type)
    {
        $entry = $this->get_latest_ledger_entry($item_id, $item_type);
        return $entry ? (float) $entry->balance : 0;
    }
}