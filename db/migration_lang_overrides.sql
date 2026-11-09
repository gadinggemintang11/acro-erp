-- =====================================================================
-- Migration: "Developer Mode" — editor teks/terjemahan lewat UI
--
-- Permintaan user 2026-09-03: sidebar/menu/label yang salah terjemahannya
-- (mis. Bahasa Indonesia) selama ini cuma bisa dibetulkan developer edit
-- file `application/language/<lang>/app_lang.php` + deploy ulang. Sekarang
-- disediakan menu utk edit langsung dari UI, disimpan di DB (BUKAN nulis
-- balik ke file bahasa yang di-attach ke git — supaya tidak ketiban/ilang
-- pas ada deploy kode berikutnya).
--
-- Cara kerja: `lang_line()` (application/helpers/language_helper.php)
-- dimodifikasi jadi cek tabel ini dulu sebelum fallback ke app_lang.php.
-- Auto-discovery: SETIAP key baru yang pernah lewat lang_line() otomatis
-- "terdaftar" (custom_value masih NULL) di ke-3 bahasa sekaligus — jadi
-- daftar key di menu Developer Mode kelengkapin sendiri seiring halaman-
-- halaman dipakai, tidak perlu scan manual.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

CREATE TABLE IF NOT EXISTS `lang_overrides` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang_key` varchar(150) NOT NULL,
  `language` varchar(20) NOT NULL COMMENT 'indonesian/english/chinese - cocok get_available_languages()',
  `custom_value` text DEFAULT NULL COMMENT 'NULL = key diketahui tapi belum di-custom',
  `default_value` text DEFAULT NULL COMMENT 'Snapshot default text dari kode (parameter ke-2 lang_line()) - referensi saja',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lang_key_language` (`lang_key`, `language`),
  KEY `idx_lang_overrides_language` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Menu baru (department SYSTEM, sebelah Common Code/Role Access/User
-- Management/Log Activity yang sudah ada - order_no 530-533).
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Developer Mode (Bahasa)', 'SYSTEM', 'developer_mode', 534)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- Akses: HANYA Admin (role_id = 1) dapat akses penuh secara default.
-- User eksplisit minta menu ini bisa diatur bebas lewat Role Access biasa
-- (bukan dikunci ke Super Admin doang di kode) - jadi role lain yang mau
-- dikasih akses tinggal di-grant manual lewat halaman Role Access, BUKAN
-- otomatis ke-grant lewat migration ini (fitur ini bisa ubah teks di
-- SELURUH aplikasi, sengaja tidak di-default-kan luas).
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT 1, m.id, 1, 0, 1, 1, 0, 0, 0, 0
FROM `menus` m
WHERE m.menu_key = 'developer_mode'
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x WHERE x.role_id = 1 AND x.menu_id = m.id
  );
