-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Waktu pembuatan: 02 Feb 2026 pada 01.37
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mingbao_erp_local`
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
(18, 8, 347, 'material', 1.0000, NULL),
(19, 8, 342, 'material', 1.0000, NULL),
(20, 8, 348, 'material', 1.0000, NULL),
(21, 8, 336, 'material', 1.0000, NULL),
(22, 8, 305, 'material', 1.0000, NULL);

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
(8, 4, NULL, 1, NULL, 'Active', 4, '2026-01-30 08:15:29', '2026-01-30 08:17:11'),
(9, 3, NULL, 1, NULL, 'Draft', 4, '2026-01-30 14:24:03', '2026-01-30 14:24:03');

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
(34, '1.102.001.00', 'Bank', 10, 'Aset', 'IDR', 0, 'active', 'Debit'),
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
  `group_description` varchar(255) DEFAULT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `common_code`
--

INSERT INTO `common_code` (`id`, `group_code`, `group_description`, `code`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SATUAN', NULL, 'PCS', 'Pieces', 'active', '2025-10-16 08:32:16', NULL),
(2, 'SATUAN', NULL, 'ROLL', 'Roll', 'active', '2025-10-16 08:32:16', NULL),
(5, 'CURRENCY', 'Mata Uang', 'USD', 'Dollar Amerika', 'active', '2025-10-16 08:32:16', '2026-01-22 14:37:31'),
(6, 'CURRENCY', 'Mata Uang', 'IDR', 'Rupiah', 'active', '2025-10-16 08:32:16', '2026-01-22 14:37:31'),
(8, 'BC_TYPE', 'bc number', 'BC 3.0', 'BC 3.0', 'active', '2025-10-16 09:16:09', '2025-12-01 15:27:11'),
(9, 'BC_TYPE', 'bc number', 'BC 4.0', 'BC 4.0', 'active', '2025-10-16 09:23:16', '2025-12-01 15:27:11'),
(10, 'BC_TYPE', 'bc number', 'BC 4.1', 'BC 4.1', 'active', '2025-10-16 10:57:31', '2025-12-01 15:27:11'),
(11, 'SEMIFINISHED_CATEGORY', NULL, 'INSOLE', 'Insole', 'active', '2025-10-16 14:23:03', NULL),
(12, 'SEMIFINISHED_CATEGORY', NULL, 'OUTSOLE', 'Outsole', 'active', '2025-10-16 14:23:03', NULL),
(13, 'SEMIFINISHED_CATEGORY', NULL, 'KAIN', 'Kain', 'active', '2025-10-16 14:23:03', NULL),
(14, 'SEMIFINISHED_CATEGORY', NULL, 'UPPER', 'Upper', 'active', '2025-10-16 14:23:03', NULL),
(15, 'SEMIFINISHED_CATEGORY', NULL, 'LINING', 'Lining', 'active', '2025-10-16 14:23:03', NULL),
(16, 'FINISHED_CATEGORY', NULL, 'SEPATU', 'FOOTWEAR', 'active', '2025-10-16 14:42:26', '2025-12-01 15:15:33'),
(17, 'MATERIAL_CATEGORY', NULL, 'UPPER', 'UPPER', 'active', '2025-10-17 08:28:12', NULL),
(18, 'ITEM_ORIGIN', NULL, 'LOKAL', 'LOKAL', 'active', '2025-10-17 08:29:30', NULL),
(19, 'ITEM_ORIGIN', NULL, 'IMPORT', 'IMPORT', 'active', '2025-10-17 08:29:38', NULL),
(20, 'SUPPLIER_TYPE', NULL, 'LOKAL', 'LOKAL', 'active', '2025-10-17 08:33:03', NULL),
(21, 'SUPPLIER_TYPE', NULL, 'IMPOR', 'IMPOR', 'active', '2025-10-17 08:33:11', NULL),
(22, 'CUSTOMER_TYPE', 'Asal Customer', 'LOKAL', 'LOKAL', 'active', '2025-10-17 08:36:32', '2026-01-22 14:37:47'),
(23, 'CUSTOMER_TYPE', 'Asal Customer', 'IMPOR', 'IMPOR', 'active', '2025-10-17 08:36:44', '2026-01-22 14:37:47'),
(24, 'MACHINE_CATEGORY', NULL, 'CUTE', 'MACHINE ASSEMBLYING', 'active', '2025-10-17 08:42:10', NULL),
(25, 'INVENTORY_CATEGORY', NULL, 'ATK', 'ALAT TULIS', 'active', '2025-10-17 08:44:17', NULL),
(26, 'INVENTORY_CONDITION', NULL, 'NEW', 'BARU', 'active', '2025-10-17 08:44:58', NULL),
(27, 'SHIPPING_MODE', NULL, 'TRUCKING', 'TRUCK', 'active', '2025-10-17 09:09:27', NULL),
(28, 'PAYMENT_MODE', NULL, 'CASH', 'CASH', 'active', '2025-10-17 09:09:39', NULL),
(29, 'PAYMENT_TYPE', NULL, 'NORMAL', 'Normal Payment', 'active', '2025-10-17 10:37:23', NULL),
(30, 'PAYMENT_TYPE', NULL, 'FOC', 'Free of Charge (FOC)', 'active', '2025-10-17 10:37:23', NULL),
(31, 'MATERIAL_CATEGORY', NULL, 'KAIN', 'KAIN', 'active', '2025-10-22 16:06:26', NULL),
(32, 'MATERIAL_CATEGORY', NULL, 'INSOLE', 'INSOLE', 'active', '2025-10-22 16:06:36', NULL),
(33, 'MATERIAL_CATEGORY', NULL, 'OUTSOLE', 'OUTSOLE', 'active', '2025-10-22 16:06:52', NULL),
(34, 'CURRENCY', 'Mata Uang', 'RMB', 'RMB', 'active', '2025-10-23 14:49:25', '2026-01-22 14:37:31'),
(35, 'MATERIAL_CATEGORY', NULL, 'Benang', 'Benang', 'active', '2025-10-30 11:23:47', NULL),
(36, 'BC_TYPE', NULL, 'BC 2.6.1', 'BC 2.6.1', 'active', '2025-12-01 15:27:27', NULL),
(37, 'CUSTOMER_TYPE', 'Asal Customer', 'EKSPOR', 'EKSPOR', 'active', '2025-12-07 15:16:06', '2026-01-22 14:37:47'),
(38, 'FINISHED_CATEGORY', NULL, 'FOOTWEAR', 'FOOTWEAR', 'active', '2025-12-07 15:16:28', NULL),
(39, 'FINISHED_CATEGORY', NULL, 'SLIPPER', 'SLIPPER', 'active', '2025-12-07 15:16:55', NULL),
(40, 'MATERIAL_CATEGORY', NULL, 'SIZE LABEL', 'SIZE LABEL', 'active', '2025-12-07 15:17:28', NULL),
(41, 'MATERIAL_CATEGORY', NULL, 'CARTON', 'CARTON', 'active', '2025-12-07 15:18:05', NULL),
(42, 'MATERIAL_CATEGORY', NULL, 'BOX', 'BOX', 'active', '2025-12-07 15:18:14', NULL),
(43, 'SATUAN', NULL, 'PAIRS', 'PAIRS', 'active', '2025-12-07 15:19:38', NULL),
(44, 'SATUAN', NULL, 'KG', 'KG', 'active', '2025-12-07 15:19:44', NULL),
(45, 'SATUAN', NULL, 'YARD', 'YARD', 'active', '2025-12-07 15:19:51', NULL),
(46, 'MATERIAL_CATEGORY', NULL, 'Accesories', 'Accesories', 'active', '2025-12-07 15:36:29', NULL),
(47, 'BC_TYPE', NULL, 'BC 2.3', 'BC 2.3', 'active', '2026-01-08 11:23:52', NULL),
(48, 'BC_TYPE', NULL, 'BC 2.6.2', 'BC 2.6.2', 'active', '2026-01-08 11:24:05', NULL),
(49, 'MACHINE_CATEGORY', NULL, 'HVM', 'HEAVY MACHINERY', 'active', '2026-01-09 08:13:35', NULL),
(50, 'MACHINE_CATEGORY', NULL, 'LGM', 'LIGHT MACHINERY', 'active', '2026-01-09 08:13:49', NULL),
(51, 'INVENTORY_CATEGORY', NULL, 'EQP', 'PERALATAN KANTOR', 'active', '2026-01-09 08:15:58', NULL),
(52, 'INVENTORY_CATEGORY', NULL, 'SUP', 'PERLENGKAPAN KANTOR', 'active', '2026-01-09 08:16:10', NULL),
(53, 'INVENTORY_CATEGORY', NULL, 'W-EQP', 'PERALATAN PRODUKSI - GUDANG', 'active', '2026-01-09 08:16:32', NULL),
(54, 'INVENTORY_CATEGORY', NULL, 'W-SUP', 'PERLENGKAPAN PRODUKSI - GUDANG', 'active', '2026-01-09 08:16:48', NULL),
(55, 'INVENTORY_CATEGORY', NULL, 'OP-EQP', 'PERALATAN OPERASIONAL - UMUM', 'active', '2026-01-09 08:17:27', NULL),
(56, 'INVENTORY_CATEGORY', NULL, 'OP-SUP', 'PERLENGKAPAN OPERASIONAL -  UMUM', 'active', '2026-01-09 08:17:53', NULL),
(57, 'SATUAN', NULL, 'UNIT', 'UNIT', 'active', '2026-01-09 08:19:51', NULL),
(58, 'MATERIAL_CATEGORY', NULL, 'MIDSOLE', 'MIDSOLE', 'active', '2026-01-29 07:47:18', NULL),
(59, 'MATERIAL_CATEGORY', NULL, 'OUTSIDE', 'OUTSIDE', 'active', '2026-01-29 07:47:35', NULL);

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
(6, 'EKS-0001', 'FLOOS', 'Ekspor', '', 'Hongkong J Street 45', '', 'floosslipper@floos.com', 'active', '2025-12-07 15:28:17', '2025-12-07 15:28:17'),
(7, 'LOK-0005', 'PT FOREVER YOUNG INTERNATIONAL', 'Lokal', '0503902116515000', 'Jatengland Industrial Park Sayung, Blok H1, Batu Krajan, Batu, Karangtengah, Demak Regency, Central Java 59561', '', '', 'active', '2026-01-06 16:02:21', '2026-01-06 16:02:21'),
(8, 'IMP-0001', 'PT.FOREVER YOUNG INTERNATIONAL', '', '0503902116515000', 'Jatengland Industrial Park Sayung, Blok H1, Batu Krajan, Batu, Karangtengah, Demak Regency, Central Java 59561', '', '', 'inactive', '2026-01-27 15:54:46', '2026-01-27 15:56:08'),
(9, 'LOK-0006', 'MACHMUD', 'Lokal', '', 'Jl. PODOREJO KALIANCAR KECAMATAN NGALIYAN KOTA SEMARANG', '085709329384', '', 'active', '2026-01-30 08:39:28', '2026-01-30 08:39:28');

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
(19, 24, NULL, 16, 4, 98.0000, NULL, ''),
(20, 25, NULL, 17, 4, 98.0000, NULL, '');

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
  `shipped_by_user` int(11) DEFAULT NULL COMMENT 'User ID yg konfirmasi',
  `exim_verified` tinyint(1) DEFAULT 0 COMMENT 'Status verifikasi EXIM (0=belum, 1=sudah)',
  `exim_verified_by` int(11) DEFAULT NULL COMMENT 'User ID yang verifikasi',
  `exim_verified_at` datetime DEFAULT NULL COMMENT 'Tanggal waktu verifikasi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `fg_delivery_header`
--

INSERT INTO `fg_delivery_header` (`id`, `delivery_number`, `delivery_date`, `sales_order_id`, `sales_contract_id`, `customer_id`, `bc_type_id`, `bc_number`, `bc_date`, `shipping_method`, `shipping_address`, `remarks`, `status`, `created_at`, `updated_at`, `shipped_at`, `shipped_by_user`, `exim_verified`, `exim_verified_by`, `exim_verified_at`) VALUES
(24, 'DO/202601/0001', '2026-01-30', NULL, NULL, 7, '47', '763345', '2026-01-31', 'Truck', 'Jatengland Industrial Park Sayung, Blok H1, Batu Krajan, Batu, Karangtengah, Demak Regency, Central Java 59561', NULL, 'Confirmed', '2026-01-30 01:37:01', '2026-01-30 01:37:27', '2026-01-30 08:37:11', 4, 1, 4, '2026-01-30 08:37:27'),
(25, 'DO/202601/0002', '2026-01-30', NULL, NULL, 7, '47', '092733', '2026-01-31', 'Truck', 'Jatengland Industrial Park Sayung, Blok H1, Batu Krajan, Batu, Karangtengah, Demak Regency, Central Java 59561', NULL, 'Confirmed', '2026-01-30 07:45:36', '2026-01-30 07:46:06', '2026-01-30 14:45:56', 4, 1, 4, '2026-01-30 14:46:06');

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
(13, 12, 73, 4, 98.0000, '[{\"size\":\"39\",\"qty\":98}]', 'PCS', ''),
(14, 13, 77, 4, 98.0000, '[{\"size\":\"40\",\"qty\":98}]', 'PCS', '');

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
(12, 'GRN-FG/202601/0001', '2026-01-30', 18, 19, 16, 4, '', 'Confirmed', '2026-01-30 01:35:14', '2026-01-30 01:35:20', '2026-01-30 08:35:20', 4),
(13, 'GRN-FG/202601/0002', '2026-01-30', 19, 20, 17, 4, '', 'Confirmed', '2026-01-30 07:44:10', '2026-01-30 07:44:32', '2026-01-30 14:44:32', 4);

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
(2, 'FG-SEP-LOK-0001', 'PMJ6000', 'SEPATU', 'LOKAL', 'PCS', 'active', '2025-10-17 08:40:04', '2025-10-17 08:40:04'),
(3, 'FG-SEP-IMP-0001', 'FLOOS-FL45', 'SEPATU', 'IMPORT', 'PCS', 'active', '2025-12-01 15:28:53', '2025-12-01 15:28:53'),
(4, 'FG-SLI-IMP-0001', 'FLOOS-JM67', 'SLIPPER', 'IMPORT', 'PCS', 'active', '2025-12-07 15:19:15', '2025-12-07 15:19:15');

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
(41, 29, 15, 43, 'MIR', 348, 'material', 100.00, 2.00, 200.00),
(42, 29, 15, 44, 'MIR', 336, 'material', 100.00, 0.20, 20.00),
(43, 29, 15, 41, 'MIR', 347, 'material', 100.00, 0.20, 20.00),
(44, 29, 15, 42, 'MIR', 342, 'material', 100.00, 0.20, 20.00),
(45, 29, 15, 45, 'MIR', 305, 'material', 100.00, 0.40, 40.00),
(46, 30, 16, 46, 'MIR', 3, 'machine', 1.00, 120.00, 120.00),
(47, 31, 29, 38, 'PO', 316, 'material', 12.00, 10000.00, 120000.00),
(48, 32, 17, 47, 'MIR', 347, 'material', 100.00, 20.00, 2000.00),
(49, 32, 17, 48, 'MIR', 342, 'material', 100.00, 2.00, 200.00),
(50, 32, 17, 49, 'MIR', 348, 'material', 100.00, 1.00, 100.00),
(51, 32, 17, 50, 'MIR', 336, 'material', 100.00, 1.00, 100.00),
(52, 32, 17, 51, 'MIR', 305, 'material', 100.00, 2.00, 200.00);

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `exim_verified` tinyint(1) DEFAULT 0 COMMENT 'Status verifikasi EXIM (0=belum, 1=sudah)',
  `exim_verified_by` int(11) DEFAULT NULL COMMENT 'User ID yang verifikasi',
  `exim_verified_at` datetime DEFAULT NULL COMMENT 'Tanggal waktu verifikasi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `grn_header`
--

INSERT INTO `grn_header` (`id`, `grn_number`, `grn_date`, `base_on`, `warehouse_type`, `supplier_id`, `delivery_note_number`, `bc_type_code`, `bc_date`, `bc_number`, `remarks`, `status`, `confirmed_by`, `confirmed_at`, `created_by`, `created_at`, `updated_at`, `exim_verified`, `exim_verified_by`, `exim_verified_at`) VALUES
(29, 'GRN/202601/0001', '2026-01-30', 'MIR', 'material', 4, '1622/65757', 'BC 3.0', '2026-01-30', '9016283', '', 'Completed', 4, '2026-01-30 08:23:40', 4, '2026-01-30 08:21:54', '2026-01-30 08:33:55', 1, 4, '2026-01-30 08:33:55'),
(30, 'GRN/202601/0002', '2026-01-30', 'MIR', 'machine', 4, '1622/65757/MACHINE', 'BC 2.3', '2026-01-31', '895364', '', 'Completed', 4, '2026-01-30 08:48:10', 4, '2026-01-30 08:43:59', '2026-01-30 08:48:10', 1, 4, '2026-01-30 08:44:58'),
(31, 'GRN/202601/0003', '2026-01-30', 'PO', 'material', 115, '1622/65757', 'BC 2.3', '2026-01-30', '122222', '', 'Completed', 4, '2026-01-30 09:16:40', 4, '2026-01-30 09:16:26', '2026-01-30 09:17:01', 1, 4, '2026-01-30 09:17:01'),
(32, 'GRN/202601/0004', '2026-01-30', 'MIR', 'material', 4, 'SJ/IMPORT/001', 'BC 3.0', '2026-01-31', '087732', '', 'Completed', 4, '2026-01-30 14:32:12', 4, '2026-01-30 14:30:29', '2026-01-30 14:32:30', 1, 4, '2026-01-30 14:32:30'),
(33, 'GRN/202602/0001', '2026-02-02', 'MIR', 'material', NULL, '1622/65757', 'BC 2.6.1', '2026-02-04', '099812', '', 'Draft', NULL, NULL, 4, '2026-02-02 07:17:15', '2026-02-02 07:17:15', 0, NULL, NULL);

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
(3, 'INV-ATK-0001', 'BULPEN', 'ATK', 'PCS', '', 'Office', 'NEW', 'Active', '2025-11-06 11:24:44', '2025-11-06 11:24:44'),
(4, 'INV-INV-0001', 'KOMPUTER SET', 'EQP', 'UNIT', 'LG', 'OFFICE', 'NEW', 'Active', '2026-01-09 08:20:34', '2026-01-09 08:20:34');

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
(55, 28, 32, 0.00, 400000.00, 0.00, 400000.00, ''),
(56, 28, 104, 400000.00, 0.00, 400000.00, 0.00, 'Lembur Hari Minggu Tanggal 14 dan 18 Desember 2025'),
(57, 29, 113, 450000.00, 0.00, 450000.00, 0.00, 'Biaya Penggantian Filter Solar Mobil H 1327 YX'),
(58, 29, 32, 0.00, 450000.00, 0.00, 450000.00, ''),
(59, 30, 113, 250000.00, 0.00, 250000.00, 0.00, 'Service Mobil - Reset Indikator Spedometer Mobil Innova'),
(60, 30, 32, 0.00, 250000.00, 0.00, 250000.00, ''),
(61, 31, 109, 150000.00, 0.00, 150000.00, 0.00, 'Pulsa Miss Zheng Siyu'),
(62, 31, 32, 0.00, 150000.00, 0.00, 150000.00, ''),
(63, 32, 110, 268000.00, 0.00, 268000.00, 0.00, 'Pembelian Keperluan Rumah BSB'),
(64, 32, 32, 0.00, 268000.00, 0.00, 268000.00, ''),
(65, 33, 127, 500000.00, 0.00, 500000.00, 0.00, ''),
(66, 33, 32, 0.00, 500000.00, 0.00, 500000.00, ''),
(67, 34, 125, 400000.00, 0.00, 400000.00, 0.00, 'Biaya muat kontainer : Konsumsi'),
(68, 34, 32, 0.00, 400000.00, 0.00, 400000.00, ''),
(69, 35, 125, 2000000.00, 0.00, 2000000.00, 0.00, 'Biaya Operasional (Beli Buah)'),
(70, 35, 32, 0.00, 2000000.00, 0.00, 2000000.00, ''),
(71, 36, 125, 116050.00, 0.00, 116050.00, 0.00, 'Beli Buah'),
(72, 36, 125, 36600.00, 0.00, 36600.00, 0.00, ''),
(73, 36, 103, 20000.00, 0.00, 20000.00, 0.00, ''),
(74, 36, 32, 0.00, 172650.00, 0.00, 172650.00, ''),
(75, 37, 103, 1000000.00, 0.00, 1000000.00, 0.00, 'Top Up e-tol kartu 7546 0000 4979 8248'),
(76, 37, 32, 0.00, 1000000.00, 0.00, 1000000.00, ''),
(77, 38, 125, 416300.00, 0.00, 416300.00, 0.00, 'Beli Roti, Buah, Yakult'),
(78, 38, 32, 0.00, 416300.00, 0.00, 416300.00, 'Beli Roti, Buah, Yakult');

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
(28, 'JU/202601/00001', '2026-01-02', 'Lembur Hari Minggu Tanggal 14 dan 18 Desember 2025', 'IDR', 1.0000, 'Manual', NULL, 400000.00, 400000.00, 400000.00, 400000.00, 'Posted', 16, '2026-01-05 09:39:04', 4, '2026-01-05 11:29:09'),
(29, 'JU/202601/00002', '2026-01-02', 'Biaya Penggantian Filter Solar Mobil H 1327 YX', 'IDR', 1.0000, 'Manual', NULL, 450000.00, 450000.00, 450000.00, 450000.00, 'Posted', 16, '2026-01-05 09:40:38', 16, '2026-01-05 13:53:54'),
(30, 'JU/202601/00003', '2026-01-02', 'Service Mobil - Reset Indikator Spedometer Mobil Innova', 'IDR', 1.0000, 'Manual', NULL, 250000.00, 250000.00, 250000.00, 250000.00, 'Posted', 16, '2026-01-05 09:41:14', 16, '2026-01-05 13:54:03'),
(31, 'JU/202601/00004', '2026-01-03', 'Pulsa Miss Zheng Siyu', 'IDR', 1.0000, 'Manual', NULL, 150000.00, 150000.00, 150000.00, 150000.00, 'Posted', 16, '2026-01-05 09:43:01', 16, '2026-01-05 13:54:10'),
(32, 'JU/202601/00005', '2026-01-03', 'Pembelian Keperluan Rumah BSB', 'IDR', 1.0000, 'Manual', NULL, 268000.00, 268000.00, 268000.00, 268000.00, 'Posted', 16, '2026-01-05 09:45:31', 16, '2026-01-05 13:54:17'),
(33, 'JU/202601/00006', '2026-01-03', 'Bensin mobil  H1327YX ', 'IDR', 1.0000, 'Manual', NULL, 500000.00, 500000.00, 500000.00, 500000.00, 'Posted', 16, '2026-01-05 09:46:15', 16, '2026-01-05 13:54:22'),
(34, 'JU/202601/00007', '2026-01-03', 'Biaya muat kontainer : Konsumsi', 'IDR', 1.0000, 'Manual', NULL, 400000.00, 400000.00, 400000.00, 400000.00, 'Posted', 16, '2026-01-05 09:47:32', 16, '2026-01-05 13:54:35'),
(35, 'JU/202601/00008', '2026-01-05', 'Biaya Operasional (Beli Buah)', 'IDR', 1.0000, 'Manual', NULL, 2000000.00, 2000000.00, 2000000.00, 2000000.00, 'Posted', 16, '2026-01-05 09:49:53', 16, '2026-01-05 13:54:40'),
(36, 'JU/202601/00009', '2026-01-05', 'Beli Buah, Minuman, dan Parkir', 'IDR', 1.0000, 'Manual', NULL, 172650.00, 172650.00, 172650.00, 172650.00, 'Posted', 16, '2026-01-05 09:52:05', 16, '2026-01-05 13:54:45'),
(37, 'JU/202601/00010', '2026-01-05', 'Top Up e-tol kartu 7546 0000 4979 8248', 'IDR', 1.0000, 'Manual', NULL, 1000000.00, 1000000.00, 1000000.00, 1000000.00, 'Posted', 16, '2026-01-05 09:52:40', 16, '2026-01-05 13:55:01'),
(38, 'JU/202601/00011', '2025-06-17', 'Beli Roti, Buah, Yakult', 'IDR', 1.0000, 'Manual', NULL, 416300.00, 416300.00, 416300.00, 416300.00, 'Posted', 16, '2026-01-05 14:00:58', 16, '2026-01-05 14:39:17');

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

--
-- Dumping data untuk tabel `machines`
--

INSERT INTO `machines` (`id`, `kode_mesin`, `nama_mesin`, `kategori`, `merk`, `tahun`, `lokasi`, `satuan`, `status`, `created_at`, `updated_at`) VALUES
(3, 'CUT-0001', 'Heating Machine', 'CUTE', 'MJ', '2026', 'ASSEMBLYNG', 'UNIT', 'Active', '2026-01-30 08:43:00', '2026-01-30 08:43:00');

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
(1, 'SCRP-0001', 'SCRAP SISA PRODUKSI', 'KG', 'active', '2025-10-17 18:22:46', '2025-12-07 15:24:21'),
(2, 'SCRP-0002', 'SCRAP KARTON', 'KG', 'active', '2025-12-07 15:25:37', '2025-12-07 15:25:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `materials`
--

CREATE TABLE `materials` (
  `id` int(11) NOT NULL,
  `kode_material` varchar(20) NOT NULL,
  `nama_material` varchar(100) NOT NULL,
  `kategori` varchar(50) DEFAULT 'Lainnya' COMMENT 'Material category from common_code.MATERIAL_CATEGORY',
  `asal` varchar(20) DEFAULT 'Lokal' COMMENT 'Origin: Lokal/Impor/Import from common_code.ITEM_ORIGIN',
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
(11, 'BEL-0001', 'Benang Jait', 'Benang', 'Lokal', 'PCS', 'active', '2025-10-30 11:24:11'),
(12, 'SII-0001', 'Tag Size ', 'SIZE LABEL', 'IMPORT', 'PCS', 'active', '2025-12-07 15:34:29'),
(13, 'UPI-0001', 'Straps', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2025-12-07 15:35:16'),
(14, 'INI-0001', 'Insole', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2025-12-07 15:35:40'),
(15, 'OUI-0001', 'Outsole', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2025-12-07 15:35:55'),
(16, 'ACI-0001', 'Papper Accesories', 'Accesories', 'IMPORT', 'PCS', 'active', '2025-12-07 15:36:53'),
(17, 'BOI-0001', 'Inner Box', 'BOX', 'IMPORT', 'PCS', 'active', '2025-12-07 15:37:07'),
(18, 'CAI-0001', 'Carton Box', 'CARTON', 'IMPORT', 'PCS', 'active', '2025-12-07 15:37:29'),
(19, 'UPI-0002', 'VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081 ', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:52:52'),
(20, 'UPI-0003', 'VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512081', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:53:37'),
(21, 'UPI-0004', 'VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512081', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:53:56'),
(22, 'UPI-0005', 'VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512081', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:54:12'),
(23, 'UPI-0006', 'VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512081', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:54:44'),
(24, 'UPI-0007', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512081', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:55:25'),
(25, 'UPI-0008', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512081', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:55:43'),
(26, 'UPI-0009', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512081', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:55:54'),
(27, 'UPI-0010', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512081', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:56:11'),
(28, 'UPI-0011', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512081', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:56:33'),
(29, 'INI-0002', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'inactive', '2026-01-21 15:57:43'),
(30, 'INI-0003', 'INSOLE中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:58:07'),
(31, 'INI-0004', 'INSOLE中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:59:12'),
(32, 'INI-0005', 'INSOLE中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:59:28'),
(33, 'INI-0006', 'INSOLE中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 15:59:48'),
(34, 'INI-0007', 'INSOLE中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:00:00'),
(35, 'INI-0008', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:00:33'),
(36, 'INI-0009', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:00:43'),
(37, 'INI-0010', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:01:01'),
(38, 'INI-0011', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:01:20'),
(39, 'INI-0012', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512081', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:01:31'),
(40, 'OUI-0002', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:03:00'),
(41, 'OUI-0003', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512081', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:03:27'),
(42, 'OUI-0004', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512081', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:03:45'),
(43, 'OUI-0005', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512081', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:03:56'),
(44, 'OUI-0006', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512081', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-21 16:04:07'),
(45, 'OUI-0007', 'OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512081', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-22 08:10:16'),
(46, 'OUI-0008', 'OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512081', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-22 08:10:36'),
(47, 'OUI-0009', 'OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512081', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-22 08:10:56'),
(48, 'OUI-0010', 'OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512081', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-22 08:11:13'),
(49, 'ACI-0002', 'REIFORCE中底补强带-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:16:39'),
(50, 'ACI-0003', 'SUPER DRY 5GRAM 防潮剂-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:20:05'),
(51, 'ACI-0004', 'BAND彩条-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:33:32'),
(52, 'ACI-0005', 'BAND彩条-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:33:57'),
(53, 'ACI-0006', 'BAND彩条-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:34:13'),
(54, 'ACI-0007', 'BAND彩条-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:34:27'),
(55, 'ACI-0008', 'BAND彩条-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:34:39'),
(56, 'ACI-0009', 'BAND彩条-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:35:00'),
(57, 'ACI-0010', 'BAND彩条-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:35:11'),
(58, 'ACI-0011', 'BAND彩条-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:35:22'),
(59, 'ACI-0012', 'BAND彩条-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:35:35'),
(60, 'ACI-0013', 'BAND彩条-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-22 08:35:48'),
(61, 'ACI-0014', '中底补强带reinforce-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-28 13:37:26'),
(62, 'ACI-0015', '5g防潮剂SUPER DRY-PO 采购订单 2512081', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-28 13:38:03'),
(63, 'UPI-0012', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:46:56'),
(64, 'UPI-0013', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:47:17'),
(65, 'UPI-0014', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:47:34'),
(66, 'UPI-0015', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:47:47'),
(67, 'UPI-0016', 'VAMP带面-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:48:01'),
(68, 'UPI-0017', 'VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:55:13'),
(69, 'UPI-0018', 'VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:55:37'),
(70, 'UPI-0019', 'VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:56:13'),
(71, 'UPI-0020', 'VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:56:32'),
(72, 'UPI-0021', 'VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512086', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 13:57:31'),
(73, 'INI-0013', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:00:32'),
(74, 'INI-0014', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:01:02'),
(75, 'INI-0015', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:01:24'),
(76, 'INI-0016', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:01:40'),
(77, 'INI-0017', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:01:56'),
(78, 'INI-0018', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:02:11'),
(79, 'INI-0019', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:02:24'),
(80, 'INI-0020', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:03:05'),
(81, 'INI-0021', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:03:16'),
(82, 'INI-0022', 'INSOLE中底-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512086', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:03:26'),
(83, 'OUI-0011', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:07:46'),
(84, 'OUI-0012', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:08:34'),
(85, 'OUI-0013', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:08:51'),
(86, 'OUI-0014', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:09:06'),
(87, 'OUI-0015', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:09:57'),
(88, 'OUI-0016', 'OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:10:18'),
(89, 'OUI-0017', 'OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:11:09'),
(90, 'OUI-0018', 'OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:11:24'),
(91, 'OUI-0019', 'OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:11:36'),
(92, 'OUI-0020', 'OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512086', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:11:51'),
(93, 'ACI-0016', '中底补强带reinforce-PO 采购订单 2512086', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-28 14:20:23'),
(94, 'ACI-0017', 'Polybag塑料袋-PO 采购订单 2512086', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-28 14:20:45'),
(95, 'ACI-0018', '2g防潮剂SUPER DRY-PO 采购订单 2512086', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-28 14:21:10'),
(96, 'ACI-0019', '塑料袋贴标Polybag Label-BLACK黑色-PO 采购订单 2512086', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-28 14:21:33'),
(97, 'ACI-0020', '塑料袋贴标Polybag Label-BROWN画眉棕-PO 采购订单 2512086', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-28 14:22:20'),
(98, 'ACI-0021', '绿色外箱贴标Carton Label-绿色Green-PO 采购订单 2512086', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-28 14:22:54'),
(99, 'UPI-0022', 'VAMP带面-BLACK黑色-SIZE 尺寸 5 -PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:39:29'),
(100, 'UPI-0023', 'VAMP带面-BLACK黑色-SIZE 尺寸 6 -PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:39:47'),
(101, 'UPI-0024', 'VAMP带面-BLACK黑色-SIZE 尺寸 7 -PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:40:09'),
(102, 'UPI-0025', 'VAMP带面-BLACK黑色-SIZE 尺寸 8 -PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:40:23'),
(103, 'UPI-0026', 'VAMP带面-BLACK黑色-SIZE 尺寸 9 -PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:40:39'),
(104, 'UPI-0027', 'VAMP带面-BLACK黑色-SIZE 尺寸 10 -PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-28 14:40:57'),
(105, 'INI-0023', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:48:34'),
(106, 'INI-0024', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:48:49'),
(107, 'INI-0025', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:49:24'),
(108, 'INI-0026', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:49:36'),
(109, 'INI-0027', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:49:48'),
(110, 'MII-0001', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:50:37'),
(111, 'MII-0002', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:50:50'),
(112, 'MII-0003', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:57:58'),
(113, 'MII-0004', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:58:10'),
(114, 'MII-0005', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 07:58:27'),
(115, 'UPI-0028', 'VAMP带面-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:07:57'),
(116, 'UPI-0029', 'VAMP带面-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:08:11'),
(117, 'UPI-0030', 'VAMP带面-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:08:26'),
(118, 'UPI-0031', 'VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:08:40'),
(119, 'UPI-0032', 'VAMP带面-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512168', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:08:58'),
(120, 'OUI-0021', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512168', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:10:18'),
(121, 'OUI-0022', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512168', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:10:29'),
(122, 'OUI-0023', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512168', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:10:41'),
(123, 'OUI-0024', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512168', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:10:53'),
(124, 'OUI-0025', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512168', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:11:05'),
(125, 'MII-0006', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:14:31'),
(126, 'MII-0007', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:15:02'),
(127, 'MII-0008', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:16:29'),
(128, 'MII-0009', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:18:23'),
(129, 'MII-0010', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512168', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:18:37'),
(130, 'INI-0028', 'INSOLE 中底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:21:08'),
(131, 'INI-0029', 'INSOLE 中底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:21:21'),
(132, 'INI-0030', 'INSOLE 中底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:21:37'),
(133, 'INI-0031', 'INSOLE 中底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:21:47'),
(134, 'INI-0032', 'INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512168', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:21:58'),
(135, 'OUI-0026', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512168', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:23:02'),
(136, 'OUI-0027', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512168', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:23:17'),
(137, 'OUI-0028', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512168', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:23:31'),
(138, 'OUI-0029', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512168', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:23:47'),
(139, 'OUI-0030', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512168', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:23:56'),
(140, 'UPI-0033', 'VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:30:01'),
(141, 'UPI-0034', 'VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:30:15'),
(142, 'UPI-0035', 'VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:30:35'),
(143, 'UPI-0036', 'VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:30:47'),
(144, 'UPI-0037', 'VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:30:57'),
(145, 'MII-0011', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:36:05'),
(146, 'MII-0012', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:36:16'),
(147, 'MII-0013', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:36:26'),
(148, 'MII-0014', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:36:36'),
(149, 'MII-0015', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:36:46'),
(150, 'INI-0033', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:37:01'),
(151, 'INI-0034', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:37:18'),
(152, 'INI-0035', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:37:27'),
(153, 'INI-0036', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:37:41'),
(154, 'INI-0037', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:37:55'),
(155, 'OUI-0031', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:39:36'),
(156, 'OUI-0032', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:39:54'),
(157, 'OUI-0033', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:40:05'),
(158, 'OUI-0034', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:40:19'),
(159, 'OUI-0035', 'OUTSIDE大底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:40:34'),
(160, 'UPI-0038', 'VAMP带面-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:41:54'),
(161, 'UPI-0039', 'VAMP带面-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:42:12'),
(162, 'UPI-0040', 'VAMP带面-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:42:52'),
(163, 'UPI-0041', 'VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:43:13'),
(164, 'UPI-0042', 'VAMP带面-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:43:23'),
(165, 'MII-0016', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:45:00'),
(166, 'MII-0017', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:45:12'),
(167, 'MII-0018', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:45:23'),
(168, 'MII-0019', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:45:36'),
(169, 'MII-0020', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:45:51'),
(170, 'INI-0038', 'INSOLE 中底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:48:46'),
(171, 'INI-0039', 'INSOLE 中底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:49:00'),
(172, 'INI-0040', 'INSOLE 中底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:49:13'),
(173, 'INI-0041', 'INSOLE 中底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:49:24'),
(174, 'INI-0042', 'INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:49:34'),
(175, 'OUI-0036', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:49:52'),
(176, 'OUI-0037', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:50:05'),
(177, 'OUI-0038', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:50:20'),
(178, 'OUI-0039', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:50:30'),
(179, 'OUI-0040', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 08:50:41'),
(180, 'UPI-0043', 'VAMP带面-BLACK黑色-SIZE 尺寸 5-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:05:12'),
(181, 'UPI-0044', 'VAMP带面-BLACK黑色-SIZE 尺寸 11-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:05:30'),
(182, 'MII-0021', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 5-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:06:18'),
(183, 'MII-0022', 'MIDSOLE距台-BLACK黑色-SIZE 尺寸 11-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:06:29'),
(184, 'INI-0043', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 5-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:06:56'),
(185, 'INI-0044', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 11-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:07:07'),
(186, 'UPI-0045', 'VAMP带面-PINK粉色-SIZE 尺寸 5-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:07:37'),
(187, 'UPI-0046', 'VAMP带面-PINK粉色-SIZE 尺寸 11-PO 采购订单 2512169', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:07:47'),
(188, 'MII-0023', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:08:09'),
(189, 'MII-0024', 'MIDSOLE距台-PINK粉色-SIZE 尺寸 11-PO 采购订单 2512169', 'MIDSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:08:20'),
(190, 'INI-0045', 'INSOLE 中底-PINK粉色-SIZE 尺寸 5-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:08:43'),
(191, 'INI-0046', 'INSOLE 中底-PINK粉色-SIZE 尺寸 11-PO 采购订单 2512169', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:08:52'),
(192, 'OUI-0041', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 5-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:09:15'),
(193, 'OUI-0042', 'OUTSIDE大底-PINK粉色-SIZE 尺寸 11-PO 采购订单 2512169', 'OUTSIDE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:09:25'),
(194, 'UPI-0047', 'VAMP带面-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601010', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:12:29'),
(195, 'UPI-0048', 'VAMP带面-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601010', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:12:44'),
(196, 'UPI-0049', 'VAMP带面-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601010', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:13:13'),
(197, 'UPI-0050', 'VAMP带面-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601010', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:13:35'),
(198, 'OUI-0043', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 22601010', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:41:39'),
(199, 'OUI-0044', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 22601010', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:42:02'),
(200, 'OUI-0045', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 22601010', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:42:17'),
(201, 'OUI-0046', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 22601010', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:42:30'),
(202, 'UPI-0051', 'VAMP带面-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601011', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:45:45'),
(203, 'UPI-0052', 'VAMP带面-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601011', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:46:00'),
(204, 'UPI-0053', 'VAMP带面-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601011', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:46:29'),
(205, 'UPI-0054', 'VAMP带面-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601011', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:46:48'),
(206, 'OUI-0047', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 22601011', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:48:32'),
(207, 'OUI-0048', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 22601011', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:48:45'),
(208, 'OUI-0049', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 22601011', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:49:08'),
(209, 'OUI-0050', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 22601011', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:49:20'),
(210, 'UPI-0055', 'VAMP带面-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:51:20'),
(211, 'UPI-0056', 'VAMP带面-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:51:37'),
(212, 'UPI-0057', 'VAMP带面-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:51:59'),
(213, 'UPI-0058', 'VAMP带面-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:52:15'),
(214, 'OUI-0051', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:56:15'),
(215, 'OUI-0052', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:56:29'),
(216, 'OUI-0053', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:56:44'),
(217, 'OUI-0054', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:56:58'),
(218, 'UPI-0059', 'VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 12/13-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:58:18'),
(219, 'UPI-0060', 'VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 1/2-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 09:58:46'),
(220, 'UPI-0061', 'VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 3/4-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:04:00'),
(221, 'UPI-0062', 'VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 5/6-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:04:20'),
(222, 'OUI-0055', 'OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 12/13-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:05:02'),
(223, 'OUI-0056', 'OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 1/2-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:05:16'),
(224, 'OUI-0057', 'OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 3/4-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:05:29'),
(225, 'OUI-0058', 'OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 5/6-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:05:47'),
(226, 'UPI-0063', 'VAMP带面-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:09:04'),
(227, 'UPI-0064', 'VAMP带面-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:09:20'),
(228, 'UPI-0065', 'VAMP带面-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:09:41'),
(229, 'UPI-0066', 'VAMP带面-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601012', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:10:20'),
(230, 'OUI-0059', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:12:12'),
(231, 'OUI-0060', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:12:40'),
(232, 'OUI-0061', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:13:40'),
(233, 'OUI-0062', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601012', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:14:14'),
(234, 'ACI-0022', 'Polybag塑料袋-PO 采购订单 2601012', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 10:15:14'),
(235, 'UPI-0067', 'VAMP带面-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:24:59'),
(236, 'UPI-0068', 'VAMP带面-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:25:19'),
(237, 'UPI-0069', 'VAMP带面-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:25:33'),
(238, 'UPI-0070', 'VAMP带面-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:25:47'),
(239, 'UPI-0071', 'VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 12/13-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:29:48'),
(240, 'UPI-0072', 'VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 1/2-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:30:13'),
(241, 'UPI-0073', 'VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 3/4-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:30:26'),
(242, 'UPI-0074', 'VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 5/6-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:30:37'),
(243, 'OUI-0063', 'OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 12/13-PO 采购订单 2601013', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:35:28'),
(244, 'OUI-0064', 'OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 1/2-PO 采购订单 2601013', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:35:55'),
(245, 'OUI-0065', 'OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 3/4-PO 采购订单 2601013', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:36:09'),
(246, 'OUI-0066', 'OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 5/6-PO 采购订单 2601013', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:36:30'),
(247, 'UPI-0075', 'VAMP带面-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:37:09'),
(248, 'UPI-0076', 'VAMP带面-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:37:22'),
(249, 'UPI-0077', 'VAMP带面-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:37:39'),
(250, 'UPI-0078', 'VAMP带面-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:37:53'),
(251, 'OUI-0067', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601013', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:38:56'),
(252, 'OUI-0068', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601013', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:39:09'),
(253, 'OUI-0069', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601013', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:39:23'),
(254, 'OUI-0070', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601013', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:39:34'),
(255, 'ACI-0023', 'Polybag塑料袋-PO 采购订单 2601013', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 10:40:57'),
(256, 'ACI-0024', 'Carton Label外箱贴标-PO 采购订单 2601013', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 10:44:55'),
(257, 'UPI-0079', 'VAMP带面-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'inactive', '2026-01-29 10:47:07'),
(258, 'UPI-0080', 'VAMP带面-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'inactive', '2026-01-29 10:47:52'),
(259, 'UPI-0081', 'VAMP带面-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'inactive', '2026-01-29 10:48:15'),
(260, 'UPI-0082', 'VAMP带面-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601013', 'UPPER', 'IMPORT', 'PAIRS', 'inactive', '2026-01-29 10:48:45'),
(261, 'UPI-0083', 'VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:53:14'),
(262, 'UPI-0084', 'VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:53:27'),
(263, 'UPI-0085', 'VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:53:38'),
(264, 'UPI-0086', 'VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:53:52'),
(265, 'UPI-0087', 'VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:54:15'),
(266, 'INI-0047', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:57:48'),
(267, 'INI-0048', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:58:06'),
(268, 'INI-0049', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:58:20'),
(269, 'INI-0050', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:58:33'),
(270, 'INI-0051', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:58:47'),
(271, 'OUI-0071', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:59:32'),
(272, 'OUI-0072', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:59:43'),
(273, 'OUI-0073', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 10:59:54'),
(274, 'OUI-0074', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:00:11'),
(275, 'OUI-0075', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:00:20'),
(276, 'UPI-0088', 'VAMP带面-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:02:18'),
(277, 'UPI-0089', 'VAMP带面-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:02:29'),
(278, 'UPI-0090', 'VAMP带面-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:02:43'),
(279, 'UPI-0091', 'VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:02:56'),
(280, 'UPI-0092', 'VAMP带面-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:03:06'),
(281, 'INI-0052', 'INSOLE 中底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:03:49'),
(282, 'INI-0053', 'INSOLE 中底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:04:03'),
(283, 'INI-0054', 'INSOLE 中底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:04:15'),
(284, 'INI-0055', 'INSOLE 中底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:04:25'),
(285, 'INI-0056', 'INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:04:39'),
(286, 'OUI-0076', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:05:08'),
(287, 'OUI-0077', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:05:19'),
(288, 'OUI-0078', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:05:30'),
(289, 'OUI-0079', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:05:43'),
(290, 'OUI-0080', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 11:05:52'),
(291, 'ACI-0025', 'BAND彩带-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:06:46'),
(292, 'ACI-0026', 'BAND彩带-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:06:57'),
(293, 'ACI-0027', 'BAND彩带-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:07:08'),
(294, 'ACI-0028', 'BAND彩带-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:07:19'),
(295, 'ACI-0029', 'BAND彩带-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:07:29'),
(296, 'ACI-0030', 'BAND彩带-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:07:41'),
(297, 'ACI-0031', 'BAND彩带-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:08:44'),
(298, 'ACI-0032', 'BAND彩带-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:08:55'),
(299, 'ACI-0033', 'BAND彩带-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:09:05'),
(300, 'ACI-0034', 'BAND彩带-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:09:18'),
(301, 'ACI-0035', 'BAND彩带-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:09:28'),
(302, 'ACI-0036', 'UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:10:24'),
(303, 'ACI-0037', 'UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:10:36'),
(304, 'ACI-0038', 'UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:10:46'),
(305, 'ACI-0039', 'UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:10:57'),
(306, 'ACI-0040', 'UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:11:05'),
(307, 'ACI-0041', 'UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:11:39'),
(308, 'ACI-0042', 'UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:11:50'),
(309, 'ACI-0043', 'UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:12:05'),
(310, 'ACI-0044', 'UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:12:16'),
(311, 'ACI-0045', 'UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:12:26'),
(312, 'ACI-0046', 'COO Hangtag材质吊牌-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:12:57'),
(313, 'ACI-0047', 'Plastic tie 9英寸子母扣-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:14:11'),
(314, 'ACI-0048', 'Carton Pink Label粉色外箱贴标-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:14:54'),
(315, 'ACI-0049', 'Carton Label唛头贴纸-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:15:19'),
(316, 'ACI-0050', 'Marking Sticker唛头贴纸-PO 采购订单 2602001', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 11:16:08'),
(317, 'UPI-0093', 'VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 13:01:46'),
(318, 'UPI-0094', 'VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 13:02:05'),
(319, 'UPI-0095', 'VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 13:02:32'),
(320, 'UPI-0096', 'VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 13:02:55'),
(321, 'UPI-0097', 'VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 13:03:39'),
(322, 'INI-0057', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:51:51'),
(323, 'INI-0058', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:52:28'),
(324, 'INI-0059', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:52:39'),
(325, 'INI-0060', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:52:48'),
(326, 'INI-0061', 'INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:53:02'),
(327, 'OUI-0081', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:55:04'),
(328, 'OUI-0082', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:55:16'),
(329, 'OUI-0083', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:55:26'),
(330, 'OUI-0084', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:55:36'),
(331, 'OUI-0085', 'OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:55:49'),
(332, 'UPI-0098', 'VAMP带面-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:56:18'),
(333, 'UPI-0099', 'VAMP带面-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:56:30'),
(334, 'UPI-0100', 'VAMP带面-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:56:42'),
(335, 'UPI-0101', 'VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:57:19'),
(336, 'UPI-0102', 'VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:57:30'),
(337, 'UPI-0103', 'VAMP带面-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002', 'UPPER', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:57:42'),
(338, 'INI-0062', 'INSOLE 中底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:58:13'),
(339, 'INI-0063', 'INSOLE 中底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:58:28'),
(340, 'INI-0064', 'INSOLE 中底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:59:33'),
(341, 'INI-0065', 'INSOLE 中底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:59:45'),
(342, 'INI-0066', 'INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002', 'INSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 14:59:56'),
(343, 'OUI-0086', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 15:03:59'),
(344, 'OUI-0087', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 15:04:10'),
(345, 'OUI-0088', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 15:04:23'),
(346, 'OUI-0089', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 15:04:35'),
(347, 'OUI-0090', 'OUTSOLE鞋底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002', 'OUTSOLE', 'IMPORT', 'PAIRS', 'active', '2026-01-29 15:04:47'),
(348, 'ACI-0051', 'Ploybag塑料袋-PO 采购订单 2602002', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 15:05:33'),
(349, 'ACI-0052', 'Carton Pink Label粉色外箱贴标-PO 采购订单 2602002', 'Accesories', 'IMPORT', 'PCS', 'active', '2026-01-29 15:06:10');

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
(56, 'Arus Kas', 'cash_flow', 53),
(57, 'Mutasi Mesin', 'lapmutasimesin', 54),
(58, 'PO Report', 'po_report', 55),
(59, 'exim verif', 'exim_verification', 56);

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
  `allowance` decimal(15,4) DEFAULT 0.0000,
  `total_qty` decimal(15,4) GENERATED ALWAYS AS (`qty` + `allowance`) STORED,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mir_details`
--

INSERT INTO `mir_details` (`id`, `mir_header_id`, `item_id`, `item_type`, `sales_contract_id`, `qty`, `allowance`, `remarks`) VALUES
(41, 15, 347, 'material', 16, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(42, 15, 342, 'material', 16, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(43, 15, 348, 'material', 16, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(44, 15, 336, 'material', 16, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(45, 15, 305, 'material', 16, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(46, 16, 3, 'machine', NULL, 1.00, 0.0000, ''),
(47, 17, 347, 'material', 17, 100.00, 2.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(48, 17, 342, 'material', 17, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(49, 17, 348, 'material', 17, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(50, 17, 336, 'material', 17, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(51, 17, 305, 'material', 17, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(52, 18, 347, 'material', 17, 100.00, 12.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(53, 18, 342, 'material', 17, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(54, 18, 348, 'material', 17, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(55, 18, 336, 'material', 17, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67'),
(56, 18, 305, 'material', 17, 100.00, 0.0000, 'Auto dari BOM FG: FLOOS-JM67');

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
(15, 'MIR/202601/0001', '2026-01-30', 4, 'FOC', 'USD', 4, '', 'Approved', 4, '2026-01-30 08:21:07', '2026-01-30 08:14:59', '2026-01-30 08:21:07'),
(16, 'MIR/202601/0002', '2026-01-30', 4, 'FOC', 'USD', 4, '', 'Approved', 4, '2026-01-30 08:43:27', '2026-01-30 08:42:25', '2026-01-30 08:43:27'),
(17, 'MIR/202601/0003', '2026-01-30', 4, 'FOC', 'USD', 4, '', 'Approved', 4, '2026-01-30 14:29:46', '2026-01-30 14:19:16', '2026-01-30 14:29:46'),
(18, 'MIR/202602/0001', '2026-02-02', 119, 'FOC', 'USD', 4, '', 'Approved', 4, '2026-02-02 07:16:51', '2026-02-02 07:16:32', '2026-02-02 07:16:51');

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
(39, 23, 19, 305, 'material', 'PCS', 100.0000, 100.0000, 0.0000, ''),
(40, 23, 19, 336, 'material', 'PAIRS', 100.0000, 100.0000, 0.0000, ''),
(41, 24, 19, 342, 'material', 'PAIRS', 100.0000, 100.0000, 0.0000, ''),
(42, 24, 19, 347, 'material', 'PAIRS', 100.0000, 100.0000, 0.0000, ''),
(43, 25, 19, 348, 'material', 'PCS', 98.0000, 98.0000, 0.0000, ''),
(44, 26, 20, 305, 'material', 'PCS', 100.0000, 100.0000, 0.0000, ''),
(45, 26, 20, 336, 'material', 'PAIRS', 100.0000, 100.0000, 0.0000, ''),
(46, 27, 20, 347, 'material', 'PAIRS', 100.0000, 100.0000, 0.0000, ''),
(47, 27, 20, 342, 'material', 'PAIRS', 100.0000, 100.0000, 0.0000, ''),
(48, 28, 20, 348, 'material', 'PCS', 98.0000, 98.0000, 0.0000, '');

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
(23, 'MR/202601/0001', 'MI/202601/0001', '2026-01-30', 'Received', '', 4, '2026-01-30 08:27:26', 4, '2026-01-30 08:27:48', 4, '2026-01-30 08:28:22', '2026-01-30 08:26:13', '2026-01-30 08:28:22'),
(24, 'MR/202601/0002', 'MI/202601/0002', '2026-01-30', 'Received', '', 4, '2026-01-30 08:29:54', 4, '2026-01-30 08:30:03', 4, '2026-01-30 08:30:22', '2026-01-30 08:29:20', '2026-01-30 08:30:22'),
(25, 'MR/202601/0003', 'MI/202601/0003', '2026-01-30', 'Received', '', 4, '2026-01-30 08:32:01', 4, '2026-01-30 08:32:08', 4, '2026-01-30 08:32:32', '2026-01-30 08:31:36', '2026-01-30 08:32:32'),
(26, 'MR/202601/0004', 'MI/202601/0004', '2026-01-30', 'Received', '', 4, '2026-01-30 14:35:52', 4, '2026-01-30 14:36:19', 4, '2026-01-30 14:36:52', '2026-01-30 14:34:58', '2026-01-30 14:36:52'),
(27, 'MR/202601/0005', 'MI/202601/0005', '2026-01-30', 'Received', '', 4, '2026-01-30 14:40:04', 4, '2026-01-30 14:40:16', 4, '2026-01-30 14:40:26', '2026-01-30 14:39:30', '2026-01-30 14:40:26'),
(28, 'MR/202601/0006', 'MI/202601/0006', '2026-01-30', 'Received', '', 4, '2026-01-30 14:42:32', 4, '2026-01-30 14:42:43', 4, '2026-01-30 14:42:55', '2026-01-30 14:42:03', '2026-01-30 14:42:55');

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
(38, 29, 316, 'material', NULL, '', 12.00, 10000.00, 10000.00, 120000.00, 0.00);

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
  `status` enum('Draft','Submitted','Closed','Confirmed','Cancelled') DEFAULT 'Draft',
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
(29, 'PO/202601/0001', 115, '2026-01-30', 'TRUCKING', 'CASH', 'IDR', 1.0000, 0, '', 'Confirmed', 4, '2026-01-30 09:15:48', 4, '2026-01-30 09:15:26', '2026-01-30 09:15:48');

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
(18, 'PROD/202601/0001', 19, 4, 100, '2026-01-30', 'Completed', '', 4, 4, '2026-01-30 08:33:18', '2026-01-30 08:28:39', '2026-01-30 08:33:41'),
(19, 'PROD/202601/0002', 20, 4, 100, '2026-01-30', 'Completed', '', 4, 4, '2026-01-30 14:43:44', '2026-01-30 14:37:48', '2026-01-30 14:43:48');

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
(69, 18, 'preparation', 305, 'material', NULL, NULL, NULL, 100.0000, '', '2026-01-30 08:28:44'),
(70, 18, 'preparation', 336, 'material', NULL, NULL, NULL, 100.0000, '', '2026-01-30 08:28:59'),
(71, 18, 'assembling', 1, 'semifinished', NULL, NULL, 70, 98.0000, '', '2026-01-30 08:30:51'),
(72, 18, 'assembling', 342, 'material', NULL, NULL, NULL, 98.0000, '', '2026-01-30 08:30:57'),
(73, 18, 'assembling', 347, 'material', NULL, NULL, NULL, 98.0000, '', '2026-01-30 08:31:02'),
(74, 18, 'finishing', 4, 'semifinished', NULL, NULL, 71, 98.0000, '', '2026-01-30 08:32:44'),
(75, 18, 'finishing', 348, 'material', NULL, NULL, NULL, 98.0000, '', '2026-01-30 08:32:52'),
(76, 19, 'preparation', 305, 'material', NULL, NULL, NULL, 98.0000, '', '2026-01-30 14:38:05'),
(77, 19, 'preparation', 336, 'material', NULL, NULL, NULL, 98.0000, '', '2026-01-30 14:38:21'),
(78, 19, 'assembling', 1, 'semifinished', NULL, NULL, 74, 98.0000, '', '2026-01-30 14:39:13'),
(79, 19, 'assembling', 342, 'material', NULL, NULL, NULL, 98.0000, '', '2026-01-30 14:40:52'),
(80, 19, 'assembling', 347, 'material', NULL, NULL, NULL, 98.0000, '', '2026-01-30 14:41:00'),
(81, 19, 'finishing', 4, 'semifinished', NULL, NULL, 75, 98.0000, '', '2026-01-30 14:41:50'),
(82, 19, 'finishing', 348, 'material', NULL, NULL, NULL, 98.0000, '', '2026-01-30 14:43:15');

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
(70, 18, 'preparation', 1, 'semifinished', 100.0000, 0.0000, NULL, 4, '2026-01-30 08:29:08', 4, '2026-01-30 08:30:40', '2026-01-30 08:29:08'),
(71, 18, 'assembling', 4, 'semifinished', 98.0000, 0.0000, NULL, 4, '2026-01-30 08:31:13', 4, '2026-01-30 08:31:26', '2026-01-30 08:31:13'),
(72, 18, 'assembling', 1, 'scrap', 0.0000, 1.0000, NULL, 4, '2026-01-30 08:31:21', 4, '2026-01-30 08:31:29', '2026-01-30 08:31:21'),
(73, 18, 'finishing', 4, 'finished_goods', 98.0000, 0.0000, '[{\"size\":\"39\",\"qty\":98}]', 4, NULL, 4, '2026-01-30 08:33:14', '2026-01-30 08:33:08'),
(74, 19, 'preparation', 1, 'semifinished', 98.0000, 0.0000, NULL, 4, '2026-01-30 14:38:33', 4, '2026-01-30 14:38:53', '2026-01-30 14:38:33'),
(75, 19, 'assembling', 4, 'semifinished', 98.0000, 0.0000, NULL, 4, '2026-01-30 14:41:18', 4, '2026-01-30 14:41:33', '2026-01-30 14:41:18'),
(76, 19, 'assembling', 1, 'scrap', 0.0000, 1.0000, NULL, 4, '2026-01-30 14:41:27', 4, '2026-01-30 14:41:38', '2026-01-30 14:41:27'),
(77, 19, 'finishing', 4, 'finished_goods', 98.0000, 0.0000, '[{\"size\":\"40\",\"qty\":98}]', 4, NULL, 4, '2026-01-30 14:43:37', '2026-01-30 14:43:31');

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
(24, 73, '39', 98.0000),
(25, 77, '40', 98.0000);

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

--
-- Dumping data untuk tabel `prod_return_detail`
--

INSERT INTO `prod_return_detail` (`id`, `prod_return_id`, `mr_detail_id`, `material_id`, `item_type`, `qty_return`, `reason`) VALUES
(54, 56, NULL, 342, 'material', 2.00, ''),
(55, 56, NULL, 347, 'material', 2.00, ''),
(56, 57, NULL, 305, 'material', 2.00, ''),
(57, 57, NULL, 336, 'material', 2.00, ''),
(58, 57, NULL, 342, 'material', 2.00, ''),
(59, 57, NULL, 347, 'material', 2.00, '');

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

--
-- Dumping data untuk tabel `prod_return_header`
--

INSERT INTO `prod_return_header` (`id`, `prod_return_no`, `return_date`, `work_order_id`, `notes`, `returned_by`, `confirmed_by`, `confirmed_at`, `received_by`, `status`, `created_at`, `updated_at`) VALUES
(56, 'RFP-202601-001', '2026-01-30', 19, '', 4, 4, '2026-01-30 08:41:24', NULL, 'Confirmed', '2026-01-30 08:41:21', '2026-01-30 08:41:24'),
(57, 'RFP-202601-002', '2026-01-30', 20, '', 4, 4, '2026-01-30 14:47:04', NULL, 'Confirmed', '2026-01-30 14:47:00', '2026-01-30 14:47:04');

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
(4, 'Staf Gudang', 'Full access to all modules', 'active'),
(5, 'bc', 'Bea Cukai', 'active'),
(6, 'Purchasing', 'Purchase', 'active'),
(7, 'Production', 'Production', 'active'),
(8, 'Accounting', 'accounting', 'active'),
(9, 'FG', 'Finishgoods', 'active'),
(10, 'WH', 'Warehouse', 'active'),
(11, 'QC', 'Qc', 'active'),
(12, 'PPIC 2', 'PPIC', 'active');

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
(1852, 3, 1, 1, 1, 1, 1),
(1853, 3, 2, 1, 1, 1, 1),
(1854, 3, 3, 1, 1, 1, 1),
(1855, 3, 4, 1, 1, 1, 1),
(1856, 3, 5, 1, 1, 1, 1),
(1857, 3, 6, 1, 1, 1, 1),
(1858, 3, 7, 1, 1, 1, 1),
(1859, 3, 8, 1, 1, 1, 1),
(1860, 3, 9, 1, 1, 1, 1),
(1861, 3, 10, 1, 1, 1, 1),
(1862, 3, 11, 1, 1, 1, 1),
(1863, 3, 12, 1, 1, 1, 1),
(1864, 3, 14, 1, 1, 1, 1),
(1865, 3, 13, 1, 1, 1, 1),
(1866, 3, 15, 1, 1, 1, 1),
(1867, 3, 16, 1, 1, 1, 1),
(1868, 3, 17, 1, 1, 1, 1),
(1869, 3, 18, 1, 1, 1, 1),
(1870, 3, 19, 1, 1, 1, 1),
(1871, 3, 20, 1, 1, 1, 1),
(1872, 3, 21, 1, 1, 1, 1),
(1873, 3, 23, 1, 1, 1, 1),
(1874, 3, 22, 1, 1, 1, 1),
(1875, 3, 24, 1, 1, 1, 1),
(1876, 3, 25, 1, 1, 1, 1),
(1877, 3, 26, 1, 1, 1, 1),
(1878, 3, 27, 1, 1, 1, 1),
(1879, 3, 28, 1, 1, 1, 1),
(1880, 3, 29, 1, 1, 1, 1),
(1881, 3, 30, 1, 1, 1, 1),
(1882, 3, 31, 1, 1, 1, 1),
(1883, 3, 32, 1, 1, 1, 1),
(1884, 3, 33, 1, 1, 1, 1),
(1885, 3, 34, 1, 1, 1, 1),
(1886, 3, 35, 1, 1, 1, 1),
(1887, 3, 36, 1, 1, 1, 1),
(1888, 3, 37, 1, 1, 1, 1),
(1889, 3, 38, 1, 1, 1, 1),
(1890, 3, 39, 1, 1, 1, 1),
(1891, 3, 40, 1, 1, 1, 1),
(1892, 3, 41, 1, 1, 1, 1),
(1893, 3, 42, 1, 1, 1, 1),
(1894, 3, 43, 1, 1, 1, 1),
(1895, 3, 44, 1, 1, 1, 1),
(1896, 3, 45, 1, 1, 1, 1),
(1897, 3, 46, 1, 1, 1, 1),
(1898, 3, 47, 1, 1, 1, 1),
(1899, 3, 48, 1, 1, 1, 1),
(1900, 3, 49, 1, 1, 1, 1),
(1901, 3, 50, 1, 1, 1, 1),
(1902, 3, 51, 1, 1, 1, 1),
(1903, 3, 52, 1, 1, 1, 1),
(1904, 3, 53, 1, 1, 1, 1),
(1905, 3, 54, 1, 1, 1, 1),
(1906, 3, 55, 1, 1, 1, 1),
(1907, 3, 56, 1, 1, 1, 1),
(1908, 3, 57, 1, 1, 1, 1),
(1909, 3, 58, 1, 1, 1, 1),
(1910, 3, 59, 1, 1, 1, 1),
(1918, 5, 30, 1, 0, 0, 0),
(1919, 5, 31, 1, 0, 0, 0),
(1920, 5, 32, 1, 0, 0, 0),
(1921, 5, 36, 1, 0, 0, 0),
(1922, 5, 37, 1, 0, 0, 0),
(1923, 5, 40, 1, 0, 0, 0),
(1924, 5, 44, 1, 0, 0, 0),
(1925, 5, 57, 1, 0, 0, 0),
(1926, 6, 1, 1, 1, 1, 1),
(1927, 6, 4, 1, 1, 1, 1),
(1928, 6, 5, 1, 1, 1, 1),
(1929, 6, 6, 1, 1, 1, 1),
(1930, 6, 7, 1, 1, 1, 1),
(1931, 6, 8, 1, 1, 1, 1),
(1932, 6, 9, 1, 1, 1, 1),
(1933, 6, 10, 1, 1, 1, 1),
(1934, 6, 11, 1, 1, 1, 1),
(1935, 6, 12, 1, 1, 1, 1),
(1936, 6, 14, 1, 1, 1, 1),
(1937, 6, 13, 1, 1, 1, 1),
(1938, 6, 16, 1, 1, 1, 1),
(1939, 6, 17, 1, 1, 1, 1),
(1940, 6, 18, 1, 1, 1, 1),
(1941, 6, 58, 1, 1, 1, 1),
(1942, 7, 1, 1, 1, 1, 1),
(1943, 7, 4, 1, 1, 1, 1),
(1944, 7, 5, 1, 1, 1, 1),
(1945, 7, 6, 1, 1, 1, 1),
(1946, 7, 7, 1, 1, 1, 1),
(1947, 7, 8, 1, 1, 1, 1),
(1948, 7, 19, 1, 1, 1, 1),
(1949, 7, 21, 1, 1, 1, 1),
(1950, 7, 23, 1, 1, 1, 1),
(1951, 7, 22, 1, 1, 1, 1),
(1952, 7, 24, 1, 1, 1, 1),
(1953, 7, 25, 1, 1, 1, 1),
(1954, 7, 26, 1, 0, 0, 0),
(1955, 7, 27, 1, 0, 0, 0),
(1956, 7, 28, 1, 0, 0, 0),
(1957, 7, 29, 1, 1, 1, 1),
(1958, 7, 33, 1, 0, 0, 0),
(1959, 7, 34, 1, 0, 0, 0),
(1960, 7, 35, 1, 0, 0, 0),
(2020, 9, 8, 1, 1, 1, 1),
(2021, 9, 19, 1, 1, 1, 1),
(2022, 9, 24, 1, 0, 0, 0),
(2023, 9, 26, 1, 1, 1, 1),
(2024, 9, 27, 1, 1, 1, 1),
(2025, 9, 28, 1, 1, 1, 1),
(2026, 9, 37, 1, 0, 0, 0),
(2067, 11, 13, 1, 1, 1, 1),
(2068, 11, 23, 1, 1, 1, 1),
(2069, 11, 24, 1, 1, 1, 0),
(2070, 10, 1, 1, 1, 1, 1),
(2071, 10, 4, 1, 1, 1, 1),
(2072, 10, 5, 1, 1, 1, 1),
(2073, 10, 6, 1, 1, 1, 1),
(2074, 10, 7, 1, 1, 1, 1),
(2075, 10, 8, 1, 1, 1, 1),
(2076, 10, 9, 1, 1, 1, 1),
(2077, 10, 10, 1, 1, 1, 1),
(2078, 10, 11, 1, 1, 1, 1),
(2079, 10, 12, 1, 1, 1, 1),
(2080, 10, 14, 1, 0, 0, 0),
(2081, 10, 13, 1, 1, 1, 1),
(2082, 10, 16, 1, 0, 0, 0),
(2083, 10, 17, 1, 1, 1, 1),
(2084, 10, 18, 1, 1, 1, 1),
(2085, 10, 19, 1, 0, 0, 0),
(2086, 10, 20, 1, 0, 0, 0),
(2087, 10, 21, 1, 1, 1, 1),
(2088, 10, 23, 1, 1, 1, 1),
(2089, 10, 22, 1, 1, 1, 1),
(2090, 10, 24, 1, 0, 0, 0),
(2091, 10, 25, 1, 1, 1, 1),
(2092, 10, 26, 1, 0, 0, 0),
(2093, 10, 27, 1, 0, 0, 0),
(2094, 10, 28, 1, 0, 0, 0),
(2095, 10, 29, 1, 1, 1, 1),
(2096, 10, 30, 1, 0, 0, 0),
(2097, 10, 31, 1, 0, 0, 0),
(2098, 10, 32, 1, 0, 0, 0),
(2099, 10, 33, 1, 1, 1, 1),
(2100, 10, 34, 1, 1, 1, 1),
(2101, 10, 35, 1, 1, 1, 1),
(2102, 10, 36, 1, 0, 0, 0),
(2103, 10, 37, 1, 0, 0, 0),
(2104, 10, 38, 1, 0, 0, 0),
(2105, 10, 39, 1, 0, 0, 0),
(2106, 10, 40, 1, 0, 0, 0),
(2107, 10, 41, 1, 1, 1, 1),
(2108, 10, 42, 1, 1, 1, 1),
(2109, 10, 43, 1, 1, 1, 1),
(2110, 10, 44, 1, 0, 0, 0),
(2111, 10, 57, 1, 0, 0, 0),
(2112, 10, 58, 1, 0, 0, 0),
(2113, 10, 59, 1, 0, 0, 0),
(2586, 8, 1, 1, 1, 1, 1),
(2587, 8, 2, 1, 0, 0, 0),
(2588, 8, 3, 1, 0, 0, 0),
(2589, 8, 4, 1, 0, 0, 0),
(2590, 8, 5, 1, 0, 0, 0),
(2591, 8, 6, 1, 0, 0, 0),
(2592, 8, 7, 1, 0, 0, 0),
(2593, 8, 8, 1, 0, 0, 0),
(2594, 8, 9, 1, 1, 1, 1),
(2595, 8, 10, 1, 1, 1, 1),
(2596, 8, 11, 1, 1, 1, 1),
(2597, 8, 12, 1, 1, 1, 1),
(2598, 8, 14, 1, 1, 1, 1),
(2599, 8, 13, 1, 0, 0, 0),
(2600, 8, 15, 1, 0, 0, 0),
(2601, 8, 16, 1, 0, 0, 0),
(2602, 8, 17, 1, 0, 0, 0),
(2603, 8, 18, 1, 0, 0, 0),
(2604, 8, 19, 1, 0, 0, 0),
(2605, 8, 20, 1, 0, 0, 0),
(2606, 8, 21, 1, 0, 0, 0),
(2607, 8, 23, 1, 0, 0, 0),
(2608, 8, 22, 1, 0, 0, 0),
(2609, 8, 24, 1, 0, 0, 0),
(2610, 8, 25, 1, 0, 0, 0),
(2611, 8, 26, 1, 0, 0, 0),
(2612, 8, 27, 1, 0, 0, 0),
(2613, 8, 28, 1, 0, 0, 0),
(2614, 8, 29, 1, 0, 0, 0),
(2615, 8, 30, 1, 0, 0, 0),
(2616, 8, 31, 1, 0, 0, 0),
(2617, 8, 32, 1, 0, 0, 0),
(2618, 8, 33, 1, 0, 0, 0),
(2619, 8, 34, 1, 0, 0, 0),
(2620, 8, 35, 1, 0, 0, 0),
(2621, 8, 36, 1, 0, 0, 0),
(2622, 8, 37, 1, 0, 0, 0),
(2623, 8, 38, 1, 0, 0, 0),
(2624, 8, 39, 1, 0, 0, 0),
(2625, 8, 40, 1, 0, 0, 0),
(2626, 8, 41, 1, 0, 0, 0),
(2627, 8, 42, 1, 0, 0, 0),
(2628, 8, 43, 1, 0, 0, 0),
(2629, 8, 44, 1, 1, 1, 1),
(2630, 8, 45, 1, 1, 1, 1),
(2631, 8, 46, 1, 1, 1, 1),
(2632, 8, 47, 1, 1, 1, 1),
(2633, 8, 48, 1, 1, 1, 1),
(2634, 8, 49, 1, 1, 1, 1),
(2635, 8, 50, 1, 1, 1, 1),
(2636, 8, 51, 1, 1, 1, 1),
(2637, 8, 52, 1, 1, 1, 1),
(2638, 8, 53, 1, 1, 1, 1),
(2639, 8, 54, 1, 1, 1, 1),
(2640, 8, 55, 1, 1, 1, 1),
(2641, 8, 56, 1, 1, 1, 1),
(2642, 8, 57, 1, 0, 0, 0),
(2643, 8, 58, 1, 1, 1, 1),
(2644, 8, 59, 1, 0, 0, 0),
(2645, 12, 1, 1, 1, 1, 1),
(2646, 12, 2, 1, 1, 1, 1),
(2647, 12, 4, 1, 1, 1, 1),
(2648, 12, 5, 1, 1, 1, 1),
(2649, 12, 6, 1, 1, 1, 1),
(2650, 12, 7, 1, 1, 1, 1),
(2651, 12, 8, 1, 1, 1, 1),
(2652, 12, 9, 1, 1, 1, 1),
(2653, 12, 10, 1, 1, 1, 1),
(2654, 12, 11, 1, 0, 0, 0),
(2655, 12, 12, 1, 0, 0, 0),
(2656, 12, 14, 1, 0, 0, 0),
(2657, 12, 13, 1, 0, 0, 0),
(2658, 12, 15, 1, 1, 1, 1),
(2659, 12, 16, 1, 0, 0, 0),
(2660, 12, 17, 1, 0, 0, 0),
(2661, 12, 18, 1, 0, 0, 0),
(2662, 12, 19, 1, 1, 1, 1),
(2663, 12, 20, 1, 1, 1, 1),
(2664, 12, 21, 1, 1, 1, 1),
(2665, 12, 23, 1, 0, 0, 0),
(2666, 12, 22, 1, 0, 0, 0),
(2667, 12, 24, 1, 1, 1, 1),
(2668, 12, 25, 1, 0, 0, 0),
(2669, 12, 26, 1, 0, 0, 0),
(2670, 12, 27, 1, 0, 0, 0),
(2671, 12, 28, 1, 0, 0, 0),
(2672, 12, 29, 1, 0, 0, 0),
(2673, 12, 33, 1, 0, 0, 0),
(2674, 12, 34, 1, 0, 0, 0),
(2675, 12, 35, 1, 0, 0, 0),
(2676, 12, 40, 1, 0, 0, 0),
(2677, 12, 41, 1, 0, 0, 0),
(2678, 12, 42, 1, 0, 0, 0),
(2679, 12, 43, 1, 0, 0, 0),
(2680, 12, 57, 1, 0, 0, 0),
(2681, 12, 58, 1, 0, 0, 0),
(2705, 1, 1, 1, 1, 1, 1),
(2706, 1, 2, 1, 1, 1, 1),
(2707, 1, 3, 1, 1, 1, 1),
(2708, 1, 4, 1, 1, 1, 1),
(2709, 1, 5, 1, 1, 1, 1),
(2710, 1, 6, 1, 1, 1, 1),
(2711, 1, 7, 1, 1, 1, 1),
(2712, 1, 8, 1, 1, 1, 1),
(2713, 1, 9, 1, 1, 1, 1),
(2714, 1, 10, 1, 1, 1, 1),
(2715, 1, 11, 1, 1, 1, 1),
(2716, 1, 12, 1, 1, 1, 1),
(2717, 1, 14, 1, 1, 1, 1),
(2718, 1, 13, 1, 1, 1, 1),
(2719, 1, 15, 1, 1, 1, 1),
(2720, 1, 16, 1, 1, 1, 1),
(2721, 1, 17, 1, 1, 1, 1),
(2722, 1, 18, 1, 1, 1, 1),
(2723, 1, 19, 1, 1, 1, 1),
(2724, 1, 20, 1, 1, 1, 1),
(2725, 1, 21, 1, 1, 1, 1),
(2726, 1, 23, 1, 1, 1, 1),
(2727, 1, 22, 1, 1, 1, 1),
(2728, 1, 24, 1, 1, 1, 1),
(2729, 1, 25, 1, 1, 1, 1),
(2730, 1, 26, 1, 1, 1, 1),
(2731, 1, 27, 1, 1, 1, 1),
(2732, 1, 28, 1, 1, 1, 1),
(2733, 1, 29, 1, 1, 1, 1),
(2734, 1, 30, 1, 1, 1, 1),
(2735, 1, 31, 1, 1, 1, 1),
(2736, 1, 32, 1, 1, 1, 1),
(2737, 1, 33, 1, 1, 1, 1),
(2738, 1, 34, 1, 1, 1, 1),
(2739, 1, 35, 1, 1, 1, 1),
(2740, 1, 36, 1, 1, 1, 1),
(2741, 1, 37, 1, 1, 1, 1),
(2742, 1, 38, 1, 1, 1, 1),
(2743, 1, 39, 1, 1, 1, 1),
(2744, 1, 40, 1, 1, 1, 1),
(2745, 1, 41, 1, 1, 1, 1),
(2746, 1, 42, 1, 1, 1, 1),
(2747, 1, 43, 1, 1, 1, 1),
(2748, 1, 44, 1, 1, 1, 1),
(2749, 1, 45, 1, 1, 1, 1),
(2750, 1, 46, 1, 1, 1, 1),
(2751, 1, 47, 1, 1, 1, 1),
(2752, 1, 48, 1, 1, 1, 1),
(2753, 1, 49, 1, 1, 1, 1),
(2754, 1, 50, 1, 1, 1, 1),
(2755, 1, 51, 1, 1, 1, 1),
(2756, 1, 52, 1, 1, 1, 1),
(2757, 1, 53, 1, 1, 1, 1),
(2758, 1, 54, 1, 1, 1, 1),
(2759, 1, 55, 1, 1, 1, 1),
(2760, 1, 56, 1, 1, 1, 1),
(2761, 1, 57, 1, 1, 1, 1),
(2762, 1, 58, 1, 1, 1, 1),
(2763, 1, 59, 1, 1, 1, 1);

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
(16, '430002323', 7, 4, '2026-01-30', '2026-01-31', 100, 'USD', 12.00, 1200.00, 'USAE', 'Open', '2026-01-30 08:13:42', '2026-01-30 08:13:42'),
(17, '4355526663', 8, 4, '2026-01-30', '2026-01-31', 100, 'USD', 12.00, 1200.00, '', 'Open', '2026-01-30 14:16:36', '2026-01-30 14:16:36'),
(18, 'SC/2026/00001', 8, 4, '2026-01-30', '2026-02-05', 120, 'USD', 10.00, 1200.00, '', 'Open', '2026-01-30 14:18:19', '2026-01-30 14:18:19');

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

--
-- Dumping data untuk tabel `scrap_in_detail`
--

INSERT INTO `scrap_in_detail` (`id`, `header_id`, `scrap_id`, `qty`) VALUES
(21, 25, 1, 1.0000);

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

--
-- Dumping data untuk tabel `scrap_in_detail_sources`
--

INSERT INTO `scrap_in_detail_sources` (`id`, `scrap_in_detail_id`, `production_output_id`, `qty_collected`) VALUES
(27, 21, 72, 1.0000);

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

--
-- Dumping data untuk tabel `scrap_in_header`
--

INSERT INTO `scrap_in_header` (`id`, `doc_no`, `doc_date`, `work_order_id`, `notes`, `status`, `created_by`, `confirmed_by`, `confirmed_at`) VALUES
(25, 'SCR-IN/202601/0001', '2026-01-30', NULL, '', 'Confirmed', 4, 4, '2026-01-30 08:35:40');

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

--
-- Dumping data untuk tabel `scrap_out_detail`
--

INSERT INTO `scrap_out_detail` (`id`, `header_id`, `scrap_id`, `qty`, `notes`, `destination`, `sales_value_per_unit`) VALUES
(16, 17, 1, 1.0000, NULL, NULL, 10000.00);

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
  `updated_at` datetime DEFAULT NULL,
  `exim_verified` tinyint(1) DEFAULT 0 COMMENT 'Status verifikasi EXIM (0=belum, 1=sudah)',
  `exim_verified_by` int(11) DEFAULT NULL COMMENT 'User ID yang verifikasi',
  `exim_verified_at` datetime DEFAULT NULL COMMENT 'Tanggal waktu verifikasi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `scrap_out_header`
--

INSERT INTO `scrap_out_header` (`id`, `doc_no`, `doc_date`, `bc_type_code`, `bc_number`, `bc_date`, `destination`, `notes`, `status`, `created_by`, `created_at`, `updated_by`, `confirmed_by`, `confirmed_at`, `updated_at`, `exim_verified`, `exim_verified_by`, `exim_verified_at`) VALUES
(17, 'SCR-OUT/202601/0001', '2026-01-30', 'BC 4.0', '178781', '2026-01-31', '9', '', 'Confirmed', 4, '2026-01-30 08:40:00', NULL, 4, '2026-01-30 08:40:03', NULL, 1, 4, '2026-01-30 08:40:36');

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

--
-- Dumping data untuk tabel `scrap_stock`
--

INSERT INTO `scrap_stock` (`id`, `scrap_id`, `scrap_in_detail_id`, `available_qty`) VALUES
(23, 1, 21, 0.0000);

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
(1, 'SF-KAI-L-0001', 'UPPER', 'KAIN', 'Lokal', 'PCS', 'active', '2025-10-16 14:20:22', '2026-01-23 16:00:29'),
(4, 'SF-OUT-L-0001', 'Sepatu setengah jadi', 'OUTSOLE', 'Lokal', 'PCS', 'active', '2025-10-16 14:33:01', '2026-01-23 16:00:29'),
(5, 'SF-OUT-L-0002', 'Tongue', 'OUTSOLE', 'Lokal', 'ROLL', 'active', '2025-10-17 08:38:23', '2026-01-23 16:00:29'),
(6, 'SF-KAI-L-0002', 'Midle tongue', 'KAIN', 'Lokal', 'PCS', 'active', '2025-10-17 08:38:41', '2026-01-23 16:00:29'),
(7, 'SF-UPP-L-0001', 'UPPER', 'UPPER', 'Lokal', 'PCS', 'active', '2025-12-07 15:26:08', '2025-12-07 15:26:08'),
(8, 'SF-LIN-L-0001', 'PMJ45 Unfinished', 'LINING', 'Lokal', 'PAIRS', 'active', '2025-12-07 15:27:22', '2025-12-07 15:27:22');

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
(4, 'akun_piutang_usaha', 'Akun Piutang Usaha (AR)', 'IDR', 40),
(7, 'akun_ppn_masukan', 'Akun PPN Masukan', 'IDR', 55),
(8, 'akun_ppn_keluaran', 'Akun PPN Keluaran', 'IDR', 71),
(9, 'akun_utang_pph_23', 'Akun Utang PPh 23', 'IDR', 73),
(10, 'akun_grn_clearing', 'Akun Clearing GRN', 'IDR', 42),
(11, 'akun_pendapatan_foc', 'Akun Pendapatan FOC', 'IDR', 43),
(12, 'akun_penjualan_fg', 'Akun Penjualan (Barang Jadi)', 'IDR', 84),
(15, 'akun_penjualan_scrap', 'Akun Penjualan (Scrap)', 'IDR', 85),
(16, 'akun_hpp_fg', 'Akun HPP (Barang Jadi)', 'IDR', 87),
(17, 'akun_persediaan_fg', 'Akun Persediaan (Barang Jadi)', 'IDR', 53),
(26, 'akun_beban_umum', 'Akun Beban Umum', 'IDR', NULL),
(29, 'akun_selisih_kurs', 'Akun Selisih Kurs', 'IDR', 137);

-- --------------------------------------------------------

--
-- Struktur dari tabel `setting_accounting_backup_20251211`
--

CREATE TABLE `setting_accounting_backup_20251211` (
  `id` int(11) NOT NULL DEFAULT 0,
  `setting_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Kode internal, e.g., "akun_piutang_usaha"',
  `setting_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Label di form, e.g., "Akun Piutang Usaha"',
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'IDR',
  `account_id` int(11) DEFAULT NULL COMMENT 'ID dari chart_of_accounts'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `setting_accounting_backup_20251211`
--

INSERT INTO `setting_accounting_backup_20251211` (`id`, `setting_key`, `setting_name`, `currency`, `account_id`) VALUES
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
(11, 'akun_pendapatan_foc', 'Akun Pendapatan FOC', 'IDR', 43),
(12, 'akun_penjualan_fg', 'Akun Penjualan (Barang Jadi)', 'IDR', 84),
(13, 'akun_penjualan_fg', 'Akun Penjualan (Barang Jadi)', 'USD', 38),
(14, 'akun_penjualan_fg', 'Akun Penjualan (Barang Jadi)', 'RMB', NULL),
(15, 'akun_penjualan_scrap', 'Akun Penjualan (Scrap)', 'IDR', 85),
(16, 'akun_hpp_fg', 'Akun HPP (Barang Jadi)', 'IDR', 87),
(17, 'akun_persediaan_fg', 'Akun Persediaan (Barang Jadi)', 'IDR', 53),
(23, 'akun_pembayaran_utang', 'Akun Pembayaran Utang (Kas/Bank)', 'IDR', 35),
(24, 'akun_pembayaran_utang', 'Akun Pembayaran Utang (Kas/Bank)', 'USD', NULL),
(25, 'akun_pembayaran_utang', 'Akun Pembayaran Utang (Kas/Bank)', 'RMB', NULL),
(26, 'akun_beban_umum', 'Akun Beban Umum', 'IDR', NULL),
(27, 'akun_beban_umum', 'Akun Beban Umum', 'USD', NULL),
(28, 'akun_beban_umum', 'Akun Beban Umum', 'RMB', NULL),
(29, 'akun_selisih_kurs', 'Akun Selisih Kurs', 'IDR', NULL);

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
  `source_doc_type` varchar(256) DEFAULT NULL COMMENT 'Optional: PO, MIR, WO, DO',
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
(333, 348, 'material', '2026-01-30 08:23:40', 'in', NULL, NULL, NULL, 29, 'MIR', 15, 100.00, 0.00, 2.00, 100.00, NULL, 71),
(334, 336, 'material', '2026-01-30 08:23:40', 'in', NULL, NULL, NULL, 29, 'MIR', 15, 100.00, 0.00, 0.20, 100.00, NULL, 72),
(335, 347, 'material', '2026-01-30 08:23:40', 'in', NULL, NULL, NULL, 29, 'MIR', 15, 100.00, 0.00, 0.20, 100.00, NULL, 73),
(336, 342, 'material', '2026-01-30 08:23:40', 'in', NULL, NULL, NULL, 29, 'MIR', 15, 100.00, 0.00, 0.20, 100.00, NULL, 74),
(337, 305, 'material', '2026-01-30 08:23:40', 'in', NULL, NULL, NULL, 29, 'MIR', 15, 100.00, 0.00, 0.40, 100.00, NULL, 75),
(338, 305, 'material', '2026-01-30 08:27:48', 'OUT_ISSUE', NULL, NULL, NULL, 23, 'MR', 19, 0.00, 100.00, 0.40, 0.00, NULL, 75),
(339, 336, 'material', '2026-01-30 08:27:48', 'OUT_ISSUE', NULL, NULL, NULL, 23, 'MR', 19, 0.00, 100.00, 0.20, 0.00, NULL, 72),
(340, 305, 'material', '2026-01-30 08:28:22', 'wip_in', NULL, NULL, NULL, 23, 'MR', 19, 100.00, 0.00, 0.40, 100.00, NULL, NULL),
(341, 336, 'material', '2026-01-30 08:28:22', 'wip_in', NULL, NULL, NULL, 23, 'MR', 19, 100.00, 0.00, 0.20, 100.00, NULL, NULL),
(342, 305, 'material', '2026-01-30 08:28:44', 'wip_out', NULL, NULL, NULL, 69, 'PROD', 19, 0.00, 100.00, 0.00, 0.00, NULL, NULL),
(343, 336, 'material', '2026-01-30 08:28:59', 'wip_out', NULL, NULL, NULL, 70, 'PROD', 19, 0.00, 100.00, 0.00, 0.00, NULL, NULL),
(344, 1, 'semifinished', '2026-01-30 08:29:08', 'wip_in', NULL, NULL, NULL, 70, 'PROD', 18, 100.00, 0.00, 0.00, 100.00, NULL, NULL),
(345, 342, 'material', '2026-01-30 08:30:03', 'OUT_ISSUE', NULL, NULL, NULL, 24, 'MR', 19, 0.00, 100.00, 0.20, 0.00, NULL, 74),
(346, 347, 'material', '2026-01-30 08:30:03', 'OUT_ISSUE', NULL, NULL, NULL, 24, 'MR', 19, 0.00, 100.00, 0.20, 0.00, NULL, 73),
(347, 342, 'material', '2026-01-30 08:30:22', 'wip_in', NULL, NULL, NULL, 24, 'MR', 19, 100.00, 0.00, 0.20, 100.00, NULL, NULL),
(348, 347, 'material', '2026-01-30 08:30:22', 'wip_in', NULL, NULL, NULL, 24, 'MR', 19, 100.00, 0.00, 0.20, 100.00, NULL, NULL),
(349, 1, 'semifinished', '2026-01-30 08:30:51', 'wip_out', NULL, NULL, NULL, 71, 'PROD', 18, 0.00, 98.00, 0.00, 2.00, NULL, NULL),
(350, 342, 'material', '2026-01-30 08:30:57', 'wip_out', NULL, NULL, NULL, 72, 'PROD', 19, 0.00, 98.00, 0.00, 2.00, NULL, NULL),
(351, 347, 'material', '2026-01-30 08:31:02', 'wip_out', NULL, NULL, NULL, 73, 'PROD', 19, 0.00, 98.00, 0.00, 2.00, NULL, NULL),
(352, 4, 'semifinished', '2026-01-30 08:31:13', 'wip_in', NULL, NULL, NULL, 71, 'PROD', 18, 98.00, 0.00, 0.00, 98.00, NULL, NULL),
(353, 348, 'material', '2026-01-30 08:32:08', 'OUT_ISSUE', NULL, NULL, NULL, 25, 'MR', 19, 0.00, 98.00, 2.00, 2.00, NULL, 71),
(354, 348, 'material', '2026-01-30 08:32:32', 'wip_in', NULL, NULL, NULL, 25, 'MR', 19, 98.00, 0.00, 2.00, 100.00, NULL, NULL),
(355, 4, 'semifinished', '2026-01-30 08:32:44', 'wip_out', NULL, NULL, NULL, 74, 'PROD', 18, 0.00, 98.00, 0.00, 0.00, NULL, NULL),
(356, 348, 'material', '2026-01-30 08:32:52', 'wip_out', NULL, NULL, NULL, 75, 'PROD', 19, 0.00, 98.00, 0.00, 2.00, NULL, NULL),
(357, 4, 'finished_goods', '2026-01-30 08:35:20', 'fg_in', NULL, NULL, NULL, 13, 'FG_RECEIPT', 12, 98.00, 0.00, 0.00, 98.00, NULL, NULL),
(358, 1, 'scrap', '2026-01-30 08:35:40', 'IN_SCRAP_WH', NULL, NULL, NULL, 25, 'SCRAP_IN', 25, 1.00, 0.00, 0.00, 1.00, NULL, 23),
(359, 4, 'finished_goods', '2026-01-30 08:37:11', 'fg_out', '47', '763345', '2026-01-31', 19, 'FG_DELIVERY', 24, 0.00, 98.00, 0.00, 0.00, NULL, NULL),
(360, 1, 'scrap', '2026-01-30 08:40:03', 'OUT_SCRAP_BC', 'BC 4.0', '178781', '2026-01-31', 16, 'SCRAP_OUT', 17, 0.00, 1.00, 10000.00, 0.00, NULL, NULL),
(361, 342, 'material', '2026-01-30 08:41:24', 'IN_RETURN_WIP', NULL, NULL, NULL, 54, 'PROD_RETURN', 56, 2.00, 0.00, 0.20, 2.00, NULL, 76),
(362, 347, 'material', '2026-01-30 08:41:24', 'IN_RETURN_WIP', NULL, NULL, NULL, 55, 'PROD_RETURN', 56, 2.00, 0.00, 0.20, 2.00, NULL, 77),
(364, 3, 'machine', '2026-01-30 08:48:10', 'in', NULL, NULL, NULL, 30, 'MIR', 16, 1.00, 0.00, 120.00, 1.00, NULL, NULL),
(365, 316, 'material', '2026-01-30 09:16:40', 'in', NULL, NULL, NULL, 31, 'PO', 29, 12.00, 0.00, 10000.00, 12.00, NULL, 78),
(366, 347, 'material', '2026-01-30 14:32:12', 'in', NULL, NULL, NULL, 32, 'MIR', 17, 100.00, 0.00, 20.00, 102.00, NULL, 79),
(367, 342, 'material', '2026-01-30 14:32:12', 'in', NULL, NULL, NULL, 32, 'MIR', 17, 100.00, 0.00, 2.00, 102.00, NULL, 80),
(368, 348, 'material', '2026-01-30 14:32:12', 'in', NULL, NULL, NULL, 32, 'MIR', 17, 100.00, 0.00, 1.00, 102.00, NULL, 81),
(369, 336, 'material', '2026-01-30 14:32:12', 'in', NULL, NULL, NULL, 32, 'MIR', 17, 100.00, 0.00, 1.00, 100.00, NULL, 82),
(370, 305, 'material', '2026-01-30 14:32:12', 'in', NULL, NULL, NULL, 32, 'MIR', 17, 100.00, 0.00, 2.00, 100.00, NULL, 83),
(371, 305, 'material', '2026-01-30 14:36:19', 'OUT_ISSUE', NULL, NULL, NULL, 26, 'MR', 20, 0.00, 100.00, 2.00, 0.00, NULL, 83),
(372, 336, 'material', '2026-01-30 14:36:19', 'OUT_ISSUE', NULL, NULL, NULL, 26, 'MR', 20, 0.00, 100.00, 1.00, 0.00, NULL, 82),
(373, 305, 'material', '2026-01-30 14:36:52', 'wip_in', NULL, NULL, NULL, 26, 'MR', 20, 100.00, 0.00, 2.00, 100.00, NULL, NULL),
(374, 336, 'material', '2026-01-30 14:36:52', 'wip_in', NULL, NULL, NULL, 26, 'MR', 20, 100.00, 0.00, 1.00, 100.00, NULL, NULL),
(375, 305, 'material', '2026-01-30 14:38:05', 'wip_out', NULL, NULL, NULL, 76, 'PROD', 20, 0.00, 98.00, 0.00, 2.00, NULL, NULL),
(376, 336, 'material', '2026-01-30 14:38:21', 'wip_out', NULL, NULL, NULL, 77, 'PROD', 20, 0.00, 98.00, 0.00, 2.00, NULL, NULL),
(377, 1, 'semifinished', '2026-01-30 14:38:33', 'wip_in', NULL, NULL, NULL, 74, 'PROD', 19, 98.00, 0.00, 0.00, 100.00, NULL, NULL),
(378, 1, 'semifinished', '2026-01-30 14:39:13', 'wip_out', NULL, NULL, NULL, 78, 'PROD', 19, 0.00, 98.00, 0.00, 2.00, NULL, NULL),
(379, 342, 'material', '2026-01-30 14:40:16', 'OUT_ISSUE', NULL, NULL, NULL, 27, 'MR', 20, 0.00, 2.00, 0.20, 100.00, NULL, 76),
(380, 342, 'material', '2026-01-30 14:40:16', 'OUT_ISSUE', NULL, NULL, NULL, 27, 'MR', 20, 0.00, 98.00, 2.00, 2.00, NULL, 80),
(381, 347, 'material', '2026-01-30 14:40:16', 'OUT_ISSUE', NULL, NULL, NULL, 27, 'MR', 20, 0.00, 2.00, 0.20, 100.00, NULL, 77),
(382, 347, 'material', '2026-01-30 14:40:16', 'OUT_ISSUE', NULL, NULL, NULL, 27, 'MR', 20, 0.00, 98.00, 20.00, 2.00, NULL, 79),
(383, 342, 'material', '2026-01-30 14:40:26', 'wip_in', NULL, NULL, NULL, 27, 'MR', 20, 100.00, 0.00, 2.00, 102.00, NULL, NULL),
(384, 347, 'material', '2026-01-30 14:40:26', 'wip_in', NULL, NULL, NULL, 27, 'MR', 20, 100.00, 0.00, 20.00, 102.00, NULL, NULL),
(385, 342, 'material', '2026-01-30 14:40:52', 'wip_out', NULL, NULL, NULL, 79, 'PROD', 20, 0.00, 98.00, 0.00, 4.00, NULL, NULL),
(386, 347, 'material', '2026-01-30 14:41:00', 'wip_out', NULL, NULL, NULL, 80, 'PROD', 20, 0.00, 98.00, 0.00, 4.00, NULL, NULL),
(387, 4, 'semifinished', '2026-01-30 14:41:18', 'wip_in', NULL, NULL, NULL, 75, 'PROD', 19, 98.00, 0.00, 0.00, 98.00, NULL, NULL),
(388, 4, 'semifinished', '2026-01-30 14:41:50', 'wip_out', NULL, NULL, NULL, 81, 'PROD', 19, 0.00, 98.00, 0.00, 0.00, NULL, NULL),
(389, 348, 'material', '2026-01-30 14:42:43', 'OUT_ISSUE', NULL, NULL, NULL, 28, 'MR', 20, 0.00, 2.00, 2.00, 100.00, NULL, 71),
(390, 348, 'material', '2026-01-30 14:42:43', 'OUT_ISSUE', NULL, NULL, NULL, 28, 'MR', 20, 0.00, 96.00, 1.00, 4.00, NULL, 81),
(391, 348, 'material', '2026-01-30 14:42:55', 'wip_in', NULL, NULL, NULL, 28, 'MR', 20, 98.00, 0.00, 1.00, 102.00, NULL, NULL),
(392, 348, 'material', '2026-01-30 14:43:15', 'wip_out', NULL, NULL, NULL, 82, 'PROD', 20, 0.00, 98.00, 0.00, 4.00, NULL, NULL),
(393, 4, 'finished_goods', '2026-01-30 14:44:32', 'fg_in', NULL, NULL, NULL, 14, 'FG_RECEIPT', 13, 98.00, 0.00, 0.00, 98.00, NULL, NULL),
(394, 4, 'finished_goods', '2026-01-30 14:45:56', 'fg_out', '47', '092733', '2026-01-31', 20, 'FG_DELIVERY', 25, 0.00, 98.00, 0.00, 0.00, NULL, NULL),
(395, 305, 'material', '2026-01-30 14:47:04', 'IN_RETURN_WIP', NULL, NULL, NULL, 56, 'PROD_RETURN', 57, 2.00, 0.00, 2.00, 2.00, NULL, 84),
(396, 336, 'material', '2026-01-30 14:47:04', 'IN_RETURN_WIP', NULL, NULL, NULL, 57, 'PROD_RETURN', 57, 2.00, 0.00, 1.00, 2.00, NULL, 85),
(397, 342, 'material', '2026-01-30 14:47:04', 'IN_RETURN_WIP', NULL, NULL, NULL, 58, 'PROD_RETURN', 57, 2.00, 0.00, 0.20, 4.00, NULL, 76),
(398, 347, 'material', '2026-01-30 14:47:04', 'IN_RETURN_WIP', NULL, NULL, NULL, 59, 'PROD_RETURN', 57, 2.00, 0.00, 0.20, 4.00, NULL, 77);

-- --------------------------------------------------------

--
-- Struktur dari tabel `stock_ledger_backup_20251207`
--

CREATE TABLE `stock_ledger_backup_20251207` (
  `id` int(11) NOT NULL DEFAULT 0,
  `item_id` int(11) NOT NULL,
  `item_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'material, machine, inventory, semifinished, finished',
  `transaction_date` datetime NOT NULL,
  `transaction_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'e.g., in, out, production_in, production_out, adjustment, opening',
  `bc_type_code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bc_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bc_date` date DEFAULT NULL,
  `transaction_id` int(11) NOT NULL COMMENT 'ID from source table (grn_header, production_issue_header, etc)',
  `source_doc_type` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Optional: PO, MIR, WO, DO',
  `source_doc_id` int(11) DEFAULT NULL COMMENT 'Optional: ID header source doc',
  `qty_in` decimal(12,2) DEFAULT 0.00,
  `qty_out` decimal(12,2) DEFAULT 0.00,
  `unit_value` decimal(15,2) DEFAULT 0.00 COMMENT 'Nilai satuan saat transaksi (IDR)',
  `balance` decimal(12,2) NOT NULL COMMENT 'Saldo kuantitas setelah transaksi',
  `warehouse_id` int(11) DEFAULT NULL,
  `stock_on_hand_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `stock_ledger_backup_20251207`
--

INSERT INTO `stock_ledger_backup_20251207` (`id`, `item_id`, `item_type`, `transaction_date`, `transaction_type`, `bc_type_code`, `bc_number`, `bc_date`, `transaction_id`, `source_doc_type`, `source_doc_id`, `qty_in`, `qty_out`, `unit_value`, `balance`, `warehouse_id`, `stock_on_hand_id`) VALUES
(265, 17, 'material', '2025-12-07 16:13:34', 'wip_in', NULL, NULL, NULL, 18, 'MR', 18, 100.00, 0.00, 1.20, 100.00, NULL, NULL),
(266, 13, 'material', '2025-12-07 16:13:34', 'wip_in', NULL, NULL, NULL, 18, 'MR', 18, 100.00, 0.00, 1.00, 100.00, NULL, NULL),
(267, 14, 'material', '2025-12-07 16:13:34', 'wip_in', NULL, NULL, NULL, 18, 'MR', 18, 100.00, 0.00, 1.20, 100.00, NULL, NULL),
(268, 15, 'material', '2025-12-07 16:13:34', 'wip_in', NULL, NULL, NULL, 18, 'MR', 18, 100.00, 0.00, 2.00, 100.00, NULL, NULL),
(269, 12, 'material', '2025-12-07 16:13:34', 'wip_in', NULL, NULL, NULL, 18, 'MR', 18, 100.00, 0.00, 12.00, 100.00, NULL, NULL),
(270, 18, 'material', '2025-12-07 16:13:34', 'wip_in', NULL, NULL, NULL, 18, 'MR', 18, 10.00, 0.00, 2.00, 10.00, NULL, NULL);

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
(71, 41, NULL, 29, NULL, 348, 'material', 100.0000, 0.0000, 2.00),
(72, 42, NULL, 29, NULL, 336, 'material', 100.0000, 0.0000, 0.20),
(73, 43, NULL, 29, NULL, 347, 'material', 100.0000, 0.0000, 0.20),
(74, 44, NULL, 29, NULL, 342, 'material', 100.0000, 0.0000, 0.20),
(75, 45, NULL, 29, NULL, 305, 'material', 100.0000, 0.0000, 0.40),
(76, NULL, NULL, NULL, NULL, 342, 'material', 2.0000, 2.0000, 0.20),
(77, NULL, NULL, NULL, NULL, 347, 'material', 2.0000, 2.0000, 0.20),
(78, 47, NULL, 31, NULL, 316, 'material', 12.0000, 12.0000, 10000.00),
(79, 48, NULL, 32, NULL, 347, 'material', 100.0000, 2.0000, 20.00),
(80, 49, NULL, 32, NULL, 342, 'material', 100.0000, 2.0000, 2.00),
(81, 50, NULL, 32, NULL, 348, 'material', 100.0000, 4.0000, 1.00),
(82, 51, NULL, 32, NULL, 336, 'material', 100.0000, 0.0000, 1.00),
(83, 52, NULL, 32, NULL, 305, 'material', 100.0000, 0.0000, 2.00),
(84, NULL, NULL, NULL, NULL, 305, 'material', 2.0000, 2.0000, 2.00),
(85, NULL, NULL, NULL, NULL, 336, 'material', 2.0000, 2.0000, 1.00);

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
  `exim_verified` tinyint(1) DEFAULT 0 COMMENT 'Status verifikasi EXIM (0=belum, 1=sudah)',
  `exim_verified_by` int(11) DEFAULT NULL COMMENT 'User ID yang melakukan verifikasi EXIM',
  `exim_verified_at` datetime DEFAULT NULL COMMENT 'Tanggal dan waktu verifikasi EXIM',
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
(2, 'LOK-0001', 'PT Sparta Prima', 'Lokal', '0013017116038000', 'Jl. Raya Serang km.13.8 Cikupa, Kab. Tangerang', '0823 1532 1119', 'active', '2025-10-16 15:50:47', '2026-01-20 15:09:22'),
(3, 'LOK-0002', 'CV. Sugondo', 'Lokal', '00276868075120000', 'Jl. Karangsari III No.106 Rt 04 Rw 03 Karang Kidul, Semarang Tengah, Kota Semarang', '0817 4167 101', 'active', '2025-10-17 08:33:28', '2026-01-20 15:09:35'),
(4, 'IMP-0001', 'PT YUNS', 'Impor', '1222', 'JALAN PATIMORANG', '111', 'active', '2025-11-04 17:47:06', '2025-11-04 17:47:06'),
(5, 'LOK-0003', 'CV Jaya Makmur Stationary', 'Lokal', '0606069755504000', 'Jl. Rejosari Tengah II No.22, Rt 10 Rw 10 Rejosari, Semarang Timur, Kota Semarang', '0897-7963-479', 'active', '2026-01-06 10:57:12', '2026-01-20 15:09:47'),
(6, 'LOK-0004', 'AA Baru Listrik', 'Lokal', '', 'Jl. Magersari No.17, RT.1/RW.1, Gunungpati, Kec. Gn. Pati, Kota Semarang', '0877-3217-7440', 'active', '2026-01-20 14:24:13', '2026-01-20 14:24:13'),
(7, 'LOK-0005', 'ADA Swalayan', 'Lokal', '', 'Jl. Mgr Sugiyopranoto No.58 - 60, Bulustalan, Kec. Semarang Sel., Kota Semarang', '', 'active', '2026-01-20 14:34:13', '2026-01-20 15:10:01'),
(8, 'LOK-0006', 'PT Agusta Triasa', 'Lokal', '', 'Jl. Terboyo Industri III No.3, Terboyo Wetan, Kec. Genuk, Kota Semarang', '(024) 6583027', 'active', '2026-01-20 14:35:55', '2026-01-20 15:10:21'),
(9, 'LOK-0007', 'Alfamart', 'Lokal', '', '', '', 'active', '2026-01-20 14:36:12', '2026-01-20 15:10:32'),
(10, 'LOK-0008', 'Amindo', 'Lokal', '', '', '', 'active', '2026-01-20 14:37:44', '2026-01-20 15:10:41'),
(11, 'LOK-0009', 'Aneka Jaya ATK', 'Lokal', '', 'Jl. Pungkuran 368, Kauman, Semarang Tengah, Kauman, Kec. Semarang Tengah, Kota Semarang', '', 'active', '2026-01-20 14:40:36', '2026-01-20 15:10:57'),
(12, 'LOK-0010', 'Aneka Jaya Swalayan', 'Lokal', '', 'Jl. Prof. Dr. Hamka No.38A, Ngaliyan, Kec. Ngaliyan, Kota Semarang', '', 'active', '2026-01-20 14:41:15', '2026-01-20 15:11:13'),
(13, 'LOK-0011', 'PT Anugrah Tritunggal Perkasa Sejati', 'Lokal', '', 'Kawasan Industri Candi blok 5 no 1, Ngaliyan, Kecamatan Ngaliyan Kodepos 50184, Semarang', '(024) 7607250', 'active', '2026-01-20 14:47:40', '2026-01-20 15:11:38'),
(14, 'LOK-0012', 'Apotek Anugerah Sehat', 'Lokal', '', 'Jalan Permata Hijau Ruko Hasanudin Blok CC-1 No, RT.2, Panggung Kidul, Kec. Semarang Utara, Kota Semarang', '0878-8788-8293', 'active', '2026-01-20 14:52:51', '2026-01-20 14:52:51'),
(15, 'LOK-0013', 'UD. Artha', 'Lokal', '', 'l. Alun-Alun Barat Depan Masjid Agung No.12, Kauman, Kec. Semarang Tengah, Kota Semarang', '0858-6684-2383', 'active', '2026-01-20 14:54:05', '2026-01-20 15:11:49'),
(16, 'LOK-0014', 'Atlanta Elektronik', 'Lokal', '', 'Jl. MT. Haryono No.540, Jagalan, Kec. Semarang Tengah, Kota Semarang', '0877-7557-1414', 'active', '2026-01-20 14:55:11', '2026-01-20 15:12:06'),
(17, 'LOK-0015', 'Bengkel Las Jaya Pasudan', 'Lokal', '', 'Jalan kalirejo-leyangan Jalan Kalirejo-Leyangan, Petung, Susukan, Kec. Ungaran Timur', '0823-1365-2873', 'active', '2026-01-20 14:57:27', '2026-01-20 14:57:27'),
(18, 'LOK-0016', 'Cakra Surya Elektrik', 'Lokal', '', 'alan H. Agus Salim Ruko Bubakan Baru A-15, Purwodinatan, Kec. Semarang Tengah, Kota Semarang', '0857-2727-1632', 'active', '2026-01-20 14:58:29', '2026-01-20 14:58:29'),
(19, 'LOK-0017', 'PT Changyi Sukses Indonesia', 'Lokal', '1000000002476171', 'Jl. Raya Pantura Bangsri Blok E3 Kec. Bulakamba, Brebes, 52253', '0823 1389 2828', 'active', '2026-01-20 15:03:00', '2026-01-20 15:03:00'),
(20, 'LOK-0018', 'Kimo Cell', 'Lokal', '', 'Jalan Prof Dr Hamka No 12, Ngaliyan, Kec. Ngaliyan, Kota Semarang', '0857-4706-5942', 'active', '2026-01-20 15:07:05', '2026-01-20 15:07:18'),
(21, 'LOK-0019', 'Cruzafort Jaya Lestari', 'Lokal', '', 'Jl KH Agus Salim Kompl Bubaan Baru Bl B/5\r\nPurwodinatan, Semarang Tengah\r\nSemarang', '021 78831075', 'active', '2026-01-20 15:08:55', '2026-01-20 15:08:55'),
(22, 'LOK-0020', 'CV Pusat Kertas Sampangan', 'Lokal', '', 'Jl. Kelud Raya No.50A, Petompon, Kec. Gajahmungkur, Kota Semarang', '0857-9995-9797', 'active', '2026-01-20 15:13:15', '2026-01-20 15:13:15'),
(23, 'LOK-0021', 'DAS teknik', 'Lokal', '', 'Jl. KH. AGUSSALIM', '', 'active', '2026-01-20 15:24:06', '2026-01-20 15:24:06'),
(24, 'LOK-0022', 'PT Dong Chuang Technology', 'Lokal', '0862640695452000', '', '', 'active', '2026-01-20 15:26:09', '2026-01-20 15:26:09'),
(25, 'LOK-0023', 'PT Fun Top Teknologi Indonesia', 'Lokal', '1000000001708761', 'Blok Ragasuta 1,  RT 000,  RW 000, BABAKAN, KERTAJATI, KAB. MAJALENGKA, JAWA BARAT 45457', '0838-5761-1228', 'active', '2026-01-20 15:32:09', '2026-01-20 15:32:09'),
(26, 'LOK-0024', 'Gasplus BSB', 'Lokal', '', 'Jl Hadi Subeno Rt 2 Rw 5 Mijen Semarang', '0811-2706-455', 'active', '2026-01-20 15:33:04', '2026-01-20 15:33:04'),
(27, 'LOK-0025', 'PT Grand Forest Packaging Indonesia', 'Impor', '0391899911516000', 'Karangkebagusan I, RT 05 RW 02 Karangkebagusan, Kec. Jepara, Kabupaten Jepara, Jawa Tengah', '0813-2530-3351', 'active', '2026-01-20 15:34:45', '2026-01-21 09:58:14'),
(28, 'LOK-0026', 'PT Harmony Packaging Indonesia', 'Lokal', '0504439357503000', 'alan Kawasan Industri Candi Blok11A No.10C, Kec. Ngaliyan, Kota Semarang, Jawa Tengah', '0821-3636-6866', 'active', '2026-01-20 15:35:50', '2026-01-20 15:35:50'),
(29, 'LOK-0027', 'PT Indoho Santosa Abadi', 'Lokal', '0020365771503000', 'Blok 23, No.6B, JL. Gatot Subroto, Kawasan Industri Candi, Kalipancur, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50147', '0811-250-800', 'active', '2026-01-20 15:36:59', '2026-01-20 15:36:59'),
(30, 'LOK-0028', 'Indomart', 'Lokal', '', '', '', 'active', '2026-01-20 15:45:27', '2026-01-20 15:45:27'),
(31, 'LOK-0029', 'PT Indonesia Baiguo Wood', 'Lokal', '0834008260516000', 'Jl. Citrosomo Desa Senenan Rt 17 Rw 06 Kec. Tahunan Kab. Jepara Jawa tengah', '0291-596 838', 'active', '2026-01-20 15:49:02', '2026-01-20 15:49:02'),
(32, 'LOK-0030', 'Istana Besi', 'Lokal', '', 'Jl. K.H. Agus Salim No.1 Kav. 49, Purwodinatan, Kec. Semarang Tengah, Kota Semarang', '0896-6699-9399', 'active', '2026-01-20 15:49:51', '2026-01-20 15:49:51'),
(33, 'LOK-0031', 'Istana Jaya Fotokopi', 'Lokal', '', 'Jl. Abdul Rahman Saleh, Kalipancur, Kec. Ngaliyan, Kota Semarang', '0831-4866-0589', 'active', '2026-01-20 15:50:35', '2026-01-20 15:50:35'),
(34, 'LOK-0032', 'Jakarta Notebook', 'Lokal', '', 'Jl. Sriwijaya No.96-98, Wonodri, Kec. Semarang Sel., Kota Semarang', '0896-8567-1406', 'active', '2026-01-20 15:51:21', '2026-01-20 15:51:21'),
(35, 'LOK-0033', 'Jaya Selang', 'Lokal', '', 'No.5 Blok D, Komplek, Jl. Komp. Pertokoan Jurnatan Jl. K.H. Agus Salim, Purwodinatan, Kec. Semarang Tengah, Kota Semarang', '0812-2272-3399', 'active', '2026-01-20 15:52:17', '2026-01-20 15:52:17'),
(36, 'LOK-0034', 'PT Jia Hua Hua Gong', 'Lokal', '0537398091451000', 'INDUSTRI RAYA III AH NO.7, RT 000, RW 000, SUKADAMAI, CIKUPA, KAB. TANGERANG, BANTEN\r\n15710', '0857-1922-2096', 'active', '2026-01-20 15:53:16', '2026-01-20 15:53:16'),
(37, 'LOK-0035', 'KAC Accesories', 'Lokal', '', 'Jalan Prof. Dr. Hamka Ngaliyan, Ngaliyan, Kec. Ngaliyan, Kota Semarang', '0821-2481-3562', 'active', '2026-01-20 15:54:43', '2026-01-20 15:54:43'),
(38, 'LOK-0036', 'Kharisma Fotocopy', 'Lokal', '', 'Jl. Wr. Supratman, Ngemplak Simongan, Kec. Semarang Barat, Kota Semarang', '0821-4705-4070', 'active', '2026-01-20 15:55:37', '2026-01-20 15:55:37'),
(39, 'LOK-0037', 'Lancar Ngaliyan Fotokopi', 'Lokal', '', ' Jl. Prof. Dr. Hamka No.2, Ngaliyan, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50184', '0812-2537-4260', 'active', '2026-01-20 15:56:21', '2026-01-20 15:56:21'),
(40, 'LOK-0038', 'Laris Jaya', 'Lokal', '', 'Depan Indomaret Tambra, Jl. Tambra No.25, Purwosari, Semarang Utara, Semarang City, Central Java', '0877-4333-7339', 'active', '2026-01-20 15:57:24', '2026-01-20 15:57:24'),
(41, 'LOK-0039', 'Listromart', 'Lokal', '', 'Jl. Raya Ngaliyan Jl. Kedungpane No.4, Ngaliyan, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50181', '0815-7890-1858', 'active', '2026-01-20 15:58:41', '2026-01-20 15:58:41'),
(42, 'LOK-0040', 'Mahkota Jaya', 'Lokal', '', 'Gg. Besen No.73, Kranggan, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50139', '0877-3373-3873', 'active', '2026-01-20 15:59:26', '2026-01-20 15:59:26'),
(43, 'LOK-0041', 'UD Marem Baru', 'Lokal', '', 'Jl. Kebonharjo Raya, Tj. Mas, Kec. Semarang Utara, Kota Semarang, Jawa Tengah 50174', '0821-3470-8949', 'active', '2026-01-20 16:00:08', '2026-01-20 16:00:08'),
(44, 'LOK-0042', 'Maureen Printing', 'Lokal', '', 'kluwak rt02, RT.kluwak rt02/RW.04, Gono Barat, Gonoharjo, Kec. Limbangan, Kabupaten Kendal, Jawa Tengah 51383', '0813-9229-7535', 'active', '2026-01-20 16:01:02', '2026-01-20 16:01:02'),
(45, 'LOK-0043', 'Toko Mesin Jahit Mix Brand Petolongan', 'Lokal', '', 'ln. Mataram No.69, Purwodinatan, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50137', '0852-9009-0410', 'active', '2026-01-20 16:02:06', '2026-01-20 16:02:06'),
(46, 'LOK-0044', 'MR. DIY', 'Lokal', '', '', '', 'active', '2026-01-20 16:03:13', '2026-01-20 16:03:13'),
(47, 'LOK-0045', 'Mriki Mart', 'Lokal', '', 'Jl. Untung Suropati, Kalipancur, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50147', '(024) 76435361', 'active', '2026-01-20 16:03:47', '2026-01-20 16:03:47'),
(48, 'LOK-0046', 'PT Muliaform Grafido', 'Lokal', '', 'Jl. Gatot Subroto Kawasan Industri Candi No.20 Blok 20 A, Ngaliyan, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50181', '0817-9548-518', 'active', '2026-01-20 16:04:41', '2026-01-20 16:04:41'),
(49, 'LOK-0047', 'Nafiri Computer', 'Lokal', '', 'Jl. Kelud Raya No.15D, Petompon, Kec. Gajahmungkur, Kota Semarang, Jawa Tengah 50237', '0895-0620-6666', 'active', '2026-01-20 16:05:24', '2026-01-20 16:05:24'),
(50, 'LOK-0048', 'Natanetwork Solution', 'Lokal', '', 'Jalan Prapen Indah F No. 2 Kel, Panjang Jiwo, Kec. Tenggilis Mejoyo, Surabaya, Jawa Timur 60299', '(031) 99860999', 'active', '2026-01-20 16:06:12', '2026-01-20 16:06:12'),
(51, 'LOK-0049', 'Nusantara Mebeul', 'Lokal', '', 'Jl. Raya Ngaliyan, Tambakaji, Kec. Ngaliyan, Kota Semarang', '0815-7704-788', 'active', '2026-01-21 07:35:34', '2026-01-21 07:35:34'),
(52, 'LOK-0050', 'PT Opaige Mebel Indonesia', 'Lokal', '', 'Barat, RT.001/RW.001, Dusun 1, Suwawal, Kec. Mlonggo, Kabupaten Jepara, Jawa Tengah 59452', '0812-1352-5850', 'active', '2026-01-21 07:37:27', '2026-01-21 07:37:27'),
(53, 'LOK-0051', 'Pak Mul Galon', 'Lokal', '', '', '0812-2963-3438', 'active', '2026-01-21 07:38:18', '2026-01-21 07:38:18'),
(54, 'LOK-0052', 'Panca Putra Listrik', 'Lokal', '', 'Jl KH Agus Salim Kompl Bubaan Baru Bl B/5 Kompl Bubaan Baru, Purwodinatan, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50137', '0878-1104-8218', 'active', '2026-01-21 07:39:14', '2026-01-21 07:39:14'),
(55, 'LOK-0053', 'Pasifik Toko', 'Lokal', '', 'Jl. Pekojan Tengah No.25-27, Purwodinatan, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50137', '0812-2560-2705', 'active', '2026-01-21 07:40:04', '2026-01-21 07:40:04'),
(56, 'LOK-0054', 'Pertamina', 'Lokal', '', '', '', 'active', '2026-01-21 07:40:24', '2026-01-21 07:40:24'),
(57, 'LOK-0055', 'Polaris Jaya', 'Lokal', '', 'Jalan K.H.Agus Salim, Kompleks Jl. Jurnatan No.D8, Purwodinatan, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50137', '0859-5166-6688', 'active', '2026-01-21 07:41:12', '2026-01-21 07:41:12'),
(58, 'LOK-0056', 'PT Pos Indonesia', 'Lokal', '', 'Jl. Pemuda No.4, Pandansari, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50188', '', 'active', '2026-01-21 07:42:11', '2026-01-21 07:42:11'),
(59, 'LOK-0057', 'Pungkuran Plastik', 'Lokal', '', 'Jl. Kanjengan No.A-16, Semarang, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50137', '081328822601', 'active', '2026-01-21 07:43:31', '2026-01-21 07:43:31'),
(60, 'LOK-0058', 'Pusat Grosir Semarang', 'Lokal', '', 'Jl. Puspogiwang No.12, Gisikdrono, Kec. Semarang Barat, Kota Semarang, Jawa Tengah 50149', '0822-6549-3572', 'active', '2026-01-21 07:44:14', '2026-01-21 07:44:14'),
(61, 'LOK-0059', 'Putra Mesin Jahit', 'Lokal', '', 'tiktok shop', '', 'active', '2026-01-21 07:45:08', '2026-01-21 07:45:08'),
(62, 'LOK-0060', 'Raja Stempel Ngaliyan', 'Lokal', '', 'Jl. Raya Ngaliyan No.45 A, Tambakaji, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50185', '0815-7774-224', 'active', '2026-01-21 07:46:06', '2026-01-21 07:46:06'),
(63, 'LOK-0061', 'Ratu Paksi', 'Lokal', '', 'Jl. Pemuda No.32, Bangunharjo, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50132', '0815-5427-1449', 'active', '2026-01-21 07:47:09', '2026-01-21 07:47:09'),
(64, 'LOK-0062', 'Sangkoeriang Stationery', 'Lokal', '', 'Jl. Prof. Dr. Hamka No.12, Tambakaji, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50185', '0812-2842-238', 'active', '2026-01-21 07:48:01', '2026-01-21 07:48:01'),
(65, 'LOK-0063', 'Satria Sakti', 'Lokal', '', 'Jl. Pemuda No.26, Bangunharjo, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50138', '0816-4222-230', 'active', '2026-01-21 07:49:34', '2026-01-21 07:49:34'),
(66, 'LOK-0064', 'Semarang Wangi', 'Lokal', '', 'l. Kokrosono No.498, Pindrikan Lor, Kec. Semarang Tengah, Kota Semarang, Jawa Tengah 50131', '0817-0567-910', 'active', '2026-01-21 07:50:15', '2026-01-21 07:50:15'),
(67, 'LOK-0065', 'Arla Maju Jaya AC', 'Lokal', '', 'Pokok Pondasi, Jl. Raya Ngaliyan No.E-8, RT.002 RW007, Ngaliyan, Kec. Ngaliyan, Kota Semarang, Jawa Tengah 50181', '0813-1933-2728', 'active', '2026-01-21 07:54:08', '2026-01-21 07:54:08'),
(68, 'LOK-0066', 'UD Setiawan', 'Lokal', '-', ' Kepoh RT 2 RW 4, Nongkosawit, Gunung Pati', '08988755955', 'active', '2026-01-21 07:56:29', '2026-01-21 07:56:29'),
(69, 'LOK-0067', 'PT Shihong Indonesia', 'Lokal', '0205384910451000', 'Jalan Industri Raya Barat Kav A. 67 Semarang', '', 'active', '2026-01-21 07:57:55', '2026-01-21 07:57:55'),
(70, 'LOK-0068', 'S Abel Nusantara', 'Lokal', '', '', '', 'active', '2026-01-21 08:09:58', '2026-01-21 08:09:58'),
(71, 'LOK-0069', 'S Agapack', 'Lokal', '', '', '', 'active', '2026-01-21 08:10:52', '2026-01-21 08:10:52'),
(72, 'LOK-0070', 'S Ali Suplier', 'Lokal', '', '', '', 'active', '2026-01-21 08:11:20', '2026-01-21 08:11:20'),
(73, 'LOK-0071', 'S Angsana kalimalang', 'Lokal', '', '', '', 'active', '2026-01-21 08:13:18', '2026-01-21 08:13:18'),
(74, 'LOK-0072', 'S AP Stationery', 'Lokal', '', '', '', 'active', '2026-01-21 08:13:36', '2026-01-21 08:13:36'),
(75, 'LOK-0073', 'S Arjuna Stationery', 'Lokal', '', '', '', 'active', '2026-01-21 08:14:02', '2026-01-21 08:14:02'),
(76, 'LOK-0074', 'S Awan_kg', 'Lokal', '', '', '', 'active', '2026-01-21 08:14:16', '2026-01-21 08:14:38'),
(77, 'LOK-0075', 'S Bantex Indonesia Ofiicial Shop', 'Lokal', '', '', '', 'active', '2026-01-21 08:15:09', '2026-01-21 08:15:09'),
(78, 'LOK-0076', 'S Cahayarezekihomestore', 'Lokal', '', '', '', 'active', '2026-01-21 08:15:27', '2026-01-21 08:15:27'),
(79, 'LOK-0077', 'S CKE Semarang', 'Lokal', '', '', '', 'active', '2026-01-21 08:15:50', '2026-01-21 08:15:50'),
(80, 'LOK-0078', 'S Deli Office Automation Official Shop', 'Lokal', '', '', '', 'active', '2026-01-21 08:16:30', '2026-01-21 08:16:30'),
(81, 'LOK-0079', 'S Grazie Official Shop', 'Lokal', '', '', '', 'active', '2026-01-21 08:16:52', '2026-01-21 08:16:52'),
(82, 'LOK-0080', 'S Hanifa_Store', 'Lokal', '', '', '', 'active', '2026-01-21 08:17:03', '2026-01-21 08:17:16'),
(83, 'LOK-0081', 'S Happy Shop', 'Lokal', '', '', '', 'active', '2026-01-21 08:17:38', '2026-01-21 08:17:38'),
(84, 'LOK-0082', 'S Hugo', 'Lokal', '', '', '', 'active', '2026-01-21 08:17:46', '2026-01-21 08:17:46'),
(85, 'LOK-0083', 'S Hutan Gugur', 'Lokal', '', '', '', 'active', '2026-01-21 08:18:11', '2026-01-21 08:18:11'),
(86, 'LOK-0084', 'S Hyga Home Official Shop', 'Lokal', '', '', '', 'active', '2026-01-21 08:18:27', '2026-01-21 08:18:27'),
(87, 'LOK-0085', 'S ichimegastore', 'Lokal', '', '', '', 'active', '2026-01-21 08:18:54', '2026-01-21 08:18:54'),
(88, 'LOK-0086', 'S Indo happy shop', 'Lokal', '', '', '', 'active', '2026-01-21 08:19:18', '2026-01-21 08:19:18'),
(89, 'LOK-0087', 'S keep amanah keep istiqamah', 'Lokal', '', '', '', 'active', '2026-01-21 08:19:42', '2026-01-21 08:19:42'),
(90, 'LOK-0088', 'S Miisoo Official Shop', 'Lokal', '', '', '', 'active', '2026-01-21 08:20:10', '2026-01-21 08:20:10'),
(91, 'LOK-0089', 'S Mitra Otomotif', 'Lokal', '', '', '', 'active', '2026-01-21 08:20:32', '2026-01-21 08:20:32'),
(92, 'LOK-0090', 'S MR-YF', 'Lokal', '', '', '', 'active', '2026-01-21 08:20:44', '2026-01-21 08:20:44'),
(93, 'LOK-0091', 'S Myfada Grosir', 'Lokal', '', '', '', 'active', '2026-01-21 08:20:58', '2026-01-21 08:20:58'),
(94, 'LOK-0092', 'S Ngola Official Store', 'Lokal', '', '', '', 'active', '2026-01-21 08:25:47', '2026-01-21 08:25:47'),
(95, 'LOK-0093', 'S Perlengkapan Kantor', 'Lokal', '', '', '', 'active', '2026-01-21 08:26:04', '2026-01-21 08:26:04'),
(96, 'LOK-0094', 'S Piring Keramik Cap Gunung', 'Lokal', '', '', '', 'active', '2026-01-21 08:26:23', '2026-01-21 08:26:23'),
(97, 'LOK-0095', 'S Pusat grosir Olahraga', 'Lokal', '', '', '', 'active', '2026-01-21 08:26:45', '2026-01-21 08:26:45'),
(98, 'LOK-0096', 'S Royo Panda Toko Resmi', 'Lokal', '', '', '', 'active', '2026-01-21 08:27:05', '2026-01-21 08:27:05'),
(99, 'LOK-0097', 'S Secret Base Surabaya', 'Lokal', '', '', '', 'active', '2026-01-21 08:27:30', '2026-01-21 08:27:30'),
(100, 'LOK-0098', 'S Selamat Berbelanja Store', 'Lokal', '', '', '', 'active', '2026-01-21 08:27:54', '2026-01-21 08:27:54'),
(101, 'LOK-0099', 'S Shopvee Id', 'Lokal', '', '', '', 'active', '2026-01-21 08:28:08', '2026-01-21 08:28:08'),
(102, 'LOK-0100', 'S Simplus Official Shop', 'Lokal', '', '', '', 'active', '2026-01-21 08:28:29', '2026-01-21 08:28:29'),
(103, 'LOK-0101', 'S Sinorstore', 'Lokal', '', '', '', 'active', '2026-01-21 08:28:50', '2026-01-21 08:28:50'),
(104, 'LOK-0102', 'S StarMax Pro', 'Lokal', '', '', '', 'active', '2026-01-21 08:29:04', '2026-01-21 08:29:04'),
(105, 'LOK-0103', 'S Suksestech official Store', 'Lokal', '', '', '', 'active', '2026-01-21 08:29:37', '2026-01-21 08:29:37'),
(106, 'LOK-0104', 'S Tape Suplies', 'Lokal', '', '', '', 'active', '2026-01-21 08:30:03', '2026-01-21 08:30:03'),
(107, 'LOK-0105', 'S TD-TOOL MALL', 'Lokal', '', '', '', 'active', '2026-01-21 08:30:27', '2026-01-21 08:30:27'),
(108, 'LOK-0106', 'S Tiger Print', 'Lokal', '', '', '', 'active', '2026-01-21 08:30:47', '2026-01-21 08:30:47'),
(109, 'LOK-0107', 'S Toko Lily Plastik Semarang', 'Lokal', '', 'Jl. Citarum No.23, Bugangan, Kec. Semarang Tim., Kota Semarang, Jawa Tengah 50126', '0856-4240-9527', 'active', '2026-01-21 08:31:33', '2026-01-21 08:31:33'),
(110, 'LOK-0108', 'S Toko Rame', 'Lokal', '', '', '', 'active', '2026-01-21 08:31:51', '2026-01-21 08:31:51'),
(111, 'LOK-0109', 'S Toko Si Babe', 'Lokal', '', '', '', 'active', '2026-01-21 08:32:08', '2026-01-21 08:32:08'),
(112, 'LOK-0110', 'S Toko SINAR UTAMA', 'Lokal', '', '', '', 'active', '2026-01-21 08:32:25', '2026-01-21 08:32:25'),
(113, 'LOK-0111', 'S Tokoplus ID', 'Lokal', '', '', '', 'active', '2026-01-21 08:32:44', '2026-01-21 08:32:44'),
(114, 'LOK-0112', 'S tomstore.id', 'Lokal', '', '', '', 'active', '2026-01-21 08:33:08', '2026-01-21 08:33:08'),
(115, 'LOK-0113', 'S Your safety', 'Lokal', '', '', '', 'active', '2026-01-21 08:33:26', '2026-01-21 08:33:26'),
(116, 'LOK-0114', 'S Yuefeibao official store', 'Lokal', '', '', '', 'active', '2026-01-21 08:33:49', '2026-01-21 08:33:49'),
(117, 'LOK-0115', 'S RM Padang Chaniago', 'Lokal', '', 'Jl. Abdulrahman Saleh No.84, Kalibanteng Kidul, Kec. Semarang Barat, Kota Semarang, Jawa Tengah 50145', '0878-3241-2205', 'active', '2026-01-21 08:34:40', '2026-01-21 08:34:40'),
(118, 'LOK-0116', 'Sinar Jaya Houseware', 'Lokal', '', 'Jl. Soekarno Hatta No.97a, Tlogosari Kulon, Kec. Pedurungan, Kota Semarang, Jawa Tengah 50196', '0877-4348-0700', 'active', '2026-01-21 08:35:24', '2026-01-21 08:35:24'),
(119, 'LOK-0117', 'Superindo', 'Lokal', '', '', '', 'active', '2026-01-21 09:45:48', '2026-01-21 09:45:48');

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
(4, 'PPIC', 'PPIC', '$2y$10$m9riaMy75fWPdOikbAw2KeZlDWaji6BfKr9qK3WPLtTg/ae7CyPSy', 'it@foreveroneinternational.com', 1, 'active', '2026-02-02 07:13:30', '2026-01-30 10:34:24', '2025-10-16 04:55:11'),
(5, 'gudang', 'gudanbg', '$2y$10$iTRT78M/mO.0/heqNdd.4eY3lWOTvmskS/VIc8sLa7tI97..fkwBS', 'it@foreveroneinternational.com', 1, 'active', '2025-10-16 14:12:43', NULL, '2025-10-16 07:12:43'),
(6, 'Production', 'prodmli', '$2y$10$97QPYbRkDKtF7njtBKaphuoVbWtzLFzf7v89MraAOWjwLz1qXC33O', 'it@foreveroneinternational.com', 3, 'active', '2025-11-05 14:31:00', '2025-11-05 14:31:00', '2025-11-04 10:46:37'),
(10, 'bc', 'bc', '$2y$10$b7iLvQwLYQL9d6nbZs576OWshj/NomV3asXV0dhsqHbjFHm40tfZe', '', 5, 'active', '2026-01-30 13:56:15', '2026-01-30 13:56:15', '2026-01-04 07:40:50'),
(11, 'IT', 'IT', '$2y$10$TCR709fTMPTJT8bCJVmorOT8MiqCkVOTqzmeKOjuTtF8nCL.TpaUq', '', 1, 'active', '2026-01-29 07:32:16', '2026-01-29 07:32:16', '2026-01-05 01:59:04'),
(12, 'QC', 'QC', '$2y$10$MNfpcLXTmxVUidX9yG1OGOdRBlh1DKkxfV0a/0sJ0ugf00ijykUMe', '', 11, 'active', '2026-01-28 09:10:32', '2026-01-28 09:10:32', '2026-01-05 01:59:23'),
(13, 'WH', 'Warehouse', '$2y$10$MwLTMX9xNeK4QTBjAm3zJ.F856cKLU7YGzoJFKhSAwoJUV7aPwDyS', '', 10, 'active', '2026-01-29 07:34:13', '2026-01-29 07:34:13', '2026-01-05 01:59:49'),
(14, 'PPIC MLI', 'PPIC Mli', '$2y$10$8G/dq8mm1mAd2IiBGZih/.ITYYJOQuftfvIAiw5uU081aw2iBQxhO', '', 12, 'active', '2026-01-28 14:53:21', '2026-01-28 14:53:21', '2026-01-05 02:00:17'),
(15, 'FG', 'Finishgoods', '$2y$10$rMju30GXq9umqt5gdf43xeaP.6LnbyzENeYf2SJk/6koSarEyEt5S', '', 9, 'active', '2026-01-05 09:00:36', NULL, '2026-01-05 02:00:36'),
(16, 'Accounting', 'Accounting', '$2y$10$lFxgMeWEEkcXZkct8g7B7eFVOSjVJXsCBb9tjmM46kjsj8hUpEuyS', '', 8, 'active', '2026-01-26 08:39:08', '2026-01-26 08:39:08', '2026-01-05 02:01:02'),
(17, 'Production', 'Production', '$2y$10$UXiEjj4rUOLrSxbTTqgDI.4fTrRQrw5/ClOLVO4WPUakHBlVJN4T6', '', 7, 'active', '2026-01-29 17:40:24', '2026-01-29 17:40:24', '2026-01-05 02:01:26'),
(18, 'Purchase', 'Purchase', '$2y$10$cKa954b79rdv2SlrBImmjerU1z2Stc4w6nrIxZXOIxu9shDZMDx9.', '', 6, 'active', '2026-01-28 09:59:12', '2026-01-28 09:59:12', '2026-01-05 02:02:28'),
(19, 'WH Peralatan', 'WarehousePeralatan', '$2y$10$V5B9rb78eGRygoBa8x//sOj.KAGeu6pBZUJdhR0URvS2eg1dYyQNG', '', 10, 'active', '2026-01-08 10:17:12', '2026-01-08 10:17:12', '2026-01-05 06:33:38'),
(20, 'PPIC MLi2', 'PPICMli2', '$2y$10$uG1pe.kTeiS3Gk1jXB6NWu8QvczhAyZCBze2cR1FSjR/nb4IOYlLa', '', 12, 'active', '2026-01-05 13:38:40', NULL, '2026-01-05 06:38:40'),
(21, 'exim anindya', 'exim1', '$2y$10$k1cq7lvOPlFlDWZU4b0K5uYDg9Jsho6lmHjICSrpVS2vlxe1i.Z1u', '', 12, 'active', '2026-01-28 15:14:11', '2026-01-28 15:14:11', '2026-01-20 03:48:42'),
(22, 'zaky', 'zaky', '$2y$10$4PhcXfE8KkfQhlik1I31YOEmpKFxKs52e2sGc//dd6BeibPtewpAq', '', 3, 'active', '2026-01-28 15:28:05', '2026-01-28 15:28:05', '2026-01-23 11:17:34');

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
(75, 4, 'PPIC', 'COMPLETE', 'Production Process', 'Menyelesaikan Proses Produksi: PROD/202511/0001. Status: In Progress -> Completed', '2025-11-11 08:38:09'),
(76, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2025/00001', '2025-12-02 02:51:27'),
(77, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202512/0001 untuk WO: N/A', '2025-12-02 02:52:32'),
(78, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap preparation: item tipe semifinished (Qty: 10) pada Produksi ID: 11', '2025-12-02 06:06:08'),
(79, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus output ID: 55 dari tahap  pada Produksi ID: 11', '2025-12-02 06:06:15'),
(80, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus output ID: 54 dari tahap  pada Produksi ID: 11', '2025-12-02 06:06:19'),
(81, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap preparation: item tipe semifinished (Qty: 10) pada Produksi ID: 11', '2025-12-02 06:06:26'),
(82, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap preparation (Produksi ID: 11)', '2025-12-02 06:06:28'),
(83, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input UPPER (Qty: 10) dari tahap preparation ke assembling pada Produksi ID: 11', '2025-12-02 06:06:39'),
(84, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe semifinished (Qty: 10) pada Produksi ID: 11', '2025-12-02 06:06:49'),
(85, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 4 pada tahap assembling (Produksi ID: 11)', '2025-12-02 06:06:53'),
(86, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input Sepatu setengah jadi (Qty: 10) dari tahap assembling ke finishing pada Produksi ID: 11', '2025-12-02 06:07:01'),
(87, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output Finished Goods (Qty: 10) pada Produksi ID: 11', '2025-12-02 06:07:19'),
(88, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 3 pada tahap finishing (Produksi ID: 11)', '2025-12-02 06:07:24'),
(89, 4, 'PPIC', 'APPROVE', 'Production Process', 'Konfirmasi Proses Produksi: PROD/202512/0001. Status: Draft -> In Progress', '2025-12-02 06:07:27'),
(90, 4, 'PPIC', 'COMPLETE', 'Production Process', 'Menyelesaikan Proses Produksi: PROD/202512/0001. Status: In Progress -> Completed', '2025-12-02 06:07:30'),
(91, 4, 'PPIC', 'EXPORT', 'Log Activity', 'Export log activity ke Excel', '2025-12-02 06:36:11'),
(92, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2025/00002', '2025-12-05 08:37:25'),
(93, 4, 'PPIC', 'CREATE', 'Master Pelanggan', 'Membuat pelanggan baru: FLOOS (Kode: EKS-0001)', '2025-12-07 08:28:17'),
(94, 4, 'PPIC', 'CREATE', 'Bill of Materials', 'Membuat BOM header (ID: 7) untuk FG: FLOOS-JM67', '2025-12-07 08:30:06'),
(95, 4, 'PPIC', 'CREATE', 'Master Material', 'Membuat material baru: Tag Size  (Kode: SII-0001)', '2025-12-07 08:34:29'),
(96, 4, 'PPIC', 'CREATE', 'Master Material', 'Membuat material baru: Straps (Kode: UPI-0001)', '2025-12-07 08:35:16'),
(97, 4, 'PPIC', 'CREATE', 'Master Material', 'Membuat material baru: Insole (Kode: INI-0001)', '2025-12-07 08:35:40'),
(98, 4, 'PPIC', 'CREATE', 'Master Material', 'Membuat material baru: Outsole (Kode: OUI-0001)', '2025-12-07 08:35:55'),
(99, 4, 'PPIC', 'CREATE', 'Master Material', 'Membuat material baru: Papper Accesories (Kode: ACI-0001)', '2025-12-07 08:36:53'),
(100, 4, 'PPIC', 'CREATE', 'Master Material', 'Membuat material baru: Inner Box (Kode: BOI-0001)', '2025-12-07 08:37:07'),
(101, 4, 'PPIC', 'CREATE', 'Master Material', 'Membuat material baru: Carton Box (Kode: CAI-0001)', '2025-12-07 08:37:29'),
(102, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 12, Tipe: material, Qty: 1) ke BOM ID: 7', '2025-12-07 08:37:45'),
(103, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Update kuantitas item (Detail ID: 12) menjadi 1 pada BOM ID: 7', '2025-12-07 08:37:47'),
(104, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 13, Tipe: material, Qty: 1) ke BOM ID: 7', '2025-12-07 08:38:00'),
(105, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 15, Tipe: material, Qty: 1) ke BOM ID: 7', '2025-12-07 08:38:15'),
(106, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 17, Tipe: material, Qty: 1) ke BOM ID: 7', '2025-12-07 08:38:22'),
(107, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 18, Tipe: material, Qty: 0.1) ke BOM ID: 7', '2025-12-07 08:38:32'),
(108, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 14, Tipe: material, Qty: 1) ke BOM ID: 7', '2025-12-07 08:38:42'),
(109, 4, 'PPIC', 'APPROVE', 'Bill of Materials', 'Approve BOM (ID: 7) untuk FG: FLOOS-JM67. Status: Draft -> Active', '2025-12-07 08:38:47'),
(110, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2025/00001', '2025-12-07 08:39:16'),
(111, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202512/0001 untuk WO: N/A', '2025-12-07 09:16:35'),
(112, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Tag Size  (Qty: 100) ke tahap preparation pada Produksi ID: 12', '2025-12-07 09:37:13'),
(113, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Tag Size  (Qty: 100) ke tahap preparation pada Produksi ID: 12', '2025-12-07 09:39:37'),
(114, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus input ID: 48 dari tahap  pada Produksi ID: 12', '2025-12-07 09:39:51'),
(115, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus input ID: 47 dari tahap  pada Produksi ID: 12', '2025-12-07 09:39:55'),
(116, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Tag Size  (Qty: 100) ke tahap preparation pada Produksi ID: 12', '2025-12-07 09:43:02'),
(117, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus input ID: 49 dari tahap  pada Produksi ID: 12', '2025-12-07 09:43:06'),
(118, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Tag Size  (Qty: 100) ke tahap preparation pada Produksi ID: 12', '2025-12-07 09:43:13'),
(119, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Straps (Qty: 100) ke tahap preparation pada Produksi ID: 12', '2025-12-07 09:43:39'),
(120, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap preparation: item tipe semifinished (Qty: 100) pada Produksi ID: 12', '2025-12-07 09:43:59'),
(121, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 7 pada tahap preparation (Produksi ID: 12)', '2025-12-07 09:44:04'),
(122, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input UPPER (Qty: 100) dari tahap preparation ke assembling pada Produksi ID: 12', '2025-12-07 09:44:13'),
(123, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus input ID: 52 dari tahap  pada Produksi ID: 12', '2025-12-07 09:45:05'),
(124, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus input ID: 52 dari tahap  pada Produksi ID: 12', '2025-12-07 09:49:00'),
(125, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus input ID: 52 dari tahap assembling pada Produksi ID: 12', '2025-12-07 09:50:21'),
(126, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input UPPER (Qty: 100) dari tahap preparation ke assembling pada Produksi ID: 12', '2025-12-07 09:50:35'),
(127, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Insole (Qty: 100) ke tahap assembling pada Produksi ID: 12', '2025-12-07 09:50:43'),
(128, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Outsole (Qty: 100) ke tahap assembling pada Produksi ID: 12', '2025-12-07 09:50:50'),
(129, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe semifinished (Qty: 200) pada Produksi ID: 12', '2025-12-07 09:51:08'),
(130, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus output ID: 60 dari tahap  pada Produksi ID: 12', '2025-12-07 09:51:13'),
(131, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe semifinished (Qty: 100) pada Produksi ID: 12', '2025-12-07 09:51:21'),
(132, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Tag Size  (Qty: 10) ke tahap assembling pada Produksi ID: 12', '2025-12-07 10:02:38'),
(133, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 8 pada tahap assembling (Produksi ID: 12)', '2025-12-07 10:02:44'),
(134, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input PMJ45 Unfinished (Qty: 100) dari tahap assembling ke finishing pada Produksi ID: 12', '2025-12-07 10:02:51'),
(135, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Inner Box (Qty: 100) ke tahap finishing pada Produksi ID: 12', '2025-12-07 10:02:58'),
(136, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Carton Box (Qty: 10) ke tahap finishing pada Produksi ID: 12', '2025-12-07 10:03:04'),
(137, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output Finished Goods (Qty: 100) pada Produksi ID: 12', '2025-12-07 10:03:21'),
(138, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output Finished Goods (Qty: 1) pada Produksi ID: 12', '2025-12-07 10:03:31'),
(139, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 4 pada tahap finishing (Produksi ID: 12)', '2025-12-07 10:03:33'),
(140, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 2 pada tahap finishing (Produksi ID: 12)', '2025-12-07 10:03:36'),
(141, 4, 'PPIC', 'APPROVE', 'Production Process', 'Konfirmasi Proses Produksi: PROD/202512/0001. Status: Draft -> In Progress', '2025-12-07 10:03:41'),
(142, 4, 'PPIC', 'CREATE', 'Material Return', 'Membuat draft Material Return baru: RFP-202512-001', '2025-12-07 10:23:41'),
(143, 4, 'PPIC', 'APPROVE', 'Material Return', 'Konfirmasi (approve) Material Return: RFP-202512-001', '2025-12-07 10:23:46'),
(144, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2025/00001', '2025-12-10 17:38:31'),
(145, 4, 'PPIC', 'UPDATE', 'Sales Contract', 'Membatalkan Sales Contract: SC/2025/00001 (ID: 11)', '2025-12-11 02:38:46'),
(146, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2025/00002', '2025-12-11 02:51:55'),
(147, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202512/0001 untuk WO: N/A', '2025-12-11 02:56:50'),
(148, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Insole (Qty: 1000) ke tahap preparation pada Produksi ID: 13', '2025-12-11 02:57:22'),
(149, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Outsole (Qty: 1000) ke tahap preparation pada Produksi ID: 13', '2025-12-11 02:57:30'),
(150, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap preparation: item tipe semifinished (Qty: 1000) pada Produksi ID: 13', '2025-12-11 02:58:04'),
(151, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 7 pada tahap preparation (Produksi ID: 13)', '2025-12-11 02:58:34'),
(152, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input UPPER (Qty: 1000) dari tahap preparation ke assembling pada Produksi ID: 13', '2025-12-11 03:02:15'),
(153, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe semifinished (Qty: 1000) pada Produksi ID: 13', '2025-12-11 03:04:05'),
(154, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 8 pada tahap assembling (Produksi ID: 13)', '2025-12-11 03:04:14'),
(155, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input PMJ45 Unfinished (Qty: 1000) dari tahap assembling ke finishing pada Produksi ID: 13', '2025-12-11 03:04:39'),
(156, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Inner Box (Qty: 1000) ke tahap finishing pada Produksi ID: 13', '2025-12-11 03:04:44'),
(157, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output Finished Goods (Qty: 1000) pada Produksi ID: 13', '2025-12-11 03:05:22'),
(158, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 4 pada tahap finishing (Produksi ID: 13)', '2025-12-11 03:05:28'),
(159, 4, 'PPIC', 'APPROVE', 'Production Process', 'Konfirmasi Proses Produksi: PROD/202512/0001. Status: Draft -> In Progress', '2025-12-11 03:05:44'),
(160, 4, 'PPIC', 'COMPLETE', 'Production Process', 'Menyelesaikan Proses Produksi: PROD/202512/0001. Status: In Progress -> Completed', '2025-12-11 03:05:53'),
(161, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2025/00003', '2025-12-11 03:16:12'),
(162, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202512/0002 untuk WO: N/A', '2025-12-11 03:17:03'),
(163, 4, 'PPIC', 'UPDATE', 'Sales Contract', 'Memperbarui Sales Contract: SC/2025/00003 (ID: 13)', '2025-12-11 03:48:49'),
(164, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus Proses Produksi: PROD/202512/0002 (ID: 14)', '2025-12-11 03:54:02'),
(165, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202512/0002 untuk WO: N/A', '2025-12-11 03:54:13'),
(166, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Insole (Qty: 500) ke tahap preparation pada Produksi ID: 15', '2025-12-11 03:54:26'),
(167, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap preparation: item tipe semifinished (Qty: 500) pada Produksi ID: 15', '2025-12-11 03:54:37'),
(168, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 7 pada tahap preparation (Produksi ID: 15)', '2025-12-11 03:54:42'),
(169, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Outsole (Qty: 500) ke tahap assembling pada Produksi ID: 15', '2025-12-11 03:55:13'),
(170, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input UPPER (Qty: 500) dari tahap preparation ke assembling pada Produksi ID: 15', '2025-12-11 03:55:20'),
(171, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe semifinished (Qty: 500) pada Produksi ID: 15', '2025-12-11 03:55:35'),
(172, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 8 pada tahap assembling (Produksi ID: 15)', '2025-12-11 03:55:42'),
(173, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus input ID: 65 dari tahap preparation pada Produksi ID: 15', '2025-12-11 08:39:17'),
(174, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus output ID: 67 dari tahap  pada Produksi ID: 15', '2025-12-11 08:39:24'),
(175, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Insole (Qty: 500) ke tahap assembling pada Produksi ID: 15', '2025-12-11 08:39:39'),
(176, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2026/00001', '2026-01-04 12:45:41'),
(177, 16, 'Accounting', 'CREATE', 'Master Pelanggan', 'Membuat pelanggan baru: PT FOREVER YOUNG INTERNATIONAL (Kode: LOK-0005)', '2026-01-06 09:02:21'),
(178, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202601/0001 untuk WO: N/A', '2026-01-06 09:56:56'),
(179, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output Finished Goods (Qty: 20) pada Produksi ID: 16', '2026-01-06 09:57:35'),
(180, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 3 pada tahap finishing (Produksi ID: 16)', '2026-01-06 09:57:41'),
(181, 16, 'Accounting', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2026/00002', '2026-01-09 01:25:31'),
(182, 16, 'Accounting', 'UPDATE', 'Sales Contract', 'Membatalkan Sales Contract: SC/2026/00002 (ID: 15)', '2026-01-09 01:26:40'),
(183, 16, 'Accounting', 'UPDATE', 'Sales Contract', 'Membatalkan Sales Contract: SC/2026/00002 (ID: 15)', '2026-01-09 01:26:49'),
(184, 16, 'Accounting', 'UPDATE', 'Sales Contract', 'Membatalkan Sales Contract: SC/2026/00002 (ID: 15)', '2026-01-09 01:27:12'),
(185, 4, 'PPIC', 'UPDATE', 'Sales Contract', 'Membatalkan Sales Contract: SC/2026/00001 (ID: 14)', '2026-01-12 01:46:44'),
(186, 4, 'PPIC', 'UPDATE', 'Sales Contract', 'Membatalkan Sales Contract: SC/2026/00001 (ID: 14)', '2026-01-12 02:08:22'),
(187, 10, 'bc', 'EXPORT', 'Log Activity', 'Export log activity ke Excel', '2026-01-20 09:39:55'),
(188, 4, 'PPIC', 'UPDATE', 'Sales Contract', 'Membatalkan Sales Contract: SC/2026/00002 (ID: 15)', '2026-01-21 02:46:49'),
(189, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081  (Kode: UPI-0002)', '2026-01-21 08:52:52'),
(190, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512081 (Kode: UPI-0003)', '2026-01-21 08:53:37'),
(191, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512081 (Kode: UPI-0004)', '2026-01-21 08:53:56'),
(192, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512081 (Kode: UPI-0005)', '2026-01-21 08:54:12'),
(193, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512081 (Kode: UPI-0006)', '2026-01-21 08:54:44'),
(194, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512081 (Kode: UPI-0007)', '2026-01-21 08:55:25'),
(195, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512081 (Kode: UPI-0008)', '2026-01-21 08:55:43'),
(196, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512081 (Kode: UPI-0009)', '2026-01-21 08:55:54'),
(197, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512081 (Kode: UPI-0010)', '2026-01-21 08:56:11'),
(198, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512081 (Kode: UPI-0011)', '2026-01-21 08:56:33'),
(199, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081 (Kode: INI-0002)', '2026-01-21 08:57:43'),
(200, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081 (Kode: INI-0003)', '2026-01-21 08:58:07'),
(201, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Menonaktifkan material: INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081', '2026-01-21 08:58:17'),
(202, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Menonaktifkan material: INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081', '2026-01-21 08:58:40'),
(203, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512081 (Kode: INI-0004)', '2026-01-21 08:59:12'),
(204, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512081 (Kode: INI-0005)', '2026-01-21 08:59:28'),
(205, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512081 (Kode: INI-0006)', '2026-01-21 08:59:48'),
(206, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512081 (Kode: INI-0007)', '2026-01-21 09:00:00'),
(207, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512081 (Kode: INI-0008)', '2026-01-21 09:00:33'),
(208, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512081 (Kode: INI-0009)', '2026-01-21 09:00:43'),
(209, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512081 (Kode: INI-0010)', '2026-01-21 09:01:01'),
(210, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512081 (Kode: INI-0011)', '2026-01-21 09:01:20'),
(211, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512081 (Kode: INI-0012)', '2026-01-21 09:01:31'),
(212, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081 (Kode: OUI-0002)', '2026-01-21 09:03:00'),
(213, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512081 (Kode: OUI-0003)', '2026-01-21 09:03:27'),
(214, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512081 (Kode: OUI-0004)', '2026-01-21 09:03:45'),
(215, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512081 (Kode: OUI-0005)', '2026-01-21 09:03:56'),
(216, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512081 (Kode: OUI-0006)', '2026-01-21 09:04:07'),
(217, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512081 (Kode: OUI-0007)', '2026-01-22 01:10:16'),
(218, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512081 (Kode: OUI-0008)', '2026-01-22 01:10:36'),
(219, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512081 (Kode: OUI-0009)', '2026-01-22 01:10:56'),
(220, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512081 (Kode: OUI-0010)', '2026-01-22 01:11:13'),
(221, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: REIFORCE中底补强带 PO 采购订单 2512081 (Kode: ACI-0002)', '2026-01-22 01:16:39'),
(222, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: SUPER DRY 5GRAM 防潮剂-PO 采购订单 2512081 (Kode: ACI-0003)', '2026-01-22 01:20:05'),
(223, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: REIFORCE中底补强带 PO 采购订单 2512081 (ID: 49) menjadi REIFORCE中底补强带-PO 采购订单 2512081', '2026-01-22 01:21:02'),
(224, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512081 (Kode: ACI-0004)', '2026-01-22 01:33:32'),
(225, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512081 (Kode: ACI-0005)', '2026-01-22 01:33:57'),
(226, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512081 (Kode: ACI-0006)', '2026-01-22 01:34:13'),
(227, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512081 (Kode: ACI-0007)', '2026-01-22 01:34:27'),
(228, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512081 (Kode: ACI-0008)', '2026-01-22 01:34:39'),
(229, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512081 (Kode: ACI-0009)', '2026-01-22 01:35:00'),
(230, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512081 (Kode: ACI-0010)', '2026-01-22 01:35:11'),
(231, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512081 (Kode: ACI-0011)', '2026-01-22 01:35:22'),
(232, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512081 (Kode: ACI-0012)', '2026-01-22 01:35:35'),
(233, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩条-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512081 (Kode: ACI-0013)', '2026-01-22 01:35:48'),
(234, 4, 'PPIC', 'DELETE', 'Production Process', 'Menghapus Proses Produksi: PROD/202601/0001 (ID: 16)', '2026-01-23 01:10:20'),
(235, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202601/0001 untuk WO: N/A', '2026-01-23 07:29:17'),
(236, 21, 'exim1', 'CREATE', 'Master Pelanggan', 'Membuat pelanggan baru: PT.FOREVER YOUNG INTERNATIONAL (Kode: IMP-0001)', '2026-01-27 08:54:46'),
(237, 21, 'exim1', 'UPDATE', 'Master Pelanggan', 'Memperbarui data pelanggan: PT.FOREVER YOUNG INTERNATIONAL (ID: 8) menjadi PT.FOREVER YOUNG INTERNATIONAL', '2026-01-27 08:55:46'),
(238, 21, 'exim1', 'UPDATE', 'Master Pelanggan', 'Memperbarui data pelanggan: PT.FOREVER YOUNG INTERNATIONAL (ID: 8) menjadi PT.FOREVER YOUNG INTERNATIONAL', '2026-01-27 08:55:59'),
(239, 21, 'exim1', 'UPDATE', 'Master Pelanggan', 'Menonaktifkan pelanggan: PT.FOREVER YOUNG INTERNATIONAL', '2026-01-27 08:56:08'),
(240, 21, 'exim1', 'UPDATE', 'Master Pelanggan', 'Menonaktifkan pelanggan: PT.FOREVER YOUNG INTERNATIONAL', '2026-01-27 08:56:27'),
(241, 21, 'exim1', 'UPDATE', 'Master Pelanggan', 'Menonaktifkan pelanggan: PT.FOREVER YOUNG INTERNATIONAL', '2026-01-27 08:56:41'),
(242, 21, 'exim1', 'UPDATE', 'Master Pelanggan', 'Menonaktifkan pelanggan: PT.FOREVER YOUNG INTERNATIONAL', '2026-01-28 00:28:54'),
(243, 21, 'exim1', 'UPDATE', 'Master Pelanggan', 'Menonaktifkan pelanggan: PT.FOREVER YOUNG INTERNATIONAL', '2026-01-28 06:30:19'),
(244, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 中底补强带reinforce-PO 采购订单 2512081 (Kode: ACI-0014)', '2026-01-28 06:37:26'),
(245, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 5g防潮剂SUPER DRY-PO 采购订单 2512081 (Kode: ACI-0015)', '2026-01-28 06:38:03'),
(246, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512086 (Kode: UPI-0012)', '2026-01-28 06:46:56'),
(247, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512086 (Kode: UPI-0013)', '2026-01-28 06:47:17'),
(248, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512086 (Kode: UPI-0014)', '2026-01-28 06:47:34'),
(249, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512086 (Kode: UPI-0015)', '2026-01-28 06:47:47'),
(250, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512086 (Kode: UPI-0016)', '2026-01-28 06:48:01'),
(251, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512086 (Kode: UPI-0017)', '2026-01-28 06:55:13'),
(252, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512086 (Kode: UPI-0018)', '2026-01-28 06:55:37'),
(253, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512086 (Kode: UPI-0019)', '2026-01-28 06:56:13'),
(254, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512086 (Kode: UPI-0020)', '2026-01-28 06:56:32'),
(255, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512086 (ID: 70) menjadi VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512086', '2026-01-28 06:56:40'),
(256, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512086 (ID: 69) menjadi VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512086', '2026-01-28 06:56:49'),
(257, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512086 (ID: 68) menjadi VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512086', '2026-01-28 06:56:57'),
(258, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512086 (Kode: UPI-0021)', '2026-01-28 06:57:31'),
(259, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512086 (Kode: INI-0013)', '2026-01-28 07:00:32'),
(260, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512086 (Kode: INI-0014)', '2026-01-28 07:01:02'),
(261, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512086 (Kode: INI-0015)', '2026-01-28 07:01:24'),
(262, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512086 (Kode: INI-0016)', '2026-01-28 07:01:40'),
(263, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512086 (Kode: INI-0017)', '2026-01-28 07:01:56'),
(264, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512086 (Kode: INI-0018)', '2026-01-28 07:02:11'),
(265, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512086 (Kode: INI-0019)', '2026-01-28 07:02:24'),
(266, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512086 (Kode: INI-0020)', '2026-01-28 07:03:05'),
(267, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512086 (Kode: INI-0021)', '2026-01-28 07:03:16'),
(268, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE中底-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512086 (Kode: INI-0022)', '2026-01-28 07:03:26'),
(269, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512086 (Kode: OUI-0011)', '2026-01-28 07:07:46'),
(270, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512086 (Kode: OUI-0012)', '2026-01-28 07:08:34'),
(271, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512086 (Kode: OUI-0013)', '2026-01-28 07:08:51'),
(272, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512086 (Kode: OUI-0014)', '2026-01-28 07:09:06'),
(273, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512086 (Kode: OUI-0015)', '2026-01-28 07:09:57'),
(274, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 6-PO 采购订单 2512086 (Kode: OUI-0016)', '2026-01-28 07:10:18'),
(275, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 7-PO 采购订单 2512086 (Kode: OUI-0017)', '2026-01-28 07:11:09'),
(276, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 8-PO 采购订单 2512086 (Kode: OUI-0018)', '2026-01-28 07:11:24'),
(277, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 9-PO 采购订单 2512086 (Kode: OUI-0019)', '2026-01-28 07:11:36'),
(278, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BROWN画眉棕-SIZE 尺寸 10-PO 采购订单 2512086 (Kode: OUI-0020)', '2026-01-28 07:11:51'),
(279, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 中底补强带reinforce-PO 采购订单 2512086 (Kode: ACI-0016)', '2026-01-28 07:20:23'),
(280, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 塑料袋Polybag-PO 采购订单 2512086 (Kode: ACI-0017)', '2026-01-28 07:20:45'),
(281, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 2g防潮剂SUPER DRY-PO 采购订单 2512086 (Kode: ACI-0018)', '2026-01-28 07:21:10'),
(282, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 塑料袋贴标Polybag label-PO 采购订单 2512086 (Kode: ACI-0019)', '2026-01-28 07:21:33'),
(283, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: 塑料袋贴标Polybag label-PO 采购订单 2512086 (ID: 96) menjadi 塑料袋贴标Polybag label-BLACK黑色-PO 采购订单 2512086', '2026-01-28 07:21:58'),
(284, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 塑料袋贴标Polybag label-BROWN画眉棕-PO 采购订单 2512086 (Kode: ACI-0020)', '2026-01-28 07:22:20'),
(285, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 绿色外箱贴标Carton Label-绿色Green-PO 采购订单 2512086 (Kode: ACI-0021)', '2026-01-28 07:22:54'),
(286, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: 塑料袋贴标Polybag label-BROWN画眉棕-PO 采购订单 2512086 (ID: 97) menjadi 塑料袋贴标Polybag Label-BROWN画眉棕-PO 采购订单 2512086', '2026-01-28 07:23:05'),
(287, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: 塑料袋贴标Polybag label-BLACK黑色-PO 采购订单 2512086 (ID: 96) menjadi 塑料袋贴标Polybag Label-BLACK黑色-PO 采购订单 2512086', '2026-01-28 07:23:17'),
(288, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 5 -PO 采购订单 2512168 (Kode: UPI-0022)', '2026-01-28 07:39:29'),
(289, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 6 -PO 采购订单 2512168 (Kode: UPI-0023)', '2026-01-28 07:39:47'),
(290, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 7 -PO 采购订单 2512168 (Kode: UPI-0024)', '2026-01-28 07:40:09'),
(291, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 8 -PO 采购订单 2512168 (Kode: UPI-0025)', '2026-01-28 07:40:23'),
(292, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 9 -PO 采购订单 2512168 (Kode: UPI-0026)', '2026-01-28 07:40:39'),
(293, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 10 -PO 采购订单 2512168 (Kode: UPI-0027)', '2026-01-28 07:40:57'),
(294, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512168 (Kode: INI-0023)', '2026-01-29 00:48:34'),
(295, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512168 (Kode: INI-0024)', '2026-01-29 00:48:49'),
(296, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512168 (Kode: INI-0025)', '2026-01-29 00:49:24'),
(297, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512168 (Kode: INI-0026)', '2026-01-29 00:49:36'),
(298, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512168 (Kode: INI-0027)', '2026-01-29 00:49:48'),
(299, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512168 (Kode: MII-0001)', '2026-01-29 00:50:37'),
(300, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512168 (Kode: MII-0002)', '2026-01-29 00:50:50'),
(301, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512168 (Kode: MII-0003)', '2026-01-29 00:57:58'),
(302, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512168 (Kode: MII-0004)', '2026-01-29 00:58:10'),
(303, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512168 (Kode: MII-0005)', '2026-01-29 00:58:27'),
(304, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512168 (Kode: UPI-0028)', '2026-01-29 01:07:57'),
(305, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512168 (Kode: UPI-0029)', '2026-01-29 01:08:11'),
(306, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512168 (Kode: UPI-0030)', '2026-01-29 01:08:26'),
(307, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512168 (Kode: UPI-0031)', '2026-01-29 01:08:40'),
(308, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512168 (Kode: UPI-0032)', '2026-01-29 01:08:58'),
(309, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512168 (Kode: OUI-0021)', '2026-01-29 01:10:18'),
(310, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512168 (Kode: OUI-0022)', '2026-01-29 01:10:29'),
(311, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512168 (Kode: OUI-0023)', '2026-01-29 01:10:41'),
(312, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512168 (Kode: OUI-0024)', '2026-01-29 01:10:53'),
(313, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512168 (Kode: OUI-0025)', '2026-01-29 01:11:05'),
(314, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512168 (Kode: MII-0006)', '2026-01-29 01:14:31'),
(315, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512168 (Kode: MII-0007)', '2026-01-29 01:15:02'),
(316, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512168 (Kode: MII-0008)', '2026-01-29 01:16:29'),
(317, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512168 (Kode: MII-0009)', '2026-01-29 01:18:23'),
(318, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512168 (Kode: MII-0010)', '2026-01-29 01:18:37'),
(319, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512168 (Kode: INI-0028)', '2026-01-29 01:21:08'),
(320, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512168 (Kode: INI-0029)', '2026-01-29 01:21:21'),
(321, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512168 (Kode: INI-0030)', '2026-01-29 01:21:37'),
(322, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512168 (Kode: INI-0031)', '2026-01-29 01:21:47'),
(323, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512168 (Kode: INI-0032)', '2026-01-29 01:21:58'),
(324, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512168 (Kode: OUI-0026)', '2026-01-29 01:23:02'),
(325, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512168 (Kode: OUI-0027)', '2026-01-29 01:23:17'),
(326, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512168 (Kode: OUI-0028)', '2026-01-29 01:23:31'),
(327, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512168 (Kode: OUI-0029)', '2026-01-29 01:23:47'),
(328, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512168 (Kode: OUI-0030)', '2026-01-29 01:23:56'),
(329, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512169 (Kode: UPI-0033)', '2026-01-29 01:30:01'),
(330, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512169 (Kode: UPI-0034)', '2026-01-29 01:30:15'),
(331, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512169 (Kode: UPI-0035)', '2026-01-29 01:30:35'),
(332, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512169 (Kode: UPI-0036)', '2026-01-29 01:30:47'),
(333, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512169 (Kode: UPI-0037)', '2026-01-29 01:30:57'),
(334, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512169 (Kode: MII-0011)', '2026-01-29 01:36:05'),
(335, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512169 (Kode: MII-0012)', '2026-01-29 01:36:16'),
(336, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512169 (Kode: MII-0013)', '2026-01-29 01:36:26'),
(337, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512169 (Kode: MII-0014)', '2026-01-29 01:36:36');
INSERT INTO `user_logs` (`id`, `user_id`, `username`, `action`, `module`, `description`, `log_time`) VALUES
(338, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512169 (Kode: MII-0015)', '2026-01-29 01:36:46'),
(339, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512169 (Kode: INI-0033)', '2026-01-29 01:37:01'),
(340, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512169 (Kode: INI-0034)', '2026-01-29 01:37:18'),
(341, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512169 (Kode: INI-0035)', '2026-01-29 01:37:27'),
(342, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512169 (Kode: INI-0036)', '2026-01-29 01:37:41'),
(343, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512169 (Kode: INI-0037)', '2026-01-29 01:37:55'),
(344, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2512169 (Kode: OUI-0031)', '2026-01-29 01:39:36'),
(345, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2512169 (Kode: OUI-0032)', '2026-01-29 01:39:54'),
(346, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2512169 (Kode: OUI-0033)', '2026-01-29 01:40:05'),
(347, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2512169 (Kode: OUI-0034)', '2026-01-29 01:40:19'),
(348, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2512169 (Kode: OUI-0035)', '2026-01-29 01:40:34'),
(349, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169 (Kode: UPI-0038)', '2026-01-29 01:41:54'),
(350, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512169 (Kode: UPI-0039)', '2026-01-29 01:42:12'),
(351, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512169 (Kode: UPI-0040)', '2026-01-29 01:42:52'),
(352, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512169 (Kode: UPI-0041)', '2026-01-29 01:43:13'),
(353, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512169 (Kode: UPI-0042)', '2026-01-29 01:43:23'),
(354, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169 (Kode: MII-0016)', '2026-01-29 01:45:00'),
(355, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512169 (Kode: MII-0017)', '2026-01-29 01:45:12'),
(356, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512169 (Kode: MII-0018)', '2026-01-29 01:45:23'),
(357, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512169 (Kode: MII-0019)', '2026-01-29 01:45:36'),
(358, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512169 (Kode: MII-0020)', '2026-01-29 01:45:51'),
(359, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169 (Kode: INI-0038)', '2026-01-29 01:48:46'),
(360, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512169 (Kode: INI-0039)', '2026-01-29 01:49:00'),
(361, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512169 (Kode: INI-0040)', '2026-01-29 01:49:13'),
(362, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512169 (Kode: INI-0041)', '2026-01-29 01:49:24'),
(363, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512169 (Kode: INI-0042)', '2026-01-29 01:49:34'),
(364, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169 (Kode: OUI-0036)', '2026-01-29 01:49:52'),
(365, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2512169 (Kode: OUI-0037)', '2026-01-29 01:50:05'),
(366, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2512169 (Kode: OUI-0038)', '2026-01-29 01:50:20'),
(367, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2512169 (Kode: OUI-0039)', '2026-01-29 01:50:30'),
(368, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2512169 (Kode: OUI-0040)', '2026-01-29 01:50:41'),
(369, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 5-PO 采购订单 2512169 (Kode: UPI-0043)', '2026-01-29 02:05:12'),
(370, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 11-PO 采购订单 2512169 (Kode: UPI-0044)', '2026-01-29 02:05:30'),
(371, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 5-PO 采购订单 2512169 (Kode: MII-0021)', '2026-01-29 02:06:18'),
(372, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-BLACK黑色-SIZE 尺寸 11-PO 采购订单 2512169 (Kode: MII-0022)', '2026-01-29 02:06:29'),
(373, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 5-PO 采购订单 2512169 (Kode: INI-0043)', '2026-01-29 02:06:56'),
(374, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 11-PO 采购订单 2512169 (Kode: INI-0044)', '2026-01-29 02:07:07'),
(375, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 5-PO 采购订单 2512169 (Kode: UPI-0045)', '2026-01-29 02:07:37'),
(376, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 11-PO 采购订单 2512169 (Kode: UPI-0046)', '2026-01-29 02:07:47'),
(377, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 6-PO 采购订单 2512169 (Kode: MII-0023)', '2026-01-29 02:08:09'),
(378, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: MIDSOLE距台-PINK粉色-SIZE 尺寸 11-PO 采购订单 2512169 (Kode: MII-0024)', '2026-01-29 02:08:20'),
(379, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 5-PO 采购订单 2512169 (Kode: INI-0045)', '2026-01-29 02:08:43'),
(380, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 11-PO 采购订单 2512169 (Kode: INI-0046)', '2026-01-29 02:08:52'),
(381, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 5-PO 采购订单 2512169 (Kode: OUI-0041)', '2026-01-29 02:09:15'),
(382, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSIDE大底-PINK粉色-SIZE 尺寸 11-PO 采购订单 2512169 (Kode: OUI-0042)', '2026-01-29 02:09:25'),
(383, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601010 (Kode: UPI-0047)', '2026-01-29 02:12:29'),
(384, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601010 (Kode: UPI-0048)', '2026-01-29 02:12:44'),
(385, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601010 (Kode: UPI-0049)', '2026-01-29 02:13:13'),
(386, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601010 (Kode: UPI-0050)', '2026-01-29 02:13:35'),
(387, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 22601010 (Kode: OUI-0043)', '2026-01-29 02:41:39'),
(388, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 22601010 (Kode: OUI-0044)', '2026-01-29 02:42:02'),
(389, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 22601010 (Kode: OUI-0045)', '2026-01-29 02:42:17'),
(390, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 22601010 (Kode: OUI-0046)', '2026-01-29 02:42:30'),
(391, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601011 (Kode: UPI-0051)', '2026-01-29 02:45:45'),
(392, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601011 (Kode: UPI-0052)', '2026-01-29 02:46:00'),
(393, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601011 (Kode: UPI-0053)', '2026-01-29 02:46:29'),
(394, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601011 (Kode: UPI-0054)', '2026-01-29 02:46:48'),
(395, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 22601011 (Kode: OUI-0047)', '2026-01-29 02:48:32'),
(396, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 22601011 (Kode: OUI-0048)', '2026-01-29 02:48:45'),
(397, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 22601011 (Kode: OUI-0049)', '2026-01-29 02:49:08'),
(398, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 22601011 (Kode: OUI-0050)', '2026-01-29 02:49:20'),
(399, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601012 (Kode: UPI-0055)', '2026-01-29 02:51:20'),
(400, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601012 (Kode: UPI-0056)', '2026-01-29 02:51:37'),
(401, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601012 (Kode: UPI-0057)', '2026-01-29 02:51:59'),
(402, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601012 (Kode: UPI-0058)', '2026-01-29 02:52:15'),
(403, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601012 (Kode: OUI-0051)', '2026-01-29 02:56:15'),
(404, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601012 (Kode: OUI-0052)', '2026-01-29 02:56:29'),
(405, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601012 (Kode: OUI-0053)', '2026-01-29 02:56:44'),
(406, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601012 (Kode: OUI-0054)', '2026-01-29 02:56:58'),
(407, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK -GRAY黑灰-SIZE 尺寸 12/13-PO 采购订单 2601012 (Kode: UPI-0059)', '2026-01-29 02:58:18'),
(408, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 1/2-PO 采购订单 2601012 (Kode: UPI-0060)', '2026-01-29 02:58:46'),
(409, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: VAMP带面-BLACK -GRAY黑灰-SIZE 尺寸 12/13-PO 采购订单 2601012 (ID: 218) menjadi VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 12/13-PO 采购订单 2601012', '2026-01-29 02:59:00'),
(410, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 3/4-PO 采购订单 2601012 (Kode: UPI-0061)', '2026-01-29 03:04:00'),
(411, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 5/6-PO 采购订单 2601012 (Kode: UPI-0062)', '2026-01-29 03:04:20'),
(412, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 12/13-PO 采购订单 2601012 (Kode: OUI-0055)', '2026-01-29 03:05:02'),
(413, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 1/2-PO 采购订单 2601012 (Kode: OUI-0056)', '2026-01-29 03:05:16'),
(414, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 3/4-PO 采购订单 2601012 (Kode: OUI-0057)', '2026-01-29 03:05:29'),
(415, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 5/6-PO 采购订单 2601012 (Kode: OUI-0058)', '2026-01-29 03:05:47'),
(416, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601012 (Kode: UPI-0063)', '2026-01-29 03:09:04'),
(417, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601012 (Kode: UPI-0064)', '2026-01-29 03:09:20'),
(418, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601012 (Kode: UPI-0065)', '2026-01-29 03:09:41'),
(419, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601012 (Kode: UPI-0066)', '2026-01-29 03:10:20'),
(420, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601012 (Kode: OUI-0059)', '2026-01-29 03:12:12'),
(421, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601012 (Kode: OUI-0060)', '2026-01-29 03:12:40'),
(422, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601012 (Kode: OUI-0061)', '2026-01-29 03:13:40'),
(423, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601012 (Kode: OUI-0062)', '2026-01-29 03:14:14'),
(424, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 塑料袋Polybag-PO 采购订单 2601012 (Kode: ACI-0022)', '2026-01-29 03:15:14'),
(425, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 12/13-PO 采购订单 2601013 (Kode: UPI-0067)', '2026-01-29 03:24:59'),
(426, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 1/2-PO 采购订单 2601013 (Kode: UPI-0068)', '2026-01-29 03:25:19'),
(427, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸3/4-PO 采购订单 2601013 (Kode: UPI-0069)', '2026-01-29 03:25:33'),
(428, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 5/6-PO 采购订单 2601013 (Kode: UPI-0070)', '2026-01-29 03:25:47'),
(429, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: VAMP带面-BLACK黑色-SIZE 尺寸3/4-PO 采购订单 2601013 (ID: 237) menjadi VAMP带面-BLACK黑色-SIZE 尺寸 3/4-PO 采购订单 2601013', '2026-01-29 03:25:55'),
(430, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 12/13-PO 采购订单 2601013 (Kode: UPI-0071)', '2026-01-29 03:29:48'),
(431, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 1/2-PO 采购订单 2601013 (Kode: UPI-0072)', '2026-01-29 03:30:13'),
(432, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 3/4-PO 采购订单 2601013 (Kode: UPI-0073)', '2026-01-29 03:30:26'),
(433, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK GRAY黑灰-SIZE 尺寸 5/6-PO 采购订单 2601013 (Kode: UPI-0074)', '2026-01-29 03:30:37'),
(434, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 12/13-PO 采购订单 2601013 (Kode: OUI-0063)', '2026-01-29 03:35:28'),
(435, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 1/2-PO 采购订单 2601013 (Kode: OUI-0064)', '2026-01-29 03:35:55'),
(436, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 3/4-PO 采购订单 2601013 (Kode: OUI-0065)', '2026-01-29 03:36:09'),
(437, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK GRAY黑-SIZE 尺寸 5/6-PO 采购订单 2601013 (Kode: OUI-0066)', '2026-01-29 03:36:30'),
(438, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601013 (Kode: UPI-0075)', '2026-01-29 03:37:09'),
(439, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601013 (Kode: UPI-0076)', '2026-01-29 03:37:22'),
(440, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601013 (Kode: UPI-0077)', '2026-01-29 03:37:39'),
(441, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601013 (Kode: UPI-0078)', '2026-01-29 03:37:53'),
(442, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601013 (Kode: OUI-0067)', '2026-01-29 03:38:56'),
(443, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601013 (Kode: OUI-0068)', '2026-01-29 03:39:09'),
(444, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601013 (Kode: OUI-0069)', '2026-01-29 03:39:23'),
(445, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601013 (Kode: OUI-0070)', '2026-01-29 03:39:34'),
(446, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: 塑料袋Polybag-PO 采购订单 2601013 (Kode: ACI-0023)', '2026-01-29 03:40:57'),
(447, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: 塑料袋Polybag-PO 采购订单 2601012 (ID: 234) menjadi 塑料袋Polybag-PO 采购订单 2601012', '2026-01-29 03:42:48'),
(448, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: 塑料袋Polybag-PO 采购订单 2601013 (ID: 255) menjadi Polybag塑料袋-PO 采购订单 2601013', '2026-01-29 03:43:24'),
(449, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: 塑料袋Polybag-PO 采购订单 2601012 (ID: 234) menjadi Polybag塑料袋-PO 采购订单 2601012', '2026-01-29 03:43:44'),
(450, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Memperbarui material: 塑料袋Polybag-PO 采购订单 2512086 (ID: 94) menjadi Polybag塑料袋-PO 采购订单 2512086', '2026-01-29 03:44:04'),
(451, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: Carton Label外箱贴标-PO 采购订单 2601013 (Kode: ACI-0024)', '2026-01-29 03:44:55'),
(452, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601013 (Kode: UPI-0079)', '2026-01-29 03:47:07'),
(453, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601013 (Kode: UPI-0080)', '2026-01-29 03:47:52'),
(454, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601013 (Kode: UPI-0081)', '2026-01-29 03:48:15'),
(455, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601013 (Kode: UPI-0082)', '2026-01-29 03:48:45'),
(456, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Menonaktifkan material: VAMP带面-PINK粉色-SIZE 尺寸 5/6-PO 采购订单 2601013', '2026-01-29 03:50:30'),
(457, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Menonaktifkan material: VAMP带面-PINK粉色-SIZE 尺寸 3/4-PO 采购订单 2601013', '2026-01-29 03:50:35'),
(458, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Menonaktifkan material: VAMP带面-PINK粉色-SIZE 尺寸 1/2-PO 采购订单 2601013', '2026-01-29 03:50:41'),
(459, 13, 'Warehouse', 'UPDATE', 'Master Material', 'Menonaktifkan material: VAMP带面-PINK粉色-SIZE 尺寸 12/13-PO 采购订单 2601013', '2026-01-29 03:50:47'),
(460, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: UPI-0083)', '2026-01-29 03:53:14'),
(461, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: UPI-0084)', '2026-01-29 03:53:27'),
(462, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: UPI-0085)', '2026-01-29 03:53:38'),
(463, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: UPI-0086)', '2026-01-29 03:53:52'),
(464, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: UPI-0087)', '2026-01-29 03:54:15'),
(465, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: INI-0047)', '2026-01-29 03:57:48'),
(466, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: INI-0048)', '2026-01-29 03:58:06'),
(467, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: INI-0049)', '2026-01-29 03:58:20'),
(468, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: INI-0050)', '2026-01-29 03:58:33'),
(469, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: INI-0051)', '2026-01-29 03:58:47'),
(470, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: OUI-0071)', '2026-01-29 03:59:32'),
(471, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: OUI-0072)', '2026-01-29 03:59:43'),
(472, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: OUI-0073)', '2026-01-29 03:59:54'),
(473, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: OUI-0074)', '2026-01-29 04:00:11'),
(474, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: OUI-0075)', '2026-01-29 04:00:20'),
(475, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: UPI-0088)', '2026-01-29 04:02:18'),
(476, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: UPI-0089)', '2026-01-29 04:02:29'),
(477, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: UPI-0090)', '2026-01-29 04:02:43'),
(478, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: UPI-0091)', '2026-01-29 04:02:56'),
(479, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: UPI-0092)', '2026-01-29 04:03:06'),
(480, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: INI-0052)', '2026-01-29 04:03:49'),
(481, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: INI-0053)', '2026-01-29 04:04:03'),
(482, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: INI-0054)', '2026-01-29 04:04:15'),
(483, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: INI-0055)', '2026-01-29 04:04:25'),
(484, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: INI-0056)', '2026-01-29 04:04:39'),
(485, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: OUI-0076)', '2026-01-29 04:05:08'),
(486, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: OUI-0077)', '2026-01-29 04:05:19'),
(487, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: OUI-0078)', '2026-01-29 04:05:30'),
(488, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: OUI-0079)', '2026-01-29 04:05:43'),
(489, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: OUI-0080)', '2026-01-29 04:05:52'),
(490, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: ACI-0025)', '2026-01-29 04:06:46'),
(491, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: ACI-0026)', '2026-01-29 04:06:57'),
(492, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: ACI-0027)', '2026-01-29 04:07:08'),
(493, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: ACI-0028)', '2026-01-29 04:07:19'),
(494, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: ACI-0029)', '2026-01-29 04:07:29'),
(495, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: ACI-0030)', '2026-01-29 04:07:41'),
(496, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: ACI-0031)', '2026-01-29 04:08:44'),
(497, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: ACI-0032)', '2026-01-29 04:08:55'),
(498, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: ACI-0033)', '2026-01-29 04:09:05'),
(499, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: ACI-0034)', '2026-01-29 04:09:18'),
(500, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: BAND彩带-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: ACI-0035)', '2026-01-29 04:09:28'),
(501, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: ACI-0036)', '2026-01-29 04:10:24'),
(502, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: ACI-0037)', '2026-01-29 04:10:36'),
(503, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: ACI-0038)', '2026-01-29 04:10:46'),
(504, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: ACI-0039)', '2026-01-29 04:10:57'),
(505, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: ACI-0040)', '2026-01-29 04:11:06'),
(506, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602001 (Kode: ACI-0041)', '2026-01-29 04:11:39'),
(507, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602001 (Kode: ACI-0042)', '2026-01-29 04:11:50'),
(508, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602001 (Kode: ACI-0043)', '2026-01-29 04:12:05'),
(509, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602001 (Kode: ACI-0044)', '2026-01-29 04:12:16'),
(510, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: UPC Hangtag UPC吊牌-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602001 (Kode: ACI-0045)', '2026-01-29 04:12:26'),
(511, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: COO Hangtag材质吊牌-PO 采购订单 2602001 (Kode: ACI-0046)', '2026-01-29 04:12:57'),
(512, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: Plastic tie 9英寸子母扣-PO 采购订单 2602001 (Kode: ACI-0047)', '2026-01-29 04:14:11'),
(513, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: Carton Pink Label粉色外箱贴标-PO 采购订单 2602001 (Kode: ACI-0048)', '2026-01-29 04:14:54'),
(514, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: Carton Label唛头贴纸-PO 采购订单 2602001 (Kode: ACI-0049)', '2026-01-29 04:15:19'),
(515, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: Marking Sticker唛头贴纸-PO 采购订单 2602001 (Kode: ACI-0050)', '2026-01-29 04:16:08'),
(516, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602002 (Kode: UPI-0093)', '2026-01-29 06:01:46'),
(517, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602002 (Kode: UPI-0094)', '2026-01-29 06:02:06'),
(518, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602002 (Kode: UPI-0095)', '2026-01-29 06:02:32'),
(519, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602002 (Kode: UPI-0096)', '2026-01-29 06:02:55'),
(520, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602002 (Kode: UPI-0097)', '2026-01-29 06:03:39'),
(521, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602002 (Kode: INI-0057)', '2026-01-29 07:51:51'),
(522, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602002 (Kode: INI-0058)', '2026-01-29 07:52:28'),
(523, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602002 (Kode: INI-0059)', '2026-01-29 07:52:39'),
(524, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602002 (Kode: INI-0060)', '2026-01-29 07:52:48'),
(525, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602002 (Kode: INI-0061)', '2026-01-29 07:53:02'),
(526, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 6-PO 采购订单 2602002 (Kode: OUI-0081)', '2026-01-29 07:55:04'),
(527, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 7-PO 采购订单 2602002 (Kode: OUI-0082)', '2026-01-29 07:55:16'),
(528, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 8-PO 采购订单 2602002 (Kode: OUI-0083)', '2026-01-29 07:55:26'),
(529, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602002 (Kode: OUI-0084)', '2026-01-29 07:55:36'),
(530, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-BLACK黑色-SIZE 尺寸 10-PO 采购订单 2602002 (Kode: OUI-0085)', '2026-01-29 07:55:49'),
(531, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602002 (Kode: UPI-0098)', '2026-01-29 07:56:18'),
(532, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602002 (Kode: UPI-0099)', '2026-01-29 07:56:30'),
(533, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602002 (Kode: UPI-0100)', '2026-01-29 07:56:42'),
(534, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002 (Kode: UPI-0101)', '2026-01-29 07:57:19'),
(535, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002 (Kode: UPI-0102)', '2026-01-29 07:57:30'),
(536, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: VAMP带面-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002 (Kode: UPI-0103)', '2026-01-29 07:57:42'),
(537, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602002 (Kode: INI-0062)', '2026-01-29 07:58:13'),
(538, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602002 (Kode: INI-0063)', '2026-01-29 07:58:28'),
(539, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602002 (Kode: INI-0064)', '2026-01-29 07:59:33'),
(540, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002 (Kode: INI-0065)', '2026-01-29 07:59:45'),
(541, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002 (Kode: INI-0066)', '2026-01-29 07:59:56'),
(542, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 6-PO 采购订单 2602002 (Kode: OUI-0086)', '2026-01-29 08:03:59'),
(543, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 7-PO 采购订单 2602002 (Kode: OUI-0087)', '2026-01-29 08:04:10'),
(544, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 8-PO 采购订单 2602002 (Kode: OUI-0088)', '2026-01-29 08:04:23'),
(545, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002 (Kode: OUI-0089)', '2026-01-29 08:04:35'),
(546, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002 (Kode: OUI-0090)', '2026-01-29 08:04:47'),
(547, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: Ploybag塑料袋-PO 采购订单 2602002 (Kode: ACI-0051)', '2026-01-29 08:05:33'),
(548, 13, 'Warehouse', 'CREATE', 'Master Material', 'Membuat material baru: Carton Pink Label粉色外箱贴标-PO 采购订单 2602002 (Kode: ACI-0052)', '2026-01-29 08:06:10'),
(549, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: 430002323', '2026-01-30 01:13:42'),
(550, 4, 'PPIC', 'CREATE', 'Bill of Materials', 'Membuat BOM header (ID: 8) untuk FG: FLOOS-JM67', '2026-01-30 01:15:29'),
(551, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 347, Tipe: material, Qty: 1) ke BOM ID: 8', '2026-01-30 01:15:57'),
(552, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 342, Tipe: material, Qty: 1) ke BOM ID: 8', '2026-01-30 01:16:11'),
(553, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 348, Tipe: material, Qty: 1) ke BOM ID: 8', '2026-01-30 01:16:24'),
(554, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 336, Tipe: material, Qty: 1) ke BOM ID: 8', '2026-01-30 01:16:39'),
(555, 4, 'PPIC', 'UPDATE', 'Bill of Materials', 'Menambah item (ID: 305, Tipe: material, Qty: 1) ke BOM ID: 8', '2026-01-30 01:16:54'),
(556, 4, 'PPIC', 'APPROVE', 'Bill of Materials', 'Approve BOM (ID: 8) untuk FG: FLOOS-JM67. Status: Draft -> Active', '2026-01-30 01:17:11'),
(557, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202601/0001 untuk WO: N/A', '2026-01-30 01:28:39'),
(558, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001 (Qty: 100) ke tahap preparation pada Produksi ID: 18', '2026-01-30 01:28:44'),
(559, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002 (Qty: 100) ke tahap preparation pada Produksi ID: 18', '2026-01-30 01:28:59'),
(560, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap preparation: item tipe semifinished (Qty: 100) pada Produksi ID: 18', '2026-01-30 01:29:08'),
(561, 4, 'PPIC', 'APPROVE', 'Production Process', 'Konfirmasi Proses Produksi: PROD/202601/0001. Status: Draft -> In Progress', '2026-01-30 01:29:11'),
(562, 4, 'PPIC', 'UPDATE', 'Production Process', 'Membatalkan konfirmasi Proses Produksi: PROD/202601/0001. Status: In Progress -> Draft', '2026-01-30 01:30:34'),
(563, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap preparation (Produksi ID: 18)', '2026-01-30 01:30:40'),
(564, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input UPPER (Qty: 98) dari tahap preparation ke assembling pada Produksi ID: 18', '2026-01-30 01:30:51'),
(565, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002 (Qty: 98) ke tahap assembling pada Produksi ID: 18', '2026-01-30 01:30:57'),
(566, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002 (Qty: 98) ke tahap assembling pada Produksi ID: 18', '2026-01-30 01:31:02'),
(567, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe semifinished (Qty: 98) pada Produksi ID: 18', '2026-01-30 01:31:13'),
(568, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe scrap (Qty: 1) pada Produksi ID: 18', '2026-01-30 01:31:21'),
(569, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 4 pada tahap assembling (Produksi ID: 18)', '2026-01-30 01:31:26'),
(570, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap assembling (Produksi ID: 18)', '2026-01-30 01:31:29'),
(571, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input Sepatu setengah jadi (Qty: 98) dari tahap assembling ke finishing pada Produksi ID: 18', '2026-01-30 01:32:44'),
(572, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Ploybag塑料袋-PO 采购订单 2602002 (Qty: 98) ke tahap finishing pada Produksi ID: 18', '2026-01-30 01:32:52'),
(573, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output Finished Goods (Qty: 98) pada Produksi ID: 18', '2026-01-30 01:33:08'),
(574, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 4 pada tahap finishing (Produksi ID: 18)', '2026-01-30 01:33:14'),
(575, 4, 'PPIC', 'APPROVE', 'Production Process', 'Konfirmasi Proses Produksi: PROD/202601/0001. Status: Draft -> In Progress', '2026-01-30 01:33:18'),
(576, 4, 'PPIC', 'COMPLETE', 'Production Process', 'Menyelesaikan Proses Produksi: PROD/202601/0001. Status: In Progress -> Completed', '2026-01-30 01:33:41'),
(577, 4, 'PPIC', 'CREATE', 'Master Pelanggan', 'Membuat pelanggan baru: MACHMUD (Kode: LOK-0006)', '2026-01-30 01:39:28'),
(578, 4, 'PPIC', 'CREATE', 'Material Return', 'Membuat draft Material Return baru: RFP-202601-001', '2026-01-30 01:41:21'),
(579, 4, 'PPIC', 'APPROVE', 'Material Return', 'Konfirmasi (approve) Material Return: RFP-202601-001', '2026-01-30 01:41:24'),
(580, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: 4355526663', '2026-01-30 07:16:36'),
(581, 4, 'PPIC', 'CREATE', 'Sales Contract', 'Membuat Sales Contract baru: SC/2026/00001', '2026-01-30 07:18:19'),
(582, 4, 'PPIC', 'CREATE', 'Bill of Materials', 'Membuat BOM header (ID: 9) untuk FG: FLOOS-FL45', '2026-01-30 07:24:03'),
(583, 4, 'PPIC', 'CREATE', 'Production Process', 'Membuat Proses Produksi baru: PROD/202601/0002 untuk WO: N/A', '2026-01-30 07:37:48'),
(584, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: UPC Hangtag UPC吊牌-BLACK黑色-SIZE 尺寸 9-PO 采购订单 2602001 (Qty: 98) ke tahap preparation pada Produksi ID: 19', '2026-01-30 07:38:05'),
(585, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: VAMP带面-PINK粉色-SIZE 尺寸 9-PO 采购订单 2602002 (Qty: 98) ke tahap preparation pada Produksi ID: 19', '2026-01-30 07:38:21'),
(586, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap preparation: item tipe semifinished (Qty: 98) pada Produksi ID: 19', '2026-01-30 07:38:33'),
(587, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap preparation (Produksi ID: 19)', '2026-01-30 07:38:53'),
(588, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input UPPER (Qty: 98) dari tahap preparation ke assembling pada Produksi ID: 19', '2026-01-30 07:39:13'),
(589, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: INSOLE 中底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002 (Qty: 98) ke tahap assembling pada Produksi ID: 19', '2026-01-30 07:40:52'),
(590, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: OUTSOLE鞋底-PINK粉色-SIZE 尺寸 10-PO 采购订单 2602002 (Qty: 98) ke tahap assembling pada Produksi ID: 19', '2026-01-30 07:41:00'),
(591, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe semifinished (Qty: 98) pada Produksi ID: 19', '2026-01-30 07:41:18'),
(592, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output tahap assembling: item tipe scrap (Qty: 1) pada Produksi ID: 19', '2026-01-30 07:41:27'),
(593, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 4 pada tahap assembling (Produksi ID: 19)', '2026-01-30 07:41:33'),
(594, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 1 pada tahap assembling (Produksi ID: 19)', '2026-01-30 07:41:38'),
(595, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input Sepatu setengah jadi (Qty: 98) dari tahap assembling ke finishing pada Produksi ID: 19', '2026-01-30 07:41:50'),
(596, 4, 'PPIC', 'UPDATE', 'Production Process', 'Input material tambahan: Ploybag塑料袋-PO 采购订单 2602002 (Qty: 98) ke tahap finishing pada Produksi ID: 19', '2026-01-30 07:43:15'),
(597, 4, 'PPIC', 'UPDATE', 'Production Process', 'Output Finished Goods (Qty: 98) pada Produksi ID: 19', '2026-01-30 07:43:31'),
(598, 4, 'PPIC', 'QC_APPROVE', 'Production Process', 'QC Check untuk Item ID 4 pada tahap finishing (Produksi ID: 19)', '2026-01-30 07:43:37'),
(599, 4, 'PPIC', 'APPROVE', 'Production Process', 'Konfirmasi Proses Produksi: PROD/202601/0002. Status: Draft -> In Progress', '2026-01-30 07:43:44'),
(600, 4, 'PPIC', 'COMPLETE', 'Production Process', 'Menyelesaikan Proses Produksi: PROD/202601/0002. Status: In Progress -> Completed', '2026-01-30 07:43:48'),
(601, 4, 'PPIC', 'CREATE', 'Material Return', 'Membuat draft Material Return baru: RFP-202601-002', '2026-01-30 07:47:00'),
(602, 4, 'PPIC', 'APPROVE', 'Material Return', 'Konfirmasi (approve) Material Return: RFP-202601-002', '2026-01-30 07:47:04');

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
(30, 19, 342, 'material', 100.0000, 'PAIRS'),
(31, 19, 347, 'material', 100.0000, 'PAIRS'),
(32, 19, 348, 'material', 100.0000, 'PCS'),
(33, 19, 305, 'material', 100.0000, 'PCS'),
(34, 19, 336, 'material', 100.0000, 'PAIRS'),
(35, 20, 342, 'material', 100.0000, 'PAIRS'),
(36, 20, 347, 'material', 100.0000, 'PAIRS'),
(37, 20, 348, 'material', 100.0000, 'PCS'),
(38, 20, 305, 'material', 100.0000, 'PCS'),
(39, 20, 336, 'material', 100.0000, 'PAIRS');

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
(19, 'WO/202601/0001', 16, 4, 100, '2026-01-30', '2026-01-31', '', 'In Progress', 4, '2026-01-30 08:25:53', 4, '2026-01-30 08:24:29', '2026-01-30 08:25:53'),
(20, 'WO/202601/0002', 17, 4, 100, '2026-01-30', '2026-01-31', '', 'In Progress', 4, '2026-01-30 14:34:37', 4, '2026-01-30 14:33:20', '2026-01-30 14:34:37');

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
  ADD KEY `shipped_by_user` (`shipped_by_user`),
  ADD KEY `idx_exim_verified` (`exim_verified`);

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
  ADD KEY `grn_header_ibfk_3` (`confirmed_by`),
  ADD KEY `idx_exim_verified` (`exim_verified`);

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
  ADD KEY `status` (`status`),
  ADD KEY `idx_exim_verified` (`exim_verified`);

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
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `fk_subkon_out_exim_verifier` (`exim_verified_by`),
  ADD KEY `idx_subkon_out_exim_verified` (`exim_verified`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `ap_invoice_header`
--
ALTER TABLE `ap_invoice_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `ap_payment_details`
--
ALTER TABLE `ap_payment_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `ap_payment_header`
--
ALTER TABLE `ap_payment_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `ar_invoice_details`
--
ALTER TABLE `ar_invoice_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `ar_invoice_header`
--
ALTER TABLE `ar_invoice_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `ar_receipt_details`
--
ALTER TABLE `ar_receipt_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `ar_receipt_header`
--
ALTER TABLE `ar_receipt_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `bom_details`
--
ALTER TABLE `bom_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `bom_header`
--
ALTER TABLE `bom_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT untuk tabel `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `fg_delivery_details`
--
ALTER TABLE `fg_delivery_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT untuk tabel `fg_delivery_header`
--
ALTER TABLE `fg_delivery_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `fg_receipt_details`
--
ALTER TABLE `fg_receipt_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `fg_receipt_header`
--
ALTER TABLE `fg_receipt_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `finished_goods`
--
ALTER TABLE `finished_goods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `grn_details`
--
ALTER TABLE `grn_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT untuk tabel `grn_header`
--
ALTER TABLE `grn_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT untuk tabel `inventories`
--
ALTER TABLE `inventories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `issuance_details`
--
ALTER TABLE `issuance_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `journal_details`
--
ALTER TABLE `journal_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- AUTO_INCREMENT untuk tabel `journal_header`
--
ALTER TABLE `journal_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT untuk tabel `machines`
--
ALTER TABLE `machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `master_scrap`
--
ALTER TABLE `master_scrap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `materials`
--
ALTER TABLE `materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=350;

--
-- AUTO_INCREMENT untuk tabel `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT untuk tabel `mir_details`
--
ALTER TABLE `mir_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT untuk tabel `mir_header`
--
ALTER TABLE `mir_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `modules`
--
ALTER TABLE `modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `mr_details`
--
ALTER TABLE `mr_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT untuk tabel `mr_header`
--
ALTER TABLE `mr_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `po_header`
--
ALTER TABLE `po_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT untuk tabel `production_header`
--
ALTER TABLE `production_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `production_inputs`
--
ALTER TABLE `production_inputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT untuk tabel `production_outputs`
--
ALTER TABLE `production_outputs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT untuk tabel `production_output_sizes`
--
ALTER TABLE `production_output_sizes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `prod_return_detail`
--
ALTER TABLE `prod_return_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT untuk tabel `prod_return_header`
--
ALTER TABLE `prod_return_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT untuk tabel `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2764;

--
-- AUTO_INCREMENT untuk tabel `sales_contracts`
--
ALTER TABLE `sales_contracts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT untuk tabel `scrap_in_detail`
--
ALTER TABLE `scrap_in_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `scrap_in_detail_sources`
--
ALTER TABLE `scrap_in_detail_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT untuk tabel `scrap_in_header`
--
ALTER TABLE `scrap_in_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `scrap_out_detail`
--
ALTER TABLE `scrap_out_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT untuk tabel `scrap_out_header`
--
ALTER TABLE `scrap_out_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `scrap_stock`
--
ALTER TABLE `scrap_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `semifinished_goods`
--
ALTER TABLE `semifinished_goods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `setting_accounting`
--
ALTER TABLE `setting_accounting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `stock_ledger`
--
ALTER TABLE `stock_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=399;

--
-- AUTO_INCREMENT untuk tabel `stock_on_hand`
--
ALTER TABLE `stock_on_hand`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT untuk tabel `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=603;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT untuk tabel `wo_header`
--
ALTER TABLE `wo_header`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

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
-- Ketidakleluasaan untuk tabel `subkon_out_header`
--
ALTER TABLE `subkon_out_header`
  ADD CONSTRAINT `fk_subkon_out_exim_verifier` FOREIGN KEY (`exim_verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

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
