<?php
function format_number($number) {
    return is_numeric($number) ? number_format($number, 2, ',', '.') : '0,00';
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
            <!-- Arus Kas dari Aktivitas Operasi -->
            <tr class="group-header">
                <td colspan="2">Arus Kas dari Aktivitas Operasi</td>
            </tr>
            <?php foreach ($report_data['cfo'] as $item): ?>
                <tr>
                    <td><?= $item['account_name'] ?></td>
                    <td class="text-right"><?= format_number($item['cash_impact']) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="subtotal-row">
                <td class="font-weight-bold">Arus Kas Bersih dari Aktivitas Operasi</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['total_cfo']) ?></td>
            </tr>

            <!-- Arus Kas dari Aktivitas Investasi -->
            <tr class="group-header">
                <td colspan="2">Arus Kas dari Aktivitas Investasi</td>
            </tr>
            <?php foreach ($report_data['cfi'] as $item): ?>
                <tr>
                    <td><?= $item['account_name'] ?></td>
                    <td class="text-right"><?= format_number($item['cash_impact']) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="subtotal-row">
                <td class="font-weight-bold">Arus Kas Bersih dari Aktivitas Investasi</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['total_cfi']) ?></td>
            </tr>

            <!-- Arus Kas dari Aktivitas Pendanaan -->
            <tr class="group-header">
                <td colspan="2">Arus Kas dari Aktivitas Pendanaan</td>
            </tr>
            <?php foreach ($report_data['cff'] as $item): ?>
                <tr>
                    <td><?= $item['account_name'] ?></td>
                    <td class="text-right"><?= format_number($item['cash_impact']) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="subtotal-row">
                <td class="font-weight-bold">Arus Kas Bersih dari Aktivitas Pendanaan</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['total_cff']) ?></td>
            </tr>
        </tbody>
        <tfoot>
            <!-- Ringkasan -->
            <tr class="grand-total-row">
                <td class="font-weight-bold">Kenaikan (Penurunan) Bersih Kas</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['peningkatan_kas']) ?></td>
            </tr>
            <tr>
                <td class="font-weight-bold">Kas dan Setara Kas Awal Periode</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['saldo_awal_kas']) ?></td>
            </tr>
            <tr class="grand-total-row">
                <td class="font-weight-bold">Kas dan Setara Kas Akhir Periode</td>
                <td class="text-right font-weight-bold"><?= format_number($report_data['saldo_akhir_kas']) ?></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>