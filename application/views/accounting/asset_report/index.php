<?php
$fmt = function ($v) { return $v === null || $v === '' ? '' : (abs((float) $v) < 0.005 ? '-' : number_format((float) $v, 2)); };
$bln = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$y1 = intdiv($f['p1'] - 1, 12); $m1 = $f['p1'] - $y1 * 12;
$y2 = intdiv($f['p2'] - 1, 12); $m2 = $f['p2'] - $y2 * 12;
$A = $assets; $P = $prepaid;
$rf = function ($v) use ($fmt) {
    $v = (array) $v; $h = '';
    foreach (['c0', 'c_add', 'c_reval', 'c_out', 'c1', 'a0', 'a_add', 'a_dep', 'a_reval', 'a_out', 'a1', 'b0', 'b1'] as $k) {
        $h .= '<td class="text-right' . (in_array($k, ['c1', 'a1', 'b1'], true) ? ' font-weight-bold' : '') . '">' . $fmt($v[$k]) . '</td>';
    }
    return $h;
};
$rf_head = '<tr><th colspan="5" class="text-center">Harga Perolehan</th><th colspan="6" class="text-center">Akumulasi Penyusutan</th><th colspan="2" class="text-center">Nilai Buku</th></tr>'
         . '<tr><th>Saldo Awal</th><th>Penambahan</th><th>Revaluasi</th><th>Pengurangan</th><th>Saldo Akhir</th>'
         . '<th>Saldo Awal</th><th title="Akumulasi awal aset lama yang masuk dalam periode">Penambahan</th><th>Penyusutan</th><th title="Eliminasi akumulasi saat revaluasi">Revaluasi</th><th>Pengurangan</th><th>Saldo Akhir</th>'
         . '<th>Awal</th><th>Akhir</th></tr>';
$periode = Asset_report_model::pk_label($f['p1']) . ' s/d ' . Asset_report_model::pk_label($f['p2']);
?>
<style>
    .rep-table { font-size: .78rem; }
    .rep-table th { white-space: nowrap; vertical-align: middle !important; text-align: center; }
    .rep-table td { white-space: nowrap; }
    .rep-table tr.sec td { background: #eef4fb; font-weight: 700; color: #1f4e78; }
    .rep-table tr.sub td { background: #f6f6f6; font-weight: 700; }
    .rep-table tr.tot td { background: #dbe8f6; font-weight: 700; border-top: 2px double #6c8ebf; }
</style>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-alt text-primary mr-2"></i><?= htmlspecialchars($title); ?> <small class="text-muted"><?= htmlspecialchars($periode); ?></small></h1>
        <div>
            <a href="<?= base_url('asset_register'); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-building"></i> Aset Tetap</a>
            <a href="<?= base_url('asset_report/export?' . $export_qs); ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Ekspor Excel</a>
        </div>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-3"><div class="card-body py-3">
        <form method="get" action="<?= base_url('asset_report'); ?>" class="form-row align-items-end">
            <div class="col-md-3 mb-2"><label class="small font-weight-bold mb-1">Dari</label>
                <div class="d-flex"><select name="m1" class="form-control form-control-sm mr-1"><?php foreach ($bln as $k => $n): ?><option value="<?= $k; ?>" <?= $m1 === $k ? 'selected' : ''; ?>><?= $n; ?></option><?php endforeach; ?></select>
                <input type="number" name="y1" class="form-control form-control-sm" style="max-width:90px" value="<?= $y1; ?>"></div></div>
            <div class="col-md-3 mb-2"><label class="small font-weight-bold mb-1">S/d</label>
                <div class="d-flex"><select name="m2" class="form-control form-control-sm mr-1"><?php foreach ($bln as $k => $n): ?><option value="<?= $k; ?>" <?= $m2 === $k ? 'selected' : ''; ?>><?= $n; ?></option><?php endforeach; ?></select>
                <input type="number" name="y2" class="form-control form-control-sm" style="max-width:90px" value="<?= $y2; ?>"></div></div>
            <div class="col-md-2 mb-2"><label class="small font-weight-bold mb-1">Kategori</label>
                <select name="kategori" class="form-control form-control-sm"><option value="">-- Semua --</option>
                    <?php foreach ($categories as $c): ?><option value="<?= htmlspecialchars($c->code); ?>" <?= $f['kategori'] === $c->code ? 'selected' : ''; ?>><?= htmlspecialchars($c->description); ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-2 mb-2"><label class="small font-weight-bold mb-1">Departemen</label>
                <select name="department" class="form-control form-control-sm"><option value="">-- Semua --</option>
                    <?php foreach ($departments as $d): ?><option value="<?= htmlspecialchars($d->code); ?>" <?= $f['department'] === $d->code ? 'selected' : ''; ?>><?= htmlspecialchars($d->description); ?></option><?php endforeach; ?>
                </select></div>
            <div class="col-md-2 mb-2"><button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i> Tampilkan</button></div>
        </form>
        <small class="text-muted">Penyusutan & amortisasi hanya dari batch yang sudah <strong>Posted</strong>. Maksimal 60 bulan.</small>
    </div></div>

    <?php if ($A['mismatch']): ?>
        <div class="alert alert-warning small"><i class="fas fa-exclamation-triangle mr-1"></i> Angka mutasi tidak cocok untuk: <?= htmlspecialchars(implode(', ', array_slice($A['mismatch'], 0, 20))); ?>. Periksa data aset tersebut.</div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3 mb-3"><div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Harga Perolehan Akhir</div>
            <div class="h6 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($A['total']['c1'], 2); ?></div></div></div></div>
        <div class="col-md-3 mb-3"><div class="card border-left-warning shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Penyusutan Periode</div>
            <div class="h6 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($A['total']['a_dep'], 2); ?></div></div></div></div>
        <div class="col-md-3 mb-3"><div class="card border-left-success shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Nilai Buku Akhir</div>
            <div class="h6 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($A['total']['b1'], 2); ?></div></div></div></div>
        <div class="col-md-3 mb-3"><div class="card border-left-info shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Biaya Dibayar Dimuka Akhir</div>
            <div class="h6 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($P['total']['s1'], 2); ?></div></div></div></div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-2">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#t-sum" role="tab">Ringkasan per Kategori</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#t-det" role="tab">Rincian per Aset</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#t-mon" role="tab">Penyusutan per Bulan</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#t-pre" role="tab">Biaya Dibayar Dimuka</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#t-evt" role="tab">Pelepasan & Revaluasi <span class="badge badge-secondary"><?= count($A['events']); ?></span></a></li>
            </ul>
        </div>
        <div class="card-body tab-content">
            <!-- Ringkasan -->
            <div class="tab-pane fade show active" id="t-sum" role="tabpanel">
                <div class="table-responsive"><table class="table table-bordered table-sm rep-table">
                    <thead class="thead-light"><tr><th rowspan="2">Kategori</th><th rowspan="2">Jml</th><?= substr($rf_head, 4); ?></thead>
                    <tbody>
                    <?php if (!$A['categories']): ?><tr><td colspan="15" class="text-center text-muted">Tidak ada aset pada periode ini.</td></tr><?php endif; ?>
                    <?php foreach ($A['categories'] as $name => $c): ?><tr><td><?= htmlspecialchars($name); ?></td><td class="text-center"><?= (int) $c['n']; ?></td><?= $rf($c); ?></tr><?php endforeach; ?>
                    <tr class="tot"><td>TOTAL</td><td class="text-center"><?= (int) $A['total']['n']; ?></td><?= $rf($A['total']); ?></tr>
                    </tbody>
                </table></div>
                <p class="small text-muted mb-0">Saldo akhir = saldo awal + penambahan + revaluasi − pengurangan (akumulasi: + penyusutan). Pengurangan = pelepasan seluruh & sebagian.</p>
            </div>

            <!-- Rincian -->
            <div class="tab-pane fade" id="t-det" role="tabpanel">
                <div class="table-responsive"><table class="table table-bordered table-sm rep-table">
                    <thead class="thead-light"><tr><th rowspan="2">No. Aset</th><th rowspan="2">Nama</th><th rowspan="2">Tgl Perolehan</th><th rowspan="2">Status</th><?= substr($rf_head, 4); ?></thead>
                    <tbody>
                    <?php $cur = null; $sub = null;
                    $keys = ['c0', 'c_add', 'c_reval', 'c_out', 'c1', 'a0', 'a_add', 'a_dep', 'a_reval', 'a_out', 'a1', 'b0', 'b1'];
                    foreach ($A['rows'] as $r):
                        if ($r->kategori_label !== $cur):
                            if ($cur !== null): ?><tr class="sub"><td colspan="4">Subtotal <?= htmlspecialchars($cur); ?></td><?= $rf($sub); ?></tr><?php endif;
                            $cur = $r->kategori_label; $sub = array_fill_keys($keys, 0.0); ?>
                            <tr class="sec"><td colspan="17"><?= htmlspecialchars($cur); ?></td></tr>
                        <?php endif; foreach ($keys as $k) $sub[$k] += $r->$k; ?>
                        <tr><td><a href="<?= base_url('asset_register/view/' . (int) $r->id); ?>"><?= htmlspecialchars($r->no_asset); ?></a></td>
                            <td><?= htmlspecialchars($r->nama ?? '-'); ?></td><td><?= date('d-m-Y', strtotime($r->tanggal_perolehan)); ?></td>
                            <td><?= htmlspecialchars($r->status); ?></td><?= $rf($r); ?></tr>
                    <?php endforeach;
                    if ($cur !== null): ?><tr class="sub"><td colspan="4">Subtotal <?= htmlspecialchars($cur); ?></td><?= $rf($sub); ?></tr><?php endif; ?>
                    <tr class="tot"><td colspan="4">TOTAL</td><?= $rf($A['total']); ?></tr>
                    </tbody>
                </table></div>
            </div>

            <!-- Penyusutan per bulan -->
            <div class="tab-pane fade" id="t-mon" role="tabpanel">
                <div class="table-responsive"><table class="table table-bordered table-sm rep-table">
                    <thead class="thead-light"><tr><th>No. Aset</th><th>Nama</th><?php foreach ($A['months'] as $pk): ?><th><?= Asset_report_model::pk_label($pk); ?></th><?php endforeach; ?><th>Total</th></tr></thead>
                    <tbody>
                    <?php $mt = array_fill_keys($A['months'], 0.0); $gt = 0; $any = false;
                    foreach ($A['rows'] as $r): if (!$r->monthly) continue; $any = true; ?>
                        <tr><td><?= htmlspecialchars($r->no_asset); ?></td><td><?= htmlspecialchars($r->nama ?? '-'); ?></td>
                        <?php foreach ($A['months'] as $pk): $v = $r->monthly[$pk] ?? 0; $mt[$pk] += $v; ?><td class="text-right"><?= $fmt($v); ?></td><?php endforeach; $gt += $r->a_dep; ?>
                        <td class="text-right font-weight-bold"><?= $fmt($r->a_dep); ?></td></tr>
                    <?php endforeach; if (!$any): ?><tr><td colspan="<?= count($A['months']) + 3; ?>" class="text-center text-muted">Belum ada penyusutan Posted pada periode ini.</td></tr><?php endif; ?>
                    <tr class="tot"><td colspan="2">TOTAL</td><?php foreach ($A['months'] as $pk): ?><td class="text-right"><?= $fmt($mt[$pk]); ?></td><?php endforeach; ?><td class="text-right"><?= $fmt($gt); ?></td></tr>
                    </tbody>
                </table></div>
            </div>

            <!-- Biaya dibayar dimuka -->
            <div class="tab-pane fade" id="t-pre" role="tabpanel">
                <div class="table-responsive"><table class="table table-bordered table-sm rep-table">
                    <thead class="thead-light"><tr><th>No.</th><th>Nama</th><th>Tgl Bayar</th><th>Nilai</th><th>Bulan</th><th>Akun (Dimuka / Beban)</th><th>Saldo Awal</th><th>Penambahan</th><th>Amortisasi</th><th>Saldo Akhir</th></tr></thead>
                    <tbody>
                    <?php if (!$P['rows']): ?><tr><td colspan="10" class="text-center text-muted">Tidak ada biaya dibayar dimuka pada periode ini.</td></tr><?php endif; ?>
                    <?php foreach ($P['rows'] as $r): ?>
                        <tr><td><a href="<?= base_url('prepaid_expense/view/' . (int) $r->id); ?>"><?= htmlspecialchars($r->doc_no); ?></a></td><td><?= htmlspecialchars($r->nama); ?></td>
                            <td><?= date('d-m-Y', strtotime($r->tanggal_bayar)); ?></td><td class="text-right"><?= $fmt($r->nilai); ?></td><td class="text-center"><?= (int) $r->jumlah_bulan; ?></td>
                            <td><?= htmlspecialchars($r->coa); ?></td>
                            <td class="text-right"><?= $fmt($r->s0); ?></td><td class="text-right"><?= $fmt($r->add); ?></td><td class="text-right"><?= $fmt($r->amort); ?></td><td class="text-right font-weight-bold"><?= $fmt($r->s1); ?></td></tr>
                    <?php endforeach; ?>
                    <tr class="tot"><td colspan="6">TOTAL</td><td class="text-right"><?= $fmt($P['total']['s0']); ?></td><td class="text-right"><?= $fmt($P['total']['add']); ?></td><td class="text-right"><?= $fmt($P['total']['amort']); ?></td><td class="text-right"><?= $fmt($P['total']['s1']); ?></td></tr>
                    </tbody>
                </table></div>
                <p class="small text-muted mb-0">Amortisasi per bulan ada di file Excel.</p>
            </div>

            <!-- Pelepasan & revaluasi -->
            <div class="tab-pane fade" id="t-evt" role="tabpanel">
                <div class="table-responsive"><table class="table table-bordered table-sm rep-table">
                    <thead class="thead-light"><tr><th>Tanggal</th><th>No. Aset</th><th>Nama</th><th>Jenis</th><th>Porsi</th><th>Harga Perolehan</th><th>Akumulasi</th><th>Nilai Buku</th><th>Harga Jual / Nilai Wajar</th><th>Laba (Rugi)</th><th>Surplus Revaluasi</th><th>Jurnal</th></tr></thead>
                    <tbody>
                    <?php if (!$A['events']): ?><tr><td colspan="12" class="text-center text-muted">Tidak ada pelepasan / revaluasi pada periode ini.</td></tr><?php endif; ?>
                    <?php foreach ($A['events'] as $e): ?>
                        <tr><td><?= date('d-m-Y', strtotime($e['tanggal'])); ?></td><td><a href="<?= base_url('asset_register/view/' . (int) $e['asset_id']); ?>"><?= htmlspecialchars($e['no_asset']); ?></a></td>
                            <td><?= htmlspecialchars($e['nama'] ?? '-'); ?></td><td><?= htmlspecialchars($e['jenis']); ?></td><td><?= htmlspecialchars($e['porsi'] ?? ''); ?></td>
                            <td class="text-right"><?= $fmt($e['nilai']); ?></td><td class="text-right"><?= $fmt($e['akumulasi']); ?></td><td class="text-right"><?= $fmt($e['buku']); ?></td>
                            <td class="text-right"><?= $fmt($e['harga']); ?></td>
                            <td class="text-right <?= $e['laba_rugi'] < 0 ? 'text-danger' : ''; ?>"><?= $fmt($e['laba_rugi']); ?></td>
                            <td class="text-right"><?= $fmt($e['surplus']); ?></td><td><?= htmlspecialchars($e['journal_no'] ?? ''); ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>
<script>
$(function () {
    try { var t = localStorage.getItem('assetReportTab'); if (t && $('a[href="' + t + '"]').length) $('a[href="' + t + '"]').tab('show'); } catch (e) {}
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) { try { localStorage.setItem('assetReportTab', $(e.target).attr('href')); } catch (x) {} });
});
</script>
