-- =====================================================================
-- Migration: Notification system (per-event, topbar bell)
--
-- ERP Revision Notes, [[erp_revision_cross_cutting]]: bell icon + dropdown
-- di topbar SUDAH ADA (Notification.php/Notification_model.php), tapi
-- selama ini cuma feed generik dari `user_logs` (log CRUD biasa, difilter
-- per role) — BUKAN notifikasi per-event bertarget yang diminta spec
-- (New IRF/Waiting Approval/Approved/Back to Draft/dst, ditujukan ke user
-- spesifik, dgn is_read & link yang benar-benar berfungsi).
--
-- Tabel baru ini menggantikan sumber data bell (Notification_model 4
-- method utamanya di-rewrite utk baca dari sini, bukan user_logs lagi).
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Penerima notifikasi',
  `title` varchar(150) NOT NULL,
  `message` varchar(500) DEFAULT NULL,
  `type` varchar(20) DEFAULT 'info' COMMENT 'success/warning/danger/info - reuse warna badge yang sudah ada',
  `icon` varchar(30) DEFAULT 'fa-bell' COMMENT 'FontAwesome class tanpa prefix fas',
  `link` varchar(255) DEFAULT NULL COMMENT 'Path relatif (tanpa base_url) tujuan klik notifikasi',
  `source_module` varchar(50) DEFAULT NULL COMMENT 'mis. consumable_request, prf, erf, po - utk grouping/debug',
  `source_id` int(11) DEFAULT NULL COMMENT 'header id dokumen sumber',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user_unread` (`user_id`, `is_read`),
  KEY `idx_notifications_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
