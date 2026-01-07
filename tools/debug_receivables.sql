-- =====================================================
-- DEBUG RECEIVABLES DASHBOARD
-- =====================================================

-- 1. Cek semua invoice
SELECT 
    COUNT(*) as total_invoices,
    SUM(total_invoice) as sum_total_invoice,
    SUM(total_paid) as sum_total_paid,
    SUM(total_invoice - COALESCE(total_paid,0)) as total_receivables
FROM ar_invoice_header;

-- 2. Cek per status
SELECT 
    status,
    COUNT(*) as count,
    SUM(total_invoice) as sum_invoice,
    SUM(total_paid) as sum_paid,
    SUM(total_invoice - COALESCE(total_paid,0)) as sisa_piutang
FROM ar_invoice_header
GROUP BY status;

-- 3. Cek per payment_status
SELECT 
    payment_status,
    COUNT(*) as count,
    SUM(total_invoice) as sum_invoice,
    SUM(total_paid) as sum_paid,
    SUM(total_invoice - COALESCE(total_paid,0)) as sisa_piutang
FROM ar_invoice_header
GROUP BY payment_status;

-- 4. Query yang dipakai di Dashboard (exact sama)
SELECT SUM(total_invoice - COALESCE(total_paid,0)) as total_receivables
FROM ar_invoice_header
WHERE status = 'Posted'
AND (payment_status IS NULL OR payment_status != 'Paid');

-- 5. Detail 5 invoice terbaru
SELECT 
    invoice_no,
    invoice_date,
    status,
    payment_status,
    total_invoice,
    COALESCE(total_paid, 0) as total_paid,
    (total_invoice - COALESCE(total_paid,0)) as sisa_piutang
FROM ar_invoice_header
ORDER BY invoice_date DESC
LIMIT 5;

-- =====================================================
-- KEMUNGKINAN MASALAH:
-- =====================================================
-- 1. Tidak ada invoice dengan status = 'Posted'
-- 2. Semua invoice payment_status = 'Paid' (sudah lunas)
-- 3. Belum ada invoice yang di-posting
-- 4. Field total_paid sudah sama dengan total_invoice
-- =====================================================
