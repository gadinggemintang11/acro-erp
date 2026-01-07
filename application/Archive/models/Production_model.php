<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Production_model extends CI_Model {

    private $table_header = 'production_header';
    private $table_inputs = 'production_inputs';
    private $table_outputs = 'production_outputs';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        // Load Stock_ledger_model HANYA jika benar-benar dibutuhkan di fungsi tertentu
        // $this->load->model('Stock_ledger_model');
    }

    // === HEADER ===
    public function get_all($filters = []) {
        $this->db->select('
            p.id, p.production_number, p.production_date, p.planned_qty, p.status,
            wo.wo_number, sc.sc_number, fg.nama_barang as fg_name
        ');
        $this->db->from($this->table_header . ' p');
        $this->db->join('wo_header wo', 'p.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'p.fg_id = fg.id', 'left');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start();
            $this->db->like('p.production_number', $term);
            $this->db->or_like('wo.wo_number', $term);
            $this->db->or_like('sc.sc_number', $term);
            $this->db->group_end();
        }

        $this->db->order_by('p.id', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : []; // Return array kosong jika gagal
    }

    public function get_header_by_id($id) {
         $this->db->select('
            p.*,
            wo.wo_number, sc.sc_number, fg.nama_barang as fg_name,
            creator.fullname as creator_name,
            confirmer.fullname as confirmer_name
        ');
        $this->db->from($this->table_header . ' p');
        $this->db->join('wo_header wo', 'p.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'p.fg_id = fg.id', 'left');
        $this->db->join('users creator', 'p.created_by = creator.id', 'left');
        $this->db->join('users confirmer', 'p.confirmed_by = confirmer.id', 'left');
        $this->db->where('p.id', $id);
        $query = $this->db->get();
         return $query ? $query->row() : null; // Return null jika tidak ditemukan
    }

    public function insert_header($data) {
        $insert = $this->db->insert($this->table_header, $data);
        return $insert ? $this->db->insert_id() : false; // Return false jika gagal
    }

    public function update_header($id, $data) {
        unset($data['status'], $data['confirmed_by'], $data['confirmed_at']);
        return $this->db->where('id', $id)->update($this->table_header, $data); // Akan return true/false
    }

    /**
     * Delete Header + Reversal Stok (Memanggil Stock_ledger_model)
     */
    public function delete_header($id) {
        $this->load->model('Stock_ledger_model');
        $this->db->trans_start();
        $inputs = $this->db->get_where($this->table_inputs, ['production_header_id' => $id])->result();
        $outputs = $this->db->get_where($this->table_outputs, ['production_header_id' => $id])->result();
        $now = date('Y-m-d H:i:s');

        foreach ($inputs as $input) {
            // ============ PERBAIKAN LOGIKA DELETE ============
            // Kita harus mereversal SEMUA wip_out, bukan hanya yg dari SOH
            // Pemakaian SFG (source_output_id) juga membuat ledger wip_out
            $ledger_reversal_type = '';
            if (!empty($input->stock_on_hand_id)) {
                $ledger_reversal_type = 'wip_in_reversal'; // Kembali ke SOH
            } else if (!empty($input->source_output_id)) {
                $ledger_reversal_type = 'wip_in_reversal_sfg'; // Kembali ke WIP SFG
            }

            if ($ledger_reversal_type) {
                $ledger_reversal = [
                    'item_id'           => $input->item_id,
                    'item_type'         => $input->item_type,
                    'transaction_date'  => $now,
                    'transaction_type'  => $ledger_reversal_type,
                    'transaction_id'    => $input->id,
                    'source_doc_type'   => 'PROD_DELETE',
                    'source_doc_id'     => $id,
                    'qty_in'            => $input->qty_used,
                    'qty_out'           => 0,
                    'stock_on_hand_id'  => $input->stock_on_hand_id // Akan NULL jika SFG, dan itu benar
                ];
                $this->Stock_ledger_model->insert($ledger_reversal);
            }
            // ============ AKHIR PERBAIKAN ============
        }

        foreach ($outputs as $output) {
            if ($output->item_type == 'semifinished' && $output->qty_produced > 0) {
                $ledger_reversal = [
                    'item_id'           => $output->item_id,
                    'item_type'         => $output->item_type,
                    'transaction_date'  => $now,
                    'transaction_type'  => 'wip_out_reversal',
                    'transaction_id'    => $output->id,
                    'source_doc_type'   => 'PROD_DELETE',
                    'source_doc_id'     => $id,
                    'qty_in'            => 0,
                    'qty_out'           => $output->qty_produced
                ];
                $this->Stock_ledger_model->insert($ledger_reversal);
            }
        }

        $this->db->where('production_header_id', $id)->delete($this->table_inputs);
        $this->db->where('production_header_id', $id)->delete($this->table_outputs);
        $this->db->where('id', $id)->delete($this->table_header);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Production_model->delete_header Gagal melakukan transaksi DB.');
            return false;
        }
        return true;
    }

    /**
     * Generate Nomor Produksi (Query ini HARUS cepat setelah index dibuat)
     */
    public function generate_prod_number() {
        $prefix = 'PROD/' . date('Ym') . '/';
        $this->db->select('production_number');
        $this->db->from($this->table_header);
        $this->db->like('production_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC'); // Index akan membantu ini
        $this->db->limit(1);
        $last = $this->db->get()->row();
        $new_num = $last ? (int) substr($last->production_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // === HELPERS ===
    public function search_open_wos($term) {
    // Escape term hanya jika tidak kosong
        $safe_term = !empty($term) ? $this->db->escape_like_str($term) : '';

        $this->db->select('wo.id, wo.wo_number, fg.nama_barang as fg_name');
        $this->db->from('wo_header wo');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->join('production_header p', 'wo.id = p.wo_header_id', 'left'); 
        $this->db->where('wo.status', 'In Progress');
        $this->db->where('p.id IS NULL'); 

        if (!empty($safe_term)) {
            $this->db->group_start();
            $this->db->like('wo.wo_number', $safe_term);
            $this->db->or_like('fg.nama_barang', $safe_term);
            $this->db->group_end();
        }
        
        $this->db->limit(10); 
        $this->db->order_by('wo.wo_number', 'ASC'); 

        $query = $this->db->get();
        
        // --- PERBAIKAN UTAMA ADA DI SINI ---
        if (!$query) {
            log_message('error', 'Production_model->search_open_wos DB Error: ' . print_r($this->db->error(), true));
            return []; // Selalu kembalikan array kosong jika query gagal
        }
        
        // Jika query berhasil tapi tidak ada hasil, result() akan mengembalikan array kosong
        return $query->result(); 
        // --- AKHIR PERBAIKAN ---
    }

    // application/models/Production_model.php

    public function get_wo_details_for_prod($wo_id) {
        // --- AWAL PERBAIKAN ---
        // Hapus komentar dari dalam string select
        $this->db->select('
            wo.id,
            wo.planned_qty,
            sc.sc_number,
            fg.id as fg_id,
            fg.nama_barang as fg_name
        ');
        // --- AKHIR PERBAIKAN ---

        // Tabel utama adalah wo_header, alias 'wo'
        $this->db->from('wo_header wo');

        // Join ke tabel sales_contracts berdasarkan ID Sales Contract di WO
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');

        // Join ke tabel finished_goods berdasarkan ID Barang Jadi di WO
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');

        // Filter berdasarkan ID Work Order yang diberikan
        $this->db->where('wo.id', $wo_id);

        // Eksekusi query dan kembalikan satu baris hasil sebagai array asosiatif
        $query = $this->db->get();

        // Tambahkan pengecekan jika query gagal atau tidak ada hasil
        if (!$query) {
            log_message('error', 'Production_model->get_wo_details_for_prod DB Error: ' . print_r($this->db->error(), true));
            return null; // Kembalikan null jika query error
        }

        return $query->row_array(); // Kembalikan hasil (bisa null jika WO tidak ditemukan)
    }

    // === STAGES ===

    /**
     * Mengambil material WIP per BATCH dan menghitung sisa stok
     */
    public function get_wip_materials_with_remaining($wo_id) {
         $this->load->model('Stock_ledger_model'); // Load model di sini

         $this->db->reset_query();
         // 1. Dapatkan semua batch yg PERNAH di-issue (production_out) untuk WO ini
         $this->db->select("
             sl.stock_on_hand_id,
             SUM(sl.qty_out) as total_issued_to_wip,
             soh.item_id, soh.item_type, soh.unit_value as cost_from_batch,
             grn.grn_number,
             CASE WHEN soh.item_type = 'material' THEN m.kode_material ELSE sf.kode_barang END as item_code,
             CASE WHEN soh.item_type = 'material' THEN m.nama_material ELSE sf.nama_barang END as item_name,
             CASE WHEN soh.item_type = 'material' THEN m.satuan ELSE sf.satuan END as item_unit
         ", FALSE);
         $this->db->from('stock_ledger sl');
         $this->db->join('stock_on_hand soh', 'sl.stock_on_hand_id = soh.id', 'inner');
         $this->db->join('materials m', 'soh.item_id = m.id AND soh.item_type = "material"', 'left');
         $this->db->join('semifinished_goods sf', 'soh.item_id = sf.id AND soh.item_type = "semifinished"', 'left');
         $this->db->join('grn_header grn', 'soh.grn_header_id = grn.id', 'left');
         $this->db->where('sl.transaction_type', 'production_out');
         $this->db->where('sl.source_doc_id', $wo_id);
         $this->db->where('soh.item_id IS NOT NULL'); // Ensure item_id is not null
         $this->db->where('soh.item_id != \'\''); // Ensure item_id is not empty
         $this->db->group_by([
             'sl.stock_on_hand_id', 'soh.item_id', 'soh.item_type', 'soh.unit_value', 'grn.grn_number',
             'item_code', 'item_name', 'item_unit' // Group by alias
         ]);
         $issued_batches = $this->db->get()->result();

         if (empty($issued_batches)) { return []; }

         // 2. Hitung total pemakaian & retur untuk setiap batch
         foreach ($issued_batches as $batch) {
             $stock_on_hand_id = $batch->stock_on_hand_id;

             // Total terpakai (wip_out)
             $this->db->reset_query();
             $this->db->select_sum('qty_out', 'total_used'); // Alias
             $this->db->where('stock_on_hand_id', $stock_on_hand_id);
             $this->db->where('transaction_type', 'wip_out');
             $usage = $this->db->get('stock_ledger')->row();
             $batch->total_used = $usage ? (float)$usage->total_used : 0;

             // Total retur (IN_RETURN_WIP)
             $this->db->reset_query();
             $this->db->select_sum('qty_in', 'total_returned'); // Alias
             $this->db->where('stock_on_hand_id', $stock_on_hand_id);
             $this->db->where('transaction_type', 'IN_RETURN_WIP');
             $return_info = $this->db->get('stock_ledger')->row();
             $batch->total_returned = $return_info ? (float)$return_info->total_returned : 0;

             // 3. Hitung sisa
             $batch->remaining_qty = round((float)$batch->total_issued_to_wip - $batch->total_used + $batch->total_returned, 5);
         }

         // 4. Filter sisa > 0
         return array_filter($issued_batches, function($b) {
             return $b->remaining_qty > 0.00001;
         });
    }

    /**
     * Simpan pemakaian material (Input)
     * Akan memanggil Stock_ledger_model HANYA jika sumbernya dari SOH
     */
    public function save_material_usage($data) {
        if (empty($data['production_header_id']) || empty($data['item_id']) || empty($data['item_type']) ||
            !isset($data['qty_used']) || $data['qty_used'] <= 0 || empty($data['stage_name'])) {
                log_message('error', 'Production: save_material_usage gagal, data tidak lengkap. Data: ' . json_encode($data));
                return false;
        }
        if (empty($data['stock_on_hand_id']) && empty($data['source_output_id'])) {
             log_message('error', 'Production: save_material_usage gagal, tidak ada sumber (SOH ID atau Source Output ID). Data: ' . json_encode($data));
             return false;
        }

        unset($data['mr_detail_id']);
        $this->db->trans_start();

        // 1. Insert production_inputs
        $insert_input = $this->db->insert($this->table_inputs, $data);
        if (!$insert_input) {
            log_message('error', 'Production: Gagal insert production_inputs! DB Error: ' . print_r($this->db->error(), true) . ' Data: ' . json_encode($data));
            $this->db->trans_rollback();
            return false;
        }
        $input_id = $this->db->insert_id();

        // 2. SELALU Buat ledger 'wip_out'
        $this->load->model('Stock_ledger_model');
        
        $ledger_data = [
            'item_id'           => $data['item_id'],
            'item_type'         => $data['item_type'],
            'transaction_date'  => date('Y-m-d H:i:s'),
            'transaction_type'  => 'wip_out', // Pemakaian WIP
            'transaction_id'    => $input_id,
            'source_doc_type'   => 'PROD',
            'source_doc_id'     => $data['production_header_id'],
            'qty_in'            => 0,
            'qty_out'           => (float)$data['qty_used'],
            'unit_value'        => 0.00, // Default cost
            'stock_on_hand_id'  => NULL    // Default NULL
        ];

        // 3. Ambil cost HANYA jika sumbernya dari SOH (Material)
        if (!empty($data['stock_on_hand_id']))
        {
            $soh_cost = $this->db->select('unit_value')->where('id', $data['stock_on_hand_id'])->get('stock_on_hand')->row();
            if($soh_cost) { 
                $ledger_data['unit_value'] = (float)$soh_cost->unit_value; 
            }
            $ledger_data['stock_on_hand_id'] = $data['stock_on_hand_id'];
        }
        // Jika sumbernya dari source_output_id (Semifinished),
        // maka unit_value tetap 0 dan stock_on_hand_id tetap NULL. Ini sudah benar.

        // 4. Masukkan ke ledger
        if (!$this->Stock_ledger_model->insert($ledger_data)) {
            log_message('error', 'Production: Gagal insert stock ledger untuk wip_out. Data: ' . json_encode($ledger_data));
            $this->db->trans_rollback();
            return false;
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Production_model->save_material_usage Gagal melakukan transaksi DB.');
            return false;
        }
        return true;
    }

    /**
     * Simpan hasil produksi (Output)
     * Akan memanggil Stock_ledger_model HANYA jika outputnya semifinished
     */
    public function save_production_output($data) {
        $qty_produced = (float)($data['qty_produced'] ?? 0);
        $qty_scrap = (float)($data['qty_scrap'] ?? 0);

        // Validasi data dasar
        if (!isset($data['production_header_id']) || $data['production_header_id'] === '' ||
            !isset($data['item_id'])          || $data['item_id'] === ''          || $data['item_id'] === null ||
            !isset($data['item_type'])        || $data['item_type'] === ''        ||
            !isset($data['stage_name'])       || $data['stage_name'] === '' ) {
                log_message('error', 'Production: save_production_output gagal, data header/item/stage kosong atau null. Data: '.json_encode($data));
                return false;
        }
        // Validasi Qty
        if (($qty_produced <= 0) && ($qty_scrap <= 0)) {
             log_message('error', 'Production: save_production_output gagal, qty 0. Data: '.json_encode($data));
             return false;
        }

        $this->db->trans_start();

        // 1. Insert production_outputs
        $insert_output = $this->db->insert($this->table_outputs, $data);
        if (!$insert_output) {
           log_message('error', 'Production: Gagal insert production_outputs! DB Error: ' . print_r($this->db->error(), true) . ' Data: ' . json_encode($data));
           $this->db->trans_rollback();
           return false;
        }
        $output_id = $this->db->insert_id();

        // 2. Buat ledger HANYA jika outputnya semifinished
        if ($data['item_type'] == 'semifinished' && $qty_produced > 0) {
            $this->load->model('Stock_ledger_model'); // Load model di sini

            // TODO: Kalkulasi Cost untuk Semifinished (jika diperlukan)
            $unit_cost = 0.00;

            $ledger_data = [
                'item_id'           => $data['item_id'],
                'item_type'         => $data['item_type'],
                'transaction_date'  => date('Y-m-d H:i:s'),
                'transaction_type'  => 'wip_in', // Hasil produksi masuk WIP
                'transaction_id'    => $output_id,
                'source_doc_type'   => 'PROD',
                'source_doc_id'     => $data['production_header_id'],
                'qty_in'            => $qty_produced,
                'qty_out'           => 0,
                'unit_value'        => $unit_cost
                // Balance akan dihitung oleh Stock_ledger_model->insert()
            ];

            if (!$this->Stock_ledger_model->insert($ledger_data)) {
                 log_message('error', 'Production: Gagal insert stock ledger untuk wip_in. Data: ' . json_encode($ledger_data));
                 $this->db->trans_rollback();
                 return false;
            }
        }
        // Jika output FG atau Scrap, tidak ada ledger dibuat di sini.

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            log_message('error', 'Production_model->save_production_output Gagal melakukan transaksi DB.');
            return false;
        }
        
        // Cek apakah WO sudah selesai dan update statusnya
        $this->check_and_update_wo_status($data['production_header_id']);
        
        return true;
    }
    
    /**
     * Cek apakah WO sudah selesai berdasarkan qty output finishing
     * Update status WO menjadi 'Finished' jika qty sudah terpenuhi
     */
    private function check_and_update_wo_status($production_header_id) {
        // Ambil info production header dan WO
        $this->db->select('ph.wo_header_id, wo.planned_qty');
        $this->db->from('production_header ph');
        $this->db->join('wo_header wo', 'ph.wo_header_id = wo.id', 'left');
        $this->db->where('ph.id', $production_header_id);
        $prod_info = $this->db->get()->row();
        
        if (!$prod_info || !$prod_info->wo_header_id) {
            return false;
        }
        
        $wo_id = $prod_info->wo_header_id;
        $qty_plan = (float)$prod_info->planned_qty;
        
        // Hitung total qty finished goods dari stage finishing untuk WO ini
        $this->db->select('COALESCE(SUM(po.qty_produced), 0) as total_fg_produced');
        $this->db->from('production_outputs po');
        $this->db->join('production_header ph', 'po.production_header_id = ph.id');
        $this->db->where('ph.wo_header_id', $wo_id);
        $this->db->where('po.stage_name', 'finishing');
        $this->db->where('po.item_type', 'finished_goods');
        $result = $this->db->get()->row();
        
        $total_produced = (float)($result->total_fg_produced ?? 0);
        
        // Jika qty sudah terpenuhi, update status WO menjadi Finished
        if ($total_produced >= $qty_plan) {
            $this->load->model('Wo_model');
            $this->Wo_model->update_status($wo_id, 'Finished');
            log_message('info', "WO ID {$wo_id} status updated to Finished. Produced: {$total_produced}, Plan: {$qty_plan}");
        }
        
        return true;
    }
    public function get_production_inputs_for_trace($prod_id, $stage) {
        $this->db->reset_query();
        $this->db->select("
            pi.qty_used, pi.item_id, pi.item_type,
            pi.stock_on_hand_id, /* Kunci traceability */
            
            /* Info Item (bisa material atau semifinished) */
            CASE 
                WHEN pi.item_type = 'material' THEN m.kode_material
                WHEN pi.item_type = 'semifinished' THEN sf.kode_barang
                ELSE NULL
            END AS item_code,
            CASE 
                WHEN pi.item_type = 'material' THEN m.nama_material
                WHEN pi.item_type = 'semifinished' THEN sf.nama_barang
                ELSE NULL
            END AS item_name,
            CASE 
                WHEN pi.item_type = 'material' THEN m.satuan
                WHEN pi.item_type = 'semifinished' THEN sf.satuan
                ELSE NULL
            END AS item_unit,

       
            grn.grn_number,
            grn.bc_type_code,
            grn.bc_number,
            grn.bc_date,
            s.nama_supplier
        ", false);

        $this->db->from($this->table_inputs . ' pi');
        // Join ke master item
        $this->db->join('materials m', 'pi.item_id = m.id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'pi.item_id = sf.id AND pi.item_type = "semifinished"', 'left');
        
        // Join ke batch (SOH) untuk traceability
        $this->db->join('stock_on_hand soh', 'pi.stock_on_hand_id = soh.id', 'left');
        // Join ke GRN & Supplier (dari SOH)
        $this->db->join('grn_header grn', 'soh.grn_header_id = grn.id', 'left');
        $this->db->join('suppliers s', 'grn.supplier_id = s.id', 'left');

        $this->db->where('pi.production_header_id', $prod_id);
        $this->db->where('pi.stage_name', $stage);
        $this->db->order_by('pi.id', 'ASC');

        $query = $this->db->get();
        return $query ? $query->result() : [];
    }
    public function search_semifinished_goods($term) {
        $term = $this->db->escape_like_str($term);
        $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type");
        $this->db->group_start()->like('kode_barang', $term)->or_like('nama_barang', $term)->group_end();
        $this->db->where('status', 'active');
        $this->db->limit(10);
        return $this->db->get('semifinished_goods')->result();
    }

    public function search_scrap_items($term) {
        $term = $this->db->escape_like_str($term);
        $this->db->select("id, scrap_code as code, scrap_name as name, 'scrap' as type, unit");
        $this->db->group_start()->like('scrap_code', $term)->or_like('scrap_name', $term)->group_end();
        $this->db->where('status', 'active');
        $this->db->limit(10);
        return $this->db->get('master_scrap')->result();
    }

    
    public function get_production_trace_details($prod_id, $stage)
    {
        $data = [];
        $this->db->reset_query();

        // === 1. Ambil Info Header (FG, WO, SC) ===
        $data['header_info'] = $this->get_header_by_id($prod_id);

        // === 2. Ambil Material yang digunakan di stage itu + Asal BC ===
        $this->db->select("
            pi.id AS input_id,
            pi.item_id, 
            pi.item_type,
            pi.qty_used,
            pi.stage_name,

            CASE 
                WHEN pi.item_type = 'material' THEN m.kode_material
                WHEN pi.item_type = 'semifinished' THEN sf.kode_barang
                ELSE NULL
            END AS item_code,

            CASE 
                WHEN pi.item_type = 'material' THEN m.nama_material
                WHEN pi.item_type = 'semifinished' THEN sf.nama_barang
                ELSE NULL
            END AS item_name,

            CASE 
                WHEN pi.item_type = 'material' THEN m.satuan
                WHEN pi.item_type = 'semifinished' THEN sf.satuan
                ELSE NULL
            END AS item_unit,

            /* Asal MR dan GRN */
            mrh.mr_number,
            grn.grn_number,
            grn.bc_type_code,
            grn.bc_number,
            grn.bc_date,
            sup.nama_supplier
        ", false);

        $this->db->from('production_inputs pi');
        $this->db->join('materials m', 'pi.item_id = m.id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'pi.item_id = sf.id AND pi.item_type = "semifinished"', 'left');

        $this->db->join('mr_details mrd', 'pi.mr_detail_id = mrd.id', 'left');
        $this->db->join('mr_header mrh', 'mrd.mr_header_id = mrh.id', 'left');
        $this->db->join('grn_details grd', 'mrd.source_grn_detail_id = grd.id', 'left');
        $this->db->join('grn_header grn', 'grd.grn_header_id = grn.id', 'left');
        $this->db->join('suppliers sup', 'grn.supplier_id = sup.id', 'left');

        $this->db->where('pi.production_header_id', $prod_id);
        $this->db->where('pi.stage_name', $stage);
        $this->db->order_by('pi.id', 'ASC');

        $materials = $this->db->get()->result();

        $data['materials_used'] = $materials;
        return $data;
    }

    public function get_production_inputs($prod_id, $stage) {
        $this->db->select('
            pi.*, 
            CASE 
                WHEN pi.item_type = "material" THEN m.nama_material 
                WHEN pi.item_type = "semifinished" THEN sf_item.nama_barang 
            END as item_name,
            
            
            mr_h.mr_number,
            po_source.stage_name as source_output_stage,
            CASE 
                 WHEN po_source.item_type = "semifinished" THEN sf_source.nama_barang 
            END as source_output_item_name

        ', FALSE);
        $this->db->from($this->table_inputs . ' pi');
        // Join untuk nama item yg dipakai
        $this->db->join('materials m', 'pi.item_id = m.id AND pi.item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf_item', 'pi.item_id = sf_item.id AND pi.item_type = "semifinished"', 'left');
        // Join untuk info sumber (jika dari MR)
        $this->db->join('mr_details mr_d', 'pi.mr_detail_id = mr_d.id', 'left');
        $this->db->join('mr_header mr_h', 'mr_d.mr_header_id = mr_h.id', 'left');
        // Join untuk info sumber (jika dari output stage lain)
        $this->db->join($this->table_outputs . ' po_source', 'pi.source_output_id = po_source.id', 'left');
        $this->db->join('semifinished_goods sf_source', 'po_source.item_id = sf_source.id AND po_source.item_type = "semifinished"', 'left');

        $this->db->where('pi.production_header_id', $prod_id);
        $this->db->where('pi.stage_name', $stage); // Filter berdasarkan stage
        $this->db->order_by('pi.id', 'ASC');
        return $this->db->get()->result();
    }

    public function get_production_outputs($production_id, $stage)
    {
        $this->db->select("
            o.*, 
            CASE 
                WHEN o.item_type IN ('finished','finished_goods') THEN fg.nama_barang
                WHEN o.item_type = 'semifinished' THEN sf.nama_barang
                WHEN o.item_type = 'scrap' THEN sc.scrap_name
                ELSE NULL
            END AS item_name,
            CASE 
                WHEN o.item_type IN ('finished','finished_goods') THEN fg.kode_barang
                WHEN o.item_type = 'semifinished' THEN sf.kode_barang
                WHEN o.item_type = 'scrap' THEN sc.scrap_code
                ELSE NULL
            END AS item_code
        ");
        $this->db->from('production_outputs o');
        $this->db->join('finished_goods fg', 'fg.id = o.item_id AND o.item_type IN ("finished","finished_goods")', 'left');
        $this->db->join('semifinished_goods sf', 'sf.id = o.item_id AND o.item_type = "semifinished"', 'left');
        $this->db->join('master_scrap sc', 'sc.id = o.item_id AND o.item_type = "scrap"', 'left');
        // Join users to get receiver (who recorded the output) and QC checker name
        $this->db->join('users receiver', 'receiver.id = o.received_by', 'left');
        $this->db->join('users qc', 'qc.id = o.qc_checked_by', 'left');
        // expose readable names
        $this->db->select('receiver.fullname as receiver_name, qc.fullname as qc_name', FALSE);
        $this->db->where('o.production_header_id', $production_id);
        $this->db->where('o.stage_name', $stage);
        $this->db->order_by('o.id', 'ASC');
        return $this->db->get()->result();
    }



    public function complete_production($id) {
        $data = ['status' => 'Completed'];
        // Anda bisa tambahkan 'completed_at', 'completed_by' jika perlu
        return $this->db->where('id', $id)
                        ->where('status', 'In Progress') // Hanya In Progress yg bisa completed
                        ->update($this->table_header, $data);
    }

    public function confirm_qc_output($output_id, $user_id) {
        $data = [
            'qc_checked_by' => $user_id,
            'qc_checked_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->where('id', $output_id)->update($this->table_outputs, $data);
    }
    public function unconfirm_qc_output($output_id) {
        $data = [
            'qc_checked_by' => NULL,
            'qc_checked_at' => NULL
        ];
        return $this->db->where('id', $output_id)->update($this->table_outputs, $data);
    }
    public function confirm_production($id, $user_id) {
        $data = [
            'status' => 'In Progress',
            'confirmed_by' => $user_id,
            'confirmed_at' => date('Y-m-d H:i:s')
        ];
        return $this->db->where('id', $id)->where('status', 'Draft')->update($this->table_header, $data);
    }
    public function unconfirm_production($id) {
        $data = [
            'status' => 'Draft',
            'confirmed_by' => NULL,
            'confirmed_at' => NULL
        ];
        // Hanya bisa unconfirm jika status 'In Progress'
        return $this->db->where('id', $id)->where('status', 'In Progress')->update($this->table_header, $data);
    }

    /**
     * FUNGSI BARU: Hapus 1 baris input dan HAPUS langsung stock_ledger terkait
     */
    public function delete_input_by_id($input_id) {
        $this->db->trans_start();
        $input = $this->db->get_where($this->table_inputs, ['id' => $input_id])->row();

        if ($input) {
            // ============ HAPUS LANGSUNG STOCK LEDGER ============
            // Hapus ledger wip_out yang dibuat saat input disimpan
            // wip_out dibuat dengan transaction_id = input_id
            $this->db->where('transaction_type', 'wip_out')
                     ->where('transaction_id', $input_id)
                     ->delete('stock_ledger');
            
            log_message('info', 'Deleted wip_out ledger for input_id: ' . $input_id);
            // ============ AKHIR PERBAIKAN ============
            
            $this->db->where('id', $input_id)->delete($this->table_inputs);
        }
        $this->db->trans_complete();
        return $this->db->trans_status();
    }



    /**
     * Hapus 1 baris output + HAPUS langsung stock_ledger terkait
     */
    public function delete_output_by_id($output_id) {
         $this->db->trans_start();
         $output = $this->db->get_where($this->table_outputs, ['id' => $output_id])->row();

         if ($output) {
             // ============ HAPUS LANGSUNG STOCK LEDGER ============
             // Hapus ledger wip_in yang dibuat saat output disimpan (untuk semifinished)
             // wip_in dibuat dengan transaction_id = output_id
             if ($output->item_type == 'semifinished') {
                 $this->db->where('transaction_type', 'wip_in')
                          ->where('transaction_id', $output_id)
                          ->where('item_type', 'semifinished')
                          ->delete('stock_ledger');
                 
                 log_message('info', 'Deleted wip_in ledger for output_id: ' . $output_id);
             }
             // ============ AKHIR PERBAIKAN ============
             
             $this->db->where('id', $output_id)->delete($this->table_outputs);
         }
         $this->db->trans_complete();
         return $this->db->trans_status();
    }
    public function insert_output($data)
    {
        // Validasi minimum
        if (empty($data['production_header_id']) || empty($data['item_id']) || empty($data['item_type'])) {
            log_message('error', 'insert_output gagal: data tidak lengkap. Data: ' . json_encode($data));
            return false;
        }

        // Siapkan array data sesuai struktur tabel
        $insert = [
            'production_header_id' => $data['production_header_id'],
            'stage_name'           => $data['stage_name'] ?? 'finishing', // default ke finishing
            'item_id'              => $data['item_id'],
            'item_type'            => $data['item_type'],
            'qty_produced'         => $data['qty_produced'] ?? 0,
            'qty_scrap'            => $data['qty_scrap'] ?? 0,
            'created_at'           => date('Y-m-d H:i:s'),
            'received_by'          => $this->session->userdata('user_id') ?? null
        ];

        // Insert ke tabel
        $this->db->insert('production_outputs', $insert);

        // Jika gagal, catat log untuk debugging
        if (!$this->db->affected_rows()) {
            log_message('error', 'insert_output gagal: ' . print_r($this->db->error(), true));
            return false;
        }

        return $this->db->insert_id();
    }



    public function get_available_previous_stage_outputs($prod_id, $current_stage = 'assembling', $previous_stage = 'preparation') {
        // 1. Ambil semua output semi-finished dari stage sebelumnya
        $this->db->select("
            po.id as output_id, 
            po.item_id, 
            po.item_type, 
            po.qty_produced,
            CASE WHEN po.item_type = 'semifinished' THEN sf.kode_barang ELSE NULL END as item_code,
            CASE WHEN po.item_type = 'semifinished' THEN sf.nama_barang ELSE NULL END as item_name,
            CASE WHEN po.item_type = 'semifinished' THEN sf.satuan ELSE NULL END as item_unit
        ");
        $this->db->from($this->table_outputs . ' po');
        $this->db->join('semifinished_goods sf', 'po.item_id = sf.id AND po.item_type = "semifinished"', 'left');
        $this->db->where('po.production_header_id', $prod_id);
        $this->db->where('po.stage_name', $previous_stage);
        $this->db->where('po.item_type', 'semifinished'); // Hanya ambil yg semi-finished
        $this->db->where('po.qc_checked_by IS NOT NULL'); // Hanya yg sudah QC
        $outputs = $this->db->get()->result();

        // 2. Hitung sisa qty yang belum terpakai di stage SEKARANG
        foreach ($outputs as $out) {
            $this->db->select_sum('qty_used');
            $this->db->where('production_header_id', $prod_id);
            $this->db->where('stage_name', $current_stage);
            $this->db->where('source_output_id', $out->output_id); // Cek pemakaian dari output ini
            $used_result = $this->db->get($this->table_inputs)->row();
            $total_used = $used_result ? (float)$used_result->qty_used : 0;

            $out->total_used = $total_used;
            $out->remaining_qty = (float)$out->qty_produced - $total_used;
        }

        return $outputs;
    }
    public function get_additional_materials_with_remaining($wo_id, $prod_id, $stage = 'assembling') {
        // 1. Ambil SEMUA batch material yg diterima dari MR utk WO ini
        $batches = $this->get_wip_material_for_wo($wo_id); // Fungsi ini mengambil dari mr_details

        if (empty($batches)) {
             return []; // Kembalikan array kosong jika tidak ada batch
        }

        foreach ($batches as $batch) {
            // Pastikan batch adalah object dan punya property
            if (!is_object($batch) || !property_exists($batch, 'mr_detail_id')) {
                 continue; // Lewati jika data batch tidak valid
            }

            // 2. Hitung total yg sudah terpakai di SEMUA stage DI PRODUKSI INI utk batch MR ini
            $this->db->reset_query(); // Reset query builder
            $this->db->select_sum('qty_used');
            $this->db->where('production_header_id', $prod_id); // Hanya di produksi ini
            $this->db->where('mr_detail_id', $batch->mr_detail_id); // Hanya dari batch MR ini
            
            // --- BARIS INI (Sekitar Line 394 Anda) HARUS SEPERTI INI ---
            $used_result = $this->db->get($this->table_inputs)->row(); 
            // ---------------------------------------------------
            
            $total_used = $used_result ? (float)$used_result->qty_used : 0;
            
            // 3. Hitung sisa
            $batch->total_used_in_this_prod = $total_used;
            $received_qty = isset($batch->total_received_wip) ? (float)$batch->total_received_wip : 0;
            $batch->remaining_qty = round($received_qty - $total_used, 5); // Bulatkan sisa
        }
         // Filter hanya yang sisanya > 0
         return array_filter($batches, function($b){ 
             return is_object($b) && property_exists($b, 'remaining_qty') && $b->remaining_qty > 0.00001; 
         });
    }
}