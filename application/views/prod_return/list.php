<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-undo-alt text-warning mr-2"></i><?= lang_line('prodreturn_title', 'Material Return dari Produksi'); ?>
        </h3>

        <div>
            <a href="<?= base_url('ProdReturn/export_excel') . '?' . http_build_query($filters ?? []); ?>" 
               class="btn btn-sm btn-success shadow-sm mr-2">
                <i class="fas fa-file-excel mr-1"></i> <?= lang_line('prodreturn_export_excel', 'Export Excel'); ?>
            </a>

            <?php if (true): // TODO: Ganti dengan check_access('prod_return', 'add') ?>
                <a href="<?= base_url('ProdReturn/create'); ?>" class="btn btn-warning shadow-sm btn-load">
                    <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('prodreturn_add_new', 'Buat Retur Baru'); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter/Search -->
    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('ProdReturn/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <label for="search" class="sr-only"><?= lang_line('prodreturn_search', 'Cari'); ?></label>
                    <input type="text" name="search" id="search" 
                           class="form-control form-control-sm w-100"
                           placeholder="<?= lang_line('prodreturn_search', 'Cari No. Retur / WO...'); ?>" 
                           value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search mr-1"></i> <?= lang_line('search', 'Cari'); ?>
                    </button>
                    <a href="<?= base_url('ProdReturn'); ?>" class="btn btn-secondary btn-sm ml-1">
                        <i class="fas fa-redo mr-1"></i> <?= lang_line('reset', 'Reset'); ?>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('prodreturn_return_no', 'No. Retur'); ?></th>
                            <th><?= lang_line('prodreturn_return_date', 'Tgl Retur'); ?></th>
                            <th><?= lang_line('prodreturn_work_order', 'No. Produksi (WO)'); ?></th>
                            <th><?= lang_line('prodreturn_returner', 'Diretur Oleh'); ?></th>
                            <th><?= lang_line('prodreturn_receiver', 'Diterima Gudang Oleh'); ?></th>
                            <th><?= lang_line('prodreturn_status', 'Status'); ?></th>
                            <th width="150"><?= lang_line('prodreturn_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($returns)): ?>
                            <?php foreach ($returns as $ret): ?>
                                <tr class="text-center">
                                    <td class="font-weight-bold text-primary">
                                        <?= htmlspecialchars($ret->return_number ?? '-'); ?>
                                    </td>
                                    <td>
                                        <?= !empty($ret->return_date) ? date('d M Y', strtotime($ret->return_date)) : '-'; ?>
                                    </td>
                                    <td><?= htmlspecialchars($ret->production_number ?? '-'); ?></td>
                                    <td class="text-left"><?= htmlspecialchars($ret->returner_name ?? '-'); ?></td>
                                    <td class="text-left"><?= htmlspecialchars($ret->receiver_name ?? '-'); ?></td>
                                    <td>
                                        <?php
                                            $badge_class = 'secondary';
                                            if (($ret->status ?? '') === 'Draft') $badge_class = 'warning';
                                            elseif (($ret->status ?? '') === 'Confirmed') $badge_class = 'success';
                                            elseif (($ret->status ?? '') === 'Cancelled') $badge_class = 'danger';
                                        ?>
                                        <span class="badge badge-<?= $badge_class; ?>">
                                            <?= htmlspecialchars($ret->status ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= base_url('ProdReturn/view/' . ($ret->id ?? 0)); ?>" 
                                           class="btn btn-sm btn-info btn-load" title="<?= lang_line('prodreturn_detail', 'Detail'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <?php if (($ret->status ?? '') === 'Draft'): ?>
                                            <?php if (true): // TODO: check_access('prod_return', 'edit') ?>
                                                <a href="<?= base_url('ProdReturn/edit/' . ($ret->id ?? 0)); ?>" 
                                                   class="btn btn-sm btn-warning btn-load ml-1" title="<?= lang_line('edit', 'Edit'); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (true): // TODO: check_access('prod_return', 'delete') ?>
                                                <a href="<?= base_url('ProdReturn/delete/' . ($ret->id ?? 0)); ?>" 
                                                   class="btn btn-sm btn-danger btn-delete ml-1" 
                                                   title="<?= lang_line('delete', 'Hapus'); ?>" data-message="<?= lang_line('prodreturn_confirm_delete', 'Yakin hapus draft ini?'); ?>">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <a href="<?= base_url('ProdReturn/export_pdf/' . ($ret->id ?? 0)); ?>" 
                                               target="_blank" class="btn btn-sm btn-secondary ml-1" 
                                               title="<?= lang_line('prodreturn_export_pdf', 'Export PDF'); ?>">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    <?= lang_line('prodreturn_no_data', 'Tidak ada data retur'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
