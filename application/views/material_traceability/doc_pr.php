<?php
$fmt_qty = function($v){ return number_format((float)$v, 2, ',', '.'); };
$h = $doc['header']; $items = $doc['items'];
?>
<div class="container-fluid">
    <?php $this->load->view('material_traceability/_breadcrumb', ['doc'=>$doc,'doc_type'=>$doc_type,'material'=>$material]); ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-info text-white py-2">
            <i class="fas fa-clipboard-list mr-1"></i> <strong><?= lang_line('mtd_doc_pr'); ?> &mdash; <?= htmlspecialchars($h->pr_number); ?></strong>
        </div>
        <div class="card-body small">
            <div class="row">
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_pr_date'); ?></div><strong><?= htmlspecialchars($h->pr_date); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_status'); ?></div><strong><?= htmlspecialchars($h->status); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_department'); ?></div><strong><?= htmlspecialchars($h->department ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_purpose'); ?></div><strong><?= htmlspecialchars($h->purpose ?? '-'); ?></strong></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_requester'); ?></div><strong><?= htmlspecialchars($h->requester_name ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_submitted_at'); ?></div><strong><?= htmlspecialchars($h->submitted_at ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_approved_by'); ?></div><strong><?= htmlspecialchars($h->approver_name ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_approved_at'); ?></div><strong><?= htmlspecialchars($h->approved_at ?? '-'); ?></strong></div>
            </div>
            <?php if (!empty($h->remarks)): ?>
                <hr><div class="text-muted"><?= lang_line('mtd_label_remarks'); ?></div><div><?= nl2br(htmlspecialchars($h->remarks)); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong><?= lang_line('mtd_card_items'); ?></strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="thead-light text-center small"><tr>
                        <th><?= lang_line('mtd_th_no'); ?></th><th><?= lang_line('mtd_th_type'); ?></th><th><?= lang_line('mtd_th_code'); ?></th><th><?= lang_line('mtd_th_name'); ?></th><th><?= lang_line('mtd_th_qty'); ?></th><th><?= lang_line('mtd_th_unit'); ?></th><th><?= lang_line('mtd_th_description'); ?></th><th><?= lang_line('mtd_th_remarks'); ?></th>
                    </tr></thead>
                    <tbody class="small">
                    <?php foreach ($items as $i => $it):
                        $isHighlight = (!empty($material) && $it->item_type==='material' && (int)$it->item_id===(int)$material->id);
                    ?>
                        <tr <?= $isHighlight ? 'class="table-warning"' : ''; ?>>
                            <td class="text-center text-muted"><?= $i+1; ?></td>
                            <td class="text-center"><span class="badge badge-secondary"><?= htmlspecialchars($it->item_type); ?></span></td>
                            <td><code><?= htmlspecialchars($it->item_code ?? '-'); ?></code></td>
                            <td><?= htmlspecialchars($it->item_name ?? '-'); ?> <?= $isHighlight ? '<i class="fas fa-star text-warning ml-1" title="' . htmlspecialchars(lang_line('mtd_label_traced_material')) . '"></i>' : ''; ?></td>
                            <td class="text-right"><?= $fmt_qty($it->qty); ?></td>
                            <td class="text-center"><?= htmlspecialchars($it->satuan ?? '-'); ?></td>
                            <td><?= htmlspecialchars($it->description ?? ''); ?></td>
                            <td><?= htmlspecialchars($it->remarks ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
