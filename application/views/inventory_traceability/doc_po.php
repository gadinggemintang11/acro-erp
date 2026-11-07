<?php $this->load->view('inventory_traceability/_breadcrumb'); ?>
<?php require __DIR__ . '/_doc_helpers.php'; $h = $doc['header']; ?>
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between flex-wrap">
                <div>
            <h5 class="mb-1"><i class="fas fa-shopping-cart text-primary mr-2"></i><?= htmlspecialchars($h->po_number); ?> <?= $status_badge($h->status); ?></h5>
            <div class="small text-muted">
                <?= lang_line('it_th_date', 'Tanggal'); ?>: <strong><?= $d($h->po_date); ?></strong>
                &middot; <?= lang_line('it_th_supplier', 'Supplier'); ?>: <strong><?= htmlspecialchars($h->supplier_name ?? '-'); ?></strong>
                &middot; <?= lang_line('it_th_type', 'Tipe'); ?>: <strong><?= htmlspecialchars($h->type ?? '-'); ?></strong>
                &middot; <?= lang_line('it_lbl_currency', 'Mata Uang'); ?>: <strong><?= htmlspecialchars($h->currency_code ?? 'IDR'); ?></strong>
                <?php if ((float) ($h->exchange_rate ?? 1) != 1): ?> (<?= lang_line('it_lbl_rate', 'kurs'); ?> <?= $fmt_qty($h->exchange_rate); ?>)<?php endif; ?>
            </div>
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
        <div class="card-header py-2"><strong class="small"><?= lang_line('it_card_items', 'Daftar Item'); ?></strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 small">
                <thead class="thead-light"><tr>
                    <th><?= lang_line('it_th_name', 'Barang'); ?></th>
                    <th class="text-right"><?= lang_line('it_th_qty', 'Qty'); ?></th>
                    <th class="text-right"><?= lang_line('it_th_price', 'Harga Satuan'); ?></th>
                    <th class="text-right"><?= lang_line('it_th_value', 'Nilai'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($doc['items'] as $r): ?>
                    <tr class="<?= $is_traced($r) ? 'table-warning font-weight-bold' : '' ?>">
                        <td><?= htmlspecialchars($r->item_display ?: ($r->description ?: '-')); ?></td>
                        <td class="text-right"><?= $fmt_qty($r->qty); ?></td>
                        <td class="text-right"><?= $fmt_money($r->local_price); ?></td>
                        <td class="text-right"><?= $fmt_money($r->total_local); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header py-2"><strong class="small"><i class="fas fa-truck-loading mr-1 text-success"></i><?= lang_line('it_card_receipts', 'Penerimaan dari PO ini'); ?></strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 small">
                <thead class="thead-light"><tr><th><?= lang_line('it_th_doc', 'No. Dokumen'); ?></th><th><?= lang_line('it_th_date', 'Tanggal'); ?></th><th><?= lang_line('it_th_warehouse', 'Gudang'); ?></th><th><?= lang_line('it_th_status', 'Status'); ?></th><th></th></tr></thead>
                <tbody>
                <?php if (empty($doc['receipts'])): ?>
                    <tr><td colspan="5" class="text-muted text-center py-3"><?= lang_line('it_none_receipt', 'Barang dari PO ini belum diterima.'); ?></td></tr>
                <?php else: foreach ($doc['receipts'] as $r): ?>
                    <tr>
                        <td><code class="text-primary"><?= htmlspecialchars($r->sgr_number); ?></code></td>
                        <td><?= $d($r->sgr_date); ?></td>
                        <td><?= htmlspecialchars($r->warehouse_type ?? '-'); ?></td>
                        <td><?= $status_badge($r->status); ?></td>
                        <td class="text-right"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('inventory_traceability/doc/sgr/' . $r->id . '/' . (int) $item_id); ?>"><i class="fas fa-eye"></i></a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
