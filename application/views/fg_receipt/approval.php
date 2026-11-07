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
                            <th><?= lang_line('fgr_approval_th_no_receipt', 'No. Receipt'); ?></th>
                            <th><?= lang_line('label_date', 'Tanggal'); ?></th>
                            <th><?= lang_line('fgr_approval_th_fg_model', 'Barang Jadi (Model)'); ?></th>
                            <th><?= lang_line('fgr_approval_th_wo_number', 'WO Number'); ?></th>
                            <th><?= lang_line('label_status', 'Status'); ?></th>
                            <th><?= lang_line('mr_approval_th_hod_approved', 'HOD Approved'); ?></th>
                            <th><?= lang_line('mr_approval_th_endorsed', 'Endorsed'); ?></th>
                            <th width="280"><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($receipts)): foreach ($receipts as $r): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($r->receipt_number); ?></td>
                            <td><?= date('d M Y', strtotime($r->receipt_date)); ?></td>
                            <td class="text-left">
                                <?php if (!empty($r->fg_name)): ?>
                                    <span class="small text-muted"><?= htmlspecialchars($r->fg_code); ?></span><br>
                                    <?= htmlspecialchars($r->fg_name); ?>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars($r->wo_number ?: '-'); ?></td>
                            <td>
                                <?php
                                    $cls = 'secondary';
                                    if ($r->status == 'Submitted')          $cls = 'warning';
                                    elseif ($r->status == 'Approved Head Dept') $cls = 'info';
                                    elseif ($r->status == 'Endorsed')        $cls = 'primary';
                                ?>
                                <span class="badge badge-<?= $cls; ?>"><?= htmlspecialchars($r->status); ?></span>
                            </td>
                            <td class="small text-left">
                                <?= htmlspecialchars($r->hod_approver_name ?: '-'); ?><br>
                                <span class="text-muted"><?= $r->hod_approved_at ? date('d M Y H:i', strtotime($r->hod_approved_at)) : ''; ?></span>
                            </td>
                            <td class="small text-left">
                                <?= htmlspecialchars($r->endorser_name ?: '-'); ?><br>
                                <span class="text-muted"><?= $r->endorsed_at ? date('d M Y H:i', strtotime($r->endorsed_at)) : ''; ?></span>
                            </td>
                            <td>
                                <a href="<?= base_url('fg_receipt/show/'.$r->id); ?>" class="btn btn-sm btn-secondary" title="<?= lang_line('mr_approval_view_detail', 'View Detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <?php if ($r->status == 'Submitted' && $can_head_dept): ?>
                                    <a href="<?= base_url('fg_receipt/approve_hod/'.$r->id.'?redirect=approval'); ?>" class="btn btn-sm btn-success btn-confirm"
                                       data-message="<?= sprintf(lang_line('fgr_approval_confirm_single', 'Approve FG Receipt %s? (approval 1 tahap -- stok barang jadi langsung masuk)'), htmlspecialchars($r->receipt_number)); ?>" title="<?= lang_line('mr_approval_btn_approve', 'Approve'); ?>">
                                        <i class="fas fa-check"></i> <?= lang_line('mr_approval_btn_approve', 'Approve'); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ($r->status == 'Approved Head Dept' && $can_endorsement): ?>
                                    <a href="<?= base_url('fg_receipt/approve_endorsement/'.$r->id.'?redirect=approval'); ?>" class="btn btn-sm btn-primary btn-confirm"
                                       data-message="<?= sprintf(lang_line('fgr_approval_confirm_endorse', 'Endorse FG Receipt %s?'), htmlspecialchars($r->receipt_number)); ?>" title="<?= lang_line('mr_approval_btn_endorse', 'Endorse'); ?>">
                                        <i class="fas fa-stamp"></i> <?= lang_line('mr_approval_btn_endorse', 'Endorse'); ?>
                                    </a>
                                <?php endif; ?>

                                <?php if ($r->status == 'Endorsed' && $can_approval): ?>
                                    <a href="<?= base_url('fg_receipt/approve_final/'.$r->id.'?redirect=approval'); ?>" class="btn btn-sm btn-success btn-confirm"
                                       data-message="<?= sprintf(lang_line('fgr_approval_confirm_approve', 'Approve final FG Receipt %s? Setelah approve, stok FG akan masuk ke ledger.'), htmlspecialchars($r->receipt_number)); ?>" title="<?= lang_line('mr_approval_btn_approve', 'Approve'); ?>">
                                        <i class="fas fa-check"></i> <?= lang_line('mr_approval_btn_approve', 'Approve'); ?>
                                    </a>
                                <?php endif; ?>

                                <?php
                                    $can_action_this = ($r->status == 'Submitted' && $can_head_dept)
                                                    || ($r->status == 'Approved Head Dept' && $can_endorsement)
                                                    || ($r->status == 'Endorsed' && $can_approval);
                                ?>
                                <?php if ($can_action_this): ?>
                                    <button type="button" class="btn btn-sm btn-warning text-white btn-return"
                                            data-id="<?= $r->id; ?>" data-doc="<?= htmlspecialchars($r->receipt_number); ?>" title="<?= lang_line('mr_approval_btn_return', 'Return to Draft'); ?>">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-reject"
                                            data-id="<?= $r->id; ?>" data-doc="<?= htmlspecialchars($r->receipt_number); ?>" title="<?= lang_line('mr_approval_btn_reject', 'Reject'); ?>">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3"><?= lang_line('fgr_approval_no_pending', 'Tidak ada FG Receipt yang menunggu aksi Anda.'); ?></td></tr>
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
                    <h5 class="modal-title"><i class="fas fa-undo mr-2"></i><?= sprintf(lang_line('fgr_approval_return_title', 'Return FG Receipt %s ke Draft'), '<span id="returnDocNumber"></span>'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted"><?= lang_line('fgr_approval_return_warning', 'Status FG Receipt akan dikembalikan ke <strong>Draft</strong> dan creator dapat merevisi & submit ulang. Audit approval akan di-reset.'); ?></p>
                    <div class="form-group mb-0">
                        <label><strong><?= lang_line('mr_approval_return_reason', 'Alasan Return'); ?></strong> <span class="text-danger">*</span></label>
                        <textarea name="return_reason" class="form-control" rows="3" required></textarea>
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
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i><?= sprintf(lang_line('fgr_approval_reject_title', 'Reject FG Receipt %s'), '<span id="rejectDocNumber"></span>'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-danger"><?= lang_line('fgr_approval_reject_warning', '<strong>Reject = final.</strong> FG Receipt tidak dapat di-revisi; creator harus membuat baru.'); ?></p>
                    <div class="form-group mb-0">
                        <label><strong><?= lang_line('mr_approval_reject_reason', 'Alasan Reject'); ?></strong> <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" class="form-control" rows="3" required></textarea>
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
        $('#formReturn').attr('action', '<?= base_url('fg_receipt/return_to_draft/'); ?>' + $(this).data('id'));
        $('#formReturn textarea').val('');
        $('#returnModal').modal('show');
    });
    $(document).on('click', '.btn-reject', function() {
        $('#rejectDocNumber').text($(this).data('doc'));
        $('#formReject').attr('action', '<?= base_url('fg_receipt/reject/'); ?>' + $(this).data('id'));
        $('#formReject textarea').val('');
        $('#rejectModal').modal('show');
    });
});
</script>
