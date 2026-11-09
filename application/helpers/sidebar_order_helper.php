<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Developer Mode" — atur urutan sidebar (user 2026-09-06: "sidebar bisa di
 * tentukan urutannya jika user merasa tidak sesuai"). Baca/tulis tabel
 * `sidebar_order` — pola auto-discovery SAMA seperti ui_columns/
 * lang_overrides (lihat ui_column_helper.php), TAPI beda titik pakai:
 * dipanggil SEKALI oleh templates/sidebar.php, bukan berulang per baris
 * tabel, krn sidebar cuma sekumpulan section tetap (bukan kolom dinamis
 * per halaman).
 *
 * Cara pakai di sidebar.php: tiap section menu utama (SEMUA 15 -- Order s/d
 * System Setup, cuma Dashboard yg fixed sbg "Home") DIBUNGKUS output
 * buffering (`ob_start()` sebelum `if ($canViewX)`, `ob_get_clean()`
 * ditampung ke $sections['key'] setelah `endif`) alih2 langsung di-echo,
 * lalu SEMUA section dilempar ke sidebar_render_ordered_sections() SEKALI
 * di titik paling akhir (setelah System Setup, sebelum tombol collapse
 * sidebar) -- jadi urutan RENDER bisa diatur tanpa mengubah urutan/isi HTML
 * tiap section itu sendiri sama sekali. Heading "Accounting & Finance"/
 * "Configuration" yg dulu misahin sebagian section ini SUDAH DIHAPUS dari
 * kode (bukan cuma disembunyikan) krn begitu section bisa saling tukar
 * posisi bebas, heading fixed di posisi lama jadi tidak akurat lagi.
 *
 * User 2026-09-06 (follow-up): "jangan cuma menu utama, tapi submenu juga"
 * -- utk ITU dipakai fungsi KEDUA di file ini, sidebar_render_ordered_items(),
 * yg beroperasi di level SATU DROPDOWN (bukan seluruh sidebar). Beda
 * mekanisme krn jumlah submenu (~150+ item di ~14 dropdown) terlalu banyak
 * utk dibungkus manual satu-satu spt section menu utama -- dipakai
 * DOMDocument utk MEMECAH otomatis HTML hasil render 1 dropdown jadi
 * "blok" (tiap `<a>` mandiri = 1 blok; tiap `<h6 class="collapse-header">`
 * + SEMUA `<a>` di bawahnya sampai `<h6>` berikutnya = 1 blok gabungan --
 * jadi grup berlabel spt "REPORT:"/"SETTING:" ikut pindah bareng isinya,
 * TIDAK bisa dipisah item-per-item lintas grup).
 */
/**
 * Peta section sidebar -> [kunci lang_line, teks default].
 *
 * SATU sumber untuk dua pemakai: sidebar.php (label menu utama) dan halaman
 * "Atur Urutan Sidebar" (tombol ganti teks, user 2026-09-16). Dulu daftarnya
 * cuma ada di sidebar.php, jadi halaman pengatur tidak tahu kunci teksnya.
 */
if (!function_exists('sidebar_section_lang_keys')) {
    function sidebar_section_lang_keys() {
        return [
            'order'            => ['sidebar_ipo_number', 'Sales Order Number'],
            'permintaan'       => ['sidebar_permintaan_barang', 'Permintaan Barang'],
            'produksi'         => ['sidebar_production', 'Production'],
            'warehouse'        => ['sidebar_warehouse', 'Warehouse'],
            'purchase'         => ['sidebar_purchase', 'Purchasing'],
            'qa'               => ['sidebar_quality_assurance_group', 'Quality Control & Assurances'],
            'fg'               => ['sidebar_finished_goods', 'Finished Goods'],
            'exim'             => ['sidebar_shipping_transport', 'Shipping & Transportation'],
            'scrap'            => ['sidebar_scrap_group', 'Scrap'],
            'subkon'           => ['sidebar_subcon', 'Subkon'],
            'it_inventory'     => ['sidebar_it_inventory', 'IT Inventory [BC]'],
            // Finance & Accounting dipecah jadi 6 menu (2026-11-09); 'accounting' = Kas, Bank & Jurnal.
            'acc_fund'         => ['sidebar_acc_fund', 'Pengajuan Dana'],
            'accounting'       => ['sidebar_acc_ledger', 'Kas, Bank & Jurnal'],
            'acc_apar'         => ['sidebar_acc_apar', 'Hutang & Piutang'],
            'acc_report'       => ['sidebar_acc_report', 'Laporan Keuangan'],
            'acc_stock'        => ['sidebar_acc_stock', 'Stock Opname'],
            'acc_setting'      => ['sidebar_acc_setting', 'Pengaturan Akuntansi'],
            'master'           => ['sidebar_master_database', 'Master Database'],
            'document_sharing' => ['sidebar_document_sharing', 'Document Sharing'],
            'system'           => ['sidebar_system_setup', 'System Setup'],
        ];
    }
}

if (!function_exists('sidebar_section_groups')) {
    /**
     * Kelompok heading sidebar (redesign 2026-11-09, user: "header masih cuma
     * operasi inti harusnya di pisah beberapa, accounting juga harusnya di
     * pisah"). Urutan KELOMPOK tetap di sini; urutan section DI DALAM tiap
     * kelompok tetap mengikuti "Atur Urutan Sidebar" (Developer Mode).
     * Section yang tidak terdaftar di kelompok mana pun masuk kelompok terakhir.
     *
     * @return array group_key => [lang_key, default_label, icon, [section_key...]]
     */
    function sidebar_section_groups() {
        return [
            'sales_prod' => ['sidebar_grp_sales_prod', 'Penjualan & Produksi', 'fa-industry',  ['order', 'produksi', 'fg', 'exim']],
            'supply'     => ['sidebar_grp_supply',     'Pengadaan & Gudang',   'fa-boxes',     ['permintaan', 'purchase', 'warehouse', 'qa', 'scrap', 'subkon']],
            'customs'    => ['sidebar_grp_customs',    'Kepabeanan',           'fa-landmark',  ['it_inventory']],
            'finance'    => ['sidebar_grp_finance',    'Keuangan & Akuntansi',             'fa-coins',     ['acc_fund', 'accounting', 'acc_apar', 'acc_report', 'acc_stock', 'acc_setting']],
            'admin'      => ['sidebar_grp_admin',      'Data & Sistem',        'fa-cogs',      ['master', 'document_sharing', 'system']],
        ];
    }
}

if (!function_exists('sidebar_render_grouped_sections')) {
    /**
     * Seperti sidebar_render_ordered_sections(), tapi diberi heading per
     * kelompok (sidebar_section_groups()). Heading hanya tampil kalau minimal
     * satu section di kelompok itu tampil untuk user ybs (punya akses & tidak
     * dicopot di Developer Mode).
     */
    function sidebar_render_grouped_sections(array $sections, array $labels = []) {
        // Urutan + filter "dicopot" tetap lewat fungsi lama: pecah lagi per section.
        $ordered_keys = [];
        $marker = '<!--sbsec:%s-->';
        $tagged = [];
        foreach ($sections as $k => $html) $tagged[$k] = sprintf($marker, $k) . $html;
        $flat = sidebar_render_ordered_sections($tagged, $labels);
        if (preg_match_all('/<!--sbsec:([a-z0-9_]+)-->/', $flat, $m)) $ordered_keys = $m[1];

        $groups   = sidebar_section_groups();
        $assigned = [];
        foreach ($groups as $g) foreach ($g[3] as $sk) $assigned[$sk] = true;
        $last = array_key_last($groups);

        $out = '';
        foreach ($groups as $gk => $g) {
            $html = '';
            foreach ($ordered_keys as $sk) {
                $in_group = in_array($sk, $g[3], true) || ($gk === $last && !isset($assigned[$sk]));
                if (!$in_group) continue;
                if (trim(strip_tags($sections[$sk] ?? '', '<a><li>')) === '') continue;
                $html .= $sections[$sk];
            }
            if ($html === '') continue;
            $out .= '<div class="sidebar-heading sb-group" data-group="' . $gk . '"><span>'
                  . htmlspecialchars(lang_line($g[0], $g[1])) . '</span></div>' . $html;
        }
        return $out;
    }
}

if (!function_exists('sidebar_render_ordered_sections')) {
    /**
     * @param array $sections Peta section_key => HTML (string kosong kalau
     *                        section itu sedang tidak tampil ke user ybs,
     *                        mis. tidak punya akses -- TETAP disertakan di
     *                        array ini apa adanya, cuma isinya kosong).
     * @param array $labels   Peta section_key => label manusiawi (utk kolom
     *                        section_label di halaman kelola, BUKAN dipakai
     *                        saat render sidebar itu sendiri).
     * @return string HTML gabungan, urut sesuai sidebar_order.sort_order.
     */
    function sidebar_render_ordered_sections(array $sections, array $labels = []) {
        // -------------------------------------------------------------------
        // RIWAYAT: fungsi ini sempat DIMATIKAN 2026-09-06 (bypass pass-through)
        // waktu user lapor SEMUA link sidebar jadi "about:blank#blocked".
        // DIHIDUPKAN LAGI 2026-09-06 setelah: (a) user konfirmasi masalahnya
        // sudah beres, (b) pipeline ini diuji ke sidebar sungguhan -- 113 link
        // sebelum == 113 link sesudah, href & atribut TIDAK ada yang berubah,
        // tidak ada target="_blank" yang tersuntik. Tersangka yang lebih
        // masuk akal (sesuai kronologi user "waktu atur sidebar, Developer
        // Mode saya offkan") adalah overlay klik-untuk-edit Developer Mode
        // yang membungkus teks sidebar -- itu sudah diperbaiki terpisah di
        // templates/footer.php (#accordionSidebar dikecualikan dari
        // wrapTextNodes()) dan perbaikannya MASIH terpasang.
        // Kalau gejalanya balik: cukup pasang lagi `return implode('', $sections);`
        // di sini + `return $html;` di sidebar_render_ordered_items().
        //
        // 2026-09-06 (percobaan ke-2): DIHIDUPKAN lalu gejala about:blank#blocked
        // LANGSUNG BALIK -> DIMATIKAN LAGI, DAN AKAR MASALAHNYA AKHIRNYA
        // KETEMU: DOMDocument::saveHTML() meng-encode kurung siku host IPv6
        // (http://[::1]/ -> http://%5B::1%5D/), bikin URL tidak sah. Sudah
        // diperbaiki di sidebar_save_html(); fitur dihidupkan lagi.
        // -------------------------------------------------------------------
        $CI =& get_instance();

        static $table_ready = null;
        if ($table_ready === null) {
            try {
                $table_ready = $CI->db->table_exists('sidebar_order');
            } catch (\Throwable $e) {
                $table_ready = false;
            }
        }
        // Migration belum jalan di environment ini -> fallback urutan asli
        // di kode (array PHP biasa, insertion order == urutan deklarasi).
        if (!$table_ready) {
            return implode('', $sections);
        }

        try {
            $rows = $CI->db->select('section_key, sort_order')->get('sidebar_order')->result();
        } catch (\Throwable $e) {
            return implode('', $sections);
        }

        $known = [];
        $max_order = -10;
        foreach ($rows as $r) {
            $known[$r->section_key] = (int) $r->sort_order;
            if ((int) $r->sort_order > $max_order) $max_order = (int) $r->sort_order;
        }

        // Auto-discovery: section baru (developer nambah menu baru lewat
        // kode) yg belum terdaftar -> daftarkan di URUTAN PALING BELAKANG
        // (bukan menyisip di tengah tanpa sepengetahuan admin).
        foreach (array_keys($sections) as $key) {
            if (!isset($known[$key])) {
                $max_order += 10;
                $known[$key] = $max_order;
                try {
                    $CI->db->query(
                        "INSERT IGNORE INTO sidebar_order (section_key, section_label, sort_order, created_at) VALUES (?, ?, ?, NOW())",
                        [$key, $labels[$key] ?? $key, $max_order]
                    );
                } catch (\Throwable $e) {
                    // Diam-diam skip -- urutan fallback tetap benar di render ini
                    // (dari $known di memori), cuma belum tersimpan ke DB.
                }
            }
        }

        // Urutkan key yang ADA di $sections (bukan semua baris sidebar_order --
        // kalau ada section yg dihapus dari kode, barisnya di DB dibiarkan
        // menganggur, tidak bikin baris kosong nge-render).
        $keys = array_keys($sections);

        // "Dicopot" admin -> tidak ikut dirender sama sekali (lihat
        // sidebar_hidden_keys()). Barisnya TETAP ada di DB, jadi bisa
        // dipasang lagi kapan saja dari Developer Mode.
        $hidden = sidebar_hidden_keys('sidebar_order', 'section_key');
        if (!empty($hidden)) {
            $keys = array_values(array_filter($keys, function ($k) use ($hidden) {
                return !isset($hidden[$k]);
            }));
        }

        usort($keys, function ($a, $b) use ($known) {
            return ($known[$a] ?? 0) <=> ($known[$b] ?? 0);
        });

        $html = '';
        foreach ($keys as $k) {
            $html .= $sections[$k];
        }
        return $html;
    }
}

/**
 * Pecah HTML hasil render 1 dropdown (isi `.collapse-inner`, SUDAH lolos
 * semua pengecekan check_access() -- yg tidak tampil ke user ybs otomatis
 * sudah tidak ada di HTML ini sama sekali) jadi "blok" reorderable:
 * - Tiap `<a>` mandiri (tidak didahului `<h6>` yg masih "terbuka") = 1 blok.
 * - Tiap `<h6 class="collapse-header">` MEMBUKA blok GRUP yang menyerap
 *   SEMUA `<a>` (atau elemen lain) sesudahnya sampai `<h6>` berikutnya.
 *   Blok grup py 3-LEVEL info terpisah (user 2026-09-06: "masih ada sub
 *   lagi" -- 1 level "grup" saja TIDAK CUKUP, item DI DALAM 1 grup mis.
 *   "FINANCE" jg harus bisa diatur urutannya sendiri2, bukan cuma ikut
 *   grupnya doang):
 *     'h6_html'      -- HTML tag <h6> itu sendiri SAJA (dipakai render ulang
 *                        di depan, apa pun urutan child-nya).
 *     'children'     -- array child block (`<a>` di dalam grup ini) dgn
 *                        bentuk SAMA persis kayak blok mandiri (key+html+label)
 *                        -- reorderable independen dari grup lain manapun.
 *     'html'         -- HTML GABUNGAN (h6 + semua children apa adanya, urutan
 *                        ASLI) -- dipakai fallback/kalau tidak ada override
 *                        level-3 tersimpan.
 * Text node (whitespace antar tag) dilampirkan ke blok/child yg lagi
 * terbuka, BUKAN dibuang, supaya spacing HTML asli tidak berubah.
 *
 * @param string $html HTML mentah 1 dropdown (atau isi 1 grup kalau dipanggil
 *                      secara rekursif dari sidebar_render_ordered_items()).
 * @param string $base Nilai `base_url()` (mis. "http://localhost/acro-erp/")
 *                      SAAT render ini terjadi -- di-strip dari href SEBELUM
 *                      di-hash jadi key, supaya key STABIL lintas environment
 *                      (localhost vs IP vs domain production semua
 *                      menghasilkan key yg SAMA utk item yg sama -- BUG
 *                      ditemukan user 2026-09-06: tanpa ini, key ikut
 *                      berubah tiap base_url beda, data jadi "pecah").
 * @return array Daftar blok URUT SESUAI HTML ASLI.
 */
/**
 * Bungkus DOMDocument::saveHTML() yang MEMPERBAIKI host IPv6.
 *
 * AKAR MASALAH `about:blank#blocked` (2026-09-06, dua kali bikin SEMUA link
 * sidebar mati): `saveHTML()` meng-URL-encode kurung siku host IPv6 —
 *     http://[::1]/acro-erp/x   ->   http://%5B::1%5D/acro-erp/x
 * `%5B::1%5D` bukan hostname yang sah, jadi Chrome menolak menavigasinya dan
 * membuka tab kosong `about:blank#blocked`. Semua link sidebar lewat sini,
 * makanya semuanya mati sekaligus; menu lain (dashboard dll) aman karena
 * tidak melewati pipeline ini.
 *
 * Kenapa lolos dari semua pengujian sebelumnya: tes memakai `http://localhost/`
 * (tanpa kurung siku) dan membandingkan `getAttribute('href')` — yang justru
 * TETAP BENAR; cuma hasil serialisasi `saveHTML()` yang rusak.
 *
 * Perbaikannya sengaja SEMPIT: cuma pola host IPv6 ter-encode tepat setelah
 * skema (`http://%5B...%5D`). `%5B`/`%5D` di query string TIDAK disentuh.
 */
if (!function_exists('sidebar_save_html')) {
    function sidebar_save_html(\DOMDocument $doc, \DOMNode $node) {
        $html = $doc->saveHTML($node);
        if ($html === false) return '';
        return preg_replace('#(https?://)%5B([0-9A-Fa-f:.]+)%5D#', '$1[$2]', $html);
    }
}

/**
 * Daftar key yang DISEMBUNYIKAN ("dicopot") admin lewat Developer Mode.
 * Ditambahkan 2026-09-07 (user: "harusnya bisa copot dan pasang menu sidebar").
 *
 * MURNI TAMPILAN — menu yang disembunyikan cuma hilang dari sidebar; URL-nya
 * tetap bisa diakses langsung dan izin Role Access TIDAK berubah. Untuk
 * benar-benar mencabut akses, pakai halaman Role Access.
 *
 * Kolom `is_hidden` baru ada setelah migration_sidebar_hidden.sql. Kalau
 * migrasinya belum jalan, fungsi ini mengembalikan array kosong = semua menu
 * tampil (perilaku lama), bukan error.
 *
 * @param string $table 'sidebar_order' | 'sidebar_item_order'
 * @param string $key_col 'section_key' | 'item_key'
 * @param string|null $scope utk item: batasi ke 1 dropdown_key
 * @return array key => true
 */
if (!function_exists('sidebar_hidden_keys')) {
    function sidebar_hidden_keys($table, $key_col, $scope = null) {
        static $cache = [];
        $ck = $table . '|' . (string) $scope;
        if (isset($cache[$ck])) return $cache[$ck];

        $CI =& get_instance();
        if (!function_exists('db_has_column') || !db_has_column($table, 'is_hidden')) {
            return $cache[$ck] = [];
        }
        try {
            $CI->db->select($key_col)->where('is_hidden', 1);
            if ($scope !== null) $CI->db->where('dropdown_key', $scope);
            $rows = $CI->db->get($table)->result();
        } catch (\Throwable $e) {
            return $cache[$ck] = [];
        }
        $out = [];
        foreach ($rows as $r) $out[$r->{$key_col}] = true;
        return $cache[$ck] = $out;
    }
}

if (!function_exists('sidebar_split_into_blocks')) {
    function sidebar_split_into_blocks($html, $base = '') {
        if (trim($html) === '') return [];

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        // <?xml encoding> trick supaya DOMDocument baca sbg UTF-8 (default-nya
        // ISO-8859-1, bikin karakter non-ASCII di label rusak).
        $ok = $doc->loadHTML('<?xml encoding="utf-8" ?><div id="__sidebar_root">' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        if (!$ok) return [['key' => '_all', 'html' => $html]]; // parse gagal -> fallback tampilkan apa adanya, jangan sampai hilang

        $root = $doc->getElementById('__sidebar_root');
        if (!$root) return [['key' => '_all', 'html' => $html]];

        // Bikin 1 "child block" dari 1 node standalone (dipakai baik utk item
        // di luar grup manapun MAUPUN item di dalam 1 grup -- bentuknya sama).
        $make_child = function ($node) use ($doc, $base) {
            $tag = strtolower($node->nodeName);
            $href = ($tag === 'a') ? $node->getAttribute('href') : sidebar_save_html($doc, $node);
            if ($base !== '' && strpos($href, $base) === 0) {
                $href = substr($href, strlen($base));
            }
            return [
                'key'   => 'item_' . substr(md5($href), 0, 12),
                'html'  => sidebar_save_html($doc, $node),
                'label' => trim($node->textContent),
                // Kunci lang_line label menu ini (atribut data-lk di sidebar.php),
                // dipakai halaman "Atur Urutan Sidebar" untuk mengganti teksnya
                // (user 2026-09-16). Kosong utk elemen yang bukan <a> menu.
                'lang_key' => ($tag === 'a') ? $node->getAttribute('data-lk') : '',
            ];
        };

        $blocks = [];
        $current = null; // ['key','html','label','is_group', + kalau grup: 'h6_html','children']

        foreach ($root->childNodes as $node) {
            if ($node->nodeType === XML_TEXT_NODE || $node->nodeType === XML_COMMENT_NODE) {
                if ($current !== null) $current['html'] .= sidebar_save_html($doc, $node);
                continue;
            }
            if ($node->nodeType !== XML_ELEMENT_NODE) continue;

            $tag = strtolower($node->nodeName);
            if ($tag === 'h6') {
                if ($current !== null) $blocks[] = $current;
                $label = trim($node->textContent);
                // Key group PRIORITAS pakai data-gk (lang_line KEY, ditambahkan
                // di tiap <h6> sidebar.php -- bahasa-independent) -- fallback
                // ke hash TEKS label kalau atribut itu somehow tidak ada
                // (mis. h6 baru yg lupa dikasih data-gk). BUG ditemukan user
                // 2026-09-06: key berbasis teks label BERUBAH tiap bahasa
                // user beda (Indonesia vs Inggris vs Chinese), bikin data
                // "pecah" sama persis spt masalah href/base_url di atas.
                $gk = $node->getAttribute('data-gk');
                $key = $gk !== '' ? ('grp_' . $gk) : ('grp_' . substr(md5($label), 0, 12));
                $h6_html = sidebar_save_html($doc, $node);
                $current = ['key' => $key, 'html' => $h6_html, 'is_group' => true, 'label' => $label, 'h6_html' => $h6_html, 'children' => [],
                            'lang_key' => $gk !== '' ? $gk : ''];
            } elseif ($current !== null && !empty($current['is_group'])) {
                // Group masih "terbuka" (belum ketemu <h6> berikutnya) -> serap
                // ke `html` (fallback gabungan, urutan asli) SEKALIGUS catat
                // sbg child tersendiri (level-3, reorderable independen).
                $current['html'] .= sidebar_save_html($doc, $node);
                $current['children'][] = $make_child($node);
            } else {
                // <a> (atau elemen lain) mandiri, di luar group manapun -> blok sendiri.
                if ($current !== null) $blocks[] = $current;
                $child = $make_child($node);
                $current = ['key' => $child['key'], 'html' => $child['html'], 'is_group' => false, 'label' => $child['label'],
                            'lang_key' => $child['lang_key'] ?? ''];
            }
        }
        if ($current !== null) $blocks[] = $current;

        return $blocks;
    }
}

/**
 * Ambil urutan tersimpan (key => sort_order) utk 1 "scope" dari
 * `sidebar_item_order` -- scope biasanya `dropdown_key` (level-2, submenu
 * dlm 1 dropdown) tapi BISA JUGA `dropdown_key::group_key` (level-3, item DI
 * DALAM 1 grup -- user 2026-09-06: "masih ada sub lagi", 1 level grup blok
 * TIDAK CUKUP utk sebagian dropdown, mis. isi "FINANCE" di Accounting jg
 * perlu diatur urutan sendiri2). Kolom DB TETAP `dropdown_key` apa adanya
 * (tidak nambah kolom baru) -- scope level-3 cuma STRING BEDA yg disimpan
 * di kolom yg sama, PK gabungan (dropdown_key,item_key) otomatis bikin
 * level-2 & level-3 tidak pernah bentrok satu sama lain.
 */
if (!function_exists('sidebar_fetch_order_map')) {
    function sidebar_fetch_order_map($scope_key) {
        $CI =& get_instance();
        static $table_ready = null;
        if ($table_ready === null) {
            try {
                $table_ready = $CI->db->table_exists('sidebar_item_order');
            } catch (\Throwable $e) {
                $table_ready = false;
            }
        }
        if (!$table_ready) return null;

        try {
            $rows = $CI->db->select('item_key, sort_order')->where('dropdown_key', $scope_key)->get('sidebar_item_order')->result();
        } catch (\Throwable $e) {
            return null;
        }

        $known = [];
        foreach ($rows as $r) { $known[$r->item_key] = (int) $r->sort_order; }
        return $known;
    }
}

/**
 * Urutkan $blocks (array key=>html=>label, dari sidebar_split_into_blocks())
 * sesuai $known (peta item_key=>sort_order dari sidebar_fetch_order_map()),
 * SEKALIGUS auto-discovery blok baru yg belum terdaftar (ditaruh paling
 * belakang). $scope_key dipakai cuma utk INSERT baris baru.
 *
 * @return array $blocks yg SUDAH terurut (in-place order, bukan cuma peta).
 */
if (!function_exists('sidebar_apply_block_order')) {
    function sidebar_apply_block_order($scope_key, array $blocks, array $known) {
        $CI =& get_instance();
        $max_order = -10;
        foreach ($known as $order) { if ($order > $max_order) $max_order = $order; }

        // Kolom lang_key baru ada setelah migration_sidebar_lang_key.sql.
        static $has_lang_key = null;
        if ($has_lang_key === null) {
            $has_lang_key = function_exists('db_has_column') ? db_has_column('sidebar_item_order', 'lang_key') : false;
        }

        foreach ($blocks as $b) {
            if (!isset($known[$b['key']])) {
                $max_order += 10;
                $known[$b['key']] = $max_order;
                try {
                    if ($has_lang_key) {
                        $CI->db->query(
                            "INSERT IGNORE INTO sidebar_item_order (dropdown_key, item_key, item_label, lang_key, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                            [$scope_key, $b['key'], $b['label'], ($b['lang_key'] ?? '') ?: null, $max_order]
                        );
                    } else {
                        $CI->db->query(
                            "INSERT IGNORE INTO sidebar_item_order (dropdown_key, item_key, item_label, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())",
                            [$scope_key, $b['key'], $b['label'], $max_order]
                        );
                    }
                } catch (\Throwable $e) {
                    // Diam-diam skip, urutan fallback dari $known tetap benar di render ini.
                }
            } elseif ($has_lang_key && !empty($b['lang_key'])) {
                // Baris lama (dibuat sebelum kolom ini ada) dilengkapi sekali jalan.
                try {
                    $CI->db->query(
                        "UPDATE sidebar_item_order SET lang_key = ? WHERE dropdown_key = ? AND item_key = ? AND (lang_key IS NULL OR lang_key = '')",
                        [$b['lang_key'], $scope_key, $b['key']]
                    );
                } catch (\Throwable $e) {
                    // Diam-diam skip.
                }
            }
        }

        usort($blocks, function ($a, $b) use ($known) {
            return ($known[$a['key']] ?? 0) <=> ($known[$b['key']] ?? 0);
        });
        return $blocks;
    }
}

/**
 * Peta "item ini sebenarnya milik segmen mana" untuk 1 dropdown
 * (`sidebar_item_parent`, user 2026-09-16: "isi menu segment itu bisa di
 * pindah antar segment").
 *
 * @return array|null [item_key => parent_key] ('' = tanpa segmen);
 *                    null kalau tabelnya belum ada (migrasi belum jalan).
 */
if (!function_exists('sidebar_fetch_parent_map')) {
    function sidebar_fetch_parent_map($dropdown_key) {
        static $table_ready = null;
        $CI =& get_instance();
        if ($table_ready === null) {
            try { $table_ready = $CI->db->table_exists('sidebar_item_parent'); }
            catch (\Throwable $e) { $table_ready = false; }
        }
        if (!$table_ready) return null;
        try {
            $rows = $CI->db->select('item_key, parent_key')->where('dropdown_key', $dropdown_key)->get('sidebar_item_parent')->result();
        } catch (\Throwable $e) {
            return null;
        }
        $map = [];
        foreach ($rows as $r) { $map[$r->item_key] = (string) $r->parent_key; }
        return $map;
    }
}

/**
 * Pindahkan item ke segmen tujuannya sesuai $moves.
 *
 * Segmen asal sebuah item ditentukan struktur HTML sidebar.php; fungsi ini
 * yang membuat "Daftar PRF" (aslinya di bawah <h6>LAPORAN:) bisa muncul di
 * dalam segmen KEUANGAN, atau keluar dari segmen mana pun ($target '').
 * Segmen yang isinya habis dibuang seluruhnya supaya tidak menyisakan judul
 * segmen kosong di sidebar.
 *
 * @param array $blocks hasil sidebar_split_into_blocks()
 * @param array $moves  [item_key => parent_key]
 * @return array blocks baru
 */
if (!function_exists('sidebar_apply_parent_moves')) {
    function sidebar_apply_parent_moves(array $blocks, array $moves) {
        if (empty($moves)) return $blocks;

        $group_index = [];
        foreach ($blocks as $i => $b) {
            if (!empty($b['is_group'])) $group_index[$b['key']] = $i;
        }

        $to_top = [];   // item yang dikeluarkan dari segmen mana pun
        $pending = [];  // [group_key => [item, ...]] item yang masuk ke segmen lain

        // Cabut dulu semua item yang punya tujuan berbeda dari tempatnya sekarang.
        foreach ($blocks as $i => $b) {
            if (!empty($b['is_group'])) {
                $keep = [];
                foreach ($b['children'] as $c) {
                    $target = $moves[$c['key']] ?? null;
                    if ($target === null || $target === $b['key']) { $keep[] = $c; continue; }
                    if ($target === '') { $to_top[] = $c; }
                    elseif (isset($group_index[$target])) { $pending[$target][] = $c; }
                    else { $keep[] = $c; } // segmen tujuan tidak ada di dropdown ini (mis. user tidak punya aksesnya)
                }
                $blocks[$i]['children'] = $keep;
            } else {
                $target = $moves[$b['key']] ?? null;
                if ($target !== null && $target !== '' && isset($group_index[$target])) {
                    $pending[$target][] = ['key' => $b['key'], 'html' => $b['html'], 'label' => $b['label']];
                    $blocks[$i]['_removed'] = true;
                }
            }
        }

        // Baru dimasukkan ke tujuannya (dua tahap supaya item yang saling
        // bertukar segmen tidak ikut terbawa lagi di tahap pencabutan).
        foreach ($pending as $group_key => $items) {
            $i = $group_index[$group_key];
            $blocks[$i]['children'] = array_merge($blocks[$i]['children'], $items);
        }

        $out = [];
        foreach ($blocks as $b) {
            if (!empty($b['_removed'])) continue;
            // Segmen tanpa isi tidak perlu ditampilkan (judulnya jadi menggantung).
            if (!empty($b['is_group']) && empty($b['children'])) continue;
            $out[] = $b;
        }
        foreach ($to_top as $c) {
            $out[] = ['key' => $c['key'], 'html' => $c['html'], 'is_group' => false, 'label' => $c['label']];
        }
        return $out;
    }
}

/**
 * Render ULANG isi 1 dropdown sesuai urutan custom tersimpan di
 * `sidebar_item_order` -- 2 LEVEL sekaligus (user 2026-09-06, follow-up
 * "masih ada sub lagi"):
 *   Level 2: urutan blok (grup ATAU item mandiri) di dalam dropdown ini,
 *            scope = $dropdown_key.
 *   Level 3: KALAU 1 blok itu grup (py `children`, mis. isi "FINANCE"),
 *            urutan item DI DALAM grup itu SENDIRI, scope =
 *            "$dropdown_key::$group_key" -- independen dari grup lain
 *            manapun (isi "ACCOUNTING" diatur terpisah dari isi "FINANCE").
 * Kedua level pakai tabel & mekanisme SAMA (sidebar_fetch_order_map() +
 * sidebar_apply_block_order()), cuma beda scope_key -- rekursif secara
 * konsep walau di sini cuma 1 tingkat kedalaman children (grup TIDAK bisa
 * berisi grup lain lagi, sesuai struktur nyata sidebar.php).
 *
 * @param string $dropdown_key Key stabil per dropdown (mis. 'purchase').
 * @param string $html         HTML mentah hasil ob_get_clean() dari isi
 *                              `.collapse-inner` dropdown itu.
 * @param string $base         base_url() saat ini -- diteruskan ke
 *                              sidebar_split_into_blocks() spy key stabil
 *                              lintas environment (lihat docblock di sana).
 */
if (!function_exists('sidebar_render_ordered_items')) {
    function sidebar_render_ordered_items($dropdown_key, $html, $base = '') {
        // Aktif kembali setelah akar masalah IPv6 diperbaiki di
        // sidebar_save_html() -- lihat catatan di sidebar_render_ordered_sections().
        $blocks = sidebar_split_into_blocks($html, $base);
        if (empty($blocks)) return $html;

        $known = sidebar_fetch_order_map($dropdown_key);
        if ($known === null) return $html; // tabel belum ada -- fallback urutan asli

        // Pindah antar segmen (2026-09-16) dijalankan PALING AWAL supaya
        // pengurutan level-3 di bawah sudah melihat isi segmen yang final.
        $moves = sidebar_fetch_parent_map($dropdown_key);
        if (!empty($moves)) $blocks = sidebar_apply_parent_moves($blocks, $moves);

        $hidden = sidebar_hidden_keys('sidebar_item_order', 'item_key', $dropdown_key);

        // Level 3 dulu (per grup) -- SEBELUM level 2, supaya urutan grup itu
        // sendiri di level 2 tidak terganggu oleh proses ini.
        foreach ($blocks as $i => $b) {
            // Grup berisi 1 item pun tetap disusun ulang HTML-nya: isinya bisa
            // saja berubah karena pindah segmen di atas.
            if (empty($b['is_group']) || empty($b['children'])) continue;
            $child_scope = $dropdown_key . '::' . $b['key'];
            $child_known = sidebar_fetch_order_map($child_scope);
            if ($child_known === null) $child_known = [];

            // Item DI DALAM grup yang "dicopot" ikut dibuang di sini
            // (scope-nya sendiri: "$dropdown_key::$group_key").
            $child_hidden = sidebar_hidden_keys('sidebar_item_order', 'item_key', $child_scope);
            $children = $b['children'];
            if (!empty($child_hidden)) {
                $children = array_values(array_filter($children, function ($c) use ($child_hidden) {
                    return !isset($child_hidden[$c['key']]);
                }));
            }

            $ordered_children = sidebar_apply_block_order($child_scope, $children, $child_known);
            $child_html = '';
            foreach ($ordered_children as $c) { $child_html .= $c['html']; }
            $blocks[$i]['html'] = $b['h6_html'] . $child_html;
        }

        // Blok level-2 (item mandiri ATAU grup utuh) yang "dicopot" dibuang.
        // Membuang blok GRUP otomatis membuang judul + seluruh isinya.
        if (!empty($hidden)) {
            $blocks = array_values(array_filter($blocks, function ($b) use ($hidden) {
                return !isset($hidden[$b['key']]);
            }));
            if (empty($blocks)) return '';
        }

        if (count($blocks) > 1) {
            $blocks = sidebar_apply_block_order($dropdown_key, $blocks, $known);
        }

        $html_out = '';
        foreach ($blocks as $b) { $html_out .= $b['html']; }
        return $html_out;
    }
}

/**
 * Daftar blok (key+label) 1 dropdown SESUAI URUTAN TERSIMPAN -- dipakai
 * halaman "Atur Urutan Sidebar" utk menampilkan drag-list per dropdown.
 * BEDA dari sidebar_render_ordered_items(): itu jalan tiap sidebar
 * dirender (butuh $html mentah); ini dipanggil dari admin page, cukup baca
 * DB apa adanya (kalau ada dropdown yg belum pernah ke-render sama sekali
 * -- mis. blm ada user yg buka menu itu -- hasilnya kosong, wajar, admin
 * akan lihat item2-nya begitu ada yg pernah buka dropdown itu min. 1x).
 */
if (!function_exists('sidebar_get_item_order')) {
    function sidebar_get_item_order($dropdown_key) {
        $CI =& get_instance();
        try {
            return $CI->db->where('dropdown_key', $dropdown_key)->order_by('sort_order', 'ASC')->get('sidebar_item_order')->result();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
