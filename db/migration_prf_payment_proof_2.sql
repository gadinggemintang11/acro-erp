-- ============================================================
-- MIGRATION: Tambah kolom bukti bayar KEDUA (opsional) di PRF payment.
-- Finance bisa upload 1 atau 2 bukti bayar per pembayaran.
-- Idempotent: aman di-rerun.
-- ============================================================

ALTER TABLE `prf_payment_tracking`
  ADD COLUMN IF NOT EXISTS `payment_proof_path_2` VARCHAR(255) NULL
  COMMENT 'Path file bukti pembayaran kedua (opsional, relatif ke FCPATH)';

-- Verifikasi
SHOW COLUMNS FROM `prf_payment_tracking` LIKE 'payment_proof_path%';
