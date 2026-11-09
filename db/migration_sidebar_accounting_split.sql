-- =====================================================================
-- Migration: menu "Finance & Accounting" dipecah jadi 6 menu (2026-11-09)
--
-- User Acropoly: "accounting juga harusnya di pisah". Dulu satu dropdown
-- berisi ±25 item; sekarang (templates/sidebar.php) jadi:
--   acc_fund    Pengajuan Dana (PRF & ERF)
--   accounting  Kas, Bank & Jurnal      <- key lama dipakai ulang
--   acc_apar    Hutang & Piutang
--   acc_report  Laporan Keuangan
--   acc_stock   Stock Opname
--   acc_setting Pengaturan Akuntansi
-- Semua tampil di bawah heading "Keuangan & Akuntansi".
--
-- Migrasi ini TIDAK wajib agar sidebar jalan (section baru otomatis
-- terdaftar saat sidebar dirender), tapi:
--   1. menaruh 6 menu itu berurutan di posisi menu accounting lama;
--   2. membawa status "dicopot" (Developer Mode) dari menu lama: kalau menu
--      accounting lama dicopot, keenamnya ikut dicopot; kalau ada ITEM yang
--      dicopot (atau seluruh grupnya, mis. "KEUANGAN"), item itu tetap
--      tersembunyi di menu barunya. item_key = md5(href) sehingga sama di
--      semua database.
-- Aman dijalankan ulang.
-- =====================================================================

-- Urutan & status dicopot hanya ditata SEKALI (saat migrasi belum tercatat),
-- supaya menjalankan ulang tidak menggeser posisi yang sudah diatur admin.
-- Baris section baru bisa saja SUDAH ada (terdaftar otomatis begitu sidebar
-- versi baru dirender sebelum migrasi ini jalan) -> pakai upsert.
SET @done       := (SELECT COUNT(*) FROM schema_migrations WHERE filename = 'migration_sidebar_accounting_split.sql');
SET @acc_sort   := COALESCE((SELECT sort_order FROM sidebar_order WHERE section_key = 'accounting'), 110);
SET @acc_hidden := COALESCE((SELECT is_hidden  FROM sidebar_order WHERE section_key = 'accounting'), 0);

UPDATE sidebar_order
SET section_label = 'Kas, Bank & Jurnal',
    sort_order    = IF(@done = 0, @acc_sort + 1, sort_order)
WHERE section_key = 'accounting';

INSERT INTO sidebar_order (section_key, section_label, sort_order, is_hidden, created_at)
SELECT n.k, n.lbl, @acc_sort + n.pos, @acc_hidden, NOW()
FROM (
    SELECT 'acc_fund' k, 'Pengajuan Dana' lbl, 0 pos UNION ALL
    SELECT 'acc_apar',    'Hutang & Piutang',     2 UNION ALL
    SELECT 'acc_report',  'Laporan Keuangan',     3 UNION ALL
    SELECT 'acc_stock',   'Stock Opname',         4 UNION ALL
    SELECT 'acc_setting', 'Pengaturan Akuntansi', 5
) n
ON DUPLICATE KEY UPDATE
    section_label = VALUES(section_label),
    sort_order    = IF(@done = 0, VALUES(sort_order), sidebar_order.sort_order),
    is_hidden     = IF(@done = 0, VALUES(is_hidden),  sidebar_order.is_hidden);

-- Item yang dicopot di menu lama -> tetap dicopot di menu baru (sekali saja;
-- baris item baru bisa sudah terdaftar otomatis -> upsert).
INSERT INTO sidebar_item_order (dropdown_key, item_key, item_label, sort_order, is_hidden, created_at)
SELECT mp.new_scope, mp.item_key, COALESCE(oi.item_label, mp.item_key), mp.pos, 1, NOW()
FROM (
    SELECT 'acc_fund::grp_sidebar_fund_prf_group' new_scope, 'item_64ad21864baa' item_key, 'grp_sidebar_finance_group' old_grp, 0 pos UNION ALL
    SELECT 'acc_fund::grp_sidebar_fund_prf_group', 'item_a11f06508c16', 'grp_sidebar_finance_group',  10 UNION ALL
    SELECT 'acc_fund::grp_sidebar_fund_prf_group', 'item_d424d5295228', 'grp_sidebar_finance_group',  20 UNION ALL
    SELECT 'acc_fund::grp_sidebar_fund_prf_group', 'item_d0ad8d964882', 'grp_sidebar_finance_report', 30 UNION ALL
    SELECT 'acc_fund::grp_sidebar_fund_erf_group', 'item_e570b8e214b3', 'grp_sidebar_finance_group',   0 UNION ALL
    SELECT 'acc_fund::grp_sidebar_fund_erf_group', 'item_cc541eb4d075', 'grp_sidebar_finance_group',  10 UNION ALL
    SELECT 'acc_fund::grp_sidebar_fund_erf_group', 'item_21cadc53a5b5', 'grp_sidebar_finance_group',  20 UNION ALL
    SELECT 'acc_fund::grp_sidebar_fund_erf_group', 'item_2ad2214385f9', 'grp_sidebar_finance_report', 30 UNION ALL
    SELECT 'accounting',                           'item_97fc5d318f77', 'grp_sidebar_accounting_group',  0 UNION ALL
    SELECT 'accounting',                           'item_608c6d3bf04e', 'grp_sidebar_accounting_group', 10 UNION ALL
    SELECT 'accounting',                           'item_7dbe74932026', 'grp_sidebar_accounting_group', 20 UNION ALL
    SELECT 'accounting',                           'item_e66c0d630089', 'grp_sidebar_accounting_group', 30 UNION ALL
    SELECT 'acc_apar::grp_sidebar_ap_group',       'item_a67f4e9fc9ea', 'grp_sidebar_accounting_group',  0 UNION ALL
    SELECT 'acc_apar::grp_sidebar_ap_group',       'item_cdf95ff4a9f1', 'grp_sidebar_accounting_group', 10 UNION ALL
    SELECT 'acc_apar::grp_sidebar_ap_group',       'item_12cc47e9ad06', 'grp_sidebar_accounting_group', 20 UNION ALL
    SELECT 'acc_apar::grp_sidebar_ar_group',       'item_a2c6cf5d7377', 'grp_sidebar_accounting_group',  0 UNION ALL
    SELECT 'acc_apar::grp_sidebar_ar_group',       'item_bd6f8da7404d', 'grp_sidebar_accounting_group', 10 UNION ALL
    SELECT 'acc_apar::grp_sidebar_ar_group',       'item_e483c7f767ee', 'grp_sidebar_accounting_group', 20 UNION ALL
    SELECT 'acc_report',                           'item_d1625c6a2789', 'grp_sidebar_accounting_report',  0 UNION ALL
    SELECT 'acc_report',                           'item_8cb8a13dd952', 'grp_sidebar_accounting_report', 10 UNION ALL
    SELECT 'acc_report',                           'item_38b07942d2c6', 'grp_sidebar_accounting_report', 20 UNION ALL
    SELECT 'acc_report',                           'item_dd15595aa14c', 'grp_sidebar_accounting_report', 30 UNION ALL
    SELECT 'acc_stock',                            'item_ad80e2238001', 'grp_sidebar_stock_opname_group',  0 UNION ALL
    SELECT 'acc_stock',                            'item_4227750baa6e', 'grp_sidebar_stock_opname_group', 10 UNION ALL
    SELECT 'acc_setting',                          'item_f488bcd7dab5', 'grp_sidebar_accounting_setting',  0 UNION ALL
    SELECT 'acc_setting',                          'item_0564490c898d', 'grp_sidebar_accounting_setting', 10 UNION ALL
    SELECT 'acc_setting',                          'item_7bff82ab4b12', 'grp_sidebar_accounting_setting', 20
) mp
LEFT JOIN sidebar_item_order oi ON oi.dropdown_key = CONCAT('accounting::', mp.old_grp) AND oi.item_key = mp.item_key
LEFT JOIN sidebar_item_order og ON og.dropdown_key = 'accounting' AND og.item_key = mp.old_grp
WHERE @done = 0
  AND (COALESCE(oi.is_hidden, 0) = 1 OR COALESCE(og.is_hidden, 0) = 1)
ON DUPLICATE KEY UPDATE is_hidden = 1;

INSERT INTO schema_migrations (filename, status, statements_run, message, applied_at)
SELECT 'migration_sidebar_accounting_split.sql', 'applied', 4, 'Sidebar: Finance & Accounting dipecah jadi 6 menu', NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM schema_migrations WHERE filename = 'migration_sidebar_accounting_split.sql');
