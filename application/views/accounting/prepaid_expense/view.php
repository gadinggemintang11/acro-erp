<?php
$fmt = function ($v) { return number_format((float) $v, 2); };
$bln = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$sisa_bln = $monthly > 0 ? (int) ceil(round((float) $p->sisa / $monthly, 4)) : 0;
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($p->doc_no); ?> <small class="text-muted"><?= htmlspecialchars($p->nama); ?></small>
            <span class="badge badge-<?= $p->status === 'Aktif' ? 'success' : 'secondary'; ?> align-middle" style="font-size:.8rem"><?= $p->status; ?></span></h1>
        <div>
            <a href="<?= base_url('prepaid_expense'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
            <?php if (check_access('prepaid_expense', 'edit')): ?><a href="<?= base_url('prepaid_expense/edit/' . (int) $p->id); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a><?php endif; ?>
            <?php if (check_access('prepaid_expense', 'delete') && !$has_posted): ?>
                <form method="post" action="<?= base_url('prepaid_expense/delete/' . (int) $p->id); ?>" class="d-inline" onsubmit="return confirm('Hapus data ini?');"><button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button></form>
            <?php endif; ?>
        </div>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>
    <div class="row">
        <div class="col-lg-6"><div class="card shadow mb-4"><div class="card-body small"><table class="table table-sm table-borderless mb-0">
            <tr><th class="w-50">Akun Dibayar Dimuka</th><td><?= htmlspecialchars($p->coa_prepaid_code . ' - ' . $p->coa_prepaid_name); ?></td></tr>
            <tr><th>Akun Beban</th><td><?= htmlspecialchars($p->coa_expense_code . ' - ' . $p->coa_expense_name); ?></td></tr>
            <tr><th>Tanggal Bayar</th><td><?= date('d-m-Y', strtotime($p->tanggal_bayar)); ?></td></tr>
            <tr><th>Departemen</th><td><?= htmlspecialchars($p->department ?: '-'); ?></td></tr>
            <tr><th>No. Jurnal Pembayaran</th><td><?= htmlspecialchars($p->ref_journal_no ?: '-'); ?></td></tr>
            <?php if ($p->keterangan): ?><tr><th>Catatan</th><td><?= nl2br(htmlspecialchars($p->keterangan)); ?></td></tr><?php endif; ?>
        </table></div></div></div>
        <div class="col-lg-6"><div class="card shadow mb-4"><div class="card-body small"><table class="table table-sm table-borderless mb-0">
            <tr><th class="w-50">Nilai</th><td class="text-right"><?= $fmt($p->nilai); ?></td></tr>
            <tr><th>Jumlah Bulan / Per Bulan</th><td class="text-right"><?= (int) $p->jumlah_bulan; ?> bln × <?= $fmt($monthly); ?></td></tr>
            <tr><th>Mulai Amortisasi</th><td class="text-right"><?= $bln[(int) date('n', strtotime($p->mulai_amortisasi))] . ' ' . date('Y', strtotime($p->mulai_amortisasi)); ?></td></tr>
            <tr><th>Sudah Diamortisasi</th><td class="text-right"><?= $fmt($p->akumulasi_amortisasi); ?><?php if ((float) $p->amortisasi_awal > 0): ?><div class="text-muted">termasuk awal <?= $fmt($p->amortisasi_awal); ?></div><?php endif; ?></td></tr>
            <tr class="border-top"><th>Sisa</th><td class="text-right font-weight-bold"><?= $fmt($p->sisa); ?> <small class="text-muted">(± <?= $sisa_bln; ?> bln)</small></td></tr>
        </table></div></div></div>
    </div>
    <div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Riwayat Amortisasi</h6></div>
        <div class="card-body"><div class="table-responsive"><table class="table table-bordered table-sm">
            <thead class="thead-light"><tr><th>Periode</th><th>Batch</th><th class="text-right">Amortisasi</th><th class="text-right">Akumulasi</th><th class="text-right">Sisa</th><th class="text-center">Status</th><th>Jurnal</th></tr></thead>
            <tbody><?php if (empty($history)): ?><tr><td colspan="7" class="text-center text-muted">Belum ada amortisasi. Diproses lewat menu Penyusutan & Amortisasi tiap bulan.</td></tr>
            <?php else: foreach ($history as $h): ?>
                <tr><td><?= $bln[(int) $h->periode_month] . ' ' . (int) $h->periode_year; ?></td>
                    <td><a href="<?= base_url('asset_depreciation/view/' . (int) $h->batch_id); ?>"><?= htmlspecialchars($h->batch_number); ?></a></td>
                    <td class="text-right"><?= $fmt($h->amortisasi_bulan_ini); ?></td><td class="text-right"><?= $fmt($h->akumulasi_sesudah); ?></td><td class="text-right"><?= $fmt($h->sisa_sesudah); ?></td>
                    <td class="text-center"><span class="badge badge-<?= $h->batch_status === 'Posted' ? 'success' : 'warning'; ?>"><?= $h->batch_status; ?></span></td>
                    <td><?= htmlspecialchars($h->journal_no ?? '-'); ?></td></tr>
            <?php endforeach; endif; ?></tbody>
        </table></div></div></div>
</div>
