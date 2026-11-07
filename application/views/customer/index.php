<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-user-tie text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <div>
            <?php if (check_access('master_customer', 'view')): ?>
                <div class="btn-group mr-2" role="group">
                    <a href="<?= base_url('master_customer/export_excel'); ?>" class="btn btn-sm btn-success" title="<?= lang_line('text_excel'); ?>">
                        <i class="fas fa-file-excel"></i> <?= lang_line('text_excel'); ?>
                    </a>
                    <a href="<?= base_url('master_customer/export_pdf'); ?>" class="btn btn-sm btn-danger" title="<?= lang_line('text_pdf'); ?>">
                        <i class="fas fa-file-pdf"></i> <?= lang_line('text_pdf'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (check_access('master_customer', 'add')): ?>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('btn_add'); ?> <?= lang_line('customer_name'); ?>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>#</th>
                            <th><?= lang_line('customer_code'); ?></th>
                            <th><?= lang_line('customer_name'); ?></th>
                            <th><?= lang_line('text_type'); ?></th>
                            <th><?= lang_line('customer_phone'); ?></th>
                            <th><?= lang_line('customer_email'); ?></th>
                            <th><?= lang_line('customer_status'); ?></th>
                            <th width="180"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($customers)): $no=1; foreach($customers as $c): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($c->kode_customer); ?></td>
                            <td class="text-left"><?= htmlspecialchars($c->nama_customer); ?></td>
                            <td><?= htmlspecialchars($c->jenis); ?></td>
                            <td><?= htmlspecialchars($c->telepon ?: '-'); ?></td>
                            <td><?= htmlspecialchars($c->email ?: '-'); ?></td>
                            <td>
                                <span class="badge badge-<?= $c->status=='active'?'success':'secondary'; ?>">
                                    <?= $c->status=='active' ? lang_line('customer_status_active') : lang_line('customer_status_inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_customer', 'edit')): ?>
                                    <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $c->id; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (check_access('master_customer', 'delete')): ?>
                                    <a href="<?= base_url('master_customer/delete/'.$c->id); ?>" class="btn btn-sm btn-danger btn-delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $c->id; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="post" action="<?= base_url('master_customer/update/'.$c->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-gradient-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('customer_edit_title'); ?></h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label><?= lang_line('customer_name'); ?></label><input type="text" name="nama_customer" class="form-control" value="<?= htmlspecialchars($c->nama_customer); ?>" required></div>

                                            <div class="form-group"><label><?= lang_line('text_type'); ?></label>
                                                <select name="jenis" class="form-control">
                                                    <?php foreach($jenis_list as $j): ?>
                                                        <option value="<?= $j->code; ?>" <?= $c->jenis == $j->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($j->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group"><label>NPWP / NIK</label><input type="text" name="npwp" class="form-control" value="<?= htmlspecialchars($c->npwp); ?>"></div>
                                            <div class="form-group"><label><?= lang_line('customer_address'); ?></label><textarea name="alamat" class="form-control"><?= htmlspecialchars($c->alamat); ?></textarea></div>
                                            <div class="form-group"><label><?= lang_line('customer_phone'); ?></label><input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($c->telepon); ?>"></div>
                                            <div class="form-group"><label><?= lang_line('customer_email'); ?></label><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($c->email); ?>"></div>
                                            <div class="form-group"><label><?= lang_line('customer_status'); ?></label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $c->status=='active'?'selected':''; ?>><?= lang_line('customer_status_active'); ?></option>
                                                    <option value="inactive" <?= $c->status=='inactive'?'selected':''; ?>><?= lang_line('customer_status_inactive'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('text_update'); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('text_cancel'); ?></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3"><?= lang_line('customer_no_data_message'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('master_customer/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('customer_add_title'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label><?= lang_line('customer_name'); ?></label><input type="text" name="nama_customer" class="form-control" required></div>

                    <div class="form-group"><label><?= lang_line('text_type'); ?></label>
                        <select name="jenis" class="form-control" required>
                            <option value="">-- <?= lang_line('text_select_type'); ?> --</option>
                            <?php foreach($jenis_list as $j): ?>
                                <option value="<?= $j->code; ?>"><?= htmlspecialchars($j->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group"><label>NPWP / NIK</label><input type="text" name="npwp" class="form-control"></div>
                    <div class="form-group"><label><?= lang_line('customer_address'); ?></label><textarea name="alamat" class="form-control"></textarea></div>
                    <div class="form-group"><label><?= lang_line('customer_phone'); ?></label><input type="text" name="telepon" class="form-control"></div>
                    <div class="form-group"><label><?= lang_line('customer_email'); ?></label><input type="email" name="email" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('text_save'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('text_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>