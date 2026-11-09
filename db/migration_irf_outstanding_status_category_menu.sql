-- =====================================================================
-- Migration: Pisah menu sidebar "IRF Outstanding" (consumable_outstanding)
-- & "IRF Status" (irf_status) jadi 2 pintu per kategori: General & Produksi.
--
-- Pola persis sama dengan migration_consumable_out_category_menu.sql:
-- 1 controller/1 tabel di belakang tiap modul, cuma gerbang izinnya yang
-- dipecah supaya Role Access bisa mengatur General vs Produksi ke orang
-- berbeda. Role_permissions lama di-salin dulu sebagai starting point
-- (supaya tidak ada yang mendadak kehilangan akses), lalu admin
-- mempersempit manual dari halaman Role Access.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Daftarkan 4 menu baru
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('IRF Outstanding - General',  'REQUISITION', 'consumable_outstanding_general',  34),
    ('IRF Outstanding - Produksi', 'REQUISITION', 'consumable_outstanding_produksi', 35),
    ('IRF Status - General',       'REQUISITION', 'irf_status_general',              36),
    ('IRF Status - Produksi',      'REQUISITION', 'irf_status_produksi',             37)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- 2) Salin role_permissions dari menu lama masing-masing ke 2 menu baru.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'consumable_outstanding'
JOIN `menus` m_new ON m_new.menu_key IN ('consumable_outstanding_general', 'consumable_outstanding_produksi')
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x
    WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'irf_status'
JOIN `menus` m_new ON m_new.menu_key IN ('irf_status_general', 'irf_status_produksi')
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x
    WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

-- 3) Admin (role_id = 1) selalu punya akses penuh ke keempatnya.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`)
SELECT 1, m.id, 1, 1, 1, 1
FROM `menus` m
WHERE m.menu_key IN ('consumable_outstanding_general', 'consumable_outstanding_produksi', 'irf_status_general', 'irf_status_produksi')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = 1 AND x.menu_id = m.id
  );
