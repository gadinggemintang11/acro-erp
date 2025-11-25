<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 20px;
        }

        h3 {
            text-align: center;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        table.table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .status-posted {
            color: #008000;
            font-weight: bold;
        }

        .status-draft {
            color: #999;
            font-style: italic;
        }
    </style>
</head>

<body>
    <h3><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h3>

    <table class="table">
        <thead>
            <tr>
                <th>No. Penerimaan</th>
                <th>Tgl. Terima</th>
                <th>Customer</th>
                <th>Akun Terima</th>
                <th class="text-right">Total</th>
                <th>CUR</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($receipts)): ?>
                <?php foreach ($receipts as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r->receipt_no, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= date('d-m-Y', strtotime($r->receipt_date)); ?></td>
                        <td><?= htmlspecialchars($r->nama_customer, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?= htmlspecialchars($r->payment_account_name, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-right"><?= number_format((float)$r->total_amount, 2); ?></td>
                        <td class="text-center"><?= htmlspecialchars($r->currency, ENT_QUOTES, 'UTF-8'); ?></td>
                        <td class="text-center">
                            <span class="<?= strtolower($r->status) == 'posted' ? 'status-posted' : 'status-draft'; ?>">
                                <?= htmlspecialchars($r->status, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center"><em>Tidak ada data penerimaan ditemukan.</em></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
