<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-check-double text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <div>
            <?php if ($stage == 'endorsement'): ?>
                <span class="badge badge-info p-2"><i class="fas fa-user-tie mr-1"></i> <?= lang_line('sc_stage_endorsement', 'Tahap Anda: Endorsement'); ?></span>
            <?php elseif ($stage == 'approval'): ?>
                <span class="badge badge-success p-2"><i class="fas fa-user-check mr-1"></i> <?= lang_line('sc_stage_approval_single', 'Approval 1 tahap'); ?></span>
            <?php elseif ($stage == 'both'): ?>
                <span class="badge badge-primary p-2"><i class="fas fa-user-shield mr-1"></i> <?= lang_line('sc_stage_both', 'Tahap Anda: Endorsement &amp; Approval'); ?></span>
            <?php else: ?>
                <span class="badge badge-secondary p-2"><i class="fas fa-info-circle mr-1"></i> <?= lang_line('sc_no_approval_role', 'Anda tidak punya hak approval'); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flashdata -->
    <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $cls): ?>
        <?php if ($this->session->flashdata($key)): ?>
        <div class="alert alert-<?= $cls; ?> alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata($key); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="dataTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('sc_ipo_number_label', 'No. Sales Order'); ?></th>
                            <th><?= lang_line('sc_customer_label', 'Pelanggan'); ?></th>
                            <th><?= lang_line('sc_style_label', 'Style'); ?></th>
                            <th><?= lang_line('sc_approval_date_label', 'Tanggal'); ?></th>
                            <th><?= lang_line('sc_po_qty_label', 'Qty'); ?></th>
                            <th><?= lang_line('sc_total_price_label', 'Total Price'); ?></th>
                            <th><?= lang_line('sc_status_label', 'Status'); ?></th>
                            <th><?= lang_line('sc_submitted_by_label', 'Submitted By'); ?></th>
                            
                            <th width="240"><?= lang_line('sc_action_label', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contracts)): foreach ($contracts as $sc): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($sc->sc_number); ?></td>
                            <td class="text-left"><?= htmlspecialchars($sc->nama_customer); ?></td>
                            <td class="text-left"><?= htmlspecialchars($sc->style_name); ?></td>
                            <td><?= date('d M Y', strtotime($sc->contract_date)); ?></td>
                            <td class="text-right"><?= number_format($sc->po_qty); ?></td>
                            <td class="text-right">
                                <?= htmlspecialchars($sc->currency_code) . ' ' . number_format($sc->total_price, 2, '.', ','); ?>
                            </td>
                            <td>
                                <?php
                                    $status_class = 'secondary';
                                    if (in_array($sc->status, ['Submitted', 'Pending Endorsement'], true)) $status_class = 'warning';
                                    elseif ($sc->status == 'Endorsed')       $status_class = 'info';
                                ?>
                                <span class="badge badge-<?= $status_class; ?>"><?= htmlspecialchars($sc->status); ?></span>
                            </td>
                            <td class="small text-left">
                                <?= htmlspecialchars($sc->submitted_by_name ?: '-'); ?><br>
                                <span class="text-muted"><?= $sc->submitted_at ? date('d M Y H:i', strtotime($sc->submitted_at)) : ''; ?></span>
                            </td>
                            
                            <td>
                                <a href="<?= base_url('sales_contract/detail/'.$sc->id.'?from=sales_contract/approval'); ?>" class="btn btn-sm btn-secondary" title="<?= lang_line('sc_tooltip_view_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php // tombol Endorse dihapus 2026-11-06 (approval 1 tahap) ?>
                                <?php if (in_array($sc->status, ['Submitted', 'Pending Endorsement', 'Endorsed'], true) && $can_approve): ?>
                                    <a href="<?= base_url('sales_contract/approve/'.$sc->id); ?>" class="btn btn-sm btn-success btn-confirm"
                                       data-message="<?= lang_line('sc_approve_message', 'Approve final Sales Order'); ?> <?= htmlspecialchars($sc->sc_number); ?>?"
                                       data-text="<?= lang_line('sc_approve_warning', 'Setelah approve, Sales Order akan aktif (Open) dan bisa dipakai modul lain.'); ?>"
                                       data-confirm-text="<?= lang_line('sc_confirm_approve', 'Ya, Approve!'); ?>"
                                       data-cancel-text="<?= lang_line('sc_cancel_text', 'Batal'); ?>"
                                       title="<?= lang_line('sc_approve_button', 'Approve'); ?>">
                                        <i class="fas fa-check"></i> <?= lang_line('sc_approve_button', 'Approve'); ?>
                                    </a>
                                <?php endif; ?>
                                <?php
                                    $can_reject_this = in_array($sc->status, ['Submitted', 'Pending Endorsement', 'Endorsed'], true) && $can_approve;
                                ?>
                                <?php if ($can_reject_this): ?>
                                    <button type="button" class="btn btn-sm btn-danger btn-reject"
                                            data-id="<?= $sc->id; ?>"
                                            data-sc="<?= htmlspecialchars($sc->sc_number); ?>"
                                            title="<?= lang_line('sc_reject_button', 'Reject'); ?>">
                                        <i class="fas fa-times"></i> <?= lang_line('sc_reject_button', 'Reject'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-3">
                                    <?php if ($stage == 'none'): ?>
                                        <?= lang_line('sc_no_approval_rights', 'Anda tidak memiliki hak Endorsement maupun Approval untuk Sales Order.'); ?>
                                    <?php else: ?>
                                        <?= lang_line('sc_no_pending_approval', 'Tidak ada Sales Order yang menunggu aksi Anda saat ini.'); ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
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

<!-- ============================================== -->
<!-- MODAL REJECT (dengan alasan) -->
<!-- ============================================== -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" id="formReject">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i><?= lang_line('sc_reject_modal_title', 'Reject Sales Order'); ?> <span id="rejectScNumber"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label for="reject_reason"><strong><?= lang_line('sc_reject_reason_input', 'Alasan Reject'); ?></strong> <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" id="reject_reason" class="form-control" rows="4" required placeholder="<?= lang_line('sc_reject_reason_placeholder', 'Tulis alasan reject...'); ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times mr-1"></i> <?= lang_line('sc_reject_button', 'Reject'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('sc_cancel_text', 'Batal'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Endorse / Approve confirmation
    $(document).on('click', '.btn-confirm', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const message = $(this).data('message') || 'Yakin melanjutkan aksi ini?';
        Swal.fire({
            title: 'Konfirmasi',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Lanjutkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = href;
        });
    });

    // Reject modal trigger
    $(document).on('click', '.btn-reject', function() {
        const id = $(this).data('id');
        const sc = $(this).data('sc');
        $('#rejectScNumber').text(sc);
        $('#formReject').attr('action', '<?= base_url('sales_contract/reject/'); ?>' + id);
        $('#reject_reason').val('');
        $('#rejectModal').modal('show');
    });
});
</script>
