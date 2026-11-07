<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div id="content">
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800">
                    <a href="<?= base_url('quality_assurance/qa_report'); ?>" class="btn btn-sm btn-secondary mr-2">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <?= lang_line('qa_report_detail_title', 'Reject Items Detail'); ?> - <?= htmlspecialchars($supplier->nama_supplier); ?>
                </h1>
            </div>
        </div>

        <!-- Supplier Info Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-info text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-building"></i> <?= lang_line('qa_report_detail_supplier_info', 'Supplier Information'); ?>
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0"><strong><?= lang_line('qa_report_detail_supplier_name', 'Supplier Name:'); ?></strong> <?= htmlspecialchars($supplier->nama_supplier); ?></p>
                        <p class="mb-0"><strong><?= lang_line('qa_report_detail_supplier_id', 'Supplier ID:'); ?></strong> <?= $supplier->id; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-0"><strong><?= lang_line('qa_report_detail_total_reject_items', 'Total Reject Items:'); ?></strong> <span class="badge badge-danger"><?= count($reject_items); ?></span></p>
                        <?php
                            $total_reject_qty = array_sum(array_map(function($x) { return $x->qa_reject_qty ?? 0; }, $reject_items));
                        ?>
                        <p class="mb-0"><strong><?= lang_line('qa_report_detail_total_reject_qty', 'Total Reject Qty:'); ?></strong> <span class="badge badge-warning"><?= number_format($total_reject_qty, 2); ?></span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-filter"></i> <?= lang_line('qa_report_filter_data', 'Filter Data'); ?>
                </h6>
            </div>
            <div class="card-body">
                <form method="GET" action="<?= base_url('quality_assurance/qa_report_detail/' . $supplier->id); ?>" class="form-inline">
                    <div class="form-group mr-3 mb-2">
                        <label for="date_from" class="mr-2"><?= lang_line('qa_report_from_date', 'From Date:'); ?></label>
                        <input type="date" class="form-control form-control-sm" id="date_from" name="date_from" value="<?= $filters['date_from'] ?? ''; ?>">
                    </div>

                    <div class="form-group mr-3 mb-2">
                        <label for="date_to" class="mr-2"><?= lang_line('qa_report_to_date', 'To Date:'); ?></label>
                        <input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?= $filters['date_to'] ?? ''; ?>">
                    </div>

                    <div class="form-group mr-3 mb-2">
                        <label for="search" class="mr-2"><?= lang_line('quality_assurance_search', 'Search:'); ?></label>
                        <input type="text" class="form-control form-control-sm" id="search" name="search" placeholder="QA/GRN Number"
                               value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-sm btn-primary mr-2">
                        <i class="fas fa-search"></i> <?= lang_line('qa_report_filter_button', 'Filter'); ?>
                    </button>
                    <a href="<?= base_url('quality_assurance/qa_report_detail/' . $supplier->id); ?>" class="btn btn-sm btn-secondary">
                        <i class="fas fa-redo"></i> <?= lang_line('qa_report_reset_button', 'Reset'); ?>
                    </a>
                </form>
            </div>
        </div>

        <!-- Reject Items Table -->
        <div class="card shadow">
            <div class="card-header py-3 bg-danger text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-exclamation-triangle"></i> <?= lang_line('qa_report_detail_rejected_items', 'Rejected Items Detail'); ?>
                </h6>
            </div>

            <div class="card-body">
                <?php if (empty($reject_items)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?= lang_line('qa_report_detail_no_items', 'No items rejected from this supplier in the selected period.'); ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped table-sm" id="rejectItemsTable">
                            <thead class="bg-light">
                                <tr>
                                    <th style="width: 3%"><?= lang_line('qa_report_detail_table_no', 'No.'); ?></th>
                                    <th style="width: 11%"><?= lang_line('qa_report_detail_table_qa_number', 'QA Number'); ?></th>
                                    <th style="width: 11%"><?= lang_line('qa_report_detail_table_grn_number', 'GRN Number'); ?></th>
                                    <th style="width: 10%"><?= lang_line('qa_report_detail_table_po_mir', 'PO/MIR Number'); ?></th>
                                    <th style="width: 9%"><?= lang_line('qa_report_detail_table_qa_date', 'QA Date'); ?></th>
                                    <th style="width: 15%"><?= lang_line('qa_report_detail_table_item_code', 'Item Code'); ?></th>
                                    <th style="width: 18%"><?= lang_line('qa_report_detail_table_item_name', 'Item Name'); ?></th>
                                    <th style="text-align: center; width: 7%"><?= lang_line('qa_report_detail_table_receive_qty', 'Receive Qty'); ?></th>
                                    <th style="text-align: center; width: 7%; color: #c0392b;"><?= lang_line('qa_report_detail_table_reject_qty', 'Reject Qty'); ?></th>
                                    <th style="width: 18%"><?= lang_line('qa_report_detail_table_reject_reason', 'Reject Reason'); ?></th>
                                    <th style="text-align: center; width: 8%"><?= lang_line('qa_report_detail_table_action', 'Action'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($reject_items as $item): ?>
                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($item->qa_number); ?></strong>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($item->grn_number); ?></strong>
                                        </td>
                                        <td>
                                            <strong style="color: #2c3e50;">
                                                <?= htmlspecialchars($item->source_doc_number ?? '-'); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y', strtotime($item->qa_date)); ?></small>
                                        </td>
                                        <td>
                                            <code><?= htmlspecialchars($item->item_code ?? '-'); ?></code>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($item->item_name ?? '-'); ?></small>
                                        </td>
                                        <td style="text-align: center;">
                                            <?= number_format($item->receive_qty, 2); ?>
                                        </td>
                                        <td style="text-align: center; color: #c0392b; font-weight: bold;">
                                            <?= number_format($item->qa_reject_qty, 2); ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?php if (!empty($item->reject_reason)): ?>
                                                    <strong><?= htmlspecialchars($item->reject_reason); ?></strong><br>
                                                <?php endif; ?>
                                                <?php if (!empty($item->reject_description)): ?>
                                                    <em><?= htmlspecialchars(substr($item->reject_description, 0, 40)); ?><?= (strlen($item->reject_description) > 40) ? '...' : ''; ?></em>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td style="text-align: center;">
                                            <a href="<?= base_url('quality_assurance/detail/' . $item->qa_header_id); ?>"
                                               class="btn btn-sm btn-info" title="<?= lang_line('qa_report_detail_view_qa', 'View QA Detail'); ?>">
                                                <i class="fas fa-eye"></i> <?= lang_line('qa_report_detail_view_button', 'Detail'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card border-left-danger shadow">
                                <div class="card-body">
                                    <div class="text-danger font-weight-bold text-uppercase mb-1"><?= lang_line('qa_report_detail_summary_total_reject_items', 'Total Reject Items'); ?></div>
                                    <div class="h3 mb-0"><?= count($reject_items); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-left-warning shadow">
                                <div class="card-body">
                                    <div class="text-warning font-weight-bold text-uppercase mb-1"><?= lang_line('qa_report_detail_summary_total_reject_qty', 'Total Reject Qty'); ?></div>
                                    <div class="h3 mb-0"><?= number_format($total_reject_qty, 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
    $(document).ready(function() {
        $('#rejectItemsTable').DataTable({
            "order": [[3, "desc"]],
            "columnDefs": [
                { "orderable": false, "targets": 9 }
            ]
        });
    });
</script>
