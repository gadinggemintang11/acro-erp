-- =====================================================================
-- Migration: Rantai kategori IRF (General/Produksi) sampai ke penerimaan
--
-- Lanjutan dari migration_irf_category.sql (yang menandai
-- consumable_request_header & consumable_out_header). Di sini kategori
-- disambungkan lebih jauh ke hilir supaya traceability tidak putus:
--   IRF (category) -> PR detail (category) -> PO detail (category)
--   -> SGR detail (category, saat barang diterima dari supplier)
--
-- Nullable di ketiganya karena mayoritas baris PR/PO/SGR di sistem ini
-- SAMA SEKALI TIDAK berasal dari alur IRF consumable (mis. raw material
-- produksi berbasis BOM/WO, jasa, asset, atau pembelian umum lain) —
-- memaksa nilai di situ cuma akan menyesatkan.
--
-- Stock (stock_ledger/stock_on_hand) SENGAJA tidak disentuh — tetap satu
-- pool bersama. Kolom ini murni label pelaporan/traceability per dokumen,
-- bukan pembagi saldo fisik.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

ALTER TABLE `pr_details`
  ADD COLUMN IF NOT EXISTS `category` ENUM('General','Produksi') NULL DEFAULT NULL AFTER `item_type`;

ALTER TABLE `po_details`
  ADD COLUMN IF NOT EXISTS `category` ENUM('General','Produksi') NULL DEFAULT NULL AFTER `item_type`;

ALTER TABLE `sgr_details`
  ADD COLUMN IF NOT EXISTS `category` ENUM('General','Produksi') NULL DEFAULT NULL AFTER `item_type`;
