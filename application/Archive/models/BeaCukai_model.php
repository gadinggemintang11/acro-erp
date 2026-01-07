<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BeaCukai_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Ambil data dokumen BC berdasarkan ID header
     */
    public function get_by_id($header_id) {
        $this->db->select('
            h.id,
            h.bc_type_code,
            h.bc_number,
            h.bc_date,
            h.supplier_id,
            s.nama_supplier AS tujuan,
            h.created_by,
            h.created_at
        ');
        $this->db->from('grn_header h');
        $this->db->join('suppliers s', 's.id = h.supplier_id', 'left');
        $this->db->where('h.id', $header_id);
        return $this->db->get()->row_array();
    }

    /**
     * Ambil data dokumen BC berdasarkan nomor BC (fallback)
     */
    public function get_by_number($bc_number) {
        $this->db->select('
            h.id,
            h.bc_type_code,
            h.bc_number,
            h.bc_date,
            h.supplier_id,
            s.nama_supplier AS tujuan,
            h.created_by,
            h.created_at
        ');
        $this->db->from('grn_header h');
        $this->db->join('suppliers s', 's.id = h.supplier_id', 'left');
        $this->db->where('h.bc_number', $bc_number);
        return $this->db->get()->row_array();
    }

    /**
     * Ambil detail scrap dari dokumen BC (penerimaan)
     */
    public function get_scrap_details($header_id, $type = 'grn') {
        if ($type === 'grn') {
            // Ambil dulu semua baris dari grn_details
            $details = $this->db
                ->select('d.id, d.item_id, d.item_type, d.receive_qty AS qty, d.unit_value')
                ->from('grn_details d')
                ->where('d.grn_header_id', $header_id)
                ->get()
                ->result_array();

            // Gabungkan dengan master tabel sesuai tipe
            foreach ($details as &$d) {
                if ($d['item_type'] === 'material') {
                    $mat = $this->db
                        ->select('kode_material AS code, nama_material AS name, satuan AS unit')
                        ->where('id', $d['item_id'])
                        ->get('materials')
                        ->row_array();
                } elseif ($d['item_type'] === 'finished_goods') {
                    $mat = $this->db
                        ->select('kode_barang AS code, nama_barang AS name, satuan AS unit')
                        ->where('id', $d['item_id'])
                        ->get('finished_goods')
                        ->row_array();
                } elseif ($d['item_type'] === 'semifinished') {
                    $mat = $this->db
                        ->select('kode_barang AS code, nama_barang AS name, satuan AS unit')
                        ->where('id', $d['item_id'])
                        ->get('semifinished_goods')
                        ->row_array();
                } else {
                    $mat = $this->db
                        ->select('scrap_code AS code, scrap_name AS name, unit')
                        ->where('id', $d['item_id'])
                        ->get('master_scrap')
                        ->row_array();
                }

                // Gabungkan hasil ke array utama
                $d['item_code'] = $mat['code'] ?? '-';
                $d['item_name'] = $mat['name'] ?? '-';
                $d['unit'] = $mat['unit'] ?? '-';
            }
            unset($d);

            return $details;
        } else {
            // Untuk scrap_out_detail
            $this->db->select('
                d.id,
                d.scrap_id AS item_id,
                s.scrap_code AS item_code,
                s.scrap_name AS item_name,
                d.qty,
                d.unit
            ');
            $this->db->from('scrap_out_detail d');
            $this->db->join('master_scrap s', 's.id = d.scrap_id', 'left');
            $this->db->where('d.header_id', $header_id);

            return $this->db->get()->result_array();
        }
    }

}
