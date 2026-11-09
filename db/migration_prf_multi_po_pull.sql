-- ============================================================
-- MIGRATION: Tambah kolom po_header_id & po_detail_id di prf_details
-- Supaya 1 PRF bisa menarik item dari BEBERAPA PO (per-baris tracking),
-- bukan cuma 1 PO per PRF seperti sebelumnya (prf_header.po_id).
-- Idempotent: aman di-rerun
-- ============================================================

ALTER TABLE `prf_details`
  ADD COLUMN IF NOT EXISTS `po_header_id` INT(11) NULL
  COMMENT 'PO asal baris ini, jika dipull dari PO',
  ADD COLUMN IF NOT EXISTS `po_detail_id` INT(11) NULL
  COMMENT 'po_details.id asal baris ini, jika dipull dari PO';

CREATE INDEX IF NOT EXISTS idx_prf_details_po_header_id ON prf_details(po_header_id);

-- ============================================================
-- VERIFIKASI
-- ============================================================
SHOW COLUMNS FROM `prf_details` LIKE 'po_%';
