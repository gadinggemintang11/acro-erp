<!DOCTYPE html>
<html>
<head>
    <title><?= $title; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            border: 1px solid #dee2e6;
            padding: 5px;
        }
        .table thead th {
            background-color: #343a40;
            color: #fff;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        h3 {
            text-align: center;
            margin-bottom: 20px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="footer">
        Page: <span class="pagenum"></span>
    </div>

    <h3><?= $title; ?></h3>
    <table class="table">
        <thead>
            <tr>
                <th>No. Dokumen</th>
                <th>Tanggal</th>
                <th>Tipe</th>
                <th>Akun Kas/Bank</th>
                <th>Keterangan</th>
                <th class="text-right">Total</th>
                <th>CUR</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= $t->doc_no; ?></td>
                <td><?= date('d-m-Y', strtotime($t->doc_date)); ?></td>
                <td><?= $t->transaction_type; ?></td>
                <td><?= $t->payment_account_name; ?></td>
                <td><?= $t->description; ?></td>
                <td class="text-right"><?= number_format($t->total_amount, 2); ?></td>
                <td><?= $t->currency; ?></td>
                <td><?= $t->status; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>