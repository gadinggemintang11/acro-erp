USE neokabersis_db;

SELECT 
    sl.transaction_date, 
    sl.transaction_type, 
    fdh.delivery_number, 
    fdh.exim_verified 
FROM stock_ledger sl 
LEFT JOIN fg_delivery_details fdd ON sl.transaction_id = fdd.id AND sl.transaction_type = 'fg_out' 
LEFT JOIN fg_delivery_header fdh ON fdd.fg_delivery_header_id = fdh.id 
LEFT JOIN scrap_out_detail sod_scrap ON sl.transaction_id = sod_scrap.id AND sl.transaction_type = 'OUT_SCRAP_BC' 
LEFT JOIN scrap_out_header soh ON sod_scrap.header_id = soh.id 
LEFT JOIN subkon_out_details sod_sub ON sl.transaction_id = sod_sub.id AND sl.transaction_type = 'production_out' AND sl.source_doc_type = 'SUBKON_OUT' 
LEFT JOIN subkon_out_header sout ON sod_sub.subkon_out_id = sout.id 
WHERE (sl.transaction_type IN ('fg_out', 'OUT_SCRAP_BC', 'ADJ_OUT') OR (sl.transaction_type = 'production_out' AND sl.source_doc_type = 'SUBKON_OUT')) 
AND (
    (sl.transaction_type = 'fg_out' AND fdh.exim_verified = 1) 
    OR (sl.transaction_type = 'OUT_SCRAP_BC' AND soh.exim_verified = 1) 
    OR (sl.transaction_type = 'production_out' AND sl.source_doc_type = 'SUBKON_OUT' AND sout.exim_verified = 1) 
    OR (sl.transaction_type = 'ADJ_OUT')
) 
AND DATE(sl.transaction_date) >= '2025-11-01' 
AND DATE(sl.transaction_date) <= '2025-11-30' 
ORDER BY sl.transaction_date DESC;
