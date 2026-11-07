-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 25 Nov 2025 pada 10.25
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `PT. NEW SPECIALIFE INDUSTRIESsis_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `adjustment_details`
--

CREATE TABLE `adjustment_details` (
  `id` int(11) NOT NULL,
  `adjustment_header_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL COMMENT 'material, semifinished, finished_goods, inventory, machine',
  `adjustment_type` enum('IN','OUT') NOT NULL COMMENT 'IN = Penyesuaian Masuk, OUT = Penyesuaian Keluar',
  `qty` decimal(15,4) NOT NULL,
  `unit_value` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Wajib diisi untuk tipe IN',
  `stock_on_hand_id` int(11) DEFAULT NULL COMMENT 'Wajib diisi untuk tipe OUT & item_type = material',
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `adjustment_header`
--

CREATE TABLE `adjustment_header` (
  `id` int(11) NOT NULL,
  `doc_no` varchar(50) NOT NULL,
  `doc_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Confirmed, Cancelled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ap_invoice_details`
--

CREATE TABLE `ap_invoice_details` (
  `id` int(11) NOT NULL,
  `ap_invoice_header_id` int(11) NOT NULL,
  `grn_detail_id` int(11) DEFAULT NULL COMMENT 'Link ke GRN detail jika berbasis GRN',
  `item_id` int(11) DEFAULT NULL,
  `item_type` varchar(50) DEFAULT NULL,
  `account_id` int(11) DEFAULT NULL COMMENT 'Link ke COA jika Jasa/Non-GRN',
  `description` varchar(255) NOT NULL,
  `qty` decimal(15,4) NOT NULL,
  `unit_price` decimal(20,2) NOT NULL,
  `total_price` decimal(20,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ap_invoice_header`
--

CREATE TABLE `ap_invoice_header` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(100) NOT NULL COMMENT 'Nomor Faktur dari Supplier',
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL COMMENT 'Tanggal Jatuh Tempo',
  `supplier_id` int(11) NOT NULL,
  `grn_ids` varchar(255) DEFAULT NULL COMMENT 'JSON array [id1, id2] jika berbasis GRN',
  `invoice_type` enum('GRN_BASED','NON_GRN') NOT NULL,
  `subtotal` decimal(20,2) NOT NULL DEFAULT 0.00,
  `ppn_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `pph_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_invoice` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Diupdate oleh modul Payment',
  `payment_status` enum('Unpaid','Partial','Paid') NOT NULL DEFAULT 'Unpaid',
  `journal_header_id` int(11) DEFAULT NULL COMMENT 'Link ke Jurnal Umum',
  `status` varchar(20) NOT NULL DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ap_payment_details`
--

CREATE TABLE `ap_payment_details` (
  `id` int(11) NOT NULL,
  `payment_header_id` int(11) NOT NULL,
  `ap_invoice_header_id` int(11) NOT NULL COMMENT 'Faktur yg dibayar',
  `amount_paid` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Jumlah yg dibayar utk faktur ini'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ap_payment_header`
--

CREATE TABLE `ap_payment_header` (
  `id` int(11) NOT NULL,
  `payment_no` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `payment_account_id` int(11) NOT NULL COMMENT 'ID Akun COA (Kas/Bank)',
  `total_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Posted, Cancelled',
  `journal_header_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ar_invoice_details`
--

CREATE TABLE `ar_invoice_details` (
  `id` int(11) NOT NULL,
  `ar_invoice_header_id` int(11) NOT NULL,
  `source_detail_id` int(11) DEFAULT NULL COMMENT 'ID dari fg_delivery_details atau scrap_out_detail',
  `item_id` int(11) NOT NULL COMMENT 'ID dari finished_goods atau master_scrap',
  `item_type` varchar(50) NOT NULL COMMENT 'finished_goods atau scrap',
  `description` varchar(255) NOT NULL,
  `qty` decimal(15,4) NOT NULL,
  `unit_price` decimal(20,2) NOT NULL,
  `total_price` decimal(20,2) NOT NULL,
  `unit_price_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_price_local` decimal(20,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ar_invoice_header`
--

CREATE TABLE `ar_invoice_header` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `source_type` enum('FG_DELIVERY','SCRAP_OUT') NOT NULL,
  `source_header_ids` varchar(255) DEFAULT NULL COMMENT 'JSON array [id1, id2] dari fg_delivery_header atau scrap_out_header',
  `currency` varchar(10) NOT NULL DEFAULT 'IDR',
  `exchange_rate` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `subtotal` decimal(20,2) NOT NULL DEFAULT 0.00,
  `ppn_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `pph_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_invoice` decimal(20,2) NOT NULL DEFAULT 0.00,
  `subtotal_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `ppn_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `pph_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_invoice_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(20,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('Unpaid','Partial','Paid') NOT NULL DEFAULT 'Unpaid',
  `journal_header_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Posted, Cancelled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ar_receipt_details`
--

CREATE TABLE `ar_receipt_details` (
  `id` int(11) NOT NULL,
  `receipt_header_id` int(11) NOT NULL,
  `ar_invoice_header_id` int(11) NOT NULL COMMENT 'Faktur yg dibayar',
  `amount_paid` decimal(20,2) NOT NULL DEFAULT 0.00,
  `amount_paid_local` decimal(20,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ar_receipt_header`
--

CREATE TABLE `ar_receipt_header` (
  `id` int(11) NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `receipt_date` date NOT NULL,
  `customer_id` int(11) NOT NULL,
  `payment_account_id` int(11) NOT NULL COMMENT 'ID Akun COA (Kas/Bank)',
  `currency` varchar(10) NOT NULL,
  `exchange_rate` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `total_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_amount_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Posted, Cancelled',
  `journal_header_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `bom_details`
--

CREATE TABLE `bom_details` (
  `id` int(11) NOT NULL,
  `bom_header_id` int(11) NOT NULL,
  `component_item_id` int(11) NOT NULL,
  `component_item_type` varchar(50) NOT NULL COMMENT '''material'', ''semifinished''',
  `quantity_required` decimal(12,4) NOT NULL DEFAULT 1.0000 COMMENT 'Qty komponen per 1 unit FG',
  `unit_measure` varchar(20) DEFAULT NULL COMMENT 'Satuan komponen (diambil saat simpan)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bom_details`
--

INSERT INTO `bom_details` (`id`, `bom_header_id`, `component_item_id`, `component_item_type`, `quantity_required`, `unit_measure`) VALUES
(9, 6, 9, 'material', 1.0000, NULL),
(10, 6, 11, 'material', 0.2000, NULL),
(11, 6, 10, 'material', 1.0000, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `bom_header`
--

CREATE TABLE `bom_header` (
  `id` int(11) NOT NULL,
  `fg_id` int(11) NOT NULL COMMENT 'ID dari finished_goods (Style)',
  `bom_code` varchar(50) DEFAULT NULL COMMENT 'Optional: Kode unik BOM jika perlu',
  `revision` int(11) DEFAULT 1,
  `description` text DEFAULT NULL,
  `status` enum('Draft','Active','Inactive','Confirm') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bom_header`
--

INSERT INTO `bom_header` (`id`, `fg_id`, `bom_code`, `revision`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(6, 2, NULL, 1, NULL, 'Active', 4, '2025-11-06 13:45:30', '2025-11-06 13:46:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cash_bank_details`
--

CREATE TABLE `cash_bank_details` (
  `id` int(11) NOT NULL,
  `header_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL COMMENT 'Akun lawan (e.g., Biaya Listrik)',
  `amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `amount_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cash_bank_header`
--

CREATE TABLE `cash_bank_header` (
  `id` int(11) NOT NULL,
  `doc_no` varchar(50) NOT NULL,
  `doc_date` date NOT NULL,
  `transaction_type` enum('IN','OUT') NOT NULL COMMENT 'IN = Masuk, OUT = Keluar',
  `cash_bank_account_id` int(11) NOT NULL COMMENT 'ID Akun COA (Kas/Bank)',
  `currency` varchar(10) NOT NULL,
  `exchange_rate` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `total_amount` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_amount_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `description` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft',
  `journal_header_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `account_type` enum('Aset','Liabilitas','Ekuitas','Pendapatan','HPP','Biaya') NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'IDR',
  `is_header` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Header (Grup), 0=Postable (Detail)',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `normal_balance` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`id`, `account_code`, `account_name`, `parent_id`, `account_type`, `currency`, `is_header`, `status`, `normal_balance`) VALUES
(1, '1', 'HARTA', 0, 'Aset', 'IDR', 1, 'active', NULL),
(2, '2', 'KEWAJIBAN', 0, 'Liabilitas', 'IDR', 1, 'active', NULL),
(3, '3', 'EKUITAS', 0, 'Ekuitas', 'IDR', 1, 'active', NULL),
(4, '4', 'LABA RUGI', 0, 'Pendapatan', 'IDR', 1, 'active', NULL),
(5, '5', 'HPP & BIAYA PRODUKSI', 0, 'Biaya', 'IDR', 1, 'active', NULL),
(6, '6', 'BIAYA OPERASIONAL', 0, 'Biaya', 'IDR', 1, 'active', NULL),
(7, '7', 'PENDAPATAN & BIAYA LAIN', 0, 'Biaya', 'IDR', 1, 'active', NULL),
(8, '8', 'BIAYA LAINNYA', 0, 'Biaya', 'IDR', 1, 'active', NULL),
(9, '1.101', 'Kas', 1, 'Aset', 'IDR', 1, 'active', 'Debit'),
(10, '1.102', 'Bank', 1, 'Aset', 'IDR', 1, 'active', 'Debit'),
(11, '1.103', 'Piutang Usaha', 1, 'Aset', 'IDR', 1, 'active', 'Debit'),
(12, '1.104', 'Sewa Dibayar Dimuka', 1, 'Aset', 'IDR', 1, 'active', 'Debit'),
(13, '1.105', 'Persediaan', 1, 'Aset', 'IDR', 1, 'active', 'Debit'),
(14, '1.108', 'Pajak Dibayar Dimuka', 1, 'Aset', 'IDR', 1, 'active', 'Debit'),
(15, '1.109', 'Aktiva Tetap', 1, 'Aset', 'IDR', 1, 'active', 'Debit'),
(16, '1.202', 'Akumulasi Penyusutan', 1, 'Aset', 'IDR', 1, 'active', 'Kredit'),
(17, '2.101', 'Hutang Dagang', 2, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
(18, '2.102', 'Hutang Pajak', 2, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
(19, '2.104', 'Hutang Lainnya', 2, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
(20, '3.100', 'Modal', 3, 'Ekuitas', 'IDR', 1, 'active', 'Kredit'),
(21, '4.101', 'Penjualan', 4, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
(22, '4.102', 'Diskon Penjualan', 4, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
(23, '5.100', 'Harga Pokok (HPP)', 5, 'HPP', 'IDR', 1, 'active', 'Debit'),
(24, '5.200', 'Biaya Produksi', 5, 'Biaya', 'IDR', 1, 'active', 'Debit'),
(25, '5.300', 'Biaya Overhead Pabrik', 5, 'Biaya', 'IDR', 1, 'active', 'Debit'),
(26, '6.100', 'Biaya Gaji & Terkait', 6, 'Biaya', 'IDR', 1, 'active', 'Debit'),
(27, '6.200', 'Biaya Kantor & Umum', 6, 'Biaya', 'IDR', 1, 'active', 'Debit'),
(28, '6.300', 'Biaya Penyusutan', 6, 'Biaya', 'IDR', 1, 'active', 'Debit'),
(29, '7.300', 'Biaya Bank & Pajak Giro', 7, 'Biaya', 'IDR', 1, 'active', 'Debit'),
(30, '7.400', 'Pendapatan Bunga & Lainnya', 7, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
(31, '8.100', 'Rugi/Lain', 8, 'Biaya', 'IDR', 1, 'active', 'Debit'),
(32, '1.101.001.00', 'Kas Kecil', 9, 'Aset', 'IDR', 0, 'active', 'Debit'),
(33, '1.101.002.00', 'Kas Besar', 9, 'Aset', 'IDR', 0, 'active', 'Debit'),
(34, '1.102.001.00', 'Bank BCA-8030736789', 10, 'Aset', 'IDR', 0, 'active', 'Debit'),
(35, '1.102.002.00', 'Bank Mandiri - 1350052013669 (IDR)', 10, 'Aset', 'IDR', 0, 'active', 'Debit'),
(36, '1.102.003.00', 'Bank Mandiri - 1350052016886 (USD)', 10, 'Aset', 'USD', 0, 'active', 'Debit'),
(37, '1.102.101.00', 'Bank CCB - 2001189797 (IDR)', 10, 'Aset', 'IDR', 0, 'active', 'Debit'),
(38, '1.102.102.00', 'Bank CCB - 2001189697 (USD)', 10, 'Aset', 'USD', 0, 'active', 'Debit'),
(39, '1.102.103.00', 'Deposito UOB', 10, 'Aset', 'IDR', 0, 'active', 'Debit'),
(40, '1.103.001.00', 'Piutang Dagang', 11, 'Aset', 'IDR', 0, 'active', 'Debit'),
(41, '1.104.002.00', 'Sewa Dibayar Dimuka', 12, 'Aset', 'IDR', 0, 'active', 'Debit'),
(42, '1.105.001.00', 'Persediaan Bahan Subkon', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(43, '1.105.002.00', 'Persediaan Packaging', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(44, '1.105.003.00', 'Persediaan Foam', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(45, '1.105.004.00', 'Persediaan Lem', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(46, '1.105.005.00', 'Persediaan Bahan Penolong', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(47, '1.105.010.00', 'Persediaan Barang Mekanik', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(48, '1.105.050.00', 'Persediaan (lain)', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(49, '1.105.100.00', 'Persediaan barang dalam Perjalanan', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(50, '1.105.200.00', 'Persediaan Barang dalam Proses', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(51, '1.105.201.00', 'Persediaan Kertas Dalam Proses', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(52, '1.105.250.00', 'Gagang Kering', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(53, '1.105.300.00', 'Persediaan Barang Jadi', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(54, '1.105.301.00', 'Persediaan Bahan Cetak', 13, 'Aset', 'IDR', 0, 'active', 'Debit'),
(55, '1.108.001.00', 'PPN Masukan', 14, 'Aset', 'IDR', 0, 'active', 'Debit'),
(56, '1.108.002.00', 'PPh Pasal 25', 14, 'Aset', 'IDR', 0, 'active', 'Debit'),
(57, '1.108.005.00', 'Akun Ayat Silang', 14, 'Aset', 'IDR', 0, 'active', 'Debit'),
(58, '1.109.003.00', 'Mesin Produksi', 15, 'Aset', 'IDR', 0, 'active', 'Debit'),
(59, '1.109.004.00', 'Peralatan Produksi', 15, 'Aset', 'IDR', 0, 'active', 'Debit'),
(60, '1.109.005.00', 'Peralatan Kantor', 15, 'Aset', 'IDR', 0, 'active', 'Debit'),
(61, '1.109.006.00', 'Electrical', 15, 'Aset', 'IDR', 0, 'active', 'Debit'),
(62, '1.109.007.00', 'Kendaraan Kantor', 15, 'Aset', 'IDR', 0, 'active', 'Debit'),
(63, '1.109.011.00', 'Software', 15, 'Aset', 'IDR', 0, 'active', 'Debit'),
(64, '1.202.003.00', 'Akum. Penyusutan Mesin Produksi', 16, 'Aset', 'IDR', 0, 'active', 'Kredit'),
(65, '1.202.004.00', 'Akum. Penyusutan Peralatan Produksi', 16, 'Aset', 'IDR', 0, 'active', 'Kredit'),
(66, '1.202.005.00', 'Akum. Penyusutan Peralatan Kantor', 16, 'Aset', 'IDR', 0, 'active', 'Kredit'),
(67, '1.202.006.00', 'Akum. Penyusutan Kendaraan Operasional', 16, 'Aset', 'IDR', 0, 'active', 'Kredit'),
(68, '1.202.007.00', 'Akum. Penyusutan Kendaraan Kantor', 16, 'Aset', 'IDR', 0, 'active', 'Kredit'),
(69, '1.202.008.00', 'Akumulasi Penyusutan Software', 16, 'Aset', 'IDR', 0, 'active', 'Kredit'),
(70, '2.101.001.00', 'Hutang Dagang', 17, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(71, '2.102.001.00', 'PPN Keluaran', 18, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(72, '2.102.002.00', 'PPh Pasal 21', 18, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(73, '2.102.003.00', 'PPh Pasal 23', 18, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(74, '2.102.005.00', 'PPH Pasal 25/29', 18, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(75, '2.102.006.00', 'PPH Terutang', 18, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(76, '2.104.001.00', 'Hutang Biaya', 19, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(77, '2.104.002.00', 'Hutang Biaya Gaji', 19, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(78, '2.104.004.00', 'Hutang Lainnya', 19, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(79, '2.104.998.00', 'Hutang Belum ditagih', 19, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
(80, '3.100.001.00', 'Modal', 20, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),
(81, '3.100.003.00', 'Laba ditahan', 20, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),
(82, '3.100.005.00', 'Setoran Modal', 20, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),
(83, '3.100.006.00', 'Laba Tahun Berjalan', 20, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),
(84, '4.101.001.00', 'Penjualan Expor', 21, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
(85, '4.101.002.00', 'Penjualan Limbah', 21, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
(86, '4.102.001.00', 'Discount', 22, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
(87, '5.100.003.00', 'Harga Pokok Barang Jadi', 23, 'HPP', 'IDR', 0, 'active', 'Debit'),
(88, '5.100.005.00', 'Harga Pokok Flavour', 23, 'HPP', 'IDR', 0, 'active', 'Debit'),
(89, '5.100.006.00', 'Harga Pokok Bahan Cetak', 23, 'HPP', 'IDR', 0, 'active', 'Debit'),
(90, '5.100.999.00', 'Variance Produksi', 23, 'HPP', 'IDR', 0, 'active', 'Debit'),
(91, '5.200.001.00', 'Gaji & Tunjangan Produksi', 24, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(92, '5.300.001.00', 'BPJS Kesehatan', 25, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(93, '5.300.002.00', 'Biaya Listrik', 25, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(94, '5.300.003.00', 'Biaya Perlengkapan Produksi', 25, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(95, '5.300.004.00', 'Biaya Perawatan Mesin', 25, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(96, '5.300.020.00', 'Biaya Penyusutan Mesin', 25, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(97, '5.300.021.00', 'Biaya Penyusutan Peralatan Pabrik', 25, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(98, '5.300.022.00', 'Biaya Penyusutan Kendaraan Operasional', 25, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(99, '6.100.001.00', 'BPJS Tenaga Kerja', 26, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(100, '6.100.002.00', 'Biaya Pengobatan', 26, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(101, '6.100.003.00', 'Biaya Pengiriman', 26, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(102, '6.100.004.00', 'Biaya Ekspedisi,Pos', 26, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(103, '6.100.005.00', 'Biaya BBM, Toll, Parkir', 26, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(104, '6.200.001.00', 'Gaji & Tunjangan Staff Kantor', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(105, '6.200.002.00', 'Biaya ATK', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(106, '6.200.003.00', 'Biaya Internet', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(107, '6.200.004.00', 'Biaya Asuransi Subcont', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(108, '6.200.005.00', 'Biaya IPL, Air', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(109, '6.200.006.00', 'Biaya Komunikasi', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(110, '6.200.007.00', 'Biaya Kebersihan', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(111, '6.200.008.00', 'Biaya Operasional Pabrik', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(112, '6.200.009.00', 'Biaya Perijinan Tenaga Asing', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(113, '6.200.010.00', 'Biaya Perawatan Kendaraan', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(114, '6.200.011.00', 'Biaya Perawatan Peralatan', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(115, '6.200.012.00', 'Biaya Tenaga Ahli', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(116, '6.200.013.00', 'Biaya Pemasaran', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(117, '6.200.014.00', 'Biaya Perawatan Gedung', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(118, '6.200.015.00', 'Biaya Freight Forwarding', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(119, '6.200.016.00', 'Biaya Akomodasi', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(120, '6.200.017.00', 'Biaya Umum dan Adm', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(121, '6.200.018.00', 'Biaya Perjalanan Dinas', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(122, '6.200.019.00', 'Biaya Perlengkapan Kantor', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(123, '6.200.020.00', 'Biaya Perijinan Perusahaan', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(124, '6.200.021.00', 'Biaya Sewa', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(125, '6.200.022.00', 'Biaya Makan & Minum', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(126, '6.200.023.00', 'Biaya Trucking', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(127, '6.200.024.00', 'Biaya Bahan Bakar', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(128, '6.200.025.00', 'Biaya Pajak', 27, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(129, '6.300.001.00', 'Biaya Penyusutan Peralatan Kantor', 28, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(130, '6.300.002.00', 'Biaya Penyusutan Kendaraan', 28, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(131, '6.300.005.00', 'Biaya Penyusutan Software', 28, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(132, '7.300.001.00', 'Biaya Admin Bank', 29, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(133, '7.300.002.00', 'Biaya Pajak Bunga Giro', 29, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(134, '7.300.003.00', 'Biaya Pembulatan', 29, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(135, '7.400.001.00', 'Pendapatan Bunga Giro', 30, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
(136, '7.400.003.00', 'Pendapatan Pembulatan', 30, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
(137, '8.100.001.00', 'Rugi Selisih Kurs', 31, 'Biaya', 'IDR', 0, 'active', 'Debit'),
(138, '8.100.002.00', 'Biaya Pajak Penghasilan', 31, 'Biaya', 'IDR', 0, 'active', 'Debit');

-- --------------------------------------------------------

--
-- Struktur dari tabel `common_code`
--

CREATE TABLE `common_code` (
  `id` int(11) NOT NULL,
  `group_code` varchar(50) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `common_code`
--

INSERT INTO `common_code` (`id`, `group_code`, `code`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SATUAN', 'PCS', 'Pieces', 'active', '2025-10-16 08:32:16', NULL),
(2, 'SATUAN', 'ROLL', 'Roll', 'active', '2025-10-16 08:32:16', NULL),
(5, 'CURRENCY', 'USD', 'Dollar Amerika', 'active', '2025-10-16 08:32:16', NULL),
(6, 'CURRENCY', 'IDR', 'Rupiah', 'active', '2025-10-16 08:32:16', NULL),
(8, 'BC_TYPE', 'BC 3.0', 'BC 3.0', 'active', '2025-10-16 09:16:09', '2025-10-24 13:19:11'),
(9, 'BC_TYPE', 'BC 4.0', 'BC 4.0', 'active', '2025-10-16 09:23:16', '2025-10-24 13:19:18'),
(10, 'BC_TYPE', 'BC 4.1', 'BC 4.1', 'active', '2025-10-16 10:57:31', '2025-10-24 13:19:21'),
(11, 'SEMIFINISHED_CATEGORY', 'INSOLE', 'Insole', 'active', '2025-10-16 14:23:03', NULL),
(12, 'SEMIFINISHED_CATEGORY', 'OUTSOLE', 'Outsole', 'active', '2025-10-16 14:23:03', NULL),
(13, 'SEMIFINISHED_CATEGORY', 'KAIN', 'Kain', 'active', '2025-10-16 14:23:03', NULL),
(14, 'SEMIFINISHED_CATEGORY', 'UPPER', 'Upper', 'active', '2025-10-16 14:23:03', NULL),
(15, 'SEMIFINISHED_CATEGORY', 'LINING', 'Lining', 'active', '2025-10-16 14:23:03', NULL),
(16, 'FINISHED_CATEGORY', 'SEPATU', 'SEPATU', 'active', '2025-10-16 14:42:26', NULL),
(17, 'MATERIAL_CATEGORY', 'UPPER', 'UPPER', 'active', '2025-10-17 08:28:12', NULL),
(18, 'ITEM_ORIGIN', 'LOKAL', 'LOKAL', 'active', '2025-10-17 08:29:30', NULL),
(19, 'ITEM_ORIGIN', 'IMPORT', 'IMPORT', 'active', '2025-10-17 08:29:38', NULL),
(20, 'SUPPLIER_TYPE', 'LOKAL', 'LOKAL', 'active', '2025-10-17 08:33:03', NULL),
(21, 'SUPPLIER_TYPE', 'IMPOR', 'IMPOR', 'active', '2025-10-17 08:33:11', NULL),
(22, 'CUSTOMER_TYPE', 'LOKAL', 'LOKAL', 'active', '2025-10-17 08:36:32', NULL),
(23, 'CUSTOMER_TYPE', 'IMPOR', 'IMPOR', 'active', '2025-10-17 08:36:44', NULL),
(24, 'MACHINE_CATEGORY', 'CUTE', 'MACHINE ASSEMBLYING', 'active', '2025-10-17 08:42:10', NULL),
(25, 'INVENTORY_CATEGORY', 'ATK', 'ALAT TULIS', 'active', '2025-10-17 08:44:17', NULL),
(26, 'INVENTORY_CONDITION', 'NEW', 'BARU', 'active', '2025-10-17 08:44:58', NULL),
(27, 'SHIPPING_MODE', 'TRUCKING', 'TRUCK', 'active', '2025-10-17 09:09:27', NULL),
(28, 'PAYMENT_MODE', 'CASH', 'CASH', 'active', '2025-10-17 09:09:39', NULL),
(29, 'PAYMENT_TYPE', 'NORMAL', 'Normal Payment', 'active', '2025-10-17 10:37:23', NULL),
(30, 'PAYMENT_TYPE', 'FOC', 'Free of Charge (FOC)', 'active', '2025-10-17 10:37:23', NULL),
(31, 'MATERIAL_CATEGORY', 'KAIN', 'KAIN', 'active', '2025-10-22 16:06:26', NULL),
(32, 'MATERIAL_CATEGORY', 'INSOLE', 'INSOLE', 'active', '2025-10-22 16:06:36', NULL),
(33, 'MATERIAL_CATEGORY', 'OUTSOLE', 'OUTSOLE', 'active', '2025-10-22 16:06:52', NULL),
(34, 'CURRENCY', 'RMB', 'RMB', 'active', '2025-10-23 14:49:25', NULL),
(35, 'MATERIAL_CATEGORY', 'Benang', 'Benang', 'active', '2025-10-30 11:23:47', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `kode_customer` varchar(50) NOT NULL,
  `nama_customer` varchar(100) NOT NULL,
  `jenis` enum('Lokal','Ekspor') NOT NULL,
  `npwp` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `customers`
--

INSERT INTO `customers` (`id`, `kode_customer`, `nama_customer`, `jenis`, `npwp`, `alamat`, `telepon`, `email`, `status`, `created_at`, `updated_at`) VALUES
(2, 'LOK-0001', 'PT FOREVER11', 'Lokal', '1222', 'Jl. PODOREJO KALIANCAR KECAMATAN NGALIYAN KOTA SEMARANG', '085709329384', 'gadinggemintang3@gmail.com', 'active', '2025-10-17 08:36:52', '2025-11-05 11:29:14'),
(3, 'LOK-0002', 'PT SIGNATURE JAYA', 'Lokal', '122233', 'Jl. Kw. Industri Candi Tahap V No.8 blok 21A, Ngaliyan, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50181\r\nIT STAFF', '085709329384', 'it@foreveroneinternational.com', 'active', '2025-10-24 16:24:01', '2025-10-24 16:24:01'),
(4, 'LOK-0003', 'PT DUO JAYA ABADI', 'Lokal', '122233', 'Jl. PODOREJO KALIANCAR KECAMATAN NGALIYAN KOTA SEMARANG', '085709329384', 'gadinggemintang3@gmail.com', 'active', '2025-11-04 17:28:06', '2025-11-04 17:28:06'),
(5, 'LOK-0004', 'PT MITRA BEKASI', 'Lokal', '1222211', 'Jl. Kw. Industri Candi Tahap V No.8 blok 21A, Ngaliyan, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50181\r\nIT STAFF', '085709329384', 'it@foreveroneinternational.com', 'active', '2025-11-04 17:47:31', '2025-11-04 17:47:31');

-- --------------------------------------------------------

--
-- Struktur dari tabel `fg_delivery_details`
--

CREATE TABLE `fg_delivery_details` (
  `id` int(11) NOT NULL,
  `fg_delivery_header_id` int(11) NOT NULL,
  `so_detail_id` int(11) DEFAULT NULL COMMENT 'FK ke sales_order_details (opsional)',
  `sales_contract_id` int(11) DEFAULT NULL,
  `fg_id` int(11) NOT NULL COMMENT 'FK ke finished_goods',
  `qty_shipped` decimal(15,4) NOT NULL,
  `uom` varchar(10) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `fg_delivery_details`
--

INSERT INTO `fg_delivery_details` (`id`, `fg_delivery_header_id`, `so_detail_id`, `sales_contract_id`, `fg_id`, `qty_shipped`, `uom`, `notes`) VALUES
(15, 19, NULL, 7, 2, 10.0000, NULL, '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `fg_delivery_header`
--

CREATE TABLE `fg_delivery_header` (
  `id` int(11) NOT NULL,
  `delivery_number` varchar(50) NOT NULL COMMENT 'Nomor Surat Jalan/DO',
  `delivery_date` date NOT NULL,
  `sales_order_id` int(11) DEFAULT NULL COMMENT 'FK ke sales_orders',
  `sales_contract_id` int(11) DEFAULT NULL COMMENT 'FK ke sales_contracts (jika SO null)',
  `customer_id` int(11) DEFAULT NULL COMMENT 'FK ke master_pelanggan',
  `bc_type_id` varchar(10) DEFAULT NULL COMMENT 'Kode Jenis Dokumen BC (e.g., 30)',
  `bc_number` varchar(50) DEFAULT NULL COMMENT 'Nomor Aju/Pendaftaran Dokumen BC',
  `bc_date` date DEFAULT NULL COMMENT 'Tanggal Dokumen BC',
  `shipping_method` varchar(50) DEFAULT NULL,
  `shipping_address` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Shipped, Cancelled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `shipped_at` datetime DEFAULT NULL COMMENT 'Waktu konfirmasi pengiriman',
  `shipped_by_user` int(11) DEFAULT NULL COMMENT 'User ID yg konfirmasi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `fg_delivery_header`
--

INSERT INTO `fg_delivery_header` (`id`, `delivery_number`, `delivery_date`, `sales_order_id`, `sales_contract_id`, `customer_id`, `bc_type_id`, `bc_number`, `bc_date`, `shipping_method`, `shipping_address`, `remarks`, `status`, `created_at`, `updated_at`, `shipped_at`, `shipped_by_user`) VALUES
(19, 'DO/202511/0001', '2025-11-11', NULL, NULL, 3, '9', '099812', '2025-11-12', 'Truck', 'Jl. PODOREJO KALIANCAR KECAMATAN NGALIYAN KOTA SEMARANG', NULL, 'Confirmed', '2025-11-11 08:41:24', '2025-11-11 08:59:48', '2025-11-11 15:59:48', 4),
(20, 'DO/202511/0002', '2025-11-11', NULL, NULL, 3, '9', '09981212', '2025-11-12', 'Truck', 'Jl. Kw. Industri Candi Tahap V No.8 blok 21A, Ngaliyan, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50181IT STAFF', NULL, 'Draft', '2025-11-11 08:59:23', '2025-11-11 08:59:23', NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `fg_receipt_details`
--

CREATE TABLE `fg_receipt_details` (
  `id` int(11) NOT NULL,
  `fg_receipt_header_id` int(11) NOT NULL,
  `prod_output_id` int(11) DEFAULT NULL COMMENT 'FK ke production_outputs jika sumbernya dari sana',
  `fg_id` int(11) NOT NULL COMMENT 'FK ke finished_goods',
  `qty_received` decimal(15,4) NOT NULL,
  `size_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`size_breakdown`)),
  `uom` varchar(10) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `fg_receipt_details`
--

INSERT INTO `fg_receipt_details` (`id`, `fg_receipt_header_id`, `prod_output_id`, `fg_id`, `qty_received`, `size_breakdown`, `uom`, `notes`) VALUES
(9, 8, 53, 2, 10.0000, '[{\"size\":\"40\",\"qty\":10}]', 'PCS', '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `fg_receipt_header`
--

CREATE TABLE `fg_receipt_header` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `receipt_date` date NOT NULL,
  `production_header_id` int(11) DEFAULT NULL,
  `wo_header_id` int(11) DEFAULT NULL,
  `sales_contract_id` int(11) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL COMMENT 'User ID Petugas Gudang',
  `remarks` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `fg_receipt_header`
--

INSERT INTO `fg_receipt_header` (`id`, `receipt_number`, `receipt_date`, `production_header_id`, `wo_header_id`, `sales_contract_id`, `received_by`, `remarks`, `status`, `created_at`, `updated_at`, `confirmed_at`, `confirmed_by`) VALUES
(8, 'GRN-FG/202511/0001', '2025-11-11', 10, 11, 7, 4, '', 'Confirmed', '2025-11-11 08:38:24', '2025-11-11 08:38:28', '2025-11-11 15:38:28', 4);

-- --------------------------------------------------------

--
-- Struktur dari tabel `finished_goods`
--

CREATE TABLE `finished_goods` (
  `id` int(11) NOT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `asal` varchar(50) DEFAULT NULL,
  `satuan` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `finished_goods`
--

INSERT INTO `finished_goods` (`id`, `kode_barang`, `nama_barang`, `kategori`, `asal`, `satuan`, `status`, `created_at`, `updated_at`) VALUES
(2, 'FG-SEP-LOK-0001', 'PMJ6000', 'SEPATU', 'LOKAL', 'PCS', 'active', '2025-10-17 08:40:04', '2025-10-17 08:40:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `grn_details`
--

CREATE TABLE `grn_details` (
  `id` int(11) NOT NULL,
  `grn_header_id` int(11) NOT NULL,
  `source_doc_header_id` int(11) NOT NULL COMMENT 'ID dari po_header or mir_header',
  `source_doc_detail_id` int(11) NOT NULL COMMENT 'ID dari po_details or mir_details',
  `source_doc_type` enum('PO','MIR') NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `receive_qty` decimal(12,2) NOT NULL,
  `unit_value` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Nilai satuan barang dlm IDR',
  `total_value` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'Nilai total barang dlm IDR'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `grn_details`
--

INSERT INTO `grn_details` (`id`, `grn_header_id`, `source_doc_header_id`, `source_doc_detail_id`, `source_doc_type`, `item_id`, `item_type`, `receive_qty`, `unit_value`, `total_value`) VALUES
(17, 19, 18, 22, 'PO', 9, 'material', 120.00, 100000.00, 12000000.00),
(18, 19, 18, 23, 'PO', 11, 'material', 120.00, 1000.00, 120000.00),
(19, 19, 18, 24, 'PO', 10, 'material', 1200.00, 12000.00, 14400000.00),
(20, 20, 7, 6, 'MIR', 9, 'material', 120.00, 2000.00, 240000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `grn_header`
--

CREATE TABLE `grn_header` (
  `id` int(11) NOT NULL,
  `grn_number` varchar(50) NOT NULL,
  `grn_date` date NOT NULL,
  `base_on` enum('PO','MIR') NOT NULL,
  `warehouse_type` enum('material','machine','inventory','semifinished') NOT NULL,
  `supplier_id` int(11) DEFAULT NULL COMMENT 'Diambil otomatis dari source doc',
  `delivery_note_number` varchar(100) DEFAULT NULL,
  `bc_type_code` varchar(50) DEFAULT NULL,
  `bc_date` date DEFAULT NULL,
  `bc_number` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('Draft','Completed') DEFAULT 'Draft',
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `grn_header`
--

INSERT INTO `grn_header` (`id`, `grn_number`, `grn_date`, `base_on`, `warehouse_type`, `supplier_id`, `delivery_note_number`, `bc_type_code`, `bc_date`, `bc_number`, `remarks`, `status`, `confirmed_by`, `confirmed_at`, `created_by`, `created_at`, `updated_at`) VALUES
(19, 'GRN/202511/0001', '2025-11-07', 'PO', 'material', 4, '1622/65757', 'BC 3.0', '2025-11-07', '099812', '', 'Completed', 4, '2025-11-07 09:38:27', 4, '2025-11-07 09:37:49', '2025-11-07 09:38:27'),
(20, 'GRN/202511/0002', '2025-11-07', 'MIR', 'material', 4, '1622/65757', 'BC 3.0', '2025-11-07', '099812', '', 'Completed', 4, '2025-11-07 09:58:11', 4, '2025-11-07 09:57:53', '2025-11-07 09:58:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `inventories`
--

CREATE TABLE `inventories` (
  `id` int(11) NOT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `satuan` varchar(20) DEFAULT NULL COMMENT 'Kode satuan dari common_code',
  `merk` varchar(100) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `kondisi` varchar(50) DEFAULT 'Baik',
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `inventories`
--

INSERT INTO `inventories` (`id`, `kode_barang`, `nama_barang`, `kategori`, `satuan`, `merk`, `lokasi`, `kondisi`, `status`, `created_at`, `updated_at`) VALUES
(3, 'INV-ATK-0001', 'BULPEN', 'ATK', 'PCS', '', 'Office', 'NEW', 'Active', '2025-11-06 11:24:44', '2025-11-06 11:24:44');

-- --------------------------------------------------------

--
-- Struktur dari tabel `issuance_details`
--

CREATE TABLE `issuance_details` (
  `id` int(11) NOT NULL,
  `mr_header_id` int(11) NOT NULL,
  `mr_detail_id` int(11) NOT NULL,
  `stock_on_hand_id` int(11) NOT NULL COMMENT 'Batch yg diambil',
  `issued_qty` decimal(12,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `journal_details`
--

CREATE TABLE `journal_details` (
  `id` int(11) NOT NULL,
  `journal_header_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(20,2) NOT NULL DEFAULT 0.00,
  `kredit` decimal(20,2) NOT NULL DEFAULT 0.00,
  `debit_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `kredit_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `journal_details`
--

INSERT INTO `journal_details` (`id`, `journal_header_id`, `account_id`, `debit`, `kredit`, `debit_local`, `kredit_local`, `description`) VALUES
(27, 14, 93, 12000000.00, 0.00, 12000000.00, 0.00, ''),
(28, 14, 34, 0.00, 12000000.00, 0.00, 12000000.00, ''),
(29, 15, 33, 100000000000.00, 0.00, 100000000000.00, 0.00, ''),
(30, 15, 84, 0.00, 100000000000.00, 0.00, 100000000000.00, '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `journal_header`
--

CREATE TABLE `journal_header` (
  `id` int(11) NOT NULL,
  `journal_no` varchar(50) NOT NULL,
  `journal_date` date NOT NULL,
  `description` text NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'IDR',
  `exchange_rate` decimal(15,4) NOT NULL DEFAULT 1.0000,
  `source_module` varchar(50) NOT NULL DEFAULT 'Manual',
  `source_doc_id` int(11) DEFAULT NULL COMMENT 'ID dari PO/GRN/DO/ADJ',
  `total_debit` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_kredit` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_debit_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `total_kredit_local` decimal(20,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Posted, Cancelled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `posted_by` int(11) DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `journal_header`
--

INSERT INTO `journal_header` (`id`, `journal_no`, `journal_date`, `description`, `currency`, `exchange_rate`, `source_module`, `source_doc_id`, `total_debit`, `total_kredit`, `total_debit_local`, `total_kredit_local`, `status`, `created_by`, `created_at`, `posted_by`, `posted_at`) VALUES
(14, 'JU/202511/00001', '2025-11-10', 'Listrik', 'IDR', 1.0000, 'Manual', NULL, 12000000.00, 12000000.00, 12000000.00, 12000000.00, 'Posted', 4, '2025-11-10 08:49:32', 4, '2025-11-10 08:49:36'),
(15, 'JU/202511/00002', '2025-11-10', 'Ekspor', 'IDR', 1.0000, 'Manual', NULL, 100000000000.00, 100000000000.00, 100000000000.00, 100000000000.00, 'Posted', 4, '2025-11-10 08:54:47', 4, '2025-11-10 08:54:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `machines`
--

CREATE TABLE `machines` (
  `id` int(11) NOT NULL,
  `kode_mesin` varchar(50) NOT NULL,
  `nama_mesin` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `merk` varchar(100) DEFAULT NULL,
  `tahun` varchar(10) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `satuan` varchar(20) DEFAULT 'UNIT',
  `status` varchar(20) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `master_scrap`
--

CREATE TABLE `master_scrap` (
  `id` int(11) NOT NULL,
  `scrap_code` varchar(50) NOT NULL,
  `scrap_name` varchar(150) NOT NULL,
  `unit` varchar(20) NOT NULL COMMENT 'Satuan (e.g., KG, PCS, LBR)',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `master_scrap`
--

INSERT INTO `master_scrap` (`id`, `scrap_code`, `scrap_name`, `unit`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SCRP-0001', 'SCRAP SISA PRODUKSI', 'ROLL', 'active', '2025-10-17 18:22:46', '2025-10-17 18:22:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `kode_material` varchar(20) NOT NULL,
  `nama_material` varchar(100) NOT NULL,
  `kategori` enum('Kain','Outsole','Insole','Benang','Lem','Lainnya') DEFAULT 'Lainnya',
  `asal` enum('Lokal','Impor') DEFAULT 'Lokal',
  `satuan` varchar(20) DEFAULT 'PCS',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `materials`
--

INSERT INTO `materials` (`id`, `kode_material`, `nama_material`, `kategori`, `asal`, `satuan`, `status`, `created_at`) VALUES
(9, 'KAL-0001', 'Kain Woven', 'Kain', 'Lokal', 'PCS', 'active', '2025-10-24 16:18:26'),
(10, 'KAL-0002', 'Eva 1.2mm', 'Kain', 'Lokal', 'ROLL', 'active', '2025-10-30 11:23:15'),
(11, 'BEL-0001', 'Benang Jait', 'Benang', 'Lokal', 'PCS', 'active', '2025-10-30 11:24:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `menus`
--

CREATE TABLE `menus` (
  `id` int(11) NOT NULL,
  `menu_name` varchar(100) NOT NULL,
  `menu_key` varchar(100) NOT NULL,
  `order_no` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `menus`
--

INSERT INTO `menus` (`id`, `menu_name`, `menu_key`, `order_no`) VALUES
(1, 'Common Code', 'commoncode', 1),
(2, 'Role Access', 'roleaccess', 2),
(3, 'User Management', 'user', 3),
(4, 'Master Material', 'master_material', 4),
(5, 'Master Material', 'master_material', 4),
(6, 'Semifinished', 'menu_master_semifinished', 4),
(7, 'Master Semifinished', 'master_semifinished', 5),
(8, 'Master Finished Goods', 'master_fg', 8),
(9, 'Master Supplier', 'master_supplier', 9),
(10, 'Master Customer', 'master_customer', 10),
(11, 'Master Mesin', 'master_machine', 11),
(12, 'Master Inventaris', 'master_inventory', 12),
(13, 'Penerimaan Barang', 'warehouse_in', 13),
(14, 'Purchase Order', 'purchase_order', 12),
(15, 'Sales_contract', 'sales_contract', 14),
(16, 'Impor Request', 'mir', 15),
(17, 'Penerimaan Barang', 'warehouse_in', 16),
(18, 'Stock', 'stock_ledger', 17),
(19, 'BOM', 'bom', 18),
(20, 'SPK', 'work_order', 19),
(21, 'Material Req', 'material_request', 19),
(22, 'Pengeluaran material', 'Material_issuance', 20),
(23, 'Production Receive', 'production_receive', 20),
(24, 'Proses Produksi', 'production_process', 21),
(25, 'Master Scrap', 'master_scrap', 22),
(26, 'FG In', 'fg_receipt', 23),
(27, 'FG Stock', 'fg_stock', 24),
(28, 'FG Out', 'fg_delivery', 25),
(29, 'Material Return Prod', 'prodreturn', 26),
(30, 'Laporan Pemasukan Barang', 'lap_pemasukan_bb', 27),
(31, 'Laporan Mutasi Scrap', 'lap_mutasi_scrap', 28),
(32, 'Log Activity', 'log_activity', 29),
(33, 'Scrap In', 'scrap_in', 30),
(34, 'Scrap Stock', 'scrap_stock', 31),
(35, 'Scrap Out', 'scrap_out', 32),
(36, 'Laporan Mutasi Bahan Baku', 'lapmutasimaterial', 33),
(37, 'Laporan Mutasi Barang Jadi', 'lapmutasifinished', 34),
(38, 'Adjustment', 'adjustment', 35),
(39, 'Stock Opname', 'stockopname', 36),
(40, 'Laporan WIP', 'lapmutasiwip', 37),
(41, 'Subkon order', 'subkon_order', 38),
(42, 'Subkon Out', 'subkon_out', 39),
(43, 'Subkon in', 'subkon_in', 40),
(44, 'Lap Pengeluaran Barang', 'lap_pengeluaran_barang', 41),
(45, 'COA', 'coa', 42),
(46, 'Jurnal Umum', 'jurnalumum', 43),
(47, 'AP', 'apinvoice', 44),
(48, 'Setting Coa', 'settingakun', 45),
(49, 'Bayar Hutang', 'appayment', 46),
(50, 'Invoice Piutang', 'arinvoice', 47),
(51, 'Pembayaran Piutang', 'arreceipt', 48),
(52, 'Cash & Bank', 'cashbank', 49),
(53, 'Neraca', 'neraca', 50),
(54, 'Laba Rugi', 'laba_rugi', 51),
(55, 'Neraca Saldo', 'trial_balance', 52),
(56, 'Arus Kas', 'cash_flow', 53);

-- --------------------------------------------------------

--
-- Struktur dari tabel `mir_details`
--

CREATE TABLE `mir_details` (
  `id` int(11) NOT NULL,
  `mir_header_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL COMMENT '''material'', ''machine'', ''inventory'', ''semifinished''',
  `sales_contract_id` int(11) DEFAULT NULL,
  `qty` decimal(12,2) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mir_details`
--

INSERT INTO `mir_details` (`id`, `mir_header_id`, `item_id`, `item_type`, `sales_contract_id`, `qty`, `remarks`) VALUES
(6, 7, 9, 'material', 7, 120000.00, '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mir_header`
--

CREATE TABLE `mir_header` (
  `id` int(11) NOT NULL,
  `mir_number` varchar(50) NOT NULL,
  `request_date` date NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `payment_type_code` varchar(50) NOT NULL COMMENT 'e.g., NORMAL, FOC',
  `currency_code` varchar(10) DEFAULT 'IDR',
  `requester_id` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('Draft','Submitted','Approved','Closed') DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mir_header`
--

INSERT INTO `mir_header` (`id`, `mir_number`, `request_date`, `supplier_id`, `payment_type_code`, `currency_code`, `requester_id`, `remarks`, `status`, `approved_by`, `approved_at`, `created_at`, `updated_at`) VALUES
(7, 'MIR/202511/0001', '2025-11-07', 4, 'FOC', 'USD', 4, '', 'Approved', 4, '2025-11-07 09:57:41', '2025-11-07 09:57:21', '2025-11-07 09:57:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `modules`
--

CREATE TABLE `modules` (
  `id` int(11) NOT NULL,
  `module_key` varchar(100) NOT NULL,
  `module_name` varchar(150) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `modules`
--

INSERT INTO `modules` (`id`, `module_key`, `module_name`, `category`, `status`) VALUES
(1, 'dashboard', 'Dashboard', 'General', 'active'),
(2, 'commoncode', 'Common Code', 'System Setup', 'active'),
(3, 'roleaccess', 'Role Access', 'System Setup', 'active'),
(4, 'user', 'User Management', 'System Setup', 'active'),
(5, 'master_material', 'Master Material', NULL, 'active');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mr_details`
--

CREATE TABLE `mr_details` (
  `id` int(11) NOT NULL,
  `mr_header_id` int(11) NOT NULL,
  `wo_header_id` int(11) NOT NULL COMMENT 'Link ke Work Order',
  `component_item_id` int(11) NOT NULL,
  `component_item_type` varchar(50) NOT NULL COMMENT '''material'', ''semifinished''',
  `unit_measure` varchar(20) DEFAULT NULL,
  `requested_qty` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Qty diminta PPIC',
  `issued_qty` decimal(12,4) NOT NULL DEFAULT 0.0000 COMMENT 'Qty dikeluarkan Gudang',
  `qty_returned` decimal(15,4) DEFAULT 0.0000,
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mr_details`
--

INSERT INTO `mr_details` (`id`, `mr_header_id`, `wo_header_id`, `component_item_id`, `component_item_type`, `unit_measure`, `requested_qty`, `issued_qty`, `qty_returned`, `notes`) VALUES
(21, 14, 11, 9, 'material', 'PCS', 120.0000, 120.0000, 0.0000, '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mr_header`
--

CREATE TABLE `mr_header` (
  `id` int(11) NOT NULL,
  `mr_number` varchar(50) NOT NULL,
  `issuance_number` varchar(50) DEFAULT NULL,
  `request_date` date NOT NULL,
  `status` enum('Draft','Submitted','Issued','Received') DEFAULT 'Draft',
  `remarks` text DEFAULT NULL,
  `requester_id` int(11) DEFAULT NULL COMMENT 'Dibuat Oleh (PPIC)',
  `requested_at` datetime DEFAULT NULL,
  `issuer_id` int(11) DEFAULT NULL COMMENT 'Disiapkan Oleh (Gudang)',
  `issued_at` datetime DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL COMMENT 'Diterima Oleh (Produksi)',
  `received_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mr_header`
--

INSERT INTO `mr_header` (`id`, `mr_number`, `issuance_number`, `request_date`, `status`, `remarks`, `requester_id`, `requested_at`, `issuer_id`, `issued_at`, `receiver_id`, `received_at`, `created_at`, `updated_at`) VALUES
(14, 'MR/202511/0001', 'MI/202511/0001', '2025-11-07', 'Received', '', NULL, '2025-11-07 09:47:59', 4, '2025-11-07 09:58:50', 4, '2025-11-07 09:59:51', '2025-11-07 09:47:45', '2025-11-07 09:59:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `opname_details`
--

CREATE TABLE `opname_details` (
  `id` int(11) NOT NULL,
  `opname_header_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `qty_sistem` decimal(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'Snapshot stok dari stock_ledger',
  `qty_fisik` decimal(15,4) DEFAULT NULL COMMENT 'Input hasil hitungan fisik',
  `selisih` decimal(15,4) NOT NULL DEFAULT 0.0000 COMMENT 'qty_fisik - qty_sistem',
  `notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `opname_header`
--

CREATE TABLE `opname_header` (
  `id` int(11) NOT NULL,
  `opname_no` varchar(50) NOT NULL,
  `opname_date` date NOT NULL,
  `item_type` varchar(50) NOT NULL COMMENT 'material, semifinished, finished_goods, dll.',
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Counting, Completed, Cancelled',
  `adjustment_header_id` int(11) DEFAULT NULL COMMENT 'ID Dokumen Adjustment hasil opname',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `po_details`
--

CREATE TABLE `po_details` (
  `id` int(11) NOT NULL,
  `po_header_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `sales_contract_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `qty` decimal(12,2) NOT NULL,
  `base_price` decimal(15,2) DEFAULT 0.00 COMMENT 'Harga satuan dalam currency PO',
  `local_price` decimal(15,2) DEFAULT 0.00 COMMENT 'Harga satuan dalam IDR (base_price * exchange_rate)',
  `total_local` decimal(15,2) DEFAULT 0.00 COMMENT 'Total harga dalam IDR (qty * local_price)',
  `total` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `po_details`
--

INSERT INTO `po_details` (`id`, `po_header_id`, `item_id`, `item_type`, `sales_contract_id`, `description`, `qty`, `base_price`, `local_price`, `total_local`, `total`) VALUES
(22, 18, 9, 'material', 7, '', 120.00, 100000.00, 100000.00, 12000000.00, 0.00),
(23, 18, 11, 'material', 7, '', 120.00, 1000.00, 1000.00, 120000.00, 0.00),
(24, 18, 10, 'material', NULL, '', 1200.00, 12000.00, 12000.00, 14400000.00, 0.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `po_header`
--

CREATE TABLE `po_header` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `po_date` date NOT NULL,
  `shipping_mode_code` varchar(50) DEFAULT NULL,
  `payment_mode_code` varchar(50) DEFAULT NULL,
  `currency_code` varchar(10) DEFAULT 'IDR',
  `exchange_rate` decimal(15,4) DEFAULT 1.0000,
  `has_ppn` tinyint(1) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `status` enum('Draft','Submitted','Closed','Confirmed') DEFAULT 'Draft',
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `po_header`
--

INSERT INTO `po_header` (`id`, `po_number`, `supplier_id`, `po_date`, `shipping_mode_code`, `payment_mode_code`, `currency_code`, `exchange_rate`, `has_ppn`, `remarks`, `status`, `confirmed_by`, `confirmed_at`, `created_by`, `created_at`, `updated_at`) VALUES
(18, 'PO/202511/0001', 4, '2025-11-07', 'TRUCKING', 'CASH', 'IDR', 1.0000, 0, '', 'Confirmed', 4, '2025-11-07 09:37:36', 4, '2025-11-07 09:36:31', '2025-11-07 09:37:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `production_header`
--

CREATE TABLE `production_header` (
  `id` int(11) NOT NULL,
  `production_number` varchar(50) NOT NULL,
  `wo_header_id` int(11) NOT NULL,
  `fg_id` int(11) NOT NULL,
  `planned_qty` int(11) NOT NULL,
  `production_date` date NOT NULL,
  `status` enum('Draft','In Progress','QC Check','Completed','Cancelled') DEFAULT 'Draft',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `production_header`
--

INSERT INTO `production_header` (`id`, `production_number`, `wo_header_id`, `fg_id`, `planned_qty`, `production_date`, `status`, `remarks`, `created_by`, `confirmed_by`, `confirmed_at`, `created_at`, `updated_at`) VALUES
(10, 'PROD/202511/0001', 11, 2, 120, '2025-11-07', 'Completed', '', 4, 4, '2025-11-11 15:37:44', '2025-11-07 09:40:22', '2025-11-11 15:38:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `production_inputs`
--

CREATE TABLE `production_inputs` (
  `id` int(11) NOT NULL,
  `production_header_id` int(11) NOT NULL,
  `stage_name` varchar(50) NOT NULL COMMENT 'e.g., cutting, sewing',
  `item_id` int(11) DEFAULT NULL,
  `item_type` varchar(50) DEFAULT NULL,
  `mr_detail_id` int(11) DEFAULT NULL,
  `stock_on_hand_id` int(11) DEFAULT NULL,
  `source_output_id` int(11) DEFAULT NULL COMMENT 'ID output dr stage sblmnya (misal cutting)',
  `qty_used` decimal(12,4) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `production_inputs`
--

INSERT INTO `production_inputs` (`id`, `production_header_id`, `stage_name`, `item_id`, `item_type`, `mr_detail_id`, `stock_on_hand_id`, `source_output_id`, `qty_used`, `notes`, `created_at`) VALUES
(43, 10, 'cutting', 9, 'material', NULL, 51, NULL, 15.0000, '', '2025-11-07 09:59:58'),
(44, 10, 'sewing', 1, 'semifinished', NULL, NULL, 50, 11.0000, '', '2025-11-07 14:03:35');

-- --------------------------------------------------------

--
-- Struktur dari tabel `production_outputs`
--

CREATE TABLE `production_outputs` (
  `id` int(11) NOT NULL,
  `production_header_id` int(11) NOT NULL,
  `stage_name` varchar(50) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL COMMENT 'semifinished, finished, scrap',
  `qty_produced` decimal(12,4) NOT NULL,
  `qty_scrap` decimal(12,4) NOT NULL DEFAULT 0.0000,
  `size_breakdown` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`size_breakdown`)),
  `received_by` int(11) DEFAULT NULL COMMENT 'Diterima Oleh (Operator)',
  `received_at` datetime DEFAULT NULL,
  `qc_checked_by` int(11) DEFAULT NULL COMMENT 'Diperiksa Oleh (QC)',
  `qc_checked_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `production_outputs`
--

INSERT INTO `production_outputs` (`id`, `production_header_id`, `stage_name`, `item_id`, `item_type`, `qty_produced`, `qty_scrap`, `size_breakdown`, `received_by`, `received_at`, `qc_checked_by`, `qc_checked_at`, `created_at`) VALUES
(50, 10, 'cutting', 1, 'semifinished', 12.0000, 0.0000, NULL, 4, '2025-11-07 10:00:20', 4, '2025-11-07 10:00:31', '2025-11-07 10:00:20'),
(51, 10, 'assembling', 4, 'semifinished', 12.0000, 0.0000, NULL, 4, '2025-11-07 13:30:04', 4, '2025-11-07 13:30:07', '2025-11-07 13:30:04'),
(52, 10, 'sewing', 1, 'semifinished', 112.0000, 0.0000, NULL, 4, '2025-11-07 14:03:45', 4, '2025-11-07 14:03:48', '2025-11-07 14:03:45'),
(53, 10, 'finishing', 2, 'finished_goods', 10.0000, 0.0000, '[{\"size\":\"40\",\"qty\":10}]', 4, NULL, 4, '2025-11-11 15:37:41', '2025-11-11 15:37:38');

-- --------------------------------------------------------

--
-- Struktur dari tabel `production_output_sizes`
--

CREATE TABLE `production_output_sizes` (
  `id` int(11) NOT NULL,
  `output_id` int(11) NOT NULL,
  `size_label` varchar(20) DEFAULT NULL,
  `qty` decimal(15,4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `production_output_sizes`
--

INSERT INTO `production_output_sizes` (`id`, `output_id`, `size_label`, `qty`) VALUES
(17, 53, '40', 10.0000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `prod_return_detail`
--

CREATE TABLE `prod_return_detail` (
  `id` int(11) NOT NULL,
  `prod_return_id` int(11) NOT NULL,
  `mr_detail_id` int(11) DEFAULT NULL,
  `material_id` int(11) NOT NULL,
  `item_type` enum('material','semifinished') NOT NULL DEFAULT 'material',
  `qty_return` decimal(10,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `prod_return_header`
--

CREATE TABLE `prod_return_header` (
  `id` int(11) NOT NULL,
  `prod_return_no` varchar(20) NOT NULL,
  `return_date` date NOT NULL,
  `work_order_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `status` varchar(15) NOT NULL DEFAULT 'Draft',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `status`) VALUES
(1, 'Admin', 'administrator', 'active'),
(3, 'PPIC', 'PPIC', 'active'),
(4, 'Staf Gudang', 'Full access to all modules', 'active');

-- --------------------------------------------------------

--
-- Struktur dari tabel `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 0,
  `can_add` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `menu_id`, `can_view`, `can_add`, `can_edit`, `can_delete`) VALUES
(26, 4, 1, 1, 0, 0, 1),
(27, 4, 2, 1, 0, 0, 1),
(28, 4, 3, 1, 0, 0, 1),
(29, 4, 4, 1, 0, 0, 1),
(30, 4, 5, 1, 0, 0, 1),
(31, 4, 6, 1, 0, 0, 1),
(32, 4, 7, 1, 0, 0, 1),
(269, 1, 1, 1, 1, 1, 1),
(270, 1, 2, 1, 1, 1, 1),
(271, 1, 3, 1, 1, 1, 1),
(272, 1, 4, 1, 1, 1, 1),
(273, 1, 5, 1, 1, 1, 1),
(274, 1, 6, 1, 1, 1, 1),
(275, 1, 7, 1, 1, 1, 1),
(276, 1, 8, 1, 1, 1, 1),
(277, 1, 9, 1, 1, 1, 1),
(278, 1, 10, 1, 1, 1, 1),
(279, 1, 11, 1, 1, 1, 1),
(280, 1, 12, 1, 1, 1, 1),
(281, 1, 14, 1, 1, 1, 1),
(282, 1, 13, 1, 1, 1, 1),
(283, 1, 15, 1, 1, 1, 1),
(284, 1, 16, 1, 1, 1, 1),
(285, 1, 17, 1, 1, 1, 1),
(286, 1, 18, 1, 1, 1, 1),
(287, 1, 19, 1, 1, 1, 1),
(1622, 3, 1, 1, 1, 1, 1),
(1623, 3, 2, 1, 1, 1, 1),
(1624, 3, 3, 1, 1, 1, 1),
(1625, 3, 4, 1, 1, 1, 1),
(1626, 3, 5, 1, 1, 1, 1),
(1627, 3, 6, 1, 1, 1, 1),
(1628, 3, 7, 1, 1, 1, 1),
(1629, 3, 8, 1, 1, 1, 1),
(1630, 3, 9, 1, 1, 1, 1),
(1631, 3, 10, 1, 1, 1, 1),
(1632, 3, 11, 1, 1, 1, 1),
(1633, 3, 12, 1, 1, 1, 1),
(1634, 3, 14, 1, 1, 1, 1),
(1635, 3, 13, 1, 1, 1, 1),
(1636, 3, 15, 1, 1, 1, 1),
(1637, 3, 16, 1, 1, 1, 1),
(1638, 3, 17, 1, 1, 1, 1),
(1639, 3, 18, 1, 1, 1, 1),
(1640, 3, 19, 1, 1, 1, 1),
(1641, 3, 20, 1, 1, 1, 1),
(1642, 3, 21, 1, 1, 1, 1),
(1643, 3, 23, 1, 1, 1, 1),
(1644, 3, 22, 1, 1, 1, 1),
(1645, 3, 24, 1, 1, 1, 1),
(1646, 3, 25, 1, 1, 1, 1),
(1647, 3, 26, 1, 1, 1, 1),
(1648, 3, 27, 1, 1, 1, 1),
(1649, 3, 28, 1, 1, 1, 1),
(1650, 3, 29, 1, 1, 1, 1),
(1651, 3, 30, 1, 1, 1, 1),
(1652, 3, 31, 1, 1, 1, 1),
(1653, 3, 32, 1, 1, 1, 1),
(1654, 3, 33, 1, 1, 1, 1),
(1655, 3, 34, 1, 1, 1, 1),
(1656, 3, 35, 1, 1, 1, 1),
(1657, 3, 36, 1, 1, 1, 1),
(1658, 3, 37, 1, 1, 1, 1),
(1659, 3, 38, 1, 1, 1, 1),
(1660, 3, 39, 1, 1, 1, 1),
(1661, 3, 40, 1, 1, 1, 1),
(1662, 3, 41, 1, 1, 1, 1),
(1663, 3, 42, 1, 1, 1, 1),
(1664, 3, 43, 1, 1, 1, 1),
(1665, 3, 44, 1, 1, 1, 1),
(1666, 3, 45, 1, 1, 1, 1),
(1667, 3, 46, 1, 1, 1, 1),
(1668, 3, 47, 1, 1, 1, 1),
(1669, 3, 48, 1, 1, 1, 1),
(1670, 3, 49, 1, 1, 1, 1),
(1671, 3, 50, 1, 1, 1, 1),
(1672, 3, 51, 1, 1, 1, 1),
(1673, 3, 52, 1, 1, 1, 1),
(1674, 3, 53, 1, 1, 1, 1),
(1675, 3, 54, 1, 1, 1, 1),
(1676, 3, 55, 1, 0, 0, 0),
(1677, 3, 56, 1, 0, 0, 0);

-- --------------------------------------------------------

--
-- Struktur dari tabel `sales_contracts`
--

CREATE TABLE `sales_contracts` (
  `id` int(11) NOT NULL,
  `sc_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `fg_id` int(11) NOT NULL COMMENT 'ID dari tabel finished_goods (Style)',
  `contract_date` date NOT NULL,
  `lchd_date` date DEFAULT NULL COMMENT 'Last Cargo Handover Date',
  `po_qty` int(11) NOT NULL DEFAULT 0,
  `currency_code` varchar(10) NOT NULL DEFAULT 'IDR',
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_price` decimal(20,2) NOT NULL DEFAULT 0.00,
  `destination` varchar(255) DEFAULT NULL,
  `status` enum('Open','Closed','Cancelled') DEFAULT 'Open',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sales_contracts`
--

INSERT INTO `sales_contracts` (`id`, `sc_number`, `customer_id`, `fg_id`, `contract_date`, `lchd_date`, `po_qty`, `currency_code`, `unit_price`, `total_price`, `destination`, `status`, `created_at`, `updated_at`) VALUES
(7, 'SC/2025/00001', 4, 2, '2025-11-06', '2025-11-06', 120, 'USD', 12.00, 1440.00, '', 'Open', '2025-11-06 13:41:02', '2025-11-06 13:41:02');

-- --------------------------------------------------------

--
-- Struktur dari tabel `scrap_in_detail`
--

CREATE TABLE `scrap_in_detail` (
  `id` int(11) NOT NULL,
  `header_id` int(11) NOT NULL,
  `scrap_id` int(11) NOT NULL,
  `qty` decimal(15,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `scrap_in_detail_sources`
--

CREATE TABLE `scrap_in_detail_sources` (
  `id` int(11) NOT NULL,
  `scrap_in_detail_id` int(11) NOT NULL,
  `production_output_id` int(11) NOT NULL,
  `qty_collected` decimal(15,4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `scrap_in_header`
--

CREATE TABLE `scrap_in_header` (
  `id` int(11) NOT NULL,
  `doc_no` varchar(50) NOT NULL,
  `doc_date` date NOT NULL,
  `work_order_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `scrap_out_detail`
--

CREATE TABLE `scrap_out_detail` (
  `id` int(11) NOT NULL,
  `header_id` int(11) NOT NULL,
  `scrap_id` int(11) NOT NULL,
  `qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `notes` varchar(255) DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `sales_value_per_unit` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Nilai Jual per unit (untuk laporan BC)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `scrap_out_header`
--

CREATE TABLE `scrap_out_header` (
  `id` int(11) NOT NULL,
  `doc_no` varchar(50) NOT NULL,
  `doc_date` date DEFAULT NULL,
  `bc_type_code` varchar(50) DEFAULT NULL,
  `bc_number` varchar(100) DEFAULT NULL,
  `bc_date` date DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL,
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `scrap_stock`
--

CREATE TABLE `scrap_stock` (
  `id` int(11) NOT NULL,
  `scrap_id` int(11) NOT NULL,
  `scrap_in_detail_id` int(11) DEFAULT NULL,
  `available_qty` decimal(15,4) NOT NULL DEFAULT 0.0000
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `semifinished_goods`
--

CREATE TABLE `semifinished_goods` (
  `id` int(11) NOT NULL,
  `kode_barang` varchar(30) NOT NULL,
  `nama_barang` varchar(150) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `asal` enum('Lokal','Impor') NOT NULL DEFAULT 'Lokal',
  `satuan` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `semifinished_goods`
--

INSERT INTO `semifinished_goods` (`id`, `kode_barang`, `nama_barang`, `kategori`, `asal`, `satuan`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SF-KAI-L-0001', 'UPPER', 'KAIN', 'Lokal', 'PCS', 'active', '2025-10-16 14:20:22', '2025-10-16 14:28:53'),
(4, 'SF-OUT-L-0001', 'Sepatu setengah jadi', 'OUTSOLE', 'Lokal', 'PCS', 'active', '2025-10-16 14:33:01', '2025-11-06 13:52:30'),
(5, 'SF-OUT-L-0002', 'Tongue', 'OUTSOLE', 'Lokal', 'ROLL', 'active', '2025-10-17 08:38:23', '2025-10-30 11:34:15'),
(6, 'SF-KAI-L-0002', 'Midle tongue', 'KAIN', 'Lokal', 'PCS', 'active', '2025-10-17 08:38:41', '2025-10-30 11:34:32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `setting_accounting`
--

CREATE TABLE `setting_accounting` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL COMMENT 'Kode internal, e.g., "akun_piutang_usaha"',
  `setting_name` varchar(255) NOT NULL COMMENT 'Label di form, e.g., "Akun Piutang Usaha"',
  `currency` varchar(10) NOT NULL DEFAULT 'IDR',
  `account_id` int(11) DEFAULT NULL COMMENT 'ID dari chart_of_accounts'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `setting_accounting`
--

INSERT INTO `setting_accounting` (`id`, `setting_key`, `setting_name`, `currency`, `account_id`) VALUES
(1, 'akun_utang_usaha', 'Akun Utang Usaha (AP)', 'IDR', 70),
(2, 'akun_utang_usaha', 'Akun Utang Usaha (AP)', 'USD', 38),
(3, 'akun_utang_usaha', 'Akun Utang Usaha (AP)', 'RMB', NULL),
(4, 'akun_piutang_usaha', 'Akun Piutang Usaha (AR)', 'IDR', 40),
(5, 'akun_piutang_usaha', 'Akun Piutang Usaha (AR)', 'USD', 38),
(6, 'akun_piutang_usaha', 'Akun Piutang Usaha (AR)', 'RMB', NULL),
(7, 'akun_ppn_masukan', 'Akun PPN Masukan', 'IDR', 55),
(8, 'akun_ppn_keluaran', 'Akun PPN Keluaran', 'IDR', 71),
(9, 'akun_utang_pph_23', 'Akun Utang PPh 23', 'IDR', 73),
(10, 'akun_grn_clearing', 'Akun Clearing GRN', 'IDR', 42),
(11, 'akun_pendapatan_foc', 'Akun Pendapatan FOC', 'IDR', NULL),
(12, 'akun_penjualan_fg', 'Akun Penjualan (Barang Jadi)', 'IDR', 84),
(13, 'akun_penjualan_fg', 'Akun Penjualan (Barang Jadi)', 'USD', 38),
(14, 'akun_penjualan_fg', 'Akun Penjualan (Barang Jadi)', 'RMB', NULL),
(15, 'akun_penjualan_scrap', 'Akun Penjualan (Scrap)', 'IDR', 85),
(16, 'akun_hpp_fg', 'Akun HPP (Barang Jadi)', 'IDR', 87),
(17, 'akun_persediaan_fg', 'Akun Persediaan (Barang Jadi)', 'IDR', 53),
(18, 'akun_rugi_selisih_kurs', 'Akun Rugi Selisih Kurs', 'IDR', 137),
(19, 'akun_laba_selisih_kurs', 'Akun Laba Selisih Kurs', 'IDR', 33);

-- --------------------------------------------------------

--
-- Struktur dari tabel `stock_ledger`
--

CREATE TABLE `stock_ledger` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL COMMENT 'material, machine, inventory, semifinished, finished',
  `transaction_date` datetime NOT NULL,
  `transaction_type` varchar(50) NOT NULL COMMENT 'e.g., in, out, production_in, production_out, adjustment, opening',
  `bc_type_code` varchar(50) DEFAULT NULL,
  `bc_number` varchar(100) DEFAULT NULL,
  `bc_date` date DEFAULT NULL,
  `transaction_id` int(11) NOT NULL COMMENT 'ID from source table (grn_header, production_issue_header, etc)',
  `source_doc_type` varchar(10) DEFAULT NULL COMMENT 'Optional: PO, MIR, WO, DO',
  `source_doc_id` int(11) DEFAULT NULL COMMENT 'Optional: ID header source doc',
  `qty_in` decimal(12,2) DEFAULT 0.00,
  `qty_out` decimal(12,2) DEFAULT 0.00,
  `unit_value` decimal(15,2) DEFAULT 0.00 COMMENT 'Nilai satuan saat transaksi (IDR)',
  `balance` decimal(12,2) NOT NULL COMMENT 'Saldo kuantitas setelah transaksi',
  `warehouse_id` int(11) DEFAULT NULL,
  `stock_on_hand_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `stock_ledger`
--

INSERT INTO `stock_ledger` (`id`, `item_id`, `item_type`, `transaction_date`, `transaction_type`, `bc_type_code`, `bc_number`, `bc_date`, `transaction_id`, `source_doc_type`, `source_doc_id`, `qty_in`, `qty_out`, `unit_value`, `balance`, `warehouse_id`, `stock_on_hand_id`) VALUES
(218, 9, 'material', '2025-11-07 09:38:26', 'in', NULL, NULL, NULL, 19, 'PO', 18, 120.00, 0.00, 100000.00, 120.00, NULL, 48),
(219, 11, 'material', '2025-11-07 09:38:26', 'in', NULL, NULL, NULL, 19, 'PO', 18, 120.00, 0.00, 1000.00, 120.00, NULL, 49),
(220, 10, 'material', '2025-11-07 09:38:27', 'in', NULL, NULL, NULL, 19, 'PO', 18, 1200.00, 0.00, 12000.00, 1200.00, NULL, 50),
(224, 9, 'material', '2025-11-07 09:58:10', 'in', NULL, NULL, NULL, 20, 'MIR', 7, 120.00, 0.00, 2000.00, 240.00, NULL, 51),
(225, 9, 'material', '2025-11-07 09:58:50', 'production_out', NULL, NULL, NULL, 14, 'MR', 11, 0.00, 120.00, 2000.00, 120.00, NULL, 51),
(226, 9, 'material', '2025-11-07 09:58:50', 'wip_in', NULL, NULL, NULL, 14, 'MR', 11, 120.00, 0.00, 2000.00, 240.00, NULL, 51),
(227, 9, 'material', '2025-11-07 09:59:51', 'wip_in', NULL, NULL, NULL, 14, 'MR', 14, 120.00, 0.00, 2000.00, 360.00, NULL, NULL),
(228, 9, 'material', '2025-11-07 09:59:58', 'wip_out', NULL, NULL, NULL, 43, 'PROD', 10, 0.00, 15.00, 2000.00, 345.00, NULL, 51),
(229, 1, 'semifinished', '2025-11-07 10:00:20', 'wip_in', NULL, NULL, NULL, 50, 'PROD', 10, 12.00, 0.00, 0.00, 12.00, NULL, NULL),
(230, 4, 'semifinished', '2025-11-07 13:30:04', 'wip_in', NULL, NULL, NULL, 51, 'PROD', 10, 12.00, 0.00, 0.00, 12.00, NULL, NULL),
(231, 1, 'semifinished', '2025-11-07 14:03:35', 'wip_out', NULL, NULL, NULL, 44, 'PROD', 10, 0.00, 11.00, 0.00, 1.00, NULL, NULL),
(232, 1, 'semifinished', '2025-11-07 14:03:45', 'wip_in', NULL, NULL, NULL, 52, 'PROD', 10, 112.00, 0.00, 0.00, 113.00, NULL, NULL),
(233, 2, 'finished_goods', '2025-11-11 15:38:28', 'fg_in', NULL, NULL, NULL, 9, 'FG_RECEIPT', 8, 10.00, 0.00, 0.00, 10.00, NULL, NULL),
(234, 2, 'finished_goods', '2025-11-11 15:59:48', 'fg_out', '9', '099812', '2025-11-12', 15, 'FG_DELIVER', 19, 0.00, 10.00, 0.00, 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `stock_on_hand`
--

CREATE TABLE `stock_on_hand` (
  `id` int(11) NOT NULL,
  `grn_detail_id` int(11) DEFAULT NULL,
  `subkon_in_detail_id` int(11) DEFAULT NULL,
  `grn_header_id` int(11) DEFAULT NULL,
  `subkon_in_header_id` int(11) DEFAULT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `initial_qty` decimal(12,4) NOT NULL,
  `available_qty` decimal(12,4) NOT NULL,
  `unit_value` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Nilai barang (IDR) saat diterima'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `stock_on_hand`
--

INSERT INTO `stock_on_hand` (`id`, `grn_detail_id`, `subkon_in_detail_id`, `grn_header_id`, `subkon_in_header_id`, `item_id`, `item_type`, `initial_qty`, `available_qty`, `unit_value`) VALUES
(48, 17, NULL, 19, NULL, 9, 'material', 120.0000, 0.0000, 100000.00),
(49, 18, NULL, 19, NULL, 11, 'material', 120.0000, 0.0000, 1000.00),
(50, 19, NULL, 19, NULL, 10, 'material', 1200.0000, 0.0000, 12000.00),
(51, 20, NULL, 20, NULL, 9, 'material', 120.0000, 0.0000, 2000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `subkon_in_details`
--

CREATE TABLE `subkon_in_details` (
  `id` int(11) NOT NULL,
  `subkon_in_id` int(11) NOT NULL COMMENT 'Link ke subkon_in_header',
  `order_output_id` int(11) NOT NULL COMMENT 'Link ke subkon_order_outputs (Item Hasil yg dipesan)',
  `item_id` int(11) NOT NULL COMMENT 'ID Item yang Diterima (SFG/FG)',
  `item_type` varchar(50) NOT NULL COMMENT 'semifinished atau finished_goods',
  `qty_received` decimal(15,4) NOT NULL,
  `satuan` varchar(20) DEFAULT NULL,
  `jasa_per_pcs` decimal(15,2) NOT NULL COMMENT 'Nilai Jasa per pcs dari kontrak',
  `total_value_jasa` decimal(20,2) NOT NULL COMMENT 'qty_received * jasa_per_pcs'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `subkon_in_header`
--

CREATE TABLE `subkon_in_header` (
  `id` int(11) NOT NULL,
  `subkon_in_no` varchar(50) NOT NULL COMMENT 'Nomor Penerimaan (GRN-SUB/2025/0001)',
  `receipt_date` date NOT NULL,
  `subkon_order_id` int(11) NOT NULL COMMENT 'Link ke Kontrak Subkon',
  `subkon_out_id` int(11) DEFAULT NULL COMMENT 'Link ke DO Kirim (Opsional, jika ingin per DO)',
  `supplier_id` int(11) NOT NULL COMMENT 'Vendor Subkon',
  `bc_type_code` varchar(50) NOT NULL COMMENT 'Kode Dokumen BC Pemasukan',
  `bc_number` varchar(100) NOT NULL COMMENT 'Nomor Dokumen BC Pemasukan',
  `bc_date` date NOT NULL COMMENT 'Tanggal Dokumen BC Pemasukan',
  `created_by` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Completed, Cancelled',
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `subkon_order_bom`
--

CREATE TABLE `subkon_order_bom` (
  `id` int(11) NOT NULL,
  `subkon_order_id` int(11) NOT NULL COMMENT 'Link ke subkon_order_header',
  `output_item_id` int(11) DEFAULT NULL,
  `item_id` int(11) NOT NULL COMMENT 'ID Material/SFG yang DIKIRIM',
  `item_type` varchar(50) NOT NULL COMMENT 'material atau semifinished',
  `qty_per_pcs` decimal(12,4) NOT NULL COMMENT 'Kebutuhan per 1 pcs barang jadi',
  `total_qty` decimal(15,4) NOT NULL COMMENT 'Total kebutuhan (qty_per_pcs * fg_qty)',
  `satuan` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `subkon_order_header`
--

CREATE TABLE `subkon_order_header` (
  `id` int(11) NOT NULL,
  `subkon_order_no` varchar(50) NOT NULL COMMENT 'Nomor Kontrak Subkon (SK/2025/0001)',
  `order_date` date NOT NULL,
  `supplier_id` int(11) NOT NULL COMMENT 'ID dari tabel suppliers (Vendor Subkon)',
  `total_jasa` decimal(20,2) NOT NULL DEFAULT 0.00,
  `pajak_persen` decimal(5,2) DEFAULT 0.00 COMMENT 'Misal: 2 untuk 2%',
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Confirmed, Closed, Cancelled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `subkon_order_outputs`
--

CREATE TABLE `subkon_order_outputs` (
  `id` int(11) NOT NULL,
  `subkon_order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL COMMENT 'ID Item Hasil (SFG/FG)',
  `item_type` varchar(50) NOT NULL,
  `qty` decimal(15,4) NOT NULL COMMENT 'Qty Hasil yang dipesan',
  `satuan` varchar(20) DEFAULT NULL,
  `jasa_per_pcs` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_jasa_item` decimal(20,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `subkon_out_details`
--

CREATE TABLE `subkon_out_details` (
  `id` int(11) NOT NULL,
  `subkon_out_id` int(11) NOT NULL COMMENT 'Link ke subkon_out_header',
  `item_id` int(11) NOT NULL COMMENT 'ID Item (Material atau SFG)',
  `item_type` varchar(50) NOT NULL COMMENT 'material atau semifinished',
  `qty_kirim` decimal(15,4) NOT NULL,
  `satuan` varchar(20) DEFAULT NULL,
  `unit_value` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Nilai/Cost per item saat dikirim',
  `total_value` decimal(20,2) NOT NULL DEFAULT 0.00 COMMENT 'qty_kirim * unit_value',
  `stock_on_hand_id` int(11) DEFAULT NULL COMMENT 'Jika item_type = material (dari gudang)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `subkon_out_header`
--

CREATE TABLE `subkon_out_header` (
  `id` int(11) NOT NULL,
  `subkon_out_no` varchar(50) NOT NULL COMMENT 'No. Surat Jalan (DO-SUB/2025/0001)',
  `subkon_order_id` int(11) NOT NULL COMMENT 'Link ke subkon_order_header',
  `supplier_id` int(11) NOT NULL COMMENT 'Vendor Subkon',
  `shipping_date` date NOT NULL COMMENT 'Tanggal Kirim',
  `bc_type_code` varchar(50) DEFAULT NULL COMMENT 'Kode Dokumen BC (misal: 4.0)',
  `bc_number` varchar(100) DEFAULT NULL COMMENT 'Nomor Aju/Pendaftaran BC',
  `bc_date` date DEFAULT NULL COMMENT 'Tanggal Dokumen BC',
  `notes` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Draft' COMMENT 'Draft, Confirmed, Cancelled',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `confirmed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `kode_supplier` varchar(50) NOT NULL,
  `nama_supplier` varchar(100) NOT NULL,
  `jenis` enum('Lokal','Impor') NOT NULL,
  `npwp` varchar(30) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `suppliers`
--

INSERT INTO `suppliers` (`id`, `kode_supplier`, `nama_supplier`, `jenis`, `npwp`, `alamat`, `telepon`, `status`, `created_at`, `updated_at`) VALUES
(2, 'LOK-0001', 'PT ADMAJAYA', 'Lokal', '1222', '111', '111', 'inactive', '2025-10-16 15:50:47', '2025-10-17 08:33:36'),
(3, 'LOK-0002', 'PT ADMAJAYA IK', 'Lokal', '1222', 'FDSFDS', '085709329384', 'active', '2025-10-17 08:33:28', '2025-10-17 08:33:28'),
(4, 'IMP-0001', 'PT YUNS', 'Impor', '1222', 'JALAN PATIMORANG', '111', 'active', '2025-11-04 17:47:06', '2025-11-04 17:47:06');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `fullname`, `username`, `password`, `email`, `role_id`, `status`, `updated_at`, `last_login`, `created_at`) VALUES
(1, 'admin', 'admin', '$2y$10$9gm.HIQEGUaVo3qrVcgwXur51cJSBCuLq2OIQV7989PsGgqcHt3dW', 'admin@PT. NEW SPECIALIFE INDUSTRIES.local', 1, 'active', '2025-11-06 08:27:20', '2025-11-06 08:27:20', '2025-10-16 02:24:13'),
(3, 'gudang', 'gudang', '$2y$10$rEHDQdNaaGm5cVbkr.m2Z.ejMBCV9YCrQMGvVx3pyIRAJy4Up4MBe', 'it@foreveroneinternational.com', 1, 'inactive', '2025-10-16 11:50:09', NULL, '2025-10-16 04:44:35'),
(4, 'PPIC', 'PPIC', '$2y$10$NtjeQbga0g6AfkD1PqIhFe8lr4Ng4gylaimEMCHNcnn.O8gtxnnj2', 'it@foreveroneinternational.com', 3, 'active', '2025-11-25 11:43:01', '2025-11-25 11:43:01', '2025-10-16 04:55:11'),
(5, 'gudang', 'gudanbg', '$2y$10$iTRT78M/mO.0/heqNdd.4eY3lWOTvmskS/VIc8sLa7tI97..fkwBS', 'it@foreveroneinternational.com', 1, 'active', '2025-10-16 14:12:43', NULL, '2025-10-16 07:12:43'),
(6, 'Production', 'prodmli', '$2y$10$97QPYbRkDKtF7njtBKaphuoVbWtzLFzf7v89MraAOWjwLz1qXC33O', 'it@foreveroneinternational.com', 3, 'active', '2025-11-05 14:31:00', '2025-11-05 14:31:00', '2025-11-04 10:46:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_logs`
--

CREATE TABLE `user_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'e.g., CREATE, UPDATE, DELETE, LOGIN, LOGOUT',
  `module` varchar(100) NOT NULL COMMENT 'e.g., Purchase Order, Master Supplier',
  `description` text NOT NULL COMMENT 'e.g., Created new PO #PO-001',
  `log_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_logs`
--

INSERT INTO `user_logs` (`id`, `user_id`, `username`, `action`, `module`, `description`, `log_time`) VALUES
(1, 4, 'PPIC', 'CREATE', 'Bill of Materials', 'Membuat BOM header (ID: 4) untuk FG: PMJ6000', '2025-10-24 09:08:21'),
(2, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 1, Tipe: semifinished, Qty: 1) ke BOM ID: 4', '2025-10-24 09:08:36'),
(3, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Update kuantitas item (Detail ID: 4) menjadi 1 pada BOM ID: 4', '2025-10-24 09:13:10'),
(4, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Update kuantitas item (Detail ID: 4) menjadi 1 pada BOM ID: 4', '2025-10-24 09:13:14'),
(5, 4, 'PPIC', 'APPROVE', 'Bill of Materials', 'Approve BOM (ID: 4) untuk FG: PMJ6000. Status: Draft -> Active', '2025-10-24 09:13:18'),
(6, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Deaktivasi BOM (ID: 4) untuk FG: PMJ6000. Status: Active -> Draft', '2025-10-24 09:17:55'),
(7, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 9, Tipe: material, Qty: 1) ke BOM ID: 4', '2025-10-24 09:18:38'),
(8, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Update kuantitas item (Detail ID: 5) menjadi 1 pada BOM ID: 4', '2025-10-24 09:18:40'),
(9, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Update kuantitas item (Detail ID: 4) menjadi 1 pada BOM ID: 4', '2025-10-24 09:18:42'),
(10, 4, 'PPIC', 'APPROVE', 'Bill of Materials', 'Approve BOM (ID: 4) untuk FG: PMJ6000. Status: Draft -> Active', '2025-10-24 09:18:45'),
(11, 4, 'PPIC', 'CREATE', 'Undefined Module', 'Membuat pelanggan baru:  (Kode: )', '2025-10-24 09:24:01'),
(12, 4, 'PPIC', 'UPDATE', 'Master Pelanggan', 'Memperbarui data pelanggan:  (ID: 3) menjadi ', '2025-10-24 09:24:52'),
(13, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2025/00001', '2025-10-30 04:22:40'),
(14, 4, 'PPIC', 'CREATE', 'Bill of Materials', 'Membuat BOM header (ID: 5) untuk FG: PMJ6000', '2025-10-30 04:30:22'),
(15, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 10, Tipe: material, Qty: 1) ke BOM ID: 5', '2025-10-30 04:30:31'),
(16, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Update kuantitas item (Detail ID: 6) menjadi 1 pada BOM ID: 5', '2025-10-30 04:30:33'),
(17, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 9, Tipe: material, Qty: 1) ke BOM ID: 5', '2025-10-30 04:30:40'),
(18, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Update kuantitas item (Detail ID: 7) menjadi 1 pada BOM ID: 5', '2025-10-30 04:30:41'),
(19, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 11, Tipe: material, Qty: 1) ke BOM ID: 5', '2025-10-30 04:30:51'),
(20, 4, 'PPIC', 'APPROVE', 'Bill of Materials', 'Approve BOM (ID: 5) untuk FG: PMJ6000. Status: Draft -> Active', '2025-10-30 04:30:54'),
(21, 4, 'PPIC', 'CREATE', 'Master Pelanggan', 'Membuat pelanggan baru: PT DUO JAYA ABADI (Kode: LOK-0003)', '2025-11-04 10:28:06'),
(22, 6, 'prodmli', 'CREATE', 'Master Pelanggan', 'Membuat pelanggan baru: PT MITRA BEKASI (Kode: LOK-0004)', '2025-11-04 10:47:31'),
(23, 6, 'prodmli', 'UPDATE', 'Master Pelanggan', 'Memperbarui data pelanggan: PT FOREVER11 (ID: 2) menjadi PT FOREVER11', '2025-11-05 04:29:14'),
(24, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2025/00001', '2025-11-06 06:41:02'),
(25, 4, 'PPIC', 'CREATE', 'Bill of Materials', 'Membuat BOM header (ID: 6) untuk FG: PMJ6000', '2025-11-06 06:45:30'),
(26, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 9, Tipe: material, Qty: 1) ke BOM ID: 6', '2025-11-06 06:45:40'),
(27, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 11, Tipe: material, Qty: 0.2) ke BOM ID: 6', '2025-11-06 06:45:50'),
(28, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 10, Tipe: material, Qty: 1) ke BOM ID: 6', '2025-11-06 06:45:58'),
(29, 4, 'PPIC', 'APPROVE', 'Bill of Materials', 'Approve BOM (ID: 6) untuk FG: PMJ6000. Status: Draft -> Active', '2025-11-06 06:46:00'),
(30, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202511/0001 untuk WO: N/A', '2025-11-06 06:50:10'),
(31, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Kain Woven (Qty: 110) ke tahap cutting pada Produksi ID: 9', '2025-11-06 06:50:17'),
(32, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Eva 1.2mm (Qty: 10) ke tahap cutting pada Produksi ID: 9', '2025-11-06 06:50:24'),
(33, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap cutting: item tipe semifinished (Qty: 110) pada Produksi ID: 9', '2025-11-06 06:50:40'),
(34, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap cutting: item tipe semifinished (Qty: 110) pada Produksi ID: 9', '2025-11-06 06:50:48'),
(35, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 5 pada tahap cutting (Produksi ID: 9)', '2025-11-06 06:50:49'),
(36, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 6 pada tahap cutting (Produksi ID: 9)', '2025-11-06 06:50:51'),
(37, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap cutting: item tipe scrap (Qty: 1) pada Produksi ID: 9', '2025-11-06 06:50:59'),
(38, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap cutting (Produksi ID: 9)', '2025-11-06 06:51:01'),
(39, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input Tongue (Qty: 100) dari tahap cutting ke sewing pada Produksi ID: 9', '2025-11-06 06:51:20'),
(40, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input Midle tongue (Qty: 100) dari tahap cutting ke sewing pada Produksi ID: 9', '2025-11-06 06:51:24'),
(41, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Benang Jait (Qty: 10) ke tahap sewing pada Produksi ID: 9', '2025-11-06 06:51:32'),
(42, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap sewing: item tipe semifinished (Qty: 110) pada Produksi ID: 9', '2025-11-06 06:51:43'),
(43, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap sewing (Produksi ID: 9)', '2025-11-06 06:51:45'),
(44, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap sewing: item tipe scrap (Qty: 0.3) pada Produksi ID: 9', '2025-11-06 06:51:51'),
(45, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap sewing (Produksi ID: 9)', '2025-11-06 06:51:53'),
(46, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input UPPER (Qty: 110) dari tahap sewing ke assembling pada Produksi ID: 9', '2025-11-06 06:52:07'),
(47, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe semifinished (Qty: 110) pada Produksi ID: 9', '2025-11-06 06:52:39'),
(48, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 4 pada tahap assembling (Produksi ID: 9)', '2025-11-06 06:52:43'),
(49, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input Sepatu setengah jadi (Qty: 110) dari tahap assembling ke finishing pada Produksi ID: 9', '2025-11-06 06:52:51'),
(50, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Benang Jait (Qty: 1) ke tahap sewing pada Produksi ID: 9', '2025-11-06 06:58:35'),
(51, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus input ID: 40 dari tahap  pada Produksi ID: 9', '2025-11-06 06:58:42'),
(52, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Benang Jait (Qty: 1) ke tahap assembling pada Produksi ID: 9', '2025-11-06 06:59:21'),
(53, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus input ID: 41 dari tahap  pada Produksi ID: 9', '2025-11-06 06:59:27'),
(54, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Kain Woven (Qty: 1) ke tahap finishing pada Produksi ID: 9', '2025-11-06 07:00:55'),
(55, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output Finished Goods (Qty: 110) pada Produksi ID: 9', '2025-11-06 07:01:21'),
(56, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 2 pada tahap finishing (Produksi ID: 9)', '2025-11-06 07:01:24'),
(57, 4, 'PPIC', 'APPROVE', 'Production Process', 'Konfirmasi Proses Produksi: PROD/202511/0001. Status: Draft -> In Progress', '2025-11-06 07:01:31'),
(58, 4, 'PPIC', 'COMPLETE', 'Production Process', 'Menyelesaikan Proses Produksi: PROD/202511/0001. Status: In Progress -> Completed', '2025-11-06 07:01:46'),
(59, 4, 'PPIC', 'CREATE', 'Material Return', 'Membuat draft Material Return baru: RFP-202511-001', '2025-11-07 02:31:49'),
(60, 4, 'PPIC', 'APPROVE', 'Material Return', 'Konfirmasi (approve) Material Return: RFP-202511-001', '2025-11-07 02:31:53'),
(61, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202511/0001 untuk WO: N/A', '2025-11-07 02:40:22'),
(62, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Kain Woven (Qty: 15) ke tahap cutting pada Produksi ID: 10', '2025-11-07 02:59:58'),
(63, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap cutting: item tipe semifinished (Qty: 12) pada Produksi ID: 10', '2025-11-07 03:00:20'),
(64, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap cutting (Produksi ID: 10)', '2025-11-07 03:00:31'),
(65, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe semifinished (Qty: 12) pada Produksi ID: 10', '2025-11-07 06:30:04'),
(66, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 4 pada tahap assembling (Produksi ID: 10)', '2025-11-07 06:30:07'),
(67, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input UPPER (Qty: 11) dari tahap cutting ke sewing pada Produksi ID: 10', '2025-11-07 07:03:35'),
(68, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap sewing: item tipe semifinished (Qty: 112) pada Produksi ID: 10', '2025-11-07 07:03:45'),
(69, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap sewing (Produksi ID: 10)', '2025-11-07 07:03:48'),
(70, 4, 'PPIC', 'APPROVE', 'Production Process', 'Konfirmasi Proses Produksi: PROD/202511/0001. Status: Draft -> In Progress', '2025-11-11 08:37:09'),
(71, 4, 'PPIC', 'UPDATE', 'Production Process', 'Membatalkan konfirmasi Proses Produksi: PROD/202511/0001. Status: In Progress -> Draft', '2025-11-11 08:37:22'),
(72, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output Finished Goods (Qty: 10) pada Produksi ID: 10', '2025-11-11 08:37:38'),
(73, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 2 pada tahap finishing (Produksi ID: 10)', '2025-11-11 08:37:41'),
(74, 4, 'PPIC', 'APPROVE', 'Production Process', 'Konfirmasi Proses Produksi: PROD/202511/0001. Status: Draft -> In Progress', '2025-11-11 08:37:44'),
(75, 4, 'PPIC', 'COMPLETE', 'Production Process', 'Menyelesaikan Proses Produksi: PROD/202511/0001. Status: In Progress -> Completed', '2025-11-11 08:38:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `warehouse_in_detail`
--

CREATE TABLE `warehouse_in_detail` (
  `id` int(11) NOT NULL,
  `header_id` int(11) NOT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `nama_barang` varchar(200) NOT NULL,
  `satuan` varchar(50) DEFAULT NULL,
  `qty` decimal(12,2) DEFAULT 0.00,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `warehouse_in_header`
--

CREATE TABLE `warehouse_in_header` (
  `id` int(11) NOT NULL,
  `no_penerimaan` varchar(50) NOT NULL,
  `jenis_bc` varchar(50) DEFAULT NULL,
  `no_bc` varchar(100) DEFAULT NULL,
  `tgl_bc` date DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `asal_barang` enum('Lokal','Impor','Maklon') DEFAULT 'Lokal',
  `base_on` enum('Purchase Order','Material Impor','Manual') DEFAULT 'Manual',
  `tgl_datang` date DEFAULT NULL,
  `tgl_penerimaan` date DEFAULT NULL,
  `status` enum('Draft','Selesai') DEFAULT 'Draft',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `diterima_oleh` varchar(100) DEFAULT NULL,
  `qc_oleh` varchar(100) DEFAULT NULL,
  `diperiksa_oleh` varchar(100) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `wo_details_material`
--

CREATE TABLE `wo_details_material` (
  `id` int(11) NOT NULL,
  `wo_header_id` int(11) NOT NULL,
  `component_item_id` int(11) NOT NULL,
  `component_item_type` varchar(50) NOT NULL COMMENT '''material'', ''semifinished''',
  `planned_qty` decimal(12,4) NOT NULL COMMENT 'Total qty komponen utk WO ini',
  `unit_measure` varchar(20) DEFAULT NULL COMMENT 'Satuan komponen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `wo_details_material`
--

INSERT INTO `wo_details_material` (`id`, `wo_header_id`, `component_item_id`, `component_item_type`, `planned_qty`, `unit_measure`) VALUES
(16, 11, 9, 'material', 120.0000, 'PCS'),
(17, 11, 10, 'material', 1200.0000, 'ROLL'),
(18, 11, 11, 'material', 120.0000, 'PCS');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wo_header`
--

CREATE TABLE `wo_header` (
  `id` int(11) NOT NULL,
  `wo_number` varchar(50) NOT NULL,
  `sales_contract_id` int(11) NOT NULL,
  `fg_id` int(11) NOT NULL COMMENT 'Otomatis dari SC',
  `planned_qty` int(11) NOT NULL COMMENT 'Qty FG yang akan diproduksi',
  `planned_start_date` date DEFAULT NULL,
  `planned_end_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('Planned','In Progress','Completed','Cancelled') DEFAULT 'Planned',
  `confirmed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `wo_header`
--

INSERT INTO `wo_header` (`id`, `wo_number`, `sales_contract_id`, `fg_id`, `planned_qty`, `planned_start_date`, `planned_end_date`, `remarks`, `status`, `confirmed_by`, `confirmed_at`, `created_by`, `created_at`, `updated_at`) VALUES
(11, 'WO/202511/0001', 7, 2, 120, '2025-11-07', '2025-11-07', '', 'In Progress', 4, '2025-11-07 09:40:11', 4, '2025-11-07 09:39:27', '2025-11-07 09:40:11');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `adjustment_details`
--
ALTER TABLE `adjustment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `adjustment_header_id` (`adjustment_header_id`),
  ADD KEY `item_idx` (`item_id`,`item_type`),
  ADD KEY `stock_on_hand_id` (`stock_on_hand_id`);

--
-- Indeks untuk tabel `adjustment_header`
--
ALTER TABLE `adjustment_header`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `ap_invoice_details`
--
ALTER TABLE `ap_invoice_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ap_invoice_header_id` (`ap_invoice_header_id`),
  ADD KEY `grn_detail_id` (`grn_detail_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indeks untuk tabel `ap_invoice_header`
--
ALTER TABLE `ap_invoice_header`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `journal_header_id` (`journal_header_id`);

--
-- Indeks untuk tabel `ap_payment_details`
--
ALTER TABLE `ap_payment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_header_id` (`payment_header_id`),
  ADD KEY `ap_invoice_header_id` (`ap_invoice_header_id`);

--
-- Indeks untuk tabel `ap_payment_header`
--
ALTER TABLE `ap_payment_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_no` (`payment_no`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `payment_account_id` (`payment_account_id`);

--
-- Indeks untuk tabel `ar_invoice_details`
--
ALTER TABLE `ar_invoice_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ar_invoice_header_id` (`ar_invoice_header_id`),
  ADD KEY `source_detail_id` (`source_detail_id`);

--
-- Indeks untuk tabel `ar_invoice_header`
--
ALTER TABLE `ar_invoice_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `journal_header_id` (`journal_header_id`);

--
-- Indeks untuk tabel `ar_receipt_details`
--
ALTER TABLE `ar_receipt_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_header_id` (`receipt_header_id`),
  ADD KEY `ar_invoice_header_id` (`ar_invoice_header_id`);

--
-- Indeks untuk tabel `ar_receipt_header`
--
ALTER TABLE `ar_receipt_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `payment_account_id` (`payment_account_id`);

--
-- Indeks untuk tabel `bom_details`
--
ALTER TABLE `bom_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `header_component_unique` (`bom_header_id`,`component_item_id`,`component_item_type`) COMMENT 'Satu komponen unik per BOM';

--
-- Indeks untuk tabel `bom_header`
--
ALTER TABLE `bom_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fg_id_unique` (`fg_id`) COMMENT 'Satu BOM aktif per FG';

--
-- Indeks untuk tabel `cash_bank_details`
--
ALTER TABLE `cash_bank_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `header_id` (`header_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indeks untuk tabel `cash_bank_header`
--
ALTER TABLE `cash_bank_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doc_no` (`doc_no`),
  ADD KEY `cash_bank_account_id` (`cash_bank_account_id`);

--
-- Indeks untuk tabel `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indeks untuk tabel `common_code`
--
ALTER TABLE `common_code`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `fg_delivery_details`
--
ALTER TABLE `fg_delivery_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fg_delivery_header_id` (`fg_delivery_header_id`),
  ADD KEY `so_detail_id` (`so_detail_id`),
  ADD KEY `fg_id` (`fg_id`);

--
-- Indeks untuk tabel `fg_delivery_header`
--
ALTER TABLE `fg_delivery_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `delivery_number` (`delivery_number`),
  ADD KEY `sales_order_id` (`sales_order_id`),
  ADD KEY `sales_contract_id` (`sales_contract_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `shipped_by_user` (`shipped_by_user`);

--
-- Indeks untuk tabel `fg_receipt_details`
--
ALTER TABLE `fg_receipt_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fg_receipt_header_id` (`fg_receipt_header_id`),
  ADD KEY `prod_output_id` (`prod_output_id`),
  ADD KEY `fg_id` (`fg_id`);

--
-- Indeks untuk tabel `fg_receipt_header`
--
ALTER TABLE `fg_receipt_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `production_header_id` (`production_header_id`),
  ADD KEY `wo_header_id` (`wo_header_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `confirmed_by` (`confirmed_by`);

--
-- Indeks untuk tabel `finished_goods`
--
ALTER TABLE `finished_goods`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `grn_details`
--
ALTER TABLE `grn_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grn_header_id` (`grn_header_id`);

--
-- Indeks untuk tabel `grn_header`
--
ALTER TABLE `grn_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grn_number` (`grn_number`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `grn_header_ibfk_3` (`confirmed_by`);

--
-- Indeks untuk tabel `inventories`
--
ALTER TABLE `inventories`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `issuance_details`
--
ALTER TABLE `issuance_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mr_header_id` (`mr_header_id`),
  ADD KEY `mr_detail_id` (`mr_detail_id`),
  ADD KEY `stock_on_hand_id` (`stock_on_hand_id`);

--
-- Indeks untuk tabel `journal_details`
--
ALTER TABLE `journal_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `journal_header_id` (`journal_header_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indeks untuk tabel `journal_header`
--
ALTER TABLE `journal_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `journal_no` (`journal_no`);

--
-- Indeks untuk tabel `machines`
--
ALTER TABLE `machines`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `master_scrap`
--
ALTER TABLE `master_scrap`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scrap_code` (`scrap_code`);

--
-- Indeks untuk tabel `materials`
--
ALTER TABLE `materials`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_material` (`kode_material`);

--
-- Indeks untuk tabel `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `mir_details`
--
ALTER TABLE `mir_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mir_header_id` (`mir_header_id`);

--
-- Indeks untuk tabel `mir_header`
--
ALTER TABLE `mir_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mir_number` (`mir_number`),
  ADD KEY `mir_header_ibfk_2` (`supplier_id`),
  ADD KEY `fk_mir_approver` (`approved_by`);

--
-- Indeks untuk tabel `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `mr_details`
--
ALTER TABLE `mr_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mr_wo_component_unique` (`mr_header_id`,`wo_header_id`,`component_item_id`,`component_item_type`),
  ADD KEY `wo_header_id` (`wo_header_id`);

--
-- Indeks untuk tabel `mr_header`
--
ALTER TABLE `mr_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `mr_number` (`mr_number`),
  ADD UNIQUE KEY `issuance_number` (`issuance_number`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `issuer_id` (`issuer_id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `idx_mr_number_generation` (`mr_number`,`id`);

--
-- Indeks untuk tabel `opname_details`
--
ALTER TABLE `opname_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `opname_header_id` (`opname_header_id`),
  ADD KEY `item_idx` (`item_id`,`item_type`);

--
-- Indeks untuk tabel `opname_header`
--
ALTER TABLE `opname_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `opname_no` (`opname_no`),
  ADD KEY `adjustment_header_id` (`adjustment_header_id`);

--
-- Indeks untuk tabel `po_details`
--
ALTER TABLE `po_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_header_id` (`po_header_id`),
  ADD KEY `sales_contract_id` (`sales_contract_id`);

--
-- Indeks untuk tabel `po_header`
--
ALTER TABLE `po_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indeks untuk tabel `production_header`
--
ALTER TABLE `production_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `production_number` (`production_number`),
  ADD KEY `wo_header_id` (`wo_header_id`),
  ADD KEY `fg_id` (`fg_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `confirmed_by` (`confirmed_by`),
  ADD KEY `idx_prod_number_generation` (`production_number`,`id`);

--
-- Indeks untuk tabel `production_inputs`
--
ALTER TABLE `production_inputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_input_prod_header` (`production_header_id`),
  ADD KEY `fk_input_mr_detail` (`mr_detail_id`),
  ADD KEY `source_output_id` (`source_output_id`),
  ADD KEY `idx_stock_on_hand_id` (`stock_on_hand_id`);

--
-- Indeks untuk tabel `production_outputs`
--
ALTER TABLE `production_outputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `production_header_id` (`production_header_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `qc_checked_by` (`qc_checked_by`),
  ADD KEY `idx_scrap_type_qty` (`item_type`,`qty_scrap`);

--
-- Indeks untuk tabel `production_output_sizes`
--
ALTER TABLE `production_output_sizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_output_sizes` (`output_id`);

--
-- Indeks untuk tabel `prod_return_detail`
--
ALTER TABLE `prod_return_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prod_return_id` (`prod_return_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indeks untuk tabel `prod_return_header`
--
ALTER TABLE `prod_return_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `prod_return_no` (`prod_return_no`),
  ADD KEY `work_order_id` (`work_order_id`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Indeks untuk tabel `sales_contracts`
--
ALTER TABLE `sales_contracts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sc_number` (`sc_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `fg_id` (`fg_id`);

--
-- Indeks untuk tabel `scrap_in_detail`
--
ALTER TABLE `scrap_in_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `header_id` (`header_id`),
  ADD KEY `scrap_id` (`scrap_id`);

--
-- Indeks untuk tabel `scrap_in_detail_sources`
--
ALTER TABLE `scrap_in_detail_sources`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_source` (`scrap_in_detail_id`,`production_output_id`),
  ADD KEY `production_output_id` (`production_output_id`);

--
-- Indeks untuk tabel `scrap_in_header`
--
ALTER TABLE `scrap_in_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doc_no` (`doc_no`),
  ADD KEY `work_order_id` (`work_order_id`);

--
-- Indeks untuk tabel `scrap_out_detail`
--
ALTER TABLE `scrap_out_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `scrap_id` (`scrap_id`),
  ADD KEY `fk_scrap_out_detail_header` (`header_id`);

--
-- Indeks untuk tabel `scrap_out_header`
--
ALTER TABLE `scrap_out_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `doc_no` (`doc_no`),
  ADD KEY `bc_type_code` (`bc_type_code`),
  ADD KEY `bc_number` (`bc_number`),
  ADD KEY `status` (`status`);

--
-- Indeks untuk tabel `scrap_stock`
--
ALTER TABLE `scrap_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `scrap_id` (`scrap_id`),
  ADD KEY `fk_scrap_stock_detail` (`scrap_in_detail_id`);

--
-- Indeks untuk tabel `semifinished_goods`
--
ALTER TABLE `semifinished_goods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_barang` (`kode_barang`);

--
-- Indeks untuk tabel `setting_accounting`
--
ALTER TABLE `setting_accounting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key_currency_pair` (`setting_key`,`currency`),
  ADD KEY `account_id` (`account_id`);

--
-- Indeks untuk tabel `stock_ledger`
--
ALTER TABLE `stock_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_idx` (`item_id`,`item_type`),
  ADD KEY `transaction_idx` (`transaction_type`,`transaction_id`),
  ADD KEY `idx_stock_on_hand_id` (`stock_on_hand_id`),
  ADD KEY `idx_ledger_item_balance` (`item_id`,`item_type`,`id`),
  ADD KEY `idx_item_type_date` (`item_type`,`transaction_date`),
  ADD KEY `idx_item_id_type` (`item_id`,`item_type`),
  ADD KEY `idx_source_doc` (`source_doc_type`,`source_doc_id`);

--
-- Indeks untuk tabel `stock_on_hand`
--
ALTER TABLE `stock_on_hand`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_idx` (`item_id`,`item_type`),
  ADD KEY `grn_detail_id` (`grn_detail_id`),
  ADD KEY `grn_header_id` (`grn_header_id`);

--
-- Indeks untuk tabel `subkon_in_details`
--
ALTER TABLE `subkon_in_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subkon_in_id` (`subkon_in_id`),
  ADD KEY `order_output_id` (`order_output_id`);

--
-- Indeks untuk tabel `subkon_in_header`
--
ALTER TABLE `subkon_in_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subkon_in_no` (`subkon_in_no`),
  ADD KEY `subkon_order_id` (`subkon_order_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indeks untuk tabel `subkon_order_bom`
--
ALTER TABLE `subkon_order_bom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subkon_order_id` (`subkon_order_id`);

--
-- Indeks untuk tabel `subkon_order_header`
--
ALTER TABLE `subkon_order_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subkon_order_no` (`subkon_order_no`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indeks untuk tabel `subkon_order_outputs`
--
ALTER TABLE `subkon_order_outputs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subkon_order_id` (`subkon_order_id`);

--
-- Indeks untuk tabel `subkon_out_details`
--
ALTER TABLE `subkon_out_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subkon_out_id` (`subkon_out_id`);

--
-- Indeks untuk tabel `subkon_out_header`
--
ALTER TABLE `subkon_out_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subkon_out_no` (`subkon_out_no`),
  ADD KEY `subkon_order_id` (`subkon_order_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indeks untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indeks untuk tabel `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `log_time` (`log_time`);

--
-- Indeks untuk tabel `warehouse_in_detail`
--
ALTER TABLE `warehouse_in_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `header_id` (`header_id`);

--
-- Indeks untuk tabel `warehouse_in_header`
--
ALTER TABLE `warehouse_in_header`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `wo_details_material`
--
ALTER TABLE `wo_details_material`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wo_component_unique` (`wo_header_id`,`component_item_id`,`component_item_type`);

--
-- Indeks untuk tabel `wo_header`
--
ALTER TABLE `wo_header`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `wo_number` (`wo_number`),
  ADD KEY `sales_contract_id` (`sales_contract_id`),
  ADD KEY `fg_id` (`fg_id`),
  ADD KEY `wo_header_ibfk_4` (`created_by`),
  ADD KEY `wo_header_ibfk_5` (`confirmed_by`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `adjustment_details`
--
ALTER TABLE `adjustment_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `adjustment_header`
--
ALTER TABLE `adjustment_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `ap_invoice_details`
--
ALTER TABLE `ap_invoice_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `ap_invoice_header`
--
ALTER TABLE `ap_invoice_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `ap_payment_details`
--
ALTER TABLE `ap_payment_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `ap_payment_header`
--
ALTER TABLE `ap_payment_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `ar_invoice_details`
--
ALTER TABLE `ar_invoice_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `ar_invoice_header`
--
ALTER TABLE `ar_invoice_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `ar_receipt_details`
--
ALTER TABLE `ar_receipt_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `ar_receipt_header`
--
ALTER TABLE `ar_receipt_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `bom_details`
--
ALTER TABLE `bom_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `bom_header`
--
ALTER TABLE `bom_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `cash_bank_details`
--
ALTER TABLE `cash_bank_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `cash_bank_header`
--
ALTER TABLE `cash_bank_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT untuk tabel `common_code`
--
ALTER TABLE `common_code`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `fg_delivery_details`
--
ALTER TABLE `fg_delivery_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `fg_delivery_header`
--
ALTER TABLE `fg_delivery_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `fg_receipt_details`
--
ALTER TABLE `fg_receipt_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `fg_receipt_header`
--
ALTER TABLE `fg_receipt_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `finished_goods`
--
ALTER TABLE `finished_goods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `grn_details`
--
ALTER TABLE `grn_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `grn_header`
--
ALTER TABLE `grn_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `inventories`
--
ALTER TABLE `inventories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `issuance_details`
--
ALTER TABLE `issuance_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `journal_details`
--
ALTER TABLE `journal_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `journal_header`
--
ALTER TABLE `journal_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `machines`
--
ALTER TABLE `machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `master_scrap`
--
ALTER TABLE `master_scrap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT untuk tabel `mir_details`
--
ALTER TABLE `mir_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `mir_header`
--
ALTER TABLE `mir_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `mr_details`
--
ALTER TABLE `mr_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `mr_header`
--
ALTER TABLE `mr_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `opname_details`
--
ALTER TABLE `opname_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `opname_header`
--
ALTER TABLE `opname_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `po_details`
--
ALTER TABLE `po_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `po_header`
--
ALTER TABLE `po_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `production_header`
--
ALTER TABLE `production_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `production_inputs`
--
ALTER TABLE `production_inputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT untuk tabel `production_outputs`
--
ALTER TABLE `production_outputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT untuk tabel `production_output_sizes`
--
ALTER TABLE `production_output_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `prod_return_detail`
--
ALTER TABLE `prod_return_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT untuk tabel `prod_return_header`
--
ALTER TABLE `prod_return_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1678;

--
-- AUTO_INCREMENT untuk tabel `sales_contracts`
--
ALTER TABLE `sales_contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `scrap_in_detail`
--
ALTER TABLE `scrap_in_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `scrap_in_detail_sources`
--
ALTER TABLE `scrap_in_detail_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `scrap_in_header`
--
ALTER TABLE `scrap_in_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `scrap_out_detail`
--
ALTER TABLE `scrap_out_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `scrap_out_header`
--
ALTER TABLE `scrap_out_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT untuk tabel `scrap_stock`
--
ALTER TABLE `scrap_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `semifinished_goods`
--
ALTER TABLE `semifinished_goods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `setting_accounting`
--
ALTER TABLE `setting_accounting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `stock_ledger`
--
ALTER TABLE `stock_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=235;

--
-- AUTO_INCREMENT untuk tabel `stock_on_hand`
--
ALTER TABLE `stock_on_hand`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT untuk tabel `subkon_in_details`
--
ALTER TABLE `subkon_in_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `subkon_in_header`
--
ALTER TABLE `subkon_in_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `subkon_order_bom`
--
ALTER TABLE `subkon_order_bom`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `subkon_order_header`
--
ALTER TABLE `subkon_order_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `subkon_order_outputs`
--
ALTER TABLE `subkon_order_outputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `subkon_out_details`
--
ALTER TABLE `subkon_out_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `subkon_out_header`
--
ALTER TABLE `subkon_out_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT untuk tabel `warehouse_in_detail`
--
ALTER TABLE `warehouse_in_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `warehouse_in_header`
--
ALTER TABLE `warehouse_in_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `wo_details_material`
--
ALTER TABLE `wo_details_material`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `wo_header`
--
ALTER TABLE `wo_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `adjustment_details`
--
ALTER TABLE `adjustment_details`
  ADD CONSTRAINT `adjustment_details_ibfk_1` FOREIGN KEY (`adjustment_header_id`) REFERENCES `adjustment_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ap_invoice_details`
--
ALTER TABLE `ap_invoice_details`
  ADD CONSTRAINT `ap_details_fk_1` FOREIGN KEY (`ap_invoice_header_id`) REFERENCES `ap_invoice_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ap_payment_details`
--
ALTER TABLE `ap_payment_details`
  ADD CONSTRAINT `ap_payment_details_ibfk_1` FOREIGN KEY (`payment_header_id`) REFERENCES `ap_payment_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ar_invoice_details`
--
ALTER TABLE `ar_invoice_details`
  ADD CONSTRAINT `ar_details_fk_1` FOREIGN KEY (`ar_invoice_header_id`) REFERENCES `ar_invoice_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ar_receipt_details`
--
ALTER TABLE `ar_receipt_details`
  ADD CONSTRAINT `ar_receipt_details_ibfk_1` FOREIGN KEY (`receipt_header_id`) REFERENCES `ar_receipt_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `bom_details`
--
ALTER TABLE `bom_details`
  ADD CONSTRAINT `bom_details_ibfk_1` FOREIGN KEY (`bom_header_id`) REFERENCES `bom_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `bom_header`
--
ALTER TABLE `bom_header`
  ADD CONSTRAINT `bom_header_ibfk_1` FOREIGN KEY (`fg_id`) REFERENCES `finished_goods` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cash_bank_details`
--
ALTER TABLE `cash_bank_details`
  ADD CONSTRAINT `cash_bank_details_ibfk_1` FOREIGN KEY (`header_id`) REFERENCES `cash_bank_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `fg_delivery_details`
--
ALTER TABLE `fg_delivery_details`
  ADD CONSTRAINT `fk_delivery_detail_fg` FOREIGN KEY (`fg_id`) REFERENCES `finished_goods` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_delivery_detail_header` FOREIGN KEY (`fg_delivery_header_id`) REFERENCES `fg_delivery_header` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `fg_receipt_details`
--
ALTER TABLE `fg_receipt_details`
  ADD CONSTRAINT `fg_receipt_details_ibfk_1` FOREIGN KEY (`fg_receipt_header_id`) REFERENCES `fg_receipt_header` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fg_receipt_details_ibfk_2` FOREIGN KEY (`prod_output_id`) REFERENCES `production_outputs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fg_receipt_details_ibfk_3` FOREIGN KEY (`fg_id`) REFERENCES `finished_goods` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `fg_receipt_header`
--
ALTER TABLE `fg_receipt_header`
  ADD CONSTRAINT `fg_receipt_header_ibfk_1` FOREIGN KEY (`production_header_id`) REFERENCES `production_header` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fg_receipt_header_ibfk_2` FOREIGN KEY (`wo_header_id`) REFERENCES `wo_header` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fg_receipt_header_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fg_receipt_header_ibfk_4` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `grn_details`
--
ALTER TABLE `grn_details`
  ADD CONSTRAINT `grn_details_ibfk_1` FOREIGN KEY (`grn_header_id`) REFERENCES `grn_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `grn_header`
--
ALTER TABLE `grn_header`
  ADD CONSTRAINT `grn_header_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `grn_header_ibfk_3` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `issuance_details`
--
ALTER TABLE `issuance_details`
  ADD CONSTRAINT `issuance_details_ibfk_1` FOREIGN KEY (`mr_header_id`) REFERENCES `mr_header` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issuance_details_ibfk_2` FOREIGN KEY (`mr_detail_id`) REFERENCES `mr_details` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `issuance_details_ibfk_3` FOREIGN KEY (`stock_on_hand_id`) REFERENCES `stock_on_hand` (`id`);

--
-- Ketidakleluasaan untuk tabel `journal_details`
--
ALTER TABLE `journal_details`
  ADD CONSTRAINT `journal_details_ibfk_1` FOREIGN KEY (`journal_header_id`) REFERENCES `journal_header` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `journal_details_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`);

--
-- Ketidakleluasaan untuk tabel `mir_details`
--
ALTER TABLE `mir_details`
  ADD CONSTRAINT `mir_details_ibfk_1` FOREIGN KEY (`mir_header_id`) REFERENCES `mir_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `mir_header`
--
ALTER TABLE `mir_header`
  ADD CONSTRAINT `fk_mir_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `mir_header_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `mr_details`
--
ALTER TABLE `mr_details`
  ADD CONSTRAINT `mr_details_ibfk_1` FOREIGN KEY (`mr_header_id`) REFERENCES `mr_header` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mr_details_ibfk_2` FOREIGN KEY (`wo_header_id`) REFERENCES `wo_header` (`id`);

--
-- Ketidakleluasaan untuk tabel `mr_header`
--
ALTER TABLE `mr_header`
  ADD CONSTRAINT `mr_header_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mr_header_ibfk_2` FOREIGN KEY (`issuer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `mr_header_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `opname_details`
--
ALTER TABLE `opname_details`
  ADD CONSTRAINT `opname_details_ibfk_1` FOREIGN KEY (`opname_header_id`) REFERENCES `opname_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `po_details`
--
ALTER TABLE `po_details`
  ADD CONSTRAINT `po_details_ibfk_1` FOREIGN KEY (`po_header_id`) REFERENCES `po_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `po_header`
--
ALTER TABLE `po_header`
  ADD CONSTRAINT `po_header_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Ketidakleluasaan untuk tabel `production_header`
--
ALTER TABLE `production_header`
  ADD CONSTRAINT `production_header_ibfk_1` FOREIGN KEY (`wo_header_id`) REFERENCES `wo_header` (`id`),
  ADD CONSTRAINT `production_header_ibfk_2` FOREIGN KEY (`fg_id`) REFERENCES `finished_goods` (`id`),
  ADD CONSTRAINT `production_header_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_header_ibfk_4` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `production_inputs`
--
ALTER TABLE `production_inputs`
  ADD CONSTRAINT `fk_input_mr_detail` FOREIGN KEY (`mr_detail_id`) REFERENCES `mr_details` (`id`),
  ADD CONSTRAINT `fk_input_prod_header` FOREIGN KEY (`production_header_id`) REFERENCES `production_header` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_inputs_ibfk_1` FOREIGN KEY (`source_output_id`) REFERENCES `production_outputs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `production_outputs`
--
ALTER TABLE `production_outputs`
  ADD CONSTRAINT `production_outputs_ibfk_1` FOREIGN KEY (`production_header_id`) REFERENCES `production_header` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `production_outputs_ibfk_2` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `production_outputs_ibfk_3` FOREIGN KEY (`qc_checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `production_output_sizes`
--
ALTER TABLE `production_output_sizes`
  ADD CONSTRAINT `fk_output_sizes` FOREIGN KEY (`output_id`) REFERENCES `production_outputs` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `sales_contracts`
--
ALTER TABLE `sales_contracts`
  ADD CONSTRAINT `sales_contracts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `sales_contracts_ibfk_2` FOREIGN KEY (`fg_id`) REFERENCES `finished_goods` (`id`);

--
-- Ketidakleluasaan untuk tabel `scrap_in_detail`
--
ALTER TABLE `scrap_in_detail`
  ADD CONSTRAINT `scrap_in_detail_ibfk_1` FOREIGN KEY (`header_id`) REFERENCES `scrap_in_header` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scrap_in_detail_ibfk_2` FOREIGN KEY (`scrap_id`) REFERENCES `master_scrap` (`id`);

--
-- Ketidakleluasaan untuk tabel `scrap_in_detail_sources`
--
ALTER TABLE `scrap_in_detail_sources`
  ADD CONSTRAINT `scrap_in_detail_sources_ibfk_1` FOREIGN KEY (`scrap_in_detail_id`) REFERENCES `scrap_in_detail` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scrap_in_detail_sources_ibfk_2` FOREIGN KEY (`production_output_id`) REFERENCES `production_outputs` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `scrap_in_header`
--
ALTER TABLE `scrap_in_header`
  ADD CONSTRAINT `scrap_in_header_ibfk_1` FOREIGN KEY (`work_order_id`) REFERENCES `wo_header` (`id`);

--
-- Ketidakleluasaan untuk tabel `scrap_out_detail`
--
ALTER TABLE `scrap_out_detail`
  ADD CONSTRAINT `fk_scrap_out_detail_header` FOREIGN KEY (`header_id`) REFERENCES `scrap_out_header` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `scrap_out_detail_ibfk_1` FOREIGN KEY (`header_id`) REFERENCES `scrap_out_header` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `scrap_out_detail_ibfk_2` FOREIGN KEY (`scrap_id`) REFERENCES `master_scrap` (`id`);

--
-- Ketidakleluasaan untuk tabel `scrap_stock`
--
ALTER TABLE `scrap_stock`
  ADD CONSTRAINT `fk_scrap_stock_detail` FOREIGN KEY (`scrap_in_detail_id`) REFERENCES `scrap_in_detail` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_scrap_stock_master` FOREIGN KEY (`scrap_id`) REFERENCES `master_scrap` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `scrap_stock_ibfk_1` FOREIGN KEY (`scrap_id`) REFERENCES `master_scrap` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `subkon_in_details`
--
ALTER TABLE `subkon_in_details`
  ADD CONSTRAINT `fk_subkon_in_header` FOREIGN KEY (`subkon_in_id`) REFERENCES `subkon_in_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `subkon_order_bom`
--
ALTER TABLE `subkon_order_bom`
  ADD CONSTRAINT `fk_subkon_bom_header` FOREIGN KEY (`subkon_order_id`) REFERENCES `subkon_order_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `subkon_order_outputs`
--
ALTER TABLE `subkon_order_outputs`
  ADD CONSTRAINT `fk_subkon_output_header` FOREIGN KEY (`subkon_order_id`) REFERENCES `subkon_order_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `subkon_out_details`
--
ALTER TABLE `subkon_out_details`
  ADD CONSTRAINT `fk_subkon_out_header` FOREIGN KEY (`subkon_out_id`) REFERENCES `subkon_out_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Ketidakleluasaan untuk tabel `warehouse_in_detail`
--
ALTER TABLE `warehouse_in_detail`
  ADD CONSTRAINT `warehouse_in_detail_ibfk_1` FOREIGN KEY (`header_id`) REFERENCES `warehouse_in_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `wo_details_material`
--
ALTER TABLE `wo_details_material`
  ADD CONSTRAINT `wo_details_material_ibfk_1` FOREIGN KEY (`wo_header_id`) REFERENCES `wo_header` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `wo_header`
--
ALTER TABLE `wo_header`
  ADD CONSTRAINT `wo_header_ibfk_1` FOREIGN KEY (`sales_contract_id`) REFERENCES `sales_contracts` (`id`),
  ADD CONSTRAINT `wo_header_ibfk_2` FOREIGN KEY (`fg_id`) REFERENCES `finished_goods` (`id`),
  ADD CONSTRAINT `wo_header_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `wo_header_ibfk_5` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
