<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h5 text-gray-800">
            <i class="fas fa-sign-in-alt text-info mr-2"></i> Detail Pemasukan Barang Jadi
        </h1>
        <a href="<?= base_url('LapMutasiFinished') ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>

    <?php if (!empty($details)): ?>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>No</th>
                            <th>Nomor WO</th>
                            <th>Sales Contract</th>
                            <th>Tgl Produksi</th>
                            <th>BC Type</th>
                            <th>BC Number</th>
                            <th>Material Code</th>
                            <th>Material Name</th>
                            <th>Qty Issued</th>
                            <th>Qty Used</th>
                            <th>Qty FG In</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no=1; foreach($details as $d): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= $d->production_number ?></td>
                            <td><?= $d->sales_contract_no ?></td>
                            <td class="text-center"><?= $d->production_date ?></td>
                            <td class="text-center"><?= $d->bc_type ?></td>
                            <td class="text-center"><?= $d->bc_number ?></td>
                            <td><?= $d->material_code ?></td>
                            <td><?= $d->material_name ?></td>
                            <td class="text-right"><?= number_format($d->qty_issued, 2) ?></td>
                            <td class="text-right"><?= number_format($d->qty_used, 2) ?></td>
                            <td class="text-right"><?= number_format($d->qty_in, 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                <a href="#" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
                <a href="#" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> Ekspor PDF</a>
            </div>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-warning">Tidak ada data pemasukan untuk barang ini.</div>
    <?php endif; ?>

</div>
