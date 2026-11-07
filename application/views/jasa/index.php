<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-concierge-bell text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <div class="d-flex align-items-center">
            <form class="form-inline mr-2" method="get" action="<?= base_url('master_jasa'); ?>">
                <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="Cari kode / nama jasa..." value="<?= htmlspecialchars($keyword ?? ''); ?>">
                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
            </form>

            <?php if (check_access('master_jasa', 'add')): ?>
                <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus-circle mr-1"></i> Tambah Jasa
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert"><?= $this->session->flashdata('success'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $this->session->flashdata('error'); ?><button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Kode Jasa</th>
                            <th>Nama Jasa</th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = isset($offset) ? ((int)$offset + 1) : 1; ?>
                        <?php if (!empty($jasas)): foreach ($jasas as $j): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td><span class="font-weight-bold text-primary"><?= htmlspecialchars($j->kode_jasa); ?></span></td>
                            <td class="text-left"><?= htmlspecialchars($j->nama_jasa); ?></td>
                            <td><?= htmlspecialchars($j->kategori ?? ''); ?></td>
                            <td><?= htmlspecialchars($j->satuan ?? ''); ?></td>
                            <td>
                                <span class="badge badge-<?= $j->status == 'active' ? 'success' : 'secondary'; ?> px-3 py-1">
                                    <?= $j->status == 'active' ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_jasa', 'edit')): ?>
                                <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $j->id; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>

                                <?php if (check_access('master_jasa', 'delete')): ?>
                                <a href="<?= base_url('master_jasa/delete/' . $j->id); ?>" class="btn btn-sm btn-danger btn-delete" data-message="Yakin nonaktifkan jasa <?= htmlspecialchars($j->nama_jasa); ?>?">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $j->id; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered" role="document">
                                <form method="post" action="<?= base_url('master_jasa/update/' . $j->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-gradient-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Jasa</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group">
                                                <label>Kode Jasa <span class="text-danger">*</span></label>
                                                <input type="text" name="kode_jasa" class="form-control" value="<?= htmlspecialchars($j->kode_jasa); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Jasa</label>
                                                <input type="text" name="nama_jasa" class="form-control" value="<?= htmlspecialchars($j->nama_jasa); ?>" required>
                                            </div>
                                            <div class="form-group">
                                                <label>Kategori</label>
                                                <select name="kategori" class="form-control">
                                                    <?php foreach ($kategori_list as $k): ?>
                                                        <option value="<?= $k->code; ?>" <?= $k->code == $j->kategori ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($k->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Satuan</label>
                                                <select name="satuan" class="form-control">
                                                    <?php foreach ($satuan_list as $s): ?>
                                                        <option value="<?= $s->code; ?>" <?= $s->code == $j->satuan ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($s->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $j->status == 'active' ? 'selected' : ''; ?>>Aktif</option>
                                                    <option value="inactive" <?= $j->status == 'inactive' ? 'selected' : ''; ?>>Nonaktif</option>
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
                            <tr><td colspan="7" class="text-center text-muted py-3">Belum ada data jasa.</td></tr>
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
        <form method="post" action="<?= base_url('master_jasa/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Jasa</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Kode Jasa <small class="text-muted">(kosongkan untuk auto-generate)</small></label>
                        <input type="text" name="kode_jasa" class="form-control" placeholder="Contoh: SRV-000001">
                    </div>
                    <div class="form-group">
                        <label>Nama Jasa</label>
                        <input type="text" name="nama_jasa" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategori_list as $k): ?>
                                <option value="<?= $k->code; ?>"><?= htmlspecialchars($k->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Satuan</label>
                        <select name="satuan" class="form-control" required>
                            <option value="">-- Pilih Satuan --</option>
                            <?php foreach ($satuan_list as $s): ?>
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
