-- =====================================================================
-- Migration: Kategori "Jasa" di IRF (Consumable Request) — ERP Revision
-- Notes 2026-09-02, lanjutan [[revision_irf_auto_pr_at_issuance]].
--
-- Requestor sekarang bisa pilih 3 kategori kebutuhan: General, Produksi
-- (non material), Jasa. TIDAK ADA pilihan "Asset" — itu ditentukan
-- Accounting belakangan saat proses jadi PR (lihat migration_pr_asset_
-- reclass di file terpisah kalau ada, atau lihat kerjaan create_pr()).
--
-- Idempotent: aman dijalankan berulang.
-- DB: nsi-db
-- =====================================================================

ALTER TABLE consumable_request_header
    MODIFY COLUMN category ENUM('General','Produksi','Jasa') NOT NULL DEFAULT 'General';
