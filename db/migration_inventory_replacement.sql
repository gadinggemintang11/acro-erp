-- ============================================================
-- Master General Item: penanda "barang ini DIGANTI oleh barang mana"
-- ------------------------------------------------------------
-- User 2026-09-09: ada kasus barang baru dibuat dengan KODE baru tapi NAMA
-- barangnya sama (mis. ganti spesifikasi/supplier). Kode lama lalu
-- di-nonaktifkan, dan saat menonaktifkan itu user memilih item penggantinya.
-- Setelah nonaktif, hover di kolom Status memunculkan info "diganti ke item
-- mana" — supaya orang gudang/purchasing tidak bingung mencari padanannya.
--
-- Kenapa kolom baru, bukan tabel relasi terpisah: relasinya 1 baris lama ->
-- 1 baris pengganti (bukan many-to-many), dan selalu dibaca bareng baris
-- master-nya sendiri di list.
--
-- Nullable & default NULL: SEMUA item lama (termasuk 5 baris yang sudah
-- 'Nonaktif' saat migrasi ini dibuat) tetap valid tanpa pengganti — item
-- yang memang mati/obsolete tidak dipaksa punya pengganti.
--
-- TIDAK dipasang FOREIGN KEY: baris master di sistem ini di-soft delete
-- (status 'Nonaktif'), tidak pernah benar-benar DELETE, jadi FK tidak
-- memberi jaminan tambahan; validasi (harus item lain, harus Active, tidak
-- boleh menunjuk diri sendiri) dilakukan di Master_inventory::update().
--
-- Kode AMAN dijalankan tanpa migrasi ini (dicek db_has_column()) — fiturnya
-- sekadar tidak muncul.
-- ============================================================

ALTER TABLE `inventories`
    ADD COLUMN `replaced_by_id` INT(11) NULL DEFAULT NULL AFTER `status`,
    ADD COLUMN `replaced_at` DATETIME NULL DEFAULT NULL AFTER `replaced_by_id`,
    ADD INDEX `idx_inventories_replaced_by` (`replaced_by_id`);





dummy test