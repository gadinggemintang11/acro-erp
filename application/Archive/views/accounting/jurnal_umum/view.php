<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('jurnalumum'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali ke Daftar</a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Jurnal</h6>
                    <?php if($header->status == 'Draft'): ?>
                    <div>
                        <a href="<?= base_url('jurnalumum/delete/' . $header->id); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus dokumen DRAFT ini?');">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                        <a href="<?= base_url('jurnalumum/post/' . $header->id); ?>" class="btn btn-success btn-sm" onclick="return confirm('ANDA YAKIN INGIN POSTING JURNAL INI?\nJurnal yang sudah di-posting tidak bisa diubah atau dihapus.');">
                            <i class="fas fa-check-circle"></i> Posting Jurnal
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>No. Jurnal:</strong><br>
                            <?= $header->journal_no; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Tanggal:</strong><br>
                            <?= date('d M Y', strtotime($header->journal_date)); ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Status:</strong><br>
                            <?php
                                $badge = 'secondary';
                                if ($header->status == 'Draft') $badge = 'warning';
                                if ($header->status == 'Posted') $badge = 'success';
                                if ($header->status == 'Cancelled') $badge = 'danger';
                            ?>
                            <span class="badge badge-<?= $badge; ?> p-2"><?= $header->status; ?></span>
                        </div>
                        <div class="col-md-3">
                            <strong>Sumber:</strong><br>
                            <?= $header->source_module; ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Keterangan:</strong><br>
                            <?= $header->description; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Mata Uang:</strong><br>
                            <?= $header->currency; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>Kurs (Rate):</strong><br>
                            <?= number_format((float)$header->exchange_rate, 4); ?>
                        </div>
                    </div>
                     <?php if($header->status == 'Posted'): ?>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Dibuat Oleh:</strong><br>
                            <?= $header->creator_name; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Diposting Oleh:</strong><br>
                            <?= $header->poster_name; ?> pada <?= date('d M Y H:i', strtotime($header->posted_at)); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detail Jurnal</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th rowspan="2" class="align-middle">Kode Akun</th>
                                    <th rowspan="2" class="align-middle">Nama Akun</th>
                                    <th colspan="2" class="text-center">Transaksi (<?= $header->currency; ?>)</th>
                                    <th colspan="2" class="text-center">Lokal (IDR)</th>
                                    <th rowspan="2" class="align-middle">Deskripsi</th>
                                </tr>
                                <tr>
                                    <th class="text-right">Debit</th>
                                    <th class="text-right">Kredit</th>
                                    <th class="text-right">Debit (IDR)</th>
                                    <th class="text-right">Kredit (IDR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($details as $d): ?>
                                <tr>
                                    <td><?= $d->account_code; ?></td>
                                    <td><?= $d->account_name; ?></td>
                                    <td class="text-right"><?= $d->debit > 0 ? number_format($d->debit, 2) : '-'; ?></td>
                                    <td class="text-right"><?= $d->kredit > 0 ? number_format($d->kredit, 2) : '-'; ?></td>
                                    <td class="text-right bg-gray-100"><?= $d->debit_local > 0 ? number_format($d->debit_local, 2) : '-'; ?></td>
                                    <td class="text-right bg-gray-100"><?= $d->kredit_local > 0 ? number_format($d->kredit_local, 2) : '-'; ?></td>
                                    <td><?= $d->description; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-200">
                                <tr class="font-weight-bold">
                                    <td colspan="2" class="text-right">TOTAL (<?= $header->currency; ?>)</td>
                                    
                                    <td class="text-right"><?= number_format($header->total_debit, 2); ?></td>
                                    <td class="text-right"><?= number_format($header->total_kredit, 2); ?></td>
                                    <td class="text-right"><?= number_format($header->total_debit_local, 2); ?></td>
                                    <td class="text-right"><?= number_format($header->total_kredit_local, 2); ?></td>
                                    <td class="text-center">
                                        <?php if($header->total_debit == $header->total_kredit): ?>
                                            <span class="badge badge-success">BALANCE</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">UNBALANCE</span>
                                        <?php endif; ?>
                                    </td>
                                    </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>