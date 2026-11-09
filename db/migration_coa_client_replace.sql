-- =====================================================================
-- Migration: Replace Chart of Accounts dengan COA Client (versi final)
-- Source   : COA PDF dari client (Section 1000 - 9100)
-- Strategy : 2-phase
--   Phase 1: INSERT semua akun dengan parent_id=0 (pakai account_code)
--   Phase 2: UPDATE parent_id via self-join berdasarkan prefix account_code
-- Catatan  :
--   * BACKUP dulu sebelum jalankan! TRUNCATE akan hapus semua data lama.
--   * Kalau di tabel transaksi (jurnal_detail, setting_akun, ar/ap_*, dsb)
--     ada FK ke chart_of_accounts.id LAMA, mapping perlu di-rebuild manual
--     karena auto_increment akan menghasilkan ID baru.
--   * Currency: default IDR. Bank USD/HKD diset sesuai mata uang.
--   * normal_balance mengikuti "Balance Direction" di PDF
--     (kontra-akun: Acc.Depreciation, Provision Bad Debt, Purchases
--     Returns/Discounts, Closing Stock = Kredit walau di sisi Aset/HPP).
-- =====================================================================

START TRANSACTION;

SET FOREIGN_KEY_CHECKS = 0;
-- TRUNCATE diblokir MySQL kalau ada FK ke tabel ini (mis. ap_invoice_details).
-- Pakai DELETE + reset AUTO_INCREMENT supaya ID mulai dari 1 lagi.
DELETE FROM chart_of_accounts;
ALTER TABLE chart_of_accounts AUTO_INCREMENT = 1;

-- =====================================================================
-- PHASE 1: Insert semua akun (parent_id sementara = 0)
-- =====================================================================
INSERT INTO chart_of_accounts
  (account_code, account_name, parent_id, account_type, currency, is_header, status, normal_balance)
VALUES
-- ---------- 1000 NON-CURRENT ASSETS ----------
('1000',       'NON-CURRENT ASSETS',                                 0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1000.01',    'Fixed Assets',                                       0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1000.01.01', 'Leasehold Improvement',                              0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1000.01.02', 'Plant and Machinery',                                0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1000.01.03', 'Mould and Tooling',                                  0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1000.01.04', 'Fixture and Fitting',                                0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1000.01.05', 'Furniture and Equipment',                            0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1000.01.06', 'Motor Vehicle',                                      0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1000.01.07', 'Computer Equipment / Software',                      0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1000.02',    'Accumulated Depreciation',                           0, 'Aset', 'IDR', 1, 'active', 'Kredit'),
('1000.02.01', 'Accumulated Depreciation - Leasehold Improvement',   0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1000.02.02', 'Accumulated Depreciation - Plant and Machinery',     0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1000.02.03', 'Accumulated Depreciation - Mould and Tooling',       0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1000.02.04', 'Accumulated Depreciation - Fixture and Fitting',     0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1000.02.05', 'Accumulated Depreciation - Furniture and Equipment', 0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1000.02.06', 'Accumulated Depreciation - Motor Vehicle',           0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1000.02.07', 'Accumulated Depreciation - Computer Equipment / Software', 0, 'Aset', 'IDR', 0, 'active', 'Kredit'),

-- ---------- 1200 CURRENT ASSETS ----------
('1200',       'CURRENT ASSETS',                                     0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1200.01',    'Deposits Paid',                                      0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1200.01.01', 'Deposits Paid - Rental and Utility',                 0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.01.99', 'Deposits Paid - Others',                             0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.02',    'Prepayment',                                         0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1200.02.01', 'Advance Payment for Purchase of Materials',          0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.02.02', 'Prepaid Insurance',                                  0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.02.03', 'Prepaid Taxes',                                      0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.02.04', 'Prepaid Rent',                                       0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.02.05', 'Advance Payment - Others',                           0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.03',    'Current Accounts',                                   0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1200.03.01', 'C/A - Project Starter Ltd',                          0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.03.02', 'C/A - Inform Designs Ltd',                           0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.04',    'Inventory',                                          0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1200.04.01', 'Inventory - FG',                                     0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.04.02', 'Inventory - Materials',                              0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.04.03', 'Inventory - WIP',                                    0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.05',    'Trade Debtors',                                      0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1200.05.01', 'Accounts Receivable',                                0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1200.05.02', 'Provision for Bad and Doubtful Debt',                0, 'Aset', 'IDR', 0, 'active', 'Kredit'),
('1200.05.03', 'Sundry Debtors',                                     0, 'Aset', 'IDR', 0, 'active', 'Debit'),

-- ---------- 1300 Cash in Bank/Hand ----------
('1300',       'Cash in Bank/Hand',                                  0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1300.01',    'Bank (IDR)',                                         0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1300.01.01', 'Mandiri IDR SA #135-00-5898889-8',                   0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1300.01.02', 'BCA IDR #7961368999',                                0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1300.01.03', 'Mandiri IDR CA #135-00-5898898-9',                   0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1300.02',    'Bank (USD)',                                         0, 'Aset', 'USD', 1, 'active', 'Debit'),
('1300.02.01', 'Mandiri USD SA #135-00-5898888-0',                   0, 'Aset', 'USD', 0, 'active', 'Debit'),
('1300.02.02', 'Mandiri USD CA #135-00-5898888-0',                   0, 'Aset', 'USD', 0, 'active', 'Debit'),
('1300.03',    'Bank (HKD)',                                         0, 'Aset', 'HKD', 1, 'active', 'Debit'),
('1300.03.01', 'Mandiri HKD SA #135-00-5898998-7',                   0, 'Aset', 'HKD', 0, 'active', 'Debit'),
('1300.03.02', 'Mandiri HKD CA #135-00-5898988-7',                   0, 'Aset', 'HKD', 0, 'active', 'Debit'),

-- ---------- 1400 Cash in Hand ----------
('1400',       'Cash in Hand',                                       0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('1400.01',    'Cash (Johanes)',                                     0, 'Aset', 'IDR', 0, 'active', 'Debit'),
('1400.02',    'Petty Cash',                                         0, 'Aset', 'IDR', 0, 'active', 'Debit'),

-- ---------- 2000 CURRENT LIABILITIES ----------
('2000',       'CURRENT LIABILITIES',                                0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('2000.01',    'Director Current',                                   0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('2000.01.01', 'C/A - Mr. Henry',                                    0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.02',    'Deposits Received',                                  0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('2000.02.01', 'Deposits Received - Trade',                          0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.02.02', 'Deposits Received - Tooling',                        0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.02.99', 'Deposits Received - Others',                         0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.03',    'Tax Payable',                                        0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('2000.03.01', 'Tax Payable - Output VAT',                           0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.03.02', 'Tax Payable - Input VAT',                            0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.03.03', 'Tax Payable - PPh 21',                               0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.03.04', 'Tax Payable - PPh 25',                               0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.03.05', 'Tax Payable - Income Tax Article 22',                0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.03.06', 'Tax Payable - Income Tax Article 23',                0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.03.07', 'Tax Payable - Corporate Income Tax',                 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.07',    'Accrued Expenses',                                   0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('2000.07.01', 'Accrued - Salary and Wages Payable',                 0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.07.99', 'Accrued - Others Expenses',                          0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.08',    'Trade Creditors',                                    0, 'Liabilitas', 'IDR', 1, 'active', 'Kredit'),
('2000.08.01', 'Accounts Payable',                                   0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),
('2000.08.02', 'Sundry Creditors',                                   0, 'Liabilitas', 'IDR', 0, 'active', 'Kredit'),

-- ---------- 3000 CAPITAL AND RESERVES ----------
('3000',       'CAPITAL AND RESERVES',                               0, 'Ekuitas', 'IDR', 1, 'active', 'Kredit'),
('3000.01',    'Equity',                                             0, 'Ekuitas', 'IDR', 1, 'active', 'Kredit'),
('3000.01.01', 'Share Capital',                                      0, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),
('3000.01.02', 'Retained Earnings',                                  0, 'Ekuitas', 'IDR', 0, 'active', 'Kredit'),

-- ---------- 4000 REVENUE ----------
('4000',       'REVENUE',                                            0, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
('4000.01',    'NET SALES',                                          0, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
('4000.01.01', 'Sales - OEM/ODM',                                    0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
('4000.01.02', 'Sales Material',                                     0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
('4000.01.03', 'Sales Others',                                       0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
('4000.02',    'Other Turnover',                                     0, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
('4000.02.01', 'Tooling Income',                                     0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),

-- ---------- 4200 OTHER INCOME AND GAIN / (LOSS) ----------
('4200',       'OTHER INCOME AND GAIN / (LOSS)',                     0, 'Pendapatan', 'IDR', 1, 'active', 'Kredit'),
('4200.01',    'Bank Interest Income',                               0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),
('4200.02',    'Sundry Income',                                      0, 'Pendapatan', 'IDR', 0, 'active', 'Kredit'),

-- ---------- 5100 COST OF GOODS SOLD ----------
('5100',       'COST OF GOODS SOLD',                                 0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('5100.01',    'Purchases',                                          0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('5100.01.01', 'Purchases - FG',                                     0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.01.02', 'Purchases - Materials',                              0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.01.10', 'Purchases - Tax Tariff',                             0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.02',    'Purchases Returns',                                  0, 'HPP', 'IDR', 1, 'active', 'Kredit'),
('5100.02.01', 'Purchases Returns - FG',                             0, 'HPP', 'IDR', 0, 'active', 'Kredit'),
('5100.02.02', 'Purchases Returns - Materials',                      0, 'HPP', 'IDR', 0, 'active', 'Kredit'),
('5100.03',    'Purchases Discounts',                                0, 'HPP', 'IDR', 1, 'active', 'Kredit'),
('5100.03.01', 'Purchases Discounts - FG',                           0, 'HPP', 'IDR', 0, 'active', 'Kredit'),
('5100.03.02', 'Purchases Discounts - Material',                     0, 'HPP', 'IDR', 0, 'active', 'Kredit'),
('5100.04',    'Stock',                                              0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('5100.04.01', 'Inventory',                                          0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.04.02', 'Opening Stock',                                      0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.04.03', 'Closing Stock',                                      0, 'HPP', 'IDR', 0, 'active', 'Kredit'),
('5100.05',    'Direct Cost',                                        0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('5100.05.01', 'Direct Labor',                                       0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.05.02', 'Direct Expense',                                     0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.05.03', 'Royalties',                                          0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.06',    'Factory Overhead',                                   0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('5100.06.01', 'Electricity & Water',                                0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.06.02', 'Indirect Labor',                                     0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.06.03', 'Indirect Material',                                  0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.06.04', 'Machine Repairs & Maintenance',                      0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.06.05', 'Factory Rent',                                       0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.06.06', 'Consumer Goods',                                     0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.06.07', 'Loose Tools & Supplies',                             0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.06.08', 'Factory Repairs & Maintenance',                      0, 'HPP', 'IDR', 0, 'active', 'Debit'),
('5100.07',    'Carriage Inwards',                                   0, 'HPP', 'IDR', 1, 'active', 'Debit'),
('5100.07.01', 'Carriage Inwards - Material',                        0, 'HPP', 'IDR', 0, 'active', 'Debit'),

-- ---------- 6100 SELLING AND DISTRIBUTION COST ----------
('6100',       'SELLING AND DISTRIBUTION COST',                      0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6100.01',    'Selling Expenses',                                   0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6100.01.01', 'Advertising and Promotional Expense',                0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.01.02', 'Bad Debt Expense',                                   0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.01.03', 'Certificate Charges',                                0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.01.04', 'Commission',                                         0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.01.05', 'Inspection Fees',                                    0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.01.06', 'Mockup Charges',                                     0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.01.07', 'Sample Charges',                                     0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.02',    'Distribution Cost',                                  0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6100.02.01', 'Declaration',                                        0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.02.02', 'Transportation Fees',                                0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.03',    'R&D Expenses',                                       0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6100.03.01', 'Design Fees',                                        0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.03.02', 'Patent and Trademark',                               0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6100.03.03', 'Research & Development Expenses',                    0, 'Biaya', 'IDR', 0, 'active', 'Debit'),

-- ---------- 6200 ADMINISTRATIVE EXPENSES ----------
('6200',       'ADMINISTRATIVE EXPENSES',                            0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6200.01',    'Services Fee',                                       0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6200.01.01', 'Accounting and Audit Fee',                           0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.01.02', 'Bank Charges',                                       0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.01.03', 'Consultancy Fee',                                    0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.01.04', 'Legal and Professional Fee',                         0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.01.05', 'Security Fee',                                       0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.02',    'Facilities Cost',                                    0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6200.02.01', 'IPL Fee',                                            0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.02.02', 'Cleaning Fee',                                       0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.02.03', 'Computer Components / Accessories',                  0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.02.04', 'Motor Car Expenses',                                 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.02.05', 'Office Rent',                                        0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.02.06', 'Office Repair and Maintenance',                      0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.03',    'Staff Cost',                                         0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6200.03.01', 'Messing',                                            0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.03.02', 'Employee Insurance',                                 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.03.03', 'Recruitment Fee',                                    0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.03.04', 'Office Salary',                                      0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.03.05', 'Staff Welfare',                                      0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.03.06', 'Training Fee',                                       0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.03.07', 'Staff Quarter - Rental',                             0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.03.08', 'Staff Quarter - Sundry Expense',                     0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.03.09', 'Staff Quarter - Management Fee and Utilities',       0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.04',    'Depreciation',                                       0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6200.04.01', 'Depreciation - Leasehold Improvement',               0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.04.02', 'Depreciation - Plant and Machinery',                 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.04.03', 'Depreciation - Mould and Tooling',                   0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.04.04', 'Depreciation - Fixture and Fitting',                 0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.04.05', 'Depreciation - Furniture and Equipment',             0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.04.07', 'Depreciation - Motor Vehicle',                       0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.04.08', 'Depreciation - Computer Equipment / Software',       0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.05',    'Office Expenses',                                    0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6200.05.01', 'Magazine and Newspaper',                             0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.05.02', 'Printing and Stationery',                            0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.05.03', 'Sundry Expenses',                                    0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.05.04', 'Telex and Telephone',                                0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6200.05.05', 'Office Water and Electricity',                       0, 'Biaya', 'IDR', 0, 'active', 'Debit'),

-- ---------- 6260 OTHER OPERATING EXPENSES ----------
('6260',       'OTHER OPERATING EXPENSES',                           0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6260.01',    'Compensation',                                       0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6260.02',    'Entertainment',                                      0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6260.03',    'Exchange Difference',                                0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6260.04',    'Insurance',                                          0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6260.05',    'Loss on Disposal of Assets',                         0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6260.06',    'Postage and Courier Charges',                        0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6260.07',    'Travelling Expenses',                                0, 'Biaya', 'IDR', 0, 'active', 'Debit'),

-- ---------- 6300 FINANCE COSTS ----------
('6300',       'FINANCE COSTS',                                      0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6300.01',    'Interest of Bank Loan',                              0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6300.01.01', 'Bank Overdraft Interest',                            0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6300.01.02', 'Interest on Finance Leases',                         0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('6300.02',    'Other Interest Expenses',                            0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('6300.02.01', 'Loan Interest Expense',                              0, 'Biaya', 'IDR', 0, 'active', 'Debit'),

-- ---------- 7100 TAXATION ----------
('7100',       'TAXATION',                                           0, 'Biaya', 'IDR', 1, 'active', 'Debit'),
('7100.01',    'VAT Output',                                         0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.02',    'VAT Input',                                          0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.03',    'PPh 21',                                             0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.04',    'PPh 25',                                             0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.05',    'PPh 29',                                             0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.06',    'Income Tax Article 22',                              0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.07',    'Income Tax Article 23',                              0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.08',    'Income Tax Corporate',                               0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.09',    'Income Tax Article 4',                               0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.10',    'VAT In Import',                                      0, 'Biaya', 'IDR', 0, 'active', 'Debit'),
('7100.99',    'Taxes - Other',                                      0, 'Biaya', 'IDR', 0, 'active', 'Debit'),

-- ---------- 9100 CONTRA ACCOUNT ----------
('9100',       'CONTRA ACCOUNT',                                     0, 'Aset', 'IDR', 1, 'active', 'Debit'),
('9100.01',    'Suspense Account',                                   0, 'Aset', 'IDR', 0, 'active', 'Debit');

-- =====================================================================
-- PHASE 2: Resolve parent_id berdasarkan prefix account_code.
-- Aturan: parent = potongan kode sebelum "." terakhir.
--   '1000.01.01' -> parent '1000.01'
--   '1000.01'    -> parent '1000'
--   '1000'       -> tidak ada parent (parent_id tetap 0)
-- =====================================================================
UPDATE chart_of_accounts c
JOIN chart_of_accounts p
  ON p.account_code = SUBSTRING_INDEX(c.account_code, '.', LENGTH(c.account_code) - LENGTH(REPLACE(c.account_code, '.', '')))
SET c.parent_id = p.id
WHERE c.account_code LIKE '%.%';

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

-- =====================================================================
-- Verifikasi
-- =====================================================================
-- Total per tipe akun:
--   SELECT account_type, COUNT(*) AS total FROM chart_of_accounts GROUP BY account_type;
--
-- Cek tidak ada orphan (selain root):
--   SELECT * FROM chart_of_accounts
--   WHERE parent_id = 0 AND account_code LIKE '%.%';
--
-- Cek struktur tree:
--   SELECT c.account_code, c.account_name, p.account_code AS parent_code
--   FROM chart_of_accounts c
--   LEFT JOIN chart_of_accounts p ON p.id = c.parent_id
--   ORDER BY c.account_code;
