<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * TIDAK DIPAKAI LAGI untuk input sejak 2026-11-08 (Acropoly): NG di QC
 * Production Receive & asal scrap Proses Produksi cukup Qty NG + Keterangan
 * (user: "hilangkan 4 kategori rinci itu, sederhanakan saja"). Kolom ng_* di
 * tabel dibiarkan untuk data lama; helper ini dipertahankan agar kode/data
 * lama yang masih memanggilnya tidak error.
 *
 * Rincian NG material — 4 kategori penyebab + kode NG.
 *
 * Acuan: assets/DAILY PRODUCTION REPORT (PQC & MATERIAL) (1).xlsx, sheet
 * MATERIAL & PIVOT. Tiap material yang NG dicatat penyebabnya terpecah ke 4
 * kolom (HUMAN ERROR / MATERIAL ISSUE / MECHANIC ISSUE / OTHERS ISSUE) yang
 * dijumlahkan jadi TOTAL, ditambah kode NG & remark.
 *
 * User 2026-09-30: "itukan sebenarnya sifatnya remark aja, angkanya disimpan
 * sebagai catatan material itu NG nya dimana" -- jadi angka di sini TIDAK
 * menyentuh stok, ledger, maupun jurnal. Murni catatan untuk laporan.
 *
 * Dipakai di dua titik input yang strukturnya sama:
 *   - production_scrap_sources  (material jadi scrap saat proses produksi)
 *   - issuance_qc_results       (material reject saat QC penerimaan produksi)
 *
 * SEMUA fungsi di sini aman dipanggil di database yang belum menjalankan
 * db/migration_material_ng_detail.sql: kolomnya dicek dulu lewat
 * db_has_column(), dan kalau belum ada, data NG tinggal tidak ikut disimpan
 * tanpa membuat query gagal.
 */

if (!function_exists('ng_categories')) {
    /**
     * 4 kategori penyebab NG. Key = nama kolom database, value = label kolom
     * di file klien. Urutannya sengaja sama dengan sheet MATERIAL.
     */
    function ng_categories() {
        return [
            'ng_human'    => 'HUMAN ERROR',
            'ng_material' => 'MATERIAL ISSUE',
            'ng_mechanic' => 'MECHANIC ISSUE',
            'ng_others'   => 'OTHERS ISSUE',
        ];
    }
}

if (!function_exists('ng_default_category')) {
    /**
     * Kategori yang dipakai form untuk mengisi otomatis saat qty diketik.
     * MATERIAL ISSUE dipilih karena di data klien (2.237 baris) kategori ini
     * yang terbesar: 12.408 dari 20.825 pcs.
     */
    function ng_default_category() { return 'ng_material'; }
}

if (!function_exists('ng_table_ready')) {
    /**
     * TRUE kalau tabel ini sudah punya kolom rincian NG (migrasi sudah jalan).
     */
    function ng_table_ready($table) {
        return db_has_column($table, 'ng_human') && db_has_column($table, 'ng_code');
    }
}

if (!function_exists('ng_code_options')) {
    /**
     * Master kode NG dari common_code group NG_CODE, untuk isi dropdown.
     * Mengembalikan array of object {code, description}.
     */
    function ng_code_options() {
        static $cache = null;
        if ($cache !== null) return $cache;
        $CI =& get_instance();
        $CI->load->database();
        try {
            if (!$CI->db->table_exists('common_code')) return $cache = [];
            $cache = $CI->db->select('code, description')
                            ->from('common_code')
                            ->where('group_code', 'NG_CODE')
                            ->where('status', 'active')
                            ->order_by('id', 'ASC')
                            ->get()->result();
        } catch (\Throwable $e) {
            $cache = [];
        }
        return $cache;
    }
}

if (!function_exists('ng_code_description')) {
    /**
     * REASON DEFECT untuk satu kode NG. String kosong kalau kode tidak dikenal
     * (mis. data lama yang diketik bebas sebelum ada dropdown).
     */
    function ng_code_description($code) {
        if ($code === null || $code === '') return '';
        static $map = null;
        if ($map === null) {
            $map = [];
            foreach (ng_code_options() as $o) $map[$o->code] = $o->description;
        }
        return $map[$code] ?? '';
    }
}

if (!function_exists('ng_collect_post')) {
    /**
     * Ambil rincian NG dari input form dan susun jadi array siap insert/update.
     *
     * @param array      $src   sumber nilai (mis. $this->input->post() atau satu
     *                          baris items[] pada form QC)
     * @param float|null $qty   qty NG baris ini. Kalau diisi dan keempat
     *                          kategori kosong semua, seluruh qty ditaruh di
     *                          kategori default -- supaya total rincian selalu
     *                          sama dengan qty tanpa memaksa user mengetik dua
     *                          kali. Di file klien ada 3 baris yang keempat
     *                          kategorinya tidak menjumlah ke TOTAL; dengan
     *                          pengisian otomatis ini hal itu tidak terulang.
     * @param string     $table nama tabel tujuan, dipakai untuk menjaga agar
     *                          kolom yang belum ada tidak ikut ditulis.
     * @return array            kolom => nilai (kosong kalau migrasi belum jalan)
     */
    function ng_collect_post($src, $qty = null, $table = null) {
        if ($table !== null && !ng_table_ready($table)) return [];

        $data  = [];
        $total = 0.0;
        foreach (array_keys(ng_categories()) as $col) {
            $v = isset($src[$col]) ? (float) $src[$col] : 0.0;
            if ($v < 0) $v = 0.0;
            $data[$col] = $v;
            $total += $v;
        }

        if ($qty !== null && $total <= 0 && (float) $qty > 0) {
            $data[ng_default_category()] = (float) $qty;
        }

        $code = isset($src['ng_code']) ? trim((string) $src['ng_code']) : '';
        $data['ng_code'] = ($code === '') ? null : substr($code, 0, 20);

        return $data;
    }
}

if (!function_exists('ng_total')) {
    /**
     * TOTAL = jumlah 4 kategori. Terima object hasil query maupun array.
     */
    function ng_total($row) {
        $sum = 0.0;
        foreach (array_keys(ng_categories()) as $col) {
            if (is_array($row))       $sum += (float) ($row[$col] ?? 0);
            elseif (is_object($row))  $sum += (float) ($row->$col ?? 0);
        }
        return $sum;
    }
}

if (!function_exists('ng_summary_text')) {
    /**
     * Ringkasan sebaris untuk ditampilkan di tabel, mis. "MAT 3 · OTH 1 (V2)".
     * Kategori bernilai 0 tidak ditulis. Kosong kalau tidak ada rincian.
     */
    function ng_summary_text($row) {
        $short = ['ng_human' => 'HUM', 'ng_material' => 'MAT', 'ng_mechanic' => 'MEC', 'ng_others' => 'OTH'];
        $parts = [];
        foreach ($short as $col => $label) {
            $v = is_array($row) ? (float) ($row[$col] ?? 0) : (float) ($row->$col ?? 0);
            if ($v > 0) $parts[] = $label . ' ' . rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.');
        }
        $code = is_array($row) ? ($row['ng_code'] ?? '') : ($row->ng_code ?? '');
        $text = implode(' · ', $parts);
        if ($code !== '' && $code !== null) $text = $text === '' ? '(' . $code . ')' : $text . ' (' . $code . ')';
        return $text;
    }
}
