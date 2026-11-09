-- ============================================================
-- MIGRATION: Sync `menus.menu_name` + `menus.order_no` agar 100%
-- mirror dengan sidebar (application/views/templates/sidebar.php)
-- + Role Access manage page (application/views/roleaccess/manage.php).
--
-- Tujuan: nama modul yang tampil di halaman Role Access identik
-- dengan label di sidebar, dan urutan rownya juga sama.
-- Idempotent: aman di-rerun. Tidak mengubah `department` (label group
-- internal DB) karena manage.php render group dari sidebar_structure.
-- ============================================================

-- ============================================================
-- 1. Normalize menu_key case (DB punya 'Material_issuance', 'Warehouse_out')
--    Lookup di auth_helper sebenarnya case-insensitive, tapi normalize
--    supaya konsisten dengan code.
-- ============================================================
UPDATE menus SET menu_key='material_issuance' WHERE menu_key='Material_issuance';
UPDATE menus SET menu_key='warehouse_out'     WHERE menu_key='Warehouse_out';
UPDATE menus SET menu_key='lap_pemasukan_bb'  WHERE menu_key='Lap_Pemasukan_BB';

-- ============================================================
-- 2. CORE OPERATIONS — IPO Number (10–19)
-- ============================================================
UPDATE menus SET menu_name='Create New IPO',          order_no=10  WHERE LOWER(menu_key)='sales_contract';
UPDATE menus SET menu_name='IPO Approval',            order_no=11  WHERE LOWER(menu_key)='sales_contract_approval';
UPDATE menus SET menu_name='IPO List',                order_no=12  WHERE LOWER(menu_key)='sales_contract_list';

-- 3. CORE — Permintaan Barang : PRODUCTION MATERIAL (20–29)
UPDATE menus SET menu_name='Create New PMR',          order_no=20  WHERE LOWER(menu_key)='material_request';
UPDATE menus SET menu_name='PMR Approval',            order_no=21  WHERE LOWER(menu_key)='material_request_approval';
UPDATE menus SET menu_name='PMR Status',              order_no=22  WHERE LOWER(menu_key)='material_outstanding';

-- 4. CORE — Permintaan Barang : GENERAL MATERIAL (30–39)
UPDATE menus SET menu_name='Create New IRF',          order_no=30  WHERE LOWER(menu_key)='consumable_request';
UPDATE menus SET menu_name='IRF Approval',            order_no=31  WHERE LOWER(menu_key)='consumable_request_approval';
UPDATE menus SET menu_name='IRF Status',              order_no=32  WHERE LOWER(menu_key)='consumable_outstanding';

-- 5. CORE — Production (40–59)
UPDATE menus SET menu_name='Create New Work Order',   order_no=40  WHERE LOWER(menu_key)='work_order';
UPDATE menus SET menu_name='Production In Progress',  order_no=41  WHERE LOWER(menu_key)='production_process';
UPDATE menus SET menu_name='Production QC',           order_no=42  WHERE LOWER(menu_key)='production_qc';
UPDATE menus SET menu_name='Bill of Material',        order_no=43  WHERE LOWER(menu_key)='bom';
UPDATE menus SET menu_name='Production Receipt',      order_no=44  WHERE LOWER(menu_key)='production_receive';
UPDATE menus SET menu_name='QC Production Report',    order_no=45  WHERE LOWER(menu_key)='qc_production';

-- 6. CORE — Warehouse : PRO-WAREHOUSE (60–79)
UPDATE menus SET menu_name='Supplier Material Receipt',          order_no=60 WHERE LOWER(menu_key)='warehouse_in';
UPDATE menus SET menu_name='Supplier Material Return Receipt',   order_no=61 WHERE LOWER(menu_key)='warehouse_out';
UPDATE menus SET menu_name='Production Material Release',        order_no=62 WHERE LOWER(menu_key)='material_issuance';
UPDATE menus SET menu_name='Retur Material Produksi (WIP)',      order_no=63 WHERE LOWER(menu_key)='prodreturn';
UPDATE menus SET menu_name='Material Item Purchase Request',     order_no=64 WHERE LOWER(menu_key)='purchase_request';
UPDATE menus SET menu_name='Material Item PR Approval',          order_no=65 WHERE LOWER(menu_key)='purchase_request_approval';
UPDATE menus SET menu_name='Material & Semi Finished Stock Report', order_no=66 WHERE LOWER(menu_key)='stock_material';

-- 7. CORE — Warehouse : GENERAL WAREHOUSE (80–99)
UPDATE menus SET menu_name='Penerimaan Barang General (Supplier)', order_no=80 WHERE LOWER(menu_key)='supplier_general_item_receipt';
UPDATE menus SET menu_name='Stock Item Release',                   order_no=81 WHERE LOWER(menu_key)='consumable_out';
UPDATE menus SET menu_name='General Item Purchase Request',        order_no=82 WHERE LOWER(menu_key)='purchase_request_general';
UPDATE menus SET menu_name='General Item PR Approval',             order_no=83 WHERE LOWER(menu_key)='purchase_request_approval_general';
UPDATE menus SET menu_name='Stock Item Report',                    order_no=84 WHERE LOWER(menu_key)='stock_inventory';
UPDATE menus SET menu_name='Equipment & Tools Item Report',        order_no=85 WHERE LOWER(menu_key)='stock_machine';

-- 8. CORE — Warehouse : OTHER (100–119)
UPDATE menus SET menu_name='Input Stock Awal',         order_no=100 WHERE LOWER(menu_key)='initial_stock';
UPDATE menus SET menu_name='Stock Ledger',             order_no=101 WHERE LOWER(menu_key)='stock_ledger';
UPDATE menus SET menu_name='Material Traceability',    order_no=102 WHERE LOWER(menu_key)='material_traceability';
UPDATE menus SET menu_name='Stock Alert',              order_no=103 WHERE LOWER(menu_key)='stock_alert';

-- 9. CORE — Purchasing : PRODUCTION MATERIAL (120–139)
UPDATE menus SET menu_name='Create New PMPO',          order_no=120 WHERE LOWER(menu_key)='purchase_order';
UPDATE menus SET menu_name='PMPO Approval',            order_no=121 WHERE LOWER(menu_key)='purchase_order_approval';
UPDATE menus SET menu_name='PMPO Status',              order_no=122 WHERE LOWER(menu_key)='pmpo_status';

-- 10. CORE — Purchasing : GENERAL MATERIAL (140–159)
UPDATE menus SET menu_name='Create New GMPO',          order_no=140 WHERE LOWER(menu_key)='purchase_order_general';
UPDATE menus SET menu_name='GMPO Approval',            order_no=141 WHERE LOWER(menu_key)='purchase_order_approval_general';
UPDATE menus SET menu_name='GMPO Status',              order_no=142 WHERE LOWER(menu_key)='gmpo_status';

-- 11. CORE — Purchasing : IMPORT (160–169)
UPDATE menus SET menu_name='Import Request',           order_no=160 WHERE LOWER(menu_key)='mir';

-- 12. CORE — Purchasing : REPORT (170–189)
UPDATE menus SET menu_name='PMPO List',                order_no=170 WHERE LOWER(menu_key)='pmpo_list';
UPDATE menus SET menu_name='GMPO List',                order_no=171 WHERE LOWER(menu_key)='gmpo_list';
UPDATE menus SET menu_name='PO Report',                order_no=172 WHERE LOWER(menu_key)='po_report';

-- 13. CORE — Quality Control & Assurances (190–209)
UPDATE menus SET menu_name='Quality Assurance',        order_no=190 WHERE LOWER(menu_key)='quality_assurance';
UPDATE menus SET menu_name='QA Sampling',              order_no=191 WHERE LOWER(menu_key)='qa_sampling';
UPDATE menus SET menu_name='QA Report',                order_no=200 WHERE LOWER(menu_key)='qa_report';
UPDATE menus SET menu_name='QA Sampling Report',       order_no=201 WHERE LOWER(menu_key)='qa_sampling_report';

-- 14. CORE — Finished Goods (210–229)
UPDATE menus SET menu_name='Create New FG Receipt',    order_no=210 WHERE LOWER(menu_key)='fg_receipt';
UPDATE menus SET menu_name='FG Receipt List',          order_no=211 WHERE LOWER(menu_key)='fg_receipt_approval';
UPDATE menus SET menu_name='FG Stock Card',            order_no=212 WHERE LOWER(menu_key)='fg_stock';
UPDATE menus SET menu_name='FG Release Permit',        order_no=213 WHERE LOWER(menu_key)='fg_delivery';

-- 15. CORE — Shipping & Transportation (230–239)
UPDATE menus SET menu_name='EXIM Verifications',       order_no=230 WHERE LOWER(menu_key)='exim_verification';

-- 16. CORE — Scrap (240–259)
UPDATE menus SET menu_name='Scrap Receipt',            order_no=240 WHERE LOWER(menu_key)='scrap_in';
UPDATE menus SET menu_name='Scrap Release',            order_no=241 WHERE LOWER(menu_key)='scrap_out';
UPDATE menus SET menu_name='Scrap Stock Report',       order_no=250 WHERE LOWER(menu_key)='scrap_stock';

-- 17. CORE — Subkon (260–279)
UPDATE menus SET menu_name='Kontrak Kerja Subkon',           order_no=260 WHERE LOWER(menu_key)='subkon_order';
UPDATE menus SET menu_name='Pengeluaran Barang Subkon',      order_no=261 WHERE LOWER(menu_key)='subkon_out';
UPDATE menus SET menu_name='Penerimaan Barang Hasil Subkon', order_no=262 WHERE LOWER(menu_key)='subkon_in';

-- 18. CORE — IT Inventory [BC] (280–309)
UPDATE menus SET menu_name='Good Incoming Report',                 order_no=280 WHERE LOWER(menu_key)='lap_pemasukan_bb';
UPDATE menus SET menu_name='Good Outgoing Report',                 order_no=281 WHERE LOWER(menu_key)='lap_pengeluaran_barang';
UPDATE menus SET menu_name='Mutation Report - Scrap',              order_no=290 WHERE LOWER(menu_key)='lap_mutasi_scrap';
UPDATE menus SET menu_name='Mutation Report - Material',           order_no=291 WHERE LOWER(menu_key)='lapmutasimaterial';
UPDATE menus SET menu_name='Mutation Report - Finish Good',        order_no=292 WHERE LOWER(menu_key)='lapmutasifinished';
UPDATE menus SET menu_name='Mutation Report - WIP',                order_no=293 WHERE LOWER(menu_key)='lapmutasiwip';
UPDATE menus SET menu_name='Mutation Report - Equipment & Tools',  order_no=294 WHERE LOWER(menu_key)='lapmutasimesin';

-- ============================================================
-- ACCOUNTING & FINANCE — Finance & Accounting
-- ============================================================

-- 19. FINANCE sub (310–329)
UPDATE menus SET menu_name='Create Payment Requisition',    order_no=310 WHERE LOWER(menu_key)='prf';
UPDATE menus SET menu_name='PRF Approval',                  order_no=311 WHERE LOWER(menu_key)='prf_approval';
UPDATE menus SET menu_name='PRF Payment',                   order_no=312 WHERE LOWER(menu_key)='prf_payment';
UPDATE menus SET menu_name='Create Expense Reimbursement',  order_no=313 WHERE LOWER(menu_key)='erf';
UPDATE menus SET menu_name='ERF Approval',                  order_no=314 WHERE LOWER(menu_key)='erf_approval';
UPDATE menus SET menu_name='ERF Payment',                   order_no=315 WHERE LOWER(menu_key)='erf_payment';

-- 20. FINANCE REPORT sub (330–339)
UPDATE menus SET menu_name='PRF List',                      order_no=330 WHERE LOWER(menu_key)='prf_list';
UPDATE menus SET menu_name='ERF List',                      order_no=331 WHERE LOWER(menu_key)='erf_list';

-- 21. ACCOUNTING sub (340–369)
UPDATE menus SET menu_name='General Journal',               order_no=340 WHERE LOWER(menu_key)='jurnalumum';
UPDATE menus SET menu_name='Voucher Disbursement Upload',   order_no=341 WHERE LOWER(menu_key)='summary_ledger';
UPDATE menus SET menu_name='Voucher Disbursement List',     order_no=342 WHERE LOWER(menu_key)='voucher_disbursement_list';
UPDATE menus SET menu_name='Account Payable',               order_no=343 WHERE LOWER(menu_key)='apinvoice';
UPDATE menus SET menu_name='Payable Payment',               order_no=344 WHERE LOWER(menu_key)='appayment';
UPDATE menus SET menu_name='Receivable Invoice',            order_no=345 WHERE LOWER(menu_key)='arinvoice';
UPDATE menus SET menu_name='Receivable Receipt',            order_no=346 WHERE LOWER(menu_key)='arreceipt';
UPDATE menus SET menu_name='Cash & Bank',                   order_no=347 WHERE LOWER(menu_key)='cashbank';

-- 22. STOCK OPNAME sub (370–379)
UPDATE menus SET menu_name='Stock Opname',                  order_no=370 WHERE LOWER(menu_key)='stockopname';
UPDATE menus SET menu_name='Stock Adjustment',              order_no=371 WHERE LOWER(menu_key)='adjustment';

-- 23. ACCOUNTING REPORT sub (380–389)
UPDATE menus SET menu_name='Profit & Loss',                 order_no=380 WHERE LOWER(menu_key)='laba_rugi';
UPDATE menus SET menu_name='Balance Sheet',                 order_no=381 WHERE LOWER(menu_key)='neraca';
UPDATE menus SET menu_name='Trial Balance',                 order_no=382 WHERE LOWER(menu_key)='trial_balance';
UPDATE menus SET menu_name='Cash Flow',                     order_no=383 WHERE LOWER(menu_key)='cash_flow';

-- 24. SETTING sub (390–399)
UPDATE menus SET menu_name='Chart of Account (COA)',        order_no=390 WHERE LOWER(menu_key)='coa';
UPDATE menus SET menu_name='Account Settings',              order_no=391 WHERE LOWER(menu_key)='settingakun';
UPDATE menus SET menu_name='Kurs Pajak',                    order_no=392 WHERE LOWER(menu_key)='kurs_pajak';

-- ============================================================
-- CONFIGURATION — Master Database (510–529)
-- Order mengikuti sidebar: Supplier, Customer, Production Material,
-- Semi-Finished Material, Finished Goods, Scrap, General Stock Material,
-- Equipment & Tools.
-- ============================================================
UPDATE menus SET menu_name='Supplier',                      order_no=510 WHERE LOWER(menu_key)='master_supplier';
UPDATE menus SET menu_name='Customer',                      order_no=511 WHERE LOWER(menu_key)='master_customer';
UPDATE menus SET menu_name='Production Material',           order_no=512 WHERE LOWER(menu_key)='master_material';
UPDATE menus SET menu_name='Semi-Finished Material',        order_no=513 WHERE LOWER(menu_key)='master_semifinished';
UPDATE menus SET menu_name='Finished Goods',                order_no=514 WHERE LOWER(menu_key)='master_fg';
UPDATE menus SET menu_name='Scrap',                         order_no=515 WHERE LOWER(menu_key)='master_scrap';
UPDATE menus SET menu_name='General Stock Material',        order_no=516 WHERE LOWER(menu_key)='master_inventory';
UPDATE menus SET menu_name='Equipment & Tools',             order_no=517 WHERE LOWER(menu_key)='master_machine';

-- CONFIGURATION — System Setup (530–549)
UPDATE menus SET menu_name='Common Code',                   order_no=530 WHERE LOWER(menu_key)='commoncode';
UPDATE menus SET menu_name='Role Access',                   order_no=531 WHERE LOWER(menu_key)='roleaccess';
UPDATE menus SET menu_name='User Management',               order_no=532 WHERE LOWER(menu_key)='user';
UPDATE menus SET menu_name='Log Activity',                  order_no=533 WHERE LOWER(menu_key)='log_activity';

-- ============================================================
-- VERIFIKASI
-- ============================================================
SELECT menu_key, menu_name, order_no, department
FROM menus
ORDER BY order_no, menu_key;
