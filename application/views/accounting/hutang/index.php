<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-file-invoice-dollar text-danger mr-2"></i><?= $title; ?></h1>
        <a href="<?= base_url('hutang/export_excel?' . http_build_query([
                'f'                => 1,
                'supplier_id'      => $filters['supplier_id'],
                'payment_status'   => $filters['payment_status'],
                'only_outstanding' => $filters['only_outstanding'] ? 1 : 0,
            ])); ?>" class="btn btn-success btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-file-excel"></i></span>
            <span class="text">Export Excel</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <!-- ===== Kartu Ringkasan ===== -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Tagihan (Posted)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($totals->total_invoice, 0); ?></div>
                    <div class="text-muted small"><?= $totals->inv_count; ?> faktur</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sudah Dibayar</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($totals->total_paid, 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Sisa Hutang (Belum Terbayar)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($totals->outstanding, 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">GRN Belum Difaktur</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($totals->uninvoiced_value, 0); ?></div>
                    <div class="text-muted small"><?= $totals->uninvoiced_count; ?> GRN diterima belum ditagih</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Filter ===== -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" action="<?= base_url('hutang'); ?>" class="form-row align-items-end">
                <input type="hidden" name="f" value="1">
                <div class="col-md-4 mb-2">
                    <label class="small font-weight-bold mb-1">Supplier</label>
                    <select name="supplier_id" class="form-control form-control-sm">
                        <option value="">-- Semua Supplier --</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s->id; ?>" <?= ($filters['supplier_id'] == $s->id) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($s->nama_supplier); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small font-weight-bold mb-1">Status Bayar</label>
                    <select name="payment_status" class="form-control form-control-sm">
                        <option value="">-- Semua --</option>
                        <?php foreach (['Unpaid', 'Partial', 'Paid'] as $st): ?>
                            <option value="<?= $st; ?>" <?= ($filters['payment_status'] == $st) ? 'selected' : ''; ?>><?= $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" class="custom-control-input" id="only_outstanding" name="only_outstanding" value="1" <?= $filters['only_outstanding'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="only_outstanding">Hanya yang belum lunas</label>
                    </div>
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== Rekap Per Supplier ===== -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Rekap Hutang Per Supplier</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>Supplier</th>
                            <th class="text-center">Jml Faktur</th>
                            <th class="text-right">Total Tagihan</th>
                            <th class="text-right">Dibayar</th>
                            <th class="text-right">Sisa Hutang</th>
                            <th class="text-center">GRN Blm Difaktur</th>
                            <th class="text-right">Nilai GRN Blm Difaktur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($summary)): ?>
                            <tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>
                        <?php else: foreach ($summary as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r->nama_supplier); ?></strong><br><small class="text-muted"><?= $r->kode_supplier; ?></small></td>
                                <td class="text-center"><?= $r->inv_count; ?></td>
                                <td class="text-right"><?= number_format($r->total_invoice, 0); ?></td>
                                <td class="text-right text-success"><?= number_format($r->total_paid, 0); ?></td>
                                <td class="text-right font-weight-bold text-danger"><?= number_format($r->outstanding, 0); ?></td>
                                <td class="text-center"><?= $r->uninvoiced_count ?: '-'; ?></td>
                                <td class="text-right"><?= $r->uninvoiced_value > 0 ? number_format($r->uninvoiced_value, 0) : '-'; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== Faktur Hutang + Aging ===== -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Daftar Faktur Hutang (Outstanding &amp; Aging)</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="dataTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>No. Faktur</th>
                            <th>Tgl / Jatuh Tempo</th>
                            <th>Supplier</th>
                            <th>PO / GRN</th>
                            <th class="text-right">Total Tagihan</th>
                            <th class="text-right">Dibayar</th>
                            <th class="text-right">Sisa Hutang</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Umur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr><td colspan="9" class="text-center text-muted">Tidak ada faktur hutang.</td></tr>
                        <?php else: foreach ($invoices as $i):
                            $is_open  = ($i->outstanding > 0.01);
                            $overdue  = ($is_open && $i->days_overdue > 0);
                            $pbadge   = $i->payment_status == 'Paid' ? 'success' : ($i->payment_status == 'Partial' ? 'warning' : 'danger');
                        ?>
                            <tr class="<?= $overdue ? 'table-danger' : ''; ?>">
                                <td>
                                    <a href="<?= base_url('apinvoice/view/' . $i->id); ?>"><strong><?= htmlspecialchars($i->invoice_no); ?></strong></a>
                                </td>
                                <td>
                                    <?= date('d-m-Y', strtotime($i->invoice_date)); ?><br>
                                    <small class="text-muted">JT: <?= date('d-m-Y', strtotime($i->due_date)); ?></small>
                                </td>
                                <td><?= htmlspecialchars($i->nama_supplier); ?></td>
                                <td><small><?= $i->po_numbers ? htmlspecialchars($i->po_numbers) : '-'; ?><br><span class="text-muted"><?= $i->grn_numbers ? htmlspecialchars($i->grn_numbers) : ''; ?></span></small></td>
                                <td class="text-right"><?= number_format($i->total_invoice, 0); ?></td>
                                <td class="text-right text-success"><?= number_format($i->total_paid, 0); ?></td>
                                <td class="text-right font-weight-bold <?= $is_open ? 'text-danger' : ''; ?>"><?= number_format($i->outstanding, 0); ?></td>
                                <td class="text-center"><span class="badge badge-<?= $pbadge; ?>"><?= $i->payment_status; ?></span></td>
                                <td class="text-center">
                                    <?php if (!$is_open): ?>
                                        <span class="badge badge-success">Lunas</span>
                                    <?php elseif ($overdue): ?>
                                        <span class="badge badge-danger">+<?= (int) $i->days_overdue; ?> hr</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?= (int) abs($i->days_overdue); ?> hr lagi</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== GRN diterima belum difaktur ===== -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-exclamation-triangle mr-1"></i> GRN Sudah Diterima Tapi Belum Difaktur (Hutang Belum Tercatat)</h6>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-2">PO yang barangnya sudah diterima (GRN Completed) namun belum dibuatkan Faktur Hutang. Buat faktur lewat menu <strong>Account Payable</strong>.</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>No. GRN</th>
                            <th>Tgl GRN</th>
                            <th>Supplier</th>
                            <th>No. PO</th>
                            <th class="text-right">Nilai GRN</th>
                            <th class="text-center">Umur (hari)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($uninvoiced_grn)): ?>
                            <tr><td colspan="6" class="text-center text-muted">Semua GRN sudah difaktur. 🎉</td></tr>
                        <?php else: foreach ($uninvoiced_grn as $g): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($g->grn_number); ?></strong></td>
                                <td><?= date('d-m-Y', strtotime($g->grn_date)); ?></td>
                                <td><?= htmlspecialchars($g->nama_supplier); ?></td>
                                <td><?= $g->po_numbers ? htmlspecialchars($g->po_numbers) : '-'; ?></td>
                                <td class="text-right"><?= number_format($g->grn_value, 0); ?></td>
                                <td class="text-center"><?= (int) $g->age_days; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
