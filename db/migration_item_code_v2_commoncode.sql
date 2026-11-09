-- =====================================================================
-- Follow-up migration_item_code_v2.sql: pindahkan pilihan "Jenis Item"
-- (Inventory) dan "Sub Kategori Produksi" (Material) dari ENUM hardcode
-- ke pola common_code, KONSISTEN dengan cara semua kategori lain diatur
-- di app ini (INVENTORY_CATEGORY, MATERIAL_CATEGORY, RM_CATEGORY,
-- ASSET_CATEGORY, JASA_CATEGORY, dst — semua lewat tabel common_code).
-- Idempotent — aman dijalankan ulang.
-- =====================================================================

-- 1) Longgarkan kolom dari ENUM ke VARCHAR supaya nilainya diatur common_code,
--    bukan di-hardcode di skema tabel (kalau nanti nambah/ubah, cukup lewat
--    Master Common Code, tidak perlu migration lagi).
ALTER TABLE inventories MODIFY COLUMN item_type VARCHAR(20) NOT NULL DEFAULT 'Stock';
ALTER TABLE materials MODIFY COLUMN sub_class VARCHAR(10) NULL;

-- 2) common_code: Jenis Item Inventory (MAIN code baru STK/ITM)
INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT
    'INVENTORY_ITEM_TYPE' AS group_code,
    'Jenis item Inventory (menentukan MAIN code kode barang baru: Stock->STK, Non-Stock->ITM)' AS group_description,
    'Stock' AS code, 'Stock (barang disimpan/tracked di stock)' AS description, 'active' AS status
) t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_ITEM_TYPE' AND code = 'Stock');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'INVENTORY_ITEM_TYPE', 'Jenis item Inventory (menentukan MAIN code kode barang baru: Stock->STK, Non-Stock->ITM)', 'Non-Stock', 'Non-Stock (langsung habis pakai, tidak disimpan)', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'INVENTORY_ITEM_TYPE' AND code = 'Non-Stock');

-- 3) common_code: Sub Kategori Produksi Material (SUB code baru RAW/SUP/CON/WIP)
INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT
    'MATERIAL_SUB_CLASS' AS group_code,
    'Sub kategori Material Production (kode master baru MTR-SUB-ORIGIN-NUMBER)' AS group_description,
    'RAW' AS code, 'Raw Material' AS description, 'active' AS status
) t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'MATERIAL_SUB_CLASS' AND code = 'RAW');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'MATERIAL_SUB_CLASS', 'Sub kategori Material Production (kode master baru MTR-SUB-ORIGIN-NUMBER)', 'SUP', 'Support Material', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'MATERIAL_SUB_CLASS' AND code = 'SUP');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'MATERIAL_SUB_CLASS', 'Sub kategori Material Production (kode master baru MTR-SUB-ORIGIN-NUMBER)', 'CON', 'Consumable', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'MATERIAL_SUB_CLASS' AND code = 'CON');

INSERT INTO common_code (group_code, group_description, code, description, status)
SELECT * FROM (SELECT 'MATERIAL_SUB_CLASS', 'Sub kategori Material Production (kode master baru MTR-SUB-ORIGIN-NUMBER)', 'WIP', 'Semifinished', 'active') t
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'MATERIAL_SUB_CLASS' AND code = 'WIP');
