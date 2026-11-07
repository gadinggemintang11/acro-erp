<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kurs_pajak_model extends CI_Model {

    private $table = 'tbl_kurs_pajak';
    private $log_table = 'tbl_kurs_pajak_sync_log';

    public $supported_currencies = ['USD', 'CNY'];

    public function get_all($filter = []) {
        $this->db->from($this->table);
        if (!empty($filter['currency_code'])) {
            $this->db->where('currency_code', $filter['currency_code']);
        }
        if (!empty($filter['year'])) {
            $this->db->where('YEAR(effective_date_start)', (int) $filter['year']);
        }
        if (!empty($filter['month'])) {
            $this->db->where('MONTH(effective_date_start)', (int) $filter['month']);
        }
        if (isset($filter['snapshot_only']) && $filter['snapshot_only']) {
            $this->db->where('is_month_end_snapshot', 1);
        }
        $this->db->order_by('effective_date_start', 'DESC');
        $this->db->order_by('currency_code', 'ASC');
        return $this->db->get()->result();
    }

    public function get_by_id($id) {
        return $this->db->get_where($this->table, ['id' => $id])->row();
    }

    public function get_active_rate($currency_code, $on_date = null) {
        $on_date = $on_date ?: date('Y-m-d');
        return $this->db
            ->where('currency_code', $currency_code)
            ->where('is_month_end_snapshot', 0)
            ->where('effective_date_start <=', $on_date)
            ->where('effective_date_end >=', $on_date)
            ->order_by('effective_date_start', 'DESC')
            ->limit(1)
            ->get($this->table)
            ->row();
    }

    public function get_month_end_snapshot($currency_code, $year_month) {
        return $this->db
            ->where('currency_code', $currency_code)
            ->where('is_month_end_snapshot', 1)
            ->where('snapshot_month', $year_month)
            ->limit(1)
            ->get($this->table)
            ->row();
    }

    public function exists_period($currency_code, $start_date, $is_snapshot = 0) {
        return (bool) $this->db
            ->where('currency_code', $currency_code)
            ->where('effective_date_start', $start_date)
            ->where('is_month_end_snapshot', $is_snapshot)
            ->count_all_results($this->table);
    }

    public function upsert($data) {
        $where = [
            'currency_code'        => $data['currency_code'],
            'effective_date_start' => $data['effective_date_start'],
            'is_month_end_snapshot' => $data['is_month_end_snapshot'] ?? 0,
        ];
        $existing = $this->db->get_where($this->table, $where)->row();
        if ($existing) {
            $this->db->where('id', $existing->id)->update($this->table, $data);
            return ['action' => 'updated', 'id' => $existing->id];
        }
        $this->db->insert($this->table, $data);
        return ['action' => 'inserted', 'id' => $this->db->insert_id()];
    }

    public function insert($data) {
        return $this->db->insert($this->table, $data);
    }

    public function update($id, $data) {
        return $this->db->where('id', $id)->update($this->table, $data);
    }

    public function delete($id) {
        return $this->db->where('id', $id)->delete($this->table);
    }

    public function log_sync($payload) {
        return $this->db->insert($this->log_table, $payload);
    }

    public function get_sync_logs($limit = 20) {
        return $this->db
            ->order_by('id', 'DESC')
            ->limit($limit)
            ->get($this->log_table)
            ->result();
    }

    public function get_latest_per_currency() {
        $result = [];
        foreach ($this->supported_currencies as $cc) {
            $row = $this->db
                ->where('currency_code', $cc)
                ->where('is_month_end_snapshot', 0)
                ->order_by('effective_date_start', 'DESC')
                ->limit(1)
                ->get($this->table)
                ->row();
            $result[$cc] = $row;
        }
        return $result;
    }
}
