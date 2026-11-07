<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('arinvoice/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text"><?= lang_line('ar_invoice_create_btn'); ?></span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ar_invoice_list_title'); ?></h6>
            <div>
                <a href="<?= base_url('arinvoice/export_excel'); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> <?= lang_line('text_excel'); ?>
                </a>
                <a href="<?= base_url('arinvoice/export_pdf'); ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> <?= lang_line('text_pdf'); ?>
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= lang_line('ar_invoice_no'); ?></th>
                            <th><?= lang_line('ar_invoice_date'); ?></th>
                            <th><?= lang_line('ar_invoice_customer'); ?></th>
                            <th><?= lang_line('text_type'); ?></th>
                            <th class="text-right"><?= lang_line('ar_invoice_total_bill'); ?></th>
                            <th class="text-right"><?= lang_line('text_amount'); ?> <?= lang_line('ar_receipt_no'); ?></th>
                            <th><?= lang_line('ar_invoice_payment_status'); ?></th>
                            <th><?= lang_line('journal_status'); ?></th>
                            <th><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('arinvoice/view/' . $inv->id); ?>">
                                    <strong><?= $inv->invoice_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($inv->invoice_date)); ?></td>
                            <td><?= $inv->nama_customer; ?></td>
                            <td>
                                <?php
                                    if ($inv->source_type == 'SCRAP_OUT') {
                                        echo '<span class="badge badge-warning">' . lang_line('arinv_type_scrap_out') . '</span>';
                                    } else {
                                        echo ($inv->customer_type == 'Lokal')
                                            ? '<span class="badge badge-primary">' . lang_line('arinv_type_fg_local') . '</span>'
                                            : '<span class="badge badge-info">' . lang_line('arinv_type_fg_export') . '</span>';
                                    }
                                ?>
                            </td>
                            <td class="text-right"><?= number_format($inv->total_invoice, 2); ?></td>
                            <td class="text-right font-weight-bold"><?= number_format($inv->total_invoice - $inv->total_paid, 2); ?></td>
                            <td>
                                <?php
                                    $badge = 'danger';
                                    if ($inv->payment_status == 'Partial') $badge = 'warning';
                                    if ($inv->payment_status == 'Paid') $badge = 'success';
                                ?>
                                <span class="badge badge-<?= $badge; ?>"><?= $inv->payment_status; ?></span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $inv->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                    <?= $inv->status; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('arinvoice/view/' . $inv->id); ?>" class="btn btn-info btn-circle btn-sm" title="<?= lang_line('btn_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($inv->status == 'Draft'): ?>
                                <a href="<?= base_url('arinvoice/delete/' . $inv->id); ?>" class="btn btn-danger btn-circle btn-sm" title="<?= lang_line('acct_delete_btn'); ?>" onclick="return confirm('<?= addslashes(lang_line('acct_confirm_delete_draft_invoice')); ?>');">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
