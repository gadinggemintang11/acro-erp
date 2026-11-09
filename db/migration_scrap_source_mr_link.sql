-- ============================================================
-- Tautan production_scrap_sources -> mr_details
-- ------------------------------------------------------------
-- Dipakai fitur "Buat Permintaan Barang" dari asal scrap (auto-create MRF)
-- supaya satu asal scrap tidak dibuatkan MR dua kali.
--
-- PENTING (ditemukan 2026-09-21): migrasi ini sempat TIDAK terdaftar di
-- deploy_migrations.sh / PENDING_MIGRATIONS_MASTER.md, jadi kolomnya tidak
-- pernah dibuat di production. Akibatnya modal "Detail Asal Scrap" di
-- Proses Produksi selalu gagal dengan HTTP 500 ("Unknown column
-- pss.mr_detail_id"), karena query-nya sudah memakai kolom ini.
--
-- Idempotent: aman dijalankan berulang (dulu ALTER polos, gagal kalau diulang).
-- ============================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_scrap_sources' AND COLUMN_NAME = 'mr_detail_id'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `production_scrap_sources` ADD COLUMN `mr_detail_id` INT(11) NULL DEFAULT NULL AFTER `source_qty`, ADD INDEX `idx_scrap_mr_detail` (`mr_detail_id`)",
    "SELECT 'production_scrap_sources.mr_detail_id sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
