<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container-fluid py-4">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-receipt me-2"></i><?= $title; ?>
        </h1>
        <div>
            <a href="<?= site_url('po_report'); ?>" class="btn btn-light border me-2">
                <i class="fas fa-arrow-left me-1"></i> <?= lang_line('po_report_back_to_list', 'Kembali'); ?>
            </a>
            <a href="<?= site_url('po_report/export_pdf/' . $po_header->id); ?>" class="btn btn-danger" target="_blank">
                <i class="fas fa-file-pdf me-1"></i> <?= lang_line('po_report_export_pdf', 'Export PDF'); ?>
            </a>
        </div>
    </div>

    <!-- PO Header Info -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="m-0 font-weight-bold"><?= lang_line('po_report_header_info', 'Informasi PO'); ?></h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_po_number', 'PO Number'); ?></label>
                    <p class="h6 font-weight-bold"><?= htmlspecialchars($po_header->po_number); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_po_date', 'Tanggal PO'); ?></label>
                    <p class="h6"><?= date('d/m/Y', strtotime($po_header->po_date)); ?></p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small"><?= lang_line('po_report_status', 'Status'); ?></label>
                    <p>
                        <span class="badge bg-<?= ($po_header->status == 'Open' ? 'warning' : 'success'); ?> fs-6">
                            <?= ucfirst($po_header->status); ?>
                        </span>
                    </p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Currency</label>
                    <p class="h6"><?= $po_header->currency_code ?? 'IDR'; ?></p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <label class="text-muted small"><?= lang_line('po_report_supplier_name', 'Supplier'); ?></label>
                    <p class="h6 font-weight-bold"><?= htmlspecialchars($po_header->nama_supplier); ?></p>
                    <p class="text-muted small"><?= htmlspecialchars($po_header->supplier_address ?? ''); ?></p>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small"><?= lang_line('po_report_remarks', 'Catatan'); ?></label>
                    <p><?= htmlspecialchars($po_header->remarks ?? '-'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- PO Line Items with Receipt Status -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="m-0 font-weight-bold"><?= lang_line('po_report_line_items', 'Detail Item PO & Status Penerimaan'); ?></h6>
        </div>
        <div class="card-body">
            <?php if (empty($po_details)): ?>
                <p class="text-muted text-center"><?= lang_line('po_report_no_items', 'Tidak ada item dalam PO ini'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th><?= lang_line('po_report_item_code', 'Kode Material'); ?></th>
                                <th><?= lang_line('po_report_item_name', 'Nama Material'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_ordered_qty', 'Qty PO'); ?></th>
                                <th><?= lang_line('po_report_unit', 'Satuan'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_unit_price', 'Harga Satuan'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_total', 'Total'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_received_qty', 'Qty Terima'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_outstanding_qty', 'Sisa'); ?></th>
                                <th><?= lang_line('po_report_status', 'Status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($po_details as $detail): ?>
                            <tr>
                                <td><?= htmlspecialchars($detail->kode_material ?? $detail->description ?? '-'); ?></td>
                                <td><?= htmlspecialchars($detail->nama_material ?? '-'); ?></td>
                                <td class="text-end"><strong><?= number_format($detail->qty, 2); ?></strong></td>
                                <td><?= htmlspecialchars($detail->satuan ?? ''); ?></td>
                                <td class="text-end">Rp <?= number_format($detail->base_price, 2); ?></td>
                                <td class="text-end">Rp <?= number_format($detail->line_total, 2); ?></td>
                                <td class="text-end">
                                    <strong><?= number_format($detail->qty_received, 2); ?></strong>
                                </td>
                                <td class="text-end">
                                    <strong class="<?= ($detail->qty_pending > 0 ? 'text-warning' : 'text-success'); ?>">
                                        <?= number_format($detail->qty_pending, 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php 
                                        $badge_class = 'secondary';
                                        if ($detail->item_receipt_status == 'Not Received') $badge_class = 'danger';
                                        elseif ($detail->item_receipt_status == 'Partial') $badge_class = 'warning';
                                        elseif ($detail->item_receipt_status == 'Complete') $badge_class = 'success';
                                    ?>
                                    <span class="badge bg-<?= $badge_class; ?>">
                                        <?= $detail->item_receipt_status; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="row mt-3">
                    <div class="col-md-6"></div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th class="text-end"><?= lang_line('po_report_total_ordered', 'Total Qty PO'); ?>:</th>
                                <td class="text-end"><strong><?php echo number_format(array_sum(array_column($po_details, 'qty')), 2); ?></strong></td>
                            </tr>
                            <tr>
                                <th class="text-end"><?= lang_line('po_report_total_received', 'Total Qty Terima'); ?>:</th>
                                <td class="text-end"><strong class="text-success"><?php echo number_format(array_sum(array_column($po_details, 'qty_received')), 2); ?></strong></td>
                            </tr>
                            <tr class="<?= (array_sum(array_column($po_details, 'qty_pending')) > 0 ? 'table-warning' : ''); ?>">
                                <th class="text-end"><?= lang_line('po_report_total_outstanding', 'Total Sisa'); ?>:</th>
                                <td class="text-end"><strong><?php echo number_format(array_sum(array_column($po_details, 'qty_pending')), 2); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- GRN List -->
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h6 class="m-0 font-weight-bold"><?= lang_line('po_report_receipt_history', 'Riwayat Penerimaan (GRN)'); ?></h6>
        </div>
        <div class="card-body">
            <?php if (empty($grns)): ?>
                <p class="text-muted text-center"><?= lang_line('po_report_no_receipts', 'Belum ada penerimaan untuk PO ini'); ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th><?= lang_line('po_report_grn_number', 'GRN Number'); ?></th>
                                <th><?= lang_line('po_report_grn_date', 'Tanggal'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_grn_items', 'Jumlah Item'); ?></th>
                                <th class="text-end"><?= lang_line('po_report_grn_qty', 'Total Qty'); ?></th>
                                <th><?= lang_line('po_report_status', 'Status'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($grns as $grn): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($grn->grn_number); ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($grn->grn_date)); ?></td>
                                <td class="text-end"><?= $grn->num_items; ?></td>
                                <td class="text-end"><strong><?= number_format($grn->total_qty_received, 2); ?></strong></td>
                                <td>
                                    <span class="badge bg-success">
                                        Completed
                                    </span>
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
