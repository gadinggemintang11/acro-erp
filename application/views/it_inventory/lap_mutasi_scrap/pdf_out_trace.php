<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #ccc; padding: 6px; }
        .table th { background-color: #f2f2f2; }
        .table-info { width: 100%; }
        .table-info th { text-align: left; padding-right: 10px; }
        h3 { text-align: center; }
    </style>
</head>
<body>

    <h3><?= htmlspecialchars($title); ?></h3>

    <table class="table-info">
        <tr>
            <th width="150">Nomor Dokumen</th>
            <td>: <?= htmlspecialchars($header['doc_no']); ?></td>
        </tr>
        <tr>
            <th>Tanggal Dokumen</th>
            <td>: <?= date('d M Y', strtotime($header['doc_date'])); ?></td>
        </tr>
        <tr>
            <th>Customer / Tujuan</th>
            <td>: <?= htmlspecialchars($header['customer_name']); ?></td>
        </tr>
        <tr>
            <th>BC Type / Number</th>
            <td>: <?= htmlspecialchars($header['bc_type_code'] . ' / ' . $header['bc_number']); ?></td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th>Nama Scrap</th>
                <th width="100">Qty Keluar</th>
                <th width="80">Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($rows as $d): ?>
                <tr>
                    <td style="text-align: center;"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($d['scrap_name']); ?></td>
                    <td style="text-align: right;"><?= number_format((float)$d['qty'], 2); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($d['unit']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
