<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-route text-primary mr-2"></i> <?= lang_line('mt_title', 'Material Traceability'); ?>
            </h3>
            <small class="text-muted"><?= lang_line('mt_subtitle', 'Telusuri jejak material di seluruh sistem: PR → PO → GRN → Permintaan Barang → Produksi → FG'); ?></small>
        </div>
        <form class="form-inline" method="get" action="<?= site_url('material_traceability'); ?>">
            <div class="input-group input-group-sm" style="min-width: 320px;">
                <input type="text" name="q" class="form-control" placeholder="<?= lang_line('mt_search_ph', 'Cari kode / nama / kategori material...'); ?>" value="<?= htmlspecialchars($keyword ?? ''); ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    <?php if (!empty($keyword)): ?>
                        <a class="btn btn-outline-secondary" href="<?= site_url('material_traceability'); ?>"><i class="fas fa-times"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="thead-light text-center small">
                        <tr>
                            <th>#</th>
                            <th class="text-left"><?= lang_line('mt_th_code', 'Kode'); ?></th>
                            <th class="text-left"><?= lang_line('mt_th_name', 'Nama Material'); ?></th>
                            <th><?= lang_line('mt_th_category', 'Kategori'); ?></th>
                            <th><?= lang_line('mt_th_unit', 'Satuan'); ?></th>
                            <th><?= lang_line('mt_th_source', 'Asal'); ?></th>
                            <th title="<?= lang_line('mt_summary_pr', 'Purchase Request'); ?>"><i class="fas fa-clipboard-list"></i> PR</th>
                            <th title="<?= lang_line('mt_summary_po', 'Purchase Order'); ?>"><i class="fas fa-shopping-cart"></i> PO</th>
                            <th title="<?= lang_line('mt_summary_grn', 'Goods Receipt'); ?>"><i class="fas fa-truck-loading"></i> GRN</th>
                            <th title="<?= lang_line('mt_summary_irf', 'Permintaan Barang'); ?>"><i class="fas fa-dolly"></i> Permintaan Barang</th>
                            <th title="<?= lang_line('mt_summary_production', 'Production'); ?>"><i class="fas fa-industry"></i> PROD</th>
                            <th><?= lang_line('mt_th_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="12" class="text-center text-muted py-4"><?= lang_line('mt_no_data', 'Tidak ada data material.'); ?></td></tr>
                        <?php else: foreach ($rows as $i => $r):
                            $total_use = (int) $r->cnt_pr + (int) $r->cnt_po + (int) $r->cnt_grn + (int) $r->cnt_mr + (int) $r->cnt_prod;
                        ?>
                        <tr>
                            <td class="text-center text-muted"><?= $offset + $i + 1; ?></td>
                            <td><code class="text-primary"><?= htmlspecialchars($r->kode_material); ?></code></td>
                            <td><?= htmlspecialchars($r->nama_material); ?></td>
                            <td class="text-center"><span class="badge badge-light"><?= htmlspecialchars($r->kategori ?? '-'); ?></span></td>
                            <td class="text-center"><?= htmlspecialchars($r->satuan ?? '-'); ?></td>
                            <td class="text-center"><?= htmlspecialchars($r->asal ?? '-'); ?></td>
                            <td class="text-center"><?= $r->cnt_pr > 0 ? '<span class="badge badge-info">'.$r->cnt_pr.'</span>' : '<span class="text-muted">0</span>'; ?></td>
                            <td class="text-center"><?= $r->cnt_po > 0 ? '<span class="badge badge-primary">'.$r->cnt_po.'</span>' : '<span class="text-muted">0</span>'; ?></td>
                            <td class="text-center"><?= $r->cnt_grn > 0 ? '<span class="badge badge-success">'.$r->cnt_grn.'</span>' : '<span class="text-muted">0</span>'; ?></td>
                            <td class="text-center"><?= $r->cnt_mr > 0 ? '<span class="badge badge-warning">'.$r->cnt_mr.'</span>' : '<span class="text-muted">0</span>'; ?></td>
                            <td class="text-center"><?= $r->cnt_prod > 0 ? '<span class="badge badge-dark">'.$r->cnt_prod.'</span>' : '<span class="text-muted">0</span>'; ?></td>
                            <td class="text-center">
                                <a href="<?= site_url('material_traceability/detail/'.$r->id); ?>"
                                   class="btn btn-sm <?= $total_use > 0 ? 'btn-primary' : 'btn-outline-secondary'; ?>"
                                   title="<?= $total_use > 0 ? lang_line('mt_title_view_trail', 'Lihat jejak material') : lang_line('mt_title_no_activity', 'Belum ada aktivitas'); ?>">
                                    <i class="fas fa-route"></i> <?= lang_line('mt_btn_trace', 'Trace'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($pagination)): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center small">
            <span class="text-muted"><?= lang_line('mt_pagination_label', 'Menampilkan'); ?> <?= count($rows); ?> <?= lang_line('mt_pagination_of', 'dari'); ?> <?= $total; ?> <?= lang_line('mt_pagination_material', 'material'); ?></span>
            <?= $pagination; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
