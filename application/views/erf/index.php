<?php
    $badge_map = [
        'Draft'=>'warning','Submitted'=>'info',
        'Approved Head Dept'=>'primary','Endorsed'=>'primary',
        'Approved'=>'success','Paid'=>'success',
        'Cancelled'=>'secondary','Rejected'=>'danger',
    ];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-hand-holding-usd text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
            <p class="text-muted small mb-0"><?= lang_line('erf_tagline', 'Buat & kelola Reimburse Request kamu. Submit untuk approval HOD → Endorsement → Finance.'); ?></p>
        </div>
        <a href="<?= base_url('erf/create'); ?>" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i> <?= lang_line('erf_create_new', 'Buat Reimburse Request Baru'); ?>
        </a>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="form-inline flex-wrap">
                <div class="form-group mr-2 mb-1">
                    <label class="mr-1 small"><?= lang_line('erf_filter_from', 'Dari'); ?></label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <label class="mr-1 small"><?= lang_line('erf_filter_to', 's/d'); ?></label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
                </div>
                <div class="form-group mr-2 mb-1">
                    <select name="status" class="form-control form-control-sm">
                        <option value=""><?= lang_line('erf_filter_all_status', '-- Semua Status --'); ?></option>
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= ($filters['status'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary mr-1 mb-1"><i class="fas fa-search"></i></button>
                <a href="<?= base_url('erf'); ?>" class="btn btn-sm btn-secondary mb-1"><i class="fas fa-redo"></i></a>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('erf_th_number', 'Reimburse Request Number'); ?></th>
                            <th><?= lang_line('erf_th_date', 'Date'); ?></th>
                            <th><?= lang_line('erf_th_requester', 'Requester'); ?></th>
                            <th class="text-right"><?= lang_line('erf_th_subtotal', 'Subtotal'); ?></th>
                            <th><?= lang_line('erf_th_status', 'Status'); ?></th>
                            <th><?= lang_line('erf_th_payment', 'Payment'); ?></th>
                            <th width="110"><?= lang_line('label_action', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($erf_list)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('erf_no_data', 'Belum ada Reimburse Request.'); ?></td></tr>
                        <?php else: foreach ($erf_list as $e): ?>
                            <?php $b = $badge_map[$e->status] ?? 'secondary'; ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($e->erf_number); ?></strong></td>
                                <td><?= date('d M Y', strtotime($e->erf_date)); ?></td>
                                <td><?= htmlspecialchars($e->requester_name ?? '-'); ?></td>
                                <td class="text-right"><?= number_format((float)$e->subtotal, 2); ?></td>
                                <td><span class="badge badge-<?= $b; ?>"><?= htmlspecialchars($e->status); ?></span></td>
                                <td><span class="badge badge-<?= $e->payment_status == 'Paid' ? 'success' : 'secondary'; ?>"><?= htmlspecialchars($e->payment_status); ?></span></td>
                                <td>
                                    <a href="<?= base_url('erf/detail/' . $e->id); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
