<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Developer Mode" — editor teks/terjemahan lewat UI (ERP Revision Notes,
 * user 2026-09-03). Baca/tulis tabel `lang_overrides` yang dipakai
 * `lang_line()` (application/helpers/language_helper.php) sebagai lapisan
 * override sebelum fallback ke app_lang.php.
 *
 * Tabel-nya 1 baris per (lang_key, language) - method di sini nge-group
 * jadi 1 baris per KEY dengan 3 sub-field (indonesian/english/chinese)
 * supaya gampang dirender sebagai 1 tabel 3-kolom di UI.
 */
class Developer_mode_model extends CI_Model {

    const LANGUAGES = ['indonesian', 'english', 'chinese'];

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Modul = prefix key sebelum underscore pertama (mis. "sidebar" dari
     * "sidebar_dashboard") - dipakai buat dropdown filter, ngebantu nyari
     * key yang relevan tanpa harus tahu nama persisnya.
     */
    public function get_modules() {
        $rows = $this->db->query("
            SELECT DISTINCT SUBSTRING_INDEX(lang_key, '_', 1) AS prefix
            FROM lang_overrides
            ORDER BY prefix ASC
        ")->result();
        return array_map(function ($r) { return $r->prefix; }, $rows);
    }

    private function _build_key_filter($filters) {
        $where  = '';
        $params = [];

        if (!empty($filters['module'])) {
            $where .= " AND lang_key LIKE ?";
            $params[] = $filters['module'] . '\_%'; // escape literal underscore
        }
        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $where .= " AND (lang_key LIKE ? OR COALESCE(custom_value, default_value) LIKE ?)";
            $params[] = $term;
            $params[] = $term;
        }
        // Hanya key yang punya SETIDAKNYA 1 custom_value terisi.
        if (!empty($filters['customized_only'])) {
            $where .= " AND lang_key IN (SELECT lang_key FROM lang_overrides WHERE custom_value IS NOT NULL AND custom_value != '')";
        }

        return [$where, $params];
    }

    public function count_keys($filters = []) {
        list($where, $params) = $this->_build_key_filter($filters);
        $sql = "SELECT COUNT(DISTINCT lang_key) AS total FROM lang_overrides WHERE 1=1 $where";
        $row = $this->db->query($sql, $params)->row();
        return $row ? (int) $row->total : 0;
    }

    /**
     * Daftar key ter-paginasi, sudah di-group jadi 1 baris/key dengan
     * sub-field per bahasa: ->indonesian, ->english, ->chinese (masing2
     * object berisi custom_value/default_value/updated_at/updated_by_name).
     */
    public function get_grouped($filters = [], $limit = 50, $offset = 0) {
        list($where, $params) = $this->_build_key_filter($filters);
        $limit  = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $key_sql = "SELECT DISTINCT lang_key FROM lang_overrides WHERE 1=1 $where ORDER BY lang_key ASC LIMIT $limit OFFSET $offset";
        $keys = array_map(function ($r) { return $r->lang_key; }, $this->db->query($key_sql, $params)->result());
        if (empty($keys)) return [];

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $rows = $this->db->query("
            SELECT lo.*, u.fullname AS updated_by_name
            FROM lang_overrides lo
            LEFT JOIN users u ON u.id = lo.updated_by
            WHERE lo.lang_key IN ($placeholders)
        ", $keys)->result();

        $grouped = [];
        foreach ($keys as $k) {
            $grouped[$k] = (object) ['lang_key' => $k];
            foreach (self::LANGUAGES as $lg) {
                $grouped[$k]->$lg = (object) ['custom_value' => null, 'default_value' => null, 'updated_at' => null, 'updated_by_name' => null];
            }
        }
        foreach ($rows as $r) {
            if (isset($grouped[$r->lang_key]) && in_array($r->language, self::LANGUAGES, true)) {
                $grouped[$r->lang_key]->{$r->language} = $r;
            }
        }
        return array_values($grouped);
    }

    public function save_override($lang_key, $language, $value, $user_id) {
        if (!in_array($language, self::LANGUAGES, true)) return false;

        $exists = $this->db->where('lang_key', $lang_key)->where('language', $language)
            ->get('lang_overrides')->row();
        $data = [
            'custom_value' => ($value === '') ? null : $value,
            'updated_by'   => $user_id,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];
        if ($exists) {
            $this->db->where('id', $exists->id)->update('lang_overrides', $data);
        } else {
            // Edge case: key ada di 1 bahasa tapi belum ke-3nya (mis. baru
            // ditambah manual/data lama) - lengkapi baris yang kurang.
            $data['lang_key']  = $lang_key;
            $data['language']  = $language;
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->insert('lang_overrides', $data);
        }
        return true;
    }

    /** Reset 1 key utk 1 bahasa (balik ke default app_lang.php). */
    public function reset_override($lang_key, $language) {
        return $this->db->where('lang_key', $lang_key)->where('language', $language)
            ->update('lang_overrides', ['custom_value' => null, 'updated_by' => null, 'updated_at' => null]);
    }

    /**
     * Nilai AKTIF saat ini per bahasa (custom_value kalau ada, else nilai
     * asli dari app_lang.php, else default_value snapshot) - dipakai UI
     * biar kelihatan "efeknya beneran apa" bukan cuma custom_value mentah.
     */
    // =====================================================================
    // Hide-kolom per list (ERP Revision Notes, user 2026-09-03) — baca/tulis
    // tabel `ui_columns` yang dipakai `ui_column_visible()`
    // (application/helpers/ui_column_helper.php). Lihat catatan lengkap di
    // migration_ui_columns.sql soal kenapa ini beda mekanisme dari teks.
    // =====================================================================

    /** Semua page_key yang sudah punya kolom terdaftar (dr halaman list mana pun yg sudah dipasangi). */
    public function get_column_pages() {
        $rows = $this->db->query("SELECT DISTINCT page_key FROM ui_columns ORDER BY page_key ASC")->result();
        return array_map(function ($r) { return $r->page_key; }, $rows);
    }

    /** Semua kolom terdaftar utk 1 halaman, urut sesuai id (= urutan auto-discovery = urutan tampil di tabel aslinya). */
    public function get_columns_for_page($page_key) {
        return $this->db->where('page_key', $page_key)->order_by('id', 'ASC')->get('ui_columns')->result();
    }

    public function set_column_hidden($page_key, $column_key, $is_hidden, $user_id) {
        return $this->db->where('page_key', $page_key)->where('column_key', $column_key)
            ->update('ui_columns', [
                'is_hidden'  => $is_hidden ? 1 : 0,
                'updated_by' => $user_id,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
    }

    // =====================================================================
    // Atur Urutan Sidebar (ERP Revision Notes, user 2026-09-06, + follow-up
    // "bukan cuma core operation, tapi semuanya baik menu utama & sub
    // menu") — baca/tulis 2 tabel:
    // `sidebar_order`      dipakai sidebar_render_ordered_sections() -- urutan
    //                      15 menu utama (Dashboard tetap fixed sbg "Home").
    // `sidebar_item_order` dipakai sidebar_render_ordered_items() -- urutan
    //                      submenu DI DALAM 1 dropdown (scoped per dropdown_key).
    // Keduanya di application/helpers/sidebar_order_helper.php. Lihat
    // migration_sidebar_order.sql utk detail lengkap.
    // =====================================================================

    /** Semua section terdaftar, urut sesuai sort_order (= urutan tampil sidebar saat ini). */
    public function get_sidebar_sections() {
        return $this->db->order_by('sort_order', 'ASC')->get('sidebar_order')->result();
    }

    /**
     * Simpan urutan baru sekaligus (drag-and-drop kirim SELURUH urutan
     * final, bukan 1 per 1) — sort_order ditulis ulang jadi 0,10,20,... sesuai
     * posisi di $ordered_keys supaya selalu ada gap utk sisipan manual nanti.
     */
    public function save_sidebar_order(array $ordered_keys, $user_id) {
        $this->db->trans_start();
        $i = 0;
        foreach ($ordered_keys as $key) {
            $key = trim((string) $key);
            if ($key === '') continue;
            $this->db->where('section_key', $key)->update('sidebar_order', [
                'sort_order' => $i * 10,
                'updated_by' => $user_id,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $i++;
        }
        $this->db->trans_complete();
        return $this->db->trans_status() !== false;
    }

    /**
     * "Copot / pasang" menu sidebar (user 2026-09-07). MURNI TAMPILAN —
     * menu yang dicopot cuma hilang dari sidebar; URL-nya tetap bisa diakses
     * dan izin Role Access TIDAK berubah.
     *
     * @param string $scope 'section' (menu utama) | 'item' (isi dropdown)
     * @param string $key   section_key ATAU item_key
     * @param bool   $hidden
     * @param string|null $dropdown_key wajib diisi kalau $scope='item'
     */
    public function set_hidden($scope, $key, $hidden, $user_id, $dropdown_key = null) {
        $table   = ($scope === 'section') ? 'sidebar_order' : 'sidebar_item_order';
        $key_col = ($scope === 'section') ? 'section_key'   : 'item_key';

        // Kolomnya baru ada setelah migration_sidebar_hidden.sql — kalau
        // migrasinya belum jalan, jangan bikin galat DB, cukup laporkan.
        if (!function_exists('db_has_column') || !db_has_column($table, 'is_hidden')) {
            return false;
        }

        $this->db->where($key_col, $key);
        if ($scope !== 'section' && $dropdown_key !== null) {
            $this->db->where('dropdown_key', $dropdown_key);
        }
        $this->db->update($table, [
            'is_hidden'  => $hidden ? 1 : 0,
            'updated_by' => $user_id,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /** Apakah fitur copot/pasang sudah bisa dipakai (migrasinya sudah jalan)? */
    public function hidden_feature_ready() {
        return function_exists('db_has_column')
            && db_has_column('sidebar_order', 'is_hidden')
            && db_has_column('sidebar_item_order', 'is_hidden');
    }

    /** Semua dropdown_key yang sudah punya item terdaftar (auto-discovery saat sidebar dirender). */
    public function get_item_order_dropdowns() {
        $rows = $this->db->query("SELECT DISTINCT dropdown_key FROM sidebar_item_order ORDER BY dropdown_key ASC")->result();
        return array_map(function ($r) { return $r->dropdown_key; }, $rows);
    }

    /** Semua item 1 dropdown, urut sesuai sort_order (= urutan tampil submenu itu saat ini). */
    public function get_items_for_dropdown($dropdown_key) {
        return $this->db->where('dropdown_key', $dropdown_key)->order_by('sort_order', 'ASC')->get('sidebar_item_order')->result();
    }

    /** Simpan urutan submenu baru sekaligus, scoped ke 1 dropdown (pola sama persis save_sidebar_order()). */
    public function save_item_order($dropdown_key, array $ordered_keys, $user_id) {
        $this->db->trans_start();
        $i = 0;
        foreach ($ordered_keys as $key) {
            $key = trim((string) $key);
            if ($key === '') continue;
            $this->db->where('dropdown_key', $dropdown_key)->where('item_key', $key)->update('sidebar_item_order', [
                'sort_order' => $i * 10,
                'updated_by' => $user_id,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $i++;
        }
        $this->db->trans_complete();
        return $this->db->trans_status() !== false;
    }

    /** Fitur pindah antar segmen butuh tabel dari migration_sidebar_item_parent.sql. */
    public function parent_feature_ready() {
        try { return $this->db->table_exists('sidebar_item_parent'); }
        catch (\Throwable $e) { return false; }
    }

    /** Segmen tujuan tiap item di 1 dropdown: [item_key => parent_key]. */
    public function get_item_parents($dropdown_key) {
        if (!$this->parent_feature_ready()) return [];
        $rows = $this->db->select('item_key, parent_key')->where('dropdown_key', $dropdown_key)->get('sidebar_item_parent')->result();
        $out = [];
        foreach ($rows as $r) { $out[$r->item_key] = (string) $r->parent_key; }
        return $out;
    }

    /**
     * Pindahkan 1 item ke segmen lain di dropdown yang sama (user 2026-09-16).
     *
     * Selain mencatat tujuannya, baris urutan item itu DIPINDAH ke scope
     * segmen tujuan -- kalau tidak, halaman "Atur Urutan Sidebar" masih
     * menampilkannya di segmen lama padahal sidebar sudah pindah.
     *
     * @param string $parent_key '' = keluar dari segmen mana pun
     * @param string $from_scope scope baris urutan saat ini ("dropdown" atau "dropdown::grp_x")
     */
    public function save_item_parent($dropdown_key, $item_key, $parent_key, $from_scope, $user_id) {
        if (!$this->parent_feature_ready()) return false;

        $target_scope = $parent_key === '' ? $dropdown_key : $dropdown_key . '::' . $parent_key;
        $now = date('Y-m-d H:i:s');

        // Label dibawa dari baris lama supaya daftar di halaman admin tetap terbaca.
        $old = $this->db->where('dropdown_key', $from_scope)->where('item_key', $item_key)->get('sidebar_item_order')->row();
        $label = $old->item_label ?? $item_key;

        $this->db->trans_start();
        $this->db->query(
            "INSERT INTO sidebar_item_parent (dropdown_key, item_key, parent_key, updated_by, updated_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE parent_key = VALUES(parent_key), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)",
            [$dropdown_key, $item_key, $parent_key, $user_id, $now]
        );

        if ($target_scope !== $from_scope) {
            $max = $this->db->select_max('sort_order', 'm')->where('dropdown_key', $target_scope)->get('sidebar_item_order')->row();
            $this->db->query(
                "INSERT IGNORE INTO sidebar_item_order (dropdown_key, item_key, item_label, sort_order, created_at) VALUES (?, ?, ?, ?, ?)",
                [$target_scope, $item_key, $label, (int) ($max->m ?? 0) + 10, $now]
            );
            $this->db->where('dropdown_key', $from_scope)->where('item_key', $item_key)->delete('sidebar_item_order');
        }
        $this->db->trans_complete();
        return $this->db->trans_status() !== false;
    }

    public function get_live_value($lang_key, $language, $fallback_default) {
        static $file_cache = [];
        if (!isset($file_cache[$language])) {
            $file_cache[$language] = [];
            $path = APPPATH . 'language/' . $language . '/app_lang.php';
            if (is_file($path)) {
                $lang = [];
                include $path; // hanya berisi $lang['x'] = 'y'; assignments, aman di-include langsung
                $file_cache[$language] = $lang;
            }
        }
        return $file_cache[$language][$lang_key] ?? $fallback_default;
    }
}
