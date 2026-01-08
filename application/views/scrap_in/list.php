<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-plus-square text-success mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <div>
             <a href="<?= base_url('Scrap_In/export_excel') . '?' . http_build_query($filters); ?>" class="btn btn-sm btn-success shadow-sm mr-2">
                <i class="fas fa-file-excel mr-1"></i> <?= lang_line('scrap_in_export', 'Export Excel'); ?>
            </a>
            <a href="<?= base_url('Scrap_In/create') ?>" class="btn btn-success shadow-sm btn-load">
                <i class="fas fa-recycle mr-1"></i> <?= lang_line('scrap_in_collect_new', 'Kumpulkan Scrap Baru'); ?>
            </a>
        </div>
    </div>

     <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('Scrap_In/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <label for="search" class="sr-only"><?= lang_line('scrap_in_search_button', 'Cari'); ?></label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('scrap_in_search', 'Cari No. Dokumen...'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> <?= lang_line('scrap_in_search_button', 'Cari'); ?></button>
                    <a href="<?= base_url('Scrap_In'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo mr-1"></i> <?= lang_line('scrap_in_reset', 'Reset'); ?></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('scrap_in_doc_number', 'No. Dokumen'); ?></th>
                            <th><?= lang_line('scrap_in_doc_date', 'Tgl Dokumen'); ?></th>
                            <th><?= lang_line('scrap_in_source', 'Sumber'); ?></th>
                            <th><?= lang_line('scrap_in_created_by', 'Dibuat Oleh'); ?></th>
                            <th><?= lang_line('scrap_in_status', 'Status'); ?></th>
                            <th width="150"><?= lang_line('scrap_in_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($documents)): foreach($documents as $doc): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($doc->doc_no); ?></td>
                            <td><?= date('d M Y', strtotime($doc->doc_date)); ?></td>
                            <td><?= lang_line('scrap_in_from_production', 'Hasil Produksi'); ?></td>
                            <td class="text-left"><?= htmlspecialchars($doc->created_by_name ?: '-'); ?></td>
                            <td>
                                <?php
                                    $badge_class = 'secondary';
                                    $status_text = $doc->status;
                                    if ($doc->status == 'Draft') {
                                        $badge_class = 'warning';
                                        $status_text = lang_line('scrap_in_status_draft', 'Draft');
                                    }
                                    if ($doc->status == 'Confirmed') {
                                        $badge_class = 'success';
                                        $status_text = lang_line('scrap_in_status_confirmed', 'Confirmed');
                                    }
                                    if ($doc->status == 'Cancelled') {
                                        $status_text = lang_line('scrap_in_status_cancelled', 'Cancelled');
                                    }
                                ?>
                                <span class="badge badge-<?= $badge_class; ?>"><?= $status_text; ?></span>
                            </td>
                            <td>
                                <a href="<?= base_url('Scrap_In/view/'.$doc->id); ?>" class="btn btn-sm btn-info btn-load" title="<?= lang_line('scrap_in_view', 'Detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($doc->status == 'Draft'): ?>
                                    <a href="<?= base_url('Scrap_In/delete/'.$doc->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" title="<?= lang_line('scrap_in_delete', 'Hapus'); ?>" data-message="<?= lang_line('scrap_in_confirm_delete', 'Yakin hapus Dokumen Draft ini?'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php else: ?>
                                     <a href="<?= base_url('Scrap_In/export_pdf/'.$doc->id); ?>" target="_blank" class="btn btn-sm btn-secondary ml-1" title="Export PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="6" class="text-center text-muted py-3"><?= lang_line('scrap_in_no_data', 'Belum ada data penerimaan scrap.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>