<!-- Begin Page Content -->
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-check-double text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            </h3>
            <small class="text-muted">Purchase Request Jasa yang menunggu aksi approval Anda</small>
        </div>
        <div>
            <?php
                $stages = [];
                if ($can_head_dept)   $stages[] = 'Head of Dept';
                if ($can_endorsement) $stages[] = 'Endorsement';
                if ($can_approval)    $stages[] = 'Approval';
            ?>
            <?php if (!empty($stages)): ?>
                <span class="badge badge-info p-2 mr-1">
                    <i class="fas fa-user-shield mr-1"></i> Tahap Anda: <?= implode(' + ', $stages); ?>
                </span>
            <?php else: ?>
                <span class="badge badge-secondary p-2 mr-1">Anda tidak punya hak approval di modul ini.</span>
            <?php endif; ?>
            <a href="<?= base_url('purchase_request_approval_jasa'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-list mr-1"></i> Lihat Semua PR
            </a>
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

    <!-- Search bar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap gap-2">
                <div class="form-group mr-2 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="PR #, Requester, Keperluan..."
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> Filter</button>
                <a href="<?= base_url('purchase_request_approval_jasa/approval'); ?>" class="btn btn-sm btn-secondary mb-1">Reset</a>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="dataTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>PR Number</th>
                            <th>Tanggal</th>
                            <th>Requester</th>
                            <th>Supplier</th>
                            <th>Keperluan</th>
                            <th>Status</th>
                            <th>HOD Approved</th>
                            <th>Endorsed</th>
                            <th width="300">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prs)): foreach ($prs as $pr): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($pr->pr_number); ?></td>
                            <td><?= $pr->pr_date ? date('d M Y', strtotime($pr->pr_date)) : '-'; ?></td>
                            <td class="text-left"><?= htmlspecialchars($pr->requester_name ?: '-'); ?></td>
                            <td class="text-left"><?= htmlspecialchars($pr->nama_supplier ?: '-'); ?></td>
                            <td class="text-left"><?= htmlspecialchars(mb_strimwidth($pr->purpose ?? '-', 0, 40, '...')); ?></td>
                            <td>
                                <?php
                                    $cls = 'secondary';
                                    if ($pr->status == 'Submitted')                $cls = 'warning';
                                    elseif ($pr->status == 'Approved Head Dept')   $cls = 'info';
                                    elseif ($pr->status == 'Endorsed')             $cls = 'primary';
                                ?>
                                <span class="badge badge-<?= $cls; ?>"><?= htmlspecialchars($pr->status); ?></span>
                            </td>
                            <td class="small text-left">
                                <?= htmlspecialchars($pr->hod_approver_name ?: '-'); ?><br>
                                <span class="text-muted"><?= !empty($pr->hod_approved_at) ? date('d M Y H:i', strtotime($pr->hod_approved_at)) : ''; ?></span>
                            </td>
                            <td class="small text-left">
                                <?= htmlspecialchars($pr->endorser_name ?: '-'); ?><br>
                                <span class="text-muted"><?= !empty($pr->endorsed_at) ? date('d M Y H:i', strtotime($pr->endorsed_at)) : ''; ?></span>
                            </td>
                            <td>
                                <a href="<?= base_url('purchase_request_approval_jasa/detail/'.$pr->id); ?>" class="btn btn-sm btn-secondary" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <?php if ($pr->status == 'Submitted' && $can_head_dept): ?>
                                    <button type="button" class="btn btn-sm btn-info btn-stage-action"
                                            data-id="<?= $pr->id; ?>" data-doc="<?= htmlspecialchars($pr->pr_number); ?>"
                                            data-action="approve_hod" data-label="Approve sebagai Head of Dept?" title="Approve Head of Dept">
                                        <i class="fas fa-user-tie"></i> HOD
                                    </button>
                                <?php endif; ?>

                                <?php if ($pr->status == 'Approved Head Dept' && $can_endorsement): ?>
                                    <button type="button" class="btn btn-sm btn-primary btn-stage-action"
                                            data-id="<?= $pr->id; ?>" data-doc="<?= htmlspecialchars($pr->pr_number); ?>"
                                            data-action="approve_endorsement" data-label="Endorse PR ini?" title="Endorse">
                                        <i class="fas fa-stamp"></i> Endorse
                                    </button>
                                <?php endif; ?>

                                <?php if ($pr->status == 'Endorsed' && $can_approval): ?>
                                    <button type="button" class="btn btn-sm btn-success btn-stage-action"
                                            data-id="<?= $pr->id; ?>" data-doc="<?= htmlspecialchars($pr->pr_number); ?>"
                                            data-action="approve_final" data-label="Final approve PR ini?" title="Final Approve">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                <?php endif; ?>

                                <?php
                                    $can_action_this = ($pr->status == 'Submitted' && $can_head_dept)
                                                    || ($pr->status == 'Approved Head Dept' && $can_endorsement)
                                                    || ($pr->status == 'Endorsed' && $can_approval);
                                ?>
                                <?php if ($can_action_this): ?>
                                    <button type="button" class="btn btn-sm btn-warning text-white btn-return"
                                            data-id="<?= $pr->id; ?>" data-doc="<?= htmlspecialchars($pr->pr_number); ?>" title="Return to Draft">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-reject"
                                            data-id="<?= $pr->id; ?>" data-doc="<?= htmlspecialchars($pr->pr_number); ?>" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-3">Tidak ada PR Jasa yang menunggu approval Anda.</td></tr>
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

<!-- Form helper untuk stage action (POST + CSRF + redirect=approval) -->
<form id="formStageAction" method="POST" action="" style="display:none">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
    <input type="hidden" name="redirect" value="approval">
</form>

<!-- Modal Return to Draft -->
<div class="modal fade" id="returnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="" id="formReturn">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <input type="hidden" name="redirect" value="approval">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-white">
                    <h5 class="modal-title"><i class="fas fa-undo mr-2"></i>Return <span id="returnDocNumber"></span> to Draft</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">PR akan dikembalikan ke status Draft. Creator dapat merevisi & submit ulang.</p>
                    <div class="form-group mb-0">
                        <label><strong>Alasan Return</strong> <span class="text-danger">*</span></label>
                        <textarea name="return_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning text-white"><i class="fas fa-undo mr-1"></i> Return to Draft</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
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
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i>Reject <span id="rejectDocNumber"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-danger">PR yang di-reject tidak bisa direvisi.</p>
                    <div class="form-group mb-0">
                        <label><strong>Alasan Reject</strong> <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times mr-1"></i> Reject</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $(document).on('click', '.btn-stage-action', function() {
        const id     = $(this).data('id');
        const doc    = $(this).data('doc');
        const action = $(this).data('action');
        const label  = $(this).data('label') || 'Lanjutkan?';

        Swal.fire({
            title: 'Konfirmasi',
            text:  label + ' ' + doc + '?',
            icon:  'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor:  '#6c757d',
            confirmButtonText:  'Ya, Lanjutkan',
            cancelButtonText:   'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#formStageAction').attr('action', '<?= base_url('purchase_request_approval_jasa/'); ?>' + action + '/' + id).submit();
            }
        });
    });

    $(document).on('click', '.btn-return', function() {
        $('#returnDocNumber').text($(this).data('doc'));
        $('#formReturn').attr('action', '<?= base_url('purchase_request_approval_jasa/return_to_draft/'); ?>' + $(this).data('id'));
        $('#formReturn textarea').val('');
        $('#returnModal').modal('show');
    });
    $(document).on('click', '.btn-reject', function() {
        $('#rejectDocNumber').text($(this).data('doc'));
        $('#formReject').attr('action', '<?= base_url('purchase_request_approval_jasa/reject/'); ?>' + $(this).data('id'));
        $('#formReject textarea').val('');
        $('#rejectModal').modal('show');
    });
});
</script>
