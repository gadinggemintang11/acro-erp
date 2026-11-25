<?php
// Form ini dipakai juga utk EDIT transaksi Draft (CashBank::edit mengirim $header + $details).
$is_edit = !empty($header);
$h_date  = $is_edit ? $header->doc_date : date('Y-m-d');
$h_type  = $is_edit ? $header->transaction_type : 'OUT';
$h_curr  = $is_edit ? $header->currency : 'IDR';
$h_rate  = $is_edit ? (float) $header->exchange_rate : 1;
$h_desc  = $is_edit ? (string) $header->description : '';
?>
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <?php if ($is_edit && !empty($journal_no)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-undo mr-1"></i> Transaksi hasil <strong>redraft</strong>. Jurnal <strong><?= htmlspecialchars($journal_no); ?></strong> berstatus Draft
            (tidak masuk laporan) dan akan diperbarui otomatis saat transaksi diposting ulang.
        </div>
    <?php endif; ?>

    <form action="<?= base_url($is_edit ? 'cashbank/update/' . (int) $header->id : 'cashbank/store'); ?>" method="POST" id="cb-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('cb_header_section', 'Header Transaksi') ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('cb_lbl_doc_no', 'No. Dokumen') ?></label>
                            <input type="text" name="doc_no" class="form-control" value="<?= $doc_no; ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('cb_lbl_date', 'Tanggal *') ?></label>
                            <input type="date" name="doc_date" class="form-control" value="<?= htmlspecialchars($h_date); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('cb_lbl_trans_type', 'Tipe Transaksi *') ?></label>
                            <select id="transaction_type" name="transaction_type" class="form-control" required>
                                <option value="OUT" <?= $h_type === 'OUT' ? 'selected' : ''; ?>><?= lang_line('cb_opt_out', 'OUT (Kas/Bank Keluar)') ?></option>
                                <option value="IN" <?= $h_type === 'IN' ? 'selected' : ''; ?>><?= lang_line('cb_opt_in', 'IN (Kas/Bank Masuk)') ?></option>
                            </select>
                        </div>
                    </div>
                     <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('cb_lbl_currency', 'Mata Uang *') ?></label>
                            <select name="currency" id="currency" class="form-control" required>
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>" <?= set_select('currency', $curr->code, $curr->code == $h_curr); ?>>
                                        <?= $curr->code; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('cb_lbl_rate', 'Kurs (Rate) *') ?></label>
                            <input type="number" id="exchange_rate" name="exchange_rate" class="form-control"
                                   value="<?= number_format($h_rate, 4, '.', ''); ?>" min="0.0001" step="0.0001" <?= $h_curr === 'IDR' ? 'readonly' : ''; ?> required>
                            <small id="kurs_info" class="form-text text-muted"></small>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label><?= lang_line('cb_lbl_cashbank', 'Akun Kas/Bank *') ?></label>
                            <select id="cash_bank_account_id" name="cash_bank_account_id" class="form-control select2-account" required>
                                <option value=""><?= lang_line('cb_opt_select_currency_first', '-- Pilih Mata Uang Dulu --') ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('cb_lbl_desc_header', 'Keterangan Header *') ?></label>
                            <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($h_desc); ?>"
                                   placeholder="<?= lang_line('cb_ph_desc_example', 'cth: Pembayaran Operasional Bulan Nov') ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><?= sprintf(lang_line('cb_section_details_curr_only', 'Detail Akun Lawan (Hanya akun %s)'), '<span class="currency-symbol-text">IDR</span>') ?></h6>
                <button type="button" id="add-row" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= lang_line('cb_btn_add_row', 'Tambah Baris') ?></button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="detail-table">
                        <thead>
                            <tr>
                                <th style="width: 35%;"><?= lang_line('cb_th_counter_account', 'Akun Lawan *') ?></th>
                                <th style="width: 25%;"><?= lang_line('cb_th_amount_required', 'Jumlah *') ?></th>
                                <th><?= lang_line('cb_th_desc', 'Deskripsi') ?></th>
                                <th style="width: 5%;"><?= lang_line('cb_th_action', 'Aksi') ?></th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                            </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th class="text-right"><?= lang_line('cb_total_label', 'TOTAL') ?> (<span class="currency-symbol-text">IDR</span>)</th>
                                <th><input type="text" id="total_amount" class="form-control text-right font-weight-bold input-currency" readonly></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url($is_edit ? 'cashbank/view/' . (int) $header->id : 'cashbank'); ?>" class="btn btn-secondary"><?= lang_line('cb_btn_back', 'Kembali') ?></a>
            <button type="submit" class="btn btn-primary"><?= $is_edit ? lang_line('cb_btn_save_changes', 'Simpan Perubahan (Draft)') : lang_line('cb_btn_save_draft', 'Simpan Transaksi (Draft)') ?></button>
        </div>
    </form>
</div>

<template id="row-template">
    <tr>
        <td>
            <select name="details[0][account_id]" class="form-control account-select select2-account" required>
                <option value=""><?= lang_line('cb_opt_select_account', '-- Pilih Akun --') ?></option>
                <?php foreach($accounts as $acc): ?>
                    <option value="<?= $acc->id; ?>" data-currency="<?= $acc->currency; ?>">
                        <?= $acc->account_code; ?> - <?= $acc->account_name; ?> (<?= $acc->currency; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text currency-symbol">Rp</span>
                </div>
                <input type="text" name="details[0][amount]" class="form-control amount-input input-currency text-right" value="0" required>
            </div>
        </td>
        <td><input type="text" name="details[0][description]" class="form-control"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css" rel="stylesheet" />


<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const I18N_CB = {
    select_account:           <?= json_encode(lang_line('cb_opt_select_account', '-- Pilih Akun --')) ?>,
    loading:                  <?= json_encode(lang_line('cb_js_loading', 'Memuat...')) ?>,
    no_cashbank_for_curr_tpl: <?= json_encode(lang_line('cb_js_no_cashbank_for_curr', 'Tidak ada Akun Kas/Bank %s')) ?>,
    detail_section_filter_tpl:<?= json_encode(lang_line('cb_js_detail_section_filter', 'Detail Akun Lawan (Hanya akun <span class="font-weight-bold">%s</span> yang tampil)')) ?>,
    fetching_kurs:            <?= json_encode(lang_line('cb_js_fetching_kurs', 'Mengambil kurs pajak terbaru...')) ?>,
    kurs_auto_tpl:            <?= json_encode(lang_line('cb_js_kurs_auto', 'Auto dari Kurs Pajak (%s s/d %s). Boleh diubah manual.')) ?>,
    kurs_unavailable:         <?= json_encode(lang_line('cb_js_kurs_unavailable', 'Kurs tidak tersedia, isi manual.')) ?>,
    kurs_failed:              <?= json_encode(lang_line('cb_js_kurs_failed', 'Gagal mengambil kurs, isi manual.')) ?>
};

// Mode edit: data tersimpan utk mengisi ulang form (null = transaksi baru).
const CB_INITIAL = <?= $is_edit ? json_encode([
    'cash_bank_account_id' => (int) $header->cash_bank_account_id,
    'exchange_rate'        => (float) $header->exchange_rate,
    'rows'                 => array_map(function ($d) { return ['account_id' => (int) $d->account_id, 'amount' => (float) $d->amount, 'description' => (string) $d->description]; }, $details ?? []),
]) : 'null'; ?>;

$(document).ready(function() {
    let rowIndex = 0;
    let initializing = true;
    const detailBody = document.getElementById("detail-body");
    const template = document.getElementById("row-template");
    const currencySelect = document.getElementById("currency");
    const rateInput = document.getElementById("exchange_rate");
    const detailHeader = document.querySelector('.card-header h6.text-primary');

    const autoNumericOptions = { decimalCharacter: '.', digitGroupSeparator: ',', decimalPlaces: 2, minimumValue: "0" };

    // Simpan master list akun di memory
    const masterAccountList = Array.from(template.content.querySelector(".account-select").options);

    // ===================================
    // INISIALISASI PLUGIN
    // ===================================

    function initAutoNumeric(elements) {
        $(elements).each(function() {
            if (AutoNumeric.getAutoNumericElement(this)) AutoNumeric.getAutoNumericElement(this).remove();
            new AutoNumeric(this, autoNumericOptions);
        });
        if (document.querySelector('#total_amount')) {
            if (AutoNumeric.getAutoNumericElement('#total_amount')) AutoNumeric.getAutoNumericElement('#total_amount').remove();
            new AutoNumeric('#total_amount', autoNumericOptions);
        }
    }

    function initSelect2(elements) {
        $(elements).select2({
            theme: 'bootstrap4',
            width: '100%',
            placeholder: I18N_CB.select_account,
            allowClear: true
        });
    }

    // ===================================
    // FUNGSI UTAMA
    // ===================================

    function getCurrencySymbol(code) {
        switch(code) {
            case 'USD': return '$';
            case 'RMB': return '¥';
            default: return 'Rp';
        }
    }

    function addRow() {
        const newRow = template.content.cloneNode(true);
        newRow.querySelectorAll("input, select").forEach(el => {
            el.name = el.name.replace(/details\[0\]/g, `details[${rowIndex}]`);
        });
        const symbol = getCurrencySymbol(currencySelect.value);
        newRow.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);
        // Ambil <tr>-nya SEBELUM append: setelah appendChild, DocumentFragment kosong,
        // jadi init di fragment (kode lama) tidak pernah mengenai baris baru --
        // baris ke-2 dst tidak terformat & tidak ikut total.
        const tr = newRow.querySelector('tr');
        detailBody.appendChild(newRow);

        initAutoNumeric(tr.querySelectorAll('.input-currency'));
        initSelect2(tr.querySelectorAll('.select2-account'));

        filterAccounts(currencySelect.value);
        rowIndex++;
    }

    $('#add-row').click(function () { addRow(); });
    if (CB_INITIAL && CB_INITIAL.rows.length) {
        CB_INITIAL.rows.forEach(function (r) {
            addRow();
            const tr = detailBody.querySelector('tr:last-child');
            $(tr).find('.account-select').val(String(r.account_id)).trigger('change');
            AutoNumeric.getAutoNumericElement(tr.querySelector('.amount-input')).set(r.amount);
            tr.querySelector('input[name$="[description]"]').value = r.description || '';
        });
    } else {
        addRow();
    }

    detailBody.addEventListener("click", function(e) {
        if (e.target.closest(".remove-row")) {
            $(e.target.closest("tr")).find('.select2-account').select2('destroy');
            e.target.closest("tr").remove();
            calcTotal();
        }
    });

    detailBody.addEventListener("input", function(e) {
        if (e.target.classList.contains("amount-input")) calcTotal();
    });

    rateInput.addEventListener("input", calcTotal);

    function calcTotal() {
        let total = 0;
        detailBody.querySelectorAll("tr").forEach(row => {
            const el = row.querySelector(".amount-input");
            total += AutoNumeric.getAutoNumericElement(el)?.getNumber() || 0;
        });
        AutoNumeric.getAutoNumericElement('#total_amount')?.set(total);
    }

    // Load Akun Kas/Bank (Header)
    function loadCashBankAccounts(currency, selectedId) {
        const accSelect = $('#cash_bank_account_id');
        accSelect.html('<option value="">' + I18N_CB.loading + '</option>').trigger('change');

        $.getJSON("<?= base_url('cashbank/ajax_get_cashbank_accounts'); ?>", { currency }, function(res) {
            accSelect.html('<option value="">' + I18N_CB.select_account + '</option>');
            if (res.success && res.data.length > 0) {
                res.data.forEach(acc => {
                    accSelect.append(new Option(`${acc.account_code} - ${acc.account_name}`, acc.id));
                });
                if (selectedId) accSelect.val(String(selectedId));
            } else {
                accSelect.html('<option value="">' + I18N_CB.no_cashbank_for_curr_tpl.replace('%s', currency) + '</option>');
            }
            initSelect2(accSelect);
        });
    }

    // Filter Akun Lawan (Detail)
    function filterAccounts(currency) {
        const selects = detailBody.querySelectorAll(".account-select");
        selects.forEach(select => {
            const selectedValue = select.value;
            select.innerHTML = '';
            select.appendChild(new Option(I18N_CB.select_account, ''));

            masterAccountList.forEach(opt => {
                if (opt.value && opt.dataset.currency === currency) {
                    select.appendChild(opt.cloneNode(true));
                }
            });
            select.value = selectedValue;
            $(select).trigger('change.select2');
        });
    }

    currencySelect.addEventListener("change", function() {
        const curr = this.value;
        const symbol = getCurrencySymbol(curr);

        $('.currency-symbol').text(symbol);
        $('.currency-symbol-text').text(curr);

        const detailCardHeader = document.querySelector('#detail-table').closest('.card').querySelector('.card-header h6');
        if (detailCardHeader) {
             detailCardHeader.innerHTML = I18N_CB.detail_section_filter_tpl.replace('%s', curr);
        }

        // Mode edit, saat halaman dibuka: pertahankan kurs & akun Kas/Bank tersimpan.
        const keepSaved = initializing && CB_INITIAL;
        if (curr === 'IDR') {
            rateInput.value = '1.0000'; rateInput.readOnly = true;
            $('#kurs_info').html('');
        } else if (keepSaved) {
            rateInput.value = CB_INITIAL.exchange_rate; rateInput.readOnly = false;
            $('#kurs_info').html('');
        } else {
            rateInput.value = ''; rateInput.readOnly = false;
            $('#kurs_info').removeClass('text-success text-danger').addClass('text-muted')
                .html('<i class="fas fa-spinner fa-spin"></i> ' + I18N_CB.fetching_kurs);
            $.getJSON('<?= base_url('kurs_pajak/ajax_get_rate'); ?>', { currency: curr })
                .done(function(res) {
                    if (res.ok && res.rate) {
                        rateInput.value = res.rate;
                        const msg = I18N_CB.kurs_auto_tpl
                            .replace('%s', res.effective_date_start)
                            .replace('%s', res.effective_date_end);
                        $('#kurs_info').removeClass('text-muted text-danger').addClass('text-success')
                            .html('<i class="fas fa-check-circle"></i> ' + msg);
                        calcTotal();
                    } else {
                        $('#kurs_info').removeClass('text-muted text-success').addClass('text-danger')
                            .html('<i class="fas fa-exclamation-triangle"></i> ' + (res.message || I18N_CB.kurs_unavailable));
                    }
                })
                .fail(function() {
                    $('#kurs_info').removeClass('text-muted text-success').addClass('text-danger')
                        .html('<i class="fas fa-exclamation-triangle"></i> ' + I18N_CB.kurs_failed);
                });
        }

        loadCashBankAccounts(curr, keepSaved ? CB_INITIAL.cash_bank_account_id : null);
        filterAccounts(curr);
        calcTotal();
    });

    // Unformat sebelum submit
    $('#cb-form').on('submit', function() {
        $('.input-currency').each(function() {
            const an = AutoNumeric.getAutoNumericElement(this);
            if (an) $(this).val(an.getNumber());
        });
        return true;
    });

    // Inisialisasi awal
    initAutoNumeric(document.querySelectorAll('.input-currency'));
    initSelect2($('.select2-account'));
    currencySelect.dispatchEvent(new Event('change'));
    initializing = false;
    calcTotal();
});
</script>
