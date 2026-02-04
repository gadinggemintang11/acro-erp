<!DOCTYPE html>
<html>
<head>
    <title><?= $title; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background-color: #f2f2f2;
        }
        .header {
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 20px;
            margin-bottom: 0;
        }
        .header p {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= $title; ?></h1>
        <p>Tanggal: <?= date('d M Y'); ?></p>
    </div>

    <?php if (isset($production)): ?>
        <h2>Detail Produksi</h2>
        <p>No. Produksi: <?= $production['production_number']; ?></p>
        <p>No. WO: <?= $production['wo_number'] ?? '-'; ?></p>
        <p>Nama FG: <?= $production['fg_name']; ?></p>

        <h3>Bahan Baku yang Digunakan</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Kode Material</th>
                    <th>Nama Material</th>
                    <th>Qty Terpakai</th>
                    <th>Satuan</th>
                    <th>BC Type</th>
                    <th>BC Number</th>
                    <th>BC Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materials as $material): ?>
                    <tr>
                        <td><?= $material['item_code']; ?></td>
                        <td><?= $material['item_name']; ?></td>
                        <td><?= $material['qty_used']; ?></td>
                        <td><?= $material['unit']; ?></td>
                        <td><?= $material['bc_type_code']; ?></td>
                        <td><?= $material['bc_number']; ?></td>
                        <td><?= $material['bc_date']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</body>
</html>
                        <td><?= $material['bc_date']; ?></td>