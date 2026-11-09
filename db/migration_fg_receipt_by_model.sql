-- ============================================================
-- Migration: FG Receipt jadi base on Model (FG), multi-WO per receipt
-- Tanggal: 2026-05-22
--
-- Perubahan skema:
--   * fg_receipt_header.fg_id   : FG/model yg dipilih untuk receipt ini.
--   * fg_receipt_details.production_header_id / wo_header_id / sales_contract_id
--     dipindah ke level detail karena 1 receipt bisa span banyak WO.
--
-- Kolom lama di fg_receipt_header (wo_header_id, production_header_id,
-- sales_contract_id) DIBIARKAN nullable supaya data lama tetap valid.
-- Data lama tidak di-backfill ke detail (atas keputusan user).
-- ============================================================

-- 1) Tambah fg_id ke header (idempotent)
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fg_receipt_header'
      AND COLUMN_NAME  = 'fg_id'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `fg_receipt_header`
        ADD COLUMN `fg_id` INT(11) NULL DEFAULT NULL COMMENT ''FG/model utama yg diterima'' AFTER `sales_contract_id`,
        ADD KEY `idx_fgrh_fg_id` (`fg_id`)',
    'SELECT "fg_receipt_header.fg_id already exists, skipped." AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Tambah production_header_id ke detail
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fg_receipt_details'
      AND COLUMN_NAME  = 'production_header_id'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `fg_receipt_details`
        ADD COLUMN `production_header_id` INT(11) NULL DEFAULT NULL AFTER `prod_output_id`,
        ADD KEY `idx_fgrd_prod_header` (`production_header_id`)',
    'SELECT "fg_receipt_details.production_header_id already exists, skipped." AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Tambah wo_header_id ke detail
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fg_receipt_details'
      AND COLUMN_NAME  = 'wo_header_id'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `fg_receipt_details`
        ADD COLUMN `wo_header_id` INT(11) NULL DEFAULT NULL AFTER `production_header_id`,
        ADD KEY `idx_fgrd_wo_header` (`wo_header_id`)',
    'SELECT "fg_receipt_details.wo_header_id already exists, skipped." AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Tambah sales_contract_id ke detail
SET @col_exists := (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'fg_receipt_details'
      AND COLUMN_NAME  = 'sales_contract_id'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE `fg_receipt_details`
        ADD COLUMN `sales_contract_id` INT(11) NULL DEFAULT NULL AFTER `wo_header_id`,
        ADD KEY `idx_fgrd_sc` (`sales_contract_id`)',
    'SELECT "fg_receipt_details.sales_contract_id already exists, skipped." AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
