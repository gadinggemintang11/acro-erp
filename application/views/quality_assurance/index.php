<?php
/**
 * Quality Assurance - List View
 * Menampilkan daftar semua QA yang ada
 */
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0"><?= lang_line('quality_assurance_title'); ?></h1>
            <small class="text-muted"><?= lang_line('quality_assurance_subtitle'); ?></small>
        </div>
        <div class="col-md-4 text-right">
            <a href="<?php echo base_url('warehouse_in'); ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-arrow-left"></i> <?= lang_line('quality_assurance_back_to_warehouse'); ?>
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label for="search" class="mr-2"><?= lang_line('quality_assurance_search'); ?></label>
                    <input type="text" class="form-control form-control-sm" id="search" name="search"
                           placeholder="<?= lang_line('quality_assurance_search_placeholder'); ?>" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mr-3">
                    <label for="status" class="mr-2"><?= lang_line('quality_assurance_status'); ?></label>
                    <select class="form-control form-control-sm" id="status" name="status">
                        <option value=""><?= lang_line('quality_assurance_all_status'); ?></option>
                        <?php foreach ($header_statuses as $status): ?>
                            <option value="<?php echo $status; ?>"
                                    <?php echo ($filters['status'] === $status) ? 'selected' : ''; ?>>
                                <?php echo $status; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mr-3">
                    <label for="qa_status" class="mr-2"><?= lang_line('quality_assurance_qa_status'); ?></label>
                    <select class="form-control form-control-sm" id="qa_status" name="qa_status">
                        <option value=""><?= lang_line('quality_assurance_all_qa_status'); ?></option>
                        <?php foreach ($qa_statuses as $qa_st): ?>
                            <option value="<?php echo $qa_st; ?>"
                                    <?php echo ($filters['qa_status'] === $qa_st) ? 'selected' : ''; ?>>
                                <?php echo $qa_st; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="fas fa-search"></i> <?= lang_line('btn_filter'); ?>
                </button>
                <a href="<?php echo base_url('quality_assurance'); ?>" class="btn btn-sm btn-secondary ml-2">
                    <?= lang_line('btn_reset'); ?>
                </a>
            </form>
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-muted"><?= lang_line('quality_assurance_total', 'Total QA'); ?></h5>
                    <h2 class="text-primary"><?php echo $total; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-muted"><?= lang_line('quality_assurance_pending', 'Pending'); ?></h5>
                    <h2 class="text-warning">
                        <?php
                        $pending_count = 0;
                        foreach ($qa_headers as $qa) {
                            if ($qa->status === 'Pending') $pending_count++;
                        }
                        echo $pending_count;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-muted"><?= lang_line('quality_assurance_in_progress', 'In Progress'); ?></h5>
                    <h2 class="text-info">
                        <?php
                        $in_progress_count = 0;
                        foreach ($qa_headers as $qa) {
                            if ($qa->status === 'In Progress') $in_progress_count++;
                        }
                        echo $in_progress_count;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="text-muted"><?= lang_line('quality_assurance_completed', 'Completed'); ?></h5>
                    <h2 class="text-success">
                        <?php
                        $completed_count = 0;
                        foreach ($qa_headers as $qa) {
                            if ($qa->status === 'Completed') $completed_count++;
                        }
                        echo $completed_count;
                        ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- QA List Table -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0"><?= lang_line('quality_assurance_list_title', 'Quality Assurance List'); ?></h5>
                </div>
                <div class="col-md-6 text-right">
                    <?php
                    $export_params = http_build_query(array_filter([
                        'status'    => $filters['status'] ?? '',
                        'qa_status' => $filters['qa_status'] ?? '',
                        'search'    => $filters['search'] ?? '',
                    ]));
                    ?>
                    <a href="<?php echo base_url('quality_assurance/export_list_excel?' . $export_params); ?>"
                       class="btn btn-sm btn-success" title="<?= lang_line('quality_assurance_export_excel', 'Export to Excel'); ?>">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="<?php echo base_url('quality_assurance/export_list_pdf?' . $export_params); ?>"
                       class="btn btn-sm btn-danger" title="<?= lang_line('quality_assurance_export_pdf', 'Export to PDF'); ?>">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th><?= lang_line('quality_assurance_table_qa_number', 'QA #'); ?></th>
                        <th><?= lang_line('quality_assurance_table_grn_number', 'GRN #'); ?></th>
                        <th><?= lang_line('quality_assurance_table_supplier', 'Supplier'); ?></th>
                        <th><?= lang_line('quality_assurance_table_qa_date', 'QA Date'); ?></th>
                        <th><?= lang_line('quality_assurance_table_status', 'Status'); ?></th>
                        <th><?= lang_line('quality_assurance_table_qa_status', 'QA Status'); ?></th>
                        <th><?= lang_line('quality_assurance_table_items', 'Items'); ?></th>
                        <th><?= lang_line('quality_assurance_table_pass_reject', 'Pass / Reject'); ?></th>
                        <th><?= lang_line('quality_assurance_table_action', 'Action'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($qa_headers)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-inbox text-muted"></i> <?= lang_line('quality_assurance_no_data', 'No Quality Assurance data'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($qa_headers as $qa): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($qa->qa_number); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($qa->grn_number); ?></td>
                                <td><?php echo htmlspecialchars($qa->nama_supplier ?? '-'); ?></td>
                                <td><?php echo date('d-m-Y', strtotime($qa->qa_date)); ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'Pending' => 'secondary',
                                        'In Progress' => 'info',
                                        'Draft' => 'warning',
                                        'Completed' => 'success',
                                        'Rejected' => 'danger'
                                    ];
                                    $badge_class = $status_class[$qa->status] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($qa->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    // Get QA status dari GRN header
                                    $qa_status_class = [
                                        'Not Started' => 'secondary',
                                        'In Progress' => 'info',
                                        'Passed' => 'success',
                                        'Partial Passed' => 'warning',
                                        'Rejected' => 'danger'
                                    ];
                                    // Tentukan dari tabel grn_header (lihat detail untuk akses qa_status)
                                    ?>
                                    <span class="badge badge-light" title="<?= htmlspecialchars(lang_line('qa_tooltip_qc_status')); ?>">
                                        <i class="fas fa-info-circle"></i>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-pill badge-light">
                                        <?php echo ($qa->total_items ?? 0); ?> <?= lang_line('quality_assurance_items_count', 'items'); ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <span class="text-success">✓ <?php echo ($qa->pass_items ?? 0); ?></span><br/>
                                        <span class="text-muted" style="font-size: 0.85em;"><?php echo number_format(($qa->pass_qty ?? 0), 2); ?> qty</span>
                                    </small>
                                    <br/>
                                    <small>
                                        <span class="text-danger">✗ <?php echo ($qa->reject_items ?? 0); ?></span><br/>
                                        <span class="text-muted" style="font-size: 0.85em;"><?php echo number_format(($qa->reject_qty ?? 0), 2); ?> qty</span>
                                    </small>
                                </td>
                                <td>
                                    <a href="<?php echo base_url('quality_assurance/detail/' . $qa->id); ?>"
                                       class="btn btn-sm btn-outline-primary" title="<?= lang_line('quality_assurance_detail_view', 'View Detail'); ?>">
                                        <i class="fas fa-eye"></i> <?= lang_line('quality_assurance_detail_view', 'View Detail'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav class="mt-4" aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($current_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo current_url() . '?page=1'; ?>"><?= lang_line('quality_assurance_pagination_first', 'First'); ?></a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo current_url() . '?page=' . ($current_page - 1); ?>"><?= lang_line('quality_assurance_pagination_previous', 'Previous'); ?></a>
                    </li>
                <?php endif; ?>

                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                    <li class="page-item <?php echo ($i === $current_page) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo current_url() . '?page=' . $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo current_url() . '?page=' . ($current_page + 1); ?>"><?= lang_line('quality_assurance_pagination_next', 'Next'); ?></a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="<?php echo current_url() . '?page=' . $total_pages; ?>"><?= lang_line('quality_assurance_pagination_last', 'Last'); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php $this->load->view('templates/footer'); ?>
