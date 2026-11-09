-- ============================================================
-- Role Level TAHAP 3: OWN DEPT vs ALL DEPT
-- ------------------------------------------------------------
-- assets/NSI - ERP ROLE LEVEL.pdf membedakan aksi untuk departemen sendiri
-- (OWN DEPT) dan semua departemen (ALL DEPT). Contoh: HOD hanya approve
-- dokumen departemennya, Accounting Officer endorse semua departemen,
-- Supervisor submit dokumen buatan Admin/Clerk di departemennya.
--
-- 1) role_permissions.all_dept_actions
--    Daftar aksi (dipisah koma, mis. "view,endorsement") yang berlaku untuk
--    SEMUA departemen; aksi yang tidak disebut hanya berlaku di departemen
--    role itu. '*' = semua aksi berlaku semua departemen.
--    Baris LAMA diisi 'legacy': semua aksinya tetap berlaku di semua
--    departemen (persis seperti sebelum migrasi), TAPI tidak dipakai untuk
--    memperluas daftar dokumen (mis. melihat draft departemen lain) — jadi
--    tampilan list user lama juga tidak berubah. Begitu role diatur ulang
--    (manual atau lewat Template PDF), nilainya diganti '*' / daftar / kosong.
--
-- 2) prf_header.department_code & erf_header.department_code
--    Kolom department_id bertipe INT, padahal aplikasi mengisinya dengan KODE
--    departemen (teks) -> semua PRF/ERF tersimpan dengan departemen 0 dan
--    departemen selama ini ditebak dari profil pemohon. Kolom teks baru ini
--    yang dipakai; diisi dari departemen pemohon.
--
-- 4) mr_header.department_code (MRF) — lihat bagian 4 di bawah.
--
-- 3) consumable_request_header.department
--    Sebagian IRF menyimpan NAMA departemen (mis. "HRGA") alih-alih kodenya
--    ("ADM"). Diseragamkan jadi kode supaya bisa dibandingkan dengan
--    departemen role.
--
-- Idempotent: aman dijalankan berulang.
-- ============================================================

-- 1) role_permissions.all_dept_actions
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions' AND COLUMN_NAME = 'all_dept_actions'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `role_permissions` ADD COLUMN `all_dept_actions` VARCHAR(255) NULL DEFAULT NULL AFTER `can_delete`",
    "SELECT 'role_permissions.all_dept_actions sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := IF(@col = 0,
    "UPDATE `role_permissions` SET `all_dept_actions` = 'legacy'",
    "SELECT 'backfill all_dept_actions dilewati (sudah pernah)' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 2) department_code di PRF & ERF
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_header' AND COLUMN_NAME = 'department_code'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `prf_header` ADD COLUMN `department_code` VARCHAR(50) NULL DEFAULT NULL AFTER `department_id`, ADD INDEX `idx_prf_header_department_code` (`department_code`)",
    "SELECT 'prf_header.department_code sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'erf_header' AND COLUMN_NAME = 'department_code'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `erf_header` ADD COLUMN `department_code` VARCHAR(50) NULL DEFAULT NULL AFTER `department_id`, ADD INDEX `idx_erf_header_department_code` (`department_code`)",
    "SELECT 'erf_header.department_code sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Isi dari departemen pemohon: cocokkan ke kode ATAU nama di master Department.
UPDATE `prf_header` h
JOIN `users` u ON u.id = h.requester_id
JOIN `common_code` c ON c.group_code = 'Department' AND (c.code = u.departemen OR c.description = u.departemen)
SET h.department_code = c.code
WHERE h.department_code IS NULL;

UPDATE `erf_header` h
JOIN `users` u ON u.id = h.requester_id
JOIN `common_code` c ON c.group_code = 'Department' AND (c.code = u.departemen OR c.description = u.departemen)
SET h.department_code = c.code
WHERE h.department_code IS NULL;

-- Profil user lama "MANAGEMENT" tidak cocok dengan nama master ("Managemen").
UPDATE `prf_header` h JOIN `users` u ON u.id = h.requester_id
SET h.department_code = 'MGM' WHERE h.department_code IS NULL AND UPPER(u.departemen) = 'MANAGEMENT';
UPDATE `erf_header` h JOIN `users` u ON u.id = h.requester_id
SET h.department_code = 'MGM' WHERE h.department_code IS NULL AND UPPER(u.departemen) = 'MANAGEMENT';

-- 3) IRF: nama departemen -> kode
UPDATE `consumable_request_header` h
JOIN `common_code` c ON c.group_code = 'Department' AND c.description = h.department AND c.code <> h.department
SET h.department = c.code;
UPDATE `consumable_request_header` SET department = 'MGM' WHERE UPPER(department) = 'MANAGEMENT';

-- 4) MRF (mr_header) belum punya kolom departemen sama sekali. Departemen
--    MRF = departemen pemohon; kolom teks ini yang dibandingkan dengan
--    departemen role (Production vs Production Warehouse, keduanya OWN DEPT).
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mr_header' AND COLUMN_NAME = 'department_code'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `mr_header` ADD COLUMN `department_code` VARCHAR(50) NULL DEFAULT NULL AFTER `requester_id`, ADD INDEX `idx_mr_header_department_code` (`department_code`)",
    "SELECT 'mr_header.department_code sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

UPDATE `mr_header` h
JOIN `users` u ON u.id = h.requester_id
JOIN `common_code` c ON c.group_code = 'Department' AND (c.code = u.departemen OR c.description = u.departemen)
SET h.department_code = c.code
WHERE h.department_code IS NULL;
UPDATE `mr_header` h JOIN `users` u ON u.id = h.requester_id
SET h.department_code = 'MGM' WHERE h.department_code IS NULL AND UPPER(u.departemen) = 'MANAGEMENT';
