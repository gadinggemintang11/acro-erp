<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>MIR: <?= htmlspecialchars($header->mir_number) ?></title>
    <style>
        @page { margin: 25px 35px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        h1 { text-align: center; margin-bottom: 5px; font-size: 16px; text-decoration: underline; }
        p { margin: 0; }
        
        .info-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .info-table td { padding: 4px; vertical-align: top; }

        .item-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .item-table th, .item-table td { border: 1px solid #999; padding: 6px; text-align: left; }
        .item-table th { background-color: #f2f2f2; font-weight: bold; text-align: center;}
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .footer { position: fixed; bottom: -10px; left: 0px; right: 0px; height: 50px; font-size: 8px; }
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>
    <h1>MATERIAL IMPORT REQUEST</h1>

    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>Nomor MIR</strong></td>
            <td style="width: 35%;">: <?= htmlspecialchars($header->mir_number); ?></td>
            <td style="width: 15%;"><strong>Supplier</strong></td>
            <td style="width: 35%;">: <?= htmlspecialchars($header->nama_supplier ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td>
            <td>: <?= date('d F Y', strtotime($header->request_date)); ?></td>
            <td><strong>Tipe Bayar</strong></td>
            <td>: <?= htmlspecialchars($header->payment_type_desc ?? '-'); ?></td>
        </tr>
         <tr>
            <td><strong>Pemohon</strong></td>
            <td>: <?= htmlspecialchars($header->requester_name ?? 'N/A'); ?></td>
            <td><strong>Currency</strong></td>
            <td>: <?= htmlspecialchars($header->currency_code ?? '-'); ?></td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:15%">Sales Contract</th>
                <th style="width:15%">Kode Item</th>
                <th>Nama Item</th>
                <th style="width:10%">Qty</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($details)): $no = 1; foreach($details as $d): ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($d->sc_number ?? '-') ?></td>
                <td><?= htmlspecialchars($d->item_code) ?></td>
                <td><?= htmlspecialchars($d->item_name) ?></td>
                <td class="text-right"><?= number_format($d->qty, 2) ?></td>
                <td><?= htmlspecialchars($d->remarks) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center" style="padding: 20px;">- Tidak ada item -</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
     <?php if($header->remarks): ?>
         <p style="margin-top: 20px;"><strong>Keterangan:</strong><br><?= nl2br(htmlspecialchars($header->remarks)); ?></p>
     <?php endif; ?>

    <div class="footer">
        Dicetak pada: <?= date('d/m/Y H:i:s'); ?> | Halaman <span class="page"></span>
    </div>
</body>
</html>