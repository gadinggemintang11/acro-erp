-- ============================================================
-- MIGRATION: Nilai valas per baris jurnal (jurnal OVB / pindah buku antar mata uang).
-- Jurnal Umum ber-mata uang IDR sekarang boleh memakai akun valas (mis. Bank USD).
-- Baris akun valas diisi nominal valas + kurs di form; debit/kredit tetap disimpan
-- dalam IDR (mata uang header, rate 1) sehingga GL/Neraca/Laba Rugi tidak berubah,
-- sedangkan nilai asli valasnya dicatat di 3 kolom di bawah.
-- Contoh OVB Bank IDR -> Bank USD:
--   Dr Bank Mandiri USD  Rp 16.250.000  (fc_currency=USD, fc_amount=1000, fc_rate=16250)
--   Cr Bank Mandiri IDR  Rp 16.250.000
-- Semua kolom nullable: baris jurnal lama / baris IDR biasa tetap NULL.
-- Idempotent: aman di-rerun.
-- ============================================================

ALTER TABLE `journal_details`
  ADD COLUMN IF NOT EXISTS `fc_currency` VARCHAR(10) NULL DEFAULT NULL
    COMMENT 'Mata uang asli baris akun valas di jurnal IDR (mis. USD); NULL = baris biasa'
    AFTER `kredit_local`,
  ADD COLUMN IF NOT EXISTS `fc_amount` DECIMAL(20,2) NULL DEFAULT NULL
    COMMENT 'Nominal asli dalam fc_currency (sisi debit/kredit mengikuti kolom debit/kredit)'
    AFTER `fc_currency`,
  ADD COLUMN IF NOT EXISTS `fc_rate` DECIMAL(18,6) NULL DEFAULT NULL
    COMMENT 'Kurs efektif = nilai IDR / fc_amount'
    AFTER `fc_amount`;

-- Verifikasi
SHOW COLUMNS FROM `journal_details` LIKE 'fc_%';
