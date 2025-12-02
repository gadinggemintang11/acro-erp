USE neokabersis_db;

-- Query SEBELUM perbaikan (salah - tanpa kurung WHERE)
SELECT COUNT(*) as before_fix
FROM stock_ledger sl 
LEFT JOIN fg_delivery_details fdd ON sl.transaction_id = fdd.id AND sl.transaction_type = 'fg_out' 
LEFT JOIN fg_delivery_header fdh ON fdd.fg_delivery_header_id = fdh.id 
WHERE sl.transaction_type IN ('fg_out', 'OUT_SCRAP_BC', 'ADJ_OUT')
OR (sl.transaction_type = 'production_out' AND sl.source_doc_type = 'SUBKON_OUT')
AND (
    (sl.transaction_type = 'fg_out' AND fdh.exim_verified = 1)
    OR (sl.transaction_type = 'OUT_SCRAP_BC')
    OR (sl.transaction_type = 'production_out')
    OR (sl.transaction_type = 'ADJ_OUT')
)
AND DATE(sl.transaction_date) >= '2025-11-01' 
AND DATE(sl.transaction_date) <= '2025-11-30';

-- Query SESUDAH perbaikan (benar - dengan kurung WHERE)
SELECT COUNT(*) as after_fix
FROM stock_ledger sl 
LEFT JOIN fg_delivery_details fdd ON sl.transaction_id = fdd.id AND sl.transaction_type = 'fg_out' 
LEFT JOIN fg_delivery_header fdh ON fdd.fg_delivery_header_id = fdh.id 
WHERE (
    sl.transaction_type IN ('fg_out', 'OUT_SCRAP_BC', 'ADJ_OUT')
    OR (sl.transaction_type = 'production_out' AND sl.source_doc_type = 'SUBKON_OUT')
)
AND (
    (sl.transaction_type = 'fg_out' AND fdh.exim_verified = 1)
    OR (sl.transaction_type = 'OUT_SCRAP_BC')
    OR (sl.transaction_type = 'production_out')
    OR (sl.transaction_type = 'ADJ_OUT')
)
AND DATE(sl.transaction_date) >= '2025-11-01' 
AND DATE(sl.transaction_date) <= '2025-11-30';

-- Cek data FG Out yang belum diverifikasi
SELECT 
    fdh.delivery_number,
    fdh.status,
    fdh.exim_verified,
    sl.transaction_date
FROM stock_ledger sl 
LEFT JOIN fg_delivery_details fdd ON sl.transaction_id = fdd.id AND sl.transaction_type = 'fg_out' 
LEFT JOIN fg_delivery_header fdh ON fdd.fg_delivery_header_id = fdh.id 
WHERE sl.transaction_type = 'fg_out'
AND DATE(sl.transaction_date) >= '2025-11-01' 
AND DATE(sl.transaction_date) <= '2025-11-30';
