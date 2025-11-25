<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Laporan Pemasukan Barang'); ?></title>
    <style>
        body {
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 25px;
        }

        h3, h4, h5 {
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h3 {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            color: #222;
        }

        .header p {
            margin: 3px 0;
            font-size: 11px;
        }

        .filters {
            text-align: center;
            font-size: 10px;
            margin-bottom: 15px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #999;
            padding: 5px 6px;
            word-wrap: break-word;
        }

        thead th {
            background-color: #f4f4f4;
            color: #111;
            font-weight: bold;
            text-align: center;
        }

        tbody tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        /* Header grouping style */
        .group-header {
            background-color: #e6f2ff;
            font-weight: bold;
            text-align: center;
        }

        .small {
            font-size: 9px;
            color: #555;
        }

        .footer {
            margin-top: 25px;
            text-align: right;
            font-size: 9px;
            color: #777;
        }
    </style>
</head>
<body>

    <div class="header">
        <h3><?= htmlspecialchars($title ?? 'Laporan Pemasukan Barang'); ?></h3>
        <p>Periode: <?= date('d M Y', strtotime($filters['start_date'])); ?> s/d <?= date('d M Y', strtotime($filters['end_date'])); ?></p>
    </div>

    <div class="filters">
        <?php if (!empty($filters['bc_type'])): ?>
            Jenis BC: <strong><?= htmlspecialchars($bc_types_map[$filters['bc_type']] ?? $filters['bc_type']); ?></strong> &nbsp;&nbsp;
        <?php endif; ?>
        <?php if (!empty($filters['search'])): ?>
            Pencarian: <strong><?= htmlspecialchars($filters['search']); ?></strong>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr class="group-header">
                <th rowspan="2" width="25">No</th>
                <th colspan="3">Data Dokumen Pabean</th>
                <th colspan="2">Bukti Penerimaan Barang</th>
                <th rowspan="2" width="100">Pengirim Barang</th>
                <th rowspan="2" width="70">Kode Barang</th>
                <th rowspan="2" width="140">Nama Barang</th>
                <th rowspan="2" width="60">Jumlah</th>
                <th rowspan="2" width="50">Satuan</th>
                <th rowspan="2" width="60">Mata Uang</th>
                <th rowspan="2" width="80">Nilai Barang</th>
            </tr>
            <tr>
                <th width="60">Jenis BC</th>
                <th width="100">Nomor BC</th>
                <th width="70">Tanggal BC</th>
                <th width="100">Nomor Penerimaan</th>
                <th width="70">Tanggal Penerimaan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($report_data)): ?>
                <tr>
                    <td colspan="13" class="text-center">Tidak ada data pada periode ini.</td>
                </tr>
            <?php else: $no = 1; ?>
                <?php foreach ($report_data as $row): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['bc_type_code'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($row['bc_number'] ?? '-'); ?></td>
                    <td class="text-center">
                        <?= isset($row['bc_date']) ? date('d/m/Y', strtotime($row['bc_date'])) : '-'; ?>
                    </td>
                    <td><?= htmlspecialchars($row['grn_number'] ?? '-'); ?></td>
                    <td class="text-center">
                        <?= isset($row['grn_date']) ? date('d/m/Y', strtotime($row['grn_date'])) : '-'; ?>
                    </td>
                    <td><?= htmlspecialchars($row['nama_supplier'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($row['item_code'] ?? '-'); ?></td>
                    <td><?= htmlspecialchars($row['item_name'] ?? '-'); ?></td>
                    <td class="text-right"><?= number_format($row['qty_in'] ?? 0, 2); ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['item_unit'] ?? '-'); ?></td>
                    <td class="text-center"><?= htmlspecialchars($row['currency_code'] ?? 'IDR'); ?></td>
                    <td class="text-right"><?= number_format($row['unit_value_idr'] ?? 0, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada <?= date('d/m/Y H:i'); ?> oleh sistem IT Inventory Kawasan Berikat.
    </div>

</body>
</html>
