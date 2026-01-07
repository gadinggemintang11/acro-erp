<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Work Order <?= htmlspecialchars($header->wo_number); ?></title>
    <style>
        @page {
            margin: 20mm 15mm;
        }
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', 'Arial', sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.5;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            background: #1e293b;
            color: white;
            padding: 25px;
            border-bottom: 4px solid #1e40af;
            margin-bottom: 25px;
        }
        .header h1 {
            margin: 0;
            font-size: 22pt;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: white;
        }
        .header p {
            margin: 12px 0 0 0;
            font-size: 13pt;
            font-weight: 600;
            background: #1e40af;
            display: inline-block;
            padding: 6px 24px;
            border-radius: 4px;
            letter-spacing: 1px;
        }
        .info-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 18px;
            margin-bottom: 25px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 7px 12px;
            vertical-align: top;
            font-size: 9.5pt;
        }
        .info-table .label {
            font-weight: 600;
            width: 140px;
            color: #475569;
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 0.3px;
        }
        .info-table .value {
            color: #1e293b;
            font-weight: 500;
        }
        .section-title {
            font-size: 11pt;
            font-weight: 700;
            color: #1e293b;
            margin: 30px 0 15px 0;
            padding: 10px 15px;
            background: #f1f5f9;
            border-left: 4px solid #1e40af;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border: 1px solid #cbd5e1;
        }
        .details-table thead {
            background: #1e293b;
            color: white;
        }
        .details-table th {
            padding: 12px 10px;
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 0.5px;
            border: 1px solid #475569;
        }
        .details-table td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            font-size: 9.5pt;
        }
        .details-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .details-table .no { 
            width: 40px; 
            text-align: center;
            font-weight: 600;
            color: #64748b;
        }
        .details-table .code {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #1e40af;
        }
        .details-table .qty { 
            width: 110px; 
            text-align: right;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            font-weight: 600;
            color: #1e293b;
            font-variant-numeric: tabular-nums;
        }
        .details-table .unit { 
            width: 80px; 
            text-align: center;
            font-weight: 500;
            color: #475569;
        }
        .details-table .type {
            text-align: center;
            width: 100px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-material {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .badge-semifinished {
            background: #fed7aa;
            color: #c2410c;
            border: 1px solid #fdba74;
        }
        .badge-status {
            background: #d1fae5;
            color: #065f46;
            font-size: 9pt;
            padding: 5px 14px;
            border: 1px solid #a7f3d0;
        }
        .remarks-section {
            margin-top: 25px;
            padding: 15px 18px;
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            border: 1px solid #fde68a;
        }
        .remarks-section .label {
            font-weight: 700;
            color: #92400e;
            margin-bottom: 6px;
            text-transform: uppercase;
            font-size: 8.5pt;
            letter-spacing: 0.5px;
        }
        .remarks-section .content {
            color: #78350f;
            font-size: 9.5pt;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            text-align: center;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 33.3%;
            padding: 10px;
            vertical-align: top;
        }
        .sig-box {
            border: 1px solid #cbd5e1;
            padding: 15px;
            background: white;
            min-height: 100px;
        }
        .sig-label {
            font-weight: 700;
            color: #475569;
            margin-bottom: 55px;
            font-size: 8.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sig-line {
            border-top: 2px solid #1e293b;
            margin: 0 15px;
            padding-top: 8px;
        }
        .sig-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 9.5pt;
        }
        .footer-note {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 12px;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Work Order</h1>
            <p><?= htmlspecialchars($header->wo_number); ?></p>
        </div>

        <div class="info-section">
            <table class="info-table">
                <tr>
                    <td class="label">Sales Contract</td>
                    <td class="value">: <strong><?= htmlspecialchars($header->sc_number); ?></strong></td>
                    <td class="label">Qty Rencana</td>
                    <td class="value">: <strong><?= number_format($header->planned_qty, 2); ?></strong></td>
                </tr>
                <tr>
                    <td class="label">Customer</td>
                    <td class="value">: <?= htmlspecialchars($header->nama_customer); ?></td>
                    <td class="label">Tgl Mulai</td>
                    <td class="value">: <?= date('d F Y', strtotime($header->planned_start_date)); ?></td>
                </tr>
                <tr>
                    <td class="label">Finished Good</td>
                    <td class="value">: <strong><?= htmlspecialchars($header->fg_name); ?></strong></td>
                    <td class="label">Tgl Selesai</td>
                    <td class="value">: <?= $header->planned_end_date ? date('d F Y', strtotime($header->planned_end_date)) : '-'; ?></td>
                </tr>
                <tr>
                    <td class="label">Status</td>
                    <td class="value">: <span class="badge badge-status"><?= htmlspecialchars($header->status); ?></span></td>
                    <td class="label">Dibuat Oleh</td>
                    <td class="value">: <?= htmlspecialchars($header->creator_name); ?></td>
                </tr>
            </table>
        </div>

        <div class="section-title">DETAIL KOMPONEN MATERIAL</div>

        <table class="details-table">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th>Kode Komponen</th>
                    <th>Nama Komponen</th>
                    <th class="type">Tipe</th>
                    <th class="qty">Qty Plan</th>
                    <th class="unit">Satuan</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($details as $d): ?>
                <tr>
                    <td class="no"><?= $no++; ?></td>
                    <td class="code"><?= htmlspecialchars($d->item_code); ?></td>
                    <td><?= htmlspecialchars($d->item_name); ?></td>
                    <td class="type">
                        <span class="badge <?= $d->item_type == 'material' ? 'badge-material' : 'badge-semifinished'; ?>">
                            <?= htmlspecialchars($d->item_type); ?>
                        </span>
                    </td>
                    <td class="qty"><?= number_format($d->planned_qty, 2); ?></td>
                    <td class="unit"><?= htmlspecialchars($d->unit_measure ?? '-'); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($details)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; color: #94a3b8; font-style: italic; padding: 20px;">Tidak ada data material.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if (!empty($header->remarks)): ?>
        <div class="remarks-section">
            <div class="label">Catatan</div>
            <div class="content"><?= htmlspecialchars($header->remarks); ?></div>
        </div>
        <?php endif; ?>

        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="sig-box">
                            <div class="sig-label">Dibuat Oleh</div>
                            <div class="sig-line">
                                <div class="sig-name"><?= htmlspecialchars($header->creator_name); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-box">
                            <div class="sig-label">Mengetahui</div>
                            <div class="sig-line">
                                <div class="sig-name">Manager PPIC</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-box">
                            <div class="sig-label">Disetujui</div>
                            <div class="sig-line">
                                <div class="sig-name">Manager Produksi</div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer-note">
            Dokumen dicetak pada: <?= date('d F Y H:i:s'); ?>
        </div>

    </div>
</body>
</html>