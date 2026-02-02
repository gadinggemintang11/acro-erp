<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('jurnalumum/update/' . $header->id); ?>" method="POST" id="journal-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Header Jurnal</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>No. Jurnal</label>
                            <input type="text" id="journal_no" name="journal_no" class="form-control" 
                                   value="<?= $header->journal_no; ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Jurnal *</label>
                            <input type="date" id="journal_date" name="journal_date" class="form-control" 
                                   value="<?= set_value('journal_date', $header->journal_date); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Mata Uang *</label>
                            <select name="currency" id="currency" class="form-control" required>
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>" <?= set_select('currency', $curr->code, $curr->code == $header->currency); ?>>
                                        <?= $curr->code; ?> (<?= $curr->description; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Kurs (Rate) *</label>
                            <input type="number" id="exchange_rate" name="exchange_rate" class="form-control" 
                                   value="<?= set_value('exchange_rate', $header->exchange_rate); ?>" 
                                   min="0.0001" step="0.0001" readonly required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Keterangan *</label>
                            <input type="text" id="description" name="description" class="form-control" 
                                   value="<?= set_value('description', $header->description); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Detail Jurnal (Hanya akun dengan mata uang <span class="currency-symbol-text font-weight-bold"><?= $header->currency; ?></span> yang tampil)</h6>
                <button type="button" id="add-row" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah Baris</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table" id="detail-table">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Akun *</th>
                                <th style="width: 20%;">Debit *</th>
                                <th style="width: 20%;">Kredit *</th>
                                <th>Deskripsi</th>
                                <th style="width: 5%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="detail-body">
                            <?php foreach ($details as $i => $detail): ?>
                            <tr>
                                <td>
                                    <select name="details[<?= $i; ?>][account_id]" class="form-control account-select" required>
                                        <option value="">-- Pilih Akun --</option>
                                        <?php foreach($accounts as $acc): ?>
                                            <option value="<?= $acc->id; ?>" 
                                                    data-currency="<?= $acc->currency; ?>"
                                                    <?= ($acc->id == $detail->account_id) ? 'selected' : ''; ?>>
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
                                        <input type="text" name="details[<?= $i; ?>][debit]" class="form-control debit-input input-currency text-right" 
                                               value="<?= ($detail->debit > 0) ? $detail->debit : ''; ?>" 
                                               required>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text currency-symbol">Rp</span>
                                        </div>
                                        <input type="text" name="details[<?= $i; ?>][kredit]" class="form-control kredit-input input-currency text-right" 
                                               value="<?= ($detail->kredit > 0) ? $detail->kredit : ''; ?>" 
                                               required>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="details[<?= $i; ?>][description]" class="form-control" 
                                           value="<?= set_value("details[{$i}][description]", $detail->description); ?>">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th class="text-right">TOTAL (<span class="currency-symbol-text"><?= $header->currency; ?></span>)</th>
                                <th><input type="text" id="total-debit" class="form-control text-right font-weight-bold" readonly></th>
                                <th><input type="text" id="total-kredit" class="form-control text-right font-weight-bold" readonly></th>
                                <th colspan="2" id="balance-status" class="text-center align-middle"></th>
                            </tr>
                            <tr class="bg-gray-200">
                                <th class="text-right">TOTAL (dalam IDR)</th>
                                <th><input type="text" id="total-debit-local" class="form-control text-right font-weight-bold" readonly></th>
                                <th><input type="text" id="total-kredit-local" class="form-control text-right font-weight-bold" readonly></th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('jurnalumum/view/' . $header->id); ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Update Jurnal</button>
        </div>
    </form>
</div>

<template id="row-template">
    <tr>
        <td>
            <select name="details[0][account_id]" class="form-control account-select" required>
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
                <input type="text" name="details[0][debit]" class="form-control debit-input input-currency text-right" value="0" required>
            </div>
        </td>
        <td>
             <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text currency-symbol">Rp</span>
                </div>
                <input type="text" name="details[0][kredit]" class="form-control kredit-input input-currency text-right" value="0" required>
            </div>
        </td>
        <td><input type="text" name="details[0][description]" class="form-control"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let rowIndex = <?= count($details); ?>;
    const detailBody = document.getElementById("detail-body");
    const template = document.getElementById("row-template");
    const currencySelect = document.getElementById("currency");
    const rateInput = document.getElementById("exchange_rate");
    const detailHeader = document.querySelector('.card-header h6.text-primary');

    // ======================================
    // LOGIKA AUTONUMERIC
    // ======================================
    
    let autoNumericOptions = {
        decimalCharacter: ',',
        digitGroupSeparator: '.',
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

    function updateAutoNumericOptions(currencyCode) {
        if (currencyCode === 'IDR') {
            autoNumericOptions.decimalCharacter = ',';
            autoNumericOptions.digitGroupSeparator = '.';
            autoNumericOptions.decimalPlaces = 2;
        } else {
            autoNumericOptions.decimalCharacter = '.';
            autoNumericOptions.digitGroupSeparator = ',';
            autoNumericOptions.decimalPlaces = 2;
        }
        initializeAutoNumeric(detailBody.querySelectorAll('.input-currency'));
    }

    // ======================================
    // AKHIR LOGIKA AUTONUMERIC
    // ======================================

    const masterAccountList = Array.from(template.content.querySelector(".account-select").options);

    function addRow() {
        const newRow = template.content.cloneNode(true);
        newRow.querySelectorAll("input, select").forEach(el => {
            el.name = el.name.replace(/details\[0\]/g, `details[${rowIndex}]`);
        });
        const symbol = getCurrencySymbol(currencySelect.value);
        newRow.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);
        detailBody.appendChild(newRow);

        filterAccountDropdowns(currencySelect.value);

        const lastRow = detailBody.querySelector('tr:last-child');
        if (lastRow) {
            initializeAutoNumeric(lastRow.querySelectorAll('.input-currency'));
        }

        rowIndex++;
    }

    document.getElementById("add-row").addEventListener("click", addRow);

    detailBody.addEventListener("click", function(e) {
        if (e.target.closest(".remove-row")) {
            e.target.closest("tr").remove();
            calculateTotals();
        }
    });

    detailBody.addEventListener("input", function(e) {
        const row = e.target.closest("tr");
        if (e.target.classList.contains("input-currency")) {
            if (e.target.classList.contains("debit-input")) {
                if (AutoNumeric.getAutoNumericElement(e.target).getNumber() > 0) {
                    AutoNumeric.getAutoNumericElement(row.querySelector(".kredit-input")).set(0);
                }
            } else if (e.target.classList.contains("kredit-input")) {
                if (AutoNumeric.getAutoNumericElement(e.target).getNumber() > 0) {
                    AutoNumeric.getAutoNumericElement(row.querySelector(".debit-input")).set(0);
                }
            }
            calculateTotals();
        }
    });

    rateInput.addEventListener("input", calculateTotals);

    function calculateTotals() {
        let totalDebit = 0;
        let totalKredit = 0;
        let rate = parseFloat(rateInput.value) || 0;
        
        detailBody.querySelectorAll("tr").forEach(row => {
            totalDebit += AutoNumeric.getAutoNumericElement(row.querySelector(".debit-input")).getNumber();
            totalKredit += AutoNumeric.getAutoNumericElement(row.querySelector(".kredit-input")).getNumber();
        });

        document.getElementById("total-debit").value = AutoNumeric.format(totalDebit, autoNumericOptions);
        document.getElementById("total-kredit").value = AutoNumeric.format(totalKredit, autoNumericOptions);
        document.getElementById("total-debit-local").value = AutoNumeric.format(totalDebit * rate, autoNumericOptions);
        document.getElementById("total-kredit-local").value = AutoNumeric.format(totalKredit * rate, autoNumericOptions);
        
        const statusEl = document.getElementById("balance-status");
        if (totalDebit === totalKredit && totalDebit > 0) {
            statusEl.innerHTML = '<span class="badge badge-success">BALANCE</span>';
        } else {
            statusEl.innerHTML = '<span class="badge badge-danger">UNBALANCE</span>';
        }
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
    
    function filterAccountDropdowns(selectedCurrency) {
        const allSelects = detailBody.querySelectorAll(".account-select");
        allSelects.forEach(select => {
            const selectedValue = select.value;
            if ($(select).hasClass('select2-hidden-accessible')) {
                $(select).select2('destroy');
            }
            select.innerHTML = '';
            select.appendChild(new Option('-- Pilih Akun --', ''));

            masterAccountList.forEach(optionNode => {
                if (optionNode.value === '') return;
                const accountCurrency = optionNode.dataset.currency;
                if (accountCurrency === selectedCurrency) {
                    select.appendChild(optionNode.cloneNode(true));
                }
            });

            select.value = selectedValue || '';

            $(select).select2({ dropdownParent: $(select).closest('td') });
        });
    }

    currencySelect.addEventListener("change", function() {
        const selectedCurrency = this.value;
        const symbol = getCurrencySymbol(selectedCurrency);

        document.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);
        document.querySelectorAll('.currency-symbol-text').forEach(el => el.textContent = selectedCurrency);
        
        if (selectedCurrency === 'IDR') {
            rateInput.value = '1.0000';
            rateInput.readOnly = true;
        } else {
            rateInput.value = '';
            rateInput.readOnly = false;
        }
        
        updateAutoNumericOptions(selectedCurrency);
        filterAccountDropdowns(selectedCurrency);
        calculateTotals();
    });

    // ======================================
    // PENTING: Unformat nilai sebelum submit
    // ======================================
    $('#journal-form').on('submit', function() {
        $('#detail-table .input-currency').each(function() {
            const anElement = AutoNumeric.getAutoNumericElement(this);
            if (anElement) {
                $(this).val(anElement.getNumber());
            }
        });
        return true;
    });

    initializeAutoNumeric(detailBody.querySelectorAll('.input-currency'));
    calculateTotals();
});
</script>