<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('jurnalumum/store'); ?>" method="POST" id="journal-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Header Jurnal</h6>
                <button type="button" class="btn btn-success btn-sm" id="btn-import" disabled>
                    <i class="fas fa-file-excel"></i> Impor Detail (Hanya IDR)
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>No. Jurnal</label>
                            <input type="text" id="journal_no" name="journal_no" class="form-control" 
                                   value="<?= set_value('journal_no', $this->input->get('journal_no') ?? $journal_no); ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Jurnal *</label>
                            <input type="date" id="journal_date" name="journal_date" class="form-control" 
                                   value="<?= set_value('journal_date', $this->input->get('journal_date') ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Mata Uang *</label>
                            <select name="currency" id="currency" class="form-control" required>
                                <?php foreach($currencies as $curr): ?>
                                    <option value="<?= $curr->code; ?>" <?= set_select('currency', $curr->code, $curr->code == 'IDR'); ?>>
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
                                   value="<?= set_value('exchange_rate', '1.0000'); ?>" 
                                   min="0.0001" step="0.0001" readonly required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label>Keterangan *</label>
                            <input type="text" id="description" name="description" class="form-control" 
                                   value="<?= set_value('description', $this->input->get('description') ?? ''); ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Detail Jurnal (Hanya akun dengan mata uang <span class="currency-symbol-text font-weight-bold">IDR</span> yang tampil)</h6>
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
                            <?php 
                            $imported_details = $this->session->flashdata('imported_details');
                            $details = set_value('details', $imported_details ?? []);
                            if (empty($details)) { $details = [[]]; }
                            
                            foreach ($details as $i => $detail): 
                            ?>
                            <tr>
                                <td>
                                    <select name="details[<?= $i; ?>][account_id]" class="form-control account-select" required>
                                        <option value="">-- Pilih Akun --</option>
                                        <?php foreach($accounts as $acc): ?>
                                            <option value="<?= $acc->id; ?>" 
                                                    data-currency="<?= $acc->currency; ?>"
                                                    <?= set_select("details[{$i}][account_id]", $acc->id, ($detail['account_id'] ?? '') == $acc->id); ?>>
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
                                               value="<?= set_value("details[{$i}][debit]", $detail['debit'] ?? 0); ?>" 
                                               required>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text currency-symbol">Rp</span>
                                        </div>
                                        <input type="text" name="details[<?= $i; ?>][kredit]" class="form-control kredit-input input-currency text-right" 
                                               value="<?= set_value("details[{$i}][kredit]", $detail['kredit'] ?? 0); ?>" 
                                               required>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="details[<?= $i; ?>][description]" class="form-control" 
                                           value="<?= set_value("details[{$i}][description]", $detail['description'] ?? ''); ?>">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <th class="text-right">TOTAL (<span class="currency-symbol-text">IDR</span>)</th>
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
            <a href="<?= base_url('jurnalumum'); ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Jurnal (Draft)</button>
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

<form id="form-impor" action="<?= base_url('jurnalumum/import'); ?>" method="GET" style="display: none;">
    <input type="hidden" name="journal_no" id="import_journal_no">
    <input type="hidden" name="journal_date" id="import_journal_date">
    <input type="hidden" name="description" id="import_description">
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let rowIndex = <?= count($details); ?>;
    const detailBody = document.getElementById("detail-body");
    const template = document.getElementById("row-template");
    const currencySelect = document.getElementById("currency");
    const rateInput = document.getElementById("exchange_rate");
    const importButton = document.getElementById("btn-import");
    const detailHeader = document.querySelector('.card-header h6.text-primary');

    // ======================================
    // LOGIKA BARU UNTUK AUTONUMERIC
    // ======================================
    
    // Opsi default untuk IDR (format Indonesia: ribuan = titik, desimal = koma)
    let autoNumericOptions = {
        decimalCharacter: ',', // desimal sebagai koma
        digitGroupSeparator: '.', // pemisah ribuan sebagai titik
        decimalPlaces: 2,
        minimumValue: "0"
    };

    // Fungsi untuk inisialisasi autoNumeric
    function initializeAutoNumeric(elements) {
        $(elements).each(function() {
            // Hapus instance lama jika ada (penting untuk baris baru)
            if (AutoNumeric.getAutoNumericElement(this)) {
                AutoNumeric.getAutoNumericElement(this).remove();
            }
            // Buat instance baru
            new AutoNumeric(this, autoNumericOptions);
        });
    }

    // Fungsi untuk mengubah opsi autoNumeric
    function updateAutoNumericOptions(currencyCode) {
        // Sesuaikan format sesuai mata uang
        if (currencyCode === 'IDR') {
            autoNumericOptions.decimalCharacter = ',';
            autoNumericOptions.digitGroupSeparator = '.';
            autoNumericOptions.decimalPlaces = 2; // Rupiah 2 desimal
        } else {
            // Untuk USD/RMB pakai format internasional (ribuan koma, desimal titik)
            autoNumericOptions.decimalCharacter = '.';
            autoNumericOptions.digitGroupSeparator = ',';
            autoNumericOptions.decimalPlaces = 2;
        }

        // Inisialisasi ulang semua input yang ada (termasuk baris baru)
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
        // Append fragment (moves children into the DOM)
        detailBody.appendChild(newRow);

        // Filter dropdowns (this will destroy/re-init Select2 for all selects)
        filterAccountDropdowns(currencySelect.value);

        // Inisialisasi autonumeric untuk baris yang baru saja ditambahkan
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
            // autoNumeric akan memicu 'input', jadi kita hitung ulang
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
            // Ambil nilai mentah dari autoNumeric
            totalDebit += AutoNumeric.getAutoNumericElement(row.querySelector(".debit-input")).getNumber();
            totalKredit += AutoNumeric.getAutoNumericElement(row.querySelector(".kredit-input")).getNumber();
        });

        // Tampilkan total dengan format
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
            // If select2 is initialized, destroy it first to safely replace options
            if ($(select).hasClass('select2-hidden-accessible')) {
                $(select).select2('destroy');
            }
            // Rebuild options
            select.innerHTML = '';
            select.appendChild(new Option('-- Pilih Akun --', ''));

            masterAccountList.forEach(optionNode => {
                if (optionNode.value === '') return;
                const accountCurrency = optionNode.dataset.currency;
                if (accountCurrency === selectedCurrency) {
                    select.appendChild(optionNode.cloneNode(true));
                }
            });

            // Restore previous selection if still available
            select.value = selectedValue || '';

            // Re-initialize select2 on the rebuilt select
            $(select).select2({ dropdownParent: $(select).closest('td') });
        });
    }

    currencySelect.addEventListener("change", function() {
        const selectedCurrency = this.value;
        const symbol = getCurrencySymbol(selectedCurrency);

        document.querySelectorAll('.currency-symbol').forEach(el => el.textContent = symbol);
        document.querySelectorAll('.currency-symbol-text').forEach(el => el.textContent = selectedCurrency);
        
        const detailCardHeader = document.querySelector('#detail-table').closest('.card').querySelector('.card-header h6');
        if(detailCardHeader) {
            detailCardHeader.innerHTML = `Detail Jurnal (Hanya akun dengan mata uang <span class="font-weight-bold">${selectedCurrency}</span> yang tampil)`;
        }
        
        if (selectedCurrency === 'IDR') {
            rateInput.value = '1.0000';
            rateInput.readOnly = true;
            importButton.disabled = false;
        } else {
            rateInput.value = '';
            rateInput.readOnly = false;
            importButton.disabled = true;
        }
        
        // Update opsi autoNumeric & filter dropdown
        updateAutoNumericOptions(selectedCurrency);
        filterAccountDropdowns(selectedCurrency);
        calculateTotals();
    });
    
    document.getElementById("btn-import").addEventListener("click", function() {
        document.getElementById("import_journal_no").value = document.getElementById("journal_no").value;
        document.getElementById("import_journal_date").value = document.getElementById("journal_date").value;
        document.getElementById("import_description").value = document.getElementById("description").value;
        document.getElementById("form-impor").submit();
    });

    // ======================================
    // PENTING: Unformat nilai sebelum submit
    // ======================================
    $('#journal-form').on('submit', function() {
        $('#detail-table .input-currency').each(function() {
            const anElement = AutoNumeric.getAutoNumericElement(this);
            if (anElement) {
                // Set value ke angka mentahnya (e.g., "1000.00")
                $(this).val(anElement.getNumber());
            }
        });
        // Form akan melanjutkan submit dengan nilai yang sudah di-unformat
        return true;
    });


    // Inisialisasi saat halaman dimuat
    initializeAutoNumeric(detailBody.querySelectorAll('.input-currency'));
    calculateTotals();
    currencySelect.dispatchEvent(new Event('change'));
});
</script>