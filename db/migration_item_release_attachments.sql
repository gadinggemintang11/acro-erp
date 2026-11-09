-- =====================================================================
-- Migration: Attachment "Bukti Pengeluaran" wajib untuk Item Release
-- (ERP Revision Notes 2026-09-02, Fase 2 poin 8). Item Release =
-- Consumable_out (barang consumable) + Material_issuance (barang produksi).
-- Skema sama persis dengan pola erf_attachments/prf_attachments yang sudah
-- ada & teruji. Idempotent — aman dijalankan ulang.
-- =====================================================================

CREATE TABLE IF NOT EXISTS consumable_out_attachments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    consumable_out_header_id INT(11) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT(11) DEFAULT 0,
    uploaded_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_consumable_out_header_id (consumable_out_header_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS material_issuance_attachments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    mr_header_id INT(11) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT(11) DEFAULT 0,
    uploaded_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mr_header_id (mr_header_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
