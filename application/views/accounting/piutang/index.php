<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-hand-holding-usd text-success mr-2"></i><?= $title; ?></h1>
        <a href="<?= base_url('piutang/export_excel?' . http_build_query([
                'f'                => 1,
                'customer_id'      => $filters['customer_id'],
                'payment_status'   => $filters['payment_status'],
                'only_outstanding' => $filters['only_outstanding'] ? 1 : 0,
            ])); ?>" class="btn btn-success btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-file-excel"></i></span>
            <span class="text">Export Excel</span>
        </a>
    </div>

    <?php $this->load->view('templates/partials/alerts'); ?>

    <!-- ===== Kartu Ringkasan (IDR) ===== -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Tagihan (Posted, IDR)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($totals->total_invoice_local, 0); ?></div>
                    <div class="text-muted small"><?= $totals->inv_count; ?> faktur</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Sudah Diterima (IDR)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($totals->total_paid_local, 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Sisa Piutang (Belum Terbayar)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($totals->outstanding_local, 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pengiriman Belum Difaktur</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totals->uninvoiced_value, 0); ?></div>
                    <div class="text-muted small"><?= $totals->uninvoiced_count; ?> dokumen dikirim belum ditagih</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== Filter ===== -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" action="<?= base_url('piutang'); ?>" class="form-row align-items-end">
                <input type="hidden" name="f" value="1">
                <div class="col-md-4 mb-2">
                    <label class="small font-weight-bold mb-1">Customer</label>
                    <select name="customer_id" class="form-control form-control-sm">
                        <option value="">-- Semua Customer --</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c->id; ?>" <?= ($filters['customer_id'] == $c->id) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($c->nama_customer); ?>
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

    <!-- ===== Rekap Per Customer ===== -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Rekap Piutang Per Customer (IDR)</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>Customer</th>
                            <th class="text-center">Jml Faktur</th>
                            <th class="text-right">Total Tagihan (IDR)</th>
                            <th class="text-right">Sisa Piutang (IDR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($summary)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>
                        <?php else: foreach ($summary as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r->nama_customer); ?></strong><br><small class="text-muted"><?= $r->kode_customer; ?></small></td>
                                <td class="text-center"><?= $r->inv_count; ?></td>
                                <td class="text-right"><?= number_format($r->total_invoice_local, 0); ?></td>
                                <td class="text-right font-weight-bold text-danger"><?= number_format($r->outstanding_local, 0); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===== Faktur Piutang + Aging ===== -->
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Daftar Faktur Piutang (Outstanding &amp; Aging)</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm" id="dataTable" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>No. Faktur</th>
                            <th>Tgl / Jatuh Tempo</th>
                            <th>Customer</th>
                            <th>Sumber</th>
                            <th class="text-right">Total Tagihan</th>
                            <th class="text-right">Diterima</th>
                            <th class="text-right">Sisa Piutang</th>
                            <th class="text-right">Sisa (IDR)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Umur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr><td colspan="10" class="text-center text-muted">Tidak ada faktur piutang.</td></tr>
                        <?php else: foreach ($invoices as $i):
                            $is_open = ($i->outstanding > 0.01);
                            $overdue = ($is_open && $i->days_overdue > 0);
                            $pbadge  = $i->payment_status == 'Paid' ? 'success' : ($i->payment_status == 'Partial' ? 'warning' : 'danger');
                        ?>
                            <tr class="<?= $overdue ? 'table-danger' : ''; ?>">
                                <td><a href="<?= base_url('arinvoice/view/' . $i->id); ?>"><strong><?= htmlspecialchars($i->invoice_no); ?></strong></a></td>
                                <td>
                                    <?= date('d-m-Y', strtotime($i->invoice_date)); ?><br>
                                    <small class="text-muted">JT: <?= date('d-m-Y', strtotime($i->due_date)); ?></small>
                                </td>
                                <td><?= htmlspecialchars($i->nama_customer); ?></td>
                                <td><span class="badge badge-<?= $i->source_type == 'FG_DELIVERY' ? 'info' : 'secondary'; ?>"><?= $i->source_type == 'FG_DELIVERY' ? 'FG' : 'Scrap'; ?></span></td>
                                <td class="text-right"><?= $i->currency; ?> <?= number_format($i->total_invoice, 2); ?></td>
                                <td class="text-right text-success"><?= number_format($i->total_paid, 2); ?></td>
                                <td class="text-right font-weight-bold <?= $is_open ? 'text-danger' : ''; ?>"><?= number_format($i->outstanding, 2); ?></td>
                                <td class="text-right"><?= number_format($i->outstanding_local, 0); ?></td>
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

    <!-- ===== Pengiriman dikirim belum difaktur ===== -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-exclamation-triangle mr-1"></i> Sudah Dikirim Tapi Belum Difaktur (Piutang Belum Tercatat)</h6>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-2">Pengiriman barang jadi (FG Delivery) / penjualan scrap yang sudah Confirmed namun belum dibuatkan Faktur Piutang. Buat faktur lewat menu <strong>Receivable Invoice</strong>. Nilai bersifat estimasi (qty × harga kontrak).</p>
            <div class="table-responsive">
                <table class="table table-bordered table-sm" width="100%">
                    <thead class="thead-light">
                        <tr>
                            <th>Sumber</th>
                            <th>No. Dokumen</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Referensi</th>
                            <th class="text-right">Nilai (estimasi)</th>
                            <th class="text-center">Umur (hari)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($uninvoiced_delivery)): ?>
                            <tr><td colspan="7" class="text-center text-muted">Semua pengiriman sudah difaktur. 🎉</td></tr>
                        <?php else: foreach ($uninvoiced_delivery as $d): ?>
                            <tr>
                                <td><span class="badge badge-<?= $d->source_type == 'FG_DELIVERY' ? 'info' : 'secondary'; ?>"><?= $d->source_type == 'FG_DELIVERY' ? 'FG Delivery' : 'Scrap Out'; ?></span></td>
                                <td><strong><?= htmlspecialchars($d->doc_no); ?></strong></td>
                                <td><?= date('d-m-Y', strtotime($d->doc_date)); ?></td>
                                <td><?= htmlspecialchars($d->nama_customer); ?></td>
                                <td><small><?= $d->ref_no ? htmlspecialchars($d->ref_no) : '-'; ?></small></td>
                                <td class="text-right"><?= $d->currency; ?> <?= number_format($d->est_value, 2); ?></td>
                                <td class="text-center"><?= (int) $d->age_days; ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
