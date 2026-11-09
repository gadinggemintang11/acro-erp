-- ============================================================
-- Rincian NG material: 4 kategori penyebab + kode NG
-- ------------------------------------------------------------
-- Acuan: assets/DAILY PRODUCTION REPORT (PQC & MATERIAL) (1).xlsx,
-- sheet MATERIAL & PIVOT (dikirim klien 2026-09-30).
--
-- Sheet MATERIAL mencatat tiap material NG dengan 4 kolom penyebab
-- (HUMAN ERROR / MATERIAL ISSUE / MECHANIC ISSUE / OTHERS ISSUE) yang
-- dijumlahkan jadi TOTAL, plus NOT GOOD CODE, REASON DEFECT & REMARK.
-- Sheet PIVOT merekap 3 di antaranya per IPO/model/part.
--
-- User 2026-09-30: "itukan sebenarnya sifatnya remark aja, angkanya
-- disimpan sebagai catatan material itu NG nya dimana" -- karena itu
-- angka-angka ini TIDAK menyentuh stok, ledger, maupun jurnal. Murni
-- catatan untuk laporan. MECHANIC ISSUE tetap dibuat walau di file klien
-- nilainya 0 di seluruh 2.237 baris, atas permintaan user.
--
-- Dua titik input, satu struktur yang sama:
--   1. production_scrap_sources  -- material yang jadi scrap saat proses
--      produksi (sumber utama: punya rantai ke WO -> IPO & model).
--   2. issuance_qc_results       -- material reject saat QC penerimaan
--      produksi (pelengkap: mr_header tidak menyimpan WO/IPO).
--
-- Kolom `notes` di production_scrap_sources sudah ada sejak awal tapi
-- TIDAK PERNAH ditulis -- mulai sekarang dipakai sebagai REMARK.
--
-- Idempotent: aman dijalankan berulang.
-- ============================================================

-- ------------------------------------------------------------
-- 1) Kolom rincian NG di production_scrap_sources
-- ------------------------------------------------------------
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_scrap_sources' AND COLUMN_NAME = 'ng_human'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `production_scrap_sources` ADD COLUMN `ng_human` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `source_qty`",
    "SELECT 'production_scrap_sources.ng_human sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_scrap_sources' AND COLUMN_NAME = 'ng_material'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `production_scrap_sources` ADD COLUMN `ng_material` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `ng_human`",
    "SELECT 'production_scrap_sources.ng_material sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_scrap_sources' AND COLUMN_NAME = 'ng_mechanic'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `production_scrap_sources` ADD COLUMN `ng_mechanic` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `ng_material`",
    "SELECT 'production_scrap_sources.ng_mechanic sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_scrap_sources' AND COLUMN_NAME = 'ng_others'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `production_scrap_sources` ADD COLUMN `ng_others` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `ng_mechanic`",
    "SELECT 'production_scrap_sources.ng_others sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_scrap_sources' AND COLUMN_NAME = 'ng_code'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `production_scrap_sources` ADD COLUMN `ng_code` VARCHAR(20) NULL DEFAULT NULL AFTER `ng_others`",
    "SELECT 'production_scrap_sources.ng_code sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ------------------------------------------------------------
-- 2) Kolom rincian NG di issuance_qc_results
-- ------------------------------------------------------------
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'issuance_qc_results' AND COLUMN_NAME = 'ng_human'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `issuance_qc_results` ADD COLUMN `ng_human` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `reject_qty`",
    "SELECT 'issuance_qc_results.ng_human sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'issuance_qc_results' AND COLUMN_NAME = 'ng_material'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `issuance_qc_results` ADD COLUMN `ng_material` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `ng_human`",
    "SELECT 'issuance_qc_results.ng_material sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'issuance_qc_results' AND COLUMN_NAME = 'ng_mechanic'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `issuance_qc_results` ADD COLUMN `ng_mechanic` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `ng_material`",
    "SELECT 'issuance_qc_results.ng_mechanic sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'issuance_qc_results' AND COLUMN_NAME = 'ng_others'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `issuance_qc_results` ADD COLUMN `ng_others` DECIMAL(12,4) NOT NULL DEFAULT 0.0000 AFTER `ng_mechanic`",
    "SELECT 'issuance_qc_results.ng_others sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'issuance_qc_results' AND COLUMN_NAME = 'ng_code'
);
SET @sql := IF(@col = 0,
    "ALTER TABLE `issuance_qc_results` ADD COLUMN `ng_code` VARCHAR(20) NULL DEFAULT NULL AFTER `ng_others`",
    "SELECT 'issuance_qc_results.ng_code sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ------------------------------------------------------------
-- 3) Master kode NG (43 kode, sheet "DATA NG")
-- ------------------------------------------------------------
-- Ditaruh di common_code supaya bisa ditambah/diubah lewat menu Common Code
-- seperti master lain, tanpa menyentuh kode program. Isi `description` =
-- "<Mandarin> | <English>", persis kolom REASON DEFECT di file klien.
-- Penjelasan bahasa Indonesia di file klien tidak ikut (kolom description
-- hanya 100 karakter; yang terpanjang di sini 74).
--
-- Catatan: sudah ada group 'NG_CATEGORY' berisi 1 baris placeholder
-- auto-generate yang tidak dipakai di kode mana pun -- itu hal berbeda dan
-- sengaja TIDAK disentuh di sini.
INSERT INTO `common_code` (`group_code`, `group_description`, `code`, `description`, `status`)
SELECT 'NG_CODE', 'Kode NG / Defect', v.code, v.description, 'active'
FROM (
    SELECT 'A' AS code, '死机 / 不显示 | NOT FUNCTIONING' AS description
    UNION ALL SELECT 'B' , '开关失控 / 接触不良 | SWITCH BUTTON ERROR'
    UNION ALL SELECT 'C' , '噪音 / 杂音 | NOISE/ROUGH SOUND'
    UNION ALL SELECT 'D' , '机内异响 | FOREIGN OBJECT SOUNDS'
    UNION ALL SELECT 'E1' , '功率高 | HIGH POWER'
    UNION ALL SELECT 'E2' , '功率低 | LOW POWER'
    UNION ALL SELECT 'E3' , '电流高 | HIGH CURRENT'
    UNION ALL SELECT 'E4' , '电流低 | LOW CURRENT'
    UNION ALL SELECT 'F' , '刀头外观不良 | POOR BLADE APPEARANCE/RUSTY BLADE/BROKEN TEETH'
    UNION ALL SELECT 'F1' , '刀斜 | MISALIGNED BLADE'
    UNION ALL SELECT 'F2' , '刀平 | VALVE NOT FIT PROPERLY'
    UNION ALL SELECT 'G' , '开关/手柄/推进不良 | SWITCH MECHANIM/PUSH BUTTON DAMAGED'
    UNION ALL SELECT 'G1' , '开关松/紧不良 | CONTROL SWITCH LOOSE/NOT PROPERLY TIGHTENED'
    UNION ALL SELECT 'H' , '显示不良 | INCOMPLETE APPEARANCE'
    UNION ALL SELECT 'I' , '剪毛不良 | POOR CUTTING PERFORMANCE/BLUNT RAZOR BLADE'
    UNION ALL SELECT 'J' , '焊锡不良 | DEFECTIVE SOLDERING/DAMAGED SOLDER JOINT'
    UNION ALL SELECT 'K' , 'LED灯不良 | DAMAGED LED LAMP'
    UNION ALL SELECT 'L' , '螺丝不良 | DAMAGED SCREW (STRIPPED THREAD, TILTED, NOT TIGHTENED)'
    UNION ALL SELECT 'M' , '配合不良 | INCORRECT PART/WRONG CASING'
    UNION ALL SELECT 'N' , '未盖到位/错位 | WRONG COVER/UPPER & LOWER CASING MISALIGNED'
    UNION ALL SELECT 'O' , '花机 | MACHINE CASING DEFECT/APPEARANCE ISSUE'
    UNION ALL SELECT 'Q' , '移印不良 | POOR PAD PRINTING/UNCLEAR LOGO'
    UNION ALL SELECT 'R' , '喷油/电镀不良 | POOR OILING INJECTION/COATING/SCRATCHES ON SURFACE'
    UNION ALL SELECT 'R+' , '喷油/电镀不良 | POOR OILING INJECTION/COATING/SCRATCHES ON SURFACE'
    UNION ALL SELECT 'L3' , '扭力不良 扭力不良 | POOR TORQUE/TIGHTENED NOT STANDARD'
    UNION ALL SELECT 'T' , '外观不良 | POOR APPEARANCE'
    UNION ALL SELECT 'T3' , '批锋不良 | FLASH/MOLDING DEFECT (EXCESS MATERIAL, SHARP EDGES)'
    UNION ALL SELECT 'V' , '颜色不良 | POOR COLOR'
    UNION ALL SELECT 'A1' , 'SCRATCH (besides blade)'
    UNION ALL SELECT 'A2' , 'DENTED'
    UNION ALL SELECT 'I2' , 'DIRTY PRODUCTS/HAIR CUTTINGS'
    UNION ALL SELECT 'T2' , 'DAMAGED/BROKEN/CRACKED INJECTION MOLDS'
    UNION ALL SELECT 'L1' , 'DEFECTIVE SPRINGS (SHORT, CROOKED, ABNORMAL SHAPE)'
    UNION ALL SELECT 'P' , 'ACCESSORIES/MATERIALS (INCORRECT, INSUFFICIENT, EXCESSIVE)'
    UNION ALL SELECT 'V1' , 'WEIGHT NOT AS SPECIFIED'
    UNION ALL SELECT 'V2' , 'PACKAGING/LABEL/PLASTIC/BAG (TORN, DAMAGED, CREASED, DIRTY)'
    UNION ALL SELECT 'V2+' , 'PACKAGING/LABEL/PLASTIC/BAG (TORN, DAMAGED, CREASED, DIRTY)'
    UNION ALL SELECT 'V4' , 'ADHESION DEFECTS (INSUFFICIENT,EXCESSIVE,SKEWED,MISALIGNED,ABNORMAL SHAPE)'
    UNION ALL SELECT 'Z1' , 'ABNORMAL PRINTING (SKEWED, INCORRECT COLOR, DELAMINATION, FADING)'
    UNION ALL SELECT 'Z3' , 'STAMPING DEFECTS (UNCLEAR, INK SMUDGES, SKEWED, COVERED)'
    UNION ALL SELECT 'na' , 'Other issue'
    UNION ALL SELECT 'MIN' , 'MINUS QUANTITY FROM WAREHOUSE'
    UNION ALL SELECT 'PLUS' , 'PLUS QUANTITY FROM WAREHOUSE'
) v
WHERE NOT EXISTS (
    SELECT 1 FROM `common_code` c WHERE c.group_code = 'NG_CODE' AND c.code = v.code
);

-- ------------------------------------------------------------
-- 4) Menu laporan
-- ------------------------------------------------------------
INSERT INTO `menus` (`menu_name`, `department`, `menu_key`, `order_no`)
SELECT 'Daily Material NG Report', 'QUALITY', 'material_ng_report', 202
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `menu_key` = 'material_ng_report');

-- Hak akses menyusul yang sudah punya QA Report (orang yang sama). Hanya
-- baris yang BELUM ada yang dibuat, jadi pengaturan Admin tidak tertimpa.
-- Kolom yang disebut SENGAJA hanya role_id, menu_id & can_view -- menyebut
-- kolom lain (mis. can_submit) membuat perintah ini gagal di database yang
-- migrasi Role Level-nya belum jalan.
INSERT INTO `role_permissions` (`role_id`, `menu_id`, `can_view`)
SELECT rp.role_id,
       (SELECT id FROM menus WHERE menu_key = 'material_ng_report'),
       rp.can_view
FROM `role_permissions` rp
WHERE rp.menu_id = (SELECT id FROM menus WHERE menu_key = 'qa_report')
  AND rp.can_view = 1
  AND NOT EXISTS (
        SELECT 1 FROM `role_permissions` x
        WHERE x.role_id = rp.role_id
          AND x.menu_id = (SELECT id FROM menus WHERE menu_key = 'material_ng_report')
  );

-- Samakan `all_dept_actions` baris baru di atas dengan baris lain: migrasi
-- Role Level tahap 3 mengisi SEMUA baris lama dengan 'legacy' (= aksi berlaku
-- semua departemen, perilaku lama). Baris yang dibuat INSERT di atas tidak
-- menyebut kolom itu sehingga jatuh ke NULL -- artinya "dibatasi departemen
-- role", beda sendiri dari 1.862 baris lainnya. Menu ini memang tidak memakai
-- penyaringan departemen, jadi sekarang tidak berdampak; diseragamkan supaya
-- tidak jadi kejutan kalau nanti menu ini ikut dibatasi per departemen.
-- Dijaga: hanya jalan kalau kolomnya memang ada (DB yang belum menjalankan
-- migrasi Role Level tahap 3 tidak punya kolom ini).
SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions' AND COLUMN_NAME = 'all_dept_actions'
);
SET @sql := IF(@col > 0,
    "UPDATE `role_permissions` rp
        JOIN `menus` m ON m.id = rp.menu_id
         SET rp.all_dept_actions = 'legacy'
       WHERE m.menu_key = 'material_ng_report' AND rp.all_dept_actions IS NULL",
    "SELECT 'kolom all_dept_actions belum ada, dilewati' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
