-- =====================================================================
-- Migration: Back to Draft 2-arah (reply) untuk PO
--
-- ERP Revision Notes, [[erp_revision_cross_cutting]]: fitur "reply kaya
-- chat" sudah ada di IRF/PRF/ERF (2026-09-02), PO sengaja belum ikut waktu
-- itu (di luar scope). Sekarang direplikasi persis pola yang sama:
-- `po_approval_logs.action` (ENUM, beda dari IRF/PRF yang VARCHAR bebas —
-- makanya PO butuh migration, sama kasusnya kayak ERF dulu) dilebarkan
-- tambah value 'Reply'.
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

ALTER TABLE `po_approval_logs`
  MODIFY `action` ENUM('Submit','Approve HOD','Endorse','Approve Final','Return to Draft','Reject','Reply') NOT NULL;
