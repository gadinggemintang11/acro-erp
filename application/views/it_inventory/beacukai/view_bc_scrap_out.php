<?php
/**
 * View: Detail Dokumen BC - Scrap Keluar
 * Menampilkan detail BC dari pengeluaran scrap
 */
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12">
            <!-- Header Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark py-3">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="m-0">
                                <i class="fas fa-file-invoice"></i> Dokumen BC - Scrap Keluar
                            </h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-light btn-sm" onclick="window.print()">
                                <i class="fas fa-print"></i> Cetak
                            </button>
                            <a href="<?= isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : base_url(); ?>" class="btn btn-light btn-sm">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="row mb-2">
                                <div class="col-sm-5"><strong>Nomor BC:</strong></div>
                                <div class="col-sm-7">
                                    <span class="badge badge-lg bg-warning">
                                        <?= htmlspecialchars($doc['bc_number'] ?? '-'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-5"><strong>Tanggal BC:</strong></div>
                                <div class="col-sm-7">
                                    <?= !empty($doc['bc_date']) ? date('d M Y', strtotime($doc['bc_date'])) : '-'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row mb-2">
                                <div class="col-sm-5"><strong>Jenis BC:</strong></div>
                                <div class="col-sm-7">
                                    <span class="badge bg-info">
                                        <?= htmlspecialchars($doc['bc_type_code'] ?? '-'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-sm-5"><strong>Tujuan:</strong></div>
                                <div class="col-sm-7"><?= htmlspecialchars($doc['tujuan'] ?? '-'); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-6">
                            <div class="row mb-2">
                                <div class="col-sm-5"><strong>Dibuat Oleh:</strong></div>
                                <div class="col-sm-7"><?= htmlspecialchars($doc['created_by'] ?? '-'); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row mb-2">
                                <div class="col-sm-5"><strong>Tanggal Dibuat:</strong></div>
                                <div class="col-sm-7">
                                    <?= !empty($doc['created_at']) ? date('d M Y H:i', strtotime($doc['created_at'])) : '-'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail Scrap Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white py-3">
                    <h6 class="m-0">
                        <i class="fas fa-list"></i> Detail Scrap yang Dikeluarkan
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($doc['details']) && is_array($doc['details'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" width="5%">No</th>
                                        <th width="15%">Kode Scrap</th>
                                        <th width="50%">Nama Scrap</th>
                                        <th width="15%">Qty</th>
                                        <th width="15%">Satuan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php foreach ($doc['details'] as $item): ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td><strong><?= htmlspecialchars($item['item_code'] ?? '-'); ?></strong></td>
                                            <td><?= htmlspecialchars($item['item_name'] ?? '-'); ?></td>
                                            <td class="text-right"><?= number_format($item['qty'] ?? 0, 2); ?></td>
                                            <td class="text-center"><?= htmlspecialchars($item['unit'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning m-0">
                            <i class="fas fa-info-circle"></i> Tidak ada detail scrap untuk dokumen BC ini.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info Box -->
            <div class="alert alert-info mt-4" role="alert">
                <i class="fas fa-circle-info"></i>
                <strong>Informasi:</strong> Halaman ini menampilkan detail scrap yang dikeluarkan dengan BC ini. 
                Data diambil dari transaksi pengeluaran scrap dengan dokumen BC.
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        .btn, .alert {
            display: none;
        }
    }
</style>