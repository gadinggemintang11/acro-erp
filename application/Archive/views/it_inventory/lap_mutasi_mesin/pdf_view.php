<!DOCTYPE html>
<html>
<head>
    <title>Laporan Mutasi Mesin</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #000; padding: 4px; }
        .table th { background-color: #f2f2f2; text-align: center; }
        .text-right { text-align: right; }
        h3 { text-align: center; }
        p { text-align: center; margin-top: -10px; }
    </style>
</head>
<body>
    <h3>Laporan Mutasi Mesin</h3>
    <p>Periode: <?= htmlspecialchars($start); ?> s/d <?= htmlspecialchars($end); ?></p>
    
    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Mesin</th>
                <th>Nama Mesin</th>
                <th>Kategori</th>
                <th>Saldo Awal</th>
                <th>Masuk</th>
                <th>Penyesuaian</th>
                <th>Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($rows as $r): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($r['kode_mesin']); ?></td>
                    <td><?= htmlspecialchars($r['nama_mesin']); ?></td>
                    <td><?= htmlspecialchars($r['kategori']); ?></td>
                    <td class="text-right"><?= number_format($r['saldo_awal'], 2); ?></td>
                    <td class="text-right"><?= number_format($r['total_masuk'], 2); ?></td>
                    <td class="text-right"><?= number_format($r['total_penyesuaian'], 2); ?></td>
                    <td class="text-right"><?= number_format($r['saldo_akhir'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>