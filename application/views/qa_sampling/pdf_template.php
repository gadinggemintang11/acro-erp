<!DOCTYPE html>
<html><head><meta charset="utf-8">
<style>
    body { font-family: Arial, sans-serif; font-size: 11px; }
    h2 { margin: 0; font-size: 14px; text-align: center; }
    .small { font-size: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #333; padding: 4px 6px; vertical-align: top; }
    th { background: #eee; }
    .info td { border: none; padding: 2px 4px; }
    .right { text-align: right; }
    .center { text-align: center; }
</style></head>
<body>
    <h2>DOKUMEN QA SAMPLING</h2>
    <div class="center small">No: <strong><?= htmlspecialchars($header->doc_no); ?></strong> &middot; Status: <?= $header->status; ?></div>

    <table class="info" style="margin-top:8px;">
        <tr>
            <td width="15%"><strong>Tanggal</strong></td><td width="35%">: <?= date('d-m-Y', strtotime($header->doc_date)); ?></td>
            <td width="15%"><strong>Requester</strong></td><td>: <?= htmlspecialchars($header->requester_name ?? '-'); ?></td>
        </tr>
        <tr>
            <td><strong>Tujuan</strong></td><td>: <?= htmlspecialchars($header->purpose); ?></td>
            <td><strong>Departemen</strong></td><td>: <?= htmlspecialchars($header->requester_dept ?? '-'); ?></td>
        </tr>
        <tr>
            <td colspan="4"><strong>Catatan</strong>: <?= htmlspecialchars($header->notes ?? '-'); ?></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th width="3%">#</th>
                <th width="9%">Sumber</th>
                <th width="11%">Tipe</th>
                <th width="13%">Kode</th>
                <th>Nama Item</th>
                <th width="10%">Qty</th>
                <th width="7%">UoM</th>
                <th width="12%">Hasil Test</th>
            </tr>
        </thead>
        <tbody>
        <?php $no=1; foreach ($details as $d): ?>
            <tr>
                <td class="center"><?= $no++; ?></td>
                <td><?= strtoupper($d->source); ?></td>
                <td><?= htmlspecialchars($item_types[$d->item_type] ?? $d->item_type); ?></td>
                <td><?= htmlspecialchars($d->item_code ?? '-'); ?></td>
                <td><?= htmlspecialchars($d->item_name ?? '-'); ?>
                    <?php if ($d->source==='wip' && $d->production_number): ?>
                        <br><span class="small">Prod: <?= htmlspecialchars($d->production_number); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($d->notes)): ?>
                        <br><span class="small">Catatan: <?= htmlspecialchars($d->notes); ?></span>
                    <?php endif; ?>
                </td>
                <td class="right"><?= number_format($d->qty_sample, 4, ',', '.'); ?></td>
                <td><?= htmlspecialchars($d->uom ?? '-'); ?></td>
                <td>
                    <strong><?= $d->test_status; ?></strong>
                    <?php if (!empty($d->test_result_notes)): ?>
                        <br><span class="small"><?= nl2br(htmlspecialchars($d->test_result_notes)); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <table style="margin-top: 40px;">
        <tr>
            <td class="center" width="33%">Requester<br><br><br>(<?= htmlspecialchars($header->requester_name ?? '____________'); ?>)</td>
            <td class="center" width="34%">Approved By<br><br><br>(<?= htmlspecialchars($header->approver_name ?? '____________'); ?>)</td>
            <td class="center" width="33%">Posted By<br><br><br>(____________)</td>
        </tr>
    </table>
</body></html>
