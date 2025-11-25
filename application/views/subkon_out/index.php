<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-truck-loading text-primary mr-2"></i><?= $title; ?></h1>
        <?php if (check_access('subkon_out', 'add')): ?>
            <a href="<?= base_url('subkon_out/create'); ?>" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus-circle fa-sm mr-1"></i> Buat Surat Jalan (DO)
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-filter mr-1"></i> Filter Pengeluaran Subkon</h6>
            <div>
                </div>
        </div>
        <div class="card-body">
            <form action="<?= base_url('subkon_out/index'); ?>" method="GET">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="start_date">Mulai Tgl Kirim</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="end_date">Sampai Tgl Kirim</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="Draft" <?= $filters['status'] == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Confirmed" <?= $filters['status'] == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Cancelled" <?= $filters['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="search">Cari (No. DO/Kontrak/Supplier)</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($filters['search']); ?>" placeholder="Cari...">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search fa-sm mr-1"></i> Tampilkan</button>
                <a href="<?= base_url('subkon_out/index'); ?>" class="btn btn-secondary">Reset</a>
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
                            <th>No. Surat Jalan</th>
                            <th>Tgl Kirim</th>
                            <th>No. Kontrak Subkon</th>
                            <th>Supplier (Vendor)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dos)): ?>
                            <tr><td colspan="7" class="text-center">Tidak ada data pengeluaran subkon ditemukan.</td></tr>
                        <?php else: $no = $start_no; foreach ($dos as $do): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><strong><?= htmlspecialchars($do->subkon_out_no); ?></strong></td>
                                <td><?= date('d-m-Y', strtotime($do->shipping_date)); ?></td>
                                <td><?= htmlspecialchars($do->subkon_order_no); ?></td>
                                <td><?= htmlspecialchars($do->nama_supplier); ?></td>
                                <td class="text-center">
                                    <?php
                                        $status_colors = ['Draft' => 'secondary', 'Confirmed' => 'success', 'Cancelled' => 'danger'];
                                        $color = $status_colors[$do->status] ?? 'dark';
                                    ?>
                                    <span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($do->status); ?></span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= base_url('subkon_out/detail/' . $do->id); ?>" class="btn btn-info btn-sm" title="Lihat Detail & Tambah Item">
                                        <i class="fas fa-pencil-alt"></i>
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