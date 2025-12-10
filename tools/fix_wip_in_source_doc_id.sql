-- Fix source_doc_id di stock_ledger untuk transaction_type = 'wip_in'
-- Mengubah dari MR ID menjadi WO ID

-- Backup dulu data sebelum update
CREATE TABLE IF NOT EXISTS stock_ledger_backup_20251207 AS 
SELECT * FROM stock_ledger WHERE transaction_type = 'wip_in';

-- Update source_doc_id dari MR ID ke WO ID
-- Ambil WO ID dari mr_details berdasarkan item_id dan item_type yang match
UPDATE stock_ledger sl
INNER JOIN mr_header mh ON sl.transaction_id = mh.id
INNER JOIN mr_details md ON md.mr_header_id = mh.id 
    AND md.component_item_id = sl.item_id 
    AND md.component_item_type = sl.item_type
SET sl.source_doc_id = md.wo_header_id
WHERE sl.transaction_type = 'wip_in'
  AND sl.source_doc_type = 'MR'
  AND sl.transaction_id IS NOT NULL
  AND md.wo_header_id IS NOT NULL;

-- Cek hasil update
SELECT 
    sl.id,
    sl.transaction_type,
    sl.transaction_id as mr_id,
    sl.source_doc_id as wo_id,
    sl.item_id,
    sl.item_type,
    sl.qty_in,
    mh.mr_number,
    wo.wo_number
FROM stock_ledger sl
LEFT JOIN mr_header mh ON sl.transaction_id = mh.id
LEFT JOIN wo_header wo ON sl.source_doc_id = wo.id
WHERE sl.transaction_type = 'wip_in'
ORDER BY sl.id DESC
LIMIT 20;
