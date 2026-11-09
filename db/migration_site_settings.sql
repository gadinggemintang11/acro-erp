-- ============================================================
-- Developer Mode: identitas perusahaan bisa diubah dari UI
-- ------------------------------------------------------------
-- User 2026-10-08: "di developer mode ini ganti gambar nya harusnya di
-- perluas karna di login kan ada logo, di pdf pdf juga ada logo, juga bisa
-- ganti nama perusahaan dan alamat untuk kedepannya develop mudah ya".
--
-- Sebelum ini nama & alamat perusahaan DITULIS LANGSUNG di 27 berkas (kop
-- PDF, export Excel, halaman login, footer aplikasi). Ganti nama perusahaan
-- berarti menyisir 33 tempat satu per satu.
--
-- Tabel ini cuma menyimpan YANG DIUBAH USER. Key yang tidak ada barisnya =
-- pakai nilai bawaan yang didefinisikan di company_info() (helper
-- site_asset_helper.php) -- pola yang sama dengan site_assets &
-- lang_overrides: nilai bawaan tetap hidup di kode, DB cuma menimpa.
-- Konsekuensinya: tidak ada baris seed di sini, dan DB yang belum dimigrasi
-- tetap menampilkan identitas yang benar.
--
-- Slot gambar BARU (logo login, logo PDF, favicon) TIDAK butuh migrasi --
-- tabel site_assets sudah ada & key-nya didaftarkan di kode
-- (Developer_mode::ASSET_SLOTS).
--
-- Idempotent: aman dijalankan berulang.
-- ============================================================

CREATE TABLE IF NOT EXISTS `site_settings` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `setting_key`   VARCHAR(100) NOT NULL,
    `setting_value` TEXT         NULL,
    `updated_by`    INT(11)      NULL,
    `updated_at`    DATETIME     NULL,
    `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_site_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
