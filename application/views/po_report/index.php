<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-list me-2"></i><?= $title; ?>
        </h1>
    </div>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= lang_line('po_report_stats_total', 'Total PO'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats->total_pos ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?= lang_line('po_report_not_received', 'Not Received'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats->not_received ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hourglass-half fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?= lang_line('po_report_stats_partial', 'Partial Received'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats->partial_received ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?= lang_line('po_report_total_grn', 'Total GRN'); ?></div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php 
                                $total_grns = 0;
                                foreach($pos as $po) { $total_grns += $po->num_grns; }
                                echo $total_grns;
                            ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('po_report_filter', 'Filter'); ?> & <?= lang_line('po_report_search', 'Cari'); ?></h6>
        </div>
        <div class="card-body">
            <?= form_open('po_report', ['method' => 'GET']) ?>
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="<?= lang_line('po_report_search', 'PO No / Supplier'); ?>" value="<?= $filters['search'] ?? ''; ?>">
                </div>
                <div class="col-md-3">
                    <select name="supplier_id" class="form-select">
                        <option value=""><?= lang_line('po_report_supplier_select', '-- Semua Supplier --'); ?></option>
                        <?php foreach($suppliers as $s): ?>
                            <option value="<?= $s->id; ?>" <?= ($filters['supplier_id'] == $s->id ? 'selected' : ''); ?>>
                                <?= htmlspecialchars($s->nama_supplier); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value=""><?= lang_line('po_report_status_select', '-- Status PO --'); ?></option>
                        <option value="Open" <?= ($filters['status'] == 'Open' ? 'selected' : ''); ?>>Open</option>
                        <option value="Closed" <?= ($filters['status'] == 'Closed' ? 'selected' : ''); ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="receipt_status" class="form-select">
                        <option value=""><?= lang_line('po_report_receipt_status_select', '-- Receipt Status --'); ?></option>
                        <option value="Not Received" <?= ($filters['receipt_status'] == 'Not Received' ? 'selected' : ''); ?>><?= lang_line('po_report_not_received', 'Not Received'); ?></option>
                        <option value="Partial" <?= ($filters['receipt_status'] == 'Partial' ? 'selected' : ''); ?>><?= lang_line('po_report_partial', 'Partial'); ?></option>
                        <option value="Complete" <?= ($filters['receipt_status'] == 'Complete' ? 'selected' : ''); ?>><?= lang_line('po_report_completed', 'Complete'); ?></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">-- Semua Tipe --</option>
                        <option value="produksi" <?= (($filters['type'] ?? '') == 'produksi' ? 'selected' : ''); ?>><?= lang_line('por_type_produksi'); ?></option>
                        <option value="non_produksi" <?= (($filters['type'] ?? '') == 'non_produksi' ? 'selected' : ''); ?>><?= lang_line('por_type_non_produksi'); ?></option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> <?= lang_line('po_report_search', 'Cari'); ?></button>
                </div>
            </div>
            <?= form_close(); ?>
        </div>
    </div>

    <!-- PO Table -->
    <div class="card shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('po_report_title', 'Daftar PO'); ?> & <?= lang_line('po_report_receipt_status', 'Status Penerimaan'); ?></h6>
        </div>
        <div class="card-body">
            <?php if (empty($pos)): ?>
                <p class="text-muted text-center"><?= lang_line('po_report_no_data', 'Tidak ada data PO ditemukan.'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th><?= lang_line('po_report_po_number', 'PO Number'); ?></th>
                                <th><?= lang_line('por_th_type'); ?></th>
                                <th><?= lang_line('po_report_pr_number', 'PR Number'); ?></th>
                                <th><?= lang_line('po_report_requester', 'Requester'); ?></th>
                                <th><?= lang_line('po_report_po_date', 'Tanggal'); ?></th>
                                <th><?= lang_line('po_report_supplier', 'Supplier'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_ordered_qty', 'Qty PO'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_received_qty', 'Qty Terima'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_outstanding', 'Sisa'); ?></th>
                                <th><?= lang_line('po_report_status', 'Status PO'); ?></th>
                                <th><?= lang_line('po_report_receipt_status', 'Receipt Status'); ?></th>
                                <th><?= lang_line('po_report_grn_number', 'GRN'); ?></th>
                                <th><?= lang_line('po_report_days_label', 'Days Information'); ?></th>
                                <th><?= lang_line('po_report_actions', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($pos as $po): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($po->po_number); ?></strong></td>
                                <td>
                                    <?php $tipe = $po->type ?? 'produksi'; ?>
                                    <span class="badge bg-<?= $tipe === 'non_produksi' ? 'secondary' : 'info'; ?>">
                                        <?= $tipe === 'non_produksi' ? 'Non Produksi' : 'Produksi'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($po->pr_number)): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($po->pr_number); ?></span>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($po->requester_name)): ?>
                                        <small><?= htmlspecialchars($po->requester_name); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($po->po_date)); ?></td>
                                <td><?= htmlspecialchars($po->nama_supplier ?? '-'); ?></td>
                                <td class="text-end"><?= number_format($po->total_po_qty, 2); ?></td>
                                <td class="text-end"><?= number_format($po->total_qty_received, 2); ?></td>
                                <td class="text-end">
                                    <strong><?= number_format($po->total_po_qty - $po->total_qty_received, 2); ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?= ($po->status == 'Open' ? 'warning' : 'success'); ?>">
                                        <?= ucfirst($po->status); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $badge_class = 'secondary';
                                        if ($po->receipt_status == 'Not Received') $badge_class = 'danger';
                                        elseif ($po->receipt_status == 'Partial') $badge_class = 'warning';
                                        elseif ($po->receipt_status == 'Complete') $badge_class = 'success';
                                    ?>
                                    <span class="badge bg-<?= $badge_class; ?>">
                                        <?= $po->receipt_status; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $po->num_grns; ?></span>
                                </td>
                                <td>
                                    <?php
                                        $days = $po->days_to_warehouse_in ?? 0;
                                        if ($po->first_grn_date) {
                                            echo '<span class="badge bg-success">' . $days . ' ' . lang_line('po_report_days_wh_in', 'hari (WH In)') . '</span>';
                                        } else {
                                            echo '<span class="badge bg-warning">' . $days . ' ' . lang_line('po_report_days_pending', 'hari (Pending)') . '</span>';
                                        }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?= site_url('po_report/detail/' . $po->id); ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> <?= lang_line('po_report_detail', 'Detail'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>
