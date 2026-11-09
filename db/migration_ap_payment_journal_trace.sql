-- ============================================================
-- MIGRATION: Jejak no jurnal di AP Payment untuk Clear Journal (Month End).
-- Saat jurnal AP Payment ikut di-clear, ap_payment_header.journal_header_id
-- di-set NULL supaya tidak ada pointer nyasar; nomor jurnal lama disimpan di
-- journal_no_cleared sebagai jejak (status Paid & record pembayaran tetap utuh).
-- Idempotent: aman di-rerun.
-- ============================================================

ALTER TABLE `ap_payment_header`
  ADD COLUMN IF NOT EXISTS `journal_no_cleared` VARCHAR(50) NULL DEFAULT NULL
    COMMENT 'No jurnal yang sudah di-clear month-end (jejak; journal_header_id di-set NULL)';

-- Verifikasi
SHOW COLUMNS FROM `ap_payment_header` LIKE 'journal_no_cleared';
