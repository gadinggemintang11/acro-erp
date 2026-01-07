<!-- application/views/fg_receipt/show.php -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-box-open text-primary mr-2"></i>
            Detail Penerimaan Barang Jadi (FG)
        </h3>

        <div>
            <?php if ($receipt_header->status == 'Draft'): ?>
                <a href="<?= base_url('fg_receipt/edit/' . $receipt_header->id); ?>" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="<?= base_url('fg_receipt/confirm/' . $receipt_header->id); ?>" class="btn btn-sm btn-success"
                   onclick="return confirm('Yakin konfirmasi penerimaan ini?')">
                    <i class="fas fa-check-circle"></i> Konfirmasi
                </a>
                <a href="<?= base_url('fg_receipt/destroy/' . $receipt_header->id); ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('Yakin hapus dokumen ini?')">
                    <i class="fas fa-trash"></i> Hapus
                </a>
            <?php elseif ($receipt_header->status == 'Confirmed'): ?>
                <a href="<?= base_url('fg_receipt/unconfirm/' . $receipt_header->id); ?>" class="btn btn-sm btn-warning"
                   onclick="return confirm('Batalkan konfirmasi dan kurangi stok?')">
                    <i class="fas fa-undo"></i> Unconfirm
                </a>
                <a href="<?= base_url('fg_receipt/destroy/' . $receipt_header->id); ?>" class="btn btn-sm btn-danger"
                   onclick="return confirm('Yakin batalkan dokumen ini (stok dikembalikan)?')">
                    <i class="fas fa-ban"></i> Cancel
                </a>
            <?php endif; ?>

            <a href="<?= base_url('fg_receipt/export_pdf/' . $receipt_header->id); ?>" class="btn btn-sm btn-info">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
        </div>
    </div>

    <!-- Header Card -->
    <div class="card mb-4 shadow">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-file-alt"></i> Informasi Dokumen</h6>
        </div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-3"><strong>No. Penerimaan</strong></div>
                <div class="col-md-3"><?= htmlspecialchars($receipt_header->receipt_number); ?></div>
                <div class="col-md-3"><strong>Tanggal Terima</strong></div>
                <div class="col-md-3"><?= date('d-m-Y', strtotime($receipt_header->receipt_date)); ?></div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3"><strong>Work Order</strong></div>
                <div class="col-md-3"><?= htmlspecialchars($receipt_header->wo_number); ?></div>
                <div class="col-md-3"><strong>Production No.</strong></div>
                <div class="col-md-3"><?= htmlspecialchars($receipt_header->production_number); ?></div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3"><strong>Status</strong></div>
                <div class="col-md-3">
                    <span class="badge 
                        <?= $receipt_header->status == 'Confirmed' ? 'badge-success' : 
                            ($receipt_header->status == 'Cancelled' ? 'badge-danger' : 'badge-secondary'); ?>">
                        <?= $receipt_header->status; ?>
                    </span>
                </div>
                <div class="col-md-3"><strong>Diterima Oleh</strong></div>
                <div class="col-md-3"><?= htmlspecialchars($receipt_header->receiver_name ?? '-'); ?></div>
            </div>

            <div class="row mb-2">
                <div class="col-md-3"><strong>Dikonfirmasi Oleh</strong></div>
                <div class="col-md-3"><?= htmlspecialchars($receipt_header->confirmer_name ?? '-'); ?></div>
                <div class="col-md-3"><strong>Keterangan</strong></div>
                <div class="col-md-3"><?= nl2br(htmlspecialchars($receipt_header->remarks)); ?></div>
            </div>
        </div>
    </div>

    <!-- Detail Tabel -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white py-3">
            <h6 class="m-0 font-weight-bold"><i class="fas fa-list"></i> Detail Item Penerimaan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th width="5%">No</th>
                            <th width="15%">Kode Barang</th>
                            <th>Nama Barang</th>
                            <th width="10%">Qty Diterima</th>
                            <th width="10%">Satuan</th>
                            <th width="15%">QC</th>
                            <th width="20%">Keterangan</th>
                            <th width="8%">Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($receipt_details)): ?>
                            <?php $no = 1; foreach ($receipt_details as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row->fg_code); ?></td>
                                    <td><?= htmlspecialchars($row->fg_name); ?></td>
                                    <td class="text-right"><?= number_format($row->qty_received, 2); ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row->fg_uom); ?></td>
                                    <td><?= htmlspecialchars($row->qc_name ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($row->notes ?? '-'); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row->prod_size_breakdown)): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-toggle="modal" 
                                                    data-target="#modalSize_<?= $row->id; ?>">
                                                <i class="fas fa-ruler"></i> View
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Modal Size Breakdown -->
                                <?php if (!empty($row->prod_size_breakdown)): ?>
                                    <?php $sizes = json_decode($row->prod_size_breakdown, true); ?>
                                    <div class="modal fade" id="modalSize_<?= $row->id; ?>" tabindex="-1" role="dialog" aria-labelledby="modalSizeLabel_<?= $row->id; ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h6 class="modal-title" id="modalSizeLabel_<?= $row->id; ?>">
                                                        Breakdown Size: <?= htmlspecialchars($row->fg_name); ?>
                                                    </h6>
                                                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <?php if (!empty($sizes)): ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm table-bordered">
                                                                <thead class="thead-light text-center">
                                                                    <tr>
                                                                        <th>Ukuran</th>
                                                                        <th>Qty</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($sizes as $s): ?>
                                                                        <tr class="text-center">
                                                                            <td><?= htmlspecialchars($s['size']); ?></td>
                                                                            <td><?= number_format($s['qty'], 2); ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    <?php else: ?>
                                                        <p class="text-muted text-center mb-0">Tidak ada data ukuran.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                                                        <i class="fas fa-times"></i> Tutup
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted">Tidak ada data item penerimaan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
