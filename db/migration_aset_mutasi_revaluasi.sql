-- ============================================================
-- MIGRATION: Aset Tetap - Revaluasi & Pelepasan Sebagian (2026-11-26)
-- Prasyarat: migration_aset_tetap_penyusutan.sql
--
-- 1) asset_mutations: riwayat perubahan nilai aset di luar penyusutan rutin:
--      REVALUASI      : nilai aset dinilai ulang ke nilai wajar (PSAK 16,
--                       metode eliminasi akumulasi). Kenaikan -> Surplus
--                       Revaluasi (ekuitas) setelah memulihkan rugi revaluasi
--                       sebelumnya; penurunan -> kurangi surplus dulu, sisanya
--                       Rugi Revaluasi (laba rugi). Penyusutan berikutnya dari
--                       nilai baru selama sisa umur.
--      LEPAS_SEBAGIAN : sebagian dari satu aset dijual / dihapus (mis. tanah
--                       300 m2 dari 1.000 m2); nilai, akumulasi & residu aset
--                       berkurang proporsional.
--    Snapshot sebelum/sesudah disimpan supaya mutasi bisa dibatalkan dan
--    laporan mutasi aset per periode bisa dihitung.
-- 2) asset_register.surplus_revaluasi & rugi_revaluasi: saldo surplus dan rugi
--    revaluasi (laba rugi) per aset, dipakai menghitung revaluasi berikutnya.
-- 3) Setting Akun: Surplus Revaluasi, Laba/Rugi Revaluasi (kosong, isi di
--    Setting Akun) & Saldo Laba (default 3.100.003 Laba ditahan bila ada).
--
-- Pola information_schema + PREPARE (MySQL & MariaDB). Idempotent.
-- ============================================================

-- 1) asset_mutations --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asset_mutations` (
  `id`                   int(11)       NOT NULL AUTO_INCREMENT,
  `asset_register_id`    int(11)       NOT NULL,
  `jenis`                varchar(20)   NOT NULL COMMENT 'REVALUASI / LEPAS_SEBAGIAN',
  `tanggal`              date          NOT NULL,
  `periode`              int(11)       NOT NULL COMMENT 'tahun*12+bulan dari tanggal',
  `dep_period_at`        int(11)       NOT NULL DEFAULT 0 COMMENT 'Periode (tahun*12+bulan) penyusutan Posted terakhir aset saat mutasi dibuat; mutasi bisa dibatalkan selama belum ada penyusutan baru',
  `porsi`                decimal(12,8) DEFAULT NULL COMMENT 'LEPAS_SEBAGIAN: bagian yang dilepas (0..1)',
  `porsi_keterangan`     varchar(100)  DEFAULT NULL COMMENT 'mis. 300 dari 1000 m2',
  `nilai_sebelum`        decimal(20,2) NOT NULL DEFAULT 0.00,
  `akumulasi_sebelum`    decimal(20,2) NOT NULL DEFAULT 0.00,
  `residu_sebelum`       decimal(20,2) NOT NULL DEFAULT 0.00,
  `umur_sebelum`         int(11)       NOT NULL DEFAULT 0,
  `mulai_sebelum`        date          DEFAULT NULL,
  `status_sebelum`       varchar(20)   DEFAULT NULL,
  `surplus_sebelum`      decimal(20,2) NOT NULL DEFAULT 0.00,
  `rugi_reval_sebelum`   decimal(20,2) NOT NULL DEFAULT 0.00,
  `nilai_sesudah`        decimal(20,2) NOT NULL DEFAULT 0.00,
  `akumulasi_sesudah`    decimal(20,2) NOT NULL DEFAULT 0.00,
  `residu_sesudah`       decimal(20,2) NOT NULL DEFAULT 0.00,
  `umur_sesudah`         int(11)       NOT NULL DEFAULT 0,
  `mulai_sesudah`        date          DEFAULT NULL,
  `delta_nilai`          decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Perubahan harga perolehan (+/-)',
  `delta_akumulasi`      decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Perubahan akumulasi penyusutan (+/-)',
  `nilai_wajar`          decimal(20,2) DEFAULT NULL COMMENT 'REVALUASI: nilai wajar (nilai buku baru)',
  `ke_surplus`           decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'REVALUASI: + menambah / - mengurangi surplus revaluasi',
  `ke_laba_rugi`         decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'REVALUASI: + pemulihan rugi / - rugi revaluasi (laba rugi)',
  `disposal_type`        varchar(10)   DEFAULT NULL COMMENT 'LEPAS_SEBAGIAN: JUAL / HAPUS',
  `harga_jual`           decimal(20,2) NOT NULL DEFAULT 0.00,
  `laba_rugi`            decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'LEPAS_SEBAGIAN: + laba / - rugi pelepasan',
  `surplus_ke_saldo_laba` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Surplus revaluasi bagian yang dilepas yang dipindah ke saldo laba',
  `journal_header_id`    int(11)       DEFAULT NULL,
  `keterangan`           text          DEFAULT NULL,
  `created_by`           int(11)       DEFAULT NULL,
  `created_at`           datetime      DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_am_asset` (`asset_register_id`),
  KEY `idx_am_periode` (`periode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) asset_register: saldo surplus & rugi revaluasi per aset ---------------
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'surplus_revaluasi');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN surplus_revaluasi DECIMAL(20,2) NOT NULL DEFAULT 0.00 COMMENT ''Saldo surplus revaluasi (ekuitas) milik aset ini''', 'SELECT ''asset_register.surplus_revaluasi sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'rugi_revaluasi');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN rugi_revaluasi DECIMAL(20,2) NOT NULL DEFAULT 0.00 COMMENT ''Rugi revaluasi yang sudah dibebankan ke laba rugi (dikurangi pemulihan)''', 'SELECT ''asset_register.rugi_revaluasi sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Setting Akun (hanya dibuat bila belum ada; akun dicari lewat kode) ------
INSERT INTO setting_accounting (setting_key, setting_name, currency, account_id)
SELECT n.k, n.nm, 'IDR', (SELECT id FROM chart_of_accounts WHERE account_code = n.code AND is_header = 0 LIMIT 1)
FROM (
    SELECT 'akun_surplus_revaluasi' k,  'Akun Surplus Revaluasi Aset Tetap (Ekuitas)' nm, '' code UNION ALL
    SELECT 'akun_laba_rugi_revaluasi',  'Akun Laba/Rugi Revaluasi Aset Tetap',  '' UNION ALL
    SELECT 'akun_saldo_laba',           'Akun Saldo Laba (Laba Ditahan)',        '3.100.003'
) n
WHERE NOT EXISTS (SELECT 1 FROM setting_accounting s WHERE s.setting_key = n.k AND s.currency = 'IDR');
