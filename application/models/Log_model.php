<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Log_model extends CI_Model {

    private $_table = "activity_logs";

    public function getAll()
    {
        // Mengambil log terbaru di paling atas
        $this->db->order_by('timestamp', 'DESC');
        return $this->db->get($this->_table)->result_array();
    }
}