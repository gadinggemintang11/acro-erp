<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Semifinished_model extends CI_Model {

    public function get_all() {
        return $this->db->order_by('id','DESC')->get('semifinished_goods')->result();
    }

    public function get_satuan_list() {
        return $this->db->where('group_code','SATUAN')->where('status','active')->get('common_code')->result();
    }
    
    public function get_kategori_list() {
        return $this->db->get_where('common_code', [
            'group_code' => 'SEMIFINISHED_CATEGORY',
            'status' => 'active'
        ])->result();
    }

    public function insert($data) {
        return $this->db->insert('semifinished_goods', $data);
    }

    public function update($id, $data) {
        return $this->db->where('id',$id)->update('semifinished_goods', $data);
    }

    // ✅ DIUBAH: Sekarang menggunakan Soft Delete
    public function delete($id) {
        return $this->db->where('id',$id)->update('semifinished_goods', ['status' => 'inactive']);
    }

    public function generate_kode($kategori, $asal) {
        $kat = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($kategori));
        $katprefix = substr($kat, 0, 3);
        if (strlen($katprefix) < 2) $katprefix = str_pad($katprefix, 2, 'X');

        $asal_prefix = ($asal === 'Impor') ? 'I' : 'L';
        $prefix = "SF-{$katprefix}-{$asal_prefix}";

        $this->db->like('kode_barang', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $row = $this->db->get('semifinished_goods', 1)->row();

        if ($row) {
            $lastNum = (int) substr($row->kode_barang, -4);
            $num = $lastNum + 1;
        } else {
            $num = 1;
        }

        return sprintf("%s-%04d", $prefix, $num);
    }

    public function check_stock_status($semifinished_id) {
        $semifinished = $this->db->where('id', $semifinished_id)->get('semifinished_goods')->row();
        
        if (!$semifinished) {
            return [
                'status' => 'error',
                'message' => 'Semifinished goods tidak ditemukan'
            ];
        }

        // Ambil current stock dari stock_ledger
        $this->db->select('balance');
        $this->db->from('stock_ledger');
        $this->db->where('item_id', $semifinished_id);
        $this->db->where('item_type', 'semifinished');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $ledger = $this->db->get()->row();
        
        $current_stock = $ledger ? (float)$ledger->balance : 0;
        $minimum_stock = isset($semifinished->minimum_stock) ? (float)$semifinished->minimum_stock : 0;
        $deficit = $minimum_stock - $current_stock;
        
        if ($current_stock <= $minimum_stock && $minimum_stock > 0) {
            return [
                'status' => 'warning',
                'alert' => true,
                'message' => "⚠️ ALERT: Stock semifinished '{$semifinished->nama_barang}' mencapai atau di bawah minimum ({$current_stock} ≤ {$minimum_stock})",
                'kode_barang' => $semifinished->kode_barang,
                'nama_barang' => $semifinished->nama_barang,
                'satuan' => $semifinished->satuan,
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