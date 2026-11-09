-- =====================================================================
-- Migration: Attachment "Bukti Penerimaan" di IRF (Consumable_request)
-- (ERP Revision Notes 2026-09-02, lanjutan Fase 2 poin 8 — klarifikasi user:
-- "di IRF juga bisa upload attachment, karena itu permintaan barang, ketika
-- sudah dikeluarkan baru button upload bukti penerimaan"). Beda dgn
-- consumable_out_attachments/material_issuance_attachments ("Bukti
-- Pengeluaran" oleh GUDANG) — ini "Bukti Penerimaan" oleh REQUESTOR, dilekatkan
-- di dokumen IRF, muncul begitu status IRF = 'Released' (barang sudah
-- dikeluarkan gudang sepenuhnya). Skema sama persis pola erf_attachments.
-- Idempotent — aman dijalankan ulang.
-- =====================================================================

CREATE TABLE IF NOT EXISTS consumable_request_attachments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    consumable_request_header_id INT(11) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT(11) DEFAULT 0,
    uploaded_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_consumable_request_header_id (consumable_request_header_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
