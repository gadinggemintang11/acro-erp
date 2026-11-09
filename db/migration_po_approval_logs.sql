-- =====================================================================
-- Migration: History/Riwayat Approval untuk PO (po_approval_logs)
-- (ERP Revision Notes 2026-09-02, Fase 2 poin 11 — replikasi History
-- universal ke PO; IRF/PRF/ERF sudah punya tabel log serupa duluan).
--
-- Skema sama persis pola erf_approval_logs/prf_approval_logs. Berlaku ke
-- SEMUA varian PO (base/general/jasa/asset) sekaligus karena semuanya
-- share Po_model + po_header yang sama (po_header_id = FK generik, bukan
-- per-varian).
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

CREATE TABLE IF NOT EXISTS po_approval_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    po_header_id INT(11) NOT NULL,
    action ENUM('Submit','Approve HOD','Endorse','Approve Final','Return to Draft','Reject') NOT NULL,
    actor_id INT(11) NOT NULL,
    actor_name VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_po_header_id (po_header_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
