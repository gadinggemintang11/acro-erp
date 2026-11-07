<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title); ?></title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        .container { width: 100%; }
        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #333; padding-bottom: 8px; }
        .header h2 { margin: 0; font-size: 16px; letter-spacing: 1px; }
        .header p { margin: 2px 0; font-size: 11px; }
        .info-table { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .info-table td { padding: 3px 6px; vertical-align: top; border: none; }
        .info-table .label { font-weight: bold; width: 14%; }
        .info-table .colon { width: 1%; }
        .info-table .value { width: 35%; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 9px; color: #fff; }
        .badge.draft { background: #888; }
        .badge.counting { background: #f39c12; }
        .badge.completed { background: #27ae60; }
        .badge.cancelled { background: #c0392b; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.data th, table.data td { border: 1px solid #999; padding: 4px 6px; vertical-align: top; }
        table.data thead th { background: #e9ecef; text-align: center; font-weight: bold; }
        table.data tfoot td { background: #f5f5f5; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .pos { color: #27ae60; font-weight: bold; }
        .neg { color: #c0392b; font-weight: bold; }
        .muted { color: #888; font-style: italic; }
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-top: 15px;
        }
        .sig-table td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            font-size: 9px;
            width: 33.33%;
        }
        .sig-header {
            background-color: #f5f5f5;
            font-weight: bold;
            line-height: 1.3;
        }
        .sig-space {
            height: 55px;
            vertical-align: middle;
        }
        .sig-name {
            font-size: 9px;
            padding: 3px;
            min-height: 12px;
            font-weight: bold;
        }
        .sig-date {
            font-size: 9px;
            padding: 3px;
            color: #555;
        }
        .footer-note { font-size: 9px; color: #888; margin-top: 10px; text-align: right; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>LAPORAN STOCK OPNAME</h2>
        <p>No. Dokumen: <strong><?= htmlspecialchars($header->opname_no); ?></strong></p>
    </div>

    <?php
        $status_class = strtolower($header->status);
        $totals = ['sistem' => 0, 'fisik' => 0, 'selisih' => 0, 'counted' => 0, 'pending' => 0];
        foreach ($details as $d) {
            $totals['sistem'] += (float)$d->qty_sistem;
            if ($d->qty_fisik !== NULL) { $totals['fisik'] += (float)$d->qty_fisik; $totals['counted']++; }
            else { $totals['pending']++; }
            $totals['selisih'] += (float)$d->selisih;
        }
        $fmt = function($n) { return rtrim(rtrim(number_format((float)$n, 4, '.', ','), '0'), '.'); };
    ?>

    <table class="info-table">
        <tr>
            <td class="label">Tanggal Opname</td><td class="colon">:</td>
            <td class="value"><?= date('d M Y', strtotime($header->opname_date)); ?></td>
            <td class="label">Tipe Item</td><td class="colon">:</td>
            <td class="value"><?= htmlspecialchars(ucfirst($header->item_type)); ?></td>
        </tr>
        <tr>
            <td class="label">Status</td><td class="colon">:</td>
            <td class="value"><span class="badge <?= $status_class; ?>"><?= htmlspecialchars($header->status); ?></span></td>
            <td class="label">Dok. Adjustment</td><td class="colon">:</td>
            <td class="value"><?= !empty($header->adjustment_doc) ? htmlspecialchars($header->adjustment_doc) : '<span class="muted">Tidak ada selisih</span>'; ?></td>
        </tr>
        <tr>
            <td class="label">Dibuat Oleh</td><td class="colon">:</td>
            <td class="value"><?= htmlspecialchars($header->creator_name ?: '-'); ?></td>
            <td class="label">Dikonfirmasi</td><td class="colon">:</td>
            <td class="value">
                <?php if (!empty($header->confimer_name)): ?>
                    <?= htmlspecialchars($header->confimer_name); ?>
                    <small>(<?= date('d M Y H:i', strtotime($header->confirmed_at)); ?>)</small>
                <?php else: ?>
                    <span class="muted">-</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="label">Catatan</td><td class="colon">:</td>
            <td class="value" colspan="4"><?= nl2br(htmlspecialchars($header->notes ?: '-')); ?></td>
        </tr>
        <tr>
            <td class="label">Progress</td><td class="colon">:</td>
            <td class="value" colspan="4">
                <?= $totals['counted']; ?> dari <?= ($totals['counted'] + $totals['pending']); ?> item dihitung
                <?php if ($totals['pending'] > 0): ?>
                    <span class="muted">(<?= $totals['pending']; ?> belum dihitung)</span>
                <?php endif; ?>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="11%">Kode</th>
                <th>Nama Item</th>
                <th width="7%">Satuan</th>
                <th width="11%">Qty Sistem</th>
                <th width="11%">Qty Fisik</th>
                <th width="11%">Selisih</th>
                <th width="20%">Catatan</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($details)): ?>
            <tr><td colspan="8" class="text-center muted">Tidak ada item.</td></tr>
        <?php else: $no = 1; foreach ($details as $d):
            $cls = ($d->selisih > 0) ? 'pos' : (($d->selisih < 0) ? 'neg' : '');
        ?>
            <tr>
                <td class="text-center"><?= $no++; ?></td>
                <td><?= htmlspecialchars($d->item_code ?? ''); ?></td>
                <td><?= htmlspecialchars($d->item_name); ?></td>
                <td class="text-center"><?= htmlspecialchars($d->item_unit); ?></td>
                <td class="text-right"><?= $fmt($d->qty_sistem); ?></td>
                <td class="text-right">
                    <?= ($d->qty_fisik !== NULL) ? $fmt($d->qty_fisik) : '<span class="muted">(Belum diisi)</span>'; ?>
                </td>
                <td class="text-right <?= $cls; ?>"><?= $fmt($d->selisih); ?></td>
                <td><?= htmlspecialchars($d->notes ?? ''); ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <?php if (!empty($details)): ?>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">TOTAL</td>
                <td class="text-right"><?= $fmt($totals['sistem']); ?></td>
                <td class="text-right"><?= $fmt($totals['fisik']); ?></td>
                <td class="text-right"><?= $fmt($totals['selisih']); ?></td>
                <td></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <table class="sig-table">
        <tr>
            <td class="sig-header">Dibuat Oleh<br>[Created By]</td>
            <td class="sig-header">Diperiksa<br>[Checked By]</td>
            <td class="sig-header">Dikonfirmasi<br>[Approved By]</td>
        </tr>
        <tr>
            <td class="sig-space"></td>
            <td class="sig-space"></td>
            <td class="sig-space"></td>
        </tr>
        <tr>
            <td class="sig-name"><?= htmlspecialchars($header->creator_name ?: '-'); ?></td>
            <td class="sig-name">-</td>
            <td class="sig-name"><?= htmlspecialchars($header->confimer_name ?: '-'); ?></td>
        </tr>
        <tr>
            <td class="sig-date">Date: <?= !empty($header->opname_date) ? date('d/m/Y', strtotime($header->opname_date)) : '___/___/___'; ?></td>
            <td class="sig-date">Date: ___/___/___</td>
            <td class="sig-date">Date: <?= !empty($header->confirmed_at) ? date('d/m/Y', strtotime($header->confirmed_at)) : '___/___/___'; ?></td>
        </tr>
    </table>

    <div class="footer-note">
        Dicetak: <?= date('d M Y H:i'); ?>
    </div>
</div>
</body>
</html>
