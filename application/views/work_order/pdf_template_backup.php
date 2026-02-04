<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Work Order <?= htmlspecialchars($header->wo_number); ?></title>
    <style>
        @page {
            margin: 20mm 15mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.5;
        }
        .container {
            width: 100%;
        }
        .header {
            text-align: center;
            background-color: #667eea;
            color: white;
            padding: 25px 15px;
            margin-bottom: 25px;
        }
        .header .company-name {
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 22pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header .doc-number {
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        .info-section {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            margin-bottom: 25px;
            width: 100%;
            border-collapse: collapse;
        }
        .info-section td {
            padding: 8px 12px;
            font-size: 9.5pt;
            border: 1px solid #e2e8f0;
        }
        .info-section .label {
            font-weight: bold;
            width: 20%;
            color: #475569;
            text-transform: uppercase;
            font-size: 8.5pt;
            background-color: #f1f5f9;
        }
        .info-section .value {
            color: #1e293b;
            font-weight: normal;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: white;
            margin: 25px 0 15px 0;
            padding: 12px 15px;
            background-color: #667eea;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 100%;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border: 1px solid #cbd5e1;
        }
        .details-table thead {
            background-color: #667eea;
            color: white;
        }
        .details-table th {
            padding: 12px 8px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5pt;
            border: 1px solid #667eea;
        }
        .details-table td {
            border: 1px solid #cbd5e1;
            padding: 10px 8px;
            font-size: 9.5pt;
        }
        .details-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .details-table td {
            border: 1px solid #e2e8f0;
            padding: 12px 10px;
            font-size: 9.5pt;
        }
        .details-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .details-table .no { 
            width: 30px; 
            text-align: center;
            font-weight: bold;
        }
        .details-table .code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: #1e40af;
        }
        .details-table .qty { 
            width: 80px; 
            text-align: right;
            font-weight: bold;
        }
        .details-table .unit { 
            width: 70px; 
            text-align: center;
        }
        .details-table .type {
            text-align: center;
            width: 90px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-material {
            background-color: #dbeafe;
            color: #0c4a6e;
        }
        .badge-semifinished {
            background-color: #fed7aa;
            color: #7c2d12;
        }
        .badge-status {
            background-color: #667eea;
            color: white;
            font-size: 8.5pt;
            padding: 5px 12px;
        }
        .remarks-section {
            margin-top: 25px;
            padding: 15px;
            background-color: #f0f4ff;
            border-left: 4px solid #667eea;
            border: 1px solid #e2e8f0;
        }
        .remarks-section .label {
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 6px;
            text-transform: uppercase;
            font-size: 8.5pt;
        }
        .remarks-section .content {
            color: #475569;
            font-size: 9.5pt;
            line-height: 1.6;
        }
        .signature-section {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 33%;
            text-align: center;
            padding: 15px;
            vertical-align: top;
        }
        .sig-box {
            border: 1px solid #cbd5e1;
            padding: 15px;
            background-color: white;
            min-height: 110px;
        }
        .sig-label {
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 50px;
            font-size: 8.5pt;
            text-transform: uppercase;
        }
        .sig-line {
            border-top: 1px solid #1e293b;
            padding-top: 8px;
            height: 20px;
        }
        .sig-name {
            font-weight: normal;
            color: #1e293b;
            font-size: 9pt;
        }
        .footer-note {
            margin-top: 30px;
            text-align: center;
            font-size: 8.5pt;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-name">PT MINGBAO LIANCHUANG INTERNATIONAL</div>
            <h1>Work Order</h1>
            <div class="doc-number"><?= htmlspecialchars($header->wo_number); ?></div>
        </div>

        <div class="info-section">
            <div>
                <table class="info-table">
                    <tr>
                        <td class="label">Sales Contract</td>
                        <td class="value"><strong><?= htmlspecialchars($header->sc_number); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label">Customer</td>
                        <td class="value"><?= htmlspecialchars($header->nama_customer); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Finished Good</td>
                        <td class="value"><strong><?= htmlspecialchars($header->fg_name); ?></strong></td>
                    </tr>
                </table>
            </div>
            <div>
                <table class="info-table">
                    <tr>
                        <td class="label">Qty Rencana</td>
                        <td class="value"><strong><?= number_format($header->planned_qty, 2); ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label">Tgl Mulai</td>
                        <td class="value"><?= date('d M Y', strtotime($header->planned_start_date)); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Tgl Selesai</td>
                        <td class="value"><?= $header->planned_end_date ? date('d M Y', strtotime($header->planned_end_date)) : '-'; ?></td>
                    </tr>
                </table>
            </div>
            <div style="grid-column: 1 / -1;">
                <table class="info-table" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <tr style="border: none; display: flex; flex-direction: column;">
                        <td class="label">Status</td>
                        <td class="value"><span class="badge badge-status"><?= htmlspecialchars($header->status); ?></span></td>
                    </tr>
                    <tr style="border: none; display: flex; flex-direction: column;">
                        <td class="label">Dibuat Oleh</td>
                        <td class="value"><?= htmlspecialchars($header->creator_name); ?></td>
                    </tr>
                </table>
            </div>
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
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                <div class="sig-box">
                    <div class="sig-label">Dibuat Oleh</div>
                    <div class="sig-line">
                        <div class="sig-name"><?= htmlspecialchars($header->creator_name); ?></div>
                    </div>
                </div>
                <div class="sig-box">
                    <div class="sig-label">Mengetahui</div>
                    <div class="sig-line">
                        <div class="sig-name">Manager PPIC</div>
                    </div>
                </div>
                <div class="sig-box">
                    <div class="sig-label">Disetujui</div>
                    <div class="sig-line">
                        <div class="sig-name">Manager Produksi</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-note">
            Dokumen dicetak pada: <?= date('d F Y H:i:s'); ?>
        </div>

    </div>
</body>
</html>