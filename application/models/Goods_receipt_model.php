<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Goods_receipt_model extends CI_Model {

    private $_table = "goods_receipts";

    public function getAll($filters = [])
    {
        $this->db->select('gr.*, s.supplier_name, u.full_name as creator_name');
        $this->db->from($this->_table . ' as gr');
        $this->db->join('suppliers as s', 'gr.supplier_id = s.id');
        $this->db->join('users as u', 'gr.created_by = u.id');

        // Menerapkan filter jika ada
        if (!empty($filters['start_date'])) {
            $this->db->where('gr.receipt_date >=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $this->db->where('gr.receipt_date <=', $filters['end_date']);
        }
        if (!empty($filters['bc_type'])) {
            $this->db->where('gr.bc_type', $filters['bc_type']);
        }

        $this->db->order_by('gr.id', 'DESC');
        return $this->db->get()->result_array();
    }

    public function getById($id)
    {
        return $this->db->get_where($this->_table, ["id" => $id])->row_array();
    }

    public function save($data)
    {
        return $this->db->insert($this->_table, $data);
    }

    public function update($data, $id)
    {
        return $this->db->update($this->_table, $data, array('id' => $id));
    }


    /**
     * Fungsi khusus untuk membuat Nomor Penerimaan otomatis
     * Format: GR-YYMMDD-XXXX (Contoh: GR-251014-0001)
     */
    public function generateReceiptNo()
    {
        $prefix = 'GR-' . date('ymd') . '-';
        $this->db->like('receipt_no', $prefix, 'after');
        $this->db->order_by('receipt_no', 'DESC');
        $this->db->limit(1);
        $last_data = $this->db->get($this->_table)->row_array();
        
        $new_num = $last_data ? (int)substr($last_data['receipt_no'], -4) + 1 : 1;
        $sequence = str_pad($new_num, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $sequence;
    }

    public function delete($id)
    {
        // Memulai transaction
        $this->db->trans_start();
        
        // Hapus dulu semua item di tabel detail yang berhubungan dengan header ini
        $this->db->delete('goods_receipt_details', array('receipt_id' => $id));
        
        // Setelah itu, baru hapus header-nya
        $this->db->delete($this->_table, array('id' => $id));
        
        // Menyelesaikan transaction
        $this->db->trans_complete();

        // Mengembalikan status transaction (true jika berhasil, false jika gagal)
        return $this->db->trans_status();
    }
    
}