<?php
/*
 * Daily Material NG Report — tampilan mengikuti sheet MATERIAL & PIVOT pada
 * file klien assets/DAILY PRODUCTION REPORT (PQC & MATERIAL) (1).xlsx.
 *
 * Kolom PART CHINESE sengaja tidak ditampilkan di layar (isinya selalu kosong;
 * diketik manual oleh user di file hasil export).
 */
$cats = ng_categories();
?>
<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= lang_line('mng_title', 'Daily Material NG Report'); ?></h1>
        <a href="<?= base_url('material_ng_report/export_excel') . '?' . http_build_query(array_filter($filters)); ?>"
           class="btn btn-success btn-sm shadow-sm">
            <i class="fas fa-file-excel mr-1"></i><?= lang_line('btn_export_excel', 'Export Excel'); ?>
        </a>
    </div>

    <?php if (empty($ready)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?= lang_line('mng_not_migrated', 'Kolom rincian NG belum ada di database. Jalankan <code>db/migration_material_ng_detail.sql</code> lewat halaman <code>/db_migration</code> terlebih dahulu.'); ?>
    </div>
    <?php endif; ?>

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
                    <label class="small mb-1">Sales Order</label>
                    <input type="text" name="ipo" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['ipo']); ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1">Model</label>
                    <input type="text" name="model" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['model']); ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1"><?= lang_line('mng_part', 'Part'); ?></label>
                    <input type="text" name="part" class="form-control form-control-sm" value="<?= htmlspecialchars($filters['part']); ?>" placeholder="<?= lang_line('mng_part_ph', 'nama / kode'); ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1"><?= lang_line('mng_ng_code', 'Kode NG'); ?></label>
                    <select name="ng_code" class="form-control form-control-sm">
                        <option value="">-</option>
                        <?php foreach ($ng_codes as $c): ?>
                        <option value="<?= htmlspecialchars($c->code); ?>" <?= $filters['ng_code'] === $c->code ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($c->code); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <label class="small mb-1"><?= lang_line('mng_source', 'Sumber'); ?></label>
                    <select name="source" class="form-control form-control-sm">
                        <option value=""><?= lang_line('mng_source_all', 'Semua'); ?></option>
                        <option value="scrap" <?= $filters['source'] === 'scrap' ? 'selected' : ''; ?>><?= lang_line('mng_source_scrap', 'Proses Produksi'); ?></option>
                        <option value="qc"    <?= $filters['source'] === 'qc'    ? 'selected' : ''; ?>><?= lang_line('mng_source_qc', 'QC Penerimaan'); ?></option>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search mr-1"></i><?= lang_line('btn_filter', 'Filter'); ?></button>
                    <a href="<?= base_url('material_ng_report'); ?>" class="btn btn-secondary btn-sm"><?= lang_line('btn_reset', 'Reset'); ?></a>
                </div>
            </form>
        </div>
    </div>

    <?php /* ── Ringkasan per kategori ── */ ?>
    <div class="row mb-4">
        <?php
        $cards = [
            'ng_human'    => ['HUMAN ERROR',    'primary'],
            'ng_material' => ['MATERIAL ISSUE', 'danger'],
            'ng_mechanic' => ['MECHANIC ISSUE', 'warning'],
            'ng_others'   => ['OTHERS ISSUE',   'info'],
            'total'       => ['TOTAL',          'dark'],
        ];
        foreach ($cards as $key => $c): ?>
        <div class="col">
            <div class="card border-left-<?= $c[1]; ?> shadow h-100 py-2">
                <div class="card-body py-2">
                    <div class="text-xs font-weight-bold text-<?= $c[1]; ?> text-uppercase mb-1"><?= $c[0]; ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totals[$key], 2); ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php /* ── Tab: rincian & pivot ── */ ?>
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-toggle="tab" href="#tabDetail"><?= lang_line('mng_tab_detail', 'Rincian (MATERIAL)'); ?>
                <span class="badge badge-secondary"><?= count($rows); ?></span></a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-toggle="tab" href="#tabPivot"><?= lang_line('mng_tab_pivot', 'Rekap (PIVOT)'); ?>
                <span class="badge badge-secondary"><?= count($pivot); ?></span></a>
        </li>
    </ul>

    <div class="tab-content border border-top-0 bg-white p-3 mb-4">

        <div class="tab-pane fade show active" id="tabDetail">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0" style="font-size:.85rem;">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>DATE</th>
                            <th>Sales Order NO.</th>
                            <th>MODEL</th>
                            <th>PART NAME</th>
                            <th>PART NUMBER</th>
                            <?php foreach ($cats as $label): ?>
                            <th class="text-right"><?= htmlspecialchars($label); ?></th>
                            <?php endforeach; ?>
                            <th class="text-right">TOTAL</th>
                            <th>NG CODE</th>
                            <th>REASON DEFECT</th>
                            <th>REMARK</th>
                            <th><?= lang_line('mng_th_source', 'Sumber'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                        <tr><td colspan="14" class="text-center text-muted py-4"><?= lang_line('mng_empty', 'Belum ada data rincian NG.'); ?></td></tr>
                        <?php else: foreach ($rows as $x): ?>
                        <tr>
                            <td class="text-center"><?= $x->ng_date ? date('d M Y', strtotime($x->ng_date)) : '-'; ?></td>
                            <td><?= htmlspecialchars($x->ipo_no) ?: '<span class="text-muted">-</span>'; ?></td>
                            <td><?= htmlspecialchars($x->model) ?: '<span class="text-muted">-</span>'; ?></td>
                            <td><?= htmlspecialchars($x->part_name); ?></td>
                            <td><?= htmlspecialchars($x->part_number); ?></td>
                            <?php foreach (array_keys($cats) as $col): ?>
                            <td class="text-right <?= ((float) $x->$col > 0) ? '' : 'text-muted'; ?>"><?= number_format((float) $x->$col, 2); ?></td>
                            <?php endforeach; ?>
                            <td class="text-right font-weight-bold"><?= number_format((float) $x->total, 2); ?></td>
                            <td class="text-center"><?= $x->ng_code ? '<span class="badge badge-danger">' . htmlspecialchars($x->ng_code) . '</span>' : '<span class="text-muted">-</span>'; ?></td>
                            <td><small><?= htmlspecialchars($x->reason) ?: '-'; ?></small></td>
                            <td><small><?= htmlspecialchars($x->remark) ?: '-'; ?></small></td>
                            <td class="text-center">
                                <span class="badge badge-<?= $x->source === 'scrap' ? 'warning text-dark' : 'info'; ?>">
                                    <?= $x->source === 'scrap' ? lang_line('mng_source_scrap', 'Proses Produksi') : lang_line('mng_source_qc', 'QC Penerimaan'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if (!empty($rows)): ?>
                    <tfoot class="font-weight-bold bg-light">
                        <tr>
                            <td colspan="5" class="text-right">GRAND TOTAL</td>
                            <?php foreach (array_keys($cats) as $col): ?>
                            <td class="text-right"><?= number_format($totals[$col], 2); ?></td>
                            <?php endforeach; ?>
                            <td class="text-right"><?= number_format($totals['total'], 2); ?></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="tabPivot">
            <div class="table-responsive">
                <table class="table table-sm table-bordered table-hover mb-0" style="font-size:.85rem;">
                    <thead class="thead-light text-center">
                        <tr>
                            <th>Sales Order NO.</th>
                            <th>MODEL</th>
                            <th>PART NAME</th>
                            <th>PART NUMBER</th>
                            <th>DATE</th>
                            <th>REMARK</th>
                            <th class="text-right">Sum of HUMAN ERROR</th>
                            <th class="text-right">Sum of OTHERS ISSUE</th>
                            <th class="text-right">Sum of MATERIAL ISSUE</th>
                            <th class="text-right">Sum of MECHANIC ISSUE</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pivot)): ?>
                        <tr><td colspan="10" class="text-center text-muted py-4"><?= lang_line('mng_empty', 'Belum ada data rincian NG.'); ?></td></tr>
                        <?php else: foreach ($pivot as $x): ?>
                        <tr>
                            <td><?= htmlspecialchars($x->ipo_no) ?: '<span class="text-muted">-</span>'; ?></td>
                            <td><?= htmlspecialchars($x->model) ?: '<span class="text-muted">-</span>'; ?></td>
                            <td><?= htmlspecialchars($x->part_name); ?></td>
                            <td><?= htmlspecialchars($x->part_number); ?></td>
                            <td class="text-center"><?= $x->ng_date ? date('d M Y', strtotime($x->ng_date)) : '-'; ?></td>
                            <td><small><?= htmlspecialchars($x->remark) ?: '-'; ?></small></td>
                            <td class="text-right"><?= number_format($x->ng_human, 2); ?></td>
                            <td class="text-right"><?= number_format($x->ng_others, 2); ?></td>
                            <td class="text-right"><?= number_format($x->ng_material, 2); ?></td>
                            <td class="text-right"><?= number_format($x->ng_mechanic, 2); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <?php if (!empty($pivot)): ?>
                    <tfoot class="font-weight-bold bg-light">
                        <tr>
                            <td colspan="6" class="text-right">Grand Total</td>
                            <td class="text-right"><?= number_format($totals['ng_human'], 2); ?></td>
                            <td class="text-right"><?= number_format($totals['ng_others'], 2); ?></td>
                            <td class="text-right"><?= number_format($totals['ng_material'], 2); ?></td>
                            <td class="text-right"><?= number_format($totals['ng_mechanic'], 2); ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

    </div>
</div>
