-- =====================================================================
-- Migration: Back to Draft jadi 2 arah (reply/chat) — ERP Revision Notes
-- 2026-09-02, Fase 2 poin 11b.
--
-- Requestor & approver bisa bolak-balik reply selama status masih Draft
-- (window revisi setelah Return to Draft/Reject), sampai requestor submit
-- ulang. Ditandai dgn action baru 'reply' di tabel log yang SUDAH ADA per
-- modul (bukan tabel baru).
--
-- IRF (consumable_request_approval_logs.action) & PRF (prf_approval_logs.
-- approval_level/status) kolomnya VARCHAR bebas — TIDAK perlu migration,
-- tinggal insert value baru dari kode PHP.
--
-- ERF (erf_approval_logs.action) kolomnya ENUM — SATU-SATUNYA yang perlu
-- migration SQL di sini: lebarkan enum tambah 'Reply'.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

ALTER TABLE erf_approval_logs
    MODIFY COLUMN action ENUM('Submit','Approve HOD','Endorse','Approve Final','Return to Draft','Reject','Record Payment','Cancel','Reply') NOT NULL;
