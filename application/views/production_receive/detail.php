<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-check-square text-primary mr-2"></i><?= htmlspecialchars($title); ?>
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->mr_number); ?></span>
            <?php if($header->issuance_number): ?>
                <span class="badge badge-success ml-2"><?= htmlspecialchars($header->issuance_number); ?></span>
            <?php endif; ?>
        </h3>
        <div>
            <?php if ($header->status == 'Issued' && check_access('production_receive', 'edit')): ?>
                 <a href="<?= base_url('production_receive/confirm_receive/'.$header->id); ?>" class="btn btn-primary btn-sm mr-1" onclick="return confirm('<?= lang_line('pr_confirm_receive_confirm', 'Yakin ingin konfirmasi penerimaan barang ini?'); ?>');">
                    <i class="fas fa-check-circle fa-sm"></i> <?= lang_line('pr_btn_confirm_receive', 'Confirm Receive'); ?>
                 </a>
            <?php elseif ($header->status == 'Received' && check_access('production_receive', 'edit')): ?>
                 <a href="<?= base_url('production_receive/unconfirm_receive/'.$header->id); ?>" class="btn btn-danger btn-sm mr-1" onclick="return confirm('<?= lang_line('pr_unconfirm_receive_confirm', 'Yakin ingin membatalkan penerimaan ini? Status akan kembali ke Issued.'); ?>');">
                    <i class="fas fa-undo fa-sm"></i> <?= lang_line('pr_btn_unconfirm_receive', 'Unconfirm Receive'); ?>
                 </a>
            <?php endif; ?>

            <?php if (($header->status == 'Issued' || $header->status == 'Received') && check_access('production_receive', 'view')): ?>
                <a href="<?= base_url('production_receive/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-info btn-sm mr-1">
                    <i class="fas fa-file-pdf fa-sm"></i> <?= lang_line('pr_btn_export_pdf', 'Export PDF'); ?>
                </a>
            <?php endif; ?>
            <a href="<?= base_url('production_receive'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-2"></i><?= lang_line('btn_back', 'Kembali'); ?>
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
             <div class="row">
                <div class="col-md-4"><strong><?= lang_line('pr_info_mr_no', 'No. MR'); ?>:</strong> <?= htmlspecialchars($header->mr_number); ?></div>
                <div class="col-md-4"><strong><?= lang_line('pr_info_date', 'Tanggal'); ?>:</strong> <?= date('d M Y', strtotime($header->request_date)); ?></div>
                <div class="col-md-4"><strong><?= lang_line('pr_info_status', 'Status'); ?>:</strong> <span class="badge badge-<?= $header->status == 'Received' ? 'success' : ($header->status == 'Issued' ? 'warning' : 'info'); ?>"><?= $header->status; ?></span></div>
             </div>
             <div class="row mt-2">
                 <div class="col-md-4"><strong><?= lang_line('pr_info_created_by', 'Dibuat Oleh'); ?>:</strong> <?= htmlspecialchars($header->requester_name ?? '-'); ?></div>
                 <div class="col-md-4"><strong><?= lang_line('pr_info_prepared_by', 'Disiapkan Oleh'); ?>:</strong> <?= htmlspecialchars($header->issuer_name ?? '-'); ?></div>
                 <div class="col-md-4"><strong><?= lang_line('pr_info_received_by', 'Diterima Oleh'); ?>:</strong> <?= htmlspecialchars($header->receiver_name ?? '-'); ?></div>
             </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('pr_items_title', 'Item yang Diterima (Read-Only)'); ?></h6>
        </div>
        <div class="card-body">
             <div class="table-responsive">
                <table class="table table-bordered table-sm" id="itemIssuanceTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('pr_th_wo', 'WO'); ?></th>
                            <th><?= lang_line('pr_th_item_code', 'Kode Item'); ?></th>
                            <th><?= lang_line('pr_th_item_name', 'Nama Item'); ?></th>
                            <th><?= lang_line('pr_th_unit', 'Satuan'); ?></th>
                            <th style="width:15%"><?= lang_line('pr_th_qty_requested', 'Qty Diminta'); ?></th>
                            <th style="width:15%"><?= lang_line('pr_th_qty_issued', 'Qty Dikeluarkan'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($details)): foreach($details as $d): ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($d->wo_number); ?></td>
                            <td><?= htmlspecialchars($d->item_code); ?></td>
                            <td><?= htmlspecialchars($d->item_name); ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                            <td class="text-right"><?= number_format($d->requested_qty, 4); ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($d->issued_qty ?? 0, 4); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-5"><?= lang_line('pr_no_mr_items', 'Item MR tidak ditemukan.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('templates/footer'); ?>
