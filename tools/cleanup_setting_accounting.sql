-- =====================================================
-- CLEANUP SETTING ACCOUNTING
-- =====================================================
-- Hapus setting yang TIDAK DIPERLUKAN
-- Berdasarkan screenshot: akun_pembayaran_ulang, dll
-- =====================================================

-- 1. BACKUP DULU (OPSIONAL)
-- =====================================================
CREATE TABLE IF NOT EXISTS setting_accounting_backup_20251211 AS 
SELECT * FROM setting_accounting;

-- 2. CEK SETTING YANG AKAN DIHAPUS
-- =====================================================
SELECT 
    setting_key,
    setting_name,
    currency,
    account_id,
    'AKAN DIHAPUS - Tidak diperlukan, user pilih manual' as status
FROM setting_accounting
WHERE setting_key IN (
    'akun_pembayaran_ulang',
    'akun_penerimaan_piutang',
    'akun_kas_kecil',
    'akun_laba_selisih_kurs',
    'akun_rugi_selisih_kurs'
);

-- 3. HAPUS SETTING YANG TIDAK PERLU
-- =====================================================
-- COPY PASTE QUERY INI KE phpMyAdmin:

DELETE FROM setting_accounting 
WHERE setting_key IN (
    'akun_pembayaran_ulang',
    'akun_penerimaan_piutang',
    'akun_kas_kecil',
    'akun_laba_selisih_kurs',
    'akun_rugi_selisih_kurs'
)
OR (setting_key = 'akun_piutang_usaha' AND currency IN ('USD', 'RMB'))
OR (setting_key = 'akun_utang_usaha' AND currency IN ('USD', 'RMB'))
OR (setting_key = 'akun_penjualan_fg' AND currency IN ('USD', 'RMB'))
OR (setting_key = 'akun_beban_umum' AND currency IN ('USD', 'RMB'));    -- ❌ Beban hanya perlu 1 akun

-- 4. VERIFY HASIL SETELAH DELETE
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
-- HASIL YANG DIHARAPKAN SETELAH CLEANUP:
-- =====================================================
-- HANYA setting berikut yang TERSISA:
-- 
-- setting_key              | currency | account_id | status
-- akun_beban_umum         | IDR      | 67         | ✅ SUDAH DI-SET
-- akun_beban_umum         | RMB      | NULL       | ❌ BELUM DI-SET
-- akun_beban_umum         | USD      | NULL       | ❌ BELUM DI-SET
-- akun_closing_grn        | IDR      | 42         | ✅ SUDAH DI-SET
-- akun_grn_diadjust       | IDR      | 43         | ✅ SUDAH DI-SET
-- akun_hpp_fg             | IDR      | 87         | ✅ SUDAH DI-SET
-- akun_kas_kecil          | IDR      | 32         | ✅ SUDAH DI-SET
-- akun_laba_selisih_kurs  | IDR      | 33         | ✅ SUDAH DI-SET
-- akun_pembayaran_ulang   | IDR      | 35         | ✅ SUDAH DI-SET
-- akun_pembayaran_ulang   | RMB      | NULL       | ❌ BELUM DI-SET
-- akun_pembayaran_ulang   | USD      | 36         | ✅ SUDAH DI-SET
-- akun_penerimaan_piutang | IDR      | 37         | ✅ SUDAH DI-SET
-- akun_penerimaan_piutang | RMB      | NULL       | ❌ BELUM DI-SET
-- akun_penerimaan_piutang | USD      | 38         | ✅ SUDAH DI-SET
-- akun_penjualan_fg       | IDR      | 84         | ✅ SUDAH DI-SET
-- akun_penjualan_fg       | RMB      | NULL       | ❌ BELUM DI-SET
-- akun_penjualan_fg       | USD      | 38         | ✅ SUDAH DI-SET
-- akun_penjualan_scrap    | IDR      | 85         | ✅ SUDAH DI-SET
-- akun_persediaan_fg      | IDR      | 53         | ✅ SUDAH DI-SET
-- akun_piutang_usaha      | IDR      | 40         | ✅ SUDAH DI-SET
-- akun_piutang_usaha      | RMB      | NULL       | ❌ BELUM DI-SET
-- akun_piutang_usaha      | USD      | 38         | ✅ SUDAH DI-SET
-- akun_ppn_keluaran       | IDR      | 71         | ✅ SUDAH DI-SET
-- akun_ppn_masukan        | IDR      | 55         | ✅ SUDAH DI-SET
-- akun_rugi_selisih_kurs  | IDR      | 137        | ✅ SUDAH DI-SET
-- akun_utang_usaha        | IDR      | 40         | ✅ SUDAH DI-SET
-- akun_utang_usaha        | RMB      | NULL       | ❌ BELUM DI-SET
-- akun_utang_usaha        | USD      | 38         | ✅ SUDAH DI-SET
--
-- SETELAH DELETE, yang TERSISA hanya:
-- ✅ akun_piutang_usaha (IDR only) → Currency di field transaksi
-- ✅ akun_utang_usaha (IDR only) → Currency di field transaksi
-- ✅ akun_penjualan_fg (IDR only) → Currency di field transaksi
-- ✅ akun_penjualan_scrap (IDR only)
-- ✅ akun_hpp_fg (IDR only)
-- ✅ akun_persediaan_fg (IDR only)
-- ✅ akun_ppn_masukan (IDR only)
-- ✅ akun_ppn_keluaran (IDR only)
-- ✅ akun_beban_umum (IDR only) → Currency di field transaksi
-- ✅ akun_closing_grn (IDR only)
-- ✅ akun_grn_diadjust (IDR only)
-- (dan setting lain yang BENAR-BENAR dipakai di kode)

-- 5. SUMMARY
-- =====================================================
SELECT 
    COUNT(*) as total_settings,
    COUNT(DISTINCT setting_key) as unique_keys,
    SUM(CASE WHEN account_id IS NULL THEN 1 ELSE 0 END) as belum_diset,
    SUM(CASE WHEN account_id IS NOT NULL THEN 1 ELSE 0 END) as sudah_diset
FROM setting_accounting;

-- =====================================================
-- CATATAN:
-- =====================================================
-- Yang DIHAPUS:
-- ❌ akun_pembayaran_ulang → User pilih kas/bank saat AP Payment
-- ❌ akun_penerimaan_piutang → User pilih kas/bank saat AR Receipt
-- ❌ akun_kas_kecil → User pilih dari dropdown
-- ❌ akun_laba/rugi_selisih_kurs → Tidak dipakai di kode
--
-- Yang TETAP DIPERTAHANKAN:
-- ✅ akun_piutang_usaha (IDR) → SATU akun untuk semua currency
-- ✅ akun_utang_usaha (IDR) → SATU akun untuk semua currency
-- ✅ akun_penjualan_fg (IDR) → SATU akun untuk semua currency
-- ✅ akun_ppn_masukan/keluaran (IDR) → Otomatis dari setting
-- ✅ akun_hpp_fg, persediaan_fg (IDR) → Otomatis dari setting
-- ✅ akun_beban_umum (IDR) → SATU akun untuk semua currency
--
-- CATATAN PENTING:
-- Currency IDR/USD/RMB disimpan di FIELD transaksi, BUKAN di akun COA
-- Misal: Invoice USD $1000 → dicatat di "Piutang Dagang" yang sama
--        dengan Invoice IDR Rp 10jt, tapi field currency berbeda
-- =====================================================
