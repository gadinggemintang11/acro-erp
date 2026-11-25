-- ============================================================
-- MIGRATION: Biaya tambahan di Faktur Supplier (AP Invoice), mis. biaya kirim.
-- PO tidak memuat biaya kirim; biaya itu baru muncul di tagihan supplier, jadi
-- dicatat sebagai baris "biaya tambahan" di faktur (Berdasarkan PO / GRN):
--   Dr Biaya Pengiriman (akun bisa diganti per baris)
--   Cr Hutang Dagang (ikut total faktur)
-- Kolom baru di ap_invoice_details:
--   is_extra_cost = 1 -> baris biaya tambahan (bukan baris barang PO / GRN)
--   ppn_exempt    = 1 -> baris ini TIDAK ikut dasar pengenaan PPN faktur
-- Setting Akun baru `akun_biaya_kirim_pembelian` = akun default baris biaya
-- tambahan (diisi 6.100.003 Biaya Pengiriman bila ada; bisa diganti di menu
-- Setting Akun).
-- Pola information_schema + PREPARE supaya jalan di MySQL & MariaDB.
-- Idempotent: aman di-rerun.
-- ============================================================

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ap_invoice_details' AND COLUMN_NAME = 'is_extra_cost');
SET @sql := IF(@c = 0,
    'ALTER TABLE ap_invoice_details ADD COLUMN is_extra_cost TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = baris biaya tambahan (mis. biaya kirim), bukan barang PO/GRN''',
    'SELECT ''ap_invoice_details.is_extra_cost sudah ada, skip''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ap_invoice_details' AND COLUMN_NAME = 'ppn_exempt');
SET @sql := IF(@c = 0,
    'ALTER TABLE ap_invoice_details ADD COLUMN ppn_exempt TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''1 = baris tidak ikut dasar pengenaan PPN faktur''',
    'SELECT ''ap_invoice_details.ppn_exempt sudah ada, skip''');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO setting_accounting (setting_key, setting_name, currency, account_id)
SELECT 'akun_biaya_kirim_pembelian',
       'Akun Biaya Kirim Pembelian (default biaya tambahan Faktur Supplier)',
       'IDR',
       (SELECT id FROM chart_of_accounts WHERE account_code = '6.100.003' AND is_header = 0 LIMIT 1)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM setting_accounting WHERE setting_key = 'akun_biaya_kirim_pembelian' AND currency = 'IDR');

-- Verifikasi
SHOW COLUMNS FROM ap_invoice_details WHERE Field IN ('is_extra_cost', 'ppn_exempt');
SELECT setting_key, currency, account_id FROM setting_accounting WHERE setting_key = 'akun_biaya_kirim_pembelian';
