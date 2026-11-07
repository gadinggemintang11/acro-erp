<?php
$active_tab = isset($active_tab) ? $active_tab : 'pending';
$f = isset($filters) ? $filters : ['date_from' => '', 'date_to' => '', 'search' => ''];
?>
<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col">
            <h1 class="mb-0"><i class="fas fa-wallet"></i> <?= lang_line('prf_payment_title', 'Pembayaran Payment Request'); ?></h1>
            <p class="text-muted mb-0"><?= lang_line('prf_payment_subtitle', 'Kelola pencatatan pembayaran & lihat history pembayaran yang sudah dilakukan finance.'); ?></p>
        </div>
        <div class="col text-right">
            <a href="<?php echo base_url('prf'); ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= lang_line('prf_back_to_prf', 'Kembali ke Payment Request'); ?>
            </a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-warning shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase font-weight-bold"><?= lang_line('prf_pay_pending', 'Menunggu Pembayaran'); ?></small>
                    <h3 class="mb-0 text-warning"><?php echo count($pending_list); ?></h3>
                    <small class="text-muted">Payment Request</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase font-weight-bold"><?= lang_line('prf_pay_outstanding', 'Total Outstanding'); ?></small>
                    <h4 class="mb-0 text-warning">Rp <?php echo number_format($sum_pending, 2, ',', '.'); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase font-weight-bold"><?= lang_line('prf_pay_paid', 'Sudah Dibayar'); ?></small>
                    <h3 class="mb-0 text-success"><?php echo count($paid_list); ?></h3>
                    <small class="text-muted">Payment Request</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase font-weight-bold"><?= lang_line('prf_pay_total_paid', 'Total Dibayar'); ?></small>
                    <h4 class="mb-0 text-success">Rp <?php echo number_format($sum_paid, 2, ',', '.'); ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab === 'pending' ? 'active' : ''; ?>" data-toggle="tab" href="#tab-pending" role="tab">
                <i class="fas fa-hourglass-half text-warning"></i> <?= lang_line('prf_pay_pending', 'Menunggu Pembayaran'); ?>
                <span class="badge badge-warning ml-1"><?php echo count($pending_list); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $active_tab === 'paid' ? 'active' : ''; ?>" data-toggle="tab" href="#tab-paid" role="tab">
                <i class="fas fa-check-circle text-success"></i> <?= lang_line('prf_pay_paid', 'Sudah Dibayar'); ?>
                <span class="badge badge-success ml-1"><?php echo count($paid_list); ?></span>
            </a>
        </li>
    </ul>

    <div class="tab-content border border-top-0 bg-white p-3">
        <!-- Pending Tab -->
        <div class="tab-pane fade <?php echo $active_tab === 'pending' ? 'show active' : ''; ?>" id="tab-pending" role="tabpanel">
            <?php if (!empty($pending_list)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= lang_line('prf_th_no', 'No. Payment Request'); ?></th>
                                <th><?= lang_line('prf_th_date', 'Tanggal'); ?></th>
                                <th><?= lang_line('prf_th_due_date', 'Jatuh Tempo'); ?></th>
                                <th><?= lang_line('prf_th_department', 'Departemen'); ?></th>
                                <th><?= lang_line('prf_th_requester2', 'Pemohon'); ?></th>
                                <th class="text-right"><?= lang_line('prf_th_total', 'Total'); ?></th>
                                <th class="text-center"><?= lang_line('prf_th_attachment', 'Lampiran'); ?></th>
                                <th class="text-center"><?= lang_line('prf_th_status', 'Status'); ?></th>
                                <th class="text-center" style="width: 140px;"><?= lang_line('label_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_list as $prf): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prf->prf_number); ?></strong></td>
                                    <td><?php echo date('d-m-Y', strtotime($prf->prf_date)); ?></td>
                                    <td>
                                        <?php if (!empty($prf->due_date)):
                                            $is_overdue = strtotime($prf->due_date) < strtotime(date('Y-m-d')); ?>
                                            <span class="<?php echo $is_overdue ? 'text-danger font-weight-bold' : ''; ?>" <?php echo $is_overdue ? 'title="' . htmlspecialchars(lang_line('prf_overdue', 'Sudah lewat jatuh tempo')) . '"' : ''; ?>>
                                                <?php echo date('d-m-Y', strtotime($prf->due_date)); ?>
                                                <?php if ($is_overdue): ?><i class="fas fa-exclamation-triangle"></i><?php endif; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($prf->department_name ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($prf->requester_name ?? '-'); ?></td>
                                    <td class="text-right">Rp <?php echo number_format($prf->subtotal, 2, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($prf->attachment_count)): ?>
                                            <a href="<?php echo base_url('prf/detail/' . $prf->id); ?>#lampiran" class="badge badge-primary" title="<?= lang_line('prf_view_attachment', 'Lihat lampiran'); ?>" style="font-size:12px;">
                                                <i class="fas fa-paperclip"></i> <?php echo $prf->attachment_count; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info"><?php echo $prf->status; ?></span>
                                        <br>
                                        <span class="badge badge-warning mt-1"><?php echo $prf->payment_status; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <?php // "PRF Selanjutnya" ([[erp_revision_prf_erf]]): tandai konteks
                                              // antrian payment supaya detail-nya bisa nawarin lompat ke
                                              // PRF berikutnya yang masih pending dibayar. ?>
                                        <a href="<?php echo base_url('prf/detail/' . $prf->id); ?>?queue=payment" class="btn btn-sm btn-success">
                                            <i class="fas fa-money-bill-wave"></i> <?= lang_line('prf_pay_btn', 'Bayar'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                    <p class="text-muted mb-0"><?= lang_line('prf_no_pending', 'Tidak ada Payment Request yang menunggu pembayaran.'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Paid Tab -->
        <div class="tab-pane fade <?php echo $active_tab === 'paid' ? 'show active' : ''; ?>" id="tab-paid" role="tabpanel">
            <!-- Filter -->
            <form method="GET" class="form-inline mb-3">
                <input type="hidden" name="tab" value="paid">
                <div class="form-group mr-2">
                    <label class="mr-2 small"><?= lang_line('prf_pay_from', 'Dari Tgl Bayar'); ?>:</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo htmlspecialchars($f['date_from'] ?? ''); ?>">
                </div>
                <div class="form-group mr-2">
                    <label class="mr-2 small"><?= lang_line('prf_pay_to', 'Sampai'); ?>:</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo htmlspecialchars($f['date_to'] ?? ''); ?>">
                </div>
                <div class="form-group mr-2">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="<?= lang_line('prf_pay_search_ph', 'Cari No. Payment Request / Pemohon'); ?>" value="<?php echo htmlspecialchars($f['search'] ?? ''); ?>">
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-search"></i> <?= lang_line('prf_search', 'Cari'); ?>
                </button>
                <a href="<?php echo base_url('prf/payment?tab=paid'); ?>" class="btn btn-sm btn-outline-secondary ml-1"><?= lang_line('prf_reset', 'Reset'); ?></a>
            </form>

            <?php if (!empty($paid_list)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= lang_line('prf_th_no', 'No. Payment Request'); ?></th>
                                <th><?= lang_line('prf_th_date', 'Tanggal'); ?> Payment Request</th>
                                <th><?= lang_line('prf_th_due_date', 'Jatuh Tempo'); ?></th>
                                <th><?= lang_line('prf_th_department', 'Departemen'); ?></th>
                                <th><?= lang_line('prf_th_requester2', 'Pemohon'); ?></th>
                                <th class="text-right"><?= lang_line('prf_th_total', 'Total'); ?></th>
                                <th class="text-center"><?= lang_line('prf_th_attachment', 'Lampiran'); ?></th>
                                <th><?= lang_line('prf_th_pay_date', 'Tgl. Bayar'); ?></th>
                                <th><?= lang_line('prf_th_pay_method', 'Metode'); ?></th>
                                <th class="text-center"><?= lang_line('prf_th_proof', 'Bukti'); ?></th>
                                <th class="text-center" style="width: 110px;"><?= lang_line('label_action', 'Aksi'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paid_list as $prf): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prf->prf_number); ?></strong></td>
                                    <td><?php echo date('d-m-Y', strtotime($prf->prf_date)); ?></td>
                                    <td><?php echo !empty($prf->due_date) ? date('d-m-Y', strtotime($prf->due_date)) : '<span class="text-muted">-</span>'; ?></td>
                                    <td><?php echo htmlspecialchars($prf->department_name ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($prf->requester_name ?? '-'); ?></td>
                                    <td class="text-right">Rp <?php echo number_format($prf->subtotal, 2, ',', '.'); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($prf->attachment_count)): ?>
                                            <a href="<?php echo base_url('prf/detail/' . $prf->id); ?>#lampiran" class="badge badge-primary" title="<?= lang_line('prf_view_attachment', 'Lihat lampiran'); ?>" style="font-size:12px;">
                                                <i class="fas fa-paperclip"></i> <?php echo $prf->attachment_count; ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $prf->payment_date ? date('d-m-Y', strtotime($prf->payment_date)) : '-'; ?></td>
                                    <td><?php echo htmlspecialchars($prf->payment_method ?: '-'); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($prf->payment_proof_path)): ?>
                                            <a href="<?php echo base_url($prf->payment_proof_path); ?>" target="_blank" class="btn btn-sm btn-outline-info" title="<?= lang_line('prf_view_proof', 'Lihat Bukti'); ?>">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo base_url('prf/detail/' . $prf->id); ?>" class="btn btn-sm btn-info" title="<?= lang_line('prf_th_detail', 'Detail'); ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-danger dropdown-toggle" data-toggle="dropdown" title="<?= lang_line('erf_btn_pdf', 'PDF'); ?>">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A4&orient=L'); ?>">A4 Landscape</a>
                                                <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A4&orient=P'); ?>">A4 Portrait</a>
                                                <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A5&orient=L'); ?>">A5 Landscape</a>
                                                <a class="dropdown-item" target="_blank" href="<?php echo base_url('prf/export_pdf/' . $prf->id . '?size=A5&orient=P'); ?>">A5 Portrait</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-receipt fa-3x text-muted mb-2"></i>
                    <p class="text-muted mb-0"><?= lang_line('prf_no_payment_history', 'Belum ada riwayat pembayaran.'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.border-left-warning { border-left: 4px solid #f6c23e !important; }
.border-left-success { border-left: 4px solid #1cc88a !important; }
.nav-tabs .nav-link { color: #555; }
.nav-tabs .nav-link.active { font-weight: 600; }
</style>
