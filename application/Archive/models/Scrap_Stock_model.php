<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Scrap_Stock_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * ==============================================================
     * 🔹 LAPORAN RINCI STOK SCRAP (PER JENIS SCRAP)
     * ==============================================================
     */
    public function get_detailed_stock($filters = [], $limit = 25, $offset = 0)
    {
        $search = isset($filters['search']) && $filters['search'] !== '' ? trim($filters['search']) : null;

        $this->db->select("
            m.id AS scrap_id,
            m.scrap_code,
            m.scrap_name,
            c.description AS satuan_desc,
            IFNULL(SUM(DISTINCT sid.qty),0) AS total_in,
            IFNULL(SUM(DISTINCT sod.qty),0) AS total_out,
            (IFNULL(SUM(DISTINCT sid.qty),0) - IFNULL(SUM(DISTINCT sod.qty),0)) AS total_available
        ");
        $this->db->from('master_scrap m');
        $this->db->join('scrap_in_detail sid', 'sid.scrap_id = m.id', 'left');
        $this->db->join('scrap_in_header sih', 'sih.id = sid.header_id AND sih.status = "Confirmed"', 'left');
        $this->db->join('scrap_out_detail sod', 'sod.scrap_id = m.id', 'left');
        $this->db->join('scrap_out_header soh', 'soh.id = sod.header_id AND soh.status = "Confirmed"', 'left');
        $this->db->join('common_code c', "c.code = m.unit AND c.group_code = 'SATUAN'", 'left');

        if ($search) {
            $this->db->group_start();
            $this->db->like('m.scrap_code', $search);
            $this->db->or_like('m.scrap_name', $search);
            $this->db->group_end();
        }

        $this->db->group_by('m.id, m.scrap_code, m.scrap_name, c.description');
        $this->db->order_by('m.scrap_code', 'ASC');

        if ($limit > 0) $this->db->limit($limit, $offset);

        return $this->db->get()->result();
    }

    public function count_detailed_stock($filters = [])
    {
        $search = isset($filters['search']) && $filters['search'] !== '' ? trim($filters['search']) : null;
        $this->db->from('master_scrap m');
        if ($search) {
            $this->db->group_start();
            $this->db->like('m.scrap_code', $search);
            $this->db->or_like('m.scrap_name', $search);
            $this->db->group_end();
        }
        return $this->db->count_all_results();
    }

    /**
     * ==============================================================
     * 🔹 RINGKASAN STOK SCRAP (SUMMARY)
     * ==============================================================
     */
   public function get_stock_summary()
    {
        $sql = "
            SELECT 
                m.id AS scrap_id,
                m.scrap_code,
                m.scrap_name,
                c.description AS satuan_desc,

                (
                    SELECT IFNULL(SUM(d_in.qty), 0)
                    FROM scrap_in_detail d_in
                    JOIN scrap_in_header h_in ON h_in.id = d_in.header_id
                    WHERE d_in.scrap_id = m.id
                    AND h_in.status = 'Confirmed'
                ) AS total_in,

               - 

                (
                    SELECT IFNULL(SUM(d_out.qty), 0)
                    FROM scrap_out_detail d_out
                    JOIN scrap_out_header h_out ON h_out.id = d_out.header_id
                    WHERE d_out.scrap_id = m.id
                    AND h_out.status = 'Confirmed'
                ) AS total_out,

               
                (
                    (
                        SELECT IFNULL(SUM(d_in.qty), 0)
                        FROM scrap_in_detail d_in
                        JOIN scrap_in_header h_in ON h_in.id = d_in.header_id
                        WHERE d_in.scrap_id = m.id
                        AND h_in.status = 'Confirmed'
                    )
                    -
                    (
                        SELECT IFNULL(SUM(d_out.qty), 0)
                        FROM scrap_out_detail d_out
                        JOIN scrap_out_header h_out ON h_out.id = d_out.header_id
                        WHERE d_out.scrap_id = m.id
                        AND h_out.status = 'Confirmed'
                    )
                ) AS total_available

            FROM master_scrap m
            LEFT JOIN common_code c ON c.code = m.unit AND c.group_code = 'SATUAN'
            ORDER BY m.scrap_code ASC
        ";
        return $this->db->query($sql)->result();
    }


    public function count_summary_stock()
    {
        $row = $this->db->select('COUNT(id) AS total')->get('master_scrap')->row();
        return (int)$row->total;
    }

    /**
     * ==============================================================
     * 🔹 DETAIL TRACEABILITY SCRAP
     * ==============================================================
     */
    public function get_summary_stock_by_id($scrap_id)
    {
        $sql = "
            SELECT 
                m.id AS scrap_id,
                m.scrap_code,
                m.scrap_name,
                m.unit,
                c.description AS satuan_desc,

               
                (
                    SELECT IFNULL(SUM(d_in.qty), 0)
                    FROM scrap_in_detail d_in
                    JOIN scrap_in_header h_in ON h_in.id = d_in.header_id
                    WHERE d_in.scrap_id = m.id
                    AND h_in.status = 'Confirmed'
                ) AS total_in,

           
                (
                    SELECT IFNULL(SUM(d_out.qty), 0)
                    FROM scrap_out_detail d_out
                    JOIN scrap_out_header h_out ON h_out.id = d_out.header_id
                    WHERE d_out.scrap_id = m.id
                    AND h_out.status = 'Confirmed'
                ) AS total_out,

       
                (
                    (
                        SELECT IFNULL(SUM(d_in.qty), 0)
                        FROM scrap_in_detail d_in
                        JOIN scrap_in_header h_in ON h_in.id = d_in.header_id
                        WHERE d_in.scrap_id = m.id
                        AND h_in.status = 'Confirmed'
                    )
                    -
                    (
                        SELECT IFNULL(SUM(d_out.qty), 0)
                        FROM scrap_out_detail d_out
                        JOIN scrap_out_header h_out ON h_out.id = d_out.header_id
                        WHERE d_out.scrap_id = m.id
                        AND h_out.status = 'Confirmed'
                    )
                ) AS total_available

            FROM master_scrap m
            LEFT JOIN common_code c ON c.code = m.unit AND c.group_code = 'SATUAN'
            WHERE m.id = ?
            GROUP BY m.id, m.scrap_code, m.scrap_name, m.unit, c.description
        ";

        return $this->db->query($sql, [$scrap_id])->row();
    }



    public function get_scrap_info($scrap_id)
    {
        return $this->db->select('id, scrap_code, scrap_name, unit')
                        ->where('id', $scrap_id)
                        ->get('master_scrap')->row();
    }

    /**
     * 📥 Riwayat Masuk (Confirmed)
     */
    public function get_in_history($scrap_id) {
        $this->db->select('
            h.doc_no, 
            h.doc_date, 
            h.status, 
            d.qty
        ');
        $this->db->from('scrap_in_detail d');
        $this->db->join('scrap_in_header h', 'h.id = d.header_id');
        $this->db->where('d.scrap_id', $scrap_id);
        $this->db->where('h.status', 'Confirmed');
        $this->db->order_by('h.doc_date', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * 📤 Riwayat Keluar (Confirmed)
     * + Relasi ke BC Type & Customer
     */
    public function get_out_history($scrap_id) {
        $this->db->select('
            h.doc_no, 
            h.doc_date, 
            h.status,
            h.bc_type_code, 
            cc.description AS bc_type_name,
            h.bc_number, 
            h.bc_date, 
            cust.nama_customer AS destination_name, 
            d.qty
        ');
        $this->db->from('scrap_out_detail d');
        $this->db->join('scrap_out_header h', 'h.id = d.header_id');
        $this->db->join('common_code cc', "cc.code = h.bc_type_code AND cc.group_code = 'BC_TYPE'", 'left');
        $this->db->join('customers cust', 'cust.id = h.destination', 'left');
        $this->db->where('d.scrap_id', $scrap_id);
        $this->db->where('h.status', 'Confirmed');
        $this->db->order_by('h.doc_date', 'ASC');
        return $this->db->get()->result();
    }
}
