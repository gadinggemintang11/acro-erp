<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="h4 mb-0 text-gray-800">
                <i class="fas fa-database text-info mr-2"></i>
                <?= htmlspecialchars($title); ?>
            </h3>
            <small class="text-muted ml-4">Menampilkan ringkasan total stok scrap yang tersedia.</small>
        </div>
        <div class="text-right">
            <a href="<?= base_url('scrap_stock/export_excel'); ?>" 
               class="btn btn-success btn-sm shadow-sm mr-2 no-loader" target="_blank">
                <i class="fas fa-file-excel mr-1"></i> Excel
            </a>
            <a href="<?= base_url('scrap_stock/export_pdf'); ?>" 
               class="btn btn-danger btn-sm shadow-sm no-loader" target="_blank">
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </a>
        </div>
    </div>

    <!-- Table Card -->
    <div class="card shadow-sm border-0 rounded-lg">
        <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list mr-2"></i> Ringkasan Stok Scrap
            </h6>
            <span class="badge badge-info badge-pill"><?= count($stock_summary); ?> Item</span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle mb-0">
                    <thead class="thead-light text-center">
                        <tr>
                            <th width="5%">No</th>
                            <th width="120">Kode Scrap</th>
                            <th>Nama Scrap</th>
                            <th width="90">Satuan</th>
                            <th width="110">Qty Masuk</th>
                            <th width="110">Qty Keluar</th>
                            <th width="120">Qty Tersedia</th>
                            <th width="90">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $start_no = $this->uri->segment(3) ? $this->uri->segment(3) + 1 : 1;
                            if (!empty($stock_summary)):
                                foreach ($stock_summary as $row): 
                                    $qty_in  = (float)($row->total_in ?? 0);
                                    $qty_out = (float)($row->total_out ?? 0);
                                    $qty_avail = (float)($row->total_available ?? 0);

                                    $badgeClass = $qty_avail <= 0 ? 'danger' : ($qty_avail < 50 ? 'warning' : 'success');
                        ?>
                        <tr>
                            <td class="text-center"><?= $start_no++; ?></td>
                            <td class="font-weight-bold text-primary text-center"><?= htmlspecialchars($row->scrap_code ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row->scrap_name ?? '-'); ?></td>
                            <td class="text-center"><?= htmlspecialchars($row->satuan_desc ?? '-'); ?></td>
                            <td class="text-right text-success"><?= number_format($qty_in, 2); ?></td>
                            <td class="text-right text-danger"><?= number_format($qty_out, 2); ?></td>
                            <td class="text-right">
                                <span class="badge badge-<?= $badgeClass; ?> px-3 py-1">
                                    <?= number_format($qty_avail, 2); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('scrap_stock/detail/' . ($row->scrap_id ?? 0)); ?>" 
                                   class="btn btn-sm btn-outline-info btn-load" title="Lihat Detail">
                                    <i class="fas fa-search"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">
                                    Tidak ada data stok scrap tersedia.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($pagination)): ?>
        <div class="card-footer py-2 px-3">
            <?= $pagination; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.table th, .table td { vertical-align: middle !important; }
.table-hover tbody tr:hover { background-color: #f8f9fc !important; transition: 0.2s; }
.badge-success { background-color: #28a745; }
.badge-warning { background-color: #ffc107; color: #212529; }
.badge-danger  { background-color: #dc3545; }
.btn-outline-info { border: 1px solid #17a2b8; }
.card-header { background: linear-gradient(90deg, #f8f9fc, #eef3fb); }
</style>
