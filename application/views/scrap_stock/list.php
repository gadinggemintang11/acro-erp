<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="h4 mb-0 text-gray-800">
                <i class="fas fa-recycle text-info mr-2"></i>
                <?= htmlspecialchars($title); ?>
            </h3>
            <small class="text-muted ml-4">
                <?= lang_line('scrs_subtitle_list'); ?>
            </small>
        </div>
        <div class="text-right">
            <a href="<?= base_url('scrap_stock/export_excel'); ?>"
               class="btn btn-success btn-sm shadow-sm mr-2 no-loader" target="_blank">
                <i class="fas fa-file-excel mr-1"></i> <?= lang_line('scrs_btn_excel'); ?>
            </a>
            <a href="<?= base_url('scrap_stock/export_pdf'); ?>"
               class="btn btn-danger btn-sm shadow-sm no-loader" target="_blank">
                <i class="fas fa-file-pdf mr-1"></i> <?= lang_line('scrs_btn_pdf'); ?>
            </a>
        </div>
    </div>

    <!-- Card Container -->
    <div class="card border-0 shadow-lg rounded-lg">
        <div class="card-header bg-gradient-light py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-database mr-2"></i> <?= lang_line('scrs_card_data'); ?>
            </h6>
            <span class="badge badge-pill badge-info">
                <?= isset($stock_data) ? count($stock_data) : 0; ?> <?= lang_line('scrs_item_count'); ?>
            </span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm align-middle mb-0">
                    <thead class="thead-light text-center">
                        <tr>
                            <th width="50">#</th>
                            <th width="130"><?= lang_line('scrs_th_code'); ?></th>
                            <th><?= lang_line('scrs_th_name'); ?></th>
                            <th width="110"><?= lang_line('scrs_th_qty_in'); ?></th>
                            <th width="110"><?= lang_line('scrs_th_qty_out'); ?></th>
                            <th width="120"><?= lang_line('scrs_th_qty_avail'); ?></th>
                            <th width="90"><?= lang_line('scrs_th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stock_data)): ?>
                            <?php $no = 1; foreach ($stock_data as $row): ?>
                                <?php
                                    $qty_in  = isset($row->total_in) ? (float)$row->total_in : 0;
                                    $qty_out = isset($row->total_out) ? (float)$row->total_out : 0;
                                    $qty_avail = isset($row->total_available)
                                                ? (float)$row->total_available
                                                : ($qty_in - $qty_out);
                                    $badgeClass = $qty_avail <= 0 ? 'danger' : ($qty_avail < 50 ? 'warning' : 'success');
                                ?>
                                <tr>
                                    <td class="text-center align-middle text-muted"><?= $no++; ?></td>
                                    <td class="font-weight-bold text-primary align-middle">
                                        <i class="fas fa-cube mr-1 text-secondary"></i>
                                        <?= htmlspecialchars($row->scrap_code ?? '-'); ?>
                                    </td>
                                    <td class="align-middle"><?= htmlspecialchars($row->scrap_name ?? '-'); ?></td>
                                    <td class="text-right text-success align-middle">
                                        <?= number_format($qty_in, 2); ?>
                                    </td>
                                    <td class="text-right text-danger align-middle">
                                        <?= number_format($qty_out, 2); ?>
                                    </td>
                                    <td class="text-right align-middle">
                                        <span class="badge badge-<?= $badgeClass; ?> px-3 py-1">
                                            <?= number_format($qty_avail, 2); ?>
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <a href="<?= base_url('scrap_stock/detail/' . ($row->scrap_id ?? 0)); ?>"
                                           class="btn btn-sm btn-outline-info btn-load" title="<?= lang_line('scrs_btn_detail'); ?>">
                                            <i class="fas fa-search"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-box-open fa-2x mb-2"></i><br>
                                        <?= lang_line('scrs_no_data_alt'); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer text-muted small text-right">
            <i class="fas fa-info-circle mr-1"></i>
            <?= lang_line('scrs_last_update'); ?> <?= date('d M Y, H:i'); ?>
        </div>
    </div>

</div>

<!-- Custom Style -->
<style>
.table th, .table td { vertical-align: middle !important; }
.table-hover tbody tr:hover { background-color: #f1f5ff !important; transition: 0.2s; }
.badge-success { background-color: #28a745; }
.badge-warning { background-color: #ffc107; color: #212529; }
.badge-danger  { background-color: #dc3545; }
.btn-outline-info { border: 1px solid #17a2b8; }
.card-header { background: linear-gradient(90deg, #f8f9fc, #eef3fb); }
</style>
