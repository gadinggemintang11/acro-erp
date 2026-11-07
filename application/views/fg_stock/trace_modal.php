<div class="modal-header bg-light border-bottom">
    <h5 class="modal-title fw-semibold">
        <i class="fas fa-file-alt text-primary me-2"></i>
        <?= lang_line('fg_stock_tm_title', 'Detail Dokumen'); ?>: <?= htmlspecialchars($header['source_type'] ?? '-') . ' #' . htmlspecialchars($header['source_id'] ?? '-'); ?>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>

<div class="modal-body">

    <!-- Informasi Dokumen -->
    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <h6 class="text-muted mb-1"><?= lang_line('fg_stock_tm_doc_number', 'Nomor Dokumen'); ?></h6>
                    <strong><?= htmlspecialchars($header['source_number'] ?? '-'); ?></strong>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted mb-1"><?= lang_line('fg_stock_tm_doc_date', 'Tanggal Dokumen'); ?></h6>
                    <strong><?= !empty($header['source_date']) ? date('d M Y', strtotime($header['source_date'])) : '-'; ?></strong>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted mb-1"><?= lang_line('fg_stock_tm_doc_type', 'Tipe Dokumen'); ?></h6>
                    <span class="badge bg-primary"><?= htmlspecialchars($header['source_type'] ?? '-'); ?></span>
                </div>
                <div class="col-md-2 text-end">
                    <?php if (!empty($header['remarks'])): ?>
                        <button class="btn btn-sm btn-outline-secondary" type="button"
                                data-bs-toggle="collapse" data-bs-target="#remarksCollapse">
                            <i class="fas fa-comment-dots me-1"></i> <?= lang_line('fg_stock_tm_btn_notes', 'Catatan'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($header['remarks'])): ?>
            <div id="remarksCollapse" class="collapse mt-2">
                <div class="alert alert-secondary small mb-0">
                    <i class="fas fa-info-circle me-1"></i>
                    <?= nl2br(htmlspecialchars($header['remarks'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail Transaksi -->
    <div class="table-responsive shadow-sm">
        <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.9rem;">
            <thead class="table-light">
                <tr>
                    <th class="text-center" width="5%"><?= lang_line('fg_stock_tm_th_no', 'No'); ?></th>
                    <th width="12%"><?= lang_line('fg_stock_tm_th_date', 'Tanggal'); ?></th>
                    <th width="10%"><?= lang_line('fg_stock_tm_th_type', 'Tipe'); ?></th>
                    <th width="16%"><?= lang_line('fg_stock_tm_th_doc_no', 'Nomor Dokumen'); ?></th>
                    <th width="10%"><?= lang_line('fg_stock_tm_th_bc_type', 'BC Type'); ?></th>
                    <th width="12%"><?= lang_line('fg_stock_tm_th_bc_number', 'BC Number'); ?></th>
                    <th width="10%" class="text-end"><?= lang_line('fg_stock_tm_th_qty_in', 'Qty In'); ?></th>
                    <th width="10%" class="text-end"><?= lang_line('fg_stock_tm_th_qty_out', 'Qty Out'); ?></th>
                    <th width="8%"><?= lang_line('fg_stock_tm_th_unit', 'Satuan'); ?></th>
                    <th width="12%"><?= lang_line('fg_stock_tm_th_description', 'Keterangan'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): $no = 1; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars(date('Y-m-d', strtotime($row['transaction_date']))); ?></td>
                            <td>
                                <span class="badge <?= ($row['qty_in'] > 0 ? 'bg-success' : 'bg-danger'); ?>">
                                    <?= strtoupper(htmlspecialchars($row['transaction_type'])); ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($row['delivery_number'] ?? $row['receipt_number'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row['bc_type_desc'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row['bc_number'] ?? '-'); ?></td>
                            <td class="text-end"><?= number_format($row['qty_in'] ?? 0, 2); ?></td>
                            <td class="text-end"><?= number_format($row['qty_out'] ?? 0, 2); ?></td>
                            <td><?= htmlspecialchars($row['satuan'] ?? '-'); ?></td>
                            <td><?= htmlspecialchars($row['nama_barang'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10" class="text-center text-muted py-3"><?= lang_line('fg_stock_tm_no_trans', 'Tidak ada detail transaksi ditemukan.'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div class="modal-footer bg-light">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i class="fas fa-times me-1"></i> <?= lang_line('fg_stock_tm_close', 'Tutup'); ?>
    </button>
</div>
