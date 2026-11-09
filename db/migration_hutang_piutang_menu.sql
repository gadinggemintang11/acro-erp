-- =====================================================================
-- Migration: Menu "Daftar Hutang" (hutang) & "Daftar Piutang" (piutang)
-- Laporan read-only AP/AR monitoring (outstanding, aging, belum difaktur).
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Daftarkan menu (menu_key UNIQUE -> upsert)
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Daftar Hutang',  'ACCOUNTING', 'hutang',  348),
    ('Daftar Piutang', 'ACCOUNTING', 'piutang', 349)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- 2) Hak akses VIEW:
--    'hutang'  diberikan ke setiap role yang sudah bisa melihat 'apinvoice'
--    'piutang' diberikan ke setiap role yang sudah bisa melihat 'arinvoice'
--    (read-only: hanya can_view = 1)

-- 2a) hutang  <- mengikuti apinvoice
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`)
SELECT rp.role_id, m_new.id, 1
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'apinvoice'
JOIN `menus` m_new ON m_new.menu_key = 'hutang'
WHERE rp.can_view = 1
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
  );

-- 2b) piutang <- mengikuti arinvoice
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`)
SELECT rp.role_id, m_new.id, 1
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'arinvoice'
JOIN `menus` m_new ON m_new.menu_key = 'piutang'
WHERE rp.can_view = 1
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
  );

-- 2c) Pastikan Admin (role_id = 1) selalu punya akses ke kedua menu
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`)
SELECT 1, m.id, 1
FROM `menus` m
WHERE m.menu_key IN ('hutang', 'piutang')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = 1 AND x.menu_id = m.id
  );
