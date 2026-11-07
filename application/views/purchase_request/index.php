<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-file-alt text-primary mr-2"></i> <?= lang_line('page_purchase_request_title'); ?></h3>
            <small class="text-muted"><?= lang_line('purchase_request_approval_subtitle'); ?></small>
        </div>
        <?php if (check_access('purchase_request', 'add')): ?>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#addPrModal">
                <i class="fas fa-plus-circle mr-1"></i> <?= lang_line('btn_add'); ?> <?= lang_line('purchase_request_short'); ?>
            </button>
        <?php endif; ?>
    </div>

    <!-- Flash messages -->
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-1"></i> <?= $this->session->flashdata('success'); ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-1"></i> <?= $this->session->flashdata('error'); ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap gap-2">
                <div class="form-group mr-2 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="<?= lang_line('purchase_request_search_placeholder'); ?>" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">-- <?= lang_line('text_all_status'); ?> --</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> <?= lang_line('btn_filter'); ?></button>
                <a href="<?= base_url('purchase_request') ?>" class="btn btn-sm btn-secondary mb-1 mr-1"><?= lang_line('btn_reset'); ?></a>
            </form>
            <div class="mt-1">
                <a href="<?= base_url('purchase_request/export_list_excel') . '?' . http_build_query($_GET) ?>" class="btn btn-sm btn-success no-loader mr-1" target="_blank"><i class="fas fa-file-excel"></i> <?= lang_line('text_excel'); ?></a>
                <a href="<?= base_url('purchase_request/export_list_pdf') . '?' . http_build_query($_GET) ?>" class="btn btn-sm btn-danger no-loader" target="_blank"><i class="fas fa-file-pdf"></i> <?= lang_line('text_pdf'); ?></a>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('purchase_request_number'); ?></th>
                            <th><?= lang_line('text_date'); ?></th>
                            <th><?= lang_line('purchase_request_requester'); ?></th>
                            <th><?= lang_line('purchase_request_purpose'); ?></th>
                            <th><?= lang_line('text_status'); ?></th>
                            <th width="160"><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prs)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('purchase_request_no_data'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($prs as $pr): ?>
                            <?php
                            $badge = [
                                'Draft'     => 'secondary',
                                'Submitted' => 'info',
                                'Approved'  => 'success',
                                'Rejected'  => 'danger',
                            ][$pr->status] ?? 'secondary';
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($pr->pr_number) ?></strong></td>
                                <td><?= date('d M Y', strtotime($pr->pr_date)) ?></td>
                                <td><?= htmlspecialchars($pr->requester_name ?? '-') ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($pr->purpose ?? '-', 0, 50, '...')) ?></td>
                                <td><span class="badge badge-<?= $badge ?>"><?= $pr->status ?></span></td>
                                <td>
                                    <a href="<?= base_url('purchase_request/detail/' . $pr->id) ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> <?= lang_line('btn_detail'); ?>
                                    </a>
                                    <?php if ($pr->status === 'Draft' && check_access('purchase_request', 'delete')): ?>
                                        <a href="<?= base_url('purchase_request/delete_header/' . $pr->id) ?>"
                                           class="btn btn-sm btn-outline-danger btn-delete"
                                           data-message="<?= lang_line('purchase_request_delete_confirm'); ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create New PR -->
<div class="modal fade" id="addPrModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="POST" action="<?= base_url('purchase_request/add_header') ?>">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i> <?= lang_line('purchase_request_create_title'); ?></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('purchase_request_date_label', 'Date'); ?> <span class="text-danger">*</span></label>
                            <input type="date" name="pr_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label><?= lang_line('purchase_request_department_label', 'Department'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="department" class="form-control" value="<?= htmlspecialchars($this->session->userdata('departemen') ?? '') ?>" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('purchase_request_purpose_label', 'Purpose of Requisition'); ?> <span class="text-danger">*</span></label>
                        <input type="text" name="purpose" class="form-control" placeholder="<?= lang_line('purchase_request_purpose_placeholder', 'e.g. Bahan baku produksi bulan Mei...'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><?= lang_line('purchase_request_remarks_label', 'Notes'); ?></label>
                        <textarea name="remarks" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-arrow-right mr-1"></i> <?= lang_line('purchase_request_create_submit'); ?></button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang_line('btn_cancel'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2({ dropdownParent: $('#addPrModal'), width: '100%' });

    // Confirm delete
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        var msg = $(this).data('message') || 'Yakin?';
        if (confirm(msg)) window.location.href = $(this).attr('href');
    });
});
</script>
