<?php
/**
 * Debug script untuk mendiagnosis masalah ajax_get_out_trace
 * Jalankan: http://localhost/mingbao_erp/debug_scrap_out_trace.php
 */

// Load CodeIgniter
define('BASEPATH', TRUE);
require_once 'index.php';

echo "<h2>🔍 Debug Scrap Out Trace</h2>";
echo "<hr>";

// Get CI instance
$CI =& get_instance();
$CI->load->database();

// 1. Cek semua data di stock_ledger untuk scrap dengan transaksi OUT
echo "<h3>1. Data Stock Ledger untuk transaksi OUT_SCRAP</h3>";
$ledger_query = $CI->db->select('id, item_id, transaction_type, transaction_id, transaction_date, qty_out')
    ->from('stock_ledger')
    ->where('item_type', 'scrap')
    ->like('transaction_type', 'OUT_SCRAP')
    ->order_by('id', 'DESC')
    ->limit(10)
    ->get();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Ledger ID</th><th>Item ID</th><th>Type</th><th>Transaction ID</th><th>Date</th><th>Qty Out</th></tr>";
foreach ($ledger_query->result_array() as $row) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['item_id']}</td>";
    echo "<td>{$row['transaction_type']}</td>";
    echo "<td>{$row['transaction_id']}</td>";
    echo "<td>{$row['transaction_date']}</td>";
    echo "<td>{$row['qty_out']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

// 2. Cek data di scrap_out_header
echo "<h3>2. Data di scrap_out_header</h3>";
$header_query = $CI->db->select('id, doc_no, doc_date, destination, bc_type_code, bc_number')
    ->from('scrap_out_header')
    ->order_by('id', 'DESC')
    ->limit(10)
    ->get();

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Header ID</th><th>Doc No</th><th>Date</th><th>Destination</th><th>BC Type</th><th>BC Number</th></tr>";
foreach ($header_query->result_array() as $row) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['doc_no']}</td>";
    echo "<td>{$row['doc_date']}</td>";
    echo "<td>{$row['destination']}</td>";
    echo "<td>{$row['bc_type_code']}</td>";
    echo "<td>{$row['bc_number']}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";

// 3. Test query yang sama dengan ajax_get_out_trace
echo "<h3>3. Test Query ajax_get_out_trace (ambil doc_id dari tabel teratas)</h3>";

$first_header = $header_query->row_array();
if ($first_header) {
    $test_doc_id = $first_header['id'];
    echo "<p><strong>Testing dengan doc_id = {$test_doc_id}</strong></p>";
    
    $test_query = $CI->db->select('
                h.id, h.doc_no, h.doc_date, h.destination, c.nama_customer AS customer_name,
                h.bc_type_code, h.bc_number, h.bc_date,
                d.scrap_id, m.scrap_name, d.qty, m.unit
            ')
            ->from('scrap_out_header h')
            ->join('scrap_out_detail d', 'h.id = d.header_id', 'left')
            ->join('master_scrap m', 'd.scrap_id = m.id', 'left')
            ->join('customers c', 'h.destination = c.id', 'left')
            ->where('h.id', $test_doc_id)
            ->get();
    
    echo "<p>Query executed: " . $CI->db->last_query() . "</p>";
    echo "<p>Rows returned: " . $test_query->num_rows() . "</p>";
    
    if ($test_query->num_rows() > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Doc No</th><th>Customer</th><th>Scrap Name</th><th>Qty</th><th>Unit</th></tr>";
        foreach ($test_query->result_array() as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['doc_no']}</td>";
            echo "<td>{$row['customer_name']}</td>";
            echo "<td>{$row['scrap_name']}</td>";
            echo "<td>{$row['qty']}</td>";
            echo "<td>{$row['unit']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ TIDAK ADA DATA DITEMUKAN!</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Tidak ada data di scrap_out_header!</p>";
}

echo "<hr>";

// 4. Cek relasi stock_ledger -> scrap_out_header
echo "<h3>4. Cek Relasi: stock_ledger.transaction_id = scrap_out_header.id</h3>";

$relation_query = $CI->db->query("
    SELECT 
        sl.id as ledger_id,
        sl.transaction_id,
        sl.transaction_type,
        soh.id as header_id,
        soh.doc_no
    FROM stock_ledger sl
    LEFT JOIN scrap_out_header soh ON sl.transaction_id = soh.id
    WHERE sl.item_type = 'scrap'
    AND sl.transaction_type LIKE 'OUT_SCRAP%'
    ORDER BY sl.id DESC
    LIMIT 20
");

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Ledger ID</th><th>Transaction ID</th><th>Type</th><th>Header ID (JOIN)</th><th>Doc No (JOIN)</th><th>Status</th></tr>";
foreach ($relation_query->result_array() as $row) {
    $status = $row['header_id'] ? '✅ OK' : '❌ NULL';
    $color = $row['header_id'] ? 'green' : 'red';
    echo "<tr>";
    echo "<td>{$row['ledger_id']}</td>";
    echo "<td>{$row['transaction_id']}</td>";
    echo "<td>{$row['transaction_type']}</td>";
    echo "<td>{$row['header_id']}</td>";
    echo "<td>{$row['doc_no']}</td>";
    echo "<td style='color:{$color};'><strong>{$status}</strong></td>";
    echo "</tr>";
}
echo "</table>";

echo "<hr>";
echo "<p><strong>✅ Debug selesai. Periksa hasil di atas untuk menemukan masalah.</strong></p>";
