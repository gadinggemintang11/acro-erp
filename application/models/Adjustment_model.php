<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Adjustment_model extends CI_Model {

    private $_has_grn_col = null;

    /** Cek apakah kolom grn_header_id sudah ada (untuk kompatibilitas server lama) */
    private function _has_grn_col() {
        if ($this->_has_grn_col === null) {
            $q = $this->db->query("SHOW COLUMNS FROM adjustment_header LIKE 'grn_header_id'");
            $this->_has_grn_col = ($q->num_rows() > 0);
        }
        return $this->_has_grn_col;
    }
    public function get_all_headers()
    {
        $sql = "
        SELECT 
            adj.*,
            u.fullname as creator_name,
            GROUP_CONCAT(DISTINCT ad.item_type ORDER BY ad.item_type SEPARATOR ', ') as item_types
        FROM adjustment_header adj
        LEFT JOIN users u ON adj.created_by = u.id
        LEFT JOIN adjustment_details ad ON adj.id = ad.adjustment_header_id
        GROUP BY adj.id
        ORDER BY adj.doc_date DESC, adj.id DESC
        ";
        return $this->db->query($sql)->result();
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
                    WHEN ad.item_type = 'scrap' THEN ms.scrap_name
                    ELSE 'UNKNOWN'
                END as item_name,
                CASE
                    WHEN ad.item_type = 'material' THEN m.satuan
                    WHEN ad.item_type = 'semifinished' THEN sf.satuan
                    WHEN ad.item_type = 'finished_goods' THEN fg.satuan
                    WHEN ad.item_type = 'inventory' THEN i.satuan
                    WHEN ad.item_type = 'scrap' THEN ms.unit
                    ELSE 'PCS'
                END as item_unit,
                soh.available_qty as batch_qty
            FROM adjustment_details ad
            LEFT JOIN materials m ON ad.item_id = m.id AND ad.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON ad.item_id = sf.id AND ad.item_type = 'semifinished'
            LEFT JOIN finished_goods fg ON ad.item_id = fg.id AND ad.item_type = 'finished_goods'
            LEFT JOIN inventories i ON ad.item_id = i.id AND ad.item_type = 'inventory'
            LEFT JOIN machines mc ON ad.item_id = mc.id AND ad.item_type = 'machine'
            LEFT JOIN master_scrap ms ON ad.item_id = ms.id AND ad.item_type = 'scrap'
            LEFT JOIN stock_on_hand soh ON ad.stock_on_hand_id = soh.id AND ad.item_type = 'material'
            WHERE ad.adjustment_header_id = ?
        ";
        return $this->db->query($sql, [$header_id])->result();
    }

    public function get_new_doc_number()
    {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('stock_adjustment', 'adjustment_header', 'doc_no');
    }
    
    // Menyimpan data ke DB (Status Draft)
    public function insert_adjustment($header_data, $details_data)
    {
        // Hapus grn_header_id dari data jika kolom belum ada di DB server
        if (!$this->_has_grn_col()) {
            unset($header_data['grn_header_id']);
        }

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
        // Hapus grn_header_id dari data jika kolom belum ada di DB server
        if (!$this->_has_grn_col()) {
            unset($header_data['grn_header_id']);
        }

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
            // Handle scrap type khusus - tidak pakai stock_ledger
            if ($item->item_type === 'scrap') {
                $this->process_scrap_adjustment($item, $header, $date, $user_id);
                continue; // Skip stock_ledger processing untuk scrap
            }
            
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
                    
                    if (!empty($stock_on_hand_id)) {
                        // Standard case: ada batch yang dipilih
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
                        // Special case: Opname adjustment tanpa batch (allow negative stock)
                        // Cari nilai unit terakhir untuk material
                        $last_ledger = $this->get_latest_ledger_entry($item->item_id, $item->item_type);
                        $unit_value = $last_ledger ? $last_ledger->unit_value : 0;
                        // stock_on_hand_id tetap NULL - tidak update batch
                    }
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
     * Process scrap adjustment - create scrap_in or scrap_out transaction
     */
    private function process_scrap_adjustment($item, $header, $date, $user_id)
    {
        if ($item->adjustment_type == 'IN') {
            // Create scrap_in transaction
            $scrap_in_header = [
                'doc_no' => 'SIN-ADJ-' . $header->doc_no,
                'doc_date' => date('Y-m-d', strtotime($date)),
                'work_order_id' => NULL,
                'notes' => 'Adjustment IN: ' . $header->notes,
                'status' => 'Confirmed',
                'created_by' => $user_id,
                'confirmed_by' => $user_id,
                'confirmed_at' => $date
            ];
            $this->db->insert('scrap_in_header', $scrap_in_header);
            $scrap_in_id = $this->db->insert_id();
            
            $scrap_in_detail = [
                'header_id' => $scrap_in_id,
                'scrap_id' => $item->item_id,
                'qty' => $item->qty
            ];
            $this->db->insert('scrap_in_detail', $scrap_in_detail);
            
        } else { // OUT
            // Create scrap_out transaction
            $scrap_out_header = [
                'doc_no' => 'SOUT-ADJ-' . $header->doc_no,
                'doc_date' => date('Y-m-d', strtotime($date)),
                'bc_type_code' => NULL,
                'bc_number' => NULL,
                'bc_date' => NULL,
                'destination' => 'Adjustment OUT',
                'notes' => 'Adjustment OUT: ' . $header->notes,
                'status' => 'Confirmed',
                'created_by' => $user_id,
                'created_at' => $date,
                'confirmed_by' => $user_id,
                'confirmed_at' => $date
            ];
            $this->db->insert('scrap_out_header', $scrap_out_header);
            $scrap_out_id = $this->db->insert_id();
            
            $scrap_out_detail = [
                'header_id' => $scrap_out_id,
                'scrap_id' => $item->item_id,
                'qty' => $item->qty,
                'notes' => 'Adjustment OUT from ' . $header->doc_no,
                'destination' => 'Adjustment OUT',
                'sales_value_per_unit' => 0
            ];
            $this->db->insert('scrap_out_detail', $scrap_out_detail);
        }
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
            case 'scrap':
                return $this->db->select('id, scrap_code as code, scrap_name as name, unit')
                                ->where('status', 'active')->get('master_scrap')->result();
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
        // Untuk scrap, gunakan perhitungan khusus dari scrap_in dan scrap_out
        if ($item_type === 'scrap') {
            $sql = "
                SELECT (
                    IFNULL((
                        SELECT SUM(d_in.qty)
                        FROM scrap_in_detail d_in
                        JOIN scrap_in_header h_in ON h_in.id = d_in.header_id
                        WHERE d_in.scrap_id = ?
                        AND h_in.status = 'Confirmed'
                    ), 0)
                    -
                    IFNULL((
                        SELECT SUM(d_out.qty)
                        FROM scrap_out_detail d_out
                        JOIN scrap_out_header h_out ON h_out.id = d_out.header_id
                        WHERE d_out.scrap_id = ?
                        AND h_out.status = 'Confirmed'
                    ), 0)
                ) AS current_balance
            ";
            $result = $this->db->query($sql, [$item_id, $item_id])->row();
            return $result ? (float) $result->current_balance : 0;
        }
        
        // Untuk tipe lainnya, gunakan stock_ledger
        $entry = $this->get_latest_ledger_entry($item_id, $item_type);
        return $entry ? (float) $entry->balance : 0;
    }

    /**
     * ===== METODE BARU: GRN LOOKUP & DOCUMENT MANAGEMENT =====
     */

    /**
     * Ambil info satu GRN untuk ditampilkan di Select2 (pre-selected)
     */
    public function get_grn_info_for_select($grn_id)
    {
        $sql = "
            SELECT gh.id, gh.grn_number,
                   COALESCE(s.nama_supplier, s_mir.nama_supplier, s_po.nama_supplier) AS nama_supplier
            FROM grn_header gh
            LEFT JOIN suppliers s ON gh.supplier_id = s.id
            LEFT JOIN grn_details gd ON gh.id = gd.grn_header_id
            LEFT JOIN mir_header mh ON gd.source_doc_type = 'MIR' AND gd.source_doc_header_id = mh.id
            LEFT JOIN suppliers s_mir ON mh.supplier_id = s_mir.id
            LEFT JOIN po_header ph ON gd.source_doc_type = 'PO' AND gd.source_doc_header_id = ph.id
            LEFT JOIN suppliers s_po ON ph.supplier_id = s_po.id
            WHERE gh.id = ?
            GROUP BY gh.id
            LIMIT 1
        ";
        return $this->db->query($sql, [$grn_id])->row();
    }

    /**
     * Cari GRN berdasarkan pencarian Select2 (filter berdasarkan grn_number, supplier, item)
     */
    public function search_grn_for_adjustment($search_term = '')
    {
        $this->db->select('
            grn.id,
            grn.grn_number,
            grn.grn_date,
            COALESCE(s.nama_supplier, s_mir.nama_supplier, s_po.nama_supplier) as nama_supplier,
            GROUP_CONCAT(DISTINCT CONCAT(m.kode_material, " - ", m.nama_material) SEPARATOR ", ") as items_desc
        ');
        $this->db->from('grn_header grn');
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');
        $this->db->join('grn_details gd', 'grn.id = gd.grn_header_id', 'left');
        $this->db->join('materials m', 'gd.item_id = m.id AND gd.item_type = "material"', 'left');
        // JOIN MIR untuk supplier fallback (GRN dari MIR tidak punya supplier_id di grn_header)
        $this->db->join('mir_header mh', 'gd.source_doc_type = "MIR" AND gd.source_doc_header_id = mh.id', 'left');
        $this->db->join('suppliers s_mir', 'mh.supplier_id = s_mir.id', 'left');
        // JOIN PO untuk supplier fallback
        $this->db->join('po_header ph', 'gd.source_doc_type = "PO" AND gd.source_doc_header_id = ph.id', 'left');
        $this->db->join('suppliers s_po', 'ph.supplier_id = s_po.id', 'left');
        
        // Filter GRN yang sudah confirmed/verified
        $this->db->where('grn.status', 'Completed');
        
        if (!empty($search_term)) {
            $this->db->group_start();
            $this->db->like('grn.grn_number', $search_term);
            $this->db->or_like('s.nama_supplier', $search_term);
            $this->db->or_like('s_mir.nama_supplier', $search_term);
            $this->db->or_like('s_po.nama_supplier', $search_term);
            $this->db->or_like('m.kode_material', $search_term);
            $this->db->or_like('m.nama_material', $search_term);
            $this->db->group_end();
        }
        
        $this->db->group_by('grn.id');
        $this->db->order_by('grn.grn_date', 'DESC');
        $this->db->limit(20);
        
        return $this->db->get()->result();
    }

    /**
     * Ambil detail GRN untuk dipilih di form adjustment
     */
    public function get_grn_details_for_adjustment($grn_id)
    {
        $sql = "
            SELECT 
                gd.id as grn_detail_id,
                gd.item_id,
                gd.item_type,
                gd.receive_qty,
                CASE 
                    WHEN gd.item_type = 'material' THEN m.kode_material
                    WHEN gd.item_type = 'semifinished' THEN sf.kode_barang
                    WHEN gd.item_type = 'machine' THEN mc.kode_mesin
                    WHEN gd.item_type = 'finished_goods' THEN fg.kode_barang
                    ELSE 'N/A'
                END as item_code,
                CASE 
                    WHEN gd.item_type = 'material' THEN m.nama_material
                    WHEN gd.item_type = 'semifinished' THEN sf.nama_barang
                    WHEN gd.item_type = 'machine' THEN mc.nama_mesin
                    WHEN gd.item_type = 'finished_goods' THEN fg.nama_barang
                    ELSE 'Unknown'
                END as item_name,
                CASE 
                    WHEN gd.item_type = 'material' THEN m.satuan
                    WHEN gd.item_type = 'semifinished' THEN sf.satuan
                    WHEN gd.item_type = 'machine' THEN mc.satuan
                    WHEN gd.item_type = 'finished_goods' THEN fg.satuan
                    ELSE 'PCS'
                END as item_unit,
                gd.unit_value
            FROM grn_details gd
            LEFT JOIN materials m ON gd.item_id = m.id AND gd.item_type = 'material'
            LEFT JOIN semifinished_goods sf ON gd.item_id = sf.id AND gd.item_type = 'semifinished'
            LEFT JOIN machines mc ON gd.item_id = mc.id AND gd.item_type = 'machine'
            LEFT JOIN finished_goods fg ON gd.item_id = fg.id AND gd.item_type = 'finished_goods'
            WHERE gd.grn_header_id = ?
            ORDER BY gd.id ASC
        ";
        return $this->db->query($sql, [$grn_id])->result();
    }

    /**
     * Simpan dokumen upload
     */
    public function save_document($adjustment_id, $file_name, $file_path, $file_type, $file_size, $user_id, $description = '')
    {
        $data = [
            'adjustment_header_id' => $adjustment_id,
            'file_name' => $file_name,
            'file_path' => $file_path,
            'file_type' => $file_type,
            'file_size' => $file_size,
            'uploaded_by' => $user_id,
            'description' => $description,
            'uploaded_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->insert('adjustment_documents', $data) ? $this->db->insert_id() : FALSE;
    }

    /**
     * Ambil dokumen-dokumen yang terkait dengan adjustment
     */
    public function get_adjustment_documents($adjustment_id)
    {
        $this->db->select('ad.*, u.fullname as uploaded_by_name');
        $this->db->from('adjustment_documents ad');
        $this->db->join('users u', 'ad.uploaded_by = u.id', 'left');
        $this->db->where('ad.adjustment_header_id', $adjustment_id);
        $this->db->order_by('ad.uploaded_at', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Ambil satu dokumen berdasarkan ID
     */
    public function get_document_by_id($document_id)
    {
        return $this->db->get_where('adjustment_documents', ['id' => $document_id])->row();
    }

    /**
     * Hapus dokumen
     */
    public function delete_document($document_id)
    {
        $document = $this->get_document_by_id($document_id);
        if ($document && file_exists($document->file_path)) {
            unlink($document->file_path);
        }
        return $this->db->delete('adjustment_documents', ['id' => $document_id]);
    }
}