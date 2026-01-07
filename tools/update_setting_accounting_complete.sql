-- =====================================================
-- REVISI LENGKAP SETTING ACCOUNTING
-- =====================================================
-- File ini menambahkan setting akun yang kurang untuk 
-- membuat sistem accounting lebih lengkap dan konsisten
-- =====================================================

-- Tambah setting akun yang baru untuk kas/bank penerimaan dan pembayaran
-- Gunakan AUTO_INCREMENT untuk id, sesuaikan dengan id terakhir yang ada

-- 1. Akun Kas/Bank untuk Penerimaan Piutang (AR Receipt)
INSERT INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`) VALUES
('akun_penerimaan_piutang', 'Akun Penerimaan Piutang (Kas/Bank)', 'IDR', NULL),
('akun_penerimaan_piutang', 'Akun Penerimaan Piutang (Kas/Bank)', 'USD', NULL),
('akun_penerimaan_piutang', 'Akun Penerimaan Piutang (Kas/Bank)', 'RMB', NULL);

-- 2. Akun Kas/Bank untuk Pembayaran Utang (AP Payment)
INSERT INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`) VALUES
('akun_pembayaran_utang', 'Akun Pembayaran Utang (Kas/Bank)', 'IDR', NULL),
('akun_pembayaran_utang', 'Akun Pembayaran Utang (Kas/Bank)', 'USD', NULL),
('akun_pembayaran_utang', 'Akun Pembayaran Utang (Kas/Bank)', 'RMB', NULL);

-- 3. Akun Beban untuk AP Invoice (jika belum ada)
INSERT INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`) VALUES
('akun_beban_umum', 'Akun Beban Umum', 'IDR', NULL),
('akun_beban_umum', 'Akun Beban Umum', 'USD', NULL),
('akun_beban_umum', 'Akun Beban Umum', 'RMB', NULL);

-- 4. Akun Selisih Kurs (untuk transaksi multi-currency)
INSERT INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`) VALUES
('akun_selisih_kurs', 'Akun Selisih Kurs', 'IDR', NULL);

-- 5. Akun Kas Besar/Petty Cash (opsional tapi penting)
INSERT INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`) VALUES
('akun_kas_kecil', 'Akun Kas Kecil (Petty Cash)', 'IDR', NULL);

-- =====================================================
-- CATATAN PENTING:
-- =====================================================
-- Setelah run SQL ini, admin HARUS setting manual di menu Setting Akun:
-- 1. akun_penerimaan_piutang (IDR) → pilih Bank/Kas utama untuk terima uang
-- 2. akun_penerimaan_piutang (USD) → pilih Bank USD untuk terima USD
-- 3. akun_pembayaran_utang (IDR) → pilih Bank/Kas utama untuk bayar supplier
-- 4. akun_pembayaran_utang (USD) → pilih Bank USD untuk bayar supplier USD
-- 5. akun_beban_umum → pilih akun beban untuk faktur non-GRN
-- 6. akun_selisih_kurs → pilih akun selisih kurs
-- =====================================================
