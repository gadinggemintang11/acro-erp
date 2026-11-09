-- =====================================================================
-- Migration: Tambah 'Jasa' ke ENUM category di pr_details & po_details
--
-- BUG DITEMUKAN (2026-09-03) saat menguji rantai penyelesaian Jasa
-- ([[revision_irf_auto_pr_at_issuance]]): `pr_details.category` dan
-- `po_details.category` masih ENUM('General','Produksi') dari sebelum
-- kategori 'Jasa' ada di consumable_request_header. Akibatnya
-- Consumable_outstanding::create_pr() GAGAL (SQL error "Data truncated for
-- column 'category'") setiap kali baris outstanding kategori Jasa dijadikan
-- PR — dalam strict SQL mode ini ERROR, bukan cuma silently NULL. Begitu
-- juga Po_model::pull_pr_items() saat menarik PR Jasa ke PO (menyalin
-- category dari pr_details ke po_details).
--
-- Kolom category ENUM('General','Produksi') lain (consumable_out_header,
-- initial_stock_draft, sgr_details, stock_ledger, stock_transfer_header)
-- SENGAJA tidak disentuh — semuanya bagian dari jalur fisik (stok gudang)
-- yang memang tidak pernah dilalui item Jasa.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

ALTER TABLE `pr_details` MODIFY `category` ENUM('General','Produksi','Jasa') DEFAULT NULL;
ALTER TABLE `po_details` MODIFY `category` ENUM('General','Produksi','Jasa') DEFAULT NULL;
