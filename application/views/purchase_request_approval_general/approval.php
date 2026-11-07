<?php
    // Listing gabungan General+Jasa+Asset (lihat memory pr_controllers_merge.md)
    // -- tiap baris routing ke controller approval ASLI tipe itu utk link
    // Detail maupun aksi stage/return/reject (data-route dipakai oleh JS di
    // bawah), supaya permission yg dicek tetap milik menu_key tipe itu sendiri
    // (bukan selalu 'purchase_request_approval_general').
    $pr_type_routes = [
        'non_produksi' => ['route' => 'purchase_request_approval_general', 'label' => 'General', 'badge' => 'secondary'],
        'jasa'         => ['route' => 'purchase_request_approval_jasa',    'label' => 'Jasa',    'badge' => 'info'],
        'asset'        => ['route' => 'purchase_request_approval_asset',   'label' => 'Asset',   'badge' => 'dark'],
    ];
?>
<!-- Begin Page Content -->
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-check-double text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            </h3>
            <small class="text-muted"><?= lang_line('prag_subtitle'); ?></small>
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
                <span class="badge badge-secondary p-2 mr-1"><?= lang_line('prag_no_approval_right'); ?></span>
            <?php endif; ?>
            <a href="<?= base_url('purchase_request_approval_general'); ?>" class="btn btn-sm btn-outline-secondary">
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
                           placeholder="<?= htmlspecialchars(lang_line('prag_search_placeholder')); ?>"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> Filter</button>
                <a href="<?= base_url('purchase_request_approval_general/approval'); ?>" class="btn btn-sm btn-secondary mb-1"><?= lang_line('prag_btn_reset'); ?></a>
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
                            <th><?= lang_line('po_kategori', 'Kategori'); ?></th>
                            <th><?= lang_line('prag_th_date'); ?></th>
                            <th><?= lang_line('prag_th_requester'); ?></th>
                            <th><?= lang_line('prag_th_supplier'); ?></th>
                            <th><?= lang_line('prag_th_purpose'); ?></th>
                            <th><?= lang_line('prag_th_status'); ?></th>
                                             <th><?= lang_line('prag_th_submitted', 'Disubmit'); ?></th>

                            <th width="300"><?= lang_line('prag_th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prs)): foreach ($prs as $pr): ?>
                        <?php $rt = $pr_type_routes[$pr->type] ?? $pr_type_routes['non_produksi']; ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($pr->pr_number); ?></td>
                            <td><span class="badge badge-<?= $rt['badge']; ?>"><?= $rt['label']; ?></span></td>
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
                        <?= !empty($pr->submitted_at) ? date('d M Y H:i', strtotime($pr->submitted_at)) : '-'; ?>
                    </td>
                            <td>
                                <a href="<?= base_url($rt['route'].'/detail/'.$pr->id); ?>" class="btn btn-sm btn-secondary" title="<?= lang_line('prag_tooltip_view_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <?php
                        // Approval PR 1 tahap (2026-11-05, seperti PO): PR yang
                        // menunggu (termasuk sisa alur 3 tahap lama) langsung
                        // di-approve final oleh pemegang izin 'approval'.
                        $can_action_this = in_array($pr->status, ['Submitted', 'Approved Head Dept', 'Endorsed'], true) && $can_approval;
                        ?>
                        <?php if ($can_action_this): ?>
                            <button type="button" class="btn btn-sm btn-success btn-stage-action"
                                    data-id="<?= $pr->id; ?>" data-doc="<?= htmlspecialchars($pr->pr_number); ?>" data-route="<?= $rt['route']; ?>"
                                    data-action="approve_final" data-label="<?= lang_line('prag_btn_approve_final_label'); ?>" title="<?= lang_line('prag_tooltip_approve_final'); ?>">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        <?php endif; ?>

<?php if ($can_action_this): ?>
                                    <button type="button" class="btn btn-sm btn-warning text-white btn-return"
                                            data-id="<?= $pr->id; ?>" data-doc="<?= htmlspecialchars($pr->pr_number); ?>" data-route="<?= $rt['route']; ?>" title="Return to Draft">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-reject"
                                            data-id="<?= $pr->id; ?>" data-doc="<?= htmlspecialchars($pr->pr_number); ?>" data-route="<?= $rt['route']; ?>" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('prag_no_pending'); ?></td></tr>
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
                    <h5 class="modal-title"><i class="fas fa-undo mr-2"></i><?= str_replace('%s', '<span id="returnDocNumber"></span>', lang_line('prag_return_modal_title')); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted"><?= lang_line('prag_return_modal_note'); ?></p>
                    <div class="form-group mb-0">
                        <label><strong><?= lang_line('prag_return_reason_label'); ?></strong> <span class="text-danger">*</span></label>
                        <textarea name="return_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning text-white"><i class="fas fa-undo mr-1"></i> Return to Draft</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('prag_btn_cancel'); ?></button>
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
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i><?= str_replace('%s', '<span id="rejectDocNumber"></span>', lang_line('prag_reject_modal_title')); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="small text-danger"><?= lang_line('prag_reject_note'); ?></p>
                    <div class="form-group mb-0">
                        <label><strong><?= lang_line('prag_reject_reason_label'); ?></strong> <span class="text-danger">*</span></label>
                        <textarea name="reject_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times mr-1"></i> Reject</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('prag_btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Stage action (HOD / Endorsement / Final) — POST dengan CSRF + konfirmasi SweetAlert.
    $(document).on('click', '.btn-stage-action', function() {
        const id     = $(this).data('id');
        const doc    = $(this).data('doc');
        const action = $(this).data('action');
        const route  = $(this).data('route') || 'purchase_request_approval_general';
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
                $('#formStageAction').attr('action', '<?= base_url(); ?>' + route + '/' + action + '/' + id).submit();
            }
        });
    });

    $(document).on('click', '.btn-return', function() {
        const route = $(this).data('route') || 'purchase_request_approval_general';
        $('#returnDocNumber').text($(this).data('doc'));
        $('#formReturn').attr('action', '<?= base_url(); ?>' + route + '/return_to_draft/' + $(this).data('id'));
        $('#formReturn textarea').val('');
        $('#returnModal').modal('show');
    });
    $(document).on('click', '.btn-reject', function() {
        const route = $(this).data('route') || 'purchase_request_approval_general';
        $('#rejectDocNumber').text($(this).data('doc'));
        $('#formReject').attr('action', '<?= base_url(); ?>' + route + '/reject/' + $(this).data('id'));
        $('#formReject textarea').val('');
        $('#rejectModal').modal('show');
    });
});
</script>
