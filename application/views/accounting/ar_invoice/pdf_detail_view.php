<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .container {
            width: 90%;
            margin: 20px auto;
        }

        h2 {
            text-align: center;
            margin-bottom: 0;
            text-transform: uppercase;
        }

        p {
            text-align: center;
            margin-top: 2px;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .header-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }

        .header-table td {
            padding: 5px;
            vertical-align: top;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .table th, 
        .table td {
            border: 1px solid #000;
            padding: 6px;
        }

        .table th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-table {
            width: 40%;
            float: right;
            margin-top: 20px;
            border-collapse: collapse;
        }

        .total-table th,
        .total-table td {
            border: 1px solid #000;
            padding: 6px;
        }

        .total-table th {
            background-color: #f9f9f9;
            text-align: left;
        }

        .grand-total {
            font-weight: bold;
            font-size: 1.1em;
            background: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>INVOICE</h2>
        <p><?= htmlspecialchars($header->invoice_no, ENT_QUOTES, 'UTF-8'); ?></p>
        
        <table class="header-table">
            <tr>
                <td style="width:15%;"><strong>Kepada Yth:</strong></td>
                <td style="width:45%;"><?= htmlspecialchars($header->nama_customer, ENT_QUOTES, 'UTF-8'); ?></td>
                <td style="width:20%;"><strong>Tanggal Faktur:</strong></td>
                <td style="width:20%;"><?= date('d M Y', strtotime($header->invoice_date)); ?></td>
            </tr>
            <tr>
                <td><strong>Alamat:</strong></td>
                <td><?= htmlspecialchars($header->customer_address, ENT_QUOTES, 'UTF-8'); ?></td>
                <td><strong>Jatuh Tempo:</strong></td>
                <td><?= date('d M Y', strtotime($header->due_date)); ?></td>
            </tr>
            <tr>
                <td><strong>NPWP:</strong></td>
                <td><?= htmlspecialchars($header->npwp, ENT_QUOTES, 'UTF-8'); ?></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <table class="table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($details)): ?>
                    <?php foreach($details as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d->description, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-right"><?= number_format((float)$d->qty, 2); ?></td>
                            <td class="text-right"><?= number_format((float)$d->unit_price, 2); ?></td>
                            <td class="text-right"><?= number_format((float)$d->total_price, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center"><em>Tidak ada data detail faktur.</em></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <table class="total-table">
            <tr>
                <th style="width:50%;">Subtotal</th>
                <td class="text-right" style="width:50%;"><?= number_format((float)$header->subtotal, 2); ?></td>
            </tr>
            <tr>
                <th>PPN</th>
                <td class="text-right"><?= number_format((float)$header->ppn_amount, 2); ?></td>
            </tr>
            <tr class="grand-total">
                <th>Total Tagihan</th>
                <td class="text-right"><?= number_format((float)$header->total_invoice, 2); ?></td>
            </tr>
        </table>
    </div>
</body>
</html>
