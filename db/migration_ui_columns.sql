-- =====================================================================
-- Migration: Developer Mode — atur kolom mana yang tampil di 1 list
--
-- Permintaan user 2026-09-03 (lanjutan Developer Mode). BEDA mekanisme
-- dari teks (lang_line()) — tidak ada "1 pintu" yang sama dipakai semua
-- tabel list di app ini (tiap halaman list nulis HTML <table> sendiri2),
-- jadi TIDAK otomatis nyambung ke SEMUA list sekaligus. Helper baru
-- ui_column_visible($page_key, $column_key, $label) (lihat
-- application/helpers/ui_column_helper.php) HARUS dipasang manual satu
-- per satu ke tiap halaman list yang mau dikasih fitur ini — pilot
-- pertama: IRF List (consumable_request/index.php).
--
-- Auto-discovery TETAP jalan (sama pola kayak lang_overrides) - begitu 1
-- halaman list sudah "dipasangi" helper ini, kolom2-nya otomatis
-- terdaftar & bisa diatur dari Developer Mode tanpa perlu develop lagi.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

CREATE TABLE IF NOT EXISTS `ui_columns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_key` varchar(100) NOT NULL COMMENT 'identifier halaman list, mis. irf_list',
  `column_key` varchar(100) NOT NULL COMMENT 'identifier kolom dalam halaman itu, mis. purpose',
  `column_label` varchar(150) DEFAULT NULL COMMENT 'label utk ditampilkan di UI Developer Mode',
  `is_hidden` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_page_column` (`page_key`, `column_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
