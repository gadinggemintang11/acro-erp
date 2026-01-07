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
    <h2>Periode: <?= date("d M Y", strtotime($start_date)) ?> - <?= date("d M Y", strtotime($end_date)) ?></h2>

    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <!-- Pendapatan -->
            <tr class="group-header">
                <td colspan="2">Pendapatan</td>
            </tr>
            <?php render_account_tree($report_data['pendapatan']); ?>
            <tr class="subtotal-row">
                <td class="font-weight-bold">Total Pendapatan</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['total_pendapatan']) ?></td>
            </tr>

            <!-- Biaya -->
            <tr class="group-header">
                <td colspan="2">Biaya</td>
            </tr>
            <?php render_account_tree($report_data['biaya']); ?>
            <tr class="subtotal-row">
                <td class="font-weight-bold">Total Biaya</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['total_biaya']) ?></td>
            </tr>

            <!-- Laba Bersih -->
            <tr class="grand-total-row">
                <td class="font-weight-bold">Laba Bersih</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['laba_bersih']) ?></td>
            </tr>
        </tbody>
    </table>
</body>
</html>