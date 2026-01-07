-- =====================================================
-- 1. CEK APAKAH ADA AKUN "LABA TAHUN BERJALAN" DI COA
-- =====================================================

SELECT 
    id,
    account_code,
    account_name,
    account_type,
    normal_balance,
    is_header,
    status
FROM chart_of_accounts
WHERE account_name LIKE '%Laba%Berjalan%'
   OR account_name LIKE '%Laba%Tahun%'
   OR account_code = '9999';

-- =====================================================
-- 2. CEK SEMUA AKUN DENGAN NORMAL_BALANCE YANG SALAH
-- =====================================================

SELECT 
    account_code,
    account_name,
    account_type,
    normal_balance,
    CASE 
        WHEN LEFT(account_code, 1) = '1' THEN 'Debit' -- Asset
        WHEN LEFT(account_code, 1) = '2' THEN 'Kredit' -- Liability
        WHEN LEFT(account_code, 1) = '3' THEN 'Kredit' -- Equity
        WHEN LEFT(account_code, 1) = '4' THEN 'Kredit' -- Revenue
        WHEN LEFT(account_code, 1) = '5' THEN 'Debit' -- Expense
    END AS normal_balance_seharusnya,
    CASE 
        WHEN LEFT(account_code, 1) = '1' AND normal_balance != 'Debit' THEN 'SALAH!'
        WHEN LEFT(account_code, 1) = '2' AND normal_balance != 'Kredit' THEN 'SALAH!'
        WHEN LEFT(account_code, 1) = '3' AND normal_balance != 'Kredit' THEN 'SALAH!'
        WHEN LEFT(account_code, 1) = '4' AND normal_balance != 'Kredit' THEN 'SALAH!'
        WHEN LEFT(account_code, 1) = '5' AND normal_balance != 'Debit' THEN 'SALAH!'
        ELSE 'OK'
    END AS status_validasi
FROM chart_of_accounts
WHERE is_header = 0
  AND status = 'active'
HAVING status_validasi = 'SALAH!';

-- =====================================================
-- 3. CEK TOTAL DEBIT VS KREDIT PER KATEGORI AKUN
-- =====================================================

SELECT 
    CASE 
        WHEN LEFT(coa.account_code, 1) = '1' THEN '1. ASSET'
        WHEN LEFT(coa.account_code, 1) = '2' THEN '2. LIABILITY'
        WHEN LEFT(coa.account_code, 1) = '3' THEN '3. EQUITY'
        WHEN LEFT(coa.account_code, 1) = '4' THEN '4. REVENUE'
        WHEN LEFT(coa.account_code, 1) = '5' THEN '5. EXPENSE'
        ELSE '9. OTHER'
    END AS kategori,
    COUNT(DISTINCT coa.id) AS jumlah_akun,
    SUM(jd.debit_local) AS total_debit,
    SUM(jd.kredit_local) AS total_kredit,
    SUM(jd.debit_local) - SUM(jd.kredit_local) AS selisih_debit_kredit
FROM chart_of_accounts coa
LEFT JOIN journal_details jd ON coa.id = jd.account_id
LEFT JOIN journal_header jh ON jd.journal_header_id = jh.id
WHERE coa.is_header = 0
  AND coa.status = 'active'
  AND (jh.status = 'Posted' OR jh.status IS NULL)
  AND (jh.journal_date <= '2026-01-07' OR jh.journal_date IS NULL)
GROUP BY kategori
ORDER BY kategori;

-- =====================================================
-- 4. CEK SALDO PER AKUN (DENGAN PERHITUNGAN BENAR)
-- =====================================================

SELECT 
    CASE 
        WHEN LEFT(coa.account_code, 1) = '1' THEN '1. ASSET'
        WHEN LEFT(coa.account_code, 1) = '2' THEN '2. LIABILITY'
        WHEN LEFT(coa.account_code, 1) = '3' THEN '3. EQUITY'
        ELSE 'OTHER'
    END AS kategori,
    coa.account_code,
    coa.account_name,
    coa.normal_balance,
    COALESCE(SUM(jd.debit_local), 0) AS total_debit,
    COALESCE(SUM(jd.kredit_local), 0) AS total_kredit,
    CASE 
        WHEN coa.normal_balance = 'Debit' 
            THEN COALESCE(SUM(jd.debit_local), 0) - COALESCE(SUM(jd.kredit_local), 0)
        ELSE 
            COALESCE(SUM(jd.kredit_local), 0) - COALESCE(SUM(jd.debit_local), 0)
    END AS saldo
FROM chart_of_accounts coa
LEFT JOIN journal_details jd ON coa.id = jd.account_id
LEFT JOIN journal_header jh ON jd.journal_header_id = jh.id
WHERE coa.is_header = 0
  AND coa.status = 'active'
  AND LEFT(coa.account_code, 1) IN ('1', '2', '3')
  AND (jh.status = 'Posted' OR jh.status IS NULL)
  AND (jh.journal_date <= '2026-01-07' OR jh.journal_date IS NULL)
GROUP BY coa.id
HAVING saldo != 0
ORDER BY kategori, coa.account_code;

-- =====================================================
-- CEK SALDO AKUN EQUITY (3xxx)
-- =====================================================

SELECT 
    coa.account_code,
    coa.account_name,
    SUM(jd.debit_local) AS total_debit,
    SUM(jd.kredit_local) AS total_kredit,
    SUM(jd.kredit_local - jd.debit_local) AS saldo
FROM chart_of_accounts coa
LEFT JOIN journal_details jd ON coa.id = jd.account_id
LEFT JOIN journal_header jh ON jd.journal_header_id = jh.id
WHERE LEFT(coa.account_code, 1) = '3'
  AND coa.is_header = 0
  AND coa.status = 'active'
  AND (jh.status = 'Posted' OR jh.status IS NULL)
GROUP BY coa.id
ORDER BY coa.account_code;

-- =====================================================
-- CEK TOTAL REVENUE DAN EXPENSE
-- =====================================================

-- Total Revenue (4xxx)
SELECT 
    'REVENUE' AS kategori,
    SUM(jd.kredit_local - jd.debit_local) AS total
FROM journal_details jd
JOIN journal_header jh ON jd.journal_header_id = jh.id
JOIN chart_of_accounts coa ON jd.account_id = coa.id
WHERE jh.status = 'Posted'
  AND LEFT(coa.account_code, 1) = '4'

UNION ALL

-- Total Expense (5xxx)
SELECT 
    'EXPENSE' AS kategori,
    SUM(jd.debit_local - jd.kredit_local) AS total
FROM journal_details jd
JOIN journal_header jh ON jd.journal_header_id = jh.id
JOIN chart_of_accounts coa ON jd.account_id = coa.id
WHERE jh.status = 'Posted'
  AND LEFT(coa.account_code, 1) = '5'

UNION ALL

-- Net Income
SELECT 
    'NET INCOME' AS kategori,
    (
        SELECT SUM(jd.kredit_local - jd.debit_local)
        FROM journal_details jd
        JOIN journal_header jh ON jd.journal_header_id = jh.id
        JOIN chart_of_accounts coa ON jd.account_id = coa.id
        WHERE jh.status = 'Posted' AND LEFT(coa.account_code, 1) = '4'
    ) - (
        SELECT SUM(jd.debit_local - jd.kredit_local)
        FROM journal_details jd
        JOIN journal_header jh ON jd.journal_header_id = jh.id
        JOIN chart_of_accounts coa ON jd.account_id = coa.id
        WHERE jh.status = 'Posted' AND LEFT(coa.account_code, 1) = '5'
    ) AS total
FROM dual;
