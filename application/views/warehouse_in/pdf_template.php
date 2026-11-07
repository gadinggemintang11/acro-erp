<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>GRN: <?= htmlspecialchars($header->grn_number) ?></title>
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
        .signature-table td { border: 1px solid #777; text-align: center; vertical-align: top; padding: 5px; height: 70px; }
        .signature-table td.label { font-size: 9px; font-weight: bold; }
        .signature-table td.name { font-size: 9px; padding-top: 50px; }
    </style>
</head>
<body>
    <h1>Goods Receipt Note</h1>
    <table class="info-table">
        <tr>
            <td style="width: 15%;"><strong>Nomor GRN</strong></td><td style="width: 35%;">: <?= htmlspecialchars($header->grn_number); ?></td>
            <td style="width: 15%;"><strong>Supplier</strong></td><td style="width: 35%;">: <?= htmlspecialchars($header->nama_supplier ?? '-'); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal</strong></td><td>: <?= date('d F Y', strtotime($header->grn_date)); ?></td>
            <td><strong>Dasar</strong></td><td>: <?= htmlspecialchars($header->base_on); ?></td>
        </tr>
         <tr>
            <td><strong>Status</strong></td><td>: <?= htmlspecialchars($header->status); ?></td>
            <td><strong>Gudang</strong></td><td>: <?= ucfirst($header->warehouse_type); ?></td>
        </tr>
         <tr>
            <td><strong>No. SJ</strong></td><td>: <?= htmlspecialchars($header->delivery_note_number ?? '-'); ?></td><td></td><td></td>
        </tr>
        <?php if($header->bc_type_code || $header->bc_number): ?>
         <tr>
            <td><strong>BC Info</strong></td>
            <td colspan="3">: <?= htmlspecialchars($header->bc_type_code ?? '-') ?> / <?= htmlspecialchars($header->bc_number ?? '-') ?> / <?= $header->bc_date ? date('d-m-Y', strtotime($header->bc_date)) : '-' ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <table class="item-table">
        <thead>
            <tr>
                <th style="width:5%">No</th><th style="width:15%">No. Sumber</th><th style="width:15%">Kode Item</th>
                <th>Nama Item</th><th style="width:10%">Qty Terima</th><th style="width:15%">Nilai Satuan (IDR)</th><th style="width:15%">Total Nilai (IDR)</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($details)): $no = 1; $grand_total_value = 0; foreach($details as $d): ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td><td><?= htmlspecialchars($d->source_doc_number ?? '-') ?></td><td><?= htmlspecialchars($d->item_code) ?></td>
                <td><?= htmlspecialchars($d->item_name) ?></td><td class="text-right"><?= number_format($d->receive_qty, 2) ?></td>
                <td class="text-right"><?= number_format($d->unit_value ?? 0, 2) ?></td><td class="text-right"><?= number_format($d->total_value ?? 0, 2) ?></td>
            </tr>
            <?php $grand_total_value += ($d->total_value ?? 0); endforeach; else: ?>
            <tr><td colspan="7" class="text-center" style="padding: 20px;">- Tidak ada item -</td></tr>
            <?php endif; ?>
        </tbody>
         <?php if(!empty($details)): ?>
        <tfoot>
            <tr style="background-color: #f2f2f2;">
                <td colspan="6" class="text-right"><strong>Grand Total Nilai (IDR)</strong></td>
                <td class="text-right"><strong><?= number_format($grand_total_value, 2); ?></strong></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

     <?php if($header->remarks): ?> <p style="margin-top: 15px;"><strong>Catatan Header:</strong><br><?= nl2br(htmlspecialchars($header->remarks)); ?></p> <?php endif; ?>

     <?php
     $creator_sig = '';
     $confirmer_sig = '';

     if (!empty($header->creator_signature)) {
         $sig_path = FCPATH . $header->creator_signature;
         if (file_exists($sig_path)) {
             $creator_sig = '<img src="' . $sig_path . '" style="height: 40px; width: auto;" />';
         }
     }

     if (!empty($header->confirmer_signature)) {
         $sig_path = FCPATH . $header->confirmer_signature;
         if (file_exists($sig_path)) {
             $confirmer_sig = '<img src="' . $sig_path . '" style="height: 40px; width: auto;" />';
         }
     }
     ?>

     <table class="signature-table" style="border-collapse: collapse; width: 100%;">
        <tr>
            <td class="label" style="width: 33%; border: 1px solid #777; text-align: center; vertical-align: middle; padding: 8px;">Diterima Oleh</td>
            <td class="label" style="width: 33%; border: 1px solid #777; text-align: center; vertical-align: middle; padding: 8px;">Diperiksa Oleh</td>
            <td class="label" style="width: 34%; border: 1px solid #777; text-align: center; vertical-align: middle; padding: 8px;">Mengetahui</td>
        </tr>
         <tr>
            <td class="name" style="width: 33%; border: 1px solid #777; text-align: center; vertical-align: bottom; padding: 10px; height: 80px;">
                <?= $creator_sig; ?>
                <?= htmlspecialchars($header->creator_name ?? '(.......................)'); ?><br>
                <small><?= $header->created_at ? date('d/m/Y H:i', strtotime($header->created_at)) : ''; ?></small>
            </td>
            <td class="name" style="width: 33%; border: 1px solid #777; text-align: center; vertical-align: bottom; padding: 10px; height: 80px;">
                <?= $confirmer_sig; ?>
                <?= htmlspecialchars($header->confirmer_name ?? '(.......................)'); ?><br>
                <small><?= $header->confirmed_at ? date('d/m/Y H:i', strtotime($header->confirmed_at)) : ''; ?></small>
            </td>
            <td class="name" style="width: 34%; border: 1px solid #777; text-align: center; vertical-align: bottom; padding: 10px; height: 80px;">(.......................</td>
        </tr>
     </table>
    <div class="footer">Dicetak pada: <?= date('d/m/Y H:i:s'); ?> | Hal <span class="page"></span></div>
</body>
</html>