<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>WO: <?= htmlspecialchars($header->wo_number) ?></title>
    <style>
        @page { margin: 25px 35px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        h1 { text-align: center; margin-bottom: 5px; font-size: 14px; text-decoration: underline; text-transform: uppercase;}
        p { margin: 0 0 3px 0; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 3px 0; vertical-align: top; font-size: 9px;}
        .item-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .item-table th, .item-table td { border: 1px solid #777; padding: 5px; text-align: left; vertical-align: top; }
        .item-table th { background-color: #EAEAEA; font-weight: bold; text-align: center;}
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { position: fixed; bottom: 0px; left: 0px; right: 0px; height: 30px; font-size: 8px; text-align: center; border-top: 0.5px solid #ccc; padding-top: 5px;}
        .footer .page:after { content: counter(page); }
        .signature-table { margin-top: 30px; width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        .signature-table td { border: 1px solid #999; text-align: center; vertical-align: top; padding: 5px; height: 70px; }
        .signature-table td.label { font-size: 9px; font-weight: bold; background-color: #f2f2f2;}
        .signature-table td.name { font-size: 9px; padding-top: 50px; }
    </style>
</head>
<body>
    <h1>Work Order</h1>

    <table class="info-table">
        <tr>
            <td style="width: 18%;"><strong>Nomor WO</strong></td><td style="width: 32%;">: <?= htmlspecialchars($header->wo_number); ?></td>
            <td style="width: 18%;"><strong>Sales Contract</strong></td><td style="width: 32%;">: <?= htmlspecialchars($header->sc_number); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Buat</strong></td><td>: <?= date('d F Y', strtotime($header->created_at)); ?></td>
            <td><strong>Customer</strong></td><td>: <?= htmlspecialchars($header->nama_customer); ?></td>
        </tr>
         <tr>
            <td><strong>Status</strong></td><td>: <?= htmlspecialchars($header->status); ?></td>
            <td><strong>Style</strong></td><td>: <?= htmlspecialchars($header->fg_name . ' (' . $header->fg_code . ')'); ?></td>
        </tr>
         <tr>
            <td><strong>Qty Rencana</strong></td><td>: <?= number_format($header->planned_qty); ?></td>
            <td><strong>Tgl Mulai Plan</strong></td><td>: <?= $header->planned_start_date ? date('d M Y', strtotime($header->planned_start_date)) : '-'; ?></td>
        </tr>
    </table>

    <h3>Rencana Penggunaan Material</h3>
    <table class="item-table">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:10%">Tipe</th>
                <th style="width:15%">Kode Komponen</th>
                <th>Nama Komponen</th>
                <th style="width:15%">Total Qty Plan</th>
                <th style="width:10%">Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($details)): $no = 1; foreach($details as $d): ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= ucfirst($d->item_type); ?></td>
                <td><?= htmlspecialchars($d->item_code); ?></td>
                <td><?= htmlspecialchars($d->item_name); ?></td>
                <td class="text-right"><?= number_format($d->planned_qty, 4); ?></td>
                <td class="text-center"><?= htmlspecialchars($d->unit_measure ?? '-'); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center" style="padding: 20px;">- Tidak ada material direncanakan -</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
     <?php if($header->remarks): ?>
         <p style="margin-top: 15px;"><strong>Catatan Header:</strong><br><?= nl2br(htmlspecialchars($header->remarks)); ?></p>
     <?php endif; ?>

     <table class="signature-table">
        <tr>
            <td class="label" style="width: 33%;">Dibuat Oleh</td>
            <td class="label" style="width: 34%;">Disetujui Oleh</td>
            <td class="label" style="width: 33%;">Mengetahui</td>
        </tr>
         <tr>
            <td class="name">
                <?= htmlspecialchars($header->creator_name ?? '(.......................)'); ?><br>
                <small><?= $header->created_at ? date('d/m/Y H:i', strtotime($header->created_at)) : ''; ?></small>
            </td>
            <td class="name">
                <?= htmlspecialchars($header->confirmer_name ?? '(.......................)'); ?><br>
                <small><?= $header->confirmed_at ? date('d/m/Y H:i', strtotime($header->confirmed_at)) : ''; ?></small>
            </td>
            <td class="name">(.......................)</td>
        </tr>
     </table>

    <div class="footer">
        Dicetak pada: <?= date('d/m/Y H:i:s'); ?> | Hal <span class="page"></span>
    </div>
</body>
</html>