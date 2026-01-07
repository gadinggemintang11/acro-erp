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
        .total-section { margin-top: 20px; float: right; width: 50%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BUKTI PEMBAYARAN UTANG</h1>
            <p>Nomor: <?= htmlspecialchars($header->payment_no); ?></p>
        </div>

        <div class="content">
            <div class="row mb-3">
                <div class="col-6">
                    <strong>Dibayarkan Kepada:</strong><br>
                    <?= htmlspecialchars($header->nama_supplier); ?>
                </div>
                <div class="col-6" style="text-align: right;">
                    <strong>Tanggal Bayar:</strong> <?= date('d M Y', strtotime($header->payment_date)); ?><br>
                    <strong>Akun Bayar:</strong> <?= htmlspecialchars($header->payment_account_name); ?>
                </div>
            </div>

            <h4>Detail Faktur yang Dibayar</h4>
            <table>
                <thead>
                    <tr>
                        <th>No. Faktur</th>
                        <th>Tgl. Faktur</th>
                        <th class="text-right">Total Faktur</th>
                        <th class="text-right">Jumlah Dibayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($details as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d->invoice_no); ?></td>
                        <td><?= date('d M Y', strtotime($d->invoice_date)); ?></td>
                        <td class="text-right"><?= number_format($d->total_invoice, 2); ?></td>
                        <td class="text-right font-weight-bold"><?= number_format($d->amount_paid, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="font-weight-bold">
                        <td colspan="3" class="text-right">TOTAL PEMBAYARAN</td>
                        <td class="text-right"><?= number_format($header->total_amount, 2); ?></td>
                    </tr>
                </tfoot>
            </table>

            <div class="row" style="margin-top: 20px;">
                <div class="col-6">
                    <strong>Catatan:</strong><br>
                    <?= htmlspecialchars($header->notes ?: '-'); ?>
                </div>
            </div>

            <div class="row" style="margin-top: 50px;">
                <table style="border: none; width: 100%; text-align: center;">
                    <tr style="border: none;">
                        <td style="border: none; width: 33%;">
                            <p>Disiapkan oleh,</p>
                            <br><br><br>
                            <p>(_________________________)</p>
                        </td>
                        <td style="border: none; width: 33%;">
                            <p>Diperiksa oleh,</p>
                            <br><br><br>
                            <p>(_________________________)</p>
                        </td>
                        <td style="border: none; width: 33%;">
                            <p>Disetujui oleh,</p>
                            <br><br><br>
                            <p>(_________________________)</p>
                        </td>
                    </tr>
                </table>
            </div>

        </div>
    </div>
</body>
</html>
