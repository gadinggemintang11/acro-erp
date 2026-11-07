<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('apinvoice/store'); ?>" method="POST" id="ap-form" onsubmit="return confirm('<?= addslashes(lang_line('text_save')); ?> <?= addslashes(lang_line('ap_invoice_no')); ?>?');">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('apinv_form_header'); ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('apinv_label_type'); ?> *</label>
                            <select id="invoice_type" name="invoice_type" class="form-control" required>
                                <option value=""><?= lang_line('apinv_select_type'); ?></option>
                                <option value="GRN_BASED"><?= lang_line('apinv_grn_based_option'); ?></option>
                                <option value="NON_GRN"><?= lang_line('apinv_non_grn_option'); ?></option>
                                <option value="PO_BASED"><?= lang_line('apinv_po_based_option', 'Berdasarkan PO (barang sudah datang)'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('ap_invoice_supplier'); ?> *</label>
                            <select id="supplier_id" name="supplier_id" class="form-control" required>
                                <option value=""><?= lang_line('acct_select_supplier'); ?></option>
                                <?php foreach($suppliers as $s): ?>
                                    <option value="<?= $s->id; ?>"><?= $s->nama_supplier; ?> (<?= $s->jenis; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><?= lang_line('ap_invoice_no'); ?></label>
                            <div class="input-group">
                                <input type="text" id="invoice_no" name="invoice_no" class="form-control" placeholder="<?= htmlspecialchars(lang_line('apinv_input_auto_generated')); ?>">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-generate-invoice" onclick="generateInvoiceNumber()">
                                        <i class="fas fa-sync-alt"></i> Generate
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted"><?= lang_line('apinv_input_format_hint'); ?></small>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('ap_invoice_date'); ?> *</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= str_replace(':', '', lang_line('acct_due_date_colon')); ?> *</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('apinv_label_ppn_rate'); ?></label>
                            <input type="number" id="ppn_rate" name="ppn_rate" class="form-control" value="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label><?= lang_line('apinv_label_pph_rate'); ?></label>
                            <input type="number" id="pph_rate" name="pph_rate" class="form-control" value="0" step="0.01">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRN Based -->
        <div class="card shadow mb-4" id="detail-grn-card" style="display: none;">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('apinv_grn_detail'); ?></h6>
                <small class="text-muted d-block mt-2"><?= lang_line('apinv_grn_detail_note'); ?></small>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label><?= lang_line('apinv_label_pick_grn'); ?></label>
                    <select id="grn_ids" name="grn_ids[]" class="form-control" multiple></select>
                </div>
                <button type="button" id="btn-load-grn" class="btn btn-info"><?= lang_line('apinv_btn_pull_grn'); ?></button>
                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th><?= lang_line('apinv_th_desc_item'); ?></th>
                                <th style="width: 12%;"><?= lang_line('acct_th_qty'); ?></th>
                                <th style="width: 18%;"><?= lang_line('acct_th_unit_price'); ?></th>
                                <th style="width: 18%;"><?= lang_line('acct_th_total'); ?></th>
                                <th style="width: 18%;"><?= lang_line('acct_label_acct_debet'); ?></th>
                                <th style="width: 18%;"><?= lang_line('acct_label_acct_kredit'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="detail-body-grn"></tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Berdasarkan PO (2026-11-06): qty maks = barang sudah datang & belum ditagih -->
        <div class="card shadow mb-4" id="detail-po-card" style="display: none;">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('apinv_po_detail', 'Detail dari Purchase Order'); ?></h6>
                <small class="text-muted d-block mt-2"><?= lang_line('apinv_po_detail_note', 'Qty maksimal = barang yang sudah datang (penerimaan barang / penyelesaian jasa / aset diterima) dikurangi yang sudah ditagih (AP lain / Payment Request). PO Jasa boleh ditagih tanpa penerimaan. Harga = harga PO tanpa PPN. Akun debit terisi otomatis dari Setting Akun → Pemetaan Akun Barang dan masih bisa diganti.'); ?></small>
            </div>
            <div class="card-body">
                <div class="form-row align-items-end">
                    <div class="col-md-9 form-group">
                        <label><?= lang_line('apinv_label_pick_po', 'Pilih PO'); ?></label>
                        <select id="po_pick" class="form-control"></select>
                    </div>
                    <div class="col-md-3 form-group">
                        <button type="button" id="btn-load-po" class="btn btn-info btn-block"><?= lang_line('apinv_btn_pull_po', 'Tarik Baris PO'); ?></button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th><?= lang_line('apinv_th_desc_item'); ?></th>
                                <th style="width: 12%;"><?= lang_line('acct_th_qty'); ?></th>
                                <th style="width: 15%;"><?= lang_line('acct_th_unit_price'); ?></th>
                                <th style="width: 15%;"><?= lang_line('acct_th_total'); ?></th>
                                <th style="width: 25%;"><?= lang_line('acct_label_acct_debet'); ?></th>
                                <th style="width: 5%;"></th>
                            </tr>
                        </thead>
                        <tbody id="detail-body-po"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Jasa / Non-GRN -->
        <div class="card shadow mb-4" id="detail-jasa-card" style="display: none;">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('apinv_jasa_detail'); ?></h6>
                <button type="button" id="add-row-jasa" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i></button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 35%;"><?= lang_line('apinv_jasa_acct_coa'); ?></th>
                                <th><?= lang_line('apinv_jasa_desc'); ?></th>
                                <th style="width: 15%;"><?= lang_line('apinv_jasa_qty'); ?></th>
                                <th style="width: 20%;"><?= lang_line('apinv_jasa_price'); ?></th>
                                <th style="width: 5%;"><?= lang_line('acct_th_action'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="detail-body-jasa"></tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Total Section -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label"><?= lang_line('acct_subtotal'); ?></label>
                            <div class="col-sm-8">
                                <input type="text" id="subtotal" class="form-control text-right" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label"><?= lang_line('acct_ppn'); ?></label>
                            <div class="col-sm-8">
                                <input type="text" id="ppn_amount" class="form-control text-right" readonly>
                            </div>
                        </div>
                         <div class="form-group row">
                            <label class="col-sm-4 col-form-label"><?= lang_line('acct_pph'); ?></label>
                            <div class="col-sm-8">
                                <input type="text" id="pph_amount" class="form-control text-right text-danger" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label"><?= lang_line('acct_grand_total'); ?></label>
                            <div class="col-sm-8">
                                <input type="text" id="grand_total" class="form-control text-right font-weight-bold" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('apinvoice'); ?>" class="btn btn-secondary"><?= lang_line('acct_btn_cancel'); ?></a>
            <button type="submit" class="btn btn-primary"><?= lang_line('apinv_btn_save_draft'); ?></button>
        </div>
    </form>
</div>

<template id="template-jasa-row">
    <tr>
        <td>
            <select name="details[0][account_id]" class="form-control jasa-account" required>
                <option value=""><?= lang_line('acct_select_account_expense'); ?></option>
                <?php foreach($accounts as $acc): ?>
                    <option value="<?= $acc->id; ?>"><?= $acc->account_code; ?> - <?= $acc->account_name; ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="details[0][description]" class="form-control" required></td>
        <td><input type="number" name="details[0][qty]" class="form-control qty-jasa" value="1" min="1" step="1"></td>
        <td><input type="number" name="details[0][unit_price]" class="form-control price-jasa" value="0" min="0" step="0.01"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>

<script>
const I18N_APINV = {
    loading:              <?= json_encode(lang_line('acct_loading')); ?>,
    select_supplier_first:<?= json_encode(lang_line('acct_select_supplier_first')); ?>,
    no_grn_to_bill:       <?= json_encode(lang_line('acct_no_grn_to_bill')); ?>,
    no_grn_items:         <?= json_encode(lang_line('acct_no_grn_items')); ?>,
    failed_load_grn:      <?= json_encode(lang_line('acct_failed_load_grn')); ?>,
    alert_select_grn_first:<?= json_encode(lang_line('acct_alert_select_grn_first')); ?>,
    select_short:         <?= json_encode('-- '. lang_line('text_select_item') .' --'); ?>,
    no_po_to_bill:        <?= json_encode(lang_line('apinv_no_po_to_bill', 'Tidak ada PO yang bisa ditagih untuk supplier ini.')); ?>
};
$(function() {
    let jasaRowIndex = 0;
    let poRowIndex = 0;
    
    // ✨ NEW: Store account options dari PHP ke JS
    let debetAccounts = <?= json_encode($debet_accounts); ?>;
    let kreditAccounts = <?= json_encode($kredit_accounts); ?>;

    $('#invoice_type').change(function() {
        const type = $(this).val();
        resetTotals();
        $('#detail-po-card').toggle(type === 'PO_BASED');
        if (type === 'PO_BASED') {
            $('#detail-grn-card, #detail-jasa-card').hide();
            $('#pph_rate').val(0).prop('readonly', false);
            loadBillablePos();
        } else if (type === 'GRN_BASED') {
            $('#detail-grn-card').show();
            $('#detail-jasa-card').hide();
            $('#pph_rate').val(0).prop('readonly', true);
            loadPendingGRN();
        } else if (type === 'NON_GRN') {
            $('#detail-grn-card').hide();
            $('#detail-jasa-card').show();
            $('#pph_rate').val(2).prop('readonly', false);
            if ($('#detail-body-jasa tr').length === 0) addRowJasa();
        } else {
            $('#detail-grn-card, #detail-jasa-card').hide();
        }
    });

    $('#supplier_id').change(function() {
        if ($('#invoice_type').val() === 'GRN_BASED') loadPendingGRN();
        if ($('#invoice_type').val() === 'PO_BASED') { $('#detail-body-po').empty(); calculateTotal(); loadBillablePos(); }
    });

    function loadBillablePos() {
        const supplierId = $('#supplier_id').val();
        const sel = $('#po_pick');
        if (!supplierId) return sel.html('<option value="">' + I18N_APINV.select_supplier_first + '</option>');
        sel.html('<option value="">' + I18N_APINV.loading + '</option>');
        $.getJSON('<?= site_url('apinvoice/ajax_get_billable_pos'); ?>', { supplier_id: supplierId }, function(res) {
            sel.html('');
            if (res.success && res.data.length) {
                res.data.forEach(p => sel.append(`<option value="${p.id}">${p.po_number} (${p.lines_open} baris, Rp ${Number(p.amount_open).toLocaleString('id-ID')})</option>`));
            } else sel.html('<option value="">' + I18N_APINV.no_po_to_bill + '</option>');
        });
    }

    $('#btn-load-po').click(function() {
        const poId = $('#po_pick').val();
        if (!poId) return alert(I18N_APINV.no_po_to_bill);
        $.getJSON('<?= site_url('apinvoice/ajax_get_po_lines'); ?>', { po_id: poId }, function(res) {
            if (!res.success || !res.data.length) return alert(I18N_APINV.no_po_to_bill);
            if (res.ppn_rate > 0) $('#ppn_rate').val(res.ppn_rate);
            res.data.forEach(function(l) {
                if ($('#detail-body-po input[data-pdid="' + l.po_detail_id + '"]').length) return;
                const idx = poRowIndex++;
                let dd = '<select name="details[' + idx + '][debet_account_id]" class="form-control form-control-sm debet-account" required><option value="">' + I18N_APINV.select_short + '</option>';
                debetAccounts.forEach(a => { dd += '<option value="' + a.id + '"' + (String(a.id) === String(l.account_id) ? ' selected' : '') + '>' + a.account_code + ' - ' + a.account_name + '</option>'; });
                dd += '</select>';
                const desc = $('<div>').text(l.description).html().replace(/"/g, '&quot;');
                $('#detail-body-po').append(`<tr>
                    <td>${desc} <small class="text-muted">(${l.po_number}, maks ${l.remaining_qty})</small>
                        <input type="hidden" name="details[${idx}][po_detail_id]" value="${l.po_detail_id}" data-pdid="${l.po_detail_id}">
                        <input type="hidden" name="details[${idx}][description]" value="${desc}"></td>
                    <td><input type="number" name="details[${idx}][qty]" class="form-control form-control-sm qty-po" value="${l.remaining_qty}" min="0.01" max="${l.remaining_qty}" step="0.01"></td>
                    <td><input type="number" name="details[${idx}][unit_price]" class="form-control form-control-sm price-po" value="${l.dpp_unit_price}" readonly></td>
                    <td><input type="text" class="form-control form-control-sm text-right total-po" readonly></td>
                    <td>${dd}</td>
                    <td><button type="button" class="btn btn-danger btn-sm remove-po"><i class="fas fa-trash"></i></button></td>
                </tr>`);
            });
            initAccountSelect2($('#detail-body-po'));
            calculateTotal();
        });
    });
    $('#detail-body-po').on('input', '.qty-po', calculateTotal);
    $('#detail-body-po').on('click', '.remove-po', function() { $(this).closest('tr').remove(); calculateTotal(); });

    function loadPendingGRN() {
        const supplierId = $('#supplier_id').val();
        const grnSelect = $('#grn_ids');
        grnSelect.html('<option>' + I18N_APINV.loading + '</option>');
        if (!supplierId) return grnSelect.html('<option>' + I18N_APINV.select_supplier_first + '</option>');
        $.getJSON('<?= site_url('apinvoice/ajax_get_pending_grn'); ?>', { supplier_id: supplierId }, function(res) {
            grnSelect.html('');
            if (res.success && res.data.length) {
                res.data.forEach(g => {
                    const docType = g.base_on || 'N/A';
                    const docNumber = g.doc_number || 'N/A';
                    grnSelect.append(`<option value="${g.id}">${g.grn_number} (${docType}: ${docNumber}) - ${g.grn_date}</option>`);
                });
            } else grnSelect.html('<option>' + I18N_APINV.no_grn_to_bill + '</option>');
        });
    }

    $('#btn-load-grn').click(function() {
        const ids = $('#grn_ids').val();
        if (!ids || !ids.length) return alert(I18N_APINV.alert_select_grn_first);
        $.post('<?= site_url('apinvoice/ajax_get_grn_details'); ?>', { grn_ids: ids }, function(res) {
            $('#detail-body-grn').empty();
            console.log('ajax_get_grn_details response:', res);
            if (res && res.success && res.data && res.data.length) {
                res.data.forEach((i, idx) => {
                    const total = i.qty * i.unit_price;
                    
                    // ✨ NEW: Build dropdown untuk debet account
                    let debetDropdown = '<select name="details[' + idx + '][debet_account_id]" class="form-control form-control-sm debet-account" required>\n';
                    debetDropdown += '  <option value="">' + I18N_APINV.select_short + '</option>\n';
                    debetAccounts.forEach(function(acc) {
                        debetDropdown += '  <option value="' + acc.id + '"' + (String(acc.id) === String(i.default_account_id) ? ' selected' : '') + '>' + acc.account_code + ' - ' + acc.account_name + '</option>\n';
                    });
                    debetDropdown += '</select>';
                    
                    // ✨ NEW: Build dropdown untuk kredit account
                    let kreditDropdown = '<select name="details[' + idx + '][kredit_account_id]" class="form-control form-control-sm kredit-account" required>\n';
                    kreditDropdown += '  <option value="">-- Pilih --</option>\n';
                    kreditAccounts.forEach(function(acc) {
                        kreditDropdown += '  <option value="' + acc.id + '">' + acc.account_code + ' - ' + acc.account_name + '</option>\n';
                    });
                    kreditDropdown += '</select>';
                    
                    $('#detail-body-grn').append(`
                        <tr>
                            <td>${i.description}
                                <input type="hidden" name="details[${idx}][description]" value="${i.description}">
                                <input type="hidden" name="details[${idx}][grn_detail_id]" value="${i.grn_detail_id}">
                                <input type="hidden" name="details[${idx}][item_id]" value="${i.item_id}">
                                <input type="hidden" name="details[${idx}][item_type]" value="${i.item_type}">
                            </td>
                            <td><input type="number" name="details[${idx}][qty]" class="form-control" value="${i.qty}" readonly></td>
                            <td><input type="number" name="details[${idx}][unit_price]" class="form-control" value="${i.unit_price}" readonly></td>
                            <td><input type="text" class="form-control text-right" value="${total.toFixed(2)}" readonly></td>
                            <td>${debetDropdown}</td>
                            <td>${kreditDropdown}</td>
                        </tr>`);
                });
                initAccountSelect2($('#detail-body-grn'));
                calculateTotal();
            } else {
                const msg = (res && res.message) ? res.message : I18N_APINV.no_grn_items;
                alert(msg);
            }
        }, 'json').fail(function(xhr, status, err) {
            alert(I18N_APINV.failed_load_grn);
            console.error('AJAX error loading GRN details:', status, err, xhr.responseText);
        });
    });

    // Inisialisasi Select2 untuk dropdown akun (debet/kredit/COA) yang baru ditambahkan
    function initAccountSelect2($scope) {
        $scope.find('select.debet-account, select.kredit-account, select.jasa-account').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) return; // sudah diinit
            $(this).select2({
                width: '100%',
                placeholder: I18N_APINV.select_short
            });
        });
    }

    function addRowJasa() {
        let tpl = $('#template-jasa-row').html();
        tpl = tpl.replace(/details\[0\]/g, 'details[' + jasaRowIndex + ']');
        const $row = $(tpl);
        $('#detail-body-jasa').append($row);
        initAccountSelect2($row);
        jasaRowIndex++;
    }
    $('#add-row-jasa').click(addRowJasa);

    $('#detail-body-jasa').on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        calculateTotal();
    });

    $('#detail-body-jasa').on('input', '.qty-jasa, .price-jasa', calculateTotal);
    $('#ppn_rate, #pph_rate').on('input', calculateTotal);

    function resetTotals() {
        $('#subtotal, #ppn_amount, #pph_amount, #grand_total').val('0.00');
    }

    function calculateTotal() {
        let subtotal = 0, type = $('#invoice_type').val();
        if (type === 'GRN_BASED') {
            $('#detail-body-grn tr').each(function() {
                const val = parseFloat($(this).find('input[type="text"]').val()) || 0;
                subtotal += val;
            });
        } else if (type === 'PO_BASED') {
            $('#detail-body-po tr').each(function() {
                const q = parseFloat($(this).find('.qty-po').val()) || 0;
                const p = parseFloat($(this).find('.price-po').val()) || 0;
                $(this).find('.total-po').val((q * p).toFixed(2));
                subtotal += q * p;
            });
        } else if (type === 'NON_GRN') {
            $('#detail-body-jasa tr').each(function() {
                const qty = parseFloat($(this).find('.qty-jasa').val()) || 0;
                const price = parseFloat($(this).find('.price-jasa').val()) || 0;
                subtotal += qty * price;
            });
        }

        const ppnRate = parseFloat($('#ppn_rate').val()) || 0;
        const pphRate = parseFloat($('#pph_rate').val()) || 0;

        const ppn = subtotal * (ppnRate / 100);
        const pph = subtotal * (pphRate / 100);
        const total = subtotal + ppn - pph;

        $('#subtotal').val(subtotal.toFixed(2));
        $('#ppn_amount').val(ppn.toFixed(2));
        $('#pph_amount').val(pph.toFixed(2));
        $('#grand_total').val(total.toFixed(2));
    }

    // Auto-generate invoice number on page load
    $(document).ready(function() {
        generateInvoiceNumber();
    });
});

function generateInvoiceNumber() {
    $.ajax({
        url: '<?= base_url('apinvoice/generate_invoice_number_ajax') ?>',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#invoice_no').val(response.invoice_no);
        },
        error: function() {
            console.error('Failed to generate invoice number');
        }
    });
}
</script>
