-- =====================================================================
-- Migration: Isi Setting Akun dengan COA Acropoly (2026-11-07)
-- Prasyarat: db/migration_coa_acropoly.sql sudah dijalankan.
--
-- HANYA mengisi setting yang masih kosong (account_id IS NULL), jadi pilihan
-- Accounting yang sudah diset tidak tertimpa. Aman dijalankan ulang.
-- Akun dicari lewat account_code (bukan id) dan harus aktif & non-header.
--
-- Sengaja DIBIARKAN KOSONG (tidak ada akun yang cocok di COA Acropoly):
--   akun_wht42          -- belum ada akun Hutang PPh 4(2) / PPh Final.
--                          Jurnal Umum dengan WHT 4(2) akan ditolak sampai diisi.
--   akun_pendapatan_foc -- belum dipakai kode mana pun.
-- Catatan:
--   akun_exchange_gain diarahkan ke 8.100.001 Rugi Selisih Kurs (satu akun
--   untuk laba & rugi kurs) karena COA belum punya akun Laba Selisih Kurs.
--   AR Receipt valas wajib punya akun untung & rugi kurs.
-- =====================================================================

START TRANSACTION;

UPDATE setting_accounting s
JOIN (
              SELECT 'akun_utang_usaha'      AS k, '2.101.001' AS code  -- Hutang Dagang
    UNION ALL SELECT 'akun_piutang_usaha',          '1.103.001'          -- Piutang Dagang
    UNION ALL SELECT 'akun_ppn_masukan',            '1.108.001'          -- PPN Masukan
    UNION ALL SELECT 'akun_ppn_keluaran',           '2.102.001'          -- PPN Keluaran
    UNION ALL SELECT 'akun_utang_pph_23',           '2.102.003'          -- PPh Pasal 23
    UNION ALL SELECT 'akun_wht23',                  '2.102.003'          -- PPh Pasal 23
    UNION ALL SELECT 'akun_grn_clearing',           '2.104.998'          -- Hutang Belum ditagih
    UNION ALL SELECT 'akun_penjualan_fg',           '4.101.001'          -- Penjualan Expor
    UNION ALL SELECT 'akun_penjualan_scrap',        '4.101.002'          -- Penjualan Limbah
    UNION ALL SELECT 'akun_hpp_fg',                 '5.100.003'          -- Harga Pokok Barang Jadi
    UNION ALL SELECT 'akun_persediaan_fg',          '1.105.300'          -- Persediaan Barang Jadi
    UNION ALL SELECT 'akun_beban_umum',             '6.200.017'          -- Biaya Umum dan Adm
    UNION ALL SELECT 'akun_biaya_admin_bank',       '7.300.001'          -- Biaya Admin Bank
    UNION ALL SELECT 'akun_selisih_kurs',           '8.100.001'          -- Rugi Selisih Kurs
    UNION ALL SELECT 'akun_exchange_loss',          '8.100.001'          -- Rugi Selisih Kurs
    UNION ALL SELECT 'akun_exchange_gain',          '8.100.001'          -- (belum ada Laba Selisih Kurs)
) m ON m.k = s.setting_key
JOIN chart_of_accounts c ON c.account_code = m.code AND c.is_header = 0 AND c.status = 'active'
SET s.account_id = c.id
WHERE s.currency = 'IDR' AND s.account_id IS NULL;

INSERT INTO schema_migrations (filename, status, statements_run, message, applied_at)
SELECT 'migration_setting_akun_acropoly.sql', 'applied', 1, 'Setting Akun diisi dari COA Acropoly (16 key; wht42 & pendapatan_foc kosong)', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM schema_migrations WHERE filename = 'migration_setting_akun_acropoly.sql');

COMMIT;

-- Verifikasi:
--   SELECT s.setting_key, c.account_code, c.account_name FROM setting_accounting s
--     LEFT JOIN chart_of_accounts c ON c.id = s.account_id ORDER BY s.id;
