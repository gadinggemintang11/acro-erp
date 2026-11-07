<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Report_model extends CI_Model {

    // Cache untuk setting akun agar tidak query berulang
    private $setting_accounts_cache = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('SettingAkun_model'); // Load untuk helper
    }

    /**
     * =======================================================================
     * FUNGSI UTAMA: LAPORAN LABA RUGI (PROFIT & LOSS)
     * =======================================================================
     */
    public function get_profit_loss($startDate, $endDate)
    {
        $balances = $this->_get_period_balances($startDate, $endDate);
        $this->db->from('chart_of_accounts');
        $this->db->where_in('account_type', ['Pendapatan', 'Biaya', 'HPP']); // Termasuk HPP
        $this->db->order_by('account_code', 'ASC');
        $elements = $this->db->get()->result_array();
        $tree = $this->_build_tree($elements);

        $report_data = [
            'pendapatan' => [],
            'biaya' => [],
            'total_pendapatan' => 0,
            'total_biaya' => 0,
            'laba_bersih' => 0
        ];

        foreach ($tree as $top_level_node) {
            $type = $top_level_node['account_type'];
            if ($type == 'Pendapatan') {
                $total = $this->_calculate_tree_balances($top_level_node, $balances);
                $report_data['pendapatan'][] = $top_level_node;
                $report_data['total_pendapatan'] += $total;
            } else if (in_array($type, ['Biaya', 'HPP'])) {
                $total = $this->_calculate_tree_balances($top_level_node, $balances);
                $report_data['biaya'][] = $top_level_node;
                $report_data['total_biaya'] += $total;
            }
        }
        $report_data['laba_bersih'] = $report_data['total_pendapatan'] - $report_data['total_biaya'];
        return $report_data;
    }

    /**
     * =======================================================================
     * FUNGSI UTAMA: NERACA (BALANCE SHEET)
     * =======================================================================
     */
    public function get_balance_sheet($asOfDate)
    {
        $balances = $this->_get_cumulative_balances($asOfDate, ['Aset', 'Liabilitas', 'Ekuitas']);
        $startOfYear = date('Y-01-01', strtotime($asOfDate));
        
        // Laba/Rugi Tahun Berjalan (1 Jan s/d tanggal laporan)
        $laba_tahun_berjalan = $this->_get_current_period_net_income($startOfYear, $asOfDate);
        
        // Laba/Rugi Periode Lalu (sebelum 1 Jan tahun berjalan)
        $laba_periode_lalu = $this->_get_retained_earnings($asOfDate);
        
        $this->db->from('chart_of_accounts');
        $this->db->where_in('account_type', ['Aset', 'Liabilitas', 'Ekuitas']);
        $this->db->order_by('account_code', 'ASC');
        $elements = $this->db->get()->result_array();
        $tree = $this->_build_tree($elements);

        $report_data = [
            'aset' => [],
            'liabilitas' => [],
            'ekuitas' => [],
            'total_aset' => 0,
            'total_liabilitas' => 0,
            'total_ekuitas' => 0,
            'laba_tahun_berjalan' => $laba_tahun_berjalan,
            'laba_periode_lalu' => $laba_periode_lalu
        ];

        foreach ($tree as $top_level_node) {
            $type = $top_level_node['account_type'];
            if ($type == 'Aset') {
                $total = $this->_calculate_tree_balances($top_level_node, $balances);
                $report_data['aset'][] = $top_level_node;
                $report_data['total_aset'] += $total;
            } else if ($type == 'Liabilitas') {
                $total = $this->_calculate_tree_balances($top_level_node, $balances);
                $report_data['liabilitas'][] = $top_level_node;
                $report_data['total_liabilitas'] += $total;
            } else if ($type == 'Ekuitas') {
                $total = $this->_calculate_tree_balances($top_level_node, $balances);
                $report_data['ekuitas'][] = $top_level_node;
                $report_data['total_ekuitas'] += $total;
            }
        }
        // Tambahkan laba/rugi periode lalu DAN tahun berjalan ke total ekuitas
        $report_data['total_ekuitas'] += $laba_periode_lalu + $laba_tahun_berjalan;
        return $report_data;
    }

    /**
     * =======================================================================
     * FUNGSI UTAMA: NERACA SALDO (TRIAL BALANCE)
     * =======================================================================
     */
    public function get_trial_balance($asOfDate)
    {
        $balances = $this->_get_cumulative_balances($asOfDate, ['Aset', 'Liabilitas', 'Ekuitas', 'Pendapatan', 'Biaya', 'HPP']);
    $this->db->select('id, account_code, account_name, account_type, is_header, normal_balance');
        $this->db->from('chart_of_accounts');
        $this->db->where('is_header', 0);
        $this->db->order_by('account_code', 'ASC');
        $accounts = $this->db->get()->result_array();
        
        $report_data = [];
        foreach ($accounts as $account) {
            $balance = $balances[$account['id']] ?? 0;
            if ($balance != 0) {
                $account['debit'] = 0;
                $account['kredit'] = 0;
                // Saldo dari _get_cumulative_balances bertanda menurut TIPE akun,
                // jadi kolom debit/kredit juga ditentukan dari tipe. Akun kontra
                // (mis. akumulasi penyusutan) otomatis jatuh di kolom Kredit.
                $is_debit_normal = in_array($account['account_type'], ['Aset', 'Biaya', 'HPP'], true);

                if ($is_debit_normal) {
                    $balance > 0 ? $account['debit'] = $balance : $account['kredit'] = abs($balance);
                } else {
                    $balance > 0 ? $account['kredit'] = $balance : $account['debit'] = abs($balance);
                }
                $report_data[] = $account;
            }
        }
        return $report_data;
    }

    /**
     * =======================================================================
     * FUNGSI UTAMA: ARUS KAS (CASH FLOW) [BARU]
     * =======================================================================
     */
    public function get_cash_flow($startDate, $endDate)
    {
        // 1. Dapatkan semua ID akun penting
        $cash_account_ids = $this->_get_cash_account_ids();
        $piutang_account_ids = $this->_get_setting_account_ids('akun_piutang_usaha');
        $utang_account_ids = $this->_get_setting_account_ids('akun_utang_usaha');
        
        // 2. Query utama: Ambil semua "Lawan Transaksi" dari Jurnal Kas
        $this->db->select(
            'jd.account_id, 
             coa.account_code, 
             coa.account_name, 
             coa.account_type,
             SUM(jd.debit_local) as total_debit, 
             SUM(jd.kredit_local) as total_kredit'
        );
        $this->db->from('journal_details jd');
        $this->db->join('journal_header jh', 'jd.journal_header_id = jh.id');
        $this->db->join('chart_of_accounts coa', 'jd.account_id = coa.id');
        
        // --- KONDISI PENTING ---
        $this->db->where('jh.status', 'Posted');
        $this->db->where('jh.journal_date >=', $startDate);
        $this->db->where('jh.journal_date <=', $endDate);
        // "Lawan" tidak boleh akun kas itu sendiri
        $this->db->where_not_in('jd.account_id', $cash_account_ids); 
        // Tapi Jurnalnya HARUS melibatkan akun kas
        $this->db->where("EXISTS (
            SELECT 1 FROM journal_details jd2
            WHERE jd2.journal_header_id = jd.journal_header_id
            AND jd2.account_id IN (" . implode(',', $cash_account_ids) . ")
        )", NULL, FALSE);
        // --- AKHIR KONDISI ---

        $this->db->group_by('jd.account_id, coa.account_code, coa.account_name, coa.account_type');
        $this->db->order_by('coa.account_code', 'ASC');
        
        $offsetting_transactions = $this->db->get()->result();

        // 3. Klasifikasikan ke CFO, CFI, CFF
        $report_data = [
            'cfo' => [], 'total_cfo' => 0,
            'cfi' => [], 'total_cfi' => 0,
            'cff' => [], 'total_cff' => 0,
        ];

        foreach ($offsetting_transactions as $row) {
            // total_kredit - total_debit dari akun "lawan" = pergerakan kas
            // Contoh: Lawan=Piutang (Kredit 100), maka Kas (Debit 100) -> Inflow (Positif)
            // Contoh: Lawan=Biaya (Debit 100), maka Kas (Kredit 100) -> Outflow (Negatif)
            $cash_impact = $row->total_kredit - $row->total_debit;
            
            $item = [
                'account_id' => $row->account_id,
                'account_name' => $row->account_name,
                'account_code' => $row->account_code,
                'cash_impact' => $cash_impact
            ];

            // Klasifikasi berdasarkan ID Akun (Paling Prioritas)
            if (in_array($row->account_id, $piutang_account_ids)) {
                $item['group_name'] = 'Penerimaan dari Pelanggan';
                $report_data['cfo'][] = $item;
                $report_data['total_cfo'] += $cash_impact;
            } 
            else if (in_array($row->account_id, $utang_account_ids)) {
                $item['group_name'] = 'Pembayaran ke Supplier';
                $report_data['cfo'][] = $item;
                $report_data['total_cfo'] += $cash_impact;
            }
            // Klasifikasi berdasarkan Tipe Akun
            else if (in_array($row->account_type, ['Biaya', 'HPP'])) {
                $item['group_name'] = 'Pembayaran Biaya Operasional';
                $report_data['cfo'][] = $item;
                $report_data['total_cfo'] += $cash_impact;
            }
            else if ($row->account_type == 'Pendapatan') {
                $item['group_name'] = 'Penerimaan Operasional Lain';
                $report_data['cfo'][] = $item;
                $report_data['total_cfo'] += $cash_impact;
            }
            else if ($row->account_type == 'Aset') {
                $item['group_name'] = 'Aktivitas Investasi (Aset)';
                $report_data['cfi'][] = $item;
                $report_data['total_cfi'] += $cash_impact;
            }
            else if (in_array($row->account_type, ['Liabilitas', 'Ekuitas'])) {
                $item['group_name'] = 'Aktivitas Pendanaan';
                $report_data['cff'][] = $item;
                $report_data['total_cff'] += $cash_impact;
            }
        }
        
        // 4. Hitung Saldo Awal dan Akhir Kas
        $report_data['saldo_awal_kas'] = $this->_get_beginning_cash_balance($startDate, $cash_account_ids);
        $report_data['peningkatan_kas'] = $report_data['total_cfo'] + $report_data['total_cfi'] + $report_data['total_cff'];
        $report_data['saldo_akhir_kas'] = $report_data['saldo_awal_kas'] + $report_data['peningkatan_kas'];

        return $report_data;
    }


    /**
     * =======================================================================
     * FUNGSI UTAMA: BUKU BESAR (GENERAL LEDGER)
     * =======================================================================
     */
    public function get_general_ledger($accountId, $startDate, $endDate)
    {
        $report_data = [];
        $this->db->select('id, account_code, account_name, account_type, normal_balance');
        $this->db->from('chart_of_accounts');
        $this->db->where('id', $accountId);
        $report_data['info_akun'] = $this->db->get()->row();
        if (!$report_data['info_akun']) throw new Exception("Akun tidak ditemukan.");

        // Prefer normal_balance if set, otherwise fallback to account_type rule
        if (isset($report_data['info_akun']->normal_balance) && $report_data['info_akun']->normal_balance) {
            $is_debit_normal = ($report_data['info_akun']->normal_balance === 'Debit');
        } else {
            $is_debit_normal = in_array($report_data['info_akun']->account_type, ['Aset', 'Biaya', 'HPP']);
        }

        $this->db->select('SUM(jd.debit_local) as total_debit, SUM(jd.kredit_local) as total_kredit');
        $this->db->from('journal_details jd');
        $this->db->join('journal_header jh', 'jd.journal_header_id = jh.id');
        $this->db->where('jh.status', 'Posted');
        $this->db->where('jd.account_id', $accountId);
        $this->db->where('jh.journal_date <', $startDate); 
        $query_sa = $this->db->get()->row();
        $saldo_awal_debit = (float) $query_sa->total_debit;
        $saldo_awal_kredit = (float) $query_sa->total_kredit;
        $report_data['saldo_awal'] = $is_debit_normal ? ($saldo_awal_debit - $saldo_awal_kredit) : ($saldo_awal_kredit - $saldo_awal_debit);

        $this->db->select('jh.journal_date, jh.journal_no, jh.description as header_description,
             jh.source_module, jh.source_doc_id, jd.journal_header_id, jd.description, 
             jd.debit_local, jd.kredit_local, api.invoice_no as ap_invoice_no,
             app.payment_no as ap_payment_no, ari.invoice_no as ar_invoice_no,
             arr.receipt_no as ar_receipt_no, cbh.doc_no as cash_bank_no');
        $this->db->from('journal_details jd');
        $this->db->join('journal_header jh', 'jd.journal_header_id = jh.id');
        $this->db->join('ap_invoice_header api', "jh.source_module = 'AP_INVOICE' AND jh.source_doc_id = api.id", 'left');
        $this->db->join('ap_payment_header app', "jh.source_module = 'AP_PAYMENT' AND jh.source_doc_id = app.id", 'left');
        $this->db->join('ar_invoice_header ari', "jh.source_module = 'AR_INVOICE' AND jh.source_doc_id = ari.id", 'left');
        $this->db->join('ar_receipt_header arr', "jh.source_module = 'AR_RECEIPT' AND jh.source_doc_id = arr.id", 'left');
        $this->db->join('cash_bank_header cbh', "jh.source_module = 'CASH_BANK' AND jh.source_doc_id = cbh.id", 'left');
        $this->db->where('jh.status', 'Posted');
        $this->db->where('jd.account_id', $accountId);
        $this->db->where('jh.journal_date >=', $startDate);
        $this->db->where('jh.journal_date <=', $endDate);
        $this->db->order_by('jh.journal_date', 'ASC');
        $this->db->order_by('jh.id', 'ASC');
        $report_data['transaksi'] = $this->db->get()->result();

        return $report_data;
    }


    /**
     * =======================================================================
     * FUNGSI HELPER (PRIVATE)
     * =======================================================================
     */

    /** [HELPER BARU] Dapatkan Saldo Awal Kas */
    private function _get_beginning_cash_balance($startDate, $cash_account_ids)
    {
        $this->db->select('SUM(jd.debit_local) - SUM(jd.kredit_local) as starting_balance');
        $this->db->from('journal_details jd');
        $this->db->join('journal_header jh', 'jd.journal_header_id = jh.id');
        $this->db->where('jh.status', 'Posted');
        $this->db->where('jh.journal_date <', $startDate); // Kumulatif SEBELUM periode
        $this->db->where_in('jd.account_id', $cash_account_ids);
        $result = $this->db->get()->row();
        return (float) $result->starting_balance;
    }

    /** [HELPER BARU] Dapatkan semua ID Akun Kas & Bank */
    private function _get_cash_account_ids()
    {
        // Deteksi akun kas & bank.
        // Perubahan: sebelumnya kode mencari pattern '1-11' (yang tidak sesuai format di repo ini).
        // Sekarang kita cari akun bertipe 'Aset' dengan kode prefix umum untuk Kas/Bank (1.101.*, 1.102.*)
        $this->db->select('id');
        $this->db->from('chart_of_accounts');
        $this->db->where('account_type', 'Aset');
        $this->db->where('is_header', 0);
        $this->db->group_start();
        $this->db->like('account_code', '1.101', 'after'); // Kas
        $this->db->or_like('account_code', '1.102', 'after'); // Bank
        $this->db->group_end();
        $query = $this->db->get();

        $ids = [];
        foreach ($query->result() as $row) {
            $ids[] = $row->id;
        }
        // Pastikan tidak kosong untuk mencegah error SQL
        return !empty($ids) ? $ids : [0];
    }

    /** [HELPER BARU] Dapatkan semua ID Akun dari SettingAkun */
    private function _get_setting_account_ids($setting_key)
    {
        // Gunakan cache
        if ($this->setting_accounts_cache === null) {
            $this->setting_accounts_cache = [];
            $settings = $this->SettingAkun_model->get_all_settings_grouped(); // Asumsi fungsi ini ada
            
            // Jika tidak ada, kita panggil dari DB
            if (empty($settings)) {
                 $query = $this->db->select('setting_key, account_id')->get('setting_accounting');
                 foreach ($query->result() as $row) {
                     if (!empty($row->account_id)) {
                        $this->setting_accounts_cache[$row->setting_key][] = $row->account_id;
                     }
                 }
            } else {
                 foreach ($settings as $key => $group) {
                    foreach ($group['currencies'] as $currency_data) {
                        if (!empty($currency_data->account_id)) {
                            $this->setting_accounts_cache[$key][] = $currency_data->account_id;
                        }
                    }
                 }
            }
        }
        
        $ids = isset($this->setting_accounts_cache[$setting_key]) ? $this->setting_accounts_cache[$setting_key] : [];
        return array_unique($ids);
    }


    /** [HELPER UPDATE] Helper Saldo Kumulatif */
    private function _get_cumulative_balances($asOfDate, $accountTypes)
    {
        $this->db->select('jd.account_id, coa.account_type, coa.normal_balance, 
             SUM(jd.debit_local) as total_debit, SUM(jd.kredit_local) as total_kredit');
        $this->db->from('journal_details jd');
        $this->db->join('journal_header jh', 'jd.journal_header_id = jh.id');
        $this->db->join('chart_of_accounts coa', 'jd.account_id = coa.id');
        $this->db->where('jh.status', 'Posted');
        $this->db->where('jh.journal_date <=', $asOfDate);
        $this->db->where_in('coa.account_type', $accountTypes);
        $this->db->where('coa.is_header', 0);
        $this->db->group_by('jd.account_id, coa.account_type');
        $query = $this->db->get();
        $balances = [];
        foreach ($query->result() as $row) {
            $balances[$row->account_id] = $this->_type_signed_balance($row->account_type, $row->total_debit, $row->total_kredit);
        }
        return $balances;
    }

    /**
     * [HELPER] Saldo bertanda menurut sisi normal TIPE akun, bukan normal_balance
     * per akun (2026-11-07).
     *
     * Laporan menjumlahkan saldo anak ke induk per tipe. Akun kontra --
     * Akumulasi Penyusutan (tipe Aset, saldo normal Kredit) atau Discount
     * Penjualan (tipe Pendapatan, saldo normal Debit) -- harus MENGURANGI total
     * kelompoknya. Kalau tanda mengikuti normal_balance akun, saldonya jadi
     * positif dan malah menambah total aset / pendapatan.
     * Hasilnya: akun kontra tampil negatif di Neraca & Laba Rugi.
     * (Buku besar per akun tetap memakai normal_balance akun itu sendiri.)
     */
    private function _type_signed_balance($account_type, $total_debit, $total_kredit)
    {
        return in_array($account_type, ['Aset', 'Biaya', 'HPP'], true)
            ? (float) $total_debit - (float) $total_kredit
            : (float) $total_kredit - (float) $total_debit;
    }

    /** [HELPER] Laba Bersih Periode Berjalan */
    private function _get_current_period_net_income($startDate, $endDate)
    {
        $balances = $this->_get_period_balances($startDate, $endDate);
        $total_pendapatan = 0; $total_biaya = 0;
        $account_ids = array_keys($balances);
        if (empty($account_ids)) return 0;

        $this->db->select('id, account_type')->from('chart_of_accounts')->where_in('id', $account_ids);
        $accounts = $this->db->get()->result_array();
        $account_types = array_column($accounts, 'account_type', 'id');
        
        foreach ($balances as $account_id => $balance) {
            if (isset($account_types[$account_id])) {
                if ($account_types[$account_id] == 'Pendapatan') {
                    $total_pendapatan += $balance;
                } else if (in_array($account_types[$account_id], ['Biaya', 'HPP'])) {
                    $total_biaya += $balance;
                }
            }
        }
        return $total_pendapatan - $total_biaya;
    }

    /**
     * [HELPER] Laba/Rugi Periode Lalu (sebelum tahun berjalan)
     * Menghitung total Pendapatan - Biaya dari awal waktu sampai sehari sebelum tahun laporan
     */
    private function _get_retained_earnings($asOfDate)
    {
        $startOfYear = date('Y-01-01', strtotime($asOfDate));
        // Hitung semua pendapatan & biaya sebelum startOfYear
        $endOfLastYear = date('Y-m-d', strtotime($startOfYear . ' -1 day'));
        
        // Jika tanggal laporan adalah 1 Jan atau lebih awal di tahun pertama, return 0
        if (strtotime($endOfLastYear) < strtotime('1970-01-01')) {
            return 0;
        }
        
        // Gunakan fungsi yang sama tapi dengan range sampai akhir tahun lalu
        $balances = $this->_get_period_balances('1970-01-01', $endOfLastYear);
        
        $total_pendapatan = 0;
        $total_biaya = 0;
        $account_ids = array_keys($balances);
        
        if (empty($account_ids)) return 0;

        $this->db->select('id, account_type')->from('chart_of_accounts')->where_in('id', $account_ids);
        $accounts = $this->db->get()->result_array();
        $account_types = array_column($accounts, 'account_type', 'id');
        
        foreach ($balances as $account_id => $balance) {
            if (isset($account_types[$account_id])) {
                if ($account_types[$account_id] == 'Pendapatan') {
                    $total_pendapatan += $balance;
                } else if (in_array($account_types[$account_id], ['Biaya', 'HPP'])) {
                    $total_biaya += $balance;
                }
            }
        }
        
        return $total_pendapatan - $total_biaya;
    }

    /** [HELPER] Saldo Periode P&L */
    private function _get_period_balances($startDate, $endDate)
    {
       $this->db->select('jd.account_id, coa.account_type, coa.normal_balance, 
           SUM(jd.debit_local) as total_debit, SUM(jd.kredit_local) as total_kredit');
        $this->db->from('journal_details jd');
        $this->db->join('journal_header jh', 'jd.journal_header_id = jh.id');
        $this->db->join('chart_of_accounts coa', 'jd.account_id = coa.id');
        $this->db->where('jh.status', 'Posted'); 
        $this->db->where('jh.journal_date >=', $startDate); 
        $this->db->where('jh.journal_date <=', $endDate);   
        $this->db->where_in('coa.account_type', ['Pendapatan', 'Biaya', 'HPP']); 
        $this->db->where('coa.is_header', 0); 
        $this->db->group_by('jd.account_id, coa.account_type');
        $query = $this->db->get();
        $balances = [];
        foreach ($query->result() as $row) {
            // Tanda mengikuti tipe akun -- lihat _type_signed_balance().
            $balances[$row->account_id] = $this->_type_signed_balance($row->account_type, $row->total_debit, $row->total_kredit);
        }
        return $balances;
    }

    /** [HELPER] Bangun Tree */
    private function _calculate_tree_balances(array &$node, array $balances)
    {
        $node['balance'] = 0;
        if ($node['is_header'] == 1) {
            if (isset($node['children'])) {
                $children_balance = 0;
                foreach ($node['children'] as &$child_node) {
                    $children_balance += $this->_calculate_tree_balances($child_node, $balances);
                }
                $node['balance'] = $children_balance;
            }
        } else {
            $node['balance'] = $balances[$node['id']] ?? 0;
        }
        return $node['balance'];
    }

    /** [HELPER] Bangun Tree */
    private function _build_tree(array $elements, $parentId = 0) 
    {
        $branch = array();
        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->_build_tree($elements, $element['id']);
                if ($children) $element['children'] = $children;
                $branch[] = $element;
            }
        }
        return $branch;
    }
}