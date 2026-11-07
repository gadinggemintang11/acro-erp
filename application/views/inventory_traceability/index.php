<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-route text-primary mr-2"></i> <?= lang_line('it_title', 'Penelusuran Barang General'); ?>
            </h3>
            <small class="text-muted"><?= lang_line('it_subtitle', 'Telusuri jejak satu barang general: Permintaan Barang → PR → PO → Penerimaan → Pengeluaran → Kartu Stok'); ?></small>
        </div>
        <form class="form-inline mt-2 mt-md-0" method="get" action="<?= site_url('inventory_traceability'); ?>">
            <div class="input-group input-group-sm" style="min-width: 320px;">
                <input type="text" name="q" class="form-control" placeholder="<?= lang_line('it_search_ph', 'Cari kode / nama / kategori / merk...'); ?>" value="<?= htmlspecialchars($keyword ?? ''); ?>">
                <div class="input-group-append">
                    <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    <?php if (!empty($keyword)): ?>
                        <a class="btn btn-outline-secondary" href="<?= site_url('inventory_traceability'); ?>"><i class="fas fa-times"></i></a>
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
                            <th class="text-left"><?= lang_line('it_th_code', 'Kode'); ?></th>
                            <th class="text-left"><?= lang_line('it_th_name', 'Nama Barang'); ?></th>
                            <th><?= lang_line('it_th_category', 'Kategori'); ?></th>
                            <th><?= lang_line('it_th_unit', 'Satuan'); ?></th>
                            <th><?= lang_line('it_th_status', 'Status'); ?></th>
                            <th title="<?= lang_line('it_summary_irf', 'Permintaan Barang'); ?>"><i class="fas fa-file-alt"></i> Permintaan Barang</th>
                            <th title="<?= lang_line('it_summary_pr', 'Purchase Request'); ?>"><i class="fas fa-clipboard-list"></i> PR</th>
                            <th title="<?= lang_line('it_summary_po', 'Purchase Order'); ?>"><i class="fas fa-shopping-cart"></i> PO</th>
                            <th title="<?= lang_line('it_summary_sgr', 'Penerimaan Barang (SGR)'); ?>"><i class="fas fa-truck-loading"></i> TERIMA</th>
                            <th title="<?= lang_line('it_summary_out', 'Pengeluaran Barang'); ?>"><i class="fas fa-dolly"></i> KELUAR</th>
                            <th><?= lang_line('it_th_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="12" class="text-center text-muted py-4"><?= lang_line('it_no_data', 'Tidak ada barang general.'); ?></td></tr>
                        <?php else: foreach ($rows as $i => $r):
                            $total_use = (int) $r->cnt_irf + (int) $r->cnt_pr + (int) $r->cnt_po + (int) $r->cnt_sgr + (int) $r->cnt_out;
                            $badge = function ($n, $cls) { return $n > 0 ? '<span class="badge badge-' . $cls . '">' . (int) $n . '</span>' : '<span class="text-muted">0</span>'; };
                        ?>
                        <tr>
                            <td class="text-center text-muted"><?= $offset + $i + 1; ?></td>
                            <td><code class="text-primary"><?= htmlspecialchars($r->kode_barang); ?></code></td>
                            <td>
                                <?= htmlspecialchars($r->nama_barang); ?>
                                <?php if (!empty($r->merk)): ?><span class="text-muted">· <?= htmlspecialchars($r->merk); ?></span><?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge badge-light"><?= htmlspecialchars($r->kategori ?? '-'); ?></span></td>
                            <td class="text-center"><?= htmlspecialchars($r->satuan ?? '-'); ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?= $r->status === 'Active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($r->status ?? '-'); ?></span>
                            </td>
                            <td class="text-center"><?= $badge($r->cnt_irf, 'secondary'); ?></td>
                            <td class="text-center"><?= $badge($r->cnt_pr, 'info'); ?></td>
                            <td class="text-center"><?= $badge($r->cnt_po, 'primary'); ?></td>
                            <td class="text-center"><?= $badge($r->cnt_sgr, 'success'); ?></td>
                            <td class="text-center"><?= $badge($r->cnt_out, 'warning'); ?></td>
                            <td class="text-center">
                                <a href="<?= site_url('inventory_traceability/detail/' . $r->id); ?>"
                                   class="btn btn-sm <?= $total_use > 0 ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                    <i class="fas fa-route"></i> <?= lang_line('it_btn_trace', 'Telusuri'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (!empty($pagination)): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <small class="text-muted"><?= sprintf(lang_line('it_total_items', 'Total %s barang'), number_format((int) $total, 0, ',', '.')); ?></small>
            <?= $pagination; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
