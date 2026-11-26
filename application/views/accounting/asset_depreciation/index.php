<?php
$fmt = function ($v) { return number_format((float) $v, 2); };
$bln = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$can_add = check_access('asset_depreciation', 'add');
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-calculator text-primary mr-2"></i><?= htmlspecialchars($title); ?></h1>
        <div>
            <a href="<?= base_url('asset_register'); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-building"></i> Aset Tetap</a>
            <a href="<?= base_url('prepaid_expense'); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-hourglass-half"></i> Biaya Dibayar Dimuka</a>
            <a href="<?= base_url('asset_report'); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-file-alt"></i> Laporan Aset</a>
        </div>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Proses Periode</h6></div>
        <div class="card-body">
            <p class="small text-muted mb-3">Satu batch per bulan berisi penyusutan semua aset tetap aktif <em>dan</em> amortisasi biaya dibayar dimuka (garis lurus, mulai bulan berikutnya setelah perolehan / bayar). Posting membuat satu jurnal: Dr Beban Penyusutan / Cr Akumulasi Penyusutan, dan Dr Beban (mis. Biaya Sewa) / Cr Biaya Dibayar Dimuka. Periode harus berurutan.</p>
            <form method="get" action="<?= base_url('asset_depreciation'); ?>" class="form-inline" id="period-form">
                <input type="hidden" name="preview" value="1">
                <label class="mr-2 small font-weight-bold">Periode</label>
                <select name="m" class="form-control form-control-sm mr-2"><?php foreach ($bln as $k => $n): ?><option value="<?= $k; ?>" <?= (int) $preview_m === $k ? 'selected' : ''; ?>><?= $n; ?></option><?php endforeach; ?></select>
                <input type="number" name="y" class="form-control form-control-sm mr-2" style="width:100px" value="<?= (int) $preview_y; ?>">
                <button type="submit" class="btn btn-info btn-sm mr-2"><i class="fas fa-eye"></i> Pratinjau</button>
                <?php if ($can_add): ?>
                    <button type="submit" class="btn btn-primary btn-sm" formaction="<?= base_url('asset_depreciation/create'); ?>" formmethod="post" <?= $has_draft ? 'disabled title="Masih ada batch Draft"' : ''; ?>
                            onclick="return confirm('Buat batch penyusutan untuk periode ini?');"><i class="fas fa-plus"></i> Buat Batch</button>
                <?php endif; ?>
            </form>
            <small class="text-muted d-block mt-2">Usulan periode berikutnya: <?= $bln[(int) $sugg_m] . ' ' . (int) $sugg_y; ?>.<?= $has_draft ? ' <span class="text-warning">Masih ada batch Draft — posting atau hapus dulu.</span>' : ''; ?></small>

            <?php if ($preview !== null): ?>
                <hr>
                <h6 class="font-weight-bold">Pratinjau <?= $bln[(int) $preview_m] . ' ' . (int) $preview_y; ?> <small class="text-muted">(belum disimpan)</small></h6>
                <?php if ($preview['errors']): ?>
                    <div class="alert alert-danger small mb-2"><strong>Akun belum lengkap:</strong><ul class="mb-0"><?php foreach ($preview['errors'] as $e): ?><li><?= htmlspecialchars($e); ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <?php $ta = array_sum(array_column($preview['assets'], 'penyusutan_bulan_ini')); $tp = array_sum(array_column($preview['prepaid'], 'amortisasi_bulan_ini')); ?>
                <p class="small mb-2"><?= count($preview['assets']); ?> aset, penyusutan Rp <?= $fmt($ta); ?> · <?= count($preview['prepaid']); ?> biaya dibayar dimuka, amortisasi Rp <?= $fmt($tp); ?> · <strong>total Rp <?= $fmt($ta + $tp); ?></strong></p>
                <?php if ($preview['assets']): ?>
                <div class="table-responsive"><table class="table table-sm table-bordered small">
                    <thead class="thead-light"><tr><th>Aset</th><th class="text-right">Akumulasi Sebelum</th><th class="text-right">Penyusutan</th><th class="text-right">Nilai Buku Sesudah</th></tr></thead>
                    <tbody><?php foreach ($preview['assets'] as $r): ?><tr><td><?= htmlspecialchars($r['_label']); ?><?= $r['_catchup'] ? ' <span class="badge badge-warning" title="Termasuk bulan-bulan sebelumnya yang belum disusutkan">termasuk susulan</span>' : ''; ?></td>
                        <td class="text-right"><?= $fmt($r['akumulasi_sebelum']); ?></td><td class="text-right"><?= $fmt($r['penyusutan_bulan_ini']); ?></td><td class="text-right"><?= $fmt($r['nilai_buku_sesudah']); ?></td></tr><?php endforeach; ?></tbody>
                </table></div>
                <?php endif; ?>
                <?php if (!$preview['assets'] && !$preview['prepaid'] && !$preview['errors']): ?><p class="small text-muted">Tidak ada yang perlu disusutkan / diamortisasi pada periode ini.</p><?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Daftar Batch</h6></div>
        <div class="card-body">
            <div class="table-responsive"><table class="table table-bordered table-sm table-hover">
                <thead class="thead-light"><tr><th>Periode</th><th>No. Batch</th><th class="text-right">Penyusutan Aset</th><th class="text-right">Amortisasi Dibayar Dimuka</th><th class="text-right">Total</th><th class="text-center">Status</th><th>Jurnal</th></tr></thead>
                <tbody>
                <?php if (empty($batches)): ?><tr><td colspan="7" class="text-center text-muted">Belum ada batch.</td></tr>
                <?php else: foreach ($batches as $b): ?>
                    <tr>
                        <td><?= $bln[(int) $b->periode_month] . ' ' . (int) $b->periode_year; ?></td>
                        <td><a href="<?= base_url('asset_depreciation/view/' . (int) $b->id); ?>" class="font-weight-bold"><?= htmlspecialchars($b->batch_number); ?></a></td>
                        <td class="text-right"><?= $fmt($b->total_asset_amount); ?> <small class="text-muted">(<?= (int) $b->n_asset; ?>)</small></td>
                        <td class="text-right"><?= $fmt($b->total_prepaid_amount); ?> <small class="text-muted">(<?= (int) $b->n_prepaid; ?>)</small></td>
                        <td class="text-right font-weight-bold"><?= $fmt($b->total_amount); ?></td>
                        <td class="text-center"><span class="badge badge-<?= $b->status === 'Posted' ? 'success' : 'warning'; ?>"><?= $b->status; ?></span></td>
                        <td><?= $b->journal_header_id ? '<a href="' . base_url('jurnalumum/view/' . (int) $b->journal_header_id) . '">' . htmlspecialchars($b->journal_no) . '</a>' : '-'; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>
