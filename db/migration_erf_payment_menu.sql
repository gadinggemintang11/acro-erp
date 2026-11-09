-- ============================================================
-- MIGRATION: Pisahkan ERF Payment jadi menu_key sendiri (`erf_payment`)
-- + tambah kolom `payment_proof_path` untuk bukti bayar.
-- Idempotent: aman di-rerun. Sister-file dari migration_prf_payment_menu.sql.
-- ============================================================

-- 1. Insert menu erf_payment (department FINANCE, order_no nempel di belakang erf_list).
INSERT INTO `menus` (`menu_name`, `menu_key`, `order_no`, `department`)
SELECT 'ERF Payment', 'erf_payment', 97, 'FINANCE'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `menu_key` = 'erf_payment');

-- 2. Migrasi izin lama: role yang dulu punya `erf.record_payment` (disimpan di
--    kolom `can_confirm`) di-promote jadi erf_payment.view + add.
INSERT INTO `role_permissions`
  (role_id, menu_id, can_view, can_add, can_edit, can_delete, can_confirm, can_head_dept, can_endorsement, can_approval)
SELECT rp.role_id, m_new.id, 1, 1, 0, 0, 0, 0, 0, 0
FROM `role_permissions` rp
INNER JOIN `menus` m_old ON rp.menu_id = m_old.id AND m_old.menu_key = 'erf'
CROSS JOIN `menus` m_new
WHERE m_new.menu_key = 'erf_payment'
  AND rp.can_confirm = 1
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp2
    WHERE rp2.role_id = rp.role_id AND rp2.menu_id = m_new.id
  );

-- 3. Kolom bukti bayar di tabel tracking.
ALTER TABLE `erf_payment_tracking`
  ADD COLUMN IF NOT EXISTS `payment_proof_path` VARCHAR(255) NULL
  COMMENT 'Path file bukti pembayaran (relatif ke FCPATH)';

-- 4. Mirror path bukti terbaru di header (untuk akses cepat dari list).
ALTER TABLE `erf_header`
  ADD COLUMN IF NOT EXISTS `payment_proof_path` VARCHAR(255) NULL
  COMMENT 'Path bukti bayar terbaru (mirror dari erf_payment_tracking)';

-- ============================================================
-- VERIFIKASI
-- ============================================================
SELECT m.id, m.menu_key, m.menu_name, m.department, m.order_no,
       (SELECT COUNT(*) FROM `role_permissions` rp
        WHERE rp.menu_id = m.id AND rp.can_view = 1) AS roles_with_view
FROM `menus` m
WHERE m.menu_key = 'erf_payment';
