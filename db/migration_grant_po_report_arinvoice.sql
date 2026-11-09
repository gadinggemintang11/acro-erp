-- ============================================================
-- MIGRATION: Grant default can_view untuk `po_report` & `arinvoice`
-- ke semua role aktif supaya link sidebar muncul.
-- Idempotent: skip role yang sudah punya row permission untuk menu tsb.
-- Setelah jalan, Role Access bisa fine-tune (add/edit/delete) per role.
-- ============================================================

INSERT INTO `role_permissions`
  (role_id, menu_id, can_view, can_add, can_edit, can_delete, can_confirm, can_head_dept, can_endorsement, can_approval)
SELECT r.id, m.id, 1, 0, 0, 0, 0, 0, 0, 0
FROM `roles` r
CROSS JOIN `menus` m
WHERE m.menu_key IN ('po_report', 'arinvoice')
  AND r.status = 'active'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp
    WHERE rp.role_id = r.id AND rp.menu_id = m.id
  );

-- ============================================================
-- VERIFIKASI
-- ============================================================
SELECT m.menu_key, m.menu_name,
       (SELECT COUNT(*) FROM `role_permissions` rp
        WHERE rp.menu_id = m.id AND rp.can_view = 1) AS roles_with_view
FROM `menus` m
WHERE m.menu_key IN ('po_report', 'arinvoice');
