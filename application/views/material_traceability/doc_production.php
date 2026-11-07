<?php
$fmt_qty = function($v){ return number_format((float)$v, 2, ',', '.'); };
$fmt_int = function($v){ return number_format((float)$v, 0, ',', '.'); };
$h = $doc['header']; $inputs = $doc['inputs']; $outputs = $doc['outputs']; $fg_receipts = $doc['fg_receipts'];

$inputs_by_stage = [];
foreach ($inputs as $it) { $inputs_by_stage[$it->stage_name][] = $it; }
$outputs_by_stage = [];
foreach ($outputs as $it) { $outputs_by_stage[$it->stage_name][] = $it; }
$stages = array_unique(array_merge(array_keys($inputs_by_stage), array_keys($outputs_by_stage)));
?>
<div class="container-fluid">
    <?php $this->load->view('material_traceability/_breadcrumb', ['doc'=>$doc,'doc_type'=>$doc_type,'material'=>$material]); ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-dark text-white py-2">
            <i class="fas fa-industry mr-1"></i> <strong><?= lang_line('mtd_doc_prod'); ?> &mdash; <?= htmlspecialchars($h->production_number); ?></strong>
        </div>
        <div class="card-body small">
            <div class="row">
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_prod_date'); ?></div><strong><?= htmlspecialchars($h->production_date); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_status'); ?></div><strong><?= htmlspecialchars($h->status); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_wo'); ?></div><strong><?= htmlspecialchars($h->wo_number ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_planned_qty'); ?></div><strong><?= $fmt_int($h->planned_qty); ?></strong></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><div class="text-muted"><?= lang_line('mtd_label_target_fg'); ?></div><strong><?= htmlspecialchars($h->fg_code ?? '-'); ?></strong> &mdash; <?= htmlspecialchars($h->fg_name ?? ''); ?></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_created_by'); ?></div><strong><?= htmlspecialchars($h->created_by_name ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_confirmed_by'); ?></div><strong><?= htmlspecialchars($h->confirmed_by_name ?? '-'); ?></strong></div>
            </div>
            <?php if (!empty($h->remarks)): ?>
                <hr><div class="text-muted"><?= lang_line('mtd_label_remarks'); ?></div><div><?= nl2br(htmlspecialchars($h->remarks)); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Per stage breakdown -->
    <?php foreach ($stages as $stage): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-info text-white py-2">
            <i class="fas fa-cogs mr-1"></i> <strong><?= lang_line('mtd_stage_prefix'); ?> <?= htmlspecialchars(ucfirst($stage)); ?></strong>
        </div>
        <div class="card-body p-0">
            <div class="row no-gutters">
                <div class="col-md-6 border-right">
                    <div class="px-3 py-2 bg-light"><strong><i class="fas fa-arrow-right text-primary mr-1"></i> <?= lang_line('mtd_section_input'); ?></strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th><?= lang_line('mtd_th_type'); ?></th><th><?= lang_line('mtd_th_code'); ?></th><th><?= lang_line('mtd_th_name'); ?></th><th><?= lang_line('mtd_th_qty'); ?></th><th><?= lang_line('mtd_th_unit'); ?></th><th><?= lang_line('mtd_th_batch_grn'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php foreach (($inputs_by_stage[$stage] ?? []) as $in):
                                $isHighlight = (!empty($material) && $in->item_type==='material' && (int)$in->item_id===(int)$material->id);
                            ?>
                                <tr <?= $isHighlight ? 'class="table-warning"' : ''; ?>>
                                    <td class="text-center"><span class="badge badge-secondary"><?= htmlspecialchars($in->item_type); ?></span></td>
                                    <td><code><?= htmlspecialchars($in->item_code ?? '-'); ?></code></td>
                                    <td><?= htmlspecialchars($in->item_name ?? '-'); ?> <?= $isHighlight ? '<i class="fas fa-star text-warning ml-1"></i>' : ''; ?></td>
                                    <td class="text-right"><strong><?= $fmt_qty($in->qty_used); ?></strong></td>
                                    <td class="text-center"><?= htmlspecialchars($in->satuan ?? '-'); ?></td>
                                    <td class="text-center">
                                        <?php if ($in->grn_header_id): ?>
                                            <a href="<?= site_url('material_traceability/doc/grn/'.(int)$in->grn_header_id.'/'.($material->id ?? 0)); ?>" class="small"><?= htmlspecialchars($in->grn_number); ?> &middot; #<?= (int)$in->batch_id; ?></a>
                                        <?php elseif ($in->source_output_id): ?>
                                            <span class="badge badge-info"><?= lang_line('mtd_from_prev_stage'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($inputs_by_stage[$stage])): ?>
                                <tr><td colspan="6" class="text-center text-muted py-2"><?= lang_line('mtd_no_input'); ?></td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="px-3 py-2 bg-light"><strong><i class="fas fa-arrow-left text-success mr-1"></i> <?= lang_line('mtd_section_output'); ?></strong></div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0 align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th><?= lang_line('mtd_th_type'); ?></th><th><?= lang_line('mtd_th_code'); ?></th><th><?= lang_line('mtd_th_name'); ?></th><th><?= lang_line('mtd_th_qty_produced'); ?></th><th><?= lang_line('mtd_th_qty_scrap'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php foreach (($outputs_by_stage[$stage] ?? []) as $out):
                                $cls = $out->item_type === 'finished_goods' ? 'table-success' : ($out->item_type === 'scrap' ? 'table-danger' : '');
                            ?>
                                <tr class="<?= $cls; ?>">
                                    <td class="text-center"><span class="badge badge-secondary"><?= htmlspecialchars($out->item_type); ?></span></td>
                                    <td><code><?= htmlspecialchars($out->item_code ?? '-'); ?></code></td>
                                    <td><?= htmlspecialchars($out->item_name ?? '-'); ?></td>
                                    <td class="text-right"><strong><?= $fmt_qty($out->qty_produced); ?></strong></td>
                                    <td class="text-right text-danger"><?= $out->qty_scrap > 0 ? $fmt_qty($out->qty_scrap) : '-'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($outputs_by_stage[$stage])): ?>
                                <tr><td colspan="5" class="text-center text-muted py-2"><?= lang_line('mtd_no_output'); ?></td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-success text-white"><i class="fas fa-warehouse mr-1"></i> <strong><?= lang_line('mtd_card_fg_receipts'); ?></strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="thead-light text-center small"><tr>
                        <th><?= lang_line('mtd_th_no'); ?></th><th><?= lang_line('mtd_th_receipt_number'); ?></th><th><?= lang_line('mtd_th_date'); ?></th><th><?= lang_line('mtd_th_fg'); ?></th><th><?= lang_line('mtd_th_qty_received'); ?></th><th><?= lang_line('mtd_label_status'); ?></th>
                    </tr></thead>
                    <tbody class="small">
                    <?php if (empty($fg_receipts)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3"><?= lang_line('mtd_no_fg_yet'); ?></td></tr>
                    <?php else: foreach ($fg_receipts as $i => $f): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $i+1; ?></td>
                            <td><code><?= htmlspecialchars($f->receipt_number); ?></code></td>
                            <td class="text-center"><?= htmlspecialchars($f->receipt_date); ?></td>
                            <td><strong><?= htmlspecialchars($f->fg_code ?? '-'); ?></strong> &mdash; <?= htmlspecialchars($f->fg_name ?? ''); ?></td>
                            <td class="text-right"><strong><?= $fmt_qty($f->qty_received); ?></strong></td>
                            <td class="text-center"><?= htmlspecialchars($f->status); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
