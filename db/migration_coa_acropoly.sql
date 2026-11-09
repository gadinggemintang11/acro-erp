-- =====================================================================
-- Migration: Chart of Accounts PT ACROPOLY INTERNATIONAL (2026-11-07)
-- Sumber   : assets/COA_2026-11-07_22-41-18.xlsx (114 akun detail)
--
-- Format kode : Excel menulis "1,101,001.00" (angka berformat ribuan).
--               Disimpan sebagai "1.101.001" -- cocok dengan filter Kas/Bank
--               di kode ('1.101' Kas, '1.102' Bank).
-- Header      : 30 akun header ditambahkan supaya tree COA & laporan
--               (Neraca / Laba Rugi) bisa mengelompokkan. Nama header usulan
--               sistem, boleh diganti di menu COA.
--               Kelas 7 sengaja TANPA header "7": 7.300 = Biaya, 7.400 =
--               Pendapatan; laporan mengelompokkan per tipe header teratas,
--               jadi satu header campuran akan membuat Pendapatan lain-lain
--               terhitung sebagai biaya.
-- Tipe akun   : 1 Aset, 2 Liabilitas, 3 Ekuitas, 4 Pendapatan, 5 HPP,
--               6 Biaya, 7.300 Biaya, 7.400 Pendapatan, 8 Biaya.
-- Saldo normal: Aset/HPP/Biaya = Debit, lainnya Kredit. Pengecualian:
--               1.202.* Akumulasi Penyusutan = Kredit, 4.102.001 Discount = Debit.
-- Mata uang   : akun bank bertanda (USD) = USD, sisanya IDR.
-- Nonaktif    : 1.105.050 (nama kosong di Excel) dan 2.102.01 HUTANG PPH23
--               (format kode beda & dobel dengan 2.102.003 PPh Pasal 23).
--
-- Aman dijalankan ulang: upsert per account_code (ID akun yang sudah ada
-- tidak berubah, jadi setting akun / jurnal yang menunjuk ID tetap valid).
-- Akun lama yang tidak ada di daftar ini TIDAK dihapus.
-- =====================================================================

START TRANSACTION;

INSERT INTO chart_of_accounts
  (account_code, account_name, parent_id, account_type, currency, is_header, status, normal_balance)
VALUES
('1', 'ASET', 0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1.101', 'Kas', 0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1.102', 'Bank', 0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1.103', 'Piutang Usaha', 0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1.104', 'Biaya Dibayar Dimuka', 0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1.105', 'Persediaan', 0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1.108', 'Pajak Dibayar Dimuka', 0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1.109', 'Aset Tetap', 0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1.202', 'Akumulasi Penyusutan', 0, 'Aset', 'IDR', 1, 'active', 'Kredit'),
('2', 'LIABILITAS', 0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('2.101', 'Hutang Dagang', 0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('2.102', 'Hutang Pajak', 0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('2.104', 'Hutang Biaya & Hutang Lainnya', 0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('3', 'EKUITAS', 0, 'Ekuitas', 'IDR', 1, 'active', 'Kredit'),
('3.100', 'Modal & Laba', 0, 'Ekuitas', 'IDR', 1, 'active', 'Kredit'),
('4', 'PENDAPATAN', 0, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
('4.101', 'Penjualan', 0, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
('4.102', 'Potongan Penjualan', 0, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
('5', 'HARGA POKOK PENJUALAN', 0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('5.100', 'Harga Pokok Penjualan', 0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('5.200', 'Biaya Tenaga Kerja Langsung', 0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('5.300', 'Biaya Overhead Pabrik', 0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('6', 'BIAYA OPERASIONAL', 0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6.100', 'Biaya Penjualan', 0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6.200', 'Biaya Umum & Administrasi', 0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6.300', 'Biaya Penyusutan Kantor', 0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('7.300', 'BIAYA LAIN-LAIN', 0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('7.400', 'PENDAPATAN LAIN-LAIN', 0, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
('8', 'BIAYA NON-OPERASIONAL', 0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('8.100', 'Selisih Kurs & Pajak Penghasilan', 0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('1.101.001', 'Kas Kecil', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.101.002', 'Kas Besar', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.102.001', 'Bank BCA-8030736789', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.102.002', 'Bank Mandiri - 1350052013669 (IDR)', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.102.003', 'Bank Mandiri - 1350052016886 (USD)', 0, 'Aset', 'USD', 0, 'active', 'Debit'),
('1.102.101', 'Bank CCB - 2001189797 (IDR)', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.102.102', 'Bank CCB - 2001189697 (USD)', 0, 'Aset', 'USD', 0, 'active', 'Debit'),
('1.102.103', 'Deposito UOB', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.103.001', 'Piutang Dagang', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.104.002', 'Sewa Dibayar Dimuka', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.001', 'Persediaan Bahan Subkon', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.002', 'Persediaan Packaging', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.003', 'Persediaan Foam', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.004', 'Persediaan Lem', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.005', 'Persediaan Bahan Penolong', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.010', 'Persediaan Barang Mekanik', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.050', 'Persediaan 1.105.050 (nama belum diisi)', 0, 'Aset', 'IDR', 0, 'inactive', 'Debit'),
('1.105.100', 'Persediaan barang dalam Perjalanan', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.200', 'Persediaan Barang dalam Proses', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.201', 'Persediaan Kertas Dalam Proses', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.250', 'Gagang Kering', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.300', 'Persediaan Barang Jadi', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.105.301', 'Persediaan Bahan Cetak', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.108.001', 'PPN Masukan', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.108.002', 'PPh Pasal 25', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.108.005', 'Akun Ayat Silang', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.109.003', 'Mesin Produksi', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.109.004', 'Peralatan Produksi', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.109.005', 'Peralatan Kantor', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.109.006', 'Electrical', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.109.007', 'Kendaraan Kantor', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.109.011', 'Software', 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1.202.003', 'Akum. Penyusutan Mesin Produksi', 0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1.202.004', 'Akum. Penyusutan Peralatan Produksi', 0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1.202.005', 'Akum. Penyusutan Peralatan Kantor', 0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1.202.006', 'Akum. Penyusutan Kendaraan Opersional', 0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1.202.007', 'Akum. Penyusutan Kendaraan Kantor', 0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1.202.008', 'Akumulasi Penyusutan Software', 0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('2.101.001', 'Hutang Dagang', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.102.001', 'PPN Keluaran', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.102.002', 'PPh Pasal 21', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.102.003', 'PPh Pasal 23', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.102.005', 'PPH Pasal 25/29', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.102.006', 'PPH Terutang', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.104.001', 'Hutang Biaya', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.104.002', 'Hutang Biaya Gaji', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.104.004', 'Hutang Lainnya', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.104.998', 'Hutang Belum ditagih', 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2.102.01', 'HUTANG PPH23', 0, 'Liabilitas', 'IDR', 0, 'inactive', 'Kredit'),
('3.100.001', 'Modal', 0, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),
('3.100.003', 'Laba ditahan', 0, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),
('3.100.005', 'Setoran Modal', 0, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),
('3.100.006', 'Laba Tahun Berjalan', 0, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),
('4.101.001', 'Penjualan Expor', 0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
('4.101.002', 'Penjualan Limbah', 0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
('4.102.001', 'Discount', 0, 'Pendapatan', 'IDR', 0, 'active', 'Debit'),
('5.100.003', 'Harga Pokok Barang Jadi', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.100.005', 'Harga Pokok Flavour', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.100.006', 'Harga Pokok Bahan Cetak', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.100.999', 'Variance Produksi', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.200.001', 'Gaji & Tunjangan Produksi', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.300.001', 'BPJS Kesehatan', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.300.002', 'Biaya Listrik', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.300.003', 'Biaya Perlengkapan Produksi', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.300.004', 'Biaya Perawatan Mesin', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.300.020', 'Biaya Penyusutan Mesin', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.300.021', 'Biaya Penyusutan Peralatan Pabrik', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5.300.022', 'Biaya Penyusutan Kendraan Operasional', 0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('6.100.001', 'BPJS Tenaga Kerja', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.100.002', 'Biaya Pengobatan', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.100.003', 'Biaya Pengiriman', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.100.004', 'Biaya Ekspedisi,Pos', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.100.005', 'Biaya BBM, Toll, Parkir', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.001', 'Gaji & Tunjangan Staff Kantor', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.002', 'Biaya ATK', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.003', 'Biaya Internet', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.004', 'Biaya Asuransi Subcont', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.005', 'Biaya IPL, Air', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.006', 'Biaya Komunikasi', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.007', 'Biaya Kebersihan', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.008', 'Biaya Operasional Pabrik', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.009', 'Biaya Perijinan Tenaga Asing', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.010', 'Biaya Perawatan Kendaraan', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.011', 'Biaya Perawatan Peralatan', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.012', 'Biaya Tenaga Ahli', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.013', 'Biaya Pemasaran', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.014', 'Biaya Perawatan Gedung', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.015', 'Biaya Freight Forwarding', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.016', 'Biaya Akomodasi', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.017', 'Biaya Umum dan Adm', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.018', 'Biaya Perjalanan Dinas', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.019', 'Biaya Perlengkapan Kantor', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.020', 'Biaya Perijinan Perusahaan', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.021', 'Biaya Sewa', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.022', 'Biaya Makan & Minum', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.023', 'Biaya Trucking', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.024', 'Biaya Bahan Bakar', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.025', 'Biaya Pajak', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.026', 'pph 23', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.027', 'PPH Final', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.029', 'PPh', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.030', 'PBB', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.200.031', 'PDRI', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.300.001', 'Biaya Penyusutan Peralatan Kantor', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.300.002', 'Biaya Penyusutan Kendaraan', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6.300.005', 'Biaya Penyusutan Software', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7.300.001', 'Biaya Admin Bank', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7.300.002', 'Biaya Pajak Bunga Giro', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7.300.003', 'Biaya Pembulatan', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7.300.004', 'DENDA PPH21', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7.400.001', 'Pendapatan Bunga Giro', 0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
('7.400.003', 'Pendapatan Pembulatan', 0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
('8.100.001', 'Rugi Selisih Kurs', 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('8.100.002', 'Biaya Pajak Penghasilan', 0, 'Biaya', 'IDR', 0, 'active', 'Debit')
ON DUPLICATE KEY UPDATE
  account_name   = VALUES(account_name),
  account_type   = VALUES(account_type),
  currency       = VALUES(currency),
  is_header      = VALUES(is_header),
  status         = VALUES(status),
  normal_balance = VALUES(normal_balance);

-- Parent = potongan kode sebelum "." terakhir ('1.101.001' -> '1.101' -> '1').
UPDATE chart_of_accounts c
LEFT JOIN chart_of_accounts p
  ON p.account_code = SUBSTRING_INDEX(c.account_code, '.', LENGTH(c.account_code) - LENGTH(REPLACE(c.account_code, '.', '')))
SET c.parent_id = COALESCE(p.id, 0)
WHERE c.account_code IN ('1', '1.101', '1.102', '1.103', '1.104', '1.105', '1.108', '1.109', '1.202', '2', '2.101', '2.102', '2.104', '3', '3.100', '4', '4.101', '4.102', '5', '5.100', '5.200', '5.300', '6', '6.100', '6.200', '6.300', '7.300', '7.400', '8', '8.100', '1.101.001', '1.101.002', '1.102.001', '1.102.002', '1.102.003', '1.102.101', '1.102.102', '1.102.103', '1.103.001', '1.104.002', '1.105.001', '1.105.002', '1.105.003', '1.105.004', '1.105.005', '1.105.010', '1.105.050', '1.105.100', '1.105.200', '1.105.201', '1.105.250', '1.105.300', '1.105.301', '1.108.001', '1.108.002', '1.108.005', '1.109.003', '1.109.004', '1.109.005', '1.109.006', '1.109.007', '1.109.011', '1.202.003', '1.202.004', '1.202.005', '1.202.006', '1.202.007', '1.202.008', '2.101.001', '2.102.001', '2.102.002', '2.102.003', '2.102.005', '2.102.006', '2.104.001', '2.104.002', '2.104.004', '2.104.998', '2.102.01', '3.100.001', '3.100.003', '3.100.005', '3.100.006', '4.101.001', '4.101.002', '4.102.001', '5.100.003', '5.100.005', '5.100.006', '5.100.999', '5.200.001', '5.300.001', '5.300.002', '5.300.003', '5.300.004', '5.300.020', '5.300.021', '5.300.022', '6.100.001', '6.100.002', '6.100.003', '6.100.004', '6.100.005', '6.200.001', '6.200.002', '6.200.003', '6.200.004', '6.200.005', '6.200.006', '6.200.007', '6.200.008', '6.200.009', '6.200.010', '6.200.011', '6.200.012', '6.200.013', '6.200.014', '6.200.015', '6.200.016', '6.200.017', '6.200.018', '6.200.019', '6.200.020', '6.200.021', '6.200.022', '6.200.023', '6.200.024', '6.200.025', '6.200.026', '6.200.027', '6.200.029', '6.200.030', '6.200.031', '6.300.001', '6.300.002', '6.300.005', '7.300.001', '7.300.002', '7.300.003', '7.300.004', '7.400.001', '7.400.003', '8.100.001', '8.100.002');

INSERT INTO schema_migrations (filename, status, statements_run, message, applied_at)
SELECT 'migration_coa_acropoly.sql', 'applied', 3, 'COA Acropoly 144 akun (30 header + 114 detail)', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM schema_migrations WHERE filename = 'migration_coa_acropoly.sql');

COMMIT;

-- Verifikasi:
--   SELECT account_type, is_header, status, COUNT(*) FROM chart_of_accounts GROUP BY 1,2,3;
--   SELECT c.account_code, c.account_name, p.account_code parent FROM chart_of_accounts c
--     LEFT JOIN chart_of_accounts p ON p.id = c.parent_id ORDER BY c.account_code;
