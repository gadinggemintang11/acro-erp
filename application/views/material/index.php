<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-cubes text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <div class="d-flex align-items-center">
            <form class="form-inline mr-2" method="get" action="<?= base_url('master_material'); ?>">
                <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="Cari kode / nama / kategori" value="<?= htmlspecialchars($keyword ?? ''); ?>">
                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
            </form>

            <?php if (check_access('master_material', 'add')): ?>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus-circle mr-1"></i> Tambah Material
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
                            <th>Kode</th>
                            <th>Nama Material</th>
                            <th>Kategori</th>
                            <th>Material Kategori</th>
                            <th>Asal</th>
                            <th>Satuan</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
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
                                    <?= ucfirst($m->status); ?>
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
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Material</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Nama Material</label>
                                                <input type="text" name="nama_material" class="form-control" value="<?= htmlspecialchars($m->nama_material); ?>" required>
                                            </div>

                                            <div class="form-group">
                                                <label>Kategori</label>
                                                <select name="kategori" class="form-control">
                                                    <?php foreach($kategori_list as $k): ?>
                                                        <option value="<?= $k->code; ?>" <?= $k->code == $m->kategori ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($k->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Material Kategori</label>
                                                <select name="kategorirm" class="form-control">
                                                    <?php foreach($kategorirm_list as $kr): ?>
                                                        <option value="<?= $kr->code; ?>" <?= $kr->code == ($m->kategorirm ?? '') ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($kr->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>


                                            <div class="form-group">
                                                <label>Asal</label>
                                                <select name="asal" class="form-control">
                                                    <?php foreach($asal_list as $a): ?>
                                                        <option value="<?= $a->code; ?>" <?= $a->code == $m->asal ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($a->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Satuan</label>
                                                <select name="satuan" class="form-control">
                                                    <?php foreach($satuan_list as $s): ?>
                                                        <option value="<?= $s->code; ?>" <?= $s->code == $m->satuan ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($s->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $m->status=='active'?'selected':''; ?>>Active</option>
                                                    <option value="inactive" <?= $m->status=='inactive'?'selected':''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-success btn-load"><i class="fas fa-save mr-1"></i> Update</button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">Tidak ada data</td></tr>
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
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Material Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Material</label>
                        <input type="text" name="nama_material" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k->code; ?>"><?= htmlspecialchars($k->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Material Kategori</label>
                        <select name="kategorirm" class="form-control" required>
                            <option value="">-- Pilih Material Kategori --</option>
                            <?php foreach($kategorirm_list as $kr): ?>
                                <option value="<?= $kr->code; ?>"><?= htmlspecialchars($kr->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Asal</label>
                        <select name="asal" class="form-control" required>
                            <option value="">-- Pilih Asal --</option>
                            <?php foreach($asal_list as $a): ?>
                                <option value="<?= $a->code; ?>"><?= htmlspecialchars($a->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Satuan</label>
                        <select name="satuan" class="form-control" required>
                            <option value="">-- Pilih Satuan --</option>
                            <?php foreach($satuan_list as $s): ?>
                                <option value="<?= $s->code; ?>"><?= htmlspecialchars($s->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>