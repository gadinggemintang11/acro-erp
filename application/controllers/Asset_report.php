<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Laporan Aset Tetap & Biaya Dibayar Dimuka per rentang bulan: ringkasan mutasi
 * per kategori, rincian per aset, penyusutan per bulan, mutasi biaya dibayar
 * dimuka, daftar pelepasan & revaluasi. Ekspor Excel (5 sheet).
 * Akses: yang boleh melihat Aset Tetap atau Penyusutan.
 */
class Asset_report extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) redirect('auth');
        if (!check_access('asset_register', 'view') && !check_access('asset_depreciation', 'view')) {
            show_error('Anda tidak memiliki akses ke Laporan Aset.', 403, 'Akses Ditolak');
        }
        $this->load->model('Asset_report_model', 'rep');
        $this->load->model('Fixed_asset_model', 'fa');
    }

    private function _filters()
    {
        $g = function ($k, $def) { $v = (int) $this->input->get($k); return $v ?: $def; };
        $y1 = min(2100, max(2000, $g('y1', (int) date('Y'))));
        $m1 = min(12, max(1, $g('m1', 1)));
        $y2 = min(2100, max(2000, $g('y2', (int) date('Y'))));
        $m2 = min(12, max(1, $g('m2', (int) date('n'))));
        $p1 = $y1 * 12 + $m1; $p2 = $y2 * 12 + $m2;
        if ($p1 > $p2) { $t = $p1; $p1 = $p2; $p2 = $t; }
        if ($p2 - $p1 > 59) $p1 = $p2 - 59; // maks 60 bulan
        return [
            'p1' => $p1, 'p2' => $p2,
            'kategori' => (string) $this->input->get('kategori'),
            'department' => (string) $this->input->get('department'),
        ];
    }

    private function _data($f)
    {
        return [
            'assets'  => $this->rep->rollforward($f['p1'], $f['p2'], $f),
            'prepaid' => $this->rep->prepaid_rollforward($f['p1'], $f['p2'], $f),
        ];
    }

    public function index()
    {
        $f = $this->_filters();
        $data = $this->_data($f) + [
            'title' => lang_line('asset_report_title', 'Laporan Aset Tetap'),
            'f' => $f, 'categories' => $this->fa->get_categories(), 'departments' => $this->fa->get_departments(),
            'export_qs' => http_build_query(array_filter([
                'y1' => intdiv($f['p1'] - 1, 12), 'm1' => $f['p1'] - intdiv($f['p1'] - 1, 12) * 12,
                'y2' => intdiv($f['p2'] - 1, 12), 'm2' => $f['p2'] - intdiv($f['p2'] - 1, 12) * 12,
                'kategori' => $f['kategori'], 'department' => $f['department'],
            ])),
        ];
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/asset_report/index', $data);
        $this->load->view('templates/footer');
    }

    /** Kolom ringkasan mutasi (dipakai sheet ringkasan & rincian). */
    private static function _rf_columns()
    {
        $g1 = 'Harga Perolehan'; $g2 = 'Akumulasi Penyusutan'; $g3 = 'Nilai Buku';
        return [
            ['label' => 'Saldo Awal', 'type' => 'money', 'group' => $g1],
            ['label' => 'Penambahan', 'type' => 'money', 'group' => $g1],
            ['label' => 'Revaluasi', 'type' => 'money', 'group' => $g1],
            ['label' => 'Pengurangan', 'type' => 'money', 'group' => $g1],
            ['label' => 'Saldo Akhir', 'type' => 'money', 'group' => $g1],
            ['label' => 'Saldo Awal', 'type' => 'money', 'group' => $g2],
            ['label' => 'Penambahan (akum. awal)', 'type' => 'money', 'group' => $g2],
            ['label' => 'Penyusutan', 'type' => 'money', 'group' => $g2],
            ['label' => 'Revaluasi (eliminasi)', 'type' => 'money', 'group' => $g2],
            ['label' => 'Pengurangan', 'type' => 'money', 'group' => $g2],
            ['label' => 'Saldo Akhir', 'type' => 'money', 'group' => $g2],
            ['label' => 'Awal', 'type' => 'money', 'group' => $g3],
            ['label' => 'Akhir', 'type' => 'money', 'group' => $g3],
        ];
    }

    private static function _rf_values($v)
    {
        $v = (array) $v;
        return [$v['c0'], $v['c_add'], $v['c_reval'], $v['c_out'], $v['c1'], $v['a0'], $v['a_add'], $v['a_dep'], $v['a_reval'], $v['a_out'], $v['a1'], $v['b0'], $v['b1']];
    }

    public function export()
    {
        $f = $this->_filters();
        $d = $this->_data($f);
        $A = $d['assets']; $P = $d['prepaid'];
        $periode = Asset_report_model::pk_label($f['p1']) . ' s/d ' . Asset_report_model::pk_label($f['p2']);
        $sub = 'Periode ' . $periode
             . ($f['kategori'] !== '' ? ' · Kategori ' . $f['kategori'] : '')
             . ($f['department'] !== '' ? ' · Departemen ' . $f['department'] : '');
        $printed = 'Dicetak ' . date('d-m-Y H:i') . ' oleh ' . ($this->session->userdata('fullname') ?: $this->session->userdata('username'));
        $notes = ['Penyusutan hanya dari batch yang sudah Posted. ' . $printed];

        $this->load->library('xlsx_report');
        $x = $this->xlsx_report;

        // 1) Ringkasan per kategori
        $rows = [];
        foreach ($A['categories'] as $name => $c) $rows[] = array_merge([$name, $c['n']], self::_rf_values($c));
        $rows[] = ['_style' => 'total', 'cells' => array_merge(['TOTAL', $A['total']['n']], self::_rf_values($A['total']))];
        $x->add_sheet([
            'title' => 'Ringkasan Mutasi', 'heading' => ['Laporan Mutasi Aset Tetap', $sub],
            'columns' => array_merge([['label' => 'Kategori', 'width' => 26], ['label' => 'Jml Aset', 'type' => 'int', 'width' => 9]], self::_rf_columns()),
            'rows' => $rows, 'freeze_cols' => 1, 'notes' => $notes,
        ]);

        // 2) Rincian per aset (subtotal per kategori)
        $rows = []; $cur = null; $sub_v = null;
        $flush = function () use (&$rows, &$cur, &$sub_v) {
            if ($cur === null) return;
            $rows[] = ['_style' => 'subtotal', 'cells' => array_merge(['', 'Subtotal ' . $cur, '', '', '', '', '', '', '', ''], self::_rf_values($sub_v))];
        };
        foreach ($A['rows'] as $r) {
            if ($r->kategori_label !== $cur) {
                $flush();
                $cur = $r->kategori_label; $sub_v = array_fill_keys(['c0', 'c_add', 'c_reval', 'c_out', 'c1', 'a0', 'a_add', 'a_dep', 'a_reval', 'a_out', 'a1', 'b0', 'b1'], 0.0);
                $rows[] = ['_style' => 'section', 'cells' => [$cur]];
            }
            foreach ($sub_v as $k => $_) $sub_v[$k] += $r->$k;
            $rows[] = array_merge([
                $r->no_asset, $r->nama, $r->kategori_label, $r->department, $r->lokasi,
                $r->sumber === 'PO' ? 'PO ' . $r->po_number : 'Manual', $r->tanggal_perolehan,
                $r->disusutkan ? $r->umur : 'Tidak disusutkan', $r->coa_asset, $r->status,
            ], self::_rf_values($r));
        }
        $flush();
        $rows[] = ['_style' => 'total', 'cells' => array_merge(['', 'TOTAL', '', '', '', '', '', '', '', ''], self::_rf_values($A['total']))];
        $x->add_sheet([
            'title' => 'Rincian Aset', 'heading' => ['Rincian Aset Tetap', $sub],
            'columns' => array_merge([
                ['label' => 'No. Aset', 'width' => 16], ['label' => 'Nama Aset', 'width' => 30], ['label' => 'Kategori', 'width' => 16],
                ['label' => 'Departemen', 'width' => 14], ['label' => 'Lokasi', 'width' => 14], ['label' => 'Sumber', 'width' => 18],
                ['label' => 'Tgl Perolehan', 'type' => 'date'], ['label' => 'Umur (bln)', 'type' => 'int', 'width' => 10],
                ['label' => 'Akun Aset', 'width' => 26], ['label' => 'Status', 'width' => 12],
            ], self::_rf_columns()),
            'rows' => $rows, 'freeze_cols' => 2, 'notes' => $notes,
        ]);

        // 3) Penyusutan per bulan
        $mcols = []; foreach ($A['months'] as $pk) $mcols[] = ['label' => Asset_report_model::pk_label($pk), 'type' => 'money', 'width' => 14, 'group' => 'Penyusutan per Bulan'];
        $rows = []; $mt = array_fill_keys($A['months'], 0.0); $gt = 0.0;
        foreach ($A['rows'] as $r) {
            if (!$r->monthly) continue;
            $cells = [$r->no_asset, $r->nama, $r->kategori_label];
            foreach ($A['months'] as $pk) { $v = $r->monthly[$pk] ?? null; $cells[] = $v; if ($v) $mt[$pk] += $v; }
            $cells[] = $r->a_dep; $gt += $r->a_dep;
            $rows[] = $cells;
        }
        $rows[] = ['_style' => 'total', 'cells' => array_merge(['', 'TOTAL', ''], array_values($mt), [$gt])];
        $x->add_sheet([
            'title' => 'Penyusutan Bulanan', 'heading' => ['Penyusutan Aset Tetap per Bulan', $sub],
            'columns' => array_merge([['label' => 'No. Aset', 'width' => 16], ['label' => 'Nama Aset', 'width' => 30], ['label' => 'Kategori', 'width' => 16]], $mcols, [['label' => 'Total', 'type' => 'money']]),
            'rows' => $rows, 'freeze_cols' => 2, 'notes' => $notes,
        ]);

        // 4) Biaya dibayar dimuka
        $pcols = []; foreach ($A['months'] as $pk) $pcols[] = ['label' => Asset_report_model::pk_label($pk), 'type' => 'money', 'width' => 14, 'group' => 'Amortisasi per Bulan'];
        $rows = []; $pm = array_fill_keys($A['months'], 0.0);
        foreach ($P['rows'] as $r) {
            $cells = [$r->doc_no, $r->nama, $r->department, $r->tanggal_bayar, $r->nilai, $r->jumlah_bulan, $r->coa, $r->s0, $r->add, $r->amort, $r->s1];
            foreach ($A['months'] as $pk) { $v = $r->monthly[$pk] ?? null; $cells[] = $v; if ($v) $pm[$pk] += $v; }
            $rows[] = $cells;
        }
        $rows[] = ['_style' => 'total', 'cells' => array_merge(['', 'TOTAL', '', '', '', '', '', $P['total']['s0'], $P['total']['add'], $P['total']['amort'], $P['total']['s1']], array_values($pm))];
        $g = 'Saldo Biaya Dibayar Dimuka';
        $x->add_sheet([
            'title' => 'Biaya Dibayar Dimuka', 'heading' => ['Mutasi Biaya Dibayar Dimuka', 'Periode ' . $periode . ($f['department'] !== '' ? ' · Departemen ' . $f['department'] : '')],
            'columns' => array_merge([
                ['label' => 'No.', 'width' => 14], ['label' => 'Nama', 'width' => 30], ['label' => 'Departemen', 'width' => 14],
                ['label' => 'Tgl Bayar', 'type' => 'date'], ['label' => 'Nilai', 'type' => 'money'], ['label' => 'Jml Bulan', 'type' => 'int', 'width' => 9],
                ['label' => 'Akun (Dimuka / Beban)', 'width' => 22],
                ['label' => 'Saldo Awal', 'type' => 'money', 'group' => $g], ['label' => 'Penambahan', 'type' => 'money', 'group' => $g],
                ['label' => 'Amortisasi', 'type' => 'money', 'group' => $g], ['label' => 'Saldo Akhir', 'type' => 'money', 'group' => $g],
            ], $pcols),
            'rows' => $rows, 'freeze_cols' => 2, 'notes' => $notes,
        ]);

        // 5) Pelepasan & revaluasi
        $rows = [];
        foreach ($A['events'] as $e) {
            $rows[] = [$e['tanggal'], $e['no_asset'], $e['nama'], $e['jenis'], $e['porsi'], $e['nilai'], $e['akumulasi'], $e['buku'], $e['harga'], $e['laba_rugi'], $e['surplus'], $e['journal_no']];
        }
        $x->add_sheet([
            'title' => 'Pelepasan & Revaluasi', 'heading' => ['Pelepasan & Revaluasi Aset Tetap', $sub],
            'columns' => [
                ['label' => 'Tanggal', 'type' => 'date'], ['label' => 'No. Aset', 'width' => 16], ['label' => 'Nama Aset', 'width' => 30],
                ['label' => 'Jenis', 'width' => 16], ['label' => 'Porsi', 'width' => 18],
                ['label' => 'Harga Perolehan', 'type' => 'money'], ['label' => 'Akumulasi', 'type' => 'money'], ['label' => 'Nilai Buku', 'type' => 'money'],
                ['label' => 'Harga Jual / Nilai Wajar', 'type' => 'money', 'width' => 18],
                ['label' => 'Laba (Rugi)', 'type' => 'money'], ['label' => 'Surplus Revaluasi', 'type' => 'money'], ['label' => 'No. Jurnal', 'width' => 18],
            ],
            'rows' => $rows,
            'notes' => ['Revaluasi: Laba (Rugi) = ke laba rugi (pemulihan +, rugi -); Surplus = perubahan surplus revaluasi (ekuitas). Pelepasan sebagian: Surplus = surplus yang dipindah ke saldo laba. ' . $printed],
        ]);

        add_log('EXPORT', 'Asset Report', 'Ekspor Excel laporan aset ' . $periode);
        $x->download('Laporan_Aset_' . str_replace(' ', '', Asset_report_model::pk_label($f['p1']) . '-' . Asset_report_model::pk_label($f['p2'])));
    }
}
