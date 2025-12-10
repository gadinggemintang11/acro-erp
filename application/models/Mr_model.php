<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mr_model extends CI_Model {

    private $table_header = 'mr_header';
    private $table_details = 'mr_details';

    public function __construct() {
        parent::__construct();
        $this->load->database();
        // Load session helper jika belum autoload
        $this->load->library('session');
        // Jangan load Stock_ledger_model di constructor; load on-demand
    }

    /**
     * Normalisasi user id yang diterima:
     * - jika null -> coba ambil dari session
     * - jika bukan numeric -> kembalikan null (agar tidak menyebabkan FK error)
     * - jika numeric -> cast ke int
     */
    private function _normalize_user_id($user_id = null) {
        if ($user_id === null) {
            $user_id = $this->session->userdata('user_id') ?? null;
        }
        // Jika user_id adalah string kosong atau non-numeric, kembalikan null
        if ($user_id === '' || $user_id === false || $user_id === 'null') {
            return null;
        }
        if (is_numeric($user_id)) {
            return (int)$user_id;
        }
        return null;
    }

    // === HEADER ===
    public function get_all($filters = []) {
        $this->db->select('mr.id, mr.mr_number, mr.request_date, mr.status, u.fullname as requester_name');
        $this->db->from($this->table_header . ' mr');
        $this->db->join('users u', 'mr.requester_id = u.id', 'left');

        if (!empty($filters['start_date'])) { $this->db->where('mr.request_date >=', $filters['start_date']); }
        if (!empty($filters['end_date'])) { $this->db->where('mr.request_date <=', $filters['end_date']); }
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->group_start();
             $this->db->like('mr.mr_number', $term);
             $this->db->or_like('u.fullname', $term);
             $this->db->group_end();
        }
        $this->db->order_by('mr.id', 'DESC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function get_header_by_id($id) {
        $this->db->select('
            mr.*,
            req.fullname as requester_name,
            iss.fullname as issuer_name,
            rec.fullname as receiver_name
        ');
        $this->db->from($this->table_header . ' mr');
        $this->db->join('users req', 'mr.requester_id = req.id', 'left');
        $this->db->join('users iss', 'mr.issuer_id = iss.id', 'left');
        $this->db->join('users rec', 'mr.receiver_id = rec.id', 'left');
        $this->db->where('mr.id', $id);
        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    /**
     * Insert header MR.
     * Jika data['requester_id'] disediakan, akan dinormalisasi.
     * Jika tidak ada dan status = 'Submitted', akan gunakan session user (jika ada).
     */
    public function insert_header($data) {
        // Normalisasi requester_id jika ada
        if (isset($data['requester_id'])) {
            $data['requester_id'] = $this->_normalize_user_id($data['requester_id']);
        }

        // Jika status Submitted dan requester_id belum terisi, coba ambil dari session
        if (!empty($data['status']) && strtolower($data['status']) === 'submitted' && empty($data['requester_id'])) {
            $data['requester_id'] = $this->_normalize_user_id(null);
            $data['requested_at'] = $data['requested_at'] ?? date('Y-m-d H:i:s');
        }

        $insert = $this->db->insert($this->table_header, $data);
        if (!$insert) {
            log_message('error', '[MR] insert_header fail: ' . json_encode($this->db->error()) . ' | DATA: ' . json_encode($data));
            return false;
        }
        return $this->db->insert_id();
    }

    public function delete_header($id) {
         // Hanya hapus Draft
         return $this->db->where('id', $id)->where('status', 'Draft')->delete($this->table_header);
    }

    /**
     * Update status MR dengan keamanan FK user_id.
     * $user_id dapat diberikan; jika null, akan coba ambil dari session.
     */
    public function update_status($id, $new_status, $user_id = null, $extra_data = []) {
        $user_id = $this->_normalize_user_id($user_id);
        $data = ['status' => $new_status];
        $now = date('Y-m-d H:i:s');

        switch ($new_status) {
            case 'Submitted':
                // Jika requester_id belum ada, set sekarang. Jika sudah ada (dari Draft), jangan overwrite
                $current = $this->db->select('requester_id')->get_where($this->table_header, ['id' => $id])->row();
                if (!$current || empty($current->requester_id)) {
                    $data['requester_id'] = $user_id;
                }
                $data['requested_at'] = $now;
                break;
            case 'Draft': // Unconfirm
                $data['requester_id'] = NULL;
                $data['requested_at'] = NULL;
                break;
            case 'Issued':
                $data['issuer_id'] = $user_id;
                $data['issued_at'] = $now;
                if (isset($extra_data['issuance_number'])) {
                     $data['issuance_number'] = $extra_data['issuance_number'];
                }
                break;
            case 'Received':
                $data['receiver_id'] = $user_id;
                $data['received_at'] = $now;
                break;
            default:
                // Untuk status lain, izinkan extra_data menimpa field spesifik
                if (!empty($extra_data) && is_array($extra_data)) {
                    foreach ($extra_data as $k => $v) {
                        $data[$k] = $v;
                    }
                }
                break;
        }

        $res = $this->db->where('id', $id)->update($this->table_header, $data);
        if (!$res) {
            log_message('error', "[MR] update_status fail for id={$id}, status={$new_status}: " . json_encode($this->db->error()) . " | DATA: " . json_encode($data));
        }
        return $res;
    }

    public function generate_mr_number() {
        $prefix = 'MR/' . date('Ym') . '/';
        $this->db->select('mr_number');
        $this->db->from($this->table_header);
        $this->db->like('mr_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $last = $this->db->get()->row();
        $new_num = $last ? (int) substr($last->mr_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // === DETAILS ===
    public function get_details_by_id($mr_id) {
         $select_clause = "
            d.id, d.mr_header_id, d.requested_qty, d.issued_qty, d.notes,
            d.component_item_id, d.component_item_type, d.wo_header_id,
            wo.wo_number, sc.sc_number, fg.nama_barang as style_name,
            CASE WHEN d.component_item_type = 'material' THEN m.kode_material WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang ELSE NULL END as item_code,
            CASE WHEN d.component_item_type = 'material' THEN m.nama_material WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang ELSE NULL END as item_name,
            CASE WHEN d.component_item_type = 'material' THEN m.satuan WHEN d.component_item_type = 'semifinished' THEN sf.satuan ELSE 'PCS' END as item_unit,
            d.unit_measure
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('wo_header wo', 'd.wo_header_id = wo.id', 'left');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.mr_header_id', $mr_id);
        $this->db->order_by('d.id', 'ASC');
        $query = $this->db->get();
        return $query ? $query->result() : [];
    }

    public function get_detail_item_by_id($detail_id) {
        $this->db->where('id', $detail_id);
        $query = $this->db->get($this->table_details);
        return $query ? $query->row() : null;
    }

    /**
     * Get item by ID with complete data (for AJAX response)
     */
    public function get_item_by_id($detail_id) {
        $select_clause = "
            d.id, d.mr_header_id, d.requested_qty, d.issued_qty, d.notes,
            d.component_item_id, d.component_item_type, d.wo_header_id,
            wo.wo_number,
            CASE WHEN d.component_item_type = 'material' THEN m.kode_material WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang ELSE NULL END as item_code,
            CASE WHEN d.component_item_type = 'material' THEN m.nama_material WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang ELSE NULL END as item_name,
            d.unit_measure
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('wo_header wo', 'd.wo_header_id = wo.id', 'left');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.id', $detail_id);
        $query = $this->db->get();
        return $query ? $query->row() : null;
    }

    public function insert_item($data) {
        // Validasi dasar
        if (empty($data['mr_header_id']) || empty($data['wo_header_id']) || empty($data['component_item_id']) || empty($data['component_item_type']) || !isset($data['requested_qty'])) {
             log_message('error', 'MR_model->insert_item gagal: Data tidak lengkap. ' . json_encode($data));
             return false;
        }

        // Check header status - hanya boleh insert jika header status = Draft
        $hdr = $this->get_header_by_id($data['mr_header_id']);
        if (!$hdr) {
            log_message('error', 'MR_model->insert_item gagal: header MR tidak ditemukan. mr_header_id=' . $data['mr_header_id']);
            return false;
        }
        if (strtolower($hdr->status) !== 'draft') {
            log_message('error', 'MR_model->insert_item gagal: header status bukan Draft. status=' . $hdr->status);
            return false;
        }

        // Ambil satuan dari master
        $satuan = 'PCS';
        if ($data['component_item_type'] == 'material') {
            $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('materials')->row();
            if ($component) $satuan = $component->satuan;
        } else if ($data['component_item_type'] == 'semifinished') {
            $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('semifinished_goods')->row();
            if ($component) $satuan = $component->satuan;
        }
        $data['unit_measure'] = $satuan;

        // Pastikan issued_qty tidak ikut tersimpan saat insert awal
        unset($data['issued_qty']);

        $res = $this->db->insert($this->table_details, $data);
        if (!$res) {
            log_message('error', 'MR_model->insert_item DB ERROR: ' . json_encode($this->db->error()) . ' | DATA: ' . json_encode($data));
            return false;
        }
        return $this->db->insert_id();
    }

    public function update_item($id, $data) {
         // Hanya boleh update qty request atau notes jika status header masih Draft
         unset($data['issued_qty']);
         $detail = $this->get_detail_item_by_id($id);
         if (!$detail) { log_message('error', 'MR_model->update_item gagal: detail tidak ditemukan id=' . $id); return false; }
         $hdr = $this->get_header_by_id($detail->mr_header_id);
         if (!$hdr || strtolower($hdr->status) !== 'draft') { log_message('error', 'MR_model->update_item gagal: header bukan Draft'); return false; }
         return $this->db->where('id', $id)->update($this->table_details, $data);
    }

    public function delete_item($id) {
        $detail = $this->get_detail_item_by_id($id);
        if (!$detail) { log_message('error', 'MR_model->delete_item gagal: detail tidak ditemukan id=' . $id); return false; }
        $hdr = $this->get_header_by_id($detail->mr_header_id);
        if (!$hdr || strtolower($hdr->status) !== 'draft') { log_message('error', 'MR_model->delete_item gagal: header bukan Draft'); return false; }
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // === HELPERS ===
    public function search_open_wos($term) {
        $term = $this->db->escape_like_str($term);
        $this->db->select('wo.id, wo.wo_number, fg.nama_barang as fg_name');
        $this->db->from('wo_header wo');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->where('wo.status', 'In Progress');
        $this->db->group_start();
        $this->db->like('wo.wo_number', $term);
        $this->db->or_like('fg.nama_barang', $term);
        $this->db->group_end();
        $this->db->limit(10);
        return $this->db->get()->result();
    }

    public function get_wo_references($wo_id) {
        $this->load->model('Wo_model');
        $header = $this->Wo_model->get_header_by_id($wo_id);
        $bom_components = $this->Wo_model->get_bom_components_by_fg($header->fg_id);
        $wo_plan = $this->Wo_model->get_planned_materials($wo_id);

        return [
            'header' => $header,
            'bom' => $bom_components,
            'plan' => $wo_plan
        ];
    }

    public function search_components($term) {
        $term = $this->db->escape_like_str($term);
        $materials = $this->db->select("id, kode_material as code, nama_material as name, 'material' as type")
                              ->group_start()->like('kode_material', $term)->or_like('nama_material', $term)->group_end()
                              ->where('status', 'active')->limit(10)->get('materials')->result();
        $semifinished = $this->db->select("id, kode_barang as code, nama_barang as name, 'semifinished' as type")
                                 ->group_start()->like('kode_barang', $term)->or_like('nama_barang', $term)->group_end()
                                 ->where('status', 'active')->limit(10)->get('semifinished_goods')->result();
        return array_merge($materials, $semifinished);
    }

    public function get_mrs_for_production($filters = []) {
        $this->db->select('mr.id, mr.mr_number, mr.issuance_number, mr.request_date, mr.status, u.fullname as requester_name, i.fullname as issuer_name');
        $this->db->from('mr_header mr');
        $this->db->join('users u', 'mr.requester_id = u.id', 'left');
        $this->db->join('users i', 'mr.issuer_id = i.id', 'left');
        if (!empty($filters['status'])) {
             $this->db->where('mr.status', $filters['status']);
        } else {
             $this->db->where_in('mr.status', ['Issued', 'Received']);
        }
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->group_start();
             $this->db->like('mr.mr_number', $term);
             $this->db->or_like('mr.issuance_number', $term);
             $this->db->group_end();
        }
        $this->db->order_by('mr.request_date', 'DESC');
        return $this->db->get()->result();
    }

    /**
     * Proses penerimaan MR oleh produksi:
     * - update header -> Received
     * - catat ledger 'wip_in' untuk setiap issued item (jika ada issued_qty)
     */
    public function process_reception($mr_id, $user_id = null) {
        $this->db->trans_start();

        $user_id = $this->_normalize_user_id($user_id);

        // 1. Update status MR Header
        $data = [
            'status' => 'Received',
            'receiver_id' => $user_id,
            'received_at' => date('Y-m-d H:i:s')
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $data);
        if ($this->db->affected_rows() === 0) {
            log_message('warning', "[MR] process_reception: no header updated for mr_id={$mr_id}");
        }

        // 2. Pindahkan stok ke WIP: catat ke stock_ledger sebagai 'wip_in'
        $items = $this->db->get_where('mr_details', ['mr_header_id' => $mr_id])->result();
        $this->load->model('Stock_ledger_model');

        foreach ($items as $item) {
            if (isset($item->issued_qty) && (float)$item->issued_qty > 0) {
                // Ambil unit_value last known dari stock_ledger jika ada
                $unit_value = 0.0;
                $last_ledger = $this->Stock_ledger_model->get_last_balance($item->component_item_id, $item->component_item_type);
                // Note: get_last_balance() mengembalikan balance, bukan unit_value. Jika kamu punya unit_value history, ambil dari stock_ledger table:
                $this->db->reset_query();
                $this->db->select('unit_value')->from('stock_ledger')
                         ->where('item_id', $item->component_item_id)
                         ->where('item_type', $item->component_item_type)
                         ->order_by('id', 'DESC')->limit(1);
                $urow = $this->db->get()->row();
                if ($urow && isset($urow->unit_value)) $unit_value = (float)$urow->unit_value;

                $ledger_data = [
                    'item_id'         => $item->component_item_id,
                    'item_type'       => $item->component_item_type,
                    'transaction_date'=> date('Y-m-d H:i:s'),
                    'transaction_type'=> 'wip_in',
                    'transaction_id'  => $mr_id,
                    'source_doc_type' => 'MR',
                    'source_doc_id'   => $mr_header->wo_header_id, // PENTING: simpan WO ID agar bisa diquery di production process
                    'qty_in'          => (float)$item->issued_qty,
                    'qty_out'         => 0,
                    'unit_value'      => $unit_value,
                    'stock_on_hand_id'=> NULL // tidak ada SOH link saat issuance MR -> produksi
                ];

                $ins = $this->Stock_ledger_model->insert($ledger_data);
                if (!$ins) {
                    log_message('error', '[MR] process_reception: gagal insert ledger for mr_id=' . $mr_id . ' item_id=' . $item->component_item_id);
                    $this->db->trans_rollback();
                    return false;
                }
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Reverse reception: set header status kembali ke Issued dan (opsional) hapus ledger wip_in
     */
    public function reverse_reception($mr_id) {
        $this->db->trans_start();

        // 1. Update status MR Header
        $data = [
            'status' => 'Issued',
            'receiver_id' => NULL,
            'received_at' => NULL
        ];
        $this->db->where('id', $mr_id)->update('mr_header', $data);

        // 2. Hapus entri 'wip_in' yang terkait (jika policy mengizinkan)
        $this->load->model('Stock_ledger_model');
        $this->Stock_ledger_model->delete_by_transaction('wip_in', $mr_id);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function insert_all_wo_items($mr_id, $wo_id) {
        $this->load->model('Wo_model');

        $wo = $this->Wo_model->get_header_by_id($wo_id);

        $bom_components = $this->Wo_model->get_bom_components_by_fg($wo->fg_id);
        $wo_plan = $this->Wo_model->get_planned_materials($wo_id);
        $existing_items = $this->db->get_where($this->table_details, [
            'mr_header_id' => $mr_id,
            'wo_header_id' => $wo_id
        ])->result();

        $items_to_insert = [];
        foreach ($bom_components as $comp) {
            // Cek apakah sudah ada di MR
            $exists = array_filter($existing_items, function($e) use ($comp) {
                return $e->component_item_id == $comp->component_item_id && $e->component_item_type == $comp->component_item_type;
            });
            if (!empty($exists)) continue; // skip jika sudah ada
            // Cek apakah ada di plan
            $planned = array_filter($wo_plan, function($p) use ($comp) {
                return $p->component_item_id == $comp->component_item_id && $p->component_item_type == $comp->component_item_type;
            });
            $planned_qty = !empty($planned) ? array_values($planned)[0]->planned_qty : 0;
            if ($planned_qty <= 0) continue; // skip jika tidak ada plan    
            $items_to_insert[] = [
                'mr_header_id'       => $mr_id,
                'wo_header_id'       => $wo_id,
                'component_item_id'  => $comp->component_item_id,
                'component_item_type'=> $comp->component_item_type,
                'requested_qty'      => $planned_qty,
                'notes'              => 'Auto-added from WO #' . $wo->wo_number
            ]; 
        }

        // Bulk insert
        if (!empty($items_to_insert)) {
            $res = $this->db->insert_batch($this->table_details, $items_to_insert);
            if ($res === false) {
                log_message('error', 'MR_model->insert_all_wo_items: gagal insert batch. ' . json_encode($this->db->error()));
                return false;
            }
        }

        return count($items_to_insert);
    }
}
