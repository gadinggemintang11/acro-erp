-- =====================================================================
-- Migration: Menu "IRF Status" (irf_status)
-- Modul baru read-only: status & analisis PER NOMOR IRF
-- (diminta berapa, sudah di-release berapa, outstanding berapa).
--
-- Menu lama 'consumable_outstanding' (dulu bernama "IRF Status") diubah
-- namanya jadi "IRF Outstanding" karena isinya agregasi PER ITEM,
-- bukan status per dokumen IRF.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Daftarkan menu baru (menu_key UNIQUE -> upsert)
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('IRF Status', 'REQUISITION', 'irf_status', 32)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- 2) Rename menu lama supaya tidak rancu di halaman Role Access
UPDATE `menus`
SET `menu_name` = 'IRF Outstanding', `order_no` = 33
WHERE `menu_key` = 'consumable_outstanding';

-- 3) Hak akses VIEW: ikuti role yang sudah bisa melihat 'consumable_outstanding'
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`)
SELECT rp.role_id, m_new.id, 1
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'consumable_outstanding'
JOIN `menus` m_new ON m_new.menu_key = 'irf_status'
WHERE rp.can_view = 1
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
  );

-- 4) Fallback: role yang bisa lihat IRF (consumable_request) juga boleh lihat statusnya
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`)
SELECT rp.role_id, m_new.id, 1
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'consumable_request'
JOIN `menus` m_new ON m_new.menu_key = 'irf_status'
WHERE rp.can_view = 1
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
  );

-- 5) Pastikan Admin (role_id = 1) selalu punya akses
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`)
SELECT 1, m.id, 1
FROM `menus` m
WHERE m.menu_key = 'irf_status'
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = 1 AND x.menu_id = m.id
  );
