<?php
$is_edit = !empty($asset);
$old = $is_edit ? [] : (array) ($this->session->flashdata('old_input') ?: []);
$v = function ($k, $default = '') use ($is_edit, $asset, $old) {
    if (!$is_edit) return array_key_exists($k, $old) ? $old[$k] : $default;
    $map = ['coa_asset_id' => 'coa_asset_efektif', 'coa_akum_id' => 'coa_akum_efektif', 'coa_beban_id' => 'coa_beban_efektif', 'kategori' => 'kategori_efektif'];
    $prop = $map[$k] ?? $k;
    return isset($asset->$prop) ? $asset->$prop : $default;
};
$is_po = $is_edit && $asset->sumber === 'PO';
$lock = !empty($locked_financial);           // sudah ada penyusutan Posted / revaluasi / pelepasan sebagian
$auto_j = $is_edit && !empty($asset->journal_header_id);
$dis = $lock ? 'disabled' : '';
$acc_opts = function ($sel) use ($accounts) {
    $h = '<option value="">-- Pilih Akun --</option>';
    foreach ($accounts as $a) $h .= '<option value="' . (int) $a->id . '"' . ((int) $sel === (int) $a->id ? ' selected' : '') . '>' . htmlspecialchars($a->account_code . ' - ' . $a->account_name) . '</option>';
    return $h;
};
$mulai_val = $v('mulai_penyusutan', '');
$mulai_val = $mulai_val ? substr($mulai_val, 0, 7) : '';
$disusutkan = $is_edit ? (int) $asset->disusutkan : (int) $v('disusutkan', 1);
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($title); ?></h1>
        <a href="<?= base_url($is_edit ? 'asset_register/view/' . (int) $asset->id : 'asset_register'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <?php if ($lock): ?>
        <div class="alert alert-warning"><i class="fas fa-lock mr-1"></i> Aset ini sudah disusutkan (ada batch Posted) atau sudah direvaluasi / dilepas sebagian. Data nilai, umur & akun dikunci; yang bisa diubah hanya departemen, lokasi, PIC & keterangan. Perubahan nilai lewat Revaluasi di kartu aset.</div>
    <?php elseif ($is_po): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i> Aset dari PO <?= htmlspecialchars($asset->po_number); ?>. Nilai & akun default mengikuti PO / Master Asset; boleh disesuaikan (mis. menambah biaya kirim/pasang ke nilai perolehan) selama belum disusutkan.</div>
    <?php endif; ?>

    <form method="post" action="<?= base_url($is_edit ? 'asset_register/update/' . (int) $asset->id : 'asset_register/store'); ?>" id="asset-form">
        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Identitas Aset</h6></div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="small font-weight-bold">Master Asset <span class="text-muted font-weight-normal">(opsional)</span></label>
                            <select name="asset_id" id="asset_id" class="form-control" <?= ($is_po || $lock) ? 'disabled' : ''; ?>>
                                <option value="">— Tanpa master (isi nama & akun sendiri, mis. tanah/bangunan) —</option>
                                <?php foreach ($masters as $m): ?>
                                    <option value="<?= (int) $m->id; ?>" <?= (int) $v('asset_id') === (int) $m->id ? 'selected' : ''; ?>><?= htmlspecialchars($m->kode_asset . ' - ' . $m->nama_asset); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($is_po): ?><input type="hidden" name="asset_id" value="<?= (int) $asset->asset_id; ?>"><?php endif; ?>
                            <small class="text-muted">Memilih master mengisi otomatis kategori, umur, residu & akun.</small>
                        </div>
                        <div class="form-group">
                            <label class="small font-weight-bold">Nama Aset <span id="nama-req" class="text-danger">*</span></label>
                            <input type="text" name="nama_aset" id="nama_aset" class="form-control" maxlength="150" value="<?= htmlspecialchars((string) $v('nama_aset')); ?>" <?= $dis; ?> placeholder="mis. Tanah Kawasan JIPS Blok V5C">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-<?= $is_edit ? 12 : 8; ?>">
                                <label class="small font-weight-bold">Kategori</label>
                                <select name="kategori" id="kategori" class="form-control" <?= $dis; ?>>
                                    <?php foreach ($categories as $c): ?><option value="<?= htmlspecialchars($c->code); ?>" <?= $v('kategori', 'LAINNYA') === $c->code ? 'selected' : ''; ?>><?= htmlspecialchars($c->description); ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (!$is_edit): ?>
                            <div class="form-group col-md-4">
                                <label class="small font-weight-bold">Jumlah Unit</label>
                                <input type="number" name="qty" class="form-control" min="1" max="100" value="<?= (int) $v('qty', 1); ?>">
                                <small class="text-muted">Tiap unit dapat no. aset sendiri.</small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4"><label class="small font-weight-bold">Departemen</label>
                                <select name="department" class="form-control"><option value="">-</option>
                                    <?php foreach ($departments as $d): ?><option value="<?= htmlspecialchars($d->code); ?>" <?= (string) $v('department') === $d->code ? 'selected' : ''; ?>><?= htmlspecialchars($d->description); ?></option><?php endforeach; ?>
                                </select></div>
                            <div class="form-group col-md-4"><label class="small font-weight-bold">Lokasi</label><input type="text" name="lokasi" class="form-control" maxlength="100" value="<?= htmlspecialchars((string) $v('lokasi')); ?>"></div>
                            <div class="form-group col-md-4"><label class="small font-weight-bold">PIC</label><input type="text" name="pic" class="form-control" maxlength="100" value="<?= htmlspecialchars((string) $v('pic')); ?>"></div>
                        </div>
                        <div class="form-group mb-0"><label class="small font-weight-bold">Keterangan</label><textarea name="keterangan" class="form-control" rows="2"><?= htmlspecialchars((string) $v('keterangan')); ?></textarea></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Nilai & Penyusutan <small class="text-muted">(garis lurus)</small></h6></div>
                    <div class="card-body">
                        <div class="form-row">
                            <div class="form-group col-md-6"><label class="small font-weight-bold">Tanggal Perolehan *</label>
                                <input type="date" name="tanggal_perolehan" id="tanggal_perolehan" class="form-control" value="<?= htmlspecialchars((string) $v('tanggal_perolehan', date('Y-m-d'))); ?>" <?= $dis; ?> required></div>
                            <div class="form-group col-md-6"><label class="small font-weight-bold">Nilai Perolehan <?= $is_edit ? '' : '(per unit)'; ?> *</label>
                                <input type="number" name="nilai_perolehan" id="nilai_perolehan" class="form-control text-right" min="0.01" step="0.01" value="<?= htmlspecialchars((string) $v('nilai_perolehan')); ?>" <?= $dis; ?> required></div>
                        </div>
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="disusutkan" name="disusutkan" value="1" <?= $disusutkan ? 'checked' : ''; ?> <?= $dis; ?>>
                            <label class="custom-control-label" for="disusutkan">Disusutkan <small class="text-muted">(hapus centang untuk tanah)</small></label>
                        </div>
                        <div id="dep-fields">
                            <div class="form-row">
                                <div class="form-group col-md-4"><label class="small font-weight-bold">Umur Manfaat (bulan) *</label>
                                    <input type="number" name="umur_manfaat_bulan" id="umur" class="form-control" min="1" value="<?= htmlspecialchars((string) $v('umur_manfaat_bulan', 48)); ?>" <?= $dis; ?>>
                                    <small class="text-muted">Pajak: kel.1 48 · kel.2 96 · kel.3 192 · bangunan 240</small></div>
                                <div class="form-group col-md-4"><label class="small font-weight-bold">Nilai Residu</label>
                                    <input type="number" name="nilai_residu" id="residu" class="form-control text-right" min="0" step="0.01" value="<?= htmlspecialchars((string) $v('nilai_residu', 0)); ?>" <?= $dis; ?>></div>
                                <div class="form-group col-md-4"><label class="small font-weight-bold">Mulai Penyusutan</label>
                                    <input type="month" name="mulai_penyusutan" id="mulai" class="form-control" value="<?= htmlspecialchars($mulai_val); ?>" <?= $dis; ?>>
                                    <small class="text-muted">Default bulan berikutnya</small></div>
                            </div>
                            <div class="form-group">
                                <label class="small font-weight-bold">Akumulasi Penyusutan Awal <span class="text-muted font-weight-normal">(aset lama / saldo awal)</span></label>
                                <input type="number" name="akumulasi_awal" id="akum_awal" class="form-control text-right" min="0" step="0.01" value="<?= htmlspecialchars((string) $v('akumulasi_awal', 0)); ?>" <?= $dis; ?>>
                                <small class="text-muted">Akumulasi s/d sebelum aset masuk sistem. Bulan yang terlewat sesudahnya dikejar otomatis di batch penyusutan pertama.</small>
                            </div>
                            <div class="small text-info" id="per-bulan"></div>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Akun</h6></div>
                    <div class="card-body">
                        <div class="form-group"><label class="small font-weight-bold">Akun Aset *</label>
                            <select name="coa_asset_id" id="coa_asset_id" class="form-control acc-select" <?= ($lock || $auto_j) ? 'disabled' : ''; ?>><?= $acc_opts($v('coa_asset_id')); ?></select>
                            <?php if ($auto_j && !$lock): ?><input type="hidden" name="coa_asset_id" value="<?= (int) $v('coa_asset_id'); ?>"><?php endif; ?></div>
                        <div class="form-group dep-acc"><label class="small font-weight-bold">Akun Akumulasi Penyusutan *</label>
                            <select name="coa_akum_id" id="coa_akum_id" class="form-control acc-select" <?= $dis; ?>><?= $acc_opts($v('coa_akum_id')); ?></select></div>
                        <div class="form-group dep-acc mb-0"><label class="small font-weight-bold">Akun Beban Penyusutan *</label>
                            <select name="coa_beban_id" id="coa_beban_id" class="form-control acc-select" <?= $dis; ?>><?= $acc_opts($v('coa_beban_id')); ?></select></div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Jurnal Perolehan</h6></div>
                    <div class="card-body">
                        <?php if ($is_po): ?>
                            <p class="mb-0 small">Dijurnal lewat <strong>Faktur Supplier</strong> PO <?= htmlspecialchars($asset->po_number); ?> (Dr Aset / Cr Hutang Dagang).</p>
                        <?php elseif ($auto_j): ?>
                            <p class="mb-0 small">Jurnal otomatis <a href="<?= base_url('jurnalumum/view/' . (int) $asset->journal_header_id); ?>"><?= htmlspecialchars($asset->acq_journal_no); ?></a>. Nilai, tanggal & akun aset tidak bisa diubah — hapus aset & input ulang bila salah.</p>
                        <?php elseif ($is_edit): ?>
                            <label class="small font-weight-bold">No. Jurnal Referensi</label>
                            <input type="text" name="ref_journal_no" class="form-control" maxlength="50" value="<?= htmlspecialchars((string) $v('ref_journal_no')); ?>">
                        <?php else: ?>
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="jm_ref" name="jurnal_mode" value="REF" class="custom-control-input" <?= $v('jurnal_mode', 'REF') !== 'AUTO' ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="jm_ref">Sudah dijurnal manual / saldo awal — cukup catat no. jurnalnya</label>
                            </div>
                            <div id="ref-box" class="mb-3 ml-4"><input type="text" name="ref_journal_no" class="form-control form-control-sm" maxlength="50" placeholder="No. jurnal referensi (opsional), mis. JU/ACRO/26110005" value="<?= htmlspecialchars((string) $v('ref_journal_no')); ?>"></div>
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="jm_auto" name="jurnal_mode" value="AUTO" class="custom-control-input" <?= $v('jurnal_mode') === 'AUTO' ? 'checked' : ''; ?>>
                                <label class="custom-control-label" for="jm_auto">Buat jurnal otomatis (Dr Aset / Cr akun lawan)</label>
                            </div>
                            <div id="auto-box" class="ml-4">
                                <select name="coa_lawan_id" id="coa_lawan_id" class="form-control acc-select"><?= $acc_opts($v('coa_lawan_id')); ?></select>
                                <small class="text-muted">Akun lawan: Kas/Bank (beli tunai), Hutang Lain-lain, atau Modal/Laba Ditahan (saldo awal). Bila ada akumulasi awal: Cr Akumulasi sebesar itu, sisanya Cr akun lawan.</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-right mb-4">
            <a href="<?= base_url($is_edit ? 'asset_register/view/' . (int) $asset->id : 'asset_register'); ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>
</div>

<script>
$(function () {
    var MASTERS = <?= json_encode(array_map(function ($m) { return ['id' => (int) $m->id, 'nama' => $m->nama_asset, 'kategori' => $m->kategori, 'umur' => (int) $m->umur_manfaat_bulan, 'residu_pct' => (float) $m->nilai_residu_persen, 'coa_asset' => (int) $m->coa_asset_id, 'coa_akum' => (int) $m->coa_akum_id, 'coa_beban' => (int) $m->coa_beban_id]; }, $masters)); ?>;
    var LOCKED = <?= $lock ? 'true' : 'false'; ?>, IS_EDIT = <?= $is_edit ? 'true' : 'false'; ?>;
    if ($.fn.select2) $('.acc-select, #asset_id').select2({ width: '100%' });
    var mulaiTouched = IS_EDIT;

    function nextMonth(dateStr) {
        if (!dateStr) return '';
        var p = dateStr.split('-'), y = parseInt(p[0], 10), m = parseInt(p[1], 10) + 1;
        if (m > 12) { m = 1; y++; }
        return y + '-' + (m < 10 ? '0' : '') + m;
    }
    function setSel(id, val) { if (val) $('#' + id).val(String(val)).trigger('change'); }
    function toggleDep() {
        var on = $('#disusutkan').is(':checked');
        $('#dep-fields, .dep-acc').toggle(on);
        perBulan();
    }
    function perBulan() {
        var n = parseFloat($('#nilai_perolehan').val()) || 0, r = parseFloat($('#residu').val()) || 0, u = parseInt($('#umur').val(), 10) || 0;
        $('#per-bulan').text($('#disusutkan').is(':checked') && n > 0 && u > 0
            ? 'Penyusutan per bulan: Rp ' + ((n - r) / u).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' selama ' + u + ' bulan.' : '');
    }
    $('#asset_id').on('change', function () {
        var m = MASTERS.find(function (x) { return String(x.id) === String($('#asset_id').val()); });
        $('#nama-req').toggle(!m);
        if (!m || LOCKED) return;
        $('#nama_aset').attr('placeholder', m.nama + ' (kosongkan untuk pakai nama master)');
        if (m.kategori) $('#kategori').val(m.kategori);
        if (m.umur) $('#umur').val(m.umur);
        var n = parseFloat($('#nilai_perolehan').val()) || 0;
        if (n > 0) $('#residu').val((n * m.residu_pct / 100).toFixed(2));
        setSel('coa_asset_id', m.coa_asset); setSel('coa_akum_id', m.coa_akum); setSel('coa_beban_id', m.coa_beban);
        perBulan();
    });
    $('#kategori').on('change', function () { if ($(this).val() === 'TANAH') { $('#disusutkan').prop('checked', false); toggleDep(); } });
    $('#tanggal_perolehan').on('change', function () { if (!mulaiTouched) $('#mulai').val(nextMonth($(this).val())); });
    $('#mulai').on('change', function () { mulaiTouched = true; });
    $('#disusutkan').on('change', toggleDep);
    $('#nilai_perolehan, #residu, #umur').on('input', perBulan);
    $('input[name=jurnal_mode]').on('change', function () {
        var auto = $('#jm_auto').is(':checked');
        $('#auto-box').toggle(auto); $('#ref-box').toggle(!auto);
    }).trigger('change');
    if (!IS_EDIT && !$('#mulai').val()) $('#mulai').val(nextMonth($('#tanggal_perolehan').val()));
    $('#nama-req').toggle(!$('#asset_id').val());
    toggleDep();
});
</script>
