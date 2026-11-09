-- =====================================================================
-- Migration: tampilkan lagi "Daftar Material Request" di sidebar (2026-11-08)
--
-- Menu Produksi > "Daftar MRF" (link ke /material_request) disembunyikan lewat
-- Developer Mode > urutan sidebar pada era NSI (sidebar_item_order.is_hidden=1),
-- sehingga dari sidebar hanya ada "Buat Material Request Baru" dan daftar MR
-- tidak bisa dibuka (user Acropoly: "untuk liat listnya gimana ya").
-- Item dicari lewat item_key ATAU label lamanya, karena kuncinya bisa beda
-- antar database. Aman dijalankan ulang.
-- =====================================================================

UPDATE sidebar_item_order
SET is_hidden  = 0,
    lang_key   = 'sidebar_mr_list',
    item_label = 'Daftar Material Request',
    sort_order = 15
WHERE dropdown_key = 'produksi'
  AND (item_key = 'item_6a44c785406d' OR item_label IN ('Daftar MRF', 'MRF List', 'Material Request List', 'Daftar Material Request'));

INSERT INTO schema_migrations (filename, status, statements_run, message, applied_at)
SELECT 'migration_sidebar_mr_list.sql', 'applied', 1, 'Sidebar Produksi: Daftar Material Request ditampilkan lagi', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM schema_migrations WHERE filename = 'migration_sidebar_mr_list.sql');
