-- =====================================================================
-- Migration: Purchase Order Jasa + Master Jasa
--
-- Modul PO baru untuk jasa (general service). Line item PO Jasa diambil
-- dari master jasa baru (tabel `jasa`), memakai model & tabel PO yang
-- sama dengan PO Barang (po_header/po_details, Po_model), dibedakan lewat
-- po_header.type = 'jasa'.
--
-- Tombol "Selesaikan Jasa" di PO Jasa menulis ke grn_header/grn_details
-- (warehouse_type = 'jasa') dengan bentuk yang sama seperti GRN barang,
-- supaya jalur AP Accounting yang sudah ada (ApInvoice_model::get_pending_grn)
-- otomatis mendeteksinya tanpa perlu query baru di AP.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Tambah value 'jasa' ke enum yang relevan (additive, tidak mengubah data lama)
ALTER TABLE `po_header` MODIFY `type` ENUM('produksi','non_produksi','jasa') NOT NULL DEFAULT 'produksi';
ALTER TABLE `grn_header` MODIFY `warehouse_type` ENUM('material','machine','inventory','semifinished','jasa') NOT NULL;

-- 2) Tabel Master Jasa
CREATE TABLE IF NOT EXISTS `jasa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_jasa` varchar(20) NOT NULL,
  `nama_jasa` varchar(150) NOT NULL,
  `kategori` varchar(50) DEFAULT 'Lainnya' COMMENT 'from common_code.JASA_CATEGORY',
  `satuan` varchar(20) DEFAULT 'Lot' COMMENT 'from common_code.SATUAN',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_jasa` (`kode_jasa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3) Seed common_code group JASA_CATEGORY (dropdown kategori di Master Jasa)
INSERT INTO `common_code` (`group_code`, `group_description`, `code`, `description`, `status`)
SELECT v.group_code, v.group_description, v.code, v.description, 'active'
FROM (
    SELECT 'JASA_CATEGORY' AS group_code, NULL AS group_description, 'MAINTENANCE' AS code, 'Maintenance' AS description
    UNION ALL SELECT 'JASA_CATEGORY', NULL, 'KONSULTASI', 'Konsultasi'
    UNION ALL SELECT 'JASA_CATEGORY', NULL, 'SEWA', 'Sewa/Rental'
    UNION ALL SELECT 'JASA_CATEGORY', NULL, 'KONSTRUKSI', 'Konstruksi'
    UNION ALL SELECT 'JASA_CATEGORY', NULL, 'LAINNYA', 'Lainnya'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM `common_code` cc WHERE cc.group_code = v.group_code AND cc.code = v.code
);

-- 4) Tambah satuan yang relevan untuk jasa (kalau belum ada)
INSERT INTO `common_code` (`group_code`, `group_description`, `code`, `description`, `status`)
SELECT v.group_code, v.group_description, v.code, v.description, 'active'
FROM (
    SELECT 'SATUAN' AS group_code, NULL AS group_description, 'JAM' AS code, 'Jam' AS description
    UNION ALL SELECT 'SATUAN', NULL, 'HARI', 'Hari'
    UNION ALL SELECT 'SATUAN', NULL, 'PAKET', 'Paket'
    UNION ALL SELECT 'SATUAN', NULL, 'LOT', 'Lot'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM `common_code` cc WHERE cc.group_code = v.group_code AND cc.code = v.code
);

-- 5) Daftarkan menu (menu_key UNIQUE -> upsert)
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Master Jasa',            'MASTER DATA',      'master_jasa',                   519),
    ('Create New PO Jasa',     'PURCHASING JASA',  'purchase_order_jasa',           143),
    ('PO Jasa Approval',       'PURCHASING JASA',  'purchase_order_approval_jasa',  144)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- 6) Hak akses: copy seluruh kolom permission dari menu analog yang sudah ada
--    - master_jasa                  <- mengikuti master_inventory
--    - purchase_order_jasa          <- mengikuti purchase_order_general
--    - purchase_order_approval_jasa <- mengikuti purchase_order_approval_general

INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'master_inventory'
JOIN `menus` m_new ON m_new.menu_key = 'master_jasa'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'purchase_order_general'
JOIN `menus` m_new ON m_new.menu_key = 'purchase_order_jasa'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_delete, rp.can_confirm, rp.can_head_dept, rp.can_endorsement, rp.can_approval
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'purchase_order_approval_general'
JOIN `menus` m_new ON m_new.menu_key = 'purchase_order_approval_jasa'
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

-- 7) Pastikan Admin (role_id = 1) selalu punya akses penuh ke 3 menu baru
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`, `can_confirm`, `can_head_dept`, `can_endorsement`, `can_approval`)
SELECT 1, m.id, 1, 1, 1, 1, 1, 1, 1, 1
FROM `menus` m
WHERE m.menu_key IN ('master_jasa', 'purchase_order_jasa', 'purchase_order_approval_jasa')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x WHERE x.role_id = 1 AND x.menu_id = m.id
  );
