<!DOCTYPE html>
<html>
<head>
    <title><?= $title; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1><?= $title; ?></h1>
    <table>
        <thead>
            <tr>
                <th>Kode Material</th>
                <th>Nama Material</th>
                <th>Tanggal Transaksi</th>
                <th>Jenis Transaksi</th>
                <th>Keterangan</th>
                <th>Qty Masuk</th>
                <th>Qty Keluar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trace_data as $trace): ?>
                <tr>
                    <td><?= $trace['kode_material']; ?></td>
                    <td><?= $trace['nama_material']; ?></td>
                    <td><?= $trace['tanggal_transaksi']; ?></td>
                    <td><?= $trace['jenis_transaksi']; ?></td>
                    <td><?= $trace['keterangan']; ?></td>
                    <td><?= $trace['qty_masuk']; ?></td>
                    <td><?= $trace['qty_keluar']; ?></td>
                </tr>
            <?php endforeach; ?>