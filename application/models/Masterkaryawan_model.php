<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master Karyawan — data karyawan untuk reimbursement (ERF).
 * Field: nama_karyawan, nip, bank_name, bank_account, status.
 */
class Masterkaryawan_model extends CI_Model {

    private $table = 'master_karyawan';

    /** Dropdown ERF: hanya karyawan aktif. */
    public function get_active() {
        return $this->db->select('id, nama_karyawan, nip, bank_name, bank_account')
            ->where('status', 'active')
            ->order_by('nama_karyawan', 'ASC')
            ->get($this->table)->result();
    }

    public function get_by_id($id) {
        return $this->db->where('id', $id)->get($this->table)->row();
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    /** Soft delete (nonaktifkan). */
    public function delete($id) {
        return $this->db->where('id', $id)->update($this->table, ['status' => 'inactive']);
    }

    /** Bulk insert untuk import Excel. */
    public function insert_batch($rows) {
        if (empty($rows)) return 0;
        $this->db->insert_batch($this->table, $rows);
        return count($rows);
    }

    /** Cek NIP duplikat (opsional exclude id tertentu). */
    public function nip_exists($nip, $exclude_id = null) {
        if (empty($nip)) return false;
        $this->db->where('nip', $nip);
        if ($exclude_id) $this->db->where('id !=', (int)$exclude_id);
        return $this->db->count_all_results($this->table) > 0;
    }

    // ------------------------------------------------------------
    // LIST + PAGINATION
    // ------------------------------------------------------------
    public function get_paginated($search = '', $status = '', $limit = 10, $offset = 0) {
        $this->_apply_filters($search, $status);
        $this->db->order_by('id', 'DESC')->limit($limit, $offset);
        return $this->db->get()->result();
    }

    public function count_all($search = '', $status = '') {
        $this->_apply_filters($search, $status);
        return $this->db->count_all_results();
    }

    public function get_for_export($search = '', $status = '') {
        $this->_apply_filters($search, $status);
        $this->db->order_by('nama_karyawan', 'ASC');
        return $this->db->get()->result();
    }

    private function _apply_filters($search, $status) {
        $this->db->from($this->table);
        if (!empty($search)) {
            $this->db->group_start()
                ->like('nama_karyawan', $search)
                ->or_like('nip', $search)
                ->or_like('bank_name', $search)
                ->or_like('bank_account', $search)
            ->group_end();
        }
        if (!empty($status)) {
            $this->db->where('status', $status);
        }
    }
}
