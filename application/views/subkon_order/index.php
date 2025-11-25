<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-signature text-primary mr-2"></i><?= $title; ?></h1>
        <?php if (check_access('subkon_order', 'add')): ?>
            <a href="<?= base_url('subkon_order/create'); ?>" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus-circle fa-sm mr-1"></i> Buat Kontrak Baru
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-filter mr-1"></i> Filter Kontrak</h6>
            <div>
                <a href="<?= base_url('subkon_order/export_list_excel?' . http_build_query($filters)); ?>" class="btn btn-sm btn-success shadow-sm"><i class="fas fa-file-excel mr-1"></i> Ekspor Excel</a>
            </div>
        </div>
        <div class="card-body">
            <form action="<?= base_url('subkon_order/index'); ?>" method="GET">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="start_date">Mulai Tgl</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="end_date">Sampai Tgl</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="Draft" <?= $filters['status'] == 'Draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="Confirmed" <?= $filters['status'] == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Closed" <?= $filters['status'] == 'Closed' ? 'selected' : ''; ?>>Closed</option>
                            <option value="Cancelled" <?= $filters['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="search">Cari (No/Supplier)</label>
                        <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($filters['search']); ?>" placeholder="Cari...">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search fa-sm mr-1"></i> Tampilkan</button>
                <a href="<?= base_url('subkon_order/index'); ?>" class="btn btn-secondary">Reset</a>
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
                            <th>No. Kontrak</th>
                            <th>Tanggal</th>
                            <th>Supplier (Vendor)</th>
                            <th class="text-right">Total Jasa (IDR)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center">Tidak ada data kontrak subkon ditemukan.</td></tr>
                        <?php else: $no = $start_no; foreach ($orders as $order): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><strong><?= htmlspecialchars($order->subkon_order_no); ?></strong></td>
                                <td><?= date('d-m-Y', strtotime($order->order_date)); ?></td>
                                <td><?= htmlspecialchars($order->nama_supplier); ?></td>
                                
                                <td class="text-right"><?= number_format($order->total_jasa, 2); ?></td>
                                <td class="text-center">
                                    <?php
                                        $status_colors = ['Draft' => 'secondary', 'Confirmed' => 'success', 'Closed' => 'primary', 'Cancelled' => 'danger'];
                                        $color = $status_colors[$order->status] ?? 'dark';
                                    ?>
                                    <span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($order->status); ?></span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= base_url('subkon_order/detail/' . $order->id); ?>" class="btn btn-info btn-sm" title="Lihat Detail & BOM">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= base_url('subkon_order/export_pdf/' . $order->id); ?>" target="_blank" class="btn btn-danger btn-sm" title="Cetak PDF">
                                        <i class="fas fa-file-pdf"></i>
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