-- ERP Revision Notes (2026-09-02) — "PRF Unit" (poin 3 di [[erp_revision_prf_erf]]).
-- prf_details SEBELUMNYA tidak punya kolom satuan sama sekali (cuma
-- description/qty/unit_price). User konfirmasi 2026-09-04: tambahkan
-- satuan per baris item PRF, dropdown-nya reuse master common_code
-- group 'SATUAN' yang SUDAH DIPAKAI di Master Material/Inventory/Machine/
-- Asset/Jasa/FG/Semifinished/Scrap (pola common_code, bukan ENUM baru).
--
-- Nullable & tidak wajib — PRF lama tanpa satuan tetap valid (konsisten
-- dgn prinsip "field baru, item/data lama tidak dipaksa migrasi").

ALTER TABLE prf_details
    ADD COLUMN unit VARCHAR(20) NULL AFTER qty;

-- Tambahan kecil ke master SATUAN: 'Transaction' (TRX) belum ada di daftar
-- (cuma PCS/KG/LOT/MTR/PACK/PAIRS/PAKET/RIM/ROLL/SET/Strip/UNIT/YARD/CAN/
-- HARI/JAM) — relevan khusus utk baris PRF yang sifatnya jasa/biaya per
-- transaksi (bukan barang fisik), sesuai tabel Unit di dokumen revisi.
-- `common_code` TIDAK punya UNIQUE(group_code, code) — pakai INSERT...SELECT
-- ...WHERE NOT EXISTS (bukan INSERT IGNORE) supaya migration aman dijalankan
-- ulang tanpa bikin baris dobel, tidak mengubah/menghapus baris SATUAN lain.
INSERT INTO common_code (group_code, code, description, status, created_at)
SELECT 'SATUAN', 'TRX', 'Transaction', 'active', NOW()
WHERE NOT EXISTS (SELECT 1 FROM common_code WHERE group_code = 'SATUAN' AND code = 'TRX');
