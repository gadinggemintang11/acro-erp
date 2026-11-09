-- Developer Mode — Atur Urutan Sidebar (user 2026-09-06: "developer mode ini
-- bisa buat dikembangkan jadi pengatur urutan sidebar, jadi sidebar bisa di
-- tentukan urutannya jika user merasa tidak sesuai", lalu follow-up: "bukan
-- cuma di core operation aja tapi semuanya baik menu utama dan sub menu").
--
-- 2 tabel:
-- `sidebar_order`      -- urutan 15 MENU UTAMA (Dashboard fixed sbg "Home",
--                          semua yg lain: Order s/d System Setup, bebas
--                          diatur). Heading "Accounting & Finance"/
--                          "Configuration" yg dulu misahin sebagian section
--                          ini SUDAH DIHAPUS dari kode (bukan cuma
--                          disembunyikan) krn begitu semua section bisa
--                          saling tukar posisi bebas, heading fixed di
--                          posisi lama jadi tidak akurat lagi.
-- `sidebar_item_order` -- urutan SUBMENU di dalam tiap dropdown (scoped per
--                          dropdown_key spy item dropdown A tidak bentrok
--                          key dgn dropdown B). Grup berlabel (mis.
--                          "REPORT:"/"SETTING:") diperlakukan sbg 1 blok
--                          gabungan (h6 + semua item di bawahnya) -- TIDAK
--                          bisa reorder item lintas grup, cuma antar-blok.
--                          TIDAK di-seed manual (beda dari sidebar_order) --
--                          murni auto-discovery saat sidebar dirender,
--                          krn ~150+ item di 14 dropdown terlalu banyak utk
--                          di-seed manual satu-satu dgn aman.
-- Lihat application/helpers/sidebar_order_helper.php & Developer_mode_model.php.
--
-- Pola SAMA persis dgn `ui_columns` (auto-discovery + INSERT IGNORE kalau
-- ada section/item baru yg belum terdaftar) -- lihat migration_ui_columns.sql.

CREATE TABLE IF NOT EXISTS `sidebar_order` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_key` VARCHAR(50) NOT NULL,
  `section_label` VARCHAR(150) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `updated_by` INT NULL,
  `updated_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sidebar_order_section_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed urutan default = urutan asli di kode saat ini (supaya langsung siap
-- pakai tanpa menunggu auto-discovery di render pertama; gap 10 per baris
-- sengaja disisakan kalau nanti perlu sisip manual lewat DB).
INSERT IGNORE INTO `sidebar_order` (`section_key`, `section_label`, `sort_order`, `created_at`) VALUES
('order',        'IPO Number',                     0, NOW()),
('permintaan',   'Permintaan Barang',              10, NOW()),
('produksi',     'Production',                     20, NOW()),
('warehouse',    'Warehouse',                      30, NOW()),
('purchase',     'Purchasing',                     40, NOW()),
('qa',           'Quality Control & Assurances',   50, NOW()),
('fg',           'Finished Goods',                 60, NOW()),
('exim',         'Shipping & Transportation',      70, NOW()),
('scrap',        'Scrap',                          80, NOW()),
('subkon',           'Subkon',                        90, NOW()),
('it_inventory',     'IT Inventory [BC]',            100, NOW()),
('accounting',       'Finance & Accounting',         110, NOW()),
('master',           'Master Database',              120, NOW()),
('document_sharing', 'Document Sharing',             130, NOW()),
('system',           'System Setup',                 140, NOW());

-- Submenu (item di dalam tiap dropdown) -- TIDAK di-seed manual, murni
-- auto-discovery (lihat penjelasan di atas).
CREATE TABLE IF NOT EXISTS `sidebar_item_order` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dropdown_key` VARCHAR(50) NOT NULL,
  `item_key` VARCHAR(50) NOT NULL,
  `item_label` VARCHAR(150) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `updated_by` INT NULL,
  `updated_at` DATETIME NULL,
  `created_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sidebar_item_order_key` (`dropdown_key`, `item_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
