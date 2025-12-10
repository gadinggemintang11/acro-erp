-- Fix MR/202512/0002 wip_in record with NULL source_doc_id
-- Update source_doc_id to WO ID based on mr_details

-- Check current state
SELECT 
    sl.id,
    sl.transaction_type,
    sl.source_doc_id as current_source_doc_id,
    md.wo_header_id as should_be_wo_id,
    mh.mr_number,
    m.nama_material
FROM stock_ledger sl
INNER JOIN mr_header mh ON sl.transaction_id = mh.id
INNER JOIN mr_details md ON md.mr_header_id = mh.id 
    AND md.component_item_id = sl.item_id 
    AND md.component_item_type = sl.item_type
LEFT JOIN materials m ON sl.item_id = m.id AND sl.item_type = 'material'
WHERE sl.transaction_type = 'wip_in'
  AND sl.source_doc_type = 'MR'
  AND sl.source_doc_id IS NULL;

-- Update the NULL source_doc_id to wo_header_id from mr_details
UPDATE stock_ledger sl
INNER JOIN mr_header mh ON sl.transaction_id = mh.id
INNER JOIN mr_details md ON md.mr_header_id = mh.id 
    AND md.component_item_id = sl.item_id 
    AND md.component_item_type = sl.item_type
SET sl.source_doc_id = md.wo_header_id
WHERE sl.transaction_type = 'wip_in'
  AND sl.source_doc_type = 'MR'
  AND sl.source_doc_id IS NULL
  AND md.wo_header_id IS NOT NULL;

-- Verify the fix
SELECT 
    sl.id,
    sl.transaction_type,
    sl.source_doc_id as updated_source_doc_id,
    sl.qty_in,
    mh.mr_number,
    md.wo_header_id,
    m.nama_material
FROM stock_ledger sl
INNER JOIN mr_header mh ON sl.transaction_id = mh.id
INNER JOIN mr_details md ON md.mr_header_id = mh.id 
    AND md.component_item_id = sl.item_id 
    AND md.component_item_type = sl.item_type
LEFT JOIN materials m ON sl.item_id = m.id AND sl.item_type = 'material'
WHERE mh.mr_number = 'MR/202512/0002'
  AND sl.transaction_type = 'wip_in';
