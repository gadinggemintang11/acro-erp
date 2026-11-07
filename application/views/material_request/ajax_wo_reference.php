<h6><?= htmlspecialchars($header->wo_number); ?></h6>
<p class="mb-2">
    <strong><?= lang_line('mr_ajwo_style'); ?></strong> <?= htmlspecialchars($header->fg_name); ?><br>
    <strong><?= lang_line('mr_ajwo_sc'); ?></strong> <?= htmlspecialchars($header->sc_number); ?><br>
    <strong><?= lang_line('mr_ajwo_qty_plan'); ?></strong> <?= number_format($header->planned_qty); ?>
</p>

<strong><?= lang_line('mr_ajwo_bom_ref'); ?></strong>
<div class="table-responsive mb-3">
    <table class="table table-sm table-bordered" style="font-size: 0.8em;">
        <thead class="thead-light"><tr><th><?= lang_line('mr_ajwo_th_item'); ?></th><th><?= lang_line('mr_ajwo_th_qty_unit'); ?></th></tr></thead>
        <tbody>
            <?php if(!empty($bom)): foreach($bom as $b): ?>
            <tr>
                <td><?= htmlspecialchars($b->item_name); ?></td>
                <td class="text-right"><?= number_format($b->quantity_required, 4); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="2" class="text-center text-muted"><?= lang_line('mr_ajwo_bom_inactive'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<strong><?= lang_line('mr_ajwo_wo_plan'); ?></strong>
<div class="table-responsive">
     <table class="table table-sm table-bordered" style="font-size: 0.8em;">
        <thead class="thead-light"><tr><th><?= lang_line('mr_ajwo_th_item'); ?></th><th><?= lang_line('mr_ajwo_th_total_qty'); ?></th></tr></thead>
        <tbody>
            <?php if(!empty($plan)): foreach($plan as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p->item_name); ?></td>
                <td class="text-right"><?= number_format($p->planned_qty, 4); ?></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="2" class="text-center text-muted"><?= lang_line('mr_ajwo_no_plan'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
