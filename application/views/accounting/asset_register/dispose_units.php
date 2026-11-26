<?php
$fmt = function ($v) { return number_format((float) $v, 2); };
$acc_opts = function ($sel) use ($accounts) {
    $h = '<option value="">-- Pilih Akun --</option>';
    foreach ($accounts as $x) $h .= '<option value="' . (int) $x->id . '"' . ((int) $sel === (int) $x->id ? ' selected' : '') . '>' . htmlspecialchars($x->account_code . ' - ' . $x->account_name) . '</option>';
    return $h;
};
$o = function ($k, $def = '') use ($old) { return $old[$k] ?? $def; };
$old_harga = (array) ($old['harga'] ?? []);
$tot_buku = 0; $tot_surplus = 0;
foreach ($units as $u) { $tot_buku += (float) $u->nilai_perolehan - (float) $u->akumulasi_penyusutan; $tot_surplus += max(0, (float) ($u->surplus_revaluasi ?? 0)); }
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-sign-out-alt text-danger mr-2"></i><?= htmlspecialchars($title); ?> <small class="text-muted"><?= count($units); ?> unit</small></h1>
        <a href="<?= base_url('asset_register'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <form method="post" action="<?= base_url('asset_register/dispose_units'); ?>" id="bulk-dispose-form" onsubmit="return confirm('Lepas ' + <?= count($units); ?> + ' unit ini? Satu jurnal pelepasan akan dibuat & diposting.');">
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Data Pelepasan</h6></div>
            <div class="card-body">
                <p class="small text-muted">Semua unit dilepas pada tanggal yang sama dengan <strong>satu jurnal</strong>. Nilai buku memakai penyusutan terakhir yang diposting. Laba/rugi dihitung dari total harga jual dikurangi total nilai buku.</p>
                <div class="form-row">
                    <div class="form-group col-md-3"><label class="small font-weight-bold">Tanggal *</label><input type="date" name="disposed_at" class="form-control" value="<?= htmlspecialchars($o('disposed_at', date('Y-m-d'))); ?>" required></div>
                    <div class="form-group col-md-3"><label class="small font-weight-bold">Jenis *</label>
                        <select name="disposal_type" id="disposal_type" class="form-control">
                            <option value="JUAL" <?= $o('disposal_type', 'JUAL') === 'JUAL' ? 'selected' : ''; ?>>Dijual</option>
                            <option value="HAPUS" <?= $o('disposal_type') === 'HAPUS' ? 'selected' : ''; ?>>Dihapus / rusak (write-off)</option>
                        </select></div>
                    <div class="form-group col-md-3 jual-only"><label class="small font-weight-bold">Total Harga Jual</label>
                        <div class="input-group"><input type="number" id="total_harga" class="form-control text-right" min="0" step="0.01" value="0">
                            <div class="input-group-append"><button class="btn btn-outline-secondary" type="button" id="btn-alloc" title="Bagi ke tiap unit sebanding nilai buku">Bagi</button></div></div>
                        <small class="text-muted">Dibagi sebanding nilai buku; harga per unit tetap bisa diubah.</small></div>
                    <div class="form-group col-md-3 jual-only"><label class="small font-weight-bold">Akun Penerimaan *</label><select name="coa_kas_id" class="form-control acc-select"><?= $acc_opts($o('coa_kas_id', 0)); ?></select></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Laba Pelepasan</label><select name="coa_laba_id" class="form-control acc-select"><?= $acc_opts($o('coa_laba_id', $def_laba)); ?></select></div>
                    <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Rugi Pelepasan</label><select name="coa_rugi_id" class="form-control acc-select"><?= $acc_opts($o('coa_rugi_id', $def_rugi)); ?></select></div>
                    <div class="form-group col-md-4"><label class="small font-weight-bold">Catatan</label><input type="text" name="disposed_notes" class="form-control" value="<?= htmlspecialchars($o('disposed_notes')); ?>"></div>
                </div>
                <?php if ($tot_surplus > 0.004): ?>
                <div class="form-row border rounded bg-light pt-2 mb-2 mx-0">
                    <div class="form-group col-md-4"><div class="custom-control custom-checkbox mt-md-4"><input type="checkbox" class="custom-control-input" id="transfer_surplus" name="transfer_surplus" value="1" checked>
                        <label class="custom-control-label small" for="transfer_surplus">Pindahkan surplus revaluasi unit-unit ini (<?= $fmt($tot_surplus); ?>) ke Saldo Laba</label></div></div>
                    <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Surplus Revaluasi</label><select name="coa_surplus_id" class="form-control acc-select"><?= $acc_opts($def_surplus); ?></select></div>
                    <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Saldo Laba</label><select name="coa_saldo_laba_id" class="form-control acc-select"><?= $acc_opts($def_saldo_laba); ?></select></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Unit yang Dilepas</h6></div>
            <div class="card-body">
                <div class="table-responsive"><table class="table table-bordered table-sm small" id="units-table">
                    <thead class="thead-light"><tr><th>No. Aset</th><th>Nama</th><th>Tgl Perolehan</th><th class="text-right">Nilai Perolehan</th><th class="text-right">Akumulasi</th><th class="text-right">Nilai Buku</th><th class="text-right jual-only" style="width:170px">Harga Jual</th><th class="text-right">Laba (Rugi)</th></tr></thead>
                    <tbody>
                    <?php foreach ($units as $u): $buku = round((float) $u->nilai_perolehan - (float) $u->akumulasi_penyusutan, 2); ?>
                        <tr data-buku="<?= $buku; ?>">
                            <td><input type="hidden" name="ids[]" value="<?= (int) $u->id; ?>"><a href="<?= base_url('asset_register/view/' . (int) $u->id); ?>" target="_blank"><?= htmlspecialchars($u->no_asset); ?></a></td>
                            <td><?= htmlspecialchars($u->nama ?? '-'); ?></td>
                            <td><?= date('d-m-Y', strtotime($u->tanggal_perolehan)); ?></td>
                            <td class="text-right"><?= $fmt($u->nilai_perolehan); ?></td>
                            <td class="text-right"><?= $fmt($u->akumulasi_penyusutan); ?></td>
                            <td class="text-right"><?= $fmt($buku); ?></td>
                            <td class="jual-only"><input type="number" name="harga[<?= (int) $u->id; ?>]" class="form-control form-control-sm text-right harga" min="0" step="0.01" value="<?= htmlspecialchars((string) ($old_harga[$u->id] ?? 0)); ?>"></td>
                            <td class="text-right hasil"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr class="font-weight-bold"><td colspan="5">Total</td><td class="text-right"><?= $fmt($tot_buku); ?></td><td class="text-right jual-only" id="sum-harga"></td><td class="text-right" id="sum-hasil"></td></tr></tfoot>
                </table></div>
                <div class="d-flex justify-content-between align-items-center">
                    <div class="small" id="bulk-preview"></div>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-check"></i> Simpan Pelepasan <?= count($units); ?> Unit</button>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
$(function () {
    if ($.fn.select2) $('.acc-select').select2({ width: '100%' });
    var f = function (n) { return Math.abs(n).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var sign = function (n) { return n >= 0 ? '<span class="text-success">' + f(n) + '</span>' : '<span class="text-danger">(' + f(n) + ')</span>'; };
    function calc() {
        var jual = $('#disposal_type').val() === 'JUAL', sh = 0, sb = 0;
        $('.jual-only').toggle(jual);
        $('#units-table tbody tr').each(function () {
            var b = parseFloat($(this).data('buku')) || 0, h = jual ? (parseFloat($(this).find('.harga').val()) || 0) : 0;
            sh += h; sb += b;
            $(this).find('.hasil').html(sign(h - b));
        });
        $('#sum-harga').text(f(sh));
        $('#sum-hasil').html(sign(sh - sb));
        $('#bulk-preview').html('Total nilai buku Rp ' + f(sb) + (jual ? ', total harga Rp ' + f(sh) : '') + ' &rarr; ' + (sh - sb >= 0 ? '<span class="text-success">laba Rp ' + f(sh - sb) + '</span>' : '<span class="text-danger">rugi Rp ' + f(sh - sb) + '</span>'));
    }
    $('#btn-alloc').on('click', function () {
        var total = Math.round((parseFloat($('#total_harga').val()) || 0) * 100), rows = $('#units-table tbody tr'), sb = 0;
        rows.each(function () { sb += Math.max(0, parseFloat($(this).data('buku')) || 0); });
        var left = total;
        rows.each(function (i) {
            var b = Math.max(0, parseFloat($(this).data('buku')) || 0);
            var part = i === rows.length - 1 ? left : Math.round(sb > 0 ? total * b / sb : total / rows.length);
            left -= part;
            $(this).find('.harga').val((part / 100).toFixed(2));
        });
        calc();
    });
    $(document).on('input change', '.harga, #disposal_type', calc);
    calc();
});
</script>
