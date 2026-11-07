<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-money-bill-wave text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
            <p class="text-muted small mb-0"><?= lang_line('erf_payment_subtitle'); ?></p>
        </div>
    </div>

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link <?= $active_tab == 'pending' ? 'active' : ''; ?>" href="?tab=pending"><?= lang_line('erf_tab_pending'); ?> (<?= count($pending_list); ?>)</a></li>
        <li class="nav-item"><a class="nav-link <?= $active_tab == 'paid' ? 'active' : ''; ?>" href="?tab=paid"><?= lang_line('erf_tab_paid'); ?> (<?= count($paid_list); ?>)</a></li>
    </ul>

    <?php $rows = $active_tab == 'paid' ? $paid_list : $pending_list; $sum = $active_tab == 'paid' ? $sum_paid : $sum_pending; ?>
    <div class="card border-0 shadow-sm">
        <div class="card-header py-2 d-flex justify-content-between">
            <span class="font-weight-bold"><?= lang_line('erf_total_label'); ?> <?= count($rows); ?> Reimburse Request</span>
            <span class="font-weight-bold text-primary"><?= lang_line('erf_sum_label'); ?> <?= number_format($sum, 2); ?></span>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('erf_th_erf_number'); ?></th>
                            <th><?= lang_line('erf_th_date'); ?></th>
                            <th><?= lang_line('erf_th_requester'); ?></th>
                            <th class="text-right"><?= lang_line('erf_th_amount'); ?></th>
                            <?php if ($active_tab == 'paid'): ?>
                                <th><?= lang_line('erf_th_payment_date'); ?></th>
                                <th><?= lang_line('erf_th_method'); ?></th>
                                <th class="text-center"><?= lang_line('erf_th_pay_proof', 'Bukti'); ?></th>
                            <?php endif; ?>
                            <th width="80"><?= lang_line('erf_th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="<?= $active_tab == 'paid' ? 8 : 5; ?>" class="text-center text-muted py-4"><i class="fas fa-inbox mr-1"></i> <?= lang_line('erf_no_data'); ?></td></tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($r->erf_number); ?></strong></td>
                                <td><?= date('d M Y', strtotime($r->erf_date)); ?></td>
                                <td><?= htmlspecialchars($r->requester_name ?? '-'); ?></td>
                                <td class="text-right"><?= number_format((float)$r->subtotal, 2); ?></td>
                                <?php if ($active_tab == 'paid'): ?>
                                    <td><?= $r->payment_date ? date('d M Y', strtotime($r->payment_date)) : '-'; ?></td>
                                    <td><?= htmlspecialchars($r->payment_method ?: '-'); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($r->payment_proof_path)): ?>
                                            <a href="<?= base_url($r->payment_proof_path); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="<?= lang_line('erf_view_proof', 'Lihat Bukti'); ?>">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td><a href="<?= base_url('erf/detail/' . $r->id); ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
