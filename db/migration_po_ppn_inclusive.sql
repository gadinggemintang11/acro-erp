-- ============================================================
-- Purchase Order: PPN include / exclude
-- ------------------------------------------------------------
-- Permintaan klien 2026-10-02: "kalo VAT nya di include kan maka VAT akan
-- di ambil dari nilai total nya. tapi kalo VAT ga di include kan, berarti
-- VAT di hitung dari subtotal dari nilai barang. dan vat bisa di input
-- manual berapa persen karna vat ga harus 11%".
--
-- Sebelum ini PO hanya punya saklar `has_ppn` (ya/tidak) dan seluruh kode
-- menghitungnya SATU cara saja -- selalu ditambahkan di atas subtotal --
-- bahkan beberapa tempat masih mematok 11% (0.11 / 1.11) walau kolom
-- `ppn_rate` sudah ada dan terisi 10 maupun 11.
--
-- Kolom baru `ppn_inclusive`:
--   0 = EXCLUDE (harga barang belum termasuk PPN)
--       PPN   = subtotal x rate%
--       Total = subtotal + PPN
--   1 = INCLUDE (harga barang sudah termasuk PPN)
--       Total = subtotal apa adanya
--       PPN   = Total x rate / (100 + rate)
--       DPP   = Total - PPN
--
-- Default 0 supaya SELURUH PO yang sudah ada tetap dihitung persis seperti
-- sebelumnya -- tidak ada angka dokumen lama yang berubah.
--
-- Sekalian: PO lama yang has_ppn=1 tapi ppn_rate masih 0 diisi 11 supaya
-- tidak jadi "ada PPN tapi 0%" setelah persentasenya dipakai sungguhan
-- (sebelumnya nilai kolom ini diabaikan di banyak tempat).
--
-- Idempotent: aman dijalankan berulang.
-- ============================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'po_header' AND COLUMN_NAME = 'ppn_inclusive'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `po_header` ADD COLUMN `ppn_inclusive` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = harga sudah termasuk PPN' AFTER `ppn_rate`",
    "SELECT 'po_header.ppn_inclusive sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Hanya menyentuh baris yang jelas keliru: ada PPN tapi persentasenya 0.
UPDATE `po_header`
   SET `ppn_rate` = 11.00
 WHERE `has_ppn` = 1
   AND (`ppn_rate` IS NULL OR `ppn_rate` <= 0);
