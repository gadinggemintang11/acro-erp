<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-clipboard-list text-warning mr-2"></i>
                <?= lang_line('ost_detail_title_mat'); ?>
            </h3>
            <small class="text-muted"><?= lang_line('ost_detail_subtitle_mat'); ?></small>
        </div>
        <a href="<?= base_url('material_outstanding') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> <?= lang_line('ost_btn_back'); ?>
        </a>
    </div>

    <?php if ($this->session->flashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $this->session->flashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4 border-warning">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <small class="text-muted d-block"><?= lang_line('ost_th_code'); ?></small>
                    <strong class="text-primary"><?= htmlspecialchars($item_code) ?></strong>
                </div>
                <div class="col-md-4">
                    <small class="text-muted d-block"><?= lang_line('ost_th_item_name'); ?></small>
                    <strong><?= htmlspecialchars($item_name) ?></strong>
                </div>
                <div class="col-md-1">
                    <small class="text-muted d-block"><?= lang_line('ost_th_type'); ?></small>
                    <strong><?= htmlspecialchars($item_type) ?></strong>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block"><?= lang_line('ost_th_unit'); ?></small>
                    <strong><?= htmlspecialchars($item_unit ?? '-') ?></strong>
                </div>
                <div class="col-md-2 text-right">
                    <small class="text-muted d-block"><?= lang_line('ost_th_total_outstanding'); ?></small>
                    <span class="badge badge-warning" style="font-size: 1rem;">
                        <?= number_format((float)$total_outstanding, 2) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="<?= base_url('material_outstanding/create_pr') ?>" id="formCreatePR">
        <input type="hidden" name="redirect_to" value="<?= base_url('material_outstanding/detail/' . $item_id . '/' . $item_type) ?>">

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('ost_card_breakdown_mat'); ?></h6>
                <div>
                    <?php if (check_access('purchase_request', 'add')): ?>
                    <button type="submit" class="btn btn-success btn-sm" id="btnCreatePR" disabled>
                        <i class="fas fa-shopping-cart mr-1"></i> <?= lang_line('ost_btn_create_pr'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light text-center">
                            <tr>
                                <th width="40">
                                    <input type="checkbox" id="checkAll" title="<?= lang_line('ost_check_all'); ?>">
                                </th>
                                <th><?= lang_line('ost_th_no_irf'); ?></th>
                                <th><?= lang_line('ost_th_date'); ?></th>
                                <th><?= lang_line('ost_th_department'); ?></th>
                                <th><?= lang_line('ost_th_requestor'); ?></th>
                                <th><?= lang_line('ost_th_status'); ?></th>
                                <th class="text-right"><?= lang_line('ost_th_qty_request'); ?></th>
                                <th class="text-right"><?= lang_line('ost_th_qty_issued'); ?></th>
                                <th class="text-right"><?= lang_line('ost_th_outstanding'); ?></th>
                                <th width="80"><?= lang_line('ost_th_action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="text-center align-middle">
                                    <input type="checkbox" name="mr_detail_ids[]" value="<?= (int)$r->mr_detail_id ?>"
                                           class="row-check" data-qty="<?= (float)$r->qty_outstanding ?>">
                                </td>
                                <td><strong class="text-primary"><?= htmlspecialchars($r->request_number) ?></strong></td>
                                <td><?= date('d M Y', strtotime($r->request_date)) ?></td>
                                <td><?= htmlspecialchars($r->department ?? '-') ?></td>
                                <td><?= htmlspecialchars($r->requestor_name ?? '-') ?></td>
                                <td>
                                    <?php
                                    $badge = ['Submitted' => 'info', 'Partial Issued' => 'warning', 'Issued' => 'success'][$r->mr_status] ?? 'secondary';
                                    ?>
                                    <span class="badge badge-<?= $badge ?>"><?= htmlspecialchars($r->mr_status) ?></span>
                                </td>
                                <td class="text-right"><?= number_format((float)$r->qty_requested, 2) ?></td>
                                <td class="text-right"><?= number_format((float)$r->qty_issued, 2) ?></td>
                                <td class="text-right">
                                    <strong class="text-warning"><?= number_format((float)$r->qty_outstanding, 2) ?></strong>
                                </td>
                                <td class="text-center">
                                    <a href="<?= base_url('material_request/detail/' . $r->mr_header_id) ?>"
                                       class="btn btn-sm btn-outline-info" title="<?= lang_line('ost_view_irf_title'); ?>">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="8" class="text-right"><strong><?= lang_line('ost_total_selected'); ?></strong></td>
                                <td class="text-right"><strong id="selectedTotal" class="text-warning">0.00</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <small class="text-muted mt-2 d-block">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= lang_line('ost_create_pr_info'); ?>
                </small>
            </div>
        </div>
    </form>
</div>

<script>
const I18N_OST = {
    alert_pick_one: <?= json_encode(lang_line('ost_alert_pick_one')); ?>,
    confirm_create: <?= json_encode(lang_line('ost_confirm_create')); ?>
};
$(document).ready(function() {
    function updateTotal() {
        var total = 0;
        var anyChecked = false;
        $('.row-check:checked').each(function() {
            total += parseFloat($(this).data('qty') || 0);
            anyChecked = true;
        });
        $('#selectedTotal').text(total.toFixed(2));
        $('#btnCreatePR').prop('disabled', !anyChecked);
    }

    $('#checkAll').on('change', function() {
        $('.row-check').prop('checked', $(this).prop('checked'));
        updateTotal();
    });

    $('.row-check').on('change', function() {
        if (!$(this).prop('checked')) {
            $('#checkAll').prop('checked', false);
        }
        updateTotal();
    });

    $('#formCreatePR').on('submit', function(e) {
        const count = $('.row-check:checked').length;
        if (count === 0) {
            e.preventDefault();
            alert(I18N_OST.alert_pick_one);
            return false;
        }
        if (!confirm(I18N_OST.confirm_create.replace('%d', count))) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
