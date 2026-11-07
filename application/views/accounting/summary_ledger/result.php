<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?= lang_line('sl_result_summary_prefix', 'Ringkasan Import — ') ?><?= htmlspecialchars($original_filename); ?>
                        <small class="text-muted"><?= sprintf(lang_line('sl_result_sheet_label', '(sheet: %s)'), htmlspecialchars($sheet_name)) ?></small>
                    </h6>
                    <div>
                        <a href="<?= base_url('summary_ledger'); ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> <?= lang_line('sl_btn_upload_again', 'Upload Lagi') ?>
                        </a>
                        <a href="<?= base_url('jurnalumum'); ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-list"></i> <?= lang_line('sl_btn_view_journal', 'Lihat di General Journal') ?>
                        </a>
                        <?php if (!empty($created_journals)): ?>
                            <button type="submit" form="form-post-all"
                                    class="btn btn-success btn-sm"
                                    onclick="return confirm(<?= htmlspecialchars(json_encode(sprintf(lang_line('sl_confirm_post_all', 'Post semua %d journal hasil import ini? Status akan berubah dari Draft → Posted dan tidak bisa di-edit lagi.'), count($created_journals))), ENT_QUOTES) ?>);">
                                <i class="fas fa-check-double"></i> <?= sprintf(lang_line('sl_btn_post_all', 'Post All Imported (%d)'), count($created_journals)) ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body">
                    <?php if (!empty($created_journals)): ?>
                        <form id="form-post-all" action="<?= base_url('summary_ledger/post_imported'); ?>" method="POST" style="display:none;">
                            <?php foreach ($created_journals as $j): ?>
                                <input type="hidden" name="journal_ids[]" value="<?= (int) $j['id']; ?>">
                            <?php endforeach; ?>
                        </form>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="border-left-success card shadow py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-success text-uppercase"><?= lang_line('sl_card_success', 'Voucher Berhasil') ?></div>
                                    <div class="h4 mb-0 font-weight-bold"><?= count($created_journals); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border-left-danger card shadow py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase"><?= lang_line('sl_card_skipped', 'Voucher Di-skip') ?></div>
                                    <div class="h4 mb-0 font-weight-bold"><?= count($skipped); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border-left-info card shadow py-2">
                                <div class="card-body">
                                    <div class="text-xs font-weight-bold text-info text-uppercase"><?= lang_line('sl_card_warnings', 'Peringatan Parsing') ?></div>
                                    <div class="h4 mb-0 font-weight-bold"><?= count($parse_warnings); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============ Created Journals ============ -->
                    <?php if (!empty($created_journals)): ?>
                        <h6 class="font-weight-bold text-success mb-2">
                            <i class="fas fa-check-circle"></i> <?= lang_line('sl_section_created_journal', 'Journal Berhasil Dibuat (status Draft)') ?>
                        </h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th><?= lang_line('sl_th_journal_no', 'Nomor Jurnal') ?></th>
                                        <th><?= lang_line('sl_th_voucher_source', 'Voucher Sumber') ?></th>
                                        <th><?= lang_line('sl_th_excel_rows', 'Baris Excel') ?></th>
                                        <th class="text-center" style="width: 90px;"><?= lang_line('sl_th_actions', 'Aksi') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($created_journals as $i => $j): ?>
                                        <tr>
                                            <td><?= $i + 1; ?></td>
                                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($j['journal_no']); ?></td>
                                            <td><?= htmlspecialchars($j['voucher_label']); ?></td>
                                            <td><?= htmlspecialchars($j['rows']); ?></td>
                                            <td class="text-center">
                                                <a href="<?= base_url('jurnalumum/edit/' . (int) $j['id']); ?>" class="btn btn-warning btn-sm" title="<?= lang_line('sl_btn_review_edit', 'Review & isi Vendor/WHT/PPN') ?>">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <a href="<?= base_url('jurnalumum'); ?>?search=<?= urlencode($j['journal_no']); ?>" class="btn btn-info btn-sm" title="<?= lang_line('sl_search_in_gj', 'Cari di General Journal') ?>">
                                                    <i class="fas fa-search"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- ============ Skipped Vouchers ============ -->
                    <?php if (!empty($skipped)): ?>
                        <h6 class="font-weight-bold text-danger mb-2">
                            <i class="fas fa-exclamation-triangle"></i> <?= lang_line('sl_section_skipped', 'Voucher Di-skip — Perlu Diperbaiki') ?>
                        </h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th style="width: 180px;"><?= lang_line('sl_th_voucher', 'Voucher') ?></th>
                                        <th style="width: 120px;"><?= lang_line('sl_th_excel_rows', 'Baris Excel') ?></th>
                                        <th><?= lang_line('sl_th_reason', 'Alasan') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($skipped as $i => $s): ?>
                                        <tr>
                                            <td><?= $i + 1; ?></td>
                                            <td class="font-weight-bold"><?= htmlspecialchars($s['voucher_no']); ?></td>
                                            <td><?= htmlspecialchars($s['rows']); ?></td>
                                            <td>
                                                <ul class="mb-0 pl-3">
                                                    <?php foreach ($s['reasons'] as $r): ?>
                                                        <li class="text-danger small"><?= htmlspecialchars($r); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <!-- ============ Parse Warnings ============ -->
                    <?php if (!empty($parse_warnings)): ?>
                        <h6 class="font-weight-bold text-info mb-2">
                            <i class="fas fa-info-circle"></i> <?= lang_line('sl_card_warnings', 'Peringatan Parsing') ?>
                        </h6>
                        <div class="alert alert-info">
                            <ul class="mb-0">
                                <?php foreach ($parse_warnings as $w): ?>
                                    <li class="small"><?= htmlspecialchars($w); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($created_journals) && empty($skipped)): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-info-circle"></i>
                            <?= sprintf(lang_line('sl_no_vouchers_found_html', 'Tidak ada voucher yang ditemukan di sheet <code>%s</code>. Pastikan baris header voucher memiliki kolom <strong>Date</strong> dan <strong>Voucher No.</strong> terisi.'), htmlspecialchars($sheet_name)) ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>
