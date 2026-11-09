-- ============================================================
-- "Copot & Pasang" menu sidebar (Developer Mode)
-- ------------------------------------------------------------
-- User 2026-09-07: "harusnya bisa copot dan pasang menu sidebar juga".
-- Melengkapi fitur Atur Urutan Sidebar yang sudah ada — sekarang menu
-- bukan cuma bisa diurutkan, tapi juga disembunyikan/ditampilkan lagi.
--
-- PENTING — ini MURNI TAMPILAN, BUKAN HAK AKSES:
--   Menu yang "dicopot" cuma hilang dari sidebar. URL-nya TETAP bisa
--   diakses langsung, dan izin Role Access TIDAK berubah sama sekali.
--   Untuk benar-benar mencabut akses, pakai halaman Role Access.
--
-- Nilai 0 = tampil (default, jadi semua menu yang sudah ada TIDAK berubah
-- perilakunya setelah migrasi ini dijalankan). 1 = disembunyikan.
-- ============================================================

ALTER TABLE `sidebar_order`
    ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sort_order`;

ALTER TABLE `sidebar_item_order`
    ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0 AFTER `sort_order`;
