<?php $this->load->view('inventory_traceability/_breadcrumb'); ?>
<?php require __DIR__ . '/_doc_helpers.php'; $h = $doc['header']; ?>
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between flex-wrap">
                <div>
                    <h5 class="mb-1"><i class="fas fa-dolly text-warning mr-2"></i><?= htmlspecialchars($h->doc_number); ?> <?= $status_badge($h->status); ?></h5>
                    <div class="small text-muted">
                        <?= lang_line('it_th_date', 'Tanggal'); ?>: <strong><?= $d($h->issuance_date); ?></strong>
                        &middot; <?= lang_line('it_th_dept', 'Departemen'); ?>: <strong><?= htmlspecialchars($h->department ?? '-'); ?></strong>
                        &middot; <?= lang_line('it_th_recipient', 'Penerima'); ?>: <strong><?= htmlspecialchars($h->recipient ?? '-'); ?></strong>
                        <?php if (!empty($h->request_number)): ?>
                            &middot; <?= lang_line('it_th_irf_src', 'Dari Permintaan Barang'); ?>:
                            <a href="<?= site_url('inventory_traceability/doc/irf/' . (int) $h->request_id . '/' . (int) $item_id); ?>"><strong><?= htmlspecialchars($h->request_number); ?></strong></a>
                        <?php endif; ?>
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
        <div class="card-header py-2"><strong class="small"><?= lang_line('it_card_items', 'Barang Dikeluarkan'); ?></strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 small">
                <thead class="thead-light"><tr><th><?= lang_line('it_th_code', 'Kode'); ?></th><th><?= lang_line('it_th_name', 'Barang'); ?></th><th class="text-right"><?= lang_line('it_th_qty', 'Qty'); ?></th><th><?= lang_line('it_th_notes', 'Catatan'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($doc['items'] as $r): ?>
                    <tr class="<?= $is_traced($r) ? 'table-warning font-weight-bold' : '' ?>">
                        <td><code><?= htmlspecialchars($r->item_code ?? '-'); ?></code></td>
                        <td><?= htmlspecialchars($r->item_name ?? '-'); ?> <span class="text-muted"><?= htmlspecialchars($r->item_unit ?? ''); ?></span></td>
                        <td class="text-right"><?= $fmt_qty($r->qty_issued); ?></td>
                        <td><?= htmlspecialchars($r->notes ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
