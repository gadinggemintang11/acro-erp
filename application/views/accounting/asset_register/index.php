<?php
$fmt = function ($v) { return number_format((float) $v, 2); };
$badge = ['Aktif' => 'success', 'Habis Disusutkan' => 'secondary', 'Disposed' => 'dark'];
$status_label = ['Aktif' => 'Aktif', 'Habis Disusutkan' => 'Habis Disusutkan', 'Disposed' => 'Dilepas'];
$can_bulk = check_access('asset_register', 'edit');
$qs = http_build_query(array_filter($filters, function ($v) { return $v !== ''; }));
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-building text-primary mr-2"></i><?= htmlspecialchars($title); ?></h1>
        <div>
            <?php if (check_access('asset_depreciation', 'view')): ?>
                <a href="<?= base_url('asset_depreciation'); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-calculator"></i> Penyusutan Bulanan</a>
            <?php endif; ?>
            <a href="<?= base_url('asset_report'); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-alt"></i> Laporan</a>
            <a href="<?= base_url('asset_register/export' . ($qs ? '?' . $qs : '')); ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
            <?php if (check_access('asset_register', 'add')): ?>
                <a href="<?= base_url('asset_register/add'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Input Aset Manual</a>
            <?php endif; ?>
        </div>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-md-4 mb-3"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Nilai Perolehan (aset belum dilepas)</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= $fmt($totals['perolehan']); ?></div></div></div></div>
        <div class="col-md-4 mb-3"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Akumulasi Penyusutan</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= $fmt($totals['akumulasi']); ?></div></div></div></div>
        <div class="col-md-4 mb-3"><div class="card border-left-success shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Nilai Buku</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= $fmt($totals['buku']); ?></div></div></div></div>
    </div>

    <div class="card shadow mb-3"><div class="card-body py-3">
        <form method="get" action="<?= base_url('asset_register'); ?>" class="form-row align-items-end">
            <div class="col-md-3 mb-2"><label class="small font-weight-bold mb-1">Cari</label>
                <input type="text" name="q" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['q']); ?>" placeholder="No. aset / nama / lokasi / PIC / PO"></div>
            <div class="col-md-3 mb-2"><label class="small font-weight-bold mb-1">Kategori</label>
                <select name="kategori" class="form-control form-control-sm"><option value="">-- Semua --</option>
                    <?php foreach ($categories as $c): ?><option value="<?= htmlspecialchars($c->code); ?>" <?= $filters['kategori'] === $c->code ? 'selected' : ''; ?>><?= htmlspecialchars($c->description); ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-2 mb-2"><label class="small font-weight-bold mb-1">Status</label>
                <select name="status" class="form-control form-control-sm"><option value="">-- Semua --</option>
                    <?php foreach ($status_label as $k => $l): ?><option value="<?= $k; ?>" <?= $filters['status'] === $k ? 'selected' : ''; ?>><?= $l; ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-2 mb-2"><label class="small font-weight-bold mb-1">Sumber</label>
                <select name="sumber" class="form-control form-control-sm"><option value="">-- Semua --</option>
                    <option value="PO" <?= $filters['sumber'] === 'PO' ? 'selected' : ''; ?>>Pembelian (PO)</option>
                    <option value="MANUAL" <?= $filters['sumber'] === 'MANUAL' ? 'selected' : ''; ?>>Input Manual</option>
                </select></div>
            <div class="col-md-2 mb-2"><button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i> Filter</button></div>
        </form>
    </div></div>

    <div class="card shadow mb-4"><div class="card-body">
        <form method="get" action="<?= base_url('asset_register/dispose_units'); ?>" id="bulk-form">
        <?php if ($can_bulk): ?>
        <div class="d-flex align-items-center mb-2">
            <button type="submit" class="btn btn-outline-danger btn-sm" id="bulk-btn" disabled><i class="fas fa-sign-out-alt"></i> Lepas Unit Terpilih (<span id="bulk-n">0</span>)</button>
            <small class="text-muted ml-2">Centang beberapa unit untuk dijual / dihapus sekaligus dalam satu jurnal.</small>
        </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-bordered table-sm table-hover" width="100%">
                <thead class="thead-light"><tr>
                    <?php if ($can_bulk): ?><th class="text-center" style="width:30px"><input type="checkbox" id="bulk-all" title="Pilih semua unit aktif"></th><?php endif; ?>
                    <th>No. Aset</th><th>Nama Aset</th><th>Kategori</th><th>Sumber</th><th>Tgl Perolehan</th>
                    <th class="text-right">Nilai Perolehan</th><th class="text-right">Akumulasi</th><th class="text-right">Nilai Buku</th>
                    <th class="text-center">Status</th>
                </tr></thead>
                <tbody>
                <?php if (empty($assets)): ?>
                    <tr><td colspan="<?= $can_bulk ? 10 : 9; ?>" class="text-center text-muted py-4">Belum ada aset. Aset dari pembelian masuk otomatis saat "Terima Aset" di PO Asset; aset lain (tanah, bangunan, aset lama) lewat tombol Input Aset Manual.</td></tr>
                <?php else: foreach ($assets as $a): ?>
                    <tr class="<?= $a->status === 'Disposed' ? 'text-muted' : ''; ?>">
                        <?php if ($can_bulk): ?><td class="text-center"><?php if ($a->status !== 'Disposed'): ?><input type="checkbox" name="ids[]" value="<?= (int) $a->id; ?>" class="bulk-cb"><?php endif; ?></td><?php endif; ?>
                        <td><a href="<?= base_url('asset_register/view/' . (int) $a->id); ?>" class="font-weight-bold"><?= htmlspecialchars($a->no_asset); ?></a></td>
                        <td><?= htmlspecialchars($a->nama ?? '-'); ?><?php if (empty($a->disusutkan)): ?> <span class="badge badge-light">tidak disusutkan</span><?php endif; ?>
                            <?php if ($a->lokasi || $a->pic): ?><div class="small text-muted"><?= htmlspecialchars(trim($a->lokasi . ($a->pic ? ' · ' . $a->pic : ''), ' ·')); ?></div><?php endif; ?></td>
                        <td><?= htmlspecialchars($a->kategori_label ?: ($a->kategori_efektif ?: '-')); ?></td>
                        <td><?= $a->sumber === 'PO' ? '<span class="badge badge-info">PO</span> <small>' . htmlspecialchars($a->po_number ?? '') . '</small>' : '<span class="badge badge-secondary">Manual</span>'; ?></td>
                        <td><?= date('d-m-Y', strtotime($a->tanggal_perolehan)); ?></td>
                        <td class="text-right"><?= $fmt($a->nilai_perolehan); ?></td>
                        <td class="text-right"><?= $fmt($a->akumulasi_penyusutan); ?></td>
                        <td class="text-right font-weight-bold"><?= $fmt($a->nilai_buku); ?></td>
                        <td class="text-center"><span class="badge badge-<?= $badge[$a->status] ?? 'light'; ?>"><?= $status_label[$a->status] ?? $a->status; ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        </form>
    </div></div>
</div>
<script>
$(function () {
    function sync() { var n = $('.bulk-cb:checked').length; $('#bulk-n').text(n); $('#bulk-btn').prop('disabled', n === 0); }
    $('#bulk-all').on('change', function () { $('.bulk-cb').prop('checked', this.checked); sync(); });
    $(document).on('change', '.bulk-cb', sync);
    sync();
});
</script>
