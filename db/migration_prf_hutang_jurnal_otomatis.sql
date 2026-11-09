-- ============================================================
-- Payment Request <-> Hutang (AP Invoice) <-> Jurnal otomatis
-- ------------------------------------------------------------
-- Permintaan user 2026-11-06:
--   "prf ini kan bisa permintaan pembayaran terhadap po (hutang) ini harusnya
--    bisa dibuat ketika buat req otomatis buat jurnal dan daftar hutang inv nya
--    ... juga pas pembayaran membuat jurnal juga? bisa di setting akun ...
--    kalo buat daftar hutang juga bisa atas po itu, jadi misal sudah di buat AP
--    maka waktu buat prf tinggal pull aja APInvoice nya jadi nanti gausa buat
--    jurnal nya lagi"
--
-- Keputusan user:
--   * AP Invoice + jurnal hutang dibuat saat Payment Request di-APPROVE.
--   * Tagihan PO parsial mengikuti barang yang sudah datang (penerimaan);
--     PO Jasa boleh ditagih tanpa penerimaan.
--   * Akun debit otomatis, dipetakan Accounting (akun persediaan banyak):
--     per barang -> per kategori -> default per jenis barang.
--     Akun belum dipetakan -> approval DITOLAK.
--   * Payment Request manual (tanpa PO / AP) tetap dijurnal manual lewat
--     Jurnal Umum seperti sebelumnya.
--
-- Isi migrasi:
--   1. Baris setting_accounting diisi ulang (ikut terhapus saat DB dibersihkan
--      2026-11-05) + akun baru Biaya Admin Bank. Akun dibiarkan kosong: COA
--      Acropoly belum diimport; Accounting mengisinya di menu Setting Akun.
--   2. Tabel item_account_map (pemetaan akun barang).
--   3. Kolom penghubung di prf_header / prf_details / prf_payment_tracking /
--      ap_invoice_header / ap_invoice_details / ap_payment_header.
-- Idempotent: aman dijalankan berulang (MySQL & MariaDB).
-- ============================================================

-- 1. Setting akun -------------------------------------------------------------
INSERT IGNORE INTO `setting_accounting` (`setting_key`, `setting_name`, `currency`, `account_id`) VALUES
('akun_utang_usaha',      'Akun Utang Usaha (AP)',            'IDR', NULL),
('akun_piutang_usaha',    'Akun Piutang Usaha (AR)',          'IDR', NULL),
('akun_ppn_masukan',      'Akun PPN Masukan',                 'IDR', NULL),
('akun_ppn_keluaran',     'Akun PPN Keluaran',                'IDR', NULL),
('akun_utang_pph_23',     'Akun Utang PPh 23',                'IDR', NULL),
('akun_grn_clearing',     'Akun Clearing GRN',                'IDR', NULL),
('akun_pendapatan_foc',   'Akun Pendapatan FOC',              'IDR', NULL),
('akun_penjualan_fg',     'Akun Penjualan (Barang Jadi)',     'IDR', NULL),
('akun_penjualan_scrap',  'Akun Penjualan (Scrap)',           'IDR', NULL),
('akun_hpp_fg',           'Akun HPP (Barang Jadi)',           'IDR', NULL),
('akun_persediaan_fg',    'Akun Persediaan (Barang Jadi)',    'IDR', NULL),
('akun_beban_umum',       'Akun Beban Umum',                  'IDR', NULL),
('akun_selisih_kurs',     'Akun Selisih Kurs',                'IDR', NULL),
('akun_exchange_loss',    'Akun Rugi Selisih Kurs',           'IDR', NULL),
('akun_exchange_gain',    'Akun Untung Selisih Kurs',         'IDR', NULL),
('akun_wht23',            'Akun WHT PPh 23 (Hutang)',         'IDR', NULL),
('akun_wht42',            'Akun WHT PPh 4(2) (Hutang)',       'IDR', NULL),
('akun_biaya_admin_bank', 'Akun Biaya Admin / Transfer Bank', 'IDR', NULL);

-- 2. Pemetaan akun barang -------------------------------------------------------
-- scope 'item'     : scope_key = id barang di master (pengecualian per barang)
-- scope 'category' : scope_key = kode kategori master (common_code *_CATEGORY)
-- scope 'default'  : scope_key = '' (cadangan per jenis barang)
CREATE TABLE IF NOT EXISTS `item_account_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_type` varchar(20) NOT NULL COMMENT 'material/semifinished/inventory/machine/jasa/asset',
  `scope` enum('default','category','item') NOT NULL,
  `scope_key` varchar(100) NOT NULL DEFAULT '',
  `account_id` int(11) NOT NULL COMMENT 'chart_of_accounts.id (sisi DEBIT jurnal hutang)',
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_item_account_map` (`item_type`, `scope`, `scope_key`),
  KEY `idx_item_account_map_account` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Akun debit jurnal hutang per barang/kategori/jenis';

-- 3. Kolom penghubung -----------------------------------------------------------
SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_header' AND COLUMN_NAME = 'source_type');
SET @s := IF(@c = 0, "ALTER TABLE `prf_header` ADD COLUMN `source_type` varchar(10) NOT NULL DEFAULT 'MANUAL' COMMENT 'MANUAL = jurnal manual via Jurnal Umum; HUTANG = dari PO/AP Invoice, jurnal otomatis' AFTER `subtotal`", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_header' AND COLUMN_NAME = 'ppn_amount');
SET @s := IF(@c = 0, "ALTER TABLE `prf_header` ADD COLUMN `ppn_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Total baris PPN (sudah termasuk di subtotal)' AFTER `source_type`", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_header' AND COLUMN_NAME = 'ap_invoice_header_id');
SET @s := IF(@c = 0, "ALTER TABLE `prf_header` ADD COLUMN `ap_invoice_header_id` int(11) DEFAULT NULL COMMENT 'AP Invoice yang dibuat otomatis saat approve (baris PO)' AFTER `journal_header_id`", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_details' AND COLUMN_NAME = 'line_type');
SET @s := IF(@c = 0, "ALTER TABLE `prf_details` ADD COLUMN `line_type` varchar(10) NOT NULL DEFAULT 'item' COMMENT 'item / ppn (baris PPN otomatis per PO)' AFTER `po_detail_id`", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_details' AND COLUMN_NAME = 'ap_invoice_header_id');
SET @s := IF(@c = 0, "ALTER TABLE `prf_details` ADD COLUMN `ap_invoice_header_id` int(11) DEFAULT NULL COMMENT 'AP Invoice yang dibayar baris ini (tarik AP Invoice)' AFTER `line_type`", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_details' AND INDEX_NAME = 'idx_prf_details_po_detail');
SET @s := IF(@c = 0, "ALTER TABLE `prf_details` ADD KEY `idx_prf_details_po_detail` (`po_detail_id`)", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_details' AND INDEX_NAME = 'idx_prf_details_ap');
SET @s := IF(@c = 0, "ALTER TABLE `prf_details` ADD KEY `idx_prf_details_ap` (`ap_invoice_header_id`)", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_payment_tracking' AND COLUMN_NAME = 'cash_account_id');
SET @s := IF(@c = 0, "ALTER TABLE `prf_payment_tracking` ADD COLUMN `cash_account_id` int(11) DEFAULT NULL COMMENT 'Akun Kas/Bank yang dikredit (PRF hutang)'", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_payment_tracking' AND COLUMN_NAME = 'pph_amount');
SET @s := IF(@c = 0, "ALTER TABLE `prf_payment_tracking` ADD COLUMN `pph_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'PPh 23 dipotong saat bayar (PRF hutang)'", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_payment_tracking' AND COLUMN_NAME = 'journal_header_id');
SET @s := IF(@c = 0, "ALTER TABLE `prf_payment_tracking` ADD COLUMN `journal_header_id` int(11) DEFAULT NULL COMMENT 'Jurnal pembayaran otomatis'", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prf_payment_tracking' AND COLUMN_NAME = 'ap_payment_header_id');
SET @s := IF(@c = 0, "ALTER TABLE `prf_payment_tracking` ADD COLUMN `ap_payment_header_id` int(11) DEFAULT NULL COMMENT 'Pembayaran Hutang (ap_payment_header) yang dibuat otomatis'", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

ALTER TABLE `ap_invoice_header` MODIFY `invoice_type` enum('GRN_BASED','NON_GRN','PO_BASED') NOT NULL;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ap_invoice_header' AND COLUMN_NAME = 'prf_header_id');
SET @s := IF(@c = 0, "ALTER TABLE `ap_invoice_header` ADD COLUMN `prf_header_id` int(11) DEFAULT NULL COMMENT 'Diisi kalau AP dibuat otomatis dari approval Payment Request', ADD KEY `idx_ap_invoice_prf` (`prf_header_id`)", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ap_invoice_header' AND COLUMN_NAME = 'po_header_id');
SET @s := IF(@c = 0, "ALTER TABLE `ap_invoice_header` ADD COLUMN `po_header_id` int(11) DEFAULT NULL COMMENT 'PO asal (AP berbasis PO, bila satu PO)'", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ap_invoice_header' AND COLUMN_NAME = 'reversal_journal_id');
SET @s := IF(@c = 0, "ALTER TABLE `ap_invoice_header` ADD COLUMN `reversal_journal_id` int(11) DEFAULT NULL COMMENT 'Jurnal balik saat AP dibatalkan', ADD COLUMN `cancelled_by` int(11) DEFAULT NULL, ADD COLUMN `cancelled_at` datetime DEFAULT NULL, ADD COLUMN `cancel_reason` varchar(255) DEFAULT NULL", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ap_invoice_details' AND COLUMN_NAME = 'po_detail_id');
SET @s := IF(@c = 0, "ALTER TABLE `ap_invoice_details` ADD COLUMN `po_detail_id` int(11) DEFAULT NULL COMMENT 'Baris PO yang ditagih (AP berbasis PO)' AFTER `grn_detail_id`, ADD KEY `idx_ap_details_po_detail` (`po_detail_id`)", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ap_payment_header' AND COLUMN_NAME = 'prf_header_id');
SET @s := IF(@c = 0, "ALTER TABLE `ap_payment_header` ADD COLUMN `prf_header_id` int(11) DEFAULT NULL COMMENT 'Diisi kalau pembayaran dicatat lewat Payment Request', ADD COLUMN `transfer_fee` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Biaya admin bank'", "SELECT 1");
PREPARE x FROM @s; EXECUTE x; DEALLOCATE PREPARE x;
