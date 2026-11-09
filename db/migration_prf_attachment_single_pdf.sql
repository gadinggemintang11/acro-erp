-- ============================================================
-- MIGRATION: Lampiran PRF — 1 file upload = 1 baris lampiran.
--
-- Sebelumnya PDF multi-halaman dipecah di browser jadi 1 JPEG per halaman,
-- masing-masing jadi baris sendiri di prf_attachments (PDF 31 halaman = 31
-- lampiran). Alasannya: FPDI versi gratis (dipakai mPDF saat export PRF)
-- tidak bisa membaca PDF 1.5+ yang memakai compressed object stream, dan
-- server tidak punya Ghostscript/qpdf/Imagick untuk mengonversi.
--
-- Sekarang PDF disimpan utuh apa adanya. Kalau ternyata FPDI tidak sanggup
-- membacanya, browser merender halamannya dan server merakit ulang jadi SATU
-- PDF 1.4 yang dipakai KHUSUS untuk export — disimpan di render_path,
-- sementara file asli tetap utuh untuk dilihat/diunduh user.
--
-- Idempotent: aman di-rerun.
-- ============================================================

ALTER TABLE `prf_attachments`
  ADD COLUMN IF NOT EXISTS `render_path` VARCHAR(500) NULL DEFAULT NULL
    COMMENT 'PDF hasil rakitan (1.4) khusus dipakai saat export PRF, kalau file asli tidak bisa dibaca FPDI. NULL = pakai file_path apa adanya',
  ADD COLUMN IF NOT EXISTS `page_count` INT(11) NULL DEFAULT NULL
    COMMENT 'Jumlah halaman PDF, untuk ditampilkan di daftar lampiran. NULL untuk gambar';

-- Verifikasi
SHOW COLUMNS FROM `prf_attachments` LIKE 'render_path';
SHOW COLUMNS FROM `prf_attachments` LIKE 'page_count';
