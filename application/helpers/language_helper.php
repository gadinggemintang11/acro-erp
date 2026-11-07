<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Language Helper
 * Helper function untuk multi-language support
 */

if (!function_exists('lang_line')) {
    /**
     * Get translated text.
     *
     * "Developer Mode" (ERP Revision Notes, user 2026-09-03): sebelum fallback
     * ke app_lang.php seperti biasa, dicek dulu apakah key ini punya
     * `custom_value` di tabel `lang_overrides` (diedit lewat menu Developer
     * Mode, tanpa perlu developer sentuh kode/deploy ulang). Override
     * di-load SEKALI per request (bukan query per pemanggilan lang_line() —
     * bisa ratusan kali per halaman) dan disimpan di static array.
     *
     * Auto-discovery: key yang BELUM PERNAH tercatat otomatis didaftarkan
     * (custom_value NULL, cuma "diketahui") ke SEMUA bahasa sekaligus, jadi
     * daftar key di menu Developer Mode kelengkapin sendiri seiring
     * halaman-halaman kepakai — tidak perlu scan manual. Kalau tabelnya
     * belum ada (migration belum jalan) atau ada error DB, diam-diam
     * di-skip & fallback ke perilaku lama — jangan sampai fitur ini bikin
     * seluruh app down gara-gara 1 tabel.
     *
     * @param string $key Language key
     * @param string $default Default text if key not found
     * @return string Translated text
     */
    function lang_line($key, $default = '') {
        $CI =& get_instance();

        static $overrides   = null; // [lang_key => custom_value] utk bahasa aktif saat ini
        static $known_keys  = null; // [lang_key => true] semua key yg sudah terdaftar (bahasa manapun)
        static $table_ready = null;
        static $cached_lang = null;

        $active_lang = get_current_language();

        // Reset cache kalau somehow bahasa aktif berubah di tengah request
        // (mis. baru ganti bahasa) — jarang terjadi tapi jaga-jaga.
        if ($cached_lang !== $active_lang) {
            $overrides   = null;
            $known_keys  = null;
            $cached_lang = $active_lang;
        }

        if ($overrides === null) {
            $overrides  = [];
            $known_keys = [];
            if ($table_ready === null) {
                try {
                    $table_ready = $CI->db->table_exists('lang_overrides');
                } catch (\Throwable $e) {
                    $table_ready = false;
                }
            }
            if ($table_ready) {
                try {
                    $rows = $CI->db->select('lang_key, language, custom_value')
                        ->get('lang_overrides')->result();
                    foreach ($rows as $r) {
                        $known_keys[$r->lang_key] = true;
                        if ($r->language === $active_lang && $r->custom_value !== null && $r->custom_value !== '') {
                            $overrides[$r->lang_key] = $r->custom_value;
                        }
                    }
                } catch (\Throwable $e) {
                    // Diam-diam skip - jangan bikin seluruh app error gara2 ini.
                }
            }
        }

        // Auto-discovery: key baru -> daftarkan ke 3 bahasa sekaligus.
        if ($table_ready && !isset($known_keys[$key])) {
            $known_keys[$key] = true;
            try {
                $CI->db->query(
                    "INSERT IGNORE INTO lang_overrides (lang_key, language, default_value, created_at)
                     VALUES (?, 'indonesian', ?, NOW()), (?, 'english', ?, NOW()), (?, 'chinese', ?, NOW())",
                    [$key, $default, $key, $default, $key, $default]
                );
            } catch (\Throwable $e) {
                // Diam-diam skip.
            }
        }

        if (isset($overrides[$key])) {
            $final = $overrides[$key];
        } else {
            $text  = $CI->lang->line($key);
            $final = $text ? $text : ($default ? $default : $key);
        }

        // Rekam urutan pemanggilan lang_line() di halaman INI (bukan semua
        // key di seluruh app — cuma yang kepakai di request sekarang), dipakai
        // overlay "Developer Mode: klik teks langsung di halaman" (footer.php)
        // buat mencocokkan teks yang KELIHATAN di DOM balik ke key aslinya.
        // Ringan (array in-memory doang, tidak nulis DB) & tidak mengubah
        // return value sama sekali — aman dipanggil terus tanpa syarat.
        _devmode_page_calls_store('add', [$key, $final]);

        return $final;
    }
}

if (!function_exists('_devmode_page_calls_store')) {
    /**
     * Static storage BERBEDA dari lang_line() sendiri (sengaja fungsi
     * terpisah) supaya "baca" (dari get_devmode_page_calls()) tidak perlu
     * lewat lang_line() lagi (yang punya efek samping: override-lookup,
     * auto-discovery insert, dst — tidak cocok dipakai cuma buat baca state).
     */
    function _devmode_page_calls_store($op, $item = null) {
        static $calls = [];
        if ($op === 'add') {
            $calls[] = $item;
            return null;
        }
        return $calls; // $op === 'get'
    }
}

if (!function_exists('get_devmode_page_calls')) {
    /**
     * Ambil semua pasangan [key, teks-yang-ditampilkan] yang sudah kepanggil
     * lewat lang_line() sejauh ini di request/halaman SEKARANG, urut sesuai
     * urutan render. Dipanggil dari templates/footer.php (paling akhir,
     * setelah semua view lain selesai) buat bekali overlay Developer Mode
     * sisi browser.
     */
    function get_devmode_page_calls() {
        return _devmode_page_calls_store('get');
    }
}

/**
 * ============================================================
 * MODE DEVELOPER MODE (user 2026-09-06)
 * ------------------------------------------------------------
 * Dulu: begitu Developer Mode dinyalakan, overlay "klik teks untuk edit"
 * LANGSUNG aktif di seluruh halaman — bikin fitur lain (mis. tombol "Atur
 * Kolom") susah dipakai karena kliknya keburu ditangkap overlay teks. User:
 * "seharusnya buat tampilan dulu dia mau pilih edit apa, baru kalau edit
 * teks ya teksnya, kalau atur kolom ya kolomnya... supaya saat mau edit
 * kolom tidak terganggu mode edit teks."
 *
 * Sekarang: Developer Mode nyala = BELUM ada mode aktif ('' / none). User
 * memilih dulu di banner mau mengedit apa. Hanya SATU mode inline yang boleh
 * aktif dalam satu waktu.
 *
 * Mode yang ada:
 *   ''       belum memilih — tidak ada overlay apa pun (default saat nyala)
 *   'text'   overlay klik-teks-untuk-edit (templates/footer.php)
 *   'column' tombol & panel "Atur Kolom" di halaman-halaman list
 * ============================================================
 */
if (!function_exists('devmode_active')) {
    /** Developer Mode menyala (belum tentu ada mode yang dipilih). */
    function devmode_active() {
        $CI =& get_instance();
        return check_access('developer_mode', 'view')
            && (bool) $CI->session->userdata('devmode_active');
    }
}

if (!function_exists('devmode_mode')) {
    /** Mode inline yang sedang dipilih: '' | 'text' | 'column'. */
    function devmode_mode() {
        if (!devmode_active()) return '';
        $CI =& get_instance();
        $m = (string) $CI->session->userdata('devmode_mode');
        return in_array($m, ['text', 'column'], true) ? $m : '';
    }
}

if (!function_exists('devmode_is')) {
    /** True kalau mode inline yang diminta sedang aktif. */
    function devmode_is($mode) {
        return devmode_mode() === $mode;
    }
}

if (!function_exists('get_current_language')) {
    /**
     * Get current active language
     * 
     * @return string Language code
     */
    function get_current_language() {
        $CI =& get_instance();
        $lang = $CI->session->userdata('site_lang');
        if (!$lang) {
            $lang = isset($_COOKIE['site_lang']) ? $_COOKIE['site_lang'] : 'indonesian';
        }
        return $lang;
    }
}

if (!function_exists('get_language_name')) {
    /**
     * Get language display name
     * 
     * @param string $code Language code
     * @return string Language display name
     */
    function get_language_name($code) {
        $languages = [
            'indonesian' => 'Bahasa Indonesia',
            'english' => 'English',
            'chinese' => '中文'
        ];
        return isset($languages[$code]) ? $languages[$code] : $code;
    }
}

if (!function_exists('get_available_languages')) {
    /**
     * Get all available languages
     * 
     * @return array Available languages
     */
    function get_available_languages() {
        return [
            'indonesian' => [
                'code' => 'indonesian',
                'name' => 'Bahasa Indonesia',
                'flag' => '🇮🇩'
            ],
            'english' => [
                'code' => 'english',
                'name' => 'English',
                'flag' => '🇺🇸'
            ],
            'chinese' => [
                'code' => 'chinese',
                'name' => '中文',
                'flag' => '🇨🇳'
            ]
        ];
    }
}

if (!function_exists('translate_module_name')) {
    /**
     * Translate module name from database
     * 
     * @param string $module_name Module name from database
     * @return string Translated module name
     */
    function translate_module_name($module_name) {
        if (empty($module_name)) return $module_name;
        
        // Convert to lowercase and replace spaces with underscore for key
        $module_key = 'module_' . strtolower(str_replace(' ', '_', $module_name));
        
        // Get translation
        return lang_line($module_key, $module_name);
    }
}

if (!function_exists('translate_action')) {
    /**
     * Translate action from database
     * 
     * @param string $action Action from database
     * @return string Translated action
     */
    function translate_action($action) {
        if (empty($action)) return $action;
        
        // Convert to lowercase for key
        $action_key = 'action_' . strtolower($action);
        
        // Get translation
        return lang_line($action_key, $action);
    }
}
