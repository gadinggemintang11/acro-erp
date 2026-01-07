<h6><?= htmlspecialchars($header->wo_number); ?></h6>
<p class="mb-2">
    <strong>Style:</strong> <?= htmlspecialchars($header->fg_name); ?><br>
    <strong>SC:</strong> <?= htmlspecialchars($header->sc_number); ?><br>
    <strong>Qty Plan:</strong> <?= number_format($header->planned_qty); ?>
</p>

<strong>Referensi BOM:</strong>
<div class="table-responsive mb-3">
    <table class="table table-sm table-bordered" style="font-size: 0.8em;">
        <thead class="thead-light"><tr><th>Item</th><th>Qty/Unit</th></tr></thead>
        <tbody>
            <?php if(!empty($bom)): foreach($bom as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b->item_name); ?></td>
                <td class="text-right"><?= number_format($b->quantity_required, 4); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="2" class="text-center text-muted">BOM tidak aktif.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<strong>Rencana Material WO:</strong>
<div class="table-responsive">
     <table class="table table-sm table-bordered" style="font-size: 0.8em;">
        <thead class="thead-light"><tr><th>Item</th><th>Total Qty</th></tr></thead>
        <tbody>
            <?php if(!empty($plan)): foreach($plan as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p->item_name); ?></td>
                <td class="text-right"><?= number_format($p->planned_qty, 4); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="2" class="text-center text-muted">Belum ada rencana material.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>