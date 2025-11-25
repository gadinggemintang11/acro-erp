<?php
function format_number($number) {
    return is_numeric($number) ? number_format($number, 2, ',', '.') : '0,00';
}

$total_debit = 0;
$total_kredit = 0;
?>
<html>
<head>
    <style>
        body { font-family: sans-serif; }
        h1 { text-align: center; font-size: 18px; }
        h2 { text-align: center; font-size: 14px; font-weight: normal; margin-top: -10px;}
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        .total-row { background-color: #D9EAD3; }
    </style>
</head>
<body>
    <h1><?= $title ?></h1>
    <h2>Per Tanggal: <?= date("d M Y", strtotime($as_of_date)) ?></h2>

    <table>
        <thead>
            <tr>
                <th>Kode Akun</th>
                <th>Nama Akun</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Kredit</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report_data as $item): ?>
                <?php
                $total_debit += $item['debit'];
                $total_kredit += $item['kredit'];
                ?>
                <tr>
                    <td><?= $item['account_code'] ?></td>
                    <td><?= $item['account_name'] ?></td>
                    <td class="text-right"><?= format_number($item['debit']) ?></td>
                    <td class="text-right"><?= format_number($item['kredit']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="font-weight-bold">Total</td>
                <td class="text-right font-weight-bold"><?= format_number($total_debit) ?></td>
                <td class="text-right font-weight-bold"><?= format_number($total_kredit) ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>