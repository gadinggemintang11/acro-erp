<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('arinvoice'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> <?= lang_line('acct_back_to_list'); ?></a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('acct_invoice_info'); ?></h6>
                    <div>
                        <a href="<?= base_url('arinvoice/export_pdf_detail/' . $header->id); ?>" target="_blank" class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf"></i> <?= lang_line('acct_export_pdf'); ?>
                        </a>
                        <?php if($header->status == 'Draft'): ?>
                        <a href="<?= base_url('arinvoice/delete/' . $header->id); ?>" class="btn btn-danger btn-sm" onclick="return confirm('<?= addslashes(lang_line('acct_confirm_delete_draft_invoice')); ?>');">
                            <i class="fas fa-trash"></i> <?= lang_line('acct_delete_btn'); ?>
                        </a>
                        <a href="<?= base_url('arinvoice/post/' . $header->id); ?>" class="btn btn-success btn-sm" onclick="return confirm('<?= addslashes(lang_line('acct_confirm_post_invoice')); ?>');">
                            <i class="fas fa-check-circle"></i> <?= lang_line('acct_post_journal_btn'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-3"><strong><?= lang_line('acct_invoice_no_colon'); ?></strong><br><?= $header->invoice_no; ?></div>
                        <div class="col-md-3"><strong><?= lang_line('acct_invoice_date_colon'); ?></strong><br><?= date('d M Y', strtotime($header->invoice_date)); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('acct_due_date_colon'); ?></strong><br><?= date('d M Y', strtotime($header->due_date)); ?></div>
                        <div class="col-md-3"><strong><?= lang_line('arinv_journal_status_colon'); ?></strong><br>
                             <span class="badge badge-<?= $header->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                <?= $header->status; ?>
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><strong><?= lang_line('acct_customer_colon'); ?></strong><br><?= $header->nama_customer; ?></div>
                        <div class="col-md-3"><strong><?= lang_line('acct_type_colon'); ?></strong><br>
                            <?= ($header->source_type == 'SCRAP_OUT') ? '<span class="badge badge-warning">' . lang_line('arinv_type_scrap_out') . '</span>' : '<span class="badge badge-primary">' . lang_line('arinv_type_fg_delivery') . '</span>'; ?>
                        </div>
                        <div class="col-md-3"><strong><?= lang_line('acct_payment_status_colon'); ?></strong><br>
                            <?php
                                $badge = 'danger';
                                if ($header->payment_status == 'Partial') $badge = 'warning';
                                if ($header->payment_status == 'Paid') $badge = 'success';
                            ?>
                            <span class="badge badge-<?= $badge; ?>"><?= $header->payment_status; ?></span>
                        </div>
                        <div class="col-md-3"><strong><?= lang_line('acct_journal_no_colon'); ?></strong><br>
                            <?= $header->journal_header_id ? ($journal_header->journal_no ?? 'N/A') : 'N/A'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('acct_invoice_items'); ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('apinv_th_desc_item'); ?></th>
                                    <th class="text-right"><?= lang_line('acct_th_qty'); ?></th>
                                    <th class="text-right"><?= lang_line('acct_th_unit_price'); ?></th>
                                    <th class="text-right"><?= lang_line('acct_th_total'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($details as $d): ?>
                                <tr>
                                    <td><?= $d->description; ?></td>
                                    <td class="text-right"><?= (float)$d->qty; ?></td>
                                    <td class="text-right"><?= number_format($d->unit_price, 2); ?></td>
                                    <td class="text-right"><?= number_format($d->total_price, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('acct_payment_breakdown'); ?></h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th><?= lang_line('acct_subtotal'); ?></th>
                            <td class="text-right"><?= number_format($header->subtotal, 2); ?></td>
                        </tr>
                        <tr>
                            <th><?= lang_line('acct_ppn'); ?></th>
                            <td class="text-right"><?= number_format($header->ppn_amount, 2); ?></td>
                        </tr>
                        <tr class="font-weight-bold bg-light">
                            <td><?= lang_line('acct_total_bill'); ?></td>
                            <td class="text-right"><?= number_format($header->total_invoice, 2); ?></td>
                        </tr>
                        <tr>
                            <td><?= lang_line('acct_total_paid'); ?></td>
                            <td class="text-right">(-) <?= number_format($header->total_paid, 2); ?></td>
                        </tr>
                        <tr class="font-weight-bold bg-gray-200" style="font-size: 1.1rem;">
                            <td><?= lang_line('acct_remaining_receivable'); ?></td>
                            <td class="text-right"><?= number_format($header->total_invoice - $header->total_paid, 2); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <?php if($header->status == 'Posted' && isset($journal_details)): ?>
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success"><?= lang_line('acct_journal_posted'); ?> - <?= $journal_header->journal_no ?? 'N/A'; ?></h6>
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
