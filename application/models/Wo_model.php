<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Wo_model extends CI_Model {

    private $table_header = 'wo_header';
    private $table_details = 'wo_details_material';

    // === HEADER ===
    public function get_all($filters = []) {
        $this->db->select('
            wo.id, wo.wo_number, wo.planned_qty, wo.planned_start_date, wo.status,
            sc.sc_number,
            fg.nama_barang as fg_name
        ');
        $this->db->from($this->table_header . ' wo');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        if (!empty($filters['search'])) {
             $term = $this->db->escape_like_str($filters['search']);
             $this->db->group_start()->like('wo.wo_number', $term)->or_like('sc.sc_number', $term)->or_like('fg.nama_barang', $term)->group_end();
        }
        $this->db->order_by('wo.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id) {
         // ✅ FIX: Komentar yang menyebabkan error SQL sudah dihapus
         $this->db->select('
            wo.*,
            sc.sc_number, sc.po_qty as sc_qty,
            fg.kode_barang as fg_code, fg.nama_barang as fg_name,
            c.nama_customer,
            creator.fullname as creator_name,
            confirmer.fullname as confirmer_name
        ');
        $this->db->from($this->table_header . ' wo');
        $this->db->join('sales_contracts sc', 'wo.sales_contract_id = sc.id', 'left');
        $this->db->join('finished_goods fg', 'wo.fg_id = fg.id', 'left');
        $this->db->join('customers c', 'sc.customer_id = c.id', 'left');
        $this->db->join('users creator', 'wo.created_by = creator.id', 'left');
        $this->db->join('users confirmer', 'wo.confirmed_by = confirmer.id', 'left');
        $this->db->where('wo.id', $id);
        return $this->db->get()->row();
    }

    public function insert_header($data) {
        $this->db->insert($this->table_header, $data);
        return $this->db->insert_id();
    }

    public function update_header($id, $data) {
        unset($data['sales_contract_id'], $data['fg_id']);
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    public function delete_header($id) {
        return $this->db->where('id', $id)->delete($this->table_header);
    }

    public function update_status($id, $new_status, $user_id = null) {
         $data = ['status' => $new_status];
        if ($new_status == 'In Progress' && $user_id) {
            $data['confirmed_by'] = $user_id;
            $data['confirmed_at'] = date('Y-m-d H:i:s');
        } elseif ($new_status == 'Planned') {
            $data['confirmed_by'] = NULL;
            $data['confirmed_at'] = NULL;
        }
        return $this->db->where('id', $id)->update($this->table_header, $data);
    }

    public function generate_wo_number() {
        $prefix = 'WO/' . date('Ym') . '/';
        $this->db->like('wo_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table_header, 1)->row();
        $new_num = $last ? (int) substr($last->wo_number, -4) + 1 : 1;
        return $prefix . str_pad($new_num, 4, '0', STR_PAD_LEFT);
    }

    // === DETAILS MATERIAL ===
    public function get_planned_materials($wo_header_id) {
        $select_clause = "
            d.id, d.wo_header_id, d.planned_qty, d.unit_measure,
            d.component_item_id as item_id, d.component_item_type as item_type,
            CASE
                WHEN d.component_item_type = 'material' THEN m.kode_material
                WHEN d.component_item_type = 'semifinished' THEN sf.kode_barang
            END as item_code,
            CASE
                WHEN d.component_item_type = 'material' THEN m.nama_material
                WHEN d.component_item_type = 'semifinished' THEN sf.nama_barang
            END as item_name
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from($this->table_details . ' d');
        $this->db->join('materials m', 'd.component_item_id = m.id AND d.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'd.component_item_id = sf.id AND d.component_item_type = "semifinished"', 'left');
        $this->db->where('d.wo_header_id', $wo_header_id);
        $this->db->order_by('item_name', 'ASC');
        return $this->db->get()->result();
    }

    public function insert_material($data) {
        $component = null; $satuan = null;
        if($data['component_item_type'] == 'material'){
            $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('materials')->row();
            $satuan = $component ? $component->satuan : null;
        } else if ($data['component_item_type'] == 'semifinished'){
             $component = $this->db->select('satuan')->where('id', $data['component_item_id'])->get('semifinished_goods')->row();
             $satuan = $component ? $component->satuan : null;
        }
        $data['unit_measure'] = $satuan ?? 'PCS';

        return $this->db->insert($this->table_details, $data);
    }

    public function delete_material($id) {
        return $this->db->where('id', $id)->delete($this->table_details);
    }

    // === HELPERS ===
    public function get_sc_details_for_wo($sc_id) {
        $this->db->select('sc.id, sc.sc_number, sc.po_qty, sc.fg_id, fg.nama_barang as fg_name, fg.kode_barang as fg_code');
        $this->db->from('sales_contracts sc');
        $this->db->join('finished_goods fg', 'sc.fg_id = fg.id', 'inner');
        $this->db->where('sc.id', $sc_id);
        $this->db->where('sc.status', 'Open');
        return $this->db->get()->row_array();
    }

    public function get_bom_components_by_fg($fg_id) {
        $bom_header = $this->db->select('id')->where('fg_id', $fg_id)->where('status', 'Active')->get('bom_header')->row();
        if(!$bom_header) return [];
        $bom_header_id = $bom_header->id;
        $select_clause = "
            bd.quantity_required, bd.unit_measure,
            CASE WHEN bd.component_item_type = 'material' THEN m.kode_material ELSE sf.kode_barang END as item_code,
            CASE WHEN bd.component_item_type = 'material' THEN m.nama_material ELSE sf.nama_barang END as item_name,
            bd.component_item_type as item_type
        ";
        $this->db->select($select_clause, FALSE);
        $this->db->from('bom_details bd');
        $this->db->join('materials m', 'bd.component_item_id = m.id AND bd.component_item_type = "material"', 'left');
        $this->db->join('semifinished_goods sf', 'bd.component_item_id = sf.id AND bd.component_item_type = "semifinished"', 'left');
        $this->db->where('bd.bom_header_id', $bom_header_id);
        $this->db->order_by('item_name', 'ASC');
        return $this->db->get()->result();
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
}