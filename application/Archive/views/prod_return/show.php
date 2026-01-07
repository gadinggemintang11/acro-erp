<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-eye text-info mr-2"></i><?= htmlspecialchars($title); ?>: <?= htmlspecialchars($return_header->return_number); ?></h3>
        <div>
             <?php
                $can_edit = check_access('material_return', 'edit');
                $can_delete = check_access('material_return', 'delete');
            ?>

            <?php if ($return_header->status == 'Draft'): ?>
                <?php if ($can_edit): // Asumsi 'edit' bisa konfirm ?>
                <a href="<?= base_url('material_return/confirm/' . $return_header->id); ?>" class="btn btn-success btn-sm btn-icon-split mr-2" onclick="return confirm('Konfirmasi retur ini? Stok WIP akan berkurang.')">
                    <span class="icon text-white-50"><i class="fas fa-check"></i></span>
                    <span class="text">Confirm</span>
                </a>
                 <?php endif; ?>
                 <?php if ($can_delete): ?>
                 <a href="<?= base_url('material_return/delete/' . $return_header->id); ?>" class="btn btn-danger btn-sm btn-icon-split btn-delete mr-2" data-message="Yakin hapus Dokumen Draft ini?">
                    <span class="icon text-white-50"><i class="fas fa-trash"></i></span>
                    <span class="text">Hapus</span>
                </a>
                 <?php endif; ?>
            <?php elseif ($return_header->status == 'Confirmed'): ?>
                 <?php if ($can_edit): // Asumsi 'edit' bisa un-confirm ?>
                 <a href="<?= base_url('material_return/unconfirm/' . $return_header->id); ?>" class="btn btn-warning btn-sm btn-icon-split mr-2" onclick="return confirm('Batalkan konfirmasi ini? Stok WIP akan dikembalikan.')">
                    <span class="icon text-white-50"><i class="fas fa-undo"></i></span>
                    <span class="text">Un-Confirm</span>
                </a>
                 <?php endif; ?>
             <?php elseif ($return_header->status == 'Cancelled'): ?>
                <span class="badge badge-danger p-2">Document Cancelled</span>
             <?php endif; ?>

             <a href="<?= base_url('material_return/export_pdf/' . $return_header->id); ?>" target="_blank" class="btn btn-secondary btn-sm btn-icon-split mr-2">
                 <span class="icon text-white-50"><i class="fas fa-file-pdf"></i></span>
                 <span class="text">PDF</span>
             </a>
             <a href="<?= base_url('material_return'); ?>" class="btn btn-secondary btn-sm btn-icon-split">
                 <span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
                 <span class="text">Kembali</span>
             </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning">Informasi Retur</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                     <table class="table table-sm table-borderless">
                         <tr><td style="width: 150px;"><strong>No. Retur</strong></td><td>: <?= htmlspecialchars($return_header->return_number); ?></td></tr>
                         <tr><td><strong>Tgl Retur</strong></td><td>: <?= date('d M Y', strtotime($return_header->return_date)); ?></td></tr>
                         <tr><td><strong>No. Produksi</strong></td><td>: <?= htmlspecialchars($return_header->production_number ?: '-'); ?></td></tr>
                     </table>
                </div>
                 <div class="col-md-6">
                     <table class="table table-sm table-borderless">
                         <tr><td style="width: 150px;"><strong>Status</strong></td>
                             <td>: <?php $badge_class = 'secondary'; if ($return_header->status == 'Draft') $badge_class = 'warning'; if ($return_header->status == 'Confirmed') $badge_class = 'success'; if ($return_header->status == 'Cancelled') $badge_class = 'danger'; ?>
                                 <span class="badge badge-<?= $badge_class; ?>"><?= htmlspecialchars($return_header->status); ?></span>
                             </td>
                         </tr>
                         <tr><td><strong>Diretur Oleh</strong></td><td>: <?= htmlspecialchars($return_header->returner_name ?: '-'); ?></td></tr>
                         <tr><td><strong>Diterima Gudang</strong></td><td>: <?= htmlspecialchars($return_header->receiver_name ?: '-'); ?></td></tr>
                         <tr><td><strong>Tgl Konfirmasi</strong></td><td>: <?= $return_header->confirmed_at ? date('d M Y H:i', strtotime($return_header->confirmed_at)) : '-'; ?></td></tr>
                     </table>
                 </div>
                 <div class="col-md-12"><strong>Remarks:</strong><p><?= nl2br(htmlspecialchars($return_header->remarks ?: '-')); ?></p></div>
            </div>
        </div>
    </div>

     <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-warning">Item yang Diretur</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" width="100%">
                        <thead class="thead-light text-center">
                            <tr>
                                <th>No</th>
                                <th>Kode Item</th>
                                <th>Nama Item</th>
                                <th>Tipe</th>
                                <th>Batch (Ref. MR)</th>
                                <th>Qty Diretur</th>
                                <th>Unit</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($return_details)): $no = 1; foreach($return_details as $detail): ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= htmlspecialchars($detail->item_code); ?></td>
                                <td><?= htmlspecialchars($detail->item_name); ?></td>
                                <td class="text-center"><?= ucfirst(htmlspecialchars($detail->item_type)); ?></td>
                                <td class="text-center"><?= htmlspecialchars($detail->batch_number ?: '-'); ?></td>
                                <td class="text-right"><?= number_format($detail->qty_returned, 4); ?></td>
                                <td class="text-center"><?= htmlspecialchars($detail->uom); ?></td>
                                <td><?= htmlspecialchars($detail->notes); ?></td>
                            </tr>
                             <?php endforeach; else: ?>
                            <tr><td colspan="8" class="text-center text-muted">Tidak ada item detail.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
     </div>

</div>
<?php $this->load->view('templates/footer'); ?>