-- =====================================================================
-- Migration: Tambah kategori "Jasa" di Master Supplier
-- (sebelumnya cuma general/produksi)
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

-- 1) Tambah value 'jasa' ke suppliers.kategori (additive, tidak mengubah data lama)
ALTER TABLE `suppliers` MODIFY `kategori` ENUM('general','produksi','jasa') NOT NULL DEFAULT 'general';

-- 2) Daftarkan pilihan dropdown-nya di common_code (dipakai filter + form Add/Edit di Master Supplier)
INSERT INTO `common_code` (`group_code`, `code`, `description`, `status`)
SELECT 'Kategori_supplier', 'jasa', 'Jasa', 'active'
WHERE NOT EXISTS (
    SELECT 1 FROM `common_code` WHERE `group_code` = 'Kategori_supplier' AND `code` = 'jasa'
);
