<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Machine_model extends CI_Model {

    private $table = 'machines';

    public function get_all() {
        $this->db->select('
            m.*, 
            cc_cat.description as kategori_desc,
            cc_sat.description as satuan_desc 
        ');
        $this->db->from('machines m');
        $this->db->join('common_code cc_cat', 'm.kategori = cc_cat.code AND cc_cat.group_code = "MACHINE_CATEGORY"', 'left');
        $this->db->join('common_code cc_sat', 'm.satuan = cc_sat.code AND cc_sat.group_code = "SATUAN"', 'left');

        $this->db->order_by('m.kode_mesin', 'ASC');
        return $this->db->get()->result();
    }

    // ✅ FUNGSI BARU: Mengambil daftar kategori dinamis
    public function get_kategori_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'MACHINE_CATEGORY', // Pastikan grup ini ada
            'status' => 'active'
        ])->result();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    // ✅ DITAMBAHKAN: 'return' agar controller bisa cek status
    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    // ✅ DIUBAH: Sekarang menggunakan Soft Delete (mengubah status, bukan menghapus)
    public function delete($id) {
        return $this->db->where('id', $id)->update($this->table, ['status' => 'Nonaktif']);
    }

    public function check_stock_status($machine_id) {
        $machine = $this->db->where('id', $machine_id)->get($this->table)->row();
        
        if (!$machine) {
            return [
                'status' => 'error',
                'message' => 'Mesin tidak ditemukan'
            ];
        }

        // Ambil current stock dari stock_ledger
        $this->db->select('balance');
        $this->db->from('stock_ledger');
        $this->db->where('item_id', $machine_id);
        $this->db->where('item_type', 'machine');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $ledger = $this->db->get()->row();
        
        $current_stock = $ledger ? (float)$ledger->balance : 0;
        $minimum_stock = isset($machine->minimum_stock) ? (float)$machine->minimum_stock : 0;
        $deficit = $minimum_stock - $current_stock;
        
        if ($current_stock <= $minimum_stock && $minimum_stock > 0) {
            return [
                'status' => 'warning',
                'alert' => true,
                'message' => "⚠️ ALERT: Stock mesin '{$machine->nama_mesin}' mencapai atau di bawah minimum ({$current_stock} ≤ {$minimum_stock})",
                'kode_mesin' => $machine->kode_mesin,
                'nama_mesin' => $machine->nama_mesin,
                'satuan' => $machine->satuan,
                'current_stock' => $current_stock,
                'minimum_stock' => $minimum_stock,
                'deficit' => $deficit
            ];
        }
        
        return [
            'status' => 'ok',
            'alert' => false,
            'current_stock' => $current_stock,
            'minimum_stock' => $minimum_stock,
            'deficit' => 0
        ];
    }
}