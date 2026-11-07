<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-cubes text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <div class="d-flex align-items-center">
            <form class="form-inline mr-2" method="get" action="<?= base_url('master_material'); ?>">
                <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="<?= lang_line('material_search_placeholder'); ?>" value="<?= htmlspecialchars($keyword ?? ''); ?>">
                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
            </form>

            <?php if (check_access('master_material', 'view')): ?>
                <div class="btn-group mr-2" role="group">
                    <a href="<?= base_url('master_material/export_excel'); ?>" class="btn btn-sm btn-success" title="<?= lang_line('text_excel'); ?>">
                        <i class="fas fa-file-excel"></i> <?= lang_line('text_excel'); ?>
                    </a>
                    <a href="<?= base_url('master_material/export_pdf'); ?>" class="btn btn-sm btn-danger" title="<?= lang_line('text_pdf'); ?>">
                        <i class="fas fa-file-pdf"></i> <?= lang_line('text_pdf'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (check_access('master_material', 'add')): ?>
                <div class="btn-group mr-2" role="group">
                    <a href="<?= base_url('master_material/download_template'); ?>" class="btn btn-sm btn-info" title="Download Template Excel">
                        <i class="fas fa-download"></i> Template
                    </a>
                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#importExcelModal" title="Import dari Excel">
                        <i class="fas fa-file-import"></i> Import
                    </button>
                </div>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('btn_add'); ?> <?= lang_line('material_name'); ?>
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
                            <th><?= lang_line('material_code'); ?></th>
                            <th><?= lang_line('material_name'); ?></th>
                            <th><?= lang_line('material_category'); ?></th>
                            <th><?= lang_line('material_rm_category'); ?></th>
                            <th><?= lang_line('material_origin'); ?></th>
                            <th><?= lang_line('material_unit'); ?></th>
                            <th><?= lang_line('material_status'); ?></th>
                            <th width="150"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = isset($offset) ? ((int)$offset + 1) : 1; ?>
                        <?php if(!empty($materials)): foreach($materials as $m): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td><span class="font-weight-bold text-primary"><?= htmlspecialchars($m->kode_material); ?></span></td>
                            <td class="text-left"><?= htmlspecialchars($m->nama_material); ?></td>
                            <td><?= htmlspecialchars($m->kategori ?? ''); ?></td>
                            <td><?= htmlspecialchars($m->kategorirm ?? ''); ?></td>
                            <td><?= htmlspecialchars($m->asal ?? ''); ?></td>
                            <td><?= htmlspecialchars($m->satuan ?? ''); ?></td>
                            <td>
                                <span class="badge badge-<?= $m->status=='active'?'success':'secondary'; ?> px-3 py-1">
                                    <?= $m->status=='active' ? lang_line('material_status_active') : lang_line('material_status_inactive'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_material', 'edit')): ?>
                                <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $m->id; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (check_access('master_material', 'delete')): ?>
                                <a href="<?= base_url('master_material/delete/'.$m->id); ?>" class="btn btn-sm btn-danger btn-delete">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $m->id; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <form method="post" action="<?= base_url('master_material/update/'.$m->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-gradient-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('material_edit_title'); ?></h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label><?= lang_line('material_code'); ?> <span class="text-danger">*</span></label>
                                                <input type="text" name="kode_material" class="form-control" value="<?= htmlspecialchars($m->kode_material); ?>" required>
                                            </div>

                                            <div class="form-group">
                                                <label><?= lang_line('material_name'); ?></label>
                                                <input type="text" name="nama_material" class="form-control" value="<?= htmlspecialchars($m->nama_material); ?>" required>
                                            </div>

                                            <div class="form-group">
                                                <label><?= lang_line('material_category'); ?></label>
                                                <select name="kategori" class="form-control">
                                                    <?php foreach($kategori_list as $k): ?>
                                                        <option value="<?= $k->code; ?>" <?= $k->code == $m->kategori ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($k->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label><?= lang_line('material_rm_category'); ?></label>
                                                <select name="kategorirm" class="form-control">
                                                    <?php foreach($kategorirm_list as $kr): ?>
                                                        <option value="<?= $kr->code; ?>" <?= $kr->code == ($m->kategorirm ?? '') ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($kr->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>


                                            <div class="form-group">
                                                <label><?= lang_line('material_origin'); ?></label>
                                                <select name="asal" class="form-control">
                                                    <?php foreach($asal_list as $a): ?>
                                                        <option value="<?= $a->code; ?>" <?= $a->code == $m->asal ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($a->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label><?= lang_line('material_unit'); ?></label>
                                                <select name="satuan" class="form-control">
                                                    <?php foreach($satuan_list as $s): ?>
                                                        <option value="<?= $s->code; ?>" <?= $s->code == $m->satuan ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($s->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label><?= lang_line('material_specification'); ?></label>
                                                <textarea name="spesifikasi" class="form-control" rows="3" placeholder="<?= lang_line('material_specification_placeholder'); ?>"><?= htmlspecialchars($m->spesifikasi ?? ''); ?></textarea>
                                            </div>

                                            <div class="form-group">
                                                <label><?= lang_line('material_minimum_stock'); ?> <small class="text-muted">- <?= lang_line('material_minimum_stock_note'); ?></small></label>
                                                <input type="number" step="0.01" name="minimum_stock" class="form-control" placeholder="<?= lang_line('material_minimum_stock_placeholder'); ?>" value="<?= htmlspecialchars($m->minimum_stock ?? 0); ?>">
                                            </div>

                                            <div class="form-group">
                                                <label><?= lang_line('material_status'); ?></label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $m->status=='active'?'selected':''; ?>><?= lang_line('material_status_active'); ?></option>
                                                    <option value="inactive" <?= $m->status=='inactive'?'selected':''; ?>><?= lang_line('material_status_inactive'); ?></option>
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
                            <tr><td colspan="8" class="text-center text-muted py-3"><?= lang_line('material_no_data_message'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div class="d-flex justify-content-end">
                    <?= $pagination ?? ''; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="post" action="<?= base_url('master_material/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i><?= lang_line('material_add_title'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Sub Kategori Produksi <span class="text-danger">*</span></label>
                        <select name="sub_class" class="form-control sub-class-select" required>
                            <option value="">-- Pilih Sub Kategori --</option>
                            <?php foreach ($sub_class_list as $sc): ?>
                                <option value="<?= $sc->code; ?>"><?= htmlspecialchars($sc->description); ?> (<?= $sc->code; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Menentukan kode SUB (RAW/SUP/CON/WIP) pada kode material baru.</small>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('material_origin'); ?> <span class="text-danger">*</span></label>
                        <select name="asal" class="form-control asal-select" required>
                            <option value=""><?= lang_line('material_origin_select'); ?></option>
                            <?php foreach($asal_list as $a): ?>
                                <option value="<?= $a->code; ?>"><?= htmlspecialchars($a->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('material_code'); ?> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="kode_material" id="add_kode_material" class="form-control" placeholder="<?= lang_line('material_code_placeholder'); ?>" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" id="toggle_kode_material_manual">Manual</button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Otomatis dari Sub Kategori + Asal (format MTR-SUB-ORIGIN-NUMBER). Klik "Manual" utk isi sendiri.</small>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('material_name'); ?></label>
                        <input type="text" name="nama_material" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('material_category'); ?></label>
                        <select name="kategori" class="form-control" required>
                            <option value=""><?= lang_line('material_category_select'); ?></option>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k->code; ?>"><?= htmlspecialchars($k->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('material_rm_category'); ?></label>
                        <select name="kategorirm" class="form-control" required>
                            <option value=""><?= lang_line('material_rm_category_select'); ?></option>
                            <?php foreach($kategorirm_list as $kr): ?>
                                <option value="<?= $kr->code; ?>"><?= htmlspecialchars($kr->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('material_unit'); ?></label>
                        <select name="satuan" class="form-control" required>
                            <option value=""><?= lang_line('material_unit_select'); ?></option>
                            <?php foreach($satuan_list as $s): ?>
                                <option value="<?= $s->code; ?>"><?= htmlspecialchars($s->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('material_specification'); ?></label>
                        <textarea name="spesifikasi" class="form-control" rows="3" placeholder="<?= lang_line('material_specification_placeholder'); ?>"></textarea>
                    </div>

                    <div class="form-group">
                        <label><?= lang_line('material_minimum_stock'); ?> <small class="text-muted">- <?= lang_line('material_minimum_stock_note'); ?></small></label>
                        <input type="number" step="0.01" name="minimum_stock" class="form-control" placeholder="<?= lang_line('material_minimum_stock_placeholder'); ?>" value="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('text_save'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('text_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    var isKodeMaterialManual = false;

    // Auto-generate kode_material saat sub_class/asal berubah (kode master barang v2)
    $('#addModal').on('change', '.sub-class-select, .asal-select', function() {
        if (isKodeMaterialManual) return;
        var subClass = $('#addModal .sub-class-select').val();
        var asal = $('#addModal .asal-select').val();
        if (subClass) {
            $.ajax({
                url: '<?= base_url('master_material/generate_kode') ?>',
                type: 'POST',
                data: { sub_class: subClass, asal: asal },
                dataType: 'json',
                success: function(response) {
                    if (response.kode) {
                        $('#add_kode_material').val(response.kode);
                    }
                }
            });
        }
    });

    $(document).on('click', '#toggle_kode_material_manual', function() {
        isKodeMaterialManual = !isKodeMaterialManual;
        var $input = $('#add_kode_material');
        $input.prop('readonly', !isKodeMaterialManual);
        $(this).text(isKodeMaterialManual ? 'Auto' : 'Manual');
        if (isKodeMaterialManual) {
            $input.val('').focus();
        }
    });
});
</script>

<?php if (check_access('master_material', 'add')): ?>
<div class="modal fade" id="importExcelModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form method="post" action="<?= base_url('master_material/upload_excel'); ?>" enctype="multipart/form-data">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-file-import mr-2"></i> Import Master Material dari Excel</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small mb-3">
                        <strong>Petunjuk:</strong>
                        <ol class="mb-0 pl-3">
                            <li>Unduh terlebih dahulu template Excel melalui tombol <b>Template</b>.</li>
                            <li>Isi data sesuai kolom. Gunakan <b>code</b> (bukan deskripsi) untuk kategori, kategorirm, asal, dan satuan. Daftar code tersedia di sheet <b>Referensi Kode</b>.</li>
                            <li>Kolom <code>kode_material</code> boleh dikosongkan agar di-generate otomatis.</li>
                            <li>Upload file di bawah ini, lalu klik <b>Import</b>.</li>
                        </ol>
                    </div>
                    <div class="form-group">
                        <label>File Excel <span class="text-danger">*</span></label>
                        <input type="file" name="excel_file" class="form-control-file" accept=".xlsx,.xls" required>
                        <small class="text-muted">Format yang didukung: .xlsx, .xls</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?= base_url('master_material/download_template'); ?>" class="btn btn-info">
                        <i class="fas fa-download mr-1"></i> Download Template
                    </a>
                    <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-upload mr-1"></i> Import</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('text_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>