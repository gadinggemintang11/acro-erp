<?php
    // Sama seperti purchase_request_general/index.php — listing gabungan
    // General+Jasa+Asset, tiap baris routing ke controller approval ASLI
    // tipe itu (lihat memory pr_controllers_merge.md).
    $pr_type_routes = [
        'non_produksi' => ['route' => 'purchase_request_approval_general', 'label' => 'General', 'badge' => 'secondary'],
        'jasa'         => ['route' => 'purchase_request_approval_jasa',    'label' => 'Jasa',    'badge' => 'info'],
        'asset'        => ['route' => 'purchase_request_approval_asset',   'label' => 'Asset',   'badge' => 'dark'],
    ];
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-clipboard-check text-primary mr-2"></i> <?= lang_line('page_purchase_request_approval_title', 'PR Approval'); ?> — Semua Status</h3>
            <small class="text-muted"><?= lang_line('purchase_request_approval_index_subtitle', 'Review dan setujui Purchase Request dari tim'); ?></small>
        </div>
        <div>
            <a href="<?= base_url('purchase_request_approval_general/approval'); ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-check-double mr-1"></i> Pending Approval Saya
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $this->session->flashdata('success') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $this->session->flashdata('error') ?> <button type="button" class="close" data-dismiss="alert">&times;</button></div>
    <?php endif; ?>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap gap-2">
                <div class="form-group mr-2 mb-1">
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="<?= lang_line('purchase_request_approval_search_placeholder', 'PR #, Requester, Keperluan...'); ?>"
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value=""><?= lang_line('purchase_request_approval_all_status', '-- Semua Status --'); ?></option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> <?= lang_line('btn_filter', 'Filter'); ?></button>
                <a href="<?= base_url('purchase_request_approval_general') ?>" class="btn btn-sm btn-secondary mb-1 mr-1"><?= lang_line('btn_reset', 'Reset'); ?></a>
            </form>
            <div class="mt-1">
                <a href="<?= base_url('purchase_request_approval_general/export_list_excel') . '?' . http_build_query($_GET) ?>" class="btn btn-sm btn-success no-loader mr-1" target="_blank"><i class="fas fa-file-excel"></i> Excel</a>
                <a href="<?= base_url('purchase_request_approval_general/export_list_pdf') . '?' . http_build_query($_GET) ?>" class="btn btn-sm btn-danger no-loader" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
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
                            <th><?= lang_line('purchase_request_approval_table_header_number', 'PR Number'); ?></th>
                            <th><?= lang_line('po_kategori', 'Kategori'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_header_date', 'Tanggal'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_header_requester', 'Requester'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_header_supplier', 'Supplier (Usulan)'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_header_purpose', 'Keperluan'); ?></th>
                            <th><?= lang_line('purchase_request_approval_table_header_status', 'Status'); ?></th>
                            <th width="100"><?= lang_line('purchase_request_approval_table_header_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prs)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('purchase_request_approval_no_data', 'Tidak ada data Purchase Request'); ?></td></tr>
                        <?php else: ?>
                            <?php foreach ($prs as $pr): ?>
                            <?php
                            $badge = [
                                'Draft'              => 'secondary',
                                'Submitted'          => 'warning',
                                'Approved Head Dept' => 'info',
                                'Endorsed'           => 'primary',
                                'Approved'           => 'success',
                                'Rejected'           => 'danger',
                            ][$pr->status] ?? 'secondary';
                            $rt = $pr_type_routes[$pr->type] ?? $pr_type_routes['non_produksi'];
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($pr->pr_number) ?></strong></td>
                                <td><span class="badge badge-<?= $rt['badge']; ?>"><?= $rt['label']; ?></span></td>
                                <td><?= date('d M Y', strtotime($pr->pr_date)) ?></td>
                                <td><?= htmlspecialchars($pr->requester_name ?? '-') ?></td>
                                <td><?= htmlspecialchars($pr->nama_supplier ?? '-') ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($pr->purpose ?? '-', 0, 50, '...')) ?></td>
                                <td><span class="badge badge-<?= $badge ?>"><?= $pr->status ?></span></td>
                                <td>
                                    <a href="<?= base_url($rt['route'] . '/detail/' . $pr->id) ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> <?= lang_line('purchase_request_approval_button_review', 'Review'); ?>
                                    </a>
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
