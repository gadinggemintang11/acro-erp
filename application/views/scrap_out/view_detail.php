<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-eye text-info mr-2"></i>Detail Scrap Out: <?= htmlspecialchars($header->doc_no); ?>
        </h3>
        <div>
            <?php if ($header->status == 'Draft'): ?>
                <a href="<?= base_url('Scrap_Out/confirm/'.$header->id); ?>" class="btn btn-success btn-sm btn-confirm" data-message="Konfirmasi dokumen ini? Stok akan dikurangi dan nilai barang dicatat.">
                    <i class="fas fa-check mr-1"></i> Konfirmasi
                </a>
                <a href="<?= base_url('Scrap_Out/edit/'.$header->id); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit mr-1"></i> Edit Dokumen</a>
                <a href="<?= base_url('Scrap_Out/delete/'.$header->id); ?>" class="btn btn-danger btn-sm btn-delete-draft" data-message="Anda yakin ingin menghapus draft ini?">
                    <i class="fas fa-trash-alt mr-1"></i> Hapus Draft
                </a>
            <?php elseif ($header->status == 'Confirmed'): ?>
                <a href="<?= base_url('Scrap_Out/unconfirm/'.$header->id); ?>" class="btn btn-danger btn-sm btn-confirm" data-message="Batalkan konfirmasi dokumen ini? Stok akan dikembalikan.">
                    <i class="fas fa-times mr-1"></i> Batal Konfirmasi
                </a>
            <?php endif; ?>
            <a href="<?= base_url('Scrap_Out/export_pdf/'.$header->id); ?>" target="_blank" class="btn btn-secondary btn-sm">
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </a>
            <a href="<?= base_url('Scrap_Out'); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-danger">Header Dokumen</h6></div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><td width="140"><strong>No. Dokumen</strong></td><td><?= htmlspecialchars($header->doc_no); ?></td></tr>
                        <tr><td><strong>Tgl Dokumen</strong></td><td><?= date('d M Y', strtotime($header->doc_date)); ?></td></tr>
                        <tr><td><strong>Customer (Tujuan)</strong></td><td><?= htmlspecialchars($header->customer_name ?: '-'); ?></td></tr>
                        <tr><td><strong>Status</strong></td><td><span class="badge badge-<?= ($header->status == 'Confirmed') ? 'success':'warning'; ?>"><?= $header->status; ?></span></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-sm table-borderless">
                        <tr><td width="140"><strong>Tipe BC</strong></td><td><?= htmlspecialchars($header->bc_type_code); ?></td></tr>
                        <tr><td><strong>No. BC</strong></td><td><?= htmlspecialchars($header->bc_number); ?></td></tr>
                        <tr><td><strong>Tgl BC</strong></td><td><?= $header->bc_date ? date('d M Y', strtotime($header->bc_date)) : '-'; ?></td></tr>
                        <tr><td><strong>Catatan</strong></td><td><?= nl2br(htmlspecialchars($header->notes)); ?></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-danger">Detail Item Scrap & Nilai Jual</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>Kode Scrap</th>
                            <th>Nama Scrap</th>
                            <th width="100">Satuan</th>
                            <th width="150">Nilai Jual / Unit (IDR)</th> <th width="130">Qty Keluar</th>
                            <th width="150">Total Nilai Jual (IDR)</th> </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_nilai_jual = 0;
                        if(!empty($details)): 
                            foreach($details as $d): 
                                $total_nilai_jual += ($d->qty * $d->sales_value_per_unit);
                        ?>
                        <tr>
                            <td class="text-center"><?= htmlspecialchars($d->scrap_code); ?></td>
                            <td><?= htmlspecialchars($d->scrap_name); ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->satuan_desc); ?></td>
                            <td class="text-right"><?= number_format($d->sales_value_per_unit, 2); ?></td>
                            <td class="text-right"><?= number_format($d->qty, 4); ?></td>
                            <td class="text-right font-weight-bold text-success"><?= number_format($d->qty * $d->sales_value_per_unit, 2); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center text-muted">Tidak ada data detail.</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-right font-weight-bold bg-light">GRAND TOTAL NILAI JUAL</td>
                            <td class="text-right font-weight-bold bg-light text-success"><?= number_format($total_nilai_jual, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Konfirmasi (SweetAlert) ===
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-confirm, .btn-delete-draft');
        if (!btn) return;

        e.preventDefault();
        const url = btn.getAttribute('href');
        const message = btn.dataset.message;
        const confirmText = btn.classList.contains('btn-confirm') ? 'Ya, Konfirmasi!' : 'Ya, Hapus Draft!';

        Swal.fire({
            title: 'Anda yakin?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: (btn.classList.contains('btn-danger') || btn.classList.contains('btn-delete-draft')) ? '#d33' : '#28a745',
            cancelButtonColor: '#3085d6',
            confirmButtonText: confirmText,
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // Inisialisasi Select2
    $('.select2').select2();
});
</script>