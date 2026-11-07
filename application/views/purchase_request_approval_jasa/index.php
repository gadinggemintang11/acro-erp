<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-clipboard-check text-primary mr-2"></i> PR Jasa Approval — Semua Status</h3>
            <small class="text-muted">Review dan setujui Purchase Request Jasa dari tim</small>
        </div>
        <div>
            <a href="<?= base_url('purchase_request_approval_jasa/approval'); ?>" class="btn btn-sm btn-primary">
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
                           placeholder="PR #, Requester, Keperluan..."
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">-- Semua Status --</option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] === $s) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i> Filter</button>
                <a href="<?= base_url('purchase_request_approval_jasa') ?>" class="btn btn-sm btn-secondary mb-1 mr-1">Reset</a>
            </form>
            <div class="mt-1">
                <a href="<?= base_url('purchase_request_approval_jasa/export_list_excel') . '?' . http_build_query($_GET) ?>" class="btn btn-sm btn-success no-loader mr-1" target="_blank"><i class="fas fa-file-excel"></i> Excel</a>
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
                            <th>PR Number</th>
                            <th>Tanggal</th>
                            <th>Requester</th>
                            <th>Supplier (Usulan)</th>
                            <th>Keperluan</th>
                            <th>Status</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prs)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> Tidak ada data Purchase Request Jasa</td></tr>
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
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($pr->pr_number) ?></strong></td>
                                <td><?= date('d M Y', strtotime($pr->pr_date)) ?></td>
                                <td><?= htmlspecialchars($pr->requester_name ?? '-') ?></td>
                                <td><?= htmlspecialchars($pr->nama_supplier ?? '-') ?></td>
                                <td><?= htmlspecialchars(mb_strimwidth($pr->purpose ?? '-', 0, 50, '...')) ?></td>
                                <td><span class="badge badge-<?= $badge ?>"><?= $pr->status ?></span></td>
                                <td>
                                    <a href="<?= base_url('purchase_request_approval_jasa/detail/' . $pr->id) ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> Review
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
