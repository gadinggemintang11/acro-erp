<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h3 class="h4 mb-0 text-gray-800">
            <i class="fas fa-box-open text-primary mr-2"></i>Detail Pengeluaran Barang Habis Pakai
            <span class="badge badge-primary ml-2"><?= htmlspecialchars($header->doc_number); ?></span>
        </h3>
        <div>
            <a href="<?= base_url('consumable_out/export_pdf/' . $header->id); ?>"
               class="btn btn-info btn-sm mr-1" target="_blank">
                <i class="fas fa-file-pdf fa-sm mr-1"></i> Export PDF
            </a>
            <?php if (check_access('consumable_out', 'delete')): ?>
            <a href="<?= base_url('consumable_out/delete/' . $header->id); ?>"
               class="btn btn-danger btn-sm mr-1"
               onclick="return confirm('Hapus dokumen ini?');">
                <i class="fas fa-trash fa-sm mr-1"></i> Hapus
            </a>
            <?php endif; ?>
            <a href="<?= base_url('consumable_out'); ?>" class="btn btn-secondary btn-sm btn-load">
                <i class="fas fa-arrow-left fa-sm mr-1"></i> Kembali
            </a>
        </div>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= $this->session->flashdata('success'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= $this->session->flashdata('error'); ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
    <?php endif; ?>

    <!-- Header Info -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>No. Dokumen:</strong><br>
                    <span class="text-primary font-weight-bold"><?= htmlspecialchars($header->doc_number); ?></span>
                </div>
                <div class="col-md-3">
                    <strong>Tanggal:</strong><br>
                    <?= date('d M Y', strtotime($header->issuance_date)); ?>
                </div>
                <div class="col-md-3">
                    <strong>Status:</strong><br>
                    <?php
                        $badge = 'secondary';
                        if ($header->status == 'Issued')    $badge = 'success';
                        if ($header->status == 'Cancelled') $badge = 'danger';
                        if ($header->status == 'Draft')     $badge = 'warning';
                    ?>
                    <span class="badge badge-<?= $badge; ?>"><?= $header->status; ?></span>
                </div>
                <div class="col-md-3">
                    <strong>Dibuat Oleh:</strong><br>
                    <?= htmlspecialchars($header->created_by_name ?? '-'); ?>
                    <?php if ($header->created_at): ?>
                        <br><small class="text-muted"><?= date('d M Y H:i', strtotime($header->created_at)); ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <hr class="my-3">
            <div class="row">
                <div class="col-md-4">
                    <strong>Departemen:</strong>
                    <?= htmlspecialchars($header->department ?? '-'); ?>
                </div>
                <div class="col-md-4">
                    <strong>Penerima:</strong>
                    <?= htmlspecialchars($header->recipient ?? '-'); ?>
                </div>
                <div class="col-md-4">
                    <strong>Disiapkan Oleh:</strong>
                    <?= htmlspecialchars($header->issued_by_name ?? '-'); ?>
                </div>
            </div>
            <?php if (!empty($header->purpose)): ?>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>Keperluan:</strong>
                    <?= htmlspecialchars($header->purpose); ?>
                </div>
                <div class="col-md-6">
                    <strong>Catatan:</strong>
                    <?= htmlspecialchars($header->notes ?? '-'); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Rincian Barang yang Dikeluarkan</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No.</th>
                            <th>Kode Barang</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th>Qty Dikeluarkan</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($details)): $no = 1; foreach ($details as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++; ?></td>
                            <td><?= htmlspecialchars($d->item_code ?? '-'); ?></td>
                            <td><?= htmlspecialchars($d->item_name ?? '-'); ?></td>
                            <td class="text-center"><?= htmlspecialchars($d->item_unit ?? '-'); ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($d->qty_issued, 4); ?></td>
                            <td><?= htmlspecialchars($d->notes ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada item.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
