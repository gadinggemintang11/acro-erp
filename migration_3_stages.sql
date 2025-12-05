-- =====================================================
-- MIGRATION SCRIPT: 4 Stages to 3 Stages
-- Mengubah Cutting -> Preparation
-- Menghapus Sewing (digabung ke Preparation atau Assembling)
-- =====================================================

-- BACKUP TERLEBIH DAHULU sebelum menjalankan script ini!
-- Uncomment line berikut jika ingin membuat backup table:
-- CREATE TABLE production_inputs_backup AS SELECT * FROM production_inputs;
-- CREATE TABLE production_outputs_backup AS SELECT * FROM production_outputs;

-- =====================================================
-- 1. UPDATE STAGE NAME di tabel production_inputs
-- =====================================================
UPDATE production_inputs 
SET stage_name = 'preparation' 
WHERE stage_name = 'cutting';

-- Jika ada data sewing, pindahkan ke preparation atau assembling
-- (Pilih salah satu sesuai kebutuhan bisnis)
-- Opsi A: Pindahkan sewing ke preparation
UPDATE production_inputs 
SET stage_name = 'preparation' 
WHERE stage_name = 'sewing';

-- Opsi B: Pindahkan sewing ke assembling (uncomment jika pilih ini)
-- UPDATE production_inputs 
-- SET stage_name = 'assembling' 
-- WHERE stage_name = 'sewing';

-- =====================================================
-- 2. UPDATE STAGE NAME di tabel production_outputs
-- =====================================================
UPDATE production_outputs 
SET stage_name = 'preparation' 
WHERE stage_name = 'cutting';

-- Pindahkan sewing ke preparation atau assembling
-- Opsi A: Pindahkan sewing ke preparation
UPDATE production_outputs 
SET stage_name = 'preparation' 
WHERE stage_name = 'sewing';

-- Opsi B: Pindahkan sewing ke assembling (uncomment jika pilih ini)
-- UPDATE production_outputs 
-- SET stage_name = 'assembling' 
-- WHERE stage_name = 'sewing';

-- =====================================================
-- 3. VERIFIKASI DATA (Optional - untuk cek hasil)
-- =====================================================
-- Cek distribusi stage_name di production_inputs
SELECT stage_name, COUNT(*) as total 
FROM production_inputs 
GROUP BY stage_name 
ORDER BY stage_name;

-- Cek distribusi stage_name di production_outputs
SELECT stage_name, COUNT(*) as total 
FROM production_outputs 
GROUP BY stage_name 
ORDER BY stage_name;

-- =====================================================
-- 4. UPDATE CONSTRAINT/INDEX jika ada (Optional)
-- =====================================================
-- Jika ada constraint atau index yang mereferensi nilai 'cutting' atau 'sewing',
-- update di sini. Contoh:
-- ALTER TABLE production_inputs DROP CHECK chk_stage_name;
-- ALTER TABLE production_inputs ADD CONSTRAINT chk_stage_name 
--     CHECK (stage_name IN ('preparation', 'assembling', 'finishing'));

-- =====================================================
-- CATATAN PENTING:
-- =====================================================
-- 1. Pastikan backup database sudah dibuat
-- 2. Jalankan script ini di database testing terlebih dahulu
-- 3. Verifikasi hasilnya sebelum apply ke production
-- 4. Update juga aplikasi/code sebelum migrasi database
-- 5. Informasikan ke user tentang perubahan proses produksi
-- =====================================================

-- ROLLBACK jika diperlukan (kembalikan dari backup):
-- DROP TABLE production_inputs;
-- DROP TABLE production_outputs;
-- CREATE TABLE production_inputs AS SELECT * FROM production_inputs_backup;
-- CREATE TABLE production_outputs AS SELECT * FROM production_outputs_backup;
