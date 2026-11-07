<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="text-gray-800 mb-1"><i class="fas fa-plus-circle text-primary mr-2"></i><?= htmlspecialchars($title); ?></h3>
            <p class="text-muted small mb-0"><?= lang_line('erf_create_subtitle', 'Reimburse Request Number akan auto-generate. Item bisa lebih dari 1.') ?></p>
        </div>
        <a href="<?= base_url('erf'); ?>" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> <?= lang_line('erf_btn_back', 'Kembali') ?></a>
    </div>


    <form method="POST" action="<?= base_url('erf/store'); ?>">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang_line('erf_card_header', 'Header') ?></h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="small font-weight-bold"><?= lang_line('erf_label_erf_number', 'Reimburse Request Number') ?></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($erf_number); ?>" readonly>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="small font-weight-bold"><?= lang_line('erf_label_department', 'Department') ?></label>
                        <?php if (count($dept_options ?? []) > 1): ?>
                        <!-- Departemen dipilih dari role user (Role Level tahap 3). -->
                        <select name="department_code" class="form-control" required>
                            <?php foreach ($dept_options as $code => $name): ?>
                            <option value="<?= htmlspecialchars($code); ?>" <?= $code === ($department_id ?? null) ? 'selected' : ''; ?>><?= htmlspecialchars($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="hidden" name="department_code" value="<?= htmlspecialchars($department_id ?? ''); ?>">
                        <input type="text" class="form-control" value="<?= htmlspecialchars($department_name); ?>" readonly>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="small font-weight-bold"><?= lang_line('erf_label_erf_date', 'Reimburse Request Date') ?> <span class="text-danger">*</span></label>
                        <input type="date" name="erf_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="small font-weight-bold"><?= lang_line('erf_lbl_requester', 'Requester') ?></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($requester_name ?? '-'); ?>" readonly>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="small font-weight-bold"><?= lang_line('erf_lbl_karyawan', 'Karyawan (Penerima Reimburse)') ?> <span class="text-danger">*</span></label>
                        <select name="karyawan_id" id="karyawan_id" class="form-control" required>
                            <option value=""><?= lang_line('erf_select_karyawan', '-- Pilih Karyawan --') ?></option>
                            <?php foreach (($karyawan_list ?? []) as $kar): ?>
                                <option value="<?= $kar->id; ?>"
                                    data-bank-name="<?= htmlspecialchars($kar->bank_name ?? ''); ?>"
                                    data-bank-account="<?= htmlspecialchars($kar->bank_account ?? ''); ?>">
                                    <?= htmlspecialchars($kar->nama_karyawan); ?><?= !empty($kar->nip) ? ' (' . htmlspecialchars($kar->nip) . ')' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted"><?= lang_line('erf_karyawan_hint', 'Bank & No. Rekening otomatis terisi dari Master Karyawan.') ?></small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="small font-weight-bold"><?= lang_line('prf_lbl_bank_name', 'Nama Bank') ?></label>
                        <input type="text" id="karyawan_bank_name" class="form-control" readonly>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="small font-weight-bold"><?= lang_line('prf_lbl_bank_account', 'No. Rekening') ?></label>
                        <input type="text" id="karyawan_bank_account" class="form-control" readonly>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold"><?= lang_line('erf_label_notes', 'Catatan') ?></label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="<?= lang_line('erf_create_notes_ph', 'Notes opsional...') ?>"></textarea>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header py-3 d-flex justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('erf_card_items', 'Items') ?></h6>
                <button type="button" class="btn btn-sm btn-success" id="btn-add-row"><i class="fas fa-plus mr-1"></i> <?= lang_line('erf_btn_add_item', 'Tambah Item') ?></button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" id="items-table">
                        <thead class="thead-light text-center">
                            <tr>
                                <th width="3%">#</th>
                                <th width="11%"><?= lang_line('erf_th_expense_date', 'Expense Date') ?></th>
                                <th width="12%"><?= lang_line('erf_th_category', 'Category') ?></th>
                                <th width="12%"><?= lang_line('erf_th_receipt', 'Receipt #') ?></th>
                                <th><?= lang_line('erf_th_description', 'Description') ?> <span class="text-danger">*</span></th>
                                <th width="8%"><?= lang_line('erf_th_qty', 'Qty') ?> <span class="text-danger">*</span></th>
                                <th width="12%"><?= lang_line('erf_th_unit_price', 'Unit Price') ?> <span class="text-danger">*</span></th>
                                <th width="12%"><?= lang_line('erf_th_subtotal', 'Subtotal') ?></th>
                                <th width="4%"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body"></tbody>
                        <tfoot>
                            <tr class="font-weight-bold">
                                <td colspan="7" class="text-right"><?= lang_line('erf_grand_total', 'Grand Total') ?></td>
                                <td class="text-right"><span id="grand-total">0</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('erf'); ?>" class="btn btn-secondary"><?= lang_line('erf_btn_cancel', 'Batal') ?></a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> <?= lang_line('erf_btn_save_draft', 'Simpan Draft') ?></button>
        </div>
    </form>
</div>

<script>
const I18N_ERFC = {
    category_ph: <?= json_encode(lang_line('erf_create_category_ph', 'Transport / Meal / dll')) ?>,
    receipt_ph:  <?= json_encode(lang_line('erf_create_receipt_ph', 'No bon')) ?>
};
(function(){
    const $body = document.getElementById('items-body');
    let idx = 0;
    function rowHtml(i) {
        return `<tr data-idx="${i}">
            <td class="text-center align-middle row-no">${i+1}</td>
            <td><input type="date" name="items[${i}][expense_date]" class="form-control form-control-sm"></td>
            <td><input type="text" name="items[${i}][category]" class="form-control form-control-sm" placeholder="${I18N_ERFC.category_ph}"></td>
            <td><input type="text" name="items[${i}][receipt_number]" class="form-control form-control-sm" placeholder="${I18N_ERFC.receipt_ph}"></td>
            <td><input type="text" name="items[${i}][description]" class="form-control form-control-sm" required></td>
            <td><input type="number" step="0.01" min="0.01" name="items[${i}][qty]" class="form-control form-control-sm text-right qty" value="1" required></td>
            <td><input type="text" name="items[${i}][unit_price]" class="form-control form-control-sm text-right price" value="0" required></td>
            <td class="text-right align-middle"><span class="subtotal">0</span></td>
            <td class="text-center align-middle"><button type="button" class="btn btn-sm btn-link text-danger remove-row"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    }
    function addRow() { $body.insertAdjacentHTML('beforeend', rowHtml(idx)); idx++; recalc(); }
    function recalc() {
        let grand = 0;
        document.querySelectorAll('#items-body tr').forEach(tr => {
            const q = parseFloat(tr.querySelector('.qty').value) || 0;
            const p = parseFloat(String(tr.querySelector('.price').value).replace(/,/g,'')) || 0;
            const sub = q * p;
            tr.querySelector('.subtotal').textContent = sub.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
            grand += sub;
        });
        document.getElementById('grand-total').textContent = grand.toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
    }
    document.getElementById('btn-add-row').addEventListener('click', addRow);
    document.addEventListener('input', e => {
        if (e.target.matches('.qty,.price')) recalc();
    });
    document.addEventListener('click', e => {
        if (e.target.closest('.remove-row')) {
            e.target.closest('tr').remove();
            document.querySelectorAll('#items-body tr .row-no').forEach((td, i) => td.textContent = i+1);
            recalc();
        }
    });
    addRow();

    function fillKaryawanBank() {
        const sel = document.getElementById('karyawan_id');
        if (!sel) return;
        const opt = sel.options[sel.selectedIndex];
        document.getElementById('karyawan_bank_name').value = opt ? (opt.dataset.bankName || '') : '';
        document.getElementById('karyawan_bank_account').value = opt ? (opt.dataset.bankAccount || '') : '';
    }
    // Select2 untuk pencarian karyawan; pakai jQuery change agar autofill bank tetap jalan.
    $(function() {
        $('#karyawan_id').select2({
            width: '100%',
            placeholder: <?= json_encode(lang_line('erf_select_karyawan', '-- Pilih Karyawan --')) ?>,
            allowClear: true
        }).on('change', fillKaryawanBank);
        fillKaryawanBank();
    });
})();
</script>
