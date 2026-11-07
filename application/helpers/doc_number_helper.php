<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pembuat nomor dokumen otomatis — format & prefix diatur di
 * config/doc_number.php: {PREFIX}/{KODE PERUSAHAAN}/{YYMM}{URUT}.
 *
 * Dibuat 2026-11-05 (rebrand Acropoly). Sebelumnya tiap modul punya
 * generator sendiri dengan gaya berbeda-beda (GRN/202611/0001,
 * POPRO - 000001, ADM-000093, PRO00001, RTN-20261105-001, ...).
 */

if (!function_exists('doc_number_config')) {
    function doc_number_config() {
        static $cfg = null;
        if ($cfg !== null) return $cfg;
        $CI =& get_instance();
        $CI->config->load('doc_number', true);
        return $cfg = $CI->config->item('doc_number');
    }
}

if (!function_exists('doc_number_prefix')) {
    /**
     * Bagian depan nomor sampai sebelum nomor urut, mis. "PR/ACRO/2611".
     * @param string      $doc_key kunci di $config['doc_number_prefixes']
     * @param string|null $date    tanggal acuan YYMM (default: hari ini)
     */
    function doc_number_prefix($doc_key, $date = null) {
        $cfg = doc_number_config();
        if (!isset($cfg['doc_number_prefixes'][$doc_key])) {
            throw new InvalidArgumentException("Prefix nomor dokumen '$doc_key' belum diatur di config/doc_number.php");
        }
        $ts = $date ? strtotime($date) : time();
        return $cfg['doc_number_prefixes'][$doc_key] . '/' . $cfg['doc_number_company_code'] . '/'
             . date('ym', $ts) . ($cfg['doc_number_separator'] ?? '');
    }
}

if (!function_exists('doc_number_next')) {
    /**
     * Nomor berikutnya untuk 1 jenis dokumen. Urutan dihitung dari nomor
     * terbesar yang sudah ada di $table.$column dengan prefix bulan yang sama
     * (jadi otomatis reset tiap bulan).
     *
     * @param string      $doc_key kunci prefix (config/doc_number.php)
     * @param string      $table   tabel penyimpan nomor
     * @param string      $column  kolom nomor
     * @param string|null $date    tanggal acuan YYMM (default: hari ini)
     * @return string mis. "PR/ACRO/26110001"
     */
    function doc_number_next($doc_key, $table, $column, $date = null) {
        $CI =& get_instance();
        $cfg    = doc_number_config();
        $digits = max(1, (int) ($cfg['doc_number_digits'] ?? 4));
        $prefix = doc_number_prefix($doc_key, $date);

        // Hanya nomor yang SISANYA murni angka yang dihitung, supaya nomor
        // berformat lain yang kebetulan berawalan sama tidak mengacaukan urutan.
        $row = $CI->db->query(
            "SELECT MAX(CAST(SUBSTRING(`$column`, ?) AS UNSIGNED)) AS max_seq
               FROM `$table`
              WHERE `$column` LIKE ? AND SUBSTRING(`$column`, ?) REGEXP '^[0-9]+$'",
            [strlen($prefix) + 1, $prefix . '%', strlen($prefix) + 1] // prefix dari config, tanpa wildcard % / _
        )->row();

        $next = ($row && $row->max_seq) ? ((int) $row->max_seq + 1) : 1;
        return $prefix . str_pad((string) $next, $digits, '0', STR_PAD_LEFT);
    }
}
