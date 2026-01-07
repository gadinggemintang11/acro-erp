<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Commoncode_model extends CI_Model {

    private $table = 'common_code';

    public function get_all()
    {
        return $this->db->order_by('group_code', 'ASC')->get($this->table)->result();
    }

    public function get_all_groups()
    {
        $this->db->select('group_code, MIN(group_description) as group_description, COUNT(*) as total_codes, MIN(id) as id');
        $this->db->group_by('group_code');
        $this->db->order_by('group_code', 'ASC');
        return $this->db->get($this->table)->result();
    }

    public function get_by_group($group)
    {
        return $this->db->where('group_code', $group)
                        ->order_by('code', 'ASC')
                        ->get($this->table)->result();
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

    public function delete_by_group($group_code)
    {
        return $this->db->where('group_code', $group_code)->delete('common_code');
    }

    public function update_group($old_group_code, $new_group_code, $group_description)
    {
        // Update group_code dan group_description untuk semua record dalam group
        $this->db->where('group_code', $old_group_code);
        $this->db->update($this->table, [
            'group_code' => $new_group_code,
            'group_description' => $group_description
        ]);
        
        return true;
    }

}
