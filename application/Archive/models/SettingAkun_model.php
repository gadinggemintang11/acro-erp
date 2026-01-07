<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SettingAkun_model extends CI_Model {

    private static $settings_cache = null;

    public function get_all_settings_grouped()
    {
        $this->db->select('s.*, coa.account_code, coa.account_name');
        $this->db->from('setting_accounting s');
        $this->db->join('chart_of_accounts coa', 's.account_id = coa.id', 'left');
        $this->db->order_by('s.setting_name', 'ASC');
        $this->db->order_by('s.currency', 'ASC');
        $query = $this->db->get();
        
        // Kelompokkan berdasarkan 'setting_key'
        $grouped_settings = [];
        foreach ($query->result() as $row) {
            $grouped_settings[$row->setting_key]['name'] = $row->setting_name;
            $grouped_settings[$row->setting_key]['currencies'][$row->currency] = $row;
        }
        return $grouped_settings;
    }

    public function get_currencies()
    {
        $this->db->where('group_code', 'CURRENCY');
        $this->db->where('status', 'active');
        $this->db->order_by('code', 'ASC');
        return $this->db->get('common_code')->result();
    }

    public function save_settings($settings)
    {
        $this->db->trans_start();
        
        foreach ($settings as $key => $currencies) {
            foreach ($currencies as $currency => $account_id) {
                $data = [
                    'account_id' => empty($account_id) ? NULL : $account_id
                ];
                $this->db->where('setting_key', $key);
                $this->db->where('currency', $currency);
                $this->db->update('setting_accounting', $data);
            }
        }
        
        $this->db->trans_complete();
        self::$settings_cache = null; 
        return $this->db->trans_status();
    }

    // FUNGSI UTAMA (diperbarui)
    public function get_account_id($setting_key, $currency = 'IDR')
    {
        if (self::$settings_cache === null) {
            self::$settings_cache = [];
            $query = $this->db->get('setting_accounting');
            foreach ($query->result() as $row) {
                // Buat key gabungan
                self::$settings_cache[$row->setting_key . '_' . $row->currency] = $row->account_id;
            }
        }

        // Cari berdasarkan key + currency
        $key_to_find = $setting_key . '_' . $currency;
        
        // Jika tidak ada (misal: cari PPN USD), fallback ke IDR
        if (!isset(self::$settings_cache[$key_to_find])) {
            $key_to_find = $setting_key . '_IDR';
        }

        return self::$settings_cache[$key_to_find] ?? null;
    }
}
