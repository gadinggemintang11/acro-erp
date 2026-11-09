-- =====================================================================
-- Migration: IRF (Consumable Request) mendukung item MESIN
--
-- Sebelumnya IRF & Consumable Out hanya bisa item dari tabel `inventories`
-- (item_type 'inventory' hardcoded). Sekarang master `machines` (Stock
-- Machine, item_type 'machine') juga bisa direquest lewat IRF.
--
-- Karena id inventories & machines bisa bentrok (sama-sama mulai dari 1),
-- kolom item_type WAJIB ada di kedua tabel detail supaya join ke
-- stock_ledger / stock_on_hand tidak salah item.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) consumable_request_details.item_type
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'consumable_request_details'
      AND COLUMN_NAME  = 'item_type'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `consumable_request_details`
        ADD COLUMN `item_type` VARCHAR(20) NOT NULL DEFAULT 'inventory' AFTER `item_id`",
    "SELECT 'consumable_request_details.item_type sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) consumable_out_details.item_type
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'consumable_out_details'
      AND COLUMN_NAME  = 'item_type'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `consumable_out_details`
        ADD COLUMN `item_type` VARCHAR(20) NOT NULL DEFAULT 'inventory' AFTER `item_id`",
    "SELECT 'consumable_out_details.item_type sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) Backfill data lama (semua baris lama pasti inventory)
UPDATE `consumable_request_details` SET `item_type` = 'inventory'
WHERE `item_type` IS NULL OR `item_type` = '';
UPDATE `consumable_out_details` SET `item_type` = 'inventory'
WHERE `item_type` IS NULL OR `item_type` = '';

-- 4) Index bantu untuk join outstanding (item_id + item_type)
SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'consumable_request_details'
      AND INDEX_NAME   = 'idx_crd_item'
);
SET @sql := IF(@idx = 0,
    "ALTER TABLE `consumable_request_details` ADD INDEX `idx_crd_item` (`item_id`, `item_type`)",
    "SELECT 'idx_crd_item sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @idx := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'consumable_out_details'
      AND INDEX_NAME   = 'idx_cod_item'
);
SET @sql := IF(@idx = 0,
    "ALTER TABLE `consumable_out_details` ADD INDEX `idx_cod_item` (`item_id`, `item_type`)",
    "SELECT 'idx_cod_item sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
