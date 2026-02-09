<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('arinvoice/store'); ?>" method="POST" id="ar-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Header Faktur</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Customer *</label>
                            <select id="customer_id" name="customer_id" class="form-control" required>
                                <option value="">-- Pilih Customer --</option>
                                <?php foreach($customers as $c): ?>
                                    <option value="<?= $c->id; ?>"><?= $c->nama_customer; ?> (<?= $c->jenis; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Sumber Tagihan *</label>
                            <select id="source_type" name="source_type" class="form-control" required>
                                <option value="">-- Pilih Sumber --</option>
                                <option value="FG_DELIVERY">1. Pengiriman Barang Jadi (DO)</option>
                                <option value="SCRAP_OUT">2. Penjualan Scrap</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Tgl. Faktur *</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Tgl. Jatuh Tempo *</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')); ?>" required>
                        </div>
                    </div>
                </div>
                 <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Mata Uang *</label>
                            <input type="text" id="currency_text" class="form-control" value="IDR" readonly>
                            <input type="hidden" id="currency" name="currency" value="IDR">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Kurs (Rate) *</label>
                            <input type="number" id="exchange_rate" name="exchange_rate" class="form-control" 
                                   value="1.0000" min="0.0001" step="0.0001" readonly required>
                        </div>
                    </div>
                 </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Detail Dokumen Sumber</h6>
            </div>
            <div class="card-body">
                <div id="doc-list-container" style="display: none;">
                    <label>Pilih Dokumen (DO / Scrap Out) yang akan ditagih *</label>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light sticky-top" style="position: sticky; top: 0; background: #f8f9fa;">
                                <tr>
                                    <th style="width: 50px;">
                                        <input type="checkbox" id="check-all-docs">
                                    </th>
                                    <th>No. Dokumen</th>
                                    <th>Tanggal</th>
                                    <th>SC Number</th>
                                    <th>Mata Uang</th>
                                </tr>
                            </thead>
                            <tbody id="doc-list-body">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="doc-placeholder">
                    <p class="text-muted">Pilih Customer dan Sumber Tagihan terlebih dahulu untuk melihat daftar dokumen.</p>
                </div>
                <button type="button" id="btn-load-docs" class="btn btn-info mt-3" style="display: none;">
                    <i class="fas fa-download"></i> Tarik Item dari Dokumen Terpilih
                </button>
                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Deskripsi (Item)</th>
                                <th style="width: 15%;">Qty</th>
                                <th style="width: 20%;">Harga Satuan *</th>
                                <th style="width: 20%;">Total</th>
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
                            <label class="col-sm-4 col-form-label">Subtotal (<span class="currency-symbol-text">IDR</span>)</label>
                            <div class="col-sm-8">
                                <input type="text" id="subtotal" class="form-control text-right" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">PPN (%)</label>
                            <div class="col-sm-8">
                                <input type="number" id="ppn_rate" name="ppn_rate" class="form-control" value="0" step="0.01">
                            </div>
                        </div>
                         <div class="form-group row">
                            <label class="col-sm-4 col-form-label">PPN (<span class="currency-symbol-text">IDR</span>)</label>
                            <div class="col-sm-8">
                                <input type="text" id="ppn_amount" class="form-control text-right" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Grand Total (<span class="currency-symbol-text">IDR</span>)</label>
                            <div class="col-sm-8">
                                <input type="text" id="grand_total" class="form-control text-right font-weight-bold" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('arinvoice'); ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Faktur (Draft)</button>
        </div>
    </form>
</div>

<script>
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
        
        // Update di header
        $('#currency').val(currencyCode);
        $('#currency_text').val(currencyCode);
        $('.currency-symbol-text').text(currencyCode);

        // Update di tabel (jika ada inputan)
        $('#detail-body .input-currency').each(function() {
            // Kita perlu re-init autoNumeric
        });

        if (currencyCode === 'IDR') {
            $('#exchange_rate').val('1.0000').prop('readonly', true);
            autoNumericOptions.decimalPlaces = 2;
        } else {
            $('#exchange_rate').val('').prop('readonly', false);
            autoNumericOptions.decimalPlaces = 2;
        }
        
        // Re-init semua inputan currency yang ada
        initializeAutoNumeric(document.querySelectorAll('.input-currency'));
        calculateTotal();
    }


    $('#customer_id, #source_type').change(function() {
        loadPendingDocs();
    });
    
    // Check all checkbox
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
                    $('#doc-placeholder').html('<p class="text-warning">Tidak ada dokumen yang bisa ditagih untuk customer ini.</p>').show();
                }
            }
        });
    }

    $('#btn-load-docs').click(function() {
        // Ambil semua checkbox yang dicentang
        var source_ids = [];
        var currencies = [];
        
        $('.doc-checkbox:checked').each(function() {
            source_ids.push($(this).val());
            currencies.push($(this).data('currency'));
        });
        
        if (source_ids.length === 0) {
            alert('Pilih minimal 1 dokumen terlebih dahulu.');
            return;
        }
        
        // Validasi: semua dokumen harus punya currency yang sama
        var firstCurrency = currencies[0];
        var allSameCurrency = currencies.every(c => c === firstCurrency);
        
        if (!allSameCurrency) {
            alert('Tidak bisa menggabungkan dokumen dengan mata uang berbeda dalam 1 invoice!\nPilih dokumen dengan mata uang yang sama.');
            return;
        }
        
        // Update currency display
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
                        // Skip item jika currency tidak cocok
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
                    
                    // Inisialisasi autoNumeric untuk baris-baris baru
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
                // Ambil nilai mentah dari autoNumeric
                price = AutoNumeric.getAutoNumericElement(priceEl) ? AutoNumeric.getAutoNumericElement(priceEl).getNumber() : 0;
            }
            
            var total = qty * price;
            // Tampilkan total dengan format
            $(this).find('.total-item').val(AutoNumeric.format(total, autoNumericOptions));
            subtotal += total;
        });

        var ppnRate = parseFloat($('#ppn_rate').val()) || 0;
        var ppnAmount = subtotal * (ppnRate / 100);
        var grandTotal = subtotal + ppnAmount;

        // Tampilkan total di footer dengan format
        $('#subtotal').val(AutoNumeric.format(subtotal, autoNumericOptions));
        $('#ppn_amount').val(AutoNumeric.format(ppnAmount, autoNumericOptions));
        $('#grand_total').val(AutoNumeric.format(grandTotal, autoNumericOptions));
    }
    
    // Unformat sebelum submit
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