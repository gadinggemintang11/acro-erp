<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <div class="btn-group">
            <a href="<?= base_url('appayment'); ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> <?= lang_line('acct_back_to_list'); ?>
            </a>
            <a href="<?= base_url('appayment/export_detail_excel/' . $header->id); ?>" class="btn btn-success btn-sm" target="_blank">
                <i class="fas fa-file-excel"></i> <?= lang_line('acct_export_excel'); ?>
            </a>
            <a href="<?= base_url('appayment/export_detail_pdf/' . $header->id); ?>" class="btn btn-danger btn-sm" target="_blank">
                <i class="fas fa-file-pdf"></i> <?= lang_line('acct_export_pdf'); ?>
            </a>
        </div>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('acct_payment_info'); ?></h6>
                    <?php if($header->status == 'Draft'): ?>
                    <div>
                        <?php if (!empty($can_edit) && empty($header->prf_header_id)): ?>
                        <a href="<?= base_url('appayment/edit/' . $header->id); ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php endif; ?>
                        <a href="<?= base_url('appayment/delete/' . $header->id); ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('<?= addslashes(lang_line('acct_confirm_delete_draft_payment')); ?>');">
                            <i class="fas fa-trash"></i> <?= lang_line('acct_delete_btn'); ?>
                        </a>
                        <a href="<?= base_url('appayment/post/' . $header->id); ?>"
                           class="btn btn-success btn-sm"
                           onclick="return confirm('<?= addslashes(lang_line('acct_confirm_post_payment')); ?>');">
                            <i class="fas fa-check-circle"></i> <?= lang_line('acct_post_payment_btn'); ?>
                        </a>
                    </div>
                    <?php elseif ($header->status == 'Posted' && !empty($can_edit)): ?>
                    <div class="text-right">
                        <?php if (empty($redraft_block)): ?>
                        <form method="post" action="<?= base_url('appayment/redraft/' . $header->id); ?>" class="d-inline form-redraft">
                            <input type="hidden" name="reason" value="">
                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-undo"></i> Redraft</button>
                        </form>
                        <?php else: ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="<?= htmlspecialchars($redraft_block); ?>"><i class="fas fa-undo"></i> Redraft</button>
                        <div class="small text-muted mt-1" style="max-width:420px"><?= htmlspecialchars($redraft_block); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($header->status == 'Draft' && !empty($journal_header)): ?>
                        <div class="alert alert-warning py-2">
                            <i class="fas fa-undo mr-1"></i> Pembayaran hasil <strong>redraft</strong>. Sisa hutang faktur sudah dikembalikan; jurnal
                            <strong><?= htmlspecialchars($journal_header->journal_no); ?></strong> berstatus Draft (tidak masuk laporan).
                            Edit bila perlu, lalu klik <strong>Posting</strong> — jurnal yang sama diperbarui &amp; diposting ulang.
                        </div>
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-3"><strong><?= lang_line('appay_payment_no_colon'); ?></strong><br><?= $header->payment_no; ?></div>
                        <div class="col-md-3"><strong><?= lang_line('appay_date_short'); ?></strong><br><?= date('d M Y', strtotime($header->payment_date)); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('acct_supplier_colon'); ?></strong><br><?= $header->nama_supplier; ?></div>
                        <div class="col-md-3"><strong><?= lang_line('appay_view_total_paid'); ?></strong><br>
                            <h5 class="font-weight-bold text-success"><?= number_format($header->total_amount, 2); ?></h5>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-3"><strong><?= lang_line('appay_view_pay_acct'); ?></strong><br><?= $header->payment_account_name; ?> (<?= $header->account_code; ?>)</div>
                        <div class="col-md-3"><strong><?= lang_line('acct_notes_colon'); ?></strong><br><?= $header->notes; ?></div>
                        <div class="col-md-3"><strong><?= lang_line('acct_status_colon'); ?></strong><br>
                             <span class="badge badge-<?= $header->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                <?= $header->status; ?>
                            </span>
                        </div>
                        <div class="col-md-3"><strong><?= lang_line('acct_journal_no_colon'); ?></strong><br>
                            <?= $header->journal_header_id ? $journal_header->journal_no : 'N/A'; ?>
                        </div>
                    </div>
                    <hr>
                    <!-- ✨ NEW: Tampilkan header-level account mapping -->
                    <div class="row">
                        <div class="col-md-6">
                            <strong><?= lang_line('appay_view_debet_acct'); ?></strong><br>
                            <?php if($header->debet_account_id): ?>
                                <span class="badge badge-primary"><?= htmlspecialchars($account_names[(int) $header->debet_account_id] ?? ('ID: ' . $header->debet_account_id)); ?></span>
                            <?php else: ?>
                                <span class="text-muted"><?= lang_line('appay_view_not_selected'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <strong><?= lang_line('appay_view_kredit_acct'); ?></strong><br>
                            <?php if($header->kredit_account_id): ?>
                                <span class="badge badge-info"><?= htmlspecialchars($account_names[(int) $header->kredit_account_id] ?? ('ID: ' . $header->kredit_account_id)); ?></span>
                            <?php else: ?>
                                <span class="text-muted"><?= lang_line('appay_view_not_selected'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ✨ NEW: Tampilkan PPh Withholding Tax Information -->
                    <?php if($header->pph_rate > 0 || $header->pph_amount > 0): ?>
                    <hr>
                    <div class="row bg-light p-3 rounded">
                        <div class="col-md-3">
                            <strong><?= lang_line('appay_view_pph_rate'); ?></strong><br>
                            <h5 class="text-warning"><?= $header->pph_rate; ?>%</h5>
                        </div>
                        <div class="col-md-3">
                            <strong><?= lang_line('appay_view_pph_amount'); ?></strong><br>
                            <h5 class="text-warning"><?= number_format($header->pph_amount, 2); ?></h5>
                        </div>
                        <div class="col-md-3">
                            <strong><?= lang_line('appay_view_pph_acct'); ?></strong><br>
                            <?php if($header->pph_account_id): ?>
                                <span class="badge badge-warning"><?= htmlspecialchars($account_names[(int) $header->pph_account_id] ?? ('ID: ' . $header->pph_account_id)); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <strong><?= lang_line('appay_view_pay_netto'); ?></strong><br>
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
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('appay_paid_invoices_title'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('ap_invoice_no'); ?></th>
                                    <th><?= lang_line('ap_invoice_date'); ?></th>
                                    <th class="text-right"><?= lang_line('appay_th_total_invoice'); ?></th>
                                    <th class="text-right"><?= lang_line('appay_th_amount_paid_this'); ?></th>
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

        <?php if(in_array($header->status, ['Posted', 'Draft'], true) && isset($journal_details) && !empty($journal_header)): ?>
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <?php if ($journal_header->status === 'Posted'): ?>
                    <h6 class="m-0 font-weight-bold text-success"><?= lang_line('acct_journal_posted'); ?> - <?= $journal_header->journal_no; ?></h6>
                    <?php else: ?>
                    <h6 class="m-0 font-weight-bold text-warning">Jurnal <?= $journal_header->journal_no; ?> (Draft &mdash; isi lama, diperbarui saat Posting)</h6>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('acct_th_account_code'); ?></th>
                                    <th><?= lang_line('acct_th_account_name'); ?></th>
                                    <th class="text-right"><?= lang_line('acct_th_debit_idr'); ?></th>
                                    <th class="text-right"><?= lang_line('acct_th_credit_idr'); ?></th>
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

<script>
document.querySelectorAll('.form-redraft').forEach(function (f) {
    f.addEventListener('submit', function (e) {
        var r = prompt('Pembayaran ini' + ' akan dikembalikan ke DRAFT supaya bisa diedit.\n' + 'Sisa hutang faktur dikembalikan dan jurnalnya ikut menjadi Draft sampai pembayaran diposting ulang.' + '\n\nAlasan redraft (wajib):');
        if (r === null) { e.preventDefault(); return; }
        if (r.trim().length < 5) { e.preventDefault(); alert('Alasan redraft minimal 5 karakter.'); return; }
        f.querySelector('input[name=reason]').value = r.trim();
    });
});
</script>
