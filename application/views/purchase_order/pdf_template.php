<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Purchase Order: <?= htmlspecialchars($header->po_number) ?></title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; }
        .header, .footer { width: 100%; text-align: center; position: fixed; }
        .header { top: 0px; }
        .footer { bottom: 0px; font-size: 8px; }
        .pagenum:before { content: counter(page); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        th { background-color: #f2f2f2; text-align: center;}
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-0 { margin-bottom: 0; }
        h1 { text-align: center; margin-bottom: 20px; font-size: 16px; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { border: none; padding: 2px 0;}
    </style>
</head>
<body>
    <h1>PURCHASE ORDER</h1>

    <table class="info-table">
        <tr>
            <td style="width: 50%;">
                <strong>Kepada Yth:</strong><br>
                <?= htmlspecialchars($header->nama_supplier); ?><br>
                <?= nl2br(htmlspecialchars($header->alamat)); ?><br>
                Telp: <?= htmlspecialchars($header->telepon); ?>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <strong>Nomor PO:</strong> <?= htmlspecialchars($header->po_number); ?><br>
                <strong>Tanggal:</strong> <?= date('d F Y', strtotime($header->po_date)); ?><br>
                <strong>Currency:</strong> <?= htmlspecialchars($header->currency_code); ?>
                <?php if($header->currency_code != 'IDR'): ?>
                    (Kurs: <?= number_format($header->exchange_rate, 4); ?>)
                <?php endif; ?><br>
                <strong>Pembayaran:</strong> <?= htmlspecialchars($header->payment_mode_desc ?? '-'); ?>
            </td>
        </tr>
    </table>


    <table>
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:15%">Kode</th>
                <th>Nama Barang</th>
                <th style="width:10%">Qty</th>
                <th style="width:10%">Satuan</th>
                <th style="width:15%">Harga (<?= $header->currency_code; ?>)</th>
                <th style="width:15%">Total (<?= $header->currency_code; ?>)</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; $grand_total_base = 0; foreach($details as $d): ?>
            <?php $total_base = $d->qty * $d->base_price; ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($d->item_code); ?></td>
                <td>
                    <?= htmlspecialchars($d->item_name); ?>
                    <?php if(!empty($d->description)): ?>
                        <br><small><?= htmlspecialchars($d->description); ?></small>
                    <?php endif; ?>
                </td>
                <td class="text-right"><?= number_format($d->qty, 2); ?></td>
                <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                <td class="text-right"><?= number_format($d->base_price, 2); ?></td>
                <td class="text-right"><?= number_format($total_base, 2); ?></td>
            </tr>
            <?php $grand_total_base += $total_base; endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right"><strong>Grand Total (<?= $header->currency_code; ?>)</strong></td>
                <td class="text-right"><strong><?= number_format($grand_total_base, 2); ?></strong></td>
            </tr>
             <?php if($header->has_ppn): ?>
             <?php $ppn_amount = $grand_total_base * 0.11; ?>
             <tr>
                <td colspan="6" class="text-right">PPN 11%</td>
                <td class="text-right"><?= number_format($ppn_amount, 2); ?></td>
            </tr>
             <tr>
                <td colspan="6" class="text-right"><strong>Total Setelah PPN</strong></td>
                <td class="text-right"><strong><?= number_format($grand_total_base + $ppn_amount, 2); ?></strong></td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>

     <?php if($header->remarks): ?>
         <p style="margin-top: 20px;"><strong>Catatan:</strong><br><?= nl2br(htmlspecialchars($header->remarks)); ?></p>
     <?php endif; ?>

    <table style="margin-top: 40px; border: none;">
         <tr>
             <td style="width: 50%; text-align: center; border: none;">Disetujui Oleh,</td>
             <td style="width: 50%; text-align: center; border: none;">Dibuat Oleh,</td>
         </tr>
          <tr>
             <td style="height: 60px; border: none;"></td>
             <td style="border: none;"></td>
         </tr>
          <tr>
             <td style="text-align: center; border: none;">(.......................)</td>
             <td style="text-align: center; border: none;">( <?= htmlspecialchars($this->session->userdata('fullname') ?? 'Admin'); ?> )</td>
         </tr>
    </table>

</body>
</html>