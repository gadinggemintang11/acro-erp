<div class="container-fluid">

    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="h4 mb-0 text-gray-800">
                <i class="fas fa-search text-info mr-2"></i>
                <?= htmlspecialchars($title); ?>
            </h3>
            <small class="text-muted ml-4">
                <?= lang_line('scrs_subtitle_trace'); ?>
            </small>
        </div>
        <a href="<?= base_url('scrap_stock/summary'); ?>" class="btn btn-secondary btn-sm shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50 mr-1"></i> <?= lang_line('scrs_btn_back'); ?>
        </a>
    </div>

    <?php if (!empty($scrap)): ?>
        <?php
            $qty_avail = (float)($scrap->total_available ?? 0);
            $badgeClass = $qty_avail <= 0 ? 'danger' : ($qty_avail < 50 ? 'warning' : 'success');
        ?>

        <!-- Informasi Scrap -->
        <div class="card shadow-sm border-left-info mb-4">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <strong><i class="fas fa-info-circle mr-1 text-info"></i> <?= lang_line('scrs_card_info'); ?></strong>
                <span class="badge badge-pill badge-info">ID #<?= htmlspecialchars($scrap->scrap_id ?? '-'); ?></span>
            </div>
            <div class="card-body py-3">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th width="35%"><?= lang_line('scrs_th_code'); ?></th>
                                <td class="text-primary font-weight-bold"><?= htmlspecialchars($scrap->scrap_code ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th><?= lang_line('scrs_th_name'); ?></th>
                                <td><?= htmlspecialchars($scrap->scrap_name ?? '-'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th width="35%"><?= lang_line('scrs_th_unit'); ?></th>
                                <td><?= htmlspecialchars($scrap->satuan_desc ?? '-'); ?></td>
                            </tr>
                            <tr>
                                <th><?= lang_line('scrs_label_stock_avail'); ?></th>
                                <td>
                                    <span class="badge badge-<?= $badgeClass; ?> px-3 py-2 font-weight-bold">
                                        <?= number_format($qty_avail, 2); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Riwayat Scrap Masuk -->
        <div class="card shadow-sm mb-4 border-left-success">
            <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                <strong><i class="fas fa-sign-in-alt mr-1"></i> <?= lang_line('scrs_card_in'); ?></strong>
                <span class="badge badge-light text-success"><?= count($in_history); ?> <?= lang_line('scrs_count_transactions'); ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light text-center">
                            <tr>
                                <th width="5%"><?= lang_line('scrs_th_no'); ?></th>
                                <th><?= lang_line('scrs_th_doc_no'); ?></th>
                                <th><?= lang_line('scrs_th_date'); ?></th>
                                <th><?= lang_line('scrs_th_qty'); ?></th>
                                <th><?= lang_line('scrs_th_status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($in_history)): ?>
                                <?php $no = 1; foreach ($in_history as $row): ?>
                                    <?php if (strtoupper($row->status ?? '') !== 'CONFIRMED') continue; ?>
                                    <tr>
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="text-primary font-weight-bold"><?= htmlspecialchars($row->doc_no ?? '-'); ?></td>
                                        <td class="text-center"><?= !empty($row->doc_date) ? date('d M Y', strtotime($row->doc_date)) : '-'; ?></td>
                                        <td class="text-right text-success font-weight-bold"><?= number_format($row->qty ?? 0, 2); ?></td>
                                        <td class="text-center">
                                            <span class="badge badge-success px-2 py-1"><?= lang_line('scrs_badge_confirmed'); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($no === 1): ?>
                                    <tr><td colspan="5" class="text-center text-muted py-2"><?= lang_line('scrs_no_confirmed'); ?></td></tr>
                                <?php endif; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-3"><?= lang_line('scrs_no_in_history'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Riwayat Scrap Keluar -->
        <div class="card shadow-sm mb-4 border-left-danger">
            <div class="card-header bg-danger text-white py-2 d-flex justify-content-between align-items-center">
                <strong><i class="fas fa-sign-out-alt mr-1"></i> <?= lang_line('scrs_card_out'); ?></strong>
                <span class="badge badge-light text-danger"><?= count($out_history); ?> <?= lang_line('scrs_count_transactions'); ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light text-center">
                            <tr>
                                <th width="5%"><?= lang_line('scrs_th_no'); ?></th>
                                <th><?= lang_line('scrs_th_doc_no'); ?></th>
                                <th><?= lang_line('scrs_th_date'); ?></th>
                                <th><?= lang_line('scrs_th_bc_type'); ?></th>
                                <th><?= lang_line('scrs_th_bc_no'); ?></th>
                                <th><?= lang_line('scrs_th_bc_date'); ?></th>
                                <th><?= lang_line('scrs_th_customer'); ?></th>
                                <th><?= lang_line('scrs_th_qty'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($out_history)): ?>
                                <?php $no = 1; foreach ($out_history as $row): ?>
                                    <?php if (strtoupper($row->status ?? '') !== 'CONFIRMED') continue; ?>
                                    <tr>
                                        <td class="text-center"><?= $no++; ?></td>
                                        <td class="text-primary font-weight-bold"><?= htmlspecialchars($row->doc_no ?? '-'); ?></td>
                                        <td class="text-center"><?= !empty($row->doc_date) ? date('d M Y', strtotime($row->doc_date)) : '-'; ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row->bc_type_name ?? $row->bc_type_code ?? '-'); ?></td>
                                        <td class="text-center"><?= htmlspecialchars($row->bc_number ?? '-'); ?></td>
                                        <td class="text-center"><?= !empty($row->bc_date) ? date('d M Y', strtotime($row->bc_date)) : '-'; ?></td>
                                        <td><?= htmlspecialchars($row->destination_name ?? '-'); ?></td>
                                        <td class="text-right text-danger font-weight-bold"><?= number_format($row->qty ?? 0, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($no === 1): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-2"><?= lang_line('scrs_no_confirmed'); ?></td></tr>
                                <?php endif; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center text-muted py-3"><?= lang_line('scrs_no_out_history'); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-danger shadow-sm">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= lang_line('scrs_not_found'); ?>
        </div>
    <?php endif; ?>

</div>

<style>
.table th, .table td { vertical-align: middle !important; }
.badge-success { background-color: #28a745; }
.badge-warning { background-color: #ffc107; color: #212529; }
.badge-danger { background-color: #dc3545; }
.border-left-info { border-left: 4px solid #17a2b8 !important; }
.border-left-success { border-left: 4px solid #28a745 !important; }
.border-left-danger { border-left: 4px solid #dc3545 !important; }
.table-hover tbody tr:hover { background-color: #f6f8ff !important; transition: 0.2s; }
</style>
