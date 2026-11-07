<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Neraca_model extends CI_Model {

    public function get_data($date)
    {
        $this->db->select('id, account_code, account_name, normal_balance, account_type');
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

            // Tanda menurut sisi normal TIPE akun (2026-11-07): akun kontra seperti
            // Akumulasi Penyusutan (Aset, normal Kredit) tampil negatif dan
            // mengurangi total aset, bukan menambahnya.
            $balance = 0;
            if ($acc['account_type'] == 'Aset') {
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

                // Kategori berdasarkan tipe akun (2026-11-07; sebelumnya digit
                // pertama kode -- rapuh kalau struktur kode COA berubah).
                $type = $acc['account_type'];
                if ($type == 'Aset') {
                    $result['assets'][] = $account_data;
                } else if ($type == 'Liabilitas') {
                    $result['liabilities'][] = $account_data;
                } else if ($type == 'Ekuitas') {
                    $result['equity'][] = $account_data;
                }
            }
        }

        // ===================================================================
        // TAMBAHKAN LABA/RUGI TAHUN BERJALAN & PERIODE LALU KE EQUITY
        // ===================================================================
        
        // Ekstrak tahun dari tanggal laporan
        $year = date('Y', strtotime($date));
        
        // 1. Hitung Laba/Rugi PERIODE LALU (tahun sebelum tahun laporan)
        $retained_earnings = $this->calculate_retained_earnings($date, $year);
        
        if ($retained_earnings != 0) {
            $result['equity'][] = [
                'id' => 'retained_earnings',
                'account' => '9998',
                'name' => 'Laba/Rugi Periode Lalu s/d ' . ($year - 1),
                'balance' => $retained_earnings
            ];
        }
        
        // 2. Hitung Laba/Rugi TAHUN BERJALAN (hanya tahun laporan)
        $current_year_income = $this->calculate_current_year_income($date, $year);
        
        if ($current_year_income != 0) {
            $result['equity'][] = [
                'id' => 'current_year_income',
                'account' => '9999',
                'name' => 'Laba/Rugi Tahun Berjalan ' . $year,
                'balance' => $current_year_income
            ];
        }

        // DEBUG INFO (hapus nanti setelah fix)
        $result['debug'] = [
            'date' => $date,
            'year' => $year,
            'retained_earnings' => $retained_earnings,
            'current_year_income' => $current_year_income,
            'total_net_income' => $retained_earnings + $current_year_income,
            'total_assets' => array_sum(array_column($result['assets'], 'balance')),
            'total_liabilities' => array_sum(array_column($result['liabilities'], 'balance')),
            'total_equity' => array_sum(array_column($result['equity'], 'balance'))
        ];

        return $result;
    }

    /**
     * Hitung Laba/Rugi Periode Lalu (sebelum tahun laporan)
     */
    private function calculate_retained_earnings($date, $current_year)
    {
        $start_of_year = $current_year . '-01-01';
        
        // Revenue periode lalu (sebelum 1 Jan tahun berjalan)
        $revenue = $this->db->query("
            SELECT COALESCE(SUM(jd.kredit_local - jd.debit_local), 0) as total
            FROM journal_details jd
            JOIN journal_header jh ON jd.journal_header_id = jh.id
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
            WHERE jh.journal_date < ?
            AND jh.status = 'Posted'
            AND coa.account_type = 'Pendapatan'
        ", [$start_of_year])->row()->total;

        // Expense periode lalu (sebelum 1 Jan tahun berjalan)
        $expense = $this->db->query("
            SELECT COALESCE(SUM(jd.debit_local - jd.kredit_local), 0) as total
            FROM journal_details jd
            JOIN journal_header jh ON jd.journal_header_id = jh.id
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
            WHERE jh.journal_date < ?
            AND jh.status = 'Posted'
            AND coa.account_type IN ('HPP', 'Biaya')
        ", [$start_of_year])->row()->total;

        return $revenue - $expense;
    }

    /**
     * Hitung Laba/Rugi Tahun Berjalan (hanya tahun laporan s/d tanggal)
     */
    private function calculate_current_year_income($date, $current_year)
    {
        $start_of_year = $current_year . '-01-01';
        
        // Revenue tahun berjalan (1 Jan s/d tanggal laporan)
        $revenue = $this->db->query("
            SELECT COALESCE(SUM(jd.kredit_local - jd.debit_local), 0) as total
            FROM journal_details jd
            JOIN journal_header jh ON jd.journal_header_id = jh.id
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
            WHERE jh.journal_date >= ?
            AND jh.journal_date <= ?
            AND jh.status = 'Posted'
            AND coa.account_type = 'Pendapatan'
        ", [$start_of_year, $date])->row()->total;

        // Expense tahun berjalan (1 Jan s/d tanggal laporan)
        $expense = $this->db->query("
            SELECT COALESCE(SUM(jd.debit_local - jd.kredit_local), 0) as total
            FROM journal_details jd
            JOIN journal_header jh ON jd.journal_header_id = jh.id
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
            WHERE jh.journal_date >= ?
            AND jh.journal_date <= ?
            AND jh.status = 'Posted'
            AND coa.account_type IN ('HPP', 'Biaya')
        ", [$start_of_year, $date])->row()->total;

        return $revenue - $expense;
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