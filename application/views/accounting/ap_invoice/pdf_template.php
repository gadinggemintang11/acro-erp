<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title; ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        .container { width: 100%; margin: 0 auto; }
        .header, .footer { text-align: center; }
        .header h1 { margin: 0; }
        .header p { margin: 5px 0; }
        .content { margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        .mb-3 { margin-bottom: 1rem; }
        .row::after { content: ""; clear: both; display: table; }
        .col-6 { float: left; width: 50%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>FAKTUR SUPPLIER</h1>
            <p>Nomor: <?= htmlspecialchars($header->invoice_no); ?></p>
        </div>

        <div class="content">
            <div class="row mb-3">
                <div class="col-6">
                    <strong>Supplier:</strong><br>
                    <?= htmlspecialchars($header->nama_supplier); ?>
                </div>
                <div class="col-6" style="text-align: right;">
                    <strong>Tanggal Faktur:</strong> <?= date('d M Y', strtotime($header->invoice_date)); ?><br>
                    <strong>Jatuh Tempo:</strong> <?= date('d M Y', strtotime($header->due_date)); ?>
                </div>
            </div>

            <h4>Detail Item</h4>
            <table>
                <thead>
                    <tr>
                        <th>Deskripsi</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Harga Satuan</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($details as $d): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($d->account_id ? "({$d->account_code}) {$d->account_name}" : $d->description); ?>
                        </td>
                        <td class="text-right"><?= number_format((float)$d->qty, 2); ?></td>
                        <td class="text-right"><?= number_format($d->unit_price, 2); ?></td>
                        <td class="text-right"><?= number_format($d->total_price, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="row" style="margin-top: 20px;">
                <div class="col-6">
                    <!-- Kolom kiri bisa untuk catatan -->
                </div>
                <div class="col-6">
                    <table style="width: 100%;">
                        <tr>
                            <th style="width: 50%;">Subtotal</th>
                            <td class="text-right"><?= number_format($header->subtotal, 2); ?></td>
                        </tr>
                        <tr>
                            <th>PPN</th>
                            <td class="text-right"><?= number_format($header->ppn_amount, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Potongan PPh</th>
                            <td class="text-right">(-) <?= number_format($header->pph_amount, 2); ?></td>
                        </tr>
                        <tr class="font-weight-bold">
                            <td>Total Tagihan</td>
                            <td class="text-right"><?= number_format($header->total_invoice, 2); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="row" style="margin-top: 50px;">
                <div class="col-6" style="text-align: center;">
                    <p>Disiapkan oleh,</p>
                    <br><br><br>
                    <p>(_________________________)</p>
                </div>
                <div class="col-6" style="text-align: center;">
                    <p>Disetujui oleh,</p>
                    <br><br><br>
                    <p>(_________________________)</p>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
