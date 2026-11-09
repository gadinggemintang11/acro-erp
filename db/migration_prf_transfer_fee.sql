-- ============================================================
-- MIGRATION: Tambah kolom biaya transfer bank (opsional) di PRF payment.
-- Dipakai saat pembayaran transfer beda bank yang kena biaya admin.
-- Biaya transfer DICATAT TERPISAH dari payment_amount (nilai tagihan vendor).
-- Total kas keluar = payment_amount + transfer_fee.
-- Idempotent: aman di-rerun.
-- ============================================================

ALTER TABLE `prf_payment_tracking`
  ADD COLUMN IF NOT EXISTS `transfer_fee` DECIMAL(15,2) NOT NULL DEFAULT 0
  COMMENT 'Biaya admin/transfer bank (mis. transfer beda bank), terpisah dari payment_amount';

-- Verifikasi
SHOW COLUMNS FROM `prf_payment_tracking` LIKE 'transfer_fee';
