-- ============================================================
-- Kelompokkan role yang SUDAH ADA ke posisi di PDF ROLE LEVEL
-- ------------------------------------------------------------
-- User 2026-09-17: "departemen, user, dan posisi di db sekarang gausa di
-- ubah, kelompokan saja mana yang menurut anda sama kaya di pdf ... hanya
-- fokus yang di mention di pdf aja".
--
-- Jadi TIDAK ada role baru, TIDAK ada user yang dipindah, dan master
-- Department/`roles.department` yang lama TIDAK diubah. Yang diisi hanya dua
-- kolom penanda (`department_code` & `position_code`) supaya tiap role tahu
-- dirinya setara posisi mana di PDF -- itulah yang dipakai Template PDF &
-- pembatasan Own/All Dept.
--
-- Pemetaan di bawah hasil pembahasan dengan user (17 Sep 2026):
--   HRGA Staff (Bayuwana)              -> ADM Supervisor
--   HRGA Manager                       -> ADM Head of Department
--   Warehouse & Shipping Staff (Anggit)-> PWH Officer
--   Admin Warehouse (Gilang)           -> PWH Admin/Clerk   (user: dia gudang produksi)
--   P I E Supervisor (Wahyu)           -> PIE Supervisor + HOD (user: "PIE Supervisor juga bisa approve head of dept")
--   Production Admin (Dewi)            -> PRO Admin/Clerk
--   Production Supervisor (Rizki)      -> PRO Supervisor + HOD (user: "production supervisor juga bisa approve head of dept")
--   Assistant SPV QA (Ndhona)          -> QAD Head of Department  (user: "assistand spv qa harusnya head of dept")
--   QA SPV (Etik)                      -> QAD Supervisor
--   Accounting Officer (Adin)          -> ACC Officer
--   EXIM Staff                         -> LOG Admin/Clerk
--   Assistant Director Factory/Office  -> MGM Assistant of Director
--   Direktur (Ho Tak Wah)              -> MGM Director
-- Role "Admin" (IT) & "Purchasing" SENGAJA dibiarkan kosong: tidak ada
-- padanannya di PDF, jadi hak aksesnya tidak akan disentuh template.
--
-- Idempotent: aman dijalankan berulang (mencocokkan nama role apa adanya).
-- ============================================================

UPDATE `roles` SET `department_code` = 'ADM', `position_code` = 'SUPERVISOR'    WHERE `role_name` = 'HRGA Staff';
UPDATE `roles` SET `department_code` = 'ADM', `position_code` = 'HOD'           WHERE `role_name` = 'HRGA Manager';
UPDATE `roles` SET `department_code` = 'PWH', `position_code` = 'OFFICER'       WHERE `role_name` = 'Warehouse & Shipping Staff';
UPDATE `roles` SET `department_code` = 'PWH', `position_code` = 'ADMIN_CLERK'   WHERE `role_name` = 'Admin Warehouse';
UPDATE `roles` SET `department_code` = 'PIE', `position_code` = 'SUPERVISOR+HOD' WHERE `role_name` = 'P I E Supervisor';
UPDATE `roles` SET `department_code` = 'PRO', `position_code` = 'ADMIN_CLERK'   WHERE `role_name` = 'Production Admin';
UPDATE `roles` SET `department_code` = 'PRO', `position_code` = 'SUPERVISOR+HOD' WHERE `role_name` = 'Production Supervisor';
UPDATE `roles` SET `department_code` = 'QAD', `position_code` = 'HOD'           WHERE `role_name` = 'Assistant SPV QA';
UPDATE `roles` SET `department_code` = 'QAD', `position_code` = 'SUPERVISOR'    WHERE `role_name` = 'QA SPV';
UPDATE `roles` SET `department_code` = 'ACC', `position_code` = 'OFFICER'       WHERE `role_name` = 'Accounting Officer';
UPDATE `roles` SET `department_code` = 'LOG', `position_code` = 'ADMIN_CLERK'   WHERE `role_name` = 'EXIM Staff';
UPDATE `roles` SET `department_code` = 'MGM', `position_code` = 'ASST_DIRECTOR' WHERE `role_name` IN ('Assistant Director Factory', 'Assistant Director Office');
UPDATE `roles` SET `department_code` = 'MGM', `position_code` = 'DIRECTOR'      WHERE `role_name` = 'Direktur';

-- Role di luar PDF: pastikan penandanya kosong supaya tidak ikut tersentuh
-- template kalau sebelumnya pernah terisi otomatis.
UPDATE `roles` SET `department_code` = NULL, `position_code` = NULL
WHERE `role_name` IN ('Admin', 'Purchasing');
