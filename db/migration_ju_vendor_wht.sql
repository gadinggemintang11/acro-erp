-- ============================================================
-- MIGRATION: Vendor + WHT (PPh 23) untuk Jurnal Umum / Voucher Disbursement.
-- - journal_header: simpan vendor (snapshot) + nilai WHT.
-- - suppliers: penanda kena PPh 23 + rate-nya (untuk WHT otomatis per vendor).
-- Idempotent: aman di-rerun.
-- ============================================================

-- Vendor (snapshot di header supaya voucher tetap akurat walau master berubah)
ALTER TABLE `journal_header`
  ADD COLUMN IF NOT EXISTS `supplier_id` INT(11) NULL COMMENT 'Vendor (FK suppliers.id)',
  ADD COLUMN IF NOT EXISTS `vendor_name` VARCHAR(150) NULL COMMENT 'Snapshot nama vendor',
  ADD COLUMN IF NOT EXISTS `vendor_bank_name` VARCHAR(100) NULL COMMENT 'Snapshot nama bank vendor',
  ADD COLUMN IF NOT EXISTS `vendor_bank_account` VARCHAR(50) NULL COMMENT 'Snapshot no rekening vendor';

-- WHT / PPh 23 yang dihitung otomatis
ALTER TABLE `journal_header`
  ADD COLUMN IF NOT EXISTS `wht_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Rate PPh 23 (%)',
  ADD COLUMN IF NOT EXISTS `wht_amount` DECIMAL(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Nominal PPh 23 (auto)';

-- Penanda vendor kena PPh 23 (memicu WHT otomatis)
ALTER TABLE `suppliers`
  ADD COLUMN IF NOT EXISTS `pph23` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Kena PPh 23 (1=ya)',
  ADD COLUMN IF NOT EXISTS `pph23_rate` DECIMAL(5,2) NOT NULL DEFAULT 2.00 COMMENT 'Rate PPh 23 (%) default 2';

-- Verifikasi
SHOW COLUMNS FROM `journal_header` LIKE 'vendor%';
SHOW COLUMNS FROM `journal_header` LIKE 'wht%';
SHOW COLUMNS FROM `suppliers` LIKE 'pph23%';
