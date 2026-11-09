-- ============================================================
-- Master Supplier: kolom "Nama Akun" (pemilik rekening)
-- ------------------------------------------------------------
-- User 2026-10-02: "di master supplier tambah kolom Nama Akun, jadi ada Nama
-- Bank, Nomor Rekening, Nama akun. lalu cek di prf, nama akun ditambahkan di
-- print pdf, karna kemaren nama akun nya di ambil dari nama supplier".
--
-- Sebelum ini cetak PRF menulis baris ACCOUNT sebagai
-- "<no. rekening> / <nama vendor>" -- pemilik rekening DITEBAK dari nama
-- supplier. Itu sering salah: rekening bisa atas nama pemilik usaha, bukan
-- nama badan usahanya.
--
-- Dua kolom:
--   suppliers.bank_account_name   -- sumber datanya, diisi di Master Supplier
--   prf_header.bank_account_name  -- SALINAN saat PRF dibuat, sejalan dgn
--                                    bank_name & bank_account yang memang
--                                    sudah disalin ke PRF. Dokumen yang sudah
--                                    terbit tidak boleh ikut berubah kalau
--                                    master suppliernya nanti diedit.
--
-- NULL/kosong = belum diisi; cetakan PRF jatuh kembali ke nama vendor seperti
-- perilaku lama, jadi dokumen lama tetap tercetak wajar.
--
-- ERF sengaja TIDAK ikut: itu reimburse ke karyawan, pemilik rekeningnya
-- memang karyawan itu sendiri (user: "erf gausah kan itu reimburse diambil
-- dari karyawan").
--
-- Idempotent: aman dijalankan berulang.
-- ============================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'suppliers' AND COLUMN_NAME = 'bank_account_name'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `suppliers` ADD COLUMN `bank_account_name` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Nama pemilik rekening' AFTER `bank_account`",
    "SELECT 'suppliers.bank_account_name sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_header' AND COLUMN_NAME = 'bank_account_name'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `prf_header` ADD COLUMN `bank_account_name` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Salinan nama pemilik rekening saat PRF dibuat' AFTER `bank_account`",
    "SELECT 'prf_header.bank_account_name sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
