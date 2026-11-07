<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-gray-800 mb-0">
            <i class="fas fa-list text-primary mr-2"></i><?= htmlspecialchars($title); ?>
        </h3>
        <small class="text-muted"><?= lang_line('sc_list_readonly_caption'); ?></small>
    </div>

    <!-- Flashdata -->
    <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $key => $cls): ?>
        <?php if ($this->session->flashdata($key)): ?>
        <div class="alert alert-<?= $cls; ?> alert-dismissible fade show" role="alert">
            <?= $this->session->flashdata($key); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <div class="card border-0 shadow-sm rounded">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" id="dataTable">
                    <thead class="thead-light text-center">
                        <tr>
                            <th><?= lang_line('sc_ipo_number_label', 'No. Sales Order'); ?></th>
                            <th><?= lang_line('sc_customer_label', 'Pelanggan'); ?></th>
                            <th><?= lang_line('sc_style_label', 'Style'); ?></th>
                            <th><?= lang_line('sc_approval_date_label', 'Tanggal'); ?></th>
                            <th><?= lang_line('sc_lchd_date_label', 'LCHD'); ?></th>
                            <th><?= lang_line('sc_po_qty_label', 'Qty'); ?></th>
                            <th><?= lang_line('sc_total_price_label', 'Total Price'); ?></th>
                            <th><?= lang_line('sc_destination_label', 'Destination'); ?></th>
                            <th><?= lang_line('sc_status_label', 'Status'); ?></th>
                            
                            <th><?= lang_line('sc_approved_by_label', 'Approved By'); ?></th>
                            <th width="60"><?= lang_line('sc_action_label', 'Aksi'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contracts)): foreach ($contracts as $sc): ?>
                        <tr class="text-center">
                            <td class="font-weight-bold text-primary"><?= htmlspecialchars($sc->sc_number); ?></td>
                            <td class="text-left"><?= htmlspecialchars($sc->nama_customer); ?></td>
                            <td class="text-left"><?= htmlspecialchars($sc->style_name); ?></td>
                            <td><?= date('d M Y', strtotime($sc->contract_date)); ?></td>
                            <td><?= $sc->lchd_date ? date('d M Y', strtotime($sc->lchd_date)) : '-'; ?></td>
                            <td class="text-right"><?= number_format($sc->po_qty); ?></td>
                            <td class="text-right">
                                <?= htmlspecialchars($sc->currency_code) . ' ' . number_format($sc->total_price, 2, '.', ','); ?>
                            </td>
                            <td class="text-left"><?= htmlspecialchars($sc->destination ?: '-'); ?></td>
                            <td>
                                <?php
                                    $status_class = 'secondary';
                                    if (in_array($sc->status, ['Submitted', 'Pending Endorsement'], true)) $status_class = 'warning';
                                    elseif ($sc->status == 'Endorsed')        $status_class = 'info';
                                    elseif ($sc->status == 'Open')            $status_class = 'primary';
                                    elseif ($sc->status == 'Finish')          $status_class = 'success';
                                    elseif ($sc->status == 'Closed')          $status_class = 'dark';
                                    elseif ($sc->status == 'Cancelled')       $status_class = 'danger';
                                    elseif ($sc->status == 'Rejected')        $status_class = 'danger';
                                ?>
                                <span class="badge badge-<?= $status_class; ?>"
                                      <?php if ($sc->status == 'Rejected' && !empty($sc->reject_reason)): ?>
                                      data-toggle="tooltip" title="<?= htmlspecialchars($sc->reject_reason); ?>"
                                      <?php endif; ?>>
                                    <?= htmlspecialchars($sc->status); ?>
                                </span>
                            </td>
                            
                            <td class="small text-left">
                                <?= htmlspecialchars($sc->approved_by_name ?: '-'); ?><br>
                                <span class="text-muted"><?= $sc->approved_at ? date('d M Y H:i', strtotime($sc->approved_at)) : ''; ?></span>
                            </td>
                            <td>
                                <a href="<?= base_url('sales_contract/detail/'.$sc->id.'?from=sales_contract/list_all'); ?>" class="btn btn-sm btn-secondary" title="<?= lang_line('sc_tooltip_view_detail'); ?>">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="11" class="text-center text-muted py-3"><?= lang_line('sc_no_data', 'Belum ada data Sales Order.'); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
