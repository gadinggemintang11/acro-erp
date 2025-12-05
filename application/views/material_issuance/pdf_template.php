<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Material Issuance - <?= htmlspecialchars($header->issuance_number ?? 'N/A'); ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            font-size: 10pt; 
            margin: 20px; 
        }
        .header-title { 
            text-align: center; 
            font-size: 16pt; 
            font-weight: bold; 
            margin-bottom: 5px;
        }
        .header-subtitle {
            text-align: center;
            font-size: 11pt;
            margin-bottom: 20px;
            color: #555;
        }
        .info-table { 
            width: 100%; 
            margin-bottom: 20px; 
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
        .items-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px;
        }
        .items-table th, .items-table td { 
            border: 1px solid #333; 
            padding: 6px; 
            font-size: 9pt;
        }
        .items-table th { 
            background-color: #4472C4; 
            color: white; 
            font-weight: bold; 
            text-align: center;
        }
        .items-table td.center { text-align: center; }
        .items-table td.right { text-align: right; }
        .signature-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .signature-table td { 
            border: 1px solid #333; 
            padding: 8px; 
            text-align: center; 
            width: 33.33%;
            vertical-align: bottom;
        }
        .signature-table .sig-label { 
            font-weight: bold; 
            font-size: 9pt;
            background-color: #f2f2f2;
            vertical-align: middle;
            height: 35px;
        }
        .signature-table .sig-space {
            height: 80px;
            vertical-align: bottom;
        }
        .signature-table .sig-name { 
            font-size: 9pt; 
            font-weight: bold;
            margin-bottom: 3px;
        }
        .signature-table .sig-date {
            font-size: 8pt; 
            color: #666;
        }
        .footer-note {
            margin-top: 15px;
            font-size: 8pt;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-title">MATERIAL ISSUANCE</div>
    <div class="header-subtitle">Pengeluaran Material Gudang</div>

    <!-- Info Table -->
    <table class="info-table">
        <tr>
            <td class="label">No. Pengeluaran:</td>
            <td><?= htmlspecialchars($header->issuance_number ?? '-'); ?></td>
            <td class="label">Tanggal:</td>
            <td><?= $header->issued_at ? date('d F Y', strtotime($header->issued_at)) : '-'; ?></td>
        </tr>
        <tr>
            <td class="label">No. Material Request:</td>
            <td><?= htmlspecialchars($header->mr_number ?? '-'); ?></td>
            <td class="label">Status:</td>
            <td><?= htmlspecialchars($header->status ?? '-'); ?></td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 12%;">Work Order</th>
                <th style="width: 13%;">Kode Item</th>
                <th style="width: 30%;">Nama Item</th>
                <th style="width: 12%;">Qty Dikirim</th>
                <th style="width: 10%;">Satuan</th>
                <th style="width: 18%;">Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($details)): ?>
                <?php $no = 1; foreach ($details as $item): ?>
                <tr>
                    <td class="center"><?= $no++; ?></td>
                    <td class="center"><?= htmlspecialchars($item->wo_number ?? '-'); ?></td>
                    <td><?= htmlspecialchars($item->item_code ?? '-'); ?></td>
                    <td><?= htmlspecialchars($item->item_name ?? '-'); ?></td>
                    <td class="right"><?= number_format($item->issued_qty ?? 0, 4); ?></td>
                    <td class="center"><?= htmlspecialchars($item->item_unit ?? 'PCS'); ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="center">Tidak ada item</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Signature Table -->
    <table class="signature-table">
        <tr>
            <td class="sig-label">Dibuat Oleh (PPIC)</td>
            <td class="sig-label">Disiapkan Oleh (Gudang)</td>
            <td class="sig-label">Diterima Oleh (Produksi)</td>
        </tr>
        <tr>
            <td class="sig-space">
                <div class="sig-name"><?= htmlspecialchars($header->requester_name ?? '_________________'); ?></div>
                <div class="sig-date"><?= $header->requested_at ? date('d/m/Y H:i', strtotime($header->requested_at)) : ''; ?></div>
            </td>
            <td class="sig-space">
                <div class="sig-name"><?= htmlspecialchars($header->issuer_name ?? '_________________'); ?></div>
                <div class="sig-date"><?= $header->issued_at ? date('d/m/Y H:i', strtotime($header->issued_at)) : ''; ?></div>
            </td>
            <td class="sig-space">
                <div class="sig-name"><?= htmlspecialchars($header->receiver_name ?? '_________________'); ?></div>
                <div class="sig-date"><?= $header->received_at ? date('d/m/Y H:i', strtotime($header->received_at)) : ''; ?></div>
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini dicetak secara otomatis pada <?= date('d F Y H:i:s'); ?>
    </div>
</body>
</html>
