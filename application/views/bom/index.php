<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-cogs text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
        <?php if (check_access('bom', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addBomModal">
                <i class="fas fa-plus-circle mr-1"></i> Buat BOM Baru
            </button>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" action="<?= base_url('bom/index'); ?>" class="form-inline">
                <div class="form-group mr-3 mb-2 flex-grow-1">
                    <label for="search" class="sr-only">Cari</label>
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="Cari Kode / Nama Style..." value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i> Cari</button>
                    <a href="<?= base_url('bom'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo mr-1"></i> Reset</a>
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
                            <th>Kode Style</th>
                            <th>Nama Style</th>
                            <th>Revisi</th>
                            <th>Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($boms)): foreach($boms as $bom): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($bom->fg_code); ?></td>
                            <td class="text-left"><?= htmlspecialchars($bom->fg_name); ?></td>
                            <td><?= $bom->revision; ?></td>
                            <td><span class="badge badge-info"><?= $bom->status; ?></span></td>
                            <td>
                                <a href="<?= base_url('bom/detail/'.$bom->id); ?>" class="btn btn-sm btn-info btn-load">
                                    <i class="fas fa-list"></i> Detail
                                </a>
                                <?php if ($bom->status == 'Draft' && check_access('bom', 'delete')): ?>
                                <a href="<?= base_url('bom/delete_bom/'.$bom->id); ?>" class="btn btn-sm btn-danger btn-delete ml-1">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data BOM</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addBomModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= base_url('bom/create_header'); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Pilih Style untuk Buat BOM</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Pilih Style (Finished Good)</label>
                        <select name="fg_id" class="form-control" required>
                            <option value="">-- Pilih Style --</option>
                            <?php if(!empty($available_fgs)): foreach($available_fgs as $fg): ?>
                                <option value="<?= $fg->id; ?>">
                                    <?= htmlspecialchars($fg->nama_barang . ' (' . $fg->kode_barang . ')'); ?>
                                </option>
                            <?php endforeach; else: ?>
                                <option value="" disabled>-- Semua style sudah memiliki BOM --</option>
                            <?php endif; ?>
                        </select>
                         <small class="form-text text-muted">Hanya menampilkan style yang belum memiliki BOM.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-load" <?= empty($available_fgs) ? 'disabled' : ''; ?>>
                        <i class="fas fa-arrow-right mr-1"></i> Lanjut ke Detail Komponen
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php $this->load->view('templates/footer'); ?>