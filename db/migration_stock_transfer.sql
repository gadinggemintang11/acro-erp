-- =====================================================================
-- Migration: Modul Stock Transfer (General <-> Produksi)
--
-- Pelengkap wajib dari split stock General/Produksi: begitu 2 bucket
-- terpisah, kadang kebutuhan nyata tidak pas sama saldo yang ada di
-- masing-masing bucket (mis. cutover awal taruh semua di General, padahal
-- fisiknya sebagian sudah ada di gudang Produksi). Modul ini modul resmi
-- utk mindahin qty dari satu bucket ke bucket lain, dicatat sebagai
-- sepasang baris stock_ledger (OUT dari sumber, IN ke tujuan) via
-- transaction_type 'STOCK_TRANSFER_OUT' / 'STOCK_TRANSFER_IN'.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

CREATE TABLE IF NOT EXISTS `stock_transfer_header` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `transfer_number` VARCHAR(30) NOT NULL,
  `transfer_date` DATE NOT NULL,
  `from_category` ENUM('General','Produksi') NOT NULL,
  `to_category` ENUM('General','Produksi') NOT NULL,
  `notes` TEXT NULL DEFAULT NULL,
  `created_by` INT(11) NULL DEFAULT NULL,
  `created_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_number` (`transfer_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_transfer_details` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `header_id` INT(11) NOT NULL,
  `item_id` INT(11) NOT NULL,
  `item_type` VARCHAR(20) NOT NULL DEFAULT 'inventory',
  `item_code` VARCHAR(50) NULL DEFAULT NULL,
  `item_name` VARCHAR(200) NULL DEFAULT NULL,
  `item_unit` VARCHAR(30) NULL DEFAULT NULL,
  `qty` DECIMAL(15,4) NOT NULL DEFAULT 0.0000,
  PRIMARY KEY (`id`),
  KEY `header_id` (`header_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Daftarkan menu (department sama dengan modul stock lain).
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Stock Transfer (General/Produksi)', 'WAREHOUSE GENERAL', 'stock_transfer', 83)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- Admin (role_id = 1) selalu punya akses penuh.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`)
SELECT 1, m.id, 1, 1, 1, 1
FROM `menus` m
WHERE m.menu_key = 'stock_transfer'
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = 1 AND x.menu_id = m.id
  );
