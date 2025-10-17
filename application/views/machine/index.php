<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-cogs text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <?php if (check_access('master_machine', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Mesin / Peralatan
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
                            <th>Kode</th>
                            <th>Nama Mesin</th>
                            <th>Kategori</th>
                            <th>Merk / Tipe</th>
                            <th>Tahun</th>
                            <th>Lokasi</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($machines)): $no=1; foreach($machines as $m): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($m->kode_mesin); ?></td>
                            <td class="text-left"><?= htmlspecialchars($m->nama_mesin); ?></td>
                            <td><?= htmlspecialchars($m->kategori); ?></td>
                            <td><?= htmlspecialchars($m->merk); ?></td>
                            <td><?= htmlspecialchars($m->tahun); ?></td>
                            <td><?= htmlspecialchars($m->lokasi); ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($m->status)=='active'?'success':'secondary'; ?>">
                                    <?= ucfirst($m->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_machine', 'edit')): ?>
                                    <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $m->id; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (check_access('master_machine', 'delete')): ?>
                                    <a href="<?= base_url('master_machine/delete/'.$m->id); ?>" class="btn btn-sm btn-danger btn-delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $m->id; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="post" action="<?= base_url('master_machine/update/'.$m->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-gradient-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Mesin / Peralatan</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Nama Mesin</label><input type="text" name="nama_mesin" class="form-control" value="<?= htmlspecialchars($m->nama_mesin); ?>" required></div>
                                            
                                            <div class="form-group"><label>Kategori</label>
                                                <select name="kategori" class="form-control">
                                                    <?php foreach($kategori_list as $k): ?>
                                                        <option value="<?= $k->code; ?>" <?= $m->kategori == $k->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($k->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group"><label>Merk / Tipe</label><input type="text" name="merk" class="form-control" value="<?= htmlspecialchars($m->merk); ?>"></div>
                                            <div class="form-group"><label>Tahun</label><input type="number" name="tahun" class="form-control" value="<?= htmlspecialchars($m->tahun); ?>"></div>
                                            <div class="form-group"><label>Lokasi</label><input type="text" name="lokasi" class="form-control" value="<?= htmlspecialchars($m->lokasi); ?>"></div>
                                            
                                            <div class="form-group"><label>Status</label>
                                                <select name="status" class="form-control">
                                                    <?php foreach($status_list as $s): ?>
                                                        <option value="<?= $s->code; ?>" <?= $m->status == $s->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($s->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
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
                            <tr><td colspan="9" class="text-center text-muted py-3">Belum ada data mesin / peralatan</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('master_machine/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Mesin / Peralatan</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Nama Mesin</label><input type="text" name="nama_mesin" class="form-control" required></div>
                    
                    <div class="form-group"><label>Kategori</label>
                        <select name="kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k->code; ?>"><?= htmlspecialchars($k->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group"><label>Merk / Tipe</label><input type="text" name="merk" class="form-control"></div>
                    <div class="form-group"><label>Tahun</label><input type="number" name="tahun" class="form-control" min="1980" max="<?= date('Y'); ?>"></div>
                    <div class="form-group"><label>Lokasi</label><input type="text" name="lokasi" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>