<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-eye text-info mr-2"></i><?= lang_line('prodreturn_view', 'Detail Retur Produksi'); ?>
        </h3>
    </div>

    <?php 
        $header  = $return_data['header'] ?? [];
        $details = $return_data['details'] ?? [];
    ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
            <h6 class="m-0 font-weight-bold text-primary">
                <?= lang_line('prodreturn_detail', 'Detail Retur'); ?>: <?= htmlspecialchars($header['prod_return_no'] ?? '-'); ?>
            </h6>

            <div class="mt-2 mt-md-0">
                <?php if (($header['status'] ?? '') === 'Draft'): ?>
                    <?php if (true): // TODO: ganti dengan check_access('prod_return', 'confirm') ?>
                    <a href="<?= base_url('ProdReturn/confirm/' . ($header['id'] ?? 0)); ?>" 
                       class="btn btn-success btn-sm btn-confirm" 
                       data-message="<?= lang_line('prodreturn_confirm_action', 'Konfirmasi retur ini? Stok akan dikembalikan ke gudang.'); ?>">
                        <i class="fas fa-check mr-1"></i> <?= lang_line('prodreturn_confirm', 'Konfirmasi'); ?>
                    </a>
                    <?php endif; ?>

                <?php elseif (($header['status'] ?? '') === 'Confirmed'): ?>
                    <?php if (true): // TODO: ganti dengan check_access('prod_return', 'unconfirm') ?>
                    <a href="<?= base_url('ProdReturn/unconfirm/' . ($header['id'] ?? 0)); ?>" 
                       class="btn btn-danger btn-sm btn-confirm" 
                       data-message="<?= lang_line('prodreturn_unconfirm_action', 'BATALKAN Konfirmasi? Stok akan ditarik dari gudang.'); ?>">
                        <i class="fas fa-times mr-1"></i> <?= lang_line('prodreturn_unconfirm', 'Batalkan Konfirmasi'); ?>
                    </a>
                    <?php endif; ?>

                    <a href="<?= base_url('ProdReturn/export_pdf/' . ($header['id'] ?? 0)); ?>" 
                       target="_blank" class="btn btn-secondary btn-sm">
                        <i class="fas fa-print mr-1"></i> <?= lang_line('prodreturn_export_pdf', 'Cetak PDF'); ?>
                    </a>
                <?php endif; ?>

                <a href="<?= base_url('ProdReturn'); ?>" class="btn btn-secondary btn-sm btn-load">
                    <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('prodreturn_back_to_list', 'Kembali ke List'); ?>
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- Header Info -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="150"><strong><?= lang_line('prodreturn_return_no', 'No. Retur'); ?></strong></td>
                            <td width="10">:</td>
                            <td><?= htmlspecialchars($header['prod_return_no'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?= lang_line('prodreturn_return_date', 'Tgl Retur'); ?></strong></td>
                            <td>:</td>
                            <td>
                                <?= !empty($header['return_date']) 
                                    ? date('d M Y', strtotime($header['return_date'])) 
                                    : '-'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?= lang_line('prodreturn_status', 'Status'); ?></strong></td>
                            <td>:</td>
                            <td>
                                <?php
                                    $status = $header['status'] ?? '-';
                                    $badge_class = 'secondary';
                                    if ($status === 'Draft') $badge_class = 'warning';
                                    elseif ($status === 'Confirmed') $badge_class = 'success';
                                    elseif ($status === 'Cancelled') $badge_class = 'danger';
                                ?>
                                <span class="badge badge-<?= $badge_class; ?>">
                                    <?= htmlspecialchars($status); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?= lang_line('prodreturn_returner', 'Diretur Oleh'); ?></strong></td>
                            <td>:</td>
                            <td><?= htmlspecialchars($header['returner_name'] ?? '-'); ?></td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td width="150"><strong><?= lang_line('prodreturn_work_order', 'No. Work Order'); ?></strong></td>
                            <td width="10">:</td>
                            <td><?= htmlspecialchars($header['wo_number'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?= lang_line('prodreturn_confirmed_by', 'Dikonfirmasi Oleh'); ?></strong></td>
                            <td>:</td>
                            <td><?= htmlspecialchars($header['receiver_name'] ?? ($header['confirmer_name'] ?? '-')); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?= lang_line('prodreturn_confirmed_at', 'Tgl Konfirmasi'); ?></strong></td>
                            <td>:</td>
                            <td>
                                <?= !empty($header['confirmed_at'])
                                    ? date('d M Y H:i', strtotime($header['confirmed_at']))
                                    : '-'; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong><?= lang_line('prodreturn_notes', 'Catatan'); ?></strong></td>
                            <td>:</td>
                            <td><?= nl2br(htmlspecialchars($header['notes'] ?? '-')); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Detail Item -->
            <h5 class="text-gray-800 mt-4"><?= lang_line('prodreturn_items_returned', 'Detail Item Retur'); ?></h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('no', 'No.'); ?></th>
                            <th><?= lang_line('prodreturn_material_name', 'Material'); ?></th>
                            <th><?= lang_line('prodreturn_unit', 'Satuan'); ?></th>
                            <th><?= lang_line('prodreturn_qty_return', 'Qty Retur'); ?></th>
                            <th><?= lang_line('prodreturn_reason', 'Alasan'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($details)): $no = 1; ?>
                            <?php foreach ($details as $item): ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($item['nama_material'] ?? '-'); ?></td>
                                    <td class="text-center"><?= htmlspecialchars($item['satuan_desc'] ?? ($item['satuan_code'] ?? '-')); ?></td>
                                    <td class="text-right"><?= number_format((float)($item['qty_return'] ?? 0), 2); ?></td>
                                    <td><?= htmlspecialchars($item['reason'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    <?= lang_line('prodreturn_no_items', 'Tidak ada item detail'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
