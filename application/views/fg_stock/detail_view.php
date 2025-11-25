<div class="container-fluid">

    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-boxes text-primary me-2"></i>
            <?= htmlspecialchars($title); ?>
        </h3>
    </div>

    <!-- Filter Form -->
    <form method="get" action="<?= base_url('fg_stock/detail/' . $fg_info->id); ?>" class="mb-3">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="start_date" class="form-control form-control-sm" 
                       value="<?= htmlspecialchars($filters['start_date']); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="end_date" class="form-control form-control-sm" 
                       value="<?= htmlspecialchars($filters['end_date']); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Cari Dokumen</label>
                <input type="text" name="search" class="form-control form-control-sm" 
                       placeholder="Cari nomor dokumen..." value="<?= htmlspecialchars($filters['search']); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm me-2 w-100">
                    <i class="fas fa-search"></i> Filter
                </button>
                <a href="<?= base_url('fg_stock/detail/' . $fg_info->id); ?>" class="btn btn-secondary btn-sm w-100">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </div>
        </div>
    </form>

    <!-- Info Barang -->
    <div class="card mb-3 shadow-sm">
        <div class="card-body py-3">
            <div class="row">
                <div class="col-md-4">
                    <h6 class="text-muted mb-1">Kode Barang</h6>
                    <strong><?= htmlspecialchars($fg_info->kode_barang); ?></strong>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted mb-1">Nama Barang</h6>
                    <strong><?= htmlspecialchars($fg_info->nama_barang); ?></strong>
                </div>
                <div class="col-md-4 text-end">
                    <a href="<?= base_url('fg_stock/export_pdf?fg_id=' . $fg_info->id . '&start_date=' . $filters['start_date'] . '&end_date=' . $filters['end_date']); ?>" 
                       class="btn btn-danger btn-sm" target="_blank">
                        <i class="fas fa-file-pdf me-1"></i> Export PDF
                    </a>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-3">
                    <h6 class="text-muted mb-1">Satuan</h6>
                    <span><?= htmlspecialchars($fg_info->satuan); ?></span>
                </div>
                <div class="col-md-3">
                    <h6 class="text-muted mb-1">Saldo Awal</h6>
                    <span class="fw-bold"><?= number_format($opening_balance, 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Ledger Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" style="font-size: 0.9rem;">
                    <thead class="table-light">
                        <tr>
                            <th width="40" class="text-center">No</th>
                            <th width="120">Tanggal</th>
                            <th width="130">Tipe Transaksi</th>
                            <th width="160">Nomor Dokumen</th>
                            <th width="100">Qty In</th>
                            <th width="100">Qty Out</th>
                            <th width="100">Saldo</th>
                            <th width="90" class="text-center">Trace</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ledger_data)): $no = 1; ?>
                            <?php foreach ($ledger_data as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++; ?></td>
                                <td><?= htmlspecialchars(date('Y-m-d', strtotime($row->transaction_date))); ?></td>
                                <td>
                                    <span class="badge <?= ($row->qty_in > 0) ? 'bg-success' : 'bg-danger'; ?>">
                                        <?= strtoupper(htmlspecialchars($row->transaction_type)); ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row->receipt_number ?? $row->delivery_number ?? '-'); ?></td>
                                <td class="text-end"><?= number_format($row->qty_in, 2); ?></td>
                                <td class="text-end"><?= number_format($row->qty_out, 2); ?></td>
                                <td class="text-end fw-bold"><?= number_format($row->balance, 2); ?></td>
                                <td class="text-center">
                                    <?php if (!empty($row->source_doc_type) && !empty($row->source_doc_id)): ?>
                                    <button class="btn btn-outline-info btn-sm btn-trace" 
                                            data-doc-type="<?= htmlspecialchars($row->source_doc_type); ?>"
                                            data-doc-id="<?= htmlspecialchars($row->source_doc_id); ?>"
                                            data-trans-id="<?= htmlspecialchars($row->transaction_id); ?>"
                                            title="Lihat Trace Dokumen">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center text-muted py-3">Tidak ada transaksi ditemukan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Container -->
<div class="modal fade" id="traceModal" tabindex="-1" aria-labelledby="traceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content"></div>
    </div>
</div>

<!-- Script -->
<script>
$(document).ready(function() {

    // Ketika tombol trace diklik
    $(document).on('click', '.btn-trace', function() {
        const docType = $(this).data('doc-type');
        const docId   = $(this).data('doc-id');
        const transId = $(this).data('trans-id');

        // Loading
        $('#traceModal .modal-content').html(`
            <div class="text-center p-5">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <p class="text-muted mb-0">Memuat detail dokumen...</p>
            </div>
        `);
        const modalEl = document.getElementById('traceModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        // Panggil AJAX
        $.ajax({
            url: "<?= base_url('fg_stock/ajax_get_traceability_info/'); ?>" + docType + "/" + docId + "/" + transId,
            type: "GET",
            dataType: "json",
            success: function(res) {
                if (res.status === 'success') {
                    $('#traceModal .modal-content').html(res.html);
                } else {
                    $('#traceModal .modal-content').html(`
                        <div class="modal-header">
                            <h5 class="modal-title text-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i> Gagal
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center py-5">
                            <p>${res.message || 'Detail tidak ditemukan.'}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    `);
                }
            },
            error: function() {
                $('#traceModal .modal-content').html(`
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="fas fa-bug me-2"></i> Koneksi Gagal
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-5">
                        <p>Tidak dapat menghubungi server.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    </div>
                `);
            }
        });
    });

    // Bersihkan isi modal saat ditutup
    $(document).on('hidden.bs.modal', '#traceModal', function() {
        $('#traceModal .modal-content').html('');
    });
});
</script>
