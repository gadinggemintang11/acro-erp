<?php $this->load->view('inventory_traceability/_breadcrumb'); ?>
<?php require __DIR__ . '/_doc_helpers.php'; $h = $doc['header']; ?>
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between flex-wrap">
                <div>
                    <h5 class="mb-1"><i class="fas fa-file-alt text-secondary mr-2"></i><?= htmlspecialchars($h->request_number); ?> <?= $status_badge($h->status); ?></h5>
                    <div class="small text-muted">
                        <?= lang_line('it_th_date', 'Tanggal'); ?>: <strong><?= $d($h->request_date); ?></strong>
                        &middot; <?= lang_line('it_th_dept', 'Departemen'); ?>: <strong><?= htmlspecialchars($h->department ?? '-'); ?></strong>
                        &middot; <?= lang_line('it_lbl_category', 'Kategori'); ?>: <strong><?= htmlspecialchars($h->category ?? '-'); ?></strong>
                        &middot; <?= lang_line('it_th_requestor', 'Pemohon'); ?>: <strong><?= htmlspecialchars($h->requestor_name ?: ($h->created_by_name ?? '-')); ?></strong>
                    </div>
                    <?php if (!empty($h->purpose)): ?><div class="small mt-1"><?= lang_line('it_lbl_purpose', 'Keperluan'); ?>: <?= htmlspecialchars($h->purpose); ?></div><?php endif; ?>
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
                    <th><?= lang_line('it_th_name', 'Barang'); ?></th><th class="text-right"><?= lang_line('it_th_qty', 'Qty'); ?></th>
                    <th class="text-right"><?= lang_line('it_th_price', 'Harga Satuan'); ?></th><th><?= lang_line('it_th_notes', 'Catatan'); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($doc['items'] as $r): ?>
                    <tr class="<?= $is_traced($r) ? 'table-warning font-weight-bold' : '' ?>">
                        <td><?= htmlspecialchars($r->item_display ?: ($r->item_name ?? '-')); ?></td>
                        <td class="text-right"><?= $fmt_qty($r->qty_requested); ?></td>
                        <td class="text-right"><?= $r->unit_price !== null ? $fmt_money($r->unit_price) : '-'; ?></td>
                        <td><?= htmlspecialchars($r->notes ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($doc['issuances'])): ?>
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header py-2"><strong class="small"><i class="fas fa-dolly mr-1 text-warning"></i><?= lang_line('it_card_related_out', 'Pengeluaran dari Permintaan Barang ini'); ?></strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 small">
                <thead class="thead-light"><tr><th><?= lang_line('it_th_doc', 'No. Dokumen'); ?></th><th><?= lang_line('it_th_date', 'Tanggal'); ?></th><th><?= lang_line('it_th_recipient', 'Penerima'); ?></th><th><?= lang_line('it_th_status', 'Status'); ?></th><th></th></tr></thead>
                <tbody>
                <?php foreach ($doc['issuances'] as $r): ?>
                    <tr>
                        <td><code class="text-primary"><?= htmlspecialchars($r->doc_number); ?></code></td>
                        <td><?= $d($r->issuance_date); ?></td>
                        <td><?= htmlspecialchars($r->recipient ?? '-'); ?></td>
                        <td><?= $status_badge($r->status); ?></td>
                        <td class="text-right"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('inventory_traceability/doc/out/' . $r->id . '/' . (int) $item_id); ?>"><i class="fas fa-eye"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
