-- ============================================================
-- MIGRATION: Aset Tetap, Penyusutan & Biaya Dibayar Dimuka (2026-11-26)
--
-- 1) asset_register: bisa input MANUAL (tanah, bangunan, aset lama/saldo
--    awal, beli tunai lewat Jurnal Umum/Kas Bank) tanpa master asset & PO;
--    akun per unit (default ikut master), akumulasi awal, tidak disusutkan,
--    data pelepasan (jual/hapus).
-- 2) prepaid_expenses + prepaid_amortization_details: Biaya Dibayar Dimuka
--    (mis. Sewa Dibayar Dimuka) diamortisasi bulanan, ikut batch penyusutan
--    (asset_depreciation_header) yang sama.
-- 3) Kategori aset Tanah & Bangunan, Setting Akun default (laba/rugi
--    pelepasan aset, sewa dibayar dimuka, biaya sewa), menu + hak akses.
--
-- Metode: garis lurus; penyusutan/amortisasi mulai BULAN BERIKUTNYA setelah
-- perolehan / tanggal bayar (keputusan user).
-- Pola information_schema + PREPARE (MySQL & MariaDB). Idempotent.
-- ============================================================

-- 1) asset_register ------------------------------------------------------
ALTER TABLE asset_register MODIFY COLUMN asset_id INT(11) NULL COMMENT 'FK assets.id -- NULL utk aset manual tanpa master asset';

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'sumber');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN sumber VARCHAR(10) NOT NULL DEFAULT ''PO'' COMMENT ''PO = dari Terima Aset PO Asset; MANUAL = input manual (tanah, bangunan, aset lama, beli tunai)''', 'SELECT ''asset_register.sumber sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'nama_aset');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN nama_aset VARCHAR(150) NULL COMMENT ''Nama aset utk input manual tanpa master asset''', 'SELECT ''asset_register.nama_aset sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'kategori');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN kategori VARCHAR(50) NULL COMMENT ''common_code ASSET_CATEGORY; NULL = ikut master asset''', 'SELECT ''asset_register.kategori sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'coa_asset_id');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN coa_asset_id INT(11) NULL COMMENT ''Akun aset; NULL = ikut master asset''', 'SELECT ''asset_register.coa_asset_id sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'coa_akum_id');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN coa_akum_id INT(11) NULL COMMENT ''Akun akumulasi penyusutan; NULL = ikut master asset''', 'SELECT ''asset_register.coa_akum_id sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'coa_beban_id');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN coa_beban_id INT(11) NULL COMMENT ''Akun beban penyusutan; NULL = ikut master asset''', 'SELECT ''asset_register.coa_beban_id sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'disusutkan');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN disusutkan TINYINT(1) NOT NULL DEFAULT 1 COMMENT ''0 = tidak disusutkan (mis. tanah)''', 'SELECT ''asset_register.disusutkan sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'akumulasi_awal');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN akumulasi_awal DECIMAL(20,2) NOT NULL DEFAULT 0.00 COMMENT ''Akumulasi penyusutan sebelum masuk sistem (aset lama / saldo awal)''', 'SELECT ''asset_register.akumulasi_awal sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'ref_journal_no');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN ref_journal_no VARCHAR(50) NULL COMMENT ''No. jurnal perolehan yang sudah dibuat manual (referensi)''', 'SELECT ''asset_register.ref_journal_no sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'disposal_type');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN disposal_type VARCHAR(10) NULL COMMENT ''JUAL / HAPUS''', 'SELECT ''asset_register.disposal_type sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'disposal_value');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN disposal_value DECIMAL(20,2) NOT NULL DEFAULT 0.00 COMMENT ''Harga jual (0 utk penghapusan)''', 'SELECT ''asset_register.disposal_value sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asset_register' AND COLUMN_NAME = 'disposal_journal_id');
SET @sql := IF(@c = 0, 'ALTER TABLE asset_register ADD COLUMN disposal_journal_id INT(11) NULL COMMENT ''Jurnal pelepasan aset''', 'SELECT ''asset_register.disposal_journal_id sudah ada''');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 2) Biaya Dibayar Dimuka ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `prepaid_expenses` (
  `id`                   int(11)       NOT NULL AUTO_INCREMENT,
  `doc_no`               varchar(50)   NOT NULL COMMENT 'mis. PPD-2026-0001',
  `nama`                 varchar(150)  NOT NULL COMMENT 'mis. Sewa Gudang Jan-Des 2027',
  `coa_prepaid_id`       int(11)       NOT NULL COMMENT 'Akun biaya dibayar dimuka (Aset), mis. 1.104.002 Sewa Dibayar Dimuka',
  `coa_expense_id`       int(11)       NOT NULL COMMENT 'Akun beban, mis. 6.200.021 Biaya Sewa',
  `department`           varchar(100)  DEFAULT NULL,
  `tanggal_bayar`        date          NOT NULL,
  `nilai`                decimal(20,2) NOT NULL DEFAULT 0.00,
  `jumlah_bulan`         int(11)       NOT NULL DEFAULT 12,
  `mulai_amortisasi`     date          NOT NULL COMMENT 'Bulan pertama diamortisasi (tgl 1). Default = bulan berikutnya setelah tanggal bayar',
  `amortisasi_awal`      decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Sudah diamortisasi sebelum masuk sistem',
  `akumulasi_amortisasi` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Termasuk amortisasi_awal; s/d periode terakhir yang diposting',
  `sisa`                 decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'nilai - akumulasi_amortisasi',
  `ref_journal_no`       varchar(50)   DEFAULT NULL COMMENT 'No. jurnal pembayaran (Kas/Bank/Faktur) sebagai referensi',
  `status`               enum('Aktif','Selesai') NOT NULL DEFAULT 'Aktif',
  `keterangan`           text          DEFAULT NULL,
  `created_by`           int(11)       DEFAULT NULL,
  `created_at`           datetime      DEFAULT current_timestamp(),
  `updated_at`           datetime      DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ppd_doc_no` (`doc_no`),
  KEY `idx_ppd_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `prepaid_amortization_details` (
  `id`                     int(11)       NOT NULL AUTO_INCREMENT,
  `depreciation_header_id` int(11)       NOT NULL COMMENT 'Batch bulanan asset_depreciation_header',
  `prepaid_id`             int(11)       NOT NULL,
  `nilai`                  decimal(20,2) NOT NULL DEFAULT 0.00,
  `akumulasi_sebelum`      decimal(20,2) NOT NULL DEFAULT 0.00,
  `amortisasi_bulan_ini`   decimal(20,2) NOT NULL DEFAULT 0.00,
  `akumulasi_sesudah`      decimal(20,2) NOT NULL DEFAULT 0.00,
  `sisa_sesudah`           decimal(20,2) NOT NULL DEFAULT 0.00,
  `coa_expense_id`         int(11)       DEFAULT NULL COMMENT 'Disalin saat batch dibuat',
  `coa_prepaid_id`         int(11)       DEFAULT NULL,
  `department`             varchar(100)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pad_header` (`depreciation_header_id`),
  KEY `idx_pad_prepaid` (`prepaid_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3a) Kategori aset Tanah & Bangunan --------------------------------------
INSERT INTO common_code (group_code, code, description, status)
SELECT 'ASSET_CATEGORY', n.code, n.descr, 'active'
FROM (SELECT 'TANAH' code, 'Tanah' descr UNION ALL SELECT 'BANGUNAN', 'Bangunan') n
WHERE NOT EXISTS (SELECT 1 FROM common_code c WHERE c.group_code = 'ASSET_CATEGORY' AND c.code = n.code);

-- 3b) Setting Akun default (hanya dibuat bila belum ada; akun dicari lewat kode)
INSERT INTO setting_accounting (setting_key, setting_name, currency, account_id)
SELECT n.k, n.nm, 'IDR', (SELECT id FROM chart_of_accounts WHERE account_code = n.code AND is_header = 0 LIMIT 1)
FROM (
    SELECT 'akun_laba_pelepasan_aset' k, 'Akun Laba Pelepasan Aset Tetap' nm, '' code UNION ALL
    SELECT 'akun_rugi_pelepasan_aset',   'Akun Rugi Pelepasan Aset Tetap',    '' UNION ALL
    SELECT 'akun_sewa_dibayar_dimuka',   'Akun Default Biaya Dibayar Dimuka (Sewa)', '1.104.002' UNION ALL
    SELECT 'akun_biaya_sewa',            'Akun Default Beban Amortisasi (Biaya Sewa)', '6.200.021'
) n
WHERE NOT EXISTS (SELECT 1 FROM setting_accounting s WHERE s.setting_key = n.k AND s.currency = 'IDR');

-- 3c) Menu + hak akses ------------------------------------------------------
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Aset Tetap',               'ACCOUNTING', 'asset_register',     360),
    ('Penyusutan & Amortisasi',  'ACCOUNTING', 'asset_depreciation', 361),
    ('Biaya Dibayar Dimuka',     'ACCOUNTING', 'prepaid_expense',    362)
ON DUPLICATE KEY UPDATE `menu_name` = VALUES(`menu_name`), `department` = VALUES(`department`), `order_no` = VALUES(`order_no`);

-- Role yang boleh EDIT Jurnal Umum -> akses penuh; yang hanya LIHAT Jurnal Umum -> lihat.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, 1, rp.can_edit, rp.can_edit, rp.can_edit, rp.can_edit, 0, 0, 0
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'jurnalumum'
JOIN `menus` m_new ON m_new.menu_key IN ('asset_register', 'asset_depreciation', 'prepaid_expense')
WHERE rp.can_view = 1
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id);

-- Admin (role 1) selalu akses penuh
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT 1, m.id, 1, 1, 1, 1, 1, 0, 0, 0
FROM `menus` m
WHERE m.menu_key IN ('asset_register', 'asset_depreciation', 'prepaid_expense')
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` x WHERE x.role_id = 1 AND x.menu_id = m.id);

-- Verifikasi
SHOW COLUMNS FROM asset_register WHERE Field IN ('asset_id', 'sumber', 'disusutkan', 'akumulasi_awal', 'disposal_journal_id');
SELECT menu_key, menu_name FROM menus WHERE menu_key IN ('asset_register', 'asset_depreciation', 'prepaid_expense');
