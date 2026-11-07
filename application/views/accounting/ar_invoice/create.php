<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('arinvoice/store'); ?>" method="POST" id="ar-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('arinv_form_header'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('arinv_customer_label'); ?></label>
                            <select id="customer_id" name="customer_id" class="form-control" required>
                                <option value=""><?= lang_line('acct_select_customer'); ?></option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?= $c->id; ?>"><?= $c->nama_customer; ?> (<?= $c->jenis; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('arinv_label_source'); ?></label>
                            <select id="source_type" name="source_type" class="form-control" required>
                                <option value=""><?= lang_line('arinv_select_source'); ?></option>
                                <option value="FG_DELIVERY"><?= lang_line('arinv_source_fg'); ?></option>
                                <option value="SCRAP_OUT"><?= lang_line('arinv_source_scrap'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?= lang_line('ap_invoice_date'); ?> *</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label><?= str_replace(':', '', lang_line('acct_due_date_colon')); ?> *</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('arinv_label_currency'); ?></label>
                            <input type="text" id="currency_text" class="form-control" value="IDR" readonly>
                            <input type="hidden" id="currency" name="currency" value="IDR">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('arinv_label_rate'); ?></label>
                            <input type="number" id="exchange_rate" name="exchange_rate" class="form-control"
                                   value="1.0000" min="0.0001" step="0.0001" readonly required>
                            <small id="kurs_info" class="form-text text-muted"></small>
                        </div>
                    </div>
                 </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('arinv_label_source_doc'); ?></h6>
            </div>
            <div class="card-body">
                <div id="doc-list-container" style="display: none;">
                    <label><?= lang_line('arinv_label_pick_doc'); ?></label>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light sticky-top" style="position: sticky; top: 0; background: #f8f9fa;">
                                <tr>
                                    <th style="width: 50px;">
                                        <input type="checkbox" id="check-all-docs">
                                    </th>
                                    <th><?= lang_line('arinv_th_doc_no'); ?></th>
                                    <th><?= lang_line('arinv_th_date'); ?></th>
                                    <th><?= lang_line('arinv_th_sc_no'); ?></th>
                                    <th><?= lang_line('arinv_th_currency'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="doc-list-body">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="doc-placeholder">
                    <p class="text-muted"><?= lang_line('arinv_placeholder_pick_doc_first'); ?></p>
                </div>
                <button type="button" id="btn-load-docs" class="btn btn-info mt-3" style="display: none;">
                    <i class="fas fa-download"></i> <?= lang_line('arinv_btn_pull_items'); ?>
                </button>
                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th><?= lang_line('apinv_th_desc_item'); ?></th>
                                <th style="width: 15%;"><?= lang_line('acct_th_qty'); ?></th>
                                <th style="width: 20%;"><?= lang_line('apinv_jasa_price'); ?></th>
                                <th style="width: 20%;"><?= lang_line('acct_th_total'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label"><?= lang_line('acct_subtotal'); ?> (<span class="currency-symbol-text">IDR</span>)</label>
                            <div class="col-sm-8">
                                <input type="text" id="subtotal" class="form-control text-right" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label"><?= lang_line('apinv_label_ppn_rate'); ?></label>
                            <div class="col-sm-8">
                                <input type="number" id="ppn_rate" name="ppn_rate" class="form-control" value="0" step="0.01">
                            </div>
                        </div>
                         <div class="form-group row">
                            <label class="col-sm-4 col-form-label"><?= lang_line('acct_ppn'); ?> (<span class="currency-symbol-text">IDR</span>)</label>
                            <div class="col-sm-8">
                                <input type="text" id="ppn_amount" class="form-control text-right" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label"><?= lang_line('acct_grand_total'); ?> (<span class="currency-symbol-text">IDR</span>)</label>
                            <div class="col-sm-8">
                                <input type="text" id="grand_total" class="form-control text-right font-weight-bold" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('arinvoice'); ?>" class="btn btn-secondary"><?= lang_line('acct_btn_cancel'); ?></a>
            <button type="submit" class="btn btn-primary"><?= lang_line('arinv_btn_save_draft'); ?></button>
        </div>
    </form>
</div>

<script>
const I18N_ARINV = {
    loading:                  <?= json_encode(lang_line('acct_loading')); ?>,
    no_docs_to_bill:          <?= json_encode(lang_line('arinv_no_docs_to_bill')); ?>,
    alert_pick_min_one:       <?= json_encode(lang_line('arinv_alert_pick_min_one')); ?>,
    alert_mixed_currency:     <?= json_encode(lang_line('arinv_alert_mixed_currency')); ?>,
    kurs_fetching:            <?= json_encode(lang_line('arinv_kurs_fetching')); ?>,
    kurs_unavailable:         <?= json_encode(lang_line('arinv_kurs_unavailable')); ?>,
    kurs_fail:                <?= json_encode(lang_line('arinv_kurs_fail')); ?>,
    kurs_auto_text:           <?= json_encode(lang_line('arinv_kurs_auto_text')); ?>
};

$(document).ready(function() {

    // Opsi default autoNumeric
    let autoNumericOptions = {
        decimalCharacter: '.',
        digitGroupSeparator: ',',
        decimalPlaces: 2,
        minimumValue: "0"
    };

    function initializeAutoNumeric(elements) {
        $(elements).each(function() {
            if (AutoNumeric.getAutoNumericElement(this)) {
                AutoNumeric.getAutoNumericElement(this).remove();
            }
            new AutoNumeric(this, autoNumericOptions);
        });
    }

    function getCurrencySymbol(currencyCode) {
        switch(currencyCode) {
            case 'USD': return '$';
            case 'RMB': return '¥';
            case 'IDR':
            default:
                return 'Rp';
        }
    }

    function updateCurrencyDisplay(currencyCode) {
        const symbol = getCurrencySymbol(currencyCode);

        $('#currency').val(currencyCode);
        $('#currency_text').val(currencyCode);
        $('.currency-symbol-text').text(currencyCode);

        $('#detail-body .input-currency').each(function() {
        });

        if (currencyCode === 'IDR') {
            $('#exchange_rate').val('1.0000').prop('readonly', true);
            $('#kurs_info').html('');
            autoNumericOptions.decimalPlaces = 2;
        } else {
            $('#exchange_rate').val('').prop('readonly', false);
            $('#kurs_info').removeClass('text-success text-danger').addClass('text-muted')
                .html('<i class="fas fa-spinner fa-spin"></i> ' + I18N_ARINV.kurs_fetching);
            $.getJSON('<?= base_url('kurs_pajak/ajax_get_rate'); ?>', { currency: currencyCode })
                .done(function(res) {
                    if (res.ok && res.rate) {
                        $('#exchange_rate').val(res.rate);
                        $('#kurs_info').removeClass('text-muted text-danger').addClass('text-success')
                            .html('<i class="fas fa-check-circle"></i> '
                                + I18N_ARINV.kurs_auto_text.replace('%s', res.effective_date_start).replace('%s', res.effective_date_end));
                    } else {
                        $('#kurs_info').removeClass('text-muted text-success').addClass('text-danger')
                            .html('<i class="fas fa-exclamation-triangle"></i> ' + (res.message || I18N_ARINV.kurs_unavailable));
                    }
                    calculateTotal();
                })
                .fail(function() {
                    $('#kurs_info').removeClass('text-muted text-success').addClass('text-danger')
                        .html('<i class="fas fa-exclamation-triangle"></i> ' + I18N_ARINV.kurs_fail);
                });
            autoNumericOptions.decimalPlaces = 2;
        }

        initializeAutoNumeric(document.querySelectorAll('.input-currency'));
        calculateTotal();
    }


    $('#customer_id, #source_type').change(function() {
        loadPendingDocs();
    });

    $(document).on('change', '#check-all-docs', function() {
        $('.doc-checkbox').prop('checked', this.checked);
    });

    function loadPendingDocs() {
        var customerId = $('#customer_id').val();
        var sourceType = $('#source_type').val();

        $('#doc-list-body').html('');
        $('#detail-body').html('');
        $('#doc-placeholder').show();
        $('#doc-list-container').hide();
        $('#btn-load-docs').hide();

        if (!customerId || !sourceType) {
            return;
        }

        $.ajax({
            url: "<?= base_url('arinvoice/ajax_get_pending_docs'); ?>",
            type: "GET",
            data: { customer_id: customerId, source_type: sourceType },
            dataType: "json",
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    $('#doc-placeholder').hide();
                    $('#doc-list-container').show();
                    $('#btn-load-docs').show();

                    var tbody = '';
                    response.data.forEach(function(doc) {
                        var docNumber = doc.delivery_number || doc.doc_no;
                        var docDate = doc.delivery_date || doc.doc_date;
                        var docRef = doc.sc_number || doc.bc_number || '-';

                        tbody += `
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="doc-checkbox" value="${doc.id}"
                                           data-currency="${doc.currency_code}">
                                </td>
                                <td>${docNumber}</td>
                                <td>${docDate}</td>
                                <td>${docRef}</td>
                                <td>${doc.currency_code}</td>
                            </tr>
                        `;
                    });
                    $('#doc-list-body').html(tbody);
                } else {
                    $('#doc-placeholder').html('<p class="text-warning">' + I18N_ARINV.no_docs_to_bill + '</p>').show();
                }
            }
        });
    }

    $('#btn-load-docs').click(function() {
        var source_ids = [];
        var currencies = [];

        $('.doc-checkbox:checked').each(function() {
            source_ids.push($(this).val());
            currencies.push($(this).data('currency'));
        });

        if (source_ids.length === 0) {
            alert(I18N_ARINV.alert_pick_min_one);
            return;
        }

        var firstCurrency = currencies[0];
        var allSameCurrency = currencies.every(c => c === firstCurrency);

        if (!allSameCurrency) {
            alert(I18N_ARINV.alert_mixed_currency);
            return;
        }

        updateCurrencyDisplay(firstCurrency);

        var source_type = $('#source_type').val();

        $.ajax({
            url: "<?= base_url('arinvoice/ajax_get_doc_details'); ?>",
            type: "POST",
            data: { source_ids: source_ids, source_type: source_type },
            dataType: "json",
            success: function(response) {
                $('#detail-body').html('');
                if (response.success && response.data.length > 0) {
                    response.data.forEach(function(item, i) {
                        if(item.currency_code != firstCurrency) {
                            return;
                        }

                        var total = item.qty * item.unit_price;
                        var priceReadonly = (source_type === 'FG_DELIVERY') ? 'readonly' : '';

                        var row = `
                            <tr>
                                <td>
                                    ${item.description}
                                    <input type="hidden" name="details[${i}][description]" value="${item.description}">
                                    <input type="hidden" name="details[${i}][source_detail_id]" value="${item.source_detail_id}">
                                    <input type="hidden" name="details[${i}][item_id]" value="${item.item_id}">
                                    <input type="hidden" name="details[${i}][item_type]" value="${item.item_type}">
                                </td>
                                <td><input type="number" name="details[${i}][qty]" class="form-control qty-item" value="${item.qty}" readonly></td>
                                <td>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text currency-symbol">${getCurrencySymbol(firstCurrency)}</span></div>
                                        <input type="text" name="details[${i}][unit_price]" class="form-control price-item input-currency text-right" value="${item.unit_price}" ${priceReadonly}>
                                    </div>
                                </td>
                                <td><input type="text" class="form-control total-item text-right" value="${total.toFixed(2)}" readonly></td>
                            </tr>
                        `;
                        $('#detail-body').append(row);
                    });

                    initializeAutoNumeric(document.querySelectorAll('#detail-body .input-currency'));
                    calculateTotal();
                }
            }
        });
    });

    $('#detail-body').on('input', '.price-item', calculateTotal);
    $('#ppn_rate').on('input', calculateTotal);

    function calculateTotal() {
        var subtotal = 0;

        $('#detail-body tr').each(function() {
            var qty = parseFloat($(this).find('.qty-item').val()) || 0;
            var priceEl = $(this).find('.price-item')[0];
            var price = 0;

            if(priceEl) {
                price = AutoNumeric.getAutoNumericElement(priceEl) ? AutoNumeric.getAutoNumericElement(priceEl).getNumber() : 0;
            }

            var total = qty * price;
            $(this).find('.total-item').val(AutoNumeric.format(total, autoNumericOptions));
            subtotal += total;
        });

        var ppnRate = parseFloat($('#ppn_rate').val()) || 0;
        var ppnAmount = subtotal * (ppnRate / 100);
        var grandTotal = subtotal + ppnAmount;

        $('#subtotal').val(AutoNumeric.format(subtotal, autoNumericOptions));
        $('#ppn_amount').val(AutoNumeric.format(ppnAmount, autoNumericOptions));
        $('#grand_total').val(AutoNumeric.format(grandTotal, autoNumericOptions));
    }

    $('#ar-form').on('submit', function() {
        $('#detail-body .input-currency').each(function() {
            const anElement = AutoNumeric.getAutoNumericElement(this);
            if (anElement) {
                $(this).val(anElement.getNumber());
            }
        });
        return true;
    });
});
</script>
