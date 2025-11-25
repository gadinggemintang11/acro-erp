<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Retur Material - <?= htmlspecialchars($return_header->return_number); ?></title>
     <style>
        /* (Salin style PDF modern dari modul FG Delivery) */
         @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');
        body { font-family: 'Roboto', sans-serif; font-size: 9pt; color: #333; line-height: 1.6; margin: 0; } .container { width: 90%; margin: 20px auto; } .header { text-align: center; border-bottom: 1px solid #e0e0e0; padding-bottom: 15px; margin-bottom: 25px; } .header h1 { margin: 0 0 8px 0; font-size: 18pt; color: #1d3557; font-weight: 500; } .header p { margin: 0; font-size: 11pt; color: #5f6368; } .info-section table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-bottom: 25px; } .info-section td { padding: 5px 0; vertical-align: top; color: #3c4043; } .info-section .label { font-weight: 500; width: 120px; color: #5f6368; padding-right: 10px;} .info-section .value strong { font-weight: 500; } h3 { margin-top: 25px; margin-bottom: 12px; font-size: 13pt; color: #1d3557; font-weight: 500; border-bottom: 1px solid #e0e0e0; padding-bottom: 6px; page-break-after: avoid; } .details-table { width: 100%; margin-bottom: 30px; border-collapse: collapse; page-break-inside: avoid; } .details-table th, .details-table td { border: none; border-bottom: 1px solid #e0e0e0; padding: 9px 6px; text-align: left; font-size: 9pt; vertical-align: middle; color: #3c4043; } .details-table th { font-weight: 500; text-align: center; color: #5f6368; border-bottom-width: 1px; border-color: #bdbdbd; background-color: transparent; text-transform: uppercase; font-size: 8pt; letter-spacing: 0.8px; } .details-table tbody tr:last-child td { border-bottom: none; } .details-table .no { width: 30px; text-align: center; } .details-table .item-code { width: 100px; } .details-table .item-name { width: auto; } .details-table .batch { width: 80px; text-align: center; } .details-table .qty { width: 70px; text-align: right; font-weight: 500; } .details-table .uom { width: 45px; text-align: center; color: #5f6368; } .details-table .notes { width: auto; color: #5f6368; font-size: 8.5pt;} .details-table .empty-row td { text-align: center; color: #bdbdbd; padding: 25px; border-bottom: none; } .signature-table { width: 100%; margin-top: 50px; text-align: center; font-size: 9.5pt; page-break-inside: avoid; border-collapse: collapse; color: #5f6368; } .signature-cell { width: 33.3%; padding-top: 70px; vertical-align: bottom; border-top: 1px solid #bdbdbd; padding-bottom: 5px; } .signature-table .label { display: block; margin-bottom: 60px; font-weight: 400; } .signature-table .name { font-weight: 500; display: block; margin-top: 10px; color: #3c4043; } .signature-table tr:nth-child(2) td { border-top: none; padding-top: 0px; }
        @page { margin: 20mm 15mm 20mm 15mm; } .footer { position: fixed; bottom: -10mm; left: 0px; right: 0px; height: 40px; font-size: 8pt; text-align: center; color: #adb5bd; } .pagenum:before { content: counter(page); }
    </style>
</head>
<body>
     <div class="footer"> Dicetak pada: <?= date('d M Y H:i:s'); ?> | Halaman <span class="pagenum"></span> </div>

    <div class="container">
        <div class="header">
            <h1>BUKTI RETUR MATERIAL (DARI PRODUKSI)</h1>
            <p><?= htmlspecialchars($return_header->return_number); ?></p>
        </div>

        <div class="info-section">
            <table>
                <tr>
                    <td style="width: 50%;"> <table>
                            <tr><td class="label">Tanggal Retur</td><td class="value">: <strong><?= date('d F Y', strtotime($return_header->return_date)); ?></strong></td></tr>
                            <tr><td class="label">No. Produksi</td><td class="value">: <?= htmlspecialchars($return_header->production_number ?: '-'); ?></td></tr>
                            <tr><td class="label">Status</td><td class="value">: <b><?= htmlspecialchars($return_header->status); ?></b></td></tr>
                        </table>
                    </td>
                    <td style="width: 50%;"> <table>
                             <tr><td class="label">Diretur Oleh</td><td class="value">: <?= htmlspecialchars($return_header->returner_name ?: '-'); ?></td></tr>
                             <tr><td class="label">Diterima Gudang</td><td class="value">: <?= htmlspecialchars($return_header->receiver_name ?: '-'); ?></td></tr>
                            <tr><td class="label" style="vertical-align: top;">Remarks</td><td class="value" style="vertical-align: top;">: <?= nl2br(htmlspecialchars($return_header->remarks ?: '-')); ?></td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>


        <h3>Item yang Diretur</h3>
        <table class="details-table">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th class="item-code">Kode Item</th>
                    <th class="item-name">Nama Item</th>
                    <th class="batch">Batch (Ref. MR)</th>
                    <th class="qty">Qty Retur</th>
                    <th class="uom">Unit</th>
                    <th class="notes">Notes</th>
                </tr>
            </thead>
            <tbody>
                 <?php if (!empty($return_details)): $no = 1; foreach($return_details as $detail): ?>
                <tr>
                    <td class="no"><?= $no++; ?></td>
                    <td class="item-code"><?= htmlspecialchars($detail->item_code); ?></td>
                    <td class="item-name"><?= htmlspecialchars($detail->item_name); ?></td>
                    <td class="batch"><?= htmlspecialchars($detail->batch_number ?: '-'); ?></td>
                    <td class="qty"><?= number_format($detail->qty_returned, 4); ?></td>
                    <td class="uom"><?= htmlspecialchars($detail->uom); ?></td>
                    <td class="notes"><?= htmlspecialchars($detail->notes); ?></td>
                </tr>
                 <?php endforeach; else: ?>
                <tr class="empty-row"><td colspan="7">Tidak ada item detail.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <table class="signature-table">
             <tr>
                 <td style="border-top: none;"><span class="label">Diserahkan Oleh (Produksi),</span></td>
                 <td style="border-top: none;"><span class="label">Diterima Oleh (Gudang),</span></td>
                 <td style="border-top: none;"><span class="label">Mengetahui (SPV),</span></td>
              </tr>
            <tr>
                <td class="signature-cell"><span class="name">(<?= htmlspecialchars($return_header->returner_name ?: '____________________'); ?>)</span></td>
                <td class="signature-cell"><span class="name">(<?= htmlspecialchars($return_header->receiver_name ?: '____________________'); ?>)</span></td>
                <td class="signature-cell"><span class="name">(<?= htmlspecialchars($return_header->confirmer_name ?: '____________________'); ?>)</span></td>
             </tr>
        </table>

    </div>
</body>
</html>