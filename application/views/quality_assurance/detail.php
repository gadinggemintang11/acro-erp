<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Quality Assurance - Detail (redesign 2026-11-08, selaras halaman produksi/MR).
 * Alur: GRN dikonfirmasi -> QC per item (In Progress) -> Complete QA (Draft,
 * menunggu approval) -> Approve (Completed: qty PASS masuk stok) / Reject.
 * Modal & fungsi JS (editQCItem, viewQCItem, bulk*, approve/unapprove/reject)
 * TIDAK diubah perilakunya -- ID elemen dipertahankan.
 */
$this->load->view('production_process/_pp_style');
$st   = $qa_header->status;
$qty  = function ($n) { $s = number_format((float) $n, 4, ',', '.'); return rtrim(rtrim($s, '0'), ','); };
$dt   = function ($v, $t = true) { return (!empty($v) && strpos((string) $v, '0000') !== 0) ? date($t ? 'd M Y H:i' : 'd M Y', strtotime($v)) : null; };
$n_checked = 0; $sum_recv = 0; $sum_pass = 0; $sum_ng = 0;
foreach ($qa_details as $d) {
    if ($d->qa_status !== 'Pending') $n_checked++;
    $sum_recv += (float) $d->receive_qty; $sum_pass += (float) $d->qa_pass_qty; $sum_ng += (float) $d->qa_reject_qty;
}
$pill = ['Pending' => 'pp-status--other', 'In Progress' => 'pp-status--progress', 'Draft' => 'pp-status--draft',
         'Completed' => 'pp-status--completed', 'Rejected' => 'pp-status--other'][$st] ?? 'pp-status--other';
$level = ['Pending' => 1, 'In Progress' => 1, 'Draft' => 2, 'Completed' => 3, 'Rejected' => 2][$st] ?? 0;
$steps = [
    [lang_line('qa2_step_grn', 'Barang diterima (GRN)'),   'fa-truck',        $qa_header->created_by_name ?? null, $dt($qa_header->created_at)],
    [lang_line('qa2_step_qc', 'QC per item'),              'fa-microscope',   $qa_header->qa_checked_by_name ?? null, $dt($qa_header->qa_checked_at)],
    [lang_line('qa2_step_approval', 'Approval atasan'),    'fa-user-check',   null, null],
    [lang_line('qa2_step_stock', 'Masuk stok gudang'),     'fa-warehouse',    null, null],
];
if (!empty($latest_approval)) { $steps[2][2] = $latest_approval->approver_name ?? null; $steps[2][3] = $dt($latest_approval->approval_date ?? null); }
$qa_status_tone = ['Pending' => 'tone-slate', 'Pass' => 'tone-green', 'Reject' => 'tone-rose', 'Partial' => 'tone-amber'];
?>
<style>
.qa2 .mr-steps { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .6rem; margin-bottom: 1.25rem; }
.qa2 .mr-step { display: flex; gap: .65rem; padding: .8rem .9rem; border-radius: 14px; background: var(--pp-card); border: 1px solid var(--pp-line); box-shadow: var(--pp-shadow); min-width: 0; }
.qa2 .mr-step-dot { width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center; flex: 0 0 auto; font-size: .82rem; background: var(--pp-soft); color: var(--pp-muted); border: 1px solid var(--pp-line); }
.qa2 .mr-step.is-done .mr-step-dot { background: #10b981; color: #fff; border-color: transparent; }
.qa2 .mr-step.is-current { border-color: var(--brand-1); box-shadow: 0 0 0 3px rgba(var(--brand-rgb), .12), var(--pp-shadow); }
.qa2 .mr-step.is-current .mr-step-dot { background: var(--brand-1); color: #fff; border-color: transparent; }
.qa2 .mr-step.is-rejected { border-color: #f43f5e; } .qa2 .mr-step.is-rejected .mr-step-dot { background: #f43f5e; color: #fff; border-color: transparent; }
.qa2 .mr-step-name { font-weight: 700; font-size: .84rem; }
.qa2 .mr-step-meta { font-size: .72rem; color: var(--pp-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (max-width: 991.98px) { .qa2 .mr-steps { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
.qa2 .qa-note { display: flex; gap: .7rem; align-items: center; justify-content: space-between; flex-wrap: wrap; padding: .85rem 1rem; border-radius: 12px; margin-bottom: 1.25rem; font-size: .87rem; border: 1px solid transparent; }
.qa2 .qa-note--ok { background: rgba(16,185,129,.1); color: #065f46; border-color: rgba(16,185,129,.25); }
.qa2 .qa-note--warn { background: rgba(245,158,11,.1); color: #92400e; border-color: rgba(245,158,11,.3); }
.qa2 .qa-note--bad { background: rgba(244,63,94,.08); color: #9f1239; border-color: rgba(244,63,94,.25); }
body.dark-mode .qa2 .qa-note--ok { color: #6ee7b7; } body.dark-mode .qa2 .qa-note--warn { color: #fcd34d; } body.dark-mode .qa2 .qa-note--bad { color: #fda4af; }
.qa2 .qa-reason { font-size: .74rem; color: #be123c; max-width: 220px; }
body.dark-mode .qa2 .qa-reason { color: #fda4af; }
.qa2 .qa-actbar { display: flex; gap: .6rem; flex-wrap: wrap; align-items: center; justify-content: flex-end; padding: .85rem 1.1rem; border-top: 1px solid var(--pp-line); background: var(--pp-soft); border-radius: 0 0 var(--pp-radius) var(--pp-radius); }
</style>

<div class="container-fluid pp2 qa2">

    <section class="pp-hero">
        <div class="pp-hero-top">
            <div class="pp-hero-id">
                <div class="pp-hero-icon"><i class="fas fa-microscope"></i></div>
                <div style="min-width:0">
                    <div class="pp-hero-kicker">Quality Assurance · <?= lang_line('qa2_incoming', 'Incoming Material'); ?></div>
                    <h1 class="pp-hero-title"><?= htmlspecialchars($qa_header->qa_number); ?> <span class="pp-status <?= $pill; ?>"><?= htmlspecialchars($st); ?></span></h1>
                    <div class="pp-hero-subline"><i class="fas fa-truck mr-1"></i>GRN <?= htmlspecialchars($qa_header->grn_number); ?><span class="mx-1">·</span><i class="fas fa-industry mr-1"></i><?= htmlspecialchars($qa_header->nama_supplier ?? '-'); ?></div>
                </div>
            </div>
            <div class="pp-hero-actions">
                <?php if ($can_edit && $st === 'In Progress'): ?>
                    <button type="button" class="btn pp-btn-light" onclick="completeQA(<?= (int) $qa_header->id; ?>)"><i class="fas fa-check-circle mr-1"></i><?= lang_line('quality_assurance_complete_button', 'Complete QA'); ?></button>
                <?php endif; ?>
                <?php if ($can_approve && $st === 'Draft'): ?>
                    <span id="approvalActions" class="d-inline-flex" style="gap:.5rem">
                        <button type="button" class="btn pp-btn-light" data-toggle="modal" data-target="#approveModal"><i class="fas fa-check-circle mr-1"></i><?= lang_line('quality_assurance_approve_button', 'Approve QA'); ?></button>
                        <button type="button" class="btn pp-btn-glass" data-toggle="modal" data-target="#rejectModal"><i class="fas fa-times-circle mr-1"></i><?= lang_line('qa_reject_final_button', 'Reject'); ?></button>
                    </span>
                <?php endif; ?>
                <a href="<?= base_url('quality_assurance/export_detail_excel/' . $qa_header->id); ?>" class="btn pp-btn-glass"><i class="fas fa-file-excel mr-1"></i>Excel</a>
                <a href="<?= base_url('quality_assurance/export_detail_pdf/' . $qa_header->id); ?>" class="btn pp-btn-glass" target="_blank"><i class="fas fa-file-pdf mr-1"></i>PDF</a>
                <a href="<?= base_url('quality_assurance'); ?>" class="btn pp-btn-glass"><i class="fas fa-arrow-left mr-1"></i><?= lang_line('quality_assurance_back_button', 'Back'); ?></a>
            </div>
        </div>
        <div class="pp-hero-meta">
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('quality_assurance_qa_date_label', 'QA Date'); ?></div><div class="pp-meta-value"><?= $dt($qa_header->qa_date, false) ?: '-'; ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('qa2_checked', 'Item diperiksa'); ?></div><div class="pp-meta-value"><?= $n_checked; ?> <small>/ <?= count($qa_details); ?></small></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('quality_assurance_receive_qty_label', 'Receive Qty'); ?></div><div class="pp-meta-value"><?= $qty($sum_recv); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('quality_assurance_pass_qty_label', 'Pass Qty'); ?></div><div class="pp-meta-value"><?= $qty($sum_pass); ?></div></div>
            <div class="pp-meta"><div class="pp-meta-label"><?= lang_line('quality_assurance_ng_qty_label', 'NG Qty'); ?></div><div class="pp-meta-value"><?= $qty($sum_ng); ?></div></div>
            <?php if (!empty($qa_header->inspector_notes)): ?>
            <div class="pp-meta" style="grid-column: span 2;"><div class="pp-meta-label"><?= lang_line('quality_assurance_inspector_notes_label', 'Inspector Notes'); ?></div><div class="pp-meta-value" style="font-weight:600" title="<?= htmlspecialchars($qa_header->inspector_notes); ?>"><?= htmlspecialchars($qa_header->inspector_notes); ?></div></div>
            <?php endif; ?>
        </div>
    </section>

    <nav class="mr-steps">
        <?php foreach ($steps as $i => $s):
            $cls = ($st === 'Rejected' && $i === 2) ? 'is-rejected' : ($i < $level || ($st === 'Completed' && $i <= 3) ? 'is-done' : ($i === $level ? 'is-current' : ''));
            $name = ($st === 'Rejected' && $i === 2) ? lang_line('mr2_rejected', 'Ditolak') : $s[0]; ?>
        <div class="mr-step <?= $cls; ?>">
            <span class="mr-step-dot"><i class="fas <?= $cls === 'is-done' ? 'fa-check' : ($cls === 'is-rejected' ? 'fa-times' : $s[1]); ?>"></i></span>
            <span style="min-width:0"><span class="mr-step-name d-block"><?= htmlspecialchars($name); ?></span>
                <span class="mr-step-meta d-block"><?= ($cls === 'is-done' || $cls === 'is-rejected') ? htmlspecialchars(trim(($s[2] ?: '') . ($s[3] ? ' · ' . $s[3] : '')) ?: '✓') : ($cls === 'is-current' ? lang_line('mr2_waiting', 'Menunggu') : '—'); ?></span></span>
        </div>
        <?php endforeach; ?>
    </nav>

    <?php if ($st === 'Completed'): ?>
        <div class="qa-note qa-note--ok"><span><i class="fas fa-check-circle mr-2"></i><b><?= lang_line('quality_assurance_approved_message', 'QA has been Approved'); ?></b> <?= lang_line('qa_approved_passed_to_stock', ''); ?></span>
            <?php if (!empty($can_unapprove)): ?><button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#unapproveModal"><i class="fas fa-undo mr-1"></i>Unapprove</button><?php endif; ?></div>
    <?php elseif ($st === 'Rejected'): ?>
        <div class="qa-note qa-note--bad"><span><i class="fas fa-times-circle mr-2"></i><b>QA Rejected (final).</b> <?= lang_line('qa2_rejected_note', 'Stok tidak masuk ledger. Klik Reopen untuk recovery jika salah klik.'); ?></span></div>
    <?php elseif ($st === 'Draft'): ?>
        <div class="qa-note qa-note--warn"><span><i class="fas fa-clock mr-2"></i><b><?= lang_line('qa2_waiting_approval', 'QA menunggu approval atasan.'); ?></b> <?= lang_line('qa2_stock_not_yet', 'Stok belum masuk ledger.'); ?></span></div>
    <?php endif; ?>

    <div class="pp-card">
        <div class="pp-card-head">
            <h2><span class="pp-ico"><i class="fas fa-clipboard-check"></i></span><span><?= lang_line('quality_assurance_items_control_title', 'Items Quality Control'); ?> (<?= count($qa_details); ?>)
                <span class="d-block pp-sub"><?= !empty($can_edit_items) ? lang_line('qa2_items_sub', 'Klik ikon edit per item, atau centang beberapa item lalu Pass / Reject sekaligus.') : lang_line('qa2_items_sub_ro', 'Hasil pemeriksaan per item.'); ?></span></span></h2>
            <?php if (!empty($can_edit_items)): ?>
            <div class="d-flex align-items-center flex-wrap" style="gap:.4rem">
                <span id="bulkSelectionInfo" class="small" style="display:none;color:var(--pp-muted)"></span>
                <button type="button" id="btnBulkPass" class="btn btn-sm btn-success" style="display:none;" onclick="bulkPassSelected()"><i class="fas fa-check mr-1"></i>Pass Selected</button>
                <button type="button" id="btnBulkReject" class="btn btn-sm btn-danger" style="display:none;" data-toggle="modal" data-target="#bulkRejectModal"><i class="fas fa-times mr-1"></i>Reject Selected</button>
            </div>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <?php if (!empty($can_edit_items)): ?><th style="width:34px"><input type="checkbox" id="bulkSelectAll" onclick="toggleSelectAll(this)" title="Select all"></th><?php endif; ?>
                    <th><?= lang_line('quality_assurance_item_name_label', 'Item Name'); ?></th>
                    <th class="num"><?= lang_line('quality_assurance_receive_qty_label', 'Receive Qty'); ?></th>
                    <th class="num"><?= lang_line('quality_assurance_pass_qty_label', 'Pass Qty'); ?></th>
                    <th class="num"><?= lang_line('quality_assurance_ng_qty_label', 'NG Qty'); ?></th>
                    <th><?= lang_line('quality_assurance_qc_status_label', 'Status'); ?></th>
                    <th><?= lang_line('qa2_th_ng_info', 'Keterangan NG'); ?></th>
                    <th><?= lang_line('quality_assurance_pic_label', 'PIC'); ?></th>
                    <th class="text-right"><?= lang_line('quality_assurance_action_label', 'Action'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (empty($qa_details)): ?>
                    <tr><td colspan="9" class="pp-empty"><i class="fas fa-inbox"></i><?= lang_line('pr_no_items', 'Tidak ada item'); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($qa_details as $detail): $is_ng = in_array($detail->qa_status, ['Reject', 'Partial'], true); ?>
                    <tr id="qa-row-<?= (int) $detail->id; ?>">
                        <?php if (!empty($can_edit_items)): ?><td><input type="checkbox" class="bulk-row-check" value="<?= (int) $detail->id; ?>" onchange="updateBulkUI()"></td><?php endif; ?>
                        <td><div class="pp-item-name"><?= htmlspecialchars($detail->item_name ?? '-'); ?></div><div class="pp-item-code"><?= htmlspecialchars($detail->item_code ?? '-'); ?></div></td>
                        <td class="num"><b><?= $qty($detail->receive_qty); ?></b></td>
                        <td class="num text-success font-weight-bold"><?= $qty($detail->qa_pass_qty); ?></td>
                        <td class="num<?= $detail->qa_reject_qty > 0 ? ' text-danger font-weight-bold' : ''; ?>"><?= $qty($detail->qa_reject_qty); ?></td>
                        <td><span class="pp-tag <?= $qa_status_tone[$detail->qa_status] ?? 'tone-slate'; ?>"><?= htmlspecialchars($detail->qa_status); ?></span></td>
                        <td>
                            <?php if ($is_ng): ?>
                                <?php if (!empty($detail->ng_category)): ?><span class="pp-chip pp-chip--warn"><?= htmlspecialchars($detail->ng_category); ?></span><?php endif; ?>
                                <div class="qa-reason mt-1" title="<?= htmlspecialchars($detail->reject_reason ?? ''); ?>"><?= htmlspecialchars(mb_strimwidth((string) ($detail->reject_reason ?? ''), 0, 60, '…')); ?></div>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td><span class="pp-item-code" style="font-size:.8rem"><?= htmlspecialchars($detail->pic ?: '—'); ?></span></td>
                        <td class="text-right">
                            <div class="pp-actions">
                                <?php if (!empty($detail->photo_link)): ?>
                                    <button type="button" class="btn btn-outline-info" onclick="previewPhoto('<?= addslashes(htmlspecialchars($detail->photo_link)); ?>')" title="<?= htmlspecialchars(lang_line('quality_assurance_preview_photo', 'Preview Photo')); ?>"><i class="fas fa-image"></i></button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-light" data-toggle="modal" data-target="#viewQCModal" onclick="viewQCItem(<?= (int) $detail->id; ?>)" title="<?= htmlspecialchars(lang_line('quality_assurance_view_detail', 'View Detail')); ?>"><i class="fas fa-eye"></i></button>
                                <?php if (!empty($can_edit_items)): ?>
                                    <button type="button" class="btn btn-pp" data-toggle="modal" data-target="#qcModal" onclick="editQCItem(<?= (int) $detail->id; ?>, <?= (float) $detail->receive_qty; ?>)" title="<?= $st === 'Draft' ? 'Koreksi item (status Draft)' : 'Edit QC item'; ?>"><i class="fas fa-edit"></i></button>
                                <?php endif; ?>
                                <?php if (!empty($detail->attachments)): ?>
                                    <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#attachmentModal" onclick="showAttachments(<?= (int) $detail->id; ?>)"><i class="fas fa-paperclip mr-1"></i><?= count($detail->attachments); ?></button>
                                <?php elseif (!empty($can_edit_items) && $is_ng): ?>
                                    <button type="button" class="btn btn-outline-secondary" data-toggle="modal" data-target="#attachmentModal" onclick="showAttachmentUpload(<?= (int) $detail->id; ?>)" title="Attachment"><i class="fas fa-paperclip"></i><i class="fas fa-plus" style="font-size:.55rem"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($can_reopen)): ?>
        <div class="qa-actbar">
            <span class="small mr-auto" style="color:var(--pp-muted)"><?= $st === 'Rejected' ? lang_line('qa2_reopen_hint_rej', 'Salah reject? Pulihkan dokumen ini.') : lang_line('qa_reset_re_qc_note', ''); ?></span>
            <button type="button" class="btn btn-light" onclick="reopenQA(<?= (int) $qa_header->id; ?>, '<?= htmlspecialchars($st); ?>')"><i class="fas fa-redo mr-1"></i><?= $st === 'Rejected' ? lang_line('qa_reopen_undo_reject', 'Reopen') : lang_line('qa_reset_re_qc', 'Reset & Re-QC'); ?></button>
        </div>
        <?php endif; ?>
    </div>

    <?php if (in_array($st, ['Draft', 'Completed', 'Rejected'], true) && !empty($approval_logs)): ?>
    <div class="pp-card">
        <div class="pp-card-head"><h2><span class="pp-ico"><i class="fas fa-history"></i></span><?= lang_line('quality_assurance_approval_history_title', 'Approval History'); ?></h2></div>
        <div class="table-responsive">
            <table class="pp-table">
                <tbody>
                <?php foreach ($approval_logs as $log):
                    $tone = ['Approved' => 'tone-green', 'Rejected' => 'tone-rose', 'Unapproved' => 'tone-blue', 'Pending Review' => 'tone-amber'][$log->approval_status] ?? 'tone-slate'; ?>
                    <tr>
                        <td style="width:220px"><div class="pp-item-name"><?= htmlspecialchars($log->approver_name); ?></div><div class="pp-item-code"><?= $dt($log->approval_date); ?></div></td>
                        <td style="width:140px"><span class="pp-tag <?= $tone; ?>"><?= htmlspecialchars($log->approval_status); ?></span></td>
                        <td><span style="font-size:.84rem"><?= nl2br(htmlspecialchars($log->approval_notes ?? '-')); ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ===== MODALS ===== -->

<!-- QC Edit Modal -->
<div class="modal fade pp-modal" id="qcModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit mr-2"></i><?= lang_line('quality_assurance_qc_input_modal_title', 'Input QC Results'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="qcForm">
                    <input type="hidden" id="qa_detail_id" name="qa_detail_id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('quality_assurance_receive_qty', 'Receive Qty'); ?></label>
                                <input type="text" class="form-control font-weight-bold" id="max_qty_display" readonly
                                       style="background:#f8f9fa; color:#333;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('quality_assurance_pic_label_short', 'PIC (Person In Charge)'); ?> <span class="text-muted">(<?= lang_line('quality_assurance_photo_link_optional', 'Optional'); ?>)</span></label>
                                <input type="text" class="form-control" id="pic" name="pic"
                                       placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_pic')); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="qa_reject_qty"><?= lang_line('quality_assurance_ng_qty_label_short', 'NG Qty'); ?> <span class="text-danger">*</span></label>
                                <input type="number" class="form-control border-danger" id="qa_reject_qty" name="qa_reject_qty"
                                       step="0.01" min="0" required oninput="calcPassQty()">
                                <small class="form-text text-muted"><?= lang_line('quality_assurance_ng_qty_help', 'Qty that failed QC'); ?></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><?= lang_line('quality_assurance_pass_qty_label_short', 'Pass Qty'); ?> <small class="text-muted"><?= lang_line('quality_assurance_pass_qty_auto', '(automatic)'); ?></small></label>
                                <div class="form-control bg-success text-white font-weight-bold" id="pass_qty_display"
                                     style="border-color:#28a745;">0</div>
                                <small class="form-text text-muted"><?= lang_line('quality_assurance_pass_qty_formula', 'Receive Qty − NG Qty'); ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="ng_category"><?= lang_line('quality_assurance_ng_category_short', 'NG Category'); ?></label>
                                <input type="text" class="form-control" id="ng_category" name="ng_category"
                                       placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_ng_examples')); ?>">
                                <small class="form-text text-muted"><?= lang_line('quality_assurance_ng_category_required', 'Required if there is NG'); ?></small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reject_reason"><?= lang_line('quality_assurance_reject_reason_short', 'Reject Reason / NG Description'); ?></label>
                                <input type="text" class="form-control" id="reject_reason" name="reject_reason"
                                       placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_ng_short')); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reject_description"><?= lang_line('quality_assurance_detail_description', 'Detail Description'); ?></label>
                        <textarea class="form-control" id="reject_description" name="reject_description"
                                  rows="2" placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_ng_desc')); ?>"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="photo_link"><?= lang_line('quality_assurance_photo_link_label', 'Photo Evidence Link'); ?> <span class="text-muted"><?= lang_line('quality_assurance_photo_link_optional', 'Optional'); ?></span></label>
                        <div class="input-group">
                            <input type="url" class="form-control" id="photo_link" name="photo_link"
                                   placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_photo_url')); ?>">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-info" onclick="previewPhotoFromInput()">
                                    <i class="fas fa-eye"></i> <?= lang_line('qa_preview_button'); ?>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            <i class="fab fa-google-drive text-success"></i> Google Drive &nbsp;|&nbsp;
                            <i class="fas fa-image text-info"></i> <?= lang_line('quality_assurance_photo_link_help', 'Photo Evidence Link'); ?>
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('quality_assurance_modal_cancel', 'Cancel'); ?></button>
                <button type="button" class="btn btn-primary" onclick="saveQCItem()">
                    <i class="fas fa-save"></i> <?= lang_line('quality_assurance_modal_save', 'Save'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View QC Detail Modal -->
<div class="modal fade pp-modal" id="viewQCModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-search mr-2"></i><?= lang_line('quality_assurance_qc_view_modal_title', 'QC Results Detail'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 text-center p-3">
                            <small class="text-muted"><?= lang_line('quality_assurance_receive_qty', 'Receive Qty'); ?></small>
                            <div class="h4 mb-0" id="view_receive_qty">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success text-center p-3">
                            <small class="text-muted"><?= lang_line('quality_assurance_pass_qty_label_short', 'Pass Qty'); ?></small>
                            <div class="h4 mb-0 text-success font-weight-bold" id="view_qa_pass_qty">-</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-danger text-center p-3">
                            <small class="text-muted"><?= lang_line('quality_assurance_ng_qty_label_short', 'NG Qty'); ?></small>
                            <div class="h4 mb-0 text-danger font-weight-bold" id="view_qa_reject_qty">-</div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="font-weight-bold"><?= lang_line('quality_assurance_pic_label', 'PIC'); ?></label>
                        <p id="view_pic" class="form-control-plaintext border-bottom">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="font-weight-bold"><?= lang_line('quality_assurance_ng_category_label', 'NG Category'); ?></label>
                        <p id="view_ng_category" class="form-control-plaintext border-bottom">-</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="font-weight-bold"><?= lang_line('quality_assurance_reject_reason_label', 'Reject Reason'); ?></label>
                        <p id="view_reject_reason" class="form-control-plaintext border-bottom">-</p>
                    </div>
                    <div class="col-md-6">
                        <label class="font-weight-bold"><?= lang_line('quality_assurance_detail_description', 'Detail Description'); ?></label>
                        <p id="view_reject_description" class="form-control-plaintext border-bottom" style="white-space: pre-wrap;">-</p>
                    </div>
                </div>

                <!-- Photo Preview Section -->
                <div id="view_photo_section">
                    <label class="font-weight-bold"><i class="fas fa-image mr-1"></i><?= lang_line('quality_assurance_photo_label', 'Photo'); ?></label>
                    <div id="view_photo_preview_container" style="display:none;">
                        <div id="view_photo_preview" style="min-height:300px; border:1px solid #dee2e6; border-radius:4px; background:#f8f9fa; overflow:hidden; margin-bottom:8px;"></div>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="view_photo_url_text" readonly>
                            <div class="input-group-append">
                                <a id="view_photo_open_btn" href="#" target="_blank" class="btn btn-info btn-sm">
                                    <i class="fas fa-external-link-alt"></i> <?= lang_line('quality_assurance_open_link', 'Open'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div id="view_photo_empty" class="text-muted">
                        <i class="fas fa-image mr-1"></i> <?= lang_line('quality_assurance_no_photo_evidence', 'No photo evidence'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('quality_assurance_modal_close', 'Close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Photo Preview Modal (standalone) -->
<div class="modal fade pp-modal" id="photoPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-image mr-2"></i><?= lang_line('quality_assurance_photo_preview_modal', 'Photo Preview'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <div id="standalone_photo_preview" style="min-height:400px; background:#f8f9fa;"></div>
            </div>
            <div class="modal-footer">
                <a id="standalone_photo_open_btn" href="#" target="_blank" class="btn btn-info">
                    <i class="fas fa-external-link-alt"></i> <?= lang_line('quality_assurance_open_new_tab', 'Open in New Tab'); ?>
                </a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('quality_assurance_modal_close', 'Close'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Attachment Modal -->
<div class="modal fade pp-modal" id="attachmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang_line('quality_assurance_attachments_title', 'Reject Attachments'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="attachmentList"></div>
                <hr>
                <h6><?= lang_line('quality_assurance_add_attachment', 'Add Attachment/Link'); ?></h6>
                <form id="attachmentForm">
                    <input type="hidden" id="attachment_qa_detail_id" name="qa_detail_id">
                    <div class="form-group">
                        <label for="attachment_url"><?= lang_line('quality_assurance_attachment_url', 'URL/Link'); ?> <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="attachment_url" name="attachment_url"
                               placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_photo_url_short')); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="attachment_type"><?= lang_line('quality_assurance_attachment_type', 'Type'); ?></label>
                                <select class="form-control" id="attachment_type" name="attachment_type">
                                    <option value=""><?= lang_line('quality_assurance_attachment_type_select', '-- Select Type --'); ?></option>
                                    <option value="photo"><?= lang_line('quality_assurance_attachment_type_photo', 'Photo'); ?></option>
                                    <option value="video"><?= lang_line('quality_assurance_attachment_type_video', 'Video'); ?></option>
                                    <option value="document"><?= lang_line('quality_assurance_attachment_type_document', 'Document'); ?></option>
                                    <option value="other"><?= lang_line('quality_assurance_attachment_type_other', 'Other'); ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="attachment_description"><?= lang_line('quality_assurance_attachment_description', 'Description'); ?></label>
                                <input type="text" class="form-control" id="attachment_description"
                                       name="attachment_description" placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_attachment_desc')); ?>">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('quality_assurance_modal_close', 'Close'); ?></button>
                <button type="button" class="btn btn-primary" onclick="addAttachment()">
                    <i class="fas fa-upload"></i> <?= lang_line('quality_assurance_attachment_add_button', 'Add'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade pp-modal" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang_line('quality_assurance_approve_modal_title', 'Approve Quality Assurance'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="approveForm">
                    <div class="form-group">
                        <label for="approval_notes"><?= lang_line('quality_assurance_approval_notes_label', 'Approval Notes'); ?></label>
                        <textarea class="form-control" id="approval_notes" name="approval_notes"
                                  rows="4" placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_approval_notes')); ?>"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('quality_assurance_modal_cancel', 'Cancel'); ?></button>
                <button type="button" class="btn btn-success" onclick="approveQA(<?php echo $qa_header->id; ?>)">
                    <i class="fas fa-check"></i> <?= lang_line('quality_assurance_modal_approve_button', 'Approve'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Reject Modal -->
<div class="modal fade pp-modal" id="bulkRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang_line('qa_bulk_reject_modal_title'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3"><?= str_replace('%s', '<span id="bulkRejectCount">0</span>', lang_line('qa_bulk_reject_modal_desc')); ?></p>
                <form id="bulkRejectForm">
                    <div class="form-group">
                        <label><?= lang_line('qa_bulk_reject_reason_label'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="bulk_reject_reason" placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_bulk_reject')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('qa_bulk_ng_category_label'); ?> <small class="text-muted"><?= lang_line('qa_bulk_optional_note'); ?></small></label>
                        <input type="text" class="form-control" id="bulk_ng_category" placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_bulk_ng_cat')); ?>">
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('qa_bulk_pic_label'); ?> <small class="text-muted"><?= lang_line('qa_bulk_optional_note'); ?></small></label>
                        <input type="text" class="form-control" id="bulk_pic" placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_bulk_pic')); ?>">
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('qa_bulk_reject_desc_label'); ?> <small class="text-muted"><?= lang_line('qa_bulk_optional_note'); ?></small></label>
                        <textarea class="form-control" id="bulk_reject_description" rows="2" placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_bulk_extra')); ?>"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('quality_assurance_modal_cancel', 'Cancel'); ?></button>
                <button type="button" class="btn btn-danger" onclick="bulkRejectSelected()">
                    <i class="fas fa-times"></i> <?= lang_line('qa_bulk_reject_button'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Unapprove Modal -->
<div class="modal fade pp-modal" id="unapproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang_line('qa_unapprove_modal_title'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong><?= lang_line('qa_unapprove_attention'); ?></strong> <?= lang_line('qa_unapprove_warning'); ?>
                    <hr class="my-2">
                    <small><?= lang_line('qa_unapprove_will_fail'); ?></small>
                </div>
                <form id="unapproveForm">
                    <div class="form-group">
                        <label for="unapprove_notes"><?= lang_line('qa_unapprove_reason_label'); ?> <small class="text-muted"><?= lang_line('qa_bulk_optional_note'); ?></small></label>
                        <textarea class="form-control" id="unapprove_notes" name="unapprove_notes"
                                  rows="3" placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_reset_reason')); ?>"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('quality_assurance_modal_cancel', 'Cancel'); ?></button>
                <button type="button" class="btn btn-danger" onclick="unapproveQA(<?php echo $qa_header->id; ?>)">
                    <i class="fas fa-undo"></i> <?= lang_line('qa_unapprove_button'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade pp-modal" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= lang_line('quality_assurance_reject_modal_title', 'Reject Quality Assurance'); ?></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <div class="form-group">
                        <label for="rejection_notes"><?= lang_line('quality_assurance_rejection_notes_label', 'Rejection Notes'); ?> <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_notes" name="rejection_notes"
                                  rows="4" placeholder="<?= htmlspecialchars(lang_line('qa_placeholder_reject_reason')); ?>" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('quality_assurance_modal_cancel', 'Cancel'); ?></button>
                <button type="button" class="btn btn-danger" onclick="rejectQA(<?php echo $qa_header->id; ?>)">
                    <i class="fas fa-times"></i> <?= lang_line('quality_assurance_modal_reject_button', 'Reject'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
const I18N_QA = {
    alert_ng_negative:         <?= json_encode(lang_line('qa_alert_ng_negative')); ?>,
    alert_ng_exceeds:          <?= json_encode(lang_line('qa_alert_ng_exceeds')); ?>,
    alert_ng_category_required:<?= json_encode(lang_line('qa_alert_ng_category_required')); ?>,
    alert_error_prefix:        <?= json_encode(lang_line('qa_alert_error_prefix')); ?>,
    alert_error_saving:        <?= json_encode(lang_line('qa_alert_error_saving')); ?>,
    alert_load_qc_failed:      <?= json_encode(lang_line('qa_alert_load_qc_failed')); ?>,
    alert_photo_url_required:  <?= json_encode(lang_line('qa_alert_photo_url_required')); ?>,
    alert_attachment_added:    <?= json_encode(lang_line('qa_alert_attachment_added')); ?>,
    alert_error_attachment:    <?= json_encode(lang_line('qa_alert_error_attachment')); ?>,
    confirm_bulk_pass:         <?= json_encode(lang_line('qa_confirm_bulk_pass')); ?>,
    alert_error_bulk_pass:     <?= json_encode(lang_line('qa_alert_error_bulk_pass')); ?>,
    alert_reject_reason_required: <?= json_encode(lang_line('qa_alert_reject_reason_required')); ?>,
    alert_error_bulk_reject:   <?= json_encode(lang_line('qa_alert_error_bulk_reject')); ?>,
    alert_bulk_update_done:    <?= json_encode(lang_line('qa_alert_bulk_update_done')); ?>,
    confirm_finish_qa:         <?= json_encode(lang_line('qa_confirm_finish_qa')); ?>,
    confirm_recovery_rejected: <?= json_encode(lang_line('qa_confirm_recovery_rejected')); ?>,
    confirm_reset_all:         <?= json_encode(lang_line('qa_confirm_reset_all')); ?>,
    alert_qa_approved:         <?= json_encode(lang_line('qa_alert_qa_approved')); ?>,
    alert_qa_rejected:         <?= json_encode(lang_line('qa_alert_qa_rejected')); ?>,
    alert_qa_rejected_note:    <?= json_encode(lang_line('qa_alert_qa_rejected_note')); ?>,
    alert_error_approve:       <?= json_encode(lang_line('qa_alert_error_approve')); ?>,
    alert_error_unapprove:     <?= json_encode(lang_line('qa_alert_error_unapprove')); ?>,
    alert_rejection_notes_required: <?= json_encode(lang_line('qa_alert_rejection_notes_required')); ?>,
    alert_error_reject:        <?= json_encode(lang_line('qa_alert_error_reject')); ?>,
    drive_label:               <?= json_encode(lang_line('qa_drive_label')); ?>,
    drive_no_preview:          <?= json_encode(lang_line('qa_drive_no_preview')); ?>,
    click_open_view:           <?= json_encode(lang_line('qa_click_open_view')); ?>
};

var maxReceiveQty = 0;

// ===================== EDIT QC ITEM =====================
function editQCItem(qaDetailId, maxQty) {
    maxReceiveQty = parseFloat(maxQty) || 0;
    document.getElementById('qa_detail_id').value = qaDetailId;
    document.getElementById('max_qty_display').value = maxQty;
    document.getElementById('qa_reject_qty').value = '';
    document.getElementById('pass_qty_display').textContent = maxQty;
    document.getElementById('pass_qty_display').className = 'form-control bg-success text-white font-weight-bold';
    document.getElementById('reject_reason').value = '';
    document.getElementById('ng_category').value = '';
    document.getElementById('reject_description').value = '';
    document.getElementById('photo_link').value = '';
    document.getElementById('pic').value = '';

    $.ajax({
        url: '<?php echo base_url("quality_assurance/get_qa_item/"); ?>' + qaDetailId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.data) {
                var d = response.data;
                document.getElementById('qa_reject_qty').value = d.qa_reject_qty || '';
                document.getElementById('reject_reason').value = d.reject_reason || '';
                document.getElementById('reject_description').value = d.reject_description || '';
                document.getElementById('photo_link').value = d.photo_link || '';
                document.getElementById('pic').value = d.pic || '';
                if (d.ng_category) {
                    document.getElementById('ng_category').value = d.ng_category;
                }
                calcPassQty();
            }
        }
    });
}

function calcPassQty() {
    var ngQty = parseFloat(document.getElementById('qa_reject_qty').value) || 0;
    var passQty = Math.max(0, maxReceiveQty - ngQty);
    var el = document.getElementById('pass_qty_display');
    el.textContent = passQty.toFixed(2);
    if (passQty <= 0 && ngQty > 0) {
        el.className = 'form-control bg-danger text-white font-weight-bold';
    } else if (ngQty > 0) {
        el.className = 'form-control bg-warning font-weight-bold';
    } else {
        el.className = 'form-control bg-success text-white font-weight-bold';
    }
}

function saveQCItem() {
    var qaDetailId = document.getElementById('qa_detail_id').value;
    var rejectQty  = parseFloat(document.getElementById('qa_reject_qty').value) || 0;
    var rejectReason = document.getElementById('reject_reason').value;
    var ngCategory   = document.getElementById('ng_category').value;
    var rejectDesc   = document.getElementById('reject_description').value;
    var photoLink    = document.getElementById('photo_link').value;
    var pic          = document.getElementById('pic').value;

    if (rejectQty < 0) {
        alert(I18N_QA.alert_ng_negative);
        return;
    }
    if (rejectQty > maxReceiveQty + 0.01) {
        alert(I18N_QA.alert_ng_exceeds.replace('%s', rejectQty).replace('%s', maxReceiveQty));
        return;
    }
    if (rejectQty > 0 && !ngCategory) {
        alert(I18N_QA.alert_ng_category_required);
        return;
    }

    $.ajax({
        url: '<?php echo base_url("quality_assurance/update_qa_item"); ?>',
        type: 'POST',
        data: {
            qa_detail_id:      qaDetailId,
            qa_reject_qty:     rejectQty,
            reject_reason:     rejectReason,
            ng_category:       ngCategory,
            reject_description: rejectDesc,
            photo_link:        photoLink,
            pic:               pic
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#qcModal').modal('hide');
                location.reload();
            } else {
                alert(I18N_QA.alert_error_prefix + response.message);
            }
        },
        error: function() {
            alert(I18N_QA.alert_error_saving);
        }
    });
}

// ===================== VIEW QC ITEM =====================
function viewQCItem(qaDetailId) {
    $.ajax({
        url: '<?php echo base_url("quality_assurance/get_qa_item/"); ?>' + qaDetailId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' && response.data) {
                var d = response.data;
                document.getElementById('view_receive_qty').textContent = parseFloat(d.receive_qty || 0).toFixed(2);
                document.getElementById('view_qa_pass_qty').textContent = parseFloat(d.qa_pass_qty || 0).toFixed(2);
                document.getElementById('view_qa_reject_qty').textContent = parseFloat(d.qa_reject_qty || 0).toFixed(2);
                document.getElementById('view_pic').textContent = d.pic || '-';
                document.getElementById('view_reject_reason').textContent = d.reject_reason || '-';
                document.getElementById('view_reject_description').textContent = d.reject_description || '-';

                // NG Category
                var ngCatEl = document.getElementById('view_ng_category');
                if (d.ng_category) {
                    ngCatEl.innerHTML = '<span class="badge badge-warning text-dark">' + escHtml(d.ng_category) + '</span>';
                } else {
                    ngCatEl.textContent = '-';
                }

                // Photo preview
                if (d.photo_link) {
                    document.getElementById('view_photo_preview_container').style.display = 'block';
                    document.getElementById('view_photo_empty').style.display = 'none';
                    document.getElementById('view_photo_url_text').value = d.photo_link;
                    document.getElementById('view_photo_open_btn').href = d.photo_link;
                    renderPhotoPreview(d.photo_link, 'view_photo_preview', 300);
                } else {
                    document.getElementById('view_photo_preview_container').style.display = 'none';
                    document.getElementById('view_photo_empty').style.display = 'block';
                }
            }
        },
        error: function() {
            alert(I18N_QA.alert_load_qc_failed);
        }
    });
}

// ===================== PHOTO PREVIEW =====================
function renderPhotoPreview(url, containerId, height) {
    var el = document.getElementById(containerId);
    height = height || 400;

    if (!url) {
        el.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted" style="min-height:' + height + 'px;"><i class="fas fa-image fa-3x"></i></div>';
        return;
    }

    var loadingHtml = '<div class="d-flex align-items-center justify-content-center" style="min-height:' + height + 'px;"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>';
    el.innerHTML = loadingHtml;

    // Google Drive
    if (url.indexOf('drive.google.com') !== -1) {
        var match = url.match(/\/d\/([^\/\?&]+)/);
        if (match && match[1]) {
            var iframeSrc = 'https://drive.google.com/file/d/' + match[1] + '/preview';
            el.innerHTML = '<iframe src="' + iframeSrc + '" width="100%" height="' + height + '" style="border:none; border-radius:4px;" allowfullscreen></iframe>';
        } else {
            el.innerHTML = '<div class="d-flex align-items-center justify-content-center flex-column text-muted" style="min-height:' + height + 'px;"><i class="fab fa-google-drive fa-3x mb-2 text-success"></i><p class="mb-0">' + I18N_QA.drive_label + ' Link</p><small>' + I18N_QA.drive_no_preview + '</small></div>';
        }
        return;
    }

    // Direct image or image hosting
    var isImage = url.match(/\.(jpg|jpeg|png|gif|webp|bmp|svg)(\?.*)?$/i)
                  || url.indexOf('imgur.com') !== -1
                  || url.indexOf('cloudinary.com') !== -1
                  || url.indexOf('imgbb.com') !== -1
                  || url.indexOf('ibb.co') !== -1
                  || url.indexOf('postimg.cc') !== -1;

    if (isImage) {
        var img = new Image();
        img.onload = function() {
            el.innerHTML = '<img src="' + url + '" style="max-width:100%; max-height:' + height + 'px; display:block; margin:auto; border-radius:4px; object-fit:contain;">';
        };
        img.onerror = function() {
            el.innerHTML = genericLinkPreview(url, height);
        };
        img.src = url;
        return;
    }

    // Generic link
    el.innerHTML = genericLinkPreview(url, height);
}

function genericLinkPreview(url, height) {
    return '<div class="d-flex align-items-center justify-content-center flex-column text-muted" style="min-height:' + height + 'px; padding:20px;">'
        + '<i class="fas fa-link fa-3x mb-3"></i>'
        + '<p class="mb-1 text-truncate" style="max-width:80%;">' + escHtml(url) + '</p>'
        + '<small>' + I18N_QA.click_open_view + '</small>'
        + '</div>';
}

function previewPhoto(url) {
    document.getElementById('standalone_photo_open_btn').href = url;
    renderPhotoPreview(url, 'standalone_photo_preview', 450);
    $('#photoPreviewModal').modal('show');
}

function previewPhotoFromInput() {
    var url = document.getElementById('photo_link').value;
    if (!url) {
        alert(I18N_QA.alert_photo_url_required);
        return;
    }
    previewPhoto(url);
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ===================== ATTACHMENTS =====================
function showAttachments(qaDetailId) {
    document.getElementById('attachment_qa_detail_id').value = qaDetailId;
}

function showAttachmentUpload(qaDetailId) {
    document.getElementById('attachment_qa_detail_id').value = qaDetailId;
}

function addAttachment() {
    var qaDetailId  = document.getElementById('attachment_qa_detail_id').value;
    var url         = document.getElementById('attachment_url').value;
    var type        = document.getElementById('attachment_type').value;
    var description = document.getElementById('attachment_description').value;

    $.ajax({
        url: '<?php echo base_url("quality_assurance/upload_attachment/"); ?>' + qaDetailId,
        type: 'POST',
        data: { attachment_url: url, attachment_type: type, attachment_description: description },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert(I18N_QA.alert_attachment_added);
                location.reload();
            } else {
                alert(I18N_QA.alert_error_prefix + response.message);
            }
        },
        error: function() {
            alert(I18N_QA.alert_error_attachment);
        }
    });
}

// ===================== BULK PASS / REJECT =====================
function getSelectedDetailIds() {
    return Array.from(document.querySelectorAll('.bulk-row-check:checked')).map(cb => cb.value);
}

function updateBulkUI() {
    var ids = getSelectedDetailIds();
    var count = ids.length;
    var info  = document.getElementById('bulkSelectionInfo');
    var btnP  = document.getElementById('btnBulkPass');
    var btnR  = document.getElementById('btnBulkReject');
    if (count > 0) {
        info.style.display = 'inline';
        info.textContent = count + ' selected';
        btnP.style.display = 'inline-block';
        btnR.style.display = 'inline-block';
    } else {
        info.style.display = 'none';
        btnP.style.display = 'none';
        btnR.style.display = 'none';
    }
    // Sync select-all checkbox state
    var all = document.querySelectorAll('.bulk-row-check');
    var sel = document.getElementById('bulkSelectAll');
    if (sel) {
        sel.checked = (count > 0 && count === all.length);
        sel.indeterminate = (count > 0 && count < all.length);
    }
}

function toggleSelectAll(master) {
    document.querySelectorAll('.bulk-row-check').forEach(cb => { cb.checked = master.checked; });
    updateBulkUI();
}

function bulkPassSelected() {
    var ids = getSelectedDetailIds();
    if (ids.length === 0) return;
    if (!confirm(I18N_QA.confirm_bulk_pass.replace('%d', ids.length))) return;

    var qaHeaderId = <?php echo (int)$qa_header->id; ?>;
    $.ajax({
        url: '<?php echo base_url("quality_assurance/bulk_qc_update/"); ?>' + qaHeaderId,
        type: 'POST',
        data: { action: 'pass', 'qa_detail_ids[]': ids },
        dataType: 'json',
        success: function(r) {
            alert(r.message || I18N_QA.alert_bulk_update_done);
            location.reload();
        },
        error: function() { alert(I18N_QA.alert_error_bulk_pass); }
    });
}

function bulkRejectSelected() {
    var ids = getSelectedDetailIds();
    if (ids.length === 0) return;
    var reason = document.getElementById('bulk_reject_reason').value.trim();
    if (!reason) { alert(I18N_QA.alert_reject_reason_required); return; }

    var qaHeaderId = <?php echo (int)$qa_header->id; ?>;
    $.ajax({
        url: '<?php echo base_url("quality_assurance/bulk_qc_update/"); ?>' + qaHeaderId,
        type: 'POST',
        traditional: true,
        data: {
            action: 'reject',
            qa_detail_ids: ids,
            reject_reason: reason,
            ng_category: document.getElementById('bulk_ng_category').value,
            pic: document.getElementById('bulk_pic').value,
            reject_description: document.getElementById('bulk_reject_description').value
        },
        dataType: 'json',
        success: function(r) {
            $('#bulkRejectModal').modal('hide');
            alert(r.message || I18N_QA.alert_bulk_update_done);
            location.reload();
        },
        error: function() { alert(I18N_QA.alert_error_bulk_reject); }
    });
}

// Update count in bulk reject modal when opened
$(document).on('show.bs.modal', '#bulkRejectModal', function() {
    document.getElementById('bulkRejectCount').textContent = getSelectedDetailIds().length;
});

// ===================== QA WORKFLOW =====================
function completeQA(qaHeaderId) {
    if (confirm(I18N_QA.confirm_finish_qa)) {
        window.location.href = '<?php echo base_url("quality_assurance/complete_qa/"); ?>' + qaHeaderId;
    }
}

function reopenQA(qaHeaderId, currentStatus) {
    var msg;
    if (currentStatus === 'Rejected') {
        msg = I18N_QA.confirm_recovery_rejected;
    } else {
        msg = I18N_QA.confirm_reset_all;
    }
    if (confirm(msg)) {
        window.location.href = '<?php echo base_url("quality_assurance/reopen_qa/"); ?>' + qaHeaderId;
    }
}

function approveQA(qaHeaderId) {
    var notes = document.getElementById('approval_notes').value;
    $.ajax({
        url: '<?php echo base_url("quality_assurance/approve_qa/"); ?>' + qaHeaderId,
        type: 'POST',
        data: { approval_notes: notes },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#approvalActions').fadeOut(300);
                $('#approveModal').modal('hide');
                var msg = '<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i><strong>' + I18N_QA.alert_qa_approved + '</strong><button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                $('.qa2').prepend(msg);
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                alert(I18N_QA.alert_error_prefix + response.message);
            }
        },
        error: function() { alert(I18N_QA.alert_error_approve); }
    });
}

function unapproveQA(qaHeaderId) {
    var notes = document.getElementById('unapprove_notes').value;
    $.ajax({
        url: '<?php echo base_url("quality_assurance/unapprove_qa/"); ?>' + qaHeaderId,
        type: 'POST',
        data: { unapprove_notes: notes },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#unapproveModal').modal('hide');
                var msg = '<div class="alert alert-info alert-dismissible fade show"><i class="fas fa-undo mr-2"></i><strong>' + response.message + '</strong><button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                $('.qa2').prepend(msg);
                setTimeout(function() { location.reload(); }, 1500);
            } else {
                alert(I18N_QA.alert_error_prefix + response.message);
            }
        },
        error: function() { alert(I18N_QA.alert_error_unapprove); }
    });
}

function rejectQA(qaHeaderId) {
    var notes = document.getElementById('rejection_notes').value;
    if (!notes) { alert(I18N_QA.alert_rejection_notes_required); return; }
    $.ajax({
        url: '<?php echo base_url("quality_assurance/reject_qa/"); ?>' + qaHeaderId,
        type: 'POST',
        data: { rejection_notes: notes },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                $('#approvalActions').fadeOut(300);
                $('#rejectModal').modal('hide');
                var msg = '<div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-2"></i><strong>' + I18N_QA.alert_qa_rejected + '</strong> ' + I18N_QA.alert_qa_rejected_note + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
                $('.qa2').prepend(msg);
                setTimeout(function() { location.reload(); }, 2000);
            } else {
                alert(I18N_QA.alert_error_prefix + response.message);
            }
        },
        error: function() { alert(I18N_QA.alert_error_reject); }
    });
}
</script>
<?php /* Footer dimuat controller (dulu dimuat di sini juga -> footer & skripnya ganda). */ ?>
