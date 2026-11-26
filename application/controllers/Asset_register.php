<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Aset Tetap (Asset Register) — daftar (+ ekspor Excel), input manual, kartu
 * aset, pelepasan (satu / beberapa unit sekaligus / sebagian), revaluasi.
 * Logika di Fixed_asset_model. Menu key: asset_register.
 */
class Asset_register extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if (!$this->session->userdata('username')) redirect('auth');
        if (!check_access('asset_register', 'view')) show_error('Anda tidak memiliki akses ke Aset Tetap.', 403, 'Akses Ditolak');
        $this->load->model('Fixed_asset_model', 'fa');
    }

    private function _render($view, $data)
    {
        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('templates/topbar', $data);
        $this->load->view('accounting/asset_register/' . $view, $data);
        $this->load->view('templates/footer');
    }

    private function _need($action)
    {
        if (!check_access('asset_register', $action)) show_error('Anda tidak memiliki izin untuk aksi ini.', 403, 'Akses Ditolak');
    }

    private function _list_filters()
    {
        return [
            'q'        => trim((string) $this->input->get('q')),
            'kategori' => (string) $this->input->get('kategori'),
            'status'   => (string) $this->input->get('status'),
            'sumber'   => (string) $this->input->get('sumber'),
        ];
    }

    public function index()
    {
        $filters = $this->_list_filters();
        $rows = $this->fa->get_list($filters);
        $tot = ['perolehan' => 0, 'akumulasi' => 0, 'buku' => 0];
        foreach ($rows as $r) {
            if ($r->status === 'Disposed') continue;
            $tot['perolehan'] += (float) $r->nilai_perolehan;
            $tot['akumulasi'] += (float) $r->akumulasi_penyusutan;
            $tot['buku']      += (float) $r->nilai_buku;
        }
        $this->_render('index', [
            'title' => lang_line('asset_register_title', 'Aset Tetap'),
            'assets' => $rows, 'filters' => $filters, 'totals' => $tot,
            'categories' => $this->fa->get_categories(),
        ]);
    }

    /** Ekspor daftar aset (filter sama dengan halaman daftar) ke Excel. */
    public function export()
    {
        $filters = $this->_list_filters();
        $rows = $this->fa->get_list($filters);
        $status_label = ['Aktif' => 'Aktif', 'Habis Disusutkan' => 'Habis Disusutkan', 'Disposed' => 'Dilepas'];
        $out = []; $tot = [0.0, 0.0, 0.0];
        foreach ($rows as $a) {
            $sch = $this->fa->schedule_info($a);
            $out[] = [
                $a->no_asset, $a->nama, $a->kategori_label ?: $a->kategori_efektif, $a->department, $a->lokasi, $a->pic,
                $a->sumber === 'PO' ? 'PO ' . $a->po_number : 'Manual', $a->tanggal_perolehan,
                (float) $a->nilai_perolehan, (float) $a->nilai_residu, $a->disusutkan ? (int) $a->umur_manfaat_bulan : 'Tidak disusutkan',
                $a->disusutkan ? $sch['monthly'] : null, $a->mulai_penyusutan,
                (float) $a->akumulasi_penyusutan, (float) $a->nilai_buku, $a->disusutkan ? $sch['sisa_bulan'] : null,
                trim($a->coa_asset_code . ' ' . $a->coa_asset_name), trim($a->coa_akum_code . ' ' . $a->coa_akum_name), trim($a->coa_beban_code . ' ' . $a->coa_beban_name),
                $status_label[$a->status] ?? $a->status, $a->disposed_at,
                $a->status === 'Disposed' ? ($a->disposal_type === 'JUAL' ? 'Dijual ' . number_format((float) $a->disposal_value, 2) : 'Dihapus') : null,
                $a->acq_journal_no ?: $a->ref_journal_no,
            ];
            if ($a->status !== 'Disposed') { $tot[0] += (float) $a->nilai_perolehan; $tot[1] += (float) $a->akumulasi_penyusutan; $tot[2] += (float) $a->nilai_buku; }
        }
        $out[] = ['_style' => 'total', 'cells' => ['', 'TOTAL (belum dilepas)', '', '', '', '', '', '', $tot[0], null, null, null, null, $tot[1], $tot[2]]];
        $desc = array_filter([
            $filters['q'] !== '' ? 'Cari "' . $filters['q'] . '"' : null,
            $filters['kategori'] !== '' ? 'Kategori ' . $filters['kategori'] : null,
            $filters['status'] !== '' ? 'Status ' . ($status_label[$filters['status']] ?? $filters['status']) : null,
            $filters['sumber'] !== '' ? 'Sumber ' . $filters['sumber'] : null,
        ]);
        $this->load->library('xlsx_report');
        $this->xlsx_report->add_sheet([
            'title' => 'Daftar Aset Tetap',
            'heading' => ['Daftar Aset Tetap per ' . date('d-m-Y'), $desc ? implode(' · ', $desc) : 'Semua aset'],
            'columns' => [
                ['label' => 'No. Aset', 'width' => 16], ['label' => 'Nama Aset', 'width' => 30], ['label' => 'Kategori', 'width' => 16],
                ['label' => 'Departemen', 'width' => 14], ['label' => 'Lokasi', 'width' => 14], ['label' => 'PIC', 'width' => 14],
                ['label' => 'Sumber', 'width' => 18], ['label' => 'Tgl Perolehan', 'type' => 'date'],
                ['label' => 'Nilai Perolehan', 'type' => 'money'], ['label' => 'Nilai Residu', 'type' => 'money'],
                ['label' => 'Umur (bln)', 'type' => 'int', 'width' => 10], ['label' => 'Penyusutan / bln', 'type' => 'money'],
                ['label' => 'Mulai Susut', 'type' => 'date'], ['label' => 'Akumulasi', 'type' => 'money'], ['label' => 'Nilai Buku', 'type' => 'money'],
                ['label' => 'Sisa (bln)', 'type' => 'int', 'width' => 9],
                ['label' => 'Akun Aset', 'width' => 26], ['label' => 'Akun Akumulasi', 'width' => 26], ['label' => 'Akun Beban', 'width' => 26],
                ['label' => 'Status', 'width' => 14], ['label' => 'Tgl Dilepas', 'type' => 'date'], ['label' => 'Pelepasan', 'width' => 20],
                ['label' => 'Jurnal Perolehan', 'width' => 18],
            ],
            'rows' => $out, 'freeze_cols' => 2,
            'notes' => ['Akumulasi & nilai buku per penyusutan terakhir yang diposting. Dicetak ' . date('d-m-Y H:i') . ' oleh ' . ($this->session->userdata('fullname') ?: $this->session->userdata('username'))],
        ]);
        add_log('EXPORT', 'Asset Register', 'Ekspor Excel daftar aset (' . count($rows) . ' baris)');
        $this->xlsx_report->download('Daftar_Aset_Tetap_' . date('Ymd'));
    }

    private function _form_data($title, $asset = null)
    {
        $this->load->model('SettingAkun_model');
        return [
            'title' => $title, 'asset' => $asset,
            'masters' => $this->fa->get_masters(), 'categories' => $this->fa->get_categories(),
            'departments' => $this->fa->get_departments(), 'accounts' => $this->fa->get_accounts(),
            'locked_financial' => $asset ? $this->fa->financial_locked($asset->id) : false,
        ];
    }

    public function add()
    {
        $this->_need('add');
        $this->_render('form', $this->_form_data(lang_line('asset_register_add', 'Input Aset Tetap Manual')));
    }

    public function store()
    {
        $this->_need('add');
        if ($this->input->method() !== 'post') redirect('asset_register/add');
        try {
            $ids = $this->fa->create_manual($this->input->post(), $this->session->userdata('user_id'));
            $a = $this->fa->get($ids[0]);
            add_log('CREATE', 'Asset Register', 'Input aset manual ' . $a->no_asset . (count($ids) > 1 ? ' (+' . (count($ids) - 1) . ' unit)' : '') . ' ' . $a->nama . ' nilai ' . number_format((float) $a->nilai_perolehan, 2));
            $this->session->set_flashdata('success', count($ids) . ' unit aset tersimpan (' . $a->no_asset . ($a->acq_journal_no ? ', jurnal perolehan ' . $a->acq_journal_no : '') . ').');
            redirect('asset_register/view/' . $ids[0]);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            $this->session->set_flashdata('old_input', $this->input->post());
            redirect('asset_register/add');
        }
    }

    public function view($id)
    {
        $a = $this->fa->get($id);
        if (!$a) show_404();
        $this->load->model('SettingAkun_model');
        $mutations = $this->fa->get_mutations($id);
        $this->_render('view', [
            'title' => 'Aset ' . $a->no_asset, 'a' => $a,
            'history' => $this->fa->get_history($id),
            'schedule' => $this->fa->schedule_info($a),
            'accounts' => $this->fa->get_accounts(),
            'has_posted' => $this->fa->has_posted_depreciation($id),
            'mutations' => $mutations,
            'mut_ready' => $this->fa->mut_ready,
            'last_posted' => $this->fa->last_posted_period($id),
            'siblings' => $a->status !== 'Disposed' ? $this->fa->siblings($a) : [],
            'disposal_group' => ($a->status === 'Disposed' && $a->disposal_journal_id)
                ? $this->db->select('id, no_asset')->where('disposal_journal_id', (int) $a->disposal_journal_id)->order_by('no_asset')->get('asset_register')->result() : [],
            'suggested_life' => $this->fa->suggested_remaining_life($a, date('Y-m-t')),
            'def_laba' => $this->SettingAkun_model->get_account_id('akun_laba_pelepasan_aset', 'IDR'),
            'def_rugi' => $this->SettingAkun_model->get_account_id('akun_rugi_pelepasan_aset', 'IDR'),
            'def_surplus' => $this->fa->setting_account('akun_surplus_revaluasi'),
            'def_lr_reval' => $this->fa->setting_account('akun_laba_rugi_revaluasi'),
            'def_saldo_laba' => $this->fa->setting_account('akun_saldo_laba'),
        ]);
    }

    public function edit($id)
    {
        $this->_need('edit');
        $a = $this->fa->get($id);
        if (!$a) show_404();
        $this->_render('form', $this->_form_data('Edit Aset ' . $a->no_asset, $a));
    }

    public function update($id)
    {
        $this->_need('edit');
        if ($this->input->method() !== 'post') redirect('asset_register/edit/' . (int) $id);
        try {
            $this->fa->update_asset($id, $this->input->post());
            $a = $this->fa->get($id);
            add_log('UPDATE', 'Asset Register', 'Edit aset ' . $a->no_asset);
            $this->session->set_flashdata('success', 'Data aset ' . $a->no_asset . ' disimpan.');
            redirect('asset_register/view/' . (int) $id);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('asset_register/edit/' . (int) $id);
        }
    }

    public function delete($id)
    {
        $this->_need('delete');
        if ($this->input->method() !== 'post') show_error('Metode ilegal.', 405);
        try {
            $nos = $this->fa->delete_asset($id);
            add_log('DELETE', 'Asset Register', 'Hapus aset manual ' . implode(', ', $nos));
            $this->session->set_flashdata('success', 'Aset dihapus: ' . implode(', ', $nos) . '.');
            redirect('asset_register');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('asset_register/view/' . (int) $id);
        }
    }

    public function dispose($id)
    {
        $this->_need('edit');
        if ($this->input->method() !== 'post') show_error('Metode ilegal.', 405);
        $post = $this->input->post();
        try {
            if (($post['dispose_scope'] ?? 'ALL') === 'PART') {
                $r = $this->fa->dispose_partial($id, $post, $this->session->userdata('user_id'));
                $a = $this->fa->get($id);
                $jno = $this->db->select('journal_no')->where('id', (int) $r['journal_id'])->get('journal_header')->row('journal_no');
                add_log('DISPOSE', 'Asset Register', 'Pelepasan sebagian aset ' . $a->no_asset . ' (' . $r['porsi'] . '), nilai buku ' . number_format($r['buku'], 2) . ', ' . ($r['hasil'] >= 0 ? 'laba ' : 'rugi ') . number_format(abs($r['hasil']), 2));
                $this->session->set_flashdata('success', 'Sebagian aset ' . $a->no_asset . ' (' . htmlspecialchars($r['porsi']) . ') dilepas. Jurnal ' . $jno . ' dibuat (' . ($r['hasil'] >= 0 ? 'laba' : 'rugi') . ' ' . number_format(abs($r['hasil']), 2) . ').');
            } else {
                $r = $this->fa->dispose($id, $post, $this->session->userdata('user_id'));
                $a = $this->fa->get($id);
                add_log('DISPOSE', 'Asset Register', ($a->disposal_type === 'JUAL' ? 'Jual' : 'Hapus') . ' aset ' . $a->no_asset . ', nilai buku ' . number_format($r['buku'], 2) . ', ' . ($r['hasil'] >= 0 ? 'laba ' : 'rugi ') . number_format(abs($r['hasil']), 2));
                $this->session->set_flashdata('success', 'Aset ' . $a->no_asset . ' dilepas. Jurnal ' . $a->disposal_journal_no . ' dibuat (' . ($r['hasil'] >= 0 ? 'laba' : 'rugi') . ' ' . number_format(abs($r['hasil']), 2) . ').');
            }
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal melepas aset: ' . htmlspecialchars($e->getMessage()));
        }
        redirect('asset_register/view/' . (int) $id);
    }

    /**
     * Pelepasan beberapa unit sekaligus. GET ?ids[]=..: form; POST: simpan
     * (satu jurnal untuk semua unit).
     */
    public function dispose_units()
    {
        $this->_need('edit');
        $this->load->model('SettingAkun_model');
        if ($this->input->method() === 'post') {
            $ids = (array) $this->input->post('ids');
            try {
                $r = $this->fa->dispose_units($ids, $this->input->post(), $this->session->userdata('user_id'));
                $jno = $this->db->select('journal_no')->where('id', (int) $r['journal_id'])->get('journal_header')->row('journal_no');
                $nos = array_map(function ($u) { return $u->no_asset; }, $this->fa->get_many($ids));
                add_log('DISPOSE', 'Asset Register', 'Pelepasan ' . $r['count'] . ' unit sekaligus (' . implode(', ', $nos) . '), nilai buku ' . number_format($r['buku'], 2) . ', ' . ($r['hasil'] >= 0 ? 'laba ' : 'rugi ') . number_format(abs($r['hasil']), 2) . ', jurnal ' . $jno);
                $this->session->set_flashdata('success', $r['count'] . ' unit aset dilepas dengan satu jurnal ' . $jno . ' (' . ($r['hasil'] >= 0 ? 'laba' : 'rugi') . ' ' . number_format(abs($r['hasil']), 2) . ').');
                redirect('asset_register?status=Disposed');
            } catch (Exception $e) {
                $this->session->set_flashdata('error', 'Gagal melepas aset: ' . htmlspecialchars($e->getMessage()));
                $this->session->set_flashdata('old_input', $this->input->post());
                redirect('asset_register/dispose_units?' . http_build_query(['ids' => array_map('intval', $ids)]));
            }
        }
        $ids = $this->input->get('ids');
        if (!is_array($ids)) $ids = array_filter(explode(',', (string) $ids));
        $units = array_values(array_filter($this->fa->get_many($ids), function ($u) { return in_array($u->status, ['Aktif', 'Habis Disusutkan'], true); }));
        if (!$units) {
            $this->session->set_flashdata('error', 'Pilih unit aset yang masih aktif (centang di daftar aset).');
            redirect('asset_register');
        }
        $this->_render('dispose_units', [
            'title' => 'Pelepasan Beberapa Unit Aset', 'units' => $units, 'accounts' => $this->fa->get_accounts(),
            'old' => $this->session->flashdata('old_input') ?: [],
            'def_laba' => $this->SettingAkun_model->get_account_id('akun_laba_pelepasan_aset', 'IDR'),
            'def_rugi' => $this->SettingAkun_model->get_account_id('akun_rugi_pelepasan_aset', 'IDR'),
            'def_surplus' => $this->fa->setting_account('akun_surplus_revaluasi'),
            'def_saldo_laba' => $this->fa->setting_account('akun_saldo_laba'),
        ]);
    }

    public function cancel_disposal($id)
    {
        $this->_need('edit');
        if ($this->input->method() !== 'post') show_error('Metode ilegal.', 405);
        try {
            $a = $this->fa->get($id);
            $nos = $this->fa->cancel_disposal($id);
            add_log('UPDATE', 'Asset Register', 'Batalkan pelepasan aset ' . implode(', ', $nos) . ' (jurnal ' . $a->disposal_journal_no . ' dihapus)');
            $this->session->set_flashdata('success', 'Pelepasan aset ' . implode(', ', $nos) . ' dibatalkan, jurnal ' . $a->disposal_journal_no . ' dihapus.');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', htmlspecialchars($e->getMessage()));
        }
        redirect('asset_register/view/' . (int) $id);
    }

    public function revalue($id)
    {
        $this->_need('edit');
        if ($this->input->method() !== 'post') show_error('Metode ilegal.', 405);
        try {
            $r = $this->fa->revalue($id, $this->input->post(), $this->session->userdata('user_id'));
            $a = $this->fa->get($id);
            $jno = $this->db->select('journal_no')->where('id', (int) $r['journal_id'])->get('journal_header')->row('journal_no');
            $parts = [];
            if (abs($r['ke_surplus']) > 0) $parts[] = 'surplus revaluasi ' . ($r['ke_surplus'] > 0 ? '+' : '') . number_format($r['ke_surplus'], 2);
            if (abs($r['ke_laba_rugi']) > 0) $parts[] = ($r['ke_laba_rugi'] > 0 ? 'pemulihan rugi revaluasi ' : 'rugi revaluasi ') . number_format(abs($r['ke_laba_rugi']), 2);
            add_log('UPDATE', 'Asset Register', 'Revaluasi aset ' . $a->no_asset . ' ke ' . number_format((float) $a->nilai_perolehan, 2) . ' (selisih ' . number_format($r['selisih'], 2) . '), jurnal ' . $jno);
            $this->session->set_flashdata('success', 'Aset ' . $a->no_asset . ' direvaluasi ke Rp ' . number_format((float) $a->nilai_perolehan, 2) . ($parts ? ' (' . implode(', ', $parts) . ')' : '') . '. Jurnal ' . $jno . ' dibuat.');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal revaluasi: ' . htmlspecialchars($e->getMessage()));
        }
        redirect('asset_register/view/' . (int) $id);
    }

    public function cancel_mutation($mutation_id)
    {
        $this->_need('edit');
        if ($this->input->method() !== 'post') show_error('Metode ilegal.', 405);
        $asset_id = (int) $this->db->select('asset_register_id')->where('id', (int) $mutation_id)->get('asset_mutations')->row('asset_register_id');
        try {
            $r = $this->fa->cancel_mutation($mutation_id);
            $label = $r['mutation']->jenis === 'REVALUASI' ? 'Revaluasi' : 'Pelepasan sebagian';
            add_log('UPDATE', 'Asset Register', 'Batalkan ' . strtolower($label) . ' aset ' . $r['asset']->no_asset . ' tanggal ' . $r['mutation']->tanggal);
            $this->session->set_flashdata('success', $label . ' aset ' . $r['asset']->no_asset . ' tanggal ' . date('d-m-Y', strtotime($r['mutation']->tanggal)) . ' dibatalkan; nilai aset dikembalikan & jurnalnya dihapus.');
        } catch (Exception $e) {
            $this->session->set_flashdata('error', htmlspecialchars($e->getMessage()));
        }
        redirect($asset_id ? 'asset_register/view/' . $asset_id : 'asset_register');
    }
}
