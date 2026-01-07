-- =====================================================
-- QUERY UNTUK CEK JURNAL YANG TIDAK BALANCE
-- =====================================================

-- 1. Cek jurnal header yang total_debit != total_kredit
SELECT 
    id,
    journal_no,
    journal_date,
    description,
    total_debit,
    total_kredit,
    (total_debit - total_kredit) AS selisih,
    status,
    source_module
FROM journal_header
WHERE ABS(total_debit - total_kredit) > 0.01
ORDER BY journal_date DESC;

-- 2. Cek jurnal details yang sum(debit) != sum(kredit) per header
SELECT 
    jh.id,
    jh.journal_no,
    jh.journal_date,
    jh.status,
    SUM(jd.debit_local) AS sum_debit_detail,
    SUM(jd.kredit_local) AS sum_kredit_detail,
    (SUM(jd.debit_local) - SUM(jd.kredit_local)) AS selisih_detail,
    jh.total_debit AS header_debit,
    jh.total_kredit AS header_kredit
FROM journal_header jh
LEFT JOIN journal_details jd ON jh.id = jd.journal_header_id
WHERE jh.status = 'Posted'
GROUP BY jh.id
HAVING ABS(SUM(jd.debit_local) - SUM(jd.kredit_local)) > 0.01
ORDER BY jh.journal_date DESC;

-- 3. Cek total saldo semua akun (harus = 0 jika balance)
SELECT 
    SUM(debit_local) AS total_all_debit,
    SUM(kredit_local) AS total_all_kredit,
    (SUM(debit_local) - SUM(kredit_local)) AS selisih_total
FROM journal_details jd
JOIN journal_header jh ON jd.journal_header_id = jh.id
WHERE jh.status = 'Posted';

-- 4. Cek per kategori akun (Asset, Liability, Equity)
SELECT 
    CASE 
        WHEN LEFT(coa.account_code, 1) = '1' THEN 'ASSET'
        WHEN LEFT(coa.account_code, 1) = '2' THEN 'LIABILITY'
        WHEN LEFT(coa.account_code, 1) = '3' THEN 'EQUITY'
        WHEN LEFT(coa.account_code, 1) = '4' THEN 'REVENUE'
        WHEN LEFT(coa.account_code, 1) = '5' THEN 'EXPENSE'
        ELSE 'OTHER'
    END AS kategori,
    SUM(jd.debit_local) AS total_debit,
    SUM(jd.kredit_local) AS total_kredit,
    CASE 
        WHEN coa.normal_balance = 'Debit' THEN SUM(jd.debit_local) - SUM(jd.kredit_local)
        ELSE SUM(jd.kredit_local) - SUM(jd.debit_local)
    END AS saldo
FROM journal_details jd
JOIN journal_header jh ON jd.journal_header_id = jh.id
JOIN chart_of_accounts coa ON jd.account_id = coa.id
WHERE jh.status = 'Posted'
GROUP BY kategori
ORDER BY kategori;

-- 5. Cek jurnal yang di-posting oleh siapa
SELECT 
    u.fullname AS posted_by,
    COUNT(*) AS jumlah_jurnal,
    SUM(jh.total_debit) AS total_debit,
    SUM(jh.total_kredit) AS total_kredit,
    SUM(jh.total_debit - jh.total_kredit) AS total_selisih
FROM journal_header jh
LEFT JOIN users u ON jh.posted_by = u.id
WHERE jh.status = 'Posted'
GROUP BY u.fullname
ORDER BY total_selisih DESC;
