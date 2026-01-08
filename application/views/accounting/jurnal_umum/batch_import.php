<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    
    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Upload File Batch Jurnal</h6>
                    <a href="<?= base_url('jurnalumum/download_batch_template'); ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                </div>
                <form action="<?= base_url('jurnalumum/process_batch_import'); ?>" method="POST" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6 class="font-weight-bold"><i class="fas fa-info-circle"></i> Cara Menggunakan Batch Import:</h6>
                            <ol class="mb-0 pl-3">
                                <li>Download template Excel dengan klik tombol <strong>"Download Template"</strong> di atas</li>
                                <li>Isi data jurnal Anda pada template tersebut</li>
                                <li><strong>Setiap transaksi baru</strong> ditandai dengan <strong>Reference baru</strong> di kolom pertama</li>
                                <li>Untuk baris detail berikutnya dalam transaksi yang sama, <strong>kosongkan kolom Reference</strong></li>
                                <li>Sistem akan otomatis membuat <strong>nomor jurnal berbeda</strong> untuk setiap transaksi</li>
                                <li>Upload file yang sudah diisi</li>
                            </ol>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Pilih File Excel (xls, xlsx, csv) *</label>
                            <input type="file" name="file_batch" class="form-control-file" required accept=".xls,.xlsx,.csv">
                            <small class="form-text text-muted">Maksimal ukuran file: 10MB</small>
                        </div>

                        <hr>

                        <h6 class="font-weight-bold text-primary mb-3">Format Template:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="width: 150px;">Reference</th>
                                        <th style="width: 120px;">Tanggal</th>
                                        <th>Keterangan Jurnal</th>
                                        <th style="width: 100px;">Kode Akun</th>
                                        <th style="width: 120px;">Debit</th>
                                        <th style="width: 120px;">Kredit</th>
                                        <th>Ket. Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="table-warning">
                                        <td><strong>GAJI-JAN</strong></td>
                                        <td>2026-01-31</td>
                                        <td>Pembayaran Gaji</td>
                                        <td>611000</td>
                                        <td class="text-right">50,000,000</td>
                                        <td class="text-right">0</td>
                                        <td>Beban gaji</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted"><em>(kosong)</em></td>
                                        <td>2026-01-31</td>
                                        <td class="text-muted"><em>(kosong)</em></td>
                                        <td>111000</td>
                                        <td class="text-right">0</td>
                                        <td class="text-right">50,000,000</td>
                                        <td>Pembayaran tunai</td>
                                    </tr>
                                    <tr class="table-success">
                                        <td><strong>PENJUALAN-001</strong></td>
                                        <td>2026-01-15</td>
                                        <td>Penjualan Tunai</td>
                                        <td>411000</td>
                                        <td class="text-right">0</td>
                                        <td class="text-right">10,000,000</td>
                                        <td>Pendapatan hari ini</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted"><em>(kosong)</em></td>
                                        <td>2026-01-15</td>
                                        <td class="text-muted"><em>(kosong)</em></td>
                                        <td>111000</td>
                                        <td class="text-right">10,000,000</td>
                                        <td class="text-right">0</td>
                                        <td>Terima tunai</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Penting:</h6>
                            <ul class="mb-0">
                                <li><strong>Reference TIDAK KOSONG = Transaksi Baru</strong> (tidak peduli di baris debit atau kredit)</li>
                                <li><strong>Reference KOSONG = Lanjutan detail</strong> dari transaksi sebelumnya</li>
                                <li>Setiap transaksi baru akan mendapat <strong>nomor jurnal otomatis berbeda</strong></li>
                                <li>Total Debit dan Kredit setiap transaksi <strong>harus seimbang</strong></li>
                                <li>Format tanggal: <code>YYYY-MM-DD</code> (contoh: 2026-01-31)</li>
                                <li>Kode akun harus sudah terdaftar di Chart of Accounts dan berstatus aktif</li>
                                <li>Semua jurnal akan dibuat dengan status <strong>Draft</strong></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="<?= base_url('jurnalumum'); ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Upload & Proses
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Contoh Hasil</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Dari template di samping akan menghasilkan:</strong></p>
                    
                    <div class="alert alert-light border">
                        <strong>Transaksi 1:</strong><br>
                        <small class="text-muted">No. Jurnal: JU/202601/0001</small><br>
                        <small class="text-muted">Tanggal: 31 Jan 2026</small><br>
                        <small>Pembayaran Gaji</small><br>
                        <small>Debit: Rp 50,000,000</small><br>
                        <small>Kredit: Rp 50,000,000</small>
                    </div>

                    <div class="alert alert-light border">
                        <strong>Transaksi 2:</strong><br>
                        <small class="text-muted">No. Jurnal: JU/202601/0002</small><br>
                        <small class="text-muted">Tanggal: 15 Jan 2026</small><br>
                        <small>Penjualan Tunai</small><br>
                        <small class="text-success">✓ Reference di KREDIT juga bisa!</small><br>
                        <small>Debit: Rp 10,000,000</small><br>
                        <small>Kredit: Rp 10,000,000</small>
                    </div>

                    <hr>
                    
                    <h6 class="font-weight-bold text-primary mt-3">Tips:</h6>
                    <ul class="small mb-0">
                        <li>Gunakan reference yang deskriptif</li>
                        <li>Reference bisa berupa: kode, kategori, atau nomor referensi</li>
                        <li>Satu transaksi bisa memiliki banyak baris detail</li>
                        <li>Pastikan setiap transaksi balance sebelum upload</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
