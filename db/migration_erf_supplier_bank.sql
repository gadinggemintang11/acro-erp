-- Migration: kolom supplier/bank (snapshot) di ERF.
-- Tujuan: ERF bisa memilih supplier (vendor), lalu nama vendor + nama bank + no. rekening
-- ditampilkan di PDF/Excel di bawah baris NAME. Data bank disnapshot saat ERF dibuat
-- supaya PDF lama tetap akurat meski data bank supplier diubah kemudian.
-- Catatan: kolom bank di tabel `suppliers` sudah dibuat di migration_prf_supplier_bank.sql.

ALTER TABLE `erf_header`
    ADD COLUMN `supplier_id` INT(11) NULL AFTER `requester_name`,
    ADD COLUMN `supplier_name` VARCHAR(150) NULL AFTER `supplier_id`,
    ADD COLUMN `bank_name` VARCHAR(100) NULL AFTER `supplier_name`,
    ADD COLUMN `bank_account` VARCHAR(50) NULL AFTER `bank_name`;
