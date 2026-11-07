<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-file-import"></i>
                <?= htmlspecialchars($import->original_filename); ?>
            </h6>
            <a href="<?= base_url('summary_ledger'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> <?= lang_line('btn_back', 'Kembali') ?>
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><strong><?= lang_line('sl_label_uploaded_at', 'Waktu Upload:') ?></strong><br>
                    <?= date('d M Y H:i', strtotime($import->uploaded_at)); ?>
                </div>
                <div class="col-md-2"><strong><?= lang_line('sl_label_by', 'Oleh:') ?></strong><br>
                    <?= htmlspecialchars($import->uploader_name ?? '-'); ?>
                </div>
                <div class="col-md-2"><strong><?= lang_line('sl_label_total_rows', 'Total Baris:') ?></strong><br>
                    <?= (int) $import->total_rows; ?>
                </div>
                <div class="col-md-2"><strong><?= lang_line('sl_label_success_vouchers', 'Voucher Sukses:') ?></strong><br>
                    <span class="text-success font-weight-bold"><?= (int) $import->success_vouchers; ?></span>
                </div>
                <div class="col-md-3"><strong><?= lang_line('sl_label_skipped_vouchers', 'Voucher Di-skip:') ?></strong><br>
                    <span class="<?= ((int) $import->skipped_vouchers > 0) ? 'text-danger font-weight-bold' : 'text-muted'; ?>">
                        <?= (int) $import->skipped_vouchers; ?>
                    </span>
                </div>
            </div>

            <?php if (!empty($import->error_summary)): ?>
                <div class="alert alert-warning small" style="white-space: pre-wrap;">
                    <strong><?= lang_line('sl_label_error_notes', 'Catatan error/skip:') ?></strong>
                    <?= htmlspecialchars($import->error_summary); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> <?= sprintf(lang_line('sl_journals_in_batch', 'Journal yang Ter-create dari Batch Ini (%d)'), count($journals)) ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($journals)): ?>
                <p class="text-muted mb-0"><?= lang_line('sl_no_journals_left', 'Tidak ada journal yang masih tersisa dari batch ini (mungkin sudah dihapus).') ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th><?= lang_line('sl_th_journal_no', 'Nomor Jurnal') ?></th>
                                <th style="width: 110px;"><?= lang_line('sl_th_journal_date', 'Tanggal') ?></th>
                                <th><?= lang_line('sl_th_description', 'Deskripsi') ?></th>
                                <th style="width: 80px;"><?= lang_line('sl_th_currency', 'Currency') ?></th>
                                <th class="text-right"><?= lang_line('sl_th_total_debit_idr', 'Total Debit (IDR)') ?></th>
                                <th class="text-right"><?= lang_line('sl_th_total_credit_idr', 'Total Credit (IDR)') ?></th>
                                <th class="text-center" style="width: 80px;"><?= lang_line('sl_th_status', 'Status') ?></th>
                                <th class="text-center" style="width: 180px;"><?= lang_line('sl_th_actions', 'Aksi') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($journals as $i => $j): ?>
                                <tr>
                                    <td><?= $i + 1; ?></td>
                                    <td class="font-weight-bold text-primary"><?= htmlspecialchars($j->journal_no); ?></td>
                                    <td><?= date('d M Y', strtotime($j->journal_date)); ?></td>
                                    <td><?= htmlspecialchars($j->description); ?></td>
                                    <td><?= htmlspecialchars($j->currency); ?></td>
                                    <td class="text-right"><?= number_format($j->total_debit_local, 2); ?></td>
                                    <td class="text-right"><?= number_format($j->total_kredit_local, 2); ?></td>
                                    <td class="text-center">
                                        <?php if ($j->status === 'Posted'): ?>
                                            <span class="badge badge-success"><?= lang_line('sl_status_posted', 'Posted') ?></span>
                                        <?php elseif ($j->status === 'Cancelled'): ?>
                                            <span class="badge badge-secondary"><?= lang_line('sl_status_cancelled', 'Cancelled') ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?= lang_line('sl_status_draft', 'Draft') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('summary_ledger/voucher_pdf/' . (int) $j->id); ?>"
                                           target="_blank"
                                           class="btn btn-danger btn-sm" title="<?= lang_line('sl_btn_export_pdf_dis', 'Export PDF Disbursement') ?>">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <a href="<?= base_url('summary_ledger/voucher_excel/' . (int) $j->id); ?>"
                                           class="btn btn-success btn-sm" title="<?= lang_line('sl_btn_export_excel_dis', 'Export Excel Disbursement') ?>">
                                            <i class="fas fa-file-excel"></i>
                                        </a>
                                        <a href="<?= base_url('jurnalumum/view/' . (int) $j->id); ?>"
                                           class="btn btn-info btn-sm" title="<?= lang_line('sl_btn_view_in_gj', 'Lihat di General Journal') ?>">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($j->status === 'Draft'): ?>
                                            <a href="<?= base_url('jurnalumum/edit/' . (int) $j->id); ?>"
                                               class="btn btn-primary btn-sm" title="<?= lang_line('sl_btn_review_edit', 'Review & isi Vendor/WHT/PPN') ?>">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <a href="<?= base_url('summary_ledger/delete_journal/' . (int) $j->id . '?import_id=' . (int) $import->id); ?>"
                                               class="btn btn-warning btn-sm"
                                               title="<?= lang_line('sl_btn_delete_draft', 'Hapus (hanya Draft)') ?>"
                                               onclick="return confirm(<?= htmlspecialchars(json_encode(sprintf(lang_line('sl_confirm_delete_journal', 'Hapus journal %s? Aksi ini tidak bisa di-undo.'), $j->journal_no)), ENT_QUOTES) ?>);">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
