<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-circle"></i> EXIM Verification - Pengeluaran Barang
        </h1>
        <a href="<?= site_url('exim_verification'); ?>" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Flash Messages -->
    <?php if ($this->session->flashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('success'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata('error'); ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Pencarian</h6>
        </div>
        <div class="card-body">
            <form method="get" action="<?= site_url('exim_verification/outgoing'); ?>">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Dari</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $filters['date_from'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Sampai</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $filters['date_to'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipe Dokumen</label>
                            <select name="doc_type" class="form-control">
                                <option value="">-- Semua Tipe --</option>
                                <option value="FG_OUT" <?= ($filters['doc_type'] == 'FG_OUT' ? 'selected' : ''); ?>>FG Out</option>
                                <option value="SCRAP_OUT" <?= ($filters['doc_type'] == 'SCRAP_OUT' ? 'selected' : ''); ?>>Scrap Out</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>BC Type</label>
                            <select name="bc_type" class="form-control">
                                <option value="">-- Semua BC Type --</option>
                                <?php foreach($bc_types as $bc): ?>
                                    <option value="<?= $bc->code; ?>" <?= ($filters['bc_type'] == $bc->code ? 'selected' : ''); ?>>
                                        <?= htmlspecialchars($bc->description); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status Verifikasi</label>
                            <select name="verified_status" class="form-control">
                                <option value="unverified" <?= ($filters['verified_status'] == 'unverified' ? 'selected' : ''); ?>>Belum Diverifikasi</option>
                                <option value="verified" <?= ($filters['verified_status'] == 'verified' ? 'selected' : ''); ?>>Sudah Diverifikasi</option>
                                <option value="all" <?= ($filters['verified_status'] == 'all' ? 'selected' : ''); ?>>Semua</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label>&nbsp;</label><br>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <a href="<?= site_url('exim_verification/outgoing'); ?>" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">List Pengeluaran Barang</h6>
        </div>
        <div class="card-body">
            <?php if (empty($outgoing_list)): ?>
                <p class="text-center text-muted">Tidak ada data pengeluaran barang.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Tipe</th>
                                <th>Nomor Dokumen</th>
                                <th>Tanggal</th>
                                <th>Customer/Tujuan</th>
                                <th>BC Type</th>
                                <th>BC Number</th>
                                <th>BC Date</th>
                                <th class="text-right">Jumlah Item</th>
                                <th class="text-right">Total Qty</th>
                                <th>Status</th>
                                <th>Status Verifikasi</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($outgoing_list as $doc): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?= ($doc->doc_type == 'FG_OUT' ? 'info' : 'secondary'); ?>">
                                        <?= $doc->doc_type == 'FG_OUT' ? 'FG Out' : 'Scrap Out'; ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars($doc->doc_number); ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($doc->doc_date)); ?></td>
                                <td><?= htmlspecialchars($doc->customer_name ?? '-'); ?></td>
                                <td><?= htmlspecialchars($doc->bc_type_id ?? '-'); ?></td>
                                <td><?= htmlspecialchars($doc->bc_number ?? '-'); ?></td>
                                <td><?= $doc->bc_date ? date('d/m/Y', strtotime($doc->bc_date)) : '-'; ?></td>
                                <td class="text-right"><?= number_format($doc->num_items, 0); ?></td>
                                <td class="text-right"><?= number_format($doc->total_qty, 2); ?></td>
                                <td>
                                    <span class="badge badge-success"><?= $doc->status; ?></span>
                                </td>
                                <td>
                                    <?php if ($doc->exim_verified): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Terverifikasi
                                        </span><br>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($doc->exim_verified_at)); ?><br>
                                            oleh: <?= htmlspecialchars($doc->verified_by_name ?? '-'); ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> Belum Diverifikasi
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($doc->exim_verified): ?>
                                        <form method="post" action="<?= site_url('exim_verification/unverify_outgoing'); ?>" style="display:inline;" 
                                              onsubmit="return confirm('Yakin ingin membatalkan verifikasi dokumen ini?');">
                                            <input type="hidden" name="doc_type" value="<?= $doc->doc_type; ?>">
                                            <input type="hidden" name="doc_id" value="<?= $doc->id; ?>">
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <i class="fas fa-undo"></i> Batal
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= site_url('exim_verification/verify_outgoing'); ?>" style="display:inline;"
                                              onsubmit="return confirm('Yakin ingin memverifikasi dokumen ini?');">
                                            <input type="hidden" name="doc_type" value="<?= $doc->doc_type; ?>">
                                            <input type="hidden" name="doc_id" value="<?= $doc->id; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> Verifikasi
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
