<?php
/**
 * Test mengapa button OUT_SCRAP tidak muncul
 */
define('BASEPATH', TRUE);
require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "<h2>🔍 Test Scrap Out Button Issue</h2><hr>";

// 1. Ambil data dari screenshot (scrap item pertama)
echo "<h3>1. Data Stock Ledger untuk OUT_SCRAP_BC (dari screenshot)</h3>";

$ledger = $CI->db->select('*')
    ->from('stock_ledger')
    ->where('item_type', 'scrap')
    ->where('transaction_type', 'OUT_SCRAP_BC')
    ->order_by('id', 'DESC')
    ->limit(5)
    ->get();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Ledger ID</th><th>Item ID</th><th>Transaction Type</th><th>Transaction ID</th><th>Qty Out</th><th>Date</th></tr>";

$test_ledger = null;
foreach ($ledger->result_array() as $row) {
    if (!$test_ledger) $test_ledger = $row;
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['item_id']}</td>";
    echo "<td><strong>{$row['transaction_type']}</strong></td>";
    echo "<td><strong style='color:blue;'>{$row['transaction_id']}</strong></td>";
    echo "<td>{$row['qty_out']}</td>";
    echo "<td>{$row['transaction_date']}</td>";
    echo "</tr>";
}
echo "</table>";

if (!$test_ledger) {
    echo "<p style='color:red;'>❌ Tidak ada data OUT_SCRAP_BC ditemukan!</p>";
    exit;
}

echo "<hr>";

// 2. Cek apakah transaction_id ada di scrap_out_header
echo "<h3>2. Cek apakah transaction_id={$test_ledger['transaction_id']} ada di scrap_out_header</h3>";

$header_check = $CI->db->select('*')
    ->from('scrap_out_header')
    ->where('id', $test_ledger['transaction_id'])
    ->get();

if ($header_check->num_rows() > 0) {
    echo "<p style='color:green;'>✅ DITEMUKAN di scrap_out_header:</p>";
    $header = $header_check->row_array();
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Doc No</th><th>Doc Date</th><th>Destination</th><th>BC Type</th><th>Status</th></tr>";
    echo "<tr>";
    echo "<td>{$header['id']}</td>";
    echo "<td>{$header['doc_no']}</td>";
    echo "<td>{$header['doc_date']}</td>";
    echo "<td>{$header['destination']}</td>";
    echo "<td>{$header['bc_type_code']}</td>";
    echo "<td>{$header['status']}</td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo "<p style='color:red;'>❌ TIDAK DITEMUKAN di scrap_out_header!</p>";
    echo "<p>Kemungkinan penyebab:</p>";
    echo "<ul>";
    echo "<li>Data scrap_out_header sudah dihapus</li>";
    echo "<li>transaction_id di stock_ledger tidak sesuai</li>";
    echo "<li>Ada kesalahan saat confirm scrap out</li>";
    echo "</ul>";
}

echo "<hr>";

// 3. Test query seperti di model
echo "<h3>3. Test Query seperti di get_traceability()</h3>";
echo "<p>Testing dengan item_id = {$test_ledger['item_id']}</p>";

$sql = "
SELECT 
    sl.id as ledger_id,
    sl.transaction_type,
    sl.transaction_id,
    sl.qty_out,
    soh.id as scrap_out_id,
    soh.doc_no as scrap_out_docno
FROM stock_ledger sl
LEFT JOIN scrap_out_header soh ON sl.transaction_type LIKE 'OUT_SCRAP%' AND sl.transaction_id = soh.id
WHERE sl.item_id = ? AND sl.item_type = 'scrap' AND sl.transaction_type LIKE 'OUT_SCRAP%'
ORDER BY sl.id DESC
LIMIT 5
";

$test_query = $CI->db->query($sql, [$test_ledger['item_id']]);

echo "<p><strong>Query:</strong></p>";
echo "<pre>".str_replace('?', $test_ledger['item_id'], $sql)."</pre>";

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Ledger ID</th><th>Type</th><th>Transaction ID</th><th>Scrap Out ID (JOIN)</th><th>Doc No (JOIN)</th><th>Status</th></tr>";

foreach ($test_query->result_array() as $row) {
    $status = $row['scrap_out_id'] ? '✅ JOIN OK' : '❌ JOIN NULL';
    $color = $row['scrap_out_id'] ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$row['ledger_id']}</td>";
    echo "<td>{$row['transaction_type']}</td>";
    echo "<td><strong>{$row['transaction_id']}</strong></td>";
    echo "<td><strong style='color:{$color};'>{$row['scrap_out_id']}</strong></td>";
    echo "<td>{$row['scrap_out_docno']}</td>";
    echo "<td style='color:{$color};'><strong>{$status}</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

// 4. Rekomendasi
echo "<h3>4. 💡 Rekomendasi Perbaikan</h3>";
echo "<div style='background:#ffffcc; padding:10px; border-left:4px solid #ff9800;'>";

if ($header_check->num_rows() == 0) {
    echo "<p><strong>Masalah Terdeteksi:</strong> Data scrap_out_header tidak ditemukan untuk transaction_id yang ada di stock_ledger.</p>";
    echo "<p><strong>Solusi:</strong></p>";
    echo "<ol>";
    echo "<li>Periksa apakah proses Confirm Scrap Out berjalan dengan benar</li>";
    echo "<li>Pastikan saat confirm, data disimpan ke scrap_out_header dan stock_ledger dengan ID yang sama</li>";
    echo "<li>Atau jika data memang tidak ada, button tidak akan muncul (ini adalah behavior yang benar)</li>";
    echo "</ol>";
} else {
    echo "<p><strong>Data ditemukan!</strong> Seharusnya button muncul. Cek:</p>";
    echo "<ol>";
    echo "<li>Clear cache browser (Ctrl+Shift+R)</li>";
    echo "<li>Periksa console browser untuk error JavaScript</li>";
    echo "<li>Periksa log file di <code>application/logs/</code></li>";
    echo "</ol>";
}

echo "</div>";

echo "<hr>";
echo "<p><strong>✅ Test selesai. Refresh halaman Lap Mutasi Scrap dan cek apakah button muncul.</strong></p>";
