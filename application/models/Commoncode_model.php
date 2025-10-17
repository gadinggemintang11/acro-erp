<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Commoncode_model extends CI_Model {

    private $table = 'common_code';

    public function get_all()
    {
        return $this->db->order_by('group_code', 'ASC')->get($this->table)->result();
    }

    public function get_by_group($group)
    {
        return $this->db->where('group_code', $group)->where('status', 'active')->get($this->table)->result();
    }

    public function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data)
    {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id)
    {
        return $this->db->where('id', $id)->delete('common_code');
    }

}
