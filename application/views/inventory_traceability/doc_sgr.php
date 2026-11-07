<?php $this->load->view('inventory_traceability/_breadcrumb'); ?>
<?php require __DIR__ . '/_doc_helpers.php'; $h = $doc['header']; ?>
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between flex-wrap">
                <div>
            <h5 class="mb-1"><i class="fas fa-truck-loading text-success mr-2"></i><?= htmlspecialchars($h->sgr_number); ?> <?= $status_badge($h->status); ?></h5>
            <div class="small text-muted">
                <?= lang_line('it_th_date', 'Tanggal'); ?>: <strong><?= $d($h->sgr_date); ?></strong>
                &middot; <?= lang_line('it_th_supplier', 'Supplier'); ?>: <strong><?= htmlspecialchars($h->supplier_name ?? '-'); ?></strong>
                &middot; <?= lang_line('it_th_warehouse', 'Gudang'); ?>: <strong><?= htmlspecialchars($h->warehouse_type ?? '-'); ?></strong>
                <?php if (!empty($h->delivery_note_number)): ?>&middot; <?= lang_line('it_lbl_dn', 'Surat Jalan'); ?>: <strong><?= htmlspecialchars($h->delivery_note_number); ?></strong><?php endif; ?>
            </div>
            <?php if (!empty($h->remarks)): ?><div class="small mt-1"><?= htmlspecialchars($h->remarks); ?></div><?php endif; ?>
                </div>
                <div class="text-right">
                <?php if (!empty($module_link)): ?>
                <a href="<?= $module_link['url']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="fas fa-external-link-alt"></i> <?= sprintf(lang_line('it_open_doc', 'Buka %s'), $module_link['label']); ?>
                </a>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header py-2"><strong class="small"><?= lang_line('it_card_items', 'Barang Diterima'); ?></strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 small">
                <thead class="thead-light"><tr>
                    <th><?= lang_line('it_th_name', 'Barang'); ?></th><th><?= lang_line('it_th_source', 'Dari PO'); ?></th>
                    <th class="text-right"><?= lang_line('it_th_qty', 'Qty'); ?></th>
                    <th class="text-right"><?= lang_line('it_th_price', 'Harga Satuan'); ?></th>
                    <th class="text-right"><?= lang_line('it_th_value', 'Nilai'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($doc['items'] as $r): ?>
                    <tr class="<?= $is_traced($r) ? 'table-warning font-weight-bold' : '' ?>">
                        <td><?= htmlspecialchars($r->item_display ?: '-'); ?></td>
                        <td><?= htmlspecialchars($r->po_number ?? '-'); ?></td>
                        <td class="text-right"><?= $fmt_qty($r->receive_qty); ?></td>
                        <td class="text-right"><?= $fmt_money($r->unit_value); ?></td>
                        <td class="text-right"><?= $fmt_money($r->total_value); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
