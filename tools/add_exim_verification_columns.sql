-- Add EXIM verification columns to relevant tables

-- For GRN (Penerimaan Barang)
ALTER TABLE `grn_header` 
ADD COLUMN `exim_verified` TINYINT(1) DEFAULT 0 COMMENT 'Status verifikasi EXIM (0=belum, 1=sudah)',
ADD COLUMN `exim_verified_by` INT(11) NULL COMMENT 'User ID yang verifikasi',
ADD COLUMN `exim_verified_at` DATETIME NULL COMMENT 'Tanggal waktu verifikasi',
ADD INDEX `idx_exim_verified` (`exim_verified`);

-- For FG Delivery (Pengeluaran FG)
ALTER TABLE `fg_delivery_header` 
ADD COLUMN `exim_verified` TINYINT(1) DEFAULT 0 COMMENT 'Status verifikasi EXIM (0=belum, 1=sudah)',
ADD COLUMN `exim_verified_by` INT(11) NULL COMMENT 'User ID yang verifikasi',
ADD COLUMN `exim_verified_at` DATETIME NULL COMMENT 'Tanggal waktu verifikasi',
ADD INDEX `idx_exim_verified` (`exim_verified`);

-- For Scrap Out (Pengeluaran Scrap)
ALTER TABLE `scrap_out_header` 
ADD COLUMN `exim_verified` TINYINT(1) DEFAULT 0 COMMENT 'Status verifikasi EXIM (0=belum, 1=sudah)',
ADD COLUMN `exim_verified_by` INT(11) NULL COMMENT 'User ID yang verifikasi',
ADD COLUMN `exim_verified_at` DATETIME NULL COMMENT 'Tanggal waktu verifikasi',
ADD INDEX `idx_exim_verified` (`exim_verified`);

-- Note: Subkon Out belum ada tabel terpisah, perlu dicek apakah sudah ada atau masih dalam fg_delivery
