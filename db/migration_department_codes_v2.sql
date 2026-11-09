-- ============================================================
-- Kode Departemen versi baru (sesuai Common Code di server production)
-- ------------------------------------------------------------
-- User 2026-09-15: klien sudah membuat master Department baru di production:
--   ACC  ACCOUNTING DEPT. inc. FINANCE;COST CONTROLER;TAX
--   ADM  ADMINISTRATION DEPARTEMENT inc. HR;GA;MAINT
--   GWH  GENERAL WAREHOUSE
--   LOG  LOGISTIC inc. EXPORT;IMPORT
--   MGM  MANAGEMENT
--   PIE  PRODUCTION & INDUSTRIAL ENGINEERING
--   PRO  PRODUCTION
--   PUR  PURCHASING
--   PWH  PRODUCTION WAREHOUSE
--   QAD  QUALITY ASSURANCE
-- Kode ini yang dipakai Template PDF ROLE LEVEL & pembatasan departemen.
--
-- Data lama masih menyimpan kode/nama lama (FIN ACT, I E, PROD, Q A, PCH,
-- HRGA, MANAGEMENT, EXIM, FACTORY, OFFICE, ...). Migrasi ini:
--   1. Menambahkan 10 kode di atas kalau belum ada (deskripsi yang sudah
--      dibuat klien TIDAK ditimpa).
--   2. Mengonversi nilai lama -> kode baru di semua kolom departemen.
--      Pemetaan kode tanpa pasangan (dikonfirmasi user): FACTORY & OFFICE ->
--      MGM, EXIM -> LOG. Nilai lepas yang ditemukan di data: FAT -> ACC,
--      MTC (maintenance) -> ADM, DIR -> MGM, Produksi -> PRO.
--   3. Menghapus baris kode lama dari master Department (setelah semua data
--      yang memakainya dikonversi).
--
-- HARUS dijalankan SEBELUM migration_role_position.sql dst. supaya backfill
-- departemen di migrasi-migrasi itu sudah memakai kode baru.
-- Idempotent: aman dijalankan berulang.
-- ============================================================

-- 1) Kode baru
INSERT INTO `common_code` (`group_code`, `group_description`, `code`, `description`, `status`)
SELECT 'Department', 'Departemen', v.code, v.description, 'active'
FROM (
    SELECT 'ACC' AS code, 'ACCOUNTING DEPT. inc. FINANCE;COST CONTROLER;TAX' AS description
    UNION ALL SELECT 'ADM', 'ADMINISTRATION DEPARTEMENT inc. HR;GA;MAINT'
    UNION ALL SELECT 'GWH', 'GENERAL WAREHOUSE'
    UNION ALL SELECT 'LOG', 'LOGISTIC inc. EXPORT;IMPORT'
    UNION ALL SELECT 'MGM', 'MANAGEMENT'
    UNION ALL SELECT 'PIE', 'PRODUCTION & INDUSTRIAL ENGINEERING'
    UNION ALL SELECT 'PRO', 'PRODUCTION'
    UNION ALL SELECT 'PUR', 'PURCHASING'
    UNION ALL SELECT 'PWH', 'PRODUCTION WAREHOUSE'
    UNION ALL SELECT 'QAD', 'QUALITY ASSURANCE'
) v
WHERE NOT EXISTS (SELECT 1 FROM `common_code` c WHERE c.group_code = 'Department' AND c.code = v.code);

-- 2) Peta nilai lama -> kode baru (dibandingkan UPPER + TRIM)
DROP TEMPORARY TABLE IF EXISTS `tmp_dept_map`;
CREATE TEMPORARY TABLE `tmp_dept_map` (`old_value` VARCHAR(100) PRIMARY KEY, `new_code` VARCHAR(20) NOT NULL);
INSERT INTO `tmp_dept_map` (`old_value`, `new_code`) VALUES
    ('FIN ACT', 'ACC'), ('FINANCE & ACCOUNTING', 'ACC'), ('FAT', 'ACC'), ('ACCOUNTING', 'ACC'),
    ('HRGA', 'ADM'), ('MTC', 'ADM'), ('MAINTENANCE', 'ADM'),
    ('EXIM', 'LOG'), ('EXPORT IMPORT', 'LOG'), ('LOGISTIC', 'LOG'),
    ('MANAGEMENT', 'MGM'), ('MANAGEMEN', 'MGM'), ('FACTORY', 'MGM'), ('OFFICE', 'MGM'), ('DIR', 'MGM'),
    ('I E', 'PIE'), ('INDUSTRIAL ENGINEERING', 'PIE'),
    ('PROD', 'PRO'), ('PRODUKSI', 'PRO'), ('PRODUCTION', 'PRO'),
    ('PCH', 'PUR'), ('PURCHASING', 'PUR'),
    ('WAREHOUSE & SHIPPING', 'PWH'),
    ('Q A', 'QAD'), ('QUALITY ASSURANCE', 'QAD'),
    ('GENERAL WAREHOUSE', 'GWH');

-- Kolom yang PASTI ada
UPDATE `users` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.departemen)) SET t.departemen = m.new_code;
UPDATE `roles` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department)) SET t.department = m.new_code;
UPDATE `consumable_request_header` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department)) SET t.department = m.new_code;
UPDATE `consumable_out_header` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department)) SET t.department = m.new_code;
UPDATE `pr_header` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department)) SET t.department = m.new_code;
UPDATE `po_header` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department)) SET t.department = m.new_code;
UPDATE `machines` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.pic_departemen)) SET t.pic_departemen = m.new_code;

-- Kolom yang dibuat migrasi lain (bisa belum ada) -> hanya kalau kolomnya ada
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roles' AND COLUMN_NAME = 'department_code');
SET @sql := IF(@c > 0, "UPDATE `roles` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department_code)) SET t.department_code = m.new_code", "SELECT 'roles.department_code belum ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_header' AND COLUMN_NAME = 'department_code');
SET @sql := IF(@c > 0, "UPDATE `prf_header` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department_code)) SET t.department_code = m.new_code", "SELECT 'prf_header.department_code belum ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'erf_header' AND COLUMN_NAME = 'department_code');
SET @sql := IF(@c > 0, "UPDATE `erf_header` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department_code)) SET t.department_code = m.new_code", "SELECT 'erf_header.department_code belum ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mr_header' AND COLUMN_NAME = 'department_code');
SET @sql := IF(@c > 0, "UPDATE `mr_header` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department_code)) SET t.department_code = m.new_code", "SELECT 'mr_header.department_code belum ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'doc_documents');
SET @sql := IF(@c > 0, "UPDATE `doc_documents` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department_code)) SET t.department_code = m.new_code", "SELECT 'doc_documents belum ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register');
SET @sql := IF(@c > 0, "UPDATE `asset_register` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department)) SET t.department = m.new_code", "SELECT 'asset_register belum ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_depreciation_details');
SET @sql := IF(@c > 0, "UPDATE `asset_depreciation_details` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.department)) SET t.department = m.new_code", "SELECT 'asset_depreciation_details belum ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @c := (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'qa_sampling_header');
SET @sql := IF(@c > 0, "UPDATE `qa_sampling_header` t JOIN `tmp_dept_map` m ON m.old_value = UPPER(TRIM(t.requester_dept)) SET t.requester_dept = m.new_code", "SELECT 'qa_sampling_header belum ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 3) Hapus kode lama dari master (datanya sudah dikonversi di atas)
DELETE FROM `common_code`
WHERE `group_code` = 'Department'
  AND UPPER(TRIM(`code`)) IN (SELECT `old_value` FROM `tmp_dept_map`)
  AND `code` NOT IN ('ACC', 'ADM', 'GWH', 'LOG', 'MGM', 'PIE', 'PRO', 'PUR', 'PWH', 'QAD');

DROP TEMPORARY TABLE IF EXISTS `tmp_dept_map`;
