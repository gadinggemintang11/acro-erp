<!DOCTYPE html>
<html>
<head>
    <title>Laporan Mutasi Mesin</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 5px; }
        .company-name { font-weight: bold; font-size: 12px; }
        .report-title { font-weight: bold; font-size: 11px; margin-top: 3px; }
        .period-text { font-size: 10px; margin-top: 3px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #000; padding: 4px; }
        .table th { background-color: #667eea; color: #fff; text-align: center; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">PT MINGBAO LIANCHUANG INTERNATIONAL</div>
        <div class="report-title">Laporan Mutasi Mesin & Peralatan Mekanik</div>
        <div class="period-text">Periode: <?= htmlspecialchars($start); ?> s/d <?= htmlspecialchars($end); ?></div>
    </div>
    
    <table class="table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="12%">Kode Mesin</th>
                <th width="25%">Nama Mesin</th>
                <th width="8%">Satuan</th>
                <th width="12%">Saldo Awal</th>
                <th width="12%">Masuk</th>
                <th width="12%">Penyesuaian</th>
                <th width="14%">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($rows as $r): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($r['kode_mesin'] ?? ''); ?></td>
                    <td><?= htmlspecialchars($r['nama_mesin'] ?? ''); ?></td>
                    <td class="text-center"><?= htmlspecialchars($r['satuan'] ?? ''); ?></td>
                    <td class="text-right"><?= number_format((float)($r['saldo_awal'] ?? 0), 2); ?></td>
                    <td class="text-right"><?= number_format((float)($r['total_masuk'] ?? 0), 2); ?></td>
                    <td class="text-right"><?= number_format((float)($r['total_penyesuaian'] ?? 0), 2); ?></td>
                    <td class="text-right"><?= number_format((float)($r['saldo_akhir'] ?? 0), 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>