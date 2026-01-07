<?php
require_once 'index.php';
$CI =& get_instance();

// Check mr_header id 20
echo "=== MR Header ID 20 ===\n";
$mr = $CI->db->get_where('mr_header', ['id' => 20])->row();
print_r($mr);

echo "\n=== Stock Ledger WIP IN untuk Material ID 15 ===\n";
$wip_in = $CI->db->query("
    SELECT sl.*, mrh.id as mr_found
    FROM stock_ledger sl
    LEFT JOIN mr_header mrh ON sl.source_doc_id = mrh.id
    WHERE sl.item_id = 15 
    AND sl.item_type = 'material'
    AND sl.transaction_type = 'wip_in'
")->result();
foreach($wip_in as $row) {
    echo "ID: {$row->id}, qty_in: {$row->qty_in}, qty_out: {$row->qty_out}, source_doc_id: {$row->source_doc_id}, mr_found: " . ($row->mr_found ?? 'NULL') . "\n";
}

echo "\n=== Test GREATEST ===\n";
$test = $CI->db->query("
    SELECT 
        sl.id, sl.transaction_type, sl.item_type, sl.qty_in, sl.qty_out,
        GREATEST(COALESCE(sl.qty_in, 0), COALESCE(sl.qty_out, 0)) as qty_real,
        mrh.id as mr_found
    FROM stock_ledger sl
    LEFT JOIN mr_header mrh ON sl.source_doc_id = mrh.id AND sl.transaction_type = 'wip_in' AND sl.item_type = 'material'
    WHERE sl.item_id = 15 
    AND sl.item_type = 'material'
    AND sl.transaction_type IN ('wip_in', 'wip_out')
    ORDER BY sl.transaction_date
")->result();
foreach($test as $row) {
    echo "ID: {$row->id}, type: {$row->transaction_type}, qty_in: {$row->qty_in}, qty_out: {$row->qty_out}, GREATEST: {$row->qty_real}, mr_found: " . ($row->mr_found ?? 'NULL') . "\n";
}
