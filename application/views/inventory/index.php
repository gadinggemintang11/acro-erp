<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-clipboard-list text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <?php if (check_access('master_inventory', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Inventaris
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
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Merk / Spesifikasi</th>
                            <th>Lokasi</th>
                            <th>Kondisi</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($inventaris)): $no=1; foreach($inventaris as $inv): ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($inv->kode_barang); ?></td>
                            <td class="text-left"><?= htmlspecialchars($inv->nama_barang); ?></td>
                            <td><?= htmlspecialchars($inv->kategori); ?></td>
                            <td><?= htmlspecialchars($inv->merk); ?></td>
                            <td><?= htmlspecialchars($inv->lokasi); ?></td>
                            <td><?= htmlspecialchars($inv->kondisi); ?></td>
                            <td>
                                <span class="badge badge-<?= strtolower($inv->status)=='active'?'success':'secondary'; ?>">
                                    <?= ucfirst($inv->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_inventory', 'edit')): ?>
                                    <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $inv->id; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (check_access('master_inventory', 'delete')): ?>
                                    <a href="<?= base_url('master_inventory/delete/'.$inv->id); ?>" class="btn btn-sm btn-danger btn-delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $inv->id; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="post" action="<?= base_url('master_inventory/update/'.$inv->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-gradient-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Inventaris</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Nama Barang</label><input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($inv->nama_barang); ?>" required></div>
                                            
                                            <div class="form-group"><label>Kategori</label>
                                                <select name="kategori" class="form-control" required>
                                                    <?php foreach($kategori_list as $k): ?>
                                                        <option value="<?= $k->code; ?>" <?= $inv->kategori == $k->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($k->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group"><label>Merk / Spesifikasi</label><input type="text" name="merk" class="form-control" value="<?= htmlspecialchars($inv->merk); ?>"></div>
                                            <div class="form-group"><label>Lokasi</label><input type="text" name="lokasi" class="form-control" value="<?= htmlspecialchars($inv->lokasi); ?>"></div>
                                            
                                            <div class="form-group"><label>Kondisi</label>
                                                <select name="kondisi" class="form-control">
                                                    <?php foreach($kondisi_list as $ko): ?>
                                                        <option value="<?= $ko->code; ?>" <?= $inv->kondisi == $ko->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($ko->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-group"><label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="Active" <?= $inv->status=='Active'?'selected':''; ?>>Active</option>
                                                    <option value="Nonaktif" <?= $inv->status=='Nonaktif'?'selected':''; ?>>Nonaktif</option>
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
                            <tr><td colspan="9" class="text-center text-muted py-3">Belum ada data inventaris</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('master_inventory/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Inventaris</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Nama Barang</label><input type="text" name="nama_barang" class="form-control" required></div>
                    
                    <div class="form-group"><label>Kategori</label>
                        <select name="kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach($kategori_list as $k): ?>
                                <option value="<?= $k->code; ?>"><?= htmlspecialchars($k->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group"><label>Merk / Spesifikasi</label><input type="text" name="merk" class="form-control"></div>
                    <div class="form-group"><label>Lokasi</label><input type="text" name="lokasi" class="form-control"></div>
                    
                    <div class="form-group"><label>Kondisi</label>
                        <select name="kondisi" class="form-control">
                            <?php foreach($kondisi_list as $ko): ?>
                                <option value="<?= $ko->code; ?>"><?= htmlspecialchars($ko->description); ?></option>
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