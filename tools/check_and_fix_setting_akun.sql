-- =====================================================
-- QUERY FINAL: CHECK & FIX SETTING AKUN
-- =====================================================
-- File ini untuk memastikan setting_accounting lengkap
-- TIDAK menambah setting untuk kas/bank (user pilih manual)
-- =====================================================

-- 1. CEK SETTING YANG ADA
-- =====================================================
SELECT 
    setting_key,
    setting_name,
    currency,
    account_id,
    CASE 
        WHEN account_id IS NULL THEN '❌ BELUM DI-SET'
        ELSE '✅ SUDAH DI-SET'
    END as status
FROM setting_accounting
ORDER BY setting_key, currency;

-- =====================================================
-- HASIL YANG DIHARAPKAN:
-- =====================================================
-- setting_key                 | currency | status
-- akun_utang_usaha           | IDR      | ✅ SUDAH DI-SET
-- akun_utang_usaha           | USD      | ✅ SUDAH DI-SET
-- akun_piutang_usaha         | IDR      | ✅ SUDAH DI-SET
-- akun_piutang_usaha         | USD      | ✅ SUDAH DI-SET
-- akun_ppn_masukan           | IDR      | ✅ SUDAH DI-SET
-- akun_ppn_keluaran          | IDR      | ✅ SUDAH DI-SET
-- akun_penjualan_fg          | IDR      | ✅ SUDAH DI-SET
-- akun_penjualan_fg          | USD      | ✅ SUDAH DI-SET
-- akun_penjualan_scrap       | IDR      | ✅ SUDAH DI-SET
-- akun_hpp_fg                | IDR      | ✅ SUDAH DI-SET
-- akun_persediaan_fg         | IDR      | ✅ SUDAH DI-SET
-- dll...

-- 2. HAPUS SETTING YANG TIDAK PERLU
-- =====================================================
-- Setting kas/bank tidak diperlukan karena user pilih manual

-- HAPUS setting yang tidak diperlukan
DELETE FROM setting_accounting 
WHERE setting_key IN (
    'akun_pembayaran_ulang',  -- Tidak perlu, user pilih kas/bank manual
    'akun_penerimaan_piutang', -- Tidak perlu, user pilih kas/bank manual
    'akun_kas_kecil',          -- Tidak perlu, user pilih dari dropdown
    'akun_laba_selisih_kurs',  -- Tidak perlu jika tidak dipakai
    'akun_rugi_selisih_kurs'   -- Tidak perlu jika tidak dipakai
);

-- 3. TAMBAH SETTING JIKA BELUM ADA (OPSIONAL)
-- =====================================================
-- Hanya jalankan INSERT jika setting belum ada dan memang diperlukan

-- Akun Beban Umum (untuk AP Invoice non-GRN)
INSERT IGNORE INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`) 
VALUES
('akun_beban_umum', 'Akun Beban Umum', 'IDR', NULL),
('akun_beban_umum', 'Akun Beban Umum', 'USD', NULL),
('akun_beban_umum', 'Akun Beban Umum', 'RMB', NULL);

-- Akun Selisih Kurs (untuk transaksi multi-currency)
INSERT IGNORE INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`) 
VALUES
('akun_selisih_kurs', 'Akun Selisih Kurs', 'IDR', NULL);

-- 4. UPDATE SETTING YANG NULL (CONTOH)
-- =====================================================
-- Ganti dengan ID akun yang sesuai dari chart_of_accounts

-- Contoh: Set akun beban umum untuk IDR
-- UPDATE setting_accounting 
-- SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '6.102.001.00' LIMIT 1)
-- WHERE setting_key = 'akun_beban_umum' AND currency = 'IDR';

-- 5. VERIFY SETTING LENGKAP
-- =====================================================
SELECT 
    COUNT(*) as total_settings,
    SUM(CASE WHEN account_id IS NULL THEN 1 ELSE 0 END) as belum_diset,
    SUM(CASE WHEN account_id IS NOT NULL THEN 1 ELSE 0 END) as sudah_diset
FROM setting_accounting;

-- HASIL YANG DIHARAPKAN:
-- total_settings | belum_diset | sudah_diset
-- 20             | 5           | 15
-- (Angka bisa berbeda tergantung jumlah setting)

-- 6. CEK AKUN KAS/BANK AKTIF
-- =====================================================
SELECT 
    id,
    account_code,
    account_name,
    currency,
    status,
    CASE 
        WHEN status = 'active' THEN '✅ AKTIF'
        ELSE '❌ NON-AKTIF'
    END as status_display
FROM chart_of_accounts
WHERE (account_code LIKE '1.101%' OR account_code LIKE '1.102%')
  AND is_header = 0
ORDER BY account_code;

-- HASIL YANG DIHARAPKAN:
-- Semua akun kas/bank status = active
-- Contoh:
-- 1.101.001.00 | Kas Kecil                        | IDR | ✅ AKTIF
-- 1.101.002.00 | Kas Besar                        | IDR | ✅ AKTIF
-- 1.102.001.00 | Bank BCA-8030736789              | IDR | ✅ AKTIF
-- 1.102.002.00 | Bank Mandiri-1350052013669 (IDR) | IDR | ✅ AKTIF
-- 1.102.003.00 | Bank Mandiri-1350052016886 (USD) | USD | ✅ AKTIF
-- dll...

-- 7. LIST SETTING YANG BELUM DIISI
-- =====================================================
SELECT 
    setting_key,
    setting_name,
    currency,
    'Silakan setting di menu Setting Akun' as action
FROM setting_accounting
WHERE account_id IS NULL
ORDER BY setting_key, currency;

-- 8. REKOMENDASI MAPPING AKUN (CONTOH)
-- =====================================================
-- Sesuaikan dengan struktur COA Anda
-- Ini hanya contoh, JANGAN langsung execute!

/*
-- Piutang Usaha
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '1.103.001.00' LIMIT 1) WHERE setting_key = 'akun_piutang_usaha' AND currency = 'IDR';
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '1.103.002.00' LIMIT 1) WHERE setting_key = 'akun_piutang_usaha' AND currency = 'USD';

-- Utang Usaha
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '2.101.001.00' LIMIT 1) WHERE setting_key = 'akun_utang_usaha' AND currency = 'IDR';
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '2.101.002.00' LIMIT 1) WHERE setting_key = 'akun_utang_usaha' AND currency = 'USD';

-- PPN
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '1.104.001.00' LIMIT 1) WHERE setting_key = 'akun_ppn_masukan' AND currency = 'IDR';
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '2.102.001.00' LIMIT 1) WHERE setting_key = 'akun_ppn_keluaran' AND currency = 'IDR';

-- Penjualan
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '4.101.001.00' LIMIT 1) WHERE setting_key = 'akun_penjualan_fg' AND currency = 'IDR';
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '4.101.002.00' LIMIT 1) WHERE setting_key = 'akun_penjualan_fg' AND currency = 'USD';

-- HPP & Persediaan
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '5.101.001.00' LIMIT 1) WHERE setting_key = 'akun_hpp_fg' AND currency = 'IDR';
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '1.105.001.00' LIMIT 1) WHERE setting_key = 'akun_persediaan_fg' AND currency = 'IDR';

-- Beban
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '6.102.001.00' LIMIT 1) WHERE setting_key = 'akun_beban_umum' AND currency = 'IDR';

-- Selisih Kurs
UPDATE setting_accounting SET account_id = (SELECT id FROM chart_of_accounts WHERE account_code = '7.201.001.00' LIMIT 1) WHERE setting_key = 'akun_selisih_kurs' AND currency = 'IDR';
*/

-- =====================================================
-- CATATAN PENTING:
-- =====================================================
-- 1. Jalankan query SELECT dulu untuk CEK
-- 2. Sesuaikan account_code dengan COA Anda
-- 3. UPDATE hanya untuk setting yang NULL
-- 4. Verify di menu Setting Akun setelah update
-- 5. KAS/BANK tidak perlu di-setting (user pilih manual)
-- =====================================================

-- =====================================================
-- KESIMPULAN:
-- =====================================================
-- Setting Akun yang PERLU diisi:
-- ✅ Piutang Usaha (IDR, USD, RMB)
-- ✅ Utang Usaha (IDR, USD, RMB)
-- ✅ PPN Masukan (IDR)
-- ✅ PPN Keluaran (IDR)
-- ✅ Penjualan FG (IDR, USD, RMB)
-- ✅ Penjualan Scrap (IDR)
-- ✅ HPP FG (IDR)
-- ✅ Persediaan FG (IDR)
-- ✅ Beban Umum (IDR, USD) - opsional
-- ✅ Selisih Kurs (IDR) - opsional

-- Setting Akun yang TIDAK PERLU:
-- ❌ Kas/Bank Penerimaan → User pilih saat transaksi
-- ❌ Kas/Bank Pembayaran → User pilih saat transaksi
-- =====================================================
