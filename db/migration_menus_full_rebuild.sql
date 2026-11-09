-- ============================================================
-- MIGRATION: FULL REBUILD `menus` agar 100% match sidebar.
-- Idempotent: aman di-rerun. Tidak menghapus row existing
-- (kalau ada menu lain selain di sidebar, akan tetap ada).
--
-- Strategy:
-- 1. Tambah UNIQUE pada (menu_key) supaya bisa pakai
--    INSERT ... ON DUPLICATE KEY UPDATE (single-statement upsert).
-- 2. Satu INSERT besar dengan ON DUPLICATE KEY UPDATE
--    untuk semua menu_key di sidebar.
-- 3. Verifikasi.
-- ============================================================

-- ------------------------------------------------------------
-- 1. UNIQUE INDEX (idempotent — skip kalau sudah ada).
-- ------------------------------------------------------------
SET @has_uk := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema = DATABASE() AND table_name = 'menus' AND index_name = 'uk_menus_menu_key'
);
SET @sql := IF(@has_uk = 0,
  'ALTER TABLE `menus` ADD UNIQUE KEY `uk_menus_menu_key` (`menu_key`)',
  'DO 0'
);
PREPARE _s FROM @sql; EXECUTE _s; DEALLOCATE PREPARE _s;

-- ------------------------------------------------------------
-- 2. Normalize beberapa menu_key kapital lama agar konsisten.
-- ------------------------------------------------------------
UPDATE `menus` SET `menu_key` = 'material_issuance' WHERE `menu_key` = 'Material_issuance';
UPDATE `menus` SET `menu_key` = 'warehouse_out'     WHERE `menu_key` = 'Warehouse_out';
UPDATE `menus` SET `menu_key` = 'lap_pemasukan_bb'  WHERE `menu_key` = 'Lap_Pemasukan_BB';

-- ------------------------------------------------------------
-- 3. UPSERT semua menu sesuai struktur sidebar.
--    order_no dirancang dengan gap supaya gampang sisipkan
--    item baru di tengah-tengah nanti.
-- ------------------------------------------------------------
INSERT INTO `menus` (`menu_key`, `menu_name`, `order_no`, `department`) VALUES
  -- ===== CORE OPERATIONS =====
  -- IPO Number (10–19)
  ('sales_contract',                    'Create New IPO',                       10,  'MARKETING'),
  ('sales_contract_approval',           'IPO Approval',                         11,  'MARKETING'),
  ('sales_contract_list',               'IPO List',                             12,  'MARKETING'),
  -- Permintaan Barang : PRODUCTION MATERIAL (20–29)
  ('material_request',                  'Create New PMR',                       20,  'REQUISITION'),
  ('material_request_approval',         'PMR Approval',                         21,  'REQUISITION'),
  ('material_outstanding',              'PMR Status',                           22,  'REQUISITION'),
  -- Permintaan Barang : GENERAL MATERIAL (30–39)
  ('consumable_request',                'Create New IRF',                       30,  'REQUISITION'),
  ('consumable_request_approval',       'IRF Approval',                         31,  'REQUISITION'),
  ('consumable_outstanding',            'IRF Status',                           32,  'REQUISITION'),
  -- Production (40–59)
  ('work_order',                        'Create New Work Order',                40,  'PPIC'),
  ('production_process',                'Production In Progress',               41,  'PPIC'),
  ('production_qc',                     'Production QC',                        42,  'PPIC'),
  ('bom',                               'Bill of Material',                     43,  'PPIC'),
  ('production_receive',                'Production Receipt',                   44,  'PPIC'),
  ('qc_production',                     'QC Production Report',                 45,  'PPIC'),
  -- Warehouse : PRO-WAREHOUSE (60–79)
  ('warehouse_in',                      'Supplier Material Receipt',            60,  'WAREHOUSE PRODUCTION'),
  ('warehouse_out',                     'Supplier Material Return Receipt',     61,  'WAREHOUSE PRODUCTION'),
  ('material_issuance',                 'Production Material Release',          62,  'WAREHOUSE PRODUCTION'),
  ('prodreturn',                        'Retur Material Produksi (WIP)',        63,  'WAREHOUSE PRODUCTION'),
  ('purchase_request',                  'Material Item Purchase Request',       64,  'WAREHOUSE PRODUCTION'),
  ('purchase_request_approval',         'Material Item PR Approval',            65,  'WAREHOUSE PRODUCTION'),
  ('stock_material',                    'Material & Semi Finished Stock Report',66,  'WAREHOUSE PRODUCTION'),
  -- Warehouse : GENERAL WAREHOUSE (80–99)
  ('supplier_general_item_receipt',     'Penerimaan Barang General (Supplier)', 80,  'WAREHOUSE GENERAL'),
  ('consumable_out',                    'Stock Item Release',                   81,  'WAREHOUSE GENERAL'),
  ('purchase_request_general',          'General Item Purchase Request',        82,  'WAREHOUSE GENERAL'),
  ('purchase_request_approval_general', 'General Item PR Approval',             83,  'WAREHOUSE GENERAL'),
  ('stock_inventory',                   'Stock Item Report',                    84,  'WAREHOUSE GENERAL'),
  ('stock_machine',                     'Equipment & Tools Item Report',        85,  'WAREHOUSE GENERAL'),
  -- Warehouse : OTHER (100–119)
  ('initial_stock',                     'Input Stock Awal',                     100, 'WAREHOUSE COMMON'),
  ('stock_ledger',                      'Stock Ledger',                         101, 'WAREHOUSE COMMON'),
  ('material_traceability',             'Material Traceability',                102, 'WAREHOUSE COMMON'),
  ('stock_alert',                       'Stock Alert',                          103, 'WAREHOUSE COMMON'),
  -- Purchasing : PRODUCTION MATERIAL (120–139)
  ('purchase_order',                    'Create New PMPO',                      120, 'PURCHASING MATERIAL'),
  ('purchase_order_approval',           'PMPO Approval',                        121, 'PURCHASING MATERIAL'),
  ('pmpo_status',                       'PMPO Status',                          122, 'PURCHASING MATERIAL'),
  -- Purchasing : GENERAL MATERIAL (140–159)
  ('purchase_order_general',            'Create New GMPO',                      140, 'PURCHASING GENERAL'),
  ('purchase_order_approval_general',   'GMPO Approval',                        141, 'PURCHASING GENERAL'),
  ('gmpo_status',                       'GMPO Status',                          142, 'PURCHASING GENERAL'),
  -- Purchasing : IMPORT (160–169)
  ('mir',                               'Import Request',                       160, 'PURCHASING MATERIAL'),
  -- Purchasing : REPORT (170–189)
  ('pmpo_list',                         'PMPO List',                            170, 'PURCHASING MATERIAL'),
  ('gmpo_list',                         'GMPO List',                            171, 'PURCHASING GENERAL'),
  ('po_report',                         'PO Report',                            172, 'PURCHASING MATERIAL'),
  -- Quality Control & Assurances (190–209)
  ('quality_assurance',                 'Quality Assurance',                    190, 'QUALITY'),
  ('qa_sampling',                       'QA Sampling',                          191, 'QUALITY'),
  ('qa_report',                         'QA Report',                            200, 'QUALITY'),
  ('qa_sampling_report',                'QA Sampling Report',                   201, 'QUALITY'),
  -- Finished Goods (210–229)
  ('fg_receipt',                        'Create New FG Receipt',                210, 'FINISHED GOODS'),
  ('fg_receipt_approval',               'FG Receipt List',                      211, 'FINISHED GOODS'),
  ('fg_stock',                          'FG Stock Card',                        212, 'FINISHED GOODS'),
  ('fg_delivery',                       'FG Release Permit',                    213, 'FINISHED GOODS'),
  -- Shipping & Transportation (230–239)
  ('exim_verification',                 'EXIM Verifications',                   230, 'EXIM'),
  -- Scrap (240–259)
  ('scrap_in',                          'Scrap Receipt',                        240, 'SCRAP'),
  ('scrap_out',                         'Scrap Release',                        241, 'SCRAP'),
  ('scrap_stock',                       'Scrap Stock Report',                   250, 'SCRAP'),
  -- Subkon (260–279)
  ('subkon_order',                      'Kontrak Kerja Subkon',                 260, 'SUBKON'),
  ('subkon_out',                        'Pengeluaran Barang Subkon',            261, 'SUBKON'),
  ('subkon_in',                         'Penerimaan Barang Hasil Subkon',       262, 'SUBKON'),
  -- IT Inventory [BC] (280–309)
  ('lap_pemasukan_bb',                  'Good Incoming Report',                 280, 'EXIM'),
  ('lap_pengeluaran_barang',            'Good Outgoing Report',                 281, 'EXIM'),
  ('lap_mutasi_scrap',                  'Mutation Report - Scrap',              290, 'EXIM'),
  ('lapmutasimaterial',                 'Mutation Report - Material',           291, 'EXIM'),
  ('lapmutasifinished',                 'Mutation Report - Finish Good',        292, 'EXIM'),
  ('lapmutasiwip',                      'Mutation Report - WIP',                293, 'EXIM'),
  ('lapmutasimesin',                    'Mutation Report - Equipment & Tools',  294, 'EXIM'),

  -- ===== ACCOUNTING & FINANCE =====
  -- Finance & Accounting : FINANCE (310–329)
  ('prf',                               'Create Payment Requisition',           310, 'FINANCE'),
  ('prf_approval',                      'PRF Approval',                         311, 'FINANCE'),
  ('prf_payment',                       'PRF Payment',                          312, 'FINANCE'),
  ('erf',                               'Create Expense Reimbursement',         313, 'FINANCE'),
  ('erf_approval',                      'ERF Approval',                         314, 'FINANCE'),
  ('erf_payment',                       'ERF Payment',                          315, 'FINANCE'),
  -- Finance & Accounting : REPORT (330–339)
  ('prf_list',                          'PRF List',                             330, 'FINANCE'),
  ('erf_list',                          'ERF List',                             331, 'FINANCE'),
  -- Finance & Accounting : ACCOUNTING (340–369)
  ('jurnalumum',                        'General Journal',                      340, 'ACCOUNTING'),
  ('summary_ledger',                    'Voucher Disbursement Upload',          341, 'ACCOUNTING'),
  ('voucher_disbursement_list',         'Voucher Disbursement List',            342, 'ACCOUNTING'),
  ('apinvoice',                         'Account Payable',                      343, 'ACCOUNTING'),
  ('appayment',                         'Payable Payment',                      344, 'ACCOUNTING'),
  ('arinvoice',                         'Receivable Invoice',                   345, 'ACCOUNTING'),
  ('arreceipt',                         'Receivable Receipt',                   346, 'ACCOUNTING'),
  ('cashbank',                          'Cash & Bank',                          347, 'ACCOUNTING'),
  -- Finance & Accounting : STOCK OPNAME (370–379)
  ('stockopname',                       'Stock Opname',                         370, 'ACCOUNTING'),
  ('adjustment',                        'Stock Adjustment',                     371, 'ACCOUNTING'),
  -- Finance & Accounting : ACCOUNTING REPORT (380–389)
  ('laba_rugi',                         'Profit & Loss',                        380, 'ACCOUNTING'),
  ('neraca',                            'Balance Sheet',                        381, 'ACCOUNTING'),
  ('trial_balance',                     'Trial Balance',                        382, 'ACCOUNTING'),
  ('cash_flow',                         'Cash Flow',                            383, 'ACCOUNTING'),
  -- Finance & Accounting : SETTING (390–399)
  ('coa',                               'Chart of Account (COA)',               390, 'ACCOUNTING'),
  ('settingakun',                       'Account Settings',                     391, 'ACCOUNTING'),
  ('kurs_pajak',                        'Kurs Pajak',                           392, 'ACCOUNTING'),

  -- ===== CONFIGURATION =====
  -- Master Database (510–529)
  ('master_supplier',                   'Supplier',                             510, 'MASTER DATA'),
  ('master_customer',                   'Customer',                             511, 'MASTER DATA'),
  ('master_material',                   'Production Material',                  512, 'MASTER DATA'),
  ('master_semifinished',               'Semi-Finished Material',               513, 'MASTER DATA'),
  ('master_fg',                         'Finished Goods',                       514, 'MASTER DATA'),
  ('master_scrap',                      'Scrap',                                515, 'MASTER DATA'),
  ('master_inventory',                  'General Stock Material',               516, 'MASTER DATA'),
  ('master_machine',                    'Equipment & Tools',                    517, 'MASTER DATA'),
  -- System Setup (530–549)
  ('commoncode',                        'Common Code',                          530, 'SYSTEM'),
  ('roleaccess',                        'Role Access',                          531, 'SYSTEM'),
  ('user',                              'User Management',                      532, 'SYSTEM'),
  ('log_activity',                      'Log Activity',                         533, 'SYSTEM')
ON DUPLICATE KEY UPDATE
  `menu_name`  = VALUES(`menu_name`),
  `order_no`   = VALUES(`order_no`),
  `department` = VALUES(`department`);

-- ============================================================
-- 4. Grant default can_view ke semua role aktif untuk menu
--    yang baru di-insert (idempotent — skip kalau sudah ada).
-- ============================================================
INSERT INTO `role_permissions`
  (role_id, menu_id, can_view, can_add, can_edit, can_delete, can_confirm, can_head_dept, can_endorsement, can_approval)
SELECT r.id, m.id, 1, 0, 0, 0, 0, 0, 0, 0
FROM `roles` r
CROSS JOIN `menus` m
WHERE r.status = 'active'
  AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp
    WHERE rp.role_id = r.id AND rp.menu_id = m.id
  );

-- ============================================================
-- VERIFIKASI
-- ============================================================
SELECT menu_key, menu_name, order_no, department
FROM `menus`
ORDER BY order_no, menu_key;
