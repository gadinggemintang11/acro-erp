<?php
/**
 * Script untuk mengisi BC info di stock_ledger dari grn_header
 * Untuk transaksi type 'in' yang BC-nya masih NULL
 */

require_once 'application/config/database.php';

// Connect to database
$conn = new mysqli($db['default']['hostname'], $db['default']['username'], $db['default']['password'], $db['default']['database']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "=== Fix Stock Ledger BC Info ===\n\n";

// Update stock_ledger BC info dari grn_header untuk transaksi 'in'
$sql = "
UPDATE stock_ledger sl
JOIN grn_header gh ON sl.transaction_id = gh.id
SET 
    sl.bc_type_code = gh.bc_type_code,
    sl.bc_number = gh.bc_number,
    sl.bc_date = gh.bc_date
WHERE sl.transaction_type = 'in'
  AND sl.bc_type_code IS NULL
  AND gh.bc_type_code IS NOT NULL
";

echo "Updating stock_ledger BC info from grn_header...\n";
if ($conn->query($sql)) {
    echo "✓ Updated " . $conn->affected_rows . " stock_ledger records with BC info from GRN\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

// Verify results
$verify = $conn->query("
    SELECT COUNT(*) as total 
    FROM stock_ledger 
    WHERE transaction_type = 'in' 
      AND bc_type_code IS NOT NULL
");
$row = $verify->fetch_assoc();
echo "\nTotal stock_ledger 'in' records with BC info: " . $row['total'] . "\n";

$conn->close();
echo "\n=== Selesai ===\n";
?>
