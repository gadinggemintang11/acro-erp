#!/usr/bin/env php
<?php
// Direct DB check script

$db_config = array(
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'erp_mingbao_local'
);

$conn = new mysqli($db_config['hostname'], $db_config['username'], $db_config['password'], $db_config['database']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== DEBUG MATERIAL ISSUANCE FLOW ===\n\n";

// 1. Check MR yang Issued atau Received
echo "1. MR Status Issued/Received:\n";
$sql = "SELECT id, mr_number, status, issuance_number FROM mr_header WHERE status IN ('Issued', 'Received') ORDER BY id DESC LIMIT 5";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    echo "   ❌ TIDAK ADA MR DENGAN STATUS ISSUED/RECEIVED!\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "   MR ID: {$row['id']}, Number: {$row['mr_number']}, Status: {$row['status']}, Issuance: {$row['issuance_number']}\n";
    }
}

// 2. Check MR Details dengan WO
echo "\n2. MR Details dengan WO:\n";
$sql = "SELECT d.id, d.mr_header_id, d.wo_header_id, d.component_item_id, d.component_item_type, d.issued_qty FROM mr_details d WHERE mr_header_id IN (SELECT id FROM mr_header WHERE status IN ('Issued', 'Received')) LIMIT 10";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    echo "   ❌ TIDAK ADA MR DETAILS!\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "   Detail ID: {$row['id']}, MR: {$row['mr_header_id']}, WO: {$row['wo_header_id']}, Item: {$row['component_item_id']} ({$row['component_item_type']}), Qty: {$row['issued_qty']}\n";
    }
}

// 3. Check wip_in entries
echo "\n3. wip_in Ledger Entries:\n";
$sql = "SELECT id, item_id, item_type, qty_in, source_doc_id, transaction_id FROM stock_ledger WHERE transaction_type='wip_in' ORDER BY id DESC LIMIT 10";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    echo "   ❌ TIDAK ADA wip_in ENTRY!\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "   ID: {$row['id']}, Item: {$row['item_id']} ({$row['item_type']}), Qty: {$row['qty_in']}, Source WO: {$row['source_doc_id']}, Trans ID: {$row['transaction_id']}\n";
    }
}

// 4. Check OUT_ISSUE entries
echo "\n4. OUT_ISSUE Ledger Entries (dari Gudang):\n";
$sql = "SELECT id, item_id, item_type, qty_out, source_doc_id, transaction_id FROM stock_ledger WHERE transaction_type='OUT_ISSUE' ORDER BY id DESC LIMIT 10";
$result = $conn->query($sql);
if ($result->num_rows === 0) {
    echo "   ❌ TIDAK ADA OUT_ISSUE ENTRY!\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "   ID: {$row['id']}, Item: {$row['item_id']} ({$row['item_type']}), Qty: {$row['qty_out']}, Source WO: {$row['source_doc_id']}, Trans ID: {$row['transaction_id']}\n";
    }
}

// 5. Check WO terbaru
echo "\n5. WO Terbaru:\n";
$sql = "SELECT id, wo_number, status FROM wo_header ORDER BY id DESC LIMIT 5";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    echo "   WO ID: {$row['id']}, Number: {$row['wo_number']}, Status: {$row['status']}\n";
}

$conn->close();
echo "\n=== END DEBUG ===\n";
?>
