<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('cashbank/store'); ?>" method="POST" id="cb-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Header Transaksi</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>No. Dokumen</label>
                            <input type="text" name="doc_no" class="form-control" value="<?= $doc_no; ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal *</label>
                            <input type="date" name="doc_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tipe Transaksi *</label>
                            <select id="transaction_type" name="transaction_type" class="form-control" required>
                                <option value="OUT">OUT (Kas/Bank Keluar)</option>
                                <option value="IN">IN (Kas/Bank Masuk)</option>
                            </select>
                        </div>
                    </div>
                     <div class="col-md-3">
                        <div class="form-group">
                            <label>Mata Uang *</label>
                            <select name="currency" id="currency" class="form-control" required>
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>" <?= set_select('currency', $curr->code, $curr->code == 'IDR'); ?>>
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
                            <label>Kurs (Rate) *</label>
                            <input type="number" id="exchange_rate" name="exchange_rate" class="form-control" 
                                   value="1.0000" min="0.0001" step="0.0001" readonly required>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>Akun Kas/Bank *</label>
                            <select id="cash_bank_account_id" name="cash_bank_account_id" class="form-control select2-account" required>
                                <option value="">-- Pilih Mata Uang Dulu --</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Keterangan Header *</label>
                            <input type="text" name="description" class="form-control" 
                                   placeholder="cth: Pembayaran Operasional Bulan Nov" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Detail Akun Lawan (Hanya akun <span class="currency-symbol-text">IDR</span>)</h6>
                <button type="button" id="add-row" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah Baris</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="detail-table">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Akun Lawan *</th>
                                <th style="width: 25%;">Jumlah *</th>
                                <th>Deskripsi</th>
                                <th style="width: 5%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                            </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th class="text-right">TOTAL (<span class="currency-symbol-text">IDR</span>)</th>
                                <th><input type="text" id="total_amount" class="form-control text-right font-weight-bold input-currency" readonly></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('cashbank'); ?>" class="btn btn-secondary">Kembali</a>
            <button type="submit" class="btn btn-primary">Simpan Transaksi (Draft)</button>
        </div>
    </form>
</div>

<template id="row-template">
    <tr>
        <td>
            <select name="details[0][account_id]" class="form-control account-select select2-account" required>
                <option value="">-- Pilih Akun --</option>
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
$(document).ready(function() {
    let rowIndex = 0;
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
            theme: 'bootstrap4', // Gunakan tema bootstrap 4
            width: '100%',
            placeholder: '-- Pilih Akun --',
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
        detailBody.appendChild(newRow);

        // Inisialisasi plugin untuk baris baru
        initAutoNumeric(newRow.querySelectorAll('.input-currency'));
        initSelect2(newRow.querySelectorAll('.select2-account'));
        
        filterAccounts(currencySelect.value);
        rowIndex++;
    }

    $('#add-row').click(addRow);
    addRow(); // Tambah 1 baris saat load

    detailBody.addEventListener("click", function(e) {
        if (e.target.closest(".remove-row")) {
            // Hancurkan select2 sebelum menghapus baris
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
    function loadCashBankAccounts(currency) {
        const accSelect = $('#cash_bank_account_id');
        accSelect.html('<option value="">Memuat...</option>').trigger('change');
        
        $.getJSON("<?= base_url('cashbank/ajax_get_cashbank_accounts'); ?>", { currency }, function(res) {
            accSelect.html('<option value="">-- Pilih Akun --</option>');
            if (res.success && res.data.length > 0) {
                res.data.forEach(acc => {
                    accSelect.append(new Option(`${acc.account_code} - ${acc.account_name}`, acc.id));
                });
            } else {
                accSelect.html(`<option value="">Tidak ada Akun Kas/Bank ${currency}</option>`);
            }
            // Re-inisialisasi select2 header
            initSelect2(accSelect);
        });
    }

    // Filter Akun Lawan (Detail)
    function filterAccounts(currency) {
        const selects = detailBody.querySelectorAll(".account-select");
        selects.forEach(select => {
            const selectedValue = select.value;
            select.innerHTML = ''; 
            select.appendChild(new Option('-- Pilih Akun --', ''));

            masterAccountList.forEach(opt => {
                if (opt.value && opt.dataset.currency === currency) {
                    select.appendChild(opt.cloneNode(true));
                }
            });
            select.value = selectedValue;
            $(select).trigger('change.select2'); // Update tampilan select2
        });
    }

    currencySelect.addEventListener("change", function() {
        const curr = this.value;
        const symbol = getCurrencySymbol(curr);
        
        $('.currency-symbol').text(symbol);
        $('.currency-symbol-text').text(curr);
        
        // Perbaikan selector header detail
        const detailCardHeader = document.querySelector('#detail-table').closest('.card').querySelector('.card-header h6');
        if (detailCardHeader) {
             detailCardHeader.innerHTML = `Detail Akun Lawan (Hanya akun <span class="font-weight-bold">${curr}</span> yang tampil)`;
        }
        
        if (curr === 'IDR') {
            rateInput.value = '1.0000'; rateInput.readOnly = true;
        } else {
            rateInput.value = ''; rateInput.readOnly = false;
        }
        
        loadCashBankAccounts(curr);
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
});
</script>