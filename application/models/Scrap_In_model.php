<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Scrap_In_model extends CI_Model {

    // --- 🔽 TAMBAHKAN BLOK INI 🔽 ---
    public function __construct() {
        parent::__construct();
        // Load model Stock_ledger_model agar bisa digunakan di model ini
        $this->load->model('Stock_ledger_model'); 
    }
    // --- AKHIR BLOK TAMBAHAN ---


    // ... (fungsi generate_doc_no, get_work_orders, get_master_scrap tetap sama) ...
    public function generate_doc_no() {
        $prefix = 'SCR-IN/' . date('Ym') . '/';
        $this->db->like('doc_no', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get('scrap_in_header', 1)->row();

        // Ambil nomor terakhir (4 digit di akhir)
        if ($last && preg_match('/(\d{4})$/', $last->doc_no, $m)) {
            $next = intval($m[1]) + 1;
        } else {
            $next = 1;
        }

        // Loop anti duplikasi — kalau nomor sudah dipakai, increment terus
        do {
            $new_no = $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
            $exists = $this->db->where('doc_no', $new_no)->count_all_results('scrap_in_header');
            if ($exists > 0) {
                $next++;
            } else {
                break;
            }
        } while (true);

        return $new_no;
    }


    public function get_work_orders() { // Mungkin tidak relevan lagi
        $this->db->select('id, wo_number');
        $this->db->from('wo_header');
        $this->db->where_in('status', ['In Progress', 'Active', 'Completed']);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(100); 
        return $this->db->get()->result_array();
    }

    public function get_master_scrap() {
        $this->db->select('m.id, m.scrap_code, m.scrap_name, c.description as satuan_desc');
        $this->db->from('master_scrap m');
        $this->db->join('common_code c', "c.code = m.unit AND c.group_code = 'SATUAN'", 'left'); // m.unit (sesuai db Anda)
        $this->db->order_by('m.scrap_name', 'ASC');
        return $this->db->get()->result_array();
    }

    // --- REVISI: get_all() ---
    public function get_all($filters = []) { // Tambah parameter filter
        $this->db->select('h.*, u.fullname as created_by_name'); // Hapus join WO
        $this->db->from('scrap_in_header h');
        $this->db->join('users u', 'u.id = h.created_by', 'left');
        
        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('h.doc_no', $term);
            $this->db->group_end();
        }

        $this->db->order_by('h.doc_date', 'DESC');
        $this->db->order_by('h.id', 'DESC'); 
        return $this->db->get()->result();
    }

    // --- REVISI: get_by_id() ---
    public function get_by_id($id) {
        $data = [];
        // Header
        $this->db->select('h.*, u.fullname as created_by_name, u_conf.fullname as confirmed_by_name');
        $this->db->from('scrap_in_header h');
        $this->db->join('users u', 'u.id = h.created_by', 'left');
        $this->db->join('users u_conf', 'u_conf.id = h.confirmed_by', 'left');
        $this->db->where('h.id', $id);
        $data['header'] = $this->db->get()->row();
        if (empty($data['header'])) return null;

        // Details
        $this->db->select('d.*, m.scrap_code, m.scrap_name, c.description as satuan_desc');
        $this->db->from('scrap_in_detail d');
        $this->db->join('master_scrap m', 'm.id = d.scrap_id');
        $this->db->join('common_code c', "c.code = m.unit AND c.group_code = 'SATUAN'", 'left'); // m.unit
        $this->db->where('d.header_id', $id);
        $details = $this->db->get()->result_array(); 
        
        // Ambil Sources untuk setiap detail
        if(!empty($details)){
            $detail_ids = array_column($details,'id');
            $this->db->select('
                sds.scrap_in_detail_id, sds.production_output_id, sds.qty_collected,
                po.production_header_id, ph.production_number, wo.wo_number
            ');
            $this->db->from('scrap_in_detail_sources sds');
            $this->db->join('production_outputs po', 'sds.production_output_id = po.id', 'left');
            $this->db->join('production_header ph', 'po.production_header_id = ph.id', 'left');
            $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
            $this->db->where_in('sds.scrap_in_detail_id', $detail_ids);
            $sources_raw = $this->db->get()->result_array();
            
            $sources_grouped = [];
            foreach($sources_raw as $source){
                $sources_grouped[$source['scrap_in_detail_id']][] = $source;
            }
            
            foreach($details as &$detail){ 
                $detail['sources'] = $sources_grouped[$detail['id']] ?? [];
            }
        }
        
        $data['details'] = $details; 
        return $data;
    }
    
    // --- FUNGSI BARU: Ambil detail scrap by ID (untuk model) ---
    public function get_production_scrap_details_by_ids($ids) {
       if (empty($ids) || !is_array($ids)) {
           return ['summary' => [], 'sources_map' => []];
       }

       $this->db->select('
           po.id as production_output_id,
           po.item_id as scrap_id,
           po.qty_scrap,
           ms.scrap_code,
           ms.scrap_name,
           cc.description as satuan_desc,
           ph.production_number,
           wo.wo_number
       ');
       $this->db->from('production_outputs po');
       $this->db->join('master_scrap ms', 'po.item_id = ms.id', 'inner');
       $this->db->join('common_code cc', 'ms.unit = cc.code AND cc.group_code = "SATUAN"', 'left');
       $this->db->join('production_header ph', 'po.production_header_id = ph.id', 'left');
       $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
       $this->db->where('po.item_type', 'scrap');
       $this->db->where('po.qty_scrap >', 0);
       $this->db->where_in('po.id', $ids); 

       $query = $this->db->get();
       $rows = $query->result_array();

       $summary = []; $sources_map = []; $summary_temp = []; 
       foreach ($rows as $row) {
           $scrap_id = $row['scrap_id'];
           $sources_map[$scrap_id][] = [
               'production_output_id' => $row['production_output_id'],
               'qty_scrap'            => $row['qty_scrap'] 
           ];
           if (!isset($summary_temp[$scrap_id])) {
               $summary_temp[$scrap_id] = [
                   'scrap_id'   => $scrap_id,
                   'total_qty' => 0
               ];
           }
           $summary_temp[$scrap_id]['total_qty'] += (float)$row['qty_scrap'];
       }
       $summary = array_values($summary_temp);
       return ['summary' => $summary, 'sources_map' => $sources_map];
    }

    // --- REVISI: get_uncollected_production_scrap() ---
    public function get_uncollected_production_scrap() {
        $this->db->select('
            po.id as production_output_id, 
            po.item_id as scrap_id, 
            po.qty_scrap, 
            ms.scrap_code, 
            ms.scrap_name, 
            cc.description as satuan_desc,
            ph.production_number, 
            wo.wo_number,
            po.stage_name
        ');
        $this->db->from('production_outputs po');
        $this->db->join('master_scrap ms', 'po.item_id = ms.id', 'inner'); // Join to master_scrap
        $this->db->join('common_code cc', 'ms.unit = cc.code AND cc.group_code = "SATUAN"', 'left');
        $this->db->join('production_header ph', 'po.production_header_id = ph.id', 'left'); 
        $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left'); 
        
        // Filter: item_type = 'scrap' dan qty_scrap > 0 (check scrap quantity, not produced quantity)
        $this->db->where('po.item_type', 'scrap');
        $this->db->where('po.qty_scrap >', 0);
        $this->db->where("NOT EXISTS (SELECT 1 FROM scrap_in_detail_sources sds WHERE sds.production_output_id = po.id)", NULL, FALSE);
        $this->db->order_by('po.id', 'ASC'); 
        
        $query = $this->db->get();
        return $query->result_array();
    }

    // --- REVISI: create_draft_from_collection() ---
    public function create_draft_from_collection($header_data, $details_summary, $source_prod_output_ids_map) {
        $this->db->trans_start();
        
        $this->db->insert('scrap_in_header', $header_data);
        $header_id = $this->db->insert_id();

        $detail_map = []; 
        $batch_details = [];
        foreach ($details_summary as $item) {
             $batch_details[] = [
                'header_id' => $header_id,
                'scrap_id' => $item['scrap_id'],
                'qty' => $item['total_qty'] 
            ];
        }
         if (!empty($batch_details)) {
            $this->db->insert_batch('scrap_in_detail', $batch_details);
            $inserted_details = $this->db->select('id, scrap_id')
                                       ->where('header_id', $header_id)
                                       ->get('scrap_in_detail')->result();
            foreach($inserted_details as $detail) {
                $detail_map[$detail->scrap_id] = $detail->id;
            }
        }

        $batch_sources = [];
        foreach ($source_prod_output_ids_map as $scrap_id => $sources) {
            if (isset($detail_map[$scrap_id])) {
                $scrap_in_detail_id = $detail_map[$scrap_id];
                foreach ($sources as $source) {
                    $batch_sources[] = [
                        'scrap_in_detail_id'   => $scrap_in_detail_id,
                        'production_output_id' => $source['production_output_id'],
                        'qty_collected'        => $source['qty_scrap'] 
                    ];
                }
            } else {
                 log_message('error',"Scrap In Collect: Gagal mapping scrap_id {$scrap_id} ke detail_id baru.");
            }
        }
        
        if (!empty($batch_sources)) {
            $this->db->insert_batch('scrap_in_detail_sources', $batch_sources);
        } else if (empty($detail_map)) {
             log_message('error',"Scrap In Collect: Tidak ada detail yang berhasil diinsert.");
             $this->db->trans_rollback(); 
             return false;
        }
        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? false : $header_id;
    }

    // --- REVISI: confirm_scrap_in() ---
    public function confirm_scrap_in($id, $user_id) {
        $this->db->trans_start();

        // 1️⃣ Validasi header
        $header = $this->db->select('status, doc_no')->where('id', $id)->get('scrap_in_header')->row();
        if (!$header || $header->status != 'Draft') {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Dokumen tidak valid atau sudah dikonfirmasi.'];
        }

        // 2️⃣ Ambil detail
        $details = $this->db->where('header_id', $id)->get('scrap_in_detail')->result();
        if (empty($details)) {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Tidak ada item detail untuk dikonfirmasi.'];
        }

        $item_type = 'scrap';
        $current_time = date('Y-m-d H:i:s');
        $batch_ledger_data = [];

        // 3️⃣ Loop setiap detail
        foreach ($details as $item) {
            if (empty($item->scrap_id) || (float)$item->qty <= 0) continue;

            $scrap_id = $item->scrap_id;
            $qty = (float)$item->qty;

            // 🔍 3a. Cek apakah sudah ada stok existing
            $existing_stock = $this->db->get_where('scrap_stock', ['scrap_id' => $scrap_id])->row();

            if ($existing_stock) {
                // ✅ Update qty existing
                $this->db->set('available_qty', 'available_qty + ' . $qty, FALSE)
                        ->where('id', $existing_stock->id)
                        ->update('scrap_stock');
                $stock_id = $existing_stock->id;
            } else {
                // ✅ Insert stok baru
                $this->db->insert('scrap_stock', [
                    'scrap_id'           => $scrap_id,
                    'scrap_in_detail_id' => $item->id,
                    'available_qty'      => $qty
                ]);
                $stock_id = $this->db->insert_id();
            }

            // 3b. Ledger
            $last_balance = $this->Stock_ledger_model->get_last_balance($scrap_id, $item_type);
            $batch_ledger_data[] = [
                'item_id'            => $scrap_id,
                'item_type'          => $item_type,
                'transaction_date'   => $current_time,
                'transaction_type'   => 'IN_SCRAP_WH',
                'transaction_id'     => $id,
                'source_doc_type'    => 'SCRAP_IN',
                'source_doc_id'      => $id,
                'qty_in'             => $qty,
                'qty_out'            => 0,
                'balance'            => $last_balance + $qty,
                'unit_value'         => 0,
                'stock_on_hand_id'   => $stock_id
            ];
        }

        // 4️⃣ Insert ledger
        if (!empty($batch_ledger_data)) {
            $this->db->insert_batch('stock_ledger', $batch_ledger_data);
        } else {
            $this->db->trans_rollback();
            return ['status' => false, 'message' => 'Tidak ada item scrap valid untuk dikonfirmasi.'];
        }

        // 5️⃣ Update status header
        $this->db->where('id', $id)->update('scrap_in_header', [
            'status' => 'Confirmed',
            'confirmed_by' => $user_id,
            'confirmed_at' => $current_time
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['status' => false, 'message' => 'Transaksi database gagal.'];
        }

        return ['status' => true, 'message' => 'Scrap berhasil dikonfirmasi dan stok diperbarui.'];
    }



    /**
     * REVISI: delete_draft()
     * Saat draft dihapus, kita juga harus menghapus link di production_outputs
     */
    public function delete_draft($id) {
        $this->db->trans_start();
        $header = $this->db->select('status')->where('id', $id)->get('scrap_in_header')->row();
        if (!$header) { return ['status' => false, 'message' => 'Data tidak ditemukan.']; }
        if ($header->status != 'Draft') { return ['status' => false, 'message' => 'Hanya status DRAFT yang bisa dihapus.']; }

        // 1. Cari detail ID yang terkait
        $details = $this->db->select('id')->where('header_id', $id)->get('scrap_in_detail')->result_array();
        
        if (!empty($details)) {
             $detail_ids = array_column($details, 'id');
             
            // 2. Cari production_output_id yang terhubung
             $sources = $this->db->select('production_output_id')
                                ->where_in('scrap_in_detail_id', $detail_ids)
                                ->get('scrap_in_detail_sources')->result_array();
                                
             if (!empty($sources)) {
                 $prod_output_ids = array_column($sources, 'production_output_id');
                 
                 // 3. Reset kolom di production_outputs (jangan hapus)
                 // (Kolom ini sudah dihapus di revisi DB sebelumnya, jadi langkah ini tidak perlu jika Anda ikut revisi itu)
                 // $this->db->where_in('id', $prod_output_ids)->update('production_outputs', ['scrap_in_detail_id' => NULL]);
                 
                 // Jika Anda menggunakan tabel scrap_in_detail_sources, tabel itu akan ikut terhapus
                 // oleh CASCADE saat scrap_in_header dihapus.
             }
        }
        
        // 4. Hapus header (detail & sources akan terhapus otomatis karena CASCADE)
        $this->db->where('id', $id)->delete('scrap_in_header'); 
        
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
             return ['status' => false, 'message' => 'Gagal menghapus draft.'];
        } else {
             return ['status' => true, 'message' => 'Draft berhasil dihapus.'];
        }
    }

} // End Class