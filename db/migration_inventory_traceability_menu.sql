-- ============================================================
-- Menu "Penelusuran Barang General" (Inventory Traceability)
-- ------------------------------------------------------------
-- User 2026-09-16: Material Traceability ternyata hanya untuk item tipe
-- material; barang general (master `inventories`) belum punya padanannya.
-- Modul baru `inventory_traceability` menelusuri satu barang general lintas
-- dokumen: IRF -> PR -> PO -> Penerimaan (SGR) -> Pengeluaran -> Kartu Stok.
--
-- Migrasi ini HANYA mendaftarkan menunya supaya bisa diatur di Role Access
-- (tidak ada tabel/kolom baru — semua datanya dibaca dari dokumen yang ada).
-- Hak aksesnya sengaja TIDAK diberikan otomatis ke role mana pun: atur
-- sendiri di Role Access, atau lewat Template PDF untuk role gudang.
-- Idempotent: aman dijalankan berulang.
-- ============================================================

INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`)
SELECT 'General Item Traceability', 'WAREHOUSE COMMON', 'inventory_traceability',
       COALESCE((SELECT m.order_no FROM (SELECT order_no FROM menus WHERE menu_key = 'material_traceability') m), 102)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `menu_key` = 'inventory_traceability');

-- Role yang sudah boleh memakai Material Traceability biasanya orang gudang
-- yang sama — beri hak View yang setara supaya tidak perlu diatur ulang satu
-- per satu. Hanya baris yang BELUM ada yang dibuat (tidak menimpa pengaturan).
-- Kolom yang disebut SENGAJA hanya role_id, menu_id & can_view: sisanya sudah
-- ber-default 0, dan menyebut kolom lain (mis. `can_submit`, yang baru ada
-- setelah migrasi Role Level) membuat perintah ini gagal di database yang
-- migrasi tersebut belum dijalankan -- persis yang terjadi di production
-- 16 Sep 2026: "Unknown column 'can_submit' in 'INSERT INTO'".
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`)
SELECT rp.role_id,
       (SELECT id FROM menus WHERE menu_key = 'inventory_traceability'),
       rp.can_view
FROM `role_permissions` rp
WHERE rp.menu_id = (SELECT id FROM menus WHERE menu_key = 'material_traceability')
  AND rp.can_view = 1
  AND NOT EXISTS (
        SELECT 1 FROM `role_permissions` x
        WHERE x.role_id = rp.role_id
          AND x.menu_id = (SELECT id FROM menus WHERE menu_key = 'inventory_traceability')
  );
