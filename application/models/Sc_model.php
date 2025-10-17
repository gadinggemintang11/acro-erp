<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sc_model extends CI_Model {

    private $table = 'sales_contracts';

    public function get_all() {
        $this->db->select('
            sc.id, sc.sc_number, sc.contract_date, sc.po_qty, sc.total_price, sc.currency_code, sc.status,
            c.nama_customer,
            fg.nama_barang as style_name
        ');
        $this->db->from($this->table . ' sc');
        $this->db->join('customers c', 'sc.customer_id = c.id', 'left');
        $this->db->join('finished_goods fg', 'sc.fg_id = fg.id', 'left');
        $this->db->order_by('sc.id', 'DESC');
        return $this->db->get()->result();
    }
    
    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    public function get_open_contracts() {
        $this->db->select('sc.id, sc.sc_number, fg.nama_barang as style_name');
        $this->db->from($this->table . ' sc');
        $this->db->join('finished_goods fg', 'sc.fg_id = fg.id', 'left');
        $this->db->where('sc.status', 'Open');
        $this->db->order_by('sc.id', 'DESC');
        return $this->db->get()->result();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    // Soft delete
    public function delete($id) {
        return $this->db->where('id', $id)->update($this->table, ['status' => 'Cancelled']);
    }

    public function generate_sc_number() {
        $prefix = 'SC/' . date('Y') . '/';
        $this->db->like('sc_number', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $last = $this->db->get($this->table, 1)->row();

        if ($last) {
            $last_num = (int) substr($last->sc_number, -5);
            $new_num = str_pad($last_num + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $new_num = '00001';
        }
        return $prefix . $new_num;
    }
}