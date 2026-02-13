<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <div class="btn-group">
            <a href="<?= base_url('appayment'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Kembali ke Daftar
            </a>
            <a href="<?= base_url('appayment/export_detail_excel/' . $header->id); ?>" class="btn btn-success btn-sm" target="_blank">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a href="<?= base_url('appayment/export_detail_pdf/' . $header->id); ?>" class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Pembayaran</h6>
                    <?php if($header->status == 'Draft'): ?>
                    <div>
                        <a href="<?= base_url('appayment/delete/' . $header->id); ?>" 
                           class="btn btn-danger btn-sm" 
                           onclick="return confirm('Anda yakin ingin menghapus pembayaran DRAFT ini?');">
                            <i class="fas fa-trash"></i> Hapus
                        </a>
                        <a href="<?= base_url('appayment/post/' . $header->id); ?>" 
                           class="btn btn-success btn-sm" 
                           onclick="return confirm('ANDA YAKIN INGIN POSTING PEMBAYARAN INI?\nJurnal otomatis akan dibuat dan status faktur akan diupdate.');">
                            <i class="fas fa-check-circle"></i> Posting Pembayaran
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong>No. Pembayaran:</strong><br><?= $header->payment_no; ?></div>
                        <div class="col-md-3"><strong>Tgl. Bayar:</strong><br><?= date('d M Y', strtotime($header->payment_date)); ?></div>
                        <div class="col-md-3"><strong>Supplier:</strong><br><?= $header->nama_supplier; ?></div>
                        <div class="col-md-3"><strong>Total Bayar:</strong><br>
                            <h5 class="font-weight-bold text-success"><?= number_format($header->total_amount, 2); ?></h5>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-3"><strong>Akun Pembayaran:</strong><br><?= $header->payment_account_name; ?> (<?= $header->account_code; ?>)</div>
                        <div class="col-md-3"><strong>Catatan:</strong><br><?= $header->notes; ?></div>
                        <div class="col-md-3"><strong>Status:</strong><br>
                             <span class="badge badge-<?= $header->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                <?= $header->status; ?>
                            </span>
                        </div>
                        <div class="col-md-3"><strong>No. Jurnal:</strong><br>
                            <?= $header->journal_header_id ? $journal_header->journal_no : 'N/A'; ?>
                        </div>
                    </div>
                    <hr>
                    <!-- ✨ NEW: Tampilkan header-level account mapping -->
                    <div class="row">
                        <div class="col-md-6">
                            <strong>📊 Akun DEBET (Hutang Berkurang):</strong><br>
                            <?php if($header->debet_account_id): ?>
                                <span class="badge badge-primary">ID: <?= $header->debet_account_id; ?></span>
                            <?php else: ?>
                                <span class="text-muted">Tidak dipilih (akan gunakan dari Setting)</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong>📊 Akun KREDIT (Kas/Bank Berkurang):</strong><br>
                            <?php if($header->kredit_account_id): ?>
                                <span class="badge badge-info">ID: <?= $header->kredit_account_id; ?></span>
                            <?php else: ?>
                                <span class="text-muted">Tidak dipilih (akan gunakan dari Setting)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- ✨ NEW: Tampilkan PPh Withholding Tax Information -->
                    <?php if($header->pph_rate > 0 || $header->pph_amount > 0): ?>
                    <hr>
                    <div class="row bg-light p-3 rounded">
                        <div class="col-md-3">
                            <strong>💰 Tarif PPh 23:</strong><br>
                            <h5 class="text-warning"><?= $header->pph_rate; ?>%</h5>
                        </div>
                        <div class="col-md-3">
                            <strong>💰 Jumlah PPh:</strong><br>
                            <h5 class="text-warning"><?= number_format($header->pph_amount, 2); ?></h5>
                        </div>
                        <div class="col-md-3">
                            <strong>💰 Akun Hutang PPh:</strong><br>
                            <?php if($header->pph_account_id): ?>
                                <span class="badge badge-warning">ID: <?= $header->pph_account_id; ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <strong>💰 Pembayaran Netto:</strong><br>
                            <h5 class="text-success"><?= number_format($header->total_amount - $header->pph_amount, 2); ?></h5>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Faktur yang Dibayar</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>No. Faktur</th>
                                    <th>Tgl. Faktur</th>
                                    <th class="text-right">Total Faktur</th>
                                    <th class="text-right">Jumlah Dibayar (Pembayaran Ini)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($details as $d): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('apinvoice/view/' . $d->ap_invoice_header_id); ?>" target="_blank">
                                            <?= $d->invoice_no; ?>
                                        </a>
                                    </td>
                                    <td><?= date('d M Y', strtotime($d->invoice_date)); ?></td>
                                    <td class="text-right"><?= number_format($d->total_invoice, 2); ?></td>
                                    <td class="text-right font-weight-bold"><?= number_format($d->amount_paid, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if($header->status == 'Posted' && isset($journal_details)): ?>
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Jurnal Entri (Posted) - <?= $journal_header->journal_no; ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Kode Akun</th>
                                    <th>Nama Akun</th>
                                    <th class="text-right">Debit (IDR)</th>
                                    <th class="text-right">Kredit (IDR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($journal_details as $jd): ?>
                                <tr>
                                    <td><?= $jd->account_code; ?></td>
                                    <td><?= $jd->account_name; ?></td>
                                    <td class="text-right"><?= $jd->debit_local > 0 ? number_format($jd->debit_local, 2) : '-'; ?></td>
                                    <td class="text-right"><?= $jd->kredit_local > 0 ? number_format($jd->kredit_local, 2) : '-'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
