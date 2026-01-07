<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('arreceipt/store'); ?>" method="POST" id="receipt-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Header Penerimaan</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>No. Penerimaan</label>
                            <input type="text" name="receipt_no" class="form-control" value="<?= $receipt_no; ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tgl. Terima *</label>
                            <input type="date" name="receipt_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Customer *</label>
                            <select id="customer_id" name="customer_id" class="form-control" required>
                                <option value="">-- Pilih Customer --</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?= $c->id; ?>"><?= $c->nama_customer; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Mata Uang Pembayaran *</label>
                            <select id="currency" name="currency" class="form-control" required>
                                <option value="">-- Pilih Mata Uang --</option>
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>"><?= $curr->code; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Kurs (Rate) *</label>
                            <input type="number" id="exchange_rate" name="exchange_rate" class="form-control" 
                                   value="1.0000" min="0.0001" step="0.0001" readonly required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Terima Ke Akun (Kas/Bank) *</label>
                            <select id="payment_account_id" name="payment_account_id" class="form-control" required>
                                <option value="">-- Pilih Mata Uang Dulu --</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                         <div class="form-group">
                            <label>Catatan</label>
                            <input type="text" name="notes" class="form-control" placeholder="No. Cek / BG / Transfer Ref">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Detail Faktur (Hanya faktur <span class="currency-symbol-text"></span> yang tampil)</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="invoice-table">
                        <thead class="thead-light">
                            <tr>
                                <th>No. Faktur</th>
                                <th>Tgl. Faktur</th>
                                <th class="text-right">Total Piutang</th>
                                <th class="text-right">Sudah Dibayar</th>
                                <th class="text-right">Sisa Piutang</th>
                                <th style="width: 25%;">Jumlah Bayar *</th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                            <tr>
                                <td colspan="6" class="text-center">Pilih customer dan mata uang untuk memuat daftar faktur.</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-gray-200">
                            <tr>
                                <td colspan="5" class="text-right font-weight-bold">Total Pembayaran (<span class="currency-symbol-text"></span>)</td>
                                <td>
                                    <input type="text" id="total_payment" class="form-control text-right font-weight-bold input-currency" readonly>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('arreceipt'); ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Penerimaan (Draft)</button>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    
    let autoNumericOptions = { decimalCharacter: '.', digitGroupSeparator: ',', decimalPlaces: 2, minimumValue: "0" };

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

    $('#customer_id, #currency').change(function() {
        loadData();
    });

    function loadData() {
        var customerId = $('#customer_id').val();
        var currency = $('#currency').val();
        
        if (!customerId || !currency) {
            return;
        }
        
        updateCurrencyDisplay(currency);
        loadPaymentAccounts(currency);
        loadUnpaidInvoices(customerId, currency);
    }
    
    function updateCurrencyDisplay(currencyCode) {
        const symbol = getCurrencySymbol(currencyCode);
        $('.currency-symbol-text').text(currencyCode);
        
        if (currencyCode === 'IDR') {
            $('#exchange_rate').val('1.0000').prop('readonly', true);
            autoNumericOptions.decimalPlaces = 2;
        } else {
            $('#exchange_rate').val('').prop('readonly', false);
            autoNumericOptions.decimalPlaces = 2;
        }
        initializeAutoNumeric(document.querySelectorAll('.input-currency'));
    }

    function loadPaymentAccounts(currency) {
        var accSelect = $('#payment_account_id');
        accSelect.html('<option value="">Memuat...</option>');
        
        $.ajax({
            url: "<?= base_url('arreceipt/ajax_get_payment_accounts'); ?>",
            type: "GET",
            data: { currency: currency },
            dataType: "json",
            success: function(response) {
                accSelect.html('<option value="">-- Pilih Akun --</option>');
                if (response.success && response.data.length > 0) {
                    response.data.forEach(function(acc) {
                        accSelect.append(`<option value="${acc.id}">${acc.account_code} - ${acc.account_name}</option>`);
                    });
                } else {
                    accSelect.html(`<option value="">Tidak ada Akun Kas/Bank ${currency}</option>`);
                }
            }
        });
    }

    function loadUnpaidInvoices(customerId, currency) {
        var tbody = $('#detail-body');
        tbody.html('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Memuat...</td></tr>');

        $.ajax({
            url: "<?= base_url('arreceipt/ajax_get_unpaid_invoices'); ?>",
            type: "GET",
            data: { customer_id: customerId, currency: currency },
            dataType: "json",
            success: function(response) {
                tbody.html('');
                if (response.success && response.data.length > 0) {
                    response.data.forEach(function(inv) {
                        var sisaPiutang = parseFloat(inv.sisa_piutang);
                        var row = `
                            <tr>
                                <td>${inv.invoice_no}</td>
                                <td>${inv.invoice_date}</td>
                                <td class="text-right">${AutoNumeric.format(inv.total_invoice, autoNumericOptions)}</td>
                                <td class="text-right">${AutoNumeric.format(inv.total_paid, autoNumericOptions)}</td>
                                <td class="text-right">${AutoNumeric.format(sisaPiutang, autoNumericOptions)}</td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend"><span class="input-group-text currency-symbol">${getCurrencySymbol(currency)}</span></div>
                                        <input type="text" name="details[${inv.id}][amount_paid]" 
                                               class="form-control text-right payment-amount input-currency" 
                                               value="0" data-max="${sisaPiutang}">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary btn-pay-full" data-amount="${sisaPiutang}">Full</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                    initializeAutoNumeric(tbody.find('.input-currency'));
                } else {
                    tbody.html('<tr><td colspan="6" class="text-center">Tidak ada faktur ${currency} yang belum lunas.</td></tr>');
                }
                calculateTotal();
            }
        });
    }

    $('#detail-body').on('input', '.payment-amount', calculateTotal);

    $('#detail-body').on('click', '.btn-pay-full', function() {
        var amount = $(this).data('amount');
        var input = $(this).closest('tr').find('.payment-amount');
        AutoNumeric.getAutoNumericElement(input[0]).set(amount);
        calculateTotal();
    });

    function calculateTotal() {
        var total = 0;
        $('.payment-amount').each(function() {
            total += AutoNumeric.getAutoNumericElement(this) ? AutoNumeric.getAutoNumericElement(this).getNumber() : 0;
        });
        AutoNumeric.getAutoNumericElement('#total_payment').set(total);
    }
    
    $('#receipt-form').on('submit', function() {
        $('.input-currency').each(function() {
            const anElement = AutoNumeric.getAutoNumericElement(this);
            if (anElement) {
                $(this).val(anElement.getNumber());
            }
        });
        return true;
    });

    initializeAutoNumeric(document.querySelectorAll('.input-currency'));
});
</script>