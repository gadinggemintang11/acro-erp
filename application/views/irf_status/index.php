<?php
/**
 * IRF Status — daftar seluruh IRF dengan ringkasan qty diminta / released /
 * dalam proses / outstanding beserta progres pemenuhannya.
 */
$badge_status = [
    'Draft'              => 'secondary',
    'Submitted'          => 'info',
    'Approved Head Dept' => 'info',
    'Endorsed'           => 'primary',
    'Approved'           => 'success',
    'Released'           => 'primary',
    'Rejected'           => 'danger',
    'Cancelled'          => 'dark',
];
$base_index_url = 'irf_status' . ($locked_category ? '/index/' . strtolower($category_slug) : '');
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-tasks text-primary mr-2"></i>
                <?= lang_line('irfs_title', 'Status Permintaan Barang'); ?>
                <?php if ($locked_category): ?>
                    <span class="badge badge-<?= $locked_category === 'Produksi' ? 'dark' : 'secondary' ?> align-middle"><?= htmlspecialchars($locked_category) ?></span>
                <?php endif; ?>
            </h3>
            <small class="text-muted"><?= lang_line('irfs_subtitle', 'Status pemenuhan tiap nomor Permintaan Barang: diminta, sudah di-release, dalam proses, dan outstanding.'); ?></small>
        </div>
        <a href="<?= base_url('irf_status/export_csv?' . http_build_query(array_filter($filters))) ?>"
           class="btn btn-sm btn-outline-success">
            <i class="fas fa-file-csv mr-1"></i> <?= lang_line('irfs_btn_export', 'Export CSV'); ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $this->session->flashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Ringkasan -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= lang_line('irfs_card_total_irf', 'Total Permintaan Barang'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format((int)$summary->irf_count) ?></div>
                            <div class="small text-muted">
                                <?= number_format((float)$summary->qty_requested, 2) ?> <?= lang_line('irfs_unit_qty', 'qty diminta'); ?>
                            </div>
                        </div>
                        <div class="col-auto"><i class="fas fa-clipboard-list fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?= lang_line('irfs_card_complete', 'Permintaan Barang Selesai'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format((int)$summary->irf_complete) ?></div>
                            <div class="small text-muted">
                                <?= number_format((float)$summary->qty_released, 2) ?> <?= lang_line('irfs_unit_released', 'qty released'); ?>
                            </div>
                        </div>
                        <div class="col-auto"><i class="fas fa-check-double fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?= lang_line('irfs_card_partial', 'Sebagian / Belum Diproses'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format((int)$summary->irf_partial) ?> / <?= number_format((int)$summary->irf_not_started) ?>
                            </div>
                            <div class="small text-muted"><?= lang_line('irfs_card_partial_hint', 'sebagian / belum sama sekali'); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-hourglass-half fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?= lang_line('irfs_card_outstanding', 'Total Outstanding'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format((float)$summary->qty_outstanding, 2) ?></div>
                            <div class="small text-muted">
                                <?= number_format((float)$summary->qty_pending, 2) ?> <?= lang_line('irfs_unit_pending', 'qty dalam proses'); ?>
                            </div>
                        </div>
                        <div class="col-auto"><i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap">
                <div class="form-group mr-2 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm" style="min-width: 220px;"
                           placeholder="<?= htmlspecialchars(lang_line('irfs_search_ph', 'No Permintaan Barang / dept / requestor / item...')); ?>"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="department" class="form-control form-control-sm">
                        <option value=""><?= lang_line('irfs_all_departments', 'Semua Departemen'); ?></option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= htmlspecialchars($d->department) ?>" <?= (($filters['department'] ?? '') === $d->department) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d->department) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value=""><?= lang_line('irfs_all_status', 'Semua Status'); ?></option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= htmlspecialchars($s->status) ?>" <?= (($filters['status'] ?? '') === $s->status) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s->status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!$locked_category && count($categories) > 1): ?>
                <div class="form-group mr-2 mb-1">
                    <select name="category" class="form-control form-control-sm">
                        <option value=""><?= lang_line('common_all'); ?> <?= lang_line('cr_category', 'Kategori'); ?></option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= htmlspecialchars($c) ?>" <?= (($filters['category'] ?? '') === $c) ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group mr-2 mb-1">
                    <?php $ff = $filters['fulfillment'] ?? ''; ?>
                    <select name="fulfillment" class="form-control form-control-sm">
                        <option value=""><?= lang_line('irfs_all_progress', 'Semua Progres'); ?></option>
                        <option value="outstanding" <?= $ff === 'outstanding' ? 'selected' : '' ?>><?= lang_line('irfs_ff_outstanding', 'Masih Outstanding'); ?></option>
                        <option value="not_started" <?= $ff === 'not_started' ? 'selected' : '' ?>><?= lang_line('irfs_ff_not_started', 'Belum Diproses'); ?></option>
                        <option value="partial"     <?= $ff === 'partial' ? 'selected' : '' ?>><?= lang_line('irfs_ff_partial', 'Sebagian Released'); ?></option>
                        <option value="complete"    <?= $ff === 'complete' ? 'selected' : '' ?>><?= lang_line('irfs_ff_complete', 'Selesai Penuh'); ?></option>
                    </select>
                </div>
                <div class="form-group mr-2 mb-1">
                    <input type="date" name="date_from" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>"
                           title="<?= lang_line('irfs_date_from', 'Tanggal Permintaan Barang dari'); ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <input type="date" name="date_to" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>"
                           title="<?= lang_line('irfs_date_to', 'Tanggal Permintaan Barang sampai'); ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1">
                    <i class="fas fa-search"></i> <?= lang_line('irfs_btn_filter', 'Filter'); ?>
                </button>
                <a href="<?= base_url($base_index_url) ?>" class="btn btn-sm btn-secondary mb-1"><?= lang_line('irfs_btn_reset', 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="irfStatusTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('irfs_th_number', 'No Permintaan Barang'); ?></th>
                            <th><?= lang_line('irfs_th_date', 'Tanggal'); ?></th>
                            <th><?= lang_line('irfs_th_department', 'Departemen'); ?></th>
                            <th><?= lang_line('irfs_th_requestor', 'Requestor'); ?></th>
                            <th class="text-center"><?= lang_line('irfs_th_status', 'Status'); ?></th>
                            <th class="text-center"><?= lang_line('irfs_th_lines', 'Item'); ?></th>
                            <th class="text-right"><?= lang_line('irfs_th_requested', 'Diminta'); ?></th>
                            <th class="text-right"><?= lang_line('irfs_th_released', 'Released'); ?></th>
                            <th class="text-right"><?= lang_line('irfs_th_pending', 'Proses'); ?></th>
                            <th class="text-right"><?= lang_line('irfs_th_outstanding', 'Outstanding'); ?></th>
                            <th class="text-center" style="min-width:130px;"><?= lang_line('irfs_th_progress', 'Progres'); ?></th>
                            <th class="text-center"><?= lang_line('irfs_th_age', 'Umur'); ?></th>
                            <th class="text-center" width="70"><?= lang_line('irfs_th_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r):
                            $req  = (float)$r->total_requested;
                            $rel  = (float)$r->total_released;
                            $pen  = (float)$r->total_pending;
                            $out  = (float)$r->total_outstanding;
                            $prog = $req > 0 ? min(($rel / $req) * 100, 100) : 0;
                            $bar  = $out <= 0 ? 'success' : ($rel > 0 ? 'info' : 'warning');
                        ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('irf_status/detail/' . (int)$r->id) ?>" class="font-weight-bold text-primary">
                                    <?= htmlspecialchars($r->request_number) ?>
                                </a>
                            </td>
                            <td data-order="<?= htmlspecialchars($r->request_date) ?>"><?= date('d M Y', strtotime($r->request_date)) ?></td>
                            <td><?= htmlspecialchars($r->department ?: '-') ?></td>
                            <td><?= htmlspecialchars($r->requestor_name ?: ($r->created_by_name ?: '-')) ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?= $badge_status[$r->status] ?? 'secondary' ?>"><?= htmlspecialchars($r->status) ?></span>
                            </td>
                            <td class="text-center" data-order="<?= (int)$r->total_lines ?>">
                                <span class="text-muted"><?= (int)$r->lines_complete ?>/<?= (int)$r->total_lines ?></span>
                            </td>
                            <td class="text-right"><?= number_format($req, 2) ?></td>
                            <td class="text-right text-success"><?= number_format($rel, 2) ?></td>
                            <td class="text-right"><?= $pen > 0 ? '<span class="text-info">' . number_format($pen, 2) . '</span>' : '<span class="text-muted">-</span>' ?></td>
                            <td class="text-right">
                                <?php if ($out > 0): ?>
                                    <strong class="text-warning"><?= number_format($out, 2) ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-order="<?= round($prog, 2) ?>">
                                <div class="progress" style="height: 14px;">
                                    <div class="progress-bar bg-<?= $bar ?>" role="progressbar"
                                         style="width: <?= round($prog, 1) ?>%;"
                                         aria-valuenow="<?= round($prog, 1) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted"><?= number_format($prog, 1) ?>%</small>
                            </td>
                            <td class="text-center" data-order="<?= (int)($r->outstanding_age_days ?? $r->age_days) ?>">
                                <?php if ($out > 0 && $r->outstanding_age_days !== null):
                                    $ageDays = (int)$r->outstanding_age_days;
                                    $ageCls  = $ageDays > 30 ? 'danger' : ($ageDays > 14 ? 'warning' : 'secondary');
                                ?>
                                    <span class="badge badge-<?= $ageCls ?>"><?= $ageDays ?> <?= lang_line('irfs_days', 'hari'); ?></span>
                                <?php else: ?>
                                    <span class="text-muted small"><?= (int)$r->age_days ?> <?= lang_line('irfs_days', 'hari'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('irf_status/detail/' . (int)$r->id) ?>"
                                   class="btn btn-sm btn-outline-primary" title="<?= lang_line('irfs_view_detail', 'Lihat detail status Permintaan Barang'); ?>">
                                    <i class="fas fa-chart-bar"></i>
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

<script>
$(document).ready(function () {
    if ($('#irfStatusTable').length) {
        $('#irfStatusTable').DataTable({
            order: [[1, 'desc']],
            pageLength: 25,
            columnDefs: [{ orderable: false, targets: [12] }]
        });
    }
});
</script>
