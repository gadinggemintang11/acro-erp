<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-circle"></i> EXIM Verification Dashboard
        </h1>
    </div>

    <!-- Statistics Row -->
    <div class="row">
        <!-- Pemasukan Statistics -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-download"></i> Statistik Pemasukan Barang (GRN)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Belum Diverifikasi
                                </div>
                                <div class="h2 mb-0 font-weight-bold text-danger">
                                    <?= number_format($incoming_stats['unverified']); ?>
                                </div>
                                <small class="text-muted">
                                    <?= $incoming_stats['recent_unverified']; ?> baru (7 hari terakhir)
                                </small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Sudah Diverifikasi
                                </div>
                                <div class="h2 mb-0 font-weight-bold text-success">
                                    <?= number_format($incoming_stats['verified']); ?>
                                </div>
                                <small class="text-muted">
                                    <?= $incoming_stats['recent_verified']; ?> terverifikasi (7 hari terakhir)
                                </small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-6 text-center">
                            <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">
                                Total Dokumen
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($incoming_stats['total']); ?>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">
                                Tingkat Verifikasi
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-primary">
                                <?= $incoming_stats['verification_rate']; ?>%
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-center">
                        <a href="<?= site_url('exim_verification/incoming?verified_status=unverified'); ?>" class="btn btn-danger btn-sm mr-2">
                            <i class="fas fa-exclamation-circle"></i> Lihat Yang Belum Diverifikasi
                        </a>
                        <a href="<?= site_url('exim_verification/incoming?verified_status=all'); ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-list"></i> Lihat Semua
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pengeluaran Statistics -->
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">
                        <i class="fas fa-upload"></i> Statistik Pengeluaran Barang
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                    Belum Diverifikasi
                                </div>
                                <div class="h2 mb-0 font-weight-bold text-danger">
                                    <?= number_format($outgoing_stats['unverified']); ?>
                                </div>
                                <small class="text-muted">
                                    <?= $outgoing_stats['recent_unverified']; ?> baru (7 hari terakhir)
                                </small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Sudah Diverifikasi
                                </div>
                                <div class="h2 mb-0 font-weight-bold text-success">
                                    <?= number_format($outgoing_stats['verified']); ?>
                                </div>
                                <small class="text-muted">
                                    <?= $outgoing_stats['recent_verified']; ?> terverifikasi (7 hari terakhir)
                                </small>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-6 text-center">
                            <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">
                                Total Dokumen
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($outgoing_stats['total']); ?>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="text-xs font-weight-bold text-gray-600 text-uppercase mb-1">
                                Tingkat Verifikasi
                            </div>
                            <div class="h4 mb-0 font-weight-bold text-primary">
                                <?= $outgoing_stats['verification_rate']; ?>%
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-center">
                        <a href="<?= site_url('exim_verification/outgoing?verified_status=unverified'); ?>" class="btn btn-danger btn-sm mr-2">
                            <i class="fas fa-exclamation-circle"></i> Lihat Yang Belum Diverifikasi
                        </a>
                        <a href="<?= site_url('exim_verification/outgoing?verified_status=all'); ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-list"></i> Lihat Semua
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown by Type -->
    <div class="row">
        <div class="col-xl-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar"></i> Detail Pengeluaran Berdasarkan Tipe
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($outgoing_stats['by_type'] as $type => $stats): ?>
                            <?php 
                                $type_label = '';
                                $type_icon = '';
                                $type_color = '';
                                if($type == 'FG_OUT') {
                                    $type_label = 'FG Delivery';
                                    $type_icon = 'fa-truck-loading';
                                    $type_color = 'info';
                                } elseif($type == 'SCRAP_OUT') {
                                    $type_label = 'Scrap Out';
                                    $type_icon = 'fa-recycle';
                                    $type_color = 'secondary';
                                } elseif($type == 'SUBKON_OUT') {
                                    $type_label = 'Subkon Out';
                                    $type_icon = 'fa-industry';
                                    $type_color = 'primary';
                                }
                            ?>
                            <div class="col-xl-4 col-md-4 mb-3">
                                <div class="card border-left-<?= $type_color; ?> shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-<?= $type_color; ?> text-uppercase mb-1">
                                                    <?= $type_label; ?>
                                                </div>
                                                <div class="row no-gutters align-items-center mt-2">
                                                    <div class="col-auto">
                                                        <div class="h6 mb-0 mr-3 font-weight-bold text-gray-800">
                                                            <span class="text-danger"><?= $stats['unverified']; ?></span> / 
                                                            <span class="text-success"><?= $stats['verified']; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    Total: <?= $stats['total']; ?> dokumen
                                                </small>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas <?= $type_icon; ?> fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Verifikasi Pemasukan Barang
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                GRN / Penerimaan Barang
                            </div>
                            <div class="mt-3">
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-info-circle"></i> Verifikasi dokumen penerimaan barang dari supplier sebelum masuk ke laporan IT Inventory
                                </p>
                                <a href="<?= site_url('exim_verification/incoming'); ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-arrow-right"></i> Ke Verifikasi Pemasukan
                                </a>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-download fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Verifikasi Pengeluaran Barang
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                FG Out / Scrap Out / Subkon Out
                            </div>
                            <div class="mt-3">
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-info-circle"></i> Verifikasi dokumen pengeluaran barang sebelum masuk ke laporan IT Inventory
                                </p>
                                <a href="<?= site_url('exim_verification/outgoing'); ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-arrow-right"></i> Ke Verifikasi Pengeluaran
                                </a>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-upload fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Information Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informasi EXIM Verification</h6>
        </div>
        <div class="card-body">
            <h5><i class="fas fa-question-circle text-info"></i> Apa itu EXIM Verification?</h5>
            <p>
                EXIM Verification adalah proses verifikasi dokumen transaksi pemasukan dan pengeluaran barang 
                sebelum data tersebut muncul di laporan IT Inventory. Tujuannya adalah untuk memastikan:
            </p>
            <ul>
                <li>Data BC (Bea Cukai) lengkap dan valid</li>
                <li>Dokumen sudah sesuai dengan regulasi ekspor-impor</li>
                <li>Transaksi telah diotorisasi oleh pihak yang berwenang</li>
            </ul>

            <h5 class="mt-4"><i class="fas fa-tasks text-success"></i> Alur Proses</h5>
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-success">Pemasukan Barang:</h6>
                    <ol>
                        <li>Transaksi GRN dibuat dan di-post</li>
                        <li>Muncul di menu <strong>EXIM Verification - Pemasukan</strong></li>
                        <li>Petugas EXIM melakukan verifikasi</li>
                        <li>Setelah diverifikasi, data masuk ke <strong>Laporan Pemasukan Barang (IT Inventory)</strong></li>
                    </ol>
                </div>
                <div class="col-md-6">
                    <h6 class="text-warning">Pengeluaran Barang:</h6>
                    <ol>
                        <li>Transaksi FG Out / Scrap Out / Subkon Out dibuat dan di-post</li>
                        <li>Muncul di menu <strong>EXIM Verification - Pengeluaran</strong></li>
                        <li>Petugas EXIM melakukan verifikasi</li>
                        <li>Setelah diverifikasi, data masuk ke <strong>Laporan Pengeluaran Barang (IT Inventory)</strong></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
