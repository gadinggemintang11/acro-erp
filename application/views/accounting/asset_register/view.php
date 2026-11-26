<?php
$fmt = function ($v) { return number_format((float) $v, 2); };
$badge = ['Aktif' => 'success', 'Habis Disusutkan' => 'secondary', 'Disposed' => 'dark'];
$status_label = ['Aktif' => 'Aktif', 'Habis Disusutkan' => 'Habis Disusutkan', 'Disposed' => 'Dilepas'];
$can_edit = check_access('asset_register', 'edit');
$can_del  = check_access('asset_register', 'delete') && $a->sumber === 'MANUAL' && !$has_posted && $a->status !== 'Disposed';
$acc_opts = function ($sel) use ($accounts) {
    $h = '<option value="">-- Pilih Akun --</option>';
    foreach ($accounts as $x) $h .= '<option value="' . (int) $x->id . '"' . ((int) $sel === (int) $x->id ? ' selected' : '') . '>' . htmlspecialchars($x->account_code . ' - ' . $x->account_name) . '</option>';
    return $h;
};
$bln = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$active = in_array($a->status, ['Aktif', 'Habis Disusutkan'], true);
$surplus = (float) ($a->surplus_revaluasi ?? 0);
$rugi_reval = (float) ($a->rugi_revaluasi ?? 0);
$last_mut_id = $mutations ? (int) end($mutations)->id : 0;
$pk_label = function ($pk) use ($bln) { if (!$pk) return '-'; $y = intdiv($pk - 1, 12); return $bln[$pk - $y * 12] . ' ' . $y; };
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0 text-gray-800"><?= htmlspecialchars($a->no_asset); ?> <small class="text-muted"><?= htmlspecialchars($a->nama ?? ''); ?></small>
            <span class="badge badge-<?= $badge[$a->status] ?? 'light'; ?> align-middle" style="font-size:.8rem"><?= $status_label[$a->status] ?? $a->status; ?></span></h1>
        <div>
            <a href="<?= base_url('asset_register'); ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
            <?php if ($can_edit && $a->status !== 'Disposed'): ?>
                <a href="<?= base_url('asset_register/edit/' . (int) $a->id); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Edit</a>
            <?php endif; ?>
            <?php if ($can_del): ?>
                <form method="post" action="<?= base_url('asset_register/delete/' . (int) $a->id); ?>" class="d-inline" onsubmit="return confirm('Hapus aset ini?<?= $a->journal_header_id ? '\nJurnal perolehan ' . addslashes($a->acq_journal_no) . ' ikut dihapus (beserta unit lain yang memakai jurnal itu).' : ''; ?>');">
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Hapus</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php $this->load->view('templates/partials/alerts'); ?>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Identitas</h6></div>
                <div class="card-body small">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th class="w-50">Kategori</th><td><?= htmlspecialchars($a->kategori_label ?: ($a->kategori_efektif ?: '-')); ?></td></tr>
                        <tr><th>Master Asset</th><td><?= $a->kode_asset ? htmlspecialchars($a->kode_asset . ' - ' . $a->master_nama) : '-'; ?></td></tr>
                        <tr><th>Sumber</th><td><?php if ($a->sumber === 'PO'): ?>PO <a href="<?= base_url('purchase_order_asset/detail/' . (int) $a->po_header_id); ?>"><?= htmlspecialchars($a->po_number); ?></a><?php else: ?>Input manual<?php endif; ?></td></tr>
                        <tr><th>Tanggal Perolehan</th><td><?= date('d-m-Y', strtotime($a->tanggal_perolehan)); ?></td></tr>
                        <tr><th>Departemen</th><td><?= htmlspecialchars($a->department ?: '-'); ?></td></tr>
                        <tr><th>Lokasi / PIC</th><td><?= htmlspecialchars(($a->lokasi ?: '-') . ' / ' . ($a->pic ?: '-')); ?></td></tr>
                        <tr><th>Jurnal Perolehan</th><td>
                            <?php if ($a->journal_header_id): ?><a href="<?= base_url('jurnalumum/view/' . (int) $a->journal_header_id); ?>"><?= htmlspecialchars($a->acq_journal_no); ?></a> <small class="text-muted">(otomatis)</small>
                            <?php elseif ($a->ref_journal_no): ?><?= htmlspecialchars($a->ref_journal_no); ?> <small class="text-muted">(referensi)</small>
                            <?php elseif ($a->sumber === 'PO'): ?><small>lewat Faktur Supplier PO</small>
                            <?php else: ?>-<?php endif; ?></td></tr>
                        <?php if ($a->keterangan): ?><tr><th>Keterangan</th><td><?= nl2br(htmlspecialchars($a->keterangan)); ?></td></tr><?php endif; ?>
                    </table>
                </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Nilai</h6></div>
                <div class="card-body small">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><th class="w-50">Nilai Perolehan</th><td class="text-right"><?= $fmt($a->nilai_perolehan); ?></td></tr>
                        <?php if ($a->disusutkan): ?>
                        <tr><th>Nilai Residu</th><td class="text-right"><?= $fmt($a->nilai_residu); ?></td></tr>
                        <tr><th>Akumulasi Penyusutan</th><td class="text-right"><?= $fmt($a->akumulasi_penyusutan); ?><?php if ((float) $a->akumulasi_awal > 0): ?><div class="text-muted">termasuk awal <?= $fmt($a->akumulasi_awal); ?></div><?php endif; ?></td></tr>
                        <?php endif; ?>
                        <tr class="border-top"><th>Nilai Buku</th><td class="text-right font-weight-bold"><?= $fmt($a->nilai_buku); ?></td></tr>
                        <?php if (abs($surplus) >= 0.005): ?><tr><th>Surplus Revaluasi</th><td class="text-right"><?= $fmt($surplus); ?></td></tr><?php endif; ?>
                        <?php if (abs($rugi_reval) >= 0.005): ?><tr><th>Rugi Revaluasi (L/R)</th><td class="text-right"><?= $fmt($rugi_reval); ?></td></tr><?php endif; ?>
                        <?php if ($a->disusutkan): ?>
                        <tr><th>Umur Manfaat<?= $mutations && array_filter($mutations, function ($m) { return $m->jenis === 'REVALUASI'; }) ? ' <small class="text-muted">(sejak revaluasi)</small>' : ''; ?></th><td class="text-right"><?= (int) $a->umur_manfaat_bulan; ?> bulan</td></tr>
                        <tr><th>Penyusutan / bulan</th><td class="text-right"><?= $fmt($schedule['monthly']); ?></td></tr>
                        <tr><th>Mulai Penyusutan</th><td class="text-right"><?= $a->mulai_penyusutan ? $bln[(int) date('n', strtotime($a->mulai_penyusutan))] . ' ' . date('Y', strtotime($a->mulai_penyusutan)) : '-'; ?></td></tr>
                        <tr><th>Sisa</th><td class="text-right"><?= (int) $schedule['sisa_bulan']; ?> bulan</td></tr>
                        <?php else: ?>
                        <tr><td colspan="2" class="text-muted">Tidak disusutkan (mis. tanah).</td></tr>
                        <?php endif; ?>
                    </table>
                </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow mb-4"><div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Akun</h6></div>
                <div class="card-body small">
                    <div class="mb-2"><strong>Aset</strong><br><?= $a->coa_asset_code ? htmlspecialchars($a->coa_asset_code . ' - ' . $a->coa_asset_name) : '<span class="text-danger">belum diisi</span>'; ?></div>
                    <?php if ($a->disusutkan): ?>
                    <div class="mb-2"><strong>Akumulasi Penyusutan</strong><br><?= $a->coa_akum_code ? htmlspecialchars($a->coa_akum_code . ' - ' . $a->coa_akum_name) : '<span class="text-danger">belum diisi</span>'; ?></div>
                    <div><strong>Beban Penyusutan</strong><br><?= $a->coa_beban_code ? htmlspecialchars($a->coa_beban_code . ' - ' . $a->coa_beban_name) : '<span class="text-danger">belum diisi</span>'; ?></div>
                    <?php endif; ?>
                </div></div>
        </div>
    </div>

    <?php if ($a->status === 'Disposed'): ?>
        <div class="card shadow mb-4 border-left-dark"><div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <strong><?= $a->disposal_type === 'JUAL' ? 'Dijual' : 'Dihapus (write-off)'; ?></strong> pada <?= date('d-m-Y', strtotime($a->disposed_at)); ?>
                <?php if ($a->disposal_type === 'JUAL'): ?> seharga <strong>Rp <?= $fmt($a->disposal_value); ?></strong><?php endif; ?>
                · nilai buku saat dilepas Rp <?= $fmt($a->nilai_buku); ?>
                · jurnal <a href="<?= base_url('jurnalumum/view/' . (int) $a->disposal_journal_id); ?>"><?= htmlspecialchars($a->disposal_journal_no); ?></a>
                <?php if ($a->disposed_notes): ?><div class="small text-muted"><?= nl2br(htmlspecialchars($a->disposed_notes)); ?></div><?php endif; ?>
                <?php if (count($disposal_group) > 1): ?><div class="small mt-1">Dilepas bersama <?= count($disposal_group) - 1; ?> unit lain dalam satu jurnal:
                    <?php foreach ($disposal_group as $g): if ((int) $g->id === (int) $a->id) continue; ?><a href="<?= base_url('asset_register/view/' . (int) $g->id); ?>" class="badge badge-light"><?= htmlspecialchars($g->no_asset); ?></a> <?php endforeach; ?></div><?php endif; ?>
            </div>
            <?php if ($can_edit): ?>
            <form method="post" action="<?= base_url('asset_register/cancel_disposal/' . (int) $a->id); ?>" onsubmit="return confirm('Batalkan pelepasan? Jurnal pelepasan dihapus dan aset aktif lagi.<?= count($disposal_group) > 1 ? '\nSemua ' . count($disposal_group) . ' unit dalam jurnal ini ikut dibatalkan.' : ''; ?>');">
                <button type="submit" class="btn btn-outline-dark btn-sm"><i class="fas fa-undo"></i> Batalkan Pelepasan</button>
            </form>
            <?php endif; ?>
        </div></div>
    <?php elseif ($can_edit): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-sign-out-alt mr-1"></i> Pelepasan Aset (Jual / Hapus)</h6>
                <button class="btn btn-outline-danger btn-sm" type="button" data-toggle="collapse" data-target="#disposeBox">Lepas Aset</button>
            </div>
            <div class="collapse" id="disposeBox"><div class="card-body">
                <p class="small text-muted">Nilai buku memakai penyusutan terakhir yang diposting. Posting dulu penyusutan s/d bulan sebelum pelepasan agar nilainya tepat.</p>
                <?php if (count($siblings) > 1): ?>
                    <div class="alert alert-light border small py-2"><i class="fas fa-layer-group mr-1"></i> Ada <?= count($siblings); ?> unit sejenis yang belum dilepas (<?= $a->po_number ? 'PO ' . htmlspecialchars($a->po_number) : 'input yang sama'; ?>).
                        <a href="<?= base_url('asset_register/dispose_units?' . http_build_query(['ids' => array_map(function ($x) { return (int) $x->id; }, $siblings)])); ?>" class="font-weight-bold">Lepas beberapa unit sekaligus &rarr;</a></div>
                <?php endif; ?>
                <form method="post" action="<?= base_url('asset_register/dispose/' . (int) $a->id); ?>" id="dispose-form" onsubmit="return confirm('Lepas aset ini? Jurnal pelepasan akan dibuat & diposting.');">
                    <div class="form-row">
                        <div class="form-group col-md-12 mb-2">
                            <label class="small font-weight-bold d-block mb-1">Yang dilepas</label>
                            <div class="custom-control custom-radio custom-control-inline"><input type="radio" id="scope_all" name="dispose_scope" value="ALL" class="custom-control-input" checked><label class="custom-control-label" for="scope_all">Seluruh aset</label></div>
                            <?php if ($mut_ready): ?><div class="custom-control custom-radio custom-control-inline"><input type="radio" id="scope_part" name="dispose_scope" value="PART" class="custom-control-input"><label class="custom-control-label" for="scope_part">Sebagian <small class="text-muted">(mis. tanah 300 dari 1.000 m², 40% instalasi)</small></label></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="form-row part-only" style="display:none">
                        <div class="form-group col-md-3"><label class="small font-weight-bold">Porsi dilepas *</label><input type="number" name="porsi_dilepas" id="porsi_dilepas" class="form-control text-right" min="0" step="any" value="0"></div>
                        <div class="form-group col-md-3"><label class="small font-weight-bold">Dari total *</label><input type="number" name="porsi_total" id="porsi_total" class="form-control text-right" min="0" step="any" value="100"></div>
                        <div class="form-group col-md-2"><label class="small font-weight-bold">Satuan</label><input type="text" name="porsi_satuan" class="form-control" value="%" maxlength="20" placeholder="%, m², pcs"></div>
                        <div class="form-group col-md-4 small text-muted pt-md-4" id="porsi-info"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3"><label class="small font-weight-bold">Tanggal *</label><input type="date" name="disposed_at" class="form-control" value="<?= date('Y-m-d'); ?>" required></div>
                        <div class="form-group col-md-3"><label class="small font-weight-bold">Jenis *</label>
                            <select name="disposal_type" id="disposal_type" class="form-control"><option value="JUAL">Dijual</option><option value="HAPUS">Dihapus / rusak (write-off)</option></select></div>
                        <div class="form-group col-md-3 jual-only"><label class="small font-weight-bold">Harga Jual *</label><input type="number" name="disposal_value" id="disposal_value" class="form-control text-right" min="0" step="0.01" value="0"></div>
                        <div class="form-group col-md-3 jual-only"><label class="small font-weight-bold">Akun Penerimaan *</label><select name="coa_kas_id" class="form-control acc-select"><?= $acc_opts(0); ?></select></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Laba Pelepasan</label><select name="coa_laba_id" class="form-control acc-select"><?= $acc_opts($def_laba); ?></select></div>
                        <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Rugi Pelepasan</label><select name="coa_rugi_id" class="form-control acc-select"><?= $acc_opts($def_rugi); ?></select></div>
                        <div class="form-group col-md-4"><label class="small font-weight-bold">Catatan</label><input type="text" name="disposed_notes" class="form-control"></div>
                    </div>
                    <?php if ($surplus > 0.004): ?>
                    <div class="form-row border rounded bg-light pt-2 mb-2 mx-0">
                        <div class="form-group col-md-4"><div class="custom-control custom-checkbox mt-md-4"><input type="checkbox" class="custom-control-input" id="transfer_surplus" name="transfer_surplus" value="1" checked>
                            <label class="custom-control-label small" for="transfer_surplus">Pindahkan surplus revaluasi bagian yang dilepas (<span id="surplus-part"><?= $fmt($surplus); ?></span>) ke Saldo Laba</label></div></div>
                        <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Surplus Revaluasi</label><select name="coa_surplus_id" class="form-control acc-select"><?= $acc_opts($def_surplus); ?></select></div>
                        <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Saldo Laba</label><select name="coa_saldo_laba_id" class="form-control acc-select"><?= $acc_opts($def_saldo_laba); ?></select></div>
                    </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small" id="dispose-preview"></div>
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-check"></i> Simpan Pelepasan</button>
                    </div>
                    <?php if (!$def_laba || !$def_rugi): ?><div class="small text-muted mt-2">Tip: isi default akun laba/rugi pelepasan di Setting Akun (buat dulu akunnya di COA, mis. 7.400.xxx Laba Pelepasan Aset & 7.300.xxx Rugi Pelepasan Aset).</div><?php endif; ?>
                </form>
            </div></div>
        </div>

        <?php if ($mut_ready): ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-info"><i class="fas fa-balance-scale mr-1"></i> Revaluasi Aset (Nilai Wajar)</h6>
                <button class="btn btn-outline-info btn-sm" type="button" data-toggle="collapse" data-target="#revalBox">Revaluasi</button>
            </div>
            <div class="collapse" id="revalBox"><div class="card-body">
                <p class="small text-muted mb-2">Metode eliminasi (PSAK 16): akumulasi penyusutan dihapus dan nilai aset menjadi nilai wajar. <strong>Kenaikan</strong> memulihkan dulu rugi revaluasi sebelumnya (laba rugi), sisanya masuk <strong>Surplus Revaluasi</strong> (ekuitas). <strong>Penurunan</strong> mengurangi surplus aset ini dulu, sisanya <strong>Rugi Revaluasi</strong>.
                    Revaluasi berlaku akhir bulan: penyusutan s/d bulan revaluasi harus sudah diposting<?= $last_posted ? ' (terakhir diposting: ' . $pk_label($last_posted) . ')' : ''; ?>; penyusutan dari nilai baru mulai bulan berikutnya.</p>
                <form method="post" action="<?= base_url('asset_register/revalue/' . (int) $a->id); ?>" id="reval-form" onsubmit="return confirm('Simpan revaluasi? Jurnal revaluasi akan dibuat & diposting.');">
                    <div class="form-row">
                        <div class="form-group col-md-3"><label class="small font-weight-bold">Tanggal Revaluasi *</label><input type="date" name="tanggal" class="form-control" value="<?= date('Y-m-t'); ?>" required></div>
                        <div class="form-group col-md-3"><label class="small font-weight-bold">Nilai Wajar *</label><input type="number" name="nilai_wajar" id="nilai_wajar" class="form-control text-right" min="0" step="0.01" value="<?= (float) $a->nilai_buku; ?>" required>
                            <small class="text-muted">Mis. dari laporan penilai (KJPP).</small></div>
                        <?php if ($a->disusutkan): ?>
                        <div class="form-group col-md-3"><label class="small font-weight-bold">Sisa Umur Manfaat (bulan) *</label><input type="number" name="umur_baru" id="umur_baru" class="form-control text-right" min="1" step="1" value="<?= (int) $suggested_life ?: ''; ?>" required></div>
                        <div class="form-group col-md-3"><label class="small font-weight-bold">Nilai Residu Baru</label><input type="number" name="residu_baru" id="residu_baru" class="form-control text-right" min="0" step="0.01" value="0"></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Surplus Revaluasi (Ekuitas)</label><select name="coa_surplus_id" class="form-control acc-select"><?= $acc_opts($def_surplus); ?></select></div>
                        <div class="form-group col-md-4"><label class="small font-weight-bold">Akun Laba/Rugi Revaluasi</label><select name="coa_lr_id" class="form-control acc-select"><?= $acc_opts($def_lr_reval); ?></select></div>
                        <div class="form-group col-md-4"><label class="small font-weight-bold">Keterangan</label><input type="text" name="keterangan" class="form-control" placeholder="mis. Laporan KJPP No. ..."></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="small" id="reval-preview"></div>
                        <button type="submit" class="btn btn-info btn-sm"><i class="fas fa-check"></i> Simpan Revaluasi</button>
                    </div>
                    <?php if (!$def_surplus || !$def_lr_reval): ?><div class="small text-muted mt-2">Tip: buat akun Surplus Revaluasi (ekuitas, mis. 3.100.xxx) & Laba/Rugi Revaluasi di COA, lalu isi defaultnya di Setting Akun.</div><?php endif; ?>
                </form>
            </div></div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($mutations): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Riwayat Revaluasi & Pelepasan Sebagian</h6></div>
        <div class="card-body">
            <div class="table-responsive"><table class="table table-bordered table-sm small">
                <thead class="thead-light"><tr><th>Tanggal</th><th>Jenis</th><th>Detail</th><th class="text-right">Nilai Perolehan</th><th class="text-right">Akumulasi</th><th class="text-right">Laba (Rugi)</th><th class="text-right">Surplus</th><th>Jurnal</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($mutations as $m):
                    $is_rev = $m->jenis === 'REVALUASI';
                    $can_cancel = $can_edit && $a->status !== 'Disposed' && (int) $m->id === $last_mut_id && (int) $last_posted === (int) $m->dep_period_at; ?>
                    <tr>
                        <td><?= date('d-m-Y', strtotime($m->tanggal)); ?></td>
                        <td><?= $is_rev ? '<span class="badge badge-info">Revaluasi</span>' : '<span class="badge badge-dark">' . ($m->disposal_type === 'JUAL' ? 'Dijual' : 'Dihapus') . ' sebagian</span>'; ?></td>
                        <td><?php if ($is_rev): ?>Nilai wajar <?= $fmt($m->nilai_wajar); ?><?= (int) $m->umur_sesudah && $a->disusutkan ? ' · sisa umur ' . (int) $m->umur_sesudah . ' bln' : ''; ?>
                            <?php else: ?><?= htmlspecialchars($m->porsi_keterangan); ?><?= $m->disposal_type === 'JUAL' ? ' · harga ' . $fmt($m->harga_jual) : ''; ?><?php endif; ?>
                            <?php if ($m->keterangan): ?><div class="text-muted"><?= htmlspecialchars($m->keterangan); ?></div><?php endif; ?></td>
                        <td class="text-right"><?= $fmt($m->nilai_sebelum); ?> &rarr; <?= $fmt($m->nilai_sesudah); ?></td>
                        <td class="text-right"><?= $fmt($m->akumulasi_sebelum); ?> &rarr; <?= $fmt($m->akumulasi_sesudah); ?></td>
                        <td class="text-right <?= ($is_rev ? $m->ke_laba_rugi : $m->laba_rugi) < 0 ? 'text-danger' : ''; ?>"><?= $fmt($is_rev ? $m->ke_laba_rugi : $m->laba_rugi); ?></td>
                        <td class="text-right"><?= $is_rev ? $fmt($m->ke_surplus) : ((float) $m->surplus_ke_saldo_laba ? '&rarr; saldo laba ' . $fmt($m->surplus_ke_saldo_laba) : '-'); ?></td>
                        <td><?= $m->journal_header_id ? '<a href="' . base_url('jurnalumum/view/' . (int) $m->journal_header_id) . '">' . htmlspecialchars($m->journal_no) . '</a>' : '-'; ?></td>
                        <td class="text-center"><?php if ($can_cancel): ?>
                            <form method="post" action="<?= base_url('asset_register/cancel_mutation/' . (int) $m->id); ?>" onsubmit="return confirm('Batalkan <?= $is_rev ? 'revaluasi' : 'pelepasan sebagian'; ?> ini? Nilai aset dikembalikan & jurnalnya dihapus.');"><button class="btn btn-outline-danger btn-sm py-0" title="Batalkan"><i class="fas fa-undo"></i></button></form>
                        <?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
            <small class="text-muted">Hanya mutasi terakhir yang bisa dibatalkan, selama belum ada penyusutan baru yang diposting sesudahnya.</small>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Riwayat Penyusutan</h6></div>
        <div class="card-body">
            <div class="table-responsive"><table class="table table-bordered table-sm">
                <thead class="thead-light"><tr><th>Periode</th><th>Batch</th><th class="text-right">Penyusutan</th><th class="text-right">Akumulasi</th><th class="text-right">Nilai Buku</th><th class="text-center">Status</th><th>Jurnal</th></tr></thead>
                <tbody>
                <?php if (empty($history)): ?><tr><td colspan="7" class="text-center text-muted">Belum ada penyusutan.</td></tr>
                <?php else: foreach ($history as $h): ?>
                    <tr>
                        <td><?= $bln[(int) $h->periode_month] . ' ' . (int) $h->periode_year; ?></td>
                        <td><a href="<?= base_url('asset_depreciation/view/' . (int) $h->batch_id); ?>"><?= htmlspecialchars($h->batch_number); ?></a></td>
                        <td class="text-right"><?= $fmt($h->penyusutan_bulan_ini); ?></td>
                        <td class="text-right"><?= $fmt($h->akumulasi_sesudah); ?></td>
                        <td class="text-right"><?= $fmt($h->nilai_buku_sesudah); ?></td>
                        <td class="text-center"><span class="badge badge-<?= $h->batch_status === 'Posted' ? 'success' : 'warning'; ?>"><?= $h->batch_status; ?></span></td>
                        <td><?= htmlspecialchars($h->journal_no ?? '-'); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table></div>
        </div>
    </div>
</div>

<script>
$(function () {
    if ($.fn.select2) $('.acc-select').select2({ width: '100%' });
    var C = <?= json_encode((float) $a->nilai_perolehan); ?>, A = <?= json_encode((float) $a->akumulasi_penyusutan); ?>, BUKU = <?= json_encode((float) $a->nilai_buku); ?>;
    var SURPLUS = <?= json_encode($surplus); ?>, RUGI = <?= json_encode($rugi_reval); ?>;
    var r2 = function (n) { return Math.round(n * 100) / 100; };
    var f = function (n) { return Math.abs(n).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    function preview() {
        var jual = $('#disposal_type').val() === 'JUAL', part = $('#scope_part').is(':checked');
        $('.jual-only').toggle(jual);
        $('.part-only').toggle(part);
        var frac = 1;
        if (part) {
            var p = parseFloat($('#porsi_dilepas').val()) || 0, t = parseFloat($('#porsi_total').val()) || 0;
            frac = (t > 0 && p > 0 && p < t) ? p / t : 0;
            $('#porsi-info').html(frac ? (frac * 100).toLocaleString('id-ID', { maximumFractionDigits: 4 }) + '% dari aset' : '<span class="text-danger">Porsi harus &gt; 0 dan &lt; total</span>');
        }
        var buku = part ? r2(r2(C * frac) - r2(A * frac)) : BUKU;
        var harga = jual ? (parseFloat($('#disposal_value').val()) || 0) : 0, hasil = harga - buku;
        $('#surplus-part').text(f(part ? r2(SURPLUS * frac) : SURPLUS));
        $('#dispose-preview').html('Nilai buku ' + (part ? 'bagian dilepas ' : '') + 'Rp ' + f(buku) + ' &rarr; ' + (hasil >= 0 ? '<span class="text-success">laba Rp ' + f(hasil) + '</span>' : '<span class="text-danger">rugi Rp ' + f(hasil) + '</span>'));
    }
    $('#disposal_type, #disposal_value, #porsi_dilepas, #porsi_total, input[name=dispose_scope]').on('input change', preview);
    preview();

    function revalPreview() {
        var F = parseFloat($('#nilai_wajar').val()) || 0, diff = r2(F - BUKU), toS = 0, toPL = 0;
        if (diff > 0) { toPL = Math.min(diff, Math.max(0, RUGI)); toS = r2(diff - toPL); }
        else if (diff < 0) { var fs = Math.min(-diff, Math.max(0, SURPLUS)); toS = -fs; toPL = r2(diff + fs); }
        var parts = [];
        if (A > 0) parts.push('eliminasi akumulasi Rp ' + f(A));
        if (toS) parts.push((toS > 0 ? 'surplus revaluasi +Rp ' : 'kurangi surplus Rp ') + f(toS));
        if (toPL) parts.push(toPL > 0 ? '<span class="text-success">pemulihan rugi Rp ' + f(toPL) + '</span>' : '<span class="text-danger">rugi revaluasi Rp ' + f(toPL) + '</span>');
        var umur = parseInt($('#umur_baru').val(), 10) || 0, res = parseFloat($('#residu_baru').val()) || 0;
        var dep = ($('#umur_baru').length && umur > 0 && F > res) ? ' · penyusutan baru Rp ' + f((F - res) / umur) + '/bln' : '';
        $('#reval-preview').html('Nilai buku Rp ' + f(BUKU) + ' &rarr; Rp ' + f(F) + ' (' + (diff >= 0 ? 'naik' : 'turun') + ' Rp ' + f(diff) + ')' + (parts.length ? ': ' + parts.join(', ') : '') + dep);
    }
    $('#nilai_wajar, #umur_baru, #residu_baru').on('input change', revalPreview);
    if ($('#reval-form').length) revalPreview();
});
</script>
