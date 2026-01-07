<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Jurnal Umum - <?= htmlspecialchars($header->journal_no); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
        }
        .header p {
            margin: 3px 0;
            font-size: 12px;
        }
        .info-section {
            margin-bottom: 15px;
            font-size: 12px;
        }
        .info-section p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 12px;
        }
        table thead {
            background-color: #e8e8e8;
            font-weight: bold;
        }
        table th {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        table td {
            border: 1px solid #ccc;
            padding: 8px;
        }
        table td.number {
            text-align: right;
        }
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .total-row td {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }
        .footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature-block {
            width: 200px;
            text-align: center;
            font-size: 11px;
        }
        .signature-block .name {
            margin-top: 40px;
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
        .validation-box {
            margin-top: 20px;
            border: 2px solid #333;
            padding: 12px;
            background-color: #f9f9f9;
        }
        .validation-box p {
            margin: 5px 0;
            font-size: 11px;
        }
        .validation-box .label {
            font-weight: bold;
        }
        .right {
            float: right;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>JURNAL UMUM (JURNAL ENTRY)</h2>
    <p>Laporan Detail Transaksi Jurnal</p>
</div>

<div class="info-section">
    <p><strong>No. Jurnal:</strong> <?= htmlspecialchars($header->journal_no); ?></p>
    <p><strong>Tanggal:</strong> <?= date('d/m/Y', strtotime($header->journal_date)); ?></p>
    <p><strong>Keterangan:</strong> <?= htmlspecialchars($header->description); ?></p>
    <p><strong>Mata Uang:</strong> <?= htmlspecialchars($header->currency); ?> | <strong>Kurs:</strong> <?= number_format($header->exchange_rate, 4); ?></p>
    <p><strong>Status:</strong> 
        <span style="background-color: <?= ($header->status == 'Draft') ? '#ffc107' : '#28a745'; ?>; color: white; padding: 2px 6px; border-radius: 3px;">
            <?= htmlspecialchars($header->status); ?>
        </span>
    </p>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 5%;">No.</th>
            <th style="width: 12%;">Kode Akun</th>
            <th style="width: 30%;">Nama Akun</th>
            <th style="width: 15%;">Debit</th>
            <th style="width: 15%;">Kredit</th>
            <th style="width: 23%;">Keterangan</th>
        </tr>
    </thead>
    <tbody>
        <?php $no = 1; ?>
        <?php foreach ($details as $detail): ?>
        <tr>
            <td><?= $no++; ?></td>
            <td><?= htmlspecialchars($detail->account_code ?? '-'); ?></td>
            <td><?= htmlspecialchars($detail->account_name ?? '-'); ?></td>
            <td class="number"><?= number_format($detail->debit, 2, ',', '.'); ?></td>
            <td class="number"><?= number_format($detail->kredit, 2, ',', '.'); ?></td>
            <td><?= htmlspecialchars($detail->description ?? ''); ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td colspan="3" style="text-align: right;">TOTAL</td>
            <td class="number"><?= number_format($header->total_debit, 2, ',', '.'); ?></td>
            <td class="number"><?= number_format($header->total_kredit, 2, ',', '.'); ?></td>
            <td></td>
        </tr>
    </tbody>
</table>

<!-- Validation/Signature Box -->
<div class="validation-box">
    <div style="display: flex; justify-content: space-between;">
        <div style="flex: 1;">
            <p class="label">Dibuat Oleh:</p>
            <p><?= htmlspecialchars($creator->fullname ?? 'Unknown'); ?></p>
            <p style="font-size: 10px; color: #666;"><?= htmlspecialchars($creator->username ?? ''); ?></p>
            <p style="font-size: 10px; color: #666; margin-top: 8px;">
                <?= date('d/m/Y H:i:s', strtotime($header->created_at ?? now())); ?>
            </p>
        </div>
    </div>
</div>

<div class="footer" style="margin-top: 40px; page-break-inside: avoid;">
    <div class="signature-block">
        <p>Dibuat Oleh</p>
        <div class="name"><?= htmlspecialchars($creator->fullname ?? 'Unknown'); ?></div>
    </div>
</div>

</body>
</html>
