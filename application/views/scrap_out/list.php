<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-minus-square text-danger mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <a href="<?= base_url('Scrap_Out/create') ?>" class="btn btn-danger shadow-sm btn-load">
            <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('scrap_out_create'); ?>
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('scrap_out_doc_number'); ?></th>
                            <th><?= lang_line('scrap_out_doc_date'); ?></th>
                            <th><?= lang_line('scrap_out_bc_type'); ?></th>
                            <th><?= lang_line('scrap_out_bc_number'); ?></th>
                            <th><?= lang_line('scrap_out_bc_date'); ?></th>
                            <th><?= lang_line('scrap_out_customer'); ?></th>
                            <th><?= lang_line('scrap_out_status'); ?></th>
                            <th width="200"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($documents)): foreach($documents as $doc): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($doc->doc_no); ?></td>
                            <td><?= date('d M Y', strtotime($doc->doc_date)); ?></td>
                            <td><?= htmlspecialchars($doc->bc_type_code ?: '-'); ?></td>
                            <td><?= htmlspecialchars($doc->bc_number ?: '-'); ?></td>
                            <td><?= $doc->bc_date ? date('d M Y', strtotime($doc->bc_date)) : '-'; ?></td>
                            <td><?= htmlspecialchars($doc->customer_name ?: '-'); ?></td>
                            <td>
                                <?php
                                    $badge = $doc->status == 'Confirmed' ? 'success' : ($doc->status == 'Draft' ? 'warning' : 'secondary');
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= htmlspecialchars($doc->status); ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('Scrap_Out/view/'.$doc->id); ?>" class="btn btn-sm btn-info btn-load" title="<?= lang_line('scrap_out_view_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($doc->status == 'Draft'): ?>
                                    <a href="<?= base_url('Scrap_Out/edit/'.$doc->id); ?>" class="btn btn-sm btn-warning" title="<?= lang_line('scrap_out_edit'); ?>">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?= base_url('Scrap_Out/delete/'.$doc->id); ?>" class="btn btn-sm btn-danger btn-delete" data-message="<?= lang_line('scrap_out_delete_confirm'); ?>">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= base_url('Scrap_Out/export_pdf/'.$doc->id); ?>" target="_blank" class="btn btn-sm btn-secondary" title="<?= lang_line('scrap_out_pdf'); ?>">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3"><?= lang_line('scrap_out_no_data'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
