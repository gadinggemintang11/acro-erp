<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ju_imp_upload_card', 'Upload File Excel/CSV') ?></h6>
        </div>
        <form action="<?= base_url('jurnalumum/process_import'); ?>" method="POST" enctype="multipart/form-data">
            <div class="card-body">
                <p><?= lang_line('ju_imp_desc', 'Upload file (XLS, XLSX, atau CSV) untuk mengisi detail jurnal secara otomatis. Pastikan format file Anda benar:') ?></p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" style="width: auto;">
                        <thead>
                            <tr class="thead-light">
                                <th><?= lang_line('ju_imp_col_a', 'Kolom A') ?></th>
                                <th><?= lang_line('ju_imp_col_b', 'Kolom B') ?></th>
                                <th><?= lang_line('ju_imp_col_c', 'Kolom C') ?></th>
                                <th><?= lang_line('ju_imp_col_d', 'Kolom D') ?></th>
                                <th><?= lang_line('ju_imp_col_e', 'Kolom E') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><?= lang_line('ju_imp_col_a_val', 'KODE_AKUN') ?></td>
                                <td><?= lang_line('ju_imp_col_b_val', 'NAMA_AKUN (Opsional)') ?></td>
                                <td><?= lang_line('ju_imp_col_c_val', 'DEBIT') ?></td>
                                <td><?= lang_line('ju_imp_col_d_val', 'KREDIT') ?></td>
                                <td><?= lang_line('ju_imp_col_e_val', 'DESKRIPSI') ?></td>
                            </tr>
                            <tr>
                                <td>1-1101</td>
                                <td>Kas IDR</td>
                                <td>100000</td>
                                <td>0</td>
                                <td>Setoran awal</td>
                            </tr>
                            <tr>
                                <td>3-1000</td>
                                <td>Modal</td>
                                <td>0</td>
                                <td>100000</td>
                                <td>Setoran awal</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <hr>

                <input type="hidden" name="journal_no" value="<?= htmlspecialchars($journal_no); ?>">
                <input type="hidden" name="journal_date" value="<?= htmlspecialchars($journal_date); ?>">
                <input type="hidden" name="description" value="<?= htmlspecialchars($description); ?>">

                <div class="form-group">
                    <label><?= lang_line('ju_imp_lbl_choose', 'Pilih File (xls, xlsx, csv) *') ?></label>
                    <input type="file" name="file_import" class="form-control-file" required>
                </div>
            </div>
            <div class="card-footer text-right">
                <a href="<?= base_url('jurnalumum/create'); ?>" class="btn btn-secondary"><?= lang_line('ju_imp_btn_cancel', 'Batal') ?></a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> <?= lang_line('ju_imp_btn_upload', 'Upload dan Proses File') ?>
                </button>
            </div>
        </form>
    </div>
</div>
