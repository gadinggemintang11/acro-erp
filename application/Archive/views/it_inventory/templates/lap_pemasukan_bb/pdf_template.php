<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Laporan Pemasukan Barang'); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h3 { margin: 0; font-size: 16px; }
        .header p { margin: 5px 0; font-size: 11px;}
        .small { font-size: 9px; color: #555; }
    </style>
</head>
<body>
    <div class="header">
        <h3><?= htmlspecialchars($title ?? 'Laporan Pemasukan Barang'); ?></h3>
        <p>Periode: <?= date('d M Y', strtotime($filters['start_date'])); ?> s/d <?= date('d M Y', strtotime($filters['end_date'])); ?></p>
        <?php if (!empty($filters['bc_type'])): ?>
            <p class="small">Filter BC Type: <?= htmlspecialchars($bc_types_map[$filters['bc_type']] ?? $filters['bc_type']); ?></p>
        <?php endif; ?>
         <?php if (!empty($filters['search'])): ?>
            <p class="small">Filter Pencarian: <?= htmlspecialchars($filters['search']); ?></p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>No. Pendaf.</th>
                <th>Tgl Pendaf.</th>
                <th>No. Masuk (GRN)</th>
                <th>Supplier</th>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Satuan</th>
                <th>Jumlah</th>
                <th>Nilai Barang (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($report_data)): ?>
                <tr><td colspan="11" class="text-center">Tidak ada data.</td></tr>
            <?php else: $no = 1; ?>
                <?php foreach ($report_data as $row): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td class="text-center"><?= date('d/m/Y H:i', strtotime($row['transaction_date'])); ?></td>
                    <td><?= htmlspecialchars($row['bc_number'] ?? '-'); ?></td>
                    <td class="text-center"><?= isset($row['bc_date']) ? date('d/m/Y', strtotime($row['bc_date'])) : '-'; ?></td>
                    <td><?= htmlspecialchars($row['grn_number'] ?? ($row['source_doc_id'] ?? '-')); ?></td>
                    <td><?= htmlspecialchars($row['nama_supplier'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($row['item_code'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($row['item_name'] ?? '-'); ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['item_unit'] ?? '-'); ?></td>
                    <td class="text-right"><?= number_format($row['qty_in'] ?? 0, 2); ?></td>
                    <td class="text-right"><?= number_format($row['unit_value_idr'] ?? 0, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>