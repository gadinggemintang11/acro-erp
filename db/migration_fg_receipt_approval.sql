-- ============================================================
-- FG RECEIPT APPROVAL WORKFLOW
-- Database: nsi-db
-- Date: 2026-05-16
-- Aman dijalankan berulang (idempotent).
-- ============================================================
-- Workflow baru:
--   Draft → Submitted → Approved Head Dept → Endorsed → Approved → stock masuk ledger
--                                                            ↑
--                                              fg_in fire HANYA di final approve
-- + Reject (final) & Return to Draft (creator revisi)
--
-- Backward compat: existing 'Confirmed' rows tetap valid (stock sudah masuk).
-- ============================================================

-- ------------------------------------------------------------
-- STEP 1: Pastikan kolom status fleksibel (VARCHAR), bukan ENUM
-- ------------------------------------------------------------
ALTER TABLE `fg_receipt_header` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'Draft';

-- ------------------------------------------------------------
-- STEP 2: Tambah kolom audit approval (idempotent)
-- ------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'fg_receipt_header'
    AND COLUMN_NAME  = 'submitted_by'
);
SET @sql := IF(@col_exists = 0,
  'ALTER TABLE `fg_receipt_header`
     ADD COLUMN `submitted_by`          INT(11)  NULL DEFAULT NULL AFTER `confirmed_by`,
     ADD COLUMN `submitted_at`          DATETIME NULL DEFAULT NULL AFTER `submitted_by`,
     ADD COLUMN `hod_approved_by`       INT(11)  NULL DEFAULT NULL AFTER `submitted_at`,
     ADD COLUMN `hod_approved_at`       DATETIME NULL DEFAULT NULL AFTER `hod_approved_by`,
     ADD COLUMN `endorsed_by`           INT(11)  NULL DEFAULT NULL AFTER `hod_approved_at`,
     ADD COLUMN `endorsed_at`           DATETIME NULL DEFAULT NULL AFTER `endorsed_by`,
     ADD COLUMN `final_approved_by`     INT(11)  NULL DEFAULT NULL AFTER `endorsed_at`,
     ADD COLUMN `final_approved_at`     DATETIME NULL DEFAULT NULL AFTER `final_approved_by`,
     ADD COLUMN `rejected_by`           INT(11)  NULL DEFAULT NULL AFTER `final_approved_at`,
     ADD COLUMN `rejected_at`           DATETIME NULL DEFAULT NULL AFTER `rejected_by`,
     ADD COLUMN `reject_reason`         TEXT     NULL DEFAULT NULL AFTER `rejected_at`,
     ADD COLUMN `returned_to_draft_by`  INT(11)  NULL DEFAULT NULL AFTER `reject_reason`,
     ADD COLUMN `returned_to_draft_at`  DATETIME NULL DEFAULT NULL AFTER `returned_to_draft_by`,
     ADD COLUMN `return_reason`         TEXT     NULL DEFAULT NULL AFTER `returned_to_draft_at`',
  'SELECT "fg_receipt_header approval columns already exist, skipped." AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- STEP 3: Insert menu baru `fg_receipt_approval`
-- ------------------------------------------------------------
INSERT INTO `menus` (`menu_name`, `menu_key`, `order_no`, `department`)
SELECT 'FG Receipt Approval', 'fg_receipt_approval', 25, 'FINISHED GOODS'
FROM dual
WHERE NOT EXISTS (
  SELECT 1 FROM `menus` WHERE `menu_key` = 'fg_receipt_approval'
);

-- ------------------------------------------------------------
-- STEP 4: Grant default can_view ke semua role aktif
-- can_head_dept / can_endorsement / can_approval default 0 (admin set manual)
-- ------------------------------------------------------------
INSERT INTO `role_permissions`
  (role_id, menu_id, can_view, can_add, can_edit, can_delete, can_confirm, can_head_dept, can_endorsement, can_approval)
SELECT r.id, m.id, 1, 0, 0, 0, 0, 0, 0, 0
FROM `roles` r
CROSS JOIN `menus` m
WHERE m.menu_key = 'fg_receipt_approval'
  AND r.status = 'active'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp
    WHERE rp.role_id = r.id AND rp.menu_id = m.id
  );

-- ------------------------------------------------------------
-- STEP 5: Backfill — existing 'Confirmed' rows dianggap sudah final approved
-- (stock sudah masuk ledger, jangan reproses)
-- ------------------------------------------------------------
UPDATE `fg_receipt_header`
SET `final_approved_by` = `confirmed_by`,
    `final_approved_at` = `confirmed_at`
WHERE `status` = 'Confirmed'
  AND `final_approved_by` IS NULL;

-- ------------------------------------------------------------
-- STEP 6: Verifikasi
-- ------------------------------------------------------------
SELECT
  m.id, m.menu_key, m.menu_name, m.department,
  COUNT(rp.id) AS roles_with_view
FROM `menus` m
LEFT JOIN `role_permissions` rp
  ON rp.menu_id = m.id AND rp.can_view = 1
WHERE m.menu_key IN ('fg_receipt', 'fg_receipt_approval', 'fg_stock', 'fg_delivery')
GROUP BY m.id, m.menu_key, m.menu_name, m.department
ORDER BY m.menu_key;
