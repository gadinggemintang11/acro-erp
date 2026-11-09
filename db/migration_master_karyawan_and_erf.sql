-- ============================================================
-- MIGRATION: Master Karyawan + ERF reimbursement (bank dari karyawan)
-- ------------------------------------------------------------
-- 1. Tabel master_karyawan (Nama, NIP, Bank, No. Rekening, status).
-- 2. Menu "Master Karyawan" di group MASTER DATA + hak akses (copy
--    dari master_supplier supaya role yang sudah lihat Master Data
--    langsung dapat akses).
-- 3. erf_header: kolom karyawan_id + karyawan_name (penerima reimburse).
--    Kolom bank_name / bank_account tetap dipakai sebagai snapshot,
--    tapi sumbernya kini dari master_karyawan, bukan supplier.
-- Idempotent: aman di-rerun (MariaDB).
-- ============================================================

-- ------------------------------------------------------------
-- 1. TABEL MASTER KARYAWAN
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `master_karyawan` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `nama_karyawan` VARCHAR(150) NOT NULL,
    `nip`           VARCHAR(50)  DEFAULT NULL,
    `bank_name`     VARCHAR(100) DEFAULT NULL,
    `bank_account`  VARCHAR(50)  DEFAULT NULL,
    `status`        ENUM('active','inactive') DEFAULT 'active',
    `created_by`    INT(11)      DEFAULT NULL,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
    `updated_by`    INT(11)      DEFAULT NULL,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_mk_status` (`status`),
    KEY `idx_mk_nip` (`nip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. MENU + HAK AKSES
-- ------------------------------------------------------------
-- menu_key UNIQUE → INSERT IGNORE bikin idempotent.
INSERT IGNORE INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`)
VALUES ('Master Karyawan', 'MASTER DATA', 'master_karyawan', 518);

SET @kary_menu_id = (SELECT `id` FROM `menus` WHERE `menu_key` = 'master_karyawan' LIMIT 1);

-- Snapshot hak akses master_supplier ke temp (tanpa baca ulang role_permissions
-- saat INSERT, agar tidak kena batasan MySQL "insert into & select from same table").
DROP TEMPORARY TABLE IF EXISTS `tmp_kary_perm`;
CREATE TEMPORARY TABLE `tmp_kary_perm` AS
SELECT rp.`role_id`, rp.`can_view`, rp.`can_add`, rp.`can_edit`, rp.`can_confirm`,
       rp.`can_head_dept`, rp.`can_endorsement`, rp.`can_approval`, rp.`can_delete`
FROM `role_permissions` rp
JOIN `menus` m ON rp.`menu_id` = m.`id`
WHERE LOWER(m.`menu_key`) = 'master_supplier';

-- Reset perm menu ini lalu isi ulang dari snapshot (idempotent).
DELETE FROM `role_permissions` WHERE `menu_id` = @kary_menu_id;

INSERT INTO `role_permissions`
    (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_confirm`,
     `can_head_dept`, `can_endorsement`, `can_approval`, `can_delete`)
SELECT t.`role_id`, @kary_menu_id, t.`can_view`, t.`can_add`, t.`can_edit`, t.`can_confirm`,
       t.`can_head_dept`, t.`can_endorsement`, t.`can_approval`, t.`can_delete`
FROM `tmp_kary_perm` t;

DROP TEMPORARY TABLE IF EXISTS `tmp_kary_perm`;

-- Pastikan Admin (role_id=1) selalu full akses meski master_supplier belum ter-grant.
INSERT INTO `role_permissions`
    (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_confirm`,
     `can_head_dept`, `can_endorsement`, `can_approval`, `can_delete`)
SELECT 1, @kary_menu_id, 1, 1, 1, 0, 0, 0, 0, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` WHERE `role_id` = 1 AND `menu_id` = @kary_menu_id
);

-- ------------------------------------------------------------
-- 3. ERF HEADER: kolom karyawan (penerima reimburse)
-- ------------------------------------------------------------
ALTER TABLE `erf_header`
    ADD COLUMN IF NOT EXISTS `karyawan_id`   INT(11)      DEFAULT NULL AFTER `requester_name`,
    ADD COLUMN IF NOT EXISTS `karyawan_name` VARCHAR(150) DEFAULT NULL AFTER `karyawan_id`;
