-- =====================================================================
-- Migration: Split saldo stock fisik General vs Produksi
--
-- Latar belakang: gudang General & gudang Produksi FISIK terpisah, masing-
-- masing punya laporan stock sendiri. Beda dari migrasi kategori
-- sebelumnya (yang cuma label transaksi) — di sini `category` di
-- `stock_ledger` BENAR-BENAR membelah saldo jadi 2 angka per item
-- (item_id + item_type + category), bukan cuma tag pelaporan.
--
-- Cutover: SEMUA saldo existing (item_type inventory/machine) dianggap
-- masuk bucket 'General' — karena sebelum fitur ini ada, memang belum ada
-- pemisahan gudang di sistem, semua aktivitas historis dianggap satu
-- bucket. Bucket 'Produksi' mulai dari 0, terisi dari SGR/Initial
-- Stock/Transfer setelah cutover ini. Kalau ternyata secara fisik hari ini
-- ada barang yang sebenarnya sudah ada di gudang Produksi, itu perlu
-- dimasukkan manual lewat modul Stock Transfer (General -> Produksi)
-- setelah migration ini jalan.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

ALTER TABLE `stock_ledger`
  ADD COLUMN IF NOT EXISTS `category` ENUM('General','Produksi') NULL DEFAULT NULL AFTER `item_type`;

-- Backfill histori: hanya utk item_type yg dipakai IRF/consumable (inventory & machine).
-- item_type lain (material/semifinished/finished_goods/scrap) SENGAJA dibiarkan NULL
-- selamanya -- kategori General/Produksi ini tidak relevan buat mereka.
UPDATE `stock_ledger`
SET `category` = 'General'
WHERE `category` IS NULL
  AND `item_type` IN ('inventory', 'machine');

-- Initial Stock (saldo awal) juga menulis ke stock_ledger untuk item_type
-- 'inventory' -- draft-nya perlu tahu kategori sebelum di-confirm ke ledger.
ALTER TABLE `initial_stock_draft`
  ADD COLUMN IF NOT EXISTS `category` ENUM('General','Produksi') NOT NULL DEFAULT 'General' AFTER `item_type`;
