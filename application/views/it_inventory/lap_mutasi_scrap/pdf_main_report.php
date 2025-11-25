<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($title); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ccc; padding: 5px; }
        .table th { background-color: #f2f2f2; text-align: center; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        h2, h4 { text-align: center; }
        .mb-0 { margin-bottom: 0; }
        .mt-0 { margin-top: 0; }
    </style>
</head>
<body>

    <h2 class="mb-0"><?= htmlspecialchars($title); ?></h2>
    <h4 class="mt-0">Periode: <?= date('d M Y', strtotime($filters['start_date'])); ?> s/d <?= date('d M Y', strtotime($filters['end_date'])); ?></h4>

    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Scrap</th>
                <th>Nama Scrap</th>
                <th>Satuan</th>
                <th>Saldo Awal</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th>Adjustment</th>
                <th>Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($report_data)): ?>
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data.</td>
                </tr>
            <?php else: $no = 1; foreach($report_data as $r): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($r['item_code']); ?></td>
                    <td><?= htmlspecialchars($r['item_name']); ?></td>
                    <td class="text-center"><?= $r['item_unit']; ?></td>
                    <td class="text-right"><?= number_format($r['opening_stock'],2); ?></td>
                    <td class="text-right"><?= number_format($r['qty_in'],2); ?></td>
                    <td class="text-right"><?= number_format($r['qty_out'],2); ?></td>
                    <td class="text-right"><?= number_format($r['qty_adj'],2); ?></td>
                    <td class="text-right"><?= number_format($r['closing_stock'],2); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

</body>
</html>
