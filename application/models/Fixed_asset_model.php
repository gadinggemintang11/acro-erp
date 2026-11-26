<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fixed_asset_model — Aset Tetap (Asset Register) dari sisi Accounting.
 *
 * Satu baris asset_register = satu unit fisik. Sumber:
 *   - PO     : dari "Terima Aset" di PO Asset (Asset_register_model::receive_from_po);
 *              jurnal perolehannya lewat Faktur Supplier (Dr Aset / Cr Hutang).
 *   - MANUAL : input di sini (tanah, bangunan, aset lama / saldo awal, beli tunai
 *              lewat Jurnal Umum / Kas Bank). Jurnal perolehan: cukup referensi
 *              (sudah dijurnal manual) ATAU dibuat otomatis (Dr Aset / Cr akun lawan;
 *              aset lama: Cr Akumulasi sebesar akumulasi awal).
 *
 * Nama, kategori & akun efektif = nilai di register bila diisi, selain itu ikut
 * master asset (aset dari PO biasanya ikut master).
 *
 * Penyusutan: garis lurus, mulai bulan berikutnya setelah perolehan (lihat
 * Asset_depreciation_model). Pelepasan (jual/hapus) membuat jurnal:
 *   Dr Akumulasi, Dr Kas/Bank (harga jual), Dr Rugi / Cr Aset, Cr Laba.
 */
class Fixed_asset_model extends CI_Model {

    private $table = 'asset_register';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('JurnalUmum_model');
    }

    /** SELECT dengan nilai efektif (register override, fallback master asset). */
    private function _base_select()
    {
        $this->db->select("ar.*,
            a.kode_asset, a.nama_asset AS master_nama,
            COALESCE(NULLIF(ar.nama_aset, ''), a.nama_asset) AS nama,
            COALESCE(NULLIF(ar.kategori, ''), a.kategori) AS kategori_efektif,
            COALESCE(ar.coa_asset_id, a.coa_asset_id) AS coa_asset_efektif,
            COALESCE(ar.coa_akum_id,  a.coa_akum_id)  AS coa_akum_efektif,
            COALESCE(ar.coa_beban_id, a.coa_beban_id) AS coa_beban_efektif,
            cc.description AS kategori_label,
            ca.account_code AS coa_asset_code, ca.account_name AS coa_asset_name,
            ck.account_code AS coa_akum_code,  ck.account_name AS coa_akum_name,
            cb.account_code AS coa_beban_code, cb.account_name AS coa_beban_name,
            ja.journal_no AS acq_journal_no, jd.journal_no AS disposal_journal_no", false);
        $this->db->from($this->table . ' ar');
        $this->db->join('assets a', 'a.id = ar.asset_id', 'left');
        $this->db->join('common_code cc', "cc.group_code = 'ASSET_CATEGORY' AND cc.code = COALESCE(NULLIF(ar.kategori, ''), a.kategori)", 'left', false);
        $this->db->join('chart_of_accounts ca', 'ca.id = COALESCE(ar.coa_asset_id, a.coa_asset_id)', 'left', false);
        $this->db->join('chart_of_accounts ck', 'ck.id = COALESCE(ar.coa_akum_id, a.coa_akum_id)', 'left', false);
        $this->db->join('chart_of_accounts cb', 'cb.id = COALESCE(ar.coa_beban_id, a.coa_beban_id)', 'left', false);
        $this->db->join('journal_header ja', 'ja.id = ar.journal_header_id', 'left');
        $this->db->join('journal_header jd', 'jd.id = ar.disposal_journal_id', 'left');
    }

    public function get_list($filters = [])
    {
        $this->_base_select();
        if (!empty($filters['kategori'])) {
            $this->db->where("COALESCE(NULLIF(ar.kategori, ''), a.kategori) = " . $this->db->escape($filters['kategori']), null, false);
        }
        if (!empty($filters['status'])) $this->db->where('ar.status', $filters['status']);
        if (!empty($filters['sumber'])) $this->db->where('ar.sumber', $filters['sumber']);
        if (!empty($filters['q'])) {
            $q = $filters['q'];
            $this->db->group_start()
                ->like('ar.no_asset', $q)->or_like('ar.nama_aset', $q)->or_like('a.nama_asset', $q)
                ->or_like('ar.lokasi', $q)->or_like('ar.pic', $q)->or_like('ar.po_number', $q)
                ->group_end();
        }
        $this->db->order_by('ar.tanggal_perolehan', 'DESC')->order_by('ar.id', 'DESC');
        return $this->db->get()->result();
    }

    public function get($id)
    {
        $this->_base_select();
        $this->db->where('ar.id', (int) $id);
        return $this->db->get()->row();
    }

    /** Riwayat penyusutan per bulan (batch Draft & Posted). */
    public function get_history($id)
    {
        return $this->db->select('d.*, h.batch_number, h.periode_year, h.periode_month, h.status AS batch_status, h.id AS batch_id, j.journal_no')
            ->from('asset_depreciation_details d')
            ->join('asset_depreciation_header h', 'h.id = d.depreciation_header_id')
            ->join('journal_header j', 'j.id = h.journal_header_id', 'left')
            ->where('d.asset_register_id', (int) $id)
            ->order_by('h.periode_year', 'ASC')->order_by('h.periode_month', 'ASC')
            ->get()->result();
    }

    /** Sudah pernah disusutkan lewat batch yang Posted? */
    public function has_posted_depreciation($id)
    {
        return $this->db->from('asset_depreciation_details d')
            ->join('asset_depreciation_header h', 'h.id = d.depreciation_header_id')
            ->where('d.asset_register_id', (int) $id)->where('h.status', 'Posted')
            ->count_all_results() > 0;
    }

    /** Periode (Y*12+M) penyusutan terakhir yang Posted utk aset ini; 0 bila belum ada. */
    public function last_posted_period($id)
    {
        $r = $this->db->select('MAX(h.periode_year * 12 + h.periode_month) AS p', false)
            ->from('asset_depreciation_details d')
            ->join('asset_depreciation_header h', 'h.id = d.depreciation_header_id')
            ->where('d.asset_register_id', (int) $id)->where('h.status', 'Posted')
            ->get()->row();
        return (int) ($r->p ?? 0);
    }

    /** Penyusutan per bulan (garis lurus) & sisa bulan. */
    public function schedule_info($a)
    {
        $umur = (int) $a->umur_manfaat_bulan;
        if (empty($a->disusutkan) || $umur <= 0) return ['monthly' => 0, 'sisa_bulan' => 0];
        $dep = (float) $a->nilai_perolehan - (float) $a->nilai_residu;
        $monthly = round($dep / $umur, 2);
        $sisa_nilai = max(0, (float) $a->nilai_buku - (float) $a->nilai_residu);
        return ['monthly' => $monthly, 'sisa_bulan' => $monthly > 0 ? (int) ceil(round($sisa_nilai / $monthly, 4)) : 0];
    }

    public function get_masters()
    {
        return $this->db->select('id, kode_asset, nama_asset, kategori, umur_manfaat_bulan, nilai_residu_persen, coa_asset_id, coa_akum_id, coa_beban_id')
            ->where('status', 'active')->order_by('nama_asset', 'ASC')->get('assets')->result();
    }

    public function get_categories()
    {
        return $this->db->select('code, description')->where('group_code', 'ASSET_CATEGORY')->where('status', 'active')
            ->order_by('description', 'ASC')->get('common_code')->result();
    }

    public function get_departments()
    {
        return $this->db->select('code, description')->where('group_code', 'Department')->where('status', 'active')
            ->order_by('description', 'ASC')->get('common_code')->result();
    }

    public function get_accounts()
    {
        return $this->db->select('id, account_code, account_name, account_type')->where('is_header', 0)->where('status', 'active')
            ->order_by('account_code', 'ASC')->get('chart_of_accounts')->result();
    }

    private function _account_ok($id)
    {
        return (int) $id > 0 && $this->db->where('id', (int) $id)->where('is_header', 0)->where('status', 'active')->count_all_results('chart_of_accounts') > 0;
    }

    /** Nomor aset berikutnya (format sama dengan Terima Aset PO). */
    private function _next_no_asset($year)
    {
        $this->load->model('Asset_register_model');
        return $this->Asset_register_model->generate_no_asset($year);
    }

    /** Validasi & normalisasi isian keuangan aset (dipakai create & update). */
    private function _financial_fields($post, $is_create)
    {
        $master = !empty($post['asset_id']) ? $this->db->where('id', (int) $post['asset_id'])->get('assets')->row() : null;
        if (!empty($post['asset_id']) && !$master) throw new Exception('Master asset tidak ditemukan.');

        $nama = trim((string) ($post['nama_aset'] ?? ''));
        if (!$master && $nama === '') throw new Exception('Nama aset wajib diisi (atau pilih Master Asset).');

        $tgl = (string) ($post['tanggal_perolehan'] ?? '');
        if (!strtotime($tgl)) throw new Exception('Tanggal perolehan wajib diisi.');
        $nilai = round((float) ($post['nilai_perolehan'] ?? 0), 2);
        if ($nilai <= 0) throw new Exception('Nilai perolehan harus lebih dari 0.');

        $disusutkan = !empty($post['disusutkan']) ? 1 : 0;
        $umur   = (int) ($post['umur_manfaat_bulan'] ?? 0);
        $residu = round((float) ($post['nilai_residu'] ?? 0), 2);
        $akum   = round((float) ($post['akumulasi_awal'] ?? 0), 2);
        if ($disusutkan) {
            if ($umur <= 0) throw new Exception('Umur manfaat (bulan) wajib diisi untuk aset yang disusutkan.');
            if ($residu < 0 || $residu >= $nilai) throw new Exception('Nilai residu harus 0 sampai di bawah nilai perolehan.');
            if ($akum < 0 || $akum > $nilai - $residu) throw new Exception('Akumulasi awal tidak boleh melebihi nilai yang bisa disusutkan (' . number_format($nilai - $residu, 2) . ').');
        } else {
            $umur = 0; $residu = 0; $akum = 0;
        }

        // Mulai penyusutan: default bulan berikutnya setelah perolehan (input bulan YYYY-MM boleh dioverride).
        $mulai = !empty($post['mulai_penyusutan']) && preg_match('/^\d{4}-\d{2}/', $post['mulai_penyusutan'])
            ? substr($post['mulai_penyusutan'], 0, 7) . '-01'
            : date('Y-m-01', strtotime(date('Y-m-01', strtotime($tgl)) . ' +1 month'));

        $coa_asset = (int) ($post['coa_asset_id'] ?? 0) ?: (int) ($master->coa_asset_id ?? 0);
        $coa_akum  = (int) ($post['coa_akum_id'] ?? 0)  ?: (int) ($master->coa_akum_id ?? 0);
        $coa_beban = (int) ($post['coa_beban_id'] ?? 0) ?: (int) ($master->coa_beban_id ?? 0);
        if (!$this->_account_ok($coa_asset)) throw new Exception('Akun Aset wajib dipilih (akun detail yang aktif).');
        if ($disusutkan && (!$this->_account_ok($coa_akum) || !$this->_account_ok($coa_beban))) {
            throw new Exception('Akun Akumulasi Penyusutan & Beban Penyusutan wajib dipilih untuk aset yang disusutkan.');
        }

        return [
            'asset_id'           => $master ? (int) $master->id : null,
            'nama_aset'          => $nama !== '' ? mb_substr($nama, 0, 150) : null,
            'kategori'           => !empty($post['kategori']) ? $post['kategori'] : ($master->kategori ?? 'LAINNYA'),
            'tanggal_perolehan'  => date('Y-m-d', strtotime($tgl)),
            'nilai_perolehan'    => $nilai,
            'disusutkan'         => $disusutkan,
            'umur_manfaat_bulan' => $umur,
            'metode_penyusutan'  => 'GARIS_LURUS',
            'nilai_residu'       => $residu,
            'akumulasi_awal'     => $akum,
            'mulai_penyusutan'   => $mulai,
            'coa_asset_id'       => $coa_asset,
            'coa_akum_id'        => $coa_akum ?: null,
            'coa_beban_id'       => $coa_beban ?: null,
        ];
    }

    private function _status_for($nilai, $akum, $residu, $disusutkan)
    {
        return ($disusutkan && round($nilai - $akum, 2) <= round($residu, 2) + 0.005) ? 'Habis Disusutkan' : 'Aktif';
    }

    /**
     * Input aset manual (1..100 unit identik). Return array id register.
     * $post['jurnal_mode'] = 'REF' (default, isi ref_journal_no) | 'AUTO' (buat jurnal; wajib coa_lawan_id).
     */
    public function create_manual($post, $user_id)
    {
        $f = $this->_financial_fields($post, true);
        $qty = max(1, min(100, (int) ($post['qty'] ?? 1)));
        $mode = ($post['jurnal_mode'] ?? 'REF') === 'AUTO' ? 'AUTO' : 'REF';
        $lawan = (int) ($post['coa_lawan_id'] ?? 0);
        if ($mode === 'AUTO' && !$this->_account_ok($lawan)) throw new Exception('Pilih akun lawan untuk jurnal perolehan otomatis (mis. Kas/Bank, Hutang Lain-lain, atau akun saldo awal/ekuitas).');

        $common = $f + [
            'sumber'               => 'MANUAL',
            'department'           => ($post['department'] ?? '') !== '' ? $post['department'] : null,
            'lokasi'               => ($post['lokasi'] ?? '') !== '' ? mb_substr($post['lokasi'], 0, 100) : null,
            'pic'                  => ($post['pic'] ?? '') !== '' ? mb_substr($post['pic'], 0, 100) : null,
            'keterangan'           => ($post['keterangan'] ?? '') !== '' ? $post['keterangan'] : null,
            'ref_journal_no'       => ($mode === 'REF' && ($post['ref_journal_no'] ?? '') !== '') ? mb_substr(trim($post['ref_journal_no']), 0, 50) : null,
            'akumulasi_penyusutan' => $f['akumulasi_awal'],
            'nilai_buku'           => round($f['nilai_perolehan'] - $f['akumulasi_awal'], 2),
            'status'               => $this->_status_for($f['nilai_perolehan'], $f['akumulasi_awal'], $f['nilai_residu'], $f['disusutkan']),
            'created_by'           => $user_id,
        ];

        $this->db->trans_start();
        $ids = []; $nos = [];
        for ($i = 0; $i < $qty; $i++) {
            $row = $common;
            $row['no_asset'] = $this->_next_no_asset(date('Y', strtotime($f['tanggal_perolehan'])));
            $this->db->insert($this->table, $row);
            $ids[] = (int) $this->db->insert_id();
            $nos[] = $row['no_asset'];
        }

        if ($mode === 'AUTO') {
            $nilai = round($f['nilai_perolehan'] * $qty, 2);
            $akum  = round($f['akumulasi_awal'] * $qty, 2);
            $nama  = $f['nama_aset'] ?: ($this->db->select('nama_asset')->where('id', (int) $f['asset_id'])->get('assets')->row('nama_asset') ?: 'Aset');
            $desc  = 'Perolehan aset tetap ' . $nama . ' (' . ($qty > 1 ? $qty . ' unit: ' . $nos[0] . ' s/d ' . end($nos) : $nos[0]) . ')';
            $lines = [['account_id' => $f['coa_asset_id'], 'debit' => $nilai, 'kredit' => 0, 'description' => $desc]];
            if ($akum > 0) $lines[] = ['account_id' => $f['coa_akum_id'], 'debit' => 0, 'kredit' => $akum, 'description' => 'Akumulasi penyusutan awal ' . $nama];
            $lines[] = ['account_id' => $lawan, 'debit' => 0, 'kredit' => round($nilai - $akum, 2), 'description' => $desc];
            $jid = $this->_insert_posted_journal($f['tanggal_perolehan'], $desc, 'ASSET_ACQUISITION', $ids[0], $lines, $user_id);
            $this->db->where_in('id', $ids)->update($this->table, ['journal_header_id' => $jid]);
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menyimpan aset (transaksi database).');
        return $ids;
    }

    /**
     * Edit aset. Data umum (departemen, lokasi, PIC, keterangan, no. jurnal referensi)
     * selalu boleh. Data keuangan hanya sebelum ada penyusutan Posted & belum dilepas;
     * nilai/tanggal/akun aset tidak bisa diubah bila jurnal perolehan dibuat otomatis
     * (hapus & input ulang).
     */
    public function update_asset($id, $post)
    {
        $a = $this->get($id);
        if (!$a) throw new Exception('Aset tidak ditemukan.');
        if ($a->status === 'Disposed') throw new Exception('Aset sudah dilepas. Batalkan pelepasan dulu bila perlu mengubah data.');

        $data = [
            'department' => ($post['department'] ?? '') !== '' ? $post['department'] : null,
            'lokasi'     => ($post['lokasi'] ?? '') !== '' ? mb_substr($post['lokasi'], 0, 100) : null,
            'pic'        => ($post['pic'] ?? '') !== '' ? mb_substr($post['pic'], 0, 100) : null,
            'keterangan' => ($post['keterangan'] ?? '') !== '' ? $post['keterangan'] : null,
        ];
        if (empty($a->journal_header_id)) {
            $data['ref_journal_no'] = ($post['ref_journal_no'] ?? '') !== '' ? mb_substr(trim($post['ref_journal_no']), 0, 50) : null;
        }

        if (!$this->has_posted_depreciation($id)) {
            // Aset PO: master asset tetap (dari PO). Aset manual: boleh ganti master.
            if ($a->sumber === 'PO') $post['asset_id'] = $a->asset_id;
            $f = $this->_financial_fields($post, false);
            if (!empty($a->journal_header_id)) {
                foreach (['nilai_perolehan', 'tanggal_perolehan', 'coa_asset_id', 'akumulasi_awal'] as $k) {
                    $cur = $k === 'coa_asset_id' ? (int) $a->coa_asset_efektif : $a->$k;
                    if ((string) $f[$k] !== (string) $cur && !(is_numeric($cur) && abs((float) $f[$k] - (float) $cur) < 0.005)) {
                        throw new Exception('Nilai, tanggal, akun aset & akumulasi awal tidak bisa diubah karena jurnal perolehan dibuat otomatis (' . $a->acq_journal_no . '). Hapus aset lalu input ulang.');
                    }
                }
            }
            if ($a->sumber === 'PO') {
                // Akun & nama aset PO: simpan override hanya bila beda dari master.
                $m = $this->db->where('id', (int) $a->asset_id)->get('assets')->row();
                foreach (['coa_asset_id', 'coa_akum_id', 'coa_beban_id'] as $k) {
                    if ($m && (int) $f[$k] === (int) $m->$k) $f[$k] = null;
                }
                if ($m && $f['kategori'] === $m->kategori) $f['kategori'] = null;
            }
            $data += $f;
            $data['akumulasi_penyusutan'] = $f['akumulasi_awal'];
            $data['nilai_buku'] = round($f['nilai_perolehan'] - $f['akumulasi_awal'], 2);
            $data['status'] = $this->_status_for($f['nilai_perolehan'], $f['akumulasi_awal'], $f['nilai_residu'], $f['disusutkan']);
        }
        $this->db->where('id', (int) $id)->update($this->table, $data);
        return true;
    }

    /**
     * Hapus aset manual (belum disusutkan & belum dilepas). Kalau jurnal perolehan
     * otomatis dipakai bersama beberapa unit, semua unit itu ikut terhapus bersama
     * jurnalnya. Return array no_asset yang terhapus.
     */
    public function delete_asset($id)
    {
        $a = $this->get($id);
        if (!$a) throw new Exception('Aset tidak ditemukan.');
        if ($a->sumber !== 'MANUAL') throw new Exception('Aset dari PO tidak bisa dihapus di sini (berasal dari Terima Aset PO).');
        $ids = [(int) $a->id];
        if (!empty($a->journal_header_id)) {
            $ids = array_map(function ($r) { return (int) $r->id; },
                $this->db->select('id')->where('journal_header_id', (int) $a->journal_header_id)->get($this->table)->result());
        }
        $rows = $this->db->where_in('id', $ids)->get($this->table)->result();
        foreach ($rows as $r) {
            if ($r->status === 'Disposed') throw new Exception('Aset ' . $r->no_asset . ' sudah dilepas. Batalkan pelepasan dulu.');
            if ($this->has_posted_depreciation($r->id)) throw new Exception('Aset ' . $r->no_asset . ' sudah disusutkan (batch Posted). Tidak bisa dihapus.');
        }
        $this->db->trans_start();
        if (!empty($a->journal_header_id)) $this->_delete_module_journal((int) $a->journal_header_id, 'ASSET_ACQUISITION');
        // Baris di batch Draft ikut dibuang (batch Draft dihitung ulang saat posting).
        $this->db->where_in('asset_register_id', $ids)->delete('asset_depreciation_details');
        $this->db->where_in('id', $ids)->delete($this->table);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menghapus aset (transaksi database).');
        return array_map(function ($r) { return $r->no_asset; }, $rows);
    }

    /**
     * Pelepasan aset: JUAL (harga jual > 0, akun penerimaan) atau HAPUS (write-off).
     * Nilai buku = per penyusutan terakhir yang diposting.
     */
    public function dispose($id, $post, $user_id)
    {
        $a = $this->get($id);
        if (!$a) throw new Exception('Aset tidak ditemukan.');
        if (!in_array($a->status, ['Aktif', 'Habis Disusutkan'], true)) throw new Exception('Aset ini sudah dilepas.');

        $tgl = (string) ($post['disposed_at'] ?? '');
        if (!strtotime($tgl)) throw new Exception('Tanggal pelepasan wajib diisi.');
        $tgl = date('Y-m-d', strtotime($tgl));
        if ($tgl < $a->tanggal_perolehan) throw new Exception('Tanggal pelepasan tidak boleh sebelum tanggal perolehan.');
        $last = $this->last_posted_period($id);
        $p_disp = (int) date('Y', strtotime($tgl)) * 12 + (int) date('n', strtotime($tgl));
        if ($last > $p_disp) {
            $y = intdiv($last - 1, 12); $m = $last - $y * 12;
            throw new Exception('Aset sudah disusutkan sampai ' . sprintf('%02d/%d', $m, $y) . '. Tanggal pelepasan minimal di periode itu, atau batalkan dulu posting penyusutan periode setelahnya.');
        }

        $type = ($post['disposal_type'] ?? '') === 'JUAL' ? 'JUAL' : 'HAPUS';
        $harga = $type === 'JUAL' ? round((float) ($post['disposal_value'] ?? 0), 2) : 0.0;
        if ($type === 'JUAL' && $harga <= 0) throw new Exception('Harga jual harus lebih dari 0.');
        $coa_kas = (int) ($post['coa_kas_id'] ?? 0);
        if ($type === 'JUAL' && !$this->_account_ok($coa_kas)) throw new Exception('Pilih akun penerimaan hasil penjualan (Kas/Bank/Piutang).');

        $nilai = (float) $a->nilai_perolehan;
        $akum  = (float) $a->akumulasi_penyusutan;
        $buku  = round($nilai - $akum, 2);
        $hasil = round($harga - $buku, 2); // + laba, - rugi
        $coa_laba = (int) ($post['coa_laba_id'] ?? 0);
        $coa_rugi = (int) ($post['coa_rugi_id'] ?? 0);
        if ($hasil > 0 && !$this->_account_ok($coa_laba)) throw new Exception('Pilih akun Laba Pelepasan Aset (laba ' . number_format($hasil, 2) . ').');
        if ($hasil < 0 && !$this->_account_ok($coa_rugi)) throw new Exception('Pilih akun Rugi Pelepasan Aset (rugi ' . number_format(-$hasil, 2) . ').');
        if ($akum > 0 && empty($a->coa_akum_efektif)) throw new Exception('Akun akumulasi penyusutan aset ini belum diisi.');

        $label = ($type === 'JUAL' ? 'Penjualan' : 'Penghapusan') . ' aset ' . $a->no_asset . ' - ' . $a->nama;
        $lines = [];
        if ($akum > 0)   $lines[] = ['account_id' => (int) $a->coa_akum_efektif, 'debit' => $akum, 'kredit' => 0, 'description' => 'Akumulasi penyusutan ' . $a->no_asset];
        if ($harga > 0)  $lines[] = ['account_id' => $coa_kas, 'debit' => $harga, 'kredit' => 0, 'description' => 'Hasil penjualan ' . $a->no_asset];
        if ($hasil < 0)  $lines[] = ['account_id' => $coa_rugi, 'debit' => -$hasil, 'kredit' => 0, 'description' => 'Rugi pelepasan ' . $a->no_asset];
        $lines[] = ['account_id' => (int) $a->coa_asset_efektif, 'debit' => 0, 'kredit' => $nilai, 'description' => 'Pelepasan ' . $a->no_asset];
        if ($hasil > 0)  $lines[] = ['account_id' => $coa_laba, 'debit' => 0, 'kredit' => $hasil, 'description' => 'Laba pelepasan ' . $a->no_asset];

        $this->db->trans_start();
        $jid = $this->_insert_posted_journal($tgl, $label, 'ASSET_DISPOSAL', (int) $a->id, $lines, $user_id);
        $this->db->where('id', (int) $a->id)->update($this->table, [
            'status' => 'Disposed', 'disposed_at' => $tgl, 'disposal_type' => $type, 'disposal_value' => $harga,
            'disposal_journal_id' => $jid,
            'disposed_notes' => ($post['disposed_notes'] ?? '') !== '' ? $post['disposed_notes'] : null,
        ]);
        // Aset yang dilepas keluar dari batch penyusutan yang masih Draft.
        $this->db->query("DELETE d FROM asset_depreciation_details d JOIN asset_depreciation_header h ON h.id = d.depreciation_header_id
                          WHERE d.asset_register_id = ? AND h.status = 'Draft'", [(int) $a->id]);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menyimpan pelepasan (transaksi database).');
        return ['journal_id' => $jid, 'hasil' => $hasil, 'buku' => $buku];
    }

    /** Batalkan pelepasan: jurnal pelepasan dihapus, aset aktif lagi. */
    public function cancel_disposal($id)
    {
        $a = $this->get($id);
        if (!$a || $a->status !== 'Disposed') throw new Exception('Aset ini tidak dalam status dilepas.');
        $this->db->trans_start();
        if (!empty($a->disposal_journal_id)) $this->_delete_module_journal((int) $a->disposal_journal_id, 'ASSET_DISPOSAL');
        $this->db->where('id', (int) $a->id)->update($this->table, [
            'status' => $this->_status_for((float) $a->nilai_perolehan, (float) $a->akumulasi_penyusutan, (float) $a->nilai_residu, (int) $a->disusutkan),
            'disposed_at' => null, 'disposal_type' => null, 'disposal_value' => 0, 'disposal_journal_id' => null, 'disposed_notes' => null,
        ]);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal membatalkan pelepasan (transaksi database).');
        return true;
    }

    /** Jurnal Posted milik modul aset. Dipanggil di dalam transaksi pemanggil. */
    private function _insert_posted_journal($date, $desc, $source_module, $source_doc_id, array $lines, $user_id)
    {
        $total_d = 0; $total_k = 0;
        foreach ($lines as $l) { $total_d += (float) $l['debit']; $total_k += (float) $l['kredit']; }
        if (round($total_d, 2) !== round($total_k, 2)) throw new Exception('Jurnal tidak seimbang (debit ' . $total_d . ', kredit ' . $total_k . ').');
        $jh = [
            'journal_no' => $this->JurnalUmum_model->get_new_journal_number(),
            'journal_date' => $date, 'description' => mb_substr($desc, 0, 65000),
            'source_module' => $source_module, 'source_doc_id' => $source_doc_id,
            'currency' => 'IDR', 'exchange_rate' => 1,
            'total_debit' => round($total_d, 2), 'total_kredit' => round($total_k, 2),
            'total_debit_local' => round($total_d, 2), 'total_kredit_local' => round($total_k, 2),
            'created_by' => $user_id, 'status' => 'Draft',
        ];
        $jid = $this->JurnalUmum_model->insert_journal($jh, $lines);
        if (!$jid) throw new Exception('Gagal membuat jurnal.');
        $this->JurnalUmum_model->post_journal($jid, $user_id);
        return (int) $jid;
    }

    private function _delete_module_journal($jid, $source_module)
    {
        $j = $this->db->select('id')->where('id', (int) $jid)->where('source_module', $source_module)->get('journal_header')->row();
        if (!$j) return;
        $this->db->where('journal_header_id', (int) $jid)->delete('journal_details');
        $this->db->where('id', (int) $jid)->delete('journal_header');
    }
}
