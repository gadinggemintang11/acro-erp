<?php
/**
 * Fix data stock_ledger yang transaction_id nya salah
 * (menggunakan detail_id instead of header_id)
 */
define('BASEPATH', TRUE);
require_once 'index.php';

$CI =& get_instance();
$CI->load->database();

echo "<h2>🔧 Fix Stock Ledger Transaction ID untuk OUT_SCRAP</h2><hr>";

// 1. Cari semua ledger OUT_SCRAP yang transaction_id nya menggunakan detail_id
$wrong_ledgers = $CI->db->query("
    SELECT 
        sl.id as ledger_id,
        sl.transaction_id as current_trans_id,
        sl.item_id,
        sl.transaction_type,
        sl.qty_out,
        sod.header_id as correct_header_id,
        soh.doc_no
    FROM stock_ledger sl
    LEFT JOIN scrap_out_detail sod ON sl.transaction_id = sod.id
    LEFT JOIN scrap_out_header soh ON sod.header_id = soh.id
    WHERE sl.transaction_type LIKE 'OUT_SCRAP%'
    AND sl.item_type = 'scrap'
    AND sod.id IS NOT NULL
    AND sl.transaction_id != sod.header_id
")->result_array();

if (empty($wrong_ledgers)) {
    echo "<p style='color:green;'>✅ Tidak ada data yang perlu diperbaiki!</p>";
    exit;
}

echo "<h3>Data yang akan diperbaiki ({$CI->db->affected_rows()} records):</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Ledger ID</th><th>Current Trans ID<br>(Detail ID)</th><th>Correct Trans ID<br>(Header ID)</th><th>Doc No</th><th>Qty Out</th></tr>";

foreach ($wrong_ledgers as $row) {
    echo "<tr>";
    echo "<td>{$row['ledger_id']}</td>";
    echo "<td style='color:red;'><strong>{$row['current_trans_id']}</strong></td>";
    echo "<td style='color:green;'><strong>{$row['correct_header_id']}</strong></td>";
    echo "<td>{$row['doc_no']}</td>";
    echo "<td>{$row['qty_out']}</td>";
    echo "</tr>";
}
echo "</table>";

// 2. Confirm sebelum update
echo "<hr>";
echo "<form method='post'>";
echo "<p><strong>⚠️ Apakah Anda yakin ingin memperbaiki data ini?</strong></p>";
echo "<button type='submit' name='confirm' value='yes' style='background:green;color:white;padding:10px 20px;border:none;cursor:pointer;'>✅ Ya, Perbaiki Data</button> ";
echo "<button type='button' onclick='window.location.reload()' style='background:gray;color:white;padding:10px 20px;border:none;cursor:pointer;'>❌ Batal</button>";
echo "</form>";

// 3. Proses update jika dikonfirmasi
if (isset($_POST['confirm']) && $_POST['confirm'] == 'yes') {
    echo "<hr><h3>🔄 Memproses Update...</h3>";
    
    $CI->db->trans_start();
    
    $updated_count = 0;
    foreach ($wrong_ledgers as $row) {
        $CI->db->where('id', $row['ledger_id'])
               ->update('stock_ledger', ['transaction_id' => $row['correct_header_id']]);
        $updated_count++;
        echo "<p>✅ Updated ledger_id={$row['ledger_id']}: {$row['current_trans_id']} → {$row['correct_header_id']}</p>";
    }
    
    $CI->db->trans_complete();
    
    if ($CI->db->trans_status()) {
        echo "<hr><p style='color:green;font-size:18px;'><strong>✅ BERHASIL! {$updated_count} records telah diperbaiki.</strong></p>";
        echo "<p><a href='".base_url('LapMutasiScrap')."' style='background:blue;color:white;padding:10px 20px;text-decoration:none;display:inline-block;'>🔍 Lihat Lap Mutasi Scrap</a></p>";
    } else {
        echo "<hr><p style='color:red;font-size:18px;'><strong>❌ GAGAL! Rollback dilakukan.</strong></p>";
    }
}
