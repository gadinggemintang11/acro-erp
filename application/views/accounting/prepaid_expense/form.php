<?php
$is_edit = !empty($row);
$v = function ($k, $d = '') use ($is_edit, $row) { return $is_edit && isset($row->$k) ? $row->$k : $d; };
$dis = !empty($locked) ? 'disabled' : '';
$acc_opts = function ($sel) use ($accounts) {
    $h = '<option value="">-- Pilih Akun --</option>';
    foreach ($accounts as $a) $h .= '<option value="' . (int) $a->id . '"' . ((int) $sel === (int) $a->id ? ' selected' : '') . '>' . htmlspecialchars($a->account_code . ' - ' . $a->account_name) . '</option>';
    return $h;
};
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($title); ?></h1>
        <a href="<?= base_url($is_edit ? 'prepaid_expense/view/' . (int) $row->id : 'prepaid_expense'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>
    <?php if (!empty($locked)): ?><div class="alert alert-warning"><i class="fas fa-lock mr-1"></i> Sudah ada amortisasi yang diposting. Nilai, akun & jadwal dikunci; yang bisa diubah hanya departemen, no. jurnal referensi & keterangan.</div><?php endif; ?>
    <form method="post" action="<?= base_url($is_edit ? 'prepaid_expense/update/' . (int) $row->id : 'prepaid_expense/store'); ?>">
        <div class="card shadow mb-4"><div class="card-body">
            <div class="form-row">
                <div class="form-group col-md-6"><label class="small font-weight-bold">Nama / Keterangan *</label>
                    <input type="text" name="nama" class="form-control" maxlength="150" value="<?= htmlspecialchars((string) $v('nama')); ?>" placeholder="mis. Sewa Gudang Jan - Des 2027" <?= $dis; ?> required></div>
                <div class="form-group col-md-3"><label class="small font-weight-bold">Departemen</label>
                    <select name="department" class="form-control"><option value="">-</option>
                        <?php foreach ($departments as $d): ?><option value="<?= htmlspecialchars($d->code); ?>" <?= (string) $v('department') === $d->code ? 'selected' : ''; ?>><?= htmlspecialchars($d->description); ?></option><?php endforeach; ?></select></div>
                <div class="form-group col-md-3"><label class="small font-weight-bold">No. Jurnal Pembayaran</label>
                    <input type="text" name="ref_journal_no" class="form-control" maxlength="50" value="<?= htmlspecialchars((string) $v('ref_journal_no')); ?>" placeholder="referensi (opsional)"></div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6"><label class="small font-weight-bold">Akun Biaya Dibayar Dimuka *</label>
                    <select name="coa_prepaid_id" class="form-control acc-select" <?= $dis; ?>><?= $acc_opts($v('coa_prepaid_id', $defaults['prepaid'])); ?></select></div>
                <div class="form-group col-md-6"><label class="small font-weight-bold">Akun Beban *</label>
                    <select name="coa_expense_id" class="form-control acc-select" <?= $dis; ?>><?= $acc_opts($v('coa_expense_id', $defaults['expense'])); ?></select></div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-3"><label class="small font-weight-bold">Tanggal Bayar *</label>
                    <input type="date" name="tanggal_bayar" id="tgl" class="form-control" value="<?= htmlspecialchars((string) $v('tanggal_bayar', date('Y-m-d'))); ?>" <?= $dis; ?> required></div>
                <div class="form-group col-md-3"><label class="small font-weight-bold">Nilai *</label>
                    <input type="number" name="nilai" id="nilai" class="form-control text-right" min="0.01" step="0.01" value="<?= htmlspecialchars((string) $v('nilai')); ?>" <?= $dis; ?> required></div>
                <div class="form-group col-md-2"><label class="small font-weight-bold">Jumlah Bulan *</label>
                    <input type="number" name="jumlah_bulan" id="bulan" class="form-control" min="1" max="600" value="<?= htmlspecialchars((string) $v('jumlah_bulan', 12)); ?>" <?= $dis; ?> required></div>
                <div class="form-group col-md-2"><label class="small font-weight-bold">Mulai Amortisasi</label>
                    <input type="month" name="mulai_amortisasi" id="mulai" class="form-control" value="<?= $v('mulai_amortisasi') ? substr($v('mulai_amortisasi'), 0, 7) : ''; ?>" <?= $dis; ?>>
                    <small class="text-muted">Default bulan berikutnya</small></div>
                <div class="form-group col-md-2"><label class="small font-weight-bold">Amortisasi Awal</label>
                    <input type="number" name="amortisasi_awal" class="form-control text-right" min="0" step="0.01" value="<?= htmlspecialchars((string) $v('amortisasi_awal', 0)); ?>" <?= $dis; ?>>
                    <small class="text-muted">Bila sudah berjalan</small></div>
            </div>
            <div class="small text-info mb-2" id="per-bulan"></div>
            <div class="form-group mb-0"><label class="small font-weight-bold">Catatan</label><textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars((string) $v('keterangan')); ?></textarea></div>
        </div></div>
        <div class="text-right mb-4"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button></div>
    </form>
</div>
<script>
$(function () {
    if ($.fn.select2) $('.acc-select').select2({ width: '100%' });
    var touched = <?= $is_edit ? 'true' : 'false'; ?>;
    function next(d) { if (!d) return ''; var p = d.split('-'), y = +p[0], m = +p[1] + 1; if (m > 12) { m = 1; y++; } return y + '-' + (m < 10 ? '0' : '') + m; }
    function pb() { var n = parseFloat($('#nilai').val()) || 0, b = parseInt($('#bulan').val(), 10) || 0;
        $('#per-bulan').text(n > 0 && b > 0 ? 'Amortisasi per bulan: Rp ' + (n / b).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' selama ' + b + ' bulan mulai ' + ($('#mulai').val() || '-') + '.' : ''); }
    $('#tgl').on('change', function () { if (!touched) { $('#mulai').val(next($(this).val())); pb(); } });
    $('#mulai').on('change', function () { touched = true; pb(); });
    $('#nilai, #bulan').on('input', pb);
    if (!$('#mulai').val()) $('#mulai').val(next($('#tgl').val()));
    pb();
});
</script>
