<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('appayment/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text"><?= lang_line('ap_payment_create_btn'); ?></span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ap_payment_list_title'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= lang_line('ap_payment_no'); ?></th>
                            <th><?= lang_line('ap_payment_date'); ?></th>
                            <th><?= lang_line('ap_payment_supplier'); ?></th>
                            <th><?= lang_line('ap_payment_account'); ?></th>
                            <th class="text-right"><?= lang_line('ap_payment_total'); ?></th>
                            <th><?= lang_line('ap_payment_journal_status'); ?></th>
                            <th><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('appayment/view/' . $p->id); ?>">
                                    <strong><?= $p->payment_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($p->payment_date)); ?></td>
                            <td><?= $p->nama_supplier; ?></td>
                            <td><?= $p->payment_account_name ?: '<span class="text-muted">-</span>'; ?></td>
                            <td class="text-right"><?= number_format($p->total_amount, 2); ?></td>
                            <td>
                                <span class="badge badge-<?= $p->status == 'Posted' ? 'success' : 'secondary'; ?>">
                                    <?= $p->status; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('appayment/view/' . $p->id); ?>" class="btn btn-info btn-circle btn-sm" title="<?= lang_line('btn_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($p->status == 'Draft'): ?>
                                <a href="<?= base_url('appayment/delete/' . $p->id); ?>" class="btn btn-danger btn-circle btn-sm" title="<?= lang_line('btn_delete'); ?>" onclick="return confirm('<?= addslashes(lang_line('acct_confirm_delete_draft_payment')); ?>');">
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