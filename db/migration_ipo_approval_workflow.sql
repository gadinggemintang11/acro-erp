-- ============================================================
-- IPO APPROVAL WORKFLOW: Menu, Status, & Audit Columns Setup
-- Database: nsi-db (sales_contracts)
-- Date: 2026-05-15
-- Aman dijalankan berulang (idempotent).
-- ============================================================
-- Workflow baru:
--   Draft → Pending Endorsement → Endorsed → Open (= Approved)
--   Setelah Fg_delivery selesai semua → Finish
--   Status `Open` tetap dipakai modul lain (PR, WO, BOM, Fg_delivery).
-- ============================================================

-- ------------------------------------------------------------
-- STEP 1: Update enum status sales_contracts
-- Tambahkan status baru tanpa membuang status lama.
-- ------------------------------------------------------------
ALTER TABLE `sales_contracts`
  MODIFY `status` ENUM(
    'Draft',
    'Pending Endorsement',
    'Endorsed',
    'Open',
    'Closed',
    'Cancelled',
    'Finish',
    'Rejected'
  ) NOT NULL DEFAULT 'Draft';

-- ------------------------------------------------------------
-- STEP 2: Tambah kolom audit untuk approval workflow
-- (Aman: cek dulu sebelum ADD COLUMN)
-- ------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'sales_contracts'
    AND COLUMN_NAME = 'submitted_at'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `sales_contracts`
     ADD COLUMN `submitted_by`  INT(11)   NULL DEFAULT NULL AFTER `status`,
     ADD COLUMN `submitted_at`  DATETIME  NULL DEFAULT NULL AFTER `submitted_by`,
     ADD COLUMN `endorsed_by`   INT(11)   NULL DEFAULT NULL AFTER `submitted_at`,
     ADD COLUMN `endorsed_at`   DATETIME  NULL DEFAULT NULL AFTER `endorsed_by`,
     ADD COLUMN `approved_by`   INT(11)   NULL DEFAULT NULL AFTER `endorsed_at`,
     ADD COLUMN `approved_at`   DATETIME  NULL DEFAULT NULL AFTER `approved_by`,
     ADD COLUMN `rejected_by`   INT(11)   NULL DEFAULT NULL AFTER `approved_at`,
     ADD COLUMN `rejected_at`   DATETIME  NULL DEFAULT NULL AFTER `rejected_by`,
     ADD COLUMN `reject_reason` TEXT      NULL DEFAULT NULL AFTER `rejected_at`',
  'SELECT "Approval audit columns already exist, skipped." AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- STEP 3: Rename existing menu_name 'Sales_contract' → 'Create New IPO'
-- (Key tetap 'sales_contract' supaya tidak break permission lama.)
-- ------------------------------------------------------------
UPDATE `menus`
SET `menu_name` = 'Create New IPO'
WHERE `menu_key` = 'sales_contract';

-- ------------------------------------------------------------
-- STEP 4: Insert menu IPO Approval & IPO List (idempotent)
-- ------------------------------------------------------------
INSERT INTO `menus` (`menu_name`, `menu_key`, `order_no`, `department`)
SELECT 'IPO Approval', 'sales_contract_approval', 15, 'Marketing'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `menus` WHERE `menu_key` = 'sales_contract_approval'
);

INSERT INTO `menus` (`menu_name`, `menu_key`, `order_no`, `department`)
SELECT 'IPO List', 'sales_contract_list', 16, 'Marketing'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `menus` WHERE `menu_key` = 'sales_contract_list'
);

-- ------------------------------------------------------------
-- STEP 5: Beri permission default ke setiap role aktif
-- - sales_contract_approval: can_view default 1, endorsement & approval default 0
--   (admin perlu set manual siapa yang boleh endorse / approve)
-- - sales_contract_list: can_view default 1 (read-only untuk semua role)
-- ------------------------------------------------------------
INSERT INTO `role_permissions`
  (role_id, menu_id, can_view, can_add, can_edit, can_delete, can_confirm, can_head_dept, can_endorsement, can_approval)
SELECT r.id, m.id, 1, 0, 0, 0, 0, 0, 0, 0
FROM `roles` r
CROSS JOIN `menus` m
WHERE m.menu_key IN ('sales_contract_approval', 'sales_contract_list')
  AND r.status = 'active'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp
    WHERE rp.role_id = r.id AND rp.menu_id = m.id
  );

-- ------------------------------------------------------------
-- STEP 6: Verifikasi
-- ------------------------------------------------------------
SELECT
  m.id, m.menu_key, m.menu_name, m.department,
  COUNT(rp.id) AS roles_with_view
FROM `menus` m
LEFT JOIN `role_permissions` rp
  ON rp.menu_id = m.id AND rp.can_view = 1
WHERE m.menu_key IN ('sales_contract', 'sales_contract_approval', 'sales_contract_list')
GROUP BY m.id, m.menu_key, m.menu_name, m.department
ORDER BY m.menu_key;
