-- =====================================================================
-- Migration: SGR (Supplier General Item Receipt) — kategori pindah ke
-- HEADER (General/Produksi/Lainnya) + pisah menu sidebar per kategori.
--
-- ERP Revision Notes 2026-09-02, Fase 2 poin 9 — prasyarat wajib sebelum
-- halaman chooser "Pemasukan" (RIF-General/RIF-Produksi/RMF) bisa dibangun.
-- Dikonfirmasi user: SEBELUMNYA kategori ada di level PER-ITEM
-- (sgr_details.category, TIDAK PERNAH benar-benar dipakai — 0 baris terisi
-- di data existing), sekarang jadi 1 DOKUMEN = 1 KATEGORI (dipilih sekali
-- saat bikin SGR baru), persis pola Consumable_out.
--
-- 'Lainnya' = kategori ke-3 KHUSUS SGR (tidak ada di Consumable_out) utk
-- dokumen yang sama sekali bukan dari alur IRF consumable (mis. terima
-- mesin yang dibeli langsung). Dokumen 'Lainnya' TETAP di menu blanket lama
-- (`supplier_general_item_receipt`) — tidak dapat menu kategori baru
-- sendiri, karena chooser "Pemasukan" cuma py 2 kotak RIF (General/Produksi).
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Kolom category di sgr_header (varchar, pola common_code-friendly kalau
--    nanti mau diformalkan; untuk sekarang cukup in_list check di kode PHP
--    spt Consumable_out, tidak perlu tabel common_code baru).
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sgr_header' AND COLUMN_NAME = 'category'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE sgr_header ADD COLUMN category VARCHAR(20) NOT NULL DEFAULT ''Lainnya'' AFTER warehouse_type',
    'SELECT ''sgr_header.category sudah ada, skip'''
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill baris lama eksplisit ke 'Lainnya' (bukan cuma DEFAULT saat ALTER,
-- jaga-jaga kalau kolom sudah ada dari percobaan sebelumnya dgn NULL).
UPDATE sgr_header SET category = 'Lainnya' WHERE category IS NULL OR category = '';

-- 2) Daftarkan 2 menu baru (menu_key UNIQUE -> upsert)
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Penerimaan Barang - General',  'WAREHOUSE GENERAL', 'supplier_general_item_receipt_general',  80),
    ('Penerimaan Barang - Produksi', 'WAREHOUSE GENERAL', 'supplier_general_item_receipt_produksi', 80)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- 3) Salin role_permissions dari 'supplier_general_item_receipt' (lama) ke
--    kedua menu baru — starting point, supaya tidak ada role yang mendadak
--    kehilangan akses begitu fitur ini live.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_confirm`, `can_delete`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_confirm, rp.can_delete
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'supplier_general_item_receipt'
JOIN `menus` m_new ON m_new.menu_key IN ('supplier_general_item_receipt_general', 'supplier_general_item_receipt_produksi')
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x
    WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

-- 4) Pastikan Admin (role_id = 1) selalu punya akses penuh ke keduanya.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_confirm`, `can_delete`)
SELECT 1, m.id, 1, 1, 1, 1, 1
FROM `menus` m
WHERE m.menu_key IN ('supplier_general_item_receipt_general', 'supplier_general_item_receipt_produksi')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = 1 AND x.menu_id = m.id
  );
