<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-recycle text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('master_scrap', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Scrap Baru
            </button>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('master_scrap/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari Kode / Nama Scrap..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search fa-sm mr-1"></i> Cari</button>
                    <a href="<?= base_url('master_scrap'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> Reset</a>
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
                            <th>Kode Scrap</th>
                            <th>Nama Scrap</th>
                            <th>Satuan</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
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
                                <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $scrap->id; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php endif; ?>
                                <?php if (check_access('master_scrap', 'delete')): ?>
                                <a href="<?= base_url('master_scrap/delete/'.$scrap->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1" data-message="Yakin ingin menonaktifkan scrap ini?">
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
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Master Scrap</h5>
                                            <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Kode Scrap</label>
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($scrap->scrap_code); ?>" disabled>
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Scrap</label>
                                                <input type="text" name="scrap_name" class="form-control" value="<?= htmlspecialchars($scrap->scrap_name); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Satuan</label>
                                                <select name="unit" class="form-control" required>
                                                    <?php foreach($satuan_list as $satuan): ?>
                                                    <option value="<?= $satuan->code; ?>" <?= $scrap->unit == $satuan->code ? 'selected' : ''; ?>><?= htmlspecialchars($satuan->description); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $scrap->status == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?= $scrap->status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Scrap Baru</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                     <div class="form-group">
                        <label>Nama Scrap</label>
                        <input type="text" name="scrap_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Satuan</label>
                        <select name="unit" class="form-control" required>
                            <option value="">-- Pilih Satuan --</option>
                            <?php foreach($satuan_list as $satuan): ?>
                            <option value="<?= $satuan->code; ?>"><?= htmlspecialchars($satuan->description); ?></option>
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

<?php $this->load->view('templates/footer'); ?>