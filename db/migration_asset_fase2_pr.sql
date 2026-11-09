-- =====================================================================
-- Migration: Purchase Request Asset (FASE 2)
--
-- Mendaftarkan menu PR Asset + PR Asset Approval. Struktur tabel tidak
-- berubah sama sekali -- PR Asset memakai `pr_header`/`pr_details` dan
-- `Purchase_request_model` yang sama dengan PR lain, dibedakan lewat
-- `pr_header.type = 'asset'` (enum-nya sudah ditambahkan di FASE 0).
--
-- Menu sengaja didaftarkan di fase ini, bukan di FASE 0, supaya sidebar
-- tidak pernah menampilkan link ke controller yang belum ada.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Menu. Ditempatkan di WAREHOUSE GENERAL bersebelahan dengan PR Jasa
--    (order 86-87), mengikuti pengelompokan yang sudah ada.
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Asset Purchase Request', 'WAREHOUSE GENERAL', 'purchase_request_asset',          88),
    ('Asset PR Approval',      'WAREHOUSE GENERAL', 'purchase_request_approval_asset', 89)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- 2) Hak akses: salin dari menu analog PR Jasa, termasuk kolom bertingkat
--    (can_head_dept / can_endorsement / can_approval) supaya alur approval
--    3-stage langsung punya pejabat yang sama seperti PR Jasa.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'purchase_request_jasa'
JOIN `menus` m_new ON m_new.menu_key = 'purchase_request_asset'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'purchase_request_approval_jasa'
JOIN `menus` m_new ON m_new.menu_key = 'purchase_request_approval_asset'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

-- 3) Admin (role_id = 1) selalu akses penuh
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT 1, m.id, 1, 1, 1, 1, 1, 1, 1, 1
FROM `menus` m
WHERE m.menu_key IN ('purchase_request_asset', 'purchase_request_approval_asset')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x WHERE x.role_id = 1 AND x.menu_id = m.id
  );

-- 4) Verifikasi
SELECT m.menu_key, m.menu_name, m.department, m.order_no,
       COUNT(rp.id)                AS jml_role,
       SUM(rp.can_view)            AS bisa_lihat,
       SUM(rp.can_head_dept)       AS bisa_hod,
       SUM(rp.can_endorsement)     AS bisa_endorse,
       SUM(rp.can_approval)        AS bisa_approve
  FROM `menus` m
  LEFT JOIN `role_permissions` rp ON rp.menu_id = m.id
 WHERE m.menu_key IN ('purchase_request_asset', 'purchase_request_approval_asset')
 GROUP BY m.id;
