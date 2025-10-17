<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-puzzle-piece text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <?php if (check_access('master_semifinished', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Data
            </button>
        <?php endif; ?>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Asal</th>
                            <th>Satuan</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($semifinished)): $no=1; foreach($semifinished as $row): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row->kode_barang); ?></td>
                            <td class="text-left"><?= htmlspecialchars($row->nama_barang); ?></td>
                            <td><?= htmlspecialchars($row->kategori); ?></td>
                            <td><?= htmlspecialchars($row->asal); ?></td>
                            <td><?= htmlspecialchars($row->satuan); ?></td>
                            <td>
                                <span class="badge badge-<?= $row->status == 'active' ? 'success' : 'secondary'; ?> px-3 py-1">
                                    <?= ucfirst($row->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_semifinished', 'edit')): ?>
                                    <button type="button" class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $row->id; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (check_access('master_semifinished', 'delete')): ?>
                                    <a href="<?= base_url('master_semifinished/delete/'.$row->id); ?>" class="btn btn-sm btn-danger btn-delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $row->id; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="post" action="<?= base_url('master_semifinished/update/'.$row->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-gradient-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Semifinished</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Nama Barang</label>
                                                <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($row->nama_barang); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Kategori</label>
                                                <select name="kategori" class="form-control" required>
                                                    <?php foreach($kategori_list as $k): ?>
                                                        <option value="<?= $k->code; ?>" <?= $row->kategori == $k->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($k->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Asal</label>
                                                <select name="asal" class="form-control" required>
                                                     <?php foreach($asal_list as $a): ?>
                                                        <option value="<?= $a->code; ?>" <?= $row->asal == $a->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($a->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Satuan</label>
                                                <select name="satuan" class="form-control" required>
                                                    <?php foreach($satuan_list as $s): ?>
                                                        <option value="<?= $s->code; ?>" <?= $row->satuan == $s->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($s->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $row->status == 'active' ? 'selected' : ''; ?>>Active</option>
                                                    <option value="inactive" <?= $row->status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
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
                            <tr><td colspan="8" class="text-center text-muted py-3">Belum ada data semifinished goods</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('master_semifinished/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Semifinished</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Nama Barang</label><input type="text" name="nama_barang" class="form-control" required></div>
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