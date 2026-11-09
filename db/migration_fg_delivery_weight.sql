-- ============================================================
-- Delivery Note: kolom berat bersih (NW) & berat kotor (GW)
-- ------------------------------------------------------------
-- Cetak Delivery Note mengikuti contoh klien
-- (assets/DeliveryOrder_DO-202606-0008.pdf) yang punya kolom NW & GW.
-- Awalnya kolom itu dicetak kosong karena tidak ada sumber datanya di sistem;
-- user 2026-10-01: "Tambah kolom NW dan GW juga di inputan" -- jadi sekarang
-- diisi sendiri per baris barang saat membuat/mengubah pengiriman.
--
-- Ditaruh di BARIS DETAIL, bukan di master barang jadi: berat yang dicatat di
-- surat jalan adalah berat kiriman itu (sudah termasuk kemasan untuk GW), bukan
-- sifat tetap barangnya.
--
-- NULL = belum ditimbang. Sengaja TIDAK diberi default 0 supaya bisa dibedakan
-- dari barang yang memang beratnya nol, dan di cetakan tetap kosong untuk
-- ditulis tangan.
--
-- Idempotent: aman dijalankan berulang.
-- ============================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fg_delivery_details' AND COLUMN_NAME = 'nw'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `fg_delivery_details` ADD COLUMN `nw` DECIMAL(15,4) NULL DEFAULT NULL COMMENT 'Net weight kiriman' AFTER `uom`",
    "SELECT 'fg_delivery_details.nw sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fg_delivery_details' AND COLUMN_NAME = 'gw'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `fg_delivery_details` ADD COLUMN `gw` DECIMAL(15,4) NULL DEFAULT NULL COMMENT 'Gross weight kiriman' AFTER `nw`",
    "SELECT 'fg_delivery_details.gw sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
