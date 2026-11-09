-- ============================================================
-- Hapus approval user baru + tipe user Executive (user 2026-11-05:
-- "untuk approval user baru itu hapus aja, lalu tipe user executive itu
-- hilangkan aja").
-- ------------------------------------------------------------
-- Kode: User::add() sekarang langsung membuat akun aktif (approval_status
-- 'approved'); halaman/menu "Persetujuan User" & User_approval_model dihapus.
-- Migrasi ini membereskan DATA lama:
--   1. User yang masih menunggu approval -> langsung disetujui (kalau tidak,
--      akunnya tidak akan pernah bisa login karena halaman approval sudah
--      tidak ada).
--   2. Antrian user_pending_changes yang masih pending ditutup:
--      'create' -> approved (akunnya sudah disetujui di langkah 1),
--      sisanya (pengajuan naik jadi Executive) -> rejected.
--   3. User bertipe Executive -> 'user', lalu nilai 'executive' dibuang
--      dari ENUM kolom users.user_type.
--   4. Menu 'user_approval' dihapus (role_permissions ikut terhapus lewat
--      FK ON DELETE CASCADE).
-- Tabel user_pending_changes TIDAK dihapus (riwayat).
-- Idempotent: aman dijalankan ulang.
-- ============================================================

UPDATE `users` SET `approval_status` = 'approved' WHERE `approval_status` = 'pending';

UPDATE `user_pending_changes` SET `status` = 'approved' WHERE `status` = 'pending' AND `change_type` = 'create';
UPDATE `user_pending_changes` SET `status` = 'rejected' WHERE `status` = 'pending';

UPDATE `users` SET `user_type` = 'user' WHERE `user_type` = 'executive';
ALTER TABLE `users` MODIFY `user_type` ENUM('super_admin','accounting','user') NOT NULL DEFAULT 'user';

DELETE FROM `menus` WHERE `menu_key` = 'user_approval';

-- 5. Entri urutan sidebar (Developer Mode > Atur Urutan Sidebar) utk link
--    "Persetujuan User" yang sudah tidak ada. item_key = md5('user/approval').
DELETE FROM `sidebar_item_order` WHERE `item_key` = 'item_1005d2190226';
