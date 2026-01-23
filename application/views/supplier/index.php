<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-truck text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>

        <?php if (check_access('master_supplier', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addModal">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Supplier
            </button>
        <?php endif; ?>
    </div>

    <!-- ✅ SEARCH & FILTER FORM -->
    <div class="card border-0 shadow-sm rounded mb-4">
        <div class="card-body">
            <form method="get" action="<?= base_url('master_supplier'); ?>" class="form-inline">
                <div class="form-group mr-2 mb-2" style="flex: 1;">
                    <input type="text" name="search" class="form-control w-100" placeholder="Cari: Kode, Nama, NPWP, Alamat..." 
                        value="<?= htmlspecialchars($search); ?>">
                </div>

                <div class="form-group mr-2 mb-2">
                    <select name="jenis" class="form-control">
                        <option value="">-- Semua Jenis --</option>
                        <?php foreach($jenis_list as $j): ?>
                            <option value="<?= $j->code; ?>" <?= $jenis == $j->code ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($j->description); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mr-2 mb-2">
                    <select name="status" class="form-control">
                        <option value="active" <?= empty($status) || $status == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?= $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="">Semua Status</option>
                    </select>
                </div>

                <div class="form-group mr-2 mb-2">
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-search mr-1"></i> Cari
                    </button>
                    <a href="<?= base_url('master_supplier'); ?>" class="btn btn-secondary ml-2">
                        <i class="fas fa-redo mr-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted">Total: <strong><?= $total_rows; ?></strong> Supplier</span>
            </div>
            <div>
                <!-- ✅ EXPORT BUTTONS -->
                <a href="<?= base_url('master_supplier/export_excel?' . http_build_query(['search' => $search, 'jenis' => $jenis, 'status' => $status])); ?>" 
                    class="btn btn-sm btn-success" title="Export ke Excel">
                    <i class="fas fa-file-excel mr-1"></i> Excel
                </a>
                <a href="<?= base_url('master_supplier/export_pdf?' . http_build_query(['search' => $search, 'jenis' => $jenis, 'status' => $status])); ?>" 
                    class="btn btn-sm btn-danger" title="Export ke PDF">
                    <i class="fas fa-file-pdf mr-1"></i> PDF
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>#</th>
                            <th>Kode</th>
                            <th>Nama Supplier</th>
                            <th>Jenis</th>
                            <th>NPWP</th>
                            <th>Telepon</th>
                            <th>Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($suppliers)): 
                            $no = $offset + 1; 
                            foreach($suppliers as $s): 
                        ?>
                        <tr class="text-center">
                            <td><?= $no++; ?></td>
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($s->kode_supplier); ?></td>
                            <td class="text-left"><?= htmlspecialchars($s->nama_supplier); ?></td>
                            <td><?= htmlspecialchars($s->jenis); ?></td>
                            <td><?= htmlspecialchars($s->npwp ?: '-'); ?></td>
                            <td><?= htmlspecialchars($s->telepon ?: '-'); ?></td>
                            <td>
                                <span class="badge badge-<?= $s->status=='active'?'success':'secondary'; ?>">
                                    <?= ucfirst($s->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (check_access('master_supplier', 'edit')): ?>
                                    <button class="btn btn-sm btn-warning text-white" data-toggle="modal" data-target="#editModal<?= $s->id; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if (check_access('master_supplier', 'delete')): ?>
                                    <a href="<?= base_url('master_supplier/delete/'.$s->id); ?>" class="btn btn-sm btn-danger btn-delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <div class="modal fade" id="editModal<?= $s->id; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="post" action="<?= base_url('master_supplier/update/'.$s->id); ?>">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-gradient-primary text-white">
                                            <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Edit Supplier</h5>
                                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="form-group"><label>Nama Supplier</label><input type="text" name="nama_supplier" class="form-control" value="<?= htmlspecialchars($s->nama_supplier); ?>" required></div>
                                            
                                            <div class="form-group"><label>Jenis</label>
                                                <select name="jenis" class="form-control">
                                                    <?php foreach($jenis_list as $j): ?>
                                                        <option value="<?= $j->code; ?>" <?= $s->jenis == $j->code ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($j->description); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            
                                            <div class="form-group"><label>NPWP</label><input type="text" name="npwp" class="form-control" value="<?= htmlspecialchars($s->npwp); ?>"></div>
                                            <div class="form-group"><label>Alamat</label><textarea name="alamat" class="form-control"><?= htmlspecialchars($s->alamat); ?></textarea></div>
                                            <div class="form-group"><label>Telepon</label><input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($s->telepon); ?>"></div>
                                            <div class="form-group"><label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="active" <?= $s->status=='active'?'selected':''; ?>>Active</option>
                                                    <option value="inactive" <?= $s->status=='inactive'?'selected':''; ?>>Inactive</option>
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
                            <tr><td colspan="8" class="text-center text-muted py-3">Belum ada data supplier</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ✅ PAGINATION - Bootstrap Styled (Sama seperti Master Material) -->
            <?php if (!empty($pagination)): ?>
            <div class="card-footer py-2 px-3">
                <?= $pagination; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('master_supplier/add'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Supplier</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group"><label>Nama Supplier</label><input type="text" name="nama_supplier" class="form-control" required></div>
                    
                    <div class="form-group"><label>Jenis</label>
                        <select name="jenis" class="form-control" required>
                            <option value="">-- Pilih Jenis --</option>
                            <?php foreach($jenis_list as $j): ?>
                                <option value="<?= $j->code; ?>"><?= htmlspecialchars($j->description); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group"><label>NPWP</label><input type="text" name="npwp" class="form-control"></div>
                    <div class="form-group"><label>Alamat</label><textarea name="alamat" class="form-control"></textarea></div>
                    <div class="form-group"><label>Telepon</label><input type="text" name="telepon" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load"><i class="fas fa-save mr-1"></i> Simpan</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>