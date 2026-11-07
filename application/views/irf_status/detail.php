<?php
/**
 * IRF Status — detail satu nomor IRF.
 * Menampilkan info lengkap: header, progres pemenuhan, rincian per item,
 * riwayat dokumen pengeluaran, jejak approval, dan metrik analisis.
 */
$badge_status = [
    'Draft'              => 'secondary',
    'Submitted'          => 'info',
    'Approved Head Dept' => 'info',
    'Endorsed'           => 'primary',
    'Approved'           => 'success',
    'Released'           => 'primary',
    'Rejected'           => 'danger',
    'Cancelled'          => 'dark',
];
$doc_badge = ['Confirmed' => 'success', 'Created' => 'info', 'Cancelled' => 'danger'];

$log_meta = [
    'submit'        => ['label' => lang_line('cr_log_submit',        'Submitted'),               'icon' => 'paper-plane',    'color' => 'info'],
    'approve_hod'   => ['label' => lang_line('cr_log_approve_hod',   'Approved (Head of Dept)'), 'icon' => 'check',          'color' => 'success'],
    'endorse'       => ['label' => lang_line('cr_log_endorse',       'Endorsed'),                'icon' => 'file-signature', 'color' => 'primary'],
    'approve_final' => ['label' => lang_line('cr_log_approve_final', 'Final Approved'),          'icon' => 'check-double',   'color' => 'success'],
    'reject'        => ['label' => lang_line('cr_log_reject',        'Rejected'),                'icon' => 'times',          'color' => 'danger'],
    'return'        => ['label' => lang_line('cr_log_return',        'Returned to Draft'),       'icon' => 'undo',           'color' => 'warning'],
    'admin_edit'    => ['label' => lang_line('cr_log_admin_edit',    'Item Diedit (Admin)'),     'icon' => 'user-shield',    'color' => 'warning'],
];

$progress   = (float)$stats['progress'];
$bar_color  = $stats['qty_outstanding'] <= 0 ? 'success' : ($stats['qty_released'] > 0 ? 'info' : 'warning');
$fmt_dt     = function ($v) { return $v ? date('d M Y H:i', strtotime($v)) : '-'; };
$fmt_d      = function ($v) { return $v ? date('d M Y', strtotime($v)) : '-'; };
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="text-gray-800 mb-0">
                <i class="fas fa-chart-bar text-primary mr-2"></i>
                <?= lang_line('irfs_detail_heading', 'Status Permintaan Barang'); ?>
                <span class="text-primary"><?= htmlspecialchars($header->request_number) ?></span>
                <span class="badge badge-<?= $badge_status[$header->status] ?? 'secondary' ?> align-middle ml-1" style="font-size:0.8rem;">
                    <?= htmlspecialchars($header->status) ?>
                </span>
            </h3>
            <small class="text-muted"><?= lang_line('irfs_detail_subtitle', 'Rincian permintaan, pengeluaran, dan sisa outstanding.'); ?></small>
        </div>
        <a href="<?= base_url('irf_status') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> <?= lang_line('irfs_btn_back', 'Kembali'); ?>
        </a>
    </div>

    <!-- Ringkasan qty -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= lang_line('irfs_st_requested', 'Qty Diminta'); ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['qty_requested'], 2) ?></div>
                    <div class="small text-muted"><?= (int)$stats['total_lines'] ?> <?= lang_line('irfs_st_lines', 'baris item'); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1"><?= lang_line('irfs_st_released', 'Qty Released'); ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['qty_released'], 2) ?></div>
                    <div class="small text-muted"><?= (int)$stats['doc_confirmed'] ?> <?= lang_line('irfs_st_docs', 'dokumen dikonfirmasi'); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1"><?= lang_line('irfs_st_pending', 'Dalam Proses'); ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['qty_pending'], 2) ?></div>
                    <div class="small text-muted"><?= (int)$stats['doc_inprocess'] ?> <?= lang_line('irfs_st_docs_open', 'dokumen belum dikonfirmasi'); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow-sm h-100 py-2">
                <div class="card-body py-2">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1"><?= lang_line('irfs_st_outstanding', 'Outstanding'); ?></div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['qty_outstanding'], 2) ?></div>
                    <div class="small text-muted">
                        <?= (int)$stats['lines_partial'] + (int)$stats['lines_untouched'] ?> <?= lang_line('irfs_st_lines_open', 'baris belum lunas'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong><?= lang_line('irfs_fulfillment', 'Progres Pemenuhan'); ?></strong>
                <span class="font-weight-bold text-<?= $bar_color ?>"><?= number_format($progress, 1) ?>%</span>
            </div>
            <div class="progress mb-2" style="height: 20px;">
                <div class="progress-bar bg-<?= $bar_color ?>" role="progressbar" style="width: <?= round($progress, 1) ?>%;"
                     aria-valuenow="<?= round($progress, 1) ?>" aria-valuemin="0" aria-valuemax="100">
                    <?= number_format($stats['qty_released'], 2) ?> / <?= number_format($stats['qty_requested'], 2) ?>
                </div>
            </div>
            <div class="small text-muted">
                <span class="mr-3"><i class="fas fa-check-circle text-success mr-1"></i><?= (int)$stats['lines_complete'] ?> <?= lang_line('irfs_lines_complete', 'item lunas'); ?></span>
                <span class="mr-3"><i class="fas fa-adjust text-info mr-1"></i><?= (int)$stats['lines_partial'] ?> <?= lang_line('irfs_lines_partial', 'item sebagian'); ?></span>
                <span class="mr-3"><i class="fas fa-circle text-warning mr-1"></i><?= (int)$stats['lines_untouched'] ?> <?= lang_line('irfs_lines_untouched', 'item belum keluar'); ?></span>
                <?php if ((int)$stats['lines_over'] > 0): ?>
                    <span class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i><?= (int)$stats['lines_over'] ?> <?= lang_line('irfs_lines_over', 'item over-issue'); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Permintaan Barang Tracking (user 2026-09-15): Permintaan Barang sampai mana — Permintaan Barang, PR, PO, diterima, dikeluarkan -->
    <?php
      $step_style = [
          'done'    => ['bg' => '#1cc88a', 'fg' => '#fff', 'text' => 'text-success', 'label' => 'Selesai'],
          'current' => ['bg' => '#4e73df', 'fg' => '#fff', 'text' => 'text-primary', 'label' => 'Berjalan'],
          'pending' => ['bg' => '#eaecf4', 'fg' => '#858796', 'text' => 'text-muted', 'label' => 'Belum'],
          'skipped' => ['bg' => '#f8f9fc', 'fg' => '#b7b9cc', 'text' => 'text-muted', 'label' => 'Dilewati'],
          'stopped' => ['bg' => '#e74a3b', 'fg' => '#fff', 'text' => 'text-danger', 'label' => 'Berhenti'],
      ];
      $doc_status_badge = function ($status) {
          $map = ['Approved' => 'success', 'Confirmed' => 'success', 'Completed' => 'success', 'Draft' => 'secondary',
                  'Rejected' => 'danger', 'Cancelled' => 'dark', 'Created' => 'info'];
          return $map[$status] ?? 'info';
      };
      $doc_link = function ($number, $url, $status) use ($doc_status_badge) {
          $label = htmlspecialchars($number);
          $html  = $url ? '<a href="' . htmlspecialchars($url) . '" target="_blank">' . $label . '</a>' : $label;
          return '<span class="text-nowrap">' . $html . ' <span class="badge badge-' . $doc_status_badge($status) . '">' . htmlspecialchars($status) . '</span></span>';
      };
      $num = function ($v) { return rtrim(rtrim(number_format((float) $v, 2, '.', ','), '0'), '.'); };
    ?>
    <div class="card border-0 shadow-sm mb-4" id="irf-tracking">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-route mr-1"></i> Permintaan Barang Tracking</strong>
            <small class="text-muted">Posisi Permintaan Barang: masih di Permintaan Barang, sudah PR, PO, diterima gudang, atau sudah dikeluarkan</small>
        </div>
        <div class="card-body">
            <div class="irf-track d-flex flex-wrap">
                <?php foreach ($tracking['steps'] as $i => $step): $st = $step_style[$step['state']]; ?>
                <div class="irf-track-step">
                    <div class="d-flex align-items-center">
                        <div class="irf-track-dot" style="background:<?= $st['bg']; ?>;color:<?= $st['fg']; ?>;<?= $step['state'] === 'skipped' ? 'border:2px dashed #d1d3e2;' : ''; ?>">
                            <i class="fas fa-<?= $step['state'] === 'done' ? 'check' : ($step['state'] === 'stopped' ? 'times' : htmlspecialchars($step['icon'])); ?>"></i>
                        </div>
                        <?php if ($i < count($tracking['steps']) - 1): ?><div class="irf-track-line" style="background:<?= $step['state'] === 'done' ? '#1cc88a' : '#e3e6f0'; ?>"></div><?php endif; ?>
                    </div>
                    <div class="mt-2 pr-2">
                        <div class="font-weight-bold small"><?= htmlspecialchars($step['label']); ?></div>
                        <div class="small <?= $st['text']; ?>"><?= htmlspecialchars($step['info']); ?></div>
                        <?php if (!empty($step['docs'])): ?>
                        <div class="small mt-1">
                            <?php foreach ($step['docs'] as $d):
                                $number = $d->pr_number ?? $d->po_number ?? $d->doc_number ?? '-';
                                $status = $d->pr_status ?? $d->po_status ?? $d->status ?? '';
                            ?>
                                <div><?= $doc_link($number, $d->url ?? null, $status); ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="table-responsive mt-3">
                <table class="table table-sm table-bordered small mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th style="min-width:180px">Item</th>
                            <th class="text-right">Diminta</th>
                            <th style="min-width:150px">PR</th>
                            <th style="min-width:150px">PO</th>
                            <th style="min-width:150px">Diterima Gudang</th>
                            <th class="text-right">Dikeluarkan</th>
                            <th style="min-width:200px">Posisi Sekarang</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $it): $trk = $tracking['items'][(int) $it->cr_detail_id] ?? ['label' => '-', 'color' => 'secondary', 'prs' => []]; ?>
                        <tr>
                            <td>
                                <div class="font-weight-bold"><?= htmlspecialchars($it->item_code ?: '-'); ?></div>
                                <?= htmlspecialchars($it->item_name ?: '-'); ?>
                            </td>
                            <td class="text-right text-nowrap"><?= $num($it->qty_requested); ?> <?= htmlspecialchars($it->item_unit ?? ''); ?></td>
                            <td>
                                <?php if (empty($trk['prs'])): ?><span class="text-muted">-</span><?php endif; ?>
                                <?php foreach ($trk['prs'] as $pr): ?>
                                    <div><?= $doc_link($pr->pr_number, $pr->url ?? null, $pr->pr_status); ?>
                                        <span class="text-muted">· <?= $num($pr->link_qty); ?></span></div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <?php $has_po = false; foreach ($trk['prs'] as $pr) foreach ($pr->pos as $po): $has_po = true; ?>
                                    <div><?= $doc_link($po->po_number, $po->url ?? null, $po->po_status); ?>
                                        <span class="text-muted">· <?= $num($po->po_qty); ?></span></div>
                                <?php endforeach; ?>
                                <?php if (!$has_po): ?><span class="text-muted">-</span><?php endif; ?>
                            </td>
                            <td>
                                <?php $has_rc = false; foreach ($trk['prs'] as $pr) foreach ($pr->pos as $po) foreach ($po->receipts as $rc): $has_rc = true; ?>
                                    <div><?= $doc_link($rc->doc_number, $rc->url ?? null, $rc->status ?: '-'); ?>
                                        <span class="text-muted">· <?= $num($rc->receive_qty); ?></span></div>
                                <?php endforeach; ?>
                                <?php if (!$has_rc): ?><span class="text-muted">-</span><?php endif; ?>
                            </td>
                            <td class="text-right text-nowrap"><?= $num($it->qty_released); ?></td>
                            <td><span class="badge badge-<?= $trk['color']; ?>" style="white-space:normal;text-align:left;"><?= htmlspecialchars($trk['label']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-2">
                <i class="fas fa-info-circle mr-1"></i> Angka di samping PR = qty Permintaan Barang ini yang dibawa ke PR. Satu PR/PO bisa menggabungkan beberapa Permintaan Barang, jadi qty PO & penerimaan adalah qty baris PO tersebut.
            </div>
        </div>
    </div>
    <style>
      .irf-track-step { flex: 1 1 0; min-width: 150px; margin-bottom: .75rem; }
      .irf-track-dot { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
      .irf-track-line { height: 3px; flex: 1; margin: 0 6px; }
    </style>

    <div class="row">
        <div class="col-lg-8">
            <!-- Informasi Permintaan Barang -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-info-circle mr-1"></i> <?= lang_line('irfs_card_info', 'Informasi Permintaan Barang'); ?></strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong><?= lang_line('irfs_lbl_number', 'No Permintaan Barang'); ?>:</strong> <?= htmlspecialchars($header->request_number) ?></p>
                            <p class="mb-2"><strong><?= lang_line('irfs_lbl_date', 'Tanggal'); ?>:</strong> <?= $fmt_d($header->request_date) ?></p>
                            <p class="mb-2"><strong><?= lang_line('irfs_lbl_department', 'Departemen'); ?>:</strong> <?= htmlspecialchars($header->department ?: '-') ?></p>
                            <p class="mb-0"><strong><?= lang_line('irfs_lbl_requestor', 'Requestor'); ?>:</strong> <?= htmlspecialchars($header->requestor_name ?: '-') ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong><?= lang_line('irfs_lbl_created_by', 'Dibuat Oleh'); ?>:</strong> <?= htmlspecialchars($header->created_by_name ?: '-') ?></p>
                            <p class="mb-2"><strong><?= lang_line('irfs_lbl_created_at', 'Dibuat Tanggal'); ?>:</strong> <?= $fmt_dt($header->created_at) ?></p>
                            <p class="mb-2"><strong><?= lang_line('irfs_lbl_approved_by', 'Approved Oleh'); ?>:</strong> <?= htmlspecialchars($header->final_approver_name ?: ($header->approved_by_name ?: ($header->hod_approver_name ?: '-'))) ?></p>
                            <p class="mb-0"><strong><?= lang_line('irfs_lbl_approved_at', 'Approved Tanggal'); ?>:</strong> <?= $fmt_dt($stats['approved_at']) ?></p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <p class="mb-2"><strong><?= lang_line('irfs_lbl_purpose', 'Keperluan'); ?>:</strong> <?= nl2br(htmlspecialchars($header->purpose ?: '-')) ?></p>
                    <p class="mb-0"><strong><?= lang_line('irfs_lbl_notes', 'Catatan'); ?>:</strong> <?= nl2br(htmlspecialchars($header->notes ?: '-')) ?></p>
                    <?php if (!empty($header->reject_reason)): ?>
                        <div class="alert alert-danger mt-3 mb-0 py-2 small">
                            <strong><?= lang_line('irfs_lbl_reject_reason', 'Alasan Reject'); ?>:</strong> <?= nl2br(htmlspecialchars($header->reject_reason)) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($header->return_reason)): ?>
                        <div class="alert alert-warning mt-3 mb-0 py-2 small">
                            <strong><?= lang_line('irfs_lbl_return_reason', 'Alasan Return ke Draft'); ?>:</strong> <?= nl2br(htmlspecialchars($header->return_reason)) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rincian per item -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-boxes mr-1"></i> <?= lang_line('irfs_card_items', 'Rincian Per Item'); ?></strong>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th><?= lang_line('irfs_th_code', 'Kode'); ?></th>
                                    <th><?= lang_line('irfs_th_item', 'Nama Barang'); ?></th>
                                    <th class="text-center"><?= lang_line('irfs_th_unit', 'Satuan'); ?></th>
                                    <th class="text-right"><?= lang_line('irfs_th_requested', 'Diminta'); ?></th>
                                    <th class="text-right"><?= lang_line('irfs_th_released', 'Released'); ?></th>
                                    <th class="text-right"><?= lang_line('irfs_th_pending', 'Proses'); ?></th>
                                    <th class="text-right"><?= lang_line('irfs_th_outstanding', 'Outstanding'); ?></th>
                                    <th class="text-right"><?= lang_line('irfs_th_stock', 'Stok Saat Ini'); ?></th>
                                    <th class="text-center" style="min-width:120px;"><?= lang_line('irfs_th_progress', 'Progres'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('irfs_no_item', 'Permintaan Barang ini tidak punya baris item.'); ?></td></tr>
                                <?php else: ?>
                                    <?php foreach ($items as $it):
                                        $req = (float)$it->qty_requested;
                                        $rel = (float)$it->qty_released;
                                        $pen = (float)$it->qty_pending;
                                        $out = (float)$it->qty_outstanding;
                                        $p   = $req > 0 ? min(($rel / $req) * 100, 100) : 0;
                                        $c   = $out <= 0 ? 'success' : ($rel > 0 ? 'info' : 'warning');
                                    ?>
                                    <tr>
                                        <td><strong class="text-primary"><?= htmlspecialchars($it->item_code ?: '-') ?></strong></td>
                                        <td>
                                            <?= htmlspecialchars($it->item_name ?: '-') ?>
                                            <?php if (($it->item_type ?? 'inventory') === 'machine'): ?>
                                                <span class="badge badge-info ml-1"><?= lang_line('cr_group_machine', 'Mesin / Sparepart'); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($it->notes)): ?>
                                                <div class="small text-muted"><i class="fas fa-comment-dots mr-1"></i><?= htmlspecialchars($it->notes) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= htmlspecialchars($it->item_unit ?: '-') ?></td>
                                        <td class="text-right"><?= number_format($req, 2) ?></td>
                                        <td class="text-right text-success"><?= number_format($rel, 2) ?></td>
                                        <td class="text-right"><?= $pen > 0 ? '<span class="text-info">' . number_format($pen, 2) . '</span>' : '<span class="text-muted">-</span>' ?></td>
                                        <td class="text-right">
                                            <?= $out > 0 ? '<strong class="text-warning">' . number_format($out, 2) . '</strong>' : '<span class="text-muted">-</span>' ?>
                                        </td>
                                        <td class="text-right <?= ($out > 0 && (float)$it->stock_on_hand < $out) ? 'text-danger font-weight-bold' : 'text-muted' ?>">
                                            <?= number_format((float)$it->stock_on_hand, 2) ?>
                                            <?php if ($out > 0 && (float)$it->stock_on_hand < $out): ?>
                                                <i class="fas fa-exclamation-triangle ml-1" title="<?= lang_line('irfs_stock_short', 'Stok tidak cukup untuk menutup outstanding'); ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 12px;">
                                                <div class="progress-bar bg-<?= $c ?>" style="width: <?= round($p, 1) ?>%;"></div>
                                            </div>
                                            <small class="text-muted"><?= number_format($p, 1) ?>%</small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <th colspan="3" class="text-right"><?= lang_line('irfs_total', 'Total'); ?></th>
                                    <th class="text-right"><?= number_format($stats['qty_requested'], 2) ?></th>
                                    <th class="text-right text-success"><?= number_format($stats['qty_released'], 2) ?></th>
                                    <th class="text-right text-info"><?= number_format($stats['qty_pending'], 2) ?></th>
                                    <th class="text-right text-warning"><?= number_format($stats['qty_outstanding'], 2) ?></th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Riwayat pengeluaran -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-dolly mr-1"></i> <?= lang_line('irfs_card_issuance', 'Riwayat Pengeluaran Barang'); ?></strong>
                </div>
                <div class="card-body">
                    <?php if (empty($issuances)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-inbox mr-1"></i> <?= lang_line('irfs_no_issuance', 'Belum ada dokumen pengeluaran untuk Permintaan Barang ini.'); ?>
                        </div>
                    <?php else: ?>
                        <div class="accordion" id="issuanceAccordion">
                            <?php foreach ($issuances as $idx => $doc): ?>
                            <div class="card mb-2 border">
                                <div class="card-header py-2 bg-white" id="issHead<?= (int)$doc->id ?>" style="cursor:pointer;"
                                     data-toggle="collapse" data-target="#issBody<?= (int)$doc->id ?>">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <div>
                                            <strong class="text-primary"><?= htmlspecialchars($doc->doc_number) ?></strong>
                                            <span class="badge badge-<?= $doc_badge[$doc->status] ?? 'secondary' ?> ml-1"><?= htmlspecialchars($doc->status) ?></span>
                                            <span class="small text-muted ml-2"><i class="fas fa-calendar-alt mr-1"></i><?= $fmt_d($doc->issuance_date) ?></span>
                                            <?php if (!empty($doc->recipient)): ?>
                                                <span class="small text-muted ml-2"><i class="fas fa-user mr-1"></i><?= htmlspecialchars($doc->recipient) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-right">
                                            <span class="small text-muted mr-2"><?= (int)$doc->total_lines ?> <?= lang_line('irfs_item_short', 'item'); ?></span>
                                            <strong><?= number_format((float)$doc->total_qty, 2) ?></strong>
                                            <i class="fas fa-chevron-down text-muted ml-2 small"></i>
                                        </div>
                                    </div>
                                </div>
                                <div id="issBody<?= (int)$doc->id ?>" class="collapse" data-parent="#issuanceAccordion">
                                    <div class="card-body py-2">
                                        <div class="row small text-muted mb-2">
                                            <div class="col-md-4"><?= lang_line('irfs_lbl_created_by', 'Dibuat Oleh'); ?>: <?= htmlspecialchars($doc->created_by_name ?: '-') ?></div>
                                            <div class="col-md-4"><?= lang_line('irfs_lbl_confirmed_by', 'Dikonfirmasi Oleh'); ?>: <?= htmlspecialchars($doc->confirmed_by_name ?: '-') ?></div>
                                            <div class="col-md-4"><?= lang_line('irfs_lbl_confirmed_at', 'Waktu Konfirmasi'); ?>: <?= $fmt_dt($doc->confirmed_at) ?></div>
                                        </div>
                                        <table class="table table-sm table-bordered mb-2">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th><?= lang_line('irfs_th_code', 'Kode'); ?></th>
                                                    <th><?= lang_line('irfs_th_item', 'Nama Barang'); ?></th>
                                                    <th class="text-center"><?= lang_line('irfs_th_unit', 'Satuan'); ?></th>
                                                    <th class="text-right"><?= lang_line('irfs_th_qty_issued', 'Qty Keluar'); ?></th>
                                                    <th><?= lang_line('irfs_th_notes', 'Catatan'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($doc->lines as $l): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($l->item_code ?: '-') ?></td>
                                                    <td><?= htmlspecialchars($l->item_name ?: '-') ?></td>
                                                    <td class="text-center"><?= htmlspecialchars($l->item_unit ?: '-') ?></td>
                                                    <td class="text-right"><?= number_format((float)$l->qty_issued, 2) ?></td>
                                                    <td class="small text-muted"><?= htmlspecialchars($l->notes ?: '-') ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        <?php $co_cat_key = 'consumable_out_' . strtolower($header->category ?? 'General'); ?>
                                        <?php if (check_access($co_cat_key, 'view') || check_access('consumable_out', 'view')): ?>
                                            <a href="<?= base_url('consumable_out/detail/' . (int)$doc->id) ?>" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-external-link-alt mr-1"></i> <?= lang_line('irfs_open_doc', 'Buka Dokumen'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ((int)$stats['doc_cancelled'] > 0): ?>
                            <div class="small text-muted mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                <?= sprintf(lang_line('irfs_cancelled_note', '%d dokumen berstatus Cancelled tidak dihitung sebagai released.'), (int)$stats['doc_cancelled']); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Kolom kanan: analisis, approval, aksi -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-stopwatch mr-1"></i> <?= lang_line('irfs_card_analysis', 'Analisis'); ?></strong>
                </div>
                <div class="card-body">
                    <?php
                    $day = lang_line('irfs_days', 'hari');
                    $rows = [
                        [lang_line('irfs_an_to_approve', 'Request → Approved'), $stats['days_to_approve'], 'hourglass-start'],
                        [lang_line('irfs_an_to_first',   'Approved → Release Pertama'), $stats['days_to_first'], 'truck-loading'],
                        [lang_line('irfs_an_to_complete','Approved → Release Terakhir'), $stats['days_to_complete'], 'flag-checkered'],
                        [lang_line('irfs_an_outstanding','Umur Outstanding Berjalan'), $stats['days_outstanding'], 'exclamation-triangle'],
                    ];
                    ?>
                    <ul class="list-unstyled mb-3">
                        <?php foreach ($rows as $r): ?>
                        <li class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small text-muted"><i class="fas fa-<?= $r[2] ?> mr-2"></i><?= $r[0] ?></span>
                            <span class="font-weight-bold">
                                <?= $r[1] === null ? '<span class="text-muted">-</span>' : (int)$r[1] . ' ' . $day ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <ul class="list-unstyled mb-0 small">
                        <li class="d-flex justify-content-between mb-1">
                            <span class="text-muted"><?= lang_line('irfs_an_first_issue', 'Release Pertama'); ?></span>
                            <span><?= $fmt_dt($stats['first_issue']) ?></span>
                        </li>
                        <li class="d-flex justify-content-between mb-1">
                            <span class="text-muted"><?= lang_line('irfs_an_last_issue', 'Release Terakhir'); ?></span>
                            <span><?= $fmt_dt($stats['last_issue']) ?></span>
                        </li>
                        <li class="d-flex justify-content-between mb-1">
                            <span class="text-muted"><?= lang_line('irfs_an_doc_total', 'Dokumen Pengeluaran'); ?></span>
                            <span>
                                <?= (int)$stats['doc_confirmed'] ?> <?= lang_line('irfs_an_confirmed', 'confirmed'); ?>
                                <?php if ((int)$stats['doc_inprocess'] > 0): ?>, <?= (int)$stats['doc_inprocess'] ?> <?= lang_line('irfs_an_open', 'proses'); ?><?php endif; ?>
                                <?php if ((int)$stats['doc_cancelled'] > 0): ?>, <?= (int)$stats['doc_cancelled'] ?> <?= lang_line('irfs_an_cancelled', 'batal'); ?><?php endif; ?>
                            </span>
                        </li>
                    </ul>

                    <?php if ($stats['qty_outstanding'] > 0): ?>
                        <div class="alert alert-warning small mt-3 mb-0">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            <?= sprintf(
                                lang_line('irfs_alert_outstanding', 'Masih ada <strong>%s</strong> qty outstanding pada %d baris item.'),
                                number_format($stats['qty_outstanding'], 2),
                                (int)$stats['lines_partial'] + (int)$stats['lines_untouched']
                            ); ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success small mt-3 mb-0">
                            <i class="fas fa-check-circle mr-1"></i>
                            <?= lang_line('irfs_alert_complete', 'Seluruh item Permintaan Barang ini sudah di-release penuh.'); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($logs)): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light">
                    <strong><i class="fas fa-history mr-1"></i> <?= lang_line('irfs_card_approval', 'Riwayat Approval'); ?></strong>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($logs as $log):
                            $m = $log_meta[$log->action] ?? ['label' => $log->action, 'icon' => 'circle', 'color' => 'secondary'];
                        ?>
                        <li class="d-flex mb-3">
                            <div class="mr-3 text-center" style="width:34px;">
                                <span class="badge badge-<?= $m['color']; ?> d-inline-flex align-items-center justify-content-center" style="width:34px;height:34px;border-radius:50%;">
                                    <i class="fas fa-<?= $m['icon']; ?>"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="font-weight-bold text-<?= $m['color']; ?>"><?= htmlspecialchars($m['label']); ?></div>
                                <div class="small text-gray-800"><?= htmlspecialchars($log->actor_name ?: '-'); ?></div>
                                <div class="small text-muted"><?= $fmt_dt($log->created_at); ?></div>
                                <?php if (!empty($log->notes)): ?>
                                    <div class="mt-1 px-2 py-1 small" style="background:#f8f9fc;border-left:3px solid #d1d3e2;border-radius:3px;color:#555;font-style:italic;word-break:break-word;">
                                        <i class="fas fa-comment-dots text-muted mr-1"></i><?= nl2br(htmlspecialchars($log->notes)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-link mr-1"></i> <?= lang_line('irfs_card_links', 'Tautan'); ?></strong></div>
                <div class="card-body">
                    <?php if (check_access('consumable_request', 'view')): ?>
                        <a href="<?= base_url('consumable_request/detail/' . (int)$header->id) ?>" class="btn btn-outline-primary btn-block mb-2">
                            <i class="fas fa-file-alt mr-1"></i> <?= lang_line('irfs_link_irf', 'Buka Dokumen Permintaan Barang'); ?>
                        </a>
                        <a href="<?= base_url('consumable_request/export_pdf/' . (int)$header->id) ?>" target="_blank" class="btn btn-outline-danger btn-block mb-2">
                            <i class="fas fa-file-pdf mr-1"></i> <?= lang_line('irfs_link_pdf', 'Export PDF Permintaan Barang'); ?>
                        </a>
                    <?php endif; ?>
                    <?php $co_cat_key = 'consumable_out_' . strtolower($header->category ?? 'General'); ?>
                    <?php if ($stats['qty_outstanding'] > 0 && $header->status === 'Approved' && (check_access($co_cat_key, 'add') || check_access('consumable_out', 'add'))): ?>
                        <a href="<?= base_url('consumable_out/add?request_id=' . (int)$header->id) ?>" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-arrow-right mr-1"></i> <?= lang_line('irfs_link_issue', 'Proses Pengeluaran'); ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?= base_url('irf_status') ?>" class="btn btn-secondary btn-block mb-0">
                        <i class="fas fa-arrow-left mr-1"></i> <?= lang_line('irfs_btn_back', 'Kembali'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
