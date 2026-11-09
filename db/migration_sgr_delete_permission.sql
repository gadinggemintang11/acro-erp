-- ============================================================
-- Migration: hak akses DELETE untuk Supplier General Item Receipt (SGR)
-- Tombol "Hapus" di list SGR hanya muncul untuk status Draft DAN
-- role yang punya can_delete. Role yang boleh membuat/edit SGR
-- (can_add + can_edit) seharusnya juga boleh membuang draft-nya sendiri.
--
-- Sebelum migrasi ini: hanya Admin (1) & HRGA Staff (13) yang punya can_delete,
-- sedangkan Warehouse & Shipping Staff (15) — pembuat SGR sehari-hari — tidak.
-- ============================================================

UPDATE role_permissions rp
JOIN menus m ON m.id = rp.menu_id
SET rp.can_delete = 1
WHERE m.menu_key = 'supplier_general_item_receipt'
  AND rp.can_add = 1
  AND rp.can_edit = 1
  AND rp.can_delete = 0;

-- Verifikasi:
-- SELECT rp.role_id, r.role_name, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete
-- FROM role_permissions rp
-- JOIN menus m ON m.id = rp.menu_id
-- LEFT JOIN roles r ON r.id = rp.role_id
-- WHERE m.menu_key = 'supplier_general_item_receipt'
-- ORDER BY rp.can_delete DESC, rp.role_id;

-- Rollback (kembalikan hanya role 15):
-- UPDATE role_permissions rp JOIN menus m ON m.id = rp.menu_id
-- SET rp.can_delete = 0
-- WHERE m.menu_key = 'supplier_general_item_receipt' AND rp.role_id = 15;
