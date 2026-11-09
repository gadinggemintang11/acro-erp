-- Migration: tambah data bank di master supplier + kolom supplier/bank (snapshot) di PRF.
-- Tujuan: PRF bisa memilih supplier (vendor), lalu nama vendor + nama bank + no. rekening
-- ditampilkan di PDF/Excel di bawah baris NAME. Data bank disnapshot saat PRF dibuat
-- supaya PDF lama tetap akurat meski data bank supplier diubah kemudian.

-- 1. Master supplier: kolom bank
ALTER TABLE `suppliers`
    ADD COLUMN `bank_name` VARCHAR(100) NULL AFTER `telepon`,
    ADD COLUMN `bank_account` VARCHAR(50) NULL AFTER `bank_name`;

-- 2. PRF header: referensi supplier + snapshot vendor/bank
ALTER TABLE `prf_header`
    ADD COLUMN `supplier_id` INT(11) NULL AFTER `requester_name`,
    ADD COLUMN `supplier_name` VARCHAR(150) NULL AFTER `supplier_id`,
    ADD COLUMN `bank_name` VARCHAR(100) NULL AFTER `supplier_name`,
    ADD COLUMN `bank_account` VARCHAR(50) NULL AFTER `bank_name`;
