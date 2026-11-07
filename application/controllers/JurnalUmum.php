<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Dompdf\Dompdf;
use Dompdf\Options;

class JurnalUmum extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) {
            redirect('auth');
        }
        $this->load->model('JurnalUmum_model', 'model');
        $this->load->model('Coa_model');
        $this->load->model('SettingAkun_model');
        $this->load->model('Prf_model');
        $this->load->library('form_validation');
        $this->load->helper('form');
    }

    /**
     * Ambil ID akun PPN dari setting_accounting.
     * $type: 'IN' (Masukan) atau 'OUT' (Keluaran).
     */
    private function _get_ppn_account_id($type)
    {
        $key = ($type === 'OUT') ? 'akun_ppn_keluaran' : 'akun_ppn_masukan';
        return $this->SettingAkun_model->get_account_id($key, 'IDR');
    }

    /**
     * Susun baris PPN otomatis (debit/kredit) dari header PPN.
     * Return array detail siap digabung ke $details_data, atau null jika PPN tidak aktif.
     */
    private function _build_ppn_detail_row($ppn_enabled, $ppn_type, $ppn_amount)
    {
        if (!$ppn_enabled || $ppn_amount <= 0) return null;

        $account_id = $this->_get_ppn_account_id($ppn_type);
        if (!$account_id) return null;

        $is_in = ($ppn_type === 'IN');
        return [
            'account_id'  => $account_id,
            'debit'       => $is_in ? $ppn_amount : 0,
            'kredit'      => $is_in ? 0 : $ppn_amount,
            'description' => 'PPN ' . ($is_in ? 'Masukan' : 'Keluaran') . ' (auto)',
            'is_ppn_line' => 1,
        ];
    }

    /**
     * Resolusi vendor terpilih (snapshot data) untuk auto-fill voucher.
     */
    private function _resolve_vendor()
    {
        $supplier_id = (int) $this->input->post('supplier_id');
        $vendor = $supplier_id ? $this->model->get_vendor_by_id($supplier_id) : null;

        return [
            'supplier_id'         => $vendor ? (int) $vendor->id : null,
            'vendor_name'         => $vendor->nama_supplier ?? null,
            'vendor_bank_name'    => $vendor->bank_name ?? null,
            'vendor_bank_account' => $vendor->bank_account ?? null,
        ];
    }

    /**
     * Hitung field WHT (PPh 23 & 4(2)) dari input form.
     * DPP di-input MANUAL per jenis (terpisah dari DPP PPN). User pilih
     * salah satu / dua / tidak sama sekali lewat checkbox.
     * Return array field header + 'total' (untuk net bank & voucher).
     */
    private function _resolve_wht()
    {
        $wht_on = $this->input->post('wht_enabled') ? 1 : 0;

        $w23_on   = ($wht_on && $this->input->post('wht23_enabled')) ? 1 : 0;
        $w23_dpp  = $w23_on ? (float) $this->input->post('wht23_dpp')  : 0;
        $w23_rate = $w23_on ? (float) $this->input->post('wht23_rate') : 0;
        // WHT dibulatkan KE BAWAH (floor) ke rupiah bulat (permintaan client).
        $w23_amt  = $w23_on ? floor($w23_dpp * $w23_rate / 100) : 0;

        $w42_on   = ($wht_on && $this->input->post('wht42_enabled')) ? 1 : 0;
        $w42_dpp  = $w42_on ? (float) $this->input->post('wht42_dpp')  : 0;
        $w42_rate = $w42_on ? (float) $this->input->post('wht42_rate') : 0;
        $w42_amt  = $w42_on ? floor($w42_dpp * $w42_rate / 100) : 0;

        return [
            'wht23_enabled' => $w23_on, 'wht23_dpp' => $w23_dpp, 'wht23_rate' => $w23_rate, 'wht23_amount' => $w23_amt,
            'wht42_enabled' => $w42_on, 'wht42_dpp' => $w42_dpp, 'wht42_rate' => $w42_rate, 'wht42_amount' => $w42_amt,
            'total'         => round($w23_amt + $w42_amt, 2),
        ];
    }

    /**
     * Susun baris WHT (Cr Hutang PPh) dari hasil _resolve_wht.
     * Akun diambil dari Setting Akun (akun_wht23 / akun_wht42).
     */
    private function _build_wht_detail_rows($wht)
    {
        $rows = [];
        $fmt  = fn($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

        if ($wht['wht23_amount'] > 0) {
            $acc = $this->SettingAkun_model->get_account_id('akun_wht23', 'IDR');
            if ($acc) {
                $rows[] = [
                    'account_id'  => $acc,
                    'debit'       => 0,
                    'kredit'      => $wht['wht23_amount'],
                    'description' => 'WHT PPh 23 (' . $fmt($wht['wht23_rate']) . '%) auto',
                    'is_ppn_line' => 0,
                    'is_wht_line' => 1,
                ];
            }
        }
        if ($wht['wht42_amount'] > 0) {
            $acc = $this->SettingAkun_model->get_account_id('akun_wht42', 'IDR');
            if ($acc) {
                $rows[] = [
                    'account_id'  => $acc,
                    'debit'       => 0,
                    'kredit'      => $wht['wht42_amount'],
                    'description' => 'WHT PPh 4(2) (' . $fmt($wht['wht42_rate']) . '%) auto',
                    'is_ppn_line' => 0,
                    'is_wht_line' => 1,
                ];
            }
        }
        return $rows;
    }

    /**
     * Terapkan WHT ke array detail: kurangi baris pembayaran (kredit MANUAL
     * terbesar = Bank/Kas) sebesar total WHT, lalu tambahkan baris Cr Hutang PPh.
     * Return array detail baru, atau FALSE bila WHT melebihi nilai pembayaran.
     */
    private function _apply_wht_net($details, $wht)
    {
        $total = (float) $wht['total'];
        if ($total <= 0) return $details;

        $idx = -1; $max = 0;
        foreach ($details as $i => $d) {
            if (!empty($d['is_ppn_line']) || !empty($d['is_wht_line'])) continue;
            $k = (float) ($d['kredit'] ?? 0);
            if ($k > $max) { $max = $k; $idx = $i; }
        }
        if ($idx < 0 || $max + 0.001 < $total) {
            return false; // tidak ada baris pembayaran / nilainya kurang dari WHT
        }

        $details[$idx]['kredit'] = round($max - $total, 2);
        foreach ($this->_build_wht_detail_rows($wht) as $row) {
            $details[] = $row;
        }
        return $details;
    }

    /**
     * Pastikan akun Hutang PPh untuk WHT yang aktif sudah diset di Setting Akun.
     * Return pesan error (string) bila belum, atau null bila aman.
     */
    private function _check_wht_accounts($wht)
    {
        if ($wht['wht23_amount'] > 0 && !$this->SettingAkun_model->get_account_id('akun_wht23', 'IDR')) {
            return 'Akun WHT PPh 23 belum diset di Setting Akun. Set dulu sebelum pakai WHT 23.';
        }
        if ($wht['wht42_amount'] > 0 && !$this->SettingAkun_model->get_account_id('akun_wht42', 'IDR')) {
            return 'Akun WHT PPh 4(2) belum diset di Setting Akun. Set dulu sebelum pakai WHT 4(2).';
        }
        return null;
    }

    public function index()
    {
        $data['title'] = lang_line('jurnal_umum_title', 'Jurnal Umum (Jurnal Entry)');
        $search = $this->input->get('search');
        $data['search'] = $search ? $search : '';
        
        if ($search) {
            $data['journals'] = $this->model->search_journals($search);
        } else {
            $data['journals'] = $this->model->get_all_headers();
        }
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/index', $data);
        $this->load->view('templates/footer');
    }

    public function create()
    {
        $data['title'] = lang_line('jurnal_umum_create', 'Buat Jurnal Umum Baru');
        $data['journal_no'] = $this->model->get_new_journal_number();
        $data['accounts'] = $this->model->get_postable_accounts();
        $data['currencies'] = $this->model->get_currencies();
        $data['vendors'] = $this->model->get_vendors();
        $data['prf_list'] = $this->Prf_model->get_journalable_prfs();
        $data['ppn_in_account_id']  = $this->_get_ppn_account_id('IN');
        $data['ppn_out_account_id'] = $this->_get_ppn_account_id('OUT');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/create', $data);
        $this->load->view('templates/footer');
    }

    public function store()
    {
        $this->_validate_journal();
        $this->form_validation->set_rules('currency', 'Mata Uang', 'required');
        $this->form_validation->set_rules('exchange_rate', 'Kurs', 'required|numeric|greater_than[0]');

        $ppn_enabled = $this->input->post('ppn_enabled') ? 1 : 0;
        if ($ppn_enabled) {
            $this->form_validation->set_rules('ppn_type', 'Jenis PPN', 'required|in_list[IN,OUT]');
            $this->form_validation->set_rules('dpp_amount', 'DPP', 'required|numeric|greater_than[0]');
            $this->form_validation->set_rules('tax_invoice_no', 'Nomor Faktur Pajak', 'required');
            $this->form_validation->set_rules('document_date', 'Tanggal Dokumen', 'required');
        }

        if ($this->form_validation->run() == FALSE) {
            $this->create();
            return;
        }

        $details = $this->input->post('details');
        $rate = (float) $this->input->post('exchange_rate');

        // Susun field PPN dari input
        $ppn_type    = $ppn_enabled ? $this->input->post('ppn_type') : null;
        $ppn_percent = $ppn_enabled ? (float) $this->input->post('ppn_percent') : 0;
        $dpp_amount  = $ppn_enabled ? (float) $this->input->post('dpp_amount') : 0;
        // VAT/PPN dibulatkan ke rupiah bulat, rounding normal (>=0.5 ke atas, <0.5 ke bawah).
        $ppn_amount  = $ppn_enabled ? round($dpp_amount * $ppn_percent / 100) : 0;

        // PPN hanya berlaku untuk IDR (sesuai praktik perpajakan Indonesia)
        if ($ppn_enabled && $this->input->post('currency') !== 'IDR') {
            $this->session->set_flashdata('error', 'PPN hanya berlaku untuk mata uang IDR.');
            $this->create();
            return;
        }

        // Tambahkan baris PPN otomatis ke array detail
        $ppn_row = $this->_build_ppn_detail_row($ppn_enabled, $ppn_type, $ppn_amount);
        if ($ppn_row) {
            $details[] = $ppn_row;
        }

        // Vendor snapshot (untuk auto-fill voucher).
        $vendor = $this->_resolve_vendor();

        // PRF (Payment Request) opsional: jurnal ditarik dari PRF yang sudah Approved & belum dijurnal.
        $prf_id = (int) $this->input->post('prf_id');
        if ($prf_id > 0) {
            $prf = $this->Prf_model->get_prf_header_by_id($prf_id);
            if (!$prf || !in_array($prf->status, ['Approved', 'Finance Approved']) || $prf->payment_status === 'Paid' || !empty($prf->journal_header_id)) {
                $this->session->set_flashdata('error', 'Payment Request tidak valid / sudah ditarik ke jurnal lain. Silakan pilih ulang.');
                $this->create();
                return;
            }
        }

        // WHT (PPh 23 / 4(2)) manual: kurangi baris Bank jadi net + baris Cr Hutang PPh.
        $wht = $this->_resolve_wht();
        if ($wht['total'] > 0 && $this->input->post('currency') !== 'IDR') {
            $this->session->set_flashdata('error', 'WHT hanya berlaku untuk mata uang IDR.');
            $this->create();
            return;
        }
        if ($acc_err = $this->_check_wht_accounts($wht)) {
            $this->session->set_flashdata('error', $acc_err);
            $this->create();
            return;
        }
        $details_wht = $this->_apply_wht_net($details, $wht);
        if ($details_wht === false) {
            $this->session->set_flashdata('error', 'Gagal: total WHT melebihi nilai pembayaran (Bank/Kas). Periksa baris kredit.');
            $this->create();
            return;
        }
        $details = $details_wht;

        $totals = $this->_calculate_totals($details, $rate);

        if ($totals['debit'] != $totals['kredit']) {
            $this->session->set_flashdata('error', 'Gagal: Total Debit ('.$totals['debit'].') dan Kredit ('.$totals['kredit'].') tidak seimbang.');
            $this->create();
            return;
        }

        $header = [
            'journal_no' => $this->input->post('journal_no'),
            'journal_date' => $this->input->post('journal_date'),
            'document_date' => $this->input->post('document_date') ?: null,
            'due_date' => $this->input->post('due_date') ?: null,
            'tax_invoice_no' => $this->input->post('tax_invoice_no') ?: null,
            'ppn_enabled' => $ppn_enabled,
            'ppn_type' => $ppn_type,
            'ppn_percent' => $ppn_enabled ? $ppn_percent : 0,
            'dpp_amount' => $dpp_amount,
            'ppn_amount' => $ppn_amount,
            'supplier_id'         => $vendor['supplier_id'],
            'vendor_name'         => $vendor['vendor_name'],
            'vendor_bank_name'    => $vendor['vendor_bank_name'],
            'vendor_bank_account' => $vendor['vendor_bank_account'],
            'wht23_enabled'       => $wht['wht23_enabled'],
            'wht23_dpp'           => $wht['wht23_dpp'],
            'wht23_rate'          => $wht['wht23_rate'],
            'wht23_amount'        => $wht['wht23_amount'],
            'wht42_enabled'       => $wht['wht42_enabled'],
            'wht42_dpp'           => $wht['wht42_dpp'],
            'wht42_rate'          => $wht['wht42_rate'],
            'wht42_amount'        => $wht['wht42_amount'],
            'description' => $this->input->post('description'),
            'currency' => $this->input->post('currency'),
            'exchange_rate' => $rate,
            'source_module' => $prf_id > 0 ? 'PRF' : 'Manual',
            'source_doc_id' => $prf_id > 0 ? $prf_id : null,
            'total_debit' => $totals['debit'],
            'total_kredit' => $totals['kredit'],
            'total_debit_local' => $totals['debit_local'],
            'total_kredit_local' => $totals['kredit_local'],
            'created_by' => $this->session->userdata('user_id'),
            'status' => 'Draft'
        ];

        $result = $this->model->insert_journal($header, $details);

        if ($result) {
            if ($prf_id > 0) {
                $this->Prf_model->link_journal($prf_id, $result);
            }
            $this->session->set_flashdata('success', 'Jurnal berhasil disimpan sebagai Draft.');
            redirect('jurnalumum/view/' . $result);
        } else {
            $this->session->set_flashdata('error', 'Gagal menyimpan jurnal.');
            redirect('jurnalumum/create');
        }
    }

    public function view($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();
        
        $data['title'] = sprintf(lang_line('title_ju_view', 'View Jurnal: %s'), $data['header']->journal_no);
        $data['details'] = $this->model->get_details_by_header_id($id);
        $data['can_edit'] = $this->model->can_edit_journal($id);
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/view', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $data['header'] = $this->model->get_header_by_id($id);
        if (!$data['header']) show_404();
        
        // Check if can edit
        if (!$this->model->can_edit_journal($id)) {
            $this->session->set_flashdata('error', 'Jurnal ini sudah di-posting dan tidak bisa diedit.');
            redirect('jurnalumum/view/' . $id);
        }

        $data['title'] = sprintf(lang_line('title_ju_edit', 'Edit Jurnal: %s'), $data['header']->journal_no);
        // Hanya tampilkan baris manual di tabel; baris PPN & WHT otomatis dibangun ulang dari header
        $data['details'] = $this->model->get_user_details_by_header_id($id);

        // Tampilkan baris pembayaran (Bank/Kas) dalam nilai GROSS: tambahkan kembali
        // total WHT ke baris kredit terbesar (yang tadi dikurangi saat simpan) supaya
        // form tetap balance & user melihat nilai sebelum potong WHT.
        $total_wht = (float) ($data['header']->wht23_amount ?? 0) + (float) ($data['header']->wht42_amount ?? 0);
        if ($total_wht > 0 && !empty($data['details'])) {
            $idx = -1; $max = 0;
            foreach ($data['details'] as $i => $d) {
                if ((float) $d->kredit > $max) { $max = (float) $d->kredit; $idx = $i; }
            }
            if ($idx >= 0 && $max > 0) {
                $data['details'][$idx]->kredit = round($max + $total_wht, 2);
            }
        }

        $data['accounts'] = $this->model->get_postable_accounts();
        $data['currencies'] = $this->model->get_currencies();
        $data['vendors'] = $this->model->get_vendors();
        $data['ppn_in_account_id']  = $this->_get_ppn_account_id('IN');
        $data['ppn_out_account_id'] = $this->_get_ppn_account_id('OUT');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/edit', $data);
        $this->load->view('templates/footer');
    }

    public function update($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();
        
        // Check if can edit
        if (!$this->model->can_edit_journal($id)) {
            $this->session->set_flashdata('error', 'Jurnal ini sudah di-posting dan tidak bisa diedit.');
            redirect('jurnalumum/view/' . $id);
        }

        $this->form_validation->set_rules('journal_date', 'Tanggal Jurnal', 'required');
        $this->form_validation->set_rules('description', 'Keterangan', 'required');

        $ppn_enabled = $this->input->post('ppn_enabled') ? 1 : 0;
        if ($ppn_enabled) {
            $this->form_validation->set_rules('ppn_type', 'Jenis PPN', 'required|in_list[IN,OUT]');
            $this->form_validation->set_rules('dpp_amount', 'DPP', 'required|numeric|greater_than[0]');
            $this->form_validation->set_rules('tax_invoice_no', 'Nomor Faktur Pajak', 'required');
            $this->form_validation->set_rules('document_date', 'Tanggal Dokumen', 'required');
        }

        if ($this->form_validation->run() == FALSE) {
            $this->edit($id);
            return;
        }

        // PPN hanya berlaku untuk IDR
        if ($ppn_enabled && $this->input->post('currency') !== 'IDR') {
            $this->session->set_flashdata('error', 'PPN hanya berlaku untuk mata uang IDR.');
            $this->edit($id);
            return;
        }

        // Hitung field PPN
        $ppn_type    = $ppn_enabled ? $this->input->post('ppn_type') : null;
        $ppn_percent = $ppn_enabled ? (float) $this->input->post('ppn_percent') : 0;
        $dpp_amount  = $ppn_enabled ? (float) $this->input->post('dpp_amount') : 0;
        // VAT/PPN dibulatkan ke rupiah bulat, rounding normal (>=0.5 ke atas, <0.5 ke bawah).
        $ppn_amount  = $ppn_enabled ? round($dpp_amount * $ppn_percent / 100) : 0;

        // Vendor snapshot (voucher) + WHT manual (PPh 23 / 4(2)).
        $vendor = $this->_resolve_vendor();
        $wht    = $this->_resolve_wht();

        // Collect header data
        $header_data = [
            'journal_date' => $this->input->post('journal_date'),
            'document_date' => $this->input->post('document_date') ?: null,
            'due_date' => $this->input->post('due_date') ?: null,
            'tax_invoice_no' => $this->input->post('tax_invoice_no') ?: null,
            'ppn_enabled' => $ppn_enabled,
            'ppn_type' => $ppn_type,
            'ppn_percent' => $ppn_enabled ? $ppn_percent : 0,
            'dpp_amount' => $dpp_amount,
            'ppn_amount' => $ppn_amount,
            'supplier_id'         => $vendor['supplier_id'],
            'vendor_name'         => $vendor['vendor_name'],
            'vendor_bank_name'    => $vendor['vendor_bank_name'],
            'vendor_bank_account' => $vendor['vendor_bank_account'],
            'wht23_enabled'       => $wht['wht23_enabled'],
            'wht23_dpp'           => $wht['wht23_dpp'],
            'wht23_rate'          => $wht['wht23_rate'],
            'wht23_amount'        => $wht['wht23_amount'],
            'wht42_enabled'       => $wht['wht42_enabled'],
            'wht42_dpp'           => $wht['wht42_dpp'],
            'wht42_rate'          => $wht['wht42_rate'],
            'wht42_amount'        => $wht['wht42_amount'],
            'description' => $this->input->post('description'),
            'currency' => $this->input->post('currency'),
            'exchange_rate' => (float) $this->input->post('exchange_rate')
        ];

        // Collect details data (user rows)
        $details_data = [];
        $details_post = $this->input->post('details');

        if (!empty($details_post) && is_array($details_post)) {
            foreach ($details_post as $detail) {
                $account_id = isset($detail['account_id']) ? $detail['account_id'] : '';
                if (empty($account_id)) continue;

                $debit  = (float) (isset($detail['debit'])  ? $detail['debit']  : 0);
                $kredit = (float) (isset($detail['kredit']) ? $detail['kredit'] : 0);

                if ($debit > 0 || $kredit > 0) {
                    $details_data[] = [
                        'account_id'  => $account_id,
                        'debit'       => $debit,
                        'kredit'      => $kredit,
                        'description' => isset($detail['description']) ? $detail['description'] : '',
                        'is_ppn_line' => 0,
                    ];
                }
            }
        }

        // Append baris PPN otomatis
        $ppn_row = $this->_build_ppn_detail_row($ppn_enabled, $ppn_type, $ppn_amount);
        if ($ppn_row) {
            $details_data[] = $ppn_row;
        }

        // Append baris WHT (Cr Hutang PPh) + Bank jadi net
        if ($wht['total'] > 0 && $this->input->post('currency') !== 'IDR') {
            $this->session->set_flashdata('error', 'WHT hanya berlaku untuk mata uang IDR.');
            $this->edit($id);
            return;
        }
        if ($acc_err = $this->_check_wht_accounts($wht)) {
            $this->session->set_flashdata('error', $acc_err);
            $this->edit($id);
            return;
        }
        $details_wht = $this->_apply_wht_net($details_data, $wht);
        if ($details_wht === false) {
            $this->session->set_flashdata('error', 'Gagal: total WHT melebihi nilai pembayaran (Bank/Kas). Periksa baris kredit.');
            $this->edit($id);
            return;
        }
        $details_data = $details_wht;

        // Hitung total termasuk baris PPN
        $total_debit = 0;
        $total_kredit = 0;
        foreach ($details_data as $d) {
            $total_debit  += (float) $d['debit'];
            $total_kredit += (float) $d['kredit'];
        }

        // Validate balance
        if (abs($total_debit - $total_kredit) > 0.01) {
            $this->session->set_flashdata('error', 'Total Debit dan Kredit harus seimbang!');
            $this->edit($id);
            return;
        }

        // Add totals to header data
        $rate = $header_data['exchange_rate'];
        $header_data['total_debit'] = $total_debit;
        $header_data['total_kredit'] = $total_kredit;
        $header_data['total_debit_local'] = $total_debit * $rate;
        $header_data['total_kredit_local'] = $total_kredit * $rate;

        // Update journal
        if ($this->model->update_journal($id, $header_data, $details_data)) {
            $this->session->set_flashdata('success', 'Jurnal berhasil diperbarui.');
            redirect('jurnalumum/view/' . $id);
        } else {
            $this->session->set_flashdata('error', 'Gagal memperbarui jurnal.');
            $this->edit($id);
        }
    }

    public function post($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header || $header->status != 'Draft') {
            $this->session->set_flashdata('error', 'Hanya jurnal status Draft yang bisa di-posting.');
            redirect('jurnalumum/view/' . $id);
        }

        if ($header->total_debit != $header->total_kredit) {
             $this->session->set_flashdata('error', 'Jurnal tidak seimbang (Unbalanced). Posting dibatalkan.');
             redirect('jurnalumum/view/' . $id);
        }

        $result = $this->model->post_journal($id, $this->session->userdata('user_id'));
        if ($result) {
            $this->session->set_flashdata('success', 'Jurnal berhasil di-posting.');
        } else {
            $this->session->set_flashdata('error', 'Gagal mem-posting jurnal.');
        }
        redirect('jurnalumum/view/' . $id);
    }

    /**
     * ✅ BATCH POST - Post multiple draft journals at once
     */
    public function batch_post()
    {
        // Get journal IDs from POST
        $journal_ids = $this->input->post('journal_ids');
        
        if (empty($journal_ids) || !is_array($journal_ids)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => FALSE,
                    'message' => 'Tidak ada jurnal yang dipilih.'
                ]));
        }

        $user_id = $this->session->userdata('user_id');
        $success_count = 0;
        $failed_count = 0;
        $failed_journals = [];

        foreach ($journal_ids as $id) {
            $header = $this->model->get_header_by_id($id);
            
            // Validasi: Hanya Draft yang bisa di-posting
            if (!$header || $header->status != 'Draft') {
                $failed_count++;
                $failed_journals[] = [
                    'id' => $id,
                    'journal_no' => $header ? $header->journal_no : 'Unknown',
                    'reason' => 'Status bukan Draft'
                ];
                continue;
            }

            // Validasi: Jurnal harus seimbang
            if ($header->total_debit != $header->total_kredit) {
                $failed_count++;
                $failed_journals[] = [
                    'id' => $id,
                    'journal_no' => $header->journal_no,
                    'reason' => 'Jurnal tidak seimbang (Debit: ' . $header->total_debit . ', Kredit: ' . $header->total_kredit . ')'
                ];
                continue;
            }

            // Post the journal
            try {
                $result = $this->model->post_journal($id, $user_id);
                if ($result) {
                    $success_count++;
                } else {
                    $failed_count++;
                    $failed_journals[] = [
                        'id' => $id,
                        'journal_no' => $header->journal_no,
                        'reason' => 'Gagal proses database'
                    ];
                }
            } catch (Exception $e) {
                $failed_count++;
                $failed_journals[] = [
                    'id' => $id,
                    'journal_no' => $header->journal_no,
                    'reason' => $e->getMessage()
                ];
            }
        }

        // Prepare response
        $message = "Posting Selesai:<br>";
        $message .= "✓ Berhasil: <strong>{$success_count}</strong> jurnal<br>";
        if ($failed_count > 0) {
            $message .= "✗ Gagal: <strong>{$failed_count}</strong> jurnal";
            if (count($failed_journals) > 0) {
                $message .= "<ul style='margin-top: 10px; font-size: 0.9em;'>";
                foreach ($failed_journals as $fj) {
                    $message .= "<li><strong>{$fj['journal_no']}</strong>: {$fj['reason']}</li>";
                }
                $message .= "</ul>";
            }
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => TRUE,
                'success_count' => $success_count,
                'failed_count' => $failed_count,
                'message' => $message,
                'failed_journals' => $failed_journals
            ]));
    }
    
    public function delete($id)
    {
        $header = $this->model->get_header_by_id($id);
        $result = $this->model->delete_draft_journal($id);
        if ($result) {
            if ($header && $header->source_module === 'PRF') {
                $this->Prf_model->unlink_journal($id);
            }
            $this->session->set_flashdata('success', 'Jurnal Draft berhasil dihapus.');
            redirect('jurnalumum');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus. Hanya status Draft yang bisa dihapus.');
            redirect('jurnalumum/view/' . $id);
        }
    }

    public function import()
    {
        $data['title'] = lang_line('title_ju_import', 'Impor Detail Jurnal');
        $data['journal_no'] = $this->input->get('journal_no');
        $data['journal_date'] = $this->input->get('journal_date');
        $data['description'] = $this->input->get('description');

        if(empty($data['journal_no'])) {
            $this->session->set_flashdata('error', 'Sesi impor tidak valid. Harap mulai dari form Jurnal Umum.');
            redirect('jurnalumum/create');
        }

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/import', $data);
        $this->load->view('templates/footer');
    }
    
    public function process_import()
    {
        $config['upload_path']   = './uploads/temp/';
        $config['allowed_types'] = 'xls|xlsx|csv';
        $config['max_size']      = 5120; // 5MB
        $config['encrypt_name']  = TRUE;

        if (!is_dir($config['upload_path'])) {
            mkdir($config['upload_path'], 0777, TRUE);
        }

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file_import')) {
            $this->session->set_flashdata('error', $this->upload->display_errors());
            redirect('jurnalumum/import?' . http_build_query($this->input->post()));
        } else {
            $file_data = $this->upload->data();
            $file_path = $config['upload_path'] . $file_data['file_name'];

            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
            if ('csv' == $extension) {
                $reader = new Csv();
            } else if ('xls' == $extension) {
                $reader = new Xls();
            } else {
                $reader = new Xlsx();
            }

            try {
                $spreadsheet = $reader->load($file_path);
                $sheet_data = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                
                $details = $this->model->process_import_data($sheet_data);
                
                unlink($file_path);

                if (empty($details)) {
                     $this->session->set_flashdata('error', 'File berhasil dibaca tetapi tidak ada data valid yang ditemukan. Pastikan format kolom benar.');
                     redirect('jurnalumum/import?' . http_build_query($this->input->post()));
                }

                $this->session->set_flashdata('imported_details', $details);
                $this->session->set_flashdata('success', 'File berhasil diimpor. Silakan periksa detail di bawah.');
                redirect('jurnalumum/create?' . http_build_query($this->input->post()));

            } catch (Exception $e) {
                unlink($file_path);
                $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
                redirect('jurnalumum/import?' . http_build_query($this->input->post()));
            }
        }
    }

    private function _validate_journal()
    {
        $this->form_validation->set_rules('journal_date', 'Tanggal Jurnal', 'required');
        $this->form_validation->set_rules('description', 'Keterangan', 'required');
        
        $details = $this->input->post('details');
        if (empty($details)) {
            $this->form_validation->set_rules('details_dummy', 'Detail Jurnal', 'required', ['required' => 'Detail Jurnal tidak boleh kosong.']);
        } else {
            foreach ($details as $i => $detail) {
                $this->form_validation->set_rules("details[{$i}][account_id]", "Baris " . ($i+1) . " Akun", 'required');
                $this->form_validation->set_rules("details[{$i}][debit]", "Baris " . ($i+1) . " Debit", 'numeric');
                $this->form_validation->set_rules("details[{$i}][kredit]", "Baris " . ($i+1) . " Kredit", 'numeric');
            }
        }
    }

    private function _calculate_totals($details, $rate = 1.0)
    {
        $total_debit = 0;
        $total_kredit = 0;
        $total_debit_local = 0;
        $total_kredit_local = 0;

        if (!empty($details)) {
            foreach ($details as $d) {
                $debit = (float) $d['debit'];
                $kredit = (float) $d['kredit'];
                
                $total_debit += $debit;
                $total_kredit += $kredit;
                $total_debit_local += $debit * $rate;
                $total_kredit_local += $kredit * $rate;
            }
        }
        return [
            'debit' => $total_debit, 
            'kredit' => $total_kredit,
            'debit_local' => $total_debit_local,
            'kredit_local' => $total_kredit_local
        ];
    }

    /**
     * ✅ Export ke Excel dengan validasi pembuat
     */
    public function export_excel($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();
        
        $details = $this->model->get_details_by_header_id($id);
        $creator = $this->_get_user_by_id($header->created_by);
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->setCellValue('A1', 'JURNAL UMUM (JURNAL ENTRY)');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A2', 'No. Jurnal: ' . $header->journal_no);
        $sheet->setCellValue('A3', 'Tanggal: ' . date('d/m/Y', strtotime($header->journal_date)));
        $sheet->setCellValue('A4', 'Keterangan: ' . $header->description);
        $sheet->setCellValue('A5', 'Mata Uang: ' . $header->currency);
        $sheet->setCellValue('A6', 'Kurs: ' . number_format($header->exchange_rate, 4));
        $sheet->setCellValue('A7', 'Status: ' . $header->status);
        
        // Detail Header
        $sheet->setCellValue('A9', 'No.');
        $sheet->setCellValue('B9', 'Kode Akun');
        $sheet->setCellValue('C9', 'Nama Akun');
        $sheet->setCellValue('D9', 'Debit');
        $sheet->setCellValue('E9', 'Kredit');
        $sheet->setCellValue('F9', 'Keterangan');
        
        $sheet->getStyle('A9:F9')->getFont()->setBold(true);
        $sheet->getStyle('A9:F9')->getFill()->setFillType('solid')->getStartColor()->setARGB('FFD3D3D3');
        
        // Detail Data
        $row = 10;
        $no = 1;
        foreach ($details as $detail) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $detail->account_code ?? '-');
            $sheet->setCellValue('C' . $row, $detail->account_name ?? '-');
            $sheet->setCellValue('D' . $row, (float)$detail->debit);
            $sheet->setCellValue('E' . $row, (float)$detail->kredit);
            $sheet->setCellValue('F' . $row, $detail->description ?? '');
            
            $sheet->getStyle('D' . $row . ':E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }
        
        // Total
        $sheet->setCellValue('C' . $row, 'TOTAL');
        $sheet->setCellValue('D' . $row, (float)$header->total_debit);
        $sheet->setCellValue('E' . $row, (float)$header->total_kredit);
        $sheet->getStyle('C' . $row . ':E' . $row)->getFont()->setBold(true);
        $sheet->getStyle('D' . $row . ':E' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Validasi Pembuat
        $row += 2;
        $sheet->setCellValue('A' . $row, 'Dibuat oleh:');
        $sheet->setCellValue('A' . ($row + 1), $creator->fullname ?? 'Unknown');
        $sheet->setCellValue('A' . ($row + 2), $creator->username ?? '');
        $sheet->setCellValue('A' . ($row + 3), date('d/m/Y H:i:s', strtotime($header->created_at ?? now())));
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(12);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        
        $filename = 'Jurnal_' . str_replace('/', '-', $header->journal_no) . '_' . date('YmdHis') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        $writer = new XlsxWriter($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * ✅ Export ke PDF dengan validasi pembuat
     */
    public function export_pdf($id)
    {
        $header = $this->model->get_header_by_id($id);
        if (!$header) show_404();
        
        $details = $this->model->get_details_by_header_id($id);
        $creator = $this->_get_user_by_id($header->created_by);
        
        $data = [
            'header' => $header,
            'details' => $details,
            'creator' => $creator
        ];
        
        $html = $this->load->view('accounting/jurnal_umum/export_pdf', $data, true);
        
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'Jurnal_' . str_replace('/', '-', $header->journal_no) . '_' . date('YmdHis') . '.pdf';
        $dompdf->stream($filename, ['Attachment' => 0]);
    }

    /**
     * Helper: Get user by ID
     */
    private function _get_user_by_id($user_id)
    {
        if (empty($user_id)) {
            return (object) ['fullname' => 'Unknown', 'username' => ''];
        }
        return $this->db->select('id, fullname, username')->where('id', $user_id)->get('users')->row();
 
    }

    /**
     * ✅ Export List Jurnal ke Excel dengan Filter
     */
    public function export_list_excel()
    {
        $start_date = $this->input->get('start_date');
        $end_date = $this->input->get('end_date');
        $status = $this->input->get('status');
        
        // Build query dengan filter
        $this->db->select('jh.*, u.fullname as creator_name');
        $this->db->from('journal_header jh');
        $this->db->join('users u', 'jh.created_by = u.id', 'left');
        
        if (!empty($start_date)) {
            $this->db->where('jh.journal_date >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('jh.journal_date <=', $end_date);
        }
        if (!empty($status)) {
            $this->db->where('jh.status', $status);
        }
        
        $this->db->order_by('jh.journal_date', 'DESC');
        $this->db->order_by('jh.id', 'DESC');
        $journals = $this->db->get()->result();
        
        // Create Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->setCellValue('A1', 'DAFTAR JURNAL UMUM');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        // Filter info
        $filterInfo = 'Periode: ';
        if ($start_date && $end_date) {
            $filterInfo .= date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date));
        } elseif ($start_date) {
            $filterInfo .= 'Dari ' . date('d/m/Y', strtotime($start_date));
        } elseif ($end_date) {
            $filterInfo .= 'Sampai ' . date('d/m/Y', strtotime($end_date));
        } else {
            $filterInfo .= 'Semua';
        }
        if ($status) {
            $filterInfo .= ' | Status: ' . $status;
        }
        
        $sheet->setCellValue('A2', $filterInfo);
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setItalic(true);
        
        // Column Headers
        $sheet->setCellValue('A4', 'No.');
        $sheet->setCellValue('B4', 'No. Jurnal');
        $sheet->setCellValue('C4', 'Tanggal');
        $sheet->setCellValue('D4', 'Keterangan');
        $sheet->setCellValue('E4', 'Mata Uang');
        $sheet->setCellValue('F4', 'Debit');
        $sheet->setCellValue('G4', 'Kredit');
        $sheet->setCellValue('H4', 'Status');
        
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A4:H4')->getFont()->getColor()->setARGB('FFFFFFFF');
        
        // Data
        $row = 5;
        $no = 1;
        $total_debit = 0;
        $total_kredit = 0;
        
        foreach ($journals as $j) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $j->journal_no);
            $sheet->setCellValue('C' . $row, date('d/m/Y', strtotime($j->journal_date)));
            $sheet->setCellValue('D' . $row, $j->description);
            $sheet->setCellValue('E' . $row, $j->currency ?? 'IDR');
            $sheet->setCellValue('F' . $row, (float)$j->total_debit);
            $sheet->setCellValue('G' . $row, (float)$j->total_kredit);
            $sheet->setCellValue('H' . $row, $j->status);
            
            $sheet->getStyle('F' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            
            $total_debit += (float)$j->total_debit;
            $total_kredit += (float)$j->total_kredit;
            
            $row++;
        }
        
        // Total
        $sheet->setCellValue('E' . $row, 'TOTAL:');
        $sheet->setCellValue('F' . $row, $total_debit);
        $sheet->setCellValue('G' . $row, $total_kredit);
        $sheet->getStyle('E' . $row . ':G' . $row)->getFont()->setBold(true);
        $sheet->getStyle('F' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0');
        
        // Column widths
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(12);
        
        $filename = 'Daftar_Jurnal_' . date('YmdHis') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        $writer = new XlsxWriter($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * ✅ BATCH IMPORT - Upload Excel dengan banyak transaksi
     * Pemisah transaksi menggunakan kolom Reference
     */
    public function batch_import()
    {
        $data['title'] = lang_line('jurnal_umum_batch_import', 'Batch Import Jurnal Umum');
        
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/batch_import', $data);
        $this->load->view('templates/footer');
    }

    /**
     * ✅ Process Batch Import dari Excel
     */
    public function process_batch_import()
    {
        // Gunakan absolute path untuk upload directory
        $upload_path = FCPATH . 'uploads/temp/';
        
        // Buat direktori jika belum ada
        if (!is_dir($upload_path)) {
            if (!mkdir($upload_path, 0777, TRUE)) {
                $this->session->set_flashdata('error', 'Gagal membuat folder upload. Periksa permission folder.');
                redirect('jurnalumum/batch_import');
                return;
            }
        }
        
        // Cek apakah folder writable
        if (!is_writable($upload_path)) {
            $this->session->set_flashdata('error', 'Folder upload tidak dapat ditulis. Path: ' . $upload_path);
            redirect('jurnalumum/batch_import');
            return;
        }
        
        $config['upload_path']   = $upload_path;
        $config['allowed_types'] = 'xls|xlsx|csv';
        $config['max_size']      = 10240; // 10MB
        $config['encrypt_name']  = TRUE;

        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file_batch')) {
            $error = $this->upload->display_errors('', '');
            $this->session->set_flashdata('error', 'Upload gagal: ' . $error);
            redirect('jurnalumum/batch_import');
        } else {
            $file_data = $this->upload->data();
            $file_path = $file_data['full_path'];

            $extension = pathinfo($file_path, PATHINFO_EXTENSION);
            if ('csv' == $extension) {
                $reader = new Csv();
            } else if ('xls' == $extension) {
                $reader = new Xls();
            } else {
                $reader = new Xlsx();
            }

            try {
                $spreadsheet = $reader->load($file_path);
                $worksheet = $spreadsheet->getActiveSheet();
                
                // ✅ Baca data dengan cara yang benar untuk preserve numeric values
                $sheet_data = [];
                $highestRow = $worksheet->getHighestRow();
                $highestColumn = $worksheet->getHighestColumn();
                
                for ($row = 1; $row <= $highestRow; $row++) {
                    $rowData = [];
                    foreach (range('A', $highestColumn) as $col) {
                        $cell = $worksheet->getCell($col . $row);
                        // Get calculated value (untuk formula) atau raw value
                        $rowData[$col] = $cell->getCalculatedValue();
                    }
                    $sheet_data[$row] = $rowData;
                }
                
                // Process batch data
                $result = $this->model->process_batch_import($sheet_data, $this->session->userdata('user_id'));
                
                unlink($file_path);

                if ($result['success']) {
                    $message = "Berhasil mengimpor {$result['total_transactions']} transaksi jurnal:<br>";
                    foreach ($result['journal_numbers'] as $jno) {
                        $message .= "✓ {$jno}<br>";
                    }
                    $this->session->set_flashdata('success', $message);
                    redirect('jurnalumum');
                } else {
                    $this->session->set_flashdata('error', 'Gagal mengimpor: ' . $result['message']);
                    redirect('jurnalumum/batch_import');
                }

            } catch (Exception $e) {
                if (file_exists($file_path)) unlink($file_path);
                $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
                redirect('jurnalumum/batch_import');
            }
        }
    }

    /**
     * ✅ Download Template Excel untuk Batch Import
     */
    public function download_batch_template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->setCellValue('A1', 'TEMPLATE BATCH IMPORT JURNAL UMUM');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
        
        $sheet->setCellValue('A2', 'Petunjuk: Isi data mulai dari baris 5. Setiap transaksi baru ditandai dengan Reference baru di kolom pertama.');
        $sheet->mergeCells('A2:H2');
        
        $sheet->setCellValue('A3', 'PENTING: Untuk kolom Debit/Kredit, isi angka TANPA titik atau koma. Contoh: 50000000 (bukan 50.000.000)');
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->getColor()->setARGB('FFFF0000');
        
        // Column Headers
        $sheet->setCellValue('A4', 'Reference');
        $sheet->setCellValue('B4', 'Tanggal (YYYY-MM-DD)');
        $sheet->setCellValue('C4', 'Keterangan Jurnal');
        $sheet->setCellValue('D4', 'Kode Akun');
        $sheet->setCellValue('E4', 'Nama Akun');
        $sheet->setCellValue('F4', 'Debit');
        $sheet->setCellValue('G4', 'Kredit');
        $sheet->setCellValue('H4', 'Keterangan Detail');
        
        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A4:H4')->getFont()->getColor()->setARGB('FFFFFFFF');
        
        // Contoh Data - Transaksi 1: Gaji (Debit dulu, lalu Kredit)
        $sheet->setCellValue('A5', 'GAJI-JAN-2026');
        $sheet->setCellValue('B5', '2026-01-31');
        $sheet->setCellValue('C5', 'Pembayaran Gaji Karyawan Januari 2026');
        $sheet->setCellValueExplicit('D5', '5.200.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E5', 'Beban Gaji');
        $sheet->setCellValue('F5', 50000000);
        $sheet->setCellValue('G5', 0);
        $sheet->setCellValue('H5', 'Gaji pokok karyawan');
        
        $sheet->setCellValue('A6', '');
        $sheet->setCellValue('B6', '2026-01-31');
        $sheet->setCellValue('C6', '');
        $sheet->setCellValueExplicit('D6', '1.101.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E6', 'Kas');
        $sheet->setCellValue('F6', 0);
        $sheet->setCellValue('G6', 50000000);
        $sheet->setCellValue('H6', 'Pembayaran tunai');
        
        // Transaksi 2: Penjualan (Kredit dulu, lalu Debit - Reference di kredit)
        $sheet->setCellValue('A7', 'PENJUALAN-001');
        $sheet->setCellValue('B7', '2026-01-15');
        $sheet->setCellValue('C7', 'Penjualan Tunai');
        $sheet->setCellValueExplicit('D7', '4.110.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E7', 'Pendapatan Penjualan');
        $sheet->setCellValue('F7', 0);
        $sheet->setCellValue('G7', 10000000);
        $sheet->setCellValue('H7', 'Pendapatan hari ini');
        
        $sheet->setCellValue('A8', '');
        $sheet->setCellValue('B8', '2026-01-15');
        $sheet->setCellValue('C8', '');
        $sheet->setCellValueExplicit('D8', '1.101.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E8', 'Kas');
        $sheet->setCellValue('F8', 10000000);
        $sheet->setCellValue('G8', 0);
        $sheet->setCellValue('H8', 'Terima tunai');
        
        // Transaksi 3: Listrik dengan banyak detail
        $sheet->setCellValue('A9', 'LISTRIK-JAN-2026');
        $sheet->setCellValue('B9', '2026-01-20');
        $sheet->setCellValue('C9', 'Biaya Listrik Multi Lokasi');
        $sheet->setCellValueExplicit('D9', '6.220.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E9', 'Beban Listrik');
        $sheet->setCellValue('F9', 3000000);
        $sheet->setCellValue('G9', 0);
        $sheet->setCellValue('H9', 'Kantor Pusat');
        
        $sheet->setCellValue('A10', '');
        $sheet->setCellValue('B10', '2026-01-20');
        $sheet->setCellValue('C10', '');
        $sheet->setCellValueExplicit('D10', '6.220.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E10', 'Beban Listrik');
        $sheet->setCellValue('F10', 1500000);
        $sheet->setCellValue('G10', 0);
        $sheet->setCellValue('H10', 'Kantor Cabang');
        
        $sheet->setCellValue('A11', '');
        $sheet->setCellValue('B11', '2026-01-20');
        $sheet->setCellValue('C11', '');
        $sheet->setCellValueExplicit('D11', '1.101.001.00', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValue('E11', 'Kas');
        $sheet->setCellValue('F11', 0);
        $sheet->setCellValue('G11', 4500000);
        $sheet->setCellValue('H11', 'Pembayaran tunai');
        
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(25);
        
        // Format angka
        $sheet->getStyle('F5:G11')->getNumberFormat()->setFormatCode('#,##0');
        
        $filename = 'Template_Batch_Jurnal_' . date('YmdHis') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        
        $writer = new XlsxWriter($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // ============================================================
    // EXPORT FULL BACKUP + IMPORT RESTORE + CLEAR JOURNAL (Month End)
    // ============================================================

    /**
     * Export Full backup: semua jurnal Posted dalam periode → Excel lengkap
     * (semua field header + PPN/WHT/vendor + semua baris detail dgn flag).
     * Bisa di-upload ulang lewat Import Restore. Menandai jurnal sbg exported.
     */
    public function export_full()
    {
        $from = $this->input->get('from') ?: null;
        $to   = $this->input->get('to')   ?: null;

        $journals = $this->model->get_full_journals_for_export($from, $to);
        if (empty($journals)) {
            $this->session->set_flashdata('error', 'Tidak ada jurnal Posted pada periode tersebut untuk di-export.');
            redirect('jurnalumum');
            return;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Journal Backup');

        $cols = ['Journal No','Journal Date','Document Date','Due Date','Description','Currency','Exchange Rate',
            'Vendor Name','Vendor Bank Name','Vendor Bank Account','Tax Invoice No',
            'PPN Enabled','PPN Type','PPN Percent','DPP Amount','PPN Amount',
            'WHT23 Enabled','WHT23 DPP','WHT23 Rate','WHT23 Amount',
            'WHT42 Enabled','WHT42 DPP','WHT42 Rate','WHT42 Amount',
            'Account Code','Account Name','Debit','Credit','Line Description','is_ppn_line','is_wht_line'];
        $sheet->fromArray($cols, null, 'A1');
        $sheet->getStyle('A1:AE1')->getFont()->setBold(true);
        $sheet->getStyle('A1:AE1')->getFill()->setFillType('solid')->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle('A1:AE1')->getFont()->getColor()->setARGB('FFFFFFFF');

        $r = 2;
        $exported_ids = [];
        foreach ($journals as $j) {
            $exported_ids[] = (int) $j->id;
            $det = !empty($j->details) ? $j->details : [(object) []];
            $first = true;
            foreach ($det as $d) {
                $row = [
                    $first ? $j->journal_no : '',
                    $j->journal_date, $j->document_date, $j->due_date, $j->description,
                    $j->currency, $j->exchange_rate,
                    $j->vendor_name, $j->vendor_bank_name, $j->vendor_bank_account, $j->tax_invoice_no,
                    $j->ppn_enabled, $j->ppn_type, $j->ppn_percent, $j->dpp_amount, $j->ppn_amount,
                    $j->wht23_enabled, $j->wht23_dpp, $j->wht23_rate, $j->wht23_amount,
                    $j->wht42_enabled, $j->wht42_dpp, $j->wht42_rate, $j->wht42_amount,
                    $d->account_code ?? '', $d->account_name ?? '', $d->debit ?? 0, $d->kredit ?? 0,
                    $d->description ?? '', $d->is_ppn_line ?? 0, $d->is_wht_line ?? 0,
                ];
                $sheet->fromArray($row, null, 'A' . $r);
                $first = false;
                $r++;
            }
        }

        $this->model->mark_exported($exported_ids, $this->session->userdata('user_id'));

        $filename = 'Journal_Backup_' . ($from ?: 'awal') . '_sd_' . ($to ?: 'akhir') . '_' . date('YmdHis') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        $writer = new XlsxWriter($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * AJAX cek sebelum Clear: jumlah jurnal Posted + yg belum di-export pada periode.
     */
    public function clear_check()
    {
        $from = $this->input->get('from') ?: null;
        $to   = $this->input->get('to')   ?: null;
        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'total'      => $this->model->count_posted_in_period($from, $to),
            'unexported' => $this->model->count_unexported_posted($from, $to),
        ]));
    }

    /**
     * Clear Journal (Month End): hapus jurnal Posted dalam periode.
     * Jurnal AP Payment ikut dihapus tapi link pembayaran di-null + jejak no jurnal.
     */
    public function clear_period()
    {
        if ($this->input->method() !== 'post') { redirect('jurnalumum'); return; }
        $from = $this->input->post('from') ?: null;
        $to   = $this->input->post('to')   ?: null;
        if (!$from || !$to) {
            $this->session->set_flashdata('error', 'Periode (dari & sampai tanggal) wajib diisi untuk Clear Journal.');
            redirect('jurnalumum'); return;
        }

        $res = $this->model->clear_posted_period($from, $to);
        if ($res['deleted'] > 0) {
            $msg = "Clear selesai: {$res['deleted']} jurnal Posted dihapus (periode {$from} s/d {$to}).";
            if ($res['ap_unlinked'] > 0) {
                $msg .= " {$res['ap_unlinked']} link pembayaran AP di-null (status Paid tetap aman).";
            }
            $this->session->set_flashdata('success', $msg);
        } else {
            $this->session->set_flashdata('error', 'Tidak ada jurnal Posted pada periode tersebut.');
        }
        redirect('jurnalumum');
    }

    /**
     * Halaman upload Import / Restore Jurnal dari file backup.
     */
    public function import_full()
    {
        $data['title'] = lang_line('ju_import_full_title', 'Import / Restore Jurnal (Backup)');
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/jurnal_umum/import_full', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Proses Import Restore: baca file backup → recreate jurnal sebagai Draft
     * (lengkap dgn metadata PPN/WHT/vendor + semua baris detail).
     */
    public function process_import_full()
    {
        $upload_path = FCPATH . 'uploads/temp/';
        if (!is_dir($upload_path)) @mkdir($upload_path, 0777, true);
        $config = ['upload_path' => $upload_path, 'allowed_types' => 'xls|xlsx|csv', 'max_size' => 20480, 'encrypt_name' => TRUE];
        $this->load->library('upload', $config);

        if (!$this->upload->do_upload('file_backup')) {
            $this->session->set_flashdata('error', 'Upload gagal: ' . $this->upload->display_errors('', ''));
            redirect('jurnalumum/import_full');
            return;
        }

        $fd   = $this->upload->data();
        $path = $fd['full_path'];
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $reader = ($ext === 'csv') ? new Csv() : (($ext === 'xls') ? new Xls() : new Xlsx());

        try {
            $sheet = $reader->load($path)->getActiveSheet();
            $rows  = $sheet->toArray(null, true, true, true); // keyed by column letter
            @unlink($path);

            // Grup baris per jurnal (kolom A = Journal No tidak kosong → jurnal baru)
            $journals = [];
            $cur = null;
            foreach ($rows as $rn => $row) {
                if ($rn == 1) continue;
                $jno  = trim((string) ($row['A'] ?? ''));
                $code = trim((string) ($row['Y'] ?? ''));
                if ($jno !== '') {
                    if ($cur) $journals[] = $cur;
                    $cur = ['h' => $row, 'd' => []];
                }
                if (!$cur) continue;
                if ($code !== '') $cur['d'][] = $row;
            }
            if ($cur) $journals[] = $cur;

            if (empty($journals)) {
                $this->session->set_flashdata('error', 'File backup tidak berisi jurnal yang valid.');
                redirect('jurnalumum/import_full');
                return;
            }

            $restored = 0; $skipped = [];
            foreach ($journals as $jr) {
                $h = $jr['h'];
                $rate = (float) ($h['G'] ?: 1);
                $header_data = [
                    'journal_no'    => trim((string) $h['A']),
                    'journal_date'  => $this->_d($h['B']) ?: date('Y-m-d'),
                    'document_date' => $this->_d($h['C']) ?: null,
                    'due_date'      => $this->_d($h['D']) ?: null,
                    'description'   => $h['E'] ?: '',
                    'currency'      => $h['F'] ?: 'IDR',
                    'exchange_rate' => $rate ?: 1,
                    'vendor_name'   => $h['H'] ?: null,
                    'vendor_bank_name'    => $h['I'] ?: null,
                    'vendor_bank_account' => $h['J'] ?: null,
                    'tax_invoice_no'=> $h['K'] ?: null,
                    'ppn_enabled'   => (int) ($h['L'] ?: 0),
                    'ppn_type'      => $h['M'] ?: null,
                    'ppn_percent'   => (float) ($h['N'] ?: 0),
                    'dpp_amount'    => (float) ($h['O'] ?: 0),
                    'ppn_amount'    => (float) ($h['P'] ?: 0),
                    'wht23_enabled' => (int) ($h['Q'] ?: 0),
                    'wht23_dpp'     => (float) ($h['R'] ?: 0),
                    'wht23_rate'    => (float) ($h['S'] ?: 0),
                    'wht23_amount'  => (float) ($h['T'] ?: 0),
                    'wht42_enabled' => (int) ($h['U'] ?: 0),
                    'wht42_dpp'     => (float) ($h['V'] ?: 0),
                    'wht42_rate'    => (float) ($h['W'] ?: 0),
                    'wht42_amount'  => (float) ($h['X'] ?: 0),
                    'created_by'    => $this->session->userdata('user_id'),
                    'status'        => 'Draft',
                    'created_at'    => date('Y-m-d H:i:s'),
                ];

                $td = 0; $tc = 0; $details = []; $bad = false;
                foreach ($jr['d'] as $d) {
                    $code = trim((string) ($d['Y'] ?? ''));
                    $aid  = $this->model->get_account_id_by_code($code);
                    if (!$aid) { $skipped[] = $header_data['journal_no'] . " (akun {$code} tidak ada)"; $bad = true; break; }
                    $debit  = (float) ($d['AA'] ?? 0);
                    $kredit = (float) ($d['AB'] ?? 0);
                    $td += $debit; $tc += $kredit;
                    $details[] = [
                        'account_id'  => $aid,
                        'debit'       => $debit,
                        'kredit'      => $kredit,
                        'description' => $d['AC'] ?? '',
                        'is_ppn_line' => (int) ($d['AD'] ?? 0),
                        'is_wht_line' => (int) ($d['AE'] ?? 0),
                    ];
                }
                if ($bad || empty($details)) continue;

                $header_data['total_debit']        = $td;
                $header_data['total_kredit']       = $tc;
                $header_data['total_debit_local']  = $td * $rate;
                $header_data['total_kredit_local'] = $tc * $rate;

                if ($this->model->restore_journal($header_data, $details)) $restored++;
            }

            $msg = "Restore selesai: {$restored} jurnal dibuat sebagai Draft.";
            if (!empty($skipped)) $msg .= ' Dilewati: ' . implode('; ', array_slice($skipped, 0, 10));
            $this->session->set_flashdata($restored > 0 ? 'success' : 'error', $msg);
            redirect('jurnalumum');

        } catch (Exception $e) {
            if (file_exists($path)) @unlink($path);
            $this->session->set_flashdata('error', 'Gagal membaca file backup: ' . $e->getMessage());
            redirect('jurnalumum/import_full');
        }
    }

    /** Normalisasi nilai tanggal dari Excel (string 'Y-m-d' atau serial). */
    private function _d($v)
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v)->format('Y-m-d');
            } catch (\Throwable $e) { return null; }
        }
        $ts = strtotime(trim((string) $v));
        return $ts ? date('Y-m-d', $ts) : null;
    }
}