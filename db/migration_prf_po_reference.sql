-- ============================================================
-- MIGRATION: Tambah kolom po_id untuk referensi ke Purchase Order
-- PRF bisa di-create dari PO yang sudah Confirmed
-- Idempotent: aman di-rerun
-- ============================================================

-- Tambah kolom po_id dan po_type ke prf_header
ALTER TABLE `prf_header`
  ADD COLUMN IF NOT EXISTS `po_id` INT(11) NULL
  COMMENT 'Reference ke Purchase Order yang di-pull (jika dari PO)',
  ADD COLUMN IF NOT EXISTS `po_type` VARCHAR(20) NULL
  COMMENT 'Tipe PO: produksi atau non_produksi (cache untuk audit trail)';

-- Index untuk performa query approval
CREATE INDEX IF NOT EXISTS idx_prf_po_id ON prf_header(po_id);

-- ============================================================
-- VERIFIKASI
-- ============================================================
SHOW COLUMNS FROM `prf_header` LIKE 'po_%';
