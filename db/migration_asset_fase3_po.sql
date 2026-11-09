-- =====================================================================
-- Migration: Purchase Order Asset (FASE 3)
--
-- Mendaftarkan menu PO Asset + PO Asset Approval. Tidak ada perubahan
-- struktur: PO Asset memakai `po_header`/`po_details` dan `Po_model` yang
-- sama, dibedakan lewat `po_header.type = 'asset'` (enum sudah ditambah
-- di FASE 0). Nomor PO memakai prefix baru 'POAST - ' (Po_model::generate_po_number).
--
-- Beda penting dari PO Jasa: PO Asset TIDAK punya "Selesaikan Jasa" yang
-- menulis ke grn_header/grn_details. Penerimaan aset ditangani langkah
-- "Terima Aset" di FASE 4 yang menulis ke `asset_register` per unit fisik.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Menu. Ditempatkan di departemen sendiri 'PURCHASING ASSET', sejajar
--    dengan pola 'PURCHASING JASA' (order 143-144).
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Create New PO Asset', 'PURCHASING ASSET', 'purchase_order_asset',          145),
    ('PO Asset Approval',   'PURCHASING ASSET', 'purchase_order_approval_asset', 146)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- 2) Hak akses: salin dari menu analog PO Jasa (termasuk can_confirm dan
--    kolom approval bertingkat).
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'purchase_order_jasa'
JOIN `menus` m_new ON m_new.menu_key = 'purchase_order_asset'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'purchase_order_approval_jasa'
JOIN `menus` m_new ON m_new.menu_key = 'purchase_order_approval_asset'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

-- 3) Admin (role_id = 1) selalu akses penuh
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT 1, m.id, 1, 1, 1, 1, 1, 1, 1, 1
FROM `menus` m
WHERE m.menu_key IN ('purchase_order_asset', 'purchase_order_approval_asset')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x WHERE x.role_id = 1 AND x.menu_id = m.id
  );

-- 4) Verifikasi
SELECT m.menu_key, m.menu_name, m.department, m.order_no,
       COUNT(rp.id)            AS jml_role,
       SUM(rp.can_view)        AS bisa_lihat,
       SUM(rp.can_add)         AS bisa_tambah,
       SUM(rp.can_confirm)     AS bisa_confirm,
       SUM(rp.can_approval)    AS bisa_approve
  FROM `menus` m
  LEFT JOIN `role_permissions` rp ON rp.menu_id = m.id
 WHERE m.menu_key IN ('purchase_order_asset', 'purchase_order_approval_asset')
 GROUP BY m.id;
