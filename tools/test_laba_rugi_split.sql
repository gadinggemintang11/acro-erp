-- =====================================================
-- TEST QUERY: Laba/Rugi Periode Lalu vs Tahun Berjalan
-- UPDATED: Gunakan date comparison bukan YEAR()
-- =====================================================

SET @tanggal_laporan = '2026-01-07';
SET @tahun_berjalan = 2026;
SET @awal_tahun = '2026-01-01';

-- 1. REVENUE PERIODE LALU (sebelum 1 Jan 2026)
SELECT 
    'REVENUE PERIODE LALU' AS kategori,
    COALESCE(SUM(jd.kredit_local - jd.debit_local), 0) AS total
FROM journal_details jd
JOIN journal_header jh ON jd.journal_header_id = jh.id
JOIN chart_of_accounts coa ON jd.account_id = coa.id
WHERE jh.journal_date < @awal_tahun
  AND jh.status = 'Posted'
  AND LEFT(coa.account_code, 1) = '4'

UNION ALL

-- 2. EXPENSE PERIODE LALU (sebelum 1 Jan 2026)
SELECT 
    'EXPENSE PERIODE LALU' AS kategori,
    COALESCE(SUM(jd.debit_local - jd.kredit_local), 0) AS total
FROM journal_details jd
JOIN journal_header jh ON jd.journal_header_id = jh.id
JOIN chart_of_accounts coa ON jd.account_id = coa.id
WHERE jh.journal_date < @awal_tahun
  AND jh.status = 'Posted'
  AND LEFT(coa.account_code, 1) = '5'

UNION ALL

-- 3. LABA/RUGI PERIODE LALU
SELECT 
    'NET INCOME PERIODE LALU' AS kategori,
    (
        SELECT COALESCE(SUM(jd.kredit_local - jd.debit_local), 0)
        FROM journal_details jd
        JOIN journal_header jh ON jd.journal_header_id = jh.id
        JOIN chart_of_accounts coa ON jd.account_id = coa.id
        WHERE jh.journal_date < @awal_tahun
          AND jh.status = 'Posted'
          AND LEFT(coa.account_code, 1) = '4'
    ) - (
        SELECT COALESCE(SUM(jd.debit_local - jd.kredit_local), 0)
        FROM journal_details jd
        JOIN journal_header jh ON jd.journal_header_id = jh.id
        JOIN chart_of_accounts coa ON jd.account_id = coa.id
        WHERE jh.journal_date < @awal_tahun
          AND jh.status = 'Posted'
          AND LEFT(coa.account_code, 1) = '5'
    ) AS total
FROM dual

UNION ALL

-- 4. REVENUE TAHUN BERJALAN (1 Jan 2026 s/d 7 Jan 2026)
SELECT 
    'REVENUE TAHUN BERJALAN' AS kategori,
    COALESCE(SUM(jd.kredit_local - jd.debit_local), 0) AS total
FROM journal_details jd
JOIN journal_header jh ON jd.journal_header_id = jh.id
JOIN chart_of_accounts coa ON jd.account_id = coa.id
WHERE jh.journal_date >= @awal_tahun
  AND jh.journal_date <= @tanggal_laporan
  AND jh.status = 'Posted'
  AND LEFT(coa.account_code, 1) = '4'

UNION ALL

-- 5. EXPENSE TAHUN BERJALAN (1 Jan 2026 s/d 7 Jan 2026)
SELECT 
    'EXPENSE TAHUN BERJALAN' AS kategori,
    COALESCE(SUM(jd.debit_local - jd.kredit_local), 0) AS total
FROM journal_details jd
JOIN journal_header jh ON jd.journal_header_id = jh.id
JOIN chart_of_accounts coa ON jd.account_id = coa.id
WHERE jh.journal_date >= @awal_tahun
  AND jh.journal_date <= @tanggal_laporan
  AND jh.status = 'Posted'
  AND LEFT(coa.account_code, 1) = '5'

UNION ALL

-- 6. LABA/RUGI TAHUN BERJALAN
SELECT 
    'NET INCOME TAHUN BERJALAN' AS kategori,
    (
        SELECT COALESCE(SUM(jd.kredit_local - jd.debit_local), 0)
        FROM journal_details jd
        JOIN journal_header jh ON jd.journal_header_id = jh.id
        JOIN chart_of_accounts coa ON jd.account_id = coa.id
        WHERE jh.journal_date >= @awal_tahun
          AND jh.journal_date <= @tanggal_laporan
          AND jh.status = 'Posted'
          AND LEFT(coa.account_code, 1) = '4'
    ) - (
        SELECT COALESCE(SUM(jd.debit_local - jd.kredit_local), 0)
        FROM journal_details jd
        JOIN journal_header jh ON jd.journal_header_id = jh.id
        JOIN chart_of_accounts coa ON jd.account_id = coa.id
        WHERE jh.journal_date >= @awal_tahun
          AND jh.journal_date <= @tanggal_laporan
          AND jh.status = 'Posted'
          AND LEFT(coa.account_code, 1) = '5'
    ) AS total
FROM dual;
