-- ============================================================
-- Role Level TAHAP 2: izin SUBMIT terpisah
-- ------------------------------------------------------------
-- assets/NSI - ERP ROLE LEVEL.pdf membedakan MAKER / SUBMIT / EDIT /
-- APPROVE. Contohnya Admin/Clerk hanya MAKER + EDIT BEFORE SUBMIT, yang
-- men-submit adalah Supervisor (user 2026-09-15: "kalau di departemen itu
-- ga ada level supervisornya, berarti admin = supervisor").
--
-- Sebelum migrasi ini tombol Submit tiap modul memakai izin yang berbeda-
-- beda dan TIDAK ADA yang benar-benar "submit":
--   IRF, ERF, MRF, PO (4 tipe), Return to Supplier -> izin Edit
--   PRF                                           -> izin Confirm
--   IPO (sales_contract)                          -> izin Add
-- Akibatnya siapa pun yang boleh Edit/Add otomatis boleh Submit.
--
-- Isi awal can_submit = izin yang SELAMA INI dipakai tombol Submit modul
-- itu, jadi setelah migrasi siapa yang bisa submit PERSIS SAMA. Pemisahan
-- baru terasa setelah Admin mengatur ulang (manual atau lewat Template PDF).
--
-- Sekalian: approve Penerimaan Barang General (SGR) selama ini memakai izin
-- Add (jadi pembuat dokumen otomatis bisa approve). Kode sekarang memakai
-- Confirm; can_confirm di menu SGR diisi dari can_add supaya yang selama ini
-- bisa approve tetap bisa.
--
-- Idempotent: aman dijalankan berulang (backfill hanya saat kolom baru dibuat).
-- ============================================================

SET @col := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'role_permissions' AND COLUMN_NAME = 'can_submit'
);

SET @sql := IF(@col = 0,
    "ALTER TABLE `role_permissions` ADD COLUMN `can_submit` TINYINT(1) NOT NULL DEFAULT 0 AFTER `can_edit`",
    "SELECT 'role_permissions.can_submit sudah ada' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Backfill hanya di run pertama (@col = 0), supaya pengaturan Admin setelahnya
-- tidak tertimpa kalau migrasi dijalankan ulang.
SET @sql := IF(@col = 0,
    "UPDATE `role_permissions` rp JOIN `menus` m ON m.id = rp.menu_id
        SET rp.can_submit = CASE
            WHEN m.menu_key = 'prf'            THEN rp.can_confirm
            WHEN m.menu_key = 'sales_contract' THEN rp.can_add
            WHEN m.menu_key IN ('consumable_request','erf','material_request','purchase_order','purchase_order_general','purchase_order_jasa','purchase_order_asset','warehouse_out') THEN rp.can_edit
            ELSE rp.can_confirm
        END",
    "SELECT 'backfill can_submit dilewati (sudah pernah)' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(@col = 0,
    "UPDATE `role_permissions` rp JOIN `menus` m ON m.id = rp.menu_id
        SET rp.can_confirm = GREATEST(rp.can_confirm, rp.can_add)
      WHERE m.menu_key IN ('supplier_general_item_receipt','supplier_general_item_receipt_general','supplier_general_item_receipt_produksi')",
    "SELECT 'backfill confirm SGR dilewati (sudah pernah)' AS info");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
