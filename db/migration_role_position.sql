-- ============================================================
-- Role = Departemen x Posisi, dan 1 user bisa punya BANYAK role
-- ------------------------------------------------------------
-- Permintaan klien 2026-09-15 (acuan: assets/NSI - ERP ROLE LEVEL.pdf):
-- "role nya di kategorikan ke posisi level aja, jadi tinggal pilih nanti
-- usernya mau di kasih role posisi apa aja". Contoh: Bayu diberi
-- Logistic - Officer, General Warehouse - Supervisor, dan ADM - Supervisor
-- sekaligus.
--
-- TAHAP 1 (file ini): struktur saja.
--   1. Master posisi di common_code group POSITION_LEVEL (bisa ditambah
--      lewat menu Common Code seperti master lain).
--   2. Departemen baru General Warehouse (GWH) & Logistic (LOG) -- dijawab
--      user: "iya itu nanti department baru".
--   3. roles.department_code + roles.position_code (nullable). Role lama
--      tetap berjalan tanpa posisi; Admin tinggal melengkapinya.
--   4. Tabel user_roles (user <-> role, many-to-many). Hak akses user =
--      GABUNGAN semua role-nya (lihat current_role_ids() di auth_helper).
--   5. Backfill: role yang sedang dipakai tiap user disalin ke user_roles,
--      jadi SETELAH migrasi hak akses semua user PERSIS SAMA seperti sebelumnya.
--
-- users.role_id TIDAK dihapus -- tetap dipakai sebagai "role utama" (label di
-- topbar, API mobile lama). Kode membaca gabungan user_roles + users.role_id,
-- jadi aman juga di DB yang belum menjalankan migrasi ini.
--
-- Idempotent: aman dijalankan berulang.
-- ============================================================

-- 1) Master posisi
INSERT INTO `common_code` (`group_code`, `group_description`, `code`, `description`, `status`)
SELECT 'POSITION_LEVEL', 'Posisi / Level Jabatan', v.code, v.description, 'active'
FROM (
    SELECT 'DIRECTOR' AS code, 'Director' AS description
    UNION ALL SELECT 'ASST_DIRECTOR', 'Assistant of Director'
    UNION ALL SELECT 'HOD',           'Head of Department'
    UNION ALL SELECT 'OFFICER',       'Officer'
    UNION ALL SELECT 'SUPERVISOR',    'Supervisor'
    UNION ALL SELECT 'ADMIN_CLERK',   'Admin/Clerk'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM `common_code` c WHERE c.group_code = 'POSITION_LEVEL' AND c.code = v.code
);

-- 2) Departemen baru
INSERT INTO `common_code` (`group_code`, `group_description`, `code`, `description`, `status`)
SELECT 'Department', 'Departemen', v.code, v.description, 'active'
FROM (
    SELECT 'GWH' AS code, 'General Warehouse' AS description
    UNION ALL SELECT 'LOG', 'Logistic'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM `common_code` c WHERE c.group_code = 'Department' AND c.code = v.code
);

-- 3) roles.department_code & roles.position_code
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'department_code'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `roles` ADD COLUMN `department_code` VARCHAR(50) NULL DEFAULT NULL AFTER `department`",
    "SELECT 'roles.department_code sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'position_code'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `roles` ADD COLUMN `position_code` VARCHAR(50) NULL DEFAULT NULL AFTER `department_code`",
    "SELECT 'roles.position_code sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Departemen role lama diisi dari kolom teks `department` bila cocok dengan
-- kode ATAU nama di master Department (mis. 'HRGA' -> ADM). Yang tidak cocok
-- (mis. 'MANAGEMENT') dibiarkan kosong untuk dipilih Admin. Posisi sengaja
-- TIDAK ditebak -- nama role lama tidak konsisten (Staff/Admin/Assistant SPV).
UPDATE `roles` r
JOIN `common_code` c ON c.group_code = 'Department' AND (c.code = r.department OR c.description = r.department)
SET r.department_code = c.code
WHERE r.department_code IS NULL;

-- 4) user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
    `id`         INT(11)  NOT NULL AUTO_INCREMENT,
    `user_id`    INT(11)  NOT NULL,
    `role_id`    INT(11)  NOT NULL,
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_role` (`user_id`, `role_id`),
    KEY `idx_user_roles_role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Backfill role yang sedang dipakai
INSERT IGNORE INTO `user_roles` (`user_id`, `role_id`)
SELECT `id`, `role_id` FROM `users` WHERE `role_id` IS NOT NULL AND `role_id` > 0;
