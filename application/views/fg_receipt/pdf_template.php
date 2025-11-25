<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Penerimaan FG - <?= htmlspecialchars($receipt_header->receipt_number); ?></title>
     <style>
        /* (Salin style dari PDF Produksi yang modern) */
         @import url('https://fonts.googleapis.com/css2?family=Lato:wght@400;700&display=swap');
        body { font-family: 'Lato', sans-serif; font-size: 9pt; color: #343a40; line-height: 1.5; } .container { width: 95%; margin: 0 auto; } .header { text-align: center; border-bottom: 2px solid #457b9d; padding-bottom: 10px; margin-bottom: 25px; } .header h1 { margin: 0 0 5px 0; font-size: 18pt; color: #1d3557; font-weight: 700; } .header p { margin: 0; font-size: 11pt; color: #6c757d; } .info-table { width: 100%; margin-bottom: 30px; border-collapse: collapse; } .info-table td { padding: 4px 0; vertical-align: top; font-size: 9.5pt; } .info-table .label { font-weight: 700; width: 120px; color: #495057; padding-right: 8px; } h3 { margin-top: 30px; border-bottom: 1px solid #adb5bd; padding-bottom: 8px; font-size: 14pt; color: #1d3557; font-weight: 700; page-break-after: avoid; } .details-table { width: 100%; margin-bottom: 25px; border-collapse: collapse; page-break-inside: avoid; } .details-table th, .details-table td { border: none; border-bottom: 1px solid #dee2e6; padding: 8px 6px; text-align: left; font-size: 9pt; vertical-align: middle; } .details-table th { font-weight: 700; text-align: center; color: #495057; border-bottom-width: 2px; border-color: #adb5bd; background-color: transparent; text-transform: uppercase; font-size: 8.5pt; letter-spacing: 0.5px; } .details-table tbody tr:last-child td { border-bottom: none; } .details-table .no { width: 30px; text-align: center; } .details-table .item-name { width: auto; font-weight: normal; } .details-table .item-code { width: 100px; } .details-table .qty { width: 70px; text-align: right; } .details-table .uom { width: 45px; text-align: center; color: #6c757d; } .details-table .notes { width: auto; color: #6c757d; font-size: 8.5pt; } .details-table .ref { width: 70px; text-align: center;} .details-table .empty-row td { text-align: center; color: #adb5bd; padding: 20px; border-bottom: none; } .signature-table { width: 100%; margin-top: 50px; text-align: center; font-size: 9.5pt; page-break-inside: avoid; border-collapse: collapse; } .signature-cell { width: 33.3%; padding-top: 60px; vertical-align: bottom; border-top: 1px solid #343a40; padding-bottom: 5px; } .supervisor-cell { padding-top: 60px; vertical-align: bottom; border-top: 1px solid #343a40; padding-bottom: 5px; } .signature-table .label { display: block; margin-bottom: 50px; font-weight: normal; color: #6c757d; } .signature-table .name { font-weight: 700; display: block; margin-top: 8px; color: #343a40; } .signature-table tr:nth-child(2) td { border-top: none; padding-top: 0px; } .signature-table tr:last-child .supervisor-cell { border-top: none; padding-top: 0px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>BUKTI PENERIMAAN BARANG JADI</h1>
            <p><?= htmlspecialchars($receipt_header->receipt_number); ?></p>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Tgl Terima</td><td>: <?= date('d F Y', strtotime($receipt_header->receipt_date)); ?></td>
                <td class="label">No. Produksi</td><td>: <?= htmlspecialchars($receipt_header->production_number); ?></td>
            </tr>
            <tr>
                 <td class="label">No. WO</td><td>: <?= htmlspecialchars($receipt_header->wo_number); ?></td>
                <td class="label">Status</td><td>: <b><?= htmlspecialchars($receipt_header->status); ?></b></td>
            </tr>
             <tr>
                 <td class="label" style="vertical-align: top;">Remarks</td>
                 <td colspan="3" style="vertical-align: top;">: <?= nl2br(htmlspecialchars($receipt_header->remarks ?: '-')); ?></td>
            </tr>
        </table>

        <h3>Item Diterima</h3>
        <table class="details-table">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th class="item-code">Kode FG</th>
                    <th class="item-name">Nama Barang Jadi</th>
                    <th class="qty">Qty Terima</th>
                    <th class="uom">Unit</th>
                    <th class="notes">Notes</th>
                    <th class="ref">Ref Prod</th>
                </tr>
            </thead>
            <tbody>
                 <?php if (!empty($receipt_details)): $no = 1; foreach($receipt_details as $detail): ?>
                <tr>
                    <td class="no"><?= $no++; ?></td>
                    <td class="item-code"><?= htmlspecialchars($detail->fg_code); ?></td>
                    <td class="item-name"><?= htmlspecialchars($detail->fg_name); ?></td>
                    <td class="qty"><?= number_format($detail->qty_received, 4); ?></td>
                    <td class="uom"><?= htmlspecialchars($detail->uom ?: $detail->fg_uom); ?></td>
                    <td class="notes"><?= htmlspecialchars($detail->notes); ?></td>
                    <td class="ref"><?= $detail->prod_output_id ?: '-'; ?></td>
                </tr>
                 <?php endforeach; else: ?>
                <tr class="empty-row"><td colspan="7">Tidak ada item detail.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <table class="signature-table">
            <?php
                 $receiver_name = htmlspecialchars($receipt_header->receiver_name ?: '-');
                 $confirmer_name = htmlspecialchars($receipt_header->confirmer_name ?: '-');
                 // Anda bisa tambahkan peran lain jika perlu, misal SPV Gudang
            ?>
             <tr>
                 <td style="border-top: none;"><span class="label">Diterima Oleh (Gudang FG),</span></td>
                 <td style="border-top: none;"><span class="label">Diketahui Oleh,</span></td>
                 <td style="border-top: none;"><span class="label">Dikonfirmasi Oleh,</span></td>
              </tr>
            <tr>
                <td class="signature-cell"><span class="name">(<?= $receiver_name ?>)</span></td>
                <td class="signature-cell"><span class="name">(____________________)</span></td> <td class="signature-cell"><span class="name">(<?= $confirmer_name ?>)</span></td>
             </tr>
        </table>

    </div>
</body>
</html>