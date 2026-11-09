-- =====================================================================
-- Migration: Developer Mode — kelola logo/gambar lewat UI
--
-- Permintaan user 2026-09-03 (lanjutan Developer Mode): logo perusahaan
-- dkk bisa diganti sendiri lewat UI, bukan cuma teks. Pola PERSIS sama
-- seperti lang_overrides: 1 tabel kecil, 1 fungsi jadi satu pintu
-- (site_asset($key, $default_path), lihat application/helpers/site_asset_helper.php),
-- path DI DB (bukan nulis ulang file gambar bawaan) supaya aman dari
-- deploy kode berikutnya.
--
-- BEDA dari lang_overrides: TIDAK auto-discovery — jumlah "slot" gambar
-- (logo sidebar, dst) terbatas & jarang nambah, didaftarkan manual di
-- Developer_mode::ASSET_SLOTS (kode), bukan auto-registrasi kayak teks.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

CREATE TABLE IF NOT EXISTS `site_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_key` varchar(100) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL COMMENT 'Path relatif dari root app, tanpa base_url()',
  `original_name` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `uploaded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asset_key` (`asset_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
