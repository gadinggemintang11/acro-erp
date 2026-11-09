-- =====================================================================
-- Migration: Tahap Proses Produksi Acropoly (2026-11-08)
--
-- Alur NSI     : Grinding -> Assembling -> Finishing            (3 tahap)
-- Alur Acropoly: Cutting  -> Sewing -> Assembling -> Finishing  (4 tahap)
--
-- Kode tahap disimpan di kolom stage_name (production_inputs &
-- production_outputs). Kode program sekarang mengenal
-- 'cutting' / 'sewing' / 'assembling' / 'finishing', jadi data lama:
--   grinding   -> cutting    (sama-sama tahap 1: ambil material dari MR/WIP)
--   assembling -> assembling (tetap; untuk input baru sumbernya hasil Sewing)
--   finishing  -> finishing  (tetap)
-- Tanpa ini, data tahap Grinding lama tidak tampil di halaman mana pun.
--
-- Waktu proses per tahap (user 2026-11-08: "kadang ga semua input hari ini
-- ... bisa aja itu produksi lama"): tabel production_stage_times menyimpan
-- Mulai & Selesai tiap tahap per dokumen produksi. Semua input/output tahap
-- itu tercatat di stok WIP (stock_ledger.transaction_date) pada waktu Mulai
-- tahapnya, bukan waktu user mengetik di sistem.
--
-- Aman dijalankan ulang (CREATE IF NOT EXISTS; UPDATE hanya nilai lama).
-- =====================================================================

-- DDL di luar transaksi (CREATE TABLE memicu implicit commit di MySQL).
CREATE TABLE IF NOT EXISTS production_stage_times (
    id                   INT(11)     NOT NULL AUTO_INCREMENT,
    production_header_id INT(11)     NOT NULL,
    stage_name           VARCHAR(50) NOT NULL COMMENT 'cutting / sewing / assembling / finishing',
    started_at           DATETIME    NULL COMMENT 'Waktu mulai tahap -- tanggal transaksi stok WIP tahap ini',
    finished_at          DATETIME    NULL COMMENT 'Waktu selesai tahap (opsional)',
    updated_by           INT(11)     NULL,
    updated_at           DATETIME    NULL,
    created_at           DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pst_header_stage (production_header_id, stage_name),
    CONSTRAINT fk_pst_header FOREIGN KEY (production_header_id)
        REFERENCES production_header (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

START TRANSACTION;

UPDATE production_inputs  SET stage_name = 'cutting' WHERE stage_name = 'grinding';
UPDATE production_outputs SET stage_name = 'cutting' WHERE stage_name = 'grinding';

-- Daftar kunci bahasa (menu Developer > Bahasa): buang kunci tahap NSI yang
-- sudah tidak ada di file bahasa, KECUALI yang pernah diberi teks custom.
DELETE FROM lang_overrides
WHERE custom_value IS NULL
  AND lang_key IN (
      'pp_stage_grinding', 'pp_tab_1_grinding', 'pp_tab_2_grinding',
      'pp_tab_2_assembling_g', 'pp_tab_3_finishing_g', 'pqc_stage_grinding'
  );

INSERT INTO schema_migrations (filename, status, statements_run, message, applied_at)
SELECT 'migration_tahap_produksi_acropoly.sql', 'applied', 4, 'Tahap produksi Acropoly: cutting -> sewing -> assembling -> finishing (data grinding -> cutting) + tabel production_stage_times', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM schema_migrations WHERE filename = 'migration_tahap_produksi_acropoly.sql');

COMMIT;

-- Verifikasi:
--   SELECT stage_name, COUNT(*) FROM production_inputs  GROUP BY stage_name;
--   SELECT stage_name, COUNT(*) FROM production_outputs GROUP BY stage_name;
