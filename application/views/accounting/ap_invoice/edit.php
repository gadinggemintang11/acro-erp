<?php
$type   = $header->invoice_type;
$is_po  = ($type === 'PO_BASED');
$is_grn = ($type === 'GRN_BASED');
$is_non = ($type === 'NON_GRN');
$type_label = $is_po ? lang_line('ap_invoice_po_based', 'Berdasarkan PO') : ($is_grn ? 'Berdasarkan GRN' : lang_line('ap_invoice_service_subcon', 'Jasa / Non-GRN'));
$acc_options = function ($list, $selected) {
    $html = '';
    foreach ($list as $a) {
        $html .= '<option value="' . (int) $a->id . '"' . ((int) $selected === (int) $a->id ? ' selected' : '') . '>'
               . htmlspecialchars($a->account_code . ' - ' . $a->account_name) . '</option>';
    }
    return $html;
};
$fmt_qty = function ($v) { return rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.'); };
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($title); ?></h1>
        <a href="<?= base_url('apinvoice/view/' . $header->id); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> <?= lang_line('acct_btn_cancel', 'Batal'); ?></a>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <?php if (!empty($journal_no)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-undo mr-1"></i> Faktur hasil <strong>redraft</strong>. Jurnal <strong><?= htmlspecialchars($journal_no); ?></strong> berstatus Draft
            (tidak masuk laporan) dan akan diperbarui otomatis saat faktur diposting ulang.
        </div>
    <?php endif; ?>

    <?php if (!empty($ppn_notice)): ?>
        <div class="alert alert-<?= $ppn_notice['type']; ?>"><i class="fas fa-percent mr-1"></i> <?= htmlspecialchars($ppn_notice['text']); ?></div>
    <?php endif; ?>

    <form action="<?= base_url('apinvoice/update/' . $header->id); ?>" method="POST" id="ap-edit-form">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><?= lang_line('apinv_form_header', 'Header Faktur'); ?></h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('ap_invoice_supplier', 'Supplier'); ?></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($header->nama_supplier); ?>" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('apinv_label_type', 'Tipe Faktur'); ?></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($type_label); ?>" readonly>
                    </div>
                    <div class="col-md-4 form-group">
                        <label><?= lang_line('ap_invoice_no', 'No. Faktur'); ?> *</label>
                        <input type="text" name="invoice_no" class="form-control" value="<?= htmlspecialchars($header->invoice_no); ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 form-group">
                        <label><?= lang_line('ap_invoice_date', 'Tgl. Faktur'); ?> *</label>
                        <input type="date" name="invoice_date" class="form-control" value="<?= htmlspecialchars($header->invoice_date); ?>" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label><?= str_replace(':', '', lang_line('acct_due_date_colon', 'Tgl. Jatuh Tempo')); ?> *</label>
                        <input type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($header->due_date); ?>" required>
                    </div>
                    <div class="col-md-3 form-group">
                        <label><?= lang_line('apinv_label_ppn_rate', 'PPN (%)'); ?></label>
                        <input type="number" id="ppn_rate" name="ppn_rate" class="form-control" value="<?= $rates['ppn']; ?>" min="0" max="100" step="0.01" <?= $is_non ? '' : 'readonly'; ?>>
                        <?php if (!$is_non): ?><small class="form-text text-muted"><?= lang_line('apinv_ppn_from_po', 'PPN otomatis mengikuti PO.'); ?></small><?php endif; ?>
                    </div>
                    <div class="col-md-3 form-group">
                        <label><?= lang_line('apinv_label_pph_rate', 'Potong PPh 23 (%)'); ?></label>
                        <input type="number" id="pph_rate" name="pph_rate" class="form-control" value="<?= $rates['pph']; ?>" min="0" max="100" step="0.01">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('acct_invoice_items', 'Detail Item Faktur'); ?></h6>
                    <small class="text-muted">
                        <?php if ($is_po): ?>
                            Qty maksimal = sisa yang bisa ditagih dari PO (faktur ini tidak ikut dihitung). Harga dikunci ke harga PO tanpa PPN.
                        <?php elseif ($is_grn): ?>
                            Qty hanya bisa dikurangi (maks. qty awal). Untuk menambah tagihan GRN lain, buat faktur baru.
                        <?php else: ?>
                            Baris bisa diubah, dihapus, atau ditambah.
                        <?php endif; ?>
                        Centang <strong>Hapus</strong> untuk membuang baris.
                    </small>
                </div>
                <?php if ($is_non): ?>
                    <button type="button" id="add-row" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= lang_line('ju_btn_add_row', 'Tambah Baris'); ?></button>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="lines-table">
                        <thead class="thead-light">
                            <tr>
                                <?php if ($is_non): ?><th style="width:26%"><?= lang_line('apinv_jasa_acct_coa', 'Akun (COA)'); ?></th><?php endif; ?>
                                <th><?= lang_line('apinv_th_desc_item', 'Deskripsi'); ?></th>
                                <th style="width:11%" class="text-right"><?= lang_line('acct_th_qty', 'Qty'); ?></th>
                                <th style="width:13%" class="text-right"><?= lang_line('acct_th_unit_price', 'Harga Satuan'); ?></th>
                                <th style="width:13%" class="text-right"><?= lang_line('acct_th_total', 'Total'); ?></th>
                                <?php if (!$is_non): ?><th style="width:20%"><?= lang_line('acct_label_acct_debet', 'Akun DEBET'); ?> *</th><?php endif; ?>
                                <?php if ($is_grn): ?><th style="width:18%"><?= lang_line('acct_label_acct_kredit', 'Akun KREDIT'); ?> *</th><?php endif; ?>
                                <th style="width:6%" class="text-center">Hapus</th>
                            </tr>
                        </thead>
                        <tbody id="lines-body">
                            <?php foreach ($details as $d): $k = (int) $d->id; $max = $max_qty[$k] ?? null; ?>
                            <tr class="line-row" data-ppn="<?= ($is_non || empty($d->ppn_exempt)) ? 1 : 0; ?>">
                                <?php if ($is_non): ?>
                                <td><select name="details[<?= $k; ?>][account_id]" class="form-control form-control-sm acc-select" required>
                                    <option value="">-- Pilih --</option><?= $acc_options($accounts, $d->account_id); ?></select></td>
                                <?php endif; ?>
                                <td><input type="text" name="details[<?= $k; ?>][description]" class="form-control form-control-sm" value="<?= htmlspecialchars($d->description); ?>" required>
                                    <?php if (!$is_non && !empty($d->ppn_exempt)): ?><span class="badge badge-secondary"><?= lang_line('apinv_badge_no_ppn', 'tanpa PPN'); ?></span><?php endif; ?></td>
                                <td>
                                    <input type="number" name="details[<?= $k; ?>][qty]" class="form-control form-control-sm text-right qty"
                                           value="<?= $fmt_qty($d->qty); ?>" min="0.0001" step="any" <?= $max !== null ? 'max="' . $fmt_qty($max) . '"' : ''; ?> required>
                                    <?php if ($max !== null): ?><small class="text-muted">maks <?= $fmt_qty($max); ?></small><?php endif; ?>
                                </td>
                                <td><input type="number" name="details[<?= $k; ?>][unit_price]" class="form-control form-control-sm text-right price"
                                           value="<?= (float) $d->unit_price; ?>" min="0" step="any" <?= $is_non ? '' : 'readonly'; ?>></td>
                                <td class="text-right align-middle line-total">0</td>
                                <?php if (!$is_non): ?>
                                <td><select name="details[<?= $k; ?>][debet_account_id]" class="form-control form-control-sm acc-select" required>
                                    <option value="">-- Pilih --</option><?= $acc_options($debet_accounts, $d->debet_account_id ?: $d->account_id); ?></select></td>
                                <?php endif; ?>
                                <?php if ($is_grn): ?>
                                <td><select name="details[<?= $k; ?>][kredit_account_id]" class="form-control form-control-sm acc-select" required>
                                    <option value="">-- Pilih --</option><?= $acc_options($kredit_accounts, $d->kredit_account_id); ?></select></td>
                                <?php endif; ?>
                                <td class="text-center align-middle">
                                    <input type="checkbox" name="details[<?= $k; ?>][remove]" value="1" class="remove-line" title="Hapus baris ini">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (!$is_non && !empty($extras_supported)): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="m-0 font-weight-bold text-primary"><?= lang_line('apinv_extra_title', 'Biaya Tambahan (di luar PO)'); ?></h6>
                    <small class="text-muted"><?= lang_line('apinv_extra_note', 'Mis. biaya kirim yang ditagih supplier tapi tidak ada di PO. Dijurnal ke akun yang dipilih dan masuk total Hutang Dagang. Hapus centang "Kena PPN" bila biaya ini tidak dikenai PPN.'); ?></small>
                </div>
                <button type="button" id="add-extra" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> <?= lang_line('apinv_extra_add', 'Tambah Biaya'); ?></button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:32%"><?= lang_line('apinv_extra_account', 'Akun'); ?></th>
                                <th><?= lang_line('apinv_extra_desc', 'Keterangan'); ?></th>
                                <th style="width:18%" class="text-right"><?= lang_line('apinv_extra_amount', 'Nominal'); ?></th>
                                <th style="width:10%" class="text-center"><?= lang_line('apinv_extra_ppn', 'Kena PPN'); ?></th>
                                <th style="width:6%"></th>
                            </tr>
                        </thead>
                        <tbody id="extra-body">
                            <?php foreach (($extras ?? []) as $x): $xk = 'e' . (int) $x->id; ?>
                            <tr class="extra-row">
                                <td><select name="extras[<?= $xk; ?>][account_id]" class="form-control form-control-sm acc-select" required>
                                    <option value="">-- Pilih --</option><?= $acc_options($debet_accounts, $x->debet_account_id ?: $x->account_id); ?></select></td>
                                <td><input type="text" name="extras[<?= $xk; ?>][description]" class="form-control form-control-sm" value="<?= htmlspecialchars($x->description); ?>" required></td>
                                <td><input type="number" name="extras[<?= $xk; ?>][amount]" class="form-control form-control-sm text-right extra-amount" value="<?= (float) $x->total_price; ?>" min="0" step="0.01" required></td>
                                <td class="text-center align-middle"><input type="checkbox" name="extras[<?= $xk; ?>][ppn]" value="1" class="extra-ppn" <?= empty($x->ppn_exempt) ? 'checked' : ''; ?>></td>
                                <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-del-extra" title="Hapus biaya"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        <table class="table table-sm mb-0">
                            <tr><th><?= lang_line('acct_subtotal', 'Subtotal'); ?></th><td class="text-right" id="t-subtotal">0</td></tr>
                            <tr><th><?= lang_line('acct_ppn', 'PPN'); ?></th><td class="text-right" id="t-ppn">0</td></tr>
                            <tr><th><?= lang_line('acct_pph', 'PPh'); ?></th><td class="text-right text-danger" id="t-pph">0</td></tr>
                            <tr class="font-weight-bold bg-light"><th><?= lang_line('acct_grand_total', 'Grand Total'); ?></th><td class="text-right" id="t-grand">0</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mb-4">
            <a href="<?= base_url('apinvoice/view/' . $header->id); ?>" class="btn btn-secondary"><?= lang_line('acct_btn_cancel', 'Batal'); ?></a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Perubahan (Draft)</button>
        </div>
    </form>
</div>

<?php if ($is_non): ?>
<template id="non-row-tpl">
    <tr class="line-row new-row">
        <td><select name="details[__K__][account_id]" class="form-control form-control-sm acc-select" required>
            <option value="">-- Pilih --</option><?= $acc_options($accounts, 0); ?></select></td>
        <td><input type="text" name="details[__K__][description]" class="form-control form-control-sm" required></td>
        <td><input type="number" name="details[__K__][qty]" class="form-control form-control-sm text-right qty" value="1" min="0.0001" step="any" required></td>
        <td><input type="number" name="details[__K__][unit_price]" class="form-control form-control-sm text-right price" value="0" min="0" step="any"></td>
        <td class="text-right align-middle line-total">0</td>
        <td class="text-center align-middle"><button type="button" class="btn btn-danger btn-sm btn-del-new" title="Hapus baris"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>
<?php endif; ?>

<?php if (!$is_non && !empty($extras_supported)): ?>
<template id="extra-row-tpl">
    <tr class="extra-row">
        <td><select name="extras[__X__][account_id]" class="form-control form-control-sm acc-select" required>
            <option value="">-- Pilih --</option><?= $acc_options($debet_accounts, $default_extra_acct ?? 0); ?></select></td>
        <td><input type="text" name="extras[__X__][description]" class="form-control form-control-sm" value="<?= htmlspecialchars(lang_line('apinv_extra_default_desc', 'Biaya kirim')); ?>" required></td>
        <td><input type="number" name="extras[__X__][amount]" class="form-control form-control-sm text-right extra-amount" value="0" min="0" step="0.01" required></td>
        <td class="text-center align-middle"><input type="checkbox" name="extras[__X__][ppn]" value="1" class="extra-ppn" checked></td>
        <td class="text-center"><button type="button" class="btn btn-danger btn-sm btn-del-extra" title="Hapus biaya"><i class="fas fa-trash"></i></button></td>
    </tr>
</template>
<?php endif; ?>

<script>
$(function () {
    var fmt = function (n) { return (Math.round(n * 100) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    function initSelect($ctx) {
        if ($.fn.select2) $ctx.find('.acc-select').select2({ width: '100%', placeholder: '-- Pilih --' });
    }
    function calc() {
        var sub = 0, taxItems = 0;
        $('#lines-body .line-row').each(function () {
            var $r = $(this);
            var removed = $r.find('.remove-line').is(':checked');
            var t = (parseFloat($r.find('.qty').val()) || 0) * (parseFloat($r.find('.price').val()) || 0);
            $r.find('.line-total').text(fmt(t));
            $r.toggleClass('text-muted', removed).css('text-decoration', removed ? 'line-through' : '');
            $r.find('input:not(.remove-line), select').prop('disabled', removed);
            if (!removed) sub += Math.round(t * 100) / 100;
            // Dasar PPN: baris dari PO tanpa PPN (data-ppn=0) tidak ikut.
            if (!removed && String($r.attr('data-ppn')) !== '0') taxItems += Math.round(t * 100) / 100;
        });
        // Biaya tambahan: masuk subtotal; ikut dasar PPN hanya bila dicentang.
        var taxable = taxItems;
        $('#extra-body .extra-row').each(function () {
            var amt = parseFloat($(this).find('.extra-amount').val()) || 0;
            sub += Math.round(amt * 100) / 100;
            if ($(this).find('.extra-ppn').is(':checked')) taxable += Math.round(amt * 100) / 100;
        });
        var ppn = taxable * (parseFloat($('#ppn_rate').val()) || 0) / 100;
        var pph = sub * (parseFloat($('#pph_rate').val()) || 0) / 100;
        $('#t-subtotal').text(fmt(sub));
        $('#t-ppn').text(fmt(ppn));
        $('#t-pph').text('(-) ' + fmt(pph));
        $('#t-grand').text(fmt(sub + ppn - pph));
    }
    $('#lines-body').on('input change', '.qty, .price, .remove-line', calc);
    $('#ppn_rate, #pph_rate').on('input change', calc);

    var newIdx = 0;
    $('#add-row').on('click', function () {
        var html = document.getElementById('non-row-tpl').innerHTML.replace(/__K__/g, 'new' + (newIdx++));
        var $row = $(html).appendTo('#lines-body');
        initSelect($row);
        calc();
    });
    $('#lines-body').on('click', '.btn-del-new', function () { $(this).closest('tr').remove(); calc(); });

    var extraIdx = 0;
    $('#add-extra').on('click', function () {
        var $row = $(document.getElementById('extra-row-tpl').innerHTML.replace(/__X__/g, 'n' + (extraIdx++))).appendTo('#extra-body');
        initSelect($row);
        calc();
    });
    $('#extra-body').on('click', '.btn-del-extra', function () { $(this).closest('tr').remove(); calc(); });
    $('#extra-body').on('input change', '.extra-amount, .extra-ppn', calc);

    $('#ap-edit-form').on('submit', function () {
        if ($('#lines-body .line-row').filter(function () { return !$(this).find('.remove-line').is(':checked'); }).length === 0) {
            alert('Faktur harus punya minimal satu baris. Kalau semua baris salah, hapus faktur Draft ini.');
            return false;
        }
        // Baris yang dicentang Hapus: input lain disabled (tidak terkirim), tapi flag remove tetap terkirim.
        return confirm('Simpan perubahan faktur? Status tetap Draft sampai Anda klik Posting Jurnal.');
    });

    initSelect($(document));
    calc();
});
</script>
