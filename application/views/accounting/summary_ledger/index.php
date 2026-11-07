<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <!-- ============ Upload Form ============ -->
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-file-upload"></i>
                        <?= lang_line('sl_upload_card_title', 'Upload Excel — Software Akuntansi China') ?>
                    </h6>
                </div>

                <form action="<?= base_url('summary_ledger/process_import'); ?>" method="POST" enctype="multipart/form-data">
                    <div class="card-body">

                        <div class="alert alert-info">
                            <h6 class="font-weight-bold mb-2"><i class="fas fa-info-circle"></i> <?= lang_line('sl_howto_title', 'Cara Pakai') ?></h6>
                            <ol class="mb-0 pl-3">
                                <li><?= lang_line('sl_howto_1', 'Export jurnal dari software akuntansi China sebagai Excel (sheet <code>Journal Import</code>).') ?></li>
                                <li><?= lang_line('sl_howto_2', 'Pastikan setiap voucher punya baris header dengan <strong>Date</strong>, <strong>FY</strong>, <strong>FP</strong>, <strong>Voucher Category</strong>, dan <strong>Voucher No.</strong> terisi.') ?></li>
                                <li><?= lang_line('sl_howto_3', 'Baris detail berikutnya cukup isi Description, Account Code, Currency, Amount, Debit Amount / Credit Amount.') ?></li>
                                <li><?= lang_line('sl_howto_4', 'Upload file di bawah. Sistem akan otomatis bikin satu <strong>Journal Header (status Draft)</strong> per voucher.') ?></li>
                                <li><?= lang_line('sl_howto_5', 'Voucher yang invalid (akun tidak ada, tidak balance, dll) akan di-skip dan dilaporkan, voucher valid tetap masuk.') ?></li>
                            </ol>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold"><?= lang_line('sl_choose_file', 'Pilih File Excel (xls, xlsx, csv) *') ?></label>
                            <input type="file" name="file_excel" class="form-control-file" required accept=".xls,.xlsx,.csv">
                            <small class="form-text text-muted"><?= lang_line('sl_max_filesize', 'Maksimal ukuran file: 15MB.') ?></small>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold"><?= lang_line('sl_sheet_name', 'Nama Sheet') ?></label>
                            <input type="text" name="sheet_name" class="form-control" value="Journal Import" placeholder="Journal Import">
                            <small class="form-text text-muted"><?= lang_line('sl_sheet_default_hint', 'Default <code>Journal Import</code>. Kosongkan untuk pakai sheet aktif pertama.') ?></small>
                        </div>

                        <hr>

                        <h6 class="font-weight-bold text-primary mb-2"><?= lang_line('sl_format_section', 'Format Kolom yang Dibaca') ?></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm small">
                                <thead class="thead-light">
                                    <tr>
                                        <th><?= lang_line('sl_th_col', 'Kolom') ?></th>
                                        <th><?= lang_line('sl_th_excel_header', 'Header Excel') ?></th>
                                        <th><?= lang_line('sl_th_notes', 'Catatan') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td>A</td><td>Date</td><td><?= lang_line('sl_col_a_note', 'Wajib di baris header voucher; menandakan voucher baru.') ?></td></tr>
                                    <tr><td>B</td><td>FY</td><td><?= lang_line('sl_col_b_note', 'Fiscal Year (mis. 2025).') ?></td></tr>
                                    <tr><td>C</td><td>FP</td><td><?= lang_line('sl_col_c_note', 'Fiscal Period / bulan (1-12).') ?></td></tr>
                                    <tr><td>D</td><td>Voucher Category</td><td><?= lang_line('sl_col_d_note', 'Mis. JV / PV / RV. Ikut dipakai untuk nomor jurnal.') ?></td></tr>
                                    <tr><td>E</td><td>Voucher No.</td><td><?= lang_line('sl_col_e_note', 'Nomor voucher di sumber.') ?></td></tr>
                                    <tr><td>F</td><td>Description</td><td><?= lang_line('sl_col_f_note', 'Diisi per baris; baris pertama dipakai sebagai deskripsi header.') ?></td></tr>
                                    <tr><td>G</td><td>Account Code</td><td><?= lang_line('sl_col_g_note', 'Harus cocok persis dengan <strong>account_code</strong> di COA &amp; akun harus aktif &amp; postable.') ?></td></tr>
                                    <tr><td>I</td><td>Currency</td><td><?= lang_line('sl_col_i_note', 'IDR / USD / HKD dll. Jika beda-beda dalam 1 voucher, header dicatat sebagai <code>MIXED</code>.') ?></td></tr>
                                    <tr><td>J</td><td>Amount (Original Currency)</td><td><?= lang_line('sl_col_j_note', 'Nilai original disimpan di kolom <code>debit</code>/<code>kredit</code>.') ?></td></tr>
                                    <tr><td>K</td><td>Debit Amount</td><td><?= lang_line('sl_col_k_note', 'Nilai dalam mata uang dasar (IDR). Dipakai untuk validasi balance.') ?></td></tr>
                                    <tr><td>L</td><td>Credit Amount</td><td><?= lang_line('sl_col_l_note', 'Sama seperti K untuk sisi kredit.') ?></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning mt-3 mb-0">
                            <h6 class="font-weight-bold mb-2"><i class="fas fa-exclamation-triangle"></i> <?= lang_line('sl_validation_section', 'Aturan Validasi') ?></h6>
                            <ul class="mb-0">
                                <li><?= lang_line('sl_rule_1', 'Account Code wajib ada di Chart of Accounts &amp; berstatus <strong>active</strong> &amp; <strong>postable</strong> (<code>is_header=0</code>).') ?></li>
                                <li><?= lang_line('sl_rule_2', 'Setiap baris detail: salah satu dari Debit Amount / Credit Amount harus terisi (tidak boleh keduanya, tidak boleh nol).') ?></li>
                                <li><?= lang_line('sl_rule_3', 'Total Debit Amount = Total Credit Amount per voucher (selisih &gt; 0.01 = di-skip).') ?></li>
                                <li><?= lang_line('sl_rule_4', 'Baris dengan kolom A bertuliskan <code>Total</code> di akhir sheet akan otomatis di-skip.') ?></li>
                                <li><?= lang_line('sl_rule_5', 'Semua journal yang berhasil masuk dibuat dengan status <strong>Draft</strong> — review dulu di menu General Journal sebelum di-post.') ?></li>
                            </ul>
                        </div>
                    </div>

                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> <?= lang_line('sl_btn_upload_process', 'Upload & Proses') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ============ History Imports ============ -->
        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-history"></i> <?= lang_line('sl_history_title', 'Riwayat Import Terakhir') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($imports)): ?>
                        <p class="text-muted mb-0"><?= lang_line('sl_no_history', 'Belum ada riwayat upload.') ?></p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover small">
                                <thead class="thead-light">
                                    <tr>
                                        <th><?= lang_line('sl_th_time', 'Waktu') ?></th>
                                        <th><?= lang_line('sl_th_file', 'File') ?></th>
                                        <th class="text-right"><?= lang_line('sl_th_success', 'Sukses') ?></th>
                                        <th class="text-right"><?= lang_line('sl_th_skipped', 'Skip') ?></th>
                                        <th><?= lang_line('sl_th_by', 'Oleh') ?></th>
                                        <th class="text-center" style="width: 60px;"><?= lang_line('sl_th_detail', 'Detail') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($imports as $imp): ?>
                                        <tr style="cursor: pointer;" onclick="window.location='<?= base_url('summary_ledger/detail/' . (int) $imp->id); ?>';">
                                            <td><?= date('d M Y H:i', strtotime($imp->uploaded_at)); ?></td>
                                            <td title="<?= htmlspecialchars($imp->original_filename); ?>">
                                                <?= htmlspecialchars(mb_strimwidth($imp->original_filename, 0, 28, '…')); ?>
                                            </td>
                                            <td class="text-right text-success font-weight-bold"><?= (int) $imp->success_vouchers; ?></td>
                                            <td class="text-right <?= ((int) $imp->skipped_vouchers > 0) ? 'text-danger font-weight-bold' : 'text-muted'; ?>">
                                                <?= (int) $imp->skipped_vouchers; ?>
                                            </td>
                                            <td><?= htmlspecialchars($imp->uploader_name ?? '-'); ?></td>
                                            <td class="text-center">
                                                <a href="<?= base_url('summary_ledger/detail/' . (int) $imp->id); ?>"
                                                   class="btn btn-info btn-sm" onclick="event.stopPropagation();" title="<?= lang_line('sl_btn_view_voucher_detail', 'Lihat detail voucher') ?>">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php if (!empty($imp->error_summary)): ?>
                                            <tr onclick="window.location='<?= base_url('summary_ledger/detail/' . (int) $imp->id); ?>';" style="cursor: pointer;">
                                                <td colspan="6" class="text-muted small" style="white-space: pre-wrap;"><?= htmlspecialchars(mb_strimwidth($imp->error_summary, 0, 400, '…')); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
