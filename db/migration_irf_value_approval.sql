-- ============================================================
-- IRF: nilai barang dari PO terakhir + jalur approval berdasarkan nilai
-- ------------------------------------------------------------
-- Permintaan user 2026-09-15:
--   - Harga satuan tiap barang terisi OTOMATIS dari PO terakhir barang itu
--     (user tidak menginput harga). Sumber: PO status Confirmed/Closed, harga > 0.
--   - Ada barang yang belum pernah di-PO      -> approval 3 tahap
--   - Semua pernah di-PO & total >= 1.000.000  -> approval 3 tahap
--   - Semua pernah di-PO & total <  1.000.000  -> approval 1 tahap
--     (Head of Dept langsung final)
--   - Harga & jalur dihitung ulang dan DIKUNCI saat submit.
--   - Pemohon khusus (Johanes) tetap langsung ke Final Approve apa pun
--     nilainya -> approval_route = 'final_only'.
--
-- IRF lama (approval_route NULL) diperlakukan 3 tahap = perilaku sebelumnya.
-- Idempotent: aman dijalankan berulang.
-- ============================================================

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consumable_request_details' AND COLUMN_NAME = 'unit_price');
SET @sql := IF(@col = 0,
    "ALTER TABLE `consumable_request_details`
        ADD COLUMN `unit_price` DECIMAL(19,4) NULL DEFAULT NULL COMMENT 'Harga satuan dari PO terakhir (NULL = belum pernah di-PO)' AFTER `qty_requested`,
        ADD COLUMN `price_po_detail_id` INT(11) NULL DEFAULT NULL COMMENT 'po_details.id sumber harga' AFTER `unit_price`",
    "SELECT 'consumable_request_details.unit_price sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'consumable_request_header' AND COLUMN_NAME = 'approval_route');
SET @sql := IF(@col = 0,
    "ALTER TABLE `consumable_request_header`
        ADD COLUMN `total_value` DECIMAL(20,2) NULL DEFAULT NULL COMMENT 'Total qty x harga PO terakhir' AFTER `notes`,
        ADD COLUMN `approval_route` VARCHAR(20) NULL DEFAULT NULL COMMENT '1_stage | 3_stage | final_only (NULL = IRF lama, 3 tahap)' AFTER `total_value`,
        ADD COLUMN `approval_route_reason` VARCHAR(255) NULL DEFAULT NULL AFTER `approval_route`",
    "SELECT 'consumable_request_header.approval_route sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
