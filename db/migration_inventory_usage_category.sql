-- ============================================================
-- Master Barang: kolom "Jenis Pemakaian" (General / Produksi)
-- ------------------------------------------------------------
-- User 2026-10-06: "saat pembuatan master barang untuk barang inventory ini
-- kasih 1 dropdown pilihan lagi, 1 general, 2 produksi. nah saat irf itu kan
-- pilih kategori kebutuhan, ketika pilih general ya hanya barang yang tipe nya
-- general yang muncul di search item nya, yang produksi juga gitu".
--
-- Sebelum ini SEMUA barang muncul di pencarian item IRF apa pun kategorinya --
-- kategori IRF cuma menentukan EMBER STOCK mana yang dibaca (stock_ledger.
-- category, lihat migration_stock_category_split.sql), bukan barang mana yang
-- boleh diminta.
--
-- Kolom BARU, beda dengan `kategori` yang sudah ada:
--   kategori        = sub-kategori kode barang (OFC, MTC, PROD, ...)
--   usage_category  = barang ini untuk keperluan General atau Produksi
--
-- Dua nilai saja, sesuai keputusan user: 'General' | 'Produksi'. Barang yang
-- kenyataannya dipakai dua-duanya HARUS pilih salah satu.
--
-- Idempotent: aman dijalankan berulang. Backfill di bawah SENGAJA cuma jalan
-- sekali -- yaitu saat kolomnya baru dibuat. Kalau migrasi ini diulang setelah
-- user membetulkan jenis pemakaian lewat Master Barang, hasil betulannya TIDAK
-- ditimpa balik.
-- ============================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventories' AND COLUMN_NAME = 'usage_category'
);

SET @sql := IF(@col = 0,
    "ALTER TABLE `inventories` ADD COLUMN `usage_category` VARCHAR(20) NOT NULL DEFAULT 'General' COMMENT 'Keperluan pemakaian: General | Produksi' AFTER `kategori`",
    "SELECT 'inventories.usage_category sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Backfill dari pemakaian NYATA di IRF (keputusan user: "ikut pemakaian nyata
-- di IRF", bukan "semua jadi General"). Barang ditandai Produksi kalau lebih
-- sering diminta lewat IRF berkategori Produksi daripada General; seri = tetap
-- General, karena General jalur yang lebih umum.
--
-- Barang yang belum pernah diminta sama sekali ikut DEFAULT kolom = General.
-- IRF berkategori 'Jasa' sengaja tidak ikut dihitung: kategori itu untuk
-- master Jasa, bukan barang.
SET @sql := IF(@col = 0,
    "UPDATE `inventories` i SET i.usage_category = 'Produksi'
     WHERE (SELECT COUNT(*) FROM consumable_request_details d
              JOIN consumable_request_header h ON h.id = d.header_id
             WHERE d.item_type = 'inventory' AND d.item_id = i.id AND h.category = 'Produksi')
         > (SELECT COUNT(*) FROM consumable_request_details d
              JOIN consumable_request_header h ON h.id = d.header_id
             WHERE d.item_type = 'inventory' AND d.item_id = i.id AND h.category = 'General')",
    "SELECT 'backfill dilewati (kolom sudah ada sebelum migrasi ini dijalankan)' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
