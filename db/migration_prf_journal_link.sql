-- ============================================================
-- MIGRATION: Link PRF ke Jurnal Umum.
-- prf_header.journal_header_id dicatat saat PRF ditarik jadi Jurnal Umum
-- (menu Jurnal Umum > Create > "Tarik dari PRF"), supaya PRF yang sama
-- tidak bisa ditarik/dijurnal dua kali. NULL lagi kalau draft jurnalnya
-- dihapus (lihat JurnalUmum::delete()).
-- Idempotent: aman di-rerun.
-- ============================================================

ALTER TABLE `prf_header`
  ADD COLUMN IF NOT EXISTS `journal_header_id` INT(11) NULL DEFAULT NULL
    COMMENT 'FK ke journal_header.id kalau PRF ini sudah ditarik jadi Jurnal Umum (cegah double-journal)';

-- Verifikasi
SHOW COLUMNS FROM `prf_header` LIKE 'journal_header_id';
