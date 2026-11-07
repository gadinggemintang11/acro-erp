<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('appayment/store'); ?>" method="POST" id="payment-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('appay_form_header'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('appay_no_label'); ?></label>
                            <input type="text" name="payment_no" class="form-control" value="<?= $payment_no; ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('appay_date_label'); ?></label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ap_invoice_supplier'); ?> *</label>
                            <select id="supplier_id" name="supplier_id" class="form-control" required>
                                <option value=""><?= lang_line('acct_select_supplier'); ?></option>
                                <?php foreach($suppliers as $s): ?>
                                    <option value="<?= $s->id; ?>"><?= $s->nama_supplier; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('appay_notes_label'); ?></label>
                            <input type="text" name="notes" class="form-control" placeholder="<?= htmlspecialchars(lang_line('appay_notes_placeholder')); ?>">
                        </div>
                    </div>
                </div>

                <!-- ✨ NEW: Header-level account selection -->
                <hr class="my-3">
                <p class="font-weight-bold mb-3"><?= lang_line('appay_acct_section_title'); ?></p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('appay_debet_label'); ?></label>
                            <select id="debet_account_id" name="debet_account_id" class="form-control" required>
                                <option value=""><?= lang_line('acct_select_account_payable'); ?></option>
                                <?php foreach($debet_accounts as $acc): ?>
                                    <option value="<?= $acc->id; ?>"><?= $acc->account_code; ?> - <?= $acc->account_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?= lang_line('appay_debet_filter'); ?></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('appay_kredit_label'); ?></label>
                            <select id="kredit_account_id" name="kredit_account_id" class="form-control" required>
                                <option value=""><?= lang_line('acct_select_account_cash_bank'); ?></option>
                                <?php foreach($kredit_accounts as $acc): ?>
                                    <option value="<?= $acc->id; ?>"><?= $acc->account_code; ?> - <?= $acc->account_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?= lang_line('appay_kredit_filter'); ?></small>
                        </div>
                    </div>
                </div>

                <!-- ✨ NEW: PPh Withholding Tax Section -->
                <hr class="my-3">
                <p class="font-weight-bold mb-3"><?= lang_line('appay_pph_section_title'); ?></p>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('appay_pph_rate_label'); ?></label>
                            <input type="number" id="pph_rate" name="pph_rate" class="form-control"
                                   value="0" min="0" max="100" step="0.01" placeholder="0">
                            <small class="form-text text-muted"><?= lang_line('appay_pph_rate_help'); ?></small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('appay_pph_amount_label'); ?></label>
                            <input type="text" id="pph_amount" class="form-control text-right" readonly>
                            <small class="form-text text-muted"><?= lang_line('appay_pph_amount_help'); ?></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label><?= lang_line('appay_pph_acct_label'); ?></label>
                            <select id="pph_account_id" name="pph_account_id" class="form-control">
                                <option value=""><?= lang_line('acct_select_account_pph'); ?></option>
                                <?php foreach($pph_payable_accounts as $acc): ?>
                                    <option value="<?= $acc->id; ?>"><?= $acc->account_code; ?> - <?= $acc->account_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted"><?= lang_line('appay_pph_acct_help'); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('appay_invoice_detail_title'); ?></h6>
                <small class="text-muted d-block mt-2"><?= lang_line('appay_invoice_detail_note'); ?></small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="invoice-table">
                        <thead class="thead-light">
                            <tr>
                                <th><?= lang_line('ap_invoice_no'); ?></th>
                                <th><?= lang_line('ap_invoice_date'); ?></th>
                                <th class="text-right"><?= lang_line('acct_total_bill'); ?></th>
                                <th class="text-right"><?= lang_line('appay_th_paid_already'); ?></th>
                                <th class="text-right"><?= lang_line('acct_remaining_payable'); ?></th>
                                <th style="width: 20%;"><?= lang_line('appay_th_pay_amount'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                            <tr>
                                <td colspan="6" class="text-center"><?= lang_line('appay_select_supplier_to_load'); ?></td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-200">
                            <tr>
                                <td colspan="5" class="text-right font-weight-bold"><?= lang_line('appay_total_payment'); ?></td>
                                <td>
                                    <input type="text" id="total_payment" class="form-control text-right font-weight-bold" readonly>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('appayment'); ?>" class="btn btn-secondary"><?= lang_line('acct_btn_cancel'); ?></a>
            <button type="submit" class="btn btn-primary"><?= lang_line('appay_btn_save_draft'); ?></button>
        </div>
    </form>
</div>

<script>
const I18N_APPAY = {
    loading:                  <?= json_encode(lang_line('acct_loading')); ?>,
    select_supplier_to_load:  <?= json_encode(lang_line('appay_select_supplier_to_load')); ?>,
    no_unpaid_invoices:       <?= json_encode(lang_line('appay_no_unpaid_invoices')); ?>,
    err_load_data:            <?= json_encode(lang_line('appay_err_load_data')); ?>,
    btn_full:                 <?= json_encode(lang_line('appay_btn_full')); ?>
};

$(document).ready(function() {

    // Aktifkan Select2 agar pemilihan akun & supplier mudah dicari
    $('#supplier_id, #debet_account_id, #kredit_account_id, #pph_account_id').select2({
        width: '100%',
        placeholder: '-- Pilih --'
    });

    // Saat supplier diganti
    $('#supplier_id').change(function() {
        var supplierId = $(this).val();
        var tbody = $('#detail-body');

        if (!supplierId) {
            tbody.html('<tr><td colspan="6" class="text-center">' + I18N_APPAY.select_supplier_to_load + '</td></tr>');
            return;
        }

        tbody.html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> ' + I18N_APPAY.loading + '</td></tr>');

        $.ajax({
            url: "<?= base_url('appayment/ajax_get_unpaid_invoices'); ?>",
            type: "GET",
            data: { supplier_id: supplierId },
            dataType: "json",
            success: function(response) {
                tbody.html('');
                if (response.success && response.data.length > 0) {
                    response.data.forEach(function(inv) {
                        var sisaUtang = parseFloat(inv.sisa_utang).toFixed(2);

                        var row = `
                            <tr>
                                <td>${inv.invoice_no}</td>
                                <td>${inv.invoice_date}</td>
                                <td class="text-right">${parseFloat(inv.total_invoice).toFixed(2)}</td>
                                <td class="text-right">${parseFloat(inv.total_paid).toFixed(2)}</td>
                                <td class="text-right">${sisaUtang}</td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" name="details[${inv.id}][amount_paid]"
                                               class="form-control text-right payment-amount"
                                               value="0" min="0" max="${sisaUtang}" step="0.01" required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary btn-sm btn-pay-full" data-amount="${sisaUtang}">${I18N_APPAY.btn_full}</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                } else {
                    tbody.html('<tr><td colspan="6" class="text-center">' + I18N_APPAY.no_unpaid_invoices + '</td></tr>');
                }
                calculateTotal();
            },
            error: function() {
                tbody.html('<tr><td colspan="6" class="text-center text-danger">' + I18N_APPAY.err_load_data + '</td></tr>');
            }
        });
    });

    // Kalkulasi total
    $('#detail-body').on('input', '.payment-amount', calculateTotal);

    // ✨ NEW: Kalkulasi PPh amount saat tarif berubah
    $('#pph_rate').on('change', calculatePPh);

    // Tombol "Full"
    $('#detail-body').on('click', '.btn-pay-full', function() {
        var amount = $(this).data('amount');
        $(this).closest('tr').find('.payment-amount').val(amount).trigger('input');
    });

    function calculateTotal() {
        var total = 0;
        $('.payment-amount').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#total_payment').val(total.toFixed(2));
        calculatePPh();
    }

    // ✨ NEW: Fungsi hitung PPh
    function calculatePPh() {
        var total = parseFloat($('#total_payment').val()) || 0;
        var pph_rate = parseFloat($('#pph_rate').val()) || 0;
        var pph_amount = total * (pph_rate / 100);
        $('#pph_amount').val(pph_amount.toFixed(2));

        if (pph_rate > 0) {
            $('#pph_account_id').prop('disabled', false).prop('required', true).trigger('change.select2');
        } else {
            $('#pph_account_id').prop('disabled', true).prop('required', false).val('').trigger('change.select2');
        }
    }
});
</script>
