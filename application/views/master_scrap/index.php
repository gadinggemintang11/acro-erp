<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-recycle text-primary mr-2"></i><?= lang_line('master_scrap_title', $title); ?></h3>
        <div>
            <?php if (check_access('master_scrap', 'view')): ?>
                <div class="btn-group mr-2" role="group">
                    <a href="<?= base_url('master_scrap/export_excel'); ?>" class="btn btn-sm btn-success" title="<?= lang_line('btn_export'); ?> Excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="<?= base_url('master_scrap/export_pdf'); ?>" class="btn btn-sm btn-danger" title="<?= lang_line('btn_export'); ?> PDF">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            <?php endif; ?>
            <?php if (check_access('master_scrap', 'add')): ?>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('btn_add'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('master_scrap/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('search_placeholder', 'Cari...'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search fa-sm mr-1"></i> <?= lang_line('btn_search'); ?></button>
                    <a href="<?= base_url('master_scrap'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> <?= lang_line('btn_reset'); ?></a>
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
                            <th><?= lang_line('master_scrap_code'); ?></th>
                            <th><?= lang_line('master_scrap_name'); ?></th>
                            <th><?= lang_line('master_scrap_uom'); ?></th>
                            <th><?= lang_line('master_scrap_status'); ?></th>
                            <th width="150"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($scraps)): foreach($scraps as $scrap): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($scrap->scrap_code); ?></td>
                            <td class="text-left"><?= htmlspecialchars($scrap->scrap_name); ?></td>
                            <td><?= htmlspecialchars($scrap->unit); ?></td>
                            <td><span class="badge badge-<?= $scrap->status == 'active' ? 'success' : 'secondary'; ?>"><?= ucfirst($scrap->status); ?></span></td>
                            <td>
                                <?php if (check_access('master_scrap', 'edit')): ?>
                                <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $scrap->id; ?>" title="<?= lang_line('btn_edit'); ?>">
                                    <i class="fas fa-edit"></i> <?= lang_line('btn_edit'); ?>
                                </button>
                                <?php endif; ?>
                                <?php if (check_access('master_scrap', 'delete')): ?>
                                <a href="<?= base_url('master_scrap/delete/'.$scrap->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="<?= lang_line('scrap_deactivate_confirm'); ?>">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $scrap->id; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="post" action="<?= base_url('master_scrap/update/'.$scrap->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-warning text-dark">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('btn_edit'); ?> <?= lang_line('master_scrap_title'); ?></h5>
                                            <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label><?= lang_line('master_scrap_code'); ?></label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($scrap->scrap_code); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label><?= lang_line('master_scrap_name'); ?></label>
                                                <input type="text" name="scrap_name" class="form-control" value="<?= htmlspecialchars($scrap->scrap_name); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label><?= lang_line('master_scrap_uom'); ?></label>
                                                <select name="unit" class="form-control" required>
                                                    <?php foreach($satuan_list as $satuan): ?>
                                                    <option value="<?= $satuan->code; ?>" <?= $scrap->unit == $satuan->code ? 'selected' : ''; ?>><?= htmlspecialchars($satuan->description); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label><?= lang_line('mst_th_status'); ?></label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $scrap->status == 'active' ? 'selected' : ''; ?>><?= lang_line('mst_status_active'); ?></option>
                                                    <option value="inactive" <?= $scrap->status == 'inactive' ? 'selected' : ''; ?>><?= lang_line('mst_status_inactive'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-warning"><i class="fas fa-save mr-1"></i> <?= lang_line('mst_btn_update'); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('mst_btn_cancel'); ?></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data scrap</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('master_scrap/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('master_scrap_title_add', 'Tambah Scrap Baru'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?= lang_line('master_scrap_code'); ?></label>
                        <div class="input-group">
                            <input type="text" name="scrap_code" id="scrap_code" class="form-control" placeholder="<?= lang_line('master_scrap_code_auto'); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="auto_generate_btn" title="<?= lang_line('master_scrap_code_generate'); ?>" data-base-url="<?= base_url(); ?>">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted"><?= lang_line('master_scrap_code_hint'); ?></small>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('master_scrap_name'); ?></label>
                        <input type="text" name="scrap_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('master_scrap_uom'); ?></label>
                        <select name="unit" class="form-control" required>
                            <option value="">-- <?= lang_line('label_select'); ?> --</option>
                            <?php foreach($satuan_list as $satuan): ?>
                            <option value="<?= $satuan->code; ?>"><?= htmlspecialchars($satuan->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> <?= lang_line('btn_save'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= base_url('assets/js/master_scrap.js'); ?>"></script>

