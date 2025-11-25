<?php
function format_number($number) {
    return is_numeric($number) ? number_format($number, 2, ',', '.') : '0,00';
}

function render_account_tree($accounts, $level = 0) {
    $indent = str_repeat("&nbsp;", $level * 4);
    foreach ($accounts as $account) {
        echo "<tr>";
        echo "<td>" . $indent . $account['account_name'] . "</td>";
        echo "<td class=\"text-right\">" . format_number($account['balance']) . "</td>";
        echo "</tr>";
        if (isset($account['children'])) {
            render_account_tree($account['children'], $level + 1);
        }
    }
}

?>
<html>
<head>
    <style>
        body { font-family: sans-serif; }
        h1 { text-align: center; font-size: 18px; }
        h2 { text-align: center; font-size: 14px; font-weight: normal; margin-top: -10px;}
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        .subtotal-row { background-color: #f2f2f2; }
        .grand-total-row { background-color: #D9EAD3; }
        .group-header td { font-weight: bold; background-color: #EFEFEF; }
    </style>
</head>
<body>
    <h1><?= $title ?></h1>
    <h2>Per Tanggal: <?= date("d M Y", strtotime($as_of_date)) ?></h2>

    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <!-- Aset -->
            <tr class="group-header">
                <td colspan="2">ASET</td>
            </tr>
            <?php render_account_tree($report_data['aset']); ?>
            <tr class="grand-total-row">
                <td class="font-weight-bold">TOTAL ASET</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['total_aset']) ?></td>
            </tr>

            <!-- Kewajiban dan Ekuitas -->
            <tr class="group-header">
                <td colspan="2">KEWAJIBAN DAN EKUITAS</td>
            </tr>
            
            <!-- Kewajiban -->
            <tr class="group-header">
                <td colspan="2">Kewajiban</td>
            </tr>
            <?php render_account_tree($report_data['liabilitas']); ?>
            <tr class="subtotal-row">
                <td class="font-weight-bold">Total Kewajiban</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['total_liabilitas']) ?></td>
            </tr>

            <!-- Ekuitas -->
            <tr class="group-header">
                <td colspan="2">Ekuitas</td>
            </tr>
            <?php render_account_tree($report_data['ekuitas']); ?>
            <tr class="subtotal-row">
                <td class="font-weight-bold">Laba Tahun Berjalan</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['laba_tahun_berjalan']) ?></td>
            </tr>
            <tr class="subtotal-row">
                <td class="font-weight-bold">Total Ekuitas</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['total_ekuitas']) ?></td>
            </tr>
            <tr class="grand-total-row">
                <td class="font-weight-bold">TOTAL KEWAJIBAN DAN EKUITAS</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['total_liabilitas'] + $report_data['total_ekuitas']) ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>