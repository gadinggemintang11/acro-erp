-- ============================================================
-- Sidebar: simpan kunci teks (lang key) tiap menu
-- ------------------------------------------------------------
-- User 2026-09-16: "edit text sidebar harusnya bisa juga di atur sidebar ya"
-- -- mengganti tulisan menu bisa langsung dari halaman "Atur Urutan Sidebar",
-- tidak harus lewat mode klik-teks-di-halaman.
--
-- Supaya baris di halaman itu tahu teks mana yang harus diubah, kunci
-- lang_line tiap menu ikut disimpan. Sumbernya atribut `data-lk` pada tiap
-- <a class="collapse-item"> di sidebar.php, diisi otomatis saat sidebar
-- dirender (lihat sidebar_split_into_blocks()). Untuk baris SEGMEN, kunci
-- sudah ada di item_key ("grp_<lang key>") jadi tidak perlu kolom ini.
--
-- Kalau kolom ini belum ada, tombol ganti teks tidak muncul dan halaman
-- tetap berjalan seperti biasa.
-- Idempotent: aman dijalankan berulang.
-- ============================================================

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sidebar_item_order' AND COLUMN_NAME = 'lang_key');
SET @sql := IF(@col = 0,
    "ALTER TABLE `sidebar_item_order` ADD COLUMN `lang_key` VARCHAR(100) NULL DEFAULT NULL COMMENT 'kunci lang_line label menu ini' AFTER `item_label`",
    "SELECT 'sidebar_item_order.lang_key sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Baris SEGMEN: kuncinya memang sudah ada di item_key.
UPDATE `sidebar_item_order`
SET `lang_key` = SUBSTRING(`item_key`, 5)
WHERE `item_key` LIKE 'grp\\_%' AND (`lang_key` IS NULL OR `lang_key` = '');
