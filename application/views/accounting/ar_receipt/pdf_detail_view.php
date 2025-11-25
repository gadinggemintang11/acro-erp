<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $title; ?></title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; margin: 20px; color: #333; }
        h2 { text-align: center; margin-bottom: 10px; text-transform: uppercase; }
        .info-table, .detail-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td { padding: 5px 8px; vertical-align: top; }
        .detail-table th, .detail-table td {
            border: 1px solid #444; padding: 6px; text-align: left;
        }
        .detail-table th { background-color: #f2f2f2; }
        .right { text-align: right; }
        .center { text-align: center; }
        .footer { margin-top: 40px; width: 100%; }
        .footer td { text-align: center; padding-top: 25px; }
        .signature-box { border-top: 1px solid #000; width: 150px; margin: 0 auto; }
    </style>
</head>
<body>

    <h2>Bukti Penerimaan Piutang</h2>

    <table class="info-table">
        <tr>
            <td><strong>No. Penerimaan</strong></td>
            <td>: <?= htmlspecialchars($header->receipt_no); ?></td>
            <td><strong>Tanggal</strong></td>
            <td>: <?= date('d-m-Y', strtotime($header->receipt_date)); ?></td>
        </tr>
        <tr>
            <td><strong>Customer</strong></td>
            <td>: <?= htmlspecialchars($header->nama_customer); ?></td>
            <td><strong>Mata Uang</strong></td>
            <td>: <?= htmlspecialchars($header->currency); ?></td>
        </tr>
        <tr>
            <td><strong>Akun Penerimaan</strong></td>
            <td>: <?= htmlspecialchars($header->payment_account_name . ' (' . $header->account_code . ')'); ?></td>
            <td><strong>Kurs</strong></td>
            <td>: <?= number_format($header->exchange_rate, 2); ?></td>
        </tr>
        <tr>
            <td><strong>Total Diterima</strong></td>
            <td>: <?= number_format($header->total_amount, 2); ?> <?= $header->currency; ?></td>
            <td><strong>Total (IDR)</strong></td>
            <td>: <?= number_format($header->total_amount_local, 2); ?></td>
        </tr>
        <?php if (!empty($header->notes)): ?>
        <tr>
            <td><strong>Catatan</strong></td>
            <td colspan="3">: <?= nl2br(htmlspecialchars($header->notes)); ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <h4>Rincian Pembayaran Faktur</h4>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%" class="center">No</th>
                <th>No. Faktur</th>
                <th>Tanggal Faktur</th>
                <th class="right">Nilai Faktur</th>
                <th class="right">Jumlah Dibayar</th>
                <th class="right">Sisa Piutang</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1; 
            $total_bayar = 0;
            if (!empty($details)): 
                foreach ($details as $d): 
                    $sisa = $d->total_invoice - $d->amount_paid;
                    $total_bayar += $d->amount_paid;
            ?>
            <tr>
                <td class="center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($d->invoice_no); ?></td>
                <td><?= date('d-m-Y', strtotime($d->invoice_date)); ?></td>
                <td class="right"><?= number_format($d->total_invoice, 2); ?></td>
                <td class="right"><?= number_format($d->amount_paid, 2); ?></td>
                <td class="right"><?= number_format($sisa, 2); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="center">Tidak ada detail pembayaran</td></tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="right">Total Dibayar</th>
                <th class="right"><?= number_format($total_bayar, 2); ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <table class="footer">
        <tr>
            <td>Dibuat Oleh</td>
            <td>Disetujui Oleh</td>
            <td>Diterima Oleh</td>
        </tr>
        <tr>
            <td><br><br><br><div class="signature-box"></div></td>
            <td><br><br><br><div class="signature-box"></div></td>
            <td><br><br><br><div class="signature-box"></div></td>
        </tr>
    </table>

</body>
</html>
