-- =====================================================================
-- Migration: PR List & PO List (monitoring lintas-tipe)
--
-- ERP Revision Notes, [[erp_revision_monitoring_lists]]: PR List & PO List
-- ternyata SEBAGIAN BESAR sudah ada (Pmpo_list/Gmpo_list utk PO per-tipe,
-- Purchase_request*::index() utk PR per-tipe) — yang belum ada cuma VIEW
-- GABUNGAN lintas 4 tipe (produksi/non_produksi/jasa/asset) dalam 1 layar.
--
-- Menu baru:
--   po_monitoring — Po_monitoring.php, reuse Po_report_model + view
--                    purchase_dashboard/list yang sudah ada (di-generalize).
--   pr_monitoring — Pr_monitoring.php, model baru
--                    Purchase_request_model::get_all_monitoring().
--
-- Read-only murni (cuma action 'view') — tidak menduplikasi CRUD tipe manapun.
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('PO List (Semua Tipe)', 'PURCHASING MATERIAL', 'po_monitoring', 173),
    ('PR List (Semua Tipe)', 'PURCHASING MATERIAL', 'pr_monitoring', 174)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- Hak akses: copy dari menu analog yang sudah ada (po_report/purchase_request)
-- sbg starting point — role yang sudah bisa lihat laporan PO/PR otomatis
-- kebagian akses monitoring gabungan ini juga.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, 0, 0, rp.can_delete, 0, 0, 0, 0
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'po_report'
JOIN `menus` m_new ON m_new.menu_key = 'po_monitoring'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, 0, 0, 0, 0, 0, 0, 0
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'purchase_request'
JOIN `menus` m_new ON m_new.menu_key = 'pr_monitoring'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

-- Admin (role_id = 1) selalu full akses ke menu baru.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT 1, m.id, 1, 0, 0, 1, 0, 0, 0, 0
FROM `menus` m
WHERE m.menu_key IN ('po_monitoring', 'pr_monitoring')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x WHERE x.role_id = 1 AND x.menu_id = m.id
  );
