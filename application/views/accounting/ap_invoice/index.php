<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <a href="<?= base_url('apinvoice/create'); ?>" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text"><?= lang_line('ap_invoice_create_btn'); ?></span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ap_invoice_list_title'); ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= lang_line('ap_invoice_no'); ?></th>
                            <th><?= lang_line('ap_invoice_date'); ?></th>
                            <th><?= lang_line('ap_invoice_supplier'); ?></th>
                            <th><?= lang_line('ap_invoice_type'); ?></th>
                            <th class="text-right"><?= lang_line('ap_invoice_total_bill'); ?></th>
                            <th class="text-right"><?= lang_line('ap_invoice_total_paid'); ?></th>
                            <th><?= lang_line('ap_invoice_payment_status'); ?></th>
                            <th><?= lang_line('ap_invoice_journal_status'); ?></th>
                            <th><?= lang_line('label_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td>
                                <a href="<?= base_url('apinvoice/view/' . $inv->id); ?>">
                                    <strong><?= $inv->invoice_no; ?></strong>
                                </a>
                            </td>
                            <td><?= date('d-m-Y', strtotime($inv->invoice_date)); ?></td>
                            <td><?= $inv->nama_supplier; ?></td>
                            <td>
                                <?php
                                    if ($inv->invoice_type == 'PO_BASED') {
                                        echo '<span class="badge badge-success">' . lang_line('ap_invoice_po_based', 'Berdasarkan PO') . '</span>';
                                    } elseif ($inv->invoice_type == 'NON_GRN') {
                                        echo '<span class="badge badge-warning">' . lang_line('ap_invoice_service_subcon') . '</span>';
                                    } else {
                                        $grn_type = isset($inv->grn_base_on) ? $inv->grn_base_on : '';
                                        if ($grn_type == 'PO') {
                                            echo '<span class="badge badge-primary">' . lang_line('ap_invoice_po_local') . '</span>';
                                        } elseif ($grn_type == 'MIR') {
                                            echo '<span class="badge badge-info">' . lang_line('ap_invoice_mir_import') . '</span>';
                                        } else {
                                            if ($inv->supplier_type == 'Lokal') {
                                                echo '<span class="badge badge-primary">' . lang_line('ap_invoice_po_local') . '</span>';
                                            } else {
                                                echo '<span class="badge badge-info">' . lang_line('ap_invoice_mir_import') . '</span>';
                                            }
                                        }
                                    }
                                ?>
                            </td>
                            <td class="text-right"><?= number_format($inv->total_invoice, 2); ?></td>
                            <td class="text-right"><?= number_format($inv->total_paid, 2); ?></td>
                            <td>
                                <?php
                                    // Ini status bayar
                                    $badge = 'danger';
                                    if ($inv->payment_status == 'Unpaid') $badge = 'danger';
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
                                <a href="<?= base_url('apinvoice/view/' . $inv->id); ?>" class="btn btn-info btn-circle btn-sm" title="<?= lang_line('btn_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if($inv->status == 'Draft'): ?>
                                <a href="<?= base_url('apinvoice/delete/' . $inv->id); ?>" class="btn btn-danger btn-circle btn-sm" title="<?= lang_line('acct_delete_btn'); ?>" onclick="return confirm('<?= addslashes(lang_line('acct_confirm_delete_draft_invoice')); ?>');">
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