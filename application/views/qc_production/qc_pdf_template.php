<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>QC Report - <?= htmlspecialchars($qc_detail->production_number); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        
        .container {
            width: 100%;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 10px;
            color: #666;
        }
        
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background-color: #3949ab;
            color: white;
            padding: 8px 10px;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 12px;
        }
        
        .info-grid {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            padding: 5px;
            border-bottom: 1px solid #eee;
            background-color: #f5f5f5;
        }
        
        .info-value {
            display: table-cell;
            width: 70%;
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        
        table th {
            background-color: #3949ab;
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        
        table td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 10px;
        }
        
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .badge-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .photo-section {
            text-align: center;
            margin: 20px 0;
            page-break-inside: avoid;
        }
        
        .photo-section img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            padding: 5px;
        }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>LAPORAN QUALITY CONTROL (QC) PRODUCTION</h1>
            <p>Nomor Produksi: <?= htmlspecialchars($qc_detail->production_number); ?></p>
            <p>Tanggal QC: <?= !empty($qc_detail->qc_checked_at) ? date('d/m/Y H:i:s', strtotime($qc_detail->qc_checked_at)) : 'N/A'; ?></p>
        </div>

        <!-- Production Information -->
        <div class="section">
            <div class="section-title">INFORMASI PRODUKSI</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Nomor Produksi</span>
                    <span class="info-value"><?= htmlspecialchars($qc_detail->production_number); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">WO Number</span>
                    <span class="info-value"><?= htmlspecialchars($qc_detail->wo_number ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Produk Akhir</span>
                    <span class="info-value"><?= htmlspecialchars($qc_detail->fg_name ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal Produksi</span>
                    <span class="info-value"><?= !empty($qc_detail->production_date) ? date('d/m/Y', strtotime($qc_detail->production_date)) : 'N/A'; ?></span>
                </div>
            </div>
        </div>

        <!-- QC Stage Information -->
        <div class="section">
            <div class="section-title">INFORMASI QC</div>
            <div class="info-grid">
                <div class="info-row">
                    <span class="info-label">Stage</span>
                    <span class="info-value"><span class="badge badge-info"><?= ucfirst($qc_detail->stage_name); ?></span></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Diperiksa Oleh</span>
                    <span class="info-value"><?= htmlspecialchars($qc_detail->checked_by_name ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= htmlspecialchars($qc_detail->checked_by_email ?? 'N/A'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tanggal & Jam QC</span>
                    <span class="info-value"><?= !empty($qc_detail->qc_checked_at) ? date('d/m/Y H:i:s', strtotime($qc_detail->qc_checked_at)) : 'N/A'; ?></span>
                </div>
            </div>
        </div>

        <!-- Item QC Information -->
        <div class="section">
            <div class="section-title">ITEM YANG DIPERIKSA</div>
            <table>
                <tr>
                    <th>Detail</th>
                    <th>Nilai</th>
                </tr>
                <tr>
                    <td>Nama Item</td>
                    <td><?= htmlspecialchars($qc_detail->item_name); ?></td>
                </tr>
                <tr>
                    <td>Kode Item</td>
                    <td><?= htmlspecialchars($qc_detail->item_code ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td>Tipe Item</td>
                    <td><span class="badge badge-secondary"><?= ucfirst($qc_detail->item_type); ?></span></td>
                </tr>
                <tr>
                    <td>Qty yang Diperiksa</td>
                    <td><span class="badge badge-success"><?= number_format($qc_detail->qty_qc, 2); ?></span> <?= htmlspecialchars($qc_detail->satuan ?? ''); ?></td>
                </tr>
            </table>
        </div>

        <!-- Size Breakdown (jika ada) -->
        <?php if (!empty($size_breakdown)): ?>
        <div class="section">
            <div class="section-title">BREAKDOWN SIZE</div>
            <table>
                <tr>
                    <th>Size</th>
                    <th class="text-right">Qty</th>
                </tr>
                <?php foreach ($size_breakdown as $size): ?>
                <tr>
                    <td><?= htmlspecialchars($size['size']); ?></td>
                    <td class="text-right"><?= number_format($size['qty'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <!-- QC Description -->
        <div class="section">
            <div class="section-title">DESKRIPSI QC</div>
            <div style="padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd;">
                <?= nl2br(htmlspecialchars($qc_detail->qc_description ?? 'Tidak ada deskripsi')); ?>
            </div>
        </div>

        <!-- Input Materials -->
        <?php if (!empty($stage_inputs)): ?>
        <div class="section">
            <div class="section-title">MATERIAL INPUT (Stage <?= ucfirst($qc_detail->stage_name); ?>)</div>
            <table>
                <tr>
                    <th>Item Name</th>
                    <th>Kode</th>
                    <th class="text-right">Qty Used</th>
                </tr>
                <?php foreach ($stage_inputs as $input): ?>
                <tr>
                    <td><?= htmlspecialchars($input->item_name); ?></td>
                    <td><?= htmlspecialchars($input->item_code); ?></td>
                    <td class="text-right"><?= number_format($input->qty_used, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <!-- QC Photo Link -->
        <?php if (!empty($qc_detail->qc_photo_url)): ?>
        <div class="section">
            <div class="section-title">LINK FOTO QC</div>
            <div style="padding: 10px; background-color: #f9f9f9; border: 1px solid #ddd;">
                <strong>URL:</strong><br>
                <?= htmlspecialchars($qc_detail->qc_photo_url); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>Generated on <?= date('d/m/Y H:i:s'); ?></p>
            <p>Laporan ini adalah dokumen resmi dari sistem produksi.</p>
        </div>
    </div>
</body>
</html>
