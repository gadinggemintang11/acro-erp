<?php
    // Approval 1 tahap (2026-11-06): Submitted -> Disetujui (Head of Dept) -> Dibayar.
    // Tahap Endorsement & Final hanya ditampilkan utk Reimburse Request lama (alur 3 tahap).
    $erf_legacy_3stage = !empty($header->endorsed_at) || in_array($header->status, ['Approved Head Dept', 'Endorsed'], true);
    $erf_last_pay = !empty($payment_tracking) ? $payment_tracking[0] : null;
    $stage_steps = [
        ['key'=>'submitted',   'label'=>lang_line('erf_stage_submitted', 'Submitted'),     'by_field'=>'submitted_by',      'at_field'=>'submitted_at',      'reached'=>!empty($header->submitted_at) || !in_array($header->status, ['Draft'])],
        ['key'=>'hod',         'label'=>lang_line($erf_legacy_3stage ? 'erf_stage_hod' : 'erf_stage_approved_hod', $erf_legacy_3stage ? 'HOD Approval' : 'Disetujui (Head of Dept)'), 'by_field'=>'hod_approved_by', 'at_field'=>'hod_approved_at', 'reached'=>!empty($header->hod_approved_at)],
    ];
    if ($erf_legacy_3stage) {
        $stage_steps[] = ['key'=>'endorsement', 'label'=>lang_line('erf_stage_endorsement', 'Endorsement'), 'by_field'=>'endorsed_by',       'at_field'=>'endorsed_at',       'reached'=>!empty($header->endorsed_at)];
        $stage_steps[] = ['key'=>'final',       'label'=>lang_line('erf_stage_final', 'Final Approval'),    'by_field'=>'final_approved_by', 'at_field'=>'final_approved_at', 'reached'=>!empty($header->final_approved_at) || $header->status === 'Approved' || $header->status === 'Paid'];
    }
    // Dibayar: terisi otomatis dari Record Payment terakhir.
    $stage_steps[] = ['key'=>'paid', 'label'=>lang_line('erf_stage_paid', 'Dibayar'), 'by_name'=>$erf_last_pay->created_by_name ?? null, 'at'=>$erf_last_pay->payment_date ?? null, 'date_only'=>true, 'reached'=>$header->status === 'Paid' || ($header->payment_status ?? '') === 'Paid' || !empty($erf_last_pay)];

    $badge = $status_colors[$header->status] ?? 'secondary';
    $grand_total = 0;
    foreach ($details as $d) $grand_total += ((float)$d->qty * (float)$d->unit_price);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-file-invoice-dollar text-primary mr-2"></i><?= htmlspecialchars($header->erf_number); ?></h3>
            <p class="text-muted small mb-0"><?= lang_line('erf_subtitle_status', 'Reimburse Request — status'); ?>: <span class="badge badge-<?= $badge; ?>"><?= htmlspecialchars($header->status); ?></span></p>
        </div>
        <div>
            <a href="<?= base_url('erf/export_pdf/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm no-loader"><i class="fas fa-file-pdf mr-1"></i> <?= lang_line('erf_btn_pdf', 'PDF'); ?></a>
            <a href="<?= base_url('erf/export_excel/' . $header->id); ?>" target="_blank" class="btn btn-success btn-sm no-loader"><i class="fas fa-file-excel mr-1"></i> <?= lang_line('erf_btn_excel', 'Excel'); ?></a>
            <?php if (!empty($can_unlock_admin) && empty($admin_edit_mode)): ?>
                <a href="<?= base_url('erf/detail/' . $header->id); ?>?admin_edit=1" class="btn btn-outline-warning btn-sm mr-1 btn-confirm no-loader"><i class="fas fa-user-shield mr-1"></i> Buka Mode Edit (Admin)</a>
            <?php elseif (!empty($admin_edit_mode)): ?>
                <span class="btn btn-outline-danger btn-sm mr-1"><i class="fas fa-user-shield mr-1"></i> Mode Edit Admin Aktif</span>
            <?php endif; ?>
            <?php if ($can_edit): ?>
                <a href="<?= base_url('erf/edit/' . $header->id); ?><?= !empty($admin_edit_mode) ? '?admin_edit=1' : ''; ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i> <?= lang_line('erf_btn_edit', 'Edit'); ?></a>
            <?php endif; ?>
            <?php if ($can_delete): ?>
                <a href="<?= base_url('erf/delete/' . $header->id); ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?= lang_line('erf_confirm_delete', 'Hapus Reimburse Request ini?'); ?>');"><i class="fas fa-trash mr-1"></i> <?= lang_line('erf_btn_delete', 'Hapus'); ?></a>
            <?php endif; ?>
            <a href="<?= base_url(!empty($back_url) ? $back_url : 'erf'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> <?= lang_line('erf_btn_back', 'Kembali'); ?></a>
        </div>
    </div>

    <!-- Header info + grand total -->
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang_line('erf_info_title', 'Informasi Reimburse Request'); ?></h6></div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td width="40%" class="text-muted"><?= lang_line('erf_lbl_number', 'Reimburse Request Number'); ?></td><td><strong><?= htmlspecialchars($header->erf_number); ?></strong></td></tr>
                                <tr><td class="text-muted"><?= lang_line('erf_lbl_date', 'Reimburse Request Date'); ?></td><td><?= date('d M Y', strtotime($header->erf_date)); ?></td></tr>
                                <tr><td class="text-muted"><?= lang_line('erf_lbl_requester', 'Requester'); ?></td><td><?= htmlspecialchars($header->requester_name ?? '-'); ?></td></tr>
                                <tr><td class="text-muted"><?= lang_line('erf_lbl_department', 'Department'); ?></td><td><?= htmlspecialchars($department_name ?? '-'); ?></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td width="40%" class="text-muted"><?= lang_line('label_status', 'Status'); ?></td><td><span class="badge badge-<?= $badge; ?>"><?= htmlspecialchars($header->status); ?></span></td></tr>
                                <tr><td class="text-muted"><?= lang_line('erf_lbl_payment_status', 'Payment Status'); ?></td><td><span class="badge badge-<?= $header->payment_status == 'Paid' ? 'success' : 'secondary'; ?>"><?= htmlspecialchars($header->payment_status); ?></span></td></tr>
                                <?php if ($header->payment_date): ?>
                                <tr><td class="text-muted"><?= lang_line('erf_lbl_paid_on', 'Paid On'); ?></td><td><?= date('d M Y', strtotime($header->payment_date)); ?> (<?= htmlspecialchars($header->payment_method ?: '-'); ?>)</td></tr>
                                <?php endif; ?>
                                <tr><td class="text-muted"><?= lang_line('erf_lbl_karyawan', 'Karyawan (Penerima)'); ?></td><td><?= htmlspecialchars($header->karyawan_name ?: '-'); ?></td></tr>
                                <tr><td class="text-muted"><?= lang_line('prf_lbl_bank_name', 'Nama Bank'); ?></td><td><?= htmlspecialchars($header->bank_name ?: '-'); ?></td></tr>
                                <tr><td class="text-muted"><?= lang_line('prf_lbl_bank_account', 'No. Rekening'); ?></td><td><?= htmlspecialchars($header->bank_account ?: '-'); ?></td></tr>
                            </table>
                        </div>
                    </div>
                    <?php if (!empty($header->notes)): ?>
                        <hr class="my-2">
                        <small class="text-muted"><?= lang_line('erf_lbl_notes', 'Catatan'); ?>:</small>
                        <div class="border rounded p-2 bg-light small"><?= nl2br(htmlspecialchars($header->notes)); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-primary shadow-sm mb-3">
                <div class="card-body py-3">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= lang_line('erf_grand_total', 'Grand Total'); ?></div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($grand_total, 2); ?></div>
                    <small class="text-muted"><?= count($details); ?> <?= lang_line('erf_item_count', 'item'); ?></small>
                </div>
            </div>
            <?php if ($can_submit): ?>
                <?php if (empty($attachments)): ?>
                <button type="button" class="btn btn-secondary btn-block" disabled title="<?= lang_line('erf_need_attachment_title', 'Upload attachment bukti pengeluaran dulu sebelum submit'); ?>">
                    <i class="fas fa-paper-plane mr-1"></i> <?= lang_line('erf_btn_submit_approval', 'Submit untuk Approval'); ?>
                </button>
                <small class="text-danger d-block text-center mt-1"><?= lang_line('erf_need_attachment', 'Upload attachment bukti pengeluaran dulu.'); ?></small>
                <?php else: ?>
                <a href="<?= base_url('erf/submit/' . $header->id); ?>" class="btn btn-primary btn-block" onclick="return confirm('<?= lang_line('erf_confirm_submit', 'Submit Reimburse Request ini untuk approval HOD?'); ?>');">
                    <i class="fas fa-paper-plane mr-1"></i> <?= lang_line('erf_btn_submit_approval', 'Submit untuk Approval'); ?>
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang_line('erf_items_title', 'Items'); ?> (<?= count($details); ?>)</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light text-center">
                        <tr>
                            <th width="3%">#</th>
                            <th width="11%"><?= lang_line('erf_th_expense_date', 'Expense Date'); ?></th>
                            <th width="12%"><?= lang_line('erf_th_category', 'Category'); ?></th>
                            <th width="12%"><?= lang_line('erf_th_receipt', 'Receipt #'); ?></th>
                            <th><?= lang_line('erf_th_description', 'Description'); ?></th>
                            <th width="8%" class="text-right"><?= lang_line('erf_th_qty', 'Qty'); ?></th>
                            <th width="12%" class="text-right"><?= lang_line('erf_th_unit_price', 'Unit Price'); ?></th>
                            <th width="12%" class="text-right"><?= lang_line('erf_th_subtotal', 'Subtotal'); ?></th>
                            <?php if (!empty($admin_edit_mode)): ?><th width="4%"></th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($details)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-3"><?= lang_line('erf_no_items', 'Belum ada item.'); ?></td></tr>
                        <?php else: $i=1; foreach ($details as $d): ?>
                            <tr>
                                <td class="text-center"><?= $i++; ?></td>
                                <td class="text-center small"><?= $d->expense_date ? date('d M Y', strtotime($d->expense_date)) : '-'; ?></td>
                                <td class="small"><?= htmlspecialchars($d->category ?: '-'); ?></td>
                                <td class="small"><?= htmlspecialchars($d->receipt_number ?: '-'); ?></td>
                                <td><?= htmlspecialchars($d->description); ?></td>
                                <td class="text-right"><?= number_format((float)$d->qty, 2); ?></td>
                                <td class="text-right"><?= number_format((float)$d->unit_price, 2); ?></td>
                                    <td class="text-right"><strong><?= number_format((float)$d->qty * (float)$d->unit_price, 2); ?></strong></td>
                                    <?php if (!empty($admin_edit_mode)): ?>
                                        <td class="text-center" style="width:80px;">
                                            <a href="<?= base_url('erf/edit/' . $header->id) . '?admin_edit=1#row-' . $d->id; ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                            <a href="<?= base_url('erf/delete_item/' . $header->id . '/' . $d->id); ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus item ini?');" title="Hapus"><i class="fas fa-trash"></i></a>
                                        </td>
                                    <?php endif; ?>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td colspan="7" class="text-right"><?= lang_line('erf_grand_total', 'Grand Total'); ?></td>
                            <td class="text-right">Rp <?= number_format($grand_total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Approval Trail -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-route mr-1"></i> <?= lang_line('erf_approval_trail', 'Approval Trail'); ?></h6></div>
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-around align-items-start">
                <?php foreach ($stage_steps as $idx => $step):
                    $reached = !empty($step['reached']);
                    $by_id   = isset($step['by_field']) ? ($header->{$step['by_field']} ?? null) : null;
                    $at_raw  = array_key_exists('at', $step) ? $step['at'] : ($header->{$step['at_field']} ?? null);
                    $by_name = $step['by_name'] ?? (($by_id && !empty($audit[$by_id])) ? $audit[$by_id] : null);
                ?>
                    <div class="text-center flex-grow-1 px-2" style="min-width:130px;">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1"
                             style="width:42px;height:42px;background:<?= $reached ? '#1cc88a' : '#e3e6f0'; ?>;color:<?= $reached ? '#fff' : '#858796'; ?>;font-weight:bold;">
                            <i class="fas fa-<?= $reached ? 'check' : ($idx + 1); ?>"></i>
                        </div>
                        <div class="small font-weight-bold <?= $reached ? 'text-success' : 'text-muted'; ?>"><?= htmlspecialchars($step['label']); ?></div>
                        <?php if ($reached && $by_name): ?>
                            <div class="small text-muted"><?= htmlspecialchars($by_name); ?></div>
                            <div class="small text-muted"><?= $at_raw ? date(!empty($step['date_only']) ? 'd M Y' : 'd M Y H:i', strtotime($at_raw)) : ''; ?></div>
                        <?php elseif ($reached): ?>
                            <div class="small text-muted">—</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($idx < count($stage_steps) - 1): ?>
                        <div class="align-self-center text-muted d-none d-md-block" style="margin-top:14px;"><i class="fas fa-arrow-right"></i></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($header->reject_reason)): ?>
            <div class="alert alert-danger mt-3 mb-0 py-2">
                <strong><i class="fas fa-times-circle mr-1"></i> <?= lang_line('erf_rejected_label', 'Rejected'); ?>:</strong> <?= nl2br(htmlspecialchars($header->reject_reason)); ?>
                <?php if (!empty($header->rejected_by) && !empty($audit[$header->rejected_by])): ?>
                    <small class="text-muted ml-2"><?= lang_line('erf_by', 'oleh'); ?> <?= htmlspecialchars($audit[$header->rejected_by]); ?> <?= lang_line('erf_on', 'pada'); ?> <?= date('d M Y H:i', strtotime($header->rejected_at)); ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($header->return_reason)): ?>
            <div class="alert alert-warning mt-3 mb-0 py-2">
                <strong><i class="fas fa-undo mr-1"></i> <?= lang_line('erf_returned_label', 'Returned to Draft'); ?>:</strong> <?= nl2br(htmlspecialchars($header->return_reason)); ?>
                <?php if (!empty($header->returned_to_draft_by) && !empty($audit[$header->returned_to_draft_by])): ?>
                    <small class="text-muted ml-2"><?= lang_line('erf_by', 'oleh'); ?> <?= htmlspecialchars($audit[$header->returned_to_draft_by]); ?> <?= lang_line('erf_on', 'pada'); ?> <?= date('d M Y H:i', strtotime($header->returned_to_draft_at)); ?></small>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons: 1 tahap (HOD) sejak 2026-11-06; Endorse/Final hanya utk ERF lama -->
    <?php if ($can_approve_hod || $can_approve_endorsement || $can_approve_final || $can_return_reject): ?>
    <div class="card shadow-sm mb-3 border-left-info">
        <div class="card-body py-3">
            <h6 class="font-weight-bold text-info mb-3"><i class="fas fa-gavel mr-1"></i> <?= lang_line('erf_approval_decision', 'Keputusan Approval'); ?></h6>
            <?php if ($can_approve_hod): ?>
                <a href="<?= base_url('erf/approve_hod/' . $header->id); ?>" class="btn btn-success mr-2" onclick="return confirm('<?= lang_line('erf_confirm_approve_single', 'Setujui Reimburse Request ini? Setelah disetujui langsung siap dibayar.'); ?>');">
                    <i class="fas fa-check mr-1"></i> <?= lang_line('erf_btn_approve_single', 'Approve'); ?>
                </a>
            <?php endif; ?>
            <?php if ($can_approve_endorsement): ?>
                <a href="<?= base_url('erf/approve_endorsement/' . $header->id); ?>" class="btn btn-success mr-2" onclick="return confirm('<?= lang_line('erf_confirm_endorse', 'Endorse Reimburse Request ini?'); ?>');">
                    <i class="fas fa-stamp mr-1"></i> <?= lang_line('erf_btn_endorse', 'Endorse'); ?>
                </a>
            <?php endif; ?>
            <?php if ($can_approve_final): ?>
                <a href="<?= base_url('erf/approve_final/' . $header->id); ?>" class="btn btn-success mr-2" onclick="return confirm('<?= lang_line('erf_confirm_final', 'Final approve Reimburse Request ini? Setelah ini Reimburse Request siap dibayar.'); ?>');">
                    <i class="fas fa-check-double mr-1"></i> <?= lang_line('erf_btn_final_approve', 'Final Approve'); ?>
                </a>
            <?php endif; ?>
            <?php if ($can_return_reject): ?>
                <button type="button" class="btn btn-warning mr-2" data-toggle="modal" data-target="#returnDraftModal"><i class="fas fa-undo mr-1"></i> <?= lang_line('erf_btn_return_draft', 'Return to Draft'); ?></button>
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#rejectModal"><i class="fas fa-times-circle mr-1"></i> <?= lang_line('erf_btn_reject', 'Reject'); ?></button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Payment section -->
    <?php if ($header->status === 'Approved' || $header->status === 'Paid'): ?>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-money-bill-wave mr-1"></i> <?= lang_line('erf_payment_title', 'Payment'); ?></h6>
            <?php if ($can_pay): ?>
                <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#paymentModal"><i class="fas fa-plus mr-1"></i> <?= lang_line('erf_btn_record_payment', 'Record Payment'); ?></button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($payment_tracking)): ?>
                <p class="text-muted mb-0"><?= lang_line('erf_no_payment', 'Belum ada pembayaran.'); ?></p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('erf_th_pay_date', 'Date'); ?></th>
                            <th class="text-right"><?= lang_line('erf_th_pay_amount', 'Amount'); ?></th>
                            <th class="text-right"><?= lang_line('erf_th_transfer_fee', 'Biaya Transfer'); ?></th>
                            <th><?= lang_line('erf_th_pay_method', 'Method'); ?></th>
                            <th><?= lang_line('erf_th_pay_ref', 'Ref #'); ?></th>
                            <th><?= lang_line('erf_th_pay_notes', 'Notes'); ?></th>
                            <th><?= lang_line('erf_th_pay_recorded_by', 'Recorded By'); ?></th>
                            <th><?= lang_line('erf_th_pay_proof', 'Bukti'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment_tracking as $pt): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($pt->payment_date)); ?></td>
                            <td class="text-right">Rp <?= number_format((float)$pt->payment_amount, 2); ?></td>
                            <td class="text-right"><?= (float)($pt->transfer_fee ?? 0) > 0 ? 'Rp ' . number_format((float)$pt->transfer_fee, 2) : '<span class="text-muted">-</span>'; ?></td>
                            <td><?= htmlspecialchars($pt->payment_method ?: '-'); ?></td>
                            <td><?= htmlspecialchars($pt->reference_number ?: '-'); ?></td>
                            <td class="small"><?= htmlspecialchars($pt->notes ?: '-'); ?></td>
                            <td class="small"><?= htmlspecialchars($pt->created_by_name ?? '-'); ?></td>
                            <td>
                                <?php if (!empty($pt->payment_proof_path)): ?>
                                    <a href="<?= base_url('erf/payment_proof/' . $pt->id); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="<?= lang_line('erf_view_proof', 'Lihat Bukti'); ?>">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    <?php if (!empty($can_upload_payment_proof)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#uploadErfProofModal" data-payment-id="<?= $pt->id; ?>" title="<?= lang_line('erf_replace_proof', 'Ganti Bukti'); ?>">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php elseif (!empty($can_upload_payment_proof)): ?>
                                    <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#uploadErfProofModal" data-payment-id="<?= $pt->id; ?>">
                                        <i class="fas fa-upload"></i> <?= lang_line('erf_btn_upload_proof', 'Upload'); ?>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bukti Pembayaran (terpisah dari Attachments Reimburse Request) -->
    <?php
    $erf_payment_proofs = [];
    if (!empty($payment_tracking)) {
        foreach ($payment_tracking as $pt) {
            if (!empty($pt->payment_proof_path)) $erf_payment_proofs[] = $pt;
        }
    }
    ?>
    <div class="card border-0 shadow-sm mb-3" id="bukti-bayar">
        <div class="card-header py-3" style="background:#e8f5e9;">
            <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-receipt mr-1"></i> <?= lang_line('erf_payment_proofs', 'Bukti Pembayaran'); ?> (<?= count($erf_payment_proofs); ?>)</h6>
        </div>
        <div class="card-body">
            <?php if (empty($erf_payment_proofs)): ?>
                <p class="text-muted mb-0"><i class="fas fa-info-circle"></i> <?= lang_line('erf_no_payment_proof', 'Belum ada bukti pembayaran. File akan tampil di sini setelah Finance mencatat pembayaran beserta bukti transfer/kwitansi.'); ?></p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($erf_payment_proofs as $pp):
                        $proof_url  = base_url('erf/payment_proof/' . $pp->id);
                        $proof_name = basename($pp->payment_proof_path);
                        $proof_ext  = strtolower(pathinfo($pp->payment_proof_path, PATHINFO_EXTENSION));
                        $is_image   = in_array($proof_ext, ['jpg', 'jpeg', 'png']);
                    ?>
                        <div class="col-md-3 mb-3">
                            <div class="card h-100 border-success">
                                <div class="card-body p-2 text-center">
                                    <?php if ($is_image): ?>
                                        <a href="<?= $proof_url; ?>" target="_blank">
                                            <img src="<?= base_url($pp->payment_proof_path); ?>" style="max-height:120px;max-width:100%;object-fit:contain;" />
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $proof_url; ?>" target="_blank" class="d-block py-3">
                                            <i class="fas fa-file-pdf fa-4x text-danger"></i>
                                        </a>
                                    <?php endif; ?>
                                    <small class="d-block text-truncate mt-2" title="<?= htmlspecialchars($proof_name); ?>">
                                        <?= htmlspecialchars($proof_name); ?>
                                    </small>
                                    <small class="text-muted d-block">
                                        <?= lang_line('erf_pay_date_short', 'Tgl'); ?>: <?= date('d-m-Y', strtotime($pp->payment_date)); ?>
                                    </small>
                                </div>
                                <div class="card-footer p-1 text-center bg-light">
                                    <a href="<?= $proof_url; ?>" target="_blank" class="btn btn-sm btn-info" title="<?= lang_line('erf_preview', 'Preview'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if (!empty($can_upload_payment_proof)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-toggle="modal" data-target="#uploadErfProofModal" data-payment-id="<?= $pp->id; ?>" title="<?= lang_line('erf_replace_proof', 'Ganti Bukti'); ?>">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Attachments -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-paperclip mr-1"></i> <?= lang_line('erf_attachments_title', 'Attachments'); ?> (<?= count($attachments); ?>)</h6>
        </div>
        <div class="card-body">
            <?php if (in_array($header->status, ['Draft','Submitted','Approved Head Dept','Endorsed','Approved'])): ?>
            <form method="POST" action="<?= base_url('erf/upload_attachment/' . $header->id); ?>" enctype="multipart/form-data" class="mb-3">
                <div class="form-group mb-0">
                    <label for="attachment" class="small font-weight-bold"><?= lang_line('erf_lbl_choose_file', 'Pilih File'); ?></label>
                    <div class="input-group input-group-sm">
                        <input type="file" name="attachment" id="attachment" class="form-control-file" required>
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-upload mr-1"></i> <?= lang_line('erf_btn_upload', 'Upload'); ?></button>
                        </div>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <?php if (empty($attachments)): ?>
                <p class="text-muted text-center py-3 mb-0"><?= lang_line('erf_no_attachment', 'Belum ada attachment.'); ?></p>
            <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="thead-light">
                        <tr><th><?= lang_line('erf_th_att_file', 'File'); ?></th><th><?= lang_line('erf_th_att_type', 'Type'); ?></th><th><?= lang_line('erf_th_att_size', 'Size'); ?></th><th><?= lang_line('erf_th_att_uploaded', 'Uploaded'); ?></th><th width="140"><?= lang_line('label_action', 'Aksi'); ?></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attachments as $a): ?>
                        <tr>
                            <td><i class="fas fa-file mr-1"></i> <?= htmlspecialchars($a->original_name); ?></td>
                            <td class="small"><?= htmlspecialchars($a->file_type); ?></td>
                            <td class="small"><?= number_format($a->file_size / 1024, 1); ?> KB</td>
                            <td class="small"><?= date('d M Y H:i', strtotime($a->created_at)); ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-link text-info btn-view-attachment" data-id="<?= $a->id; ?>" data-name="<?= htmlspecialchars($a->original_name); ?>" data-url="<?= base_url('erf/attachment/' . $a->id); ?>"><i class="fas fa-eye mr-1"></i> <?= lang_line('erf_btn_view', 'View'); ?></button>
                                <?php if ($header->status == 'Draft'): ?>
                                <a href="<?= base_url('erf/delete_attachment/' . $a->id); ?>" class="btn btn-sm btn-link text-danger" onclick="return confirm('<?= lang_line('erf_confirm_delete_att', 'Hapus attachment?'); ?>');"><i class="fas fa-trash"></i> <?= lang_line('erf_btn_delete', 'Hapus'); ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Audit log + Back to Draft 2-arah (ERP Revision Notes 2026-09-02, Fase
         2 poin 11b) — requestor & approver bolak-balik reply selagi Draft. -->
    <?php if (!empty($logs)): ?>
    <?php
        $erf_log_style = [
            'Submit'          => ['icon' => 'paper-plane',    'color' => 'info'],
            'Approve HOD'     => ['icon' => 'check',          'color' => 'success'],
            'Endorse'         => ['icon' => 'file-signature', 'color' => 'primary'],
            'Approve Final'   => ['icon' => 'check-double',   'color' => 'success'],
            'Return to Draft' => ['icon' => 'undo',           'color' => 'warning'],
            'Reject'          => ['icon' => 'times',          'color' => 'danger'],
            'Record Payment'  => ['icon' => 'wallet',         'color' => 'success'],
            'Cancel'          => ['icon' => 'ban',            'color' => 'danger'],
            'Reply'           => ['icon' => 'reply',          'color' => 'secondary'],
        ];
    ?>
    <div class="card border-0 shadow-sm mb-3" id="riwayat-approval">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-history mr-1"></i> <?= lang_line('erf_activity_log', 'Activity Log'); ?></h6></div>
        <div class="card-body">
            <ul class="list-unstyled mb-0">
                <?php foreach ($logs as $l):
                    $s = $erf_log_style[$l->action] ?? ['icon' => 'circle', 'color' => 'secondary'];
                    $is_reply = ($l->action === 'Reply');
                    $reply_from_requestor = $is_reply && ((int)($l->actor_id ?? 0) === (int)$header->requester_id);
                ?>
                <?php if ($is_reply): ?>
                <li class="d-flex mb-3 <?= $reply_from_requestor ? 'flex-row-reverse text-right' : ''; ?>">
                    <div class="<?= $reply_from_requestor ? 'ml-2' : 'mr-2'; ?>" style="max-width:75%;">
                        <div class="px-3 py-2" style="background:<?= $reply_from_requestor ? '#e7f1ff' : '#f1f1f1'; ?>;border-radius:14px;<?= $reply_from_requestor ? 'border-top-right-radius:2px;' : 'border-top-left-radius:2px;'; ?>">
                            <div class="small font-weight-bold text-gray-800"><?= htmlspecialchars($l->actor_full ?: $l->actor_name ?: '-'); ?> <span class="font-weight-normal text-muted">— <?= htmlspecialchars($reply_from_requestor ? 'Requestor' : 'Approver'); ?></span></div>
                            <div class="small" style="word-break:break-word;"><?= nl2br(htmlspecialchars($l->notes ?? '')); ?></div>
                        </div>
                        <div class="small text-muted mt-1"><?= $l->created_at ? date('d M Y H:i', strtotime($l->created_at)) : ''; ?></div>
                    </div>
                </li>
                <?php else: ?>
                <li class="d-flex mb-3">
                    <div class="mr-3 text-center" style="width:34px;">
                        <span class="badge badge-<?= $s['color']; ?> d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;border-radius:50%;">
                            <i class="fas fa-<?= $s['icon']; ?>"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="font-weight-bold text-<?= $s['color']; ?>"><?= htmlspecialchars($l->action); ?></div>
                        <div class="small text-gray-800"><?= htmlspecialchars($l->actor_full ?: $l->actor_name); ?></div>
                        <div class="small text-muted"><?= $l->created_at ? date('d M Y H:i', strtotime($l->created_at)) : ''; ?></div>
                        <?php if (!empty($l->notes)): ?>
                            <div class="mt-1 px-2 py-1 small" style="background:#f8f9fc;border-left:3px solid #d1d3e2;border-radius:3px;color:#555;font-style:italic;word-break:break-word;">
                                <i class="fas fa-comment-dots text-muted mr-1"></i><?= nl2br(htmlspecialchars($l->notes)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <?php if (!empty($can_reply)): ?>
            <form method="POST" action="<?= base_url('erf/reply/' . $header->id); ?>" class="mt-3 pt-3 border-top">
                <div class="form-group mb-2">
                    <label class="small font-weight-bold mb-1"><?= lang_line('erf_reply_label', 'Balas'); ?></label>
                    <textarea name="message" class="form-control form-control-sm" rows="2" placeholder="<?= lang_line('erf_reply_placeholder', 'Tulis balasan/klarifikasi...'); ?>" required></textarea>
                </div>
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-paper-plane mr-1"></i> <?= lang_line('erf_reply_send', 'Kirim Balasan'); ?></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal: Return to Draft -->
<?php if ($can_return_reject): ?>
<form method="POST" action="<?= base_url('erf/return_to_draft/' . $header->id); ?>">
<div class="modal fade" id="returnDraftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark"><h5 class="modal-title"><i class="fas fa-undo mr-2"></i> <?= lang_line('erf_btn_return_draft', 'Return to Draft'); ?></h5><button type="button" class="close text-dark" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <p class="small text-muted"><?= lang_line('erf_return_modal_text', 'Reimburse Request dikembalikan ke Draft. Requester bisa revisi & submit ulang.'); ?></p>
                <textarea name="return_reason" class="form-control" rows="4" required placeholder="<?= lang_line('erf_return_modal_placeholder', 'Apa yang perlu direvisi...'); ?>"></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-warning"><i class="fas fa-undo mr-1"></i> <?= lang_line('erf_btn_return', 'Return'); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
            </div>
        </div>
    </div>
</div>
</form>

<form method="POST" action="<?= base_url('erf/reject/' . $header->id); ?>">
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white"><h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i> <?= lang_line('erf_btn_reject', 'Reject'); ?> Reimburse Request</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <p class="small text-muted"><?= lang_line('erf_reject_modal_text', 'Reimburse Request yang di-reject tidak bisa direvisi lagi.'); ?></p>
                <textarea name="reject_reason" class="form-control" rows="4" required placeholder="<?= lang_line('erf_reject_modal_placeholder', 'Alasan reject...'); ?>"></textarea>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger"><i class="fas fa-times-circle mr-1"></i> <?= lang_line('erf_btn_reject', 'Reject'); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
            </div>
        </div>
    </div>
</div>
</form>
<?php endif; ?>

<!-- Modal: Record Payment -->
<?php if (!empty($can_pay)): ?>
<form method="POST" action="<?= base_url('erf/record_payment/' . $header->id); ?>" enctype="multipart/form-data">
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-money-bill-wave mr-2"></i> <?= lang_line('erf_pay_modal_title', 'Record Payment'); ?></h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-2"><label class="small font-weight-bold"><?= lang_line('erf_pay_date', 'Payment Date'); ?> *</label><input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d'); ?>" required></div>
                    <div class="col-md-6 mb-2"><label class="small font-weight-bold"><?= lang_line('erf_pay_amount', 'Amount'); ?> *</label><input type="number" step="0.01" id="erfPaymentAmount" name="payment_amount" class="form-control text-right" value="<?= $grand_total; ?>" required></div>
                    <div class="col-md-6 mb-2"><label class="small font-weight-bold"><?= lang_line('erf_transfer_fee', 'Biaya Transfer Bank'); ?> <span class="text-muted">(<?= lang_line('erf_optional', 'opsional'); ?>)</span></label><input type="number" step="0.01" min="0" id="erfTransferFee" name="transfer_fee" class="form-control text-right" value="0"></div>
                    <div class="col-md-12 mb-2">
                        <small class="form-text text-muted">
                            <?= lang_line('erf_transfer_fee_hint', 'Biaya admin bank bila transfer beda bank. Dicatat terpisah dari amount.'); ?>
                            <?= lang_line('erf_total_cash_out', 'Total kas keluar'); ?>: <strong id="erfTotalCashOut">Rp 0</strong>
                        </small>
                    </div>
                    <div class="col-md-6 mb-2"><label class="small font-weight-bold"><?= lang_line('erf_pay_method', 'Method'); ?></label><input type="text" name="payment_method" class="form-control" placeholder="<?= lang_line('erf_pay_method_ph', 'Transfer / Cash'); ?>"></div>
                    <div class="col-md-6 mb-2"><label class="small font-weight-bold"><?= lang_line('erf_pay_ref', 'Reference #'); ?></label><input type="text" name="reference_number" class="form-control" placeholder="<?= lang_line('erf_pay_ref_ph', 'No transfer / no bukti'); ?>"></div>
                    <div class="col-md-12 mb-2"><label class="small font-weight-bold"><?= lang_line('erf_pay_notes', 'Notes'); ?></label><textarea name="notes" class="form-control" rows="2"></textarea></div>
                    <div class="col-md-12 mb-2">
                        <label class="small font-weight-bold"><?= lang_line('erf_pay_proof', 'Bukti Bayar'); ?> <span class="text-muted">(PDF/JPG/PNG, maks 10 MB)</span></label>
                        <input type="file" name="payment_proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> <?= lang_line('erf_btn_record_payment', 'Record Payment'); ?></button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
            </div>
        </div>
    </div>
</div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var amt = document.getElementById('erfPaymentAmount');
    var fee = document.getElementById('erfTransferFee');
    var out = document.getElementById('erfTotalCashOut');
    if (!amt || !fee || !out) return;
    function fmt(n){ return 'Rp ' + (n).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function recalc(){
        var total = (parseFloat(amt.value) || 0) + (parseFloat(fee.value) || 0);
        out.textContent = fmt(total);
    }
    amt.addEventListener('input', recalc);
    fee.addEventListener('input', recalc);
    recalc();
});
</script>
<?php endif; ?>

<!-- Modal: Upload/Replace Payment Proof -->
<?php if (!empty($can_upload_payment_proof)): ?>
<div class="modal fade" id="uploadErfProofModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-receipt mr-2"></i> <?= lang_line('erf_upload_proof_title', 'Upload Bukti Bayar'); ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="uploadErfProofForm" method="POST" enctype="multipart/form-data" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="small font-weight-bold"><?= lang_line('erf_pay_proof', 'Bukti Bayar'); ?> <span class="text-muted">(PDF/JPG/PNG, maks 10 MB)</span></label>
                        <input type="file" name="payment_proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <small class="text-muted"><?= lang_line('erf_replace_proof_note', 'Bukti lama akan diganti dengan file ini.'); ?></small>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success"><i class="fas fa-upload mr-1"></i> <?= lang_line('erf_btn_upload', 'Upload'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var modal = document.getElementById('uploadErfProofModal');
    if (!modal) return;
    var base = '<?= base_url('erf/upload_payment_proof/'); ?>';
    $(modal).on('show.bs.modal', function(event){
        var btn = event.relatedTarget;
        if (!btn) return;
        var pid = btn.getAttribute('data-payment-id');
        document.getElementById('uploadErfProofForm').action = base + pid;
    });
});
</script>
<?php endif; ?>

<!-- Modal: View Attachment -->
<div class="modal fade" id="attachmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-file mr-2"></i> <span id="attachmentName"></span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="attachmentBody" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p><?= lang_line('erf_att_loading', 'Loading...'); ?></p>
                </div>
            </div>
            <div class="modal-footer">
                <a id="attachmentDownload" href="#" class="btn btn-primary" download><i class="fas fa-download mr-1"></i> <?= lang_line('erf_att_download', 'Download'); ?></a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('erf_att_close', 'Close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-view-attachment').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const fileId = this.getAttribute('data-id');
            const fileName = this.getAttribute('data-name');
            const fileUrl = this.getAttribute('data-url');
            const fileExtension = fileName.split('.').pop().toLowerCase();

            document.getElementById('attachmentName').textContent = fileName;
            const downloadBtn = document.getElementById('attachmentDownload');
            downloadBtn.href = fileUrl;
            downloadBtn.setAttribute('download', fileName);

            const attachmentBody = document.getElementById('attachmentBody');

            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

            if (imageExtensions.includes(fileExtension)) {
                attachmentBody.innerHTML = '<img src="' + fileUrl + '" style="max-width: 100%; height: auto;" class="rounded" />';
            } else if (fileExtension === 'pdf') {
                attachmentBody.innerHTML = '<embed src="' + fileUrl + '" type="application/pdf" style="width: 100%; height: 500px;" />';
            } else {
                attachmentBody.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-file fa-3x mb-3 text-secondary"></i><p><?= lang_line('erf_att_no_preview', 'Preview tidak tersedia untuk tipe file ini.'); ?></p><p class="small"><?= lang_line('erf_att_click_download', 'Klik Download untuk membuka file.'); ?></p></div>';
            }

            $('#attachmentModal').modal('show');
        });
    });
});
</script>
