<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class YearEndClosing_model extends CI_Model {

    public function get_all_closings()
    {
        $this->db->select('yec.*, u.fullname as created_by_name, jh.journal_no');
        $this->db->from('year_end_closings yec');
        $this->db->join('users u', 'yec.created_by = u.id', 'left');
        $this->db->join('journal_header jh', 'yec.journal_header_id = jh.id', 'left');
        $this->db->order_by('yec.closing_year', 'DESC');
        return $this->db->get()->result();
    }

    public function get_closing_by_id($id)
    {
        $this->db->select('yec.*, u.fullname as created_by_name, jh.journal_no, coa.account_code, coa.account_name');
        $this->db->from('year_end_closings yec');
        $this->db->join('users u', 'yec.created_by = u.id', 'left');
        $this->db->join('journal_header jh', 'yec.journal_header_id = jh.id', 'left');
        $this->db->join('chart_of_accounts coa', 'yec.retained_earnings_account_id = coa.id', 'left');
        $this->db->where('yec.id', $id);
        return $this->db->get()->row();
    }

    public function check_existing_closing($year)
    {
        return $this->db->get_where('year_end_closings', ['closing_year' => $year])->row();
    }

    public function get_available_years()
    {
        $this->db->select('DISTINCT YEAR(journal_date) as year', FALSE);
        $this->db->from('journal_header');
        $this->db->where('status', 'Posted');
        $this->db->order_by('year', 'DESC');
        return $this->db->get()->result();
    }

    public function get_retained_earnings_accounts()
    {
        // Akun Retained Earnings biasanya di kategori 3xxx (Equity)
        $this->db->select('id, account_code, account_name');
        $this->db->from('chart_of_accounts');
        $this->db->where('status', 'active');
        $this->db->where('is_header', 0);
        $this->db->where('LEFT(account_code, 1)', '3');
        $this->db->like('account_name', 'laba', 'both');
        $this->db->or_like('account_name', 'retained', 'both');
        $this->db->or_like('account_name', 'ditahan', 'both');
        return $this->db->get()->result();
    }

    public function calculate_closing_summary($year)
    {
        // Hitung total Revenue (4xxx)
        $revenue = $this->db->query("
            SELECT SUM(jd.kredit_local - jd.debit_local) as total
            FROM journal_details jd
            JOIN journal_header jh ON jd.journal_header_id = jh.id
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
            WHERE YEAR(jh.journal_date) = ?
            AND jh.status = 'Posted'
            AND LEFT(coa.account_code, 1) = '4'
        ", [$year])->row()->total ?? 0;

        // Hitung total Expense (5xxx)
        $expense = $this->db->query("
            SELECT SUM(jd.debit_local - jd.kredit_local) as total
            FROM journal_details jd
            JOIN journal_header jh ON jd.journal_header_id = jh.id
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
            WHERE YEAR(jh.journal_date) = ?
            AND jh.status = 'Posted'
            AND LEFT(coa.account_code, 1) = '5'
        ", [$year])->row()->total ?? 0;

        $net_income = $revenue - $expense;

        return [
            'revenue' => $revenue,
            'expense' => $expense,
            'net_income' => $net_income
        ];
    }

    public function get_revenue_accounts($year)
    {
        return $this->db->query("
            SELECT 
                coa.id,
                coa.account_code,
                coa.account_name,
                SUM(jd.kredit_local - jd.debit_local) as balance
            FROM journal_details jd
            JOIN journal_header jh ON jd.journal_header_id = jh.id
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
            WHERE YEAR(jh.journal_date) = ?
            AND jh.status = 'Posted'
            AND LEFT(coa.account_code, 1) = '4'
            GROUP BY coa.id
            HAVING balance != 0
            ORDER BY coa.account_code
        ", [$year])->result();
    }

    public function get_expense_accounts($year)
    {
        return $this->db->query("
            SELECT 
                coa.id,
                coa.account_code,
                coa.account_name,
                SUM(jd.debit_local - jd.kredit_local) as balance
            FROM journal_details jd
            JOIN journal_header jh ON jd.journal_header_id = jh.id
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
            WHERE YEAR(jh.journal_date) = ?
            AND jh.status = 'Posted'
            AND LEFT(coa.account_code, 1) = '5'
            GROUP BY coa.id
            HAVING balance != 0
            ORDER BY coa.account_code
        ", [$year])->result();
    }

    public function process_closing($year, $retained_earnings_account_id, $user_id)
    {
        $this->db->trans_start();

        $summary = $this->calculate_closing_summary($year);
        $revenue_accounts = $this->get_revenue_accounts($year);
        $expense_accounts = $this->get_expense_accounts($year);

        // Generate journal number
        $journal_no = $this->_generate_closing_journal_no($year);
        $closing_date = $year . '-12-31'; // Tanggal tutup buku = 31 Desember tahun tersebut

        // Insert journal header
        $journal_header = [
            'journal_no' => $journal_no,
            'journal_date' => $closing_date,
            'description' => 'Year-End Closing ' . $year . ' - Transfer Net Income to Retained Earnings',
            'currency' => 'IDR',
            'exchange_rate' => 1.0000,
            'total_debit' => 0,
            'total_kredit' => 0,
            'total_debit_local' => 0,
            'total_kredit_local' => 0,
            'created_by' => $user_id,
            'status' => 'Posted',
            'posted_by' => $user_id,
            'posted_at' => date('Y-m-d H:i:s'),
            'source_module' => 'YEAR_END_CLOSING'
        ];

        $this->db->insert('journal_header', $journal_header);
        $journal_header_id = $this->db->insert_id();

        $details = [];
        $total_debit = 0;
        $total_kredit = 0;

        // Close Revenue accounts (Debit Revenue, Kredit Income Summary/Retained Earnings)
        foreach ($revenue_accounts as $acc) {
            if ($acc->balance > 0) {
                $details[] = [
                    'journal_header_id' => $journal_header_id,
                    'account_id' => $acc->id,
                    'description' => 'Closing Revenue ' . $year,
                    'debit' => $acc->balance,
                    'kredit' => 0,
                    'debit_local' => $acc->balance,
                    'kredit_local' => 0
                ];
                $total_debit += $acc->balance;
            }
        }

        // Close Expense accounts (Debit Income Summary/Retained Earnings, Kredit Expense)
        foreach ($expense_accounts as $acc) {
            if ($acc->balance > 0) {
                $details[] = [
                    'journal_header_id' => $journal_header_id,
                    'account_id' => $acc->id,
                    'description' => 'Closing Expense ' . $year,
                    'debit' => 0,
                    'kredit' => $acc->balance,
                    'debit_local' => 0,
                    'kredit_local' => $acc->balance
                ];
                $total_kredit += $acc->balance;
            }
        }

        // Transfer to Retained Earnings
        $net_income = $summary['net_income'];
        if ($net_income > 0) {
            // Laba: Kredit Retained Earnings
            $details[] = [
                'journal_header_id' => $journal_header_id,
                'account_id' => $retained_earnings_account_id,
                'description' => 'Net Income ' . $year,
                'debit' => 0,
                'kredit' => $net_income,
                'debit_local' => 0,
                'kredit_local' => $net_income
            ];
            $total_kredit += $net_income;
        } else if ($net_income < 0) {
            // Rugi: Debit Retained Earnings
            $details[] = [
                'journal_header_id' => $journal_header_id,
                'account_id' => $retained_earnings_account_id,
                'description' => 'Net Loss ' . $year,
                'debit' => abs($net_income),
                'kredit' => 0,
                'debit_local' => abs($net_income),
                'kredit_local' => 0
            ];
            $total_debit += abs($net_income);
        }

        // Insert details
        if (!empty($details)) {
            $this->db->insert_batch('journal_details', $details);
        }

        // Update journal header total
        $this->db->where('id', $journal_header_id);
        $this->db->update('journal_header', [
            'total_debit' => $total_debit,
            'total_kredit' => $total_kredit,
            'total_debit_local' => $total_debit,
            'total_kredit_local' => $total_kredit
        ]);

        // Insert year_end_closings record
        $closing_data = [
            'closing_year' => $year,
            'closing_date' => $closing_date,
            'retained_earnings_account_id' => $retained_earnings_account_id,
            'revenue_total' => $summary['revenue'],
            'expense_total' => $summary['expense'],
            'net_income' => $summary['net_income'],
            'journal_header_id' => $journal_header_id,
            'created_by' => $user_id,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('year_end_closings', $closing_data);
        $closing_id = $this->db->insert_id();

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            return ['success' => false, 'message' => 'Database transaction failed'];
        }

        return [
            'success' => true,
            'closing_id' => $closing_id,
            'journal_no' => $journal_no
        ];
    }

    private function _generate_closing_journal_no($year)
    {
        // Format: YEC/YYYY/0001
        $prefix = 'YEC/' . $year . '/';
        
        $this->db->select('journal_no');
        $this->db->from('journal_header');
        $this->db->like('journal_no', $prefix, 'after');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $result = $this->db->get()->row();
        
        if ($result) {
            $last_number = (int) substr($result->journal_no, -4);
            $next_number = $last_number + 1;
        } else {
            $next_number = 1;
        }
        
        return $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    }
}
