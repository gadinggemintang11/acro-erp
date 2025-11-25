<?php
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Laporan-Neraca.xls");
?>
<h1>Laporan Neraca per <?= $date ?></h1>
<table border="1" cellspacing="0" cellpadding="5" width="100%">
    <thead>
        <tr>
            <th>Akun</th>
            <th>Nama Akun</th>
            <th>Saldo</th>
        </tr>
    </thead>
    <tbody>
        <tr><td colspan="3"><b>Aktiva</b></td></tr>
        <?php $totalAssets = 0; foreach($neraca['assets'] as $asset): ?>
        <tr>
            <td><?= $asset['account'] ?></td>
            <td><?= $asset['name'] ?></td>
            <td align="right"><?= number_format($asset['balance']) ?></td>
        </tr>
        <?php $totalAssets += $asset['balance']; endforeach; ?>
        <tr>
            <td colspan="2" align="right"><b>Total Aktiva</b></td>
            <td align="right"><b><?= number_format($totalAssets) ?></b></td>
        </tr>

        <tr><td colspan="3"><b>Kewajiban</b></td></tr>
        <?php $totalLiabilities = 0; foreach($neraca['liabilities'] as $liability): ?>
        <tr>
            <td><?= $liability['account'] ?></td>
            <td><?= $liability['name'] ?></td>
            <td align="right"><?= number_format($liability['balance']) ?></td>
        </tr>
        <?php $totalLiabilities += $liability['balance']; endforeach; ?>
        <tr>
            <td colspan="2" align="right"><b>Total Kewajiban</b></td>
            <td align="right"><b><?= number_format($totalLiabilities) ?></b></td>
        </tr>

        <tr><td colspan="3"><b>Ekuitas</b></td></tr>
        <?php $totalEquity = 0; foreach($neraca['equity'] as $equity): ?>
        <tr>
            <td><?= $equity['account'] ?></td>
            <td><?= $equity['name'] ?></td>
            <td align="right"><?= number_format($equity['balance']) ?></td>
        </tr>
        <?php $totalEquity += $equity['balance']; endforeach; ?>
        <tr>
            <td colspan="2" align="right"><b>Total Ekuitas</b></td>
            <td align="right"><b><?= number_format($totalEquity) ?></b></td>
        </tr>
        <tr>
            <td colspan="2" align="right"><b>Total Kewajiban dan Ekuitas</b></td>
            <td align="right"><b><?= number_format($totalLiabilities + $totalEquity) ?></b></td>
        </tr>
    </tbody>
</table>
