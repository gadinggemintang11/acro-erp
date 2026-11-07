<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Penerimaan FG - <?= htmlspecialchars($receipt_header->receipt_number); ?></title>
    <style>
        @page {
            margin: 20mm 15mm;
        }
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', 'Arial', sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.6;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #1e293b;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 22pt;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .header p {
            margin: 0;
            font-size: 12pt;
            font-weight: 600;
            color: #475569;
            letter-spacing: 0.5px;
        }
        .info-section {
            background: white;
            border: 1px solid #cbd5e1;
            padding: 20px;
            margin-bottom: 25px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 8px 0;
            vertical-align: top;
            font-size: 10pt;
        }
        .info-table .label {
            font-weight: 600;
            width: 140px;
            color: #1e293b;
            font-size: 9pt;
        }
        .info-table .value {
            color: #1e293b;
            font-weight: 400;
        }
        .section-title {
            font-size: 11pt;
            font-weight: 700;
            color: #1e293b;
            margin: 30px 0 15px 0;
            padding: 12px 0;
            border-bottom: 2px solid #1e293b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .details-table {
            width: 100%;
            table-layout: fixed; /* lebar % di bawah -- dulu px tetap, tabel meluber keluar kertas A4 */
            border-collapse: collapse;
            margin-top: 15px;
            border: 1px solid #1e293b;
        }
        .details-table thead {
            background: white;
            border-bottom: 2px solid #1e293b;
        }
        .details-table th {
            padding: 7px 4px;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7.5pt;
            letter-spacing: 0;
            border: 1px solid #1e293b;
            color: #1e293b;
        }
        .details-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 4px;
            font-size: 8.5pt;
            word-wrap: break-word;
            color: #1e293b;
            background: white;
        }
        .details-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .details-table .no {
            width: 5%;
            text-align: center;
            font-weight: 600;
        }
        .details-table .item-code {
            width: 12%;
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
        .details-table .item-name {
            font-weight: 500;
        }
        .details-table .qty {
            width: 9%;
            text-align: right;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }
        .details-table .uom {
            width: 7%;
            text-align: center;
            font-weight: 500;
        }
        .details-table td.notes {
            font-size: 9pt;
        }
        .details-table .ref {
            width: 12%;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 7.5pt;
        }
        .details-table .empty-row td {
            text-align: center;
            color: #64748b;
            font-style: italic;
            padding: 20px;
        }
        .badge-status {
            display: inline-block;
            background: white;
            color: #1e293b;
            font-size: 9pt;
            padding: 4px 12px;
            border: 1px solid #1e293b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .remarks-section {
            margin-top: 20px;
            padding: 15px 18px;
            background: white;
            border: 1px solid #cbd5e1;
            border-left: 3px solid #1e293b;
        }
        .remarks-section .label {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-size: 9pt;
            letter-spacing: 0.5px;
        }
        .remarks-section .content {
            color: #1e293b;
            font-size: 10pt;
            line-height: 1.6;
        }
        .signature-section {
            margin-top: 60px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            text-align: center;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        .sig-box {
            border: 1px solid #1e293b;
            padding: 20px 15px;
            background: white;
            min-height: 80px;
        }
        .sig-label {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 50px;
            font-size: 9pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .sig-line {
            border-top: 2px solid #1e293b;
            margin: 0 20px;
            padding-top: 10px;
        }
        .sig-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 10pt;
        }
        .footer-note {
            margin-top: 30px;
            text-align: center;
            font-size: 8pt;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bukti Penerimaan Barang Jadi</h1>
            <p><?= htmlspecialchars($receipt_header->receipt_number); ?></p>
        </div>

        <div class="info-section">
            <table class="info-table">
                <tr>
                    <td class="label">Tgl Terima</td>
                    <td class="value">: <strong><?= date('d F Y', strtotime($receipt_header->receipt_date)); ?></strong></td>
                    <td class="label">Barang Jadi</td>
                    <td class="value">: <strong><?= htmlspecialchars(($receipt_header->fg_code ?? '') . ' - ' . ($receipt_header->fg_name ?? '-')); ?></strong></td>
                </tr>
                <tr>
                    <td class="label">No. Penerimaan</td>
                    <td class="value">: <?= htmlspecialchars($receipt_header->receipt_number); ?></td>
                    <td class="label">Status</td>
                    <td class="value">: <span class="badge-status"><?= htmlspecialchars($receipt_header->status); ?></span></td>
                </tr>
            </table>
        </div>

        <?php if (!empty($receipt_header->remarks)): ?>
        <div class="remarks-section">
            <div class="label">Catatan</div>
            <div class="content"><?= nl2br(htmlspecialchars($receipt_header->remarks)); ?></div>
        </div>
        <?php endif; ?>

        <div class="section-title">Detail Item Diterima</div>

        <table class="details-table">
            <thead>
                <tr>
                    <th class="no">No</th>
                    <th class="ref">No. WO</th>
                    <th class="ref">No. Sales Order</th>
                    <th class="ref">No. Produksi</th>
                    <th class="item-code">Kode FG</th>
                    <th class="item-name">Nama Barang Jadi</th>
                    <th class="qty">Qty Terima</th>
                    <th class="uom">Unit</th>
                    <th class="notes">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($receipt_details)): $no = 1; foreach($receipt_details as $detail): ?>
                <tr>
                    <td class="no"><?= $no++; ?></td>
                    <td class="ref"><?= htmlspecialchars($detail->wo_number ?: '-'); ?></td>
                    <td class="ref"><?= htmlspecialchars($detail->ipo_number ?: '-'); ?></td>
                    <td class="ref"><?= htmlspecialchars($detail->production_number ?: '-'); ?></td>
                    <td class="item-code"><?= htmlspecialchars($detail->fg_code); ?></td>
                    <td class="item-name"><?= htmlspecialchars($detail->fg_name); ?></td>
                    <td class="qty"><?= number_format($detail->qty_received, 2); ?></td>
                    <td class="uom"><?= htmlspecialchars($detail->uom ?: $detail->fg_uom); ?></td>
                    <td class="notes"><?= htmlspecialchars($detail->notes ?: '-'); ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr class="empty-row">
                    <td colspan="9">Tidak ada item detail.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="signature-section">
            <?php
                // 2 kolom (2026-11-06): Dibuat & Disetujui (approval 1 tahap Head of Dept).
                $sig_cols = [
                    ['label' => 'Dibuat',    'slot' => $sig_made ?? []],
                    ['label' => 'Disetujui', 'slot' => $sig_approved ?? []],
                ];
            ?>
            <table class="signature-table">
                <tr>
                    <?php foreach ($sig_cols as $col): $slot = $col['slot']; ?>
                    <td>
                        <div class="sig-box">
                            <div class="sig-label"><?= $col['label']; ?></div>
                            <div style="height: 58px;"><?= $slot['html'] ?? ''; ?></div>
                            <div class="sig-line">
                                <div class="sig-name"><?= htmlspecialchars($slot['name'] ?? '-'); ?></div>
                                <div style="font-size: 9px; color: #555;"><?= !empty($slot['date']) ? date('d/m/Y', strtotime($slot['date'])) : '&nbsp;'; ?></div>
                            </div>
                        </div>
                    </td>
                    <?php endforeach; ?>
                </tr>
            </table>
        </div>

        <div class="footer-note">
            Dokumen dicetak pada: <?= date('d F Y H:i:s'); ?>
        </div>

    </div>
</body>
</html>