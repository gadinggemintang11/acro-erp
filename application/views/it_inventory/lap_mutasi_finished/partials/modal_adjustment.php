<?php if (isset($detail) && $detail): ?>
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-edit"></i> Detail Penyesuaian</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th style="width: 35%;">No. Dokumen</th>
                        <td><strong><?= htmlspecialchars($detail->doc_no ?? '-'); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Tanggal Dokumen</th>
                        <td><?= $detail->doc_date ? date('d M Y', strtotime($detail->doc_date)) : '-'; ?></td>
                    </tr>
                    <tr>
                        <th>Dibuat Oleh</th>
                        <td><?= htmlspecialchars($detail->created_by_user ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Catatan</th>
                        <td><?= htmlspecialchars($detail->notes ?? 'Tidak ada catatan'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-info-circle"></i> Informasi Penyesuaian</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info" style="font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i> <strong>Penyesuaian Stok:</strong><br>
                    Transaksi ini merupakan penyesuaian stok yang dilakukan untuk mengoreksi saldo barang jadi berdasarkan hasil stock opname atau koreksi sistem.
                </div>
                
                <div class="text-center">
                    <a href="<?= base_url('AdjustmentHeader/detail/' . $detail->id); ?>" 
                       class="btn btn-primary btn-sm" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Lihat Detail Lengkap
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning">Data detail penyesuaian tidak ditemukan.</div>
<?php endif; ?>