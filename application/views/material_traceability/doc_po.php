<?php
$fmt_qty = function($v){ return number_format((float)$v, 2, ',', '.'); };
$fmt_money = function($v){ return 'Rp ' . number_format((float)$v, 0, ',', '.'); };
$h = $doc['header']; $items = $doc['items']; $grns = $doc['grn_receipts'];
?>
<div class="container-fluid">
    <?php $this->load->view('material_traceability/_breadcrumb', ['doc'=>$doc,'doc_type'=>$doc_type,'material'=>$material]); ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-success text-white py-2">
            <i class="fas fa-shopping-cart mr-1"></i> <strong><?= lang_line('mtd_doc_po'); ?> &mdash; <?= htmlspecialchars($h->po_number); ?></strong>
        </div>
        <div class="card-body small">
            <div class="row">
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_po_date'); ?></div><strong><?= htmlspecialchars($h->po_date); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_status'); ?></div><strong><?= htmlspecialchars($h->status); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_currency'); ?></div><strong><?= htmlspecialchars($h->currency_code); ?> (<?= lang_line('mtd_label_rate'); ?>: <?= htmlspecialchars($h->exchange_rate); ?>)</strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_ppn'); ?></div><strong><?= $h->has_ppn ? lang_line('mtd_yes') : lang_line('mtd_no'); ?></strong></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><div class="text-muted"><?= lang_line('mtd_label_supplier'); ?></div><strong><?= htmlspecialchars($h->supplier_name ?? '-'); ?></strong><br><small><?= htmlspecialchars($h->supplier_address ?? ''); ?></small></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_shipping_mode'); ?></div><strong><?= htmlspecialchars($h->shipping_mode_code ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_payment_mode'); ?></div><strong><?= htmlspecialchars($h->payment_mode_code ?? '-'); ?></strong></div>
            </div>
            <?php if (!empty($h->remarks)): ?>
                <hr><div class="text-muted"><?= lang_line('mtd_label_remarks'); ?></div><div><?= nl2br(htmlspecialchars($h->remarks)); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white"><strong><?= lang_line('mtd_card_items_po'); ?></strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="thead-light text-center small"><tr>
                        <th><?= lang_line('mtd_th_no'); ?></th><th><?= lang_line('mtd_th_type'); ?></th><th><?= lang_line('mtd_th_code'); ?></th><th><?= lang_line('mtd_th_name'); ?></th><th><?= lang_line('mtd_th_qty'); ?></th><th><?= lang_line('mtd_th_unit'); ?></th><th><?= lang_line('mtd_th_unit_price'); ?></th><th><?= lang_line('mtd_th_total_idr'); ?></th>
                    </tr></thead>
                    <tbody class="small">
                    <?php foreach ($items as $i => $it):
                        $isHighlight = (!empty($material) && $it->item_type==='material' && (int)$it->item_id===(int)$material->id);
                    ?>
                        <tr <?= $isHighlight ? 'class="table-warning"' : ''; ?>>
                            <td class="text-center text-muted"><?= $i+1; ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= htmlspecialchars($it->item_type); ?></span></td>
                            <td><code><?= htmlspecialchars($it->item_code ?? '-'); ?></code></td>
                            <td><?= htmlspecialchars($it->item_name ?? '-'); ?> <?= $isHighlight ? '<i class="fas fa-star text-warning ml-1"></i>' : ''; ?></td>
                            <td class="text-right"><?= $fmt_qty($it->qty); ?></td>
                            <td class="text-center"><?= htmlspecialchars($it->satuan ?? '-'); ?></td>
                            <td class="text-right"><?= $fmt_money($it->local_price); ?></td>
                            <td class="text-right"><strong><?= $fmt_money($it->total_local); ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong><?= lang_line('mtd_card_grn_from_po'); ?></strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="thead-light text-center small"><tr>
                        <th><?= lang_line('mtd_th_no'); ?></th><th><?= lang_line('mtd_th_grn_number'); ?></th><th><?= lang_line('mtd_th_date'); ?></th><th><?= lang_line('mtd_label_status'); ?></th><th><?= lang_line('mtd_th_action'); ?></th>
                    </tr></thead>
                    <tbody class="small">
                    <?php if (empty($grns)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3"><?= lang_line('mtd_no_grn_yet'); ?></td></tr>
                    <?php else: foreach ($grns as $i => $g): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $i+1; ?></td>
                            <td><code><?= htmlspecialchars($g->grn_number); ?></code></td>
                            <td class="text-center"><?= htmlspecialchars($g->grn_date); ?></td>
                            <td class="text-center"><?= htmlspecialchars($g->status); ?></td>
                            <td class="text-center">
                                <a class="btn btn-xs btn-outline-primary" href="<?= site_url('material_traceability/doc/grn/'.$g->id.'/'.($material->id ?? 0)); ?>"><i class="fas fa-eye"></i> <?= lang_line('mtd_btn_trace_grn'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
