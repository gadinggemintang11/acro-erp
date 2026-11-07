<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * "Developer Mode" — atur kolom mana yang tampil di 1 halaman list (ERP
 * Revision Notes, user 2026-09-03). Pola auto-discovery SAMA seperti
 * lang_line()/lang_overrides, TAPI di-scope per HALAMAN ($page_key) —
 * HARUS dipasang manual satu per satu ke tiap halaman list yang mau
 * dikasih fitur ini (tidak ada "1 pintu" seperti lang_line() yang otomatis
 * kepakai di semua tabel list, krn tiap halaman nulis HTML <table> sendiri2).
 *
 * Pemakaian di view: panggil PERSIS 2x per kolom (sekali di <th>, sekali
 * di <td> baris data) dgn $page_key + $column_key yang SAMA, supaya
 * header & datanya tersembunyi bareng2 konsisten.
 */
if (!function_exists('ui_column_visible')) {
    function ui_column_visible($page_key, $column_key, $label = null, $default_visible = true) {
        $CI =& get_instance();

        static $hidden_map = [];
        static $known_map  = [];
        static $table_ready = null;

        if ($table_ready === null) {
            try {
                $table_ready = $CI->db->table_exists('ui_columns');
            } catch (\Throwable $e) {
                $table_ready = false;
            }
        }
        if (!$table_ready) return $default_visible;

        if (!isset($hidden_map[$page_key])) {
            $hidden_map[$page_key] = [];
            $known_map[$page_key]  = [];
            try {
                $rows = $CI->db->where('page_key', $page_key)->get('ui_columns')->result();
                foreach ($rows as $r) {
                    $known_map[$page_key][$r->column_key] = true;
                    if ((int) $r->is_hidden === 1) {
                        $hidden_map[$page_key][$r->column_key] = true;
                    }
                }
            } catch (\Throwable $e) {
                // Diam-diam skip.
            }
        }

        // Auto-discovery: kolom baru utk halaman ini -> daftarkan, is_hidden
        // ikut $default_visible (BUG ditemukan+fixed sebelum sempat kepakai
        // user: sempat hardcode is_hidden=0 di sini, jadi kolom baru yang
        // maunya "default tersembunyi" malah muncul duluan pas pertama kali
        // ke-discover).
        if (!isset($known_map[$page_key][$column_key])) {
            $known_map[$page_key][$column_key] = true;
            $initial_hidden = $default_visible ? 0 : 1;
            try {
                $CI->db->query(
                    "INSERT IGNORE INTO ui_columns (page_key, column_key, column_label, is_hidden, created_at) VALUES (?, ?, ?, ?, NOW())",
                    [$page_key, $column_key, $label, $initial_hidden]
                );
            } catch (\Throwable $e) {
                // Diam-diam skip.
            }
            if ($initial_hidden) {
                $hidden_map[$page_key][$column_key] = true;
            }
        }

        return empty($hidden_map[$page_key][$column_key]);
    }
}
