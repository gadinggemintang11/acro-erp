<?php
$fmt_qty = function($v){ return number_format((float)$v, 2, ',', '.'); };
$h = $doc['header']; $items = $doc['items'];
?>
<div class="container-fluid">
    <?php $this->load->view('material_traceability/_breadcrumb', ['doc'=>$doc,'doc_type'=>$doc_type,'material'=>$material]); ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-danger text-white py-2">
            <i class="fas fa-dolly mr-1"></i> <strong><?= lang_line('mtd_irf_issuance'); ?> &mdash; <?= htmlspecialchars($h->mr_number); ?></strong>
            <?php if (!empty($h->issuance_number)): ?>
                <span class="badge badge-light ml-2"><?= lang_line('mtd_label_issuance'); ?> <?= htmlspecialchars($h->issuance_number); ?></span>
            <?php endif; ?>
        </div>
        <div class="card-body small">
            <div class="row">
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_request_date'); ?></div><strong><?= htmlspecialchars($h->request_date); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_status'); ?></div><strong><?= htmlspecialchars($h->status); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_requester_ppic'); ?></div><strong><?= htmlspecialchars($h->requester_name ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_requested_at'); ?></div><strong><?= htmlspecialchars($h->requested_at ?? '-'); ?></strong></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_issuer'); ?></div><strong><?= htmlspecialchars($h->issuer_name ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_issued_at'); ?></div><strong><?= htmlspecialchars($h->issued_at ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_receiver'); ?></div><strong><?= htmlspecialchars($h->receiver_name ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_received_at'); ?></div><strong><?= htmlspecialchars($h->received_at ?? '-'); ?></strong></div>
            </div>
            <?php if (!empty($h->remarks)): ?>
                <hr><div class="text-muted"><?= lang_line('mtd_label_remarks'); ?></div><div><?= nl2br(htmlspecialchars($h->remarks)); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong><?= lang_line('mtd_card_items_requested'); ?></strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="thead-light text-center small"><tr>
                        <th><?= lang_line('mtd_th_no'); ?></th><th><?= lang_line('mtd_th_type'); ?></th><th><?= lang_line('mtd_th_code'); ?></th><th><?= lang_line('mtd_th_name'); ?></th><th><?= lang_line('mtd_th_qty_requested'); ?></th><th><?= lang_line('mtd_th_qty_issued'); ?></th><th><?= lang_line('mtd_th_qty_returned'); ?></th><th><?= lang_line('mtd_th_unit'); ?></th><th><?= lang_line('mtd_th_wo'); ?></th><th><?= lang_line('mtd_th_target_fg'); ?></th>
                    </tr></thead>
                    <tbody class="small">
                    <?php foreach ($items as $i => $it):
                        $isHighlight = (!empty($material) && $it->component_item_type==='material' && (int)$it->component_item_id===(int)$material->id);
                    ?>
                        <tr <?= $isHighlight ? 'class="table-warning"' : ''; ?>>
                            <td class="text-center text-muted"><?= $i+1; ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= htmlspecialchars($it->component_item_type); ?></span></td>
                            <td><code><?= htmlspecialchars($it->item_code ?? '-'); ?></code></td>
                            <td><?= htmlspecialchars($it->item_name ?? '-'); ?> <?= $isHighlight ? '<i class="fas fa-star text-warning ml-1"></i>' : ''; ?></td>
                            <td class="text-right"><?= $fmt_qty($it->requested_qty); ?></td>
                            <td class="text-right"><strong><?= $fmt_qty($it->issued_qty); ?></strong></td>
                            <td class="text-right text-warning"><?= $fmt_qty($it->qty_returned); ?></td>
                            <td class="text-center"><?= htmlspecialchars($it->unit_measure ?? '-'); ?></td>
                            <td class="text-center"><?= htmlspecialchars($it->wo_number ?? '-'); ?></td>
                            <td><small><strong><?= htmlspecialchars($it->fg_code ?? '-'); ?></strong> <?= htmlspecialchars($it->fg_name ?? ''); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
