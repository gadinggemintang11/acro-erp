-- ============================================================
-- Migration: WO bisa berstatus 'Finished' (auto-finish dari Production)
-- Tanggal: 2026-05-22
--
-- Production_model::_check_wo_completion memanggil
--   Wo_model::update_status($wo_id, 'Finished')
-- yang menulis kolom finished_at/finished_by. Skema lama tidak punya
-- kolom itu dan enum status belum include 'Finished' → error 1054.
-- ============================================================

-- 1) Extend enum status: tambah 'Finished'
ALTER TABLE `wo_header`
    MODIFY COLUMN `status` ENUM('Planned','In Progress','Completed','Finished','Cancelled')
        NULL DEFAULT 'Planned';

-- 2) Tambah finished_at (idempotent)
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wo_header'
      AND COLUMN_NAME  = 'finished_at'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `wo_header`
        ADD COLUMN `finished_at` DATETIME NULL DEFAULT NULL AFTER `confirmed_at`',
    'SELECT "wo_header.finished_at already exists, skipped." AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Tambah finished_by (idempotent)
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'wo_header'
      AND COLUMN_NAME  = 'finished_by'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `wo_header`
        ADD COLUMN `finished_by` INT(11) NULL DEFAULT NULL AFTER `finished_at`,
        ADD KEY `idx_wo_finished_by` (`finished_by`)',
    'SELECT "wo_header.finished_by already exists, skipped." AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
