<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-box-open text-success mr-2"></i><?= lang_line('subkon_in_title', 'Daftar Penerimaan Subkon'); ?></h1>
        <?php if (check_access('subkon_in', 'add')): ?>
            <a href="<?= base_url('subkon_in/create'); ?>" class="btn btn-success shadow-sm">
                <i class="fas fa-plus-circle fa-sm mr-1"></i> <?= lang_line('subkon_in_create_new', 'Buat Penerimaan (GRN)'); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fa fa-filter mr-1"></i> <?= lang_line('subkon_in_filter', 'Filter Penerimaan Subkon'); ?></h6>
        </div>
        <div class="card-body">
            <form action="<?= base_url('subkon_in/index'); ?>" method="GET">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="start_date"><?= lang_line('subkon_in_start_date', 'Mulai Tgl Terima'); ?></label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?= htmlspecialchars($filters['start_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="end_date"><?= lang_line('subkon_in_end_date', 'Sampai Tgl Terima'); ?></label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="<?= htmlspecialchars($filters['end_date']); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="status"><?= lang_line('subkon_in_status', 'Status'); ?></label>
                        <select name="status" id="status" class="form-control">
                            <option value=""><?= lang_line('subkon_in_all_status', 'Semua Status'); ?></option>
                            <option value="Draft" <?= $filters['status'] == 'Draft' ? 'selected' : ''; ?>><?= lang_line('subkon_in_status_draft', 'Draft'); ?></option>
                            <option value="Completed" <?= $filters['status'] == 'Completed' ? 'selected' : ''; ?>><?= lang_line('subkon_in_status_completed', 'Completed'); ?></option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="search"><?= lang_line('subkon_in_search', 'Cari (No. GRN/Kontrak/Supplier)'); ?></label>
                        <input type="text" name="search" id="search" class="form-control" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="<?= lang_line('subkon_in_search_placeholder', 'Cari...'); ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search fa-sm mr-1"></i> <?= lang_line('subkon_in_show', 'Tampilkan'); ?></button>
                <a href="<?= base_url('subkon_in/index'); ?>" class="btn btn-secondary"><?= lang_line('subkon_in_reset', 'Reset'); ?></a>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="dataTable" width="100%" cellspacing="0" style="font-size: 0.9rem;">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('subkon_in_no', 'No'); ?></th>
                            <th><?= lang_line('subkon_in_grn_number', 'No. Penerimaan'); ?></th>
                            <th><?= lang_line('subkon_in_receipt_date', 'Tgl Terima'); ?></th>
                            <th><?= lang_line('subkon_in_contract_no', 'No. Kontrak Subkon'); ?></th>
                            <th><?= lang_line('subkon_in_supplier', 'Supplier (Vendor)'); ?></th>
                            <th class="text-center"><?= lang_line('subkon_in_status', 'Status'); ?></th>
                            <th class="text-center"><?= lang_line('subkon_in_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($grns)): ?>
                            <tr><td colspan="7" class="text-center"><?= lang_line('subkon_in_no_data', 'Tidak ada data penerimaan subkon ditemukan.'); ?></td></tr>
                        <?php else: $no = $start_no; foreach ($grns as $grn): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><strong><?= htmlspecialchars($grn->subkon_in_no); ?></strong></td>
                                <td><?= date('d-m-Y', strtotime($grn->receipt_date)); ?></td>
                                <td><?= htmlspecialchars($grn->subkon_order_no); ?></td>
                                <td><?= htmlspecialchars($grn->nama_supplier); ?></td>
                                <td class="text-center">
                                    <?php
                                        $status_colors = ['Draft' => 'secondary', 'Completed' => 'success', 'Cancelled' => 'danger'];
                                        $color = $status_colors[$grn->status] ?? 'dark';
                                    ?>
                                    <span class="badge badge-<?= $color; ?>"><?= htmlspecialchars($grn->status); ?></span>
                                </td>
                                <td class="text-center text-nowrap">
                                    <a href="<?= base_url('subkon_in/detail/' . $grn->id); ?>" class="btn btn-info btn-sm" title="<?= lang_line('subkon_in_view_detail', 'Lihat Detail & Konfirmasi'); ?>">
                                        <i class="fas fa-search-plus"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <?= $pagination; ?>
            </div>
        </div>
    </div>
</div>