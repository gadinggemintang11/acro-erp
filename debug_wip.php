<?php
// Debug script untuk cek query WIP
require_once 'system/database/DB.php';

$db_config = [
    'dsn' => '',
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'neokabersis_db',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE,
    'db_debug' => TRUE,
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'encrypt' => FALSE,
    'compress' => FALSE,
    'stricton' => FALSE,
    'failover' => [],
    'save_queries' => TRUE
];

$db = DB($db_config, TRUE);

$start_date = '2025-12-01';
$end_date = '2025-12-31';

echo "<h2>Debug Laporan WIP Desember 2025</h2>";

// Test 1: Cek transaksi WIP di Desember
echo "<h3>1. Total Transaksi WIP di Desember:</h3>";
$query1 = "SELECT transaction_type, item_type, COUNT(*) as total, SUM(qty_in) as total_in, SUM(qty_out) as total_out
FROM stock_ledger 
WHERE transaction_date >= '{$start_date} 00:00:00' 
  AND transaction_date <= '{$end_date} 23:59:59'
  AND transaction_type IN ('wip_in', 'wip_out', 'IN_RETURN_WIP')
GROUP BY transaction_type, item_type";
$result1 = $db->query($query1);
echo "<pre>";
print_r($result1->result_array());
echo "</pre>";

// Test 2: Cek item yang muncul di laporan dengan CTE sederhana (tanpa validasi)
echo "<h3>2. Item dengan Transaksi WIP (Tanpa Validasi):</h3>";
$query2 = "SELECT 
    i.kode_material as kode, 
    'material' as tipe,
    SUM(CASE WHEN sl.transaction_type IN ('wip_in', 'IN_RETURN_WIP') THEN sl.qty_in ELSE 0 END) as masuk,
    SUM(CASE WHEN sl.transaction_type = 'wip_out' THEN sl.qty_out ELSE 0 END) as keluar
FROM materials i
LEFT JOIN stock_ledger sl ON i.id = sl.item_id AND sl.item_type = 'material'
WHERE sl.transaction_date >= '{$start_date} 00:00:00' 
  AND sl.transaction_date <= '{$end_date} 23:59:59'
  AND sl.transaction_type IN ('wip_in', 'wip_out', 'IN_RETURN_WIP')
GROUP BY i.id
UNION ALL
SELECT 
    i.kode_barang as kode,
    'semifinished' as tipe,
    SUM(CASE WHEN sl.transaction_type IN ('wip_in', 'IN_RETURN_WIP') THEN sl.qty_in ELSE 0 END) as masuk,
    SUM(CASE WHEN sl.transaction_type = 'wip_out' THEN sl.qty_out ELSE 0 END) as keluar
FROM semifinished_goods i
LEFT JOIN stock_ledger sl ON i.id = sl.item_id AND sl.item_type = 'semifinished'
WHERE sl.transaction_date >= '{$start_date} 00:00:00' 
  AND sl.transaction_date <= '{$end_date} 23:59:59'
  AND sl.transaction_type IN ('wip_in', 'wip_out', 'IN_RETURN_WIP')
GROUP BY i.id";
$result2 = $db->query($query2);
echo "<pre>";
print_r($result2->result_array());
echo "</pre>";

// Test 3: Cek validasi production_inputs/outputs
echo "<h3>3. Validasi Transaksi dengan Production Tables:</h3>";
$query3 = "SELECT 
    sl.id,
    sl.transaction_type,
    sl.item_type,
    sl.qty_in,
    sl.qty_out,
    po.id as po_id,
    pi.id as pi_id,
    ph_out.status as ph_out_status,
    ph_in.status as ph_in_status
FROM stock_ledger sl
LEFT JOIN production_outputs po ON sl.transaction_id = po.id AND sl.transaction_type = 'wip_in' AND sl.item_type = 'semifinished'
LEFT JOIN production_header ph_out ON po.production_header_id = ph_out.id
LEFT JOIN production_inputs pi ON sl.transaction_id = pi.id AND sl.transaction_type = 'wip_out'
LEFT JOIN production_header ph_in ON pi.production_header_id = ph_in.id
WHERE sl.transaction_date >= '{$start_date} 00:00:00' 
  AND sl.transaction_date <= '{$end_date} 23:59:59'
  AND sl.transaction_type IN ('wip_in', 'wip_out')
LIMIT 10";
$result3 = $db->query($query3);
echo "<pre>";
print_r($result3->result_array());
echo "</pre>";

echo "<p><strong>Selesai debug.</strong></p>";
