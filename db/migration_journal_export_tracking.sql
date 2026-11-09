-- ============================================================
-- MIGRATION: Tracking export Excel untuk safeguard Clear Journal (Month End).
-- journal_header.exported_at / exported_by di-set saat jurnal di-export
-- (Export Full backup). Dipakai untuk warning "ada jurnal belum di-export"
-- sebelum Clear.
-- Idempotent: aman di-rerun.
-- ============================================================

ALTER TABLE `journal_header`
  ADD COLUMN IF NOT EXISTS `exported_at` DATETIME   NULL DEFAULT NULL COMMENT 'Waktu terakhir di-export ke Excel (backup)',
  ADD COLUMN IF NOT EXISTS `exported_by` INT(11)    NULL DEFAULT NULL COMMENT 'User yang export (FK users.id)';

-- Verifikasi
SHOW COLUMNS FROM `journal_header` LIKE 'exported%';
