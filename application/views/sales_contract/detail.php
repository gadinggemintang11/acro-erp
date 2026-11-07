<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-file-signature text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <a href="<?= base_url($back_url); ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('sc_back', 'Kembali'); ?>
        </a>
    </div>

    <?php
        $status_class = 'secondary';
        if (in_array($contract->status, ['Submitted', 'Pending Endorsement'], true)) $status_class = 'warning';
        elseif ($contract->status == 'Endorsed')        $status_class = 'info';
        elseif ($contract->status == 'Open')            $status_class = 'primary';
        elseif ($contract->status == 'Finish')          $status_class = 'success';
        elseif ($contract->status == 'Closed')          $status_class = 'dark';
        elseif ($contract->status == 'Cancelled')       $status_class = 'danger';
        elseif ($contract->status == 'Rejected')        $status_class = 'danger';
    ?>

    <div class="row">
        <!-- Kolom kiri: data Sales Order -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded mb-4">
                <div class="card-header bg-white border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-primary"><i class="fas fa-info-circle mr-2"></i><?= lang_line('sc_ipo_information', 'Informasi Sales Order'); ?></h5>
                    <span class="badge badge-<?= $status_class; ?> p-2"><?= get_translated_status($contract->status); ?></span>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm mb-0">
                        <tbody>
                            <tr>
                                <th style="width: 35%;"><?= lang_line('sc_ipo_number_label', 'No. Sales Order'); ?></th>
                                <td class="font-weight-bold text-primary"><?= htmlspecialchars($contract->sc_number); ?></td>
                            </tr>
                            <tr>
                                <th><?= lang_line('sc_customer_label', 'Pelanggan'); ?></th>
                                <td><?= htmlspecialchars($contract->nama_customer ?: '-'); ?></td>
                            </tr>
                            <?php if (!empty($contract->customer_address)): ?>
                            <tr>
                                <th><?= lang_line('sc_customer_address', 'Alamat Pelanggan'); ?></th>
                                <td><?= nl2br(htmlspecialchars($contract->customer_address)); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th><?= lang_line('sc_style_label', 'Style'); ?></th>
                                <td>
                                    <?= htmlspecialchars($contract->style_name ?: '-'); ?>
                                    <?php if (!empty($contract->style_code)): ?>
                                        <small class="text-muted">(<?= htmlspecialchars($contract->style_code); ?>)</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?= lang_line('sc_contract_date_label', 'Tanggal Kontrak'); ?></th>
                                <td><?= $contract->contract_date ? date('d M Y', strtotime($contract->contract_date)) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th><?= lang_line('sc_lchd_date_label', 'LCHD Date'); ?></th>
                                <td><?= $contract->lchd_date ? date('d M Y', strtotime($contract->lchd_date)) : '-'; ?></td>
                            </tr>
                            <tr>
                                <th><?= lang_line('sc_po_qty_label', 'PO Qty'); ?></th>
                                <td><?= number_format($contract->po_qty); ?></td>
                            </tr>
                            <tr>
                                <th><?= lang_line('sc_currency_label', 'Currency'); ?></th>
                                <td><?= htmlspecialchars($contract->currency_code); ?></td>
                            </tr>
                            <tr>
                                <th><?= lang_line('sc_unit_price_label', 'Unit Price'); ?></th>
                                <td><?= number_format($contract->unit_price, 2, '.', ','); ?></td>
                            </tr>
                            <tr>
                                <th><?= lang_line('sc_total_price_label', 'Total Price'); ?></th>
                                <td class="font-weight-bold">
                                    <?= htmlspecialchars($contract->currency_code) . ' ' . number_format($contract->total_price, 2, '.', ','); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?= lang_line('sc_destination_label', 'Destination'); ?></th>
                                <td><?= htmlspecialchars($contract->destination ?: '-'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: audit trail approval -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded mb-4">
                <div class="card-header bg-white border-bottom-0">
                    <h5 class="mb-0 text-primary"><i class="fas fa-history mr-2"></i><?= lang_line('sc_approval_workflow', 'Approval Workflow'); ?></h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <!-- Draft / Submitted -->
                        <li class="mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <span class="badge badge-pill <?= !empty($contract->submitted_at) ? 'badge-success' : 'badge-secondary'; ?>" style="width: 32px; height: 32px; line-height: 24px;">
                                        <i class="fas fa-paper-plane"></i>
                                    </span>
                                </div>
                                <div>
                                    <strong><?= lang_line('sc_status_submitted', 'Submitted'); ?></strong><br>
                                    <?php if (!empty($contract->submitted_at)): ?>
                                        <small><?= htmlspecialchars($contract->submitted_by_name ?: '-'); ?></small><br>
                                        <small class="text-muted"><?= date('d M Y H:i', strtotime($contract->submitted_at)); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted"><?= lang_line('sc_status_not_submitted', 'Belum di-submit'); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <!-- Endorsed: hanya utk SO lama (approval 1 tahap sejak 2026-11-06) -->
                    <?php if (!empty($contract->endorsed_at)): ?>
                    <li class="mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <span class="badge badge-pill <?= !empty($contract->endorsed_at) ? 'badge-success' : 'badge-secondary'; ?>" style="width: 32px; height: 32px; line-height: 24px;">
                                        <i class="fas fa-user-tie"></i>
                                    </span>
                                </div>
                                <div>
                                    <strong><?= lang_line('sc_status_endorsed', 'Endorsed'); ?></strong><br>
                                    <?php if (!empty($contract->endorsed_at)): ?>
                                        <small><?= htmlspecialchars($contract->endorsed_by_name ?: '-'); ?></small><br>
                                        <small class="text-muted"><?= date('d M Y H:i', strtotime($contract->endorsed_at)); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted"><?= lang_line('sc_status_not_endorsed', 'Belum di-endorse'); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endif; ?>
                        <!-- Approved -->
                        <li class="mb-3">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <span class="badge badge-pill <?= !empty($contract->approved_at) ? 'badge-success' : 'badge-secondary'; ?>" style="width: 32px; height: 32px; line-height: 24px;">
                                        <i class="fas fa-user-check"></i>
                                    </span>
                                </div>
                                <div>
                                    <strong><?= lang_line('sc_status_approved', 'Approved (Final)'); ?></strong><br>
                                    <?php if (!empty($contract->approved_at)): ?>
                                        <small><?= htmlspecialchars($contract->approved_by_name ?: '-'); ?></small><br>
                                        <small class="text-muted"><?= date('d M Y H:i', strtotime($contract->approved_at)); ?></small>
                                    <?php else: ?>
                                        <small class="text-muted"><?= lang_line('sc_status_not_approved', 'Belum di-approve'); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <!-- Rejected -->
                        <?php if (!empty($contract->rejected_at)): ?>
                        <li class="mb-0">
                            <div class="d-flex">
                                <div class="mr-3">
                                    <span class="badge badge-pill badge-danger" style="width: 32px; height: 32px; line-height: 24px;">
                                        <i class="fas fa-times"></i>
                                    </span>
                                </div>
                                <div>
                                    <strong class="text-danger"><?= lang_line('sc_status_rejected', 'Rejected'); ?></strong><br>
                                    <small><?= htmlspecialchars($contract->rejected_by_name ?: '-'); ?></small><br>
                                    <small class="text-muted"><?= date('d M Y H:i', strtotime($contract->rejected_at)); ?></small>
                                    <?php if (!empty($contract->reject_reason)): ?>
                                        <div class="mt-2 p-2 bg-light rounded border-left border-danger">
                                            <small><strong><?= lang_line('sc_reject_reason_label', 'Alasan:'); ?></strong> <?= htmlspecialchars($contract->reject_reason); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
