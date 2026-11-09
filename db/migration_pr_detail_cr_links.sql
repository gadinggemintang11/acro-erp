-- =====================================================================
-- Migration: PR Detail <-> Consumable Request Detail links
--
-- Latar belakang (ERP Revision Notes 2026-09-03, [[revision_irf_auto_pr_at_issuance]]):
-- Consumable_outstanding::create_pr() selama ini cuma menulis daftar nomor
-- IRF sumber ke kolom teks bebas `pr_details.remarks` — tidak ada FK
-- terstruktur balik ke `consumable_request_details`. Untuk item fisik itu
-- tidak masalah (outstanding-nya dihitung dari consumable_out_details /
-- pengeluaran gudang, independen dari PR/PO). Tapi utk kategori Jasa TIDAK
-- ADA pengeluaran gudang sama sekali — jasa "selesai" ditandai di level PO
-- (Purchase_order_jasa::complete_service() -> grn_header warehouse_type='jasa').
-- Tanpa link terstruktur ini, sinyal "selesai" itu tidak pernah bisa
-- ditelusuri balik ke baris IRF asal, jadi outstanding Jasa permanen 100%.
--
-- Rantai lengkap setelah migration ini:
--   consumable_request_details (cr_detail_id)
--     -> pr_detail_cr_links (baru, banyak-ke-banyak + qty)
--     -> pr_details (pr_detail_id)
--     -> po_details.pr_detail_id (sudah ada)
--     -> grn_details.source_doc_detail_id (source_doc_type='PO', sudah ada)
--     -> grn_header.warehouse_type='jasa' AND status='Completed'
--
-- Dipakai read-only (JOIN) oleh Consumable_outstanding_model &
-- Consumable_request_model::check_outstanding_by_item() utk menambahkan
-- qty jasa yang sudah "diselesaikan" PO ke perhitungan outstanding,
-- setara dgn qty_issued utk barang fisik. Ditulis oleh
-- Consumable_outstanding::create_pr() setiap kali PR dibuat dari outstanding.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

CREATE TABLE IF NOT EXISTS `pr_detail_cr_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pr_detail_id` int(11) NOT NULL,
  `cr_detail_id` int(11) unsigned NOT NULL,
  `qty` decimal(15,4) NOT NULL DEFAULT 0.0000,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pr_detail_id` (`pr_detail_id`),
  KEY `idx_cr_detail_id` (`cr_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
