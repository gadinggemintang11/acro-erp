-- =====================================================================
-- Migration: Purchase Request Jasa (langkah PR sebelum PO Jasa)
--
-- Mengikuti pola PR General: PR Jasa dibuat & disetujui (3-stage) lebih
-- dulu, baru dari situ "Buat PO" mengonversinya jadi Purchase Order Jasa
-- (po_header.pr_header_id terisi). Reuse tabel pr_header/pr_details dan
-- Purchase_request_model yang sama, dibedakan lewat pr_header.type='jasa'.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Tambah value 'jasa' ke pr_header.type (additive, tidak mengubah data lama)
ALTER TABLE `pr_header` MODIFY `type` ENUM('produksi','non_produksi','jasa') NOT NULL DEFAULT 'produksi';

-- 2) Daftarkan menu (menu_key UNIQUE -> upsert)
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Jasa Purchase Request',   'WAREHOUSE GENERAL', 'purchase_request_jasa',          86),
    ('Jasa PR Approval',        'WAREHOUSE GENERAL', 'purchase_request_approval_jasa', 87)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- 3) Hak akses: copy seluruh kolom permission dari menu analog general
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'purchase_request_general'
JOIN `menus` m_new ON m_new.menu_key = 'purchase_request_jasa'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'purchase_request_approval_general'
JOIN `menus` m_new ON m_new.menu_key = 'purchase_request_approval_jasa'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

-- 4) Pastikan Admin (role_id = 1) selalu punya akses penuh ke 2 menu baru
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT 1, m.id, 1, 1, 1, 1, 1, 1, 1, 1
FROM `menus` m
WHERE m.menu_key IN ('purchase_request_jasa', 'purchase_request_approval_jasa')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x WHERE x.role_id = 1 AND x.menu_id = m.id
  );
