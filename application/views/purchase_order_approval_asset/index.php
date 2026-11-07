<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-0"><i class="fas fa-clipboard-check text-primary mr-2"></i>
                <?= htmlspecialchars($po_type_label ?? 'PO Asset'); ?> Approval
            </h3>
            <small class="text-muted">
                Alur approval 3 tahap: HOD &rarr; Endorsement &rarr; Approval Final.
                <?php
                    $tier = [];
                    if (!empty($can_hod))         $tier[] = 'HOD';
                    if (!empty($can_endorsement)) $tier[] = 'Endorsement';
                    if (!empty($can_approval))    $tier[] = 'Approval';
                ?>
                <?php if (!empty($tier)): ?>
                    &middot; Akses Anda: <strong><?= implode(' + ', $tier); ?></strong>
                <?php endif; ?>
            </small>
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
                           placeholder="No PO, Supplier, Dibuat Oleh..."
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
                <a href="<?= base_url('purchase_order_approval_asset') ?>" class="btn btn-sm btn-secondary mb-1 mr-1">Reset</a>
            </form>
            <div class="mt-1">
                <a href="<?= base_url('purchase_order_approval_asset/export_list_excel') . '?' . http_build_query($_GET) ?>" class="btn btn-sm btn-success no-loader mr-1" target="_blank"><i class="fas fa-file-excel"></i> Excel</a>
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
                            <th>PO Number</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Dibuat Oleh</th>
                            <th>Mata Uang</th>
                            <th>Subtotal</th>
                            <th>Status</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pos)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> Tidak ada data Purchase Order Asset</td></tr>
                        <?php else: ?>
                            <?php foreach ($pos as $po): ?>
                            <?php
                            $badge = [
                                'Draft'=>'warning','Submitted'=>'info',
                                'Approved Head Dept'=>'primary','Endorsed'=>'primary',
                                'Confirmed'=>'success','Closed'=>'dark',
                                'Cancelled'=>'secondary','Rejected'=>'danger'
                            ][$po->status] ?? 'secondary';
                            ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($po->po_number) ?></strong></td>
                                <td><?= date('d M Y', strtotime($po->po_date)) ?></td>
                                <td><?= htmlspecialchars($po->nama_supplier ?? '-') ?></td>
                                <td><?= htmlspecialchars($po->creator_name ?? '-') ?></td>
                                <td><?= htmlspecialchars($po->currency_code ?? 'IDR') ?></td>
                                <td class="text-right"><?= number_format((float)$po->subtotal, 2, '.', ',') ?></td>
                                <td><span class="badge badge-<?= $badge ?>"><?= $po->status ?></span></td>
                                <td>
                                    <a href="<?= base_url('purchase_order_approval_asset/detail/' . $po->id) ?>"
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
