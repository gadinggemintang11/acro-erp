<?php
    $is_approved = !empty($qc_detail->qc_checked_by);
    $stage_color_map = ['grinding' => 'warning', 'assembling' => 'info', 'finishing' => 'success'];
    $stage_color = $stage_color_map[$qc_detail->stage_name] ?? 'secondary';
    $stage_label = lang_line('pqc_stage_'.$qc_detail->stage_name, ucfirst($qc_detail->stage_name));
?>
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1">
                <i class="fas fa-clipboard-check text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            </h3>
            <p class="text-muted small mb-0">
                <?= lang_line('pqc_info_production_no', 'Production No.'); ?> <strong><?= htmlspecialchars($qc_detail->production_number ?: '-'); ?></strong>
                &middot; <?= lang_line('pqc_info_wo_number', 'WO Number'); ?> <strong><?= htmlspecialchars($qc_detail->wo_number ?: '-'); ?></strong>
                &middot; <?= lang_line('pqc_info_stage', 'Stage'); ?> <span class="badge badge-<?= $stage_color; ?>"><?= $stage_label; ?></span>
            </p>
        </div>
        <a href="<?= base_url('production_qc'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> <?= lang_line('pqc_btn_back_to_pending', 'Kembali ke Pending QC'); ?>
        </a>
    </div>

    <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $cls): ?>
        <?php if ($this->session->flashdata($key)): ?>
        <div class="alert alert-<?= $cls; ?> alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata($key); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Status banner -->
    <?php if ($is_approved): ?>
    <div class="alert alert-success d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-check-circle mr-1"></i>
            <?= sprintf(
                lang_line('pqc_approved_by_at', 'QC sudah di-approve oleh <strong>%s</strong> pada <strong>%s</strong>.'),
                htmlspecialchars($qc_detail->checked_by_name ?: '-'),
                $qc_detail->qc_checked_at ? date('d M Y H:i', strtotime($qc_detail->qc_checked_at)) : '-'
            ); ?>
        </div>
        <?php if ($can_approve): ?>
        <a href="<?= base_url('production_qc/unapprove_qc/'.$qc_detail->id); ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('<?= lang_line('pqc_confirm_unapprove', 'Batalkan approve QC untuk output ini?'); ?>');">
            <i class="fas fa-undo"></i> <?= lang_line('pqc_btn_unapprove', 'Batal Approve'); ?>
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <i class="fas fa-hourglass-half mr-1"></i>
        <?= lang_line('pqc_pending_message', '<strong>Menunggu approve QC.</strong> Review detail di bawah lalu approve di panel kanan.'); ?>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- LEFT: Info detail -->
        <div class="col-lg-8">

            <!-- Output Info -->
            <div class="card border-0 shadow-sm rounded mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-box mr-1"></i> <?= lang_line('pqc_output_information', 'Output Information'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width:40%;"><?= lang_line('pqc_info_production_no', 'Production No.'); ?></td>
                                    <td><strong class="text-primary"><?= htmlspecialchars($qc_detail->production_number ?: '-'); ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><?= lang_line('pqc_info_wo_number', 'WO Number'); ?></td>
                                    <td><?= htmlspecialchars($qc_detail->wo_number ?: '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><?= lang_line('pqc_info_production_date', 'Production Date'); ?></td>
                                    <td><?= $qc_detail->production_date ? date('d M Y', strtotime($qc_detail->production_date)) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><?= lang_line('pqc_info_stage', 'Stage'); ?></td>
                                    <td><span class="badge badge-<?= $stage_color; ?>"><?= $stage_label; ?></span></td>
                                </tr>
                                <?php if (!empty($qc_detail->fg_name)): ?>
                                <tr>
                                    <td class="text-muted"><?= lang_line('pqc_info_fg_target', 'FG Target'); ?></td>
                                    <td><?= htmlspecialchars($qc_detail->fg_name); ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" style="width:40%;"><?= lang_line('pqc_info_item_type', 'Item Type'); ?></td>
                                    <td><span class="badge badge-secondary"><?= ucfirst(htmlspecialchars($qc_detail->item_type ?: '-')); ?></span></td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><?= lang_line('pqc_info_item_code', 'Item Code'); ?></td>
                                    <td><strong><?= htmlspecialchars($qc_detail->item_code ?: '-'); ?></strong></td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><?= lang_line('pqc_info_item_name', 'Item Name'); ?></td>
                                    <td><?= htmlspecialchars($qc_detail->item_name ?: '-'); ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><?= lang_line('pqc_info_qty_produced', 'Qty Produced'); ?></td>
                                    <td>
                                        <strong class="text-success"><?= number_format((float)$qc_detail->qty_qc, 2); ?></strong>
                                        <?php if (!empty($qc_detail->satuan)): ?>
                                            <small class="text-muted"><?= htmlspecialchars($qc_detail->satuan); ?></small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <?php if (!empty($qc_detail->production_remarks)): ?>
                    <hr>
                    <div>
                        <small class="text-muted"><?= lang_line('pqc_remarks_label', 'Remarks produksi:'); ?></small>
                        <div class="border rounded p-2 bg-light small"><?= nl2br(htmlspecialchars($qc_detail->production_remarks)); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Size breakdown (FG finishing) -->
            <?php if (!empty($size_breakdown)): ?>
            <div class="card border-0 shadow-sm rounded mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-ruler-combined mr-1"></i> <?= lang_line('pqc_size_breakdown', 'Size Breakdown'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 text-center">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('pqc_th_size', 'Size'); ?></th>
                                    <th class="text-right"><?= lang_line('pqc_th_qty', 'Qty'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($size_breakdown as $size => $qty): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($size); ?></strong></td>
                                    <td class="text-right"><?= number_format((float)$qty, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Traceability: input materials -->
            <div class="card border-0 shadow-sm rounded mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-route mr-1"></i> <?= lang_line('pqc_traceability_title', 'Input Materials Traceability'); ?>
                        <small class="text-muted"><?= sprintf(lang_line('pqc_traceability_stage', '(stage: %s)'), $stage_label); ?></small>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($stage_inputs)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="thead-light text-center">
                                <tr>
                                    <th><?= lang_line('pqc_th_type', 'Type'); ?></th>
                                    <th><?= lang_line('pqc_th_code', 'Code'); ?></th>
                                    <th class="text-left"><?= lang_line('pqc_th_item_name', 'Item Name'); ?></th>
                                    <th class="text-right"><?= lang_line('pqc_th_qty_used', 'Qty Used'); ?></th>
                                    <th><?= lang_line('pqc_th_notes', 'Notes'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stage_inputs as $in): ?>
                                <tr class="text-center">
                                    <td><span class="badge badge-secondary"><?= ucfirst(htmlspecialchars($in->item_type)); ?></span></td>
                                    <td><strong><?= htmlspecialchars($in->item_code ?: '-'); ?></strong></td>
                                    <td class="text-left"><?= htmlspecialchars($in->item_name ?: '-'); ?></td>
                                    <td class="text-right"><?= number_format((float)$in->qty_used, 2); ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars($in->notes ?: '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-info-circle mr-1"></i> <?= lang_line('pqc_no_inputs', 'Tidak ada input material tercatat pada stage ini.'); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- RIGHT: Approve panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded mb-4 <?= $is_approved ? 'border-left-success' : 'border-left-warning'; ?>">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-stamp mr-1"></i> <?= lang_line('pqc_approval_title', 'QC Approval'); ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if ($is_approved): ?>
                        <div class="mb-3">
                            <small class="text-muted"><?= lang_line('pqc_approved_by', 'Approved By'); ?></small>
                            <div><strong><?= htmlspecialchars($qc_detail->checked_by_name ?: '-'); ?></strong></div>
                            <?php if (!empty($qc_detail->checked_by_email)): ?>
                                <small class="text-muted"><?= htmlspecialchars($qc_detail->checked_by_email); ?></small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted"><?= lang_line('pqc_approved_at', 'Approved At'); ?></small>
                            <div><?= $qc_detail->qc_checked_at ? date('d M Y H:i', strtotime($qc_detail->qc_checked_at)) : '-'; ?></div>
                        </div>
                        <?php if (!empty($qc_detail->qc_description)): ?>
                        <div class="mb-3">
                            <small class="text-muted"><?= lang_line('pqc_qc_notes', 'Catatan QC'); ?></small>
                            <div class="border rounded p-2 bg-light small"><?= nl2br(htmlspecialchars($qc_detail->qc_description)); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($qc_detail->qc_photo_url)): ?>
                        <div class="mb-3">
                            <small class="text-muted"><?= lang_line('pqc_qc_photo', 'Foto QC'); ?></small>
                            <div>
                                <a href="<?= htmlspecialchars($qc_detail->qc_photo_url); ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($qc_detail->qc_photo_url); ?>"
                                         class="img-fluid rounded border" alt="QC Photo" style="max-height:200px;">
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($can_approve): ?>
                            <form method="post" action="<?= base_url('production_qc/approve_qc/'.$qc_detail->id); ?>"
                                  onsubmit="return confirm('<?= lang_line('pqc_confirm_approve', 'Approve QC untuk output ini?'); ?>');">
                                <div class="form-group">
                                    <label class="small font-weight-bold"><?= lang_line('pqc_qc_notes_label', 'Catatan QC'); ?> <span class="text-muted"><?= lang_line('pqc_optional', '(opsional)'); ?></span></label>
                                    <textarea name="qc_description" class="form-control form-control-sm" rows="3"
                                              placeholder="<?= lang_line('pqc_qc_notes_placeholder', 'Catatan pemeriksaan QC...'); ?>"></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="small font-weight-bold"><?= lang_line('pqc_qc_photo_url_label', 'URL Foto QC'); ?> <span class="text-muted"><?= lang_line('pqc_optional', '(opsional)'); ?></span></label>
                                    <input type="text" name="qc_photo_url" class="form-control form-control-sm"
                                           placeholder="https://...">
                                    <small class="text-muted"><?= lang_line('pqc_qc_photo_url_hint', 'Isi URL foto jika ada bukti visual.'); ?></small>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-check-circle"></i> <?= lang_line('pqc_btn_approve', 'Approve QC'); ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-secondary mb-0 small">
                                <i class="fas fa-lock mr-1"></i> <?= lang_line('pqc_no_permission', 'Anda tidak memiliki izin approve QC. Hubungi admin untuk mendapatkan permission <code>can_confirm</code> pada menu Production QC.'); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
