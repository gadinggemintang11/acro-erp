<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Asset_depreciation_model — batch bulanan Penyusutan Aset Tetap & Amortisasi
 * Biaya Dibayar Dimuka (satu batch per periode, UNIQUE periode_year+month).
 *
 * Perhitungan (garis lurus, mulai bulan berikutnya setelah perolehan / bayar):
 *   target akumulasi s/d periode = min(nilai yg disusutkan, round(per_bulan x bulan_berjalan))
 *   penyusutan periode ini       = target - akumulasi saat ini (termasuk akumulasi awal)
 * Dengan "target" ini bulan yang terlewat (aset baru diinput belakangan) ikut
 * terkejar otomatis, akumulasi awal aset lama terhitung, dan bulan terakhir
 * menghabiskan sisa pembulatan.
 *
 * Alur: Buat batch (Draft, hanya hitung) -> Hitung ulang -> Posting (1 jurnal:
 * Dr Beban / Cr Akumulasi & Dr Beban / Cr Dibayar Dimuka, per akun; nilai di
 * register diperbarui) -> Batalkan posting (hanya batch terakhir; jurnal kembali
 * Draft & terkunci, nilai register dikembalikan) -> Hapus batch Draft.
 */
class Asset_depreciation_model extends CI_Model {

    const SOURCE = 'ASSET_DEPRECIATION';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('JurnalUmum_model');
    }

    public static function period_key($y, $m) { return (int) $y * 12 + (int) $m; }

    public static function period_label($y, $m)
    {
        $bln = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return ($bln[(int) $m] ?? $m) . ' ' . $y;
    }

    public function get_batches()
    {
        return $this->db->select('h.*, j.journal_no, u.fullname AS created_by_name,
                (SELECT COALESCE(SUM(penyusutan_bulan_ini),0) FROM asset_depreciation_details d WHERE d.depreciation_header_id = h.id) AS total_asset_amount,
                (SELECT COUNT(*) FROM asset_depreciation_details d WHERE d.depreciation_header_id = h.id) AS n_asset,
                (SELECT COALESCE(SUM(amortisasi_bulan_ini),0) FROM prepaid_amortization_details p WHERE p.depreciation_header_id = h.id) AS total_prepaid_amount,
                (SELECT COUNT(*) FROM prepaid_amortization_details p WHERE p.depreciation_header_id = h.id) AS n_prepaid', false)
            ->from('asset_depreciation_header h')
            ->join('journal_header j', 'j.id = h.journal_header_id', 'left')
            ->join('users u', 'u.id = h.created_by', 'left')
            ->order_by('h.periode_year', 'DESC')->order_by('h.periode_month', 'DESC')
            ->get()->result();
    }

    public function get_batch($id)
    {
        return $this->db->select('h.*, j.journal_no, j.status AS journal_status')
            ->from('asset_depreciation_header h')->join('journal_header j', 'j.id = h.journal_header_id', 'left')
            ->where('h.id', (int) $id)->get()->row();
    }

    public function get_asset_details($batch_id)
    {
        return $this->db->select("d.*, ar.no_asset, COALESCE(NULLIF(ar.nama_aset, ''), a.nama_asset) AS nama,
                cb.account_code AS coa_beban_code, ck.account_code AS coa_akum_code", false)
            ->from('asset_depreciation_details d')
            ->join('asset_register ar', 'ar.id = d.asset_register_id')
            ->join('assets a', 'a.id = ar.asset_id', 'left')
            ->join('chart_of_accounts cb', 'cb.id = d.coa_beban_id', 'left')
            ->join('chart_of_accounts ck', 'ck.id = d.coa_akum_id', 'left')
            ->where('d.depreciation_header_id', (int) $batch_id)->order_by('ar.no_asset', 'ASC')->get()->result();
    }

    public function get_prepaid_details($batch_id)
    {
        return $this->db->select('d.*, p.doc_no, p.nama, ce.account_code AS coa_expense_code, cp.account_code AS coa_prepaid_code')
            ->from('prepaid_amortization_details d')
            ->join('prepaid_expenses p', 'p.id = d.prepaid_id')
            ->join('chart_of_accounts ce', 'ce.id = d.coa_expense_id', 'left')
            ->join('chart_of_accounts cp', 'cp.id = d.coa_prepaid_id', 'left')
            ->where('d.depreciation_header_id', (int) $batch_id)->order_by('p.doc_no', 'ASC')->get()->result();
    }

    /** Batch dengan periode terbaru (Draft/Posted), null bila belum ada. */
    public function latest_batch()
    {
        return $this->db->order_by('periode_year', 'DESC')->order_by('periode_month', 'DESC')->limit(1)->get('asset_depreciation_header')->row();
    }

    /** Usulan periode berikutnya: setelah batch terakhir, atau bulan berjalan. */
    public function suggested_period()
    {
        $last = $this->latest_batch();
        if ($last) {
            $k = self::period_key($last->periode_year, $last->periode_month) + 1;
            $y = intdiv($k - 1, 12);
            return [$y, $k - $y * 12];
        }
        return [(int) date('Y'), (int) date('n')];
    }

    /**
     * Hitung penyusutan & amortisasi periode (tanpa menulis).
     * Return ['assets' => [...], 'prepaid' => [...], 'errors' => [...]].
     */
    public function compute($y, $m)
    {
        $y = (int) $y; $m = (int) $m;
        $pk = self::period_key($y, $m);
        $end = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $y, $m)));
        $out = ['assets' => [], 'prepaid' => [], 'errors' => []];

        $assets = $this->db->select("ar.*, COALESCE(NULLIF(ar.nama_aset, ''), a.nama_asset) AS nama,
                COALESCE(ar.coa_akum_id, a.coa_akum_id) AS coa_akum, COALESCE(ar.coa_beban_id, a.coa_beban_id) AS coa_beban", false)
            ->from('asset_register ar')->join('assets a', 'a.id = ar.asset_id', 'left')
            ->where('ar.status', 'Aktif')->where('ar.disusutkan', 1)->where('ar.umur_manfaat_bulan >', 0)
            ->where('ar.mulai_penyusutan <=', $end)->where('ar.tanggal_perolehan <=', $end)
            ->order_by('ar.no_asset', 'ASC')->get()->result();
        foreach ($assets as $a) {
            $dep = round((float) $a->nilai_perolehan - (float) $a->nilai_residu, 2);
            if ($dep <= 0) continue;
            $start = self::period_key(date('Y', strtotime($a->mulai_penyusutan)), date('n', strtotime($a->mulai_penyusutan)));
            $months = $pk - $start + 1;
            if ($months <= 0) continue;
            $target = $months >= (int) $a->umur_manfaat_bulan ? $dep : min($dep, round($dep / (int) $a->umur_manfaat_bulan * $months, 2));
            $amount = round($target - (float) $a->akumulasi_penyusutan, 2);
            if ($amount <= 0.004) continue;
            if (empty($a->coa_akum) || empty($a->coa_beban)) {
                $out['errors'][] = $a->no_asset . ' (' . $a->nama . '): akun Akumulasi / Beban Penyusutan belum diisi (atur di Master Asset atau di data aset).';
                continue;
            }
            $after = round((float) $a->akumulasi_penyusutan + $amount, 2);
            $out['assets'][] = [
                'asset_register_id'    => (int) $a->id,
                'nilai_perolehan'      => (float) $a->nilai_perolehan,
                'akumulasi_sebelum'    => (float) $a->akumulasi_penyusutan,
                'penyusutan_bulan_ini' => $amount,
                'akumulasi_sesudah'    => $after,
                'nilai_buku_sesudah'   => round((float) $a->nilai_perolehan - $after, 2),
                'coa_beban_id'         => (int) $a->coa_beban,
                'coa_akum_id'          => (int) $a->coa_akum,
                'department'           => $a->department,
                '_label'               => $a->no_asset . ' ' . $a->nama,
                '_catchup'             => $amount > round($dep / (int) $a->umur_manfaat_bulan, 2) + 0.01,
            ];
        }

        $prepaids = $this->db->where('status', 'Aktif')->where('mulai_amortisasi <=', $end)->order_by('doc_no', 'ASC')->get('prepaid_expenses')->result();
        foreach ($prepaids as $p) {
            $start = self::period_key(date('Y', strtotime($p->mulai_amortisasi)), date('n', strtotime($p->mulai_amortisasi)));
            $months = $pk - $start + 1;
            if ($months <= 0 || (int) $p->jumlah_bulan <= 0) continue;
            $nilai = (float) $p->nilai;
            $target = $months >= (int) $p->jumlah_bulan ? $nilai : min($nilai, round($nilai / (int) $p->jumlah_bulan * $months, 2));
            $amount = round($target - (float) $p->akumulasi_amortisasi, 2);
            if ($amount <= 0.004) continue;
            $after = round((float) $p->akumulasi_amortisasi + $amount, 2);
            $out['prepaid'][] = [
                'prepaid_id'           => (int) $p->id,
                'nilai'                => $nilai,
                'akumulasi_sebelum'    => (float) $p->akumulasi_amortisasi,
                'amortisasi_bulan_ini' => $amount,
                'akumulasi_sesudah'    => $after,
                'sisa_sesudah'         => round($nilai - $after, 2),
                'coa_expense_id'       => (int) $p->coa_expense_id,
                'coa_prepaid_id'       => (int) $p->coa_prepaid_id,
                'department'           => $p->department,
            ];
        }
        return $out;
    }

    /** Simpan hasil compute ke detail batch (hapus detail lama dulu). Di dalam transaksi pemanggil. */
    private function _write_details($batch_id, $calc)
    {
        $this->db->where('depreciation_header_id', (int) $batch_id)->delete('asset_depreciation_details');
        $this->db->where('depreciation_header_id', (int) $batch_id)->delete('prepaid_amortization_details');
        $total = 0;
        foreach ($calc['assets'] as $r) {
            unset($r['_label'], $r['_catchup']);
            $r['depreciation_header_id'] = (int) $batch_id;
            $this->db->insert('asset_depreciation_details', $r);
            $total += $r['penyusutan_bulan_ini'];
        }
        foreach ($calc['prepaid'] as $r) {
            $r['depreciation_header_id'] = (int) $batch_id;
            $this->db->insert('prepaid_amortization_details', $r);
            $total += $r['amortisasi_bulan_ini'];
        }
        $this->db->where('id', (int) $batch_id)->update('asset_depreciation_header', [
            'total_amount' => round($total, 2),
            'total_asset'  => count($calc['assets']) + count($calc['prepaid']),
        ]);
    }

    private function _check_calc($calc)
    {
        if ($calc['errors']) throw new Exception("Lengkapi dulu akun penyusutan:\n- " . implode("\n- ", $calc['errors']));
        if (empty($calc['assets']) && empty($calc['prepaid'])) throw new Exception('Tidak ada aset tetap maupun biaya dibayar dimuka yang perlu disusutkan/diamortisasi pada periode ini.');
    }

    /** Buat batch Draft utk periode (harus setelah batch terakhir; hanya 1 Draft). */
    public function create_batch($y, $m, $user_id)
    {
        $y = (int) $y; $m = (int) $m;
        if ($y < 2000 || $m < 1 || $m > 12) throw new Exception('Periode tidak valid.');
        $last = $this->latest_batch();
        if ($last && self::period_key($y, $m) <= self::period_key($last->periode_year, $last->periode_month)) {
            throw new Exception('Periode harus setelah batch terakhir (' . self::period_label($last->periode_year, $last->periode_month) . ').');
        }
        if ($this->db->where('status', 'Draft')->count_all_results('asset_depreciation_header') > 0) {
            throw new Exception('Masih ada batch Draft. Posting atau hapus dulu batch tersebut.');
        }
        $calc = $this->compute($y, $m);
        $this->_check_calc($calc);

        $this->db->trans_start();
        $this->db->insert('asset_depreciation_header', [
            'batch_number' => sprintf('DEP-%04d-%02d', $y, $m), 'periode_year' => $y, 'periode_month' => $m,
            'status' => 'Draft', 'created_by' => $user_id,
        ]);
        $id = (int) $this->db->insert_id();
        $this->_write_details($id, $calc);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal membuat batch (transaksi database).');
        return $id;
    }

    public function recalc($id)
    {
        $b = $this->get_batch($id);
        if (!$b || $b->status !== 'Draft') throw new Exception('Hanya batch Draft yang bisa dihitung ulang.');
        $calc = $this->compute($b->periode_year, $b->periode_month);
        $this->_check_calc($calc);
        $this->db->trans_start();
        $this->_write_details($id, $calc);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menghitung ulang (transaksi database).');
        return $calc;
    }

    /** Posting: hitung ulang (data terbaru), buat/perbarui jurnal, perbarui register. */
    public function post($id, $user_id)
    {
        $b = $this->get_batch($id);
        if (!$b || $b->status !== 'Draft') throw new Exception('Hanya batch Draft yang bisa diposting.');
        $calc = $this->compute($b->periode_year, $b->periode_month);
        $this->_check_calc($calc);

        // Jurnal: debit per akun beban, kredit per akun akumulasi / dibayar dimuka.
        $deb = []; $kre = [];
        foreach ($calc['assets'] as $r) {
            $deb[$r['coa_beban_id']] = ($deb[$r['coa_beban_id']] ?? 0) + $r['penyusutan_bulan_ini'];
            $kre[$r['coa_akum_id']]  = ($kre[$r['coa_akum_id']] ?? 0) + $r['penyusutan_bulan_ini'];
        }
        $deb_p = []; $kre_p = [];
        foreach ($calc['prepaid'] as $r) {
            $deb_p[$r['coa_expense_id']] = ($deb_p[$r['coa_expense_id']] ?? 0) + $r['amortisasi_bulan_ini'];
            $kre_p[$r['coa_prepaid_id']] = ($kre_p[$r['coa_prepaid_id']] ?? 0) + $r['amortisasi_bulan_ini'];
        }
        $label = self::period_label($b->periode_year, $b->periode_month);
        $lines = []; $total = 0;
        foreach ($deb as $acc => $v)   { $lines[] = ['account_id' => $acc, 'debit' => round($v, 2), 'kredit' => 0, 'description' => 'Beban penyusutan ' . $label]; $total += round($v, 2); }
        foreach ($deb_p as $acc => $v) { $lines[] = ['account_id' => $acc, 'debit' => round($v, 2), 'kredit' => 0, 'description' => 'Amortisasi biaya dibayar dimuka ' . $label]; $total += round($v, 2); }
        foreach ($kre as $acc => $v)   $lines[] = ['account_id' => $acc, 'debit' => 0, 'kredit' => round($v, 2), 'description' => 'Akumulasi penyusutan ' . $label];
        foreach ($kre_p as $acc => $v) $lines[] = ['account_id' => $acc, 'debit' => 0, 'kredit' => round($v, 2), 'description' => 'Amortisasi biaya dibayar dimuka ' . $label];

        $period_end = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $b->periode_year, $b->periode_month)));
        $jh = [
            'journal_date' => $period_end,
            'description'  => 'Penyusutan aset tetap & amortisasi biaya dibayar dimuka ' . $label . ' (' . $b->batch_number . ')',
            'source_module' => self::SOURCE, 'source_doc_id' => (int) $b->id,
            'currency' => 'IDR', 'exchange_rate' => 1,
            'total_debit' => round($total, 2), 'total_kredit' => round($total, 2),
            'total_debit_local' => round($total, 2), 'total_kredit_local' => round($total, 2),
            'created_by' => $user_id, 'status' => 'Draft',
        ];

        $this->db->trans_start();
        $this->_write_details($id, $calc);
        $draft_j = !empty($b->journal_header_id) ? $this->db->select('id, status')->where('id', (int) $b->journal_header_id)->get('journal_header')->row() : null;
        if ($draft_j && $draft_j->status === 'Draft') {
            $jid = (int) $draft_j->id;
            if (!$this->JurnalUmum_model->replace_draft_journal($jid, $jh, $lines)) throw new Exception('Gagal memperbarui jurnal batch.');
        } else {
            $jh['journal_no'] = $this->JurnalUmum_model->get_new_journal_number();
            $jid = (int) $this->JurnalUmum_model->insert_journal($jh, $lines);
        }
        $this->JurnalUmum_model->post_journal($jid, $user_id);

        foreach ($calc['assets'] as $r) {
            $res = (float) $this->db->select('nilai_residu')->where('id', $r['asset_register_id'])->get('asset_register')->row('nilai_residu');
            $this->db->where('id', $r['asset_register_id'])->update('asset_register', [
                'akumulasi_penyusutan' => $r['akumulasi_sesudah'],
                'nilai_buku'           => $r['nilai_buku_sesudah'],
                'status'               => $r['nilai_buku_sesudah'] <= round($res, 2) + 0.005 ? 'Habis Disusutkan' : 'Aktif',
            ]);
        }
        foreach ($calc['prepaid'] as $r) {
            $this->db->where('id', $r['prepaid_id'])->update('prepaid_expenses', [
                'akumulasi_amortisasi' => $r['akumulasi_sesudah'],
                'sisa'                 => $r['sisa_sesudah'],
                'status'               => $r['sisa_sesudah'] <= 0.005 ? 'Selesai' : 'Aktif',
            ]);
        }
        $this->db->where('id', (int) $id)->update('asset_depreciation_header', [
            'status' => 'Posted', 'journal_header_id' => $jid, 'posted_by' => $user_id, 'posted_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal posting (transaksi database).');
        return $this->db->select('journal_no')->where('id', $jid)->get('journal_header')->row('journal_no');
    }

    /** Batalkan posting batch TERAKHIR: nilai register dikembalikan, jurnal kembali Draft (terkunci). */
    public function unpost($id)
    {
        $b = $this->get_batch($id);
        if (!$b || $b->status !== 'Posted') throw new Exception('Hanya batch Posted yang bisa dibatalkan.');
        $last = $this->latest_batch();
        if ((int) $last->id !== (int) $b->id) throw new Exception('Hanya batch periode terakhir (' . self::period_label($last->periode_year, $last->periode_month) . ') yang bisa dibatalkan.');

        $assets = $this->db->select('d.*, ar.status AS ar_status, ar.no_asset, ar.nilai_residu')
            ->from('asset_depreciation_details d')->join('asset_register ar', 'ar.id = d.asset_register_id')
            ->where('d.depreciation_header_id', (int) $id)->get()->result();
        foreach ($assets as $d) {
            if ($d->ar_status === 'Disposed') throw new Exception('Aset ' . $d->no_asset . ' sudah dilepas setelah penyusutan ini. Batalkan pelepasannya dulu.');
        }
        $prepaids = $this->db->where('depreciation_header_id', (int) $id)->get('prepaid_amortization_details')->result();

        $this->db->trans_start();
        foreach ($assets as $d) {
            $buku = round((float) $d->nilai_perolehan - (float) $d->akumulasi_sebelum, 2);
            $this->db->where('id', (int) $d->asset_register_id)->update('asset_register', [
                'akumulasi_penyusutan' => (float) $d->akumulasi_sebelum,
                'nilai_buku'           => $buku,
                'status'               => $buku <= round((float) $d->nilai_residu, 2) + 0.005 ? 'Habis Disusutkan' : 'Aktif',
            ]);
        }
        foreach ($prepaids as $d) {
            $sisa = round((float) $d->nilai - (float) $d->akumulasi_sebelum, 2);
            $this->db->where('id', (int) $d->prepaid_id)->update('prepaid_expenses', [
                'akumulasi_amortisasi' => (float) $d->akumulasi_sebelum, 'sisa' => $sisa, 'status' => $sisa <= 0.005 ? 'Selesai' : 'Aktif',
            ]);
        }
        if (!empty($b->journal_header_id)) {
            $this->db->where('id', (int) $b->journal_header_id)->where('status', 'Posted')
                     ->update('journal_header', ['status' => 'Draft', 'posted_by' => null, 'posted_at' => null]);
        }
        $this->db->where('id', (int) $id)->update('asset_depreciation_header', ['status' => 'Draft', 'posted_by' => null, 'posted_at' => null]);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal membatalkan posting (transaksi database).');
        return true;
    }

    /** Hapus batch Draft (jurnal Draft-nya ikut dihapus). */
    public function delete_batch($id)
    {
        $b = $this->get_batch($id);
        if (!$b || $b->status !== 'Draft') throw new Exception('Hanya batch Draft yang bisa dihapus.');
        $this->db->trans_start();
        if (!empty($b->journal_header_id)) {
            $this->db->where('journal_header_id', (int) $b->journal_header_id)->delete('journal_details');
            $this->db->where('id', (int) $b->journal_header_id)->where('status', 'Draft')->where('source_module', self::SOURCE)->delete('journal_header');
        }
        $this->db->where('depreciation_header_id', (int) $id)->delete('asset_depreciation_details');
        $this->db->where('depreciation_header_id', (int) $id)->delete('prepaid_amortization_details');
        $this->db->where('id', (int) $id)->delete('asset_depreciation_header');
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menghapus batch (transaksi database).');
        return $b->batch_number;
    }
}
