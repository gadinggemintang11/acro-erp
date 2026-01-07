<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { text-align: center; margin-bottom: 10px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px; text-align: left; }
        th { background-color: #eee; text-align: center; }
    </style>
</head>
<body>
    <h2>Dokumen Scrap Out<br><?= htmlspecialchars($header->doc_no); ?></h2>
    <table>
        <tr><td width="150">Tanggal Dokumen</td><td><?= date('d M Y', strtotime($header->doc_date)); ?></td></tr>
        <tr><td>Tipe BC</td><td><?= htmlspecialchars($header->bc_type_code); ?></td></tr>
        <tr><td>No. BC</td><td><?= htmlspecialchars($header->bc_number); ?></td></tr>
        <tr><td>Tanggal BC</td><td><?= $header->bc_date ? date('d M Y', strtotime($header->bc_date)) : '-'; ?></td></tr>
        <tr><td>Customer</td><td><?= htmlspecialchars($header->customer_name ?: '-'); ?></td></tr>
        <tr><td>Catatan</td><td><?= nl2br(htmlspecialchars($header->notes ?: '-')); ?></td></tr>
    </table>

    <h3>Detail Item Scrap</h3>
    <table>
        <thead>
            <tr>
                <th>Kode Scrap</th>
                <th>Nama Scrap</th>
                <th>Satuan</th>
                <th>Qty Keluar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($details as $d): ?>
            <tr>
                <td><?= htmlspecialchars($d->scrap_code); ?></td>
                <td><?= htmlspecialchars($d->scrap_name); ?></td>
                <td><?= htmlspecialchars($d->satuan_desc); ?></td>
                <td style="text-align:right"><?= number_format($d->qty, 4); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p style="margin-top:30px; text-align:right;">Dicetak pada <?= date('d-m-Y H:i:s'); ?></p>
</body>
</html>
