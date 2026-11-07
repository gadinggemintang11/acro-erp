<?php
$fmt_qty = function($v){ return number_format((float)$v, 2, ',', '.'); };
$fmt_int = function($v){ return number_format((float)$v, 0, ',', '.'); };
$fmt_money = function($v){ return 'Rp ' . number_format((float)$v, 0, ',', '.'); };
$status_badge = function($s){
    $map = [
        'Draft'=>'secondary','Submitted'=>'info','Approved'=>'success','Rejected'=>'danger',
        'Confirmed'=>'success','Closed'=>'dark','Cancelled'=>'danger',
        'Issued'=>'primary','Received'=>'success',
        'In Progress'=>'warning','QC Check'=>'info','Completed'=>'success','Pending'=>'secondary',
    ];
    $cls = $map[$s] ?? 'light';
    return '<span class="badge badge-'.$cls.'">'.htmlspecialchars($s ?? '-').'</span>';
};
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb bg-transparent p-0 small mb-0">
            <li class="breadcrumb-item"><a href="<?= site_url('material_traceability'); ?>"><i class="fas fa-route mr-1"></i><?= lang_line('mt_title', 'Material Traceability'); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($material->kode_material); ?></li>
        </ol>
    </nav>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <h4 class="mb-1"><i class="fas fa-cube text-primary mr-2"></i><?= htmlspecialchars($material->nama_material); ?></h4>
                    <div class="text-muted small">
                        <code class="text-primary"><?= htmlspecialchars($material->kode_material); ?></code>
                        &nbsp;&middot;&nbsp; <?= htmlspecialchars($material->kategori ?? '-'); ?>
                        &nbsp;&middot;&nbsp; <?= htmlspecialchars($material->kategorirm ?? '-'); ?>
                        &nbsp;&middot;&nbsp; <?= lang_line('mt_lbl_unit', 'Satuan'); ?>: <strong><?= htmlspecialchars($material->satuan ?? '-'); ?></strong>
                        &nbsp;&middot;&nbsp; <?= lang_line('mt_lbl_origin', 'Asal'); ?>: <strong><?= htmlspecialchars($material->asal ?? '-'); ?></strong>
                    </div>
                </div>
                <a href="<?= site_url('material_traceability'); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> <?= lang_line('mt_back', 'Kembali'); ?></a>
            </div>
        </div>
    </div>

    <!-- Summary cards -->
    <div class="row mb-3">
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="card border-left-info shadow-sm h-100">
                <div class="card-body py-2 px-3">
                    <div class="text-info text-uppercase small font-weight-bold"><?= lang_line('mt_stock_on_hand', 'Stock On Hand'); ?></div>
                    <div class="h5 mb-0 font-weight-bold"><?= $fmt_qty($summary['stock']->on_hand ?? 0); ?></div>
                    <div class="small text-muted"><?= htmlspecialchars($material->satuan ?? ''); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-pr" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-primary shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-primary text-uppercase small font-weight-bold"><?= lang_line('mt_summary_pr', 'Purchase Request'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['pr']->docs); ?> <small class="text-muted"><?= lang_line('mt_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= lang_line('mt_qty_label', 'Qty'); ?>: <?= $fmt_qty($summary['pr']->qty); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-po" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-success shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-success text-uppercase small font-weight-bold"><?= lang_line('mt_summary_po', 'Purchase Order'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['po']->docs); ?> <small class="text-muted"><?= lang_line('mt_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= $fmt_money($summary['po']->value); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-grn" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-warning shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-warning text-uppercase small font-weight-bold"><?= lang_line('mt_summary_grn', 'Goods Receipt'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['grn']->docs); ?> <small class="text-muted"><?= lang_line('mt_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= lang_line('mt_qty_received', 'Qty diterima'); ?>: <?= $fmt_qty($summary['grn']->qty); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-mr" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-danger shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-danger text-uppercase small font-weight-bold"><?= lang_line('mt_summary_irf', 'Permintaan Barang'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['mr']->docs); ?> <small class="text-muted"><?= lang_line('mt_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= lang_line('mt_qty_issued', 'Issued'); ?>: <?= $fmt_qty($summary['mr']->qty_issued); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-prod" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-dark shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-dark text-uppercase small font-weight-bold"><?= lang_line('mt_summary_production', 'Production'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['prod']->docs); ?> <small class="text-muted"><?= lang_line('mt_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= lang_line('mt_qty_used', 'Qty dipakai'); ?>: <?= $fmt_qty($summary['prod']->qty); ?></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs nav-justified" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-pr"><i class="fas fa-clipboard-list mr-1"></i> <?= lang_line('mt_tab_pr', 'PR'); ?> <span class="badge badge-light"><?= count($prs); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-po"><i class="fas fa-shopping-cart mr-1"></i> <?= lang_line('mt_tab_po', 'PO'); ?> <span class="badge badge-light"><?= count($pos); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-grn"><i class="fas fa-truck-loading mr-1"></i> <?= lang_line('mt_tab_grn', 'GRN'); ?> <span class="badge badge-light"><?= count($grns); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-mr"><i class="fas fa-dolly mr-1"></i> <?= lang_line('mt_tab_irf', 'Permintaan Barang'); ?> <span class="badge badge-light"><?= count($mrs); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-prod"><i class="fas fa-industry mr-1"></i> <?= lang_line('mt_tab_prod', 'Production'); ?> <span class="badge badge-light"><?= count($prods); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-ledger"><i class="fas fa-book mr-1"></i> <?= lang_line('mt_tab_ledger', 'Stock Ledger'); ?> <span class="badge badge-light"><?= count($ledger); ?></span></a></li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">

                <!-- PR -->
                <div class="tab-pane fade show active" id="tab-pr">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th>#</th><th><?= lang_line('mt_th_pr_number', 'PR Number'); ?></th><th><?= lang_line('mt_th_date', 'Tanggal'); ?></th><th><?= lang_line('mt_th_department', 'Department'); ?></th><th><?= lang_line('mt_th_purpose', 'Purpose'); ?></th><th><?= lang_line('mt_th_requester', 'Requester'); ?></th><th><?= lang_line('mt_qty_label', 'Qty'); ?></th><th><?= lang_line('mt_th_status', 'Status'); ?></th><th><?= lang_line('mt_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($prs)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('mt_no_pr', 'Belum ada PR untuk material ini.'); ?></td></tr>
                            <?php else: foreach ($prs as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i+1; ?></td>
                                    <td><code><?= htmlspecialchars($r->pr_number); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->pr_date); ?></td>
                                    <td><?= htmlspecialchars($r->department ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($r->purpose ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($r->requester_name ?? '-'); ?></td>
                                    <td class="text-right"><?= $fmt_qty($r->qty); ?></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('material_traceability/doc/pr/'.$r->header_id.'/'.$material->id); ?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PO -->
                <div class="tab-pane fade" id="tab-po">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th>#</th><th><?= lang_line('mt_th_po_number', 'PO Number'); ?></th><th><?= lang_line('mt_th_date', 'Tanggal'); ?></th><th><?= lang_line('mt_th_supplier', 'Supplier'); ?></th><th><?= lang_line('mt_qty_label', 'Qty'); ?></th><th><?= lang_line('mt_th_unit_price', 'Unit Price'); ?></th><th><?= lang_line('mt_th_total', 'Total'); ?></th><th><?= lang_line('mt_th_status', 'Status'); ?></th><th><?= lang_line('mt_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($pos)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('mt_no_po', 'Belum ada PO untuk material ini.'); ?></td></tr>
                            <?php else: foreach ($pos as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i+1; ?></td>
                                    <td><code><?= htmlspecialchars($r->po_number); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->po_date); ?></td>
                                    <td><?= htmlspecialchars($r->supplier_name ?? '-'); ?></td>
                                    <td class="text-right"><?= $fmt_qty($r->qty); ?></td>
                                    <td class="text-right"><?= $fmt_money($r->local_price); ?></td>
                                    <td class="text-right"><strong><?= $fmt_money($r->total_local); ?></strong></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('material_traceability/doc/po/'.$r->header_id.'/'.$material->id); ?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- GRN -->
                <div class="tab-pane fade" id="tab-grn">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th>#</th><th><?= lang_line('mt_th_grn_number', 'GRN Number'); ?></th><th><?= lang_line('mt_th_date', 'Tanggal'); ?></th><th><?= lang_line('mt_th_grn_source', 'Source'); ?></th><th><?= lang_line('mt_th_po_ref', 'PO Ref'); ?></th><th><?= lang_line('mt_th_supplier', 'Supplier'); ?></th><th><?= lang_line('mt_th_qty_received', 'Qty Diterima'); ?></th><th><?= lang_line('mt_th_batch', 'Batch ID'); ?></th><th><?= lang_line('mt_th_batch_remaining', 'Sisa Batch'); ?></th><th><?= lang_line('mt_th_status', 'Status'); ?></th><th><?= lang_line('mt_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($grns)): ?>
                                <tr><td colspan="11" class="text-center text-muted py-3"><?= lang_line('mt_no_grn', 'Belum ada GRN untuk material ini.'); ?></td></tr>
                            <?php else: foreach ($grns as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i+1; ?></td>
                                    <td><code><?= htmlspecialchars($r->grn_number); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->grn_date); ?></td>
                                    <td class="text-center"><span class="badge badge-secondary"><?= htmlspecialchars($r->source_doc_type ?? '-'); ?></span></td>
                                    <td><?= htmlspecialchars($r->po_number ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($r->supplier_name ?? '-'); ?></td>
                                    <td class="text-right"><?= $fmt_qty($r->receive_qty); ?></td>
                                    <td class="text-center"><?php if ($r->stock_batch_id): ?><code>#<?= (int)$r->stock_batch_id; ?></code><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                                    <td class="text-right"><?= $r->stock_batch_id ? $fmt_qty($r->batch_remaining) : '-'; ?></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('material_traceability/doc/grn/'.$r->header_id.'/'.$material->id); ?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Permintaan Barang -->
                <div class="tab-pane fade" id="tab-mr">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th>#</th><th><?= lang_line('mt_th_irf_number', 'Nomor Permintaan Barang'); ?></th><th><?= lang_line('mt_th_issuance_number', 'Issuance #'); ?></th><th><?= lang_line('mt_th_date', 'Tanggal'); ?></th><th><?= lang_line('mt_th_requester', 'Requester'); ?></th><th><?= lang_line('mt_th_wo', 'WO'); ?></th><th><?= lang_line('mt_th_qty_requested', 'Qty Diminta'); ?></th><th><?= lang_line('mt_th_qty_issued', 'Qty Issued'); ?></th><th><?= lang_line('mt_th_status', 'Status'); ?></th><th><?= lang_line('mt_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($mrs)): ?>
                                <tr><td colspan="10" class="text-center text-muted py-3"><?= lang_line('mt_no_irf', 'Belum ada Permintaan Barang untuk material ini.'); ?></td></tr>
                            <?php else: foreach ($mrs as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i+1; ?></td>
                                    <td><code><?= htmlspecialchars($r->mr_number); ?></code></td>
                                    <td><code><?= htmlspecialchars($r->issuance_number ?? '-'); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->request_date); ?></td>
                                    <td><?= htmlspecialchars($r->requester_name ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($r->wo_number ?? '-'); ?></td>
                                    <td class="text-right"><?= $fmt_qty($r->requested_qty); ?></td>
                                    <td class="text-right"><strong><?= $fmt_qty($r->issued_qty); ?></strong></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('material_traceability/doc/mr/'.$r->header_id.'/'.$material->id); ?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Production -->
                <div class="tab-pane fade" id="tab-prod">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th>#</th><th><?= lang_line('mt_th_prod_number', 'Production #'); ?></th><th><?= lang_line('mt_th_date', 'Tanggal'); ?></th><th><?= lang_line('mt_th_wo', 'WO'); ?></th><th><?= lang_line('mt_th_target_fg', 'Target FG'); ?></th><th><?= lang_line('mt_th_planned', 'Planned'); ?></th><th><?= lang_line('mt_th_qty_used', 'Qty Material Dipakai'); ?></th><th><?= lang_line('mt_th_status', 'Status'); ?></th><th><?= lang_line('mt_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($prods)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('mt_no_prod', 'Material ini belum pernah dipakai di produksi.'); ?></td></tr>
                            <?php else: foreach ($prods as $i => $r): ?>
                                <tr class="bg-light">
                                    <td class="text-center text-muted"><?= $i+1; ?></td>
                                    <td><code><?= htmlspecialchars($r->production_number); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->production_date); ?></td>
                                    <td><?= htmlspecialchars($r->wo_number ?? '-'); ?></td>
                                    <td><strong><?= htmlspecialchars($r->fg_code ?? '-'); ?></strong> &mdash; <?= htmlspecialchars($r->fg_name ?? ''); ?></td>
                                    <td class="text-right"><?= $fmt_int($r->planned_qty); ?></td>
                                    <td class="text-right"><strong><?= $fmt_qty($r->qty_used); ?></strong></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('material_traceability/doc/prod/'.$r->header_id.'/'.$material->id); ?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                                <?php if (!empty($r->outputs)): ?>
                                <tr>
                                    <td></td>
                                    <td colspan="8" class="pb-3">
                                        <small class="text-muted font-weight-bold mb-1 d-block"><i class="fas fa-level-up-alt fa-rotate-90 mr-1"></i><?= lang_line('mt_outputs_produced', 'Output yang dihasilkan'); ?>:</small>
                                        <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                            <?php foreach ($r->outputs as $out):
                                                $iconCls = 'fa-cube';
                                                $colorCls = 'secondary';
                                                if ($out->item_type === 'finished_goods') { $iconCls = 'fa-box'; $colorCls = 'success'; }
                                                elseif ($out->item_type === 'semifinished') { $iconCls = 'fa-puzzle-piece'; $colorCls = 'info'; }
                                                elseif ($out->item_type === 'scrap')        { $iconCls = 'fa-recycle'; $colorCls = 'warning'; }
                                                $qty_show = ($out->item_type === 'scrap') ? $out->qty_scrap : $out->qty_produced;
                                            ?>
                                                <span class="badge badge-<?= $colorCls; ?> p-2">
                                                    <i class="fas <?= $iconCls; ?> mr-1"></i>
                                                    <?= htmlspecialchars($out->stage_name); ?> &middot;
                                                    <strong><?= htmlspecialchars($out->item_code ?? '-'); ?></strong>
                                                    <?= htmlspecialchars($out->item_name ?? ''); ?>
                                                    &middot; <?= $fmt_qty($qty_show); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Stock Ledger -->
                <div class="tab-pane fade" id="tab-ledger">
                    <div class="table-responsive" style="max-height: 600px;">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small sticky-top bg-light"><tr>
                                <th>#</th><th><?= lang_line('mt_th_date', 'Tanggal'); ?></th><th><?= lang_line('mt_th_tx_type', 'Tipe Transaksi'); ?></th><th><?= lang_line('mt_th_source_doc', 'Source Doc'); ?></th><th><?= lang_line('mt_th_batch_short', 'Batch'); ?></th><th><?= lang_line('mt_th_qty_in', 'Qty In'); ?></th><th><?= lang_line('mt_th_qty_out', 'Qty Out'); ?></th><th><?= lang_line('mt_th_unit_value', 'Unit Value'); ?></th><th><?= lang_line('mt_th_balance', 'Balance'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($ledger)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('mt_no_ledger', 'Belum ada pergerakan stok untuk material ini.'); ?></td></tr>
                            <?php else: foreach ($ledger as $i => $r):
                                $tx = $r->transaction_type;
                                $tx_cls = 'secondary';
                                if (stripos($tx,'in') !== false && stripos($tx,'wip_in') === false) $tx_cls = 'success';
                                elseif (stripos($tx,'out') !== false) $tx_cls = 'danger';
                                elseif (stripos($tx,'wip') !== false) $tx_cls = 'info';
                                elseif (stripos($tx,'adjust') !== false) $tx_cls = 'warning';
                            ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i+1; ?></td>
                                    <td class="text-center"><?= htmlspecialchars($r->transaction_date); ?></td>
                                    <td class="text-center"><span class="badge badge-<?= $tx_cls; ?>"><?= htmlspecialchars($tx); ?></span></td>
                                    <td class="text-center"><small><?= htmlspecialchars($r->source_doc_type ?? '-'); ?> <?= $r->source_doc_id ? '#'.(int)$r->source_doc_id : ''; ?></small></td>
                                    <td class="text-center"><?= $r->stock_on_hand_id ? '<code>#'.(int)$r->stock_on_hand_id.'</code>' : '-'; ?></td>
                                    <td class="text-right text-success"><?= $r->qty_in > 0 ? '+'.$fmt_qty($r->qty_in) : ''; ?></td>
                                    <td class="text-right text-danger"><?= $r->qty_out > 0 ? '-'.$fmt_qty($r->qty_out) : ''; ?></td>
                                    <td class="text-right"><?= $fmt_money($r->unit_value); ?></td>
                                    <td class="text-right"><strong><?= $fmt_qty($r->balance); ?></strong></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted"><?= lang_line('mt_ledger_note', '* Menampilkan hingga 300 transaksi terbaru.'); ?></small>
                </div>

            </div>
        </div>
    </div>
</div>
