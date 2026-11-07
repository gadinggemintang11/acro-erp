<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('arreceipt/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text"><?= lang_line('ar_receipt_create_btn'); ?></span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ar_receipt_list_title'); ?></h6>
            <div>
                <a href="<?= base_url('arreceipt/export_excel'); ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> <?= lang_line('text_excel'); ?>
                </a>
                <a href="<?= base_url('arreceipt/export_pdf'); ?>" target="_blank" class="btn btn-danger btn-sm">
                    <i class="fas fa-file-pdf"></i> <?= lang_line('text_pdf'); ?>
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= lang_line('ar_receipt_no'); ?></th>
                            <th><?= lang_line('ar_receipt_date'); ?></th>
                            <th><?= lang_line('ar_receipt_customer'); ?></th>
                            <th><?= lang_line('ar_receipt_account'); ?></th>
                            <th class="text-right"><?= lang_line('ar_receipt_total'); ?></th>
                            <th><?= lang_line('ar_receipt_currency'); ?></th>
                            <th><?= lang_line('ar_receipt_journal_status'); ?></th>
                            <th><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receipts as $r): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('arreceipt/view/' . $r->id); ?>">
                                    <strong><?= $r->receipt_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($r->receipt_date)); ?></td>
                            <td><?= $r->nama_customer; ?></td>
                            <td><?= $r->payment_account_name; ?></td>
                            <td class="text-right"><?= number_format($r->total_amount, 2); ?></td>
                            <td><?= $r->currency; ?></td>
                            <td>
                                <span class="badge badge-<?= $r->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                    <?= $r->status; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('arreceipt/view/' . $r->id); ?>" class="btn btn-info btn-circle btn-sm" title="<?= lang_line('btn_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($r->status == 'Draft'): ?>
                                <a href="<?= base_url('arreceipt/delete/' . $r->id); ?>" class="btn btn-danger btn-circle btn-sm" title="<?= lang_line('btn_delete'); ?>" onclick="return confirm('<?= lang_line('msg_confirm_delete'); ?>');">
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