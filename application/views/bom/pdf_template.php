<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>BOM: <?= htmlspecialchars($header->fg_code) ?></title>
    <style>
        @page { margin: 25px 35px; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; color: #333; }
        h1 { text-align: center; margin-bottom: 5px; font-size: 14px; text-decoration: underline; text-transform: uppercase;}
        h2 { text-align: center; margin-top: 2px; margin-bottom: 10px; font-size: 11px; font-weight: normal;}
        p { margin: 0; }
        .info-table { width: 100%; margin-bottom: 15px; border-collapse: collapse; }
        .info-table td { padding: 3px 0; vertical-align: top; font-size: 9px;}
        .item-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .item-table th, .item-table td { border: 1px solid #777; padding: 5px; text-align: left; vertical-align: top; }
        .item-table th { background-color: #EAEAEA; font-weight: bold; text-align: center;}
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer { position: fixed; bottom: 0px; left: 0px; right: 0px; height: 30px; font-size: 8px; text-align: center; border-top: 0.5px solid #ccc; padding-top: 5px;}
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>
    <h1>Bill of Materials</h1>
    <h2>Style: <?= htmlspecialchars($header->fg_name . ' (' . $header->fg_code . ')'); ?></h2>

    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>Kode Style</strong></td><td style="width: 35%;">: <?= htmlspecialchars($header->fg_code); ?></td>
            <td style="width: 15%;"><strong>Revisi</strong></td><td style="width: 35%;">: <?= $header->revision; ?></td>
        </tr>
        <tr>
            <td><strong>Nama Style</strong></td><td>: <?= htmlspecialchars($header->fg_name); ?></td>
            <td><strong>Status</strong></td><td>: <?= $header->status; ?></td>
        </tr>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:10%">Tipe</th>
                <th style="width:15%">Kode Komponen</th>
                <th>Nama Komponen</th>
                <th style="width:15%">Qty Dibutuhkan</th>
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
                <td class="text-right"><?= number_format($d->quantity_required, 4); ?></td>
                <td class="text-center"><?= htmlspecialchars($d->unit_measure ?? '-'); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-center" style="padding: 20px;">- Tidak ada komponen -</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: <?= date('d/m/Y H:i:s'); ?> | Hal <span class="page"></span>
    </div>
</body>
</html>