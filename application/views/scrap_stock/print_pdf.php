<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title); ?></title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #999; padding: 4px; text-align: left; vertical-align: top;}
        th { background-color: #f0f0f0; text-align: center; font-size: 8px; font-weight: bold; text-transform: uppercase;}
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        h1 { text-align: center; margin-bottom: 5px; font-size: 14px; }
        p.filter-info { text-align: center; font-size: 8px; margin-top: 0; margin-bottom: 15px; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($title); ?></h1>
    <p class="filter-info">
        Filter Pencarian: <?= !empty($filters['search']) ? htmlspecialchars($filters['search']) : 'Tidak ada'; ?>
    </p>

    <table>
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:12%">Kode Scrap</th>
                <th>Nama Scrap</th>
                <th style="width:15%">No. Dok Penerimaan</th>
                <th style="width:10%">Tgl Penerimaan</th>
                <th style="width:8%">Satuan</th>
                <th style="width:10%">Qty Tersedia</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($stock_data)): $no = 1; $total_qty = 0; foreach($stock_data as $row): ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td class="text-center"><?= htmlspecialchars($row->scrap_code); ?></td>
                <td><?= htmlspecialchars($row->scrap_name); ?></td>
                <td class="text-center"><?= htmlspecialchars($row->scrap_in_doc_no); ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row->scrap_in_doc_date)); ?></td>
                <td class="text-center"><?= htmlspecialchars($row->satuan_desc ?? $row->item_unit); ?></td>
                <td class="text-right"><?= number_format((float)$row->available_qty, 4, '.', ','); ?></td>
            </tr>
            <?php $total_qty += (float)$row->available_qty; endforeach; ?>
            <!-- Total Row -->
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="6" class="text-right">Total Keseluruhan</td>
                <td class="text-right"><?= number_format($total_qty, 4, '.', ','); ?></td>
            </tr>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">Tidak ada data stok yang ditemukan.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>