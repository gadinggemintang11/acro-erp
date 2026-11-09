-- ============================================================
-- Sidebar: pindah item ANTAR segmen
-- ------------------------------------------------------------
-- User 2026-09-16: "misal Daftar PRF yang di segment laporan, ingin saya
-- pindah ke segment Keuangan bisa harusnya, jadi tidak hanya pindah segment,
-- tapi isi menu segment itu bisa di pindah antar segment".
--
-- Sebelumnya, segmen sebuah item ditentukan MURNI oleh struktur HTML
-- sidebar.php (satu <h6> membuka segmen, menyerap semua <a> sesudahnya
-- sampai <h6> berikutnya) -- jadi item hanya bisa diurutkan DI DALAM
-- segmennya sendiri. Tabel ini menyimpan pengecualiannya: "item X di
-- dropdown Y sebenarnya milik segmen Z".
--
--   parent_key = 'grp_...'  -> pindah ke segmen itu
--   parent_key = ''         -> keluar dari segmen mana pun (langsung di
--                              bawah dropdown, tanpa judul segmen)
--
-- Kalau tabel ini belum ada, fitur pindah segmen tidak muncul di halaman
-- "Atur Urutan Sidebar" dan sidebar tampil seperti aslinya (tidak error).
-- Idempotent: aman dijalankan berulang.
-- ============================================================

CREATE TABLE IF NOT EXISTS `sidebar_item_parent` (
    `dropdown_key` VARCHAR(100) NOT NULL COMMENT 'dropdown induk, mis. accounting',
    `item_key`     VARCHAR(100) NOT NULL COMMENT 'item_<hash> dari sidebar_item_order',
    `parent_key`   VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'grp_<lang key> segmen tujuan; kosong = tanpa segmen',
    `updated_by`   INT(11) NULL,
    `updated_at`   DATETIME NULL,
    PRIMARY KEY (`dropdown_key`, `item_key`),
    KEY `idx_sidebar_item_parent_target` (`dropdown_key`, `parent_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
