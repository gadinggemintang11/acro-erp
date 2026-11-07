<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('jurnalumum'); ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> <?= lang_line('ju_btn_back', 'Kembali') ?>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-upload mr-1"></i> <?= lang_line('ju_import_full_card', 'Restore Jurnal dari File Backup') ?>
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <?= lang_line('ju_import_full_info', 'Upload file hasil <strong>Export Full Backup</strong>. Jurnal akan dibuat ulang sebagai <strong>Draft</strong> lengkap dengan PPN/WHT/vendor dan semua baris detail. Format kolom harus sama persis dengan file backup.') ?>
                    </div>

                    <form action="<?= base_url('jurnalumum/process_import_full'); ?>" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label><?= lang_line('ju_import_full_file', 'File Backup (.xlsx / .xls / .csv)') ?></label>
                            <input type="file" name="file_backup" class="form-control-file" accept=".xlsx,.xls,.csv" required>
                        </div>
                        <button type="submit" class="btn btn-primary"
                                onclick="return confirm(<?= htmlspecialchars(json_encode(lang_line('ju_import_full_confirm', 'Restore jurnal dari file ini sebagai Draft baru?')), ENT_QUOTES) ?>);">
                            <i class="fas fa-undo"></i> <?= lang_line('ju_import_full_btn', 'Restore Jurnal') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
