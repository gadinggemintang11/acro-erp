-- ============================================================
-- Atur Tata Letak PDF (Developer Mode)
-- ------------------------------------------------------------
-- User 2026-09-10: "bisa ga ya developer mode dikembangkan lebih canggih
-- untuk atur template PDF di PRF di ERF dll, bisa atur letak digeser-geser
-- dan dibesarkan font atau dll?"
--
-- Yang dibangun: TAHAP 1 — kontrol ANGKA (ukuran font, margin, tinggi baris,
-- ukuran logo/stempel), BUKAN kanvas geser-geser bebas. Alasannya tercatat
-- di helpers/pdf_layout_helper.php: template PRF/ERF berbasis tabel HTML dan
-- Mpdf di proyek ini sudah terbukti tidak andal untuk posisi absolut.
-- Dengan kontrol angka, pagination tetap diurus Mpdf sendiri sehingga
-- pengaturan user tidak bisa merusak alur halaman.
--
-- 1 baris = 1 setelan pada 1 template. Setelan yang TIDAK ada barisnya
-- otomatis memakai nilai bawaan di helper — jadi tabel kosong = tampilan
-- PDF persis seperti sebelum fitur ini ada.
-- ============================================================

CREATE TABLE IF NOT EXISTS `pdf_layout_overrides` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `template_key`  VARCHAR(64)  NOT NULL COMMENT 'mis. prf, erf',
    `setting_key`   VARCHAR(64)  NOT NULL COMMENT 'mis. font_body, margin_top',
    `setting_value` VARCHAR(32)  NOT NULL,
    `updated_by`    INT(11)      NULL,
    `updated_at`    DATETIME     NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_pdf_layout` (`template_key`, `setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
