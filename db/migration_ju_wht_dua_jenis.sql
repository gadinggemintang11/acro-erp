-- ============================================================
-- MIGRATION: WHT 23 & WHT 4(2) untuk Jurnal Umum.
-- - journal_header: simpan dua jenis WHT (enabled/dpp/rate/amount) terpisah.
--   * WHT 23  default rate 2%  -> Cr Hutang PPh 23  (akun: setting akun_wht23)
--   * WHT 4(2) default rate 10% -> Cr Hutang PPh 4(2) (akun: setting akun_wht42)
-- - journal_details: penanda baris WHT otomatis (is_wht_line) supaya form edit
--   bisa membangun ulang baris WHT dari header (pola sama is_ppn_line).
-- - setting_accounting: seed key akun WHT (account_id diisi user di Setting Akun).
-- Idempotent: aman di-rerun (butuh MariaDB / MySQL yg support IF NOT EXISTS).
-- ============================================================

-- Dua jenis WHT (manual, dipilih per jurnal; DPP di-input sendiri)
ALTER TABLE `journal_header`
  ADD COLUMN IF NOT EXISTS `wht23_enabled` TINYINT(1)     NOT NULL DEFAULT 0     COMMENT 'WHT PPh 23 aktif',
  ADD COLUMN IF NOT EXISTS `wht23_dpp`     DECIMAL(20,2)  NOT NULL DEFAULT 0.00  COMMENT 'DPP basis WHT 23 (input manual)',
  ADD COLUMN IF NOT EXISTS `wht23_rate`    DECIMAL(5,2)   NOT NULL DEFAULT 2.00  COMMENT 'Rate WHT 23 (%) default 2',
  ADD COLUMN IF NOT EXISTS `wht23_amount`  DECIMAL(20,2)  NOT NULL DEFAULT 0.00  COMMENT 'Nominal WHT 23 = dpp*rate',
  ADD COLUMN IF NOT EXISTS `wht42_enabled` TINYINT(1)     NOT NULL DEFAULT 0     COMMENT 'WHT PPh 4(2) aktif',
  ADD COLUMN IF NOT EXISTS `wht42_dpp`     DECIMAL(20,2)  NOT NULL DEFAULT 0.00  COMMENT 'DPP basis WHT 4(2) (input manual)',
  ADD COLUMN IF NOT EXISTS `wht42_rate`    DECIMAL(5,2)   NOT NULL DEFAULT 10.00 COMMENT 'Rate WHT 4(2) (%) default 10',
  ADD COLUMN IF NOT EXISTS `wht42_amount`  DECIMAL(20,2)  NOT NULL DEFAULT 0.00  COMMENT 'Nominal WHT 4(2) = dpp*rate';

-- Penanda baris WHT otomatis di detail
ALTER TABLE `journal_details`
  ADD COLUMN IF NOT EXISTS `is_wht_line` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Baris WHT otomatis (1=ya)';

-- Seed key akun WHT di setting_accounting (account_id NULL -> diisi user di Setting Akun)
INSERT INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`)
SELECT 'akun_wht23', 'Akun WHT PPh 23 (Hutang)', 'IDR', NULL
WHERE NOT EXISTS (
  SELECT 1 FROM `setting_accounting` WHERE `setting_key` = 'akun_wht23' AND `currency` = 'IDR'
);

INSERT INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`)
SELECT 'akun_wht42', 'Akun WHT PPh 4(2) (Hutang)', 'IDR', NULL
WHERE NOT EXISTS (
  SELECT 1 FROM `setting_accounting` WHERE `setting_key` = 'akun_wht42' AND `currency` = 'IDR'
);

-- Verifikasi
SHOW COLUMNS FROM `journal_header` LIKE 'wht23%';
SHOW COLUMNS FROM `journal_header` LIKE 'wht42%';
SHOW COLUMNS FROM `journal_details` LIKE 'is_wht_line';
SELECT setting_key, setting_name, currency, account_id FROM `setting_accounting` WHERE setting_key IN ('akun_wht23','akun_wht42');
