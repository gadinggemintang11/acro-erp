<?php
/**
 * Jejak 1 barang general. Susunannya SENGAJA dibuat sama persis dengan
 * material_traceability/detail.php (kartu ringkasan yang mengarah ke tab +
 * tab per jenis dokumen) — user 2026-09-16: "harusnya kan sama dibuat seperti
 * itu". Yang berbeda hanya jenis dokumennya, karena barang general lewat
 * IRF → PR → PO → Penerimaan (SGR) → Pengeluaran, bukan MRF/GRN/Produksi.
 */
$fmt_qty = function ($v) { return number_format((float) $v, 2, ',', '.'); };
$fmt_int = function ($v) { return number_format((float) $v, 0, ',', '.'); };
$fmt_money = function ($v) { return 'Rp ' . number_format((float) $v, 0, ',', '.'); };
$status_badge = function ($s) {
    $map = [
        'Draft' => 'secondary', 'Submitted' => 'info', 'Approved Head Dept' => 'info', 'Endorsed' => 'info',
        'Approved' => 'success', 'Released' => 'success', 'Rejected' => 'danger', 'Cancelled' => 'danger',
        'Created' => 'secondary', 'Confirmed' => 'success', 'Completed' => 'success', 'Closed' => 'dark',
        'Issued' => 'primary', 'Received' => 'success', 'Pending' => 'secondary',
    ];
    $cls = $map[$s] ?? 'light';
    return '<span class="badge badge-' . $cls . '">' . htmlspecialchars($s ?? '-') . '</span>';
};
$stock = $summary['stock'];
?>
<div class="container-fluid">
    <nav aria-label="breadcrumb" class="mb-2">
        <ol class="breadcrumb bg-transparent p-0 small mb-0">
            <li class="breadcrumb-item"><a href="<?= site_url('inventory_traceability'); ?>"><i class="fas fa-route mr-1"></i><?= lang_line('it_title', 'Penelusuran Barang General'); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($item->kode_barang); ?></li>
        </ol>
    </nav>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap">
                <div>
                    <h4 class="mb-1"><i class="fas fa-box text-primary mr-2"></i><?= htmlspecialchars($item->nama_barang); ?></h4>
                    <div class="text-muted small">
                        <code class="text-primary"><?= htmlspecialchars($item->kode_barang); ?></code>
                        &nbsp;&middot;&nbsp; <?= htmlspecialchars($item->kategori ?? '-'); ?>
                        &nbsp;&middot;&nbsp; <?= lang_line('it_lbl_kind', 'Jenis'); ?>: <?= htmlspecialchars($item->item_type ?? '-'); ?>
                        &nbsp;&middot;&nbsp; <?= lang_line('it_lbl_unit', 'Satuan'); ?>: <strong><?= htmlspecialchars($item->satuan ?? '-'); ?></strong>
                        <?php if (!empty($item->merk)): ?>&nbsp;&middot;&nbsp; <?= lang_line('it_lbl_brand', 'Merk'); ?>: <strong><?= htmlspecialchars($item->merk); ?></strong><?php endif; ?>
                        &nbsp;&middot;&nbsp; <?= lang_line('it_th_status', 'Status'); ?>: <?= $status_badge($item->status === 'Active' ? 'Approved' : 'Draft') === '' ? '' : '<span class="badge badge-' . ($item->status === 'Active' ? 'success' : 'secondary') . '">' . htmlspecialchars($item->status ?? '-') . '</span>'; ?>
                    </div>
                </div>
                <a href="<?= site_url('inventory_traceability'); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> <?= lang_line('it_back', 'Kembali'); ?></a>
            </div>
        </div>
    </div>

    <!-- Kartu ringkasan: klik untuk lompat ke tab-nya -->
    <div class="row mb-3">
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="card border-left-info shadow-sm h-100">
                <div class="card-body py-2 px-3">
                    <div class="text-info text-uppercase small font-weight-bold"><?= lang_line('it_stock_on_hand', 'Stok Sekarang'); ?></div>
                    <div class="h5 mb-0 font-weight-bold"><?= $fmt_qty($stock['total']); ?></div>
                    <div class="small text-muted">
                        <?php if (empty($stock['per_category'])): ?>
                            <?= lang_line('it_no_stock_movement', 'belum ada mutasi'); ?>
                        <?php else: ?>
                            <?php $parts = []; foreach ($stock['per_category'] as $c) { $parts[] = htmlspecialchars($c->category ?: '-') . ' ' . $fmt_qty($c->balance); } echo implode(' &middot; ', $parts); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-irf" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-secondary shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-secondary text-uppercase small font-weight-bold"><?= lang_line('it_summary_irf', 'Permintaan (Permintaan Barang)'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['irf']->docs); ?> <small class="text-muted"><?= lang_line('it_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= lang_line('it_qty_label', 'Qty'); ?>: <?= $fmt_qty($summary['irf']->qty); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-pr" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-primary shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-primary text-uppercase small font-weight-bold"><?= lang_line('it_summary_pr', 'Purchase Request'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['pr']->docs); ?> <small class="text-muted"><?= lang_line('it_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= lang_line('it_qty_label', 'Qty'); ?>: <?= $fmt_qty($summary['pr']->qty); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-po" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-success shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-success text-uppercase small font-weight-bold"><?= lang_line('it_summary_po', 'Purchase Order'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['po']->docs); ?> <small class="text-muted"><?= lang_line('it_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= $fmt_money($summary['po']->value); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-sgr" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-warning shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-warning text-uppercase small font-weight-bold"><?= lang_line('it_summary_sgr', 'Penerimaan'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['sgr']->docs); ?> <small class="text-muted"><?= lang_line('it_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= lang_line('it_qty_received', 'Qty diterima'); ?>: <?= $fmt_qty($summary['sgr']->qty); ?></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <a href="#tab-out" data-toggle="tab" class="text-decoration-none">
                <div class="card border-left-danger shadow-sm h-100">
                    <div class="card-body py-2 px-3">
                        <div class="text-danger text-uppercase small font-weight-bold"><?= lang_line('it_summary_out', 'Pengeluaran'); ?></div>
                        <div class="h5 mb-0 font-weight-bold"><?= $fmt_int($summary['out']->docs); ?> <small class="text-muted"><?= lang_line('it_doc_count', 'dok'); ?></small></div>
                        <div class="small text-muted"><?= lang_line('it_qty_issued', 'Dikeluarkan'); ?>: <?= $fmt_qty($summary['out']->qty); ?></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white p-0">
            <ul class="nav nav-tabs nav-justified" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-irf"><i class="fas fa-file-alt mr-1"></i> <?= lang_line('it_tab_irf', 'Permintaan Barang'); ?> <span class="badge badge-light"><?= count($irfs); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-pr"><i class="fas fa-clipboard-list mr-1"></i> <?= lang_line('it_tab_pr', 'PR'); ?> <span class="badge badge-light"><?= count($prs); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-po"><i class="fas fa-shopping-cart mr-1"></i> <?= lang_line('it_tab_po', 'PO'); ?> <span class="badge badge-light"><?= count($pos); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-sgr"><i class="fas fa-truck-loading mr-1"></i> <?= lang_line('it_tab_sgr', 'Penerimaan'); ?> <span class="badge badge-light"><?= count($sgrs); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-out"><i class="fas fa-dolly mr-1"></i> <?= lang_line('it_tab_out', 'Pengeluaran'); ?> <span class="badge badge-light"><?= count($outs); ?></span></a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-ledger"><i class="fas fa-book mr-1"></i> <?= lang_line('it_tab_ledger', 'Kartu Stok'); ?> <span class="badge badge-light"><?= count($ledger); ?></span></a></li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">

                <!-- Permintaan Barang -->
                <div class="tab-pane fade show active" id="tab-irf">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th>#</th><th><?= lang_line('it_th_irf_number', 'No. Permintaan Barang'); ?></th><th><?= lang_line('it_th_date', 'Tanggal'); ?></th><th><?= lang_line('it_th_dept', 'Departemen'); ?></th><th><?= lang_line('it_th_category', 'Kategori'); ?></th><th><?= lang_line('it_th_requestor', 'Pemohon'); ?></th><th><?= lang_line('it_qty_label', 'Qty'); ?></th><th><?= lang_line('it_th_status', 'Status'); ?></th><th><?= lang_line('it_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($irfs)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('it_no_irf', 'Barang ini belum pernah diminta lewat Permintaan Barang.'); ?></td></tr>
                            <?php else: foreach ($irfs as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i + 1; ?></td>
                                    <td><code><?= htmlspecialchars($r->request_number); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->request_date); ?></td>
                                    <td><?= htmlspecialchars($r->department ?? '-'); ?></td>
                                    <td class="text-center"><span class="badge badge-light"><?= htmlspecialchars($r->category ?? '-'); ?></span></td>
                                    <td><?= htmlspecialchars($r->requestor_name ?? '-'); ?></td>
                                    <td class="text-right"><?= $fmt_qty($r->qty_requested); ?></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('inventory_traceability/doc/irf/' . $r->header_id . '/' . $item->id); ?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PR -->
                <div class="tab-pane fade" id="tab-pr">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th>#</th><th><?= lang_line('it_th_pr_number', 'No. PR'); ?></th><th><?= lang_line('it_th_date', 'Tanggal'); ?></th><th><?= lang_line('it_th_dept', 'Departemen'); ?></th><th><?= lang_line('it_th_type', 'Tipe'); ?></th><th><?= lang_line('it_th_requestor', 'Pemohon'); ?></th><th><?= lang_line('it_qty_label', 'Qty'); ?></th><th><?= lang_line('it_th_status', 'Status'); ?></th><th><?= lang_line('it_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($prs)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('it_no_pr', 'Belum pernah masuk PR.'); ?></td></tr>
                            <?php else: foreach ($prs as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i + 1; ?></td>
                                    <td><code><?= htmlspecialchars($r->pr_number); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->pr_date); ?></td>
                                    <td><?= htmlspecialchars($r->department ?? '-'); ?></td>
                                    <td class="text-center"><span class="badge badge-light"><?= htmlspecialchars($r->type ?? '-'); ?></span></td>
                                    <td><?= htmlspecialchars($r->requester_name ?? '-'); ?></td>
                                    <td class="text-right"><?= $fmt_qty($r->qty); ?></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('inventory_traceability/doc/pr/' . $r->header_id . '/' . $item->id); ?>"><i class="fas fa-eye"></i></a></td>
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
                                <th>#</th><th><?= lang_line('it_th_po_number', 'No. PO'); ?></th><th><?= lang_line('it_th_date', 'Tanggal'); ?></th><th><?= lang_line('it_th_supplier', 'Supplier'); ?></th><th><?= lang_line('it_qty_label', 'Qty'); ?></th><th><?= lang_line('it_th_unit_price', 'Harga Satuan'); ?></th><th><?= lang_line('it_th_total', 'Total'); ?></th><th><?= lang_line('it_th_status', 'Status'); ?></th><th><?= lang_line('it_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($pos)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('it_no_po', 'Belum pernah dibeli lewat PO.'); ?></td></tr>
                            <?php else: foreach ($pos as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i + 1; ?></td>
                                    <td><code><?= htmlspecialchars($r->po_number); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->po_date); ?></td>
                                    <td><?= htmlspecialchars($r->supplier_name ?? '-'); ?></td>
                                    <td class="text-right"><?= $fmt_qty($r->qty); ?></td>
                                    <td class="text-right"><?= $fmt_money($r->local_price); ?></td>
                                    <td class="text-right"><strong><?= $fmt_money($r->total_local); ?></strong></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('inventory_traceability/doc/po/' . $r->header_id . '/' . $item->id); ?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Penerimaan (SGR) -->
                <div class="tab-pane fade" id="tab-sgr">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th>#</th><th><?= lang_line('it_th_sgr_number', 'No. Penerimaan'); ?></th><th><?= lang_line('it_th_date', 'Tanggal'); ?></th><th><?= lang_line('it_th_source', 'Sumber'); ?></th><th><?= lang_line('it_th_po_ref', 'Ref. PO'); ?></th><th><?= lang_line('it_th_supplier', 'Supplier'); ?></th><th><?= lang_line('it_th_qty_received', 'Qty Diterima'); ?></th><th><?= lang_line('it_th_value', 'Nilai'); ?></th><th><?= lang_line('it_th_status', 'Status'); ?></th><th><?= lang_line('it_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($sgrs)): ?>
                                <tr><td colspan="10" class="text-center text-muted py-3"><?= lang_line('it_no_sgr', 'Belum pernah diterima gudang.'); ?></td></tr>
                            <?php else: foreach ($sgrs as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i + 1; ?></td>
                                    <td><code><?= htmlspecialchars($r->sgr_number); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->sgr_date); ?></td>
                                    <td class="text-center"><span class="badge badge-secondary"><?= htmlspecialchars($r->source_doc_type ?: ($r->base_on ?? '-')); ?></span></td>
                                    <td><?= htmlspecialchars($r->po_number ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($r->supplier_name ?? '-'); ?></td>
                                    <td class="text-right"><?= $fmt_qty($r->receive_qty); ?></td>
                                    <td class="text-right"><?= $fmt_money($r->total_value); ?></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('inventory_traceability/doc/sgr/' . $r->header_id . '/' . $item->id); ?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pengeluaran -->
                <div class="tab-pane fade" id="tab-out">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small"><tr>
                                <th>#</th><th><?= lang_line('it_th_out_number', 'No. Pengeluaran'); ?></th><th><?= lang_line('it_th_date', 'Tanggal'); ?></th><th><?= lang_line('it_th_irf_src', 'Dari Permintaan Barang'); ?></th><th><?= lang_line('it_th_dept', 'Departemen'); ?></th><th><?= lang_line('it_th_recipient', 'Penerima'); ?></th><th><?= lang_line('it_th_qty_issued', 'Qty Keluar'); ?></th><th><?= lang_line('it_th_status', 'Status'); ?></th><th><?= lang_line('it_th_action', 'Aksi'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($outs)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('it_no_out', 'Belum pernah dikeluarkan gudang.'); ?></td></tr>
                            <?php else: foreach ($outs as $i => $r): ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i + 1; ?></td>
                                    <td><code><?= htmlspecialchars($r->doc_number); ?></code></td>
                                    <td class="text-center"><?= htmlspecialchars($r->issuance_date); ?></td>
                                    <td><?= htmlspecialchars($r->request_number ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($r->department ?? '-'); ?></td>
                                    <td><?= htmlspecialchars($r->recipient ?? '-'); ?></td>
                                    <td class="text-right"><?= $fmt_qty($r->qty_issued); ?></td>
                                    <td class="text-center"><?= $status_badge($r->status); ?></td>
                                    <td class="text-center"><a class="btn btn-xs btn-outline-primary" href="<?= site_url('inventory_traceability/doc/out/' . $r->header_id . '/' . $item->id); ?>"><i class="fas fa-eye"></i></a></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kartu Stok -->
                <div class="tab-pane fade" id="tab-ledger">
                    <div class="table-responsive" style="max-height: 600px;">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="thead-light text-center small sticky-top bg-light"><tr>
                                <th>#</th><th><?= lang_line('it_th_date', 'Tanggal'); ?></th><th><?= lang_line('it_th_tx_type', 'Tipe Transaksi'); ?></th><th><?= lang_line('it_th_warehouse', 'Gudang'); ?></th><th><?= lang_line('it_th_source_doc', 'Dokumen'); ?></th><th><?= lang_line('it_th_qty_in', 'Qty Masuk'); ?></th><th><?= lang_line('it_th_qty_out', 'Qty Keluar'); ?></th><th><?= lang_line('it_th_unit_value', 'Harga Satuan'); ?></th><th><?= lang_line('it_th_balance', 'Saldo'); ?></th>
                            </tr></thead>
                            <tbody class="small">
                            <?php if (empty($ledger)): ?>
                                <tr><td colspan="9" class="text-center text-muted py-3"><?= lang_line('it_no_ledger', 'Belum ada pergerakan stok untuk barang ini.'); ?></td></tr>
                            <?php else: foreach ($ledger as $i => $r):
                                $tx = $r->transaction_type;
                                $tx_cls = 'secondary';
                                if (stripos($tx, 'in') !== false && stripos($tx, 'initial') === false) $tx_cls = 'success';
                                elseif (stripos($tx, 'out') !== false) $tx_cls = 'danger';
                                elseif (stripos($tx, 'initial') !== false) $tx_cls = 'info';
                                elseif (stripos($tx, 'adj') !== false) $tx_cls = 'warning';
                            ?>
                                <tr>
                                    <td class="text-center text-muted"><?= $i + 1; ?></td>
                                    <td class="text-center"><?= htmlspecialchars($r->transaction_date); ?></td>
                                    <td class="text-center"><span class="badge badge-<?= $tx_cls; ?>"><?= htmlspecialchars($tx); ?></span></td>
                                    <td class="text-center"><?= htmlspecialchars($r->category ?: '-'); ?></td>
                                    <td class="text-center"><small><?= htmlspecialchars($r->source_doc_type ?? '-'); ?> <?= $r->source_doc_id ? '#' . (int) $r->source_doc_id : ''; ?></small></td>
                                    <td class="text-right text-success"><?= (float) $r->qty_in > 0 ? '+' . $fmt_qty($r->qty_in) : ''; ?></td>
                                    <td class="text-right text-danger"><?= (float) $r->qty_out > 0 ? '-' . $fmt_qty($r->qty_out) : ''; ?></td>
                                    <td class="text-right"><?= $fmt_money($r->unit_value); ?></td>
                                    <td class="text-right"><strong><?= $fmt_qty($r->balance); ?></strong></td>
                                </tr>
                            <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-muted"><?= lang_line('it_ledger_note', '* Menampilkan hingga 300 transaksi terbaru.'); ?></small>
                </div>

            </div>
        </div>
    </div>
</div>
