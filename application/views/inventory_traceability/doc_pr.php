<?php $this->load->view('inventory_traceability/_breadcrumb'); ?>
<?php require __DIR__ . '/_doc_helpers.php'; $h = $doc['header']; ?>
<div class="container-fluid">
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between flex-wrap">
                <div>
                    <h5 class="mb-1"><i class="fas fa-clipboard-list text-info mr-2"></i><?= htmlspecialchars($h->pr_number); ?> <?= $status_badge($h->status); ?></h5>
                    <div class="small text-muted">
                        <?= lang_line('it_th_date', 'Tanggal'); ?>: <strong><?= $d($h->pr_date); ?></strong>
                        &middot; <?= lang_line('it_th_dept', 'Departemen'); ?>: <strong><?= htmlspecialchars($h->department ?? '-'); ?></strong>
                        &middot; <?= lang_line('it_th_type', 'Tipe'); ?>: <strong><?= htmlspecialchars($h->type ?? '-'); ?></strong>
                        &middot; <?= lang_line('it_th_requestor', 'Pemohon'); ?>: <strong><?= htmlspecialchars($h->requester_name ?? '-'); ?></strong>
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
        <div class="card-header py-2"><strong class="small"><?= lang_line('it_card_items', 'Daftar Item'); ?></strong></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 small">
                <thead class="thead-light"><tr><th><?= lang_line('it_th_name', 'Barang'); ?></th><th class="text-right"><?= lang_line('it_th_qty', 'Qty'); ?></th><th><?= lang_line('it_th_notes', 'Catatan'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($doc['items'] as $r): ?>
                    <tr class="<?= $is_traced($r) ? 'table-warning font-weight-bold' : '' ?>">
                        <td><?= htmlspecialchars($r->item_display ?: '-'); ?></td>
                        <td class="text-right"><?= $fmt_qty($r->qty); ?></td>
                        <td><?= htmlspecialchars($r->remarks ?: ($r->description ?: '-')); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-2"><strong class="small"><i class="fas fa-file-alt mr-1 text-secondary"></i><?= lang_line('it_card_from_irf', 'Berasal dari Permintaan Barang'); ?></strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 small">
                        <tbody>
                        <?php if (empty($doc['irfs'])): ?>
                            <tr><td class="text-muted text-center py-3"><?= lang_line('it_none_irf_src', 'Tidak ada Permintaan Barang tertaut.'); ?></td></tr>
                        <?php else: foreach ($doc['irfs'] as $r): ?>
                            <tr>
                                <td><code class="text-primary"><?= htmlspecialchars($r->request_number); ?></code> <span class="text-muted"><?= $d($r->request_date); ?></span></td>
                                <td class="text-right"><?= $status_badge($r->status); ?>
                                    <a class="btn btn-xs btn-outline-primary ml-1" href="<?= site_url('inventory_traceability/doc/irf/' . $r->id . '/' . (int) $item_id); ?>"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header py-2"><strong class="small"><i class="fas fa-shopping-cart mr-1 text-primary"></i><?= lang_line('it_card_to_po', 'Lanjut ke PO'); ?></strong></div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 small">
                        <tbody>
                        <?php if (empty($doc['pos'])): ?>
                            <tr><td class="text-muted text-center py-3"><?= lang_line('it_none_po_next', 'Belum ada PO dari PR ini.'); ?></td></tr>
                        <?php else: foreach ($doc['pos'] as $r): ?>
                            <tr>
                                <td><code class="text-primary"><?= htmlspecialchars($r->po_number); ?></code> <span class="text-muted"><?= $d($r->po_date); ?></span></td>
                                <td class="text-right"><?= $status_badge($r->status); ?>
                                    <a class="btn btn-xs btn-outline-primary ml-1" href="<?= site_url('inventory_traceability/doc/po/' . $r->id . '/' . (int) $item_id); ?>"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
