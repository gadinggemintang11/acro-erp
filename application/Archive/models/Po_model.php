<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Po_model extends CI_Model {

    // --- Definisi Nama Tabel (Sesuaikan jika perlu) ---
    private $table_header = 'po_header';
    private $table_details = 'po_details';
    private $table_supplier = 'suppliers';
    private $table_common = 'common_code'; 
    private $table_sc = 'sales_contracts';
    private $table_materials = 'materials';
    private $table_semifinished = 'semifinished_goods';
    private $table_machines = 'machines';     
    private $table_inventories = 'inventories'; 
    private $table_grn_details = 'grn_details'; 
    // --- Akhir Definisi ---

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // === HEADER ===

    public function get_all_po() {
        $this->db->select('h.id, h.po_number, h.po_date, h.status, s.nama_supplier');
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_supplier . ' s', 'h.supplier_id = s.id', 'left');
        $this->db->order_by('h.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_po_header_by_id($id) {
        $this->db->select('
            h.*, s.nama_supplier, s.alamat, s.telepon,
            cc_ship.description as shipping_mode_desc,
            cc_pay.description as payment_mode_desc,
            cc_curr.description as currency_desc,
            u_confirm.fullname as confirmer_name,
            u_create.fullname as creator_name 
        ');
        $this->db->from($this->table_header . ' h');
        $this->db->join($this->table_supplier . ' s', 'h.supplier_id = s.id', 'left');
        $this->db->join($this->table_common . ' cc_ship', "h.shipping_mode_code = cc_ship.code AND cc_ship.group_code = 'SHIPPING_MODE'", 'left');
        $this->db->join($this->table_common . ' cc_pay', "h.payment_mode_code = cc_pay.code AND cc_pay.group_code = 'PAYMENT_MODE'", 'left');
        $this->db->join($this->table_common . ' cc_curr', "h.currency_code = cc_curr.code AND cc_curr.group_code = 'CURRENCY'", 'left');
        $this->db->join('users u_confirm', 'h.confirmed_by = u_confirm.id', 'left');
        $this->db->join('users u_create', 'h.created_by = u_create.id', 'left'); 
        $this->db->where('h.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        if (empty($data['status'])) { $data['status'] = 'Draft'; }
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    public function update_header($id, $data) {
        unset($data['status'], $data['confirmed_by'], $data['confirmed_at'], $data['po_number'], $data['created_by'], $data['supplier_id']);
        $this->db->where('id', $id)->where('status', 'Draft');
        $this->db->update($this->table_header, $data);
        return $this->db->affected_rows() > 0; 
    }

    public function delete_header($id) {
        $details_count = $this->db->where('po_header_id', $id)->count_all_results($this->table_details);
        if ($details_count > 0) { return false; }
        $this->db->where('id', $id)->where('status', 'Draft');
        $this->db->delete($this->table_header);
        return $this->db->affected_rows() > 0;
    }

    public function generate_po_number() {
        $prefix = 'PO/' . date('Ym') . '/';
        $this->db->like('po_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table_header, 1)->row();
        $new_num = $last ? (int) substr($last->po_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    public function update_status($id, $new_status, $userId = NULL, $reset_approval = FALSE) {
        $data = ['status' => $new_status];
        if ($new_status == 'Confirmed' && $userId) {
            $data['confirmed_by'] = $userId;
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif (in_array($new_status, ['Draft', 'Submitted']) || $reset_approval) {
            $data['confirmed_by'] = NULL;
            $data['confirmed_at'] = NULL;
        } 
        $this->db->where('id', $id);
        $update_result = $this->db->update($this->table_header, $data);
        return ($update_result && $this->db->affected_rows() > 0);
    }

    // === DETAILS ===

    public function get_po_details_by_id($po_id) {
        $select_clause = "
            d.id, d.po_header_id, d.item_id, d.item_type, d.description, d.qty,
            d.base_price, d.local_price, d.total_local, d.sales_contract_id,
            CASE
                WHEN d.item_type = 'material' THEN m.kode_material
                WHEN d.item_type = 'semifinished' THEN sf.kode_barang
                WHEN d.item_type = 'machine' THEN mc.kode_mesin   
                WHEN d.item_type = 'inventory' THEN i.kode_barang 
            END as item_code,
            CASE
                WHEN d.item_type = 'material' THEN m.nama_material
                WHEN d.item_type = 'semifinished' THEN sf.nama_barang
                WHEN d.item_type = 'machine' THEN mc.nama_mesin   
                WHEN d.item_type = 'inventory' THEN i.nama_barang 
            END as item_name,
            CASE
                WHEN d.item_type = 'material' THEN m.satuan
                WHEN d.item_type = 'semifinished' THEN sf.satuan
                WHEN d.item_type = 'inventory' THEN i.satuan      
                WHEN d.item_type = 'machine' THEN ''            
                ELSE ''
            END as item_unit,
            sc.sc_number
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join($this->table_materials . ' m', 'd.item_id = m.id AND d.item_type = "material"', 'left');
        $this->db->join($this->table_semifinished . ' sf', 'd.item_id = sf.id AND d.item_type = "semifinished"', 'left');
        $this->db->join($this->table_machines . ' mc', 'd.item_id = mc.id AND d.item_type = "machine"', 'left'); 
        $this->db->join($this->table_inventories . ' i', 'd.item_id = i.id AND d.item_type = "inventory"', 'left'); 
        $this->db->join($this->table_sc . ' sc', 'd.sales_contract_id = sc.id', 'left');
        $this->db->where('d.po_header_id', $po_id);
        $this->db->order_by('d.id', 'ASC'); 
        return $this->db->get()->result();
    }

    public function add_item($data) {
        if (empty($data['po_header_id']) || empty($data['item_id'])) {
            log_message('error', 'Po_model::add_item() - Missing po_header_id or item_id.');
            return false;
        }
        if (strpos($data['item_id'], '-') !== false) {
             list($type, $id) = explode('-', $data['item_id']);
             $numeric_item_id = (int)$id; 
             if ($numeric_item_id <= 0) {
                 log_message('error', 'Po_model::add_item() - Invalid numeric ID after explode: ' . $data['item_id']);
                 return false; 
             }
             $data['item_id'] = $numeric_item_id; 
        } else {
             log_message('error', 'Po_model::add_item() - Invalid item_id format received: ' . $data['item_id']);
             return false;
        }
        if ($this->db->insert($this->table_details, $data)) {
            return $this->db->insert_id();
        } else {
             log_message('error', 'Po_model::add_item() - DB Insert failed: ' . print_r($this->db->error(), true));
            return false;
        }
    }

    public function delete_item($id) {
        $this->db->where('id', $id);
        $this->db->delete($this->table_details);
        return $this->db->affected_rows() > 0;
    }

    public function search_all_items($term) {
        $term = $this->db->escape_like_str(trim($term));
        if (empty($term)) { return []; }
        $limit = 15; 

        // Query Material
        // REVISI: Select id, code, name, unit, type
        $this->db->select("m.id, m.kode_material as code, m.nama_material as name, m.satuan as unit, 'material' as item_type");
        $this->db->from($this->table_materials . ' m'); 
        $this->db->group_start()->like('m.kode_material', $term)->or_like('m.nama_material', $term)->group_end();
        $query1 = $this->db->get_compiled_select(); 

        // Query Semifinished
        // REVISI: Select id, code, name, unit, type
        $this->db->select("sf.id, sf.kode_barang as code, sf.nama_barang as name, sf.satuan as unit, 'semifinished' as item_type");
        $this->db->from($this->table_semifinished . ' sf'); 
        $this->db->group_start()->like('sf.kode_barang', $term)->or_like('sf.nama_barang', $term)->group_end();
        $query2 = $this->db->get_compiled_select();

        // Query Inventory
        // REVISI: Select id, code, name, unit, type (Pastikan kolom inventory benar)
        $this->db->select("i.id, i.kode_barang as code, i.nama_barang as name, i.satuan as unit, 'inventory' as item_type");
        $this->db->from($this->table_inventories . ' i'); 
        $this->db->group_start()->like('i.kode_barang', $term)->or_like('i.nama_barang', $term)->group_end();
        $query3 = $this->db->get_compiled_select(); 

        // Query Machine 
        // REVISI: Select id, code, name, unit, type
        $this->db->select("mc.id, mc.kode_mesin as code, mc.nama_mesin as name, '' as unit, 'machine' as item_type"); 
        $this->db->from($this->table_machines . ' mc'); 
        $this->db->group_start()->like('mc.kode_mesin', $term)->or_like('mc.nama_mesin', $term)->group_end();
        $query4 = $this->db->get_compiled_select(); 

        $final_sql = "($query1) UNION ALL ($query2) UNION ALL ($query3) UNION ALL ($query4) LIMIT " . $limit;
        $final_query = $this->db->query($final_sql);
            
        if (!$final_query) { /* ... (error logging) ... */ return []; }    
        return $final_query->result(); // Kembalikan hasil query
    }
    public function get_item_details($item_id_string) {
        if (empty($item_id_string) || strpos($item_id_string, '-') === false) return null; 
        list($type, $id) = explode('-', $item_id_string);
        $id = (int)$id;
        if ($id <= 0) return null;
        $result = null;
        switch ($type) {
            case 'material':
                $this->db->select("id, kode_material as code, nama_material as name, satuan as unit, 'material' as item_type");
                $result = $this->db->get_where($this->table_materials, ['id' => $id])->row();
                break;
            case 'semifinished':
                 $this->db->select("id, kode_barang as code, nama_barang as name, satuan as unit, 'semifinished' as item_type");
                $result = $this->db->get_where($this->table_semifinished, ['id' => $id])->row();
                break;
             case 'inventory': 
                 $this->db->select("id, kode_barang as code, nama_barang as name, satuan as unit, 'inventory' as item_type");
                $result = $this->db->get_where($this->table_inventories, ['id' => $id])->row(); 
                break;
             case 'machine': 
                 $this->db->select("id, kode_mesin as code, nama_mesin as name, '' as unit, 'machine' as item_type"); 
                $result = $this->db->get_where($this->table_machines, ['id' => $id])->row(); 
                break;
        }
        return $result;
    }

    public function check_if_po_has_receipts($po_id) {
        $this->db->select('grn.id'); 
        $this->db->from($this->table_grn_details . ' grn'); 
        $this->db->join($this->table_details . ' pod', 'grn.source_doc_detail_id = pod.id AND grn.source_doc_type = "PO"', 'inner'); 
        $this->db->where('pod.po_header_id', $po_id); 
        $this->db->limit(1); 
        $query = $this->db->get();
        return ($query && $query->num_rows() > 0); 
    }

} // End Class