<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-box-open text-success mr-2"></i><?= $title; ?></h1>
        <?php if (check_access('subkon_in', 'add')): ?>
            <a href="<?= base_url('subkon_in/create'); ?>" class="btn btn-success shadow-sm">
                <i class="fas fa-plus-circle fa-sm mr-1"></i> Buat Penerimaan (GRN)
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-filter mr-1"></i> Filter Penerimaan Subkon</h6>
        </div>
        <div class="card-body">
            <form action="<?= base_url('subkon_in/index'); ?>" method="GET">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="start_date">Mulai Tgl Terima</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="end_date">Sampai Tgl Terima</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="Draft" <?= $filters['status'] == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Completed" <?= $filters['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="search">Cari (No. GRN/Kontrak/Supplier)</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Cari...">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search fa-sm mr-1"></i> Tampilkan</button>
                <a href="<?= base_url('subkon_in/index'); ?>" class="btn btn-secondary">Reset</a>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="dataTable" width="100%" cellspacing="0" style="font-size: 0.9rem;">
                    <thead class="thead-light">
                        <tr>
                            <th>No</th>
                            <th>No. Penerimaan</th>
                            <th>Tgl Terima</th>
                            <th>No. Kontrak Subkon</th>
                            <th>Supplier (Vendor)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grns)): ?>
                            <tr><td colspan="7" class="text-center">Tidak ada data penerimaan subkon ditemukan.</td></tr>
                        <?php else: $no = $start_no; foreach ($grns as $grn): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><strong><?= htmlspecialchars($grn->subkon_in_no); ?></strong></td>
                                <td><?= date('d-m-Y', strtotime($grn->receipt_date)); ?></td>
                                <td><?= htmlspecialchars($grn->subkon_order_no); ?></td>
                                <td><?= htmlspecialchars($grn->nama_supplier); ?></td>
                                <td class="text-center">
                                    <?php
                                        $status_colors = ['Draft' => 'secondary', 'Completed' => 'success', 'Cancelled' => 'danger'];
                                        $color = $status_colors[$grn->status] ?? 'dark';
                                    ?>
                                    <span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($grn->status); ?></span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= base_url('subkon_in/detail/' . $grn->id); ?>" class="btn btn-info btn-sm" title="Lihat Detail & Konfirmasi">
                                        <i class="fas fa-search-plus"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <?= $pagination; ?>
            </div>
        </div>
    </div>
</div>