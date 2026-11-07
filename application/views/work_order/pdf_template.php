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
            font-family: Arial, sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
        }
        /* HEADER */
        .header {
            text-align: center;
            background-color: #667eea;
            color: white;
            padding: 20px 15px;
            margin-bottom: 20px;
        }
        .header .company-name {
            font-size: 9.5pt;
            font-weight: bold;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 20pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header .doc-number {
            font-size: 12pt;
            font-weight: bold;
        }
        /* INFO SECTION */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .info-table td {
            padding: 8px 12px;
            font-size: 9.5pt;
            border: 1px solid #e2e8f0;
        }
        .info-table .label {
            font-weight: bold;
            width: 20%;
            color: #475569;
            text-transform: uppercase;
            font-size: 8.5pt;
            background-color: #f1f5f9;
        }
        /* SECTION TITLE */
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: white;
            background-color: #667eea;
            padding: 12px 15px;
            margin: 20px 0 15px 0;
            text-transform: uppercase;
        }
        /* DETAILS TABLE */
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border: 1px solid #cbd5e1;
        }
        .details-table thead tr {
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
            padding: 10px 8px;
            font-size: 9.5pt;
            border: 1px solid #cbd5e1;
        }
        .details-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .details-table .no {
            text-align: center;
            width: 30px;
            font-weight: bold;
        }
        .details-table .code {
            font-family: monospace;
            font-weight: bold;
            color: #1e40af;
        }
        .details-table .qty {
            text-align: right;
            width: 70px;
            font-weight: bold;
        }
        .details-table .unit {
            text-align: center;
            width: 60px;
        }
        .details-table .type {
            text-align: center;
            width: 80px;
        }
        /* BADGES */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 2px;
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
            padding: 4px 10px;
        }
        /* REMARKS */
        .remarks-section {
            margin: 20px 0;
            padding: 12px;
            background-color: #f0f4ff;
            border-left: 3px solid #667eea;
            border: 1px solid #e2e8f0;
        }
        .remarks-section .label {
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-size: 8.5pt;
        }
        .remarks-section .content {
            color: #475569;
            font-size: 9.5pt;
        }
        /* SIGNATURE */
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 33.33%;
            text-align: center;
            padding: 15px;
            vertical-align: top;
            border: 1px solid #cbd5e1;
        }
        .sig-box {
            min-height: 100px;
            background-color: white;
        }
        .sig-label {
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 40px;
            font-size: 8.5pt;
            text-transform: uppercase;
        }
        .sig-line {
            border-top: 1px solid #1e293b;
            padding-top: 4px;
            height: 16px;
        }
        .sig-name {
            color: #1e293b;
            font-size: 8.5pt;
        }
        /* FOOTER */
        .footer-note {
            margin-top: 25px;
            text-align: center;
            font-size: 8.5pt;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="company-name"><?= htmlspecialchars(company_info('company_name')) ?></div>
            <h1>Work Order</h1>
            <div class="doc-number"><?= htmlspecialchars($header->wo_number); ?></div>
        </div>

        <!-- INFO TABLE -->
        <table class="info-table">
            <tr>
                <td class="label">Sales Order Number</td>
                <td><strong><?= htmlspecialchars($header->sc_number); ?></strong></td>
                <td class="label">Qty Rencana</td>
                <td><strong><?= number_format($header->planned_qty, 2); ?></strong></td>
            </tr>
            <tr>
                <td class="label">Customer</td>
                <td><?= htmlspecialchars($header->nama_customer); ?></td>
                <td class="label">Tgl Mulai</td>
                <td><?= date('d M Y', strtotime($header->planned_start_date)); ?></td>
            </tr>
            <tr>
                <td class="label">Finished Good</td>
                <td><strong><?= htmlspecialchars($header->fg_name); ?></strong></td>
                <td class="label">Tgl Selesai</td>
                <td><?= $header->planned_end_date ? date('d M Y', strtotime($header->planned_end_date)) : '-'; ?></td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td><span class="badge badge-status"><?= htmlspecialchars($header->status); ?></span></td>
                <td class="label">Dibuat Oleh</td>
                <td><?= htmlspecialchars($header->creator_name); ?></td>
            </tr>
        </table>

        <!-- SECTION TITLE -->
        <div class="section-title">DETAIL KOMPONEN MATERIAL</div>

        <!-- DETAILS TABLE -->
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
                <?php if (!empty($details)): ?>
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
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: #94a3b8; padding: 20px;">Tidak ada data material.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- REMARKS -->
        <?php if (!empty($header->remarks)): ?>
        <div class="remarks-section">
            <div class="label">Catatan</div>
            <div class="content"><?= nl2br(htmlspecialchars($header->remarks)); ?></div>
        </div>
        <?php endif; ?>

        <?php
        $creator_sig = '';
        $confirmer_sig = '';

        if (!empty($header->creator_signature)) {
            $sig_path = FCPATH . $header->creator_signature;
            if (file_exists($sig_path)) {
                $creator_sig = '<img src="' . $sig_path . '" style="height: 40px; width: auto; margin-bottom: 5px;" />';
            }
        }

        if (!empty($header->confirmer_signature)) {
            $sig_path = FCPATH . $header->confirmer_signature;
            if (file_exists($sig_path)) {
                $confirmer_sig = '<img src="' . $sig_path . '" style="height: 40px; width: auto; margin-bottom: 5px;" />';
            }
        }
        ?>

        <!-- SIGNATURE -->
        <div class="signature-section">
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="sig-box">
                            <div class="sig-label">Dibuat Oleh</div>
                            <?= $creator_sig; ?>
                            <div class="sig-line"></div>
                            <div class="sig-name"><?= htmlspecialchars($header->creator_name); ?></div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-box">
                            <div class="sig-label">Mengetahui</div>
                            <div class="sig-line"></div>
                            <div class="sig-name">Manager PPIC</div>
                        </div>
                    </td>
                    <td>
                        <div class="sig-box">
                            <div class="sig-label">Disetujui</div>
                            <?= $confirmer_sig; ?>
                            <div class="sig-line"></div>
                            <div class="sig-name">Manager Produksi</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- FOOTER -->
        <div class="footer-note">
            Dokumen dicetak pada: <?= date('d M Y H:i:s'); ?>
        </div>
    </div>
</body>
</html>
