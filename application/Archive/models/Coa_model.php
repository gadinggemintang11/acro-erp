<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coa_model extends CI_Model {

    private $table = 'chart_of_accounts';

    public function get_all_as_tree()
    {
        $this->db->order_by('account_code', 'ASC');
        $query = $this->db->get($this->table);
        $accounts = $query->result_array();
        
        // Buat array tree
        return $this->build_tree($accounts);
    }

    // Fungsi rekursif untuk membangun struktur tree
    private function build_tree(array $elements, $parentId = 0) {
        $branch = array();
        // Normalize parentId to int for safe comparisons
        $parentId = (int) $parentId;
        foreach ($elements as $element) {
            // Ensure element parent_id is integer (handle null/empty)
            $elemParent = isset($element['parent_id']) ? (int)$element['parent_id'] : 0;
            if ($elemParent === $parentId) {
                $children = $this->build_tree($elements, $element['id']);
                if ($children) {
                    $element['children'] = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    // Ambil hanya akun header (untuk dropdown parent)
    public function get_header_accounts($exclude_id = null)
    {
        $this->db->where('is_header', 1);
        $this->db->where('status', 'active');
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id); // Jangan biarkan akun jadi parent-nya sendiri
        }
        $this->db->order_by('account_code', 'ASC');
        $query = $this->db->get($this->table);
        return $query->result();
    }

    public function get_by_id($id)
    {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data)
    {
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    // Cek sebelum hapus
    public function is_deletable($id)
    {
        // 1. Cek apakah punya anak (sub-akun)
        $this->db->where('parent_id', $id);
        $query_children = $this->db->get($this->table);
        if ($query_children->num_rows() > 0) {
            return ['status' => FALSE, 'message' => 'Akun ini memiliki sub-akun. Hapus sub-akun terlebih dahulu.'];
        }

        // 2. Cek apakah sudah dipakai di jurnal (ASUMSI NAMA TABEL: journal_details)
        // (Baris ini bisa Anda aktifkan nanti setelah modul Jurnal Entry jadi)
        /*
        $this->db->where('account_id', $id);
        $query_journal = $this->db->get('journal_details');
        if ($query_journal->num_rows() > 0) {
            return ['status' => FALSE, 'message' => 'Akun ini sudah memiliki transaksi jurnal. Tidak dapat dihapus.'];
        }
        */

        return ['status' => TRUE, 'message' => 'OK'];
    }

    public function delete($id)
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }
}