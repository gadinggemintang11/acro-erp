<div class="container-fluid">
    <h3 class="h4 mb-0 text-gray-800"><i class="fas fa-edit text-warning mr-2"></i><?= lang_line('fgr_e_title'); ?></h3>
    <form action="<?= base_url('fg_receipt/update/' . $receipt_header->id); ?>" method="POST" id="form-fg-receipt">
        <div class="card shadow mb-4">
             <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-warning"><?= lang_line('fgr_e_card_info'); ?></h6></div>
             <div class="card-body">
                 <div class="row">
                     <div class="col-md-3 form-group">
                         <label><?= lang_line('fgr_e_lbl_no'); ?></label>
                         <input type="text" class="form-control" value="<?= htmlspecialchars($receipt_header->receipt_number); ?>" readonly>
                     </div>
                     <div class="col-md-3 form-group">
                         <label for="receipt_date"><?= lang_line('fgr_e_lbl_date'); ?> <span class="text-danger">*</span></label>
                         <input type="date" name="receipt_date" id="receipt_date" class="form-control" value="<?= htmlspecialchars($receipt_header->receipt_date); ?>" required>
                     </div>
                     <div class="col-md-3 form-group">
                         <label><?= lang_line('fgr_e_lbl_fg_model'); ?></label>
                         <input type="text" class="form-control" value="<?= htmlspecialchars(($receipt_header->fg_code ?? '') . ' - ' . ($receipt_header->fg_name ?? '-')); ?>" readonly>
                     </div>
                     <div class="col-md-3 form-group">
                         <label for="remarks"><?= lang_line('fgr_e_lbl_remarks'); ?></label>
                         <textarea name="remarks" id="remarks" class="form-control" rows="1"><?= htmlspecialchars($receipt_header->remarks); ?></textarea>
                     </div>
                 </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-warning"><?= lang_line('fgr_e_card_items'); ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="receipt-items-table" width="100%">
                        <thead class="thead-light">
                             <tr>
                                <th><?= lang_line('fgr_e_th_wo'); ?></th>
                                <th><?= lang_line('fgr_e_th_ipo'); ?></th>
                                <th><?= lang_line('fgr_e_th_prod'); ?></th>
                                <th><?= lang_line('fgr_e_th_fg_item'); ?></th>
                                <th width="13%"><?= lang_line('fgr_e_th_qty_recv'); ?> <span class="text-danger">*</span></th>
                                <th width="8%"><?= lang_line('fgr_e_th_unit'); ?></th>
                                <th><?= lang_line('fgr_e_th_notes'); ?></th>
                                <th width="50"><?= lang_line('fgr_e_th_action'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $itemIndex = 0; foreach($receipt_details as $detail): ?>
                            <tr>
                                <td class="align-middle small"><?= htmlspecialchars($detail->wo_number ?: '-'); ?></td>
                                <td class="align-middle small"><?= htmlspecialchars($detail->ipo_number ?: '-'); ?></td>
                                <td class="align-middle small"><?= htmlspecialchars($detail->production_number ?: '-'); ?></td>
                                <td class="align-middle">
                                    <?= htmlspecialchars($detail->fg_name); ?> (<?= htmlspecialchars($detail->fg_code); ?>)
                                    <input type="hidden" name="details[<?= $itemIndex ?>][prod_output_id]"       value="<?= (int)$detail->prod_output_id; ?>">
                                    <input type="hidden" name="details[<?= $itemIndex ?>][wo_header_id]"         value="<?= (int)($detail->row_wo_id ?: 0); ?>">
                                    <input type="hidden" name="details[<?= $itemIndex ?>][production_header_id]" value="<?= (int)($detail->row_production_header_id ?: 0); ?>">
                                    <input type="hidden" name="details[<?= $itemIndex ?>][sales_contract_id]"    value="<?= (int)($detail->row_sales_contract_id ?: 0); ?>">
                                    <input type="hidden" name="details[<?= $itemIndex ?>][fg_id]"                value="<?= (int)$detail->fg_id; ?>">
                                    <input type="hidden" name="details[<?= $itemIndex ?>][uom]"                  value="<?= htmlspecialchars($detail->uom ?: $detail->fg_uom); ?>">
                                </td>
                                <td>
                                    <input type="number" step="0.0001" min="0.0001"
                                           name="details[<?= $itemIndex ?>][qty_received]"
                                           class="form-control form-control-sm text-right item-qty-received"
                                           required
                                           value="<?= (float)$detail->qty_received; ?>">
                                </td>
                                <td class="align-middle text-center"><?= htmlspecialchars($detail->uom ?: $detail->fg_uom); ?></td>
                                <td>
                                    <input type="text" name="details[<?= $itemIndex ?>][notes]" class="form-control form-control-sm" value="<?= htmlspecialchars($detail->notes); ?>">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-danger btn-sm remove-item-row" title="<?= lang_line('fgr_e_tt_remove'); ?>"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php $itemIndex++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-right">
                <button type="submit" class="btn btn-warning btn-load"><i class="fas fa-save mr-1"></i> <?= lang_line('fgr_e_btn_update'); ?></button>
                <a href="<?= base_url('fg_receipt/show/' . $receipt_header->id); ?>" class="btn btn-secondary"><?= lang_line('fgr_e_btn_cancel'); ?></a>
            </div>
        </div>
    </form>
</div>
<script>
const I18N_FGR_E = {
  info_title:   <?= json_encode(lang_line('fgr_e_swal_info_title'), JSON_UNESCAPED_UNICODE); ?>,
  min_item_msg: <?= json_encode(lang_line('fgr_e_min_item_msg'), JSON_UNESCAPED_UNICODE); ?>
};
$(document).ready(function() {
    $('#receipt-items-table').on('click', '.remove-item-row', function() {
        if ($('#receipt-items-table tbody tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            Swal.fire(I18N_FGR_E.info_title, I18N_FGR_E.min_item_msg, 'info');
        }
    });
});
</script>
