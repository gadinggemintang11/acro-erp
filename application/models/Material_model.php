<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material_model extends CI_Model {

    // Nama tabel di database
    private $_table = "materials";

    /**
     * Fungsi untuk mengambil semua data dari tabel materials
     */
    public function getAll()
    {
        // Sama artinya dengan: "SELECT * FROM materials"
        return $this->db->get($this->_table)->result_array();
    }
    public function save($data)
    {
        // Sama artinya dengan: "INSERT INTO materials (kolom1, kolom2, ...) VALUES (nilai1, nilai2, ...)"
        return $this->db->insert($this->_table, $data);
    }
    public function getLastByPrefix($prefix)
    {
        $this->db->like('item_code', $prefix, 'after'); // 'after' artinya prefix harus di awal
        $this->db->order_by('item_code', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get($this->_table);
        return $query->row_array();
    }
    public function delete($id)
    {
        // Sama artinya dengan: "DELETE FROM materials WHERE id = $id"
        return $this->db->delete($this->_table, array("id" => $id));
    }
    public function getById($id)
    {
        return $this->db->get_where($this->_table, ["id" => $id])->row_array();
    }
    public function update()
    {
        $post = $this->input->post();
        // Ambil ID dari hidden input di form
        $this->id = $post["id"]; 
        $this->item_name = $post["item_name"];
        $this->material_category = $post["material_category"];
        $this->sub_category = $post["sub_category"];
        $this->item_type = $post["item_type"];
        $this->unit = $post["unit"];
        $this->hs_code = $post["hs_code"];
        $this->updated_at = date('Y-m-d H:i:s'); // Tambahkan timestamp update

        // Jalankan query UPDATE
        return $this->db->update($this->_table, $this, array('id' => $post['id']));
    }
}