<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Developer Mode" — kelola logo/gambar lewat UI (ERP Revision Notes, user
 * 2026-09-03). Pola PERSIS sama seperti lang_line()/lang_overrides: 1
 * fungsi jadi satu pintu, path DI DB (tabel site_assets) kalau ada
 * override, else fallback ke $default_path hardcode di kode.
 *
 * BEDA dari lang_line(): TIDAK auto-discovery (jumlah slot gambar
 * terbatas & jarang berubah — didaftarkan manual di
 * Developer_mode::ASSET_SLOTS, bukan auto-registrasi kayak teks).
 * Kalau tabelnya belum ada / ada error DB, diam-diam skip & fallback ke
 * $default_path — jangan sampai fitur ini bikin logo hilang seluruh app.
 */
if (!function_exists('site_asset')) {
    function site_asset($key, $default_path) {
        $CI =& get_instance();

        static $cache = null;
        if ($cache === null) {
            $cache = [];
            try {
                if ($CI->db->table_exists('site_assets')) {
                    $rows = $CI->db->select('asset_key, file_path')->get('site_assets')->result();
                    foreach ($rows as $r) {
                        if (!empty($r->file_path)) {
                            $cache[$r->asset_key] = $r->file_path;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Diam-diam skip.
            }
        }

        return $cache[$key] ?? $default_path;
    }
}

/**
 * Nilai teks identitas situs yang bisa diubah user lewat Developer Mode
 * (tabel site_settings). Polanya PERSIS site_asset() di atas: DB cuma
 * MENIMPA, nilai bawaannya tetap hidup di kode -- jadi DB yang belum
 * dimigrasi, tabel yang belum ada, atau error DB apa pun tetap menampilkan
 * identitas yang benar, bukan string kosong.
 */
if (!function_exists('site_setting')) {
    function site_setting($key, $default = '') {
        $CI =& get_instance();

        static $cache = null;
        if ($cache === null) {
            $cache = [];
            try {
                if ($CI->db->table_exists('site_settings')) {
                    $rows = $CI->db->select('setting_key, setting_value')->get('site_settings')->result();
                    foreach ($rows as $r) {
                        if ($r->setting_value !== null && trim($r->setting_value) !== '') {
                            $cache[$r->setting_key] = $r->setting_value;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Diam-diam skip -- identitas perusahaan tidak boleh hilang
                // dari seluruh aplikasi gara-gara satu error DB.
            }
        }

        return $cache[$key] ?? $default;
    }
}

/**
 * Identitas perusahaan. SATU-SATUNYA tempat nilai bawaannya ditulis --
 * sebelum 2026-10-08 nama & alamat tersebar sebagai teks mentah di 27
 * berkas (kop PDF, export Excel, login, footer), jadi ganti nama perusahaan
 * berarti menyisir semuanya.
 *
 * Kunci yang tersedia (lihat Developer_mode::SETTING_FIELDS utk form-nya):
 *   app_name        -- nama aplikasi (judul tab, kartu login, footer)
 *   company_name    -- nama resmi, dipakai di kop & judul dokumen
 *   company_address -- alamat, BOLEH beberapa baris (dipisah newline)
 *   company_phone   -- baris kontak di kaki dokumen
 */
if (!function_exists('company_info')) {
    function company_info($key) {
        static $defaults = [
            // Nama aplikasi (judul tab, kartu login, footer) -- dipisah dari
            // nama perusahaan karena keduanya memang beda: produknya bisa
            // dipakai perusahaan lain dgn nama aplikasi yang sama, atau
            // sebaliknya.
            // Rebrand 2026-11-05: repo ini dulu milik PT New Specialife
            // Industries, sekarang PT Acropoly International. Telepon & email
            // sengaja kosong (belum diketahui) -- baris kosong otomatis tidak
            // dicetak di kop/kaki dokumen; isi lewat Developer Mode.
            'app_name'        => 'ERP ACROPOLY',
            'company_name'    => 'PT. ACROPOLY INTERNATIONAL',
            'company_address' => "Kawasan Industri Jatengland Industrial Park Sayung (JIPS) Blok V5C, Sayung\nKabupaten Demak, Jawa Tengah",
            'company_phone'   => '',
            'company_email'   => '',
        ];
        return site_setting($key, $defaults[$key] ?? '');
    }
}

/**
 * Alamat + telepon sebagai larik baris -- untuk kop dokumen yang menuliskan
 * kontak perusahaan baris per baris (export Excel, kop PR/PO/Disbursement).
 *
 * $max > 0: jumlah baris dibatasi (mis. Excel yang slotnya cuma A2:A4).
 * Telepon tetap di baris terakhir; kelebihan baris alamat digabung koma ke
 * baris alamat terakhir yang muat, jadi tidak ada bagian alamat yang hilang.
 */
if (!function_exists('company_contact_lines')) {
    function company_contact_lines($max = 0) {
        $addr  = company_address_lines();
        $phone = trim((string) company_info('company_phone'));
        $room  = $max > 0 ? $max - ($phone !== '' ? 1 : 0) : 0;
        if ($room > 0 && count($addr) > $room) {
            $head   = array_slice($addr, 0, $room - 1);
            $head[] = implode(', ', array_slice($addr, $room - 1));
            $addr   = $head;
        }
        if ($phone !== '') $addr[] = $phone;
        return $addr;
    }
}

/** Alamat sebagai larik baris (sudah dibuang baris kosongnya). */
if (!function_exists('company_address_lines')) {
    function company_address_lines() {
        $lines = preg_split('/\r\n|\r|\n/', (string) company_info('company_address'));
        return array_values(array_filter(array_map('trim', $lines), function ($l) { return $l !== ''; }));
    }
}

/**
 * Alamat siap tempel ke HTML (sudah di-escape, newline jadi <br>).
 * Dipakai kop/kaki PDF -- Dompdf & Mpdf dua-duanya menerima <br> biasa.
 */
if (!function_exists('company_address_html')) {
    function company_address_html($separator = '<br>') {
        return implode($separator, array_map(function ($l) {
            return htmlspecialchars($l, ENT_QUOTES, 'UTF-8');
        }, company_address_lines()));
    }
}
