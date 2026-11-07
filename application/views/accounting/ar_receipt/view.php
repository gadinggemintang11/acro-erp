<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('arreceipt'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> <?= lang_line('acct_back_to_list'); ?></a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('acct_receipt_info'); ?></h6>
                    <div>
                        <a href="<?= base_url('arreceipt/export_pdf_detail/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf"></i> <?= lang_line('acct_export_pdf'); ?>
                        </a>
                        <?php if($header->status == 'Draft'): ?>
                        <a href="<?= base_url('arreceipt/delete/' . $header->id); ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?= addslashes(lang_line('acct_confirm_delete_draft_receipt')); ?>');">
                            <i class="fas fa-trash"></i> <?= lang_line('acct_delete_btn'); ?>
                        </a>
                        <a href="<?= base_url('arreceipt/post/' . $header->id); ?>" class="btn btn-success btn-sm" onclick="return confirm('<?= addslashes(lang_line('acct_confirm_post_receipt')); ?>');">
                            <i class="fas fa-check-circle"></i> <?= lang_line('arrcp_post_receipt_btn'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3"><strong><?= lang_line('arrcp_view_receipt_no_colon'); ?></strong><br><?= $header->receipt_no; ?></div>
                        <div class="col-md-3"><strong><?= lang_line('arrcp_view_date_colon'); ?></strong><br><?= date('d M Y', strtotime($header->receipt_date)); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('acct_customer_colon'); ?></strong><br><?= $header->nama_customer; ?></div>
                        <div class="col-md-3"><strong><?= lang_line('arrcp_view_total_received'); ?></strong><br><h5 class="font-weight-bold text-success"><?= $header->currency; ?> <?= number_format($header->total_amount, 2); ?></h5></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-3"><strong><?= lang_line('arrcp_view_account_label'); ?></strong><br><?= $header->payment_account_name; ?> (<?= $header->account_code; ?>)</div>
                        <div class="col-md-3"><strong><?= lang_line('arrcp_view_rate_label'); ?></strong><br><?= number_format((float)$header->exchange_rate, 4); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('acct_status_colon'); ?></strong><br>
                             <span class="badge badge-<?= $header->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                <?= $header->status; ?>
                            </span>
                        </div>
                        <div class="col-md-3"><strong><?= lang_line('acct_journal_no_colon'); ?></strong><br>
                            <?= $header->journal_header_id ? ($journal_header->journal_no ?? 'N/A') : 'N/A'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('arrcp_view_paid_invoices_title'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('ap_invoice_no'); ?></th>
                                    <th><?= lang_line('ap_invoice_date'); ?></th>
                                    <th class="text-right"><?= lang_line('arrcp_th_total_invoice_curr'); ?> (<?= $header->currency; ?>)</th>
                                    <th class="text-right"><?= lang_line('arrcp_th_amount_paid_this'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($details as $d): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('arinvoice/view/' . $d->ar_invoice_header_id); ?>" target="_blank">
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
                    <h6 class="m-0 font-weight-bold text-success"><?= lang_line('acct_journal_posted'); ?> - <?= $journal_header->journal_no; ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('acct_th_account_code'); ?></th>
                                    <th><?= lang_line('acct_th_account_name'); ?></th>
                                    <th class="text-right"><?= lang_line('acct_th_debit'); ?> (<?= $header->currency; ?>)</th>
                                    <th class="text-right"><?= lang_line('acct_th_credit'); ?> (<?= $header->currency; ?>)</th>
                                    <th class="text-right"><?= lang_line('acct_th_debit_idr'); ?></th>
                                    <th class="text-right"><?= lang_line('acct_th_credit_idr'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($journal_details as $jd): ?>
                                <tr>
                                    <td><?= $jd->account_code; ?></td>
                                    <td><?= $jd->account_name; ?></td>
                                    <td class="text-right"><?= $jd->debit > 0 ? number_format($jd->debit, 2) : '-'; ?></td>
                                    <td class="text-right"><?= $jd->kredit > 0 ? number_format($jd->kredit, 2) : '-'; ?></td>
                                    <td class="text-right bg-gray-100"><?= $jd->debit_local > 0 ? number_format($jd->debit_local, 2) : '-'; ?></td>
                                    <td class="text-right bg-gray-100"><?= $jd->kredit_local > 0 ? number_format($jd->kredit_local, 2) : '-'; ?></td>
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
