-- ============================================================
-- Rebrand: PT New Specialife Industries (NSI) -> PT Acropoly International
-- ------------------------------------------------------------
-- User 2026-11-05: repo ini bekas milik PT NEW SPECIALIFE INDUSTRIES, sekarang
-- milik PT ACROPOLY INTERNATIONAL (Kawasan Industri Jatengland Industrial
-- Park Sayung (JIPS) Blok V5C, Sayung, Kabupaten Demak, Jawa Tengah).
--
-- Nilai bawaan baru sudah ditulis di kode (company_info() di
-- site_asset_helper.php + berkas bahasa). Migrasi ini cuma MEMBERSIHKAN sisa
-- NSI yang tersimpan di DB, karena baris DB selalu menimpa nilai bawaan:
--
--  1. lang_overrides -- teks yang pernah disimpan lewat Developer Mode
--     "Kelola Teks" masih berisi nama/kode NSI. Baris yang isinya sama dengan
--     bawaan lama DIHAPUS (supaya berkas bahasa per-bahasa yang dipakai lagi),
--     sisanya nama perusahaannya diganti di tempat.
--  2. site_settings -- identitas perusahaan yang masih bernilai NSI dihapus,
--     jadi kembali ke bawaan Acropoly di kode. Nilai yang SUDAH diubah user ke
--     Acropoly tidak disentuh.
--
-- TIDAK menyentuh data transaksi (PRF/ERF/jurnal/supplier/user) -- itu
-- catatan sejarah yang memang terjadi di masa NSI.
-- TIDAK menyentuh site_assets (logo unggahan): gambar unggahan tidak bisa
-- dikenali isinya dari SQL. Cek manual di Developer Mode > Kelola Logo &
-- Gambar, klik "Reset ke Default" utk slot yang masih logo NSI.
--
-- Idempotent: aman dijalankan ulang.
-- ============================================================

-- Catatan: teks NSI bisa ada di `custom_value` (diubah user) ATAU di
-- `default_value` (snapshot teks fallback view yang dicatat sekali lewat
-- INSERT IGNORE di lang_line(), dipakai halaman Kelola Teks). Baris yang
-- custom_value-nya kosong aman dihapus: lang_line() mencatatnya ulang dengan
-- teks fallback yang baru saat halaman terkait dibuka.

-- 1a. Contoh nomor PO "PO/NSI/..." -- hapus, berkas bahasa per-bahasa dipakai.
DELETE FROM `lang_overrides`
 WHERE `lang_key` = 'po_number_placeholder'
   AND (`custom_value` LIKE '%PO/NSI/%' OR (`custom_value` IS NULL AND `default_value` LIKE '%PO/NSI/%'));

-- 1b. Key sudah tidak dipakai kode sejak 2026-10-08 (kop PO pakai company_info).
DELETE FROM `lang_overrides` WHERE `lang_key` = 'po_letterhead_company_name';

-- 1c. Copyright login yang masih NSI -> hapus, pakai berkas bahasa.
DELETE FROM `lang_overrides`
 WHERE `lang_key` = 'login_copyright'
   AND (`custom_value` LIKE '%SPECIALIFE%' OR (`custom_value` IS NULL AND `default_value` LIKE '%SPECIALIFE%'));

-- 1d. Sisa override lain yang menyebut NSI: ganti nama di tempat (dua kolom).
UPDATE `lang_overrides`
   SET `custom_value` = REPLACE(REPLACE(REPLACE(REPLACE(`custom_value`,
         'NEW SPECIALIFE INDUSTRIES', 'ACROPOLY INTERNATIONAL'),
         'New Specialife Industries', 'Acropoly International'),
         'ERP NSI', 'ERP ACROPOLY'),
         'NSI ERP', 'ACROPOLY ERP'),
       `default_value` = REPLACE(REPLACE(REPLACE(REPLACE(`default_value`,
         'NEW SPECIALIFE INDUSTRIES', 'ACROPOLY INTERNATIONAL'),
         'New Specialife Industries', 'Acropoly International'),
         'ERP NSI', 'ERP ACROPOLY'),
         'NSI ERP', 'ACROPOLY ERP')
 WHERE CONCAT_WS(' ', `custom_value`, `default_value`) LIKE '%SPECIALIFE%'
    OR CONCAT_WS(' ', `custom_value`, `default_value`) LIKE '%ERP NSI%'
    OR CONCAT_WS(' ', `custom_value`, `default_value`) LIKE '%NSI ERP%';

-- 2. Identitas perusahaan yang masih NSI -> hapus (kembali ke bawaan Acropoly).
DELETE FROM `site_settings` WHERE `setting_key` = 'company_name'    AND `setting_value` LIKE '%SPECIALIFE%';
DELETE FROM `site_settings` WHERE `setting_key` = 'app_name'        AND `setting_value` LIKE '%NSI%';
DELETE FROM `site_settings` WHERE `setting_key` = 'company_address' AND `setting_value` LIKE '%NGALIYAN%';
DELETE FROM `site_settings` WHERE `setting_key` = 'company_phone'   AND `setting_value` LIKE '%3532%';
