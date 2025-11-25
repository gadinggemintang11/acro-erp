<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Kartu Stok - <?= htmlspecialchars($fg_info->kode_barang) ?></title>
<style>
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#222; }
    .hdr { width:100%; margin-bottom:12px; }
    .hdr .left{ float:left; width:60% }
    .hdr .right{ float:right; width:38%; text-align:right; }
    .clear{ clear:both; }
    table { width:100%; border-collapse: collapse; margin-top:8px; }
    th, td { border:1px solid #ccc; padding:6px; vertical-align:top; }
    th { background:#f3f6ff; font-weight:700; }
    .text-right{ text-align:right; }
    .text-center{ text-align:center; }
    .small { font-size:10px; color:#666; }
</style>
</head>
<body>
    <div class="hdr">
        <div class="left">
            <h3 style="margin:0;padding:0;">KARTU STOK - <?= htmlspecialchars($fg_info->nama_barang) ?></h3>
            <div class="small"><?= htmlspecialchars($fg_info->kode_barang) ?> &nbsp; | &nbsp; Satuan: <?= htmlspecialchars($fg_info->satuan) ?></div>
        </div>
        <div class="right">
            <div><strong>Periode:</strong></div>
            <div class="small"><?= htmlspecialchars($filters['start_date']) ?> s/d <?= htmlspecialchars($filters['end_date']) ?></div>
        </div>
        <div class="clear"></div>
    </div>

    <table>
        <tr>
            <td style="border:none;padding:0 6px 6px 6px;" colspan="10">
                <strong>Saldo Awal:</strong> <?= number_format((float)$opening_balance,2) ?>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="95">Tanggal</th>
                <th width="120">Tipe Transaksi</th>
                <th>Dokumen Sumber</th>
                <th width="90" class="text-right">Qty In</th>
                <th width="90" class="text-right">Qty Out</th>
                <th width="90" class="text-right">Balance</th>
                <th width="110">BC Type</th>
                <th width="120">BC Number</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($ledger_data)): $no=1; foreach($ledger_data as $r): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td class="text-center"><?= date('Y-m-d', strtotime($r->transaction_date)) ?></td>
                    <td class="text-center"><?= htmlspecialchars($r->transaction_type) ?></td>
                    <td class="text-center"><?= (!empty($r->source_doc_type) && !empty($r->source_doc_id)) ? htmlspecialchars($r->source_doc_type . '/#' . $r->source_doc_id) : '-' ?></td>
                    <td class="text-right"><?= (float)$r->qty_in>0 ? number_format($r->qty_in,2) : '' ?></td>
                    <td class="text-right"><?= (float)$r->qty_out>0 ? number_format($r->qty_out,2) : '' ?></td>
                    <td class="text-right"><?= number_format($r->balance,2) ?></td>
                    <td class="text-center"><?= htmlspecialchars($r->bc_type_code ?? $r->bc_type_desc ?? '-') ?></td>
                    <td class="text-center"><?= htmlspecialchars($r->bc_number ?? '-') ?></td>
                    <td><?= htmlspecialchars($r->remarks ?? '') ?></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="10" class="text-center small">Tidak ada transaksi untuk periode ini.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top:18px; width:100%; text-align:right;">
        <div style="font-size:11px;">Dibuat oleh: ____________________</div>
    </div>
</body>
</html>
