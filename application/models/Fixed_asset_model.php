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
 * Asset_depreciation_model). Pelepasan (jual/hapus, satu atau beberapa unit
 * sekaligus dalam satu jurnal) membuat jurnal:
 *   Dr Akumulasi, Dr Kas/Bank (harga jual), Dr Rugi / Cr Aset, Cr Laba.
 *
 * Mutasi (tabel asset_mutations, snapshot sebelum/sesudah, bisa dibatalkan
 * selama belum ada penyusutan baru):
 *   - LEPAS_SEBAGIAN: sebagian dari satu aset dijual/dihapus (mis. tanah
 *     300 dari 1.000 m2) -> nilai, akumulasi & residu berkurang proporsional.
 *   - REVALUASI (PSAK 16, metode eliminasi): akumulasi dieliminasi, nilai aset
 *     = nilai wajar. Kenaikan: pulihkan rugi revaluasi sebelumnya (laba rugi),
 *     sisanya Surplus Revaluasi (ekuitas). Penurunan: kurangi surplus aset ini
 *     dulu, sisanya Rugi Revaluasi. Penyusutan berikutnya dari nilai baru
 *     selama sisa umur, mulai bulan berikutnya.
 */
class Fixed_asset_model extends CI_Model {

    private $table = 'asset_register';

    /** Migrasi revaluasi & pelepasan sebagian (#77) sudah dijalankan? */
    public $mut_ready = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('JurnalUmum_model');
        $this->mut_ready = $this->db->table_exists('asset_mutations') && $this->db->field_exists('surplus_revaluasi', $this->table);
    }

    private function _need_mut()
    {
        if (!$this->mut_ready) throw new Exception('Fitur ini butuh migrasi database "migration_aset_mutasi_revaluasi.sql". Jalankan dulu di menu Migrasi Database.');
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

    /** Riwayat revaluasi & pelepasan sebagian (lama -> baru). */
    public function get_mutations($id)
    {
        if (!$this->mut_ready) return [];
        return $this->db->select('m.*, j.journal_no, u.fullname AS created_by_name')
            ->from('asset_mutations m')
            ->join('journal_header j', 'j.id = m.journal_header_id', 'left')
            ->join('users u', 'u.id = m.created_by', 'left')
            ->where('m.asset_register_id', (int) $id)->order_by('m.id', 'ASC')->get()->result();
    }

    public function has_mutations($id)
    {
        return $this->mut_ready && $this->db->where('asset_register_id', (int) $id)->count_all_results('asset_mutations') > 0;
    }

    /** Nilai/umur/akun dikunci bila sudah disusutkan (Posted) atau sudah ada mutasi. */
    public function financial_locked($id)
    {
        return $this->has_posted_depreciation($id) || $this->has_mutations($id);
    }

    /** Beberapa aset sekaligus (urut no. aset). */
    public function get_many(array $ids)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) return [];
        $this->_base_select();
        $this->db->where_in('ar.id', $ids)->order_by('ar.no_asset', 'ASC');
        return $this->db->get()->result();
    }

    /**
     * Unit sejenis yang belum dilepas: satu baris PO yang sama, atau satu jurnal
     * perolehan otomatis yang sama (input manual beberapa unit).
     */
    public function siblings($a)
    {
        if (!empty($a->po_detail_id))           $this->db->where('po_detail_id', (int) $a->po_detail_id);
        elseif (!empty($a->journal_header_id))  $this->db->where('journal_header_id', (int) $a->journal_header_id);
        else return [];
        return $this->db->select('id, no_asset')->where_in('status', ['Aktif', 'Habis Disusutkan'])
            ->order_by('no_asset', 'ASC')->get($this->table)->result();
    }

    /** Periode (Y*12+M) dari tanggal. */
    private static function _pk($date)
    {
        return (int) date('Y', strtotime($date)) * 12 + (int) date('n', strtotime($date));
    }

    private static function _pk_label($pk)
    {
        $y = intdiv($pk - 1, 12); $m = $pk - $y * 12;
        return sprintf('%02d/%d', $m, $y);
    }

    /** Tanggal mutasi/pelepasan: >= tgl perolehan & tidak sebelum penyusutan Posted terakhir. */
    private function _event_date($a, $raw, $label)
    {
        if (!strtotime((string) $raw)) throw new Exception('Tanggal ' . $label . ' wajib diisi.');
        $tgl = date('Y-m-d', strtotime($raw));
        if ($tgl < $a->tanggal_perolehan) throw new Exception('Tanggal ' . $label . ' ' . $a->no_asset . ' tidak boleh sebelum tanggal perolehan.');
        $last = $this->last_posted_period($a->id);
        if ($last > self::_pk($tgl)) {
            throw new Exception('Aset ' . $a->no_asset . ' sudah disusutkan sampai ' . self::_pk_label($last) . '. Tanggal ' . $label . ' minimal di periode itu, atau batalkan dulu posting penyusutan periode setelahnya.');
        }
        return $tgl;
    }

    /** Baris aset di batch penyusutan Draft dibuang (dihitung ulang saat posting). */
    private function _drop_from_draft_batches(array $ids)
    {
        if (!$ids) return;
        $this->db->query("DELETE d FROM asset_depreciation_details d JOIN asset_depreciation_header h ON h.id = d.depreciation_header_id
                          WHERE h.status = 'Draft' AND d.asset_register_id IN (" . implode(',', array_map('intval', $ids)) . ")");
    }

    /** Akun default dari Setting Akun (0 bila kosong). */
    public function setting_account($key)
    {
        $this->load->model('SettingAkun_model');
        return (int) $this->SettingAkun_model->get_account_id($key, 'IDR');
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

        if (!$this->financial_locked($id)) {
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
            if ($this->has_mutations($r->id)) throw new Exception('Aset ' . $r->no_asset . ' punya riwayat revaluasi / pelepasan sebagian. Batalkan mutasinya dulu.');
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

    /** Satu aset (form di kartu aset): harga jual di disposal_value. */
    public function dispose($id, $post, $user_id)
    {
        $post['harga'] = [(int) $id => $post['disposal_value'] ?? 0];
        return $this->dispose_units([(int) $id], $post, $user_id);
    }

    /** Validasi akun laba/rugi pelepasan & surplus -> saldo laba. Return [coa_laba, coa_rugi, coa_surplus, coa_saldo_laba]. */
    private function _disposal_accounts($post, $hasil, $surplus_transfer)
    {
        $coa_laba = (int) ($post['coa_laba_id'] ?? 0);
        $coa_rugi = (int) ($post['coa_rugi_id'] ?? 0);
        if ($hasil > 0 && !$this->_account_ok($coa_laba)) throw new Exception('Pilih akun Laba Pelepasan Aset (laba ' . number_format($hasil, 2) . ').');
        if ($hasil < 0 && !$this->_account_ok($coa_rugi)) throw new Exception('Pilih akun Rugi Pelepasan Aset (rugi ' . number_format(-$hasil, 2) . ').');
        $coa_s = 0; $coa_sl = 0;
        if ($surplus_transfer > 0) {
            $coa_s  = (int) ($post['coa_surplus_id'] ?? 0) ?: $this->setting_account('akun_surplus_revaluasi');
            $coa_sl = (int) ($post['coa_saldo_laba_id'] ?? 0) ?: $this->setting_account('akun_saldo_laba');
            if (!$this->_account_ok($coa_s) || !$this->_account_ok($coa_sl)) throw new Exception('Pilih akun Surplus Revaluasi & Saldo Laba untuk memindahkan surplus revaluasi (' . number_format($surplus_transfer, 2) . '), atau hilangkan centang pemindahan surplus.');
        }
        return [$coa_laba, $coa_rugi, $coa_s, $coa_sl];
    }

    /**
     * Pelepasan satu / beberapa unit sekaligus dalam SATU jurnal.
     * JUAL: harga per unit di $post['harga'][id] (total > 0) + akun penerimaan;
     * HAPUS: write-off. Laba/rugi = total harga - total nilai buku (per
     * penyusutan terakhir yang diposting). Opsional: surplus revaluasi unit-unit
     * itu dipindah ke saldo laba ($post['transfer_surplus']).
     */
    public function dispose_units(array $ids, $post, $user_id)
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) throw new Exception('Pilih minimal satu unit aset.');
        if (count($ids) > 200) throw new Exception('Maksimal 200 unit dalam satu pelepasan.');

        $type = ($post['disposal_type'] ?? '') === 'JUAL' ? 'JUAL' : 'HAPUS';
        $coa_kas = (int) ($post['coa_kas_id'] ?? 0);
        if ($type === 'JUAL' && !$this->_account_ok($coa_kas)) throw new Exception('Pilih akun penerimaan hasil penjualan (Kas/Bank/Piutang).');
        $prices = (array) ($post['harga'] ?? []);

        $units = []; $tgl = null; $tot_harga = 0.0; $tot_buku = 0.0; $tot_surplus = 0.0;
        foreach ($ids as $id) {
            $a = $this->get($id);
            if (!$a) throw new Exception('Aset #' . $id . ' tidak ditemukan.');
            if (!in_array($a->status, ['Aktif', 'Habis Disusutkan'], true)) throw new Exception('Aset ' . $a->no_asset . ' sudah dilepas.');
            $tgl = $this->_event_date($a, $post['disposed_at'] ?? '', 'pelepasan');
            $harga = $type === 'JUAL' ? round((float) ($prices[$id] ?? 0), 2) : 0.0;
            if ($harga < 0) throw new Exception('Harga jual ' . $a->no_asset . ' tidak boleh negatif.');
            if (empty($a->coa_asset_efektif)) throw new Exception('Akun aset ' . $a->no_asset . ' belum diisi.');
            if ((float) $a->akumulasi_penyusutan > 0 && empty($a->coa_akum_efektif)) throw new Exception('Akun akumulasi penyusutan ' . $a->no_asset . ' belum diisi.');
            $units[] = ['a' => $a, 'harga' => $harga];
            $tot_harga += $harga;
            $tot_buku  += round((float) $a->nilai_perolehan - (float) $a->akumulasi_penyusutan, 2);
            if ($this->mut_ready) $tot_surplus += max(0, (float) $a->surplus_revaluasi);
        }
        $tot_harga = round($tot_harga, 2); $tot_buku = round($tot_buku, 2); $tot_surplus = round($tot_surplus, 2);
        if ($type === 'JUAL' && $tot_harga <= 0) throw new Exception('Harga jual harus lebih dari 0.');
        $hasil = round($tot_harga - $tot_buku, 2); // + laba, - rugi
        $transfer = (!empty($post['transfer_surplus']) && $tot_surplus > 0) ? $tot_surplus : 0.0;
        list($coa_laba, $coa_rugi, $coa_s, $coa_sl) = $this->_disposal_accounts($post, $hasil, $transfer);

        $n = count($units);
        $first = $units[0]['a'];
        $ref = $n === 1 ? $first->no_asset : $n . ' unit (' . $first->no_asset . ' dst.)';
        $label = ($type === 'JUAL' ? 'Penjualan' : 'Penghapusan') . ' aset ' . $ref . ($n === 1 ? ' - ' . $first->nama : '');

        // Jurnal: akumulasi & aset dikelompokkan per akun.
        $akum = []; $aset = [];
        foreach ($units as $u) {
            $a = $u['a'];
            if ((float) $a->akumulasi_penyusutan > 0) $akum[(int) $a->coa_akum_efektif] = ($akum[(int) $a->coa_akum_efektif] ?? 0) + (float) $a->akumulasi_penyusutan;
            $aset[(int) $a->coa_asset_efektif] = ($aset[(int) $a->coa_asset_efektif] ?? 0) + (float) $a->nilai_perolehan;
        }
        $lines = [];
        foreach ($akum as $acc => $v) $lines[] = ['account_id' => $acc, 'debit' => round($v, 2), 'kredit' => 0, 'description' => 'Akumulasi penyusutan ' . $ref];
        if ($tot_harga > 0) $lines[] = ['account_id' => $coa_kas, 'debit' => $tot_harga, 'kredit' => 0, 'description' => 'Hasil penjualan ' . $ref];
        if ($hasil < 0)     $lines[] = ['account_id' => $coa_rugi, 'debit' => -$hasil, 'kredit' => 0, 'description' => 'Rugi pelepasan ' . $ref];
        if ($transfer > 0)  $lines[] = ['account_id' => $coa_s, 'debit' => $transfer, 'kredit' => 0, 'description' => 'Surplus revaluasi terealisasi ' . $ref];
        foreach ($aset as $acc => $v) $lines[] = ['account_id' => $acc, 'debit' => 0, 'kredit' => round($v, 2), 'description' => 'Pelepasan ' . $ref];
        if ($hasil > 0)     $lines[] = ['account_id' => $coa_laba, 'debit' => 0, 'kredit' => $hasil, 'description' => 'Laba pelepasan ' . $ref];
        if ($transfer > 0)  $lines[] = ['account_id' => $coa_sl, 'debit' => 0, 'kredit' => $transfer, 'description' => 'Surplus revaluasi terealisasi ' . $ref];

        $this->db->trans_start();
        $jid = $this->_insert_posted_journal($tgl, $label, 'ASSET_DISPOSAL', (int) $first->id, $lines, $user_id);
        foreach ($units as $u) {
            $this->db->where('id', (int) $u['a']->id)->update($this->table, [
                'status' => 'Disposed', 'disposed_at' => $tgl, 'disposal_type' => $type, 'disposal_value' => $u['harga'],
                'disposal_journal_id' => $jid,
                'disposed_notes' => ($post['disposed_notes'] ?? '') !== '' ? $post['disposed_notes'] : null,
            ]);
        }
        // Aset yang dilepas keluar dari batch penyusutan yang masih Draft.
        $this->_drop_from_draft_batches($ids);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menyimpan pelepasan (transaksi database).');
        return ['journal_id' => $jid, 'hasil' => $hasil, 'buku' => $tot_buku, 'count' => $n, 'surplus' => $transfer];
    }

    /**
     * Batalkan pelepasan: jurnal pelepasan dihapus, aset aktif lagi. Bila jurnal
     * itu dipakai beberapa unit (pelepasan sekaligus), semua unitnya ikut batal.
     * Return array no_asset.
     */
    public function cancel_disposal($id)
    {
        $a = $this->get($id);
        if (!$a || $a->status !== 'Disposed') throw new Exception('Aset ini tidak dalam status dilepas.');
        $rows = !empty($a->disposal_journal_id)
            ? $this->db->where('disposal_journal_id', (int) $a->disposal_journal_id)->where('status', 'Disposed')->get($this->table)->result()
            : [$this->db->where('id', (int) $a->id)->get($this->table)->row()];
        $this->db->trans_start();
        if (!empty($a->disposal_journal_id)) $this->_delete_module_journal((int) $a->disposal_journal_id, 'ASSET_DISPOSAL');
        foreach ($rows as $r) {
            $this->db->where('id', (int) $r->id)->update($this->table, [
                'status' => $this->_status_for((float) $r->nilai_perolehan, (float) $r->akumulasi_penyusutan, (float) $r->nilai_residu, (int) $r->disusutkan),
                'disposed_at' => null, 'disposal_type' => null, 'disposal_value' => 0, 'disposal_journal_id' => null, 'disposed_notes' => null,
            ]);
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal membatalkan pelepasan (transaksi database).');
        return array_map(function ($r) { return $r->no_asset; }, $rows);
    }

    /** Snapshot kolom register sebelum mutasi. */
    private function _snapshot_before($a)
    {
        return [
            'nilai_sebelum'      => (float) $a->nilai_perolehan,
            'akumulasi_sebelum'  => (float) $a->akumulasi_penyusutan,
            'residu_sebelum'     => (float) $a->nilai_residu,
            'umur_sebelum'       => (int) $a->umur_manfaat_bulan,
            'mulai_sebelum'      => $a->mulai_penyusutan,
            'status_sebelum'     => $a->status,
            'surplus_sebelum'    => (float) $a->surplus_revaluasi,
            'rugi_reval_sebelum' => (float) $a->rugi_revaluasi,
        ];
    }

    /**
     * Pelepasan SEBAGIAN satu aset (jual / hapus), mis. tanah 300 dari 1.000 m2.
     * Porsi = porsi_dilepas / porsi_total. Nilai, akumulasi, residu (dan surplus
     * revaluasi) aset berkurang proporsional; penyusutan berikutnya otomatis dari
     * sisa nilai. Jurnal: Dr Akumulasi (porsi), Dr Kas, Dr Rugi / Cr Aset (porsi), Cr Laba.
     */
    public function dispose_partial($id, $post, $user_id)
    {
        $this->_need_mut();
        $a = $this->get($id);
        if (!$a) throw new Exception('Aset tidak ditemukan.');
        if (!in_array($a->status, ['Aktif', 'Habis Disusutkan'], true)) throw new Exception('Aset ini sudah dilepas.');
        $tgl = $this->_event_date($a, $post['disposed_at'] ?? '', 'pelepasan');

        $part  = round((float) ($post['porsi_dilepas'] ?? 0), 4);
        $total = round((float) ($post['porsi_total'] ?? 0), 4);
        if ($part <= 0 || $total <= 0 || $part >= $total) throw new Exception('Porsi dilepas harus lebih dari 0 dan kurang dari total. Untuk melepas seluruh aset pilih "Seluruh aset".');
        $f = $part / $total;

        $C = (float) $a->nilai_perolehan; $A = (float) $a->akumulasi_penyusutan; $R = (float) $a->nilai_residu;
        $pc = round($C * $f, 2); $pa = round($A * $f, 2); $pr = round($R * $f, 2);
        if ($pc <= 0 || $pc >= $C) throw new Exception('Porsi terlalu kecil / besar untuk nilai aset ini.');
        if (empty($a->coa_asset_efektif)) throw new Exception('Akun aset belum diisi.');
        if ($pa > 0 && empty($a->coa_akum_efektif)) throw new Exception('Akun akumulasi penyusutan aset ini belum diisi.');

        $type = ($post['disposal_type'] ?? '') === 'JUAL' ? 'JUAL' : 'HAPUS';
        $harga = $type === 'JUAL' ? round((float) ($post['disposal_value'] ?? 0), 2) : 0.0;
        if ($type === 'JUAL' && $harga <= 0) throw new Exception('Harga jual harus lebih dari 0.');
        $coa_kas = (int) ($post['coa_kas_id'] ?? 0);
        if ($type === 'JUAL' && !$this->_account_ok($coa_kas)) throw new Exception('Pilih akun penerimaan hasil penjualan (Kas/Bank/Piutang).');

        $buku_p = round($pc - $pa, 2);
        $hasil  = round($harga - $buku_p, 2);
        $surplus = (float) $a->surplus_revaluasi;
        $ps = round(max(0, $surplus) * $f, 2);
        $transfer = (!empty($post['transfer_surplus']) && $ps > 0) ? $ps : 0.0;
        list($coa_laba, $coa_rugi, $coa_s, $coa_sl) = $this->_disposal_accounts($post, $hasil, $transfer);

        $fmt_num = function ($x) { return rtrim(rtrim(number_format($x, 4, ',', '.'), '0'), ','); };
        $satuan = trim((string) ($post['porsi_satuan'] ?? ''));
        $porsi_ket = mb_substr($fmt_num($part) . ' dari ' . $fmt_num($total) . ($satuan !== '' ? ' ' . $satuan : ''), 0, 100);
        $ref = $a->no_asset . ' (' . $porsi_ket . ')';
        $label = ($type === 'JUAL' ? 'Penjualan sebagian' : 'Penghapusan sebagian') . ' aset ' . $ref . ' - ' . $a->nama;

        $lines = [];
        if ($pa > 0)       $lines[] = ['account_id' => (int) $a->coa_akum_efektif, 'debit' => $pa, 'kredit' => 0, 'description' => 'Akumulasi penyusutan ' . $ref];
        if ($harga > 0)    $lines[] = ['account_id' => $coa_kas, 'debit' => $harga, 'kredit' => 0, 'description' => 'Hasil penjualan ' . $ref];
        if ($hasil < 0)    $lines[] = ['account_id' => $coa_rugi, 'debit' => -$hasil, 'kredit' => 0, 'description' => 'Rugi pelepasan ' . $ref];
        if ($transfer > 0) $lines[] = ['account_id' => $coa_s, 'debit' => $transfer, 'kredit' => 0, 'description' => 'Surplus revaluasi terealisasi ' . $ref];
        $lines[] = ['account_id' => (int) $a->coa_asset_efektif, 'debit' => 0, 'kredit' => $pc, 'description' => 'Pelepasan sebagian ' . $ref];
        if ($hasil > 0)    $lines[] = ['account_id' => $coa_laba, 'debit' => 0, 'kredit' => $hasil, 'description' => 'Laba pelepasan ' . $ref];
        if ($transfer > 0) $lines[] = ['account_id' => $coa_sl, 'debit' => 0, 'kredit' => $transfer, 'description' => 'Surplus revaluasi terealisasi ' . $ref];

        $nC = round($C - $pc, 2); $nA = round($A - $pa, 2); $nR = round($R - $pr, 2);
        $this->db->trans_start();
        $jid = $this->_insert_posted_journal($tgl, $label, 'ASSET_PARTIAL_DISPOSAL', (int) $a->id, $lines, $user_id);
        $this->db->insert('asset_mutations', $this->_snapshot_before($a) + [
            'asset_register_id' => (int) $a->id, 'jenis' => 'LEPAS_SEBAGIAN', 'tanggal' => $tgl, 'periode' => self::_pk($tgl),
            'dep_period_at' => $this->last_posted_period($a->id),
            'porsi' => round($f, 8), 'porsi_keterangan' => $porsi_ket,
            'nilai_sesudah' => $nC, 'akumulasi_sesudah' => $nA, 'residu_sesudah' => $nR,
            'umur_sesudah' => (int) $a->umur_manfaat_bulan, 'mulai_sesudah' => $a->mulai_penyusutan,
            'delta_nilai' => -$pc, 'delta_akumulasi' => -$pa,
            'disposal_type' => $type, 'harga_jual' => $harga, 'laba_rugi' => $hasil, 'surplus_ke_saldo_laba' => $transfer,
            'journal_header_id' => $jid,
            'keterangan' => ($post['disposed_notes'] ?? '') !== '' ? $post['disposed_notes'] : null,
            'created_by' => $user_id,
        ]);
        $this->db->where('id', (int) $a->id)->update($this->table, [
            'nilai_perolehan' => $nC, 'akumulasi_penyusutan' => $nA, 'nilai_residu' => $nR, 'nilai_buku' => round($nC - $nA, 2),
            'status' => $this->_status_for($nC, $nA, $nR, (int) $a->disusutkan),
            'surplus_revaluasi' => round($surplus - $ps, 2),
            'rugi_revaluasi' => round((float) $a->rugi_revaluasi * (1 - $f), 2),
        ]);
        $this->_drop_from_draft_batches([(int) $a->id]);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menyimpan pelepasan sebagian (transaksi database).');
        return ['journal_id' => $jid, 'hasil' => $hasil, 'buku' => $buku_p, 'porsi' => $porsi_ket];
    }

    /** Usulan sisa umur (bulan) setelah revaluasi per tanggal tsb. */
    public function suggested_remaining_life($a, $tgl = null)
    {
        $umur = (int) $a->umur_manfaat_bulan;
        if (empty($a->disusutkan) || $umur <= 0 || empty($a->mulai_penyusutan)) return 0;
        $used = self::_pk($tgl ?: date('Y-m-d')) - self::_pk($a->mulai_penyusutan) + 1;
        return max(0, $umur - max(0, $used));
    }

    /**
     * Revaluasi aset ke nilai wajar (PSAK 16, metode eliminasi akumulasi).
     * Berlaku akhir bulan tanggal revaluasi: penyusutan s/d bulan itu harus sudah
     * diposting; penyusutan nilai baru mulai bulan berikutnya selama sisa umur.
     */
    public function revalue($id, $post, $user_id)
    {
        $this->_need_mut();
        $a = $this->get($id);
        if (!$a) throw new Exception('Aset tidak ditemukan.');
        if (!in_array($a->status, ['Aktif', 'Habis Disusutkan'], true)) throw new Exception('Aset ini sudah dilepas.');
        $tgl = $this->_event_date($a, $post['tanggal'] ?? '', 'revaluasi');
        $y = (int) date('Y', strtotime($tgl)); $m = (int) date('n', strtotime($tgl));
        $this->load->model('Asset_depreciation_model');
        $due = $a->status === 'Aktif' ? Asset_depreciation_model::due_amount($a, $y, $m) : 0.0;
        if ($due > 0) {
            throw new Exception('Penyusutan aset ini s/d ' . Asset_depreciation_model::period_label($y, $m) . ' belum diposting (kurang ' . number_format($due, 2) . '). Posting dulu batch penyusutan periode itu, baru revaluasi — revaluasi berlaku akhir bulan.');
        }

        $F = round((float) ($post['nilai_wajar'] ?? 0), 2);
        if ($F <= 0) throw new Exception('Nilai wajar harus lebih dari 0.');
        $C = (float) $a->nilai_perolehan; $A = (float) $a->akumulasi_penyusutan; $B = round($C - $A, 2);
        $disusutkan = (int) $a->disusutkan;
        if ($disusutkan) {
            $umur_baru = (int) ($post['umur_baru'] ?? 0);
            if ($umur_baru <= 0) throw new Exception('Sisa umur manfaat (bulan) setelah revaluasi wajib diisi.');
            $res_baru = round((float) ($post['residu_baru'] ?? 0), 2);
            if ($res_baru < 0 || $res_baru >= $F) throw new Exception('Nilai residu baru harus 0 sampai di bawah nilai wajar.');
            $mulai_baru = date('Y-m-01', strtotime(date('Y-m-01', strtotime($tgl)) . ' +1 month'));
        } else {
            $umur_baru = (int) $a->umur_manfaat_bulan; $res_baru = 0.0; $mulai_baru = $a->mulai_penyusutan;
        }

        $diff = round($F - $B, 2);   // + naik, - turun (thd nilai buku)
        $dC   = round($F - $C, 2);   // perubahan harga perolehan
        if (abs($dC) < 0.005 && $A < 0.005) throw new Exception('Nilai wajar sama dengan nilai buku dan tidak ada akumulasi penyusutan — tidak ada yang direvaluasi.');
        $surplus = (float) $a->surplus_revaluasi; $rugi = (float) $a->rugi_revaluasi;
        $to_surplus = 0.0; $to_pl = 0.0;
        if ($diff > 0) {
            $to_pl = round(min($diff, max(0, $rugi)), 2);           // pemulihan rugi revaluasi sebelumnya
            $to_surplus = round($diff - $to_pl, 2);
        } elseif ($diff < 0) {
            $from_s = round(min(-$diff, max(0, $surplus)), 2);      // kurangi surplus aset ini dulu
            $to_surplus = -$from_s;
            $to_pl = round($diff + $from_s, 2);                     // sisanya rugi (negatif)
        }

        $coa_s  = (int) ($post['coa_surplus_id'] ?? 0) ?: $this->setting_account('akun_surplus_revaluasi');
        $coa_lr = (int) ($post['coa_lr_id'] ?? 0) ?: $this->setting_account('akun_laba_rugi_revaluasi');
        if (abs($to_surplus) > 0 && !$this->_account_ok($coa_s)) throw new Exception('Pilih akun Surplus Revaluasi (ekuitas).');
        if (abs($to_pl) > 0 && !$this->_account_ok($coa_lr)) throw new Exception('Pilih akun Laba/Rugi Revaluasi.');
        if (empty($a->coa_asset_efektif)) throw new Exception('Akun aset belum diisi.');
        if ($A > 0 && empty($a->coa_akum_efektif)) throw new Exception('Akun akumulasi penyusutan aset ini belum diisi.');

        $ref = $a->no_asset . ' - ' . $a->nama;
        $lines = [];
        if ($A > 0)          $lines[] = ['account_id' => (int) $a->coa_akum_efektif, 'debit' => round($A, 2), 'kredit' => 0, 'description' => 'Eliminasi akumulasi penyusutan (revaluasi) ' . $a->no_asset];
        if ($dC > 0)         $lines[] = ['account_id' => (int) $a->coa_asset_efektif, 'debit' => $dC, 'kredit' => 0, 'description' => 'Revaluasi ' . $ref];
        if ($to_surplus < 0) $lines[] = ['account_id' => $coa_s, 'debit' => -$to_surplus, 'kredit' => 0, 'description' => 'Penurunan revaluasi (kurangi surplus) ' . $a->no_asset];
        if ($to_pl < 0)      $lines[] = ['account_id' => $coa_lr, 'debit' => -$to_pl, 'kredit' => 0, 'description' => 'Rugi revaluasi ' . $a->no_asset];
        if ($dC < 0)         $lines[] = ['account_id' => (int) $a->coa_asset_efektif, 'debit' => 0, 'kredit' => -$dC, 'description' => 'Revaluasi ' . $ref];
        if ($to_surplus > 0) $lines[] = ['account_id' => $coa_s, 'debit' => 0, 'kredit' => $to_surplus, 'description' => 'Surplus revaluasi ' . $a->no_asset];
        if ($to_pl > 0)      $lines[] = ['account_id' => $coa_lr, 'debit' => 0, 'kredit' => $to_pl, 'description' => 'Pemulihan rugi revaluasi ' . $a->no_asset];

        $this->db->trans_start();
        $jid = $this->_insert_posted_journal($tgl, 'Revaluasi aset ' . $ref . ' ke nilai wajar ' . number_format($F, 2), 'ASSET_REVALUATION', (int) $a->id, $lines, $user_id);
        $this->db->insert('asset_mutations', $this->_snapshot_before($a) + [
            'asset_register_id' => (int) $a->id, 'jenis' => 'REVALUASI', 'tanggal' => $tgl, 'periode' => self::_pk($tgl),
            'dep_period_at' => $this->last_posted_period($a->id),
            'nilai_sesudah' => $F, 'akumulasi_sesudah' => 0, 'residu_sesudah' => $res_baru,
            'umur_sesudah' => $umur_baru, 'mulai_sesudah' => $mulai_baru,
            'delta_nilai' => $dC, 'delta_akumulasi' => -round($A, 2),
            'nilai_wajar' => $F, 'ke_surplus' => $to_surplus, 'ke_laba_rugi' => $to_pl,
            'journal_header_id' => $jid,
            'keterangan' => ($post['keterangan'] ?? '') !== '' ? $post['keterangan'] : null,
            'created_by' => $user_id,
        ]);
        $this->db->where('id', (int) $a->id)->update($this->table, [
            'nilai_perolehan' => $F, 'akumulasi_penyusutan' => 0, 'nilai_buku' => $F, 'nilai_residu' => $res_baru,
            'umur_manfaat_bulan' => $umur_baru, 'mulai_penyusutan' => $mulai_baru,
            'status' => $this->_status_for($F, 0, $res_baru, $disusutkan),
            'surplus_revaluasi' => round($surplus + $to_surplus, 2),
            'rugi_revaluasi' => round($rugi - $to_pl, 2),
        ]);
        $this->_drop_from_draft_batches([(int) $a->id]);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal menyimpan revaluasi (transaksi database).');
        return ['journal_id' => $jid, 'selisih' => $diff, 'ke_surplus' => $to_surplus, 'ke_laba_rugi' => $to_pl];
    }

    /**
     * Batalkan revaluasi / pelepasan sebagian: hanya mutasi TERAKHIR aset, aset
     * belum dilepas seluruhnya, dan belum ada penyusutan yang diposting sejak
     * mutasi dibuat. Nilai aset dikembalikan dari snapshot, jurnal dihapus.
     */
    public function cancel_mutation($mutation_id)
    {
        $this->_need_mut();
        $m = $this->db->where('id', (int) $mutation_id)->get('asset_mutations')->row();
        if (!$m) throw new Exception('Data mutasi tidak ditemukan.');
        $a = $this->get($m->asset_register_id);
        if (!$a) throw new Exception('Aset tidak ditemukan.');
        if ($a->status === 'Disposed') throw new Exception('Aset sudah dilepas seluruhnya. Batalkan pelepasannya dulu.');
        $latest = (int) $this->db->select_max('id')->where('asset_register_id', (int) $a->id)->get('asset_mutations')->row('id');
        if ($latest !== (int) $m->id) throw new Exception('Hanya mutasi terakhir aset ini yang bisa dibatalkan. Batalkan dulu mutasi sesudahnya.');
        if ($this->last_posted_period($a->id) !== (int) $m->dep_period_at) {
            throw new Exception('Sudah ada penyusutan yang diposting setelah mutasi ini. Batalkan dulu posting batch penyusutan periode setelahnya.');
        }
        $this->db->trans_start();
        if (!empty($m->journal_header_id)) $this->_delete_module_journal((int) $m->journal_header_id, $m->jenis === 'REVALUASI' ? 'ASSET_REVALUATION' : 'ASSET_PARTIAL_DISPOSAL');
        $this->db->where('id', (int) $a->id)->update($this->table, [
            'nilai_perolehan' => (float) $m->nilai_sebelum, 'akumulasi_penyusutan' => (float) $m->akumulasi_sebelum,
            'nilai_buku' => round((float) $m->nilai_sebelum - (float) $m->akumulasi_sebelum, 2),
            'nilai_residu' => (float) $m->residu_sebelum, 'umur_manfaat_bulan' => (int) $m->umur_sebelum, 'mulai_penyusutan' => $m->mulai_sebelum,
            'status' => $m->status_sebelum ?: $this->_status_for((float) $m->nilai_sebelum, (float) $m->akumulasi_sebelum, (float) $m->residu_sebelum, (int) $a->disusutkan),
            'surplus_revaluasi' => (float) $m->surplus_sebelum, 'rugi_revaluasi' => (float) $m->rugi_reval_sebelum,
        ]);
        $this->db->where('id', (int) $m->id)->delete('asset_mutations');
        $this->_drop_from_draft_batches([(int) $a->id]);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) throw new Exception('Gagal membatalkan mutasi (transaksi database).');
        return ['asset' => $a, 'mutation' => $m];
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
