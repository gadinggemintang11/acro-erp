<?php
$fmt_qty = function($v){ return number_format((float)$v, 2, ',', '.'); };
$fmt_money = function($v){ return 'Rp ' . number_format((float)$v, 0, ',', '.'); };
$h = $doc['header']; $items = $doc['items'];
?>
<div class="container-fluid">
    <?php $this->load->view('material_traceability/_breadcrumb', ['doc'=>$doc,'doc_type'=>$doc_type,'material'=>$material]); ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-warning py-2">
            <i class="fas fa-truck-loading mr-1"></i> <strong><?= lang_line('mtd_doc_grn'); ?> &mdash; <?= htmlspecialchars($h->grn_number); ?></strong>
        </div>
        <div class="card-body small">
            <div class="row">
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_grn_date'); ?></div><strong><?= htmlspecialchars($h->grn_date); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_base_on'); ?></div><strong><?= htmlspecialchars($h->base_on); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_warehouse_type'); ?></div><strong><?= htmlspecialchars($h->warehouse_type); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_status'); ?></div><strong><?= htmlspecialchars($h->status); ?></strong></div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6"><div class="text-muted"><?= lang_line('mtd_label_supplier'); ?></div><strong><?= htmlspecialchars($h->supplier_name ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_delivery_note'); ?></div><strong><?= htmlspecialchars($h->delivery_note_number ?? '-'); ?></strong></div>
                <div class="col-md-3"><div class="text-muted"><?= lang_line('mtd_label_bc_number'); ?></div><strong><?= htmlspecialchars($h->bc_number ?? '-'); ?></strong></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white"><strong><?= lang_line('mtd_card_items_received'); ?></strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="thead-light text-center small"><tr>
                        <th><?= lang_line('mtd_th_no'); ?></th><th><?= lang_line('mtd_th_kode_material'); ?></th><th><?= lang_line('mtd_th_name'); ?></th><th><?= lang_line('mtd_th_qty_received'); ?></th><th><?= lang_line('mtd_th_unit'); ?></th><th><?= lang_line('mtd_th_unit_value'); ?></th><th><?= lang_line('mtd_th_total_value'); ?></th><th><?= lang_line('mtd_th_source_doc'); ?></th><th><?= lang_line('mtd_th_batch_id'); ?></th><th><?= lang_line('mtd_th_batch_remaining'); ?></th>
                    </tr></thead>
                    <tbody class="small">
                    <?php foreach ($items as $i => $it):
                        $isHighlight = (!empty($material) && $it->item_type==='material' && (int)$it->item_id===(int)$material->id);
                    ?>
                        <tr <?= $isHighlight ? 'class="table-warning"' : ''; ?>>
                            <td class="text-center text-muted"><?= $i+1; ?></td>
                            <td><code><?= htmlspecialchars($it->kode_material ?? '-'); ?></code></td>
                            <td><?= htmlspecialchars($it->nama_material ?? '-'); ?> <?= $isHighlight ? '<i class="fas fa-star text-warning ml-1"></i>' : ''; ?></td>
                            <td class="text-right"><strong><?= $fmt_qty($it->receive_qty); ?></strong></td>
                            <td class="text-center"><?= htmlspecialchars($it->satuan ?? '-'); ?></td>
                            <td class="text-right"><?= $fmt_money($it->unit_value); ?></td>
                            <td class="text-right"><?= $fmt_money($it->total_value); ?></td>
                            <td class="text-center">
                                <?php if ($it->po_header_id): ?>
                                    <a href="<?= site_url('material_traceability/doc/po/'.$it->po_header_id.'/'.($material->id ?? 0)); ?>"><?= htmlspecialchars($it->po_number); ?></a>
                                <?php else: ?>
                                    <span class="text-muted"><?= htmlspecialchars($it->source_doc_type ?? '-'); ?> #<?= (int)$it->source_doc_header_id; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $it->stock_batch_id ? '<code>#'.(int)$it->stock_batch_id.'</code>' : '-'; ?></td>
                            <td class="text-right"><?= $it->stock_batch_id ? $fmt_qty($it->batch_remaining).' / '.$fmt_qty($it->batch_initial) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
