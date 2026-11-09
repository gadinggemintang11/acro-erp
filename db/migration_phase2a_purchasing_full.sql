-- ============================================================
-- PHASE 2A (FULL): Purchasing restructure + 4 new menus
-- Database: nsi-db
-- Date: 2026-05-18
-- Aman dijalankan berulang (idempotent).
-- ============================================================
-- Tujuan:
--   1. Rename PR Approval labels (akan dipindah ke Warehouse di sidebar)
--   2. Insert 4 menu baru: pmpo_status, gmpo_status, pmpo_list, gmpo_list
--   3. Grant default permissions
-- ============================================================

-- ============================================================
-- 1. RENAME PR Approval labels (menu yang akan dipindah ke Warehouse)
-- ============================================================
UPDATE `menus` SET `menu_name` = 'Material Item PR Approval'
WHERE `menu_key` = 'purchase_request_approval';

UPDATE `menus` SET `menu_name` = 'General Item PR Approval'
WHERE `menu_key` = 'purchase_request_approval_general';

-- Re-affirm PMPO/GMPO labels (Phase 2A original masih relevant)
UPDATE `menus` SET `menu_name` = 'Create New Production Material Purchase Order'
WHERE `menu_key` = 'purchase_order';

UPDATE `menus` SET `menu_name` = 'PMPO Approval'
WHERE `menu_key` = 'purchase_order_approval';

UPDATE `menus` SET `menu_name` = 'Create New General Purchase Order'
WHERE `menu_key` = 'purchase_order_general';

UPDATE `menus` SET `menu_name` = 'GMPO Approval'
WHERE `menu_key` = 'purchase_order_approval_general';

-- ============================================================
-- 2. INSERT 4 menu baru (idempotent)
-- ============================================================

-- PMPO STATUS (Production MPO status tracker)
INSERT INTO `menus` (`menu_name`, `menu_key`, `order_no`, `department`)
SELECT 'PMPO Status', 'pmpo_status', 56, 'PURCHASING'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `menu_key` = 'pmpo_status');

-- GMPO STATUS (General MPO status tracker)
INSERT INTO `menus` (`menu_name`, `menu_key`, `order_no`, `department`)
SELECT 'GMPO Status', 'gmpo_status', 84, 'PURCHASING'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `menu_key` = 'gmpo_status');

-- PMPO LIST (Report — POs type=produksi)
INSERT INTO `menus` (`menu_name`, `menu_key`, `order_no`, `department`)
SELECT 'PMPO List', 'pmpo_list', 90, 'PURCHASING'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `menu_key` = 'pmpo_list');

-- GMPO LIST (Report — POs type=non_produksi)
INSERT INTO `menus` (`menu_name`, `menu_key`, `order_no`, `department`)
SELECT 'GMPO List', 'gmpo_list', 91, 'PURCHASING'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `menu_key` = 'gmpo_list');

-- ============================================================
-- 3. GRANT default can_view ke semua role aktif untuk 4 menu baru
-- ============================================================
INSERT INTO `role_permissions`
  (role_id, menu_id, can_view, can_add, can_edit, can_delete, can_confirm, can_head_dept, can_endorsement, can_approval)
SELECT r.id, m.id, 1, 0, 0, 0, 0, 0, 0, 0
FROM `roles` r
CROSS JOIN `menus` m
WHERE m.menu_key IN ('pmpo_status', 'gmpo_status', 'pmpo_list', 'gmpo_list')
  AND r.status = 'active'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp
    WHERE rp.role_id = r.id AND rp.menu_id = m.id
  );

-- ============================================================
-- VERIFIKASI
-- ============================================================
SELECT m.id, m.menu_key, m.menu_name, m.department, m.order_no,
       COUNT(rp.id) AS roles_with_view
FROM `menus` m
LEFT JOIN `role_permissions` rp ON rp.menu_id = m.id AND rp.can_view = 1
WHERE m.menu_key IN (
  'purchase_request_approval','purchase_request_approval_general',
  'purchase_order','purchase_order_approval',
  'purchase_order_general','purchase_order_approval_general',
  'pmpo_status','gmpo_status','pmpo_list','gmpo_list',
  'mir','po_report'
)
GROUP BY m.id, m.menu_key, m.menu_name, m.department, m.order_no
ORDER BY m.order_no, m.menu_key;
