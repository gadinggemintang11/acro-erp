-- =====================================================================
-- Migration: Kategori IRF (General vs Produksi)
--
-- Latar belakang: satu item master (mis. sapu) bisa dipakai baik untuk
-- kebutuhan umum/office (General) maupun kebutuhan lantai produksi
-- (Produksi). Stock & master item TETAP satu (tidak dipecah), tapi tiap
-- transaksi (IRF & pengeluarannya) perlu ditandai kategorinya supaya:
--   - Daftar IRF, pengeluaran, dan outstanding bisa difilter/dipisah
--     per kategori (requestor/PIC follow-up beda antara General & Produksi).
--   - Item yang sama tetap bisa muncul di kedua kategori (dual-use),
--     karena kategori melekat di transaksi, bukan di master barang.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

ALTER TABLE `consumable_request_header`
  ADD COLUMN IF NOT EXISTS `category` ENUM('General','Produksi') NOT NULL DEFAULT 'General' AFTER `department`;

ALTER TABLE `consumable_out_header`
  ADD COLUMN IF NOT EXISTS `category` ENUM('General','Produksi') NOT NULL DEFAULT 'General' AFTER `department`;
