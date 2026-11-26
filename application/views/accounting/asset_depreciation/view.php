<?php
$fmt = function ($v) { return number_format((float) $v, 2); };
$bln = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$draft = $b->status === 'Draft';
// Ringkasan jurnal (sama dengan pengelompokan saat posting)
$jr = [];
$add = function ($code, $side, $v) use (&$jr) { $k = $side . '|' . $code; $jr[$k] = ($jr[$k] ?? 0) + (float) $v; };
foreach ($assets as $d)   { $add($d->coa_beban_code, 'D', $d->penyusutan_bulan_ini); $add($d->coa_akum_code, 'K', $d->penyusutan_bulan_ini); }
foreach ($prepaids as $d) { $add($d->coa_expense_code, 'D', $d->amortisasi_bulan_ini); $add($d->coa_prepaid_code, 'K', $d->amortisasi_bulan_ini); }
ksort($jr);
$ta = array_sum(array_map(function ($d) { return (float) $d->penyusutan_bulan_ini; }, $assets));
$tp = array_sum(array_map(function ($d) { return (float) $d->amortisasi_bulan_ini; }, $prepaids));
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($b->batch_number); ?> <small class="text-muted"><?= $bln[(int) $b->periode_month] . ' ' . (int) $b->periode_year; ?></small>
            <span class="badge badge-<?= $draft ? 'warning' : 'success'; ?> align-middle" style="font-size:.8rem"><?= $b->status; ?></span></h1>
        <div class="d-flex flex-wrap" style="gap:.25rem">
            <a href="<?= base_url('asset_depreciation'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
            <a href="<?= base_url('asset_depreciation/export/' . (int) $b->id); ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
            <?php if ($draft && check_access('asset_depreciation', 'add')): ?>
                <form method="post" action="<?= base_url('asset_depreciation/recalc/' . (int) $b->id); ?>"><button class="btn btn-info btn-sm"><i class="fas fa-sync"></i> Hitung Ulang</button></form>
            <?php endif; ?>
            <?php if ($draft && check_access('asset_depreciation', 'confirm')): ?>
                <form method="post" action="<?= base_url('asset_depreciation/post/' . (int) $b->id); ?>" onsubmit="return confirm('Posting batch ini? Jurnal dibuat dan nilai buku aset diperbarui.');"><button class="btn btn-success btn-sm"><i class="fas fa-check-circle"></i> Posting</button></form>
            <?php endif; ?>
            <?php if (!$draft && $is_latest && check_access('asset_depreciation', 'confirm')): ?>
                <form method="post" action="<?= base_url('asset_depreciation/unpost/' . (int) $b->id); ?>" onsubmit="var r = confirm('Batalkan posting batch ini? Nilai aset dikembalikan, jurnal kembali Draft.'); return r;"><button class="btn btn-outline-danger btn-sm"><i class="fas fa-undo"></i> Batalkan Posting</button></form>
            <?php endif; ?>
            <?php if ($draft && check_access('asset_depreciation', 'delete')): ?>
                <form method="post" action="<?= base_url('asset_depreciation/delete/' . (int) $b->id); ?>" onsubmit="return confirm('Hapus batch Draft ini?');"><button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button></form>
            <?php endif; ?>
        </div>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <?php if ($draft): ?>
        <div class="alert alert-warning small"><i class="fas fa-info-circle mr-1"></i> Batch Draft: angka di bawah adalah hasil hitung terakhir. Saat <strong>Posting</strong> dihitung ulang otomatis dengan data aset terbaru<?= $b->journal_header_id ? ', dan jurnal ' . htmlspecialchars($b->journal_no) . ' (Draft) diperbarui' : ''; ?>.</div>
    <?php elseif (!$is_latest): ?>
        <div class="alert alert-light small border">Hanya batch periode terakhir yang bisa dibatalkan postingnya.</div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-3"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Penyusutan Aset (<?= count($assets); ?>)</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= $fmt($ta); ?></div></div></div></div>
        <div class="col-md-4 mb-3"><div class="card border-left-info shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Amortisasi Dibayar Dimuka (<?= count($prepaids); ?>)</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= $fmt($tp); ?></div></div></div></div>
        <div class="col-md-4 mb-3"><div class="card border-left-success shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Jurnal</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $b->journal_header_id ? '<a href="' . base_url('jurnalumum/view/' . (int) $b->journal_header_id) . '">' . htmlspecialchars($b->journal_no) . '</a> <small class="text-muted">(' . htmlspecialchars($b->journal_status) . ')</small>' : '<span class="text-muted">dibuat saat posting</span>'; ?></div></div></div></div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Ringkasan Jurnal</h6></div>
        <div class="card-body"><table class="table table-sm table-bordered small mb-0" style="max-width:640px">
            <thead class="thead-light"><tr><th>Akun</th><th class="text-right">Debit</th><th class="text-right">Kredit</th></tr></thead>
            <tbody><?php foreach ($jr as $k => $v): list($side, $code) = explode('|', $k, 2); ?>
                <tr><td><?= htmlspecialchars($code ?: '-'); ?></td><td class="text-right"><?= $side === 'D' ? $fmt($v) : ''; ?></td><td class="text-right"><?= $side === 'K' ? $fmt($v) : ''; ?></td></tr>
            <?php endforeach; ?></tbody>
            <tfoot><tr class="font-weight-bold"><td>Total</td><td class="text-right"><?= $fmt($ta + $tp); ?></td><td class="text-right"><?= $fmt($ta + $tp); ?></td></tr></tfoot>
        </table></div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Penyusutan Aset Tetap</h6></div>
        <div class="card-body"><div class="table-responsive"><table class="table table-sm table-bordered small">
            <thead class="thead-light"><tr><th>No. Aset</th><th>Nama</th><th class="text-right">Nilai Perolehan</th><th class="text-right">Akumulasi Sebelum</th><th class="text-right">Penyusutan</th><th class="text-right">Akumulasi Sesudah</th><th class="text-right">Nilai Buku</th><th>Akun Beban / Akumulasi</th></tr></thead>
            <tbody><?php if (empty($assets)): ?><tr><td colspan="8" class="text-center text-muted">-</td></tr><?php else: foreach ($assets as $d): ?>
                <tr><td><a href="<?= base_url('asset_register/view/' . (int) $d->asset_register_id); ?>"><?= htmlspecialchars($d->no_asset); ?></a></td><td><?= htmlspecialchars($d->nama ?? '-'); ?></td>
                    <td class="text-right"><?= $fmt($d->nilai_perolehan); ?></td><td class="text-right"><?= $fmt($d->akumulasi_sebelum); ?></td>
                    <td class="text-right font-weight-bold"><?= $fmt($d->penyusutan_bulan_ini); ?></td><td class="text-right"><?= $fmt($d->akumulasi_sesudah); ?></td>
                    <td class="text-right"><?= $fmt($d->nilai_buku_sesudah); ?></td><td><?= htmlspecialchars($d->coa_beban_code . ' / ' . $d->coa_akum_code); ?></td></tr>
            <?php endforeach; endif; ?></tbody>
        </table></div></div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Amortisasi Biaya Dibayar Dimuka</h6></div>
        <div class="card-body"><div class="table-responsive"><table class="table table-sm table-bordered small">
            <thead class="thead-light"><tr><th>No.</th><th>Nama</th><th class="text-right">Nilai</th><th class="text-right">Akumulasi Sebelum</th><th class="text-right">Amortisasi</th><th class="text-right">Sisa</th><th>Akun Beban / Dibayar Dimuka</th></tr></thead>
            <tbody><?php if (empty($prepaids)): ?><tr><td colspan="7" class="text-center text-muted">-</td></tr><?php else: foreach ($prepaids as $d): ?>
                <tr><td><a href="<?= base_url('prepaid_expense/view/' . (int) $d->prepaid_id); ?>"><?= htmlspecialchars($d->doc_no); ?></a></td><td><?= htmlspecialchars($d->nama); ?></td>
                    <td class="text-right"><?= $fmt($d->nilai); ?></td><td class="text-right"><?= $fmt($d->akumulasi_sebelum); ?></td>
                    <td class="text-right font-weight-bold"><?= $fmt($d->amortisasi_bulan_ini); ?></td><td class="text-right"><?= $fmt($d->sisa_sesudah); ?></td>
                    <td><?= htmlspecialchars($d->coa_expense_code . ' / ' . $d->coa_prepaid_code); ?></td></tr>
            <?php endforeach; endif; ?></tbody>
        </table></div></div>
    </div>
</div>
