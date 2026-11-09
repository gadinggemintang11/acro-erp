<?php
/*
 * Daily Material NG Report -- disederhanakan 2026-11-08: tiap NG cukup Qty NG +
 * Keterangan. Rincian 4 kategori, Kode NG, dan format MATERIAL/PIVOT warisan
 * NSI dihapus. Data: QC Production Receive & asal scrap Proses Produksi.
 */
$qty = function ($n) { $s = number_format((float) $n, 4, ',', '.'); return rtrim(rtrim($s, '0'), ','); };
?>
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><?= lang_line('mng_title', 'Daily Material NG Report'); ?></h1>
            <div class="small text-muted"><?= lang_line('mng_subtitle', 'Material NG dari QC Production Receive dan dari scrap Proses Produksi — qty & keterangan.'); ?></div>
        </div>
        <a href="<?= base_url('material_ng_report/export_excel') . '?' . http_build_query(array_filter($filters)); ?>"
           class="btn btn-success btn-sm shadow-sm">
            <i class="fas fa-file-excel mr-1"></i><?= lang_line('btn_export_excel', 'Export Excel'); ?>
        </a>
    </div>

    <?php /* ── Filter ── */ ?>
    <div class="card shadow mb-4">
        <div class="card-body py-3">
            <form method="get" class="form-row align-items-end">
                <div class="col-md-2 mb-2">
                    <label class="small mb-1"><?= lang_line('mng_date_from', 'Dari Tanggal'); ?></label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_from'] ?? ''); ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1"><?= lang_line('mng_date_to', 'Sampai Tanggal'); ?></label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['date_to'] ?? ''); ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1"><?= lang_line('mng_source', 'Sumber'); ?></label>
                    <select name="source" class="form-control form-control-sm">
                        <option value=""><?= lang_line('mng_source_all', 'Semua'); ?></option>
                        <option value="qc"    <?= $filters['source'] === 'qc'    ? 'selected' : ''; ?>><?= lang_line('mng_source_qc', 'QC Penerimaan'); ?></option>
                        <option value="scrap" <?= $filters['source'] === 'scrap' ? 'selected' : ''; ?>><?= lang_line('mng_source_scrap', 'Proses Produksi'); ?></option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1"><?= lang_line('mng_part', 'Part'); ?></label>
                    <input type="text" name="part" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['part']); ?>" placeholder="<?= lang_line('mng_part_ph', 'nama / kode'); ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1">Sales Order</label>
                    <input type="text" name="ipo" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['ipo']); ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1">Model</label>
                    <input type="text" name="model" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['model']); ?>">
                </div>
                <div class="col-12 mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i><?= lang_line('btn_filter', 'Filter'); ?></button>
                    <a href="<?= base_url('material_ng_report'); ?>" class="btn btn-secondary btn-sm"><?= lang_line('btn_reset', 'Reset'); ?></a>
                    <?php if (!empty($filters['ipo']) || !empty($filters['model'])): ?>
                        <small class="text-muted ml-2"><i class="fas fa-info-circle mr-1"></i><?= lang_line('mng_qc_no_so', 'NG QC Penerimaan tidak punya Sales Order/Model, jadi tidak ikut saat filter itu dipakai.'); ?></small>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php /* ── Ringkasan ── */ ?>
    <div class="row mb-4">
        <?php
        $cards = [
            [lang_line('mng_card_total', 'Total Qty NG'),           $qty($totals['qty_ng']),   'danger',  'fa-exclamation-triangle'],
            [lang_line('mng_source_qc', 'QC Penerimaan'),           $qty($totals['qty_qc']),   'info',    'fa-microscope'],
            [lang_line('mng_source_scrap', 'Proses Produksi'),      $qty($totals['qty_scrap']),'warning', 'fa-cogs'],
            [lang_line('mng_card_events', 'Jumlah Kejadian'),       number_format($totals['events']) . ' · ' . number_format($totals['parts']) . ' part', 'dark', 'fa-list'],
        ];
        foreach ($cards as $c): ?>
        <div class="col-sm-6 col-xl-3 mb-3">
            <div class="card border-left-<?= $c[2]; ?> shadow h-100 py-2">
                <div class="card-body py-2 d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-xs font-weight-bold text-<?= $c[2]; ?> text-uppercase mb-1"><?= htmlspecialchars($c[0]); ?></div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $c[1]; ?></div>
                    </div>
                    <i class="fas <?= $c[3]; ?> fa-2x text-gray-300"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if (count(array_unique(array_map(function ($r) { return $r->unit; }, $rows))) > 1): ?>
        <div class="small text-muted mb-3"><i class="fas fa-info-circle mr-1"></i><?= lang_line('mng_mixed_unit', 'Total di atas menjumlahkan beberapa satuan; lihat Rekap per Part untuk total per satuan.'); ?></div>
    <?php endif; ?>

    <?php /* ── Tab: rincian & rekap ── */ ?>
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#tabDetail"><?= lang_line('mng_tab_detail2', 'Rincian NG'); ?>
                <span class="badge badge-secondary"><?= count($rows); ?></span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tabRecap"><?= lang_line('mng_tab_recap', 'Rekap per Part'); ?>
                <span class="badge badge-secondary"><?= count($recap); ?></span></a>
        </li>
    </ul>

    <div class="tab-content border border-top-0 bg-white p-3 mb-4">
        <div class="tab-pane fade show active" id="tabDetail">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0" style="font-size:.85rem;">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center"><?= lang_line('mng_th_date', 'Tanggal'); ?></th>
                            <th class="text-center"><?= lang_line('mng_th_source', 'Sumber'); ?></th>
                            <th><?= lang_line('mng_th_doc', 'No. Dokumen'); ?></th>
                            <th>Sales Order</th>
                            <th>Model</th>
                            <th><?= lang_line('mng_th_part', 'Part'); ?></th>
                            <th class="text-right"><?= lang_line('mng_th_qty_ng', 'Qty NG'); ?></th>
                            <th><?= lang_line('mng_th_remark', 'Keterangan'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4"><?= lang_line('mng_empty2', 'Belum ada data NG.'); ?></td></tr>
                        <?php else: foreach ($rows as $x): ?>
                        <tr>
                            <td class="text-center text-nowrap"><?= $x->ng_date ? date('d M Y', strtotime($x->ng_date)) : '-'; ?></td>
                            <td class="text-center">
                                <span class="badge badge-<?= $x->source === 'scrap' ? 'warning text-dark' : 'info'; ?>"><?= Material_ng_model::source_label($x->source); ?></span>
                            </td>
                            <td class="text-nowrap"><?= htmlspecialchars($x->doc_number) ?: '<span class="text-muted">-</span>'; ?></td>
                            <td><?= htmlspecialchars($x->ipo_no) ?: '<span class="text-muted">-</span>'; ?></td>
                            <td><?= htmlspecialchars($x->model) ?: '<span class="text-muted">-</span>'; ?></td>
                            <td><strong><?= htmlspecialchars($x->part_name); ?></strong><br><small class="text-muted"><?= htmlspecialchars($x->part_number); ?></small></td>
                            <td class="text-right text-nowrap font-weight-bold text-danger"><?= $qty($x->qty_ng); ?> <small class="text-muted font-weight-normal"><?= htmlspecialchars($x->unit); ?></small></td>
                            <td><?= htmlspecialchars($x->remark) ?: '<span class="text-muted">-</span>'; ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="tabRecap">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0" style="font-size:.85rem;">
                    <thead class="thead-light">
                        <tr>
                            <th><?= lang_line('mng_th_part', 'Part'); ?></th>
                            <th class="text-center"><?= lang_line('mng_th_unit', 'Satuan'); ?></th>
                            <th class="text-right"><?= lang_line('mng_source_qc', 'QC Penerimaan'); ?></th>
                            <th class="text-right"><?= lang_line('mng_source_scrap', 'Proses Produksi'); ?></th>
                            <th class="text-right"><?= lang_line('mng_card_total', 'Total Qty NG'); ?></th>
                            <th class="text-right"><?= lang_line('mng_card_events', 'Jumlah Kejadian'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recap)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4"><?= lang_line('mng_empty2', 'Belum ada data NG.'); ?></td></tr>
                        <?php else: foreach ($recap as $x): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($x->part_name); ?></strong><br><small class="text-muted"><?= htmlspecialchars($x->part_number); ?></small></td>
                            <td class="text-center"><?= htmlspecialchars($x->unit); ?></td>
                            <td class="text-right"><?= $qty($x->qty_qc); ?></td>
                            <td class="text-right"><?= $qty($x->qty_scrap); ?></td>
                            <td class="text-right font-weight-bold text-danger"><?= $qty($x->qty_ng); ?></td>
                            <td class="text-right"><?= number_format($x->events); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
