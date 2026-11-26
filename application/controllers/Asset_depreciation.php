<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penyusutan & Amortisasi — batch bulanan (aset tetap + biaya dibayar dimuka).
 * Logika di Asset_depreciation_model. Menu key: asset_depreciation.
 */
class Asset_depreciation extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) redirect('auth');
        if (!check_access('asset_depreciation', 'view')) show_error('Anda tidak memiliki akses ke Penyusutan & Amortisasi.', 403, 'Akses Ditolak');
        $this->load->model('Asset_depreciation_model', 'dep');
    }

    private function _render($view, $data)
    {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/asset_depreciation/' . $view, $data);
        $this->load->view('templates/footer');
    }

    private function _need($action)
    {
        if (!check_access('asset_depreciation', $action)) show_error('Anda tidak memiliki izin untuk aksi ini.', 403, 'Akses Ditolak');
    }

    public function index()
    {
        list($y, $m) = $this->dep->suggested_period();
        $preview_y = (int) ($this->input->get('y') ?: $y);
        $preview_m = (int) ($this->input->get('m') ?: $m);
        $this->_render('index', [
            'title' => lang_line('asset_depreciation_title', 'Penyusutan & Amortisasi'),
            'batches' => $this->dep->get_batches(),
            'sugg_y' => $y, 'sugg_m' => $m,
            'preview' => $this->input->get('preview') ? $this->dep->compute($preview_y, $preview_m) : null,
            'preview_y' => $preview_y, 'preview_m' => $preview_m,
            'has_draft' => (bool) array_filter($this->dep->get_batches(), function ($b) { return $b->status === 'Draft'; }),
        ]);
    }

    public function create()
    {
        $this->_need('add');
        if ($this->input->method() !== 'post') redirect('asset_depreciation');
        try {
            $id = $this->dep->create_batch($this->input->post('y'), $this->input->post('m'), $this->session->userdata('user_id'));
            $b = $this->dep->get_batch($id);
            add_log('CREATE', 'Asset Depreciation', 'Buat batch ' . $b->batch_number . ' total ' . number_format((float) $b->total_amount, 2));
            $this->session->set_flashdata('success', 'Batch ' . $b->batch_number . ' dibuat (Draft). Periksa angkanya lalu klik Posting.');
            redirect('asset_depreciation/view/' . $id);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', nl2br(htmlspecialchars($e->getMessage())));
            redirect('asset_depreciation');
        }
    }

    public function view($id)
    {
        $b = $this->dep->get_batch($id);
        if (!$b) show_404();
        $this->_render('view', [
            'title' => 'Batch ' . $b->batch_number, 'b' => $b,
            'assets' => $this->dep->get_asset_details($id),
            'prepaids' => $this->dep->get_prepaid_details($id),
            'is_latest' => ($l = $this->dep->latest_batch()) && (int) $l->id === (int) $b->id,
        ]);
    }

    /** Ekspor detail batch (aset & biaya dibayar dimuka + ringkasan jurnal) ke Excel. */
    public function export($id)
    {
        $b = $this->dep->get_batch($id);
        if (!$b) show_404();
        $assets = $this->dep->get_asset_details($id);
        $prepaids = $this->dep->get_prepaid_details($id);
        $label = Asset_depreciation_model::period_label($b->periode_year, $b->periode_month);
        $sub = 'Periode ' . $label . ' · Status ' . $b->status . ($b->journal_no ? ' · Jurnal ' . $b->journal_no . ' (' . $b->journal_status . ')' : '');
        $this->load->library('xlsx_report');
        $x = $this->xlsx_report;

        $rows = []; $t = 0.0;
        foreach ($assets as $d) {
            $rows[] = [$d->no_asset, $d->nama, $d->department, (float) $d->nilai_perolehan, (float) $d->akumulasi_sebelum, (float) $d->penyusutan_bulan_ini, (float) $d->akumulasi_sesudah, (float) $d->nilai_buku_sesudah, $d->coa_beban_code, $d->coa_akum_code];
            $t += (float) $d->penyusutan_bulan_ini;
        }
        $rows[] = ['_style' => 'total', 'cells' => ['', 'TOTAL', '', null, null, $t]];
        $x->add_sheet([
            'title' => 'Penyusutan Aset', 'heading' => ['Penyusutan Aset Tetap ' . $b->batch_number, $sub],
            'columns' => [
                ['label' => 'No. Aset', 'width' => 16], ['label' => 'Nama Aset', 'width' => 30], ['label' => 'Departemen', 'width' => 14],
                ['label' => 'Nilai Perolehan', 'type' => 'money'], ['label' => 'Akumulasi Sebelum', 'type' => 'money'], ['label' => 'Penyusutan', 'type' => 'money'],
                ['label' => 'Akumulasi Sesudah', 'type' => 'money'], ['label' => 'Nilai Buku', 'type' => 'money'],
                ['label' => 'Akun Beban', 'width' => 14], ['label' => 'Akun Akumulasi', 'width' => 14],
            ],
            'rows' => $rows, 'freeze_cols' => 2,
        ]);

        $rows = []; $tp = 0.0;
        foreach ($prepaids as $d) {
            $rows[] = [$d->doc_no, $d->nama, $d->department, (float) $d->nilai, (float) $d->akumulasi_sebelum, (float) $d->amortisasi_bulan_ini, (float) $d->sisa_sesudah, $d->coa_expense_code, $d->coa_prepaid_code];
            $tp += (float) $d->amortisasi_bulan_ini;
        }
        $rows[] = ['_style' => 'total', 'cells' => ['', 'TOTAL', '', null, null, $tp]];
        $x->add_sheet([
            'title' => 'Amortisasi Dibayar Dimuka', 'heading' => ['Amortisasi Biaya Dibayar Dimuka ' . $b->batch_number, $sub],
            'columns' => [
                ['label' => 'No.', 'width' => 14], ['label' => 'Nama', 'width' => 30], ['label' => 'Departemen', 'width' => 14],
                ['label' => 'Nilai', 'type' => 'money'], ['label' => 'Akumulasi Sebelum', 'type' => 'money'], ['label' => 'Amortisasi', 'type' => 'money'],
                ['label' => 'Sisa', 'type' => 'money'], ['label' => 'Akun Beban', 'width' => 14], ['label' => 'Akun Dibayar Dimuka', 'width' => 16],
            ],
            'rows' => $rows, 'freeze_cols' => 2,
        ]);

        // Ringkasan jurnal (pengelompokan sama dengan saat posting)
        $jr = [];
        $add = function ($code, $side, $v) use (&$jr) { $k = ($code ?: '-') . '|' . $side; $jr[$k] = ($jr[$k] ?? 0) + (float) $v; };
        foreach ($assets as $d)   { $add($d->coa_beban_code, 'D', $d->penyusutan_bulan_ini); $add($d->coa_akum_code, 'K', $d->penyusutan_bulan_ini); }
        foreach ($prepaids as $d) { $add($d->coa_expense_code, 'D', $d->amortisasi_bulan_ini); $add($d->coa_prepaid_code, 'K', $d->amortisasi_bulan_ini); }
        $names = [];
        foreach ($this->db->select('account_code, account_name')->get('chart_of_accounts')->result() as $c) $names[$c->account_code] = $c->account_name;
        uksort($jr, function ($p, $q) { return strcmp(substr($p, -1) . $p, substr($q, -1) . $q); });
        $rows = [];
        foreach ($jr as $k => $v) { list($code, $side) = explode('|', $k); $rows[] = [$code, $names[$code] ?? '', $side === 'D' ? $v : null, $side === 'K' ? $v : null]; }
        $rows[] = ['_style' => 'total', 'cells' => ['', 'TOTAL', $t + $tp, $t + $tp]];
        $x->add_sheet([
            'title' => 'Ringkasan Jurnal', 'heading' => ['Ringkasan Jurnal ' . $b->batch_number, $sub],
            'columns' => [['label' => 'Kode Akun', 'width' => 14], ['label' => 'Nama Akun', 'width' => 34], ['label' => 'Debit', 'type' => 'money'], ['label' => 'Kredit', 'type' => 'money']],
            'rows' => $rows,
        ]);
        add_log('EXPORT', 'Asset Depreciation', 'Ekspor Excel batch ' . $b->batch_number);
        $x->download('Penyusutan_' . $b->batch_number);
    }

    private function _action($id, $perm, callable $fn, $ok_msg, $log)
    {
        $this->_need($perm);
        if ($this->input->method() !== 'post') show_error('Metode ilegal.', 405);
        try {
            $res = $fn();
            $b = $this->dep->get_batch($id);
            add_log('UPDATE', 'Asset Depreciation', sprintf($log, $b ? $b->batch_number : '#' . $id));
            $this->session->set_flashdata('success', sprintf($ok_msg, $b ? $b->batch_number : '', is_string($res) ? $res : ''));
        } catch (Exception $e) {
            $this->session->set_flashdata('error', nl2br(htmlspecialchars($e->getMessage())));
        }
    }

    public function recalc($id)
    {
        $this->_action($id, 'add', function () use ($id) { return $this->dep->recalc($id); }, 'Batch %s dihitung ulang.', 'Hitung ulang batch %s');
        redirect('asset_depreciation/view/' . (int) $id);
    }

    public function post($id)
    {
        $this->_action($id, 'confirm', function () use ($id) { return $this->dep->post($id, $this->session->userdata('user_id')); },
            'Batch %s diposting. Jurnal %s dibuat, nilai buku aset & sisa biaya dibayar dimuka diperbarui.', 'Posting batch %s');
        redirect('asset_depreciation/view/' . (int) $id);
    }

    public function unpost($id)
    {
        $this->_action($id, 'confirm', function () use ($id) { return $this->dep->unpost($id); },
            'Posting batch %s dibatalkan. Nilai aset dikembalikan, jurnal kembali Draft (diperbarui saat posting ulang).', 'Batalkan posting batch %s');
        redirect('asset_depreciation/view/' . (int) $id);
    }

    public function delete($id)
    {
        $this->_need('delete');
        if ($this->input->method() !== 'post') show_error('Metode ilegal.', 405);
        try {
            $no = $this->dep->delete_batch($id);
            add_log('DELETE', 'Asset Depreciation', 'Hapus batch ' . $no);
            $this->session->set_flashdata('success', 'Batch ' . $no . ' dihapus.');
            redirect('asset_depreciation');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('asset_depreciation/view/' . (int) $id);
        }
    }
}
