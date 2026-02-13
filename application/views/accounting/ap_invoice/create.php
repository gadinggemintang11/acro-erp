<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800"><?= $title; ?></h1>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form action="<?= base_url('apinvoice/store'); ?>" method="POST" id="ap-form" onsubmit="return confirm('Simpan faktur sebagai draft?');">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Header Faktur</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Tipe Faktur *</label>
                            <select id="invoice_type" name="invoice_type" class="form-control" required>
                                <option value="">-- Pilih Tipe --</option>
                                <option value="GRN_BASED">1. Berbasis GRN (PO/MIR)</option>
                                <option value="NON_GRN">2. Jasa / Non-GRN (Subkon/Biaya)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Supplier *</label>
                            <select id="supplier_id" name="supplier_id" class="form-control" required>
                                <option value="">-- Pilih Supplier --</option>
                                <?php foreach($suppliers as $s): ?>
                                    <option value="<?= $s->id; ?>"><?= $s->nama_supplier; ?> (<?= $s->jenis; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>No. Faktur Supplier *</label>
                            <input type="text" name="invoice_no" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tgl. Faktur *</label>
                            <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tgl. Jatuh Tempo *</label>
                            <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>PPN (%)</label>
                            <input type="number" id="ppn_rate" name="ppn_rate" class="form-control" value="0" step="0.01">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Potong PPh 23 (%)</label>
                            <input type="number" id="pph_rate" name="pph_rate" class="form-control" value="0" step="0.01">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRN Based -->
        <div class="card shadow mb-4" id="detail-grn-card" style="display: none;">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Detail GRN</h6>
                <small class="text-muted d-block mt-2">⭐ Pilih akun DEBET (Persediaan/Aset) dan KREDIT (Utang) untuk setiap item</small>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Pilih GRN (Penerimaan Barang) yang akan ditagih *</label>
                    <select id="grn_ids" name="grn_ids[]" class="form-control" multiple></select>
                </div>
                <button type="button" id="btn-load-grn" class="btn btn-info">Tarik Item dari GRN</button>
                <hr>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th>Deskripsi (Item)</th>
                                <th style="width: 12%;">Qty</th>
                                <th style="width: 18%;">Harga Satuan</th>
                                <th style="width: 18%;">Total</th>
                                <th style="width: 18%;">Akun DEBET *</th>
                                <th style="width: 18%;">Akun KREDIT *</th>
                            </tr>
                        </thead>
                        <tbody id="detail-body-grn"></tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Jasa / Non-GRN -->
        <div class="card shadow mb-4" id="detail-jasa-card" style="display: none;">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Detail Biaya/Jasa</h6>
                <button type="button" id="add-row-jasa" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i></button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 35%;">Akun COA (Biaya/Aset) *</th>
                                <th>Deskripsi *</th>
                                <th style="width: 15%;">Qty *</th>
                                <th style="width: 20%;">Harga Satuan *</th>
                                <th style="width: 5%;">Aksi</th>
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
                            <label class="col-sm-4 col-form-label">Subtotal</label>
                            <div class="col-sm-8">
                                <input type="text" id="subtotal" class="form-control text-right" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">PPN</label>
                            <div class="col-sm-8">
                                <input type="text" id="ppn_amount" class="form-control text-right" readonly>
                            </div>
                        </div>
                         <div class="form-group row">
                            <label class="col-sm-4 col-form-label">PPh</label>
                            <div class="col-sm-8">
                                <input type="text" id="pph_amount" class="form-control text-right text-danger" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Grand Total</label>
                            <div class="col-sm-8">
                                <input type="text" id="grand_total" class="form-control text-right font-weight-bold" readonly>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('apinvoice'); ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Faktur (Draft)</button>
        </div>
    </form>
</div>

<template id="template-jasa-row">
    <tr>
        <td>
            <select name="details[0][account_id]" class="form-control" required>
                <option value="">-- Pilih Akun Biaya --</option>
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
$(function() {
    let jasaRowIndex = 0;
    
    // ✨ NEW: Store account options dari PHP ke JS
    let debetAccounts = <?= json_encode($debet_accounts); ?>;
    let kreditAccounts = <?= json_encode($kredit_accounts); ?>;

    $('#invoice_type').change(function() {
        const type = $(this).val();
        resetTotals();
        if (type === 'GRN_BASED') {
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
    });

    function loadPendingGRN() {
        const supplierId = $('#supplier_id').val();
        const grnSelect = $('#grn_ids');
        grnSelect.html('<option>Memuat...</option>');
        if (!supplierId) return grnSelect.html('<option>Pilih Supplier Dulu</option>');
        $.getJSON('<?= site_url('apinvoice/ajax_get_pending_grn'); ?>', { supplier_id: supplierId }, function(res) {
            grnSelect.html('');
            if (res.success && res.data.length) {
                res.data.forEach(g => {
                    const docType = g.base_on || 'N/A';
                    const docNumber = g.doc_number || 'N/A';
                    grnSelect.append(`<option value="${g.id}">${g.grn_number} (${docType}: ${docNumber}) - ${g.grn_date}</option>`);
                });
            } else grnSelect.html('<option>Tidak ada GRN yang bisa ditagih</option>');
        });
    }

    $('#btn-load-grn').click(function() {
        const ids = $('#grn_ids').val();
        if (!ids || !ids.length) return alert('Pilih GRN terlebih dahulu.');
        $.post('<?= site_url('apinvoice/ajax_get_grn_details'); ?>', { grn_ids: ids }, function(res) {
            $('#detail-body-grn').empty();
            console.log('ajax_get_grn_details response:', res);
            if (res && res.success && res.data && res.data.length) {
                res.data.forEach((i, idx) => {
                    const total = i.qty * i.unit_price;
                    
                    // ✨ NEW: Build dropdown untuk debet account
                    let debetDropdown = '<select name="details[' + idx + '][debet_account_id]" class="form-control form-control-sm debet-account" required>\n';
                    debetDropdown += '  <option value="">-- Pilih --</option>\n';
                    debetAccounts.forEach(function(acc) {
                        debetDropdown += '  <option value="' + acc.id + '">' + acc.account_code + ' - ' + acc.account_name + '</option>\n';
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
                calculateTotal();
            } else {
                const msg = (res && res.message) ? res.message : 'Tidak ada item GRN ditemukan.';
                alert(msg);
            }
        }, 'json').fail(function(xhr, status, err) {
            alert('Gagal memuat item dari GRN. Cek console untuk detail.');
            console.error('AJAX error loading GRN details:', status, err, xhr.responseText);
        });
    });

    function addRowJasa() {
        let tpl = $('#template-jasa-row').html();
        tpl = tpl.replace(/details\[0\]/g, 'details[' + jasaRowIndex + ']');
        $('#detail-body-jasa').append(tpl);
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
});
</script>
