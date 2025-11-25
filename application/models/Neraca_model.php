<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Neraca_model extends CI_Model {

    public function get_data($date)
    {
        $this->db->select('id, account_code, account_name, normal_balance');
        $this->db->from('chart_of_accounts');
        $this->db->where('status', 'active');
        $this->db->where('is_header', 0);
        $accounts = $this->db->get()->result_array();

        $result = [
            'assets' => [],
            'liabilities' => [],
            'equity' => []
        ];

        foreach ($accounts as $acc) {
            $this->db->select('SUM(jd.debit_local) as total_debit, SUM(jd.kredit_local) as total_kredit');
            $this->db->from('journal_details jd');
            $this->db->join('journal_header jh', 'jd.journal_header_id = jh.id');
            $this->db->where('jd.account_id', $acc['id']);
            $this->db->where('jh.journal_date <=', $date);
            $this->db->where('jh.status', 'Posted');
            $query = $this->db->get()->row();

            $balance = 0;
            if ($acc['normal_balance'] == 'Debit') {
                $balance = ($query->total_debit ?? 0) - ($query->total_kredit ?? 0);
            } else {
                $balance = ($query->total_kredit ?? 0) - ($query->total_debit ?? 0);
            }

            if ($balance != 0) {
                $account_data = [
                    'id' => $acc['id'],
                    'account' => $acc['account_code'],
                    'name' => $acc['account_name'],
                    'balance' => $balance
                ];

                // Categorize based on the first digit of account_code
                $first_digit = substr($acc['account_code'], 0, 1);
                if ($first_digit == '1') {
                    $result['assets'][] = $account_data;
                } else if ($first_digit == '2') {
                    $result['liabilities'][] = $account_data;
                } else if ($first_digit == '3') {
                    $result['equity'][] = $account_data;
                }
            }
        }

        return $result;
    }

    public function get_detail_ledger($account_id, $date)
    {
        $this->db->select('
            jh.journal_date AS tanggal,
            jh.journal_no AS no_jurnal,
            jd.description AS keterangan,
            jd.debit_local AS debit,
            jd.kredit_local AS kredit,
            ari.invoice_no AS no_invoice_ar,
            api.invoice_no AS no_invoice_ap,
            cus.nama_customer AS customer_name,
            sup.nama_supplier AS supplier_name
        ');
        $this->db->from('journal_details jd');
        $this->db->join('journal_header jh', 'jd.journal_header_id = jh.id');
        $this->db->join('chart_of_accounts coa', 'jd.account_id = coa.id');
        
        // Join for AR Invoice
        $this->db->join('ar_invoice_header ari', "jh.source_doc_id = ari.id AND jh.source_module = 'AR_INVOICE'", 'left');
        $this->db->join('master_customer cus', 'ari.customer_id = cus.id_customer', 'left');

        // Join for AP Invoice
        $this->db->join('ap_invoice_header api', "jh.source_doc_id = api.id AND jh.source_module = 'AP_INVOICE'", 'left');
        $this->db->join('master_supplier sup', 'api.supplier_id = sup.id_supplier', 'left');

        // You can add more joins for other modules like AP/AR Payment here in the same way

        $this->db->where('jd.account_id', $account_id);
        $this->db->where('jh.journal_date <=', $date);
        $this->db->where('jh.status', 'Posted');
        $this->db->order_by('jh.journal_date', 'ASC');
        
        return $this->db->get()->result_array();
    }
}