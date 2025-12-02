<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-dolly text-primary mr-2"></i><?= lang_line('material_issuance_title', 'Pengeluaran Material'); ?></h3>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body py-2">
            <form method="get" action="<?= base_url('material_issuance/index'); ?>" class="form-inline">
                <div class="form-group mr-2 mb-2 flex-grow-1">
                    <input type="text" name="search" id="search" class="form-control form-control-sm w-100" placeholder="<?= lang_line('material_issuance_search', 'Cari No MR...'); ?>" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                </div>
                <div class="form-group mr-3 mb-2">
                    <label for="status" class="mr-2"><?= lang_line('material_issuance_filter_status', 'Status'); ?>:</label>
                    <select name="status" id="status" class="form-control form-control-sm" style="width: 180px;">
                        <option value=""><?= lang_line('material_issuance_all_status', '-- Semua Status --'); ?></option>
                        <option value="Submitted" <?= ($filters['status'] ?? '') == 'Submitted' ? 'selected' : ''; ?>><?= lang_line('material_issuance_submitted', 'Submitted'); ?></option>
                        <option value="Issued" <?= ($filters['status'] ?? '') == 'Issued' ? 'selected' : ''; ?>><?= lang_line('material_issuance_issued', 'Issued'); ?></option>
                        <option value="Received" <?= ($filters['status'] ?? '') == 'Received' ? 'selected' : ''; ?>><?= lang_line('material_issuance_received', 'Received'); ?></option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search fa-sm mr-1"></i> <?= lang_line('search', 'Cari'); ?></button>
                    <a href="<?= base_url('material_issuance'); ?>" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-redo fa-sm mr-1"></i> <?= lang_line('reset', 'Reset'); ?></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-header py-3">
             <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('material_issuance_items_list', 'Daftar Material Request (Warehouse)'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('material_issuance_mr_number', 'No. MR'); ?></th>
                            <th><?= lang_line('material_issuance_request_date', 'Tgl Permintaan'); ?></th>
                            <th><?= lang_line('material_issuance_requester', 'Dibuat Oleh (PPIC)'); ?></th>
                            <th><?= lang_line('material_issuance_status', 'Status'); ?></th> <th width="190"><?= lang_line('material_issuance_actions', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($mrs)): foreach($mrs as $mr): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($mr->mr_number); ?></td>
                            <td><?= date('d M Y', strtotime($mr->request_date)); ?></td>
                            <td class="text-left"><?= htmlspecialchars($mr->requester_name ?? '-'); ?></td>
                            <td><span class="badge badge-<?= $mr->status == 'Issued' ? 'success' : ($mr->status == 'Received' ? 'primary' : 'warning'); ?>"><?= $mr->status; ?></span></td>
                            <td>
                                <?php if($mr->status == 'Submitted'): ?>
                                    <a href="<?= base_url('material_issuance/detail/'.$mr->id); ?>" class="btn btn-sm btn-info btn-load">
                                        <i class="fas fa-sign-out-alt"></i> <?= lang_line('material_issuance_process', 'Proses Pengeluaran'); ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= base_url('material_issuance/detail/'.$mr->id); ?>" class="btn btn-sm btn-secondary btn-load">
                                        <i class="fas fa-eye"></i> <?= lang_line('material_issuance_detail', 'Lihat Detail'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="5" class="text-center text-muted py-3"><?= lang_line('material_issuance_no_data', 'Tidak ada Material Request ditemukan'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

