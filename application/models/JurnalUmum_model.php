<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class JurnalUmum_model extends CI_Model {

    public function get_all_headers()
    {
        $this->db->select('jh.*, u.fullname as creator_name');
        $this->db->from('journal_header jh');
        $this->db->join('users u', 'jh.created_by = u.id', 'left');
        $this->db->order_by('jh.journal_date', 'DESC');
        $this->db->order_by('jh.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get_header_by_id($id)
    {
        $this->db->select('jh.*, u.fullname as creator_name, p.fullname as poster_name');
        $this->db->from('journal_header jh');
        $this->db->join('users u', 'jh.created_by = u.id', 'left');
        $this->db->join('users p', 'jh.posted_by = p.id', 'left');
        $this->db->where('jh.id', $id);
        return $this->db->get()->row();
    }

    public function get_new_journal_number()
    {
        $this->db->select('RIGHT(journal_no, 5) as kode', FALSE);
        $this->db->order_by('journal_no', 'DESC');
        $this->db->limit(1);
        $query = $this->db->get_where('journal_header', ['YEAR(journal_date)' => date('Y')]);
        
        $kode = ($query->num_rows() > 0) ? intval($query->row()->kode) + 1 : 1;
        $kode_max = str_pad($kode, 5, "0", STR_PAD_LEFT);
        return "JU/" . date('Ym') . "/" . $kode_max;
    }

    public function post_journal($header_id, $user_id)
    {
        $this->db->where('id', $header_id);
        $this->db->where('status', 'Draft');
        return $this->db->update('journal_header', [
            'status' => 'Posted',
            'posted_by' => $user_id,
            'posted_at' => date('Y-m-d H:i:s')
        ]);
    }

    public function delete_draft_journal($header_id)
    {
        $this->db->trans_start();
        $this->db->delete('journal_details', ['journal_header_id' => $header_id]);
        $this->db->delete('journal_header', ['id' => $header_id, 'status' => 'Draft']);
        $affected_rows = $this->db->affected_rows();
        $this->db->trans_complete();
        return ($this->db->trans_status() && $affected_rows > 0);
    }
    
    public function process_import_data($sheet_data)
    {
        $details = [];
        $accounts = $this->get_postable_accounts();
        $account_map = [];
        foreach ($accounts as $acc) {
            $account_map[trim($acc->account_code)] = $acc->id;
        }

        foreach ($sheet_data as $row_num => $row) {
            if ($row_num == 1) continue; 
            $account_code = trim($row['A']);
            $debit = (float) $row['C'];
            $kredit = (float) $row['D'];
            $description = $row['E'];
            if (empty($account_code) || ($debit == 0 && $kredit == 0)) {
                continue; 
            }
            $account_id = $account_map[$account_code] ?? null;
            if ($account_id === null) {
                continue; 
            }
            $details[] = [
                'account_id' => $account_id,
                'debit' => $debit,
                'kredit' => $kredit,
                'description' => $description
            ];
        }
        return $details;
    }

    public function get_currencies()
    {
        $this->db->where('group_code', 'CURRENCY');
        $this->db->where('status', 'active');
        $this->db->order_by('code', 'ASC');
        return $this->db->get('common_code')->result();
    }

    public function get_postable_accounts()
    {
        $this->db->select('id, account_code, account_name, currency');
        $this->db->where('is_header', 0);
        $this->db->where('status', 'active');
        $this->db->order_by('account_code', 'ASC');
        return $this->db->get('chart_of_accounts')->result();
    }

    public function get_details_by_header_id($header_id)
    {
        $this->db->select('jd.*, coa.account_code, coa.account_name');
        $this->db->from('journal_details jd');
        $this->db->join('chart_of_accounts coa', 'jd.account_id = coa.id');
        $this->db->where('jd.journal_header_id', $header_id);
        $this->db->order_by('jd.id', 'ASC');
        return $this->db->get()->result();
    }

    public function insert_journal($header_data, $details_data)
    {
        $this->db->trans_start();

        $this->db->insert('journal_header', $header_data);
        $header_id = $this->db->insert_id();
        
        $rate = (float) $header_data['exchange_rate'];

        foreach ($details_data as $detail) {
            if (!empty($detail['account_id']) && ($detail['debit'] > 0 || $detail['kredit'] > 0)) {
                $detail['journal_header_id'] = $header_id;
                
                $detail['debit_local'] = (float) $detail['debit'] * $rate;
                $detail['kredit_local'] = (float) $detail['kredit'] * $rate;
                
                $this->db->insert('journal_details', $detail);
            }
        }

        $this->db->trans_complete();
        return ($this->db->trans_status() === FALSE) ? FALSE : $header_id;
    }
}