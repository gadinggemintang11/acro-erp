-- ============================================================
-- Tipe User & Approval Akun (ERP Revision Notes bagian "TYPE OF USER" /
-- "ACCESSIBILITY" — dokumen asli 2026-09-02, BARU dikerjakan 2026-09-04
-- setelah user tanya ulang "apakah masalah user itu sudah?"). Lihat
-- memory user_type_approval_feature.md utk desain lengkap + keputusan yg
-- diambil via AskUserQuestion (Admin=Super Admin, Accounting=Finance &
-- Accounting/role "Accounting Officer", Executive User TIDAK ganti role
-- asli — cuma label tambahan, approval create/edit user DIBANGUN sesuai
-- dokumen).
-- ============================================================

ALTER TABLE `users`
  ADD COLUMN `user_type` ENUM('super_admin','executive','accounting','user') NOT NULL DEFAULT 'user' AFTER `role_id`,
  ADD COLUMN `approval_status` ENUM('approved','pending','rejected') NOT NULL DEFAULT 'approved' AFTER `user_type`;

-- Tandai 4 akun riil sesuai dokumen "TYPE OF USER". Role ASLI mereka
-- (Admin/Direktur/Assistant Director Office/Accounting Officer) TIDAK
-- diubah sama sekali — cuma label user_type ditambahkan.
UPDATE `users` SET `user_type` = 'super_admin' WHERE `username` = 'martinus';
UPDATE `users` SET `user_type` = 'executive'   WHERE `username` IN ('henry', 'johanes'); -- Ho Tak Wah, Johanes O
UPDATE `users` SET `user_type` = 'accounting'  WHERE `username` = 'adin';

-- Semua akun LAIN (termasuk yg sudah ada sebelumnya) tetap user_type='user'
-- (default kolom) & approval_status='approved' (default kolom) — TIDAK ada
-- akun existing yang tiba-tiba ke-lock gara-gara migration ini.

CREATE TABLE IF NOT EXISTS `user_pending_changes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `target_user_id` INT NOT NULL,
  `change_type` ENUM('create','edit') NOT NULL,
  `proposed_data` TEXT DEFAULT NULL COMMENT 'JSON field=>value baru, dipakai utk change_type=edit; NULL utk create (data sudah langsung di row users, tinggal approve/reject statusnya)',
  `required_approver` ENUM('director','accounting') NOT NULL COMMENT 'director = target user_type executive; accounting = selain itu (super_admin/accounting/user)',
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_by` INT DEFAULT NULL,
  `requested_at` DATETIME DEFAULT NULL,
  `approved_by` INT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `reject_reason` VARCHAR(255) DEFAULT NULL,
  KEY `idx_upc_target` (`target_user_id`),
  KEY `idx_upc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu baru "Persetujuan User" (queue approval create/edit akun).
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`)
VALUES ('User Approval', 'SYSTEM', 'user_approval', 536);

-- Grant can_view LANGSUNG ke 3 role yang REALISTIS akan pakai fitur ini:
-- Admin (role_id=1, oversight/troubleshoot semua), Direktur (role_id=27,
-- approver 'director'), Accounting Officer (role_id=22, approver
-- 'accounting'). Role lain TIDAK dapat akses menu ini sama sekali (tidak
-- relevan buat mereka) — beda dari pola "grant ke semua role" yg dipakai
-- Document Sharing, di sini approver-nya memang spesifik cuma 2-3 orang.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`)
SELECT r.id, (SELECT id FROM `menus` WHERE menu_key = 'user_approval'), 1, 0, 1, 0
FROM `roles` r WHERE r.id IN (1, 27, 22);
