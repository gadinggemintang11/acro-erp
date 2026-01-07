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
    public function search_tree($search)
    {
        $this->db->order_by('account_code', 'ASC');
        $this->db->group_start();
        $this->db->like('account_code', $search);
        $this->db->or_like('account_name', $search);
        $this->db->group_end();
        $accounts = $this->db->get($this->table)->result_array();
        // Ambil semua parent dari hasil search agar tree tetap utuh
        if (!empty($accounts)) {
            $ids = array_column($accounts, 'id');
            $parent_ids = array_column($accounts, 'parent_id');
            $all_ids = array_unique(array_merge($ids, $parent_ids));
            // Ambil semua parent hingga root
            $checked_ids = $all_ids;
            do {
                $this->db->where_in('id', $checked_ids);
                $parents = $this->db->get($this->table)->result_array();
                $new_parent_ids = [];
                foreach ($parents as $p) {
                    if ($p['parent_id'] && !in_array($p['parent_id'], $all_ids)) {
                        $all_ids[] = $p['parent_id'];
                        $new_parent_ids[] = $p['parent_id'];
                    }
                }
                $checked_ids = $new_parent_ids;
            } while (!empty($checked_ids));
            $this->db->order_by('account_code', 'ASC');
            $this->db->where_in('id', $all_ids);
            $all_accounts = $this->db->get($this->table)->result_array();
            return $this->build_tree($all_accounts);
        } else {
            return [];
        }
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