<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">

    <!-- main JS: Chart, Trace modal and Select2 init -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const nf = new Intl.NumberFormat('id-ID');

        // Load chart data and render chart
        fetch('<?= base_url('dashboard/sc_chart_data'); ?>')
            .then(r => r.json())
            .then(data => {
                const labels = data.map(d => d.sc_number);
                const po = data.map(d => d.po_qty);
                const prod = data.map(d => d.produced_qty);
                const del = data.map(d => d.delivered_qty);

                const ctx = document.getElementById('scChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'PO Qty', data: po, backgroundColor: 'rgba(54,162,235,0.6)' },
                            { label: 'Produced', data: prod, backgroundColor: 'rgba(75,192,192,0.6)' },
                            { label: 'Delivered', data: del, backgroundColor: 'rgba(255,159,64,0.6)' }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) label += ': ';
                                        label += nf.format(context.parsed.y || 0);
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: { y: { beginAtZero: true, ticks: { callback: function(v){ return nf.format(v); } } } }
                    }
                });
            }).catch(err => console.error('SC chart load error', err));

        // Initialize Select2 (ajax) for SC select if Select2 is available
        try {
            if (window.jQuery && jQuery().select2) {
                $('#sc_select_trace').select2({
                    theme: 'bootstrap-5',
                    placeholder: '-- Pilih Sales Contract --',
                    allowClear: true,
                    ajax: {
                        url: '<?= base_url('dashboard/sc_search'); ?>',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) { return { q: params.term }; },
                        processResults: function(data) { return data; },
                        cache: true
                    },
                    minimumInputLength: 1
                });
            }
        } catch (e) { console.warn('Select2 init failed', e); }

        // Trace modal load
        document.getElementById('btnLoadTrace').addEventListener('click', function(){
            const scId = document.getElementById('sc_select_trace').value;
            if (!scId) { alert('Pilih Sales Contract dulu'); return; }
            const target = document.getElementById('traceContent');
            target.innerHTML = '<p class="text-muted">Loading...</p>';
            fetch('<?= base_url('dashboard/sc_trace/'); ?>' + scId)
                .then(r => r.json())
                .then(json => {
                    let html = '';
                    html += '<h5>Sales Contract: ' + (json.sc.sc_number || '') + '</h5>';

                    // MR (Material Requests) + Issuances
                    html += '<div class="mb-3 mt-3"><strong>Material Requests (MR) & Issuances</strong></div>';
                    if (!json.mrs || json.mrs.length == 0) {
                        html += '<p class="text-muted">No Material Requests found for this SC.</p>';
                    } else {
                        json.mrs.forEach(function(mr){
                            html += '<div class="card mb-2"><div class="card-body p-2">';
                            html += '<div class="d-flex justify-content-between"><div><strong>' + (mr.mr_number||'') + '</strong> <small class="text-muted">' + (mr.request_date||'') + '</small></div>';
                            html += '<div><small>Status: ' + (mr.status||'') + '</small> ';
                            if (mr.issuance_number) html += '<span class="badge bg-info ms-2">Issuance: ' + mr.issuance_number + '</span>';
                            html += '</div></div>';

                            // MR details table
                            if (mr.details && mr.details.length > 0) {
                                html += '<div class="table-responsive mt-2"><table class="table table-sm mb-0"><thead><tr><th>WO</th><th>Item</th><th>Req</th><th>Issued</th><th>Unit</th></tr></thead><tbody>';
                                mr.details.forEach(function(d){
                                    html += '<tr><td>' + (d.wo_number||'') + '</td><td>' + (d.item_name||'') + '</td><td>' + (d.requested_qty||0) + '</td><td>' + (d.issued_qty||0) + '</td><td>' + (d.item_unit||'') + '</td></tr>';
                                });
                                html += '</tbody></table></div>';
                            }

                            html += '</div></div>';
                        });
                    }

                    // Work Orders + Productions (with inputs/outputs)
                    html += '<div class="mb-3 mt-4"><strong>Work Orders & Production Steps</strong></div>';
                    if (json.work_orders.length == 0) html += '<p class="text-muted">No work orders found.</p>';
                    else {
                        json.work_orders.forEach(function(wo){
                            html += '<div class="card mb-2"><div class="card-body p-2">';
                            html += '<div class="d-flex justify-content-between"><div><strong><a href="<?= base_url('work_order/detail/'); ?>' + wo.id + '" target="_blank">' + (wo.wo_number||'') + '</a></strong> <small class="text-muted">' + (wo.product_name||'') + '</small></div>';
                            html += '<div><small>Produced: ' + (wo.produced_qty||0) + '</small> <small class="ms-2">Status: ' + (wo.status||'') + '</small></div></div>';

                            // productions
                            if (wo.productions && wo.productions.length > 0) {
                                wo.productions.forEach(function(p){
                                    html += '<div class="mt-2"><div class="fw-bold">' + (p.production_number||'') + ' <small class="text-muted">(' + (p.status||'') + ')</small></div>';
                                    html += '<div class="row">';
                                    html += '<div class="col-md-6">';
                                    html += '<div class="text-sm text-muted">Inputs</div>';
                                    if (p.inputs && p.inputs.length > 0) {
                                        html += '<table class="table table-sm mb-0"><thead><tr><th>Item</th><th>Qty</th><th>Batch</th></tr></thead><tbody>';
                                        p.inputs.forEach(function(i){
                                            html += '<tr><td>' + (i.item_type||'') + ' #' + (i.item_id||'') + '</td><td>' + (i.qty_used||0) + '</td><td>' + (i.stock_on_hand_id||'') + '</td></tr>';
                                        });
                                        html += '</tbody></table>';
                                    } else { html += '<div class="text-muted small">No inputs recorded.</div>'; }
                                    html += '</div>';

                                    html += '<div class="col-md-6">';
                                    html += '<div class="text-sm text-muted">Outputs</div>';
                                    if (p.outputs && p.outputs.length > 0) {
                                        html += '<table class="table table-sm mb-0"><thead><tr><th>Item</th><th>Qty</th></tr></thead><tbody>';
                                        p.outputs.forEach(function(o){
                                            html += '<tr><td>' + (o.item_type||'') + ' #' + (o.item_id||'') + '</td><td>' + (o.qty_produced||0) + '</td></tr>';
                                        });
                                        html += '</tbody></table>';
                                    } else { html += '<div class="text-muted small">No outputs recorded.</div>'; }
                                    html += '</div>';
                                    html += '</div>';
                                    html += '</div>';
                                });
                            }

                            html += '</div></div>';
                        });
                    }

                    // Deliveries
                    html += '<div class="mb-3 mt-4"><strong>Deliveries</strong></div>';
                    if (json.deliveries.length == 0) html += '<p class="text-muted">No deliveries found.</p>';
                    else {
                        html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>DO Number</th><th>Date</th><th>Qty Shipped</th></tr></thead><tbody>';
                        json.deliveries.forEach(function(d){
                            html += '<tr><td><a href="<?= base_url('fg_delivery/show/'); ?>' + d.header_id + '" target="_blank">' + (d.delivery_number||'') + '</a></td><td>' + (d.delivery_date||'') + '</td><td>' + (d.total_shipped||0) + '</td></tr>';
                        });
                        html += '</tbody></table></div>';
                    }

                    target.innerHTML = html;
                }).catch(err => { target.innerHTML = '<p class="text-danger">Error loading trace.</p>'; console.error(err); });
        });
    });
    </script>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Pengiriman (Hari Ini)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $shipments_today_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shipping-fast fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Pengguna</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $user_count; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Receivables (Outstanding)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($total_receivables ?? 0, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-dark shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                Cash Balance (kas & bank)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($cash_balance ?? 0, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Pending DO (Draft)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $pending_do_count ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Confirmed DO</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $confirmed_do_count ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Inventory (FG / WIP)</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800">FG: <?= number_format($inventory_fg_qty ?? 0, 2); ?> | WIP: <?= number_format($inventory_wip_qty ?? 0, 2); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="row">

        <div class="col-lg-12 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sales Contract Traceability</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <canvas id="scChart" height="120"></canvas>
                    </div>
                    <?php if (empty($sc_summary)): ?>
                        <p class="text-center text-muted">Belum ada data Sales Contract.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>SC Number</th>
                                        <th>Customer</th>
                                        <th>Style</th>
                                        <th>SC Qty</th>
                                        <th>Produced Qty</th>
                                        <th>Delivered Qty</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sc_summary as $sc): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= base_url('sales_contract/detail/' . $sc->sc_id); ?>">
                                                <?= html_escape($sc->sc_number); ?>
                                            </a>
                                        </td>
                                        <td><?= html_escape($sc->nama_customer); ?></td>
                                        <td><?= html_escape($sc->style); ?></td>
                                        <td><?= number_format($sc->sc_qty, 2); ?></td>
                                        <td><?= number_format($sc->produced_qty ?? 0, 2); ?></td>
                                        <td><?= number_format($sc->delivered_qty ?? 0, 2); ?></td>
                                        <td>
                                            <?php 
                                                $progress = 0;
                                                if ($sc->sc_qty > 0) {
                                                    $progress = ($sc->delivered_qty / $sc->sc_qty) * 100;
                                                }
                                            ?>
                                            <div class="progress">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $progress; ?>%" aria-valuenow="<?= $progress; ?>" aria-valuemin="0" aria-valuemax="100"><?= round($progress, 2); ?>%</div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                                <a href="<?= base_url('sales_contract'); ?>" class="btn-load">Lihat semua Sales Contract &rarr;</a>
                                <button id="openTraceModal" class="btn btn-secondary ms-2" data-bs-toggle="modal" data-bs-target="#scTraceModal">Trace SC (pilih di tabel)</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- SC Trace Modal -->
<div class="modal fade" id="scTraceModal" tabindex="-1" aria-labelledby="scTraceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Receivables (Outstanding)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($total_receivables ?? 0, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card border-left-dark shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                Cash Balance (kas & bank)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">Rp <?= number_format($cash_balance ?? 0, 2, ',', '.'); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-wallet fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    fetch('<?= base_url('dashboard/sc_chart_data'); ?>')
    
    <!-- MAIN SC CHART: moved to main dashboard area -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Sales Contract: Ordered vs Produced vs Delivered (Recent)</strong>
                </div>
                <div class="card-body">
                    <canvas id="scChart" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>
        .then(r => r.json())
        .then(data => {
            const labels = data.map(d => d.sc_number);
            const po = data.map(d => d.po_qty);
            const prod = data.map(d => d.produced_qty);
            const del = data.map(d => d.delivered_qty);

            const ctx = document.getElementById('scChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'PO Qty', data: po, backgroundColor: 'rgba(54,162,235,0.6)' },
                        { label: 'Produced', data: prod, backgroundColor: 'rgba(75,192,192,0.6)' },
                        { label: 'Delivered', data: del, backgroundColor: 'rgba(255,159,64,0.6)' }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true } }
                }
            });
        }).catch(err => console.error('SC chart load error', err));

    // Trace modal load
    document.getElementById('btnLoadTrace').addEventListener('click', function(){
        const scId = document.getElementById('sc_select_trace').value;
        if (!scId) { alert('Pilih Sales Contract dulu'); return; }
        const target = document.getElementById('traceContent');
        target.innerHTML = '<p class="text-muted">Loading...</p>';
        fetch('<?= base_url('dashboard/sc_trace/'); ?>' + scId)
            .then(r => r.json())
            .then(json => {
                let html = '';
                html += '<h5>Sales Contract: ' + (json.sc.sc_number || '') + '</h5>';

                // MR (Material Requests) + Issuances
                html += '<div class="mb-3 mt-3"><strong>Material Requests (MR) & Issuances</strong></div>';
                if (!json.mrs || json.mrs.length == 0) {
                    html += '<p class="text-muted">No Material Requests found for this SC.</p>';
                } else {
                    json.mrs.forEach(function(mr){
                        html += '<div class="card mb-2"><div class="card-body p-2">';
                        html += '<div class="d-flex justify-content-between"><div><strong>' + (mr.mr_number||'') + '</strong> <small class="text-muted">' + (mr.request_date||'') + '</small></div>';
                        html += '<div><small>Status: ' + (mr.status||'') + '</small> ';
                        if (mr.issuance_number) html += '<span class="badge bg-info ms-2">Issuance: ' + mr.issuance_number + '</span>';
                        html += '</div></div>';

                        // MR details table
                        if (mr.details && mr.details.length > 0) {
                            html += '<div class="table-responsive mt-2"><table class="table table-sm mb-0"><thead><tr><th>WO</th><th>Item</th><th>Req</th><th>Issued</th><th>Unit</th></tr></thead><tbody>';
                            mr.details.forEach(function(d){
                                html += '<tr><td>' + (d.wo_number||'') + '</td><td>' + (d.item_name||'') + '</td><td>' + (d.requested_qty||0) + '</td><td>' + (d.issued_qty||0) + '</td><td>' + (d.item_unit||'') + '</td></tr>';
                            });
                            html += '</tbody></table></div>';
                        }

                        html += '</div></div>';
                    });
                }

                // Work Orders + Productions (with inputs/outputs)
                html += '<div class="mb-3 mt-4"><strong>Work Orders & Production Steps</strong></div>';
                if (json.work_orders.length == 0) html += '<p class="text-muted">No work orders found.</p>';
                else {
                    json.work_orders.forEach(function(wo){
                        html += '<div class="card mb-2"><div class="card-body p-2">';
                        html += '<div class="d-flex justify-content-between"><div><strong><a href="<?= base_url('work_order/detail/'); ?>' + wo.id + '" target="_blank">' + (wo.wo_number||'') + '</a></strong> <small class="text-muted">' + (wo.product_name||'') + '</small></div>';
                        html += '<div><small>Produced: ' + (wo.produced_qty||0) + '</small> <small class="ms-2">Status: ' + (wo.status||'') + '</small></div></div>';

                        // productions
                        if (wo.productions && wo.productions.length > 0) {
                            wo.productions.forEach(function(p){
                                html += '<div class="mt-2"><div class="fw-bold">' + (p.production_number||'') + ' <small class="text-muted">(' + (p.status||'') + ')</small></div>';
                                html += '<div class="row">';
                                html += '<div class="col-md-6">';
                                html += '<div class="text-sm text-muted">Inputs</div>';
                                if (p.inputs && p.inputs.length > 0) {
                                    html += '<table class="table table-sm mb-0"><thead><tr><th>Item</th><th>Qty</th><th>Batch</th></tr></thead><tbody>';
                                    p.inputs.forEach(function(i){
                                        html += '<tr><td>' + (i.item_type||'') + ' #' + (i.item_id||'') + '</td><td>' + (i.qty_used||0) + '</td><td>' + (i.stock_on_hand_id||'') + '</td></tr>';
                                    });
                                    html += '</tbody></table>';
                                } else { html += '<div class="text-muted small">No inputs recorded.</div>'; }
                                html += '</div>'; // col

                                html += '<div class="col-md-6">';
                                html += '<div class="text-sm text-muted">Outputs</div>';
                                if (p.outputs && p.outputs.length > 0) {
                                    html += '<table class="table table-sm mb-0"><thead><tr><th>Item</th><th>Qty</th></tr></thead><tbody>';
                                    p.outputs.forEach(function(o){
                                        html += '<tr><td>' + (o.item_type||'') + ' #' + (o.item_id||'') + '</td><td>' + (o.qty_produced||0) + '</td></tr>';
                                    });
                                    html += '</tbody></table>';
                                } else { html += '<div class="text-muted small">No outputs recorded.</div>'; }
                                html += '</div>'; // col
                                html += '</div>'; // row
                                html += '</div>'; // production
                            });
                        }

                        html += '</div></div>';
                    });
                }

                // Deliveries
                html += '<div class="mb-3 mt-4"><strong>Deliveries</strong></div>';
                if (json.deliveries.length == 0) html += '<p class="text-muted">No deliveries found.</p>';
                else {
                    html += '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>DO Number</th><th>Date</th><th>Qty Shipped</th></tr></thead><tbody>';
                    json.deliveries.forEach(function(d){
                        html += '<tr><td><a href="<?= base_url('fg_delivery/show/'); ?>' + d.header_id + '" target="_blank">' + (d.delivery_number||'') + '</a></td><td>' + (d.delivery_date||'') + '</td><td>' + (d.total_shipped||0) + '</td></tr>';
                    });
                    html += '</tbody></table></div>';
                }

                target.innerHTML = html;
            }).catch(err => { target.innerHTML = '<p class="text-danger">Error loading trace.</p>'; console.error(err); });
    });
});
</script>

<div class="container-fluid mt-2">
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Low-stock Alerts (Finished Goods)</strong>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_alerts)): ?>
                        <p class="text-muted">Tidak ada alert low-stock saat ini.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>FG Code</th>
                                        <th>Nama Barang</th>
                                        <th class="text-end">Qty</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock_alerts as $l): ?>
                                    <tr>
                                        <td><?= html_escape($l->fg_code ?? ''); ?></td>
                                        <td><?= html_escape($l->fg_name ?? ''); ?></td>
                                        <td class="text-end"><?= number_format($l->current_stock ?? 0, 2); ?></td>
                                        <td class="text-end"><a href="<?= base_url('fg_stock/detail/' . ($l->fg_id ?? '')); ?>" class="btn btn-sm btn-outline-primary">Kartu Stok</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>