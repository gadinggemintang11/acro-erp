<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Kartu Stok - <?= htmlspecialchars($fg_info->kode_barang) ?></title>
<style>
    @page {
        margin: 15mm;
    }
    body {
        font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
        font-size: 10pt;
        color: #1e293b;
        line-height: 1.6;
    }
    .header-section {
        text-align: center;
        border-bottom: 3px solid #1e293b;
        padding-bottom: 20px;
        margin-bottom: 25px;
    }
    .header-section h1 {
        margin: 0 0 10px 0;
        font-size: 20pt;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .product-info {
        background: white;
        border: 1px solid #cbd5e1;
        padding: 15px 20px;
        margin-bottom: 20px;
        display: table;
        width: 100%;
    }
    .info-row {
        display: table-row;
    }
    .info-label {
        display: table-cell;
        font-weight: 700;
        color: #1e293b;
        font-size: 9pt;
        padding: 6px 15px 6px 0;
        width: 120px;
    }
    .info-value {
        display: table-cell;
        color: #1e293b;
        font-size: 10pt;
        padding: 6px 0;
        font-weight: 500;
    }
    .code-text {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: #1e293b;
    }
    .opening-balance {
        background: white;
        border: 1px solid #1e293b;
        border-left: 3px solid #1e293b;
        padding: 12px 18px;
        margin-bottom: 20px;
        font-size: 10pt;
    }
    .opening-balance .label {
        font-weight: 700;
        color: #1e293b;
        margin-right: 10px;
    }
    .opening-balance .value {
        font-weight: 600;
        font-variant-numeric: tabular-nums;
        color: #1e293b;
    }
    .section-title {
        font-size: 11pt;
        font-weight: 700;
        color: #1e293b;
        margin: 25px 0 12px 0;
        padding: 10px 0;
        border-bottom: 2px solid #1e293b;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    table.ledger {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #1e293b;
        margin-top: 10px;
    }
    table.ledger thead {
        background: white;
        border-bottom: 2px solid #1e293b;
    }
    table.ledger th {
        padding: 10px 8px;
        text-align: center;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 8.5pt;
        letter-spacing: 0.5px;
        border: 1px solid #1e293b;
        color: #1e293b;
    }
    table.ledger td {
        border: 1px solid #cbd5e1;
        padding: 8px;
        font-size: 9pt;
        color: #1e293b;
        background: white;
    }
    table.ledger tbody tr:nth-child(even) {
        background-color: #f8fafc;
    }
    table.ledger tbody tr:hover {
        background-color: #f1f5f9;
    }
    .text-right {
        text-align: right;
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }
    .text-center {
        text-align: center;
    }
    .qty-in {
        color: #059669;
        font-weight: 600;
    }
    .qty-out {
        color: #dc2626;
        font-weight: 600;
    }
    .balance-cell {
        font-weight: 700;
        color: #1e293b;
    }
    .doc-ref {
        font-family: 'Courier New', monospace;
        font-size: 8.5pt;
    }
    .empty-row td {
        text-align: center;
        color: #64748b;
        font-style: italic;
        padding: 20px;
    }
    .signature-section {
        margin-top: 40px;
        page-break-inside: avoid;
    }
    .sig-box {
        float: right;
        width: 250px;
        border: 1px solid #1e293b;
        padding: 15px;
        background: white;
        text-align: center;
    }
    .sig-label {
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 50px;
        font-size: 9pt;
        text-transform: uppercase;
    }
    .sig-line {
        border-top: 2px solid #1e293b;
        margin: 0 10px;
        padding-top: 8px;
    }
    .sig-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 9pt;
    }
    .footer-note {
        clear: both;
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
    <div class="header-section">
        <h1>Kartu Stok Barang Jadi</h1>
    </div>

    <div class="product-info">
        <div class="info-row">
            <div class="info-label">Kode Barang</div>
            <div class="info-value">: <span class="code-text"><?= htmlspecialchars($fg_info->kode_barang) ?></span></div>
        </div>
        <div class="info-row">
            <div class="info-label">Nama Barang</div>
            <div class="info-value">: <strong><?= htmlspecialchars($fg_info->nama_barang) ?></strong></div>
        </div>
        <div class="info-row">
            <div class="info-label">Satuan</div>
            <div class="info-value">: <?= htmlspecialchars($fg_info->satuan) ?></div>
        </div>
        <div class="info-row">
            <div class="info-label">Periode</div>
            <div class="info-value">: <?= htmlspecialchars($filters['start_date']) ?> s/d <?= htmlspecialchars($filters['end_date']) ?></div>
        </div>
    </div>

    <div class="opening-balance">
        <span class="label">Saldo Awal:</span>
        <span class="value"><?= number_format((float)$opening_balance, 2) ?></span>
    </div>

    <div class="section-title">Riwayat Transaksi</div>

    <table class="ledger">
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="80">Tanggal</th>
                <th width="100">Tipe</th>
                <th width="110">Dokumen</th>
                <th width="75">Qty In</th>
                <th width="75">Qty Out</th>
                <th width="75">Balance</th>
                <th width="80">BC Type</th>
                <th width="100">BC Number</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($ledger_data)): $no=1; foreach($ledger_data as $r): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= date('d-m-Y', strtotime($r->transaction_date)) ?></td>
                    <td class="text-center"><?= htmlspecialchars($r->transaction_type) ?></td>
                    <td class="text-center doc-ref"><?= (!empty($r->source_doc_type) && !empty($r->source_doc_id)) ? htmlspecialchars($r->source_doc_type . '/#' . $r->source_doc_id) : '-' ?></td>
                    <td class="text-right qty-in"><?= (float)$r->qty_in > 0 ? number_format($r->qty_in, 2) : '' ?></td>
                    <td class="text-right qty-out"><?= (float)$r->qty_out > 0 ? number_format($r->qty_out, 2) : '' ?></td>
                    <td class="text-right balance-cell"><?= number_format($r->balance, 2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($r->bc_type_code ?? $r->bc_type_desc ?? '-') ?></td>
                    <td class="text-center"><?= htmlspecialchars($r->bc_number ?? '-') ?></td>
                    <td><?= htmlspecialchars($r->remarks ?? '-') ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr class="empty-row">
                    <td colspan="10">Tidak ada transaksi untuk periode ini.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="signature-section">
        <div class="sig-box">
            <div class="sig-label">Dibuat Oleh</div>
            <div class="sig-line">
                <div class="sig-name">___________________</div>
            </div>
        </div>
    </div>

    <div class="footer-note">
        Dokumen dicetak pada: <?= date('d F Y H:i:s') ?>
    </div>
</body>
</html>
