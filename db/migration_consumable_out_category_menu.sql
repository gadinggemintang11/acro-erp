-- =====================================================================
-- Migration: Pisah menu sidebar "Stock Item Release" (consumable_out)
-- jadi 2 pintu per kategori: General & Produksi.
--
-- Kenapa 2 menu_key baru (bukan cuma filter tampilan): supaya Role Access
-- bisa mengatur siapa yang boleh pegang pengeluaran General vs Produksi
-- SECARA TERPISAH (requirement awalnya: "usernya beda"). Implementasinya
-- tetap 1 controller/1 tabel (consumable_out_header + category) — cuma
-- gerbang izinnya yang dipecah.
--
-- Migrasi ini MENYALIN hak akses role yang sudah ada di 'consumable_out'
-- ke KEDUA menu baru, supaya tidak ada role yang mendadak kehilangan akses
-- begitu di-deploy. Setelah ini, silakan buka halaman Role Access untuk
-- mempersempit per role sesuai kebutuhan (mis. staff GA cuma General,
-- staff produksi cuma Produksi). Controller juga tetap fallback OR ke
-- permission lama 'consumable_out' selama masa transisi.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Daftarkan 2 menu baru (menu_key UNIQUE -> upsert)
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`) VALUES
    ('Pengeluaran Barang - General',  'WAREHOUSE GENERAL', 'consumable_out_general',  81),
    ('Pengeluaran Barang - Produksi', 'WAREHOUSE GENERAL', 'consumable_out_produksi', 82)
ON DUPLICATE KEY UPDATE
    `menu_name`  = VALUES(`menu_name`),
    `department` = VALUES(`department`),
    `order_no`   = VALUES(`order_no`);

-- 2) Salin role_permissions dari 'consumable_out' (lama) ke kedua menu baru.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_confirm`, `can_delete`)
SELECT rp.role_id, m_new.id, rp.can_view, rp.can_add, rp.can_edit, rp.can_confirm, rp.can_delete
FROM `role_permissions` rp
JOIN `menus` m_src ON m_src.id = rp.menu_id AND m_src.menu_key = 'consumable_out'
JOIN `menus` m_new ON m_new.menu_key IN ('consumable_out_general', 'consumable_out_produksi')
WHERE NOT EXISTS (
    SELECT 1 FROM `role_permissions` x
    WHERE x.role_id = rp.role_id AND x.menu_id = m_new.id
);

-- 3) Pastikan Admin (role_id = 1) selalu punya akses penuh ke keduanya.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_confirm`, `can_delete`)
SELECT 1, m.id, 1, 1, 1, 1, 1
FROM `menus` m
WHERE m.menu_key IN ('consumable_out_general', 'consumable_out_produksi')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` x
      WHERE x.role_id = 1 AND x.menu_id = m.id
  );
