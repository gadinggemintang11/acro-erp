<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-industry text-primary mr-2"></i>
            <?= $title; ?>
        </h1>
        <?php if(check_access('work_order', 'add')): ?>
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#createWoModal">
            <i class="fas fa-plus fa-sm mr-1"></i> Buat Work Order Baru
        </button>
        <?php endif; ?>
    </div>

    <!-- Filter & Search Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-gradient-primary">
            <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-filter mr-2"></i> Filter & Pencarian
            </h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= base_url('work_order'); ?>" id="filterForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="search">
                                <i class="fas fa-search"></i> Cari (No WO, No SC, Nama FG)
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="search" 
                                   name="search" 
                                   placeholder="Ketik untuk mencari..."
                                   value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label>&nbsp;</label>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i> Cari
                            </button>
                            <a href="<?= base_url('work_order'); ?>" class="btn btn-secondary btn-block mt-2">
                                <i class="fas fa-redo mr-1"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list mr-2"></i> Daftar Work Order
            </h6>
            <span class="badge badge-info badge-pill">
                Total: <?= number_format($total_rows ?? 0); ?> data
            </span>
        </div>
        <div class="card-body">
            <?php if (!empty($filters['search'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle mr-2"></i>
                Menampilkan hasil pencarian untuk: <strong>"<?= htmlspecialchars($filters['search']); ?>"</strong>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 15%;">No WO</th>
                            <th style="width: 15%;">No SC</th>
                            <th style="width: 25%;">Finished Good</th>
                            <th style="width: 10%;" class="text-right">Qty Rencana</th>
                            <th style="width: 12%;">Tgl Mulai</th>
                            <th style="width: 10%;" class="text-center">Status</th>
                            <th style="width: 8%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($wos)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Tidak ada data Work Order</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php 
                            $offset = ($this->uri->segment(3)) ? (int)$this->uri->segment(3) : 0;
                            $no = $offset + 1;
                            foreach ($wos as $wo): 
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><strong><?= htmlspecialchars($wo->wo_number); ?></strong></td>
                                <td><?= htmlspecialchars($wo->sc_number ?? '-'); ?></td>
                                <td><?= htmlspecialchars($wo->fg_name ?? '-'); ?></td>
                                <td class="text-right"><?= number_format($wo->planned_qty, 2); ?></td>
                                <td><?= $wo->planned_start_date ? date('d/m/Y', strtotime($wo->planned_start_date)) : '-'; ?></td>
                                <td class="text-center">
                                    <?php if ($wo->status == 'Planned'): ?>
                                        <span class="badge badge-info">
                                            <i class="fas fa-clock mr-1"></i> Planned
                                        </span>
                                    <?php elseif ($wo->status == 'In Progress'): ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-spinner mr-1"></i> In Progress
                                        </span>
                                    <?php elseif ($wo->status == 'Finished'): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle mr-1"></i> Finished
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">
                                            <?= htmlspecialchars($wo->status); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('work_order/detail/' . $wo->id); ?>" 
                                       class="btn btn-sm btn-info btn-load" 
                                       title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if (!empty($pagination)): ?>
            <div class="row mt-3">
                <div class="col-md-6">
                    <p class="text-muted">
                        Menampilkan 
                        <strong><?= count($wos); ?></strong> dari 
                        <strong><?= number_format($total_rows ?? 0); ?></strong> data
                    </p>
                </div>
                <div class="col-md-6">
                    <nav aria-label="Page navigation">
                        <?= $pagination; ?>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>