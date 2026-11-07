<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ju_bi_upload_card', 'Upload File Batch Jurnal') ?></h6>
                    <a href="<?= base_url('jurnalumum/download_batch_template'); ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-download"></i> <?= lang_line('ju_bi_btn_download_template', 'Download Template') ?>
                    </a>
                </div>
                <form action="<?= base_url('jurnalumum/process_batch_import'); ?>" method="POST" enctype="multipart/form-data">
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6 class="font-weight-bold"><i class="fas fa-info-circle"></i> <?= lang_line('ju_bi_howto_title', 'Cara Menggunakan Batch Import:') ?></h6>
                            <ol class="mb-0 pl-3">
                                <li><?= lang_line('ju_bi_howto_1', 'Download template Excel dengan klik tombol <strong>"Download Template"</strong> di atas') ?></li>
                                <li><?= lang_line('ju_bi_howto_2', 'Isi data jurnal Anda pada template tersebut') ?></li>
                                <li><?= lang_line('ju_bi_howto_3', '<strong>Setiap transaksi baru</strong> ditandai dengan <strong>Reference baru</strong> di kolom pertama') ?></li>
                                <li><?= lang_line('ju_bi_howto_4', 'Untuk baris detail berikutnya dalam transaksi yang sama, <strong>kosongkan kolom Reference</strong>') ?></li>
                                <li><?= lang_line('ju_bi_howto_5', 'Sistem akan otomatis membuat <strong>nomor jurnal berbeda</strong> untuk setiap transaksi') ?></li>
                                <li><?= lang_line('ju_bi_howto_6', 'Upload file yang sudah diisi') ?></li>
                            </ol>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold"><?= lang_line('ju_bi_lbl_choose', 'Pilih File Excel (xls, xlsx, csv) *') ?></label>
                            <input type="file" name="file_batch" class="form-control-file" required accept=".xls,.xlsx,.csv">
                            <small class="form-text text-muted"><?= lang_line('ju_bi_max_size', 'Maksimal ukuran file: 10MB') ?></small>
                        </div>

                        <hr>

                        <h6 class="font-weight-bold text-primary mb-3"><?= lang_line('ju_bi_template_section', 'Format Template:') ?></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="width: 150px;"><?= lang_line('ju_bi_th_reference', 'Reference') ?></th>
                                        <th style="width: 120px;"><?= lang_line('ju_bi_th_date', 'Tanggal') ?></th>
                                        <th><?= lang_line('ju_bi_th_journal_desc', 'Keterangan Jurnal') ?></th>
                                        <th style="width: 100px;"><?= lang_line('ju_bi_th_account_code', 'Kode Akun') ?></th>
                                        <th style="width: 120px;"><?= lang_line('ju_bi_th_debit', 'Debit') ?></th>
                                        <th style="width: 120px;"><?= lang_line('ju_bi_th_kredit', 'Kredit') ?></th>
                                        <th><?= lang_line('ju_bi_th_detail_desc', 'Ket. Detail') ?></th>
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
                                        <td class="text-muted"><em><?= lang_line('ju_bi_empty', '(kosong)') ?></em></td>
                                        <td>2026-01-31</td>
                                        <td class="text-muted"><em><?= lang_line('ju_bi_empty', '(kosong)') ?></em></td>
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
                                        <td class="text-muted"><em><?= lang_line('ju_bi_empty', '(kosong)') ?></em></td>
                                        <td>2026-01-15</td>
                                        <td class="text-muted"><em><?= lang_line('ju_bi_empty', '(kosong)') ?></em></td>
                                        <td>111000</td>
                                        <td class="text-right">10,000,000</td>
                                        <td class="text-right">0</td>
                                        <td>Terima tunai</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <h6 class="font-weight-bold"><i class="fas fa-exclamation-triangle"></i> <?= lang_line('ju_bi_important_title', 'Penting:') ?></h6>
                            <ul class="mb-0">
                                <li><?= lang_line('ju_bi_rule_1', '<strong>Reference TIDAK KOSONG = Transaksi Baru</strong> (tidak peduli di baris debit atau kredit)') ?></li>
                                <li><?= lang_line('ju_bi_rule_2', '<strong>Reference KOSONG = Lanjutan detail</strong> dari transaksi sebelumnya') ?></li>
                                <li><?= lang_line('ju_bi_rule_3', 'Setiap transaksi baru akan mendapat <strong>nomor jurnal otomatis berbeda</strong>') ?></li>
                                <li><?= lang_line('ju_bi_rule_4', 'Total Debit dan Kredit setiap transaksi <strong>harus seimbang</strong>') ?></li>
                                <li><?= lang_line('ju_bi_rule_5', 'Format tanggal: <code>YYYY-MM-DD</code> (contoh: 2026-01-31)') ?></li>
                                <li><?= lang_line('ju_bi_rule_6', 'Kode akun harus sudah terdaftar di Chart of Accounts dan berstatus aktif') ?></li>
                                <li><?= lang_line('ju_bi_rule_7', 'Semua jurnal akan dibuat dengan status <strong>Draft</strong>') ?></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="<?= base_url('jurnalumum'); ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> <?= lang_line('ju_bi_btn_back', 'Kembali') ?>
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> <?= lang_line('ju_bi_btn_upload_process', 'Upload & Proses') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success"><?= lang_line('ju_bi_example_card', 'Contoh Hasil') ?></h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong><?= lang_line('ju_bi_example_intro', 'Dari template di samping akan menghasilkan:') ?></strong></p>

                    <div class="alert alert-light border">
                        <strong><?= lang_line('ju_bi_example_trx1', 'Transaksi 1:') ?></strong><br>
                        <small class="text-muted"><?= lang_line('ju_label_journal_no', 'No. Jurnal:') ?> JU/ACRO/26010001</small><br>
                        <small class="text-muted"><?= lang_line('ju_th_post_date', 'Tgl Posting') ?>: 31 Jan 2026</small><br>
                        <small>Pembayaran Gaji</small><br>
                        <small><?= lang_line('ju_th_debit', 'Debit') ?>: Rp 50,000,000</small><br>
                        <small><?= lang_line('ju_th_kredit', 'Kredit') ?>: Rp 50,000,000</small>
                    </div>

                    <div class="alert alert-light border">
                        <strong><?= lang_line('ju_bi_example_trx2', 'Transaksi 2:') ?></strong><br>
                        <small class="text-muted"><?= lang_line('ju_label_journal_no', 'No. Jurnal:') ?> JU/ACRO/26010002</small><br>
                        <small class="text-muted"><?= lang_line('ju_th_post_date', 'Tgl Posting') ?>: 15 Jan 2026</small><br>
                        <small>Penjualan Tunai</small><br>
                        <small class="text-success"><?= lang_line('ju_bi_example_ref_in_kredit_ok', '✓ Reference di KREDIT juga bisa!') ?></small><br>
                        <small><?= lang_line('ju_th_debit', 'Debit') ?>: Rp 10,000,000</small><br>
                        <small><?= lang_line('ju_th_kredit', 'Kredit') ?>: Rp 10,000,000</small>
                    </div>

                    <hr>

                    <h6 class="font-weight-bold text-primary mt-3"><?= lang_line('ju_bi_tips_title', 'Tips:') ?></h6>
                    <ul class="small mb-0">
                        <li><?= lang_line('ju_bi_tip_1', 'Gunakan reference yang deskriptif') ?></li>
                        <li><?= lang_line('ju_bi_tip_2', 'Reference bisa berupa: kode, kategori, atau nomor referensi') ?></li>
                        <li><?= lang_line('ju_bi_tip_3', 'Satu transaksi bisa memiliki banyak baris detail') ?></li>
                        <li><?= lang_line('ju_bi_tip_4', 'Pastikan setiap transaksi balance sebelum upload') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
