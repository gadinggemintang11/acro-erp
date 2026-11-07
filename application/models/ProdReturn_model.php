<?php
defined('BASEPATH') OR exit('No direct script access allowed');
//NeoKABER @ Gading Gemintang 2025
class ProdReturn_model extends CI_Model {

  
    private $tbl_header = 'prod_return_header';
    private $tbl_detail = 'prod_return_detail';
    private $tbl_soh    = 'stock_on_hand';
    private $tbl_grn_d  = 'grn_details';
    private $tbl_users  = 'users';
    private $tbl_wo     = 'wo_header';
    private $tbl_mat    = 'materials';
    private $tbl_sf     = 'semifinished_goods';
    private $tbl_cc     = 'common_code';
    private $tbl_mrd    = 'mr_details';
    private $tbl_prod_i = 'production_inputs';
    private $tbl_prod_h = 'production_header';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        
    }

    
    private function _get_item_type($item_id) {
        if (empty($item_id) || !is_numeric($item_id)) return null;

        
        $is_material = $this->db->select('id')->from($this->tbl_mat)->where('id', $item_id)->count_all_results() > 0;
        if ($is_material) {
            return 'material';
        }

        
        $is_semifinished = $this->db->select('id')->from($this->tbl_sf)->where('id', $item_id)->count_all_results() > 0;
        if ($is_semifinished) {
            return 'semifinished';
        }
        
        log_message('warning', "ProdReturn_model->_get_item_type: Tidak dapat menemukan tipe untuk item ID {$item_id}");
        return null;
    }

    public function get_active_work_orders() {
        $this->db->select('id, wo_number')
                 ->from($this->tbl_wo)
                 ->where_in('status', ['In Progress'])
                 ->order_by('id', 'DESC');
        $q = $this->db->get();
        return $q ? $q->result_array() : [];
    }

    
    public function get_all_returns($filters = []) {
        $this->db->select("
            h.id, h.prod_return_no AS return_number, h.return_date, h.status,
            w.wo_number AS production_number,
            u_ret.fullname AS returner_name,
            u_conf.fullname AS confirmer_name
        ")->from($this->tbl_header . ' h')
          ->join($this->tbl_wo . ' w', 'w.id = h.work_order_id', 'left')
          ->join($this->tbl_users . ' u_ret', 'u_ret.id = h.returned_by', 'left')
          ->join($this->tbl_users . ' u_conf', 'u_conf.id = h.confirmed_by', 'left');

        if (!empty($filters['search'])) {
            $term = $this->db->escape_like_str($filters['search']);
            $this->db->group_start()
                     ->like('h.prod_return_no', $term)
                     ->or_like('w.wo_number', $term)
                     ->group_end();
        }

        $this->db->order_by('h.id', 'DESC');
        $q = $this->db->get();
        return $q ? $q->result() : [];
    }
    public function generate_return_no() {
        // Format nomor diatur di config/doc_number.php (rebrand Acropoly 2026-11-05).
        return doc_number_next('production_return', 'prod_return_header', 'prod_return_no');
    }

  
    public function get_return_by_id($id) {
        $data = [];

        $this->db->select('h.*, w.wo_number, u_ret.fullname as returner_name, u_ret.signature_path as returner_signature, u_conf.fullname as confirmer_name, u_conf.signature_path as confirmer_signature');
        $this->db->from($this->tbl_header . ' h');
        $this->db->join($this->tbl_wo . ' w', 'w.id = h.work_order_id', 'left');
        $this->db->join($this->tbl_users . ' u_ret', 'u_ret.id = h.returned_by', 'left');
        $this->db->join($this->tbl_users . ' u_conf', 'u_conf.id = h.confirmed_by', 'left');
        $this->db->where('h.id', $id);
        $header_query = $this->db->get();
        if (!$header_query) { /* Log error */ return null; }
        $data['header'] = $header_query->row_array();
        if (empty($data['header'])) return null;

        
        $this->db->select("
            d.*,
            CASE
                WHEN m.id IS NOT NULL THEN 'material'
                WHEN sf.id IS NOT NULL THEN 'semifinished'
                ELSE 'unknown'
            END as item_type,
            CASE
                WHEN m.id IS NOT NULL THEN m.nama_material
                WHEN sf.id IS NOT NULL THEN sf.nama_barang
                ELSE 'Unknown Item'
            END as nama_material,
            CASE
                WHEN m.id IS NOT NULL THEN m.satuan
                WHEN sf.id IS NOT NULL THEN sf.satuan
                ELSE 'PCS'
            END as satuan_code
        ");
        $this->db->from($this->tbl_detail . ' d');
        $this->db->join($this->tbl_mat . ' m', 'm.id = d.material_id', 'left'); // LEFT JOIN
        $this->db->join($this->tbl_sf . ' sf', 'sf.id = d.material_id', 'left'); // LEFT JOIN
        $this->db->where('d.prod_return_id', $id);
        $this->db->order_by('d.id', 'ASC');
        $detail_query = $this->db->get();
        if (!$detail_query) { /* Log error */ $data['details'] = []; }
        else { $data['details'] = $detail_query->result_array(); }

        return $data;
    }
    
     public function get_data_for_pdf($return_id) {
         return $this->get_return_by_id($return_id);
     }

    // ---------------------------
    // Ambil sisa WIP per WO (digunakan AJAX)
    // (FIXED v4: Perbaikan typo dan logika returned_sql)
    // ---------------------------
    public function get_wip_balance_by_wo($wo_id) {
        if (empty($wo_id) || !is_numeric($wo_id)) return [];

        // 1. Total Issued (Material masuk ke WIP via MR)
        $this->db->select('component_item_id as material_id, SUM(issued_qty) as total_qty, component_item_type as item_type');
        $this->db->from($this->tbl_mrd);
        $this->db->where('wo_header_id', $wo_id);
        $this->db->where('issued_qty >', 0);
        $this->db->group_by('component_item_id, component_item_type');
        $issued_sql = $this->db->get_compiled_select();
        $this->db->reset_query();

        // 2a. Total Consumed dari Gudang (punya stock_on_hand_id)
        $this->db->select('pi.item_id as material_id, SUM(pi.qty_used) as total_qty, pi.item_type');
        $this->db->from($this->tbl_prod_i . ' pi');
        $this->db->join($this->tbl_prod_h . ' ph', 'pi.production_header_id = ph.id', 'inner');
        $this->db->where('ph.wo_header_id', $wo_id);
        $this->db->where('pi.stock_on_hand_id IS NOT NULL');
        $this->db->group_by('pi.item_id, pi.item_type');
        $consumed_soh_sql = $this->db->get_compiled_select();
        $this->db->reset_query();

        // 2b. Total Consumed dari WIP (wip_out di stock_ledger)
        $this->db->select('sl.item_id as material_id, SUM(sl.qty_out) as total_qty, sl.item_type');
        $this->db->from('stock_ledger sl');
        $this->db->where('sl.transaction_type', 'wip_out');
        $this->db->where('sl.source_doc_id', $wo_id);
        $this->db->group_by('sl.item_id, sl.item_type');
        $consumed_wip_sql = $this->db->get_compiled_select();
        $this->db->reset_query();

        // 3. Total Returned (Kecuali Cancelled)
        $this->db->select("
            prd.material_id, 
            SUM(prd.qty_return) as total_qty, 
            CASE
                WHEN m.id IS NOT NULL THEN 'material'
                WHEN sf.id IS NOT NULL THEN 'semifinished'
                ELSE 'unknown'
            END as item_type
        ", FALSE);
        $this->db->from($this->tbl_detail . ' prd');
        $this->db->join($this->tbl_header . ' prh', 'prd.prod_return_id = prh.id', 'inner');
        $this->db->join($this->tbl_mat . ' m', 'prd.material_id = m.id', 'left');
        $this->db->join($this->tbl_sf . ' sf', 'prd.material_id = sf.id', 'left');
        $this->db->where('prh.work_order_id', $wo_id);
        $this->db->where('prh.status !=', 'Cancelled');
        $this->db->group_by('prd.material_id, item_type');
        $returned_sql = $this->db->get_compiled_select();
        $this->db->reset_query();

        // Gabungkan semua movement
        $union_query_sql = "
            SELECT material_id, item_type, total_qty, 'issued' as type FROM ({$issued_sql}) as issued
            UNION ALL
            SELECT material_id, item_type, total_qty, 'consumed' as type FROM ({$consumed_soh_sql}) as consumed_soh
            UNION ALL
            SELECT material_id, item_type, total_qty, 'consumed' as type FROM ({$consumed_wip_sql}) as consumed_wip
            UNION ALL
            SELECT material_id, item_type, total_qty, 'returned' as type FROM ({$returned_sql}) as returned
        ";
        
        $union_query = $this->db->query($union_query_sql);
        if (!$union_query) { /* Log error */ return []; }
        $wip_movements = $union_query->result_array();
        log_message('debug', "ProdReturn_model->get_wip_balance_by_wo movements for WO ID {$wo_id}: " . print_r($wip_movements, true));

        // Hitung saldo
        $wip_balance = [];
        foreach ($wip_movements as $move) {
            $key = $move['material_id'] . '|' . (is_null($move['item_type']) ? 'unknown' : $move['item_type']);
            if ($move['item_type'] == 'unknown') continue;
            if (!isset($wip_balance[$key])) { $wip_balance[$key] = 0.0; }
            $wip_balance[$key] += ($move['type'] == 'issued' ? (float)$move['total_qty'] : -(float)$move['total_qty']);
        }
        log_message('debug', "ProdReturn_model->get_wip_balance_by_wo calculated balance for WO ID {$wo_id}: " . print_r($wip_balance, true));

        // Format hasil
        $result = [];
        if (!empty($wip_balance)) {
            $item_keys_with_balance = array_keys(array_filter($wip_balance, function($bal){ return $bal > 0.0001; }));
            if (!empty($item_keys_with_balance)) {
                $item_ids_to_query = [];
                foreach ($item_keys_with_balance as $key) { list($id, $type) = explode('|', $key); $item_ids_to_query[$type][] = $id; }
                $items_info = [];
                if (!empty($item_ids_to_query['material'])) {
                    $mat_query = $this->db->select("id, nama_material as name, satuan, 'material' as item_type", FALSE)->from($this->tbl_mat)->where_in('id', array_unique($item_ids_to_query['material']))->get();
                    if ($mat_query) $items_info = array_merge($items_info, $mat_query->result_array());
                }
                if (!empty($item_ids_to_query['semifinished'])) {
                    $sf_query = $this->db->select("id, nama_barang as name, satuan, 'semifinished' as item_type", FALSE)->from($this->tbl_sf)->where_in('id', array_unique($item_ids_to_query['semifinished']))->get();
                    if ($sf_query) $items_info = array_merge($items_info, $sf_query->result_array());
                }
                $items_map = [];
                foreach ($items_info as $item) { $items_map[$item['id'] . '|' . $item['item_type']] = $item; }
                foreach ($wip_balance as $key => $balance) {
                    if ($balance > 0.0001 && isset($items_map[$key])) {
                        $item_info = $items_map[$key];
                        list($item_id, $item_type) = explode('|', $key);
                        $result[] = [
                            'material_id'   => $item_id,
                            'item_type'     => $item_type,
                            'nama_material' => $item_info['name'],
                            'satuan'        => $item_info['satuan'],
                            'qty_sisa_wip'  => round($balance, 5)
                        ];
                    }
                }
            }
        }
        log_message('debug', "ProdReturn_model->get_wip_balance_by_wo final result for WO ID {$wo_id}: " . print_r($result, true));
        return $result;
    }

    // ---------------------------
    // Simpan retur (Draft)
    // (FIXED: Hapus 'item_type' dari insert)
    // ---------------------------
    public function create_return($header_data, $detail_data) {
        if (empty($header_data['work_order_id']) || empty($header_data['return_date']) || empty($detail_data)) { 
            log_message('error', 'ProdReturn_model->create_return gagal: Data header/detail tidak lengkap.');
            return false; 
        }
        $this->db->trans_start();

        $header_data['prod_return_no'] = $this->generate_return_no();
        $header_data['status'] = 'Draft';
        $header_data['returned_by'] = $this->session->userdata('user_id') ?? null;

        if (!$this->db->insert($this->tbl_header, $header_data)) { 
            log_message('error', 'ProdReturn_model->create_return Gagal insert header: ' . print_r($this->db->error(), true));
            $this->db->trans_rollback(); return false; 
        }
        $return_id = $this->db->insert_id();

        $batch_details = [];
        foreach ($detail_data as $item) {
            if (empty($item['material_id']) || !isset($item['qty_return']) || $item['qty_return'] <= 0) { 
                log_message('error', "ProdReturn_model->create_return Gagal: Item detail tidak valid untuk return ID {$return_id}. Item: " . json_encode($item));
                $this->db->trans_rollback(); return false; 
            }
            $batch_details[] = [
                'prod_return_id' => $return_id, 
                'material_id'    => $item['material_id'],
                // 'item_type' DIHAPUS
                'qty_return'     => (float)$item['qty_return'],
                'reason'         => strip_tags($item['reason'] ?? null)
            ];
        }

        if (!empty($batch_details)) {
            if (!$this->db->insert_batch($this->tbl_detail, $batch_details)) { 
                log_message('error', 'ProdReturn_model->create_return Gagal insert batch detail: ' . print_r($this->db->error(), true));
                $this->db->trans_rollback(); return false; 
            }
        } else { 
             log_message('error', "ProdReturn_model->create_return Gagal: Tidak ada detail item valid setelah diproses.");
             $this->db->trans_rollback(); return false;
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) { 
            log_message('error', 'ProdReturn_model->create_return Gagal transaksi DB.');
            return false; 
        }
        return $return_id;
    }

    // ---------------------------
    // Update retur (Draft)
    // (FIXED: Hapus 'item_type' dari insert)
    // ---------------------------
    public function update_return($id, $header_data, $detail_data) {
         if (empty($id) || empty($header_data) || empty($detail_data)) return false;

         $this->db->trans_start();

         $this->db->where('id', $id)->where('status', 'Draft');
         $this->db->update($this->tbl_header, $header_data);
         if ($this->db->affected_rows() == 0) { 
             log_message('warning', "ProdReturn_model->update_return Gagal: Header ID {$id} tidak ditemukan atau status bukan Draft.");
             $this->db->trans_rollback(); return false; 
         }

         $this->db->where('prod_return_id', $id)->delete($this->tbl_detail);

         $batch_details = [];
         foreach ($detail_data as $item) {
             if (empty($item['material_id']) || !isset($item['qty_return']) || $item['qty_return'] <= 0) { 
                 log_message('error', "ProdReturn_model->update_return Gagal: Item detail tidak valid untuk return ID {$id}. Item: " . json_encode($item));
                 $this->db->trans_rollback(); return false; 
             }
             $batch_details[] = [
                 'prod_return_id' => $id,
                 'material_id'    => $item['material_id'],
                 // 'item_type' DIHAPUS
                 'qty_return'     => (float)$item['qty_return'],
                 'reason'         => strip_tags($item['reason'] ?? null)
             ];
         }
         
         if (empty($batch_details)) {
             log_message('error', "ProdReturn_model->update_return Gagal: Tidak ada detail item valid setelah diproses untuk ID {$id}.");
             $this->db->trans_rollback(); return false;
         }
         
         if (!$this->db->insert_batch($this->tbl_detail, $batch_details)) { 
             log_message('error', 'ProdReturn_model->update_return Gagal insert batch detail: ' . print_r($this->db->error(), true));
             $this->db->trans_rollback(); return false; 
         }

         $this->db->trans_complete();
         return $this->db->trans_status();
    }

    // ---------------------------
    // Hapus Draft Retur
    // ---------------------------
    public function delete_draft_return($id) {
        $this->db->trans_start();
        $header = $this->db->select('status')->where('id', $id)->get($this->tbl_header)->row();
        if (!$header) { $this->db->trans_rollback(); return ['success' => false, 'message' => 'Data tidak ditemukan.']; }
        if ($header->status != 'Draft') { $this->db->trans_rollback(); return ['success' => false, 'message' => 'Hanya status DRAFT yang bisa dihapus.']; }
        $this->db->where('prod_return_id', $id)->delete($this->tbl_detail);
        $this->db->where('id', $id)->delete($this->tbl_header);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) { 
            log_message('error', "ProdReturn_model->delete_draft_return Gagal transaksi DB untuk ID {$id}.");
            return ['success' => false, 'message' => 'Gagal menghapus data (Database Error).']; 
        }
        return ['success' => true, 'message' => 'Draft retur berhasil dihapus.'];
    }

    // ---------------------------
    // Konfirmasi Retur (FINAL)
    // ---------------------------
    public function confirm_return($id) {
        $this->load->model('Stock_ledger_model');
        $this->load->model('Stock_on_hand_model'); 

        $this->db->trans_start();

        $header = $this->db->where('id', $id)->get($this->tbl_header)->row_array();
        if (!$header) { $this->db->trans_rollback(); return ['success' => false, 'message' => 'Data retur tidak ditemukan.']; }
        if ($header['status'] != 'Draft') { $this->db->trans_rollback(); return ['success' => false, 'message' => 'Hanya status DRAFT yang bisa dikonfirmasi.']; }

        $details = $this->db->where('prod_return_id', $id)->get($this->tbl_detail)->result_array();
        if (empty($details)) { $this->db->trans_rollback(); return ['success' => false, 'message' => 'Item detail tidak ditemukan.']; }

        $user_id = $this->session->userdata('user_id') ?? null;
        $now = date('Y-m-d H:i:s');

        foreach ($details as $item) {
            $material_id = $item['material_id'];
            $qty_return = (float)$item['qty_return'];
            $item_detail_id = $item['id'];
            if ($qty_return <= 0) continue; // Lewati jika qty 0

            // 1. Dapatkan tipe item yg benar
            $item_type = $this->_get_item_type($material_id);
            if (is_null($item_type)) {
                log_message('error', "ProdReturn_model->confirm_return Gagal: Tipe item tidak dikenal untuk ID {$material_id}.");
                $this->db->trans_rollback(); 
                return ['success' => false, 'message' => "Tipe item tidak dikenal untuk ID {$material_id}."];
            }

            // 2. Cari SOH dan tambah stok
            // Asumsi SOH Model punya fungsi ini
            $soh_result = $this->Stock_on_hand_model->find_or_create_and_increase_stock($material_id, $item_type, $qty_return);
            
            if (!$soh_result || !$soh_result['success']) {
                $err_msg = $soh_result['message'] ?? 'Gagal update Stock On Hand.';
                log_message('error', "ProdReturn_model->confirm_return Gagal: {$err_msg} untuk item {$material_id}.");
                $this->db->trans_rollback(); 
                return ['success' => false, 'message' => $err_msg];
            }
            $soh_id_to_update = $soh_result['soh_id'];
            $cost = $soh_result['unit_value'];

            // 2b. Ambil BC info dari transaksi wip_out terakhir untuk item ini di WO ini
            $bc_info = $this->db->select('bc_type_code, bc_number, bc_date')
                                ->from('stock_ledger')
                                ->where('item_id', $material_id)
                                ->where('item_type', $item_type)
                                ->where('source_doc_id', $header['wo_header_id'])
                                ->where_in('transaction_type', ['wip_in', 'wip_out'])
                                ->where('bc_type_code IS NOT NULL')
                                ->order_by('id', 'DESC')
                                ->limit(1)
                                ->get()
                                ->row();

            // 3. Catat ke Stock Ledger dengan BC info
            $ledger_data = [
                'item_id'           => $material_id,
                'item_type'         => $item_type,
                'transaction_date'  => $now,
                'transaction_type'  => 'IN_RETURN_WIP',
                'transaction_id'    => $item_detail_id,
                'source_doc_type'   => 'PROD_RETURN',
                'source_doc_id'     => $id,
                'qty_in'            => $qty_return,
                'qty_out'           => 0,
                'unit_value'        => $cost,
                'stock_on_hand_id'  => $soh_id_to_update,
                // BC info dari transaksi WIP sebelumnya
                'bc_type_code'      => $bc_info->bc_type_code ?? NULL,
                'bc_number'         => $bc_info->bc_number ?? NULL,
                'bc_date'           => $bc_info->bc_date ?? NULL
            ];

            if (!$this->Stock_ledger_model->insert($ledger_data)) {
                 log_message('error', 'ProdReturn_model->confirm_return Gagal insert ledger IN_RETURN_WIP. Data: ' . json_encode($ledger_data));
                 $this->db->trans_rollback(); 
                 return ['success' => false, 'message' => 'Gagal mencatat ledger stok.'];
            }
        }

        // 4. Update Status Header ke Confirmed
        $update_header = $this->db->where('id', $id)
                          ->update($this->tbl_header, [
                              'status' => 'Confirmed',
                              'confirmed_by' => $user_id,
                              'confirmed_at' => $now
                          ]);
         if (!$update_header) { 
             log_message('error', "ProdReturn_model->confirm_return Gagal update status header ID {$id} ke Confirmed.");
             $this->db->trans_rollback(); 
             return ['success' => false, 'message' => 'Gagal update status header retur.']; 
         }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) { 
            log_message('error', 'ProdReturn_model->confirm_return Gagal transaksi DB.');
            return ['success' => false, 'message' => 'Gagal konfirmasi (Database Transaction Error).']; 
        }
        return ['success' => true, 'message' => 'Retur ' . $header['prod_return_no'] . ' berhasil dikonfirmasi.'];
    }

    // ---------------------------
    // Batalkan Konfirmasi Retur (FINAL)
    // ---------------------------
    public function unconfirm_return($id) {
        $this->load->model('Stock_ledger_model');
        $this->load->model('Stock_on_hand_model');

        $this->db->trans_start();

        $header = $this->db->where('id', $id)->get($this->tbl_header)->row_array();
        if (!$header) { $this->db->trans_rollback(); return ['success' => false, 'message' => 'Data retur tidak ditemukan.']; }
        if ($header['status'] != 'Confirmed') { $this->db->trans_rollback(); return ['success' => false, 'message' => 'Hanya status "Confirmed" yang bisa dibatalkan.']; }

        $details = $this->db->where('prod_return_id', $id)->get($this->tbl_detail)->result_array();
        if (empty($details)) { $this->db->trans_rollback(); return ['success' => false, 'message' => 'Detail item retur tidak ditemukan.'];}

        $now = date('Y-m-d H:i:s');

        foreach ($details as $item) {
            $material_id = $item['material_id'];
            $qty_return = (float)$item['qty_return'];
            $item_detail_id = $item['id'];
            if ($qty_return <= 0) continue;

            // 1. Dapatkan tipe item yg benar
            $item_type = $this->_get_item_type($material_id);
            if (is_null($item_type)) { /* Log error, rollback, return */ }

            // 2. Cari SOH dan kurangi stok
            // Asumsi SOH Model punya fungsi ini
            $decrease_result = $this->Stock_on_hand_model->find_and_decrease_stock($material_id, $item_type, $qty_return);
            
            if (!$decrease_result || !$decrease_result['success']) {
                $err_msg = $decrease_result['message'] ?? "Gagal mengurangi stok SOH saat unconfirm.";
                log_message('error', "ProdReturn_model->unconfirm_return Error: {$err_msg} Item: {$material_id}, Qty: {$qty_return}");
                $this->db->trans_rollback();
                return ['success' => false, 'message' => $err_msg . " Stok mungkin sudah terpakai."];
            }
            $soh_id_to_update = $decrease_result['soh_id'];
            $cost = $decrease_result['unit_value'];

            // 3. Catat Ledger Reversal (tanpa 'balance')
            $ledger_data = [
                'item_id'           => $material_id,
                'item_type'         => $item_type,
                'transaction_date'  => $now,
                'transaction_type'  => 'IN_RETURN_WIP_CANCELLED',
                'transaction_id'    => $item_detail_id,
                'source_doc_type'   => 'PROD_RETURN_CANCEL',
                'source_doc_id'     => $id,
                'qty_in'            => 0,
                'qty_out'           => $qty_return,
                'unit_value'        => $cost,
                'stock_on_hand_id'  => $soh_id_to_update
            ];

            if (!$this->Stock_ledger_model->insert($ledger_data)) { 
                /* Log error */ $this->db->trans_rollback(); 
                return ['success' => false, 'message' => 'Gagal mencatat ledger reversal stok.']; 
            }
        }

        // 4. Update Status Header kembali ke Draft
        $update_header = $this->db->where('id', $id)
                           ->update($this->tbl_header, [
                               'status' => 'Draft',
                               'confirmed_by' => NULL,
                               'confirmed_at' => NULL
                           ]);
        if (!$update_header) { /* Log error */ $this->db->trans_rollback(); return ['success' => false, 'message' => 'Gagal update status header retur.']; }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) { /* Log error */ return ['success' => false, 'message' => 'Gagal membatalkan konfirmasi (Database Transaction Error).']; }
        return ['success' => true, 'message' => 'Konfirmasi retur ' . $header['prod_return_no'] . ' berhasil dibatalkan.'];
    }

} // End Class