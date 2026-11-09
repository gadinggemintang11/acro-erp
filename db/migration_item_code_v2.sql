-- =====================================================================
-- Migration: Kode Master Barang Baru (format MAIN-SUB-NUMBER)
-- ERP Revision Notes 2026-09-02, item Fase 1 #6 (lihat erp_revision_code_system
-- di memory Claude). Idempotent — aman dijalankan ulang.
--
-- Scope (dikonfirmasi user): Inventory (STK/ITM) + Material (MTR) + Jasa (SRV).
-- Asset & Machine TIDAK disentuh (tetap pakai kode existing AST-/manual).
--
-- PENTING: berlaku HANYA untuk item BARU. Item lama (kode_barang/kode_material/
-- kode_jasa existing) TIDAK dimigrasikan/diubah — kolom ini cuma dipakai untuk
-- menentukan format kode barang baru ke depan.
-- =====================================================================

-- 1) Inventory: field baru "Jenis Item" (Stock/Non-Stock) -> jadi MAIN code (STK/ITM)
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventories' AND COLUMN_NAME = 'item_type'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE inventories ADD COLUMN item_type ENUM(''Stock'',''Non-Stock'') NOT NULL DEFAULT ''Stock'' AFTER kategori',
    'SELECT ''inventories.item_type sudah ada, skip'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2) Inventory: SUB code baru (General) -- group baru, TIDAK menimpa INVENTORY_CATEGORY lama
--    supaya kategori lama punya item existing tetap utuh & tetap muncul di form Edit.
--    Form Tambah Barang (item baru) pakai grup baru ini.
INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT
    'INVENTORY_SUB_CATEGORY' AS group_code,
    'Sub kategori barang General (kode master baru, MAIN-SUB-NUMBER)' AS group_description,
    'STA' AS code, 'Stationery' AS description, 'active' AS status
) t
WHERE NOT EXISTS (
    SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_SUB_CATEGORY' AND code = 'STA'
);

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'INVENTORY_SUB_CATEGORY', 'Sub kategori barang General (kode master baru, MAIN-SUB-NUMBER)', 'CVL', 'Building & Construction Item', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_SUB_CATEGORY' AND code = 'CVL');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'INVENTORY_SUB_CATEGORY', 'Sub kategori barang General (kode master baru, MAIN-SUB-NUMBER)', 'MEC', 'Piping, AC, Sanitary', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_SUB_CATEGORY' AND code = 'MEC');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'INVENTORY_SUB_CATEGORY', 'Sub kategori barang General (kode master baru, MAIN-SUB-NUMBER)', 'ELC', 'Cable, HV Electricity', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_SUB_CATEGORY' AND code = 'ELC');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'INVENTORY_SUB_CATEGORY', 'Sub kategori barang General (kode master baru, MAIN-SUB-NUMBER)', 'APL', 'Appliance', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_SUB_CATEGORY' AND code = 'APL');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'INVENTORY_SUB_CATEGORY', 'Sub kategori barang General (kode master baru, MAIN-SUB-NUMBER)', 'FUR', 'Furniture', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_SUB_CATEGORY' AND code = 'FUR');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'INVENTORY_SUB_CATEGORY', 'Sub kategori barang General (kode master baru, MAIN-SUB-NUMBER)', 'COM', 'Computers', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_SUB_CATEGORY' AND code = 'COM');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'INVENTORY_SUB_CATEGORY', 'Sub kategori barang General (kode master baru, MAIN-SUB-NUMBER)', 'ENT', 'Equipment & Tools', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_SUB_CATEGORY' AND code = 'ENT');

-- 3) Material: SUB class baru (Production) -> RAW/SUP/CON/WIP, dipakai bareng
--    kolom `asal` (Lokal/Import) yang sudah ada jadi origin flag L/I.
--    kategori/kategorirm lama TIDAK dihapus, tetap ada utk data existing.
SET @col_exists2 := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'materials' AND COLUMN_NAME = 'sub_class'
);
SET @sql2 := IF(@col_exists2 = 0,
    'ALTER TABLE materials ADD COLUMN sub_class ENUM(''RAW'',''SUP'',''CON'',''WIP'') NULL AFTER kategorirm',
    'SELECT ''materials.sub_class sudah ada, skip'''
);
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 4) Jasa: TIDAK ADA perubahan skema. Kode baru = SRV-{6digit} (tidak ada SUB
--    code yang didefinisikan user utk Service), cukup ubah generate_kode() di kode PHP.
