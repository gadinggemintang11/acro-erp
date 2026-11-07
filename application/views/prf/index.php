<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0"><?= lang_line('prf_draft_title', 'Payment Request Draft'); ?></h1>
            <small class="text-muted"><?= lang_line('prf_draft_subtitle', 'Menampilkan Payment Request dengan status <strong>Draft</strong>'); ?></small>
        </div>
        <div class="col-md-4 text-right">
            <?php if (check_access('prf', 'add')): ?>
            <a href="<?php echo base_url('prf/create'); ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> <?= lang_line('prf_create_new', 'Buat Payment Request Baru'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <!-- Filter Tanggal -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="m-0"><?= lang_line('prf_filter_title', 'Filter'); ?></h6>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2"><?= lang_line('prf_filter_from', 'Dari Tanggal'); ?>:</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo isset($filters['date_from']) ? $filters['date_from'] : ''; ?>">
                </div>
                <div class="form-group mr-3">
                    <label class="mr-2"><?= lang_line('prf_filter_to', 'Sampai Tanggal'); ?>:</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo isset($filters['date_to']) ? $filters['date_to'] : ''; ?>">
                </div>
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="fas fa-search"></i> <?= lang_line('prf_search', 'Cari'); ?>
                </button>
                <a href="<?php echo base_url('prf'); ?>" class="btn btn-link ml-1"><?= lang_line('prf_reset', 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= lang_line('prf_th_no', 'No. Payment Request'); ?></th>
                        <th><?= lang_line('prf_th_date', 'Tanggal'); ?></th>
                        <th><?= lang_line('prf_th_requester', 'Requester'); ?></th>
                        <th><?= lang_line('prf_th_amount', 'Jumlah'); ?></th>
                        <th><?= lang_line('prf_th_status', 'Status'); ?></th>
                        <th><?= lang_line('prf_th_action', 'Action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($prf_list)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="fas fa-inbox text-muted"></i> <?= lang_line('prf_no_draft', 'Tidak ada Payment Request Draft'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($prf_list as $prf): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($prf->prf_number); ?></strong></td>
                                <td><?php echo date('d-m-Y', strtotime($prf->prf_date)); ?></td>
                                <td><?php echo htmlspecialchars($prf->requester_name ?? '-'); ?></td>
                                <td class="text-right">Rp <?php echo number_format($prf->subtotal, 2, ',', '.'); ?></td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $prf->status; ?></span>
                                </td>
                                <td>
                                    <a href="<?php echo base_url('prf/detail/' . $prf->id); ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-danger dropdown-toggle" data-toggle="dropdown" title="<?= lang_line('erf_btn_pdf', 'PDF'); ?>">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A4&orient=L'); ?>">A4 Landscape</a>
                                            <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A4&orient=P'); ?>">A4 Portrait</a>
                                            <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A5&orient=L'); ?>">A5 Landscape</a>
                                            <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A5&orient=P'); ?>">A5 Portrait</a>
                                        </div>
                                    </div>
                                    <a href="<?php echo base_url('prf/export_excel/' . $prf->id); ?>" class="btn btn-sm btn-success" title="<?= lang_line('erf_btn_excel', 'Excel'); ?>">
                                        <i class="fas fa-file-excel"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
