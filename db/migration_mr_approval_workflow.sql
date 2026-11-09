-- Migration: Tambah kolom approval workflow di mr_header
-- Date: 2026-05-14
-- Flow: Draft -> Submitted -> Approved Head Dept -> Endorsed -> Approved -> Issued -> Received
-- Visibility per stage diatur oleh role permission (head_dept / endorsement / approval)

ALTER TABLE `mr_header`
  ADD COLUMN `hod_approved_by`   INT(11) NULL DEFAULT NULL AFTER `requested_at`,
  ADD COLUMN `hod_approved_at`   DATETIME NULL DEFAULT NULL AFTER `hod_approved_by`,
  ADD COLUMN `endorsed_by`       INT(11) NULL DEFAULT NULL AFTER `hod_approved_at`,
  ADD COLUMN `endorsed_at`       DATETIME NULL DEFAULT NULL AFTER `endorsed_by`,
  ADD COLUMN `final_approved_by` INT(11) NULL DEFAULT NULL AFTER `endorsed_at`,
  ADD COLUMN `final_approved_at` DATETIME NULL DEFAULT NULL AFTER `final_approved_by`;

-- Ubah kolom status agar menerima 3 nilai stage approval yang baru.
-- Kalau kolom status sebelumnya ENUM, MySQL akan menolak/empty-string saat di-set
-- ke 'Approved Head Dept' / 'Endorsed' / 'Approved'. Ubah ke VARCHAR agar fleksibel.
ALTER TABLE `mr_header` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'Draft';

-- Fix data: row yang status-nya jadi '' karena gagal di-set ENUM saat HOD approve.
-- Bisa dikenali dari hod_approved_by terisi tapi status kosong.
UPDATE `mr_header`
SET `status` = 'Approved Head Dept'
WHERE (`status` = '' OR `status` IS NULL)
  AND `hod_approved_by` IS NOT NULL
  AND `endorsed_by` IS NULL
  AND `final_approved_by` IS NULL;

UPDATE `mr_header`
SET `status` = 'Endorsed'
WHERE (`status` = '' OR `status` IS NULL)
  AND `endorsed_by` IS NOT NULL
  AND `final_approved_by` IS NULL;

UPDATE `mr_header`
SET `status` = 'Approved'
WHERE (`status` = '' OR `status` IS NULL)
  AND `final_approved_by` IS NOT NULL;
