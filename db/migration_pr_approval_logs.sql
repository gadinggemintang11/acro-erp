-- ============================================================
-- Riwayat Approval + Back to Draft 2-arah (reply) untuk PR
-- ------------------------------------------------------------
-- User 2026-09-06: PR (General/Jasa/Asset) sudah punya tombol Back to Draft
-- + alasan, tapi SAMA SEKALI belum punya tabel riwayat — jadi alasannya cuma
-- tersimpan di kolom `pr_header.return_reason` (1 arah, ketimpa tiap return
-- berikutnya) dan requestor tidak bisa membalas.
--
-- Tabel ini replikasi persis `po_approval_logs` (lihat
-- db/migration_po_back_to_draft_reply.sql) — sengaja SAMA strukturnya supaya
-- pola model/_log()/view-nya bisa dipakai ulang tanpa modifikasi.
--
-- Action 'Reply' sudah langsung masuk ENUM di sini (PO dulu perlu 2 migration
-- terpisah karena tabelnya dibuat lebih dulu sebelum fitur reply ada).
--
-- PR Produksi (type='produksi', alur 1-stage) ikut kebagian tabel ini juga —
-- log Submit/Approve/Reject-nya tercatat, walau UI riwayatnya baru dipasang
-- di General/Jasa/Asset.
-- ============================================================

CREATE TABLE IF NOT EXISTS `pr_approval_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pr_header_id` int(11) NOT NULL,
  `action` enum('Submit','Approve HOD','Endorse','Approve Final','Return to Draft','Reject','Reply') NOT NULL,
  `actor_id` int(11) NOT NULL,
  `actor_name` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pr_header_id` (`pr_header_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
