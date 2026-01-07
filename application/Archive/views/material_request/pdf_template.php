<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>MR: <?= htmlspecialchars($header->mr_number) ?></title>
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
        .signature-table td { border: 1px solid #333; text-align: center; vertical-align: bottom; padding: 8px; height: 80px; }
        .signature-table .sig-label { font-size: 9px; font-weight: bold; background-color: #f2f2f2; vertical-align: middle; padding: 8px;}
        .signature-table .sig-space { height: 60px; vertical-align: bottom; }
        .signature-table .sig-name { font-size: 9px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Material Request</h1>

    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>Nomor MR</strong></td><td style="width: 35%;">: <?= htmlspecialchars($header->mr_number); ?></td>
            <td style="width: 15%;"><strong>Status</strong></td><td style="width: 35%;">: <?= htmlspecialchars($header->status); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Minta</strong></td><td>: <?= date('d F Y', strtotime($header->request_date)); ?></td>
            <td></td><td></td>
        </tr>
    </table>

    <h3>Daftar Item Diminta</h3>
    <table class="item-table">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:15%">Work Order</th>
                <th style="width:15%">Kode Item</th>
                <th>Nama Item</th>
                <th style="width:10%">Qty Diminta</th>
                <th style="width:10%">Satuan</th>
                <th style="width:15%">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($details)): $no = 1; foreach($details as $d): ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($d->wo_number ?? '-') ?></td>
                <td><?= htmlspecialchars($d->item_code) ?></td>
                <td><?= htmlspecialchars($d->item_name) ?></td>
                <td class="text-right"><?= number_format($d->requested_qty, 4) ?></td>
                <td class="text-center"><?= htmlspecialchars($d->unit_measure ?? '-') ?></td>
                <td><?= nl2br(htmlspecialchars($d->notes)) ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center" style="padding: 20px;">- Tidak ada item -</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
     <?php if($header->remarks): ?>
         <p style="margin-top: 15px;"><strong>Catatan Header:</strong><br><?= nl2br(htmlspecialchars($header->remarks)); ?></p>
     <?php endif; ?>

     <table class="signature-table">
        <tr>
            <td class="sig-label" style="width: 33%;">Dibuat Oleh (PPIC)</td>
            <td class="sig-label" style="width: 34%;">Disiapkan Oleh (Gudang)</td>
            <td class="sig-label" style="width: 33%;">Diterima Oleh (Produksi)</td>
        </tr>
        <tr>
            <td class="sig-space">
                <div class="sig-name"><?= htmlspecialchars($header->requester_name ?? '_________________'); ?></div>
                <small style="font-size: 8px;"><?= $header->requested_at ? date('d/m/Y H:i', strtotime($header->requested_at)) : ''; ?></small>
            </td>
            <td class="sig-space">
                <div class="sig-name"><?= htmlspecialchars($header->issuer_name ?? '_________________'); ?></div>
                <small style="font-size: 8px;"><?= $header->issued_at ? date('d/m/Y H:i', strtotime($header->issued_at)) : ''; ?></small>
            </td>
            <td class="sig-space">
                <div class="sig-name"><?= htmlspecialchars($header->receiver_name ?? '_________________'); ?></div>
                <small style="font-size: 8px;"><?= $header->received_at ? date('d/m/Y H:i', strtotime($header->received_at)) : ''; ?></small>
            </td>
        </tr>
     </table>

    <div class="footer">
        Dicetak pada: <?= date('d/m/Y H:i:s'); ?> | Hal <span class="page"></span>
    </div>
</body>
</html>