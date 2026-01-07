-- =====================================================
-- TABLE: year_end_closings
-- Untuk mencatat proses tutup buku tahunan
-- =====================================================

CREATE TABLE IF NOT EXISTS `year_end_closings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `closing_year` int(4) NOT NULL COMMENT 'Tahun yang ditutup (e.g., 2025)',
  `closing_date` date NOT NULL COMMENT 'Tanggal tutup buku (biasanya 31 Des)',
  `retained_earnings_account_id` int(11) NOT NULL COMMENT 'ID akun Laba Ditahan',
  `revenue_total` decimal(15,2) DEFAULT 0.00 COMMENT 'Total Pendapatan tahun tersebut',
  `expense_total` decimal(15,2) DEFAULT 0.00 COMMENT 'Total Beban tahun tersebut',
  `net_income` decimal(15,2) DEFAULT 0.00 COMMENT 'Laba Bersih (Revenue - Expense)',
  `journal_header_id` int(11) DEFAULT NULL COMMENT 'ID jurnal penutup yang dibuat',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_closing_year` (`closing_year`),
  KEY `idx_retained_earnings` (`retained_earnings_account_id`),
  KEY `idx_journal_header` (`journal_header_id`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_yec_retained_earnings` FOREIGN KEY (`retained_earnings_account_id`) REFERENCES `chart_of_accounts` (`id`),
  CONSTRAINT `fk_yec_journal_header` FOREIGN KEY (`journal_header_id`) REFERENCES `journal_header` (`id`),
  CONSTRAINT `fk_yec_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE: Buat akun Retained Earnings jika belum ada
-- =====================================================

INSERT INTO `chart_of_accounts` 
  (`account_code`, `account_name`, `account_type`, `normal_balance`, `is_header`, `parent_id`, `status`, `is_postable`) 
VALUES 
  ('3200', 'Laba Ditahan (Retained Earnings)', 'Equity', 'Kredit', 0, NULL, 'active', 1)
ON DUPLICATE KEY UPDATE account_name = account_name;
