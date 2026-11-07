<?php
    $badge_map = [
        'Submitted'=>'info',
        'Approved Head Dept'=>'primary',
        'Endorsed'=>'primary',
    ];
    $stage_label = [
        'Submitted'          => lang_line('erf_approval_stage_waiting', 'Menunggu Approval'),
        'Approved Head Dept' => lang_line('erf_approval_stage_pending_endo', 'Pending Endorsement'),
        'Endorsed'           => lang_line('erf_approval_stage_pending_final', 'Pending Final Approval'),
    ];
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-check-double text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
            <p class="text-muted small mb-0">
                <?= lang_line('erf_approval_workflow_1stage', 'Approval 1 tahap: <strong>Head of Dept</strong> &rarr; langsung siap dibayar') ?>
                <?php
                    $tier = [];
                    if (!empty($can_hod))         $tier[] = 'HOD';
                    if (!empty($can_endorsement)) $tier[] = 'Endorsement';
                    if (!empty($can_approval))    $tier[] = 'Approval';
                ?>
                <?php if (!empty($tier)): ?> &middot; <?= lang_line('erf_approval_access_label', 'Akses Anda:') ?> <strong><?= implode(' + ', $tier); ?></strong><?php endif; ?>
            </p>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list mr-1"></i> <?= lang_line('erf_approval_pending_title', 'Pending Approval') ?> (<?= count($pending); ?>)</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('erf_th_erf_number', 'Reimburse Request Number') ?></th>
                            <th><?= lang_line('erf_th_date', 'Date') ?></th>
                            <th><?= lang_line('erf_th_requester', 'Requester') ?></th>
                            <th class="text-right"><?= lang_line('erf_th_subtotal', 'Subtotal') ?></th>
                            <th><?= lang_line('erf_th_status', 'Status') ?></th>
                            <th><?= lang_line('erf_approval_pending_stage', 'Pending Stage') ?></th>
                            <th width="100"><?= lang_line('erf_th_action', 'Aksi') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pending)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-2x text-success mb-2"></i><br>
                                <?= lang_line('erf_approval_no_pending', 'Tidak ada Reimburse Request yang menunggu approval Anda.') ?>
                            </td></tr>
                        <?php else: foreach ($pending as $e): ?>
                            <?php $b = $badge_map[$e->status] ?? 'secondary'; ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($e->erf_number); ?></strong></td>
                                <td><?= date('d M Y', strtotime($e->erf_date)); ?></td>
                                <td><?= htmlspecialchars($e->requester_name ?? '-'); ?></td>
                                <td class="text-right"><?= number_format((float)$e->subtotal, 2); ?></td>
                                <td><span class="badge badge-<?= $b; ?>"><?= htmlspecialchars($e->status); ?></span></td>
                                <td><span class="badge badge-warning"><?= htmlspecialchars($stage_label[$e->status] ?? '—'); ?></span></td>
                                <td><a href="<?= base_url('erf/detail/' . $e->id); ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> <?= lang_line('erf_approval_btn_review', 'Review') ?></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
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
