<?php $fmt = function ($v) { return number_format((float) $v, 2); }; ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-hourglass-half text-primary mr-2"></i><?= htmlspecialchars($title); ?></h1>
        <div>
            <?php if (check_access('asset_depreciation', 'view')): ?><a href="<?= base_url('asset_depreciation'); ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-calculator"></i> Proses Bulanan</a><?php endif; ?>
            <?php if (check_access('prepaid_expense', 'add')): ?><a href="<?= base_url('prepaid_expense/add'); ?>" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Tambah</a><?php endif; ?>
        </div>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>
    <div class="alert alert-light border small">
        Contoh sewa dibayar dimuka 12 bulan Rp 120.000.000: <strong>saat bayar</strong> (lewat Kas & Bank / Faktur Supplier) dijurnal <em>Dr Sewa Dibayar Dimuka / Cr Bank</em>, lalu daftarkan di sini.
        <strong>Tiap bulan</strong> batch Penyusutan & Amortisasi menjurnal <em>Dr Biaya Sewa Rp 10.000.000 / Cr Sewa Dibayar Dimuka</em> sampai habis.
    </div>
    <div class="card shadow mb-3"><div class="card-body py-3">
        <form method="get" action="<?= base_url('prepaid_expense'); ?>" class="form-inline">
            <input type="text" name="q" class="form-control form-control-sm mr-2" placeholder="No. / nama" value="<?= htmlspecialchars($filters['q']); ?>">
            <select name="status" class="form-control form-control-sm mr-2"><option value="">-- Semua status --</option>
                <option value="Aktif" <?= $filters['status'] === 'Aktif' ? 'selected' : ''; ?>>Aktif</option><option value="Selesai" <?= $filters['status'] === 'Selesai' ? 'selected' : ''; ?>>Selesai</option></select>
            <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
        </form>
    </div></div>
    <div class="card shadow mb-4"><div class="card-body"><div class="table-responsive">
        <table class="table table-bordered table-sm table-hover">
            <thead class="thead-light"><tr><th>No.</th><th>Nama</th><th>Akun</th><th>Mulai</th><th class="text-center">Bulan</th><th class="text-right">Nilai</th><th class="text-right">Per Bulan</th><th class="text-right">Teramortisasi</th><th class="text-right">Sisa</th><th class="text-center">Status</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?><tr><td colspan="10" class="text-center text-muted py-4">Belum ada data.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><a href="<?= base_url('prepaid_expense/view/' . (int) $r->id); ?>" class="font-weight-bold"><?= htmlspecialchars($r->doc_no); ?></a></td>
                    <td><?= htmlspecialchars($r->nama); ?></td>
                    <td class="small"><?= htmlspecialchars($r->coa_prepaid_code . ' → ' . $r->coa_expense_code); ?></td>
                    <td><?= date('m/Y', strtotime($r->mulai_amortisasi)); ?></td>
                    <td class="text-center"><?= (int) $r->jumlah_bulan; ?></td>
                    <td class="text-right"><?= $fmt($r->nilai); ?></td>
                    <td class="text-right"><?= $fmt((int) $r->jumlah_bulan ? $r->nilai / $r->jumlah_bulan : 0); ?></td>
                    <td class="text-right"><?= $fmt($r->akumulasi_amortisasi); ?></td>
                    <td class="text-right font-weight-bold"><?= $fmt($r->sisa); ?></td>
                    <td class="text-center"><span class="badge badge-<?= $r->status === 'Aktif' ? 'success' : 'secondary'; ?>"><?= $r->status; ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div></div></div>
</div>
