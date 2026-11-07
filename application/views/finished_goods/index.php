<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-box text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <div>
            <?php if (check_access('master_fg', 'view')): ?>
                <div class="btn-group mr-2" role="group">
                    <a href="<?= base_url('master_fg/export_excel'); ?>" class="btn btn-sm btn-success" title="<?= lang_line('mst_btn_excel'); ?>">
                        <i class="fas fa-file-excel"></i> <?= lang_line('mst_btn_excel'); ?>
                    </a>
                    <a href="<?= base_url('master_fg/export_pdf'); ?>" class="btn btn-sm btn-danger" title="<?= lang_line('mst_btn_pdf'); ?>">
                        <i class="fas fa-file-pdf"></i> <?= lang_line('mst_btn_pdf'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (check_access('master_fg', 'add')): ?>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('mst_btn_add_data'); ?>
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
                            <th><?= lang_line('mst_th_no'); ?></th>
                            <th><?= lang_line('mst_th_code'); ?></th>
                            <th><?= lang_line('mst_th_model'); ?></th>
                            <th><?= lang_line('mst_th_unit'); ?></th>
                            <th><?= lang_line('mst_th_conversion'); ?></th>
                            <th><?= lang_line('mst_th_status'); ?></th>
                            <th width="180"><?= lang_line('mst_th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($fg)): $no=1; foreach($fg as $row): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($row->kode_barang); ?></td>
                            <td class="text-left"><?= htmlspecialchars($row->nama_barang); ?></td>
                            <td><?= htmlspecialchars($row->satuan); ?></td>
                            <td><small><?= htmlspecialchars($row->konversi_satuan ?? '-'); ?></small></td>
                            <td>
                                <span class="badge badge-<?= $row->status == 'active' ? 'success' : 'secondary'; ?> px-3 py-1">
                                    <?= $row->status == 'active' ? lang_line('mst_status_active') : lang_line('mst_status_inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_fg', 'edit')): ?>
                                    <button type="button" class="btn btn-sm btn-warning text-white"
                                            data-toggle="modal" data-target="#editModal<?= $row->id; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (check_access('master_fg', 'delete')): ?>
                                    <a href="<?= base_url('master_fg/delete/'.$row->id); ?>"
                                       class="btn btn-sm btn-danger btn-delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $row->id; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="post" action="<?= base_url('master_fg/update/'.$row->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-gradient-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('mst_modal_edit_fg'); ?></h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label><?= lang_line('mst_th_code'); ?> <span class="text-danger"><?= lang_line('mst_label_required'); ?></span></label>
                                                <input type="text" name="kode_barang" class="form-control" value="<?= htmlspecialchars($row->kode_barang); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label><?= lang_line('mst_th_model'); ?> <span class="text-danger"><?= lang_line('mst_label_required'); ?></span></label>
                                                <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($row->nama_barang); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label><?= lang_line('mst_th_unit'); ?></label>
                                                <select name="satuan" class="form-control satuan-select" data-modal-id="editModal<?= $row->id; ?>" required>
                                                    <option value=""><?= lang_line('mst_opt_select_unit'); ?></option>
                                                    <?php foreach($satuan_list as $s): ?>
                                                        <option value="<?= $s->code; ?>" data-description="<?= htmlspecialchars($s->description); ?>" <?= $row->satuan == $s->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($s->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="konversi_<?= $row->id; ?>"><?= lang_line('mst_label_content_in'); ?> <span class="satuan-label"><?= htmlspecialchars($row->satuan ?? lang_line('mst_unit_label')); ?></span></label>
                                                <input type="text" id="konversi_<?= $row->id; ?>" name="konversi_satuan" class="form-control" placeholder="<?= htmlspecialchars(lang_line('mst_conversion_placeholder')); ?>" value="<?= htmlspecialchars($row->konversi_satuan ?? ''); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label><?= lang_line('mst_th_status'); ?></label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $row->status == 'active' ? 'selected' : ''; ?>><?= lang_line('mst_status_active'); ?></option>
                                                    <option value="inactive" <?= $row->status == 'inactive' ? 'selected' : ''; ?>><?= lang_line('mst_status_inactive'); ?></option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('mst_btn_update'); ?></button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('mst_btn_cancel'); ?></button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-3"><?= lang_line('mst_empty_fg'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('master_fg/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('mst_modal_add_fg'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?= lang_line('mst_th_code'); ?> <span class="text-danger"><?= lang_line('mst_label_required'); ?></span></label>
                        <input type="text" name="kode_barang" class="form-control" placeholder="<?= htmlspecialchars(lang_line('mst_code_placeholder_fg')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('mst_th_model'); ?> <span class="text-danger"><?= lang_line('mst_label_required'); ?></span></label>
                        <input type="text" name="nama_barang" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('mst_th_unit'); ?></label>
                        <select name="satuan" class="form-control satuan-select" data-modal-id="addModal" required>
                            <option value=""><?= lang_line('mst_opt_select_unit'); ?></option>
                            <?php foreach($satuan_list as $s): ?>
                                <option value="<?= $s->code; ?>" data-description="<?= htmlspecialchars($s->description); ?>">
                                    <?= htmlspecialchars($s->description); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="konversi_add"><?= lang_line('mst_label_content_in'); ?> <span class="satuan-label"><?= lang_line('mst_unit_label'); ?></span></label>
                        <input type="text" id="konversi_add" name="konversi_satuan" class="form-control" placeholder="<?= htmlspecialchars(lang_line('mst_conversion_placeholder')); ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('mst_btn_save'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('mst_btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const I18N_MST = {
    unit_label: <?= json_encode(lang_line('mst_unit_label')); ?>
};
document.addEventListener('DOMContentLoaded', function() {
    // Handle satuan selection change untuk konversi satuan dinamis
    const satuanSelects = document.querySelectorAll('.satuan-select');

    satuanSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            const modalId = this.getAttribute('data-modal-id');
            const selectedOption = this.options[this.selectedIndex];
            const satuanDescription = selectedOption.getAttribute('data-description') || I18N_MST.unit_label;

            // Cari label satuan dalam modal yang sama
            const modal = document.getElementById(modalId);
            if (modal) {
                const satuanLabels = modal.querySelectorAll('.satuan-label');
                satuanLabels.forEach(function(label) {
                    label.textContent = satuanDescription;
                });
            }
        });

        // Trigger change event saat modal dibuka untuk menampilkan satuan yang sudah dipilih
        select.dispatchEvent(new Event('change'));
    });
});
</script>
