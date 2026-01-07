<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    
    <div class="row mb-3">
        <div class="col">
            <?php if(check_access('work_order', 'add')): ?>
            <button class="btn btn-primary" data-toggle="modal" data-target="#createWoModal">
                <i class="fas fa-plus fa-sm"></i> Buat Work Order Baru
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Work Order</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No WO</th>
                            <th>No SC</th>
                            <th>Finished Good</th>
                            <th>Qty Rencana</th>
                            <th>Tgl Mulai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($wos as $wo): ?>
                        <tr>
                            <td><?= htmlspecialchars($wo->wo_number); ?></td>
                            <td><?= htmlspecialchars($wo->sc_number); ?></td>
                            <td><?= htmlspecialchars($wo->fg_name); ?></td>
                            <td><?= number_format($wo->planned_qty, 2); ?></td>
                            <td><?= date('d-m-Y', strtotime($wo->planned_start_date)); ?></td>
                            <td>
                                <span class="badge badge-<?= ($wo->status == 'Planned') ? 'info' : 'success'; ?>">
                                    <?= htmlspecialchars($wo->status); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= base_url('work_order/detail/' . $wo->id); ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>