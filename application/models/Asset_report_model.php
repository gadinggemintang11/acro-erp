<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Asset_report_model — Laporan Mutasi Aset Tetap & Biaya Dibayar Dimuka per
 * rentang periode (bulan), dihitung ulang dari sumber datanya:
 *   - asset_register        : nilai sekarang (atau saat dilepas)
 *   - batch penyusutan      : hanya batch Posted (per periode)
 *   - asset_mutations       : revaluasi & pelepasan sebagian (delta nilai/akumulasi)
 * Nilai awal aset = nilai sekarang dikurangi semua delta mutasi; akumulasi awal
 * = akumulasi sekarang - penyusutan Posted - delta mutasi. Dari situ nilai per
 * akhir periode mana pun bisa dihitung, sehingga
 *   saldo akhir = saldo awal + penambahan + revaluasi - pengurangan
 * (akumulasi: + penyusutan) selalu cocok.
 */
class Asset_report_model extends CI_Model {

    public static function pk($date) { return (int) date('Y', strtotime($date)) * 12 + (int) date('n', strtotime($date)); }
    public static function pk_first_day($pk) { $y = intdiv($pk - 1, 12); return sprintf('%04d-%02d-01', $y, $pk - $y * 12); }
    public static function pk_last_day($pk) { return date('Y-m-t', strtotime(self::pk_first_day($pk))); }
    public static function pk_label($pk)
    {
        $bln = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $y = intdiv($pk - 1, 12);
        return $bln[$pk - $y * 12] . ' ' . $y;
    }

    private function _mut_ready()
    {
        return $this->db->table_exists('asset_mutations');
    }

    /** [asset_id => [pk => total penyusutan Posted]] */
    private function _posted_dep(array $ids)
    {
        $out = [];
        if (!$ids) return $out;
        foreach (array_chunk($ids, 500) as $chunk) {
            $rows = $this->db->select('d.asset_register_id AS id, (h.periode_year * 12 + h.periode_month) AS pk, SUM(d.penyusutan_bulan_ini) AS amt', false)
                ->from('asset_depreciation_details d')
                ->join('asset_depreciation_header h', "h.id = d.depreciation_header_id AND h.status = 'Posted'", 'inner', false)
                ->where_in('d.asset_register_id', $chunk)
                ->group_by(['d.asset_register_id', 'pk'])->get()->result();
            foreach ($rows as $r) $out[(int) $r->id][(int) $r->pk] = (float) $r->amt;
        }
        return $out;
    }

    /** [asset_id => [mutasi...]] */
    private function _mutations(array $ids)
    {
        $out = [];
        if (!$ids || !$this->_mut_ready()) return $out;
        foreach (array_chunk($ids, 500) as $chunk) {
            $rows = $this->db->select('m.*, j.journal_no')->from('asset_mutations m')
                ->join('journal_header j', 'j.id = m.journal_header_id', 'left')
                ->where_in('m.asset_register_id', $chunk)->order_by('m.id', 'ASC')->get()->result();
            foreach ($rows as $r) $out[(int) $r->asset_register_id][] = $r;
        }
        return $out;
    }

    /**
     * Mutasi aset tetap per aset untuk periode p1..p2 (tahun*12+bulan).
     * $filters: kategori, department.
     * Return ['rows' => [...per aset...], 'categories' => [kode => ringkasan], 'total' => ringkasan,
     *         'months' => [pk...], 'events' => [...pelepasan & revaluasi...], 'mismatch' => [no_asset...]].
     */
    public function rollforward($p1, $p2, $filters = [])
    {
        $p1 = (int) $p1; $p2 = (int) $p2;
        $d1 = self::pk_first_day($p1); $d2 = self::pk_last_day($p2);
        $mut_ready = $this->_mut_ready();

        $this->db->select("ar.id, ar.no_asset, ar.sumber, ar.po_number, ar.tanggal_perolehan, ar.nilai_perolehan, ar.akumulasi_penyusutan,
                ar.nilai_residu, ar.umur_manfaat_bulan, ar.disusutkan, ar.status, ar.disposed_at, ar.disposal_type, ar.disposal_value,
                ar.department, ar.lokasi, ar.pic, ar.mulai_penyusutan, ar.disposal_journal_id, jd.journal_no AS disposal_journal_no,
                COALESCE(NULLIF(ar.nama_aset, ''), a.nama_asset) AS nama,
                COALESCE(NULLIF(ar.kategori, ''), a.kategori) AS kategori,
                cc.description AS kategori_label, ca.account_code AS coa_asset_code, ca.account_name AS coa_asset_name", false)
            ->from('asset_register ar')
            ->join('assets a', 'a.id = ar.asset_id', 'left')
            ->join('common_code cc', "cc.group_code = 'ASSET_CATEGORY' AND cc.code = COALESCE(NULLIF(ar.kategori, ''), a.kategori)", 'left', false)
            ->join('chart_of_accounts ca', 'ca.id = COALESCE(ar.coa_asset_id, a.coa_asset_id)', 'left', false)
            ->join('journal_header jd', 'jd.id = ar.disposal_journal_id', 'left')
            ->where('ar.tanggal_perolehan <=', $d2)
            ->group_start()->where('ar.status !=', 'Disposed')->or_where('ar.disposed_at IS NULL', null, false)->or_where('ar.disposed_at >=', $d1)->group_end();
        if (!empty($filters['kategori'])) $this->db->where("COALESCE(NULLIF(ar.kategori, ''), a.kategori) = " . $this->db->escape($filters['kategori']), null, false);
        if (!empty($filters['department'])) $this->db->where('ar.department', $filters['department']);
        $assets = $this->db->order_by('kategori_label', 'ASC')->order_by('ar.no_asset', 'ASC')->get()->result();

        $ids = array_map(function ($a) { return (int) $a->id; }, $assets);
        $deps = $this->_posted_dep($ids);
        $muts = $this->_mutations($ids);
        $months = range($p1, $p2);

        $keys = ['c0', 'c_add', 'c_reval', 'c_out', 'c1', 'a0', 'a_add', 'a_dep', 'a_reval', 'a_out', 'a1', 'b0', 'b1'];
        $zero = array_fill_keys($keys, 0.0);
        $rows = []; $cats = []; $total = $zero; $events = []; $mismatch = [];

        foreach ($assets as $a) {
            $id = (int) $a->id;
            $dep = $deps[$id] ?? [];
            $ml  = $muts[$id] ?? [];
            $acq = self::pk($a->tanggal_perolehan);
            $disp = ($a->status === 'Disposed' && $a->disposed_at) ? self::pk($a->disposed_at) : null;
            $sum_mn = 0.0; $sum_ma = 0.0;
            foreach ($ml as $m) { $sum_mn += (float) $m->delta_nilai; $sum_ma += (float) $m->delta_akumulasi; }
            $base_c = round((float) $a->nilai_perolehan - $sum_mn, 2);
            $base_a = round((float) $a->akumulasi_penyusutan - array_sum($dep) - $sum_ma, 2);

            $at = function ($P) use ($acq, $disp, $base_c, $base_a, $dep, $ml) {
                if ($acq > $P || ($disp !== null && $disp <= $P)) return [0.0, 0.0];
                $c = $base_c; $ak = $base_a;
                foreach ($ml as $m) if ((int) $m->periode <= $P) { $c += (float) $m->delta_nilai; $ak += (float) $m->delta_akumulasi; }
                foreach ($dep as $pk => $v) if ($pk <= $P) $ak += $v;
                return [round($c, 2), round($ak, 2)];
            };
            list($c0, $a0) = $at($p1 - 1);
            list($c1, $a1) = $at($p2);
            $in = function ($pk) use ($p1, $p2) { return $pk >= $p1 && $pk <= $p2; };

            $v = $zero;
            $v['c0'] = $c0; $v['a0'] = $a0; $v['c1'] = $c1; $v['a1'] = $a1;
            if ($in($acq)) { $v['c_add'] = $base_c; $v['a_add'] = $base_a; }
            $monthly = [];
            foreach ($dep as $pk => $amt) if ($in($pk)) { $v['a_dep'] += $amt; $monthly[$pk] = $amt; }
            foreach ($ml as $m) {
                if (!$in((int) $m->periode)) continue;
                if ($m->jenis === 'REVALUASI') { $v['c_reval'] += (float) $m->delta_nilai; $v['a_reval'] += (float) $m->delta_akumulasi; }
                else { $v['c_out'] -= (float) $m->delta_nilai; $v['a_out'] -= (float) $m->delta_akumulasi; }
                $events[] = $this->_mut_event($a, $m);
            }
            if ($disp !== null && $in($disp)) {
                $v['c_out'] += (float) $a->nilai_perolehan; $v['a_out'] += (float) $a->akumulasi_penyusutan;
                $buku = round((float) $a->nilai_perolehan - (float) $a->akumulasi_penyusutan, 2);
                $events[] = [
                    'tanggal' => $a->disposed_at, 'no_asset' => $a->no_asset, 'nama' => $a->nama, 'asset_id' => $id,
                    'jenis' => $a->disposal_type === 'JUAL' ? 'Dijual' : 'Dihapus', 'porsi' => 'Seluruh',
                    'nilai' => (float) $a->nilai_perolehan, 'akumulasi' => (float) $a->akumulasi_penyusutan, 'buku' => $buku,
                    'harga' => $a->disposal_type === 'JUAL' ? (float) $a->disposal_value : null,
                    'laba_rugi' => round((float) $a->disposal_value - $buku, 2), 'surplus' => null,
                    'journal_no' => $a->disposal_journal_no,
                ];
            }
            $v['b0'] = round($v['c0'] - $v['a0'], 2); $v['b1'] = round($v['c1'] - $v['a1'], 2);
            foreach ($keys as $k) $v[$k] = round($v[$k], 2);

            $nonzero = false;
            foreach (['c0', 'c1', 'c_add', 'c_out', 'c_reval', 'a_dep', 'a0', 'a1'] as $k) if (abs($v[$k]) >= 0.005) { $nonzero = true; break; }
            if (!$nonzero) continue;
            if (abs($v['c0'] + $v['c_add'] + $v['c_reval'] - $v['c_out'] - $v['c1']) > 0.02
                || abs($v['a0'] + $v['a_add'] + $v['a_dep'] + $v['a_reval'] - $v['a_out'] - $v['a1']) > 0.02) {
                $mismatch[] = $a->no_asset;
            }

            $status = ($disp !== null && $disp <= $p2) ? 'Dilepas' : ($a->status === 'Disposed' ? 'Aktif' : ($a->status === 'Habis Disusutkan' ? 'Habis Disusutkan' : 'Aktif'));
            $rows[] = (object) ([
                'id' => $id, 'no_asset' => $a->no_asset, 'nama' => $a->nama, 'kategori' => $a->kategori ?: '-',
                'kategori_label' => $a->kategori_label ?: ($a->kategori ?: 'Tanpa Kategori'),
                'department' => $a->department, 'lokasi' => $a->lokasi, 'sumber' => $a->sumber, 'po_number' => $a->po_number,
                'tanggal_perolehan' => $a->tanggal_perolehan, 'umur' => (int) $a->umur_manfaat_bulan, 'disusutkan' => (int) $a->disusutkan,
                'coa_asset' => trim(($a->coa_asset_code ?? '') . ' ' . ($a->coa_asset_name ?? '')),
                'status' => $status, 'monthly' => $monthly,
            ] + $v);

            $ck = $a->kategori_label ?: ($a->kategori ?: 'Tanpa Kategori');
            if (!isset($cats[$ck])) $cats[$ck] = $zero + ['n' => 0];
            $cats[$ck]['n']++;
            foreach ($keys as $k) { $cats[$ck][$k] += $v[$k]; $total[$k] += $v[$k]; }
        }
        foreach ($cats as &$c) foreach ($keys as $k) $c[$k] = round($c[$k], 2);
        unset($c);
        foreach ($keys as $k) $total[$k] = round($total[$k], 2);
        $total['n'] = count($rows);
        usort($events, function ($x, $y) { return strcmp($x['tanggal'] . $x['no_asset'], $y['tanggal'] . $y['no_asset']); });

        return ['rows' => $rows, 'categories' => $cats, 'total' => $total, 'months' => $months, 'events' => $events, 'mismatch' => $mismatch];
    }

    private function _mut_event($a, $m)
    {
        if ($m->jenis === 'REVALUASI') {
            return [
                'tanggal' => $m->tanggal, 'no_asset' => $a->no_asset, 'nama' => $a->nama, 'asset_id' => (int) $a->id,
                'jenis' => 'Revaluasi', 'porsi' => null,
                'nilai' => (float) $m->nilai_sebelum, 'akumulasi' => (float) $m->akumulasi_sebelum,
                'buku' => round((float) $m->nilai_sebelum - (float) $m->akumulasi_sebelum, 2),
                'harga' => (float) $m->nilai_wajar, 'laba_rugi' => (float) $m->ke_laba_rugi, 'surplus' => (float) $m->ke_surplus,
                'journal_no' => $m->journal_no,
            ];
        }
        $nilai = -(float) $m->delta_nilai; $ak = -(float) $m->delta_akumulasi;
        return [
            'tanggal' => $m->tanggal, 'no_asset' => $a->no_asset, 'nama' => $a->nama, 'asset_id' => (int) $a->id,
            'jenis' => ($m->disposal_type === 'JUAL' ? 'Dijual' : 'Dihapus') . ' sebagian', 'porsi' => $m->porsi_keterangan,
            'nilai' => $nilai, 'akumulasi' => $ak, 'buku' => round($nilai - $ak, 2),
            'harga' => $m->disposal_type === 'JUAL' ? (float) $m->harga_jual : null,
            'laba_rugi' => (float) $m->laba_rugi, 'surplus' => (float) $m->surplus_ke_saldo_laba ? -(float) $m->surplus_ke_saldo_laba : null,
            'journal_no' => $m->journal_no,
        ];
    }

    /**
     * Mutasi Biaya Dibayar Dimuka p1..p2: saldo awal, penambahan (dibayar dalam
     * periode, setelah amortisasi awal), amortisasi Posted, saldo akhir.
     */
    public function prepaid_rollforward($p1, $p2, $filters = [])
    {
        $p1 = (int) $p1; $p2 = (int) $p2;
        $this->db->select('p.*, ce.account_code AS coa_expense_code, cp.account_code AS coa_prepaid_code')
            ->from('prepaid_expenses p')
            ->join('chart_of_accounts ce', 'ce.id = p.coa_expense_id', 'left')
            ->join('chart_of_accounts cp', 'cp.id = p.coa_prepaid_id', 'left')
            ->where('p.tanggal_bayar <=', self::pk_last_day($p2));
        if (!empty($filters['department'])) $this->db->where('p.department', $filters['department']);
        $list = $this->db->order_by('p.doc_no', 'ASC')->get()->result();

        $amort = [];
        if ($list) {
            $rows = $this->db->select('d.prepaid_id AS id, (h.periode_year * 12 + h.periode_month) AS pk, SUM(d.amortisasi_bulan_ini) AS amt', false)
                ->from('prepaid_amortization_details d')
                ->join('asset_depreciation_header h', "h.id = d.depreciation_header_id AND h.status = 'Posted'", 'inner', false)
                ->where_in('d.prepaid_id', array_map(function ($p) { return (int) $p->id; }, $list))
                ->group_by(['d.prepaid_id', 'pk'])->get()->result();
            foreach ($rows as $r) $amort[(int) $r->id][(int) $r->pk] = (float) $r->amt;
        }

        $out = []; $total = ['s0' => 0.0, 'add' => 0.0, 'amort' => 0.0, 's1' => 0.0];
        foreach ($list as $p) {
            $am = $amort[(int) $p->id] ?? [];
            $base_a = round((float) $p->akumulasi_amortisasi - array_sum($am), 2);
            $pay = self::pk($p->tanggal_bayar);
            $at = function ($P) use ($pay, $p, $base_a, $am) {
                if ($pay > $P) return 0.0;
                $s = (float) $p->nilai - $base_a;
                foreach ($am as $pk => $v) if ($pk <= $P) $s -= $v;
                return round($s, 2);
            };
            $v = ['s0' => $at($p1 - 1), 'add' => ($pay >= $p1 && $pay <= $p2) ? round((float) $p->nilai - $base_a, 2) : 0.0, 'amort' => 0.0, 's1' => $at($p2)];
            $monthly = [];
            foreach ($am as $pk => $amt) if ($pk >= $p1 && $pk <= $p2) { $v['amort'] += $amt; $monthly[$pk] = $amt; }
            $v['amort'] = round($v['amort'], 2);
            if (abs($v['s0']) < 0.005 && abs($v['s1']) < 0.005 && abs($v['add']) < 0.005 && abs($v['amort']) < 0.005) continue;
            foreach ($total as $k => $_) $total[$k] += $v[$k];
            $out[] = (object) ([
                'id' => (int) $p->id, 'doc_no' => $p->doc_no, 'nama' => $p->nama, 'department' => $p->department,
                'tanggal_bayar' => $p->tanggal_bayar, 'nilai' => (float) $p->nilai, 'jumlah_bulan' => (int) $p->jumlah_bulan,
                'mulai_amortisasi' => $p->mulai_amortisasi, 'coa' => trim($p->coa_prepaid_code . ' / ' . $p->coa_expense_code, ' /'),
                'status' => $p->status, 'monthly' => $monthly,
            ] + $v);
        }
        foreach ($total as $k => $_) $total[$k] = round($total[$k], 2);
        return ['rows' => $out, 'total' => $total];
    }
}
