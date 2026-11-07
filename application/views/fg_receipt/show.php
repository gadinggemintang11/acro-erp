<!-- application/views/fg_receipt/show.php -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-box-open text-primary mr-2"></i>
            <?= lang_line('fgr_show_title'); ?>
        </h3>

        <div>
            <?php if ($receipt_header->status == 'Draft'): ?>
                <?php if (check_access('fg_receipt', 'edit')): ?>
                <a href="<?= base_url('fg_receipt/edit/' . $receipt_header->id); ?>" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i> <?= lang_line('fgr_show_btn_edit'); ?>
                </a>
                <a href="<?= base_url('fg_receipt/submit_for_approval/' . $receipt_header->id); ?>" class="btn btn-sm btn-success"
                   onclick="return confirm('<?= htmlspecialchars(lang_line('fgr_show_msg_submit'), ENT_QUOTES); ?>')">
                    <i class="fas fa-paper-plane"></i> <?= lang_line('fgr_show_btn_submit'); ?>
                </a>
                <?php endif; ?>
                <?php if (check_access('fg_receipt', 'delete')): ?>
                <a href="<?= base_url('fg_receipt/destroy/' . $receipt_header->id); ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('<?= htmlspecialchars(lang_line('fgr_show_msg_delete'), ENT_QUOTES); ?>')">
                    <i class="fas fa-trash"></i> <?= lang_line('fgr_show_btn_delete'); ?>
                </a>
                <?php endif; ?>
            <?php elseif (in_array($receipt_header->status, ['Submitted', 'Approved Head Dept', 'Endorsed'])): ?>
                <span class="badge badge-info p-2"><i class="fas fa-hourglass-half mr-1"></i> <?= lang_line('fgr_show_status_waiting'); ?> (<?= htmlspecialchars($receipt_header->status); ?>)</span>
            <?php elseif ($receipt_header->status == 'Approved' || $receipt_header->status == 'Confirmed'): ?>
                <?php if (check_access('fg_receipt', 'delete')): ?>
                <a href="<?= base_url('fg_receipt/destroy/' . $receipt_header->id); ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('<?= htmlspecialchars(lang_line('fgr_show_msg_cancel'), ENT_QUOTES); ?>')">
                    <i class="fas fa-ban"></i> <?= lang_line('fgr_show_btn_cancel'); ?>
                </a>
                <?php endif; ?>
            <?php elseif ($receipt_header->status == 'Rejected'): ?>
                <span class="badge badge-danger p-2"><i class="fas fa-times-circle mr-1"></i> <?= htmlspecialchars($receipt_header->status); ?></span>
            <?php endif; ?>

            <a href="<?= base_url('fg_receipt/export_pdf/' . $receipt_header->id); ?>" class="btn btn-sm btn-info">
                <i class="fas fa-file-pdf"></i> <?= lang_line('fgr_show_btn_pdf'); ?>
            </a>
        </div>
    </div>

    <!-- Header Card -->
    <div class="card mb-4 shadow">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-file-alt"></i> <?= lang_line('fgr_show_card_info'); ?></h6>
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-3"><strong><?= lang_line('fgr_show_lbl_no'); ?></strong></div>
                <div class="col-md-3"><?= htmlspecialchars($receipt_header->receipt_number); ?></div>
                <div class="col-md-3"><strong><?= lang_line('fgr_show_lbl_date'); ?></strong></div>
                <div class="col-md-3"><?= date('d-m-Y', strtotime($receipt_header->receipt_date)); ?></div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3"><strong><?= lang_line('fgr_show_lbl_fg_model'); ?></strong></div>
                <div class="col-md-9">
                    <?php if (!empty($receipt_header->fg_name)): ?>
                        <?= htmlspecialchars($receipt_header->fg_code . ' - ' . $receipt_header->fg_name); ?>
                    <?php else: ?>
                        <span class="text-muted"><?= lang_line('fgr_show_old_data'); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3"><strong><?= lang_line('fgr_show_lbl_status'); ?></strong></div>
                <div class="col-md-3">
                    <span class="badge
                        <?= $receipt_header->status == 'Confirmed' ? 'badge-success' :
                            ($receipt_header->status == 'Cancelled' ? 'badge-danger' : 'badge-secondary'); ?>">
                        <?= $receipt_header->status; ?>
                    </span>
                </div>
                <div class="col-md-3"><strong><?= lang_line('fgr_show_lbl_receiver'); ?></strong></div>
                <div class="col-md-3"><?= htmlspecialchars($receipt_header->receiver_name ?? '-'); ?></div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3"><strong><?= lang_line('fgr_show_lbl_confirmer'); ?></strong></div>
                <div class="col-md-3"><?= htmlspecialchars($receipt_header->confirmer_name ?? '-'); ?></div>
                <div class="col-md-3"><strong><?= lang_line('fgr_show_lbl_remarks'); ?></strong></div>
                <div class="col-md-3"><?= nl2br(htmlspecialchars($receipt_header->remarks)); ?></div>
            </div>
        </div>
    </div>

    <!-- Detail Tabel -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white py-3">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-list"></i> <?= lang_line('fgr_show_card_items'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th width="4%"><?= lang_line('fgr_show_th_no'); ?></th>
                            <th width="11%"><?= lang_line('fgr_show_th_wo_no'); ?></th>
                            <th width="11%"><?= lang_line('fgr_show_th_ipo_no'); ?></th>
                            <th width="11%"><?= lang_line('fgr_show_th_prod_no'); ?></th>
                            <th width="10%"><?= lang_line('fgr_show_th_fg_code'); ?></th>
                            <th><?= lang_line('fgr_show_th_fg_name'); ?></th>
                            <th width="8%"><?= lang_line('fgr_show_th_qty'); ?></th>
                            <th width="6%"><?= lang_line('fgr_show_th_uom'); ?></th>
                            <th width="10%"><?= lang_line('fgr_show_th_qc'); ?></th>
                            <th width="12%"><?= lang_line('fgr_show_th_notes'); ?></th>
                            <th width="6%"><?= lang_line('fgr_show_th_size'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($receipt_details)): ?>
                            <?php $no = 1; foreach ($receipt_details as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td class="small"><?= htmlspecialchars($row->wo_number ?: '-'); ?></td>
                                    <td class="small"><?= htmlspecialchars($row->ipo_number ?: '-'); ?></td>
                                    <td class="small"><?= htmlspecialchars($row->production_number ?: '-'); ?></td>
                                    <td><?= htmlspecialchars($row->fg_code); ?></td>
                                    <td><?= htmlspecialchars($row->fg_name); ?></td>
                                    <td class="text-right"><?= number_format($row->qty_received, 2); ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row->uom ?: $row->fg_uom); ?></td>
                                    <td class="small"><?= htmlspecialchars($row->qc_name ?? '-'); ?></td>
                                    <td class="small"><?= htmlspecialchars($row->notes ?? '-'); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row->prod_size_breakdown)): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                    data-toggle="modal"
                                                    data-target="#modalSize_<?= $row->id; ?>">
                                                <i class="fas fa-ruler"></i> <?= lang_line('fgr_show_btn_view_size'); ?>
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Modal Size Breakdown -->
                                <?php if (!empty($row->prod_size_breakdown)): ?>
                                    <?php $sizes = json_decode($row->prod_size_breakdown, true); ?>
                                    <div class="modal fade" id="modalSize_<?= $row->id; ?>" tabindex="-1" role="dialog" aria-labelledby="modalSizeLabel_<?= $row->id; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h6 class="modal-title" id="modalSizeLabel_<?= $row->id; ?>">
                                                        <?= sprintf(htmlspecialchars(lang_line('fgr_show_size_title'), ENT_QUOTES), htmlspecialchars($row->fg_name)); ?>
                                                    </h6>
                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php if (!empty($sizes)): ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered">
                                                                <thead class="thead-light text-center">
                                                                    <tr>
                                                                        <th><?= lang_line('fgr_show_size_col_size'); ?></th>
                                                                        <th><?= lang_line('fgr_show_size_col_qty'); ?></th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($sizes as $s): ?>
                                                                        <tr class="text-center">
                                                                            <td><?= htmlspecialchars($s['size']); ?></td>
                                                                            <td><?= number_format($s['qty'], 2); ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="text-muted text-center mb-0"><?= lang_line('fgr_show_size_no_data'); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                                                        <i class="fas fa-times"></i> <?= lang_line('fgr_show_btn_close'); ?>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="11" class="text-center text-muted"><?= lang_line('fgr_show_no_items'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
