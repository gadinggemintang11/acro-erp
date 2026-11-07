<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div id="content">
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><?= lang_line('qa_report_title', 'QA Report - Supplier Rejection Summary'); ?></h1>
        </div>

        <!-- Filters Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 text-white" style="background-color: var(--brand-1);">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-filter"></i> <?= lang_line('qa_report_filter_data', 'Filter Data'); ?>
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= base_url('quality_assurance/qa_report'); ?>" class="form-inline">
                    <div class="form-group mr-3 mb-2">
                        <label for="date_from" class="mr-2"><?= lang_line('qa_report_from_date', 'From Date:'); ?></label>
                        <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?= $filters['date_from'] ?? ''; ?>">
                    </div>

                    <div class="form-group mr-3 mb-2">
                        <label for="date_to" class="mr-2"><?= lang_line('qa_report_to_date', 'To Date:'); ?></label>
                        <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?= $filters['date_to'] ?? ''; ?>">
                    </div>

                    <div class="form-group mr-3 mb-2">
                        <label for="supplier_id" class="mr-2"><?= lang_line('qa_report_supplier_label', 'Supplier:'); ?></label>
                        <select class="form-control form-control-sm" id="supplier_id" name="supplier_id">
                            <option value=""><?= lang_line('qa_report_all_suppliers', '-- All Suppliers --'); ?></option>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?= $supplier->id; ?>" <?= ($filters['supplier_id'] == $supplier->id) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($supplier->nama_supplier); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mr-3 mb-2">
                        <label for="qa_status" class="mr-2"><?= lang_line('qa_report_qa_status_label', 'QA Status:'); ?></label>
                        <select class="form-control form-control-sm" id="qa_status" name="qa_status">
                            <option value=""><?= lang_line('qa_report_all_status', '-- All Status --'); ?></option>
                            <?php foreach ($qa_statuses as $status): ?>
                                <option value="<?= $status; ?>" <?= ($filters['qa_status'] == $status) ? 'selected' : ''; ?>>
                                    <?= $status; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group mr-3 mb-2">
                        <label for="min_reject_percentage" class="mr-2"><?= lang_line('qa_report_min_reject_pct', 'Min Reject %:'); ?></label>
                        <input type="number" class="form-control form-control-sm" id="min_reject_percentage" name="min_reject_percentage"
                               value="<?= $filters['min_reject_percentage'] ?? ''; ?>" min="0" max="100" step="0.01" style="width: 80px;">
                    </div>

                    <button type="submit" class="btn btn-sm mr-2" style="background-color: var(--brand-1); color: white;">
                        <i class="fas fa-search"></i> <?= lang_line('qa_report_filter_button', 'Filter'); ?>
                    </button>
                    <a href="<?= base_url('quality_assurance/qa_report'); ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-redo"></i> <?= lang_line('qa_report_reset_button', 'Reset'); ?>
                    </a>
                </form>
            </div>
        </div>

        <!-- Report Table -->
        <div class="card shadow">
            <div class="card-header py-3 text-white d-flex justify-content-between align-items-center" style="background-color: var(--brand-1);">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-chart-bar"></i> <?= lang_line('qa_report_table_title', 'Supplier Rejection Report'); ?>
                </h6>
                <span class="badge badge-light"><?= count($supplier_rejection_data); ?> <?= lang_line('qa_report_supplier_count', 'Supplier(s)'); ?></span>
            </div>

            <div class="card-body">
                <?php if (empty($supplier_rejection_data)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <?= lang_line('qa_report_no_data', 'No data matching the filter criteria.'); ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped" id="qaReportTable">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 5%"><?= lang_line('qa_report_table_no', 'No.'); ?></th>
                                    <th><?= lang_line('qa_report_table_supplier_name', 'Supplier Name'); ?></th>
                                    <th style="text-align: center;"><?= lang_line('qa_report_table_total_qa', 'Total QA'); ?></th>
                                    <th style="text-align: center;"><?= lang_line('qa_report_table_total_items', 'Total Items'); ?></th>
                                    <th style="text-align: center; color: #c0392b;"><?= lang_line('qa_report_table_reject_items', 'Reject Items'); ?></th>
                                    <th style="text-align: right;"><?= lang_line('qa_report_table_reject_qty', 'Reject Qty'); ?></th>
                                    <th style="text-align: center; color: #e74c3c;"><?= lang_line('qa_report_table_reject_pct', 'Reject %'); ?></th>
                                    <th style="text-align: center; width: 15%;"><?= lang_line('qa_report_table_action', 'Action'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($supplier_rejection_data as $supplier): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($supplier->supplier_name ?? 'Unknown'); ?></strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge badge-info"><?= $supplier->total_qa ?? 0; ?></span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?= $supplier->total_items ?? 0; ?>
                                        </td>
                                        <td style="text-align: center; color: #c0392b; font-weight: bold;">
                                            <?= $supplier->total_reject_items ?? 0; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <strong><?= number_format($supplier->total_reject_qty ?? 0, 2); ?></strong>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="badge" style="background-color: <?= (($supplier->reject_percentage ?? 0) > 10) ? '#e74c3c' : '#f39c12'; ?>; color: white;">
                                                <?= $supplier->reject_percentage ?? 0; ?>%
                                            </span>
                                        </td>
                                        <td style="text-align: center;">
                                            <?php if ($supplier->supplier_id > 0): ?>
                                                <a href="<?= base_url('quality_assurance/qa_report_detail/' . $supplier->supplier_id); ?>"
                                                   class="btn btn-sm btn-info" title="<?= lang_line('qa_report_view_details', 'View Details'); ?>">
                                                    <i class="fas fa-eye"></i> <?= lang_line('qa_report_view_button', 'View'); ?>
                                                </a>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-secondary" disabled title="<?= lang_line('qa_report_supplier_unknown', 'Supplier unknown'); ?>">
                                                    <i class="fas fa-ban"></i> <?= lang_line('qa_report_view_button', 'View'); ?>
                                                </button>
                                                <br><small class="text-muted"><?= lang_line('qa_report_supplier_unknown', 'Supplier unknown'); ?></small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Statistics -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card border-left-info shadow">
                                <div class="card-body">
                                    <div class="text-info font-weight-bold text-uppercase mb-1"><?= lang_line('qa_report_summary_total_suppliers', 'Total Suppliers'); ?></div>
                                    <div class="h3 mb-0"><?= count($supplier_rejection_data); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-left-warning shadow">
                                <div class="card-body">
                                    <div class="text-warning font-weight-bold text-uppercase mb-1"><?= lang_line('qa_report_summary_total_qa', 'Total QA Records'); ?></div>
                                    <div class="h3 mb-0"><?= array_sum(array_map(function($x) { return $x->total_qa ?? 0; }, $supplier_rejection_data)); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-left-danger shadow">
                                <div class="card-body">
                                    <div class="text-danger font-weight-bold text-uppercase mb-1"><?= lang_line('qa_report_summary_total_reject_items', 'Total Reject Items'); ?></div>
                                    <div class="h3 mb-0"><?= array_sum(array_map(function($x) { return $x->total_reject_items ?? 0; }, $supplier_rejection_data)); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-left-success shadow">
                                <div class="card-body">
                                    <div class="text-success font-weight-bold text-uppercase mb-1"><?= lang_line('qa_report_summary_avg_reject_pct', 'Avg Reject %'); ?></div>
                                    <div class="h3 mb-0">
                                        <?php
                                            $avg_reject = 0;
                                            if (!empty($supplier_rejection_data)) {
                                                $avg_reject = array_sum(array_map(function($x) { return $x->reject_percentage ?? 0; }, $supplier_rejection_data)) / count($supplier_rejection_data);
                                            }
                                            echo number_format($avg_reject, 2) . '%';
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
    .badge-light {
        background-color: #e9ecef !important;
        color: #333 !important;
    }
</style>
