-- Migration: Tambah kolom kategori pada tabel suppliers
-- Pemisah antara supplier barang general dan produksi
-- Tanggal: 2026-06-12

ALTER TABLE `suppliers`
  ADD COLUMN `kategori` ENUM('general','produksi') NOT NULL DEFAULT 'general'
  AFTER `jenis`;
