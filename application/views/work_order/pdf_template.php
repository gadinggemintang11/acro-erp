<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Work Order <?= htmlspecialchars($header->wo_number); ?></title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
        }
        .container {
            width: 95%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 18pt;
        }
        .header p {
            margin: 5px 0;
            font-size: 12pt;
        }
        .info-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px;
            vertical-align: top;
        }
        .info-table .label {
            font-weight: bold;
            width: 120px;
        }
        .details-table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .details-table th, .details-table td {
            border: 1px solid #999;
            padding: 8px;
            text-align: left;
        }
        .details-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .details-table .no { width: 30px; text-align: center; }
        .details-table .qty { width: 80px; text-align: right; }
        .details-table .unit { width: 60px; text-align: center; }
        .footer {
            margin-top: 40px;
            width: 100%;
            font-size: 9pt;
        }
        .signature-table {
            width: 100%;
            margin-top: 50px;
            text-align: center;
        }
        .signature-table td {
            width: 33.3%;
            padding-top: 60px;
            border-top: 1px solid #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WORK ORDER</h1>
            <p><?= htmlspecialchars($header->wo_number); ?></p>
        </div>

        <table class="info-table">
            <tr>
                <td class="label">Sales Contract</td>
                <td>: <?= htmlspecialchars($header->sc_number); ?></td>
                <td class="label">Qty Rencana</td>
                <td>: <?= number_format($header->planned_qty, 2); ?></td>
            </tr>
            <tr>
                <td class="label">Customer</td>
                <td>: <?= htmlspecialchars($header->nama_customer); ?></td>
                <td class="label">Tgl Mulai</td>
                <td>: <?= date('d F Y', strtotime($header->planned_start_date)); ?></td>
            </tr>
            <tr>
                <td class="label">Finished Good</td>
                <td>: <?= htmlspecialchars($header->fg_name); ?></td>
                <td class="label">Tgl Selesai</td>
                <td>: <?= $header->planned_end_date ? date('d F Y', strtotime($header->planned_end_date)) : '-'; ?></td>
            </tr>
             <tr>
                <td class="label">Status</td>
                <td>: <b><?= htmlspecialchars($header->status); ?></b></td>
                <td class="label">Dibuat Oleh</td>
                <td>: <?= htmlspecialchars($header->creator_name); ?></td>
            </tr>
        </table>

        <table class="details-table">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th>Kode Komponen</th>
                    <th>Nama Komponen</th>
                    <th>Tipe</th>
                    <th class="qty">Qty Plan</th>
                    <th class="unit">Satuan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($details as $d): ?>
                <tr>
                    <td class="no"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($d->item_code); ?></td>
                    <td><?= htmlspecialchars($d->item_name); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($d->item_type); ?></td>
                    <td class="qty"><?= number_format($d->planned_qty, 4); ?></td>
                    <td class="unit"><?= htmlspecialchars($d->unit_measure ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($details)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data material.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div classs="footer">
            <p>Catatan: <?= htmlspecialchars($header->remarks); ?></p>
        </div>

        <table class="signature-table">
            <tr>
                <td>Dibuat Oleh, <br><br><br><br> (<?= htmlspecialchars($header->creator_name); ?>)</td>
                <td>Mengetahui, <br><br><br><br> (Manager PPIC)</td>
                <td>Disetujui, <br><br><br><br> (Manager Produksi)</td>
            </tr>
        </table>

    </div>
</body>
</html>