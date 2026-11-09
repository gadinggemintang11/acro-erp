-- ============================================================
-- Admin: buka SEMUA akses di Role Access
-- ------------------------------------------------------------
-- User 2026-09-29 (membaca "Role Access VER.01.xlsx" dari klien):
-- "admin itu semua akses di buka".
--
-- Role Admin (role_id = 1) sebetulnya sudah dipakai sebagai bypass di
-- banyak tempat lewat is_admin(), TAPI matriks role_permissions-nya
-- sendiri tidak pernah ikut dilengkapi waktu menu baru ditambahkan.
-- Akibatnya menu yang lebih baru tidak muncul/tidak bisa dipakai Admin
-- kalau jalurnya memakai check_access(), bukan is_admin().
--
-- Kondisi sebelum migrasi ini di DB lokal:
--   - 4 menu TANPA baris izin sama sekali:
--       purchase_request_asset, purchase_request_approval_asset,
--       document_sharing, user_approval
--   - developer_mode cuma punya can_view; padahal kodenya juga mengecek
--     check_access('developer_mode', 'edit').
--
-- Migrasi ini menambal keduanya sekaligus, dan tetap benar untuk menu
-- apa pun yang ditambahkan setelah ini selama dijalankan ulang.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- ============================================================

-- 1) Lengkapi baris yang HILANG (menu yang belum pernah punya izin Admin).
INSERT INTO `role_permissions`
    (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_confirm`,
     `can_head_dept`, `can_endorsement`, `can_approval`, `can_delete`)
SELECT 1, m.`id`, 1, 1, 1, 1, 1, 1, 1, 1
FROM `menus` m
LEFT JOIN `role_permissions` rp
       ON rp.`menu_id` = m.`id` AND rp.`role_id` = 1
WHERE rp.`id` IS NULL;

-- 2) Nyalakan semua kolom pada baris yang SUDAH ada tapi belum penuh.
UPDATE `role_permissions`
   SET `can_view` = 1, `can_add` = 1, `can_edit` = 1, `can_confirm` = 1,
       `can_head_dept` = 1, `can_endorsement` = 1, `can_approval` = 1, `can_delete` = 1
 WHERE `role_id` = 1;

-- 3) Laporan hasil.
SELECT
    (SELECT COUNT(*) FROM `menus`)                                   AS total_menu,
    (SELECT COUNT(*) FROM `role_permissions` WHERE `role_id` = 1)    AS baris_izin_admin,
    (SELECT COUNT(*) FROM `menus` m
       LEFT JOIN `role_permissions` rp ON rp.`menu_id` = m.`id` AND rp.`role_id` = 1
      WHERE rp.`id` IS NULL)                                         AS menu_masih_bolong;
