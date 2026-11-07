<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-industry text-primary mr-2"></i>
            <?= lang_line('work_order_title', $title); ?>
        </h1>
        <?php if(check_access('work_order', 'add')): ?>
        <button class="btn shadow-sm" style="background-color: var(--brand-1); color: white;" data-toggle="modal" data-target="#createWoModal">
            <i class="fas fa-plus fa-sm mr-1"></i> <?= lang_line('work_order_create'); ?>
        </button>
        <?php endif; ?>
    </div>

    <!-- Filter & Search Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3" style="background-color: var(--brand-1);">
            <h6 class="m-0 font-weight-bold text-white">
                <i class="fas fa-filter mr-2"></i> <?= lang_line('work_order_filter_title'); ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= base_url('work_order'); ?>" id="filterForm">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="search">
                                <i class="fas fa-search"></i> <?= lang_line('work_order_search_label'); ?>
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="search"
                                   name="search"
                                   placeholder="<?= lang_line('common_search_placeholder'); ?>"
                                   value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label>&nbsp;</label>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i> <?= lang_line('btn_search'); ?>
                            </button>
                            <a href="<?= base_url('work_order'); ?>" class="btn btn-secondary btn-block mt-2">
                                <i class="fas fa-redo mr-1"></i> <?= lang_line('btn_reset'); ?>
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
                <i class="fas fa-list mr-2"></i> <?= lang_line('work_order_list_title'); ?>
            </h6>
            <span class="badge badge-info badge-pill">
                <?= lang_line('work_order_total_label'); ?> <?= number_format($total_rows ?? 0); ?> <?= lang_line('common_data_count'); ?>
            </span>
        </div>
        <div class="card-body">
            <?php if (!empty($filters['search'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle mr-2"></i>
                <?= lang_line('work_order_search_results_label'); ?> <strong>"<?= htmlspecialchars($filters['search']); ?>"</strong>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th style="width: 5%;"><?= lang_line('label_no'); ?></th>
                            <th style="width: 15%;"><?= lang_line('work_order_number'); ?></th>
                            <th style="width: 15%;"><?= lang_line('work_order_sc_number'); ?></th>
                            <th style="width: 25%;"><?= lang_line('fg_name'); ?></th>
                            <th style="width: 10%;" class="text-right"><?= lang_line('work_order_qty_plan'); ?></th>
                            <th style="width: 12%;"><?= lang_line('work_order_start_date'); ?></th>
                            <th style="width: 10%;" class="text-center"><?= lang_line('label_status'); ?></th>
                            <th style="width: 8%;" class="text-center"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($wos)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted"><?= lang_line('work_order_no_data'); ?></p>
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
                                            <i class="fas fa-clock mr-1"></i> <?= lang_line('work_order_status_planned'); ?>
                                        </span>
                                    <?php elseif ($wo->status == 'In Progress'): ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-spinner mr-1"></i> <?= lang_line('work_order_status_in_progress'); ?>
                                        </span>
                                    <?php elseif ($wo->status == 'Finished'): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check-circle mr-1"></i> <?= lang_line('work_order_status_finished'); ?>
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
                                       title="<?= lang_line('btn_detail'); ?>">
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
                        <?= lang_line('work_order_pagination_label'); ?>
                        <strong><?= count($wos); ?></strong> <?= lang_line('from_text'); ?>
                        <strong><?= number_format($total_rows ?? 0); ?></strong> <?= lang_line('common_data_count'); ?>
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