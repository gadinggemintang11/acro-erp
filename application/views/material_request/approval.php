<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Antrian approval Material Request -- redesign 2026-11-08 (selaras daftar &
 * detail MR). Approval MR 1 tahap (Head of Dept); tombol Endorse/Final hanya
 * untuk dokumen lama yang masih di status 3-tahap.
 */
$this->load->view('production_process/_pp_style');
$dept_names = function_exists('department_names') ? department_names() : [];
$stages = [];
if ($can_head_dept)   $stages[] = lang_line('mr_approval_stage_hod', 'Head of Dept');
if ($can_endorsement) $stages[] = lang_line('mr_approval_stage_endorsement', 'Endorsement');
if ($can_approval)    $stages[] = lang_line('mr_approval_stage_approval', 'Approval');
$flash_icons = ['success' => 'fa-check-circle', 'error' => 'fa-exclamation-circle', 'warning' => 'fa-exclamation-triangle'];
?>
<div class="container-fluid pp2">
    <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:.75rem">
        <div>
            <h1 class="h4 mb-0 font-weight-bold" style="color:var(--pp-text)"><i class="fas fa-check-double mr-2" style="color:var(--brand-1)"></i><?= htmlspecialchars($title); ?></h1>
            <div class="small" style="color:var(--pp-muted)"><?= lang_line('mr2_appr_sub', 'Material Request yang menunggu persetujuan Anda. Approve = langsung masuk ke Material Issuance.'); ?></div>
        </div>
        <span class="pp-chip <?= $stages ? 'pp-chip--ok' : ''; ?>" style="font-size:.78rem;padding:.35rem .8rem"><i class="fas fa-user-shield"></i>
            <?= $stages ? lang_line('mr_approval_stage_label', 'Tahap Anda') . ': ' . htmlspecialchars(implode(' + ', $stages)) : lang_line('mr_approval_no_rights', 'Tidak ada hak approval'); ?></span>
    </div>

    <?php foreach ($flash_icons as $fk => $fi): if ($msg = $this->session->flashdata($fk)): ?>
        <div class="pp-flash pp-flash--<?= $fk; ?>" role="alert"><i class="fas <?= $fi; ?>"></i><div><?= $msg; ?></div>
            <button type="button" class="close" onclick="this.parentNode.remove()">&times;</button></div>
    <?php endif; endforeach; ?>

    <div class="pp-card">
        <div class="pp-card-head">
            <h2><span class="pp-ico"><i class="fas fa-inbox"></i></span><?= lang_line('mr2_appr_queue', 'Antrian Approval'); ?> (<?= count($mrs); ?>)</h2>
        </div>
        <div class="table-responsive">
            <table class="pp-table">
                <thead><tr>
                    <th><?= lang_line('mr_approval_th_no', 'No. MR'); ?></th>
                    <th><?= lang_line('mi2_th_dept_req', 'Departemen / Pemohon'); ?></th>
                    <th><?= lang_line('mr2_th_items_wo', 'Item / WO'); ?></th>
                    <th><?= lang_line('mr2_appr_submitted', 'Diajukan'); ?></th>
                    <th><?= lang_line('label_status', 'Status'); ?></th>
                    <th class="text-right"><?= lang_line('label_action', 'Aksi'); ?></th>
                </tr></thead>
                <tbody>
                <?php if (!empty($mrs)): foreach ($mrs as $mr):
                    $can_action_this = ($mr->status == 'Submitted' && $can_head_dept)
                                    || ($mr->status == 'Approved Head Dept' && $can_endorsement)
                                    || ($mr->status == 'Endorsed' && $can_approval); ?>
                    <tr>
                        <td>
                            <a href="<?= base_url('material_request/detail/' . $mr->id); ?>" class="pp-list-title"><?= htmlspecialchars($mr->mr_number); ?></a>
                            <div class="pp-item-code"><i class="far fa-calendar mr-1"></i><?= $mr->request_date ? date('d M Y', strtotime($mr->request_date)) : '-'; ?></div>
                        </td>
                        <td>
                            <div class="font-weight-bold" style="font-size:.82rem"><?= htmlspecialchars($dept_names[$mr->department_code ?? ''] ?? ($mr->department_code ?: '-')); ?></div>
                            <div class="pp-item-code"><?= htmlspecialchars($mr->requester_name ?: '-'); ?></div>
                        </td>
                        <td><span class="pp-chip"><i class="fas fa-box"></i><?= (int) $mr->line_count; ?></span> <span class="pp-chip"><i class="fas fa-clipboard-check"></i><?= (int) $mr->wo_count; ?> WO</span>
                            <?php if (!empty($mr->remarks)): ?><div class="pp-item-code text-truncate mt-1" style="max-width:220px" title="<?= htmlspecialchars($mr->remarks); ?>"><?= htmlspecialchars($mr->remarks); ?></div><?php endif; ?></td>
                        <td><span style="font-size:.82rem"><?= $mr->requested_at ? date('d M Y H:i', strtotime($mr->requested_at)) : '-'; ?></span>
                            <?php if ($mr->hod_approved_at): ?><div class="pp-item-code">HOD: <?= htmlspecialchars($mr->hod_approver_name ?: '-'); ?></div><?php endif; ?></td>
                        <td><span class="pp-status pp-status--progress"><?= htmlspecialchars($mr->status); ?></span></td>
                        <td class="text-right">
                            <div class="pp-actions">
                                <a href="<?= base_url('material_request/detail/' . $mr->id); ?>" class="btn btn-light" title="<?= htmlspecialchars(lang_line('mr_approval_view_detail', 'View Detail')); ?>"><i class="fas fa-eye"></i></a>
                                <?php if ($mr->status == 'Submitted' && $can_head_dept): ?>
                                    <a href="<?= base_url('material_request/approve_hod/' . $mr->id . '?redirect=approval'); ?>" class="btn btn-success btn-confirm"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('mr2_confirm_approve', 'Approve Material Request %s? MR langsung masuk ke Material Issuance.'), $mr->mr_number)); ?>"><i class="fas fa-check mr-1"></i>Approve</a>
                                <?php endif; ?>
                                <?php if ($mr->status == 'Approved Head Dept' && $can_endorsement): ?>
                                    <a href="<?= base_url('material_request/approve_endorsement/' . $mr->id . '?redirect=approval'); ?>" class="btn btn-pp btn-confirm"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('mr_approval_confirm_endorse', 'Endorse MR %s?'), $mr->mr_number)); ?>"><i class="fas fa-stamp mr-1"></i><?= lang_line('mr_approval_btn_endorse', 'Endorse'); ?></a>
                                <?php endif; ?>
                                <?php if ($mr->status == 'Endorsed' && $can_approval): ?>
                                    <a href="<?= base_url('material_request/approve_final/' . $mr->id . '?redirect=approval'); ?>" class="btn btn-success btn-confirm"
                                       data-message="<?= htmlspecialchars(sprintf(lang_line('mr_approval_confirm_approve', 'Approve final MR %s? Setelah approve, MR aktif & bisa di-issue.'), $mr->mr_number)); ?>"><i class="fas fa-check mr-1"></i><?= lang_line('mr_approval_btn_approve', 'Approve'); ?></a>
                                <?php endif; ?>
                                <?php if ($can_action_this): ?>
                                    <button type="button" class="btn btn-outline-warning btn-return" data-id="<?= $mr->id; ?>" data-doc="<?= htmlspecialchars($mr->mr_number); ?>" title="<?= htmlspecialchars(lang_line('mr2_btn_return', 'Kembalikan')); ?>"><i class="fas fa-undo"></i></button>
                                    <button type="button" class="btn btn-outline-danger btn-reject" data-id="<?= $mr->id; ?>" data-doc="<?= htmlspecialchars($mr->mr_number); ?>" title="<?= htmlspecialchars(lang_line('mr2_btn_reject', 'Tolak')); ?>"><i class="fas fa-times"></i></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="6" class="pp-empty"><i class="fas fa-check-circle"></i><?= lang_line('mr_approval_no_pending', 'Tidak ada MR yang menunggu aksi Anda.'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
        // Kartu "Riwayat Approval Saya" (user 2026-09-06: halaman approval
        // "hanya menampilkan list daftar yang akan di approve, seharusnya di
        // bagian bawah ada riwayat yang user itu approve"). Partial bersama
        // dipakai SEMUA halaman approval — lihat templates/my_approval_history.php.
        // Dijaga isset(): view yang dipakai bersama halaman non-approval
        // (mis. prf/list_all juga jadi PRF List) tidak ikut menampilkannya.
        if (isset($my_approval_history)) {
            $this->load->view('templates/my_approval_history', [
                'history' => $my_approval_history,
                'route'   => $my_history_route ?? '',
            ]);
        }
    ?>
</div>

<!-- Modal Return to Draft -->
<div class="modal fade pp-modal" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" id="formReturn">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <input type="hidden" name="redirect" value="approval">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-undo mr-2"></i><?= sprintf(lang_line('mr_approval_return_title', 'Return MR %s ke Draft'), '<span id="returnDocNumber"></span>'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted"><?= lang_line('mr_approval_return_warning', 'Status MR akan dikembalikan ke <strong>Draft</strong> dan creator dapat merevisi & submit ulang. Audit approval akan di-reset.'); ?></p>
                    <div class="form-group mb-0">
                        <label><strong><?= lang_line('mr_approval_return_reason', 'Alasan Return'); ?></strong> <span class="text-danger">*</span></label>
                        <textarea name="return_reason" class="form-control" rows="3" required placeholder="<?= lang_line('mr_approval_return_placeholder', 'Tulis alasan kenapa MR ini dikembalikan ke Draft...'); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning text-white"><i class="fas fa-undo mr-1"></i> <?= lang_line('mr_approval_btn_return', 'Return to Draft'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reject -->
<div class="modal fade pp-modal" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" id="formReject">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <input type="hidden" name="redirect" value="approval">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i><?= sprintf(lang_line('mr_approval_reject_title', 'Reject MR %s'), '<span id="rejectDocNumber"></span>'); ?></h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-danger"><?= lang_line('mr_approval_reject_warning', '<strong>Reject = final.</strong> MR tidak dapat di-revisi; creator harus membuat MR baru.'); ?></p>
                    <div class="form-group mb-0">
                        <label><strong><?= lang_line('mr_approval_reject_reason', 'Alasan Reject'); ?></strong> <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" class="form-control" rows="3" required placeholder="<?= lang_line('mr_approval_reject_placeholder', 'Tulis alasan reject...'); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times mr-1"></i> <?= lang_line('mr_approval_btn_reject', 'Reject'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('common_cancel', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Confirmation untuk approve actions
    $(document).on('click', '.btn-confirm', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const message = $(this).data('message') || '<?= lang_line('common_confirm_continue', 'Yakin melanjutkan?'); ?>';
        Swal.fire({
            title: '<?= lang_line('common_confirmation', 'Konfirmasi'); ?>', text: message, icon: 'question',
            showCancelButton: true, confirmButtonColor: '#28a745', cancelButtonColor: '#6c757d',
            confirmButtonText: '<?= lang_line('common_yes_continue', 'Ya, Lanjutkan'); ?>', cancelButtonText: '<?= lang_line('common_cancel', 'Batal'); ?>'
        }).then((result) => { if (result.isConfirmed) window.location.href = href; });
    });

    $(document).on('click', '.btn-return', function() {
        $('#returnDocNumber').text($(this).data('doc'));
        $('#formReturn').attr('action', '<?= base_url('material_request/return_to_draft/'); ?>' + $(this).data('id'));
        $('#formReturn textarea').val('');
        $('#returnModal').modal('show');
    });
    $(document).on('click', '.btn-reject', function() {
        $('#rejectDocNumber').text($(this).data('doc'));
        $('#formReject').attr('action', '<?= base_url('material_request/reject/'); ?>' + $(this).data('id'));
        $('#formReject textarea').val('');
        $('#rejectModal').modal('show');
    });
});
</script>
