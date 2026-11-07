<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-clipboard-check text-primary mr-2"></i><?= lang_line('production_receive_title', 'Penerimaan Material Produksi'); ?>
        </h1>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('pr_filter_search_title', 'Filter & Cari'); ?></h6>
        </div>
        <div class="card-body">
            <form method="GET" action="<?= base_url('production_receive'); ?>" class="form-inline">
                <input type="text" name="search" placeholder="<?= lang_line('pr_search_issuance_placeholder', 'Cari MR atau MI...'); ?>" class="form-control mr-2" value="<?= htmlspecialchars($filters['search'] ?? ''); ?>">
                <select name="status" class="form-control mr-2">
                    <option value=""><?= lang_line('pr_filter_status_all', '-- Semua Status --'); ?></option>
                    <option value="Issued" <?= ($filters['status'] === 'Issued' ? 'selected' : ''); ?>>Issued</option>
                    <option value="Received" <?= ($filters['status'] === 'Received' ? 'selected' : ''); ?>>Received</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><?= lang_line('btn_search', 'Cari'); ?></button>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('pr_issuance_list_title', 'Daftar Issuance'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('pr_info_mr_number', 'MR Number'); ?></th>
                            <th><?= lang_line('pr_info_issuance_number', 'Issuance Number'); ?></th>
                            <th><?= lang_line('pr_th_request_date', 'Tgl Minta'); ?></th>
                            <th><?= lang_line('pr_th_issued_at', 'Issued At'); ?></th>
                            <th><?= lang_line('pr_th_status', 'Status'); ?></th>
                            <th><?= lang_line('pr_th_requester', 'Requester'); ?></th>
                            <th><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($issuances)): ?>
                            <?php foreach ($issuances as $iss): ?>
                            <tr>
                                <td><?= htmlspecialchars($iss->mr_number); ?></td>
                                <td><strong><?= htmlspecialchars($iss->issuance_number); ?></strong></td>
                                <td><?= date('d M Y', strtotime($iss->request_date)); ?></td>
                                <td><?= date('d M Y H:i', strtotime($iss->issued_at)); ?></td>
                                <td>
                                    <?php
                                    $badge = 'warning';
                                    if ($iss->status == 'Received') $badge = 'success';
                                    ?>
                                    <span class="badge badge-<?= $badge; ?>"><?= $iss->status; ?></span>
                                </td>
                                <td><?= htmlspecialchars($iss->requester_name ?? '-'); ?></td>
                                <td>
                                    <a href="<?= base_url('production_receive/detail_issuance/' . $iss->issuance_id); ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> <?= lang_line('pr_btn_detail', 'Detail'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-4"><?= lang_line('pr_no_issuance_data', 'Tidak ada data issuance'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
