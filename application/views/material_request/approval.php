<!-- Begin Page Content -->
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-check-double text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <div>
            <?php
                $stages = [];
                if ($can_head_dept)   $stages[] = lang_line('mr_approval_stage_hod', 'Head of Dept');
                if ($can_endorsement) $stages[] = lang_line('mr_approval_stage_endorsement', 'Endorsement');
                if ($can_approval)    $stages[] = lang_line('mr_approval_stage_approval', 'Approval');
            ?>
            <?php if (!empty($stages)): ?>
                <span class="badge badge-info p-2">
                    <i class="fas fa-user-shield mr-1"></i> <?= lang_line('mr_approval_stage_label', 'Tahap Anda'); ?>: <?= implode(' + ', $stages); ?>
                </span>
            <?php else: ?>
                <span class="badge badge-secondary p-2"><?= lang_line('mr_approval_no_rights', 'Tidak ada hak approval'); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $cls): ?>
        <?php if ($this->session->flashdata($key)): ?>
        <div class="alert alert-<?= $cls; ?> alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata($key); ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="dataTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('mr_approval_th_no', 'No. MR'); ?></th>
                            <th><?= lang_line('mr_approval_th_request_date', 'Tanggal Request'); ?></th>
                            <th><?= lang_line('mr_approval_th_requester', 'Requester'); ?></th>
                            <th><?= lang_line('label_status', 'Status'); ?></th>
                            <th><?= lang_line('mr_approval_th_hod_approved', 'HOD Approved'); ?></th>
                            <th><?= lang_line('mr_approval_th_endorsed', 'Endorsed'); ?></th>
                            <th width="280"><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($mrs)): foreach ($mrs as $mr): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($mr->mr_number); ?></td>
                            <td><?= $mr->request_date ? date('d M Y', strtotime($mr->request_date)) : '-'; ?></td>
                            <td class="text-left"><?= htmlspecialchars($mr->requester_name ?: '-'); ?></td>
                            <td>
                                <?php
                                    $cls = 'secondary';
                                    if ($mr->status == 'Submitted')          $cls = 'warning';
                                    elseif ($mr->status == 'Approved Head Dept') $cls = 'info';
                                    elseif ($mr->status == 'Endorsed')        $cls = 'primary';
                                ?>
                                <span class="badge badge-<?= $cls; ?>"><?= htmlspecialchars($mr->status); ?></span>
                            </td>
                            <td class="small text-left">
                                <?= htmlspecialchars($mr->hod_approver_name ?: '-'); ?><br>
                                <span class="text-muted"><?= $mr->hod_approved_at ? date('d M Y H:i', strtotime($mr->hod_approved_at)) : ''; ?></span>
                            </td>
                            <td class="small text-left">
                                <?= htmlspecialchars($mr->endorser_name ?: '-'); ?><br>
                                <span class="text-muted"><?= $mr->endorsed_at ? date('d M Y H:i', strtotime($mr->endorsed_at)) : ''; ?></span>
                            </td>
                            <td>
                                <a href="<?= base_url('material_request/detail/'.$mr->id); ?>" class="btn btn-sm btn-secondary" title="<?= lang_line('mr_approval_view_detail', 'View Detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <?php if ($mr->status == 'Submitted' && $can_head_dept): ?>
                                    <a href="<?= base_url('material_request/approve_hod/'.$mr->id.'?redirect=approval'); ?>" class="btn btn-sm btn-info btn-confirm"
                                       data-message="<?= sprintf('Approve Material Request %s? (approval 1 tahap, langsung final)', htmlspecialchars($mr->mr_number)); ?>" title="Approve">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                <?php endif; ?>

                                <?php if ($mr->status == 'Approved Head Dept' && $can_endorsement): ?>
                                    <a href="<?= base_url('material_request/approve_endorsement/'.$mr->id.'?redirect=approval'); ?>" class="btn btn-sm btn-primary btn-confirm"
                                       data-message="<?= sprintf(lang_line('mr_approval_confirm_endorse', 'Endorse MR %s?'), htmlspecialchars($mr->mr_number)); ?>" title="<?= lang_line('mr_approval_btn_endorse', 'Endorse'); ?>">
                                        <i class="fas fa-stamp"></i> <?= lang_line('mr_approval_btn_endorse', 'Endorse'); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ($mr->status == 'Endorsed' && $can_approval): ?>
                                    <a href="<?= base_url('material_request/approve_final/'.$mr->id.'?redirect=approval'); ?>" class="btn btn-sm btn-success btn-confirm"
                                       data-message="<?= sprintf(lang_line('mr_approval_confirm_approve', 'Approve final MR %s? Setelah approve, MR aktif & bisa di-issue.'), htmlspecialchars($mr->mr_number)); ?>" title="<?= lang_line('mr_approval_btn_approve', 'Approve'); ?>">
                                        <i class="fas fa-check"></i> <?= lang_line('mr_approval_btn_approve', 'Approve'); ?>
                                    </a>
                                <?php endif; ?>

                                <?php
                                    $can_action_this = ($mr->status == 'Submitted' && $can_head_dept)
                                                    || ($mr->status == 'Approved Head Dept' && $can_endorsement)
                                                    || ($mr->status == 'Endorsed' && $can_approval);
                                ?>
                                <?php if ($can_action_this): ?>
                                    <button type="button" class="btn btn-sm btn-warning text-white btn-return"
                                            data-id="<?= $mr->id; ?>" data-doc="<?= htmlspecialchars($mr->mr_number); ?>" title="<?= lang_line('mr_approval_btn_return', 'Return to Draft'); ?>">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-reject"
                                            data-id="<?= $mr->id; ?>" data-doc="<?= htmlspecialchars($mr->mr_number); ?>" title="<?= lang_line('mr_approval_btn_reject', 'Reject'); ?>">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-3"><?= lang_line('mr_approval_no_pending', 'Tidak ada MR yang menunggu aksi Anda.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" id="formReturn">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <input type="hidden" name="redirect" value="approval">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-undo mr-2"></i><?= sprintf(lang_line('mr_approval_return_title', 'Return MR %s ke Draft'), '<span id="returnDocNumber"></span>'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
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
<div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" id="formReject">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <input type="hidden" name="redirect" value="approval">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i><?= sprintf(lang_line('mr_approval_reject_title', 'Reject MR %s'), '<span id="rejectDocNumber"></span>'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
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
