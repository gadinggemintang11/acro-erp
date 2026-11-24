<?php
$acc_options = function ($list, $selected) {
    $html = '';
    foreach ($list as $a) {
        $html .= '<option value="' . (int) $a->id . '"' . ((int) $selected === (int) $a->id ? ' selected' : '') . '>'
               . htmlspecialchars($a->account_code . ' - ' . $a->account_name) . '</option>';
    }
    return $html;
};
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($title); ?></h1>
        <a href="<?= base_url('appayment/view/' . $header->id); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> <?= lang_line('acct_btn_cancel', 'Batal'); ?></a>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <?php if (!empty($journal_no)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-undo mr-1"></i> Pembayaran hasil <strong>redraft</strong>. Jurnal <strong><?= htmlspecialchars($journal_no); ?></strong> berstatus Draft
            (tidak masuk laporan) dan akan diperbarui otomatis saat pembayaran diposting ulang.
        </div>
    <?php endif; ?>

    <form action="<?= base_url('appayment/update/' . $header->id); ?>" method="POST" id="payment-edit-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang_line('appay_form_header', 'Header Pembayaran'); ?></h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label><?= lang_line('appay_no_label', 'No. Pembayaran'); ?></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($header->payment_no); ?>" readonly>
                    </div>
                    <div class="col-md-3 form-group">
                        <label><?= lang_line('appay_date_label', 'Tgl. Bayar'); ?> *</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= htmlspecialchars($header->payment_date); ?>" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label><?= lang_line('ap_invoice_supplier', 'Supplier'); ?></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($header->nama_supplier); ?>" readonly>
                    </div>
                    <div class="col-md-3 form-group">
                        <label><?= lang_line('appay_notes_label', 'Catatan'); ?></label>
                        <input type="text" name="notes" class="form-control" value="<?= htmlspecialchars((string) $header->notes); ?>">
                    </div>
                </div>

                <hr class="my-3">
                <p class="font-weight-bold mb-3"><?= lang_line('appay_acct_section_title', 'Akun Jurnal'); ?></p>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label><?= lang_line('appay_debet_label', 'Akun DEBET (Hutang)'); ?></label>
                        <select id="debet_account_id" name="debet_account_id" class="form-control" required>
                            <option value=""><?= lang_line('acct_select_account_payable', '-- Pilih --'); ?></option>
                            <?= $acc_options($debet_accounts, $header->debet_account_id); ?>
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label><?= lang_line('appay_kredit_label', 'Akun KREDIT (Kas/Bank)'); ?></label>
                        <select id="kredit_account_id" name="kredit_account_id" class="form-control" required>
                            <option value=""><?= lang_line('acct_select_account_cash_bank', '-- Pilih --'); ?></option>
                            <?= $acc_options($kredit_accounts, $header->kredit_account_id); ?>
                        </select>
                    </div>
                </div>

                <hr class="my-3">
                <p class="font-weight-bold mb-3"><?= lang_line('appay_pph_section_title', 'Potong PPh'); ?></p>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label><?= lang_line('appay_pph_rate_label', 'Tarif PPh (%)'); ?></label>
                        <input type="number" id="pph_rate" name="pph_rate" class="form-control" value="<?= (float) $header->pph_rate; ?>" min="0" max="100" step="0.01">
                    </div>
                    <div class="col-md-3 form-group">
                        <label><?= lang_line('appay_pph_amount_label', 'Nominal PPh'); ?></label>
                        <input type="text" id="pph_amount" class="form-control text-right" readonly>
                    </div>
                    <div class="col-md-6 form-group">
                        <label><?= lang_line('appay_pph_acct_label', 'Akun Hutang PPh'); ?></label>
                        <select id="pph_account_id" name="pph_account_id" class="form-control">
                            <option value=""><?= lang_line('acct_select_account_pph', '-- Pilih --'); ?></option>
                            <?= $acc_options($pph_payable_accounts, $header->pph_account_id); ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('appay_invoice_detail_title', 'Faktur yang Dibayar'); ?></h6>
                <small class="text-muted d-block mt-1">Sisa hutang dihitung tanpa pembayaran ini, jadi nominalnya bisa diubah. Isi 0 untuk melepas faktur dari pembayaran ini.</small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th><?= lang_line('ap_invoice_no', 'No. Faktur'); ?></th>
                                <th><?= lang_line('ap_invoice_date', 'Tgl. Faktur'); ?></th>
                                <th class="text-right"><?= lang_line('acct_total_bill', 'Total Tagihan'); ?></th>
                                <th class="text-right"><?= lang_line('appay_th_paid_already', 'Sudah Dibayar'); ?></th>
                                <th class="text-right"><?= lang_line('acct_remaining_payable', 'Sisa Hutang'); ?></th>
                                <th style="width: 22%;"><?= lang_line('appay_th_pay_amount', 'Jumlah Bayar'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                            <?php if (empty($invoices)): ?>
                                <tr><td colspan="6" class="text-center text-muted"><?= lang_line('appay_no_unpaid_invoices', 'Tidak ada faktur yang bisa dibayar.'); ?></td></tr>
                            <?php else: foreach ($invoices as $inv): $sisa = max(0, (float) $inv->sisa_utang); ?>
                                <tr class="<?= $inv->amount_paid > 0 ? 'table-info' : ''; ?>">
                                    <td><a href="<?= base_url('apinvoice/view/' . $inv->id); ?>" target="_blank"><?= htmlspecialchars($inv->invoice_no); ?></a></td>
                                    <td><?= date('d-m-Y', strtotime($inv->invoice_date)); ?></td>
                                    <td class="text-right"><?= number_format((float) $inv->total_invoice, 2); ?></td>
                                    <td class="text-right"><?= number_format((float) $inv->total_paid, 2); ?></td>
                                    <td class="text-right"><?= number_format($sisa, 2); ?></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" name="details[<?= (int) $inv->id; ?>][amount_paid]" class="form-control text-right payment-amount"
                                                   value="<?= number_format((float) $inv->amount_paid, 2, '.', ''); ?>" min="0" max="<?= number_format($sisa, 2, '.', ''); ?>" step="0.01">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-primary btn-sm btn-pay-full" data-amount="<?= number_format($sisa, 2, '.', ''); ?>"><?= lang_line('appay_btn_full', 'Full'); ?></button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                        <tfoot class="bg-gray-200">
                            <tr>
                                <td colspan="5" class="text-right font-weight-bold"><?= lang_line('appay_total_payment', 'Total Pembayaran'); ?></td>
                                <td><input type="text" id="total_payment" class="form-control text-right font-weight-bold" readonly></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('appayment/view/' . $header->id); ?>" class="btn btn-secondary"><?= lang_line('acct_btn_cancel', 'Batal'); ?></a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan (Draft)</button>
        </div>
    </form>
</div>

<script>
$(function () {
    if ($.fn.select2) $('#debet_account_id, #kredit_account_id, #pph_account_id').select2({ width: '100%', placeholder: '-- Pilih --' });

    function calc() {
        var total = 0;
        $('.payment-amount').each(function () { total += parseFloat($(this).val()) || 0; });
        $('#total_payment').val(total.toFixed(2));
        var rate = parseFloat($('#pph_rate').val()) || 0;
        $('#pph_amount').val((total * rate / 100).toFixed(2));
        if (rate > 0) {
            $('#pph_account_id').prop('disabled', false).prop('required', true).trigger('change.select2');
        } else {
            $('#pph_account_id').prop('disabled', true).prop('required', false).val('').trigger('change.select2');
        }
    }
    $('#detail-body').on('input', '.payment-amount', calc);
    $('#detail-body').on('click', '.btn-pay-full', function () {
        $(this).closest('tr').find('.payment-amount').val($(this).data('amount')).trigger('input');
    });
    $('#pph_rate').on('input change', calc);
    $('#payment-edit-form').on('submit', function () {
        if ((parseFloat($('#total_payment').val()) || 0) <= 0) { alert('Total pembayaran harus lebih dari 0.'); return false; }
        return confirm('Simpan perubahan pembayaran? Status tetap Draft sampai Anda klik Posting.');
    });
    calc();
});
</script>
